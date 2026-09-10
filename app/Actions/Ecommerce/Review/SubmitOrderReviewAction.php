<?php

namespace App\Actions\Ecommerce\Review;

use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitOrderReviewAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploadedImages
     * @return Collection<int, OrderReview>
     */
    public function handle(OrderShop $orderShop, array $data, array $uploadedImages, User $reviewer): Collection
    {
        try {
            return DB::transaction(function () use ($orderShop, $data, $uploadedImages, $reviewer): Collection {
                $lockedOrderShop = OrderShop::query()
                    ->whereKey($orderShop->getKey())
                    ->whereHas('order', fn (Builder $query) => $query->where('user_id', $reviewer->id))
                    ->with(['items.productFlat.product', 'shop'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOrderShop->shipping_status || blank($lockedOrderShop->waybill_number)) {
                    throw ValidationException::withMessages([
                        'reviewData' => 'Pesanan belum sampai.',
                    ]);
                }

                $createdReviews = collect();

                foreach ($lockedOrderShop->items as $item) {
                    $key = "shopitem__{$item->id}";

                    if (! array_key_exists($key, $data)) {
                        continue;
                    }

                    $product = $item->productFlat?->product;

                    if ($product === null) {
                        throw ValidationException::withMessages([
                            "reviewData.{$key}" => 'Produk pesanan tidak ditemukan.',
                        ]);
                    }

                    $createdReviews->push($this->createReview(
                        orderShop: $lockedOrderShop,
                        reviewer: $reviewer,
                        reviewable: $product,
                        reviewData: $this->validateReviewData($data[$key], $key),
                        uploadedImages: $this->validateUploadedImages($uploadedImages[$key] ?? [], $key),
                    ));
                }

                $shopKey = "shop__{$lockedOrderShop->shop_id}";

                if (array_key_exists($shopKey, $data)) {
                    $createdReviews->push($this->createReview(
                        orderShop: $lockedOrderShop,
                        reviewer: $reviewer,
                        reviewable: $lockedOrderShop->shop,
                        reviewData: $this->validateReviewData($data[$shopKey], $shopKey),
                        uploadedImages: $this->validateUploadedImages($uploadedImages[$shopKey] ?? [], $shopKey),
                    ));
                }

                if ($createdReviews->isEmpty()) {
                    throw ValidationException::withMessages([
                        'reviewData' => 'Tidak ada ulasan yang dapat disimpan.',
                    ]);
                }

                return $createdReviews;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'reviewData' => 'Anda sudah memberikan ulasan untuk pesanan ini.',
            ]);
        }
    }

    /**
     * @return array{rating: int|float, comment: string|null}
     */
    private function validateReviewData(mixed $reviewData, string $key): array
    {
        return Validator::make(
            ['review' => $reviewData],
            [
                'review' => ['required', 'array'],
                'review.rating' => ['required', 'numeric', 'between:1,5', 'multiple_of:0.5'],
                'review.comment' => ['nullable', 'string', 'max:2000'],
            ],
            [],
            [
                'review.rating' => "rating {$key}",
                'review.comment' => "komentar {$key}",
            ],
        )->validate()['review'];
    }

    /** @return array<int, mixed> */
    private function validateUploadedImages(mixed $uploadedImages, string $key): array
    {
        if (! is_array($uploadedImages)) {
            throw ValidationException::withMessages([
                "images.{$key}" => 'Data gambar ulasan tidak valid.',
            ]);
        }

        if (count($uploadedImages) > 5) {
            throw ValidationException::withMessages([
                "images.{$key}" => 'Maksimal 5 foto per ulasan.',
            ]);
        }

        return $uploadedImages;
    }

    /**
     * @param  array{rating: int|float, comment: string|null}  $reviewData
     * @param  array<int, mixed>  $uploadedImages
     */
    private function createReview(
        OrderShop $orderShop,
        User $reviewer,
        Model $reviewable,
        array $reviewData,
        array $uploadedImages,
    ): OrderReview {
        $alreadyReviewed = OrderReview::query()
            ->where('user_id', $reviewer->id)
            ->where('order_shop_id', $orderShop->id)
            ->where('reviewable_type', $reviewable->getMorphClass())
            ->where('reviewable_id', $reviewable->getKey())
            ->exists();

        if ($alreadyReviewed) {
            throw ValidationException::withMessages([
                'reviewData' => 'Anda sudah memberikan ulasan untuk pesanan ini.',
            ]);
        }

        $review = OrderReview::query()->create([
            'user_id' => $reviewer->id,
            'order_shop_id' => $orderShop->id,
            'reviewable_type' => $reviewable->getMorphClass(),
            'reviewable_id' => $reviewable->getKey(),
            'rating' => $reviewData['rating'],
            'comment' => $reviewData['comment'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($uploadedImages as $image) {
            $review->addMedia($image)->toMediaCollection('review_images');
        }

        return $review;
    }
}

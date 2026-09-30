<?php

namespace App\Actions\Ecommerce\Review;

use App\Data\Review\ReviewData;
use App\Data\Review\ReviewSubmissionData;
use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitOrderReviewAction
{
    /**
     * @return Collection<int, OrderReview>
     */
    public function handle(OrderShop $orderShop, ReviewSubmissionData $data, User $reviewer): Collection
    {
        try {
            return DB::transaction(function () use ($orderShop, $data, $reviewer): Collection {
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

                    if (! array_key_exists($key, $data->reviews)) {
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
                        reviewData: $data->reviews[$key],
                    ));
                }

                $shopKey = "shop__{$lockedOrderShop->shop_id}";

                if (array_key_exists($shopKey, $data->reviews)) {
                    $createdReviews->push($this->createReview(
                        orderShop: $lockedOrderShop,
                        reviewer: $reviewer,
                        reviewable: $lockedOrderShop->shop,
                        reviewData: $data->reviews[$shopKey],
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

    private function createReview(
        OrderShop $orderShop,
        User $reviewer,
        Model $reviewable,
        ReviewData $reviewData,
    ): OrderReview {
        $reviewData->validate();
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
            'rating' => $reviewData->rating,
            'comment' => $reviewData->comment,
            'status' => 'pending',
        ]);

        foreach ($reviewData->images as $image) {
            $review->addMedia($image)->toMediaCollection('review_images');
        }

        return $review;
    }
}

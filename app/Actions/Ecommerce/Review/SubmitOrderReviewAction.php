<?php

namespace App\Actions\Ecommerce\Review;

use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Traits\WithMediaCollection;
use Illuminate\Support\Facades\DB;

class SubmitOrderReviewAction
{
    use WithMediaCollection;

    public function handle(OrderShop $orderShop, array $data, array $uploadedImages)
    {
        // 1. Validate order status
        if (! $orderShop->shipping_status || $orderShop->waybill_number === null) {
            throw new \Exception('Pesanan belum sampai.');
        }

        // 2. Validate user hasn't reviewed yet
        $hasReviewed = OrderReview::where('order_shop_id', $orderShop->id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($hasReviewed) {
            throw new \Exception('Anda sudah memberikan ulasan untuk pesanan ini.');
        }

        return DB::transaction(function () use ($orderShop, $data, $uploadedImages) {
            $createdReviews = collect();

            // Store items reviews
            foreach ($orderShop->items as $item) {
                $key = "App\\Models\\Order\\OrderShopItem__{$item->id}";

                if (isset($data[$key])) {
                    $reviewData = $data[$key];

                    $review = OrderReview::create([
                        'user_id' => auth()->id(),
                        'order_shop_id' => $orderShop->id,
                        'reviewable_type' => 'App\\Models\\Order\\OrderShopItem',
                        'reviewable_id' => $item->id,
                        'rating' => $reviewData['rating'],
                        'comment' => $reviewData['comment'] ?? null,
                        'status' => 'pending',
                    ]);

                    // Store images if exist
                    if (isset($uploadedImages[$key]) && is_array($uploadedImages[$key])) {
                        foreach ($uploadedImages[$key] as $image) {
                            $review->addMedia($image)->toMediaCollection('review_images');
                        }
                    }

                    $createdReviews->push($review);
                }
            }

            // Store shop review
            $shopKey = "App\\Models\\Shop\\Shop__{$orderShop->shop_id}";
            if (isset($data[$shopKey])) {
                $shopReviewData = $data[$shopKey];

                $shopReview = OrderReview::create([
                    'user_id' => auth()->id(),
                    'order_shop_id' => $orderShop->id,
                    'reviewable_type' => 'App\\Models\\Shop\\Shop',
                    'reviewable_id' => $orderShop->shop_id,
                    'rating' => $shopReviewData['rating'],
                    'comment' => $shopReviewData['comment'] ?? null,
                    'status' => 'pending',
                ]);

                // Store images if exist
                if (isset($uploadedImages[$shopKey]) && is_array($uploadedImages[$shopKey])) {
                    foreach ($uploadedImages[$shopKey] as $image) {
                        $shopReview->addMedia($image)->toMediaCollection('review_images');
                    }
                }

                $createdReviews->push($shopReview);
            }

            return $createdReviews;
        });
    }
}

<?php

namespace App\Actions\Ecommerce\Review;

use App\Models\Order\OrderReview;
use Illuminate\Support\Facades\DB;

class DeleteOrderReviewAction
{
    public function execute(OrderReview $review)
    {
        return DB::transaction(function () use ($review) {
            $oldStatus = $review->status;
            $reviewable = $review->reviewable;

            $review->clearMediaCollection('review_images');
            $review->delete();

            // Recalculate target rating if it was previously approved
            if ($oldStatus === 'approved' && $reviewable) {
                $this->recalculateRating($reviewable);
            }

            return true;
        });
    }

    protected function recalculateRating($reviewable)
    {
        if (! $reviewable) {
            return;
        }

        $reviews = OrderReview::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->where('status', 'approved');

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? $reviews->avg('rating') : 0;

        $reviewable->update([
            'rating' => round($averageRating, 2),
            'total_reviews' => $totalReviews,
        ]);
    }
}

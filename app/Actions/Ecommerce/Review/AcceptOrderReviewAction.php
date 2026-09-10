<?php

namespace App\Actions\Ecommerce\Review;

use App\Models\Order\OrderReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AcceptOrderReviewAction
{
    public function execute(OrderReview $review, User $moderator): OrderReview
    {
        return DB::transaction(function () use ($review, $moderator): OrderReview {
            $lockedReview = $this->accessibleReview($review, $moderator);

            $lockedReview->update(['status' => 'approved']);
            $this->recalculateRating($lockedReview->reviewable);

            return $lockedReview;
        });
    }

    private function accessibleReview(OrderReview $review, User $moderator): OrderReview
    {
        return OrderReview::query()
            ->whereKey($review->getKey())
            ->whereHas('orderShop', fn (Builder $query) => $query->accessibleTo($moderator))
            ->with('reviewable')
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function recalculateRating(?Model $reviewable): void
    {
        if ($reviewable === null) {
            return;
        }

        $reviews = OrderReview::query()
            ->where('reviewable_type', $reviewable->getMorphClass())
            ->where('reviewable_id', $reviewable->getKey())
            ->where('status', 'approved');

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? $reviews->avg('rating') : 0;

        $reviewable->update([
            'rating' => round((float) $averageRating, 2),
            'total_reviews' => $totalReviews,
        ]);
    }
}

<?php

use App\Actions\Ecommerce\Review\AcceptOrderReviewAction;
use App\Actions\Ecommerce\Review\DeleteOrderReviewAction;
use App\Actions\Ecommerce\Review\RejectOrderReviewAction;
use App\Livewire\BaseComponent;
use App\Models\Order\OrderReview;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

new class extends BaseComponent
{
    #[Url]
    public string $status = 'pending';

    public function mount(): void
    {
        $this->paginationOrderBy = 'created_at';
        $this->paginationOrder = 'desc';
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    #[Computed]
    public function data(): LengthAwarePaginator
    {
        $query = $this->accessibleReviews($this->currentUser())
            ->with(['user', 'reviewable', 'media'])
            ->when($this->status !== 'semua', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->search, function ($query) {
                $query->where(function (Builder $searchQuery): void {
                    $searchQuery
                        ->whereHas('user', function (Builder $userQuery): void {
                            $userQuery->where(function (Builder $userSearchQuery): void {
                                $userSearchQuery
                                    ->where('name', 'like', '%'.$this->search.'%')
                                    ->orWhere('email', 'like', '%'.$this->search.'%');
                            });
                        })
                        ->orWhere('comment', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy($this->paginationOrderBy, $this->paginationOrder);

        return $query->paginate($this->paginate);
    }

    #[On('accept')]
    public function accept(int $id, AcceptOrderReviewAction $action): void
    {
        try {
            $user = $this->currentUser();
            $review = $this->accessibleReviews($user)->findOrFail($id);
            $action->execute($review, $user);

            unset($this->data);

            $this->dispatch('toast',
                type: 'success',
                message: 'Review accepted successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to accept review. Please try again.'
            );
        }
    }

    #[On('reject')]
    public function reject(int $id, RejectOrderReviewAction $action): void
    {
        try {
            $user = $this->currentUser();
            $review = $this->accessibleReviews($user)->findOrFail($id);
            $action->execute($review, $user);

            unset($this->data);

            $this->dispatch('toast',
                type: 'success',
                message: 'Review rejected successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to reject review. Please try again.'
            );
        }
    }

    #[On('delete')]
    public function delete(int $id, DeleteOrderReviewAction $action): void
    {
        try {
            $user = $this->currentUser();
            $review = $this->accessibleReviews($user)->findOrFail($id);
            $action->execute($review, $user);

            unset($this->data);

            $this->dispatch('toast',
                type: 'success',
                message: 'Review deleted successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to delete review. Please try again.'
            );
        }
    }

    private function accessibleReviews(User $user): Builder
    {
        return OrderReview::query()
            ->whereHas('orderShop', fn (Builder $query) => $query->accessibleTo($user));
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
};

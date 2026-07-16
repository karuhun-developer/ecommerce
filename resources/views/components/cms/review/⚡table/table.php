<?php

use App\Actions\Ecommerce\Review\AcceptOrderReviewAction;
use App\Actions\Ecommerce\Review\DeleteOrderReviewAction;
use App\Actions\Ecommerce\Review\RejectOrderReviewAction;
use App\Livewire\BaseComponent;
use App\Models\Order\OrderReview;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

new class extends BaseComponent
{
    #[Url]
    public $status = 'pending';

    public function mount()
    {
        $this->paginationOrderBy = 'created_at';
        $this->paginationOrder = 'desc';
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage();
    }

    #[Computed]
    public function data()
    {
        $query = OrderReview::with(['user', 'reviewable', 'media'])
            ->when($this->status !== 'semua', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                })->orWhere('comment', 'like', '%'.$this->search.'%');
            })
            ->orderBy($this->paginationOrderBy, $this->paginationOrder);

        return $query->paginate($this->paginate);
    }

    #[On('accept')]
    public function accept($id, AcceptOrderReviewAction $action)
    {
        try {
            $review = OrderReview::findOrFail($id);
            $action->execute($review);

            $this->dispatch('toast',
                type: 'success',
                message: 'Review accepted successfully.'
            );
        } catch (Exception $e) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to accept review: '.$e->getMessage()
            );
        }
    }

    #[On('reject')]
    public function reject($id, RejectOrderReviewAction $action)
    {
        try {
            $review = OrderReview::findOrFail($id);
            $action->execute($review);

            $this->dispatch('toast',
                type: 'success',
                message: 'Review rejected successfully.'
            );
        } catch (Exception $e) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to reject review: '.$e->getMessage()
            );
        }
    }

    #[On('delete')]
    public function delete($id, DeleteOrderReviewAction $action)
    {
        try {
            $review = OrderReview::findOrFail($id);
            $action->execute($review);

            $this->dispatch('toast',
                type: 'success',
                message: 'Review deleted successfully.'
            );
        } catch (Exception $e) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to delete review: '.$e->getMessage()
            );
        }
    }
};

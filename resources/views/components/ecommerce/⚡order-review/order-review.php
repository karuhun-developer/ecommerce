<?php

use App\Actions\Ecommerce\Review\SubmitOrderReviewAction;
use App\Livewire\Forms\ReviewForm;
use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public OrderShop $orderShop;

    public ReviewForm $form;

    public function mount(OrderShop $orderShop): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $this->orderShop = OrderShop::query()
            ->whereKey($orderShop->getKey())
            ->whereHas('order', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with(['items.productFlat', 'shop'])
            ->firstOrFail();

        $this->initializeData();
    }

    private function initializeData(): void
    {
        if ($this->hasAlreadyReviewed) {
            return;
        }

        foreach ($this->orderShop->items as $item) {
            $key = "shopitem__{$item->id}";
            $this->form->reviewData[$key] = [
                'rating' => 5,
                'comment' => '',
            ];
            $this->form->images[$key] = [];
        }

        if ($this->orderShop->shop) {
            $shopKey = "shop__{$this->orderShop->shop_id}";
            $this->form->reviewData[$shopKey] = [
                'rating' => 5,
                'comment' => '',
            ];
            $this->form->images[$shopKey] = [];
        }
    }

    #[Computed]
    public function hasAlreadyReviewed(): bool
    {
        return OrderReview::where('order_shop_id', $this->orderShop->id)
            ->where('user_id', auth()->id())
            ->exists();
    }

    public function removeImage(string $key, int $index): void
    {
        if (isset($this->form->images[$key][$index])) {
            unset($this->form->images[$key][$index]);
            $this->form->images[$key] = array_values($this->form->images[$key]);
        }
    }

    public function submit(SubmitOrderReviewAction $action): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            $this->dispatch('toast', type: 'error', message: 'Anda harus masuk untuk memberikan ulasan.');

            return;
        }

        if ($this->hasAlreadyReviewed) {
            $this->dispatch('toast', type: 'error', message: 'Anda sudah memberikan ulasan.');

            return;
        }

        $data = $this->form->data();

        try {
            $action->handle(
                orderShop: $this->orderShop,
                data: $data,
                reviewer: $user,
            );

            $this->dispatch('toast', type: 'success', message: 'Ulasan berhasil dikirim! Menunggu persetujuan admin.');

            Flux::modal("review-modal-{$this->orderShop->id}")->close();

            unset($this->hasAlreadyReviewed);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast', type: 'error', message: 'Ulasan gagal dikirim. Silakan coba lagi.');
        }
    }
};

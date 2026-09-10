<?php

use App\Actions\Ecommerce\Review\SubmitOrderReviewAction;
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

    public array $reviewData = [];

    public array $images = [];

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
            $this->reviewData[$key] = [
                'rating' => 5,
                'comment' => '',
            ];
            $this->images[$key] = [];
        }

        if ($this->orderShop->shop) {
            $shopKey = "shop__{$this->orderShop->shop_id}";
            $this->reviewData[$shopKey] = [
                'rating' => 5,
                'comment' => '',
            ];
            $this->images[$shopKey] = [];
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
        if (isset($this->images[$key][$index])) {
            unset($this->images[$key][$index]);
            $this->images[$key] = array_values($this->images[$key]);
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

        $rules = [];

        foreach (array_keys($this->reviewData) as $key) {
            $rules["reviewData.{$key}"] = ['required', 'array'];
            $rules["reviewData.{$key}.rating"] = ['required', 'numeric', 'between:1,5', 'multiple_of:0.5'];
            $rules["reviewData.{$key}.comment"] = ['nullable', 'string', 'max:2000'];
            $rules["images.{$key}"] = ['array', 'max:5'];
            $rules["images.{$key}.*"] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        $this->validate($rules);

        try {
            $action->handle(
                orderShop: $this->orderShop,
                data: $this->reviewData,
                uploadedImages: $this->images,
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

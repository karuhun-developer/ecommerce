<?php

use App\Actions\Ecommerce\Review\SubmitOrderReviewAction;
use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Flux\Flux;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public OrderShop $orderShop;

    public $reviewData = [];

    public $images = [];

    public function mount(OrderShop $orderShop)
    {
        $this->orderShop = $orderShop->load(['items', 'shop']);

        $this->initializeData();
    }

    private function initializeData()
    {
        if ($this->hasAlreadyReviewed) {
            return;
        }

        // Initialize for items
        foreach ($this->orderShop->items as $item) {
            $key = "shopitem__{$item->id}";
            $this->reviewData[$key] = [
                'rating' => 5,
                'comment' => '',
            ];
            $this->images[$key] = [];
        }

        // Initialize for shop
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
    public function hasAlreadyReviewed()
    {
        if (! auth()->check()) {
            return false;
        }

        return OrderReview::where('order_shop_id', $this->orderShop->id)
            ->where('user_id', auth()->id())
            ->exists();
    }

    public function removeImage($key, $index)
    {
        if (isset($this->images[$key][$index])) {
            unset($this->images[$key][$index]);
            // Reindex array to prevent holes
            $this->images[$key] = array_values($this->images[$key]);
        }
    }

    public function submit(SubmitOrderReviewAction $action)
    {
        if ($this->hasAlreadyReviewed) {
            $this->dispatch('toast', type: 'error', message: 'Anda sudah memberikan ulasan.');

            return;
        }

        // Dynamic validation rules based on keys
        $rules = [];
        $messages = [];

        foreach ($this->reviewData as $key => $data) {
            $rules["reviewData.{$key}.rating"] = 'required|numeric|min:0|max:5';
            $rules["reviewData.{$key}.comment"] = 'nullable|string|max:2000';
            $rules["images.{$key}.*"] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120';

            // Limit to 5 images max
            if (isset($this->images[$key]) && count($this->images[$key]) > 5) {
                $this->addError("images.{$key}", 'Maksimal 5 foto per ulasan.');

                return;
            }
        }

        $this->validate($rules, $messages);

        try {
            $action->handle(
                orderShop: $this->orderShop,
                data: $this->reviewData,
                uploadedImages: $this->images
            );

            $this->dispatch('toast', type: 'success', message: 'Ulasan berhasil dikirim! Menunggu persetujuan admin.');


            // Close the modal after successful submission
            Flux::modal("review-modal-{$this->orderShop->id}")->close();

            // Re-render component state
            unset($this->hasAlreadyReviewed);
        } catch (Exception $e) {
            Log::error('Order review error', ['message' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }
};

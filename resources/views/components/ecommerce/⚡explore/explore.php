<?php

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'sort')]
    public string $sortBy = 'latest';

    #[Url(as: 'min')]
    public ?int $minPrice = null;

    #[Url(as: 'max')]
    public ?int $maxPrice = null;

    public bool $showFilter = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function selectCategory(?int $id): void
    {
        $this->categoryId = $this->categoryId === $id ? null : $id;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryId = null;
        $this->sortBy = 'latest';
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['mainProductFlat', 'category'])
            ->where('status', true)
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->categoryId, fn ($q) => $q->where('product_category_id', $this->categoryId))
            ->when($this->minPrice, fn ($q) => $q->where('price', '>=', $this->minPrice))
            ->when($this->maxPrice, fn ($q) => $q->where('price', '<=', $this->maxPrice))
            ->when($this->sortBy === 'latest', fn ($q) => $q->latest())
            ->when($this->sortBy === 'price_asc', fn ($q) => $q->orderBy('price', 'asc'))
            ->when($this->sortBy === 'price_desc', fn ($q) => $q->orderBy('price', 'desc'))
            ->when($this->sortBy === 'popular', fn ($q) => $q->orderBy('total_sales', 'desc'))
            ->when($this->sortBy === 'rating', fn ($q) => $q->orderBy('rating', 'desc'))
            ->paginate(24);
    }
};

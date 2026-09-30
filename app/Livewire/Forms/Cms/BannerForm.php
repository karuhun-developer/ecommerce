<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Content\BannerData;
use App\Models\Content\Banner;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class BannerForm extends Form
{
    public string $title = '';

    public ?string $subtitle = null;

    public string $image_alt = '';

    public ?string $cta_label = null;

    public ?string $cta_url = null;

    public bool $active = false;

    public int $sort_order = 0;

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public ?TemporaryUploadedFile $image = null;

    public function setBanner(Banner $banner): void
    {
        $this->fill($banner->only(['title', 'subtitle', 'image_alt', 'cta_label', 'cta_url', 'active', 'sort_order']));
        $this->starts_at = $banner->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $banner->ends_at?->format('Y-m-d\TH:i');
    }

    public function data(?Banner $banner): BannerData
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:2000'],
            'image_alt' => ['required', 'string', 'max:255'],
            'cta_label' => ['nullable', 'required_with:cta_url', 'string', 'max:100'],
            'cta_url' => ['nullable', 'required_with:cta_label', 'url:http,https', 'max:2048'],
            'active' => ['boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:99999'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => [$banner?->hasMedia('banner') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        return new BannerData($data['title'], $data['subtitle'] ?: null, $data['image_alt'], $data['cta_label'] ?: null, $data['cta_url'] ?: null, $data['active'], $data['sort_order'], $data['starts_at'] ? CarbonImmutable::parse($data['starts_at']) : null, $data['ends_at'] ? CarbonImmutable::parse($data['ends_at']) : null, $data['image']);
    }
}

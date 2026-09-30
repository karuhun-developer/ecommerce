<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Content\PageData;
use App\Models\Content\Page;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PageForm extends Form
{
    public string $title = '';

    public string $slug = '';

    public string $body = '';

    public bool $published = false;

    public ?string $footer_group = null;

    public int $sort_order = 0;

    public function setPage(Page $page): void
    {
        $this->fill($page->only(['title', 'slug', 'body', 'published', 'footer_group', 'sort_order']));
    }

    public function data(?Page $page): PageData
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'body' => ['required', 'string', 'max:200000'], 'published' => ['boolean'],
            'footer_group' => ['nullable', Rule::exists('footer_groups', 'key')],
            'sort_order' => ['required', 'integer', 'min:0', 'max:99999'],
        ]);

        return new PageData($data['title'], $data['slug'], $data['body'], $data['published'], $data['footer_group'] ?: null, $data['sort_order']);
    }
}

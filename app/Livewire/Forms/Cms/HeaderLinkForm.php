<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Content\HeaderLinkData;
use App\Models\Content\HeaderLink;
use Illuminate\Validation\Rule;
use Livewire\Form;

class HeaderLinkForm extends Form
{
    public string $label = '';

    public string $position = 'left';

    public string $destination = 'page';

    public ?int $page_id = null;

    public ?string $url = null;

    public bool $active = true;

    public int $sort_order = 0;

    public function setHeaderLink(HeaderLink $link): void
    {
        $this->fill($link->only(['label', 'position', 'destination', 'page_id', 'url', 'active', 'sort_order']));
    }

    public function data(): HeaderLinkData
    {
        $data = $this->validate([
            'label' => ['required', 'string', 'max:255'],
            'position' => ['required', Rule::in(['left', 'right'])],
            'destination' => ['required', Rule::in(['page', 'url'])],
            'page_id' => ['exclude_unless:destination,page', 'required', 'integer', Rule::exists('pages', 'id')],
            'url' => ['exclude_unless:destination,url', 'required', 'string', 'max:2048', 'url:http,https'],
            'active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:99999'],
        ]);

        return new HeaderLinkData($data['label'], $data['position'], $data['destination'], $data['page_id'] ?? null, $data['url'] ?? null, $data['active'], $data['sort_order']);
    }
}

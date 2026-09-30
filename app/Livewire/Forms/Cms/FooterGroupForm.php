<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Content\FooterGroupData;
use App\Models\Content\FooterGroup;
use Livewire\Form;

class FooterGroupForm extends Form
{
    public string $name = '';

    public bool $active = true;

    public int $sort_order = 0;

    public function setFooterGroup(FooterGroup $group): void
    {
        $this->fill($group->only(['name', 'active', 'sort_order']));
    }

    public function data(): FooterGroupData
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:99999'],
        ]);

        return new FooterGroupData($data['name'], $data['active'], $data['sort_order']);
    }
}

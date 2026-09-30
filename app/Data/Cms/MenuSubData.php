<?php

namespace App\Data\Cms;

final readonly class MenuSubData
{
    public function __construct(
        public int $role_id,
        public int $menu_id,
        public string $name,
        public string $url,
        public int $order,
        public bool $status,
        public ?string $icon = null,
        public ?string $active_pattern = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self((int) ($data['role_id']), (int) ($data['menu_id']), $data['name'], $data['url'], (int) ($data['order']), (bool) ($data['status']), $data['icon'] ?? null, $data['active_pattern'] ?? null);
    }

    public function attributes(): array
    {
        return ['role_id' => $this->role_id, 'menu_id' => $this->menu_id, 'name' => $this->name, 'url' => $this->url, 'order' => $this->order, 'status' => $this->status, 'icon' => $this->icon, 'active_pattern' => $this->active_pattern];
    }
}

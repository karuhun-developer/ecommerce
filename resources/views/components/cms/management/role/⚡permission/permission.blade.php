<div class="space-y-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="lg">Permissions: {{ $role->name }}</flux:heading>
        <div class="flex gap-2">
            <flux:button variant="primary" wire:click="checkAll" wire:loading.attr="disabled">Check All</flux:button>
            <flux:button variant="danger" wire:click="uncheckAll" wire:loading.attr="disabled">Uncheck All</flux:button>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->permissions as $permission)
            <flux:checkbox
                wire:key="permission-{{ $permission->id }}-{{ in_array($permission->id, $this->assignedPermissions) ? 'assigned' : 'unassigned' }}"
                wire:click="toggle({{ $permission->id }})"
                :checked="in_array($permission->id, $this->assignedPermissions)"
                wire:loading.attr="disabled"
                :label="$permission->name"
            />
        @endforeach
    </div>
</div>

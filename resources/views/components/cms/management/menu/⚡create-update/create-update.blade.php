
<div>
    <!-- Create / Update Modal -->
    <flux:modal
        name="defaultModal"
        class="max-w-2xl md:min-w-2xl"
        flyout
    >
        <form class="space-y-6" wire:submit.prevent="submit">
            <div>
                <flux:heading size="lg">
                    {{ $isUpdate ? 'Update' : 'Create' }} Menu Item
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $isUpdate ? 'Update the details of the menu item below.' : 'Fill in the details to create a new menu item.' }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Role</flux:label>
                <flux:select wire:model="role_id" placeholder="Select role ....">
                    <flux:select.option value="">-- Select Role --</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="role_id" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Name</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">URL</flux:label>
                <flux:text>Use laravel route name ex: cms.dashboard</flux:text>
                <flux:input wire:model="url" type="text" />
                <flux:error name="url" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Icon</flux:label>
                @if($icon)
                    <span class="mb-2">
                        <flux:icon name="{{ $icon }}" size="lg" />
                    </span>
                @endif
                <flux:select wire:model.live="icon" laceholder="Select icon ....">
                    @foreach ($this->icons as $i)
                        <flux:select.option value="{{ $i }}">
                            {{ $i }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="icon" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Order</flux:label>
                <flux:input wire:model="order" type="number" />
                <flux:error name="order" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Active Pattern</flux:label>
                <flux:text>Use commas to separate multiple patterns.</flux:text>
                <flux:input wire:model="active_pattern" type="text" />
                <flux:error name="active_pattern" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Status</flux:label>
                <flux:select wire:model="status" placeholder="Select status ....">
                    @foreach (\App\Enums\CommonStatusEnum::cases() as $status)
                        <flux:select.option value="{{ $status->value }}">
                            {{ $status->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

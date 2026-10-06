
<div>
    @can('update' . $this->modelInstance)
        <form class="space-y-6" wire:submit.prevent="saveRateMethod">
            <flux:field>
                <flux:label badge="Required">Shipping Rate Method</flux:label>
                <flux:text>Coordinates use the map location and support instant couriers. Area ID requires an area selection and does not support instant couriers.</flux:text>
                <flux:select wire:model="rateMethod">
                    <option value="coordinates">Coordinates</option>
                    <option value="area_id">Area ID</option>
                </flux:select>
                <flux:error name="rateMethod" />
            </flux:field>
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    @endcan

    <div class="flex items-center justify-between mt-5 mb-4 gap-4">
        <div class="flex items-center gap-2">
            <p class="text-sm text-gray-600">Show</p>
            <flux:select size="sm" wire:model.live.debounce="paginate" placeholder="Per Page">
                <option value="10">10 Per Page</option>
                <option value="25">25 Per Page</option>
                <option value="50">50 Per Page</option>
                <option value="100">100 Per Page</option>
            </flux:select>
        </div>
        <div class="flex items-center gap-2">
            <flux:input.group>
                <flux:input size="sm" icon="magnifying-glass" type="text" placeholder="Search ...." wire:model.live.debounce="search" class="max-w-xs" />
            </flux:input.group>
        </div>
    </div>

    @if($error)
        <flux:callout variant="danger" class="mb-4">
            <flux:callout.text>{{ $error }}</flux:callout.text>
            <flux:button size="sm" wire:click="$refresh">Retry</flux:button>
        </flux:callout>
    @endif

    <flux:table :paginate="$data" class="min-w-full">
        <flux:table.columns>
            <flux:table.column>Actions</flux:table.column>
            <x-loop-th :$searchBy :$paginationOrder :$paginationOrderBy />
        </flux:table.columns>
        <flux:table.rows>
            @forelse($data as $d)
                <flux:table.row wire:key="courier-{{ $d['code'] }}">
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon:trailing="chevron-down" size="sm">Options</flux:button>
                            <flux:menu>
                                @can('update' . $this->modelInstance)
                                    <flux:menu.item
                                        variant="default"
                                        icon="pencil"
                                        @click="
                                            $flux.modal('defaultModal').show();
                                            $wire.dispatch('set-action', {
                                                code: '{{ $d['code'] }}',
                                            });
                                        ">
                                        Update
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                    <flux:table.cell>{{ $d['name'] }}</flux:table.cell>
                    <flux:table.cell>{{ $d['code'] }}</flux:table.cell>
                    <flux:table.cell>{{ $d['services'] }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $d['enabled'] ? 'green' : 'red' }}" size="sm">{{ $d['enabled'] ? 'Active' : 'Inactive' }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="999" align="center" variant="strong">No data found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:cms.shipping.courier.create-update lazy />
</div>

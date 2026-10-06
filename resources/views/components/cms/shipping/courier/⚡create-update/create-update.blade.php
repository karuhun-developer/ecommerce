
<div>
    <flux:modal
        name="defaultModal"
        class="max-w-2xl md:min-w-2xl"
        flyout
    >
        <form class="space-y-6" wire:submit.prevent="submit">
            <div>
                <flux:heading size="lg">Update Courier</flux:heading>
                <flux:text class="mt-2">Update the courier availability below.</flux:text>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" type="text" readonly />
            </flux:field>

            <flux:field>
                <flux:label>Code</flux:label>
                <flux:input wire:model="code" type="text" readonly />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Status</flux:label>
                <flux:text>Active couriers are included when checking shipping rates.</flux:text>
                <flux:select wire:model.number="enabled">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </flux:select>
                <flux:error name="enabled" />
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

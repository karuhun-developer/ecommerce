
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
                    {{ $isUpdate ? 'Update' : 'Create' }} Permission Item
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $isUpdate ? 'Update the details of the permission item below.' : 'Fill in the details to create a new permission item.' }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Name</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Guard Name</flux:label>
                <flux:select wire:model="guard_name" placeholder="Guard Name..">
                    <flux:select.option value="">-- Select Guard Name --</flux:select.option>
                    <flux:select.option value="api">api</flux:select.option>
                    <flux:select.option value="web">web</flux:select.option>
                </flux:select>
                <flux:error name="guard_name" />
            </flux:field>


            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

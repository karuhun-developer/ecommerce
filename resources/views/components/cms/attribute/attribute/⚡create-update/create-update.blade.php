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
                    {{ $isUpdate ? 'Update' : 'Create' }} Attribute
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $isUpdate ? 'Update the details of the attribute below.' : 'Fill in the details to create a new attribute.' }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Attribute Group</flux:label>
                <flux:text>Select the attribute group this attribute belongs to.</flux:text>
                <flux:select wire:model="attribute_group_id">
                    <flux:select.option value="">-- Select Attribute Group --</flux:select.option>
                    @foreach($this->attributeGroups as $group)
                        <flux:select.option value="{{ $group->id }}">{{ $group->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="attribute_group_id" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Name</flux:label>
                <flux:text>Attribute name, e.g. "Red", "Large"</flux:text>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Value</flux:label>
                <flux:text>Actual value of the attribute, e.g. "red", "L"</flux:text>
                <flux:input wire:model="value" type="text" />
                <flux:error name="value" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:text>Optional description for the attribute.</flux:text>
                <flux:textarea wire:model="description" />
                <flux:error name="description" />
            </flux:field>


            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

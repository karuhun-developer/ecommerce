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
                    {{ $isUpdate ? 'Update' : 'Create' }} Category
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $isUpdate ? 'Update the details of the category below.' : 'Fill in the details to create a new category.' }}
                </flux:text>
            </div>

            @if (!config('shop.single_shop'))
                <flux:field>
                    <flux:label badge="Required">Shop</flux:label>
                    <flux:text>Select the shop this category belongs to.</flux:text>
                    <flux:select wire:model="shop_id">
                        <flux:select.option value="">-- Select Shop --</flux:select.option>
                        @foreach($this->shops as $shop)
                            <flux:select.option value="{{ $shop->id }}">{{ $shop->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="shop_id" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Image</flux:label>
                <flux:text>Optional image for the category. Accepted formats: JPEG, PNG. Max size: 2MB.</flux:text>
                <x-file-preview :file="$image" :form_file="$oldImage" />
                <x-file-upload model="image" accept="image/jpeg, image/png, .jpg, .jpeg, .png" />
                <flux:error name="image" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Name</flux:label>
                <flux:text>Category name, e.g. "Electronics", "Clothing"</flux:text>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:text>Optional description for the category.</flux:text>
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
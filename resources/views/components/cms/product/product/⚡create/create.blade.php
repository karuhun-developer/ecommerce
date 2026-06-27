
<div>
    <!-- Create Modal -->
    <flux:modal
        name="defaultModal"
        class="max-w-6xl md:min-w-6xl"
        flyout
    >
        <form wire:submit="submit">
            <div class="space-y-6" x-data="{ type: $wire.entangle('type') }">
                <flux:heading size="lg">Create Product</flux:heading>
                @if (!isSingleShop())
                    <flux:field>
                        <flux:label badge="Required">Shop</flux:label>
                        <flux:text>Select the shop this product belongs to.</flux:text>
                        <flux:select wire:model.live="shop_id" @change="$wire.product_category_id = null">
                            <flux:select.option value="">--Select Shop--</flux:select.option>
                            @foreach($this->shops as $shop)
                                <flux:select.option value="{{ $shop->id }}">{{ $shop->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="shop_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label badge="Required">Category</flux:label>
                    <flux:text>Select the category this product belongs to.</flux:text>
                    <flux:select wire:model="product_category_id">
                        <flux:select.option value="">--Select Category--</flux:select.option>
                        @foreach($this->categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="product_category_id" />
                </flux:field>

                <flux:field>
                    <flux:radio.group wire:model="type" label="Product Type" description="Choose whether this product is simple or variable.">
                        <flux:radio value="simple" label="Simple" />
                        <flux:radio value="variable" label="Variable" />
                    </flux:radio.group>
                    <flux:error name="type" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Required">Name</flux:label>
                    <flux:text>Enter the name of the product.</flux:text>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:text>Provide a detailed description of the product.</flux:text>
                    <livewire:jodit-text-editor
                        wire:model="description"
                        :buttons="['bold', 'italic', 'underline', 'strikeThrough']"
                    />
                    <flux:error name="description" />
                </flux:field>

                <flux:separator />
                <flux:heading size="md">Product Details</flux:heading>

                <flux:field>
                    <flux:label badge="Required">Price</flux:label>
                    <flux:text>Set the price of the product.</flux:text>
                    <flux:input mask:dynamic="$money($input, ',')" wire:model="price" />
                    <flux:error name="price" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label badge="Required">Weight (gram)</flux:label>
                        <flux:text>Set the weight of the product in grams.</flux:text>
                        <flux:input wire:model="weight" type="number" step="0.01" />
                        <flux:error name="weight" />
                    </flux:field>
                    <flux:field>
                        <flux:label badge="Required">Length (cm)</flux:label>
                        <flux:text>Set the length of the product in centimeters.</flux:text>
                        <flux:input wire:model="length" type="number" step="0.01" />
                        <flux:error name="length" />
                    </flux:field>
                    <flux:field>
                        <flux:label badge="Required">Width (cm)</flux:label>
                        <flux:text>Set the width of the product in centimeters.</flux:text>
                        <flux:input wire:model="width" type="number" step="0.01" />
                        <flux:error name="width" />
                    </flux:field>
                    <flux:field>
                        <flux:label badge="Required">Height (cm)</flux:label>
                        <flux:text>Set the height of the product in centimeters.</flux:text>
                        <flux:input wire:model="height" type="number" step="0.01" />
                        <flux:error name="height" />
                    </flux:field>
                </div>

                <flux:switch wire:model="is_unlimited_stock" label="Unlimited Stock" description="Enable this if the product has unlimited stock. This is useful for digital products or services." />

                <div x-show="type === 'variable'" class="mt-6 border-t pt-4" style="display: none;">
                    <flux:heading size="md" class="mb-4">Select Attributes</flux:heading>
                    @foreach($this->attributeGroups as $group)
                        <div class="mb-4">
                            <flux:label>{{ $group->name }}</flux:label>
                            <div class="flex flex-wrap gap-4 mt-2">
                                @foreach($group->attributes as $attribute)
                                    <flux:checkbox 
                                        wire:model="selectedAttributes.{{ $group->id }}" 
                                        value="{{ $attribute->id }}" 
                                        label="{{ $attribute->name }}" 
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @error('selectedAttributes')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex">
                    <flux:spacer />

                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>

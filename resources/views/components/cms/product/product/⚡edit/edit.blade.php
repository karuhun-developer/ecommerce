<div>
    <form wire:submit="submit">
        <div class="space-y-6">
            <div class="space-y-6">
                <flux:heading size="lg" class="mb-4">General Information</flux:heading>
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
            </div>

            @if($product->type === 'variable')
                <div class="p-6">
                    <flux:heading size="lg" class="mb-4">Variables & Attributes</flux:heading>
                    <flux:text class="mb-4 text-sm text-gray-500">Select the attributes for this variable product. If you add or remove attributes, flats will be synchronized automatically.</flux:text>
                    @foreach($this->attributeGroups as $group)
                        <div class="mb-6">
                            <flux:label class="font-bold">{{ $group->name }}</flux:label>
                            <div class="flex flex-wrap gap-4 mt-2">
                                @foreach($group->attributes as $attribute)
                                    <flux:checkbox 
                                        wire:model="selectedAttributes.{{ $group->id }}" 
                                        value="{{ $attribute->id }}" 
                                        label="{{ $attribute->name }}" 
                                    />
                                @endforeach
                            </div>
                            <flux:error name="selectedAttributes.{{ $group->id }}" class="mt-1" />
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:separator />

            <div class="space-y-6 mt-6">
                <flux:heading size="lg" class="mb-4">Product Variants (Flats) & Media</flux:heading>
                <flux:text class="mb-4 text-sm text-gray-500">Upload up to 8 images for each product variant. Max file size is 5MB. Allowed types: jpg, jpeg, png, webp</flux:text>
                <div class="grid grid-cols-1 {{ $product->type === 'variable' ? 'md:grid-cols-2' : '' }} gap-6">
                    @foreach($this->flats as $flat)
                        <div class="border rounded-lg p-4 space-y-4">
                            <flux:heading size="md" class="mb-2">{{ $flat->name }}</flux:heading>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                @for($i = 0; $i < 4; $i++)
                                    @php
                                        $existingUrl = $flat->getFirstMediaUrl("image_slot_{$i}");
                                        $isDeleted = isset($deletedImages[$flat->id][$i]) && $deletedImages[$flat->id][$i];
                                    @endphp
                                    <div class="border rounded bg-white p-2 flex flex-col items-center justify-center relative min-h-[120px]">
                                        <div class="text-xs text-gray-400 absolute top-1 left-2">Slot {{ $i + 1 }}</div>
                                        @if(isset($images[$flat->id][$i]))
                                            <img src="{{ $images[$flat->id][$i]->temporaryUrl() }}" class="w-full h-20 object-cover mt-4 rounded">
                                            <button type="button" wire:click="removeImage({{ $flat->id }}, {{ $i }})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                &times;
                                            </button>
                                        @elseif($existingUrl && !$isDeleted)
                                            <img src="{{ $existingUrl }}" class="w-full h-20 object-cover mt-4 rounded">
                                            <button type="button" wire:click="removeExistingImage({{ $flat->id }}, {{ $i }})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                &times;
                                            </button>
                                        @else
                                            <div class="mt-4 w-full">
                                                <input type="file" wire:model="images.{{ $flat->id }}.{{ $i }}" 
                                                    class="block w-full text-xs text-slate-500
                                                        file:mr-2 file:py-1 file:px-2
                                                        file:rounded file:border-0
                                                        file:text-xs file:font-semibold
                                                        file:bg-blue-50 file:text-blue-700
                                                        hover:file:bg-blue-100"
                                                    accept="image/*" 
                                                />
                                                <flux:error name="images.{{ $flat->id }}.{{ $i }}" />
                                            </div>
                                        @endif
                                    </div>
                                @endfor
                            </div>
                            <flux:field>
                                <flux:label badge="Required">Name</flux:label>
                                <flux:text>Enter the name of this flat product.</flux:text>
                                <flux:input wire:model="productFlats.{{ $flat->id }}.name" />
                                <flux:error name="productFlats.{{ $flat->id }}.name" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:text>Provide a detailed description of this flat product.</flux:text>
                                <livewire:jodit-text-editor
                                    wire:model="productFlats.{{ $flat->id }}.description"
                                    identifier="description-{{ $flat->id }}"
                                    :buttons="['bold', 'italic', 'underline', 'strikeThrough']"
                                />
                                <flux:error name="productFlats.{{ $flat->id }}.description" />
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Required">Price</flux:label>
                                <flux:text>Set the price of this flat product.</flux:text>
                                <flux:input mask:dynamic="$money($input, ',')" wire:model="productFlats.{{ $flat->id }}.price" />
                                <flux:error name="productFlats.{{ $flat->id }}.price" />
                            </flux:field>

                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label badge="Required">Weight (gram)</flux:label>
                                    <flux:text>Set the weight of this flat product in grams.</flux:text>
                                    <flux:input wire:model="productFlats.{{ $flat->id }}.weight" type="number" step="0.01" />
                                    <flux:error name="productFlats.{{ $flat->id }}.weight" />
                                </flux:field>
                                <flux:field>
                                    <flux:label badge="Required">Length (cm)</flux:label>
                                    <flux:text>Set the length of this flat product in centimeters.</flux:text>
                                    <flux:input wire:model="productFlats.{{ $flat->id }}.length" type="number" step="0.01" />
                                    <flux:error name="productFlats.{{ $flat->id }}.length" />
                                </flux:field>
                                <flux:field>
                                    <flux:label badge="Required">Width (cm)</flux:label>
                                    <flux:text>Set the width of this flat product in centimeters.</flux:text>
                                    <flux:input wire:model="productFlats.{{ $flat->id }}.width" type="number" step="0.01" />
                                    <flux:error name="productFlats.{{ $flat->id }}.width" />
                                </flux:field>
                                <flux:field>
                                    <flux:label badge="Required">Height (cm)</flux:label>
                                    <flux:text>Set the height of this flat product in centimeters.</flux:text>
                                    <flux:input wire:model="productFlats.{{ $flat->id }}.height" type="number" step="0.01" />
                                    <flux:error name="productFlats.{{ $flat->id }}.height" />
                                </flux:field>
                                <flux:field>
                                    <flux:label badge="Required">Stock</flux:label>
                                    <flux:text>Set the stock quantity of this flat product.</flux:text>
                                    <flux:input wire:model="productFlats.{{ $flat->id }}.stock" type="number" step="1" />
                                    <flux:error name="productFlats.{{ $flat->id }}.stock" />
                                </flux:field>
                                <flux:switch wire:model="productFlats.{{ $flat->id }}.is_unlimited_stock" label="Unlimited Stock" description="Enable this if this flat product has unlimited stock." />
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </div>
    </form>
</div>
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

            <flux:switch wire:model="is_featured" label="Is Featured" description="Mark this category as featured. Featured categories may be highlighted in the app." />

            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
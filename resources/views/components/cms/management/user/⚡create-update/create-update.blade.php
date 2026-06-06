
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
                    {{ $isUpdate ? 'Update' : 'Create' }} User Item
                </flux:heading>
                <flux:text class="mt-2">
                    {{ $isUpdate ? 'Update the details of the user item below.' : 'Fill in the details to create a new user item.' }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Role</flux:label>
                <flux:select wire:model="role" placeholder="Select role ....">
                    <flux:select.option value="">-- Select Role --</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="role" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Name</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Email</flux:label>
                <flux:input wire:model="email" type="email" />
                <flux:error name="email" />
            </flux:field>

            @if (!$isUpdate)
                <flux:field>
                    <flux:label badge="Required">Password</flux:label>
                    <flux:input wire:model="password" type="password" />
                    <flux:error name="password" />
                </flux:field>
            @endif

            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

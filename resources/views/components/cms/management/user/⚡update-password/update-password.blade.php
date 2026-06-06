
<div>
    <!-- Change Password Modal -->
    <flux:modal
        name="changePasswordModal"
        class="max-w-2xl md:min-w-2xl"
        flyout
    >
        <form class="space-y-6" wire:submit.prevent="submit">
            <div>
                <flux:heading size="lg">
                    Change User Password
                </flux:heading>
                <flux:text class="mt-2">
                    Fill in the new password for the user item below.
                </flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">New Password</flux:label>
                <flux:input wire:model="password" type="password" />
                <flux:error name="password" />
            </flux:field>

            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Change Password</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
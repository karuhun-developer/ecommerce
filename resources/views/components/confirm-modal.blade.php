<flux:modal name="confirm" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg" id="confirm-title">
                ?
            </flux:heading>

            <flux:text class="mt-2" id="confirm-message">
                ?
            </flux:text>
        </div>

        <div class="flex gap-2">
            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="danger">Cancel</flux:button>
            </flux:modal.close>

            <flux:button variant="primary" id="confirm-button">
                Yes
            </flux:button>
        </div>
    </div>
</flux:modal>
<div>
    <div class="grid grid-cols-1">
        <div class="col-span-1">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm my-3">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                    <div class="lg:flex lg:items-center lg:justify-between">
                        <div class="mt-4 lg:mt-0 lg:ml-auto">
                            <div class="flex gap-2">
                                <flux:button
                                    variant="primary"
                                    icon="check"
                                    @click="
                                        $wire.checkAll();
                                        setTimeout(() => {
                                            location.reload();
                                        }, 1000);
                                    ">
                                    Check All
                                </flux:button>
                                <flux:button
                                    variant="danger"
                                    icon="x-mark"
                                    @click="
                                        $wire.uncheckAll();
                                        setTimeout(() => {
                                            location.reload();
                                        }, 1000);
                                    ">
                                    Uncheck All
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        @foreach($permissions as $route => $type)
                            <div class="w-full">
                                <h5 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Route: {{ $route }}</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    @foreach($type as $name => $value)
                                        @php
                                            $label = explode('.', $name);
                                            $label = $label[0];
                                        @endphp
                                        <div class="flex items-center">
                                            <div class="flex items-center"
                                                x-data="{ check: {{ $value ? 'true' : 'false' }} }"
                                                x-init="$watch('check', value => {
                                                    $wire.{{ $value ? 'uncheck' : 'check' }}('{{ $name }}', '{{ str_replace('\\', '\\\\', $route) }}');
                                                });">
                                                <input class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-zinc-800 focus:ring-2 dark:bg-zinc-700 dark:border-zinc-600 disabled:opacity-50"
                                                    type="checkbox"
                                                    x-model="check"
                                                    wire:loading.attr="disabled" />
                                                <label class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                                    {{ ucfirst($label) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

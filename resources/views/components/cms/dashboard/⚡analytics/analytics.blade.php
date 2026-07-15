<div class="space-y-6">
    <!-- User Stats (Superadmin Only) -->
    @if($this->isSuperadmin)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900 dark:text-blue-300">
                        <flux:icon.users class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pelanggan</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->userStats['users']) }}</div>
                    </div>
                </div>
            </flux:card>
            
            @if(!isSingleShop())
                <flux:card>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-100 text-purple-600 rounded-lg dark:bg-purple-900 dark:text-purple-300">
                            <flux:icon.building-storefront class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pemilik Toko</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->userStats['shopowners']) }}</div>
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-4 justify-between items-center bg-gray-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-gray-200 dark:border-zinc-700">
        <div class="font-bold text-lg">Statistik Penjualan</div>
        <div class="flex gap-4 items-center">
            <flux:input type="date" wire:model.live="startDate" size="sm" />
            <span>-</span>
            <flux:input type="date" wire:model.live="endDate" size="sm" />
        </div>
    </div>

    <!-- Order Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 text-green-600 rounded-lg dark:bg-green-900 dark:text-green-300">
                    <flux:icon.currency-dollar class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pendapatan</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ numberToCurrency($this->orderStats['revenue']) }}</div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900 dark:text-blue-300">
                    <flux:icon.shopping-bag class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Penjualan Dibayar</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->orderStats['paid_sales']) }}</div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-yellow-100 text-yellow-600 rounded-lg dark:bg-yellow-900 dark:text-yellow-300">
                    <flux:icon.clock class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum Dibayar</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->orderStats['unpaid_sales']) }}</div>
                </div>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart -->
        <flux:card class="lg:col-span-2">
            <h3 class="font-bold text-lg mb-4">Grafik Penjualan</h3>
            <div 
                x-data="{
                    chart: null,
                    initChart() {
                        if (typeof ApexCharts === 'undefined') {
                            const script = document.createElement('script');
                            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                            script.onload = () => this.renderChart();
                            document.head.appendChild(script);
                        } else {
                            this.renderChart();
                        }
                    },
                    renderChart() {
                        let chartData = @js($this->chartData);
                        
                        let options = {
                            series: [
                                { name: 'Dibayar', data: chartData.paid },
                                { name: 'Belum Dibayar', data: chartData.unpaid }
                            ],
                            chart: {
                                type: 'area',
                                height: 300,
                                toolbar: { show: false },
                                fontFamily: 'inherit'
                            },
                            colors: ['#3b82f6', '#eab308'],
                            dataLabels: { enabled: false },
                            stroke: { curve: 'smooth', width: 2 },
                            xaxis: {
                                categories: chartData.categories,
                                labels: { style: { colors: '#9ca3af' } }
                            },
                            yaxis: {
                                labels: { style: { colors: '#9ca3af' } }
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.4,
                                    opacityTo: 0.05,
                                    stops: [0, 100]
                                }
                            },
                            legend: { position: 'top', horizontalAlign: 'right' }
                        };

                        this.chart = new ApexCharts(this.$refs.chartContainer, options);
                        this.chart.render();
                        
                        Livewire.on('update-chart', (data) => {
                            let newData = data[0].data;
                            this.chart.updateOptions({
                                xaxis: { categories: newData.categories }
                            });
                            this.chart.updateSeries([
                                { name: 'Dibayar', data: newData.paid },
                                { name: 'Belum Dibayar', data: newData.unpaid }
                            ]);
                        });
                    }
                }"
                x-init="initChart()"
            >
                <div x-ref="chartContainer" class="w-full h-[300px]" wire:ignore></div>
            </div>
        </flux:card>

        <!-- Recent Transactions -->
        <flux:card>
            <h3 class="font-bold text-lg mb-4">Transaksi Terbaru</h3>
            <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2">
                @forelse($this->recentTransactions as $tx)
                    <div class="flex justify-between items-center border-b pb-3 last:border-0 last:pb-0">
                        <div>
                            <div class="font-medium text-sm text-gray-900 dark:text-white">{{ $tx->order->reference }}</div>
                            <div class="text-xs text-gray-500">{{ $tx->order->user->name ?? 'Guest' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-sm text-gray-900 dark:text-white">{{ numberToCurrency($tx->total) }}</div>
                            <div class="text-xs text-gray-500">{{ $tx->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-sm text-gray-500 py-4">Belum ada transaksi.</div>
                @endforelse
            </div>
        </flux:card>
    </div>
</div>

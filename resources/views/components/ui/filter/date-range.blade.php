@props([
    'startDate' => 'startDateFilter',
    'endDate' => 'endDateFilter',
    'reportRangeId' => 'reportrange',
    'parentEl' => 'body',
])
<div class="w-full" x-data="{
    startDate: $wire.entangle('{{ $startDate }}'),
    endDate: $wire.entangle('{{ $endDate }}'),
    cb(start, end) {
        if (start == null && end == null) {
            $('#{{ $reportRangeId }} span').html('All Time');
            return;
        }

        $('#{{ $reportRangeId }} span').html(start.format('DD/MM/YYYY HH:mm') + ' - ' + end.format('DD/MM/YYYY HH:mm'));

        $wire.set('{{ $startDate }}', start.format('YYYY-MM-DD HH:mm:ss'));
        $wire.set('{{ $endDate }}', end.format('YYYY-MM-DD HH:mm:ss'));
    },
    resetFilters() {
        $wire.set('{{ $startDate }}', '');
        $wire.set('{{ $endDate }}', '');
        this.cb(null, null);
    },
    init() {
        let start = this.startDate == '' ? null : moment(this.startDate);
        let end = this.endDate == '' ? null : moment(this.endDate);
        
        $('#{{ $reportRangeId }}').daterangepicker({
            parentEl: '{{ $parentEl }}',
            // startDate: start,
            // endDate: end,
            timePicker: true,
            ranges: {
                'Today': [moment().startOf('day'), moment()],
                'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                'Last 1 Hours': [moment().subtract(1, 'hours'), moment()],
                'Last 12 Hours': [moment().subtract(12, 'hours'), moment()],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'Month To Date': [moment().startOf('month'), moment()],
                //'This Month': [moment().startOf('month'), moment().endOf('month')],
                //'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                //'Last 6 Months': [moment().subtract(6, 'month'), moment()],
                //'Last 1 Year': [moment().subtract(1, 'year'), moment()],
                //'Last 2 Year': [moment().subtract(2, 'year'), moment()],
                //'Last 3 Year': [moment().subtract(3, 'year'), moment()],
            },
        }, this.cb);

        // Init
        if (start == null && end == null) {
            $('#{{ $reportRangeId }} span').html('All Time');
            return;
        }

        $('#{{ $reportRangeId }} span').html(start.format('DD/MM/YYYY HH:mm') + ' - ' + end.format('DD/MM/YYYY HH:mm'));

    }
}">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter Date Range:</p>
        <div class="w-full md:flex-1 md:max-w-md" wire:ignore>
            <div id="{{ $reportRangeId }}" class="bg-white dark:bg-gray-800 cursor-pointer px-3 py-2 md:px-4 md:py-3 border border-gray-300 dark:border-gray-600 w-full rounded-md flex items-center justify-between text-gray-700 dark:text-gray-300 h-10 md:h-8">
                <div class="flex items-center gap-2 md:gap-3 min-w-0">
                    <flux:icon.calendar size="sm" class="text-gray-400 dark:text-gray-500 flex-shrink-0" />
                    <span class="text-xs md:text-sm font-medium truncate"></span>
                </div>
            </div>
        </div>
        <flux:button
            @click="resetFilters()"
            variant="primary"
            color="yellow"
            icon="arrow-path"
            size="sm"
            class="w-full md:w-auto">
            Reset
        </flux:button>
    </div>
</div>
@assets
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endassets
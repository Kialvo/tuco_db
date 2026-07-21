{{--
    Shared Stats date-range picker (dropdown + presets + custom range).

    Renders INSIDE a <form id="statsFiltersForm"> whose x-data is
    statsRangePicker({ dateFrom, dateTo }). Every preset resolves to a concrete
    date_from / date_to range and submits the form (the controller filters
    publication_date by those). The statsRangePicker() function is pushed once.
--}}
<div class="relative flex flex-col gap-1"
     @keydown.escape.window="open = false"
     @click.outside="open = false">
    {{-- Params submitted with the form; kept in sync by the picker. --}}
    <input type="hidden" name="date_from" :value="dateFrom">
    <input type="hidden" name="date_to" :value="dateTo">

    @if(($showDateLabel ?? true))
        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Date Range</span>
    @endif
    <button type="button"
            @click="open = !open"
            :aria-expanded="open.toString()"
            class="inline-flex h-[42px] min-w-[240px] items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-3 text-sm font-medium text-green-700 shadow-sm transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-200">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0" aria-hidden="true">
            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="flex-1 text-left" x-text="displayLabel"></span>
        <x-icon name="chevron-down" size="sm" class="shrink-0 transition-transform"
                ::class="open ? 'rotate-180' : ''" />
    </button>

    <div x-show="open" x-cloak x-transition
         role="dialog" aria-label="Select date range"
         class="absolute right-0 top-full z-30 mt-2 w-72 origin-top-right rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
        {{-- Presets — each resolves to a concrete date range. --}}
        <div class="space-y-1">
            <template x-for="preset in presets" :key="preset.key">
                <button type="button"
                        @click="applyPreset(preset)"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm transition"
                        :class="isActivePreset(preset) ? 'bg-green-50 font-medium text-green-700' : 'text-slate-700 hover:bg-slate-50'"
                        x-text="preset.label"></button>
            </template>
        </div>

        {{-- Custom range. --}}
        <div class="mt-2 border-t border-slate-200 pt-2">
            <button type="button"
                    @click="showCustom = !showCustom"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                Custom range
                <x-icon name="chevron-down" size="sm" class="transition-transform"
                        ::class="showCustom ? 'rotate-180' : ''" />
            </button>

            <div x-show="showCustom" x-cloak class="mt-2 space-y-2 px-1">
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Start date</label>
                    <input type="date" x-model="customStart" :max="customEnd || null"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">End date</label>
                    <input type="date" x-model="customEnd" :min="customStart || null"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-200">
                </div>
                <button type="button"
                        :disabled="!customStart || !customEnd || customStart > customEnd"
                        @click="applyCustom()"
                        class="w-full rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-40">
                    Apply
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // Stats date-range picker. Presets resolve to concrete date_from/date_to
            // ranges (the controller filters publication_date by them). Calendar
            // presets ("Previous …") use the last complete week/month/quarter/year;
            // "Last N Days/Months" are rolling windows ending today.
            function statsRangePicker(config) {
                return {
                    open: false,
                    showCustom: false,
                    dateFrom: config.dateFrom || '',
                    dateTo: config.dateTo || '',
                    customStart: config.dateFrom || '',
                    customEnd: config.dateTo || '',

                    // ── date helpers (local time, YYYY-MM-DD) ──────────────
                    fmt(d) {
                        const y = d.getFullYear();
                        const m = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return y + '-' + m + '-' + day;
                    },
                    today() {
                        const d = new Date();
                        d.setHours(0, 0, 0, 0);
                        return d;
                    },
                    lastDays(n) {
                        const to = this.today();
                        const from = new Date(to);
                        from.setDate(from.getDate() - (n - 1));
                        return { from: this.fmt(from), to: this.fmt(to) };
                    },
                    lastMonths(n) {
                        const to = this.today();
                        const from = new Date(to);
                        from.setMonth(from.getMonth() - n);
                        return { from: this.fmt(from), to: this.fmt(to) };
                    },
                    previousWeek() {
                        // ISO week (Mon–Sun); previous complete week.
                        const d = this.today();
                        const offset = (d.getDay() + 6) % 7; // 0 = Monday
                        const thisMonday = new Date(d);
                        thisMonday.setDate(d.getDate() - offset);
                        const from = new Date(thisMonday);
                        from.setDate(thisMonday.getDate() - 7);
                        const to = new Date(from);
                        to.setDate(from.getDate() + 6);
                        return { from: this.fmt(from), to: this.fmt(to) };
                    },
                    previousMonth() {
                        const d = this.today();
                        const from = new Date(d.getFullYear(), d.getMonth() - 1, 1);
                        const to = new Date(d.getFullYear(), d.getMonth(), 0);
                        return { from: this.fmt(from), to: this.fmt(to) };
                    },
                    previousQuarter() {
                        const d = this.today();
                        const firstMonthThisQ = Math.floor(d.getMonth() / 3) * 3;
                        const from = new Date(d.getFullYear(), firstMonthThisQ - 3, 1);
                        const to = new Date(d.getFullYear(), firstMonthThisQ, 0);
                        return { from: this.fmt(from), to: this.fmt(to) };
                    },
                    previousYear() {
                        const d = this.today();
                        const from = new Date(d.getFullYear() - 1, 0, 1);
                        const to = new Date(d.getFullYear() - 1, 11, 31);
                        return { from: this.fmt(from), to: this.fmt(to) };
                    },

                    get presets() {
                        return [
                            // All time = no date filter (the default state).
                            { key: 'all', label: 'All time', from: '', to: '' },
                            { key: 'last7', label: 'Last 7 Days', ...this.lastDays(7) },
                            { key: 'prevWeek', label: 'Previous Week', ...this.previousWeek() },
                            { key: 'last30', label: 'Last 30 Days', ...this.lastDays(30) },
                            { key: 'prevMonth', label: 'Previous Month', ...this.previousMonth() },
                            { key: 'prevQuarter', label: 'Previous Quarter', ...this.previousQuarter() },
                            { key: 'last90', label: 'Last 90 Days', ...this.lastDays(90) },
                            { key: 'last12m', label: 'Last 12 Months', ...this.lastMonths(12) },
                            { key: 'prevYear', label: 'Previous Year', ...this.previousYear() },
                        ];
                    },

                    get displayLabel() {
                        if (this.dateFrom && this.dateTo) {
                            return this.formatLabel(this.dateFrom) + ' – ' + this.formatLabel(this.dateTo);
                        }
                        if (this.dateFrom) return 'From ' + this.formatLabel(this.dateFrom);
                        if (this.dateTo) return 'Up to ' + this.formatLabel(this.dateTo);
                        return 'All time';
                    },

                    formatLabel(dateStr) {
                        if (!dateStr) return '';
                        const d = new Date(dateStr + 'T00:00:00');
                        if (isNaN(d.getTime())) return dateStr;
                        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    },

                    isActivePreset(preset) {
                        return this.dateFrom === preset.from && this.dateTo === preset.to;
                    },

                    submit() {
                        this.$nextTick(() => document.getElementById('statsFiltersForm').submit());
                    },

                    applyPreset(preset) {
                        this.dateFrom = preset.from;
                        this.dateTo = preset.to;
                        this.open = false;
                        this.submit();
                    },

                    applyCustom() {
                        if (!this.customStart || !this.customEnd || this.customStart > this.customEnd) return;
                        this.dateFrom = this.customStart;
                        this.dateTo = this.customEnd;
                        this.open = false;
                        this.showCustom = false;
                        this.submit();
                    },
                };
            }
        </script>
    @endpush
@endonce

{{--
    Shared ApexCharts tooltip helper for the Stats pages.

    ApexCharts renders a shared tooltip in SERIES order, which on a widget with
    ten stacked companies means the biggest contributor can sit last while the €0
    rows sit on top. There is no built-in "sort by value" option (`inverseOrder`
    only reverses), so the sort has to happen in a `tooltip.custom` renderer.

    Include this ONCE per page inside @push('scripts'), then replace the
    `y: { formatter }` config of every MULTI-SERIES widget with:

        tooltip: {
            theme: 'light', shared: true, intersect: false,
            custom: statsSortedTooltip(function (v) { return euro.format(v); })
        }

    The markup deliberately reuses ApexCharts' own class names so the default
    light-theme stylesheet paints it exactly like the tooltip it replaces.

    Single-series widgets have nothing to sort — leave those tooltips alone.
--}}
<script>
    (function () {
        // Series names come from the DB (company + website names), so the tooltip
        // is built through escaping, never raw interpolation.
        const esc = function (value) {
            return String(value ?? '').replace(/[&<>"']/g, function (ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        };

        /**
         * Build a tooltip.custom renderer listing the series for the hovered
         * category sorted HIGHEST → LOWEST.
         *
         * @param formatter  fn(value) → display string (defaults to toLocaleString)
         * @param options    { hideEmpty: bool } drop zero rows entirely
         */
        window.statsSortedTooltip = function (formatter, options) {
            const fmt = formatter || function (v) { return Number(v).toLocaleString(); };
            const hideEmpty = !! (options && options.hideEmpty);

            return function (ctx) {
                const series = ctx.series;
                const dataPointIndex = ctx.dataPointIndex;
                const w = ctx.w;
                const collapsed = w.globals.collapsedSeriesIndices || [];

                const rows = [];
                for (let i = 0; i < series.length; i++) {
                    // A legend-hidden series carries no value at this index anyway,
                    // but check the collapsed list too — it is the explicit signal.
                    if (collapsed.indexOf(i) !== -1) continue;
                    const value = series[i] ? series[i][dataPointIndex] : undefined;
                    if (value === undefined || value === null) continue;
                    if (hideEmpty && Number(value) === 0) continue;

                    rows.push({
                        name: w.globals.seriesNames[i],
                        value: Number(value),
                        color: w.globals.colors[i],
                    });
                }

                rows.sort(function (a, b) { return b.value - a.value; });

                // categoryLabels holds the category TEXT ("Feb 2018"); globals.labels
                // is a numeric index array on these charts, so reading it first
                // titles the tooltip with a row number. It is only accepted as a
                // fallback when it actually carries a string. (The x-axis label
                // formatter blanks ticks for sparsity but does NOT touch these.)
                const g = w.globals;
                const category = g.categoryLabels && g.categoryLabels[dataPointIndex];
                const fallback = g.labels && g.labels[dataPointIndex];
                const title = (category !== undefined && category !== null && category !== '')
                    ? category
                    : (typeof fallback === 'string' ? fallback : '');

                const total = rows.reduce(function (sum, row) { return sum + row.value; }, 0);

                let html = '<div class="apexcharts-tooltip-title" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">'
                    + '<span style="color:#64748b;">' + esc(title) + '</span>'
                    + '<span style="font-weight:600;color:#0f172a;">Total: ' + esc(fmt(total)) + '</span>'
                    + '</div>';

                if (! rows.length) {
                    return html + '<div style="padding:4px 10px;color:#64748b;">No data</div>';
                }

                rows.forEach(function (row) {
                    html += '<div class="apexcharts-tooltip-series-group apexcharts-active" style="display:flex;">'
                        + '<span class="apexcharts-tooltip-marker" style="background-color:' + esc(row.color) + '"></span>'
                        + '<div class="apexcharts-tooltip-text">'
                        + '<div class="apexcharts-tooltip-y-group">'
                        + '<span class="apexcharts-tooltip-text-y-label">' + esc(row.name) + ': </span>'
                        + '<span class="apexcharts-tooltip-text-y-value">' + esc(fmt(row.value)) + '</span>'
                        + '</div></div></div>';
                });

                return html;
            };
        };
    })();
</script>

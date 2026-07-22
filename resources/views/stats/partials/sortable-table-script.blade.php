{{--
    Shared client-side sorter for the Stats breakdown tables.

    Usage — mark up the table declaratively, no per-table JS:
      <table data-sortable>
        <thead><tr>
          <th data-sort-key data-sort-type="text">Client</th>
          <th data-sort-key data-sort-type="number" data-sort-default>Net Profit</th>
        </tr></thead>
        <tbody>
          <tr><td data-sort-value="Better Collective">…</td>
              <td data-sort-value="491646.02">EUR 491,646.02</td></tr>
          <tr data-sort-pinned>…totals row, never reordered…</tr>
        </tbody>
      </table>

    Sorts the RAW value from data-sort-value, never the formatted cell text —
    "EUR 1,200" sorted as a string is wrong. Empty/"—" cells sink to the bottom
    in BOTH directions so a missing margin never masquerades as the smallest one.
    The column carrying data-sort-default is applied on load (desc for numbers,
    asc for text). Sets aria-sort on the active header.

    Include this ONCE per page inside @push('scripts').
--}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table[data-sortable]').forEach(function (table) {
            const headers = Array.from(table.querySelectorAll('th[data-sort-key]'));
            const tbody = table.querySelector('tbody');
            if (! headers.length || ! tbody) return;

            const state = { index: null, dir: null };

            const cellValue = function (row, index) {
                const cell = row.children[index];
                if (! cell) return null;
                const raw = cell.getAttribute('data-sort-value');
                return raw === null || raw === '' ? null : raw;
            };

            const apply = function (index, dir) {
                const type = headers[index].getAttribute('data-sort-type') || 'text';
                const factor = dir === 'asc' ? 1 : -1;

                // Pinned rows (totals) are detached, the rest sorted, then re-appended.
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const pinned = rows.filter((r) => r.hasAttribute('data-sort-pinned'));
                const sortable = rows.filter((r) => ! r.hasAttribute('data-sort-pinned'));

                sortable.sort(function (a, b) {
                    const av = cellValue(a, index);
                    const bv = cellValue(b, index);

                    // Missing values always sink, whichever way we're sorting.
                    if (av === null && bv === null) return 0;
                    if (av === null) return 1;
                    if (bv === null) return -1;

                    if (type === 'number') {
                        return (parseFloat(av) - parseFloat(bv)) * factor;
                    }

                    return av.localeCompare(bv, undefined, { sensitivity: 'base' }) * factor;
                });

                sortable.forEach((r) => tbody.appendChild(r));
                pinned.forEach((r) => tbody.appendChild(r));

                headers.forEach(function (th, i) {
                    const active = i === index;
                    th.setAttribute('aria-sort', active ? (dir === 'asc' ? 'ascending' : 'descending') : 'none');
                    const indicator = th.querySelector('[data-sort-indicator]');
                    if (indicator) {
                        indicator.textContent = active ? (dir === 'asc' ? '↑' : '↓') : '';
                    }
                });

                state.index = index;
                state.dir = dir;
            };

            headers.forEach(function (th, index) {
                th.setAttribute('aria-sort', 'none');
                th.classList.add('cursor-pointer', 'select-none');
                th.setAttribute('tabindex', '0');
                th.setAttribute('role', 'button');

                const activate = function () {
                    const type = th.getAttribute('data-sort-type') || 'text';
                    const initial = type === 'number' ? 'desc' : 'asc';
                    const dir = state.index === index
                        ? (state.dir === 'asc' ? 'desc' : 'asc')
                        : initial;
                    apply(index, dir);
                };

                th.addEventListener('click', activate);
                th.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        activate();
                    }
                });
            });

            const defaultIndex = headers.findIndex((th) => th.hasAttribute('data-sort-default'));
            if (defaultIndex >= 0) {
                const type = headers[defaultIndex].getAttribute('data-sort-type') || 'text';
                apply(defaultIndex, type === 'number' ? 'desc' : 'asc');
            }
        });
    });
</script>

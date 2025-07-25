@extends('layouts.dashboard')

@section('content')
    <h1 class="text-lg font-bold text-gray-700 py-6">Discover New Websites</h1>

    <div class="bg-white p-6 rounded shadow w-full max-w-4xl">
        <form id="searchForm" class="space-y-4">

            {{--  ➜ Keywords (required)  --}}
            <input id="kwInput" name="query" type="text"
                   class="w-full border px-3 py-2 rounded"
                   placeholder="Keywords (e.g. fintech konferenz)" required>

            <div class="flex flex-wrap items-center gap-3">

                {{--  ➜ Language ISO-639-1 (optional)  --}}
                <input id="langInput" name="language" type="text"
                       class="border rounded px-2 py-1 w-24 text-sm"
                       placeholder="lang (e.g. de)">

                {{--  ➜ TLD filter (optional)  --}}
                <input id="tldInput" name="tld" type="text"
                       class="border rounded px-2 py-1 w-24 text-sm"
                       placeholder="tld (e.g. .ch)">

                <label class="text-sm">
                    <input type="checkbox" id="toggleGovEdu"> Hide .gov / .edu / .org
                </label>

                <button class="bg-cyan-600 text-white px-4 py-2 rounded hover:bg-cyan-700">
                    Search
                </button>
            </div>
        </form>

        {{--  Results  --}}
        <div id="resultBox" class="mt-6 hidden p-4 bg-gray-50 rounded shadow">
            <div class="flex justify-between items-center mb-2">
                <h2 class="font-semibold">
                    Fresh domains (<span id="freshCount">0</span>)
                </h2>
                <a href="#" id="btnExport"
                   class="bg-gray-600 text-white px-3 py-1 rounded cursor-pointer">
                    Export CSV
                </a>
            </div>
            <ul id="domainList"
                class="list-disc pl-6 text-sm mt-2 max-h-96 overflow-y-auto">
            </ul>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $form    = $('#searchForm');
            const $hideGov = $('#toggleGovEdu');
            const $box     = $('#resultBox');
            const $list    = $('#domainList');
            const $count   = $('#freshCount');

            // Re-bind the toggle listener
            $hideGov.on('change', applyGovEduFilter);

            $form.on('submit', e => {
                e.preventDefault();
                const payload = $form.serializeArray();
                if ($hideGov.is(':checked')) {
                    payload.push({ name: 'exclude_gov_edu', value: 1 });
                }

                $.post("{{ route('tools.discover.search') }}",
                    $.param(payload),
                    res => renderList(res.new)
                ).fail(xhr => {
                    alert(xhr.responseJSON?.message ?? 'Search failed');
                    console.error(xhr);
                });
            });

            function renderList(urls) {
                $list.empty();
                urls.forEach(u => {
                    $list.append(`
                <li data-host="${u}">
                  <label>
                    <input type="checkbox" class="mr-1" value="${u}" checked>
                    <a href="//${u}" target="_blank" class="underline text-blue-600">
                      ${u}
                    </a>
                  </label>
                </li>
            `);
                });

                // 1) show or hide the result panel
                $box.toggleClass('hidden', $list.children().length === 0);

                // 2) apply your checkbox filter & update count
                applyGovEduFilter();
            }

            function applyGovEduFilter() {
                const hide = $hideGov.is(':checked');
                $list.children().each((_, li) => {
                    const host = $(li).data('host').toLowerCase();
                    const bad  = /\.(gov|edu|org)$/i.test(host);
                    $(li).toggle(!(hide && bad));
                });
                $count.text($list.children(':visible').length);
            }

            $('#btnExport').on('click', () => {
                const domains = $list.find('input:checked')
                    .map((_, el) => el.value)
                    .get();
                if (!domains.length) return alert('Select at least one');
                window.location = "{{ route('tools.discover.export') }}?" + $.param({ domains });
            });
        });
    </script>
@endpush



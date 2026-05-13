<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 shadow-card ds-table']) }}>
    {{-- No inner scroll wrapper — sticky thead must propagate to <main>.
         Wide tables will trigger horizontal scroll on main directly. --}}
    <table class="w-full text-sm">
        @isset($head)
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    {{ $head }}
                </tr>
            </thead>
        @endisset
        <tbody class="divide-y divide-gray-100">
            {{ $slot }}
        </tbody>
    </table>
</div>

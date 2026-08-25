{{-- fill: stretch the card to the remaining height of a flex-column parent and
     scroll the rows inside it. Requires the page to opt in AND its layout to be
     in fill mode (see marketplace-layout). Off by default so the other six
     consumers render exactly as before. --}}
@props(['tableClass' => '', 'fill' => false])
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 shadow-card ds-table'.($fill ? ' ds-table--fill flex-1 min-h-0' : '')]) }}>
    <table class="w-full text-sm {{ $tableClass }}" style="min-width: max-content">
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

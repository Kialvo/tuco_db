@props([
    'paginator' => null,    // an LengthAwarePaginator instance
])

@if($paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <div class="flex items-center justify-between mt-4">
        <span class="text-sm text-gray-400">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </span>

        <nav class="flex items-center gap-1">
            {{-- Prev --}}
            @if($paginator->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed opacity-50">← Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">← Prev</a>
            @endif

            {{-- Numbered pages --}}
            @php
                $current = $paginator->currentPage();
                $last    = $paginator->lastPage();
                $window  = 1;
                $pages   = collect(range(max(1, $current - $window), min($last, $current + $window)));
                if (! $pages->contains(1))     $pages = $pages->prepend(1);
                if (! $pages->contains($last)) $pages = $pages->push($last);
            @endphp

            @php $prev = 0; @endphp
            @foreach($pages as $p)
                @if($p - $prev > 1)
                    <span class="px-1.5 text-gray-400 text-sm">…</span>
                @endif
                @if($p == $current)
                    <span class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg font-semibold">{{ $p }}</span>
                @else
                    <a href="{{ $paginator->url($p) }}"
                       class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">{{ $p }}</a>
                @endif
                @php $prev = $p; @endphp
            @endforeach

            {{-- Next --}}
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Next →</a>
            @else
                <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed opacity-50">Next →</span>
            @endif
        </nav>
    </div>
@endif

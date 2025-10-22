{{-- resources/views/vendor/pagination/custom-dark.blade.php --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation">
        {{-- MOBILE (below sm): condensed 1 2 3 4 5 … last --}}
        <div class="sm:hidden flex items-center justify-center space-x-2">
            {{-- Prev --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1 rounded-md bg-[#1f2937] text-gray-500 cursor-not-allowed">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="px-3 py-1 rounded-md bg-[#1f2937] text-[#84CC16] hover:bg-[#374151]">‹</a>
            @endif

            @php
                $last = $paginator->lastPage();
                $cur  = $paginator->currentPage();
                $firstChunk = range(1, min(5, $last));
            @endphp

            @foreach ($firstChunk as $page)
                @if ($page == $cur)
                    <span class="px-3 py-1 rounded-md bg-[#84CC16] text-[#111827] font-bold">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}"
                       class="px-3 py-1 rounded-md bg-[#1f2937] text-white hover:bg-[#374151]">{{ $page }}</a>
                @endif
            @endforeach

            @if ($last > 5)
                <span class="px-3 py-1 text-gray-400">…</span>
                @if ($cur == $last)
                    <span class="px-3 py-1 rounded-md bg-[#84CC16] text-[#111827] font-bold">{{ $last }}</span>
                @else
                    <a href="{{ $paginator->url($last) }}"
                       class="px-3 py-1 rounded-md bg-[#1f2937] text-white hover:bg-[#374151]">{{ $last }}</a>
                @endif
            @endif

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="px-3 py-1 rounded-md bg-[#1f2937] text-[#84CC16] hover:bg-[#374151]">›</a>
            @else
                <span class="px-3 py-1 rounded-md bg-[#1f2937] text-gray-500 cursor-not-allowed">›</span>
            @endif
        </div>

        {{-- DESKTOP (sm and up): original behavior --}}
        <div class="hidden sm:flex items-center space-x-2">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1 rounded-md bg-[#1f2937] text-gray-500 cursor-not-allowed">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="px-3 py-1 rounded-md bg-[#1f2937] text-[#84CC16] hover:bg-[#374151]">‹</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-3 py-1 text-gray-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-1 rounded-md bg-[#84CC16] text-[#111827] font-bold">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                               class="px-3 py-1 rounded-md bg-[#1f2937] text-white hover:bg-[#374151]">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="px-3 py-1 rounded-md bg-[#1f2937] text-[#84CC16] hover:bg-[#374151]">›</a>
            @else
                <span class="px-3 py-1 rounded-md bg-[#1f2937] text-gray-500 cursor-not-allowed">›</span>
            @endif
        </div>
    </nav>
@endif

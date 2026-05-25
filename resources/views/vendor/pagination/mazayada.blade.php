@if ($paginator->hasPages())
    <nav class="pgn" role="navigation" aria-label="Pagination">
        <div class="pgn-info">
            عرض
            <span class="num">{{ $paginator->firstItem() ?? 0 }}</span>
            إلى
            <span class="num">{{ $paginator->lastItem() ?? 0 }}</span>
            من
            <span class="num">{{ $paginator->total() }}</span>
            نتيجة
        </div>

        <ul class="pgn-list">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="pgn-item is-disabled" aria-disabled="true" aria-label="السابق">
                    <span class="pgn-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </span>
                </li>
            @else
                <li class="pgn-item">
                    <a class="pgn-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="السابق">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="pgn-item is-disabled" aria-disabled="true"><span class="pgn-link pgn-dots">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pgn-item is-active" aria-current="page"><span class="pgn-link num">{{ $page }}</span></li>
                        @else
                            <li class="pgn-item"><a class="pgn-link num" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="pgn-item">
                    <a class="pgn-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="التالي">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                </li>
            @else
                <li class="pgn-item is-disabled" aria-disabled="true" aria-label="التالي">
                    <span class="pgn-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif

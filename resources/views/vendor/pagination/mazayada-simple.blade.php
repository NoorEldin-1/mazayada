@if ($paginator->hasPages())
    <nav class="pgn" role="navigation" aria-label="Pagination">
        <ul class="pgn-list">
            @if ($paginator->onFirstPage())
                <li class="pgn-item is-disabled" aria-disabled="true"><span class="pgn-link">{{ __('pagination.previous') }}</span></li>
            @else
                <li class="pgn-item"><a class="pgn-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a></li>
            @endif

            @if ($paginator->hasMorePages())
                <li class="pgn-item"><a class="pgn-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a></li>
            @else
                <li class="pgn-item is-disabled" aria-disabled="true"><span class="pgn-link">{{ __('pagination.next') }}</span></li>
            @endif
        </ul>
    </nav>
@endif

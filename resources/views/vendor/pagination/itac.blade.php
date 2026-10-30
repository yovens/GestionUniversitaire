@if ($paginator->hasPages())
<nav class="pager" aria-label="Pagination">
  <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}</span>
  <div class="pages">
    @if (! $paginator->onFirstPage())<a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente">‹</a>@endif
    @foreach ($elements as $element)
      @if (is_string($element))<span class="gap">…</span>@endif
      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())<span class="cur" aria-current="page">{{ $page }}</span>
          @else<a href="{{ $url }}">{{ $page }}</a>@endif
        @endforeach
      @endif
    @endforeach
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante">›</a>@endif
  </div>
</nav>
@endif

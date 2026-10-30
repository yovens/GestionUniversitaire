@props(['title', 'action' => null, 'href' => null])
<div class="empty">
  <h3>{{ $title }}</h3>
  <p>{{ $slot }}</p>
  @if($action && $href)<a class="btn" href="{{ $href }}"><x-icon name="plus"/> {{ $action }}</a>@endif
</div>

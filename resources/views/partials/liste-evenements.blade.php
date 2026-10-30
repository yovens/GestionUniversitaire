@if($evenements->isEmpty())
  <p class="muted small">Rien de prévu prochainement.</p>
@else
<ul class="list">
  @foreach($evenements as $e)
    <li>
      <span class="date-box"><b>{{ $e->date_debut->format('d') }}</b><span>{{ $e->date_debut->translatedFormat('M') }}</span></span>
      <div class="grow"><strong>{{ $e->titre }}</strong>
        <div class="small muted">{{ $e->type_label }}@if(! $e->date_fin->isSameDay($e->date_debut)) · jusqu'au {{ $e->date_fin->format('d/m') }}@endif</div></div>
      <span class="ev ev-{{ $e->type }}" style="width:10px;padding:0;height:22px" aria-hidden="true"></span>
    </li>
  @endforeach
</ul>
@endif

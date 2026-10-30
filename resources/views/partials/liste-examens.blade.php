@if($examens->isEmpty())
  <p class="muted small">Aucun examen planifié.</p>
@else
<ul class="list">
  @foreach($examens as $e)
    <li>
      <span class="date-box"><b>{{ $e->date_debut->format('d') }}</b><span>{{ $e->date_debut->translatedFormat('M') }}</span></span>
      <div class="grow"><strong>{{ $e->nom }}</strong>
        <div class="small muted">{{ $e->type_label }} · session {{ $e->session }} · jusqu'au {{ $e->date_fin->format('d/m') }}</div></div>
      <x-fac :faculte="$e->faculte"/>
    </li>
  @endforeach
</ul>
@endif

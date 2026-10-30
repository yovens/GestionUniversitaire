{{-- $b = résultat de ResultatService::bulletin --}}
@if($b['lignes']->isEmpty())
  <p class="muted">Aucun cours n'est encore défini pour cette classe.</p>
@else
<div class="table-wrap"><table class="data">
  <thead><tr><th>Cours</th><th class="num">Coef.</th><th class="num">Intra</th><th class="num">Final</th><th class="num">Reprise</th><th class="num">Moyenne</th><th></th></tr></thead>
  <tbody>
  @php($s = null)
  @foreach($b['lignes'] as $l)
    @if($s !== $l['matiere']->session)
      @php($s = $l['matiere']->session)
      <tr><td colspan="7" class="small muted" style="background:#f8f9fc"><strong>Session {{ $s }}</strong></td></tr>
    @endif
    <tr>
      <td class="strong">{{ $l['matiere']->nom }}</td>
      <td class="num">{{ $l['matiere']->coefficient }}</td>
      <td class="num"><x-grade :value="$l['intra']" :seuil="$b['seuil']"/></td>
      <td class="num"><x-grade :value="$l['final']" :seuil="$b['seuil']"/></td>
      <td class="num"><x-grade :value="$l['reprise']" :seuil="$b['seuil']"/></td>
      <td class="num"><x-grade :value="$l['moyenne']" :seuil="$b['seuil']"/></td>
      <td>@if($l['moyenne'] !== null)<span @class(['badge', 'ok' => $l['valide'], 'danger' => ! $l['valide']])>{{ $l['valide'] ? 'Validé' : 'À reprendre' }}</span>@endif</td>
    </tr>
  @endforeach
  </tbody>
  <tfoot><tr>
    <td>Moyenne générale pondérée</td><td class="num">{{ $b['credits'] }}</td><td colspan="3"></td>
    <td class="num"><x-grade :value="$b['moyenne_generale']" :seuil="$b['seuil']"/></td>
    <td><span @class(['badge', 'ok' => $b['decision_code'] === 'admis', 'danger' => $b['decision_code'] === 'reprise', 'info' => $b['decision_code'] === 'partiel'])>{{ $b['decision'] }}</span></td>
  </tr></tfoot>
</table></div>
<p class="small muted" style="margin-top:8px">Moyenne d'un cours : intra {{ config('itac.notes.poids_intra') }} % + final {{ config('itac.notes.poids_final') }} %. Une note de reprise remplace la moyenne si elle est meilleure. Seuil de réussite : {{ rtrim(rtrim(number_format($b['seuil'], 2, ',', ''), '0'), ',') }}/100.</p>
@endif

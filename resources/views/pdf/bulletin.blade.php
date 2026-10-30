<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Bulletin</title>@include('pdf._style')</head>
<body>
@php($e = $b['etudiant'])
@include('pdf._entete', ['faculte' => $b['classe']->faculte])
<h1>Bulletin de notes — {{ $b['inscription']->annee->libelle }}@if($b['session']) · session {{ $b['session'] }}@endif</h1>
<table class="kv">
  <tr><td class="k">Nom et prénom</td><td><strong>{{ $e->nom }} {{ $e->prenom }}</strong></td><td class="k">Matricule</td><td>{{ $e->matricule }}</td></tr>
  <tr><td class="k">Classe</td><td>{{ $b['classe']->nom }}</td><td class="k">Faculté</td><td>{{ $b['classe']->faculte->nom }}</td></tr>
</table>
<table class="t" style="margin-top:10px">
  <thead><tr><th>Cours</th><th class="n">Coef.</th><th class="n">Intra</th><th class="n">Final</th><th class="n">Reprise</th><th class="n">Moyenne</th><th>Résultat</th></tr></thead>
  <tbody>
  @foreach($b['lignes'] as $l)
    @php($f = fn ($v) => $v === null ? '—' : number_format($v, 2, ',', ''))
    <tr>
      <td>{{ $l['matiere']->nom }} <span class="muted">(S{{ $l['matiere']->session }})</span></td>
      <td class="n">{{ $l['matiere']->coefficient }}</td>
      <td class="n">{{ $f($l['intra']) }}</td><td class="n">{{ $f($l['final']) }}</td><td class="n">{{ $f($l['reprise']) }}</td>
      <td class="n {{ $l['moyenne'] === null ? '' : ($l['valide'] ? 'pass' : 'fail') }}">{{ $f($l['moyenne']) }}</td>
      <td>{{ $l['moyenne'] === null ? '' : ($l['valide'] ? 'Validé' : 'À reprendre') }}</td>
    </tr>
  @endforeach
  </tbody>
  <tfoot><tr><td>Moyenne générale pondérée</td><td class="n">{{ $b['credits'] }}</td><td colspan="3"></td>
    <td class="n">{{ $b['moyenne_generale'] === null ? '—' : number_format($b['moyenne_generale'], 2, ',', '') }}</td><td>/ 100</td></tr></tfoot>
</table>
<div class="decision">
  <strong>Décision : {{ $b['decision'] }}</strong>
  @if($b['echecs']->isNotEmpty())<br>Cours à reprendre : {{ $b['echecs']->map(fn ($l) => $l['matiere']->nom)->join(', ') }}.@endif
  <br><span class="muted">Crédits validés : {{ $b['credits_valides'] }} / {{ $b['credits'] }} · seuil de réussite {{ rtrim(rtrim(number_format($b['seuil'], 2, ',', ''), '0'), ',') }}/100</span>
</div>
@if($b['examens_bloques']->isNotEmpty())
  <p class="fail" style="margin-top:8px">Notes non incluses (paiement requis) : {{ $b['examens_bloques']->pluck('nom')->join(', ') }}.</p>
@endif
@if(! $officiel)<p class="muted" style="margin-top:8px">Relevé consulté en ligne par l'étudiant — seuls les examens publiés figurent sur ce document. Bulletin officiel délivré par le secrétariat.</p>@endif
@if($officiel)
<table class="sign"><tr><td><span>Le secrétariat</span></td><td><span>Le doyen</span></td></tr></table>
@endif
<div class="foot">Édité le {{ now()->format('d/m/Y à H:i') }} · Moyenne d'un cours : intra {{ config('itac.notes.poids_intra') }} % + final {{ config('itac.notes.poids_final') }} % ; la reprise remplace la moyenne si elle est meilleure.</div>
</body></html>

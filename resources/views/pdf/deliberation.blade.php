<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Procès-verbal</title>@include('pdf._style')<style>table.t td, table.t th { font-size: 8.5px; padding: 3px 4px; }</style></head>
<body>
@php($seuil = (float) $classe->faculte->seuil_reussite)
@include('pdf._entete', ['faculte' => $classe->faculte])
<h1>Procès-verbal des résultats — {{ $classe->nom }} · {{ $annee->libelle }} · {{ $session ? 'session '.$session : 'année complète' }}</h1>
<table class="t">
  <thead><tr><th class="n">Rang</th><th>Matricule</th><th>Nom et prénom</th>
    @foreach($matieres as $m)<th class="n">{{ $m->code }}</th>@endforeach<th class="n">Moy.</th><th>Décision</th></tr></thead>
  <tbody>
  @foreach($resultats as $r)
    <tr><td class="n">{{ $r['rang'] ?? '—' }}</td><td>{{ $r['etudiant']->matricule }}</td><td>{{ $r['etudiant']->nom }} {{ $r['etudiant']->prenom }}</td>
      @foreach($r['lignes'] as $l)<td class="n {{ $l['moyenne'] !== null && $l['moyenne'] < $seuil ? 'fail' : '' }}">{{ $l['moyenne'] === null ? '—' : number_format($l['moyenne'], 1, ',', '') }}</td>@endforeach
      <td class="n"><strong>{{ $r['moyenne_generale'] === null ? '—' : number_format($r['moyenne_generale'], 2, ',', '') }}</strong></td><td>{{ $r['decision'] }}</td></tr>
  @endforeach
  </tbody>
</table>
<p class="muted" style="margin-top:6px">@foreach($matieres as $m){{ $m->code }} : {{ $m->nom }} (coef. {{ $m->coefficient }})@if(! $loop->last) · @endif @endforeach</p>
<table class="sign"><tr><td><span>Le secrétariat</span></td><td><span>Le doyen</span></td></tr></table>
<div class="foot">Édité le {{ now()->format('d/m/Y à H:i') }}</div>
</body></html>

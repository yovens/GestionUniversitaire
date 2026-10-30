@extends('layouts.app')
@section('title', 'Résultats '.$classe->nom)
@section('content')
@php($seuil = (float) $classe->faculte->seuil_reussite)
<div class="page-head" style="--fac: {{ $classe->faculte->couleur }}">
  <div class="titles"><div class="crumb"><a href="{{ route('resultats.index', ['annee' => $annee->id]) }}">Résultats</a></div>
    <h1 class="fac-rule">{{ $classe->nom }} — {{ $session ? 'session '.$session : 'année complète' }}</h1>
    <p>{{ $classe->faculte->nom }} · {{ $annee->libelle }} · {{ $resultats->count() }} étudiant(s) · seuil {{ rtrim(rtrim(number_format($seuil, 2, ',', ''), '0'), ',') }}/100</p></div>
  <div class="actions">
    <a class="btn secondary" href="{{ route('resultats.deliberation.pdf', request()->query()) }}" target="_blank" rel="noopener"><x-icon name="download"/> PDF du procès-verbal</a>
    @if(! $session && $me->hasRole('admin', 'doyen'))
      <form method="post" action="{{ route('resultats.decisions', ['classe' => $classe->id, 'annee' => $annee->id]) }}" data-confirm="Enregistrer « Admis » ou « En reprise » sur l'inscription de chaque étudiant dont les résultats sont complets ?">@csrf
        <button class="btn" type="submit"><x-icon name="check"/> Valider les décisions</button></form>
    @endif
  </div>
</div>
<section class="panel flush">
  @if($resultats->isEmpty())
    <x-empty title="Aucun étudiant inscrit">Aucune inscription dans cette classe pour {{ $annee->libelle }}.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th class="num">Rang</th><th>Étudiant</th>
      @foreach($matieres as $m)<th class="num" title="{{ $m->nom }} (coef. {{ $m->coefficient }})">{{ $m->code }}</th>@endforeach
      <th class="num">Moyenne</th><th>Décision</th><th></th></tr></thead>
    <tbody>
    @foreach($resultats as $r)
      <tr>
        <td class="num">{{ $r['rang'] ?? '—' }}</td>
        <td><a href="{{ route('etudiants.show', ['etudiant' => $r['etudiant'], 'annee' => $annee->id]) }}"><strong>{{ $r['etudiant']->nom }} {{ $r['etudiant']->prenom }}</strong></a><div class="small muted">{{ $r['etudiant']->matricule }}</div></td>
        @foreach($r['lignes'] as $l)<td class="num"><x-grade :value="$l['moyenne']" :seuil="$seuil"/></td>@endforeach
        <td class="num"><x-grade :value="$r['moyenne_generale']" :seuil="$seuil"/></td>
        <td><span @class(['badge', 'ok' => $r['decision_code'] === 'admis', 'danger' => $r['decision_code'] === 'reprise', 'info' => $r['decision_code'] === 'partiel'])>{{ $r['decision'] }}</span></td>
        <td class="row-actions"><a class="btn ghost sm" href="{{ route('bulletins.pdf', ['inscription' => $r['inscription'], 'session' => $session]) }}" target="_blank" rel="noopener">Bulletin</a></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</section>
<p class="small muted">Survolez un code de cours pour voir son intitulé. Les moyennes incluent les examens non encore publiés.</p>
@endsection

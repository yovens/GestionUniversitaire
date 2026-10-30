@extends('layouts.app')
@section('title', 'Mon espace')
@section('content')
<div class="page-head">
  <div class="titles">
    <h1>Bonjour, {{ $me->prenom }}</h1>
    <p>Matricule {{ $me->matricule }}@if($inscription) — {{ $inscription->classe->nom }}, {{ $inscription->classe->faculte->nom }}@endif</p>
  </div>
  <div class="actions"><a class="btn" href="{{ route('etudiant.notes') }}"><x-icon name="chart"/> Voir mes notes</a></div>
</div>

@if(! $inscription)
  <div class="alert warn">Vous n'êtes pas encore inscrit(e) pour l'année {{ $annee?->libelle ?? 'en cours' }}. Passez au secrétariat de votre faculté.</div>
@endif

@if($inscription)
<dl class="facts">
  <div class="fact"><dt>Moyenne générale publiée</dt><dd>{{ $bulletin && $bulletin['moyenne_generale'] !== null ? number_format($bulletin['moyenne_generale'], 2, ',', '') : '—' }} <small>/ 100</small></dd></div>
  <div class="fact"><dt>Crédits validés</dt><dd>{{ $bulletin['credits_valides'] ?? 0 }} <small>sur {{ $bulletin['credits'] ?? 0 }}</small></dd></div>
  <div class="fact"><dt>Payé cette année</dt><dd>{{ number_format($situation['total_paye'], 0, ',', ' ') }} <small>HTG</small></dd></div>
  <div class="fact"><dt>Reste à payer</dt><dd>{{ number_format($situation['solde'], 0, ',', ' ') }} <small>HTG</small></dd></div>
</dl>
@endif

<div class="split">
  <section class="panel">
    <div class="panel-head"><h2>Mes frais de scolarité</h2><a class="small" href="{{ route('etudiant.paiements') }}">Détail et reçus</a></div>
    @include('partials.echeancier', ['situation' => $situation])
    @if($bulletin && $bulletin['examens_bloques']->isNotEmpty())
      <div class="alert warn" style="margin-top:14px">Les notes de {{ $bulletin['examens_bloques']->pluck('nom')->join(', ', ' et ') }} sont publiées mais restent masquées jusqu'au règlement du versement exigé.</div>
    @endif
  </section>
  <section class="panel">
    <div class="panel-head"><h3>À venir</h3><a class="small" href="{{ route('calendrier') }}">Calendrier</a></div>
    @include('partials.liste-evenements', ['evenements' => $evenements])
  </section>
</div>
@endsection

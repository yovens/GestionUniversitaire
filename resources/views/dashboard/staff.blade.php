@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('content')
<div class="page-head">
  <div class="titles">
    <h1>Bonjour, {{ $me->prenom }}</h1>
    <p>{{ $me->faculte ? $me->faculte->nom : 'Vue d’ensemble de toutes les facultés' }}@if($annee) — année {{ $annee->libelle }}@endif</p>
  </div>
  <div class="actions">
    <a class="btn" href="{{ route('etudiants.create') }}"><x-icon name="plus"/> Inscrire un étudiant</a>
    <a class="btn secondary" href="{{ route('paiements.create') }}"><x-icon name="wallet"/> Encaisser un paiement</a>
  </div>
</div>

@if(! $annee)
  <div class="alert warn">Aucune année académique n'est active. @if($me->isAdmin())<a href="{{ route('admin.annees.create') }}">Créer l'année académique</a> pour commencer les inscriptions.@else Contactez l'administration.@endif</div>
@endif
@if($examensNonPublies > 0)
  <div class="alert info">{{ $examensNonPublies }} examen(s) terminé(s) dont les notes ne sont pas encore publiées. <a href="{{ route('examens.index') }}">Voir les examens</a></div>
@endif

<dl class="facts">
  <div class="fact"><dt>Étudiants inscrits</dt><dd>{{ number_format($nbEtudiants, 0, ',', ' ') }}</dd></div>
  <div class="fact"><dt>Professeurs actifs</dt><dd>{{ $nbProfesseurs }}</dd></div>
  <div class="fact"><dt>Encaissé ce mois</dt><dd>{{ number_format($encaisseMois, 0, ',', ' ') }} <small>HTG</small></dd></div>
  <div class="fact"><dt>Encaissé sur l'année</dt><dd>{{ number_format($encaisseAnnee, 0, ',', ' ') }} <small>HTG</small></dd></div>
</dl>

<div class="panel-head"><h2>Facultés</h2>@if($me->isAdmin())<a href="{{ route('admin.facultes.index') }}" class="small">Gérer les facultés</a>@endif</div>
@if($facultes->isEmpty())
  <div class="panel"><x-empty title="Aucune faculté" :action="$me->isAdmin() ? 'Créer une faculté' : null" :href="$me->isAdmin() ? route('admin.facultes.create') : null">Commencez par créer les facultés de l'université (Informatique, Génie civil, Agronomie…).</x-empty></div>
@else
<div class="fac-bands" style="margin-bottom:22px">
  @foreach($facultes as $f)
    <a class="fac-band" style="--fac: {{ $f->couleur }}" href="{{ route('etudiants.index', ['faculte' => $f->id]) }}">
      <span class="code">{{ $f->code }}</span>
      <span><span class="name">{{ $f->nom }}</span>@unless($f->actif) <span class="badge">inactive</span>@endunless</span>
      <span class="stat"><b>{{ $f->nb_etudiants }}</b><span>étudiants</span></span>
      <span class="stat optional"><b>{{ $f->nb_classes }}</b><span>classes</span></span>
      <span class="stat"><b>{{ number_format($f->encaisse, 0, ',', ' ') }}</b><span>HTG encaissés</span></span>
    </a>
  @endforeach
</div>
@endif

<div class="split">
  <section class="panel flush">
    <div class="panel-head"><h2>Derniers paiements</h2><a class="small" href="{{ route('paiements.index') }}">Tous les paiements</a></div>
    @if($derniersPaiements->isEmpty())
      <x-empty title="Aucun paiement enregistré">Les encaissements apparaîtront ici.</x-empty>
    @else
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Reçu</th><th>Étudiant</th><th>Date</th><th class="num">Montant</th></tr></thead>
      <tbody>
      @foreach($derniersPaiements as $p)
        <tr>
          <td class="nowrap"><a href="{{ route('paiements.recu', $p) }}" target="_blank" rel="noopener">{{ $p->numero_recu }}</a></td>
          <td>@if($p->etudiant)<a href="{{ route('etudiants.show', $p->etudiant) }}">{{ $p->etudiant->nom_complet }}</a>@endif</td>
          <td>{{ $p->date_paiement->format('d/m/Y') }}</td>
          <td class="num"><x-money :value="$p->montant"/></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    @endif
  </section>

  <div>
    <section class="panel">
      <div class="panel-head"><h3>Examens à venir</h3><a class="small" href="{{ route('examens.index') }}">Planifier</a></div>
      @include('partials.liste-examens', ['examens' => $examensAVenir])
    </section>
    <section class="panel">
      <div class="panel-head"><h3>Calendrier</h3><a class="small" href="{{ route('calendrier') }}">Ouvrir</a></div>
      @include('partials.liste-evenements', ['evenements' => $evenements])
    </section>
  </div>
</div>

@if($journal->isNotEmpty())
<section class="panel">
  <div class="panel-head"><h3>Activité récente</h3><a class="small" href="{{ route('admin.journal') }}">Journal complet</a></div>
  <ul class="list">
    @foreach($journal as $j)
      <li><div class="grow">{{ $j->description }}<div class="small muted">{{ $j->user?->nom_complet ?? 'Système' }} · {{ $j->created_at->diffForHumans() }}</div></div></li>
    @endforeach
  </ul>
</section>
@endif
@endsection

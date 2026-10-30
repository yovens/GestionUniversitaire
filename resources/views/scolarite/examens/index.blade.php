@extends('layouts.app')
@section('title', 'Examens')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Examens {{ $annee?->libelle }}</h1><p>Planifiez les périodes d'examens. Publier un examen rend ses notes visibles aux étudiants à jour de paiement et verrouille la saisie des professeurs.</p></div>
  <div class="actions"><a class="btn" href="{{ route('examens.create') }}"><x-icon name="plus"/> Planifier un examen</a></div>
</div>
<form class="filters" method="get">@include('partials.filtre-annee') @include('partials.filtre-faculte')</form>
<section class="panel flush">
  @if($examens->isEmpty())
    <x-empty title="Aucun examen planifié" action="Planifier un examen" :href="route('examens.create')">Créez les intras, finaux et reprises de chaque session.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Examen</th><th>Faculté</th><th>Type</th><th class="num">Session</th><th>Dates</th><th>Versement exigé</th><th class="num">Notes</th><th>Publication</th><th></th></tr></thead>
    <tbody>
    @foreach($examens as $e)
      @php($modifiable = $me->faculteScope() === null || $e->faculte_id === $me->faculteScope())
      <tr>
        <td class="strong">{{ $e->nom }}</td>
        <td><x-fac :faculte="$e->faculte"/></td>
        <td>{{ $e->type_label }}</td>
        <td class="num">{{ $e->session }}</td>
        <td class="small">{{ $e->date_debut->format('d/m') }} → {{ $e->date_fin->format('d/m/Y') }}</td>
        <td>{{ $e->tranche_requise ? 'Versement '.$e->tranche_requise : 'Aucun' }}</td>
        <td class="num">{{ $e->notes_count }}</td>
        <td>@if($e->notes_publiees)<span class="badge ok">Publiées {{ $e->publie_at?->format('d/m') }}</span>@else<span class="badge">Non publiées</span>@endif</td>
        <td><div class="row-actions">
          @if($modifiable)
            <form method="post" action="{{ route('examens.publication', $e) }}" data-confirm="{{ $e->notes_publiees ? 'Retirer la publication ? Les étudiants ne verront plus ces notes.' : 'Publier les notes de cet examen ? Les étudiants à jour de paiement pourront les voir.' }}">@csrf
              <button class="btn {{ $e->notes_publiees ? 'secondary' : '' }} sm" type="submit">{{ $e->notes_publiees ? 'Dépublier' : 'Publier' }}</button></form>
            <a class="btn ghost sm" href="{{ route('examens.edit', $e) }}">Modifier</a>
            @if($e->notes_count === 0)
              <form method="post" action="{{ route('examens.destroy', $e) }}" data-confirm="Supprimer l'examen {{ $e->nom }} ?">@csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer</button></form>
            @endif
          @else
            <span class="small muted">Géré par l'administration</span>
          @endif
        </div></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</section>

<section class="panel flush">
  <div class="panel-head"><h2>Sujets déposés par les professeurs</h2></div>
  @if($sujets->isEmpty())
    <x-empty title="Aucun sujet déposé">Les professeurs déposent leurs sujets depuis leur espace.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Sujet</th><th>Cours</th><th>Examen</th><th>Professeur</th><th>Déposé le</th><th></th></tr></thead>
    <tbody>
    @foreach($sujets as $s)
      <tr><td class="strong">{{ $s->titre }}</td><td>{{ $s->matiere->nom }} <span class="small muted">{{ $s->matiere->classe->nom }}</span></td><td>{{ $s->examen?->nom ?? '—' }}</td>
        <td>{{ $s->professeur->nom_complet }}</td><td class="small">{{ $s->created_at->format('d/m/Y H:i') }}</td>
        <td class="row-actions">@if($s->fichier)<a class="btn secondary sm" href="{{ route('fichiers.sujet', $s) }}" target="_blank" rel="noopener"><x-icon name="download"/> Ouvrir</a>@endif</td></tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</section>
@endsection

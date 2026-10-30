@extends('layouts.app')
@section('title', 'Étudiants')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Étudiants</h1><p>{{ $etudiants->total() }} dossier(s) — inscriptions de {{ $annee?->libelle ?? '—' }}</p></div>
  <div class="actions"><a class="btn" href="{{ route('etudiants.create') }}"><x-icon name="plus"/> Inscrire un étudiant</a></div>
</div>
<form class="filters" method="get">
  <div class="field grow"><label for="q">Rechercher</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, matricule, téléphone"></div>
  @include('partials.filtre-annee')
  @include('partials.filtre-faculte')
  <div class="field"><label for="classe">Classe</label><select id="classe" name="classe" data-autosubmit><option value="">Toutes</option>
    @foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('classe') == $c->id)>{{ $c->nom }} ({{ $c->faculte->code }})</option>@endforeach</select></div>
  <div class="field"><label for="statut">Statut</label><select id="statut" name="statut" data-autosubmit><option value="">Tous</option>
    @foreach(\App\Models\Inscription::STATUTS as $k => $v)<option value="{{ $k }}" @selected(request('statut') === $k)>{{ $v }}</option>@endforeach
    <option value="non_inscrit" @selected(request('statut') === 'non_inscrit')>Non inscrit cette année</option></select></div>
  <button class="btn secondary" type="submit">Filtrer</button>
</form>
<section class="panel flush">
  @if($etudiants->isEmpty())
    <x-empty title="Aucun étudiant trouvé" action="Inscrire un étudiant" :href="route('etudiants.create')">Modifiez les filtres ou inscrivez un nouvel étudiant.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Étudiant</th><th>Matricule</th><th>Faculté</th><th>Classe</th><th>Statut</th><th>Téléphone</th></tr></thead>
    <tbody>
    @foreach($etudiants as $e)
      @php($i = $e->inscriptions->first())
      <tr @class(['is-off' => ! $e->actif])>
        <td><div class="person"><x-avatar :user="$e"/><div><a href="{{ route('etudiants.show', ['etudiant' => $e, 'annee' => $annee?->id]) }}">{{ $e->nom }} {{ $e->prenom }}</a></div></div></td>
        <td>{{ $e->matricule }}</td>
        <td><x-fac :faculte="$e->faculte"/></td>
        <td>{{ $i?->classe->nom ?? '—' }}</td>
        <td>@if($i)<span @class(['badge', 'ok' => $i->statut === 'admis', 'warn' => $i->statut === 'reprise', 'danger' => $i->statut === 'abandon', 'info' => $i->statut === 'inscrit'])>{{ $i->statut_label }}</span>@else<span class="badge">Non inscrit</span>@endif</td>
        <td>{{ $e->phone }}</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $etudiants->links() }}
  @endif
</section>
@endsection

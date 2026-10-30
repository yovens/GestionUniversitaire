@extends('layouts.app')
@section('title', 'Cours')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Cours</h1><p>Programme de chaque classe, avec coefficient et session. Professeurs affichés pour {{ $annee?->libelle ?? 'l’année courante' }}.</p></div>
  <div class="actions"><a class="btn" href="{{ route('matieres.create', ['classe' => request('classe')]) }}"><x-icon name="plus"/> Nouveau cours</a></div>
</div>
<form class="filters" method="get">
  <div class="field grow"><label for="q">Rechercher</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou code"></div>
  @include('partials.filtre-faculte')
  <div class="field"><label for="classe">Classe</label><select id="classe" name="classe" data-autosubmit><option value="">Toutes</option>
    @foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('classe') == $c->id)>{{ $c->nom }} ({{ $c->faculte->code }})</option>@endforeach</select></div>
  <button class="btn secondary" type="submit">Filtrer</button>
</form>
<section class="panel flush">
  @if($matieres->isEmpty())
    <x-empty title="Aucun cours" action="Ajouter un cours" :href="route('matieres.create', ['classe' => request('classe')])">Ajoutez les cours du programme de cette classe.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Code</th><th>Cours</th><th>Classe</th><th class="num">Coef.</th><th class="num">Session</th><th>Professeur</th><th></th></tr></thead>
    <tbody>
    @foreach($matieres as $m)
      <tr>
        <td>{{ $m->code }}</td>
        <td class="strong">{{ $m->nom }}</td>
        <td><x-fac :faculte="$m->classe->faculte"/> {{ $m->classe->nom }}</td>
        <td class="num">{{ $m->coefficient }}</td>
        <td class="num">{{ $m->session }}</td>
        <td>@if($a = $m->affectations->first()){{ $a->professeur->nom_complet }}@else<a class="small" href="{{ route('affectations.index', ['classe' => $m->classe_id]) }}">Affecter</a>@endif</td>
        <td><div class="row-actions">
          <a class="btn ghost sm" href="{{ route('matieres.edit', $m) }}">Modifier</a>
          <form method="post" action="{{ route('matieres.destroy', $m) }}" data-confirm="Supprimer le cours {{ $m->nom }} ?">@csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer</button></form>
        </div></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $matieres->links() }}
  @endif
</section>
@endsection

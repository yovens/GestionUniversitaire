@extends('layouts.app')
@section('title', 'Classes')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Classes</h1><p>Les niveaux d'étude de chaque faculté (L1, L2…). Les cours sont rattachés à une classe.</p></div>
  <div class="actions"><a class="btn" href="{{ route('classes.create', ['faculte' => request('faculte')]) }}"><x-icon name="plus"/> Nouvelle classe</a></div>
</div>
<form class="filters" method="get">@include('partials.filtre-faculte')</form>
@forelse($classes as $faculte => $liste)
  <section class="panel flush" style="--fac: {{ $liste->first()->faculte->couleur }}">
    <div class="panel-head"><h2 class="fac-rule">{{ $faculte }}</h2></div>
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Classe</th><th class="num">Niveau</th><th class="num">Cours</th><th class="num">Inscrits cette année</th><th></th></tr></thead>
      <tbody>
      @foreach($liste as $c)
        <tr>
          <td class="strong">{{ $c->nom }}</td>
          <td class="num">{{ $c->niveau }}</td>
          <td class="num"><a href="{{ route('matieres.index', ['classe' => $c->id]) }}">{{ $c->matieres_count }}</a></td>
          <td class="num"><a href="{{ route('etudiants.index', ['classe' => $c->id, 'faculte' => $c->faculte_id]) }}">{{ $c->nb_inscrits }}</a></td>
          <td><div class="row-actions">
            <a class="btn ghost sm" href="{{ route('matieres.create', ['classe' => $c->id]) }}">Ajouter un cours</a>
            <a class="btn ghost sm" href="{{ route('classes.edit', $c) }}">Modifier</a>
            <form method="post" action="{{ route('classes.destroy', $c) }}" data-confirm="Supprimer la classe {{ $c->nom }} ?">@csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer</button></form>
          </div></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </section>
@empty
  <div class="panel"><x-empty title="Aucune classe" action="Créer une classe" :href="route('classes.create')">Créez les niveaux de chaque faculté : L1 Informatique, L2 Informatique…</x-empty></div>
@endforelse
@endsection

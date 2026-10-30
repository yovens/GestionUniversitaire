@extends('layouts.app')
@section('title', 'Résultats')
@section('content')
<div class="page-head"><div class="titles"><h1>Résultats & bulletins</h1><p>Ouvrez une classe pour voir le classement, éditer les bulletins et valider les décisions de fin d'année.</p></div></div>
<form class="filters" method="get">@include('partials.filtre-annee') @include('partials.filtre-faculte')</form>
@forelse($classes as $faculte => $liste)
  <section class="panel flush" style="--fac: {{ $liste->first()->faculte->couleur }}">
    <div class="panel-head"><h2 class="fac-rule">{{ $faculte }}</h2></div>
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Classe</th><th class="num">Inscrits</th><th></th></tr></thead>
      <tbody>
      @foreach($liste as $c)
        <tr><td class="strong">{{ $c->nom }}</td><td class="num">{{ $c->nb_inscrits }}</td>
          <td><div class="row-actions">
            <a class="btn ghost sm" href="{{ route('resultats.deliberation', ['classe' => $c->id, 'session' => 1, 'annee' => $annee?->id]) }}">Session 1</a>
            <a class="btn ghost sm" href="{{ route('resultats.deliberation', ['classe' => $c->id, 'session' => 2, 'annee' => $annee?->id]) }}">Session 2</a>
            <a class="btn secondary sm" href="{{ route('resultats.deliberation', ['classe' => $c->id, 'annee' => $annee?->id]) }}">Année complète</a>
          </div></td></tr>
      @endforeach
      </tbody>
    </table></div>
  </section>
@empty
  <div class="panel"><x-empty title="Aucune classe">Les classes apparaîtront ici dès leur création.</x-empty></div>
@endforelse
@endsection

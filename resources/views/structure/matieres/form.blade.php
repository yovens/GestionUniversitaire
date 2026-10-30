@extends('layouts.app')
@section('title', 'Cours')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('matieres.index') }}">Cours</a></div><h1>{{ $matiere->exists ? $matiere->nom : 'Nouveau cours' }}</h1></div></div>
<section class="panel" style="max-width:760px">
  @if($classes->isEmpty())
    <x-empty title="Aucune classe" action="Créer une classe" :href="route('classes.create')">Un cours appartient toujours à une classe. Créez d'abord la classe.</x-empty>
  @else
  <form method="post" action="{{ $matiere->exists ? route('matieres.update', $matiere) : route('matieres.store') }}">
    @csrf @if($matiere->exists) @method('put') @endif
    <div class="form-grid">
      <x-field name="classe_id" label="Classe" type="select" required class="full">
        <option value="">Choisir…</option>
        @foreach($classes->groupBy(fn ($c) => $c->faculte->nom) as $fac => $liste)
          <optgroup label="{{ $fac }}">@foreach($liste as $c)<option value="{{ $c->id }}" @selected(old('classe_id', $matiere->classe_id) == $c->id)>{{ $c->nom }}</option>@endforeach</optgroup>
        @endforeach
      </x-field>
      <x-field name="nom" label="Intitulé du cours" :value="$matiere->nom" required placeholder="Résistance des matériaux"/>
      <x-field name="code" label="Code" :value="$matiere->code" required placeholder="GC-RDM1"/>
      <x-field name="coefficient" label="Coefficient (crédits)" type="number" min="1" max="20" :value="$matiere->coefficient" required/>
      <x-field name="session" label="Session" type="select" required>
        <option value="1" @selected(old('session', $matiere->session) == 1)>Session 1</option>
        <option value="2" @selected(old('session', $matiere->session) == 2)>Session 2</option>
      </x-field>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Enregistrer</button><a class="btn secondary" href="{{ route('matieres.index') }}">Annuler</a></div>
  </form>
  @endif
</section>
@endsection

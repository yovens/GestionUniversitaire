@extends('layouts.app')
@section('title', 'Année académique')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('admin.annees.index') }}">Années académiques</a></div><h1>{{ $annee->exists ? 'Année '.$annee->libelle : 'Nouvelle année académique' }}</h1></div></div>
<section class="panel" style="max-width:640px">
  <form method="post" action="{{ $annee->exists ? route('admin.annees.update', $annee) : route('admin.annees.store') }}">
    @csrf @if($annee->exists) @method('put') @endif
    <div class="form-grid">
      <x-field name="libelle" label="Libellé" :value="$annee->libelle" required class="full" placeholder="2026-2027"/>
      <x-field name="date_debut" label="Date de début" type="date" :value="$annee->date_debut?->format('Y-m-d')" required/>
      <x-field name="date_fin" label="Date de fin" type="date" :value="$annee->date_fin?->format('Y-m-d')" required/>
      @unless($annee->exists)
        <div class="field full"><label class="check"><input type="checkbox" name="courante" value="1" @checked(old('courante'))> En faire l'année courante dès maintenant</label></div>
      @endunless
    </div>
    <div class="form-actions"><button class="btn" type="submit">Enregistrer</button><a class="btn secondary" href="{{ route('admin.annees.index') }}">Annuler</a></div>
  </form>
</section>
@endsection

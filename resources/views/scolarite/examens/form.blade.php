@extends('layouts.app')
@section('title', 'Examen')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('examens.index') }}">Examens</a></div><h1>{{ $examen->exists ? $examen->nom : 'Planifier un examen' }}</h1><p>L'examen est ajouté automatiquement au calendrier.</p></div></div>
<section class="panel" style="max-width:820px">
  <form method="post" action="{{ $examen->exists ? route('examens.update', $examen) : route('examens.store') }}">
    @csrf @if($examen->exists) @method('put') @endif
    <div class="form-grid">
      <x-field name="nom" label="Nom" :value="$examen->nom" required placeholder="Intra session 1" class="full"/>
      <x-field name="type" label="Type" type="select" required>
        @foreach(\App\Models\Examen::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $examen->type) === $k)>{{ $v }}</option>@endforeach
      </x-field>
      <x-field name="session" label="Session" type="select" required>
        <option value="1" @selected(old('session', $examen->session) == 1)>Session 1</option>
        <option value="2" @selected(old('session', $examen->session) == 2)>Session 2</option>
      </x-field>
      @if($me->faculteScope() === null)
        <x-field name="faculte_id" label="Faculté concernée" type="select" class="full">
          <option value="">Toutes les facultés</option>
          @foreach($facultes as $f)<option value="{{ $f->id }}" @selected(old('faculte_id', $examen->faculte_id) == $f->id)>{{ $f->nom }}</option>@endforeach
        </x-field>
      @endif
      <x-field name="date_debut" label="Premier jour" type="date" :value="$examen->date_debut?->format('Y-m-d')" required/>
      <x-field name="date_fin" label="Dernier jour" type="date" :value="$examen->date_fin?->format('Y-m-d')" required/>
      <x-field name="tranche_requise" label="Versement à régler pour voir les notes" type="number" min="1" max="12" :value="$examen->tranche_requise" class="full"
        hint="Ex. 2 : l'étudiant doit avoir payé les versements 1 et 2. Laissez vide pour ne pas bloquer."/>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Enregistrer</button><a class="btn secondary" href="{{ route('examens.index') }}">Annuler</a></div>
  </form>
</section>
@endsection

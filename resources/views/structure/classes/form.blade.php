@extends('layouts.app')
@section('title', 'Classe')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('classes.index') }}">Classes</a></div><h1>{{ $classe->exists ? $classe->nom : 'Nouvelle classe' }}</h1></div></div>
<section class="panel" style="max-width:640px">
  <form method="post" action="{{ $classe->exists ? route('classes.update', $classe) : route('classes.store') }}">
    @csrf @if($classe->exists) @method('put') @endif
    <div class="form-grid">
      <x-field name="faculte_id" label="Faculté" type="select" required class="full">
        <option value="">Choisir…</option>
        @foreach($facultes as $f)<option value="{{ $f->id }}" @selected(old('faculte_id', $classe->faculte_id ?? request('faculte')) == $f->id)>{{ $f->nom }}</option>@endforeach
      </x-field>
      <x-field name="nom" label="Nom de la classe" :value="$classe->nom" required placeholder="L1 Génie civil"/>
      <x-field name="niveau" label="Niveau (année d'étude)" type="number" min="1" max="7" :value="$classe->niveau" required/>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Enregistrer</button><a class="btn secondary" href="{{ route('classes.index') }}">Annuler</a></div>
  </form>
</section>
@endsection

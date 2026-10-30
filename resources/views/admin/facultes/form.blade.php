@extends('layouts.app')
@section('title', $faculte->exists ? 'Modifier la faculté' : 'Nouvelle faculté')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('admin.facultes.index') }}">Facultés</a></div><h1>{{ $faculte->exists ? $faculte->nom : 'Nouvelle faculté' }}</h1></div></div>
<section class="panel" style="max-width:760px">
  <form method="post" action="{{ $faculte->exists ? route('admin.facultes.update', $faculte) : route('admin.facultes.store') }}">
    @csrf @if($faculte->exists) @method('put') @endif
    <div class="form-grid">
      <x-field name="nom" label="Nom de la faculté" :value="$faculte->nom" required placeholder="Génie civil"/>
      <x-field name="code" label="Code court" :value="$faculte->code" required maxlength="20" hint="Sert de préfixe aux matricules (ex. GC-26-0001)." placeholder="GC"/>
      <div class="field">
        <label for="f_couleur">Couleur d'identification</label>
        <div style="display:flex;gap:10px;align-items:center">
          <input id="f_couleur" type="color" name="couleur" value="{{ old('couleur', $faculte->couleur) }}" data-preview="#apercu">
          <span id="apercu" class="fac-chip" style="--fac: {{ old('couleur', $faculte->couleur) }}">{{ $faculte->code ?: 'CODE' }}</span>
        </div>
        <div class="hint">Elle permet de reconnaître la faculté partout dans l'application.</div>
      </div>
      <x-field name="seuil_reussite" label="Seuil de réussite (sur 100)" type="number" step="0.5" min="0" max="100" :value="$faculte->seuil_reussite" required/>
      <x-field name="description" label="Description" type="textarea" :value="$faculte->description" class="full"/>
      <div class="field full"><label class="check"><input type="hidden" name="actif" value="0"><input type="checkbox" name="actif" value="1" @checked(old('actif', $faculte->actif))> Faculté active (accepte des inscriptions)</label></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">{{ $faculte->exists ? 'Enregistrer' : 'Créer la faculté' }}</button><a class="btn secondary" href="{{ route('admin.facultes.index') }}">Annuler</a></div>
  </form>
</section>
@endsection

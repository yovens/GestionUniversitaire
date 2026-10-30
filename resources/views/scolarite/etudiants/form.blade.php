@extends('layouts.app')
@section('title', $etudiant->exists ? 'Modifier le dossier' : 'Inscrire un étudiant')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('etudiants.index') }}">Étudiants</a>@if($etudiant->exists) / <a href="{{ route('etudiants.show', $etudiant) }}">{{ $etudiant->nom_complet }}</a>@endif</div>
  <h1>{{ $etudiant->exists ? 'Modifier le dossier' : 'Inscrire un étudiant' }}</h1>
  @unless($etudiant->exists)<p>Le matricule et un mot de passe provisoire sont créés automatiquement.</p>@endunless</div></div>
@if($classes->isEmpty())
  <div class="panel"><x-empty title="Aucune classe disponible">Les classes de votre faculté doivent être créées avant toute inscription.</x-empty></div>
@else
<form method="post" enctype="multipart/form-data" action="{{ $etudiant->exists ? route('etudiants.update', $etudiant) : route('etudiants.store') }}">
  @csrf @if($etudiant->exists) @method('put') @endif
  <section class="panel">
    <h2>Inscription {{ \App\Models\AnneeAcademique::courante()?->libelle }}</h2>
    <div class="form-grid">
      <x-field name="classe_id" label="Classe" type="select" :required="! $etudiant->exists" class="full"
        :hint="$etudiant->exists && ! $inscription ? 'Pas d’inscription pour l’année courante : utilisez « Réinscrire » sur la fiche.' : null">
        <option value="">Choisir la classe…</option>
        @foreach($classes->groupBy(fn ($c) => $c->faculte->nom) as $fac => $liste)
          <optgroup label="{{ $fac }}">@foreach($liste as $c)<option value="{{ $c->id }}" @selected(old('classe_id', $inscription?->classe_id) == $c->id)>{{ $c->nom }}</option>@endforeach</optgroup>
        @endforeach
      </x-field>
    </div>
  </section>
  <section class="panel">
    <h2>Identité</h2>
    <div class="form-grid">
      <x-field name="prenom" label="Prénom" :value="$etudiant->prenom" required/>
      <x-field name="nom" label="Nom" :value="$etudiant->nom" required/>
      <x-field name="sexe" label="Sexe" type="select"><option value="">—</option><option value="F" @selected(old('sexe', $etudiant->sexe) === 'F')>Femme</option><option value="M" @selected(old('sexe', $etudiant->sexe) === 'M')>Homme</option></x-field>
      <x-field name="date_naissance" label="Date de naissance" type="date" :value="$etudiant->date_naissance?->format('Y-m-d')"/>
      <x-field name="lieu_naissance" label="Lieu de naissance" :value="$etudiant->lieu_naissance"/>
      <x-field name="photo" label="Photo" type="file" accept="image/jpeg,image/png,image/webp" hint="Facultatif — 2 Mo maximum."/>
    </div>
  </section>
  <section class="panel">
    <h2>Coordonnées et compte</h2>
    <div class="form-grid">
      <x-field name="phone" label="Téléphone" type="tel" :value="$etudiant->phone"/>
      <x-field name="email" label="E-mail" type="email" :value="$etudiant->email"/>
      <x-field name="adresse" label="Adresse" :value="$etudiant->adresse" class="full"/>
      <x-field name="contact_urgence" label="Personne à contacter (nom et téléphone)" :value="$etudiant->contact_urgence"/>
      <x-field name="username" label="Identifiant de connexion" :value="$etudiant->exists ? $etudiant->username : null" hint="Laissez vide : le matricule servira d’identifiant."/>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">{{ $etudiant->exists ? 'Enregistrer le dossier' : 'Inscrire l’étudiant' }}</button>
      <a class="btn secondary" href="{{ $etudiant->exists ? route('etudiants.show', $etudiant) : route('etudiants.index') }}">Annuler</a>
    </div>
  </section>
</form>
@endif
@endsection

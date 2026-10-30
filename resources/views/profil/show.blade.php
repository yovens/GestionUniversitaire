@extends('layouts.app')
@section('title', 'Mon profil')
@section('content')
<div class="page-head"><div class="titles"><h1>Mon profil</h1><p>{{ $user->role->label() }}@if($user->faculte) — {{ $user->faculte->nom }}@endif</p></div>
  <div class="actions"><a class="btn secondary" href="{{ route('password.edit') }}"><x-icon name="lock"/> Changer mon mot de passe</a></div></div>

<div class="split">
  <section class="panel">
    <h2>Coordonnées</h2>
    <form method="post" action="{{ route('profil.update') }}">
      @csrf @method('put')
      <div class="form-grid">
        <x-field name="phone" label="Téléphone" type="tel" :value="$user->phone"/>
        <x-field name="email" label="E-mail" type="email" :value="$user->email"/>
        <x-field name="adresse" label="Adresse" :value="$user->adresse" class="full"/>
        <x-field name="contact_urgence" label="Personne à contacter en cas d’urgence" :value="$user->contact_urgence" class="full" hint="Nom et téléphone"/>
      </div>
      <div class="form-actions"><button class="btn" type="submit">Enregistrer les coordonnées</button></div>
    </form>
  </section>
  <aside>
    <section class="panel">
      <div style="display:flex;gap:16px;align-items:center;margin-bottom:14px">
        <x-avatar :user="$user" size="lg"/>
        <div><h3 style="margin:0">{{ $user->nom_complet }}</h3><p class="muted small" style="margin:0">{{ '@'.$user->username }}</p></div>
      </div>
      <dl class="kv">
        @if($user->matricule)<dt>Matricule</dt><dd>{{ $user->matricule }}</dd>@endif
        <dt>Compte créé</dt><dd>{{ $user->created_at?->format('d/m/Y') }}</dd>
        <dt>Dernière connexion</dt><dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
      </dl>
      <form method="post" action="{{ route('profil.photo') }}" enctype="multipart/form-data" style="margin-top:16px">
        @csrf
        <x-field name="photo" label="Changer la photo" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG, PNG ou WebP, 2 Mo maximum." required/>
        <button class="btn secondary" type="submit" style="margin-top:8px"><x-icon name="upload"/> Envoyer la photo</button>
      </form>
    </section>
  </aside>
</div>
@endsection

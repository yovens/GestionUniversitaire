@extends('layouts.app')
@section('title', $user->exists ? 'Modifier le compte' : 'Nouveau compte')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('admin.utilisateurs.index') }}">Personnel</a></div><h1>{{ $user->exists ? $user->nom_complet : 'Nouveau compte du personnel' }}</h1></div></div>
<section class="panel" style="max-width:820px">
  <form method="post" action="{{ $user->exists ? route('admin.utilisateurs.update', $user) : route('admin.utilisateurs.store') }}">
    @csrf @if($user->exists) @method('put') @endif
    <div class="form-grid">
      <x-field name="role" label="Rôle" type="select" required data-role-select>
        @foreach($roles as $r)<option value="{{ $r->value }}" @selected(old('role', $user->role?->value) === $r->value)>{{ $r->label() }}</option>@endforeach
      </x-field>
      <div data-faculte-field>
        <x-field name="faculte_id" label="Faculté" type="select" hint="Un doyen ou une secrétaire ne voit que sa faculté. Laissez « Toutes » pour le secrétariat central.">
          <option value="">Toutes les facultés</option>
          @foreach($facultes as $f)<option value="{{ $f->id }}" @selected(old('faculte_id', $user->faculte_id) == $f->id)>{{ $f->nom }}</option>@endforeach
        </x-field>
      </div>
      <x-field name="prenom" label="Prénom" :value="$user->prenom" required/>
      <x-field name="nom" label="Nom" :value="$user->nom" required/>
      <x-field name="sexe" label="Sexe" type="select"><option value="">—</option><option value="F" @selected(old('sexe', $user->sexe) === 'F')>Femme</option><option value="M" @selected(old('sexe', $user->sexe) === 'M')>Homme</option></x-field>
      <x-field name="phone" label="Téléphone" type="tel" :value="$user->phone"/>
      <x-field name="email" label="E-mail" type="email" :value="$user->email"/>
      <x-field name="username" label="Identifiant de connexion" :value="$user->username" required hint="Lettres, chiffres, tirets. Ex. : jpierre"/>
      @unless($user->exists)
        <x-field name="password" label="Mot de passe provisoire" type="text" hint="Laissez vide pour en générer un automatiquement. Il devra être changé à la première connexion." class="full" autocomplete="off"/>
      @endunless
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">{{ $user->exists ? 'Enregistrer' : 'Créer le compte' }}</button>
      <a class="btn secondary" href="{{ route('admin.utilisateurs.index') }}">Annuler</a>
    </div>
  </form>
  @if($user->exists && $user->id !== $me->id)
    <form method="post" action="{{ route('admin.utilisateurs.destroy', $user) }}" data-confirm="Supprimer définitivement l'accès de {{ $user->nom_complet }} ? L'historique est conservé." style="margin-top:12px">
      @csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer ce compte</button>
    </form>
  @endif
</section>
@endsection

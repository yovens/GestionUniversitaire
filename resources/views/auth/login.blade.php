@extends('layouts.guest')
@section('title', 'Connexion')
@section('content')
@php
  try { $facs = \App\Models\Faculte::actives()->get(['code', 'nom', 'couleur']); } catch (\Throwable) { $facs = collect(); }
@endphp
<div class="auth">
  <section class="auth-side">
    <a class="brand" href="{{ route('login') }}">
      <span class="brand-mark"><img src="{{ asset('images/logo.png') }}" alt=""></span>
      <span><strong>{{ config('itac.etablissement.sigle') }}</strong><span>{{ config('itac.etablissement.adresse') }}</span></span>
    </a>
    <div>
      <h1>{{ config('itac.etablissement.nom') }}</h1>
      <p>Inscriptions, notes, paiements et calendrier de toutes les facultés, au même endroit.</p>
      @if($facs->isNotEmpty())
        <div class="auth-facs">
          @foreach($facs as $f)<span style="--fac: {{ $f->couleur }}" title="{{ $f->nom }}">{{ $f->nom }}</span>@endforeach
        </div>
      @endif
    </div>
    <p class="small">Un problème de connexion ? Adressez-vous au secrétariat de votre faculté.</p>
  </section>
  <section class="auth-main">
    <div class="auth-card">
      <h2>Se connecter</h2>
      <p class="muted">Utilisez votre identifiant, votre matricule ou votre e-mail.</p>
      @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
      <form method="post" action="{{ route('login.attempt') }}" novalidate>
        @csrf
        <x-field name="username" label="Identifiant" :value="old('username')" autocomplete="username" autofocus required/>
        <x-field name="password" label="Mot de passe" type="password" autocomplete="current-password" required/>
        <div class="field"><label class="check"><input type="checkbox" name="remember" value="1"> Rester connecté sur cet appareil</label></div>
        <button class="btn" type="submit">Se connecter</button>
      </form>
    </div>
  </section>
</div>
@endsection

@extends($force ? 'layouts.guest' : 'layouts.app')
@section('title', 'Mot de passe')
@section('content')
@if($force)<div class="auth"><section class="auth-side"><div><h1>Bienvenue</h1><p>Pour protéger votre compte, choisissez un mot de passe personnel avant de continuer.</p></div></section><section class="auth-main"><div class="auth-card">@else<div class="panel" style="max-width:560px">@endif
  <h2>{{ $force ? 'Choisissez votre mot de passe' : 'Changer mon mot de passe' }}</h2>
  <p class="muted">Au moins 8 caractères, avec des lettres et des chiffres.</p>
  <form method="post" action="{{ route('password.update') }}">
    @csrf @method('put')
    <x-field name="current_password" :label="$force ? 'Mot de passe provisoire' : 'Mot de passe actuel'" type="password" autocomplete="current-password" required/>
    <x-field name="password" label="Nouveau mot de passe" type="password" autocomplete="new-password" required/>
    <x-field name="password_confirmation" label="Confirmer le nouveau mot de passe" type="password" autocomplete="new-password" required/>
    <button class="btn" type="submit">Enregistrer le mot de passe</button>
  </form>
  @if($force)
    <form method="post" action="{{ route('logout') }}" style="margin-top:14px">@csrf<button class="btn ghost" type="submit">Se déconnecter</button></form>
  @endif
@if($force)</div></section></div>@else</div>@endif
@endsection

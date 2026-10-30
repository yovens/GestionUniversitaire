<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Tableau de bord') · {{ config('itac.etablissement.sigle') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Bricolage+Grotesque:opsz,wght@12..96,500..700&display=swap">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
  <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
</head>
<body>
@php($anneeCourante = \App\Models\AnneeAcademique::courante())
<div class="shell">
  @include('partials.sidebar')
  <div class="main">
    <header class="topbar">
      <button class="btn secondary sm menu-toggle" type="button" data-menu-toggle aria-label="Ouvrir le menu"><x-icon name="menu"/></button>
      <div class="spacer"></div>
      @if($anneeCourante)
        <span class="year-pill">Année <b>{{ $anneeCourante->libelle }}</b></span>
      @endif
      @if($me->faculte)
        <x-fac :faculte="$me->faculte"/>
      @endif
      <a class="user-chip" href="{{ route('profil.show') }}">
        <x-avatar :user="$me"/>
        <span class="who">{{ $me->nom_complet }}<small>{{ $me->role->label() }}</small></span>
      </a>
      <form method="post" action="{{ route('logout') }}" class="inline">
        @csrf
        <button class="btn ghost sm" type="submit" title="Se déconnecter"><x-icon name="logout"/><span class="sr-only">Se déconnecter</span></button>
      </form>
    </header>
    <main class="content" id="contenu">
      @include('partials.flash')
      @yield('content')
    </main>
  </div>
</div>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>

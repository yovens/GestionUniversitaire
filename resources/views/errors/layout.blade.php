<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · {{ config('itac.etablissement.sigle', 'ITAC') }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body><div class="error-page"><div class="box">
  <div class="code">@yield('code')</div>
  <h1>@yield('title')</h1>
  <p class="muted">@yield('message')</p>
  <p style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="{{ url('/') }}">Retour à l'accueil</a>
    <a class="btn secondary" href="{{ url()->previous() }}">Page précédente</a>
  </p>
</div></div></body></html>

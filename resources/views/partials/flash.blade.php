@if(session('identifiants'))
  <div class="credentials" role="status">
    <p>Identifiants à remettre à la personne. Ce mot de passe provisoire ne sera plus affiché : il devra être changé à la première connexion.</p>
    <dl>
      <dt>Identifiant</dt><dd>{{ session('identifiants.username') }}</dd>
      <dt>Mot de passe</dt><dd>{{ session('identifiants.password') }}</dd>
    </dl>
  </div>
@endif
@if(session('success'))
  <div class="alert success" role="status"><x-icon name="check" width="18" height="18"/><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
  <div class="alert error" role="alert"><div>{{ session('error') }}</div></div>
@endif
@if($errors->any() && ! request()->routeIs('login'))
  <div class="alert error" role="alert">
    <div>Le formulaire contient {{ $errors->count() > 1 ? $errors->count().' erreurs' : 'une erreur' }} :
      <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  </div>
@endif

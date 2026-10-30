@extends('layouts.app')
@section('title', 'Personnel')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Personnel</h1><p>Administrateurs, doyens, secrétaires et professeurs. Les comptes étudiants se créent à l'inscription.</p></div>
  <div class="actions"><a class="btn" href="{{ route('admin.utilisateurs.create') }}"><x-icon name="plus"/> Nouveau compte</a></div>
</div>
<form class="filters" method="get">
  <div class="field grow"><label for="q">Rechercher</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, identifiant, téléphone"></div>
  <div class="field"><label for="role">Rôle</label><select id="role" name="role" data-autosubmit><option value="">Tous</option>@foreach($roles as $r)<option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
  <div class="field"><label for="faculte">Faculté</label><select id="faculte" name="faculte" data-autosubmit><option value="">Toutes</option>@foreach($facultes as $f)<option value="{{ $f->id }}" @selected(request('faculte') == $f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <button class="btn secondary" type="submit">Filtrer</button>
</form>
<section class="panel flush">
  @if($users->isEmpty())
    <x-empty title="Aucun compte trouvé">Modifiez les filtres ou créez un compte.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Nom</th><th>Rôle</th><th>Faculté</th><th>Identifiant</th><th>Dernière connexion</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $u)
      <tr @class(['is-off' => ! $u->actif])>
        <td><div class="person"><x-avatar :user="$u"/><div><strong>{{ $u->nom_complet }}</strong><small>{{ $u->phone ?: $u->email }}</small></div></div></td>
        <td>{{ $u->role->label() }}</td>
        <td>@if($u->faculte)<x-fac :faculte="$u->faculte"/>@else<span class="muted small">Toutes</span>@endif</td>
        <td>{{ $u->username }}</td>
        <td class="small">{{ $u->last_login_at?->diffForHumans() ?? 'Jamais' }}</td>
        <td><div class="row-actions">
          <a class="btn ghost sm" href="{{ route('admin.utilisateurs.edit', $u) }}">Modifier</a>
          <form method="post" action="{{ route('admin.utilisateurs.reinitialiser', $u) }}" data-confirm="Générer un nouveau mot de passe provisoire pour {{ $u->nom_complet }} ?">@csrf<button class="btn ghost sm" type="submit">Mot de passe</button></form>
          @if($u->id !== $me->id)
          <form method="post" action="{{ route('admin.utilisateurs.basculer', $u) }}">@csrf<button class="btn secondary sm" type="submit">{{ $u->actif ? 'Désactiver' : 'Activer' }}</button></form>
          @endif
        </div></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $users->links() }}
  @endif
</section>
@endsection

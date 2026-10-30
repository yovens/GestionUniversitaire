@extends('layouts.app')
@section('title', 'Affectations')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Affectations {{ $annee->libelle }}</h1><p>Choisissez le professeur de chaque cours. Le professeur pourra alors saisir les notes de ce cours.</p></div>
</div>
<form class="filters" method="get">
  @include('partials.filtre-faculte')
  <div class="field"><label class="check" style="margin-top:22px"><input type="checkbox" name="sans_prof" value="1" @checked(request('sans_prof')) data-autosubmit> Seulement les cours sans professeur</label></div>
</form>
@if($professeurs->isEmpty())
  <div class="alert warn">Aucun professeur actif. @if($me->isAdmin())<a href="{{ route('admin.utilisateurs.create') }}">Créer un compte professeur</a>.@endif</div>
@endif
@forelse($matieres as $classe => $liste)
  <section class="panel flush" style="--fac: {{ $liste->first()->classe->faculte->couleur }}">
    <div class="panel-head"><h3 class="fac-rule">{{ $classe }}</h3></div>
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Cours</th><th class="num">Session</th><th>Professeur</th><th></th></tr></thead>
      <tbody>
      @foreach($liste as $m)
        @php($a = $m->affectations->first())
        <tr>
          <td class="strong">{{ $m->nom }} <span class="small muted">{{ $m->code }}</span></td>
          <td class="num">{{ $m->session }}</td>
          <td>
            <form method="post" action="{{ route('affectations.store') }}" style="display:flex;gap:6px;align-items:center">
              @csrf <input type="hidden" name="matiere_id" value="{{ $m->id }}">
              <select name="professeur_id" aria-label="Professeur pour {{ $m->nom }}" style="min-width:220px" required>
                <option value="">— Aucun —</option>
                @foreach($professeurs as $p)<option value="{{ $p->id }}" @selected($a?->professeur_id === $p->id)>{{ $p->nom }} {{ $p->prenom }}</option>@endforeach
              </select>
              <button class="btn secondary sm" type="submit">{{ $a ? 'Changer' : 'Affecter' }}</button>
            </form>
          </td>
          <td class="row-actions">
            @if($a)<form method="post" action="{{ route('affectations.destroy', $a) }}" data-confirm="Retirer {{ $a->professeur->nom_complet }} de ce cours ?">@csrf @method('delete')<button class="btn ghost sm" type="submit">Retirer</button></form>@endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </section>
@empty
  <div class="panel"><x-empty title="Aucun cours à afficher">Créez d'abord les cours dans « Cours ».</x-empty></div>
@endforelse
@endsection

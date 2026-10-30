@extends('layouts.app')
@section('title', 'Notes')
@section('content')
<div class="page-head"><div class="titles"><h1>Saisie des notes</h1><p>Choisissez un cours et un examen pour ouvrir la grille de la classe. Les notes sont sur {{ config('itac.notes.max') }}.</p></div></div>
@if(! $annee)
  <div class="alert warn">Aucune année académique courante.</div>
@else
  @if($me->isStaff())
  <form class="filters" method="get">
    <div class="field grow"><label for="classe">Classe</label><select id="classe" name="classe" data-autosubmit><option value="">Choisir une classe…</option>
      @foreach($classes->groupBy(fn ($c) => $c->faculte->nom) as $fac => $liste)<optgroup label="{{ $fac }}">@foreach($liste as $c)<option value="{{ $c->id }}" @selected(request('classe') == $c->id)>{{ $c->nom }}</option>@endforeach</optgroup>@endforeach
    </select></div>
  </form>
  @endif
  @if($lignes->isEmpty())
    <div class="panel"><x-empty title="{{ $me->isProfesseur() ? 'Aucun cours affecté cette année' : 'Choisissez une classe' }}">
      {{ $me->isProfesseur() ? 'Vos cours apparaîtront ici dès leur affectation.' : 'Les cours de la classe et leurs examens apparaîtront ici.' }}</x-empty></div>
  @else
  <section class="panel flush">
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Cours</th><th>Classe</th><th>Examens de la session</th></tr></thead>
      <tbody>
      @foreach($lignes as $l)
        <tr>
          <td class="strong">{{ $l['matiere']->nom }}<div class="small muted">{{ $l['matiere']->code }} · session {{ $l['matiere']->session }}</div></td>
          <td><x-fac :faculte="$l['matiere']->classe->faculte"/> {{ $l['matiere']->classe->nom }}</td>
          <td>
            @forelse($l['examens'] as $e)
              @php($n = $compte[$l['matiere']->id.'-'.$e->id] ?? 0)
              <a class="btn {{ $e->notes_publiees ? 'secondary' : '' }} sm" style="margin:2px" href="{{ route('notes.saisie', ['examen' => $e->id, 'matiere' => $l['matiere']->id]) }}">
                @if($e->notes_publiees)<x-icon name="lock"/>@endif {{ $e->nom }} <span class="small" style="opacity:.8">({{ $n }})</span></a>
            @empty
              <span class="small muted">Aucun examen planifié pour cette session.</span>
            @endforelse
          </td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </section>
  <p class="small muted"><x-icon name="lock" width="14" height="14"/> = notes publiées : consultation seule pour les professeurs. Le chiffre entre parenthèses indique les notes déjà saisies.</p>
  @endif
@endif
@endsection

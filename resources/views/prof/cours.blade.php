@extends('layouts.app')
@section('title', 'Mes cours')
@section('content')
<div class="page-head"><div class="titles"><h1>Mes cours</h1><p>Tous les cours qui vous ont été affectés, par année.</p></div></div>
@forelse($affectations as $annee => $liste)
  <section class="panel flush">
    <div class="panel-head"><h2>{{ $annee }}</h2></div>
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Cours</th><th>Classe</th><th class="num">Coef.</th><th class="num">Session</th><th></th></tr></thead>
      <tbody>@foreach($liste as $a)
        <tr><td class="strong">{{ $a->matiere->nom }} <span class="small muted">{{ $a->matiere->code }}</span></td><td><x-fac :faculte="$a->matiere->classe->faculte"/> {{ $a->matiere->classe->nom }}</td>
          <td class="num">{{ $a->matiere->coefficient }}</td><td class="num">{{ $a->matiere->session }}</td>
          <td class="row-actions"><a class="btn secondary sm" href="{{ route('prof.cours.show', $a) }}">Liste et notes</a></td></tr>
      @endforeach</tbody>
    </table></div>
  </section>
@empty
  <div class="panel"><x-empty title="Aucun cours affecté">Le doyen de votre faculté vous affectera vos cours.</x-empty></div>
@endforelse
@endsection

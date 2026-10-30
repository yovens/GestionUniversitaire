@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Bonjour, {{ $me->prenom }}</h1><p>Vos cours @if($annee)pour l'année {{ $annee->libelle }}@endif</p></div>
  <div class="actions"><a class="btn" href="{{ route('notes.saisie') }}"><x-icon name="pen"/> Saisir des notes</a></div>
</div>

<div class="split">
  <section class="panel flush">
    <div class="panel-head"><h2>Mes cours</h2><a class="small" href="{{ route('prof.cours') }}">Historique</a></div>
    @if($affectations->isEmpty())
      <x-empty title="Aucun cours affecté">Le doyen ou l'administration vous affectera vos cours pour l'année en cours.</x-empty>
    @else
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Cours</th><th>Classe</th><th class="num">Étudiants</th><th class="num">Notes saisies</th><th></th></tr></thead>
      <tbody>
      @foreach($affectations as $a)
        <tr>
          <td class="strong">{{ $a->matiere->nom }}<div class="small muted">{{ $a->matiere->code }} · session {{ $a->matiere->session }}</div></td>
          <td><x-fac :faculte="$a->matiere->classe->faculte"/> {{ $a->matiere->classe->nom }}</td>
          <td class="num">{{ $a->nb_etudiants }}</td>
          <td class="num">{{ $a->nb_notes }}</td>
          <td class="row-actions"><a class="btn secondary sm" href="{{ route('prof.cours.show', $a) }}">Ouvrir</a></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
    @endif
  </section>
  <section class="panel">
    <h3>Examens à venir</h3>
    @include('partials.liste-examens', ['examens' => $examens])
  </section>
</div>
@endsection

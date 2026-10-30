@extends('layouts.app')
@section('title', $affectation->matiere->nom)
@section('content')
@php($m = $affectation->matiere)
<div class="page-head" style="--fac: {{ $m->classe->faculte->couleur }}">
  <div class="titles"><div class="crumb"><a href="{{ route('prof.cours') }}">Mes cours</a></div><h1 class="fac-rule">{{ $m->nom }}</h1>
    <p>{{ $m->classe->nom }} · {{ $m->classe->faculte->nom }} · {{ $affectation->annee->libelle }} · session {{ $m->session }}</p></div>
  <div class="actions">
    @foreach($examens as $e)<a class="btn {{ $e->notes_publiees ? 'secondary' : '' }}" href="{{ route('notes.saisie', ['examen' => $e->id, 'matiere' => $m->id]) }}"><x-icon name="{{ $e->notes_publiees ? 'lock' : 'pen' }}"/> {{ $e->nom }}</a>@endforeach
  </div>
</div>
<section class="panel flush">
  @if($etudiants->isEmpty())
    <x-empty title="Aucun étudiant inscrit">La liste se remplira au fil des inscriptions.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Étudiant</th><th>Matricule</th>@foreach($examens as $e)<th class="num">{{ $e->nom }}</th>@endforeach<th class="num">Moyenne du cours</th></tr></thead>
    <tbody>
    @foreach($etudiants as $row)
      <tr><td class="strong">{{ $row['etudiant']->nom }} {{ $row['etudiant']->prenom }}</td><td class="small">{{ $row['etudiant']->matricule }}</td>
        @foreach($examens as $e)
          @php($n = $row['notes']->get($e->id))
          <td class="num">@if($n && $n->absent)<span class="badge warn">Abs.</span>@else<x-grade :value="$n ? (float) $n->valeur : null" :seuil="$seuil"/>@endif</td>
        @endforeach
        <td class="num"><x-grade :value="$row['moyenne']" :seuil="$seuil"/></td></tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</section>
@endsection

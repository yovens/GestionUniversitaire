@extends('layouts.app')
@section('title', 'Sujets d’examen')
@section('content')
<div class="page-head"><div class="titles"><h1>Sujets d’examen</h1><p>Déposez vos sujets : seul le secrétariat de la faculté concernée peut les ouvrir.</p></div></div>
<div class="split">
  <section class="panel flush">
    @if($sujets->isEmpty())
      <x-empty title="Aucun sujet déposé">Utilisez le formulaire pour envoyer votre premier sujet.</x-empty>
    @else
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Sujet</th><th>Cours</th><th>Examen</th><th>Déposé le</th><th></th></tr></thead>
      <tbody>@foreach($sujets as $s)
        <tr><td class="strong">{{ $s->titre }}@if($s->consigne)<div class="small muted">{{ \Illuminate\Support\Str::limit($s->consigne, 80) }}</div>@endif</td>
          <td>{{ $s->matiere->nom }}<div class="small muted">{{ $s->matiere->classe->nom }}</div></td><td>{{ $s->examen?->nom ?? '—' }}</td>
          <td class="small">{{ $s->created_at->format('d/m/Y') }}</td>
          <td><div class="row-actions">
            @if($s->fichier)<a class="btn ghost sm" href="{{ route('fichiers.sujet', $s) }}" target="_blank" rel="noopener">Ouvrir</a>@endif
            <form method="post" action="{{ route('prof.sujets.destroy', $s) }}" data-confirm="Supprimer ce sujet ?">@csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer</button></form>
          </div></td></tr>
      @endforeach</tbody>
    </table></div>
    @endif
  </section>
  <section class="panel">
    <h2>Déposer un sujet</h2>
    @if($affectations->isEmpty())
      <p class="muted">Aucun cours affecté cette année.</p>
    @else
    <form method="post" action="{{ route('prof.sujets.store') }}" enctype="multipart/form-data" class="stack">
      @csrf
      <x-field name="matiere_id" label="Cours" type="select" required>
        @foreach($affectations as $a)<option value="{{ $a->matiere_id }}" @selected(old('matiere_id') == $a->matiere_id)>{{ $a->matiere->nom }} — {{ $a->matiere->classe->nom }}</option>@endforeach
      </x-field>
      <x-field name="examen_id" label="Examen" type="select"><option value="">—</option>@foreach($examens as $e)<option value="{{ $e->id }}" @selected(old('examen_id') == $e->id)>{{ $e->libelle }}</option>@endforeach</x-field>
      <x-field name="titre" label="Titre" required/>
      <x-field name="consigne" label="Consignes (facultatif)" type="textarea"/>
      <x-field name="fichier" label="Fichier" type="file" accept=".pdf,.doc,.docx" hint="PDF ou Word, 5 Mo maximum."/>
      <button class="btn" type="submit"><x-icon name="upload"/> Déposer le sujet</button>
    </form>
    @endif
  </section>
</div>
@endsection

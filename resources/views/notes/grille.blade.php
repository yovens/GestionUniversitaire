@extends('layouts.app')
@section('title', 'Notes '.$matiere->code)
@section('content')
@php($seuil = (float) $matiere->classe->faculte->seuil_reussite)
<div class="page-head" style="--fac: {{ $matiere->classe->faculte->couleur }}">
  <div class="titles"><div class="crumb"><a href="{{ route('notes.saisie', $me->isStaff() ? ['classe' => $matiere->classe_id] : []) }}">Saisie des notes</a></div>
    <h1 class="fac-rule">{{ $matiere->nom }}</h1>
    <p>{{ $matiere->classe->nom }} · {{ $examen->nom }} ({{ $examen->type_label }}) · {{ $inscriptions->count() }} étudiant(s) · note sur {{ $max }}</p></div>
</div>
@if($lectureSeule)
  <div class="alert info"><x-icon name="lock" width="18" height="18"/><div>Les notes de cet examen sont publiées : la grille est en lecture seule. Pour une correction, adressez-vous au secrétariat.</div></div>
@elseif($examen->notes_publiees)
  <div class="alert warn">Notes déjà publiées : toute modification est visible immédiatement par les étudiants et inscrite au journal.</div>
@endif

@if($inscriptions->isEmpty())
  <div class="panel"><x-empty title="Aucun étudiant inscrit dans cette classe">Les étudiants inscrits pour {{ $examen->annee->libelle }} apparaîtront ici.</x-empty></div>
@else
<form method="post" action="{{ route('notes.enregistrer') }}" data-grade-grid data-seuil="{{ $seuil }}" data-max="{{ $max }}" class="panel flush">
  @csrf
  <input type="hidden" name="examen_id" value="{{ $examen->id }}"><input type="hidden" name="matiere_id" value="{{ $matiere->id }}">
  <div class="table-wrap"><table class="data grade-grid">
    <thead><tr><th class="num">#</th><th>Étudiant</th><th>Matricule</th><th class="num">Note / {{ $max }}</th><th>Absent</th><th class="small">Saisie</th></tr></thead>
    <tbody>
    @foreach($inscriptions as $idx => $i)
      @php($n = $notes->get($i->etudiant_id))
      <tr>
        <td class="num muted">{{ $idx + 1 }}</td>
        <td class="strong">{{ $i->etudiant->nom }} {{ $i->etudiant->prenom }}</td>
        <td class="small">{{ $i->etudiant->matricule }}</td>
        <td class="num">
          <label class="sr-only" for="n{{ $i->etudiant_id }}">Note de {{ $i->etudiant->nom_complet }}</label>
          <input id="n{{ $i->etudiant_id }}" type="text" inputmode="decimal" autocomplete="off" data-grade name="notes[{{ $i->etudiant_id }}]"
            value="{{ old('notes.'.$i->etudiant_id, $n && ! $n->absent ? rtrim(rtrim(number_format((float) $n->valeur, 2, '.', ''), '0'), '.') : '') }}" @disabled($lectureSeule)>
        </td>
        <td><label class="check"><input type="checkbox" name="absent[{{ $i->etudiant_id }}]" value="1" @checked(old('absent.'.$i->etudiant_id, $n?->absent)) @disabled($lectureSeule)><span class="sr-only">Absent</span></label></td>
        <td class="small muted">{{ $n?->updated_at?->format('d/m H:i') }}</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @unless($lectureSeule)
  <div class="sticky-actions">
    <button class="btn" type="submit"><x-icon name="check"/> Enregistrer les notes</button>
    <span class="small muted">Entrée ou ↓ : étudiant suivant. Une case vide efface la note. Absent compte 0 dans la moyenne.</span>
  </div>
  @endunless
</form>
@endif
@endsection

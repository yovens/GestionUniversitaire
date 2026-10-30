@extends('layouts.app')
@section('title', 'Frais & échéances')
@section('content')
<div class="page-head"><div class="titles"><h1>Frais & échéances {{ $annee->libelle }}</h1>
  <p>Montant de chaque versement par faculté. Un examen peut exiger qu'un versement soit réglé pour que l'étudiant voie ses notes.</p></div></div>
@php($peutModifier = $me->hasRole('admin', 'doyen'))
@forelse($facultes as $f)
  <section class="panel" style="--fac: {{ $f->couleur }}">
    <div class="panel-head"><h2 class="fac-rule">{{ $f->nom }}</h2><span class="muted">Total annuel : <strong>{{ number_format($f->tranches->sum('montant'), 2, ',', ' ') }} HTG</strong></span></div>
    @if($f->tranches->isEmpty())
      <p class="muted">Aucun versement défini : les notes ne sont pas bloquées pour cette faculté.</p>
    @else
    <div class="table-wrap"><table class="data">
      <thead><tr><th class="num">N°</th><th>Versement</th><th class="num">Montant</th><th>Date limite</th><th></th></tr></thead>
      <tbody>
      @foreach($f->tranches as $t)
        <tr><td class="num">{{ $t->numero }}</td><td class="strong">{{ $t->libelle }}</td><td class="num"><x-money :value="$t->montant"/></td><td>{{ $t->date_limite?->format('d/m/Y') ?? '—' }}</td>
          <td class="row-actions">@if($peutModifier)<form method="post" action="{{ route('frais.destroy', $t) }}" data-confirm="Supprimer ce versement ?">@csrf @method('delete')<button class="btn ghost sm" type="submit">Supprimer</button></form>@endif</td></tr>
      @endforeach
      </tbody>
    </table></div>
    @endif
    @if($peutModifier)
    <details class="disclose" style="margin-top:12px"><summary>Ajouter ou modifier un versement</summary>
      <form method="post" action="{{ route('frais.store') }}">
        @csrf <input type="hidden" name="faculte_id" value="{{ $f->id }}">
        <div class="form-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">
          <x-field name="numero" label="N°" type="number" min="1" max="12" :value="$f->tranches->count() + 1" required hint="Un n° existant est remplacé."/>
          <x-field name="libelle" label="Libellé" :value="'Versement '.($f->tranches->count() + 1)" required/>
          <x-field name="montant" label="Montant (HTG)" type="number" step="0.01" min="0" required/>
          <x-field name="date_limite" label="Date limite" type="date"/>
        </div>
        <button class="btn" type="submit" style="margin-top:10px">Enregistrer le versement</button>
      </form>
    </details>
    @endif
  </section>
@empty
  <div class="panel"><x-empty title="Aucune faculté">Créez d'abord les facultés.</x-empty></div>
@endforelse
@endsection

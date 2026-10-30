@extends('layouts.app')
@section('title', 'Encaisser un paiement')
@section('content')
<div class="page-head"><div class="titles"><div class="crumb"><a href="{{ route('paiements.index') }}">Paiements</a></div><h1>Encaisser un paiement</h1><p>Année {{ $annee->libelle }}. Un reçu numéroté est généré à l'enregistrement.</p></div></div>

@if(! $etudiant)
  <section class="panel" style="max-width:760px">
    <h2>1. Trouver l'étudiant</h2>
    <form method="get" style="display:flex;gap:8px;align-items:flex-end">
      <div class="field" style="flex:1"><label for="q">Nom, matricule ou téléphone</label><input id="q" type="search" name="q" value="{{ request('q') }}" autofocus required></div>
      <button class="btn" type="submit">Rechercher</button>
    </form>
    @if(request()->filled('q'))
      @if($resultats->isEmpty())
        <p class="muted" style="margin-top:14px">Aucun étudiant ne correspond à « {{ request('q') }} ».</p>
      @else
        <ul class="list" style="margin-top:14px">
          @foreach($resultats as $r)
            <li><div class="person grow"><x-avatar :user="$r"/><div><strong>{{ $r->nom_complet }}</strong><small>{{ $r->matricule }} · {{ $r->faculte?->nom }}</small></div></div>
              <a class="btn secondary sm" href="{{ route('paiements.create', ['etudiant' => $r->id]) }}">Choisir</a></li>
          @endforeach
        </ul>
      @endif
    @endif
  </section>
@else
<div class="split">
  <section class="panel">
    <div class="person" style="margin-bottom:16px"><x-avatar :user="$etudiant"/><div><strong>{{ $etudiant->nom_complet }}</strong><small>{{ $etudiant->matricule }} · <a href="{{ route('paiements.create') }}">changer d'étudiant</a></small></div></div>
    <form method="post" action="{{ route('paiements.store') }}" enctype="multipart/form-data">
      @csrf <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
      <div class="form-grid">
        <x-field name="tranche_numero" label="Versement concerné" type="select" data-tranche>
          <option value="">Non précisé</option>
          @foreach($situation['tranches'] as $l)
            <option value="{{ $l['tranche']->numero }}" data-reste="{{ number_format($l['reste'], 2, '.', '') }}" @selected(old('tranche_numero', $situation['prochaine']['tranche']->numero ?? null) == $l['tranche']->numero)>
              {{ $l['tranche']->libelle }} — reste {{ number_format($l['reste'], 0, ',', ' ') }} HTG</option>
          @endforeach
        </x-field>
        <x-field name="montant" label="Montant reçu (HTG)" type="number" step="0.01" min="1" :value="old('montant', $situation['prochaine'] ? number_format($situation['prochaine']['reste'], 2, '.', '') : null)" required/>
        <x-field name="date_paiement" label="Date du paiement" type="date" :value="old('date_paiement', today()->format('Y-m-d'))" :max="today()->format('Y-m-d')" required/>
        <x-field name="methode" label="Méthode" type="select" required>
          @foreach(\App\Models\Paiement::METHODES as $k => $v)<option value="{{ $k }}" @selected(old('methode') === $k)>{{ $v }}</option>@endforeach
        </x-field>
        <x-field name="reference" label="Référence de transaction" hint="N° MonCash, bordereau de banque…"/>
        <x-field name="justificatif" label="Justificatif (facultatif)" type="file" accept=".pdf,image/*" hint="PDF ou image, 5 Mo maximum."/>
        <x-field name="note" label="Remarque" class="full"/>
      </div>
      <div class="form-actions"><button class="btn" type="submit"><x-icon name="check"/> Enregistrer et générer le reçu</button><a class="btn secondary" href="{{ route('etudiants.show', $etudiant) }}">Annuler</a></div>
    </form>
  </section>
  <aside class="panel">
    <h3>Situation {{ $annee->libelle }}</h3>
    @include('partials.echeancier', ['situation' => $situation])
  </aside>
</div>
@endif
@endsection

@extends('layouts.app')
@section('title', 'Paiements')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Paiements</h1><p>{{ $totaux->nb }} paiement(s) valide(s) — total {{ number_format((float) $totaux->total, 2, ',', ' ') }} HTG</p></div>
  <div class="actions">
    <a class="btn secondary" href="{{ route('paiements.export', request()->query()) }}"><x-icon name="download"/> Exporter (Excel)</a>
    <a class="btn" href="{{ route('paiements.create') }}"><x-icon name="plus"/> Encaisser un paiement</a>
  </div>
</div>
<form class="filters" method="get">
  <div class="field grow"><label for="q">Rechercher</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="N° de reçu, nom, matricule"></div>
  @include('partials.filtre-annee')
  @include('partials.filtre-faculte')
  <div class="field"><label for="du">Du</label><input id="du" type="date" name="du" value="{{ request('du') }}"></div>
  <div class="field"><label for="au">Au</label><input id="au" type="date" name="au" value="{{ request('au') }}"></div>
  <div class="field"><label for="methode">Méthode</label><select id="methode" name="methode" data-autosubmit><option value="">Toutes</option>@foreach(\App\Models\Paiement::METHODES as $k => $v)<option value="{{ $k }}" @selected(request('methode') === $k)>{{ $v }}</option>@endforeach</select></div>
  <div class="field"><label for="etat">État</label><select id="etat" name="etat" data-autosubmit><option value="">Tous</option><option value="annule" @selected(request('etat') === 'annule')>Annulés</option></select></div>
  <button class="btn secondary" type="submit">Filtrer</button>
</form>
<section class="panel flush">
  @if($paiements->isEmpty())
    <x-empty title="Aucun paiement" action="Encaisser un paiement" :href="route('paiements.create')">Aucun paiement ne correspond à ces filtres.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Reçu</th><th>Date</th><th>Étudiant</th><th>Faculté</th><th class="num">Tranche</th><th class="num">Montant</th><th>Méthode</th><th>Encaissé par</th></tr></thead>
    <tbody>
    @foreach($paiements as $p)
      <tr @class(['is-off' => $p->estAnnule()])>
        <td><a href="{{ route('paiements.recu', $p) }}" target="_blank" rel="noopener">{{ $p->numero_recu }}</a>@if($p->estAnnule()) <span class="badge danger">Annulé</span>@endif</td>
        <td>{{ $p->date_paiement->format('d/m/Y') }}</td>
        <td>@if($p->etudiant)<a href="{{ route('etudiants.show', $p->etudiant) }}">{{ $p->etudiant->nom_complet }}</a><div class="small muted">{{ $p->etudiant->matricule }}</div>@endif</td>
        <td><x-fac :faculte="$p->etudiant?->faculte"/></td>
        <td class="num">{{ $p->tranche_numero ?? '—' }}</td>
        <td class="num"><x-money :value="$p->montant"/></td>
        <td>{{ $p->methode_label }}@if($p->reference)<div class="small muted">{{ $p->reference }}</div>@endif</td>
        <td class="small">{{ $p->caissier?->nom_complet }}</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  {{ $paiements->links() }}
  @endif
</section>
@endsection

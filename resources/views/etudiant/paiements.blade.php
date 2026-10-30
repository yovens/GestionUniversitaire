@extends('layouts.app')
@section('title', 'Mes paiements')
@section('content')
<div class="page-head"><div class="titles"><h1>Mes paiements</h1><p>Année {{ $annee?->libelle }} · reste à payer {{ number_format($situation['solde'], 2, ',', ' ') }} HTG</p></div></div>
<div class="split">
  <section class="panel flush">
    <div class="panel-head"><h2>Reçus</h2></div>
    @if($paiements->isEmpty())
      <x-empty title="Aucun paiement enregistré">Vos versements enregistrés par la caisse apparaîtront ici avec leur reçu.</x-empty>
    @else
    <div class="table-wrap"><table class="data">
      <thead><tr><th>Reçu</th><th>Date</th><th>Année</th><th class="num">Montant</th><th>Méthode</th></tr></thead>
      <tbody>@foreach($paiements as $p)
        <tr @class(['is-off' => $p->estAnnule()])>
          <td>@if($p->estAnnule()){{ $p->numero_recu }} <span class="badge danger">Annulé</span>@else<a href="{{ route('paiements.recu', $p) }}" target="_blank" rel="noopener">{{ $p->numero_recu }}</a>@endif</td>
          <td>{{ $p->date_paiement->format('d/m/Y') }}</td><td class="small">{{ $p->annee?->libelle }}</td>
          <td class="num"><x-money :value="$p->montant"/></td><td>{{ $p->methode_label }}</td></tr>
      @endforeach</tbody>
    </table></div>
    @endif
  </section>
  <aside class="panel"><h3>Échéancier</h3>@include('partials.echeancier', ['situation' => $situation])</aside>
</div>
@endsection

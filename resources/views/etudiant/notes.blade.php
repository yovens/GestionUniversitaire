@extends('layouts.app')
@section('title', 'Mes notes')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Mes notes</h1>@if($inscription)<p>{{ $inscription->classe->nom }} · {{ $inscription->classe->faculte->nom }} · {{ $inscription->annee->libelle }}</p>@endif</div>
  <div class="actions">
    @if($inscriptions->count() > 1)
      <form method="get"><select name="inscription" data-autosubmit aria-label="Année">@foreach($inscriptions as $i)<option value="{{ $i->id }}" @selected($inscription?->id === $i->id)>{{ $i->annee->libelle }} — {{ $i->classe->nom }}</option>@endforeach</select></form>
    @endif
    @if($inscription)<a class="btn" href="{{ route('etudiant.bulletin', ['inscription' => $inscription->id]) }}"><x-icon name="download"/> Télécharger mon bulletin</a>@endif
  </div>
</div>
@if(! $inscription)
  <div class="panel"><x-empty title="Aucune inscription">Votre dossier d'inscription n'est pas encore enregistré.</x-empty></div>
@else
  @if($bulletin['examens_bloques']->isNotEmpty())
    <div class="alert warn"><div><strong>Notes masquées :</strong> {{ $bulletin['examens_bloques']->pluck('nom')->join(', ', ' et ') }}. Elles s'afficheront dès que le versement exigé sera réglé (reste à payer : {{ number_format($situation['solde'], 2, ',', ' ') }} HTG). <a href="{{ route('etudiant.paiements') }}">Voir ma situation</a></div></div>
  @endif
  <section class="panel">
    <div class="panel-head"><h2>Bulletin</h2><span class="small muted">Seuls les examens publiés sont pris en compte</span></div>
    @include('partials.table-bulletin', ['b' => $bulletin])
  </section>
  <section class="panel">
    <h2>Détail par examen</h2>
    @forelse($detail as $d)
      <details class="disclose" style="padding:10px 0;border-bottom:1px solid var(--line)" @if($loop->last) open @endif>
        <summary>{{ $d['examen']->nom }} <span class="muted small">· {{ $d['examen']->type_label }} · publié le {{ $d['examen']->publie_at?->format('d/m/Y') }}</span>
          @unless($d['accessible'])<span class="badge warn">Paiement requis</span>@endunless</summary>
        @if(! $d['accessible'])
          <p class="muted">Réglez le versement {{ $d['examen']->tranche_requise }} pour voir ces notes.</p>
        @elseif($d['notes']->isEmpty())
          <p class="muted">Aucune note enregistrée pour vous dans cet examen.</p>
        @else
          <table class="data"><tbody>@foreach($d['notes'] as $n)
            <tr><td>{{ $n->matiere->nom }}</td><td class="num">@if($n->absent)<span class="badge warn">Absent</span>@else<x-grade :value="(float) $n->valeur" :seuil="$bulletin['seuil']"/>@endif</td></tr>
          @endforeach</tbody></table>
        @endif
      </details>
    @empty
      <p class="muted">Aucun résultat publié pour le moment.</p>
    @endforelse
  </section>
@endif
@endsection

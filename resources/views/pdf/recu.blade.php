<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Reçu {{ $paiement->numero_recu }}</title>@include('pdf._style')</head>
<body>
@include('pdf._entete', ['faculte' => $paiement->etudiant?->faculte])
<h1>Reçu de paiement n° {{ $paiement->numero_recu }}</h1>
@if($paiement->estAnnule())<p><span class="stamp">REÇU ANNULÉ le {{ $paiement->annule_at->format('d/m/Y') }}</span> — {{ $paiement->motif_annulation }}</p>@endif
<table class="kv">
  <tr><td class="k">Étudiant</td><td><strong>{{ $paiement->etudiant?->nom_complet }}</strong></td></tr>
  <tr><td class="k">Matricule</td><td>{{ $paiement->etudiant?->matricule }}</td></tr>
  <tr><td class="k">Année académique</td><td>{{ $paiement->annee?->libelle }}</td></tr>
  <tr><td class="k">Date du paiement</td><td>{{ $paiement->date_paiement->format('d/m/Y') }}</td></tr>
  <tr><td class="k">Versement</td><td>{{ $paiement->tranche_numero ? 'n° '.$paiement->tranche_numero : 'Non précisé' }}</td></tr>
  <tr><td class="k">Méthode</td><td>{{ $paiement->methode_label }}@if($paiement->reference) — réf. {{ $paiement->reference }}@endif</td></tr>
</table>
<table class="t" style="margin-top:12px">
  <tr><th>Montant reçu</th><td class="n" style="font-size:14px"><strong>{{ number_format((float) $paiement->montant, 2, ',', ' ') }} HTG</strong></td></tr>
  <tr><th>Total payé sur l'année</th><td class="n">{{ number_format($situation['total_paye'], 2, ',', ' ') }} HTG</td></tr>
  <tr><th>Reste à payer</th><td class="n">{{ number_format($situation['solde'], 2, ',', ' ') }} HTG</td></tr>
</table>
@if($paiement->note)<p class="muted" style="margin-top:8px">Remarque : {{ $paiement->note }}</p>@endif
<table class="sign"><tr><td></td><td><span>Caisse{{ $paiement->caissier ? " — ".$paiement->caissier->nom_complet : "" }}</span></td></tr></table>
<div class="foot">Reçu émis le {{ $paiement->created_at->format('d/m/Y à H:i') }} · document généré par le système de gestion {{ config('itac.etablissement.sigle') }}</div>
</body></html>

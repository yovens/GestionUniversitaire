@extends('layouts.app')
@section('title', $etudiant->nom_complet)
@section('content')
<div class="page-head" style="--fac: {{ $etudiant->faculte?->couleur ?? '#9aa3b5' }}">
  <div class="titles">
    <div class="crumb"><a href="{{ route('etudiants.index') }}">Étudiants</a></div>
    <div style="display:flex;gap:16px;align-items:center">
      <x-avatar :user="$etudiant" size="lg"/>
      <div>
        <h1 style="margin:0">{{ $etudiant->nom_complet }}</h1>
        <p>{{ $etudiant->matricule }} · <x-fac :faculte="$etudiant->faculte"/> @if($inscription){{ $inscription->classe->nom }} · {{ $annee->libelle }}@endif
          @unless($etudiant->actif)<span class="badge danger">Compte désactivé</span>@endunless</p>
      </div>
    </div>
  </div>
  <div class="actions">
    <a class="btn" href="{{ route('paiements.create', ['etudiant' => $etudiant->id]) }}"><x-icon name="wallet"/> Encaisser</a>
    @if($inscription)<a class="btn secondary" href="{{ route('bulletins.pdf', $inscription) }}" target="_blank" rel="noopener"><x-icon name="file"/> Bulletin PDF</a>@endif
    <a class="btn secondary" href="{{ route('etudiants.edit', $etudiant) }}"><x-icon name="pen"/> Modifier</a>
  </div>
</div>

@if(session('recu'))
  <div class="alert info"><x-icon name="receipt" width="18" height="18"/><div><a href="{{ route('paiements.recu', session('recu')) }}" target="_blank" rel="noopener">Imprimer le reçu</a> pour le remettre à l'étudiant.</div></div>
@endif

<div class="split">
  <div>
    <section class="panel">
      <div class="panel-head"><h2>Résultats {{ $annee?->libelle }}</h2>
        @if($inscription)<span class="small muted">Vue secrétariat : inclut les examens non publiés</span>@endif</div>
      @if($bulletin)
        @include('partials.table-bulletin', ['b' => $bulletin])
      @else
        <p class="muted">Pas d'inscription pour cette année.</p>
      @endif
    </section>

    <section class="panel flush">
      <div class="panel-head"><h2>Paiements</h2><a class="small" href="{{ route('paiements.create', ['etudiant' => $etudiant->id]) }}">Encaisser un paiement</a></div>
      @if($paiements->isEmpty())
        <x-empty title="Aucun paiement">Les versements de l'étudiant apparaîtront ici.</x-empty>
      @else
      <div class="table-wrap"><table class="data">
        <thead><tr><th>Reçu</th><th>Date</th><th>Année</th><th class="num">Tranche</th><th class="num">Montant</th><th>Méthode</th><th></th></tr></thead>
        <tbody>
        @foreach($paiements as $p)
          <tr @class(['is-off' => $p->estAnnule()])>
            <td class="nowrap"><a href="{{ route('paiements.recu', $p) }}" target="_blank" rel="noopener">{{ $p->numero_recu }}</a></td>
            <td>{{ $p->date_paiement->format('d/m/Y') }}</td>
            <td class="small">{{ $p->annee?->libelle }}</td>
            <td class="num">{{ $p->tranche_numero ?? '—' }}</td>
            <td class="num"><x-money :value="$p->montant"/></td>
            <td>{{ $p->methode_label }}@if($p->justificatif) · <a href="{{ route('fichiers.justificatif', $p) }}" target="_blank" rel="noopener">justificatif</a>@endif</td>
            <td class="row-actions">
              @if($p->estAnnule())
                <span class="badge danger" title="{{ $p->motif_annulation }}">Annulé</span>
              @else
                <details class="disclose small"><summary>Annuler</summary>
                  <form method="post" action="{{ route('paiements.annuler', $p) }}" data-confirm="Annuler définitivement le reçu {{ $p->numero_recu }} ?">
                    @csrf <input type="text" name="motif_annulation" required minlength="5" placeholder="Motif de l'annulation">
                    <button class="btn danger sm" type="submit" style="margin-top:6px">Confirmer l'annulation</button>
                  </form>
                </details>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
      @endif
    </section>
  </div>

  <aside>
    <section class="panel">
      <h3>Frais {{ $annee?->libelle }}</h3>
      @include('partials.echeancier', ['situation' => $situation])
    </section>

    @if($inscription)
    <section class="panel">
      <h3>Inscription</h3>
      <form method="post" action="{{ route('inscriptions.statut', $inscription) }}" style="display:flex;gap:8px;align-items:flex-end">
        @csrf @method('put')
        <div class="field" style="flex:1"><label for="statut">Statut</label>
          <select id="statut" name="statut">@foreach(\App\Models\Inscription::STATUTS as $k => $v)<option value="{{ $k }}" @selected($inscription->statut === $k)>{{ $v }}</option>@endforeach</select></div>
        <button class="btn secondary" type="submit">Mettre à jour</button>
      </form>
      <p class="small muted" style="margin-top:8px">Inscrit(e) le {{ $inscription->date_inscription->format('d/m/Y') }}</p>
    </section>
    @elseif($anneeCourante)
    <section class="panel">
      <h3>Réinscrire pour {{ $anneeCourante->libelle }}</h3>
      <form method="post" action="{{ route('etudiants.reinscrire', $etudiant) }}">
        @csrf
        <x-field name="classe_id" label="Classe" type="select" required>
          @foreach($classes->groupBy(fn ($c) => $c->faculte->nom) as $fac => $liste)
            <optgroup label="{{ $fac }}">@foreach($liste as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach</optgroup>
          @endforeach
        </x-field>
        <button class="btn" type="submit" style="margin-top:10px">Réinscrire</button>
      </form>
    </section>
    @endif

    <section class="panel">
      <h3>Dossier</h3>
      <dl class="kv">
        <dt>Identifiant</dt><dd>{{ $etudiant->username }}</dd>
        <dt>Téléphone</dt><dd>{{ $etudiant->phone ?: '—' }}</dd>
        <dt>E-mail</dt><dd>{{ $etudiant->email ?: '—' }}</dd>
        <dt>Naissance</dt><dd>{{ $etudiant->date_naissance?->format('d/m/Y') ?? '—' }}@if($etudiant->lieu_naissance) à {{ $etudiant->lieu_naissance }}@endif</dd>
        <dt>Adresse</dt><dd>{{ $etudiant->adresse ?: '—' }}</dd>
        <dt>Urgence</dt><dd>{{ $etudiant->contact_urgence ?: '—' }}</dd>
      </dl>
      <h3 style="margin-top:16px">Parcours</h3>
      <ul class="list">
        @foreach($etudiant->inscriptions as $i)
          <li><div class="grow"><strong>{{ $i->annee->libelle }}</strong> — {{ $i->classe->nom }}<div class="small muted">{{ $i->classe->faculte->nom }}</div></div><span class="badge">{{ $i->statut_label }}</span></li>
        @endforeach
      </ul>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
        <form method="post" action="{{ route('etudiants.reinitialiser', $etudiant) }}" data-confirm="Générer un nouveau mot de passe provisoire ?">@csrf<button class="btn secondary sm" type="submit"><x-icon name="lock"/> Nouveau mot de passe</button></form>
        <form method="post" action="{{ route('etudiants.destroy', $etudiant) }}" data-confirm="Supprimer ce dossier ? Possible uniquement s'il n'a ni notes ni paiements.">@csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer le dossier</button></form>
      </div>
    </section>
  </aside>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Journal d’activité')
@section('content')
<div class="page-head"><div class="titles"><h1>Journal d’activité</h1><p>Toutes les actions sensibles : connexions, notes modifiées, paiements, annulations, comptes.</p></div></div>
<form class="filters" method="get">
  <div class="field grow"><label for="q">Rechercher</label><input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, reçu, cours…"></div>
  <div class="field"><label for="action">Type</label><select id="action" name="action" data-autosubmit><option value="">Tout</option>@foreach($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select></div>
  <button class="btn secondary" type="submit">Filtrer</button>
</form>
<section class="panel flush">
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Description</th><th>Adresse IP</th></tr></thead>
    <tbody>
    @forelse($entrees as $e)
      <tr>
        <td class="small" style="white-space:nowrap">{{ $e->created_at->format('d/m/Y H:i') }}</td>
        <td>{{ $e->user?->nom_complet ?? '—' }}</td>
        <td><span class="badge">{{ $e->action }}</span></td>
        <td>{{ $e->description }}
          @if(! empty($e->details['changements']))
            <details class="disclose small"><summary>Détail des {{ count($e->details['changements']) }} changement(s)</summary>
              @foreach($e->details['changements'] as $c)<div>Étudiant #{{ $c['etudiant'] }} : {{ $c['avant'] ?? 'vide' }} → {{ $c['apres'] ?? 'supprimée' }}</div>@endforeach
            </details>
          @endif
        </td>
        <td class="small muted">{{ $e->ip }}</td>
      </tr>
    @empty
      <tr><td colspan="5"><x-empty title="Journal vide">Aucune action enregistrée pour ces filtres.</x-empty></td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $entrees->links() }}
</section>
@endsection

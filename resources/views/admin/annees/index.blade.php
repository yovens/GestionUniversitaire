@extends('layouts.app')
@section('title', 'Années académiques')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Années académiques</h1><p>L'année courante détermine les inscriptions, les examens et les frais affichés partout.</p></div>
  <div class="actions"><a class="btn" href="{{ route('admin.annees.create') }}"><x-icon name="plus"/> Nouvelle année</a></div>
</div>
<section class="panel flush">
  @if($annees->isEmpty())
    <x-empty title="Aucune année académique" action="Créer l'année" :href="route('admin.annees.create')">Créez l'année en cours pour pouvoir inscrire des étudiants.</x-empty>
  @else
  <div class="table-wrap"><table class="data">
    <thead><tr><th>Année</th><th>Début</th><th>Fin</th><th class="num">Inscrits</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @foreach($annees as $a)
      <tr>
        <td class="strong">{{ $a->libelle }}</td>
        <td>{{ $a->date_debut->format('d/m/Y') }}</td>
        <td>{{ $a->date_fin->format('d/m/Y') }}</td>
        <td class="num">{{ $a->nb_inscrits }}</td>
        <td>@if($a->courante)<span class="badge ok">Année courante</span>@endif</td>
        <td><div class="row-actions">
          @unless($a->courante)
            <form method="post" action="{{ route('admin.annees.courante', $a) }}" data-confirm="Faire de {{ $a->libelle }} l'année courante ? Les nouvelles inscriptions et les paiements y seront rattachés.">@csrf<button class="btn secondary sm" type="submit">Définir comme courante</button></form>
          @endunless
          <a class="btn ghost sm" href="{{ route('admin.annees.edit', $a) }}">Modifier</a>
          @unless($a->courante)
          <form method="post" action="{{ route('admin.annees.destroy', $a) }}" data-confirm="Supprimer l'année {{ $a->libelle }} ?">@csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer</button></form>
          @endunless
        </div></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</section>
@endsection

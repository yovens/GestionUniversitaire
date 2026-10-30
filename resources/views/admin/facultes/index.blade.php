@extends('layouts.app')
@section('title', 'Facultés')
@section('content')
<div class="page-head">
  <div class="titles"><h1>Facultés</h1><p>Chaque faculté a ses classes, ses cours, son échéancier de frais et son seuil de réussite.</p></div>
  <div class="actions"><a class="btn" href="{{ route('admin.facultes.create') }}"><x-icon name="plus"/> Nouvelle faculté</a></div>
</div>
@if($facultes->isEmpty())
  <div class="panel"><x-empty title="Aucune faculté" action="Créer la première faculté" :href="route('admin.facultes.create')">Ajoutez Informatique, Génie civil, Agronomie, Sciences administratives…</x-empty></div>
@else
<div class="fac-bands">
  @foreach($facultes as $f)
    <div class="fac-band" style="--fac: {{ $f->couleur }}">
      <span class="code">{{ $f->code }}</span>
      <span><span class="name">{{ $f->nom }}</span> @unless($f->actif)<span class="badge">inactive</span>@endunless
        <span class="small muted" style="display:block">Seuil de réussite : {{ rtrim(rtrim(number_format($f->seuil_reussite, 2, ',', ''), '0'), ',') }}/100</span></span>
      <span class="stat"><b>{{ $f->classes_count }}</b><span>classes</span></span>
      <span class="stat optional"><b>{{ $f->matieres_count }}</b><span>cours</span></span>
      <span class="row-actions" style="display:flex;gap:6px;justify-content:flex-end">
        <a class="btn secondary sm" href="{{ route('admin.facultes.edit', $f) }}">Modifier</a>
        <form method="post" action="{{ route('admin.facultes.destroy', $f) }}" data-confirm="Supprimer la faculté {{ $f->nom }} ?">@csrf @method('delete')<button class="btn danger sm" type="submit">Supprimer</button></form>
      </span>
    </div>
  @endforeach
</div>
@endif
@endsection

@extends('layouts.app')
@section('title', 'Calendrier')
@section('content')
<div class="page-head"><div class="titles"><h1>Calendrier universitaire</h1><p>Rentrées, examens, reprises et congés. Les examens planifiés y apparaissent automatiquement.</p></div></div>
<div class="split">
  <section class="panel">
    <div class="cal-head">
      <a class="btn secondary sm" href="{{ route('calendrier', ['mois' => $mois->subMonth()->format('Y-m')]) }}" aria-label="Mois précédent">‹</a>
      <h2>{{ $mois->translatedFormat('F Y') }}</h2>
      <a class="btn secondary sm" href="{{ route('calendrier', ['mois' => $mois->addMonth()->format('Y-m')]) }}" aria-label="Mois suivant">›</a>
      <a class="btn ghost sm" href="{{ route('calendrier') }}">Aujourd'hui</a>
    </div>
    <div class="cal" role="grid" aria-label="Calendrier de {{ $mois->translatedFormat('F Y') }}">
      @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $d)<div class="dow" role="columnheader">{{ $d }}</div>@endforeach
      @foreach($jours as $j)
        <div @class(['day', 'out' => $j['hors_mois'], 'today' => $j['date']->isToday()]) role="gridcell">
          <span class="d">{{ $j['date']->day }}</span>
          @foreach($j['evenements']->take(3) as $e)
            <span class="ev ev-{{ $e->type }}" title="{{ $e->titre }}{{ $e->faculte ? ' — '.$e->faculte->code : '' }}">{{ $e->titre }}</span>
          @endforeach
          @if($j['evenements']->count() > 3)<span class="small muted">+{{ $j['evenements']->count() - 3 }}</span>@endif
        </div>
      @endforeach
    </div>
    <div class="legend" style="margin-top:12px">@foreach(\App\Models\Evenement::TYPES as $k => $v)<span class="ev ev-{{ $k }}">{{ $v }}</span>@endforeach</div>
  </section>
  <aside>
    <section class="panel">
      <h3>Prochains rendez-vous</h3>
      @if($aVenir->isEmpty())<p class="muted small">Rien de prévu.</p>@else
      <ul class="list">@foreach($aVenir as $e)
        <li><span class="date-box"><b>{{ $e->date_debut->format('d') }}</b><span>{{ $e->date_debut->translatedFormat('M') }}</span></span>
          <div class="grow"><strong>{{ $e->titre }}</strong><div class="small muted">{{ $e->type_label }}@if(! $e->date_fin->isSameDay($e->date_debut)) · jusqu'au {{ $e->date_fin->format('d/m') }}@endif</div>
            @if($e->description)<div class="small">{{ $e->description }}</div>@endif</div>
          <span style="display:flex;flex-direction:column;gap:4px;align-items:flex-end">
            <x-fac :faculte="$e->faculte"/>
            @if($peutGerer && ! $e->examen_id && ($me->faculteScope() === null || $e->faculte_id === $me->faculteScope()))
              <a class="small" href="{{ route('calendrier', ['mois' => $mois->format('Y-m'), 'modifier' => $e->id]) }}">Modifier</a>
              <form method="post" action="{{ route('calendrier.destroy', $e) }}" data-confirm="Supprimer « {{ $e->titre }} » ?">@csrf @method('delete')<button class="btn ghost sm" type="submit" style="min-height:0;padding:0">Supprimer</button></form>
            @endif
          </span></li>
      @endforeach</ul>@endif
    </section>
    @if($peutGerer)
    <section class="panel">
      <h3>{{ $edition ? 'Modifier l’événement' : 'Ajouter un événement' }}</h3>
      <form method="post" action="{{ $edition ? route('calendrier.update', $edition) : route('calendrier.store') }}" class="stack">
        @csrf @if($edition) @method('put') @endif
        <x-field name="titre" label="Titre" :value="$edition?->titre" required/>
        <x-field name="type" label="Type" type="select" required>@foreach(\App\Models\Evenement::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $edition?->type) === $k)>{{ $v }}</option>@endforeach</x-field>
        <x-field name="date_debut" label="Du" type="date" :value="$edition?->date_debut?->format('Y-m-d')" required/>
        <x-field name="date_fin" label="Au" type="date" :value="$edition?->date_fin?->format('Y-m-d')" required/>
        @if($me->faculteScope() === null)
          <x-field name="faculte_id" label="Faculté" type="select"><option value="">Toutes</option>@foreach($facultes as $f)<option value="{{ $f->id }}" @selected(old('faculte_id', $edition?->faculte_id) == $f->id)>{{ $f->nom }}</option>@endforeach</x-field>
        @endif
        <x-field name="description" label="Description" type="textarea" :value="$edition?->description"/>
        <button class="btn" type="submit">{{ $edition ? 'Enregistrer' : 'Ajouter au calendrier' }}</button>
      </form>
    </section>
    @endif
  </aside>
</div>
@endsection

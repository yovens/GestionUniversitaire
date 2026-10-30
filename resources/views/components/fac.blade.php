@props(['faculte'])
@if($faculte)
  <span class="fac-chip" style="--fac: {{ $faculte->couleur }}" title="{{ $faculte->nom }}">{{ $faculte->code }}</span>
@else
  <span class="fac-chip" style="--fac: #9aa3b5">Toutes</span>
@endif

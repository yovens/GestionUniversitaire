@props(['user', 'size' => null])
@if($user?->photo)
  <img src="{{ route('fichiers.photo', $user) }}" alt="" {{ $attributes->merge(['class' => 'avatar '.($size ?? '')]) }}>
@else
  <span {{ $attributes->merge(['class' => 'avatar '.($size ?? '')]) }} aria-hidden="true">{{ $user?->initiales }}</span>
@endif

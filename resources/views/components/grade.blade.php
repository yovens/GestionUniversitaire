@props(['value', 'seuil' => 65])
@if($value === null)
  <span class="muted">—</span>
@else
  <span @class(['grade', 'fail' => $value < $seuil, 'pass' => $value >= $seuil])>{{ number_format((float) $value, 2, ',', '') }}</span>
@endif

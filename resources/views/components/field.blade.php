@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'class' => '', 'required' => false])
@php $id = 'f_'.str_replace(['[', ']', '.'], '_', $name); $err = $errors->first(str_replace(['[', ']'], ['.', ''], $name)); @endphp
<div class="field {{ $class }}">
  <label for="{{ $id }}">{{ $label }}@if($required) <span aria-hidden="true" class="muted">*</span>@endif</label>
  @if($type === 'textarea')
    <textarea id="{{ $id }}" name="{{ $name }}" @class(['is-invalid' => $err]) {{ $required ? 'required' : '' }} {{ $attributes }}>{{ old($name, $value) }}</textarea>
  @elseif($type === 'select')
    <select id="{{ $id }}" name="{{ $name }}" @class(['is-invalid' => $err]) {{ $required ? 'required' : '' }} {{ $attributes }}>{{ $slot }}</select>
  @else
    <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" @if($type !== 'file' && $type !== 'password') value="{{ old($name, $value) }}" @endif @class(['is-invalid' => $err]) {{ $required ? 'required' : '' }} {{ $attributes }}>
  @endif
  @if($hint)<div class="hint">{{ $hint }}</div>@endif
  @if($err)<div class="error">{{ $err }}</div>@endif
</div>

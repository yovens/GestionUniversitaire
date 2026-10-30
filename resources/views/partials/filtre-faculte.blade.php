{{-- Sélecteur de faculté : affiché seulement pour ceux qui voient plusieurs facultés --}}
@if($me->faculteScope() === null && isset($facultes) && $facultes->count() > 1)
  <div class="field"><label for="faculte">Faculté</label>
    <select id="faculte" name="faculte" data-autosubmit>
      <option value="">Toutes</option>
      @foreach($facultes as $f)<option value="{{ $f->id }}" @selected(request('faculte') == $f->id)>{{ $f->nom }}</option>@endforeach
    </select></div>
@endif

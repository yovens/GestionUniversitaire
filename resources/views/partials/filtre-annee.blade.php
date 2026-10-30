<div class="field"><label for="annee">Année</label>
  <select id="annee" name="annee" data-autosubmit>
    @foreach($annees as $a)<option value="{{ $a->id }}" @selected($annee?->id === $a->id)>{{ $a->libelle }}@if($a->courante) (courante)@endif</option>@endforeach
  </select></div>

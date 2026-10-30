@if($situation['tranches']->isEmpty())
  <p class="muted">L'échéancier de votre faculté n'est pas encore défini pour cette année.</p>
@else
  @php($pct = $situation['total_du'] > 0 ? min(100, round($situation['total_paye'] / $situation['total_du'] * 100)) : 100)
  <p><strong>{{ number_format($situation['total_paye'], 2, ',', ' ') }} HTG</strong> payés sur {{ number_format($situation['total_du'], 2, ',', ' ') }} HTG ({{ $pct }} %)</p>
  <div class="meter" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $pct }}%"></span></div>
  <div class="schedule" style="margin-top:14px">
    @foreach($situation['tranches'] as $l)
      <div @class(['tranche', 'done' => $l['soldee'], 'late' => $l['en_retard']])>
        <span class="dot">@if($l['soldee'])✓@else{{ $l['tranche']->numero }}@endif</span>
        <div>
          <strong>{{ $l['tranche']->libelle }}</strong>
          <div class="small muted">
            {{ number_format($l['tranche']->montant, 2, ',', ' ') }} HTG
            @if($l['tranche']->date_limite) · avant le {{ $l['tranche']->date_limite->format('d/m/Y') }}@endif
          </div>
        </div>
        <div class="num small">
          @if($l['soldee'])<span class="badge ok">Réglée</span>
          @elseif($l['en_retard'])<span class="badge danger">En retard · reste {{ number_format($l['reste'], 0, ',', ' ') }}</span>
          @else<span class="badge">Reste {{ number_format($l['reste'], 0, ',', ' ') }}</span>@endif
        </div>
      </div>
    @endforeach
  </div>
@endif

<table class="head"><tr>
  <td style="width:64px">@if(file_exists(public_path('images/logo.png')))<img src="{{ public_path('images/logo.png') }}" alt="">@endif</td>
  <td>
    <div class="inst">{{ config('itac.etablissement.nom') }}</div>
    <div class="sub">{{ config('itac.etablissement.adresse') }}@if(config('itac.etablissement.telephone')) · {{ config('itac.etablissement.telephone') }}@endif @if(config('itac.etablissement.email')) · {{ config('itac.etablissement.email') }}@endif</div>
    @isset($faculte)<div class="sub">{{ $faculte->nom }}</div>@endisset
  </td>
</tr></table>

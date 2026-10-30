@php
  $lien = fn ($route, $label, $icon, $actif = null) => [
    'href' => route($route), 'label' => $label, 'icon' => $icon,
    'active' => request()->routeIs($actif ?? $route),
  ];
  $groupes = [];
  $groupes[''] = [$lien('dashboard', 'Tableau de bord', 'home')];

  if ($me->hasRole('admin', 'doyen', 'secretaire')) {
    $groupes['Scolarité'] = [
      $lien('etudiants.index', 'Étudiants', 'student', 'etudiants.*'),
      $lien('paiements.index', 'Paiements', 'wallet', 'paiements.*'),
      $lien('frais.index', 'Frais & échéances', 'receipt', 'frais.*'),
      $lien('examens.index', 'Examens', 'clock', 'examens.*'),
      $lien('notes.saisie', 'Notes', 'pen', 'notes.*'),
      $lien('resultats.index', 'Résultats & bulletins', 'chart', 'resultats.*'),
      $lien('calendrier', 'Calendrier', 'calendar'),
    ];
  }
  if ($me->hasRole('admin', 'doyen')) {
    $groupes['Structure académique'] = [
      $lien('classes.index', 'Classes', 'layers', 'classes.*'),
      $lien('matieres.index', 'Cours', 'book', 'matieres.*'),
      $lien('affectations.index', 'Affectations', 'link', 'affectations.*'),
    ];
  }
  if ($me->isAdmin()) {
    $groupes['Administration'] = [
      $lien('admin.facultes.index', 'Facultés', 'building', 'admin.facultes.*'),
      $lien('admin.annees.index', 'Années académiques', 'calendar', 'admin.annees.*'),
      $lien('admin.utilisateurs.index', 'Personnel', 'users', 'admin.utilisateurs.*'),
      $lien('admin.journal', 'Journal d’activité', 'shield'),
    ];
  }
  if ($me->isProfesseur()) {
    $groupes['Enseignement'] = [
      $lien('prof.cours', 'Mes cours', 'book', 'prof.cours*'),
      $lien('notes.saisie', 'Saisie des notes', 'pen', 'notes.*'),
      $lien('prof.sujets.index', 'Sujets d’examen', 'upload', 'prof.sujets.*'),
      $lien('calendrier', 'Calendrier', 'calendar'),
    ];
  }
  if ($me->isEtudiant()) {
    $groupes['Mon dossier'] = [
      $lien('etudiant.notes', 'Mes notes', 'chart'),
      $lien('etudiant.paiements', 'Mes paiements', 'wallet'),
      $lien('calendrier', 'Calendrier', 'calendar'),
    ];
  }
  $groupes['Compte'] = [$lien('profil.show', 'Mon profil', 'user', 'profil.*')];
@endphp
<aside class="sidebar" aria-label="Navigation principale">
  <a class="brand" href="{{ route('dashboard') }}">
    <span class="brand-mark"><img src="{{ asset('images/logo.png') }}" alt=""></span>
    <span><strong>{{ config('itac.etablissement.sigle') }}</strong><span>Gestion universitaire</span></span>
  </a>
  <nav class="nav">
    @foreach($groupes as $titre => $liens)
      <div class="nav-group">
        @if($titre)<p>{{ $titre }}</p>@endif
        @foreach($liens as $l)
          <a href="{{ $l['href'] }}" @class(['active' => $l['active']]) @if($l['active']) aria-current="page" @endif>
            <x-icon :name="$l['icon']"/>{{ $l['label'] }}
          </a>
        @endforeach
      </div>
    @endforeach
  </nav>
  <div class="sidebar-foot">{{ config('itac.etablissement.nom') }}</div>
</aside>

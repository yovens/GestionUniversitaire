// ITAC — petits comportements d'interface (aucune dépendance externe)
(function () {
  'use strict';

  // Menu mobile
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-menu-toggle]');
    if (t) { document.body.classList.toggle('nav-open'); e.preventDefault(); return; }
    if (document.body.classList.contains('nav-open') && !e.target.closest('.sidebar')) {
      document.body.classList.remove('nav-open');
    }
  });

  // Confirmation avant une action destructive : <form data-confirm="...">
  document.addEventListener('submit', function (e) {
    var f = e.target;
    var msg = f.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
    // Évite le double envoi (double clic sur "Enregistrer")
    if (f.dataset.sent === '1' && !f.hasAttribute('data-allow-resend')) { e.preventDefault(); return; }
    f.dataset.sent = '1';
    setTimeout(function () { f.dataset.sent = '0'; }, 4000);
  });

  // Navigation automatique des filtres <select data-autosubmit>
  document.addEventListener('change', function (e) {
    if (e.target.matches('[data-autosubmit]')) { e.target.form.submit(); }
  });

  // Grille de notes : Entrée = case suivante, coloration des notes sous le seuil
  var grid = document.querySelector('[data-grade-grid]');
  if (grid) {
    var seuil = parseFloat(grid.getAttribute('data-seuil') || '65');
    var max = parseFloat(grid.getAttribute('data-max') || '100');
    var inputs = Array.prototype.slice.call(grid.querySelectorAll('input[data-grade]'));
    var dirty = false;
    function check(input) {
      var v = parseFloat(String(input.value).replace(',', '.'));
      input.classList.toggle('low', !isNaN(v) && v < seuil);
      input.classList.toggle('is-invalid', input.value !== '' && (isNaN(v) || v < 0 || v > max));
    }
    inputs.forEach(function (input, i) {
      check(input);
      input.addEventListener('input', function () {
        check(input); dirty = true;
        input.closest('tr').classList.add('changed');
      });
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === 'ArrowDown') {
          e.preventDefault();
          if (inputs[i + 1]) { inputs[i + 1].focus(); inputs[i + 1].select(); }
        } else if (e.key === 'ArrowUp' && inputs[i - 1]) {
          e.preventDefault(); inputs[i - 1].focus(); inputs[i - 1].select();
        }
      });
    });
    grid.querySelectorAll('input[type=checkbox]').forEach(function (c) {
      c.addEventListener('change', function () {
        dirty = true; c.closest('tr').classList.add('changed');
        var n = c.closest('tr').querySelector('input[data-grade]');
        if (n) { n.disabled = c.checked; if (c.checked) n.value = ''; }
      });
      var n = c.closest('tr').querySelector('input[data-grade]');
      if (n && c.checked) n.disabled = true;
    });
    grid.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
  }

  // Formulaire de paiement : préremplir le montant restant de la tranche choisie
  var tranche = document.querySelector('select[data-tranche]');
  if (tranche) {
    tranche.addEventListener('change', function () {
      var opt = tranche.options[tranche.selectedIndex];
      var reste = opt && opt.getAttribute('data-reste');
      var montant = document.querySelector('input[name=montant]');
      if (reste && montant && !montant.dataset.touched) { montant.value = reste; }
    });
    var m = document.querySelector('input[name=montant]');
    if (m) m.addEventListener('input', function () { m.dataset.touched = '1'; });
  }

  // Aperçu de couleur de faculté
  document.querySelectorAll('input[type=color][data-preview]').forEach(function (c) {
    var target = document.querySelector(c.getAttribute('data-preview'));
    c.addEventListener('input', function () { if (target) target.style.setProperty('--fac', c.value); });
  });

  // Rôle "admin" : la faculté n'a pas de sens
  var role = document.querySelector('select[data-role-select]');
  if (role) {
    var fac = document.querySelector('[data-faculte-field]');
    var sync = function () { if (fac) fac.hidden = role.value === 'admin'; };
    role.addEventListener('change', sync); sync();
  }
})();

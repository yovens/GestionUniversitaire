<?php

use App\Http\Controllers\Admin\AnneeAcademiqueController;
use App\Http\Controllers\Admin\FaculteController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CalendrierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Etudiant\EspaceEtudiantController;
use App\Http\Controllers\FichierController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\Prof\CoursController;
use App\Http\Controllers\Prof\SujetController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Scolarite\BulletinController;
use App\Http\Controllers\Scolarite\EtudiantController;
use App\Http\Controllers\Scolarite\ExamenController;
use App\Http\Controllers\Scolarite\FraisController;
use App\Http\Controllers\Scolarite\PaiementController;
use App\Http\Controllers\Structure\AffectationController;
use App\Http\Controllers\Structure\ClasseController;
use App\Http\Controllers\Structure\MatiereController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/connexion', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});

Route::middleware(['auth', 'compte'])->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'logout'])->name('logout');
    Route::get('/mot-de-passe', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/mot-de-passe', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');

    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::post('/profil/photo', [ProfilController::class, 'photo'])->name('profil.photo');

    Route::get('/fichiers/photo/{user}', [FichierController::class, 'photo'])->name('fichiers.photo');
    Route::get('/fichiers/justificatif/{paiement}', [FichierController::class, 'justificatif'])->name('fichiers.justificatif');
    Route::get('/fichiers/sujet/{sujet}', [FichierController::class, 'sujet'])->name('fichiers.sujet');

    Route::get('/calendrier', [CalendrierController::class, 'index'])->name('calendrier');

    // Saisie des notes : professeur (ses cours) ou personnel de scolarité
    Route::middleware('role:admin,doyen,secretaire,professeur')->group(function () {
        Route::get('/notes/saisie', [NoteController::class, 'edit'])->name('notes.saisie');
        Route::post('/notes/saisie', [NoteController::class, 'update'])->name('notes.enregistrer');
    });

    // ---------- Administration générale ----------
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('facultes', FaculteController::class)->except('show');
        Route::resource('annees', AnneeAcademiqueController::class)->except('show')->parameters(['annees' => 'annee']);
        Route::post('annees/{annee}/courante', [AnneeAcademiqueController::class, 'courante'])->name('annees.courante');
        Route::resource('utilisateurs', UtilisateurController::class)->except('show')->parameters(['utilisateurs' => 'user']);
        Route::post('utilisateurs/{user}/reinitialiser', [UtilisateurController::class, 'reinitialiser'])->name('utilisateurs.reinitialiser');
        Route::post('utilisateurs/{user}/statut', [UtilisateurController::class, 'basculer'])->name('utilisateurs.basculer');
        Route::get('journal', [JournalController::class, 'index'])->name('journal');
    });

    // ---------- Structure académique (admin + doyen) ----------
    Route::middleware('role:admin,doyen')->group(function () {
        Route::resource('classes', ClasseController::class)->except('show');
        Route::resource('matieres', MatiereController::class)->except('show');
        Route::get('affectations', [AffectationController::class, 'index'])->name('affectations.index');
        Route::post('affectations', [AffectationController::class, 'store'])->name('affectations.store');
        Route::delete('affectations/{affectation}', [AffectationController::class, 'destroy'])->name('affectations.destroy');
    });

    // ---------- Scolarité (admin + doyen + secrétaire) ----------
    Route::middleware('role:admin,doyen,secretaire')->group(function () {
        Route::resource('etudiants', EtudiantController::class)->parameters(['etudiants' => 'etudiant']);
        Route::post('etudiants/{etudiant}/reinscrire', [EtudiantController::class, 'reinscrire'])->name('etudiants.reinscrire');
        Route::put('inscriptions/{inscription}/statut', [EtudiantController::class, 'statut'])->name('inscriptions.statut');
        Route::post('etudiants/{etudiant}/mot-de-passe', [EtudiantController::class, 'reinitialiser'])->name('etudiants.reinitialiser');

        Route::get('paiements', [PaiementController::class, 'index'])->name('paiements.index');
        Route::get('paiements/nouveau', [PaiementController::class, 'create'])->name('paiements.create');
        Route::post('paiements', [PaiementController::class, 'store'])->name('paiements.store');
        Route::post('paiements/{paiement}/annuler', [PaiementController::class, 'annuler'])->name('paiements.annuler');
        Route::get('paiements/export', [PaiementController::class, 'export'])->name('paiements.export');

        Route::get('frais', [FraisController::class, 'index'])->name('frais.index');
        Route::post('frais', [FraisController::class, 'store'])->name('frais.store');
        Route::delete('frais/{tranche}', [FraisController::class, 'destroy'])->name('frais.destroy');

        Route::resource('examens', ExamenController::class)->except('show');
        Route::post('examens/{examen}/publication', [ExamenController::class, 'publication'])->name('examens.publication');

        Route::get('resultats', [BulletinController::class, 'index'])->name('resultats.index');
        Route::get('resultats/classe', [BulletinController::class, 'deliberation'])->name('resultats.deliberation');
        Route::get('resultats/classe/pdf', [BulletinController::class, 'deliberationPdf'])->name('resultats.deliberation.pdf');
        Route::post('resultats/classe/decisions', [BulletinController::class, 'appliquerDecisions'])->name('resultats.decisions');
        Route::get('bulletins/{inscription}', [BulletinController::class, 'pdf'])->name('bulletins.pdf');

        Route::post('calendrier', [CalendrierController::class, 'store'])->name('calendrier.store');
        Route::put('calendrier/{evenement}', [CalendrierController::class, 'update'])->name('calendrier.update');
        Route::delete('calendrier/{evenement}', [CalendrierController::class, 'destroy'])->name('calendrier.destroy');
    });

    // Reçu : personnel ou l'étudiant concerné (contrôle dans le contrôleur)
    Route::get('paiements/{paiement}/recu', [PaiementController::class, 'recu'])->name('paiements.recu');

    // ---------- Espace professeur ----------
    Route::middleware('role:professeur')->prefix('prof')->name('prof.')->group(function () {
        Route::get('cours', [CoursController::class, 'index'])->name('cours');
        Route::get('cours/{affectation}', [CoursController::class, 'show'])->name('cours.show');
        Route::get('sujets', [SujetController::class, 'index'])->name('sujets.index');
        Route::post('sujets', [SujetController::class, 'store'])->name('sujets.store');
        Route::delete('sujets/{sujet}', [SujetController::class, 'destroy'])->name('sujets.destroy');
    });

    // ---------- Espace étudiant ----------
    Route::middleware('role:etudiant')->prefix('etudiant')->name('etudiant.')->group(function () {
        Route::get('notes', [EspaceEtudiantController::class, 'notes'])->name('notes');
        Route::get('bulletin.pdf', [EspaceEtudiantController::class, 'bulletin'])->name('bulletin');
        Route::get('paiements', [EspaceEtudiantController::class, 'paiements'])->name('paiements');
    });
});

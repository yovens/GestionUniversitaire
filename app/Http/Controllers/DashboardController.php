<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Evenement;
use App\Models\Examen;
use App\Models\Faculte;
use App\Models\Inscription;
use App\Models\JournalActivite;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\ResultatService;

class DashboardController extends Controller
{
    public function __invoke(FinanceService $finance, ResultatService $resultats)
    {
        $user = $this->user();
        $annee = $this->anneeCourante();

        return match ($user->role) {
            Role::Professeur => $this->professeur($annee),
            Role::Etudiant => $this->etudiant($annee, $finance, $resultats),
            default => $this->staff($annee),
        };
    }

    private function staff($annee)
    {
        $user = $this->user();
        $scope = $user->faculteScope();

        $facultes = Faculte::visiblePar($user)->orderBy('nom')->get()->map(function (Faculte $f) use ($annee) {
            $f->nb_etudiants = $annee ? Inscription::where('annee_academique_id', $annee->id)
                ->whereHas('classe', fn ($q) => $q->where('faculte_id', $f->id))->where('statut', '!=', 'abandon')->count() : 0;
            $f->nb_classes = Classe::where('faculte_id', $f->id)->count();
            $f->encaisse = $annee ? (float) Paiement::valides()->where('annee_academique_id', $annee->id)
                ->whereHas('etudiant.inscriptions', fn ($q) => $q->where('annee_academique_id', $annee->id)
                    ->whereHas('classe', fn ($c) => $c->where('faculte_id', $f->id)))->sum('montant') : 0;

            return $f;
        });

        $paiementsQuery = Paiement::valides()->with('etudiant')
            ->when($scope, fn ($q) => $q->whereHas('etudiant', fn ($e) => $e->where('faculte_id', $scope)));

        return view('dashboard.staff', [
            'annee' => $annee,
            'facultes' => $facultes,
            'nbProfesseurs' => User::role(Role::Professeur)->where('actif', true)->count(),
            'nbEtudiants' => $facultes->sum('nb_etudiants'),
            'encaisseMois' => (float) (clone $paiementsQuery)->whereBetween('date_paiement', [now()->startOfMonth(), now()->endOfMonth()])->sum('montant'),
            'encaisseAnnee' => $facultes->sum('encaisse'),
            'derniersPaiements' => (clone $paiementsQuery)->latest()->limit(6)->get(),
            'examensAVenir' => Examen::visiblePar($user)->with('faculte')->where('date_fin', '>=', today())->orderBy('date_debut')->limit(5)->get(),
            'examensNonPublies' => $annee ? Examen::visiblePar($user)->where('annee_academique_id', $annee->id)->where('notes_publiees', false)->where('date_fin', '<', today())->count() : 0,
            'evenements' => Evenement::pourFaculte($scope)->where('date_fin', '>=', today())->orderBy('date_debut')->limit(5)->get(),
            'journal' => $user->isAdmin() ? JournalActivite::with('user')->latest('created_at')->limit(8)->get() : collect(),
        ]);
    }

    private function professeur($annee)
    {
        $user = $this->user();
        $affectations = $annee
            ? Affectation::with('matiere.classe.faculte')->where('professeur_id', $user->id)->where('annee_academique_id', $annee->id)->get()
            : collect();

        $affectations->each(function (Affectation $a) use ($annee) {
            $a->nb_etudiants = Inscription::where('classe_id', $a->matiere->classe_id)->where('annee_academique_id', $annee->id)->where('statut', '!=', 'abandon')->count();
            $a->nb_notes = Note::where('matiere_id', $a->matiere_id)->whereHas('examen', fn ($q) => $q->where('annee_academique_id', $annee->id))->count();
        });

        $facultes = $affectations->pluck('matiere.faculte_id')->unique()->filter();

        return view('dashboard.professeur', [
            'annee' => $annee,
            'affectations' => $affectations,
            'examens' => $annee ? Examen::where('annee_academique_id', $annee->id)
                ->where(fn ($q) => $q->whereNull('faculte_id')->orWhereIn('faculte_id', $facultes))
                ->where('date_fin', '>=', today())->orderBy('date_debut')->limit(5)->get() : collect(),
        ]);
    }

    private function etudiant($annee, FinanceService $finance, ResultatService $resultats)
    {
        $user = $this->user();
        $inscription = $annee ? Inscription::with('classe.faculte')->where('etudiant_id', $user->id)->where('annee_academique_id', $annee->id)->first() : null;

        return view('dashboard.etudiant', [
            'annee' => $annee,
            'inscription' => $inscription,
            'situation' => $finance->situation($user, $annee),
            'bulletin' => $inscription ? $resultats->bulletin($inscription, null, 'etudiant') : null,
            'evenements' => Evenement::pourFaculte($inscription?->classe->faculte_id)->where('date_fin', '>=', today())->orderBy('date_debut')->limit(6)->get(),
        ]);
    }
}

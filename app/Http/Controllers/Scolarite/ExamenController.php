<?php

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use App\Models\Examen;
use App\Models\SujetExamen;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamenController extends Controller
{
    public function index()
    {
        $annee = $this->anneeChoisie();
        $examens = Examen::visiblePar($this->user())->with('faculte')->withCount('notes')
            ->when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id))
            ->when($this->faculteFiltre(), fn ($q, $f) => $q->pourFaculte($f))
            ->orderBy('session')->orderBy('date_debut')->get();

        $sujets = SujetExamen::with('professeur', 'matiere.classe', 'examen')
            ->whereHas('matiere', fn ($q) => $q->visiblePar($this->user()))
            ->latest()->limit(30)->get();

        return view('scolarite.examens.index', [
            'examens' => $examens,
            'annee' => $annee,
            'annees' => \App\Models\AnneeAcademique::orderByDesc('date_debut')->get(),
            'sujets' => $sujets,
            'facultes' => $this->facultesVisibles(),
        ]);
    }

    public function create()
    {
        $this->requireAnnee();

        return view('scolarite.examens.form', [
            'examen' => new Examen(['type' => 'intra', 'session' => 1, 'faculte_id' => $this->user()->faculteScope()]),
            'facultes' => $this->facultesVisibles(),
        ]);
    }

    public function store(Request $request)
    {
        $annee = $this->requireAnnee();
        $data = $this->valider($request);

        $examen = DB::transaction(function () use ($data, $annee) {
            $examen = Examen::create($data + ['annee_academique_id' => $annee->id]);
            $this->synchroniserEvenement($examen);

            return $examen;
        });
        Journal::log('examen.creation', "Planification de l'examen {$examen->nom}", $examen);

        return redirect()->route('examens.index')->with('success', "Examen « {$examen->nom} » planifié et ajouté au calendrier.");
    }

    public function edit(Examen $examen)
    {
        $this->verifierExamen($examen);

        return view('scolarite.examens.form', ['examen' => $examen, 'facultes' => $this->facultesVisibles()]);
    }

    public function update(Request $request, Examen $examen)
    {
        $this->verifierExamen($examen);
        $data = $this->valider($request);
        if ($examen->notes()->exists() && $data['type'] !== $examen->type) {
            return back()->withInput()->with('error', "Des notes existent déjà : le type d'examen ne peut plus changer.");
        }
        DB::transaction(function () use ($examen, $data) {
            $examen->update($data);
            $this->synchroniserEvenement($examen);
        });
        Journal::log('examen.modification', "Modification de l'examen {$examen->nom}", $examen);

        return redirect()->route('examens.index')->with('success', 'Examen mis à jour.');
    }

    public function destroy(Examen $examen)
    {
        $this->verifierExamen($examen);
        if ($examen->notes()->exists()) {
            return back()->with('error', 'Des notes sont déjà saisies pour cet examen : suppression impossible.');
        }
        $examen->delete(); // l'événement du calendrier est supprimé en cascade
        Journal::log('examen.suppression', "Suppression de l'examen {$examen->nom}");

        return back()->with('success', 'Examen supprimé.');
    }

    public function publication(Examen $examen)
    {
        $this->verifierExamen($examen);
        $publier = ! $examen->notes_publiees;
        $examen->forceFill(['notes_publiees' => $publier, 'publie_at' => $publier ? now() : null])->save();
        Journal::log('examen.publication', ($publier ? 'Publication' : 'Retrait de publication')." des notes de {$examen->nom}", $examen);

        return back()->with('success', $publier
            ? "Notes de « {$examen->nom} » publiées : les étudiants à jour de paiement peuvent les consulter. La saisie est verrouillée pour les professeurs."
            : "Publication retirée : les professeurs peuvent à nouveau corriger les notes.");
    }

    private function verifierExamen(Examen $examen): void
    {
        $scope = $this->user()->faculteScope();
        if ($scope !== null && $examen->faculte_id !== $scope) {
            abort(403, $examen->faculte_id === null
                ? 'Cet examen est commun à toutes les facultés : seule l’administration centrale peut le modifier.'
                : 'Cet examen appartient à une autre faculté.');
        }
    }

    private function valider(Request $request): array
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(Examen::TYPES))],
            'session' => ['required', 'in:1,2'],
            'faculte_id' => ['nullable', 'exists:facultes,id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'tranche_requise' => ['nullable', 'integer', 'between:1,12'],
        ]);
        $scope = $this->user()->faculteScope();
        if ($scope !== null) {
            $data['faculte_id'] = $scope;
        }

        return $data;
    }

    private function synchroniserEvenement(Examen $examen): void
    {
        Evenement::updateOrCreate(['examen_id' => $examen->id], [
            'faculte_id' => $examen->faculte_id,
            'titre' => $examen->nom,
            'description' => 'Examen '.$examen->type_label.' — session '.$examen->session,
            'type' => $examen->type === 'reprise' ? 'reprise' : 'examen',
            'date_debut' => $examen->date_debut,
            'date_fin' => $examen->date_fin,
            'created_by' => auth()->id(),
        ]);
    }
}

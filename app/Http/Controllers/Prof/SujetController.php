<?php

namespace App\Http\Controllers\Prof;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Examen;
use App\Models\SujetExamen;
use App\Services\Fichiers;
use App\Services\Journal;
use Illuminate\Http\Request;

/** Dépôt des sujets d'examen par le professeur (visibles par le secrétariat de sa faculté). */
class SujetController extends Controller
{
    public function index()
    {
        $annee = $this->anneeCourante();
        $affectations = $annee ? Affectation::with('matiere.classe')->where('professeur_id', $this->user()->id)
            ->where('annee_academique_id', $annee->id)->get() : collect();

        return view('prof.sujets', [
            'sujets' => SujetExamen::with('matiere.classe', 'examen')->where('professeur_id', $this->user()->id)->latest()->get(),
            'affectations' => $affectations,
            'examens' => $annee ? Examen::where('annee_academique_id', $annee->id)->orderBy('date_debut')->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'matiere_id' => ['required', 'integer'],
            'examen_id' => ['nullable', 'exists:examens,id'],
            'titre' => ['required', 'string', 'max:150'],
            'consigne' => ['nullable', 'string', 'max:5000'],
            'fichier' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:'.config('itac.uploads.document_max_kb')],
        ]);
        $affecte = Affectation::where('professeur_id', $this->user()->id)->where('matiere_id', $data['matiere_id'])->exists();
        abort_unless($affecte, 403, 'Ce cours ne vous est pas affecté.');

        $fichier = $request->file('fichier');
        $sujet = SujetExamen::create([
            'professeur_id' => $this->user()->id,
            'matiere_id' => $data['matiere_id'],
            'examen_id' => $data['examen_id'] ?? null,
            'titre' => $data['titre'],
            'consigne' => $data['consigne'] ?? null,
            'fichier' => $fichier ? Fichiers::stocker($fichier, 'sujets') : null,
            'fichier_nom' => $fichier ? mb_substr($fichier->getClientOriginalName(), 0, 200) : null,
        ]);
        Journal::log('sujet.depot', "Dépôt du sujet « {$sujet->titre} »", $sujet);

        return back()->with('success', 'Sujet déposé. Le secrétariat de la faculté peut le consulter.');
    }

    public function destroy(SujetExamen $sujet)
    {
        abort_unless($sujet->professeur_id === $this->user()->id, 403);
        Fichiers::supprimer($sujet->fichier);
        $sujet->delete();

        return back()->with('success', 'Sujet supprimé.');
    }
}

<?php

namespace App\Http\Controllers\Prof;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Examen;
use App\Models\Inscription;
use App\Models\Note;
use App\Services\ResultatService;

class CoursController extends Controller
{
    public function index()
    {
        $affectations = Affectation::with('matiere.classe.faculte', 'annee')->where('professeur_id', $this->user()->id)
            ->orderByDesc('annee_academique_id')->get()->groupBy(fn ($a) => $a->annee->libelle);

        return view('prof.cours', compact('affectations'));
    }

    public function show(Affectation $affectation, ResultatService $service)
    {
        abort_unless($affectation->professeur_id === $this->user()->id, 403);
        $affectation->load('matiere.classe.faculte', 'annee');
        $m = $affectation->matiere;

        $examens = Examen::where('annee_academique_id', $affectation->annee_academique_id)
            ->pourFaculte($m->faculte_id)->where('session', $m->session)->orderBy('date_debut')->get();
        $notes = Note::where('matiere_id', $m->id)->whereIn('examen_id', $examens->pluck('id'))->get()->groupBy('etudiant_id');

        $etudiants = Inscription::with('etudiant')->where('classe_id', $m->classe_id)
            ->where('annee_academique_id', $affectation->annee_academique_id)->where('statut', '!=', 'abandon')->get()
            ->sortBy(fn ($i) => mb_strtolower($i->etudiant->nom.' '.$i->etudiant->prenom))
            ->map(function ($i) use ($notes, $examens, $service) {
                $parExamen = $notes->get($i->etudiant_id, collect())->keyBy('examen_id');
                $vals = fn ($type) => $examens->where('type', $type)->map(fn ($e) => $parExamen->get($e->id))->filter()
                    ->map(fn ($n) => $n->absent ? 0.0 : (float) $n->valeur);
                $avg = fn ($c) => $c->isEmpty() ? null : $c->avg();

                return [
                    'etudiant' => $i->etudiant,
                    'notes' => $parExamen,
                    'moyenne' => $service->moyenneCours($avg($vals('intra')), $avg($vals('final')), $avg($vals('reprise'))),
                ];
            })->values();

        return view('prof.cours-show', [
            'affectation' => $affectation,
            'examens' => $examens,
            'etudiants' => $etudiants,
            'seuil' => (float) $m->faculte->seuil_reussite,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Structure;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Matiere;
use App\Models\User;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AffectationController extends Controller
{
    public function index(Request $request)
    {
        $annee = $this->requireAnnee();
        $faculte = $this->faculteFiltre();

        $matieres = Matiere::visiblePar($this->user())
            ->with(['classe.faculte', 'affectations' => fn ($q) => $q->where('annee_academique_id', $annee->id)->with('professeur')])
            ->when($faculte, fn ($q, $f) => $q->where('faculte_id', $f))
            ->when($request->integer('classe'), fn ($q, $c) => $q->where('classe_id', $c))
            ->when($request->boolean('sans_prof'), fn ($q) => $q->whereDoesntHave('affectations', fn ($a) => $a->where('annee_academique_id', $annee->id)))
            ->orderBy('classe_id')->orderBy('session')->orderBy('nom')->get();

        return view('structure.affectations.index', [
            'annee' => $annee,
            'matieres' => $matieres->groupBy(fn ($m) => $m->classe->nom.' · '.$m->classe->faculte->code),
            'professeurs' => User::role(Role::Professeur)->where('actif', true)->orderBy('nom')->get(),
            'facultes' => $this->facultesVisibles(),
        ]);
    }

    public function store(Request $request)
    {
        $annee = $this->requireAnnee();
        $data = $request->validate([
            'matiere_id' => ['required', 'exists:matieres,id'],
            'professeur_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Professeur->value)->whereNull('deleted_at')],
        ]);
        $matiere = Matiere::findOrFail($data['matiere_id']);
        $this->verifierFaculte($matiere->faculte_id);

        $aff = Affectation::updateOrCreate(
            ['matiere_id' => $matiere->id, 'annee_academique_id' => $annee->id],
            ['professeur_id' => $data['professeur_id']]
        );
        $aff->load('professeur');
        Journal::log('affectation', "{$aff->professeur->nom_complet} affecté(e) au cours {$matiere->code} ({$annee->libelle})", $aff);

        return back()->with('success', "Cours {$matiere->nom} confié à {$aff->professeur->nom_complet}.");
    }

    public function destroy(Affectation $affectation)
    {
        $affectation->load('matiere', 'professeur');
        $this->verifierFaculte($affectation->matiere->faculte_id);
        $affectation->delete();
        Journal::log('affectation.retrait', "Retrait de {$affectation->professeur->nom_complet} du cours {$affectation->matiere->code}");

        return back()->with('success', 'Affectation retirée.');
    }
}

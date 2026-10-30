<?php

namespace App\Http\Controllers\Structure;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Matiere;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatiereController extends Controller
{
    public function index(Request $request)
    {
        $annee = $this->anneeCourante();
        $matieres = Matiere::visiblePar($this->user())
            ->with(['classe.faculte', 'affectations' => fn ($q) => $q->where('annee_academique_id', $annee?->id)->with('professeur')])
            ->when($this->faculteFiltre(), fn ($q, $f) => $q->where('faculte_id', $f))
            ->when($request->integer('classe'), fn ($q, $c) => $q->where('classe_id', $c))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('nom', 'like', '%'.$request->string('q').'%')->orWhere('code', 'like', '%'.$request->string('q').'%')))
            ->orderBy('classe_id')->orderBy('session')->orderBy('nom')
            ->paginate(40)->withQueryString();

        return view('structure.matieres.index', [
            'matieres' => $matieres,
            'annee' => $annee,
            'facultes' => $this->facultesVisibles(),
            'classes' => Classe::visiblePar($this->user())->with('faculte')->orderBy('faculte_id')->orderBy('niveau')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('structure.matieres.form', [
            'matiere' => new Matiere(['coefficient' => 1, 'session' => 1, 'classe_id' => $request->integer('classe') ?: null]),
            'classes' => Classe::visiblePar($this->user())->with('faculte')->orderBy('faculte_id')->orderBy('niveau')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $m = Matiere::create($this->valider($request));
        Journal::log('matiere.creation', "Création du cours {$m->code} {$m->nom}", $m);

        return redirect()->route('matieres.index', ['classe' => $m->classe_id])->with('success', "Cours « {$m->nom} » ajouté.");
    }

    public function edit(Matiere $matiere)
    {
        $this->verifierFaculte($matiere->faculte_id);

        return view('structure.matieres.form', [
            'matiere' => $matiere,
            'classes' => Classe::visiblePar($this->user())->with('faculte')->orderBy('faculte_id')->orderBy('niveau')->get(),
        ]);
    }

    public function update(Request $request, Matiere $matiere)
    {
        $this->verifierFaculte($matiere->faculte_id);
        $data = $this->valider($request, $matiere);
        if ($data['classe_id'] != $matiere->classe_id && $matiere->notes()->exists()) {
            return back()->withInput()->with('error', 'Ce cours a déjà des notes : impossible de le déplacer dans une autre classe.');
        }
        $matiere->update($data);
        Journal::log('matiere.modification', "Modification du cours {$matiere->code}", $matiere);

        return redirect()->route('matieres.index', ['classe' => $matiere->classe_id])->with('success', 'Cours mis à jour.');
    }

    public function destroy(Matiere $matiere)
    {
        $this->verifierFaculte($matiere->faculte_id);
        if ($matiere->notes()->exists()) {
            return back()->with('error', 'Ce cours a déjà des notes : suppression impossible.');
        }
        $matiere->delete();
        Journal::log('matiere.suppression', "Suppression du cours {$matiere->code}");

        return back()->with('success', 'Cours supprimé.');
    }

    private function valider(Request $request, ?Matiere $m = null): array
    {
        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'code' => ['required', 'string', 'max:30', Rule::unique('matieres')->ignore($m?->id)],
            'nom' => ['required', 'string', 'max:150'],
            'coefficient' => ['required', 'integer', 'between:1,20'],
            'session' => ['required', 'in:1,2'],
        ]);
        $this->verifierFaculte(Classe::whereKey($data['classe_id'])->value('faculte_id'));
        $data['code'] = strtoupper(trim($data['code']));

        return $data;
    }
}

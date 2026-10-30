<?php

namespace App\Http\Controllers\Structure;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Inscription;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClasseController extends Controller
{
    public function index()
    {
        $annee = $this->anneeCourante();
        $classes = Classe::visiblePar($this->user())->with('faculte')->withCount('matieres')
            ->when($this->faculteFiltre(), fn ($q, $f) => $q->where('faculte_id', $f))
            ->orderBy('faculte_id')->orderBy('niveau')->get()
            ->each(function ($c) use ($annee) {
                $c->nb_inscrits = $annee ? Inscription::where('classe_id', $c->id)->where('annee_academique_id', $annee->id)->count() : 0;
            });

        return view('structure.classes.index', [
            'classes' => $classes->groupBy(fn ($c) => $c->faculte->nom),
            'facultes' => $this->facultesVisibles(),
        ]);
    }

    public function create()
    {
        return view('structure.classes.form', ['classe' => new Classe(['niveau' => 1, 'faculte_id' => $this->faculteFiltre()]), 'facultes' => $this->facultesVisibles()]);
    }

    public function store(Request $request)
    {
        $classe = Classe::create($this->valider($request));
        Journal::log('classe.creation', "Création de la classe {$classe->nom}", $classe);

        return redirect()->route('classes.index')->with('success', "Classe « {$classe->nom} » créée.");
    }

    public function edit(Classe $class)
    {
        $this->verifierFaculte($class->faculte_id);

        return view('structure.classes.form', ['classe' => $class, 'facultes' => $this->facultesVisibles()]);
    }

    public function update(Request $request, Classe $class)
    {
        $this->verifierFaculte($class->faculte_id);
        $data = $this->valider($request, $class);
        if ($data['faculte_id'] != $class->faculte_id && ($class->inscriptions()->exists() || $class->matieres()->exists())) {
            return back()->withInput()->with('error', 'Impossible de changer la faculté d’une classe qui a déjà des cours ou des inscrits.');
        }
        $class->update($data);

        return redirect()->route('classes.index')->with('success', 'Classe mise à jour.');
    }

    public function destroy(Classe $class)
    {
        $this->verifierFaculte($class->faculte_id);
        if ($class->inscriptions()->exists() || $class->matieres()->exists()) {
            return back()->with('error', 'Cette classe contient des cours ou des étudiants : suppression impossible.');
        }
        $class->delete();
        Journal::log('classe.suppression', "Suppression de la classe {$class->nom}");

        return back()->with('success', 'Classe supprimée.');
    }

    private function valider(Request $request, ?Classe $c = null): array
    {
        $data = $request->validate([
            'faculte_id' => ['required', 'exists:facultes,id'],
            'nom' => ['required', 'string', 'max:100',
                Rule::unique('classes')->where('faculte_id', $request->input('faculte_id'))->ignore($c?->id)],
            'niveau' => ['required', 'integer', 'between:1,7'],
        ], ['nom.unique' => 'Une classe porte déjà ce nom dans cette faculté.']);
        $this->verifierFaculte((int) $data['faculte_id']);

        return $data;
    }
}

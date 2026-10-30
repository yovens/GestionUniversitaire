<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculte;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaculteController extends Controller
{
    public function index()
    {
        return view('admin.facultes.index', [
            'facultes' => Faculte::withCount(['classes', 'matieres'])->orderBy('nom')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.facultes.form', ['faculte' => new Faculte(['couleur' => '#2F5DA8', 'seuil_reussite' => 65, 'actif' => true])]);
    }

    public function store(Request $request)
    {
        $f = Faculte::create($this->valider($request));
        Journal::log('faculte.creation', "Création de la faculté {$f->nom}", $f);

        return redirect()->route('admin.facultes.index')->with('success', "Faculté « {$f->nom} » créée.");
    }

    public function edit(Faculte $faculte)
    {
        return view('admin.facultes.form', compact('faculte'));
    }

    public function update(Request $request, Faculte $faculte)
    {
        $faculte->update($this->valider($request, $faculte));
        Journal::log('faculte.modification', "Modification de la faculté {$faculte->nom}", $faculte);

        return redirect()->route('admin.facultes.index')->with('success', 'Faculté mise à jour.');
    }

    public function destroy(Faculte $faculte)
    {
        if ($faculte->classes()->exists() || $faculte->users()->exists()) {
            return back()->with('error', 'Impossible de supprimer une faculté qui contient des classes ou des utilisateurs. Désactivez-la plutôt.');
        }
        $faculte->delete();
        Journal::log('faculte.suppression', "Suppression de la faculté {$faculte->nom}");

        return back()->with('success', 'Faculté supprimée.');
    }

    private function valider(Request $request, ?Faculte $f = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('facultes')->ignore($f?->id)],
            'nom' => ['required', 'string', 'max:150'],
            'couleur' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'seuil_reussite' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}

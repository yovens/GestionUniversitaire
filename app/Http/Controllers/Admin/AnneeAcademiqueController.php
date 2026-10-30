<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeAcademique;
use App\Models\Inscription;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnneeAcademiqueController extends Controller
{
    public function index()
    {
        $annees = AnneeAcademique::orderByDesc('date_debut')->get()->each(function ($a) {
            $a->nb_inscrits = Inscription::where('annee_academique_id', $a->id)->count();
        });

        return view('admin.annees.index', compact('annees'));
    }

    public function create()
    {
        $y = (int) now()->format('Y');
        $debut = now()->month >= 8 ? $y : $y - 1;

        return view('admin.annees.form', ['annee' => new AnneeAcademique([
            'libelle' => $debut.'-'.($debut + 1),
            'date_debut' => "$debut-10-01",
            'date_fin' => ($debut + 1).'-07-31',
        ])]);
    }

    public function store(Request $request)
    {
        $annee = AnneeAcademique::create($this->valider($request));
        if ($request->boolean('courante') || ! AnneeAcademique::where('courante', true)->exists()) {
            $annee->definirCommeCourante();
        }
        Journal::log('annee.creation', "Création de l'année {$annee->libelle}", $annee);

        return redirect()->route('admin.annees.index')->with('success', "Année {$annee->libelle} créée.");
    }

    public function edit(AnneeAcademique $annee)
    {
        return view('admin.annees.form', compact('annee'));
    }

    public function update(Request $request, AnneeAcademique $annee)
    {
        $annee->update($this->valider($request, $annee));

        return redirect()->route('admin.annees.index')->with('success', 'Année mise à jour.');
    }

    public function courante(AnneeAcademique $annee)
    {
        $annee->definirCommeCourante();
        Journal::log('annee.courante', "Année courante : {$annee->libelle}", $annee);

        return back()->with('success', "{$annee->libelle} est maintenant l'année courante.");
    }

    public function destroy(AnneeAcademique $annee)
    {
        if ($annee->courante || Inscription::where('annee_academique_id', $annee->id)->exists()) {
            return back()->with('error', "Impossible de supprimer l'année courante ou une année qui a des inscriptions.");
        }
        try {
            $annee->delete();
        } catch (\Illuminate\Database\QueryException) {
            return back()->with('error', 'Cette année est encore utilisée (examens, paiements…).');
        }

        return back()->with('success', 'Année supprimée.');
    }

    private function valider(Request $request, ?AnneeAcademique $a = null): array
    {
        return $request->validate([
            'libelle' => ['required', 'regex:/^\d{4}-\d{4}$/', Rule::unique('annees_academiques')->ignore($a?->id)],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ], ['libelle.regex' => 'Format attendu : 2025-2026.']);
    }
}

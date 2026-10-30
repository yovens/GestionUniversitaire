<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Services\Journal;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendrierController extends Controller
{
    public function index(Request $request)
    {
        $me = $this->user();
        try {
            $mois = $request->filled('mois') ? CarbonImmutable::createFromFormat('Y-m', $request->string('mois'))->startOfMonth() : CarbonImmutable::today()->startOfMonth();
        } catch (\Throwable) {
            $mois = CarbonImmutable::today()->startOfMonth();
        }

        $faculte = match (true) {
            $me->isEtudiant() => Inscription::with('classe')->where('etudiant_id', $me->id)->latest('annee_academique_id')->first()?->classe?->faculte_id,
            $me->isProfesseur() => null,
            default => $me->faculteScope(),
        };

        $debutGrille = $mois->startOfWeek(CarbonImmutable::MONDAY);
        $finGrille = $mois->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        $requete = fn () => Evenement::with('faculte')
            ->when($faculte, fn ($q) => $q->pourFaculte($faculte));

        $evenements = $requete()->where('date_debut', '<=', $finGrille)->where('date_fin', '>=', $debutGrille)
            ->orderBy('date_debut')->get();

        $jours = [];
        for ($d = $debutGrille; $d <= $finGrille; $d = $d->addDay()) {
            $jours[] = [
                'date' => $d,
                'hors_mois' => $d->month !== $mois->month,
                'evenements' => $evenements->filter(fn ($e) => $e->date_debut->lte($d) && $e->date_fin->gte($d))->values(),
            ];
        }

        return view('calendrier.index', [
            'mois' => $mois,
            'jours' => $jours,
            'aVenir' => $requete()->where('date_fin', '>=', today())->orderBy('date_debut')->limit(12)->get(),
            'peutGerer' => $me->isStaff(),
            'facultes' => $me->isStaff() ? $this->facultesVisibles() : collect(),
            'edition' => $me->isStaff() && $request->integer('modifier') ? Evenement::find($request->integer('modifier')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $e = Evenement::create($this->valider($request) + ['created_by' => auth()->id()]);
        Journal::log('calendrier.ajout', "Ajout de l'événement « {$e->titre} »", $e);

        return redirect()->route('calendrier', ['mois' => $e->date_debut->format('Y-m')])->with('success', 'Événement ajouté au calendrier.');
    }

    public function update(Request $request, Evenement $evenement)
    {
        $this->verifierEvenement($evenement);
        $evenement->update($this->valider($request));

        return redirect()->route('calendrier', ['mois' => $evenement->date_debut->format('Y-m')])->with('success', 'Événement mis à jour.');
    }

    public function destroy(Evenement $evenement)
    {
        $this->verifierEvenement($evenement);
        $evenement->delete();
        Journal::log('calendrier.suppression', "Suppression de l'événement « {$evenement->titre} »");

        return back()->with('success', 'Événement supprimé.');
    }

    private function verifierEvenement(Evenement $e): void
    {
        if ($e->examen_id) {
            abort(back()->with('error', 'Cet événement est lié à un examen : modifiez l’examen lui-même.'));
        }
        $scope = $this->user()->faculteScope();
        abort_if($scope !== null && $e->faculte_id !== $scope, 403, 'Événement d’une autre faculté ou commun à toutes.');
    }

    private function valider(Request $request): array
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(array_keys(Evenement::TYPES))],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'faculte_id' => ['nullable', 'exists:facultes,id'],
        ]);
        $scope = $this->user()->faculteScope();
        if ($scope !== null) {
            $data['faculte_id'] = $scope;
        }

        return $data;
    }
}

<?php

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\Faculte;
use App\Models\TrancheFrais;
use App\Services\Journal;
use Illuminate\Http\Request;

/** Échéancier des frais par faculté pour l'année courante. */
class FraisController extends Controller
{
    public function index()
    {
        $annee = $this->requireAnnee();
        $facultes = Faculte::visiblePar($this->user())->orderBy('nom')
            ->with(['tranches' => fn ($q) => $q->where('annee_academique_id', $annee->id)])->get();

        return view('scolarite.frais', compact('annee', 'facultes'));
    }

    public function store(Request $request)
    {
        abort_unless($this->user()->hasRole('admin', 'doyen'), 403, 'Seuls l’administration et le doyen fixent les frais.');
        $annee = $this->requireAnnee();
        $data = $request->validate([
            'faculte_id' => ['required', 'exists:facultes,id'],
            'numero' => ['required', 'integer', 'between:1,12'],
            'libelle' => ['required', 'string', 'max:100'],
            'montant' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'date_limite' => ['nullable', 'date'],
        ]);
        $this->verifierFaculte((int) $data['faculte_id']);

        $t = TrancheFrais::updateOrCreate(
            ['faculte_id' => $data['faculte_id'], 'annee_academique_id' => $annee->id, 'numero' => $data['numero']],
            $data
        );
        Journal::log('frais', "Tranche {$t->numero} ({$t->libelle}) : {$t->montant} HTG", $t);

        return back()->with('success', 'Échéancier mis à jour.');
    }

    public function destroy(TrancheFrais $tranche)
    {
        abort_unless($this->user()->hasRole('admin', 'doyen'), 403);
        $this->verifierFaculte($tranche->faculte_id);
        $tranche->delete();
        Journal::log('frais.suppression', "Suppression de la tranche {$tranche->numero}");

        return back()->with('success', 'Tranche supprimée.');
    }
}

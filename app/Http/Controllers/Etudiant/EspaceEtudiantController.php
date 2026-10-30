<?php

namespace App\Http\Controllers\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Paiement;
use App\Services\FinanceService;
use App\Services\ResultatService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class EspaceEtudiantController extends Controller
{
    public function notes(Request $request, ResultatService $resultats, FinanceService $finance)
    {
        $me = $this->user();
        $inscriptions = Inscription::with('classe.faculte', 'annee')->where('etudiant_id', $me->id)->orderByDesc('annee_academique_id')->get();
        $inscription = $inscriptions->firstWhere('id', $request->integer('inscription')) ?? $inscriptions->first();

        $detail = collect();
        if ($inscription) {
            $examens = Examen::where('annee_academique_id', $inscription->annee_academique_id)
                ->pourFaculte($inscription->classe->faculte_id)->where('notes_publiees', true)->orderBy('date_debut')->get();
            $detail = $examens->map(fn (Examen $e) => [
                'examen' => $e,
                'accessible' => $finance->peutVoirNotes($me, $e),
                'notes' => Note::with('matiere')->where('etudiant_id', $me->id)->where('examen_id', $e->id)->get()->sortBy('matiere.nom'),
            ]);
        }

        return view('etudiant.notes', [
            'inscriptions' => $inscriptions,
            'inscription' => $inscription,
            'bulletin' => $inscription ? $resultats->bulletin($inscription, null, 'etudiant') : null,
            'detail' => $detail,
            'situation' => $inscription ? $finance->situation($me, $inscription->annee) : null,
        ]);
    }

    public function bulletin(Request $request, ResultatService $resultats)
    {
        $me = $this->user();
        $inscription = Inscription::with('classe.faculte', 'annee', 'etudiant')->where('etudiant_id', $me->id)
            ->when($request->integer('inscription'), fn ($q, $id) => $q->whereKey($id))
            ->orderByDesc('annee_academique_id')->firstOrFail();

        return Pdf::loadView('pdf.bulletin', ['b' => $resultats->bulletin($inscription, null, 'etudiant'), 'officiel' => false])
            ->setPaper('a4')->download('bulletin-'.$me->matricule.'-'.$inscription->annee->libelle.'.pdf');
    }

    public function paiements(FinanceService $finance)
    {
        $me = $this->user();
        $annee = $this->anneeCourante();

        return view('etudiant.paiements', [
            'annee' => $annee,
            'situation' => $finance->situation($me, $annee),
            'paiements' => Paiement::with('annee')->where('etudiant_id', $me->id)->latest('date_paiement')->get(),
        ]);
    }
}

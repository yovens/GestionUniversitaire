<?php

namespace App\Http\Controllers\Scolarite;

use App\Http\Controllers\Controller;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Services\Journal;
use App\Services\ResultatService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulletinController extends Controller
{
    public function index()
    {
        $annee = $this->anneeChoisie();
        $classes = Classe::visiblePar($this->user())->with('faculte')
            ->when($this->faculteFiltre(), fn ($q, $f) => $q->where('faculte_id', $f))
            ->orderBy('faculte_id')->orderBy('niveau')->get()
            ->each(fn ($c) => $c->nb_inscrits = $annee ? Inscription::where('classe_id', $c->id)->where('annee_academique_id', $annee->id)->count() : 0);

        return view('scolarite.resultats.index', [
            'classes' => $classes->groupBy(fn ($c) => $c->faculte->nom),
            'annee' => $annee,
            'annees' => AnneeAcademique::orderByDesc('date_debut')->get(),
            'facultes' => $this->facultesVisibles(),
        ]);
    }

    public function deliberation(Request $request, ResultatService $service)
    {
        [$classe, $annee, $session] = $this->contexte($request);

        return view('scolarite.resultats.deliberation', [
            'classe' => $classe,
            'annee' => $annee,
            'session' => $session,
            'matieres' => Matiere::where('classe_id', $classe->id)->when($session, fn ($q) => $q->where('session', $session))->orderBy('session')->orderBy('nom')->get(),
            'resultats' => $service->deliberation($classe->id, $annee->id, $session),
        ]);
    }

    public function deliberationPdf(Request $request, ResultatService $service)
    {
        [$classe, $annee, $session] = $this->contexte($request);
        $pdf = Pdf::loadView('pdf.deliberation', [
            'classe' => $classe,
            'annee' => $annee,
            'session' => $session,
            'matieres' => Matiere::where('classe_id', $classe->id)->when($session, fn ($q) => $q->where('session', $session))->orderBy('session')->orderBy('nom')->get(),
            'resultats' => $service->deliberation($classe->id, $annee->id, $session),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('resultats-'.str($classe->nom)->slug().'-'.$annee->libelle.'.pdf');
    }

    /** Met à jour le statut des inscriptions (admis / reprise) selon les résultats annuels complets. */
    public function appliquerDecisions(Request $request, ResultatService $service)
    {
        abort_unless($this->user()->hasRole('admin', 'doyen'), 403, 'Seuls l’administration et le doyen valident les décisions.');
        [$classe, $annee] = $this->contexte($request);
        $resultats = $service->deliberation($classe->id, $annee->id, null);

        $n = 0;
        DB::transaction(function () use ($resultats, &$n) {
            foreach ($resultats as $r) {
                if (in_array($r['decision_code'], ['admis', 'reprise'], true)) {
                    $r['inscription']->update(['statut' => $r['decision_code']]);
                    $n++;
                }
            }
        });
        Journal::log('deliberation', "Décisions appliquées pour {$classe->nom} ({$annee->libelle}) : {$n} étudiant(s)", $classe);

        return back()->with('success', "{$n} décision(s) enregistrée(s). Les étudiants aux résultats incomplets n'ont pas été modifiés.");
    }

    public function pdf(Request $request, Inscription $inscription, ResultatService $service)
    {
        $inscription->load('classe.faculte', 'annee', 'etudiant');
        $this->verifierFaculte($inscription->classe->faculte_id);
        $session = in_array($request->integer('session'), [1, 2], true) ? $request->integer('session') : null;
        $bulletin = $service->bulletin($inscription, $session);
        Journal::log('bulletin', "Bulletin de {$inscription->etudiant->nom_complet} édité", $inscription);

        return Pdf::loadView('pdf.bulletin', ['b' => $bulletin, 'officiel' => true])->setPaper('a4')
            ->stream('bulletin-'.$inscription->etudiant->matricule.'.pdf');
    }

    private function contexte(Request $request): array
    {
        $request->validate(['classe' => ['required', 'integer'], 'session' => ['nullable', 'in:1,2'], 'annee' => ['nullable', 'integer']]);
        $classe = Classe::with('faculte')->findOrFail($request->integer('classe'));
        $this->verifierFaculte($classe->faculte_id);
        $annee = $this->anneeChoisie() ?? abort(404, 'Aucune année académique.');
        $session = $request->integer('session') ?: null;

        return [$classe, $annee, $session];
    }
}

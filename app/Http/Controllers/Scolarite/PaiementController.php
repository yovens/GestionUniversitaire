<?php

namespace App\Http\Controllers\Scolarite;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\User;
use App\Services\Fichiers;
use App\Services\FinanceService;
use App\Services\Journal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $annee = $this->anneeChoisie();
        $paiements = $this->filtrer($request, $annee?->id)->with('etudiant.faculte', 'caissier')
            ->latest('date_paiement')->latest('id')->paginate(30)->withQueryString();

        $totaux = $this->filtrer($request, $annee?->id)->valides()
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(montant),0) as total')->first();

        return view('scolarite.paiements.index', [
            'paiements' => $paiements,
            'totaux' => $totaux,
            'annee' => $annee,
            'annees' => \App\Models\AnneeAcademique::orderByDesc('date_debut')->get(),
            'facultes' => $this->facultesVisibles(),
        ]);
    }

    public function create(Request $request, FinanceService $finance)
    {
        $annee = $this->requireAnnee();
        $etudiant = null;
        $situation = null;
        if ($request->integer('etudiant')) {
            $etudiant = User::role(Role::Etudiant)->visiblePar($this->user())->findOrFail($request->integer('etudiant'));
            $situation = $finance->situation($etudiant, $annee);
        }

        $resultats = collect();
        if ($request->filled('q') && ! $etudiant) {
            $resultats = User::role(Role::Etudiant)->visiblePar($this->user())->with('faculte')
                ->recherche($request->string('q')->toString())->orderBy('nom')->limit(15)->get();
        }

        return view('scolarite.paiements.create', compact('annee', 'etudiant', 'situation', 'resultats'));
    }

    public function store(Request $request, FinanceService $finance)
    {
        $annee = $this->requireAnnee();
        $data = $request->validate([
            'etudiant_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Etudiant->value)],
            'montant' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'tranche_numero' => ['nullable', 'integer', 'between:1,12'],
            'date_paiement' => ['required', 'date', 'before_or_equal:today'],
            'methode' => ['required', Rule::in(array_keys(Paiement::METHODES))],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'justificatif' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.config('itac.uploads.document_max_kb')],
        ]);
        $etudiant = User::findOrFail($data['etudiant_id']);
        $this->verifierFaculte($etudiant->faculte_id);

        $chemin = $request->hasFile('justificatif') ? Fichiers::stocker($request->file('justificatif'), 'justificatifs') : null;

        $paiement = null;
        for ($essai = 1; $essai <= 3 && ! $paiement; $essai++) {
            try {
                $paiement = DB::transaction(fn () => Paiement::create(array_merge(collect($data)->except('justificatif')->all(), [
                    'numero_recu' => FinanceService::prochainNumeroRecu(),
                    'annee_academique_id' => $annee->id,
                    'justificatif' => $chemin,
                    'enregistre_par' => auth()->id(),
                ])));
            } catch (QueryException $e) {
                // collision rare sur le numéro de reçu (deux caisses en même temps) : on réessaie
                if ($essai === 3) {
                    Fichiers::supprimer($chemin);
                    report($e);

                    return back()->withInput()->with('error', "Le paiement n'a pas pu être enregistré. Réessayez.");
                }
                usleep(150000);
            }
        }

        Journal::log('paiement.creation', "Paiement {$paiement->numero_recu} : ".number_format($paiement->montant, 2, ',', ' ')." HTG pour {$etudiant->nom_complet}", $paiement);

        return redirect()->route('etudiants.show', $etudiant)
            ->with('success', "Paiement enregistré — reçu {$paiement->numero_recu}.")
            ->with('recu', $paiement->id);
    }

    public function annuler(Request $request, Paiement $paiement)
    {
        $paiement->load('etudiant');
        $this->verifierFaculte($paiement->etudiant?->faculte_id);
        abort_unless($this->user()->hasRole('admin', 'doyen', 'secretaire'), 403);
        $data = $request->validate(['motif_annulation' => ['required', 'string', 'min:5', 'max:255']]);

        if ($paiement->estAnnule()) {
            return back()->with('error', 'Ce paiement est déjà annulé.');
        }
        $paiement->forceFill([
            'annule_at' => now(), 'annule_par' => auth()->id(), 'motif_annulation' => $data['motif_annulation'],
        ])->save();
        Journal::log('paiement.annulation', "Annulation du reçu {$paiement->numero_recu} : {$data['motif_annulation']}", $paiement);

        return back()->with('success', "Le reçu {$paiement->numero_recu} est annulé.");
    }

    public function recu(Paiement $paiement, FinanceService $finance)
    {
        $me = $this->user();
        $paiement->load('etudiant.faculte', 'annee', 'caissier');
        $autorise = $me->id === $paiement->etudiant_id || ($me->isStaff() && $me->peutAccederFaculte($paiement->etudiant?->faculte_id));
        abort_unless($autorise, 403);

        $pdf = Pdf::loadView('pdf.recu', [
            'paiement' => $paiement,
            'situation' => $finance->situation($paiement->etudiant, $paiement->annee),
        ])->setPaper('a5', 'portrait');

        return $pdf->stream("recu-{$paiement->numero_recu}.pdf");
    }

    public function export(Request $request)
    {
        $annee = $this->anneeChoisie();
        $lignes = $this->filtrer($request, $annee?->id)->with('etudiant.faculte', 'caissier')->orderBy('date_paiement')->get();
        Journal::log('paiement.export', 'Export CSV des paiements ('.$lignes->count().' lignes)');

        return response()->streamDownload(function () use ($lignes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM pour Excel
            fputcsv($out, ['Reçu', 'Date', 'Matricule', 'Étudiant', 'Faculté', 'Tranche', 'Montant', 'Méthode', 'Référence', 'Statut', 'Enregistré par'], ';');
            foreach ($lignes as $p) {
                fputcsv($out, [
                    $p->numero_recu, $p->date_paiement->format('Y-m-d'), $p->etudiant?->matricule, $p->etudiant?->nom_complet,
                    $p->etudiant?->faculte?->code, $p->tranche_numero, number_format((float) $p->montant, 2, '.', ''),
                    $p->methode_label, $p->reference, $p->estAnnule() ? 'Annulé' : 'Valide', $p->caissier?->nom_complet,
                ], ';');
            }
            fclose($out);
        }, 'paiements-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtrer(Request $request, ?int $anneeId)
    {
        $scope = $this->faculteFiltre();

        return Paiement::query()
            ->when($anneeId, fn ($q) => $q->where('annee_academique_id', $anneeId))
            ->when($scope, fn ($q, $f) => $q->whereHas('etudiant', fn ($e) => $e->withTrashed()->where('faculte_id', $f)))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date_paiement', '>=', $request->date('du')))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date_paiement', '<=', $request->date('au')))
            ->when($request->filled('methode'), fn ($q) => $q->where('methode', $request->string('methode')))
            ->when($request->string('etat')->toString() === 'annule', fn ($q) => $q->whereNotNull('annule_at'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('numero_recu', 'like', '%'.$request->string('q').'%')
                ->orWhereHas('etudiant', fn ($e) => $e->withTrashed()->recherche($request->string('q')->toString()))));
    }
}

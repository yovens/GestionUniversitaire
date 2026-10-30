<?php

namespace App\Services;

use App\Models\AnneeAcademique;
use App\Models\Examen;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\TrancheFrais;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Situation financière d'un étudiant pour une année académique,
 * et règle d'accès aux notes selon les versements effectués.
 */
class FinanceService
{
    /** @var array<string, array> */
    private array $cache = [];

    public function situation(User $etudiant, ?AnneeAcademique $annee): array
    {
        if (! $annee) {
            return $this->vide();
        }
        $key = $etudiant->id.'-'.$annee->id;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $inscription = Inscription::with('classe')->where('etudiant_id', $etudiant->id)
            ->where('annee_academique_id', $annee->id)->first();

        $tranches = $inscription
            ? TrancheFrais::where('faculte_id', $inscription->classe->faculte_id)
                ->where('annee_academique_id', $annee->id)->orderBy('numero')->get()
            : collect();

        $paye = (float) Paiement::valides()->where('etudiant_id', $etudiant->id)
            ->where('annee_academique_id', $annee->id)->sum('montant');

        $total = (float) $tranches->sum('montant');
        $cumul = 0.0;
        $lignes = $tranches->map(function (TrancheFrais $t) use (&$cumul, $paye) {
            $cumul += (float) $t->montant;
            $couvert = max(0, min((float) $t->montant, $paye - ($cumul - (float) $t->montant)));

            return [
                'tranche' => $t,
                'cumul' => $cumul,
                'couvert' => $couvert,
                'reste' => (float) $t->montant - $couvert,
                'soldee' => $paye >= $cumul - 0.001,
                'en_retard' => $paye < $cumul - 0.001 && $t->date_limite && $t->date_limite->isPast(),
            ];
        });

        $prochaine = $lignes->first(fn ($l) => ! $l['soldee']);

        return $this->cache[$key] = [
            'tranches' => $lignes,
            'total_du' => $total,
            'total_paye' => $paye,
            'solde' => max(0, $total - $paye),
            'prochaine' => $prochaine,
            'a_jour' => $prochaine === null,
            'retard' => $lignes->contains(fn ($l) => $l['en_retard']),
        ];
    }

    /** Montant cumulé exigé jusqu'à la tranche n incluse. */
    public function cumulExige(Collection $tranches, int $numero): float
    {
        return (float) $tranches->filter(fn ($l) => $l['tranche']->numero <= $numero)->sum(fn ($l) => (float) $l['tranche']->montant);
    }

    /** L'étudiant peut-il voir les notes de cet examen ? */
    public function peutVoirNotes(User $etudiant, Examen $examen): bool
    {
        if (! $examen->notes_publiees) {
            return false;
        }
        if (! $examen->tranche_requise) {
            return true;
        }
        $annee = $examen->annee ?? AnneeAcademique::find($examen->annee_academique_id);
        $s = $this->situation($etudiant, $annee);
        if ($s['tranches']->isEmpty()) {
            return true; // aucun échéancier défini pour la faculté : pas de blocage
        }

        return $s['total_paye'] + 0.001 >= $this->cumulExige($s['tranches'], $examen->tranche_requise);
    }

    public function oublier(): void
    {
        $this->cache = [];
    }

    /** Numéro de reçu unique et séquentiel par année : REC-2026-000123 */
    public static function prochainNumeroRecu(): string
    {
        $annee = now()->format('Y');
        $prefix = "REC-{$annee}-";
        $dernier = DB::table('paiements')->where('numero_recu', 'like', $prefix.'%')
            ->lockForUpdate()->orderByDesc('numero_recu')->value('numero_recu');
        $n = $dernier ? ((int) substr($dernier, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    private function vide(): array
    {
        return ['tranches' => collect(), 'total_du' => 0, 'total_paye' => 0, 'solde' => 0, 'prochaine' => null, 'a_jour' => true, 'retard' => false];
    }
}

<?php

namespace App\Services;

use App\Models\Examen;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Calcul des résultats : moyenne par cours, moyenne générale pondérée, décision.
 *
 * Règles (configurables dans config/itac.php) :
 *  - moyenne du cours = intra × poids_intra + final × poids_final (si un seul existe, il compte seul)
 *  - une note de reprise remplace la moyenne du cours si elle est meilleure
 *  - moyenne générale = Σ(moyenne cours × coefficient) / Σ coefficients
 *  - réussite si moyenne générale ≥ seuil de la faculté ET aucun cours sous le seuil
 *  - les résultats sont « partiels » tant qu'un cours n'a pas sa note finale (ou de reprise)
 */
class ResultatService
{
    public function __construct(private FinanceService $finance) {}

    /**
     * @param  string  $vue  'staff' (tout), 'etudiant' (publié + payé)
     */
    public function bulletin(Inscription $inscription, ?int $session = null, string $vue = 'staff'): array
    {
        $inscription->loadMissing('classe.faculte', 'annee', 'etudiant');
        $classe = $inscription->classe;
        $etudiant = $inscription->etudiant;
        $seuil = (float) ($classe->faculte->seuil_reussite ?? 65);

        $matieres = Matiere::where('classe_id', $classe->id)
            ->when($session, fn ($q) => $q->where('session', $session))
            ->orderBy('session')->orderBy('nom')->get();

        $examens = Examen::where('annee_academique_id', $inscription->annee_academique_id)
            ->pourFaculte($classe->faculte_id)
            ->when($session, fn ($q) => $q->where('session', $session))
            ->get()->keyBy('id');

        $bloques = collect();
        if ($vue === 'etudiant') {
            $examens = $examens->filter(function (Examen $e) use ($etudiant, $bloques) {
                if (! $e->notes_publiees) {
                    return false;
                }
                if (! $this->finance->peutVoirNotes($etudiant, $e)) {
                    $bloques->push($e);

                    return false;
                }

                return true;
            });
        }

        $notes = Note::where('etudiant_id', $etudiant->id)
            ->whereIn('examen_id', $examens->keys())
            ->whereIn('matiere_id', $matieres->pluck('id'))
            ->get()->groupBy('matiere_id');

        $lignes = $matieres->map(function (Matiere $m) use ($notes, $examens, $seuil) {
            $parType = ['intra' => [], 'final' => [], 'reprise' => []];
            foreach ($notes->get($m->id, collect()) as $n) {
                $type = $examens[$n->examen_id]->type ?? null;
                if ($type && isset($parType[$type])) {
                    $parType[$type][] = $n->absent ? 0.0 : (float) $n->valeur;
                }
            }
            $intra = $this->moy($parType['intra']);
            $final = $this->moy($parType['final']);
            $reprise = $this->moy($parType['reprise']);
            $moyenne = $this->moyenneCours($intra, $final, $reprise);

            return [
                'matiere' => $m,
                'intra' => $intra,
                'final' => $final,
                'reprise' => $reprise,
                'moyenne' => $moyenne,
                'valide' => $moyenne !== null && $moyenne + 0.0001 >= $seuil,
                // un cours n'est définitif qu'après le final (ou la reprise) ; l'intra seul reste provisoire
                'definitif' => $final !== null || $reprise !== null,
            ];
        });

        $evaluees = $lignes->filter(fn ($l) => $l['moyenne'] !== null);
        $coef = $evaluees->sum(fn ($l) => $l['matiere']->coefficient ?: 1);
        $generale = $coef > 0
            ? round($evaluees->sum(fn ($l) => $l['moyenne'] * ($l['matiere']->coefficient ?: 1)) / $coef, 2)
            : null;

        $echecs = $evaluees->reject(fn ($l) => $l['valide']);
        $complet = $lignes->isNotEmpty() && $lignes->every(fn ($l) => $l['definitif']);

        if ($generale === null) {
            $decision = 'Aucune note disponible';
            $code = 'vide';
        } elseif (! $complet) {
            $decision = 'Résultats partiels';
            $code = 'partiel';
        } elseif ($generale + 0.0001 >= $seuil && $echecs->isEmpty()) {
            $decision = 'Admis(e)';
            $code = 'admis';
        } else {
            $decision = 'En reprise';
            $code = 'reprise';
        }

        return [
            'inscription' => $inscription,
            'etudiant' => $etudiant,
            'classe' => $classe,
            'session' => $session,
            'seuil' => $seuil,
            'lignes' => $lignes,
            'moyenne_generale' => $generale,
            'credits' => $lignes->sum(fn ($l) => $l['matiere']->coefficient),
            'credits_valides' => $lignes->filter(fn ($l) => $l['valide'])->sum(fn ($l) => $l['matiere']->coefficient),
            'echecs' => $echecs->values(),
            'decision' => $decision,
            'decision_code' => $code,
            'examens_bloques' => $bloques->values(),
        ];
    }

    /** Résultats de toute une classe, triés par rang. */
    public function deliberation(int $classeId, int $anneeId, ?int $session = null): Collection
    {
        $inscriptions = Inscription::with('etudiant', 'classe.faculte', 'annee')
            ->where('classe_id', $classeId)->where('annee_academique_id', $anneeId)
            ->where('statut', '!=', 'abandon')->get();

        $resultats = $inscriptions->map(fn ($i) => $this->bulletin($i, $session))
            ->sortByDesc(fn ($r) => $r['moyenne_generale'] ?? -1)->values();

        $rang = 0;
        $precedent = null;
        foreach ($resultats as $idx => $r) {
            if ($r['moyenne_generale'] !== $precedent) {
                $rang = $idx + 1;
                $precedent = $r['moyenne_generale'];
            }
            $resultats[$idx] = $r + ['rang' => $r['moyenne_generale'] === null ? null : $rang];
        }

        return $resultats;
    }

    public function moyenneCours(?float $intra, ?float $final, ?float $reprise): ?float
    {
        $pi = (float) config('itac.notes.poids_intra', 40);
        $pf = (float) config('itac.notes.poids_final', 60);

        $base = match (true) {
            $intra !== null && $final !== null => ($intra * $pi + $final * $pf) / max(0.0001, $pi + $pf),
            $intra !== null => $intra,
            $final !== null => $final,
            default => null,
        };

        if ($reprise !== null) {
            $base = $base === null ? $reprise : max($base, $reprise);
        }

        return $base === null ? null : round($base, 2);
    }

    private function moy(array $v): ?float
    {
        return $v ? array_sum($v) / count($v) : null;
    }
}

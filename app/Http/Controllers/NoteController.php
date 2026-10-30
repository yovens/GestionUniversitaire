<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Examen;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Note;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Grille de saisie des notes (un examen × un cours = toute la classe sur une page).
 */
class NoteController extends Controller
{
    public function edit(Request $request)
    {
        $examen = $request->integer('examen') ? Examen::with('annee', 'faculte')->find($request->integer('examen')) : null;
        $matiere = $request->integer('matiere') ? Matiere::with('classe.faculte')->find($request->integer('matiere')) : null;

        if (! $examen || ! $matiere) {
            return view('notes.choisir', $this->choix($request));
        }

        $droit = $this->droit($examen, $matiere);
        abort_if($droit === 'aucun', 403, "Vous n'êtes pas autorisé à saisir les notes de ce cours pour cet examen.");

        $inscriptions = Inscription::with('etudiant')
            ->where('classe_id', $matiere->classe_id)->where('annee_academique_id', $examen->annee_academique_id)
            ->where('statut', '!=', 'abandon')->get()
            ->sortBy(fn ($i) => mb_strtolower($i->etudiant->nom.' '.$i->etudiant->prenom))->values();

        $notes = Note::where('examen_id', $examen->id)->where('matiere_id', $matiere->id)->get()->keyBy('etudiant_id');

        return view('notes.grille', [
            'examen' => $examen,
            'matiere' => $matiere,
            'inscriptions' => $inscriptions,
            'notes' => $notes,
            'lectureSeule' => $droit === 'lecture',
            'max' => (int) config('itac.notes.max', 100),
        ]);
    }

    public function update(Request $request)
    {
        $max = (int) config('itac.notes.max', 100);
        // Accepter la virgule décimale (« 72,5 ») et les espaces
        $request->merge(['notes' => collect($request->input('notes', []))
            ->map(fn ($v) => is_string($v) ? str_replace([',', ' '], ['.', ''], trim($v)) : $v)->all()]);
        $request->validate([
            'examen_id' => ['required', 'exists:examens,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'notes' => ['array'],
            'notes.*' => ['nullable', 'numeric', 'min:0', "max:$max"],
            'absent' => ['array'],
        ], [
            'notes.*.numeric' => 'Chaque note doit être un nombre.',
            'notes.*.max' => "Une note ne peut pas dépasser $max.",
            'notes.*.min' => 'Une note ne peut pas être négative.',
        ]);

        $examen = Examen::findOrFail($request->integer('examen_id'));
        $matiere = Matiere::with('classe')->findOrFail($request->integer('matiere_id'));
        if ($this->droit($examen, $matiere) !== 'ecriture') {
            abort(403, 'Saisie verrouillée : les notes de cet examen sont publiées ou ce cours ne vous est pas affecté.');
        }

        // Seuls les étudiants réellement inscrits dans la classe peuvent recevoir une note
        $inscrits = Inscription::where('classe_id', $matiere->classe_id)->where('annee_academique_id', $examen->annee_academique_id)
            ->where('statut', '!=', 'abandon')->pluck('etudiant_id')->flip();

        $saisies = $request->input('notes', []);
        $absents = $request->input('absent', []);
        $existantes = Note::where('examen_id', $examen->id)->where('matiere_id', $matiere->id)->get()->keyBy('etudiant_id');
        $changements = [];

        DB::transaction(function () use ($saisies, $absents, $inscrits, $existantes, $examen, $matiere, &$changements) {
            $ids = collect(array_keys($saisies))->merge(array_keys($absents))->map(fn ($id) => (int) $id)->unique();
            foreach ($ids as $etudiantId) {
                if (! $inscrits->has($etudiantId)) {
                    continue;
                }
                $absent = ! empty($absents[$etudiantId]);
                $brut = $saisies[$etudiantId] ?? null;
                $valeur = ($brut === null || $brut === '') ? null : round((float) str_replace(',', '.', (string) $brut), 2);
                $ancienne = $existantes->get($etudiantId);

                if ($valeur === null && ! $absent) {
                    if ($ancienne) {
                        $changements[] = [$etudiantId, $ancienne->absent ? 'ABS' : (float) $ancienne->valeur, null];
                        $ancienne->delete();
                    }
                    continue;
                }

                $avant = $ancienne ? ($ancienne->absent ? 'ABS' : (float) $ancienne->valeur) : null;
                $apres = $absent ? 'ABS' : $valeur;
                if ($avant === $apres) {
                    continue;
                }
                Note::updateOrCreate(
                    ['examen_id' => $examen->id, 'matiere_id' => $matiere->id, 'etudiant_id' => $etudiantId],
                    ['valeur' => $absent ? null : $valeur, 'absent' => $absent, 'saisie_par' => auth()->id()]
                );
                $changements[] = [$etudiantId, $avant, $apres];
            }
        });

        if ($changements) {
            $apresPublication = $examen->notes_publiees ? ' (après publication)' : '';
            Journal::log('notes.saisie', count($changements)." note(s) modifiée(s) — {$matiere->code}, {$examen->nom}{$apresPublication}", $matiere, [
                'examen_id' => $examen->id,
                'changements' => array_map(fn ($c) => ['etudiant' => $c[0], 'avant' => $c[1], 'apres' => $c[2]], $changements),
            ]);
        }

        return redirect()->route('notes.saisie', ['examen' => $examen->id, 'matiere' => $matiere->id])
            ->with('success', $changements ? count($changements).' note(s) enregistrée(s).' : 'Aucune modification.');
    }

    /** 'ecriture', 'lecture' ou 'aucun' */
    private function droit(Examen $examen, Matiere $matiere): string
    {
        $me = $this->user();
        if ($examen->faculte_id !== null && $examen->faculte_id !== $matiere->faculte_id) {
            return 'aucun';
        }
        if ($examen->session !== $matiere->session) {
            return 'aucun';
        }
        if ($me->isStaff()) {
            return $me->peutAccederFaculte($matiere->faculte_id) ? 'ecriture' : 'aucun';
        }
        if ($me->isProfesseur()) {
            $affecte = Affectation::where('professeur_id', $me->id)->where('matiere_id', $matiere->id)
                ->where('annee_academique_id', $examen->annee_academique_id)->exists();
            if (! $affecte) {
                return 'aucun';
            }

            return $examen->notes_publiees ? 'lecture' : 'ecriture';
        }

        return 'aucun';
    }

    private function choix(Request $request): array
    {
        $me = $this->user();
        $annee = $this->anneeCourante();
        if (! $annee) {
            return ['annee' => null, 'lignes' => collect(), 'examens' => collect(), 'classes' => collect()];
        }

        if ($me->isProfesseur()) {
            $matieres = Affectation::with('matiere.classe.faculte')->where('professeur_id', $me->id)
                ->where('annee_academique_id', $annee->id)->get()->pluck('matiere');
        } else {
            $matieres = Matiere::visiblePar($me)->with('classe.faculte')
                ->when($request->integer('classe'), fn ($q, $c) => $q->where('classe_id', $c), fn ($q) => $q->whereRaw('1 = 0'))
                ->orderBy('session')->orderBy('nom')->get();
        }

        $examens = Examen::where('annee_academique_id', $annee->id)->orderBy('date_debut')->get();
        $lignes = $matieres->map(fn (Matiere $m) => [
            'matiere' => $m,
            'examens' => $examens->filter(fn ($e) => ($e->faculte_id === null || $e->faculte_id === $m->faculte_id) && $e->session === $m->session)->values(),
        ]);

        return [
            'annee' => $annee,
            'lignes' => $lignes,
            'classes' => $me->isStaff() ? Classe::visiblePar($me)->with('faculte')->orderBy('faculte_id')->orderBy('niveau')->get() : collect(),
            'compte' => Note::whereIn('matiere_id', $matieres->pluck('id'))->whereIn('examen_id', $examens->pluck('id'))
                ->selectRaw('matiere_id, examen_id, COUNT(*) as n')->groupBy('matiere_id', 'examen_id')->get()
                ->mapWithKeys(fn ($r) => [$r->matiere_id.'-'.$r->examen_id => $r->n]),
        ];
    }
}

<?php

namespace App\Http\Controllers\Scolarite;

use App\Enums\Role;
use App\Http\Controllers\Admin\UtilisateurController;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\User;
use App\Services\Fichiers;
use App\Services\FinanceService;
use App\Services\Journal;
use App\Services\Matricule;
use App\Services\ResultatService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EtudiantController extends Controller
{
    public function index(Request $request)
    {
        $annee = $this->anneeChoisie();
        $faculte = $this->faculteFiltre();
        $statut = $request->string('statut')->toString();

        $etudiants = User::role(Role::Etudiant)
            ->visiblePar($this->user())
            ->with(['faculte', 'inscriptions' => fn ($q) => $q->where('annee_academique_id', $annee?->id)->with('classe')])
            ->when($faculte, fn ($q, $f) => $q->where('faculte_id', $f))
            ->when($statut === 'non_inscrit', fn ($q) => $q->whereDoesntHave('inscriptions', fn ($i) => $i->where('annee_academique_id', $annee?->id)))
            ->when($statut && $statut !== 'non_inscrit', fn ($q) => $q->whereHas('inscriptions', fn ($i) => $i->where('annee_academique_id', $annee?->id)->where('statut', $statut)))
            ->when($request->integer('classe'), fn ($q, $c) => $q->whereHas('inscriptions', fn ($i) => $i->where('annee_academique_id', $annee?->id)->where('classe_id', $c)))
            ->recherche($request->string('q')->toString())
            ->orderBy('nom')->orderBy('prenom')
            ->paginate(30)->withQueryString();

        return view('scolarite.etudiants.index', [
            'etudiants' => $etudiants,
            'annee' => $annee,
            'annees' => \App\Models\AnneeAcademique::orderByDesc('date_debut')->get(),
            'facultes' => $this->facultesVisibles(),
            'classes' => Classe::visiblePar($this->user())->with('faculte')->when($faculte, fn ($q, $f) => $q->where('faculte_id', $f))->orderBy('faculte_id')->orderBy('niveau')->get(),
        ]);
    }

    public function create()
    {
        $this->requireAnnee();

        return view('scolarite.etudiants.form', [
            'etudiant' => new User(['role' => Role::Etudiant]),
            'classes' => Classe::visiblePar($this->user())->with('faculte')->orderBy('faculte_id')->orderBy('niveau')->get(),
            'inscription' => null,
        ]);
    }

    public function store(Request $request)
    {
        $annee = $this->requireAnnee();
        $data = $this->valider($request);
        $classe = Classe::with('faculte')->findOrFail($data['classe_id']);
        $this->verifierFaculte($classe->faculte_id);
        $motDePasse = UtilisateurController::motDePasseProvisoire();

        $etudiant = null;
        for ($essai = 1; $essai <= 3 && ! $etudiant; $essai++) {
            try {
                $etudiant = DB::transaction(function () use ($data, $classe, $annee, $motDePasse, $request) {
                    $matricule = Matricule::generer($classe->faculte);
                    $etudiant = User::create(array_merge(collect($data)->except(['classe_id', 'photo'])->all(), [
                        'role' => Role::Etudiant->value,
                        'faculte_id' => $classe->faculte_id,
                        'matricule' => $matricule,
                        'username' => ($data['username'] ?? null) ?: strtolower($matricule),
                        'password' => $motDePasse,
                        'must_change_password' => true,
                        'actif' => true,
                    ]));
                    if ($request->hasFile('photo')) {
                        $etudiant->update(['photo' => Fichiers::stocker($request->file('photo'), 'photos')]);
                    }
                    Inscription::create([
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $classe->id,
                        'annee_academique_id' => $annee->id,
                        'statut' => 'inscrit',
                        'date_inscription' => today(),
                        'created_by' => auth()->id(),
                    ]);

                    return $etudiant;
                });
            } catch (QueryException $e) {
                // deux inscriptions simultanées peuvent viser le même matricule : on réessaie
                if ($essai === 3 || ! str_contains(strtolower($e->getMessage()), 'unique')) {
                    throw $e;
                }
            }
        }

        Journal::log('etudiant.inscription', "Inscription de {$etudiant->nom_complet} ({$etudiant->matricule}) en {$classe->nom}", $etudiant);

        return redirect()->route('etudiants.show', $etudiant)
            ->with('success', "{$etudiant->nom_complet} est inscrit(e) en {$classe->nom}.")
            ->with('identifiants', ['username' => $etudiant->username, 'password' => $motDePasse]);
    }

    public function show(User $etudiant, FinanceService $finance, ResultatService $resultats)
    {
        $this->verifierEtudiant($etudiant);
        $annee = $this->anneeChoisie();
        $etudiant->load(['faculte', 'inscriptions' => fn ($q) => $q->with('classe.faculte', 'annee')->orderByDesc('annee_academique_id')]);
        $inscription = $etudiant->inscriptions->firstWhere('annee_academique_id', $annee?->id);

        return view('scolarite.etudiants.show', [
            'etudiant' => $etudiant,
            'annee' => $annee,
            'inscription' => $inscription,
            'situation' => $finance->situation($etudiant, $annee),
            'bulletin' => $inscription ? $resultats->bulletin($inscription) : null,
            'paiements' => Paiement::with('caissier', 'annee')->where('etudiant_id', $etudiant->id)->latest('date_paiement')->latest('id')->get(),
            'classes' => Classe::visiblePar($this->user())->with('faculte')->orderBy('faculte_id')->orderBy('niveau')->get(),
            'anneeCourante' => $this->anneeCourante(),
        ]);
    }

    public function edit(User $etudiant)
    {
        $this->verifierEtudiant($etudiant);
        $annee = $this->anneeCourante();

        return view('scolarite.etudiants.form', [
            'etudiant' => $etudiant,
            'classes' => Classe::visiblePar($this->user())->with('faculte')->orderBy('faculte_id')->orderBy('niveau')->get(),
            'inscription' => $annee ? $etudiant->inscriptions()->where('annee_academique_id', $annee->id)->first() : null,
        ]);
    }

    public function update(Request $request, User $etudiant)
    {
        $this->verifierEtudiant($etudiant);
        $data = $this->valider($request, $etudiant);
        $annee = $this->anneeCourante();
        $inscription = $annee ? $etudiant->inscriptions()->where('annee_academique_id', $annee->id)->first() : null;

        DB::transaction(function () use ($data, $etudiant, $inscription, $request) {
            $champs = collect($data)->except(['classe_id', 'photo'])->all();
            if (! ($champs['username'] ?? null)) {
                unset($champs['username']);
            }
            $etudiant->update($champs);

            if ($request->hasFile('photo')) {
                $ancien = $etudiant->photo;
                $etudiant->update(['photo' => Fichiers::stocker($request->file('photo'), 'photos')]);
                Fichiers::supprimer($ancien);
            }

            if ($inscription && ! empty($data['classe_id']) && (int) $data['classe_id'] !== $inscription->classe_id) {
                $classe = Classe::findOrFail($data['classe_id']);
                $this->verifierFaculte($classe->faculte_id);
                $aDesNotes = Note::where('etudiant_id', $etudiant->id)
                    ->whereHas('matiere', fn ($q) => $q->where('classe_id', $inscription->classe_id))
                    ->whereHas('examen', fn ($q) => $q->where('annee_academique_id', $inscription->annee_academique_id))->exists();
                if ($aDesNotes) {
                    abort(back()->withInput()->with('error', 'L’étudiant a déjà des notes dans sa classe actuelle : changement de classe refusé.'));
                }
                $inscription->update(['classe_id' => $classe->id]);
                $etudiant->update(['faculte_id' => $classe->faculte_id]);
            }
        });

        Journal::log('etudiant.modification', "Modification du dossier de {$etudiant->nom_complet}", $etudiant);

        return redirect()->route('etudiants.show', $etudiant)->with('success', 'Dossier mis à jour.');
    }

    public function destroy(User $etudiant)
    {
        $this->verifierEtudiant($etudiant);
        if (Paiement::where('etudiant_id', $etudiant->id)->exists() || Note::where('etudiant_id', $etudiant->id)->exists()) {
            return back()->with('error', 'Cet étudiant a des notes ou des paiements : son dossier doit être conservé. Marquez plutôt son inscription « Abandon ».');
        }
        $etudiant->inscriptions()->delete();
        $etudiant->delete();
        Journal::log('etudiant.suppression', "Suppression du dossier de {$etudiant->nom_complet} ({$etudiant->matricule})");

        return redirect()->route('etudiants.index')->with('success', 'Dossier supprimé.');
    }

    public function reinscrire(Request $request, User $etudiant)
    {
        $this->verifierEtudiant($etudiant);
        $annee = $this->requireAnnee();
        $data = $request->validate(['classe_id' => ['required', 'exists:classes,id']]);
        $classe = Classe::findOrFail($data['classe_id']);
        $this->verifierFaculte($classe->faculte_id);

        try {
            Inscription::create([
                'etudiant_id' => $etudiant->id, 'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
                'statut' => 'inscrit', 'date_inscription' => today(), 'created_by' => auth()->id(),
            ]);
        } catch (QueryException) {
            return back()->with('error', "Cet étudiant est déjà inscrit pour {$annee->libelle}.");
        }
        $etudiant->update(['faculte_id' => $classe->faculte_id]);
        Journal::log('etudiant.reinscription', "Réinscription de {$etudiant->nom_complet} en {$classe->nom} ({$annee->libelle})", $etudiant);

        return back()->with('success', "Réinscription en {$classe->nom} enregistrée.");
    }

    public function statut(Request $request, Inscription $inscription)
    {
        $inscription->load('classe', 'etudiant');
        $this->verifierFaculte($inscription->classe->faculte_id);
        $data = $request->validate(['statut' => ['required', Rule::in(array_keys(Inscription::STATUTS))]]);
        $inscription->update($data);
        Journal::log('inscription.statut', "Statut de {$inscription->etudiant->nom_complet} : {$inscription->statut_label}", $inscription);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function reinitialiser(User $etudiant)
    {
        $this->verifierEtudiant($etudiant);
        $mdp = UtilisateurController::motDePasseProvisoire();
        $etudiant->forceFill(['password' => $mdp, 'must_change_password' => true])->save();
        Journal::log('etudiant.mdp', "Réinitialisation du mot de passe de {$etudiant->nom_complet}", $etudiant);

        return back()->with('identifiants', ['username' => $etudiant->username, 'password' => $mdp])
            ->with('success', 'Nouveau mot de passe provisoire généré.');
    }

    private function verifierEtudiant(User $etudiant): void
    {
        abort_unless($etudiant->isEtudiant(), 404);
        $this->verifierFaculte($etudiant->faculte_id);
    }

    private function valider(Request $request, ?User $e = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['nullable', 'in:M,F'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($e?->id)],
            'contact_urgence' => ['nullable', 'string', 'max:150'],
            'username' => ['nullable', 'alpha_dash', 'min:3', 'max:60', Rule::unique('users', 'username')->ignore($e?->id)],
            'classe_id' => [$e ? 'nullable' : 'required', 'exists:classes,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('itac.uploads.photo_max_kb')],
        ]);
    }
}

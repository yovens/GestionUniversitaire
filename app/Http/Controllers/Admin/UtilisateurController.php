<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Faculte;
use App\Models\User;
use App\Services\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Gestion du personnel : administrateurs, doyens, secrétaires, professeurs. */
class UtilisateurController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('faculte')
            ->whereIn('role', array_map(fn ($r) => $r->value, Role::staff()))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->integer('faculte'), fn ($q, $f) => $q->where('faculte_id', $f))
            ->recherche($request->string('q'))
            ->orderBy('nom')->paginate(25)->withQueryString();

        return view('admin.utilisateurs.index', [
            'users' => $users,
            'facultes' => Faculte::orderBy('nom')->get(),
            'roles' => Role::staff(),
        ]);
    }

    public function create()
    {
        return view('admin.utilisateurs.form', [
            'user' => new User(['role' => Role::Professeur, 'actif' => true]),
            'facultes' => Faculte::orderBy('nom')->get(),
            'roles' => Role::staff(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);
        $motDePasse = ($data['password'] ?? null) ?: self::motDePasseProvisoire();
        $user = User::create(array_merge($data, ['password' => $motDePasse, 'must_change_password' => true, 'actif' => true]));
        Journal::log('utilisateur.creation', "Création du compte {$user->username} ({$user->role->label()})", $user);

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', "Compte créé pour {$user->nom_complet}.")
            ->with('identifiants', ['username' => $user->username, 'password' => $motDePasse]);
    }

    public function edit(User $user)
    {
        abort_if($user->isEtudiant(), 404);

        return view('admin.utilisateurs.form', [
            'user' => $user,
            'facultes' => Faculte::orderBy('nom')->get(),
            'roles' => Role::staff(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->isEtudiant(), 404);
        $data = $this->valider($request, $user);
        if ($user->id === $this->user()->id && $data['role'] !== Role::Admin->value) {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre rôle d’administrateur.');
        }
        unset($data['password']);
        $user->update($data);
        Journal::log('utilisateur.modification', "Modification du compte {$user->username}", $user);

        return redirect()->route('admin.utilisateurs.index')->with('success', 'Compte mis à jour.');
    }

    public function reinitialiser(User $user)
    {
        $mdp = self::motDePasseProvisoire();
        $user->forceFill(['password' => $mdp, 'must_change_password' => true])->save();
        Journal::log('utilisateur.mdp', "Réinitialisation du mot de passe de {$user->username}", $user);

        return back()->with('identifiants', ['username' => $user->username, 'password' => $mdp])
            ->with('success', 'Mot de passe réinitialisé. Communiquez-le à la personne.');
    }

    public function basculer(User $user)
    {
        if ($user->id === $this->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }
        $user->forceFill(['actif' => ! $user->actif])->save();
        Journal::log('utilisateur.statut', ($user->actif ? 'Activation' : 'Désactivation')." du compte {$user->username}", $user);

        return back()->with('success', $user->actif ? 'Compte activé.' : 'Compte désactivé.');
    }

    public function destroy(User $user)
    {
        if ($user->id === $this->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        if ($user->affectations()->exists()) {
            return back()->with('error', 'Ce professeur a des cours affectés. Retirez les affectations ou désactivez le compte.');
        }
        $user->delete(); // suppression logique (historique conservé)
        Journal::log('utilisateur.suppression', "Suppression du compte {$user->username}", $user);

        return back()->with('success', 'Compte supprimé.');
    }

    public static function motDePasseProvisoire(): string
    {
        return 'Itac-'.Str::upper(Str::random(4)).random_int(100, 999);
    }

    private function valider(Request $request, ?User $u = null): array
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_map(fn ($r) => $r->value, Role::staff()))],
            'faculte_id' => ['nullable', 'exists:facultes,id', Rule::requiredIf(fn () => $request->input('role') === Role::Doyen->value)],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['nullable', 'in:M,F'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($u?->id)],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:60', Rule::unique('users', 'username')->ignore($u?->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ], ['faculte_id.required' => 'Un doyen doit être rattaché à une faculté.']);

        if ($data['role'] === Role::Admin->value) {
            $data['faculte_id'] = null;
        }

        return $data;
    }
}

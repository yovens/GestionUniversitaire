<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\SujetExamen;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** Sert les fichiers privés après vérification des droits. */
class FichierController extends Controller
{
    public function photo(User $user)
    {
        $disk = Storage::disk('local');
        if (! $user->photo || ! $disk->exists($user->photo)) {
            abort(404);
        }

        return response()->file($disk->path($user->photo), ['Cache-Control' => 'private, max-age=86400']);
    }

    public function justificatif(Paiement $paiement)
    {
        $me = $this->user();
        $autorise = $me->id === $paiement->etudiant_id
            || ($me->isStaff() && $me->peutAccederFaculte($paiement->etudiant?->faculte_id));
        abort_unless($autorise, 403);

        return $this->servir($paiement->justificatif, 'justificatif-'.$paiement->numero_recu);
    }

    public function sujet(SujetExamen $sujet)
    {
        $me = $this->user();
        $sujet->loadMissing('matiere');
        $autorise = $me->id === $sujet->professeur_id
            || ($me->isStaff() && $me->peutAccederFaculte($sujet->matiere?->faculte_id));
        abort_unless($autorise, 403);

        return $this->servir($sujet->fichier, $sujet->fichier_nom ?: 'sujet');
    }

    private function servir(?string $chemin, string $nom)
    {
        $disk = Storage::disk('local');
        abort_unless($chemin && $disk->exists($chemin), 404, 'Fichier introuvable.');
        $ext = pathinfo($chemin, PATHINFO_EXTENSION);
        $nom = pathinfo($nom, PATHINFO_FILENAME).'.'.$ext;

        return response()->file($disk->path($chemin), [
            'Content-Disposition' => 'inline; filename="'.addslashes($nom).'"',
        ]);
    }
}

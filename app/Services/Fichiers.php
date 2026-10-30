<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tous les fichiers envoyés sont stockés hors du dossier public (disque "local")
 * avec un nom aléatoire, puis servis via un contrôleur qui vérifie les droits.
 */
class Fichiers
{
    public static function stocker(UploadedFile $file, string $dossier): string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $nom = Str::uuid()->toString().'.'.$ext;

        return $file->storeAs($dossier, $nom, 'local');
    }

    public static function supprimer(?string $chemin): void
    {
        if ($chemin && Storage::disk('local')->exists($chemin)) {
            Storage::disk('local')->delete($chemin);
        }
    }
}

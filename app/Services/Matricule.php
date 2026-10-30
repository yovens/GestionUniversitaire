<?php

namespace App\Services;

use App\Models\Faculte;
use App\Models\User;

/** Matricule étudiant : CODEFAC-AA-NNNN (ex. INFO-26-0042) */
class Matricule
{
    public static function generer(Faculte $faculte): string
    {
        $prefix = strtoupper($faculte->code).'-'.now()->format('y').'-';
        $dernier = User::withTrashed()->where('matricule', 'like', $prefix.'%')
            ->orderByDesc('matricule')->value('matricule');
        $n = $dernier ? ((int) substr($dernier, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}

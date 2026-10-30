<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Doyen = 'doyen';
    case Secretaire = 'secretaire';
    case Professeur = 'professeur';
    case Etudiant = 'etudiant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Doyen => 'Doyen / Responsable',
            self::Secretaire => 'Secrétaire',
            self::Professeur => 'Professeur',
            self::Etudiant => 'Étudiant',
        };
    }

    /** Rôles du personnel (gérés depuis l'écran Utilisateurs). */
    public static function staff(): array
    {
        return [self::Admin, self::Doyen, self::Secretaire, self::Professeur];
    }
}

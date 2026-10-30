<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Limite une requête aux facultés auxquelles l'utilisateur a accès.
 * Admin (ou secrétaire central sans faculté) : toutes les facultés.
 */
trait ScopedToFaculte
{
    public function scopeVisiblePar(Builder $query, User $user, string $column = 'faculte_id'): Builder
    {
        $fid = $user->faculteScope();

        return $fid === null ? $query : $query->where($this->qualifyColumn($column), $fid);
    }
}

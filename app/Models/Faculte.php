<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculte extends Model
{
    protected $attributes = ['actif' => true, 'couleur' => '#2F5DA8', 'seuil_reussite' => 65];

    protected $fillable = ['code', 'nom', 'couleur', 'description', 'seuil_reussite', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'seuil_reussite' => 'decimal:2'];
    }

    public function classes(): HasMany { return $this->hasMany(Classe::class)->orderBy('niveau'); }
    public function matieres(): HasMany { return $this->hasMany(Matiere::class); }
    public function tranches(): HasMany { return $this->hasMany(TrancheFrais::class)->orderBy('numero'); }
    public function users(): HasMany { return $this->hasMany(User::class); }

    public function doyens(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::Doyen->value);
    }

    public function scopeVisiblePar(Builder $q, User $user): Builder
    {
        $fid = $user->faculteScope();

        return $fid === null ? $q : $q->whereKey($fid);
    }

    public function scopeActives(Builder $q): Builder
    {
        return $q->where('actif', true)->orderBy('nom');
    }
}

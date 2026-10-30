<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\ScopedToFaculte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, ScopedToFaculte;

    protected $attributes = ['actif' => true, 'must_change_password' => true];

    protected $fillable = [
        'role', 'faculte_id', 'matricule', 'nom', 'prenom', 'sexe', 'date_naissance', 'lieu_naissance',
        'adresse', 'phone', 'email', 'contact_urgence', 'username', 'password', 'photo', 'actif',
        'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['faculte_id' => 'integer', 
            'role' => Role::class,
            'password' => 'hashed',
            'actif' => 'boolean',
            'must_change_password' => 'boolean',
            'date_naissance' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    // ---- Relations -------------------------------------------------------

    public function faculte(): BelongsTo
    {
        return $this->belongsTo(Faculte::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'etudiant_id');
    }

    public function inscriptionCourante(): HasOne
    {
        return $this->hasOne(Inscription::class, 'etudiant_id')
            ->whereHas('annee', fn ($q) => $q->where('courante', true));
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'professeur_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'etudiant_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'etudiant_id');
    }

    // ---- Rôles -----------------------------------------------------------

    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $value = $role instanceof Role ? $role : Role::from($role);
            if ($this->role === $value) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool { return $this->role === Role::Admin; }
    public function isEtudiant(): bool { return $this->role === Role::Etudiant; }
    public function isProfesseur(): bool { return $this->role === Role::Professeur; }
    public function isStaff(): bool { return in_array($this->role, [Role::Admin, Role::Doyen, Role::Secretaire], true); }

    /**
     * Faculté à laquelle le personnel est limité (null = toutes).
     */
    public function faculteScope(): ?int
    {
        if ($this->role === Role::Admin) {
            return null;
        }

        return $this->faculte_id; // secrétaire central sans faculté => null => toutes
    }

    public function peutAccederFaculte(?int $faculteId): bool
    {
        $scope = $this->faculteScope();

        return $scope === null || $faculteId === null || $scope === (int) $faculteId;
    }

    // ---- Présentation ----------------------------------------------------

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getInitialesAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->prenom, 0, 1).mb_substr($this->nom, 0, 1));
    }

    public function scopeRole(Builder $q, Role $role): Builder
    {
        return $q->where('role', $role->value);
    }

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        if (! filled($terme)) {
            return $q;
        }
        $t = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($terme)).'%';

        return $q->where(fn ($w) => $w->where('nom', 'like', $t)->orWhere('prenom', 'like', $t)
            ->orWhere('matricule', 'like', $t)->orWhere('username', 'like', $t)->orWhere('phone', 'like', $t));
    }
}

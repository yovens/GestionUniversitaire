<?php

namespace App\Models;

use App\Models\Concerns\ScopedToFaculte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matiere extends Model
{
    use ScopedToFaculte;

    protected $attributes = ['coefficient' => 1, 'session' => 1];

    protected $fillable = ['faculte_id', 'classe_id', 'code', 'nom', 'coefficient', 'session'];

    protected function casts(): array
    {
        return ['faculte_id' => 'integer', 'classe_id' => 'integer', 'coefficient' => 'integer', 'session' => 'integer'];
    }

    protected static function booted(): void
    {
        // La faculté est toujours celle de la classe : jamais d'incohérence
        static::saving(function (Matiere $m) {
            if ($m->classe_id) {
                $m->faculte_id = Classe::whereKey($m->classe_id)->value('faculte_id');
            }
        });
    }

    public function faculte(): BelongsTo { return $this->belongsTo(Faculte::class); }
    public function classe(): BelongsTo { return $this->belongsTo(Classe::class); }
    public function affectations(): HasMany { return $this->hasMany(Affectation::class); }
    public function notes(): HasMany { return $this->hasMany(Note::class); }

    public function professeurPour(?AnneeAcademique $annee): ?User
    {
        if (! $annee) {
            return null;
        }

        return $this->affectations->firstWhere('annee_academique_id', $annee->id)?->professeur;
    }
}

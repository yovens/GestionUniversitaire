<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    protected function casts(): array
    {
        return ['professeur_id' => 'integer', 'matiere_id' => 'integer', 'annee_academique_id' => 'integer'];
    }

    protected $fillable = ['professeur_id', 'matiere_id', 'annee_academique_id'];

    public function professeur(): BelongsTo { return $this->belongsTo(User::class, 'professeur_id')->withTrashed(); }
    public function matiere(): BelongsTo { return $this->belongsTo(Matiere::class); }
    public function annee(): BelongsTo { return $this->belongsTo(AnneeAcademique::class, 'annee_academique_id'); }
}

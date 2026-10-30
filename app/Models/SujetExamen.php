<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SujetExamen extends Model
{
    protected $table = 'sujets_examen';

    protected function casts(): array
    {
        return ['professeur_id' => 'integer', 'matiere_id' => 'integer'];
    }

    protected $fillable = ['professeur_id', 'matiere_id', 'examen_id', 'titre', 'consigne', 'fichier', 'fichier_nom'];

    public function professeur(): BelongsTo { return $this->belongsTo(User::class, 'professeur_id')->withTrashed(); }
    public function matiere(): BelongsTo { return $this->belongsTo(Matiere::class); }
    public function examen(): BelongsTo { return $this->belongsTo(Examen::class); }
}

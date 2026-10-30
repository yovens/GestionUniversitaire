<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $attributes = ['absent' => false];

    protected $fillable = ['examen_id', 'matiere_id', 'etudiant_id', 'valeur', 'absent', 'saisie_par'];

    protected function casts(): array
    {
        return ['etudiant_id' => 'integer', 'examen_id' => 'integer', 'matiere_id' => 'integer', 'valeur' => 'decimal:2', 'absent' => 'boolean'];
    }

    public function examen(): BelongsTo { return $this->belongsTo(Examen::class); }
    public function matiere(): BelongsTo { return $this->belongsTo(Matiere::class); }
    public function etudiant(): BelongsTo { return $this->belongsTo(User::class, 'etudiant_id')->withTrashed(); }
    public function auteur(): BelongsTo { return $this->belongsTo(User::class, 'saisie_par')->withTrashed(); }
}

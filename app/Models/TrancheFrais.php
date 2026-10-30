<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrancheFrais extends Model
{
    protected $table = 'tranches_frais';

    protected $fillable = ['faculte_id', 'annee_academique_id', 'numero', 'libelle', 'montant', 'date_limite'];

    protected function casts(): array
    {
        return ['faculte_id' => 'integer', 'montant' => 'decimal:2', 'date_limite' => 'date', 'numero' => 'integer'];
    }

    public function faculte(): BelongsTo { return $this->belongsTo(Faculte::class); }
    public function annee(): BelongsTo { return $this->belongsTo(AnneeAcademique::class, 'annee_academique_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscription extends Model
{
    public const STATUTS = [
        'inscrit' => 'Inscrit',
        'admis' => 'Admis',
        'reprise' => 'En reprise',
        'abandon' => 'Abandon',
    ];

    protected $attributes = ['statut' => 'inscrit'];

    protected $fillable = ['etudiant_id', 'classe_id', 'annee_academique_id', 'statut', 'date_inscription', 'created_by'];

    protected function casts(): array
    {
        return ['classe_id' => 'integer', 'etudiant_id' => 'integer', 'annee_academique_id' => 'integer', 'date_inscription' => 'date'];
    }

    public function etudiant(): BelongsTo { return $this->belongsTo(User::class, 'etudiant_id')->withTrashed(); }
    public function classe(): BelongsTo { return $this->belongsTo(Classe::class); }
    public function annee(): BelongsTo { return $this->belongsTo(AnneeAcademique::class, 'annee_academique_id'); }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    public const METHODES = [
        'especes' => 'Espèces',
        'moncash' => 'MonCash',
        'natcash' => 'NatCash',
        'virement' => 'Virement bancaire',
        'depot' => 'Dépôt bancaire',
        'cheque' => 'Chèque',
    ];

    protected $fillable = [
        'numero_recu', 'etudiant_id', 'annee_academique_id', 'tranche_numero', 'montant', 'date_paiement',
        'methode', 'reference', 'note', 'justificatif', 'enregistre_par',
    ];

    protected function casts(): array
    {
        return ['etudiant_id' => 'integer', 'tranche_numero' => 'integer', 'montant' => 'decimal:2', 'date_paiement' => 'date', 'annule_at' => 'datetime'];
    }

    public function etudiant(): BelongsTo { return $this->belongsTo(User::class, 'etudiant_id')->withTrashed(); }
    public function annee(): BelongsTo { return $this->belongsTo(AnneeAcademique::class, 'annee_academique_id'); }
    public function caissier(): BelongsTo { return $this->belongsTo(User::class, 'enregistre_par')->withTrashed(); }
    public function annulePar(): BelongsTo { return $this->belongsTo(User::class, 'annule_par')->withTrashed(); }

    public function scopeValides(Builder $q): Builder
    {
        return $q->whereNull('annule_at');
    }

    public function estAnnule(): bool
    {
        return $this->annule_at !== null;
    }

    public function getMethodeLabelAttribute(): string
    {
        return self::METHODES[$this->methode] ?? $this->methode;
    }
}

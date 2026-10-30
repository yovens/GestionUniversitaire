<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    public const TYPES = ['intra' => 'Intra', 'final' => 'Final', 'reprise' => 'Reprise'];

    protected $attributes = ['notes_publiees' => false, 'session' => 1];

    protected $fillable = [
        'annee_academique_id', 'faculte_id', 'nom', 'type', 'session', 'date_debut', 'date_fin',
        'tranche_requise', 'notes_publiees', 'publie_at',
    ];

    protected function casts(): array
    {
        return ['faculte_id' => 'integer', 'annee_academique_id' => 'integer', 
            'date_debut' => 'date', 'date_fin' => 'date', 'notes_publiees' => 'boolean',
            'publie_at' => 'datetime', 'session' => 'integer', 'tranche_requise' => 'integer',
        ];
    }

    public function annee(): BelongsTo { return $this->belongsTo(AnneeAcademique::class, 'annee_academique_id'); }
    public function faculte(): BelongsTo { return $this->belongsTo(Faculte::class); }
    public function notes(): HasMany { return $this->hasMany(Note::class); }

    /** Examens qui concernent une faculté (les siens + ceux communs à toutes). */
    public function scopePourFaculte(Builder $q, ?int $faculteId): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('faculte_id')->when($faculteId, fn ($x) => $x->orWhere('faculte_id', $faculteId)));
    }

    public function scopeVisiblePar(Builder $q, User $user): Builder
    {
        $fid = $user->faculteScope();

        return $fid === null ? $q : $q->pourFaculte($fid);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getLibelleAttribute(): string
    {
        return $this->nom.' ('.$this->type_label.', session '.$this->session.')';
    }
}

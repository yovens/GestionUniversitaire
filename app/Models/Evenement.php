<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evenement extends Model
{
    public const TYPES = [
        'cours' => 'Ouverture / reprise des cours',
        'examen' => 'Examens',
        'reprise' => 'Reprises',
        'conge' => 'Congés / vacances',
        'autre' => 'Autre activité',
    ];

    protected $fillable = ['faculte_id', 'examen_id', 'titre', 'description', 'type', 'date_debut', 'date_fin', 'created_by'];

    protected function casts(): array
    {
        return ['faculte_id' => 'integer', 'examen_id' => 'integer', 'date_debut' => 'date', 'date_fin' => 'date'];
    }

    public function faculte(): BelongsTo { return $this->belongsTo(Faculte::class); }

    public function scopePourFaculte(Builder $q, ?int $faculteId): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('faculte_id')->when($faculteId, fn ($x) => $x->orWhere('faculte_id', $faculteId)));
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}

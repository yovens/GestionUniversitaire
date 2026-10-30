<?php

namespace App\Models;

use App\Models\Concerns\ScopedToFaculte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    use ScopedToFaculte;

    protected function casts(): array
    {
        return ['faculte_id' => 'integer', 'niveau' => 'integer'];
    }

    protected $fillable = ['faculte_id', 'nom', 'niveau'];

    public function faculte(): BelongsTo { return $this->belongsTo(Faculte::class); }
    public function matieres(): HasMany { return $this->hasMany(Matiere::class)->orderBy('session')->orderBy('nom'); }
    public function inscriptions(): HasMany { return $this->hasMany(Inscription::class); }

    public function getLibelleCompletAttribute(): string
    {
        return $this->nom.' — '.($this->faculte?->code ?? '');
    }
}

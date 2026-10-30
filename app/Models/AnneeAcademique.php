<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnneeAcademique extends Model
{
    protected $table = 'annees_academiques';

    protected $attributes = ['courante' => false];

    protected $fillable = ['libelle', 'date_debut', 'date_fin', 'courante'];

    protected function casts(): array
    {
        return ['date_debut' => 'date', 'date_fin' => 'date', 'courante' => 'boolean'];
    }

    public static function courante(): ?self
    {
        $id = Cache::remember('annee_courante_id', 600, fn () => static::where('courante', true)->value('id'));

        return $id ? static::find($id) : null;
    }

    public function definirCommeCourante(): void
    {
        DB::transaction(function () {
            static::where('courante', true)->update(['courante' => false]);
            $this->forceFill(['courante' => true])->save();
        });
        Cache::forget('annee_courante_id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('annee_courante_id'));
        static::deleted(fn () => Cache::forget('annee_courante_id'));
    }
}

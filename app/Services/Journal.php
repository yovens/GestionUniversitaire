<?php

namespace App\Services;

use App\Models\JournalActivite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Journal d'audit : qui a fait quoi, quand. Ne bloque jamais l'action principale.
 */
class Journal
{
    public static function log(string $action, string $description, ?Model $sujet = null, array $details = []): void
    {
        try {
            JournalActivite::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'sujet_type' => $sujet ? class_basename($sujet) : null,
                'sujet_id' => $sujet?->getKey(),
                'description' => mb_substr($description, 0, 1000),
                'details' => $details ?: null,
                'ip' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Journal indisponible : '.$e->getMessage());
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder de production : rien de fictif.
 * Utilisez `php artisan itac:installer` pour créer l'administrateur,
 * ou `php artisan db:seed --class=DemoSeeder` pour une base de démonstration.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Rien à créer. Lancez « php artisan itac:installer » pour créer le premier administrateur.');
    }
}

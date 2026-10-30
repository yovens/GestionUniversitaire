<?php

use Illuminate\Support\Facades\Schedule;

// Tâches planifiées — activez le cron : * * * * * cd /chemin/vers/itac && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('itac:sauvegarde')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('auth:clear-resets')->daily();

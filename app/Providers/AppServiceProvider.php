<?php

namespace App\Providers;

use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(FinanceService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.itac');
        // $me = utilisateur connecté, disponible dans toutes les vues
        View::composer('*', function ($view) {
            if (! array_key_exists('me', $view->getData())) {
                $view->with('me', auth()->user());
            }
        });
        Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');

        // En développement : erreurs explicites sur les attributs inconnus (jamais en production)
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        if ($this->app->isProduction()) {
            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}

<?php

namespace App\Providers;

use App\Services\Admin\ContentFlags;
use App\Support\Site\SiteSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /* Una sola lectura de la configuración por petición */
        $this->app->singleton(SiteSettings::class);
        $this->app->singleton(ContentFlags::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Un admin puede revisar y configurar todo, sea de quien sea. Las
         * políticas siguen protegiendo a los demás: esto solo abre la puerta
         * a quien tiene el rol.
         */
        Gate::before(fn ($user) => $user->isAdmin() ? true : null);

        /* El nombre, el icono y el anuncio, en todas las pantallas */
        View::composer('*', function ($view) {
            $view->with('sitio', app(SiteSettings::class));
        });
    }
}

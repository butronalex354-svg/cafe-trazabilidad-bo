<?php

namespace App\Providers;

use App\Models\ControlCalidad;
use App\Models\Cosecha;
use App\Models\EtapaProcesamiento;
use App\Models\Inventario;
use App\Models\Parcela;
use App\Models\Verificacion;
use App\Observers\AuditoriaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // RF27: auditoria automatica sobre las tablas principales del sistema.
        Parcela::observe(AuditoriaObserver::class);
        Cosecha::observe(AuditoriaObserver::class);
        Verificacion::observe(AuditoriaObserver::class);
        Inventario::observe(AuditoriaObserver::class);
        ControlCalidad::observe(AuditoriaObserver::class);
        EtapaProcesamiento::observe(AuditoriaObserver::class);
    }
}

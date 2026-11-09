<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo diario de la base a las 2:00 (app/Console/Commands/RespaldarBaseDatos.php).
// Funciona mientras corra "php artisan schedule:work" (local) o el cron del servidor.
Schedule::command('respaldo:bd')->dailyAt('02:00');

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Guarda una copia completa de la base de datos (mysqldump) en
 * storage/app/respaldos y borra las copias mas viejas, dejando solo las
 * ultimas N. Se programa a diario en routes/console.php.
 */
class RespaldarBaseDatos extends Command
{
    protected $signature = 'respaldo:bd {--conservar= : Cuántos respaldos guardar (por defecto RESPALDO_CONSERVAR o 14)}';

    protected $description = 'Guarda una copia de la base de datos en storage/app/respaldos';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));

        if (! in_array($db['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            $this->error('El respaldo solo funciona con MySQL/MariaDB.');

            return self::FAILURE;
        }

        $carpeta = storage_path('app/respaldos');
        File::ensureDirectoryExists($carpeta);
        $archivo = $carpeta.DIRECTORY_SEPARATOR.$db['database'].'_'.now()->format('Y-m-d_His').'.sql';

        // La contrasena va por variable de entorno, no en la linea de comandos,
        // para que no quede visible en la lista de procesos.
        $resultado = Process::env(['MYSQL_PWD' => (string) $db['password']])
            ->timeout(600)
            ->run([
                config('database.respaldo.mysqldump'),
                '--host='.$db['host'],
                '--port='.$db['port'],
                '--user='.$db['username'],
                '--single-transaction',
                '--routines',
                '--default-character-set=utf8mb4',
                '--result-file='.$archivo,
                $db['database'],
            ]);

        if ($resultado->failed() || ! File::exists($archivo) || File::size($archivo) === 0) {
            File::delete($archivo);
            $this->error('No se pudo hacer el respaldo: '.trim($resultado->errorOutput() ?: $resultado->output()));

            return self::FAILURE;
        }

        $conservar = max(1, (int) ($this->option('conservar') ?: config('database.respaldo.conservar')));
        $respaldos = collect(File::glob($carpeta.DIRECTORY_SEPARATOR.$db['database'].'_*.sql'))->sort()->values();
        File::delete($respaldos->slice(0, max(0, $respaldos->count() - $conservar))->all());

        $this->info('Respaldo guardado: '.$archivo.' ('.round(File::size($archivo) / 1024).' KB)');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CheckProductionReadiness extends Command
{
    protected $signature = 'production:check';

    protected $description = 'Verifica requisitos críticos antes o después del despliegue en producción';

    public function handle(): int
    {
        $checks = [
            ['APP_ENV es production', app()->environment('production')],
            ['APP_DEBUG está desactivado', ! config('app.debug')],
            ['APP_URL utiliza HTTPS', str_starts_with((string) config('app.url'), 'https://')],
            ['APP_KEY está configurada', filled(config('app.key'))],
            ['Cookies de sesión seguras', (bool) config('session.secure')],
            ['Sesiones cifradas', (bool) config('session.encrypt')],
            ['Almacenamiento privado disponible', is_writable(storage_path('app/private'))],
            ['Caché de Laravel escribible', is_writable(base_path('bootstrap/cache'))],
        ];

        try {
            DB::selectOne('SELECT 1 AS ok');
            $checks[] = ['Conexión MySQL disponible', true];
            $checks[] = ['MySQL 8 o superior', version_compare((string) DB::selectOne('SELECT VERSION() AS version')->version, '8.0', '>=')];
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());
            $checks[] = ['Sin migraciones pendientes', $pending === []];
        } catch (Throwable $exception) {
            $checks[] = ['Base de datos y migraciones', false];
            $this->error($exception->getMessage());
        }

        try {
            Storage::disk('local')->put('.production-check', now()->toIso8601String());
            Storage::disk('local')->delete('.production-check');
            $checks[] = ['Escritura real en almacenamiento privado', true];
        } catch (Throwable) {
            $checks[] = ['Escritura real en almacenamiento privado', false];
        }

        foreach ($checks as [$label, $passed]) {
            $passed ? $this->components->info($label) : $this->components->error($label);
        }

        return collect($checks)->every(fn ($check) => $check[1]) ? self::SUCCESS : self::FAILURE;
    }
}

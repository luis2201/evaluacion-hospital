<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstrumentoMecSimV1Seeder extends Seeder
{
    /** @var list<string> */
    private const TABLES = ['modelos_evaluacion', 'dominios', 'criterios', 'descriptores', 'categorias_resultado'];

    public function run(): void
    {
        $sql = file_get_contents(database_path('schema/evaluacion_hsimulacion_mysql_v2.sql'));

        if ($sql === false) {
            throw new RuntimeException('No se pudo leer el esquema oficial MEC-SIM v1.');
        }

        DB::transaction(function () use ($sql): void {
            foreach (self::TABLES as $table) {
                DB::unprepared($this->extractInsert($sql, $table));
            }
        });

        $this->assertOfficialCounts();
    }

    private function extractInsert(string $sql, string $table): string
    {
        $start = strpos($sql, "INSERT INTO {$table}");

        if ($start === false) {
            throw new RuntimeException("No se encontró la semilla de {$table}.");
        }

        $inString = false;
        $length = strlen($sql);

        for ($position = $start; $position < $length; $position++) {
            if ($sql[$position] === "'") {
                if ($inString && ($sql[$position + 1] ?? null) === "'") {
                    $position++;

                    continue;
                }

                $inString = ! $inString;
            }

            if ($sql[$position] === ';' && ! $inString) {
                return substr($sql, $start, $position - $start + 1);
            }
        }

        throw new RuntimeException("La sentencia INSERT de {$table} está incompleta.");
    }

    private function assertOfficialCounts(): void
    {
        foreach (['dominios' => 5, 'criterios' => 17, 'descriptores' => 44, 'categorias_resultado' => 4] as $table => $expected) {
            $actual = DB::table($table)->count();

            if ($actual !== $expected) {
                throw new RuntimeException("La semilla oficial esperaba {$expected} registros en {$table}; se encontraron {$actual}.");
            }
        }

        if ((float) DB::table('dominios')->sum('peso') !== 100.0) {
            throw new RuntimeException('Los pesos de los dominios no suman 100 %.');
        }
    }
}

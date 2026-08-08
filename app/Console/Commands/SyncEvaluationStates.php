<?php

namespace App\Console\Commands;

use App\Services\EvaluationCalendarService;
use Illuminate\Console\Command;

class SyncEvaluationStates extends Command
{
    protected $signature = 'evaluations:sync-states';

    protected $description = 'Sincroniza las fases de las evaluaciones con sus fechas configuradas';

    public function handle(EvaluationCalendarService $calendar): int
    {
        $updated = $calendar->syncAll();
        $this->info("Evaluaciones actualizadas: {$updated}");

        return self::SUCCESS;
    }
}

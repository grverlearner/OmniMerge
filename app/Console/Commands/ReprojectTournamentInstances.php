<?php

namespace App\Console\Commands;

use App\Models\TournamentInstance;
use App\Services\Tournaments\Runtime\TournamentInstanceProjector;
use Illuminate\Console\Command;
use Throwable;

/*
|--------------------------------------------------------------------------
| tournaments:reproject
|--------------------------------------------------------------------------
|
| Vuelve a proyectar las competiciones desde su estado guardado.
|
| Hace falta porque una competición terminada no ejecutará ninguna acción
| más y, por tanto, nunca rellenaría por sí sola los campos añadidos en
| fases posteriores (desenlaces, clasificación por fase, entity_id
| desnormalizados...).
|
| Es seguro: el proyector es idempotente y el estado JSON —que es la
| fuente de verdad— no se toca en ningún momento.
|
*/

class ReprojectTournamentInstances extends Command
{
    protected $signature = 'tournaments:reproject
                            {--instance= : Reproyectar solo esta competición}';

    protected $description = 'Reproyecta el historial de las competiciones desde su estado guardado';

    public function handle(
        TournamentInstanceProjector $projector
    ): int {

        $query =
            TournamentInstance::query()
            ->with('state')
            ->orderBy('id');

        if ($this->option('instance')) {

            $query->whereKey(
                (int) $this->option('instance')
            );
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No hay competiciones que reproyectar.');

            return self::SUCCESS;
        }

        $this->info("Reproyectando {$total} competiciones...");

        $done = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunkById(
            25,

            function ($instances) use (
                $projector,
                &$done,
                &$skipped,
                &$failed
            ) {

                foreach ($instances as $instance) {

                    $state =
                        $instance->state?->state;

                    if (! is_array($state)) {

                        $this->warn(
                            "  {$instance->code}: sin estado guardado, se omite."
                        );

                        $skipped++;

                        continue;
                    }

                    try {

                        $projector->project(
                            $instance,
                            $state
                        );

                        $this->line(
                            "  {$instance->code} · {$instance->name}"
                        );

                        $done++;
                    } catch (Throwable $exception) {

                        $this->error(
                            "  {$instance->code}: {$exception->getMessage()}"
                        );

                        $failed++;
                    }
                }
            }
        );

        $this->newLine();

        $this->info(
            "Reproyectadas: {$done} · Omitidas: {$skipped} · Con error: {$failed}"
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}

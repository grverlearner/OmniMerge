<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\TournamentInstanceSnapshot;
use App\Models\TournamentInstanceState;
use App\Models\Universe;
use App\Models\UniverseCompetitor;
use App\Models\UniverseTournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| TournamentInstanceService
|--------------------------------------------------------------------------
|
| Ciclo de vida de una competición real: crearla (congelando la
| configuración), iniciarla, pausarla, reanudarla, cancelarla, borrarla.
|
| La ejecución propiamente dicha vive en TournamentInstanceRuntimeService.
|
*/

class TournamentInstanceService
{
    public function __construct(
        private readonly
        TournamentSnapshotBuilder $snapshotBuilder,

        private readonly
        TournamentSnapshotHydrator $hydrator,

        private readonly
        TournamentInstanceStateFactory $stateFactory,

        private readonly
        TournamentInstanceProjector $projector
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    |
    | Aquí se congela la configuración. A partir de este momento, editar
    | la TournamentTemplate, sus fases o sus reglas NO afecta a esta
    | competición.
    |
    | $assignments = [ startId => [universeCompetitorId, ...] ]
    |
    */

    public function create(
        Universe $universe,
        UniverseTournament $universeTournament,
        array $data,
        array $assignments
    ): TournamentInstance {

        $template =
            $universeTournament->tournamentTemplate;

        if (! $template) {

            throw ValidationException::withMessages([
                'universe_tournament_id' => [
                    'Este torneo ya no tiene una plantilla disponible.',
                ],
            ]);
        }

        $competitorIds =
            collect($assignments)
            ->flatten()
            ->map(
                fn($id) => (int) $id
            )
            ->unique()
            ->values();

        if ($competitorIds->isEmpty()) {

            throw ValidationException::withMessages([
                'assignments' => [
                    'Selecciona al menos un competidor para la competición.',
                ],
            ]);
        }

        $competitors =
            UniverseCompetitor::query()
            ->where(
                'universe_id',
                $universe->id
            )
            ->whereIn(
                'id',
                $competitorIds
            )
            ->with('entity')
            ->get()
            ->keyBy('id');

        if ($competitors->count() !== $competitorIds->count()) {

            throw ValidationException::withMessages([
                'assignments' => [
                    'Alguno de los competidores seleccionados ya no pertenece a este Universo.',
                ],
            ]);
        }

        /*
         * El snapshot se toma AHORA, no al iniciar: la asignación de
         * participantes ya depende de los starts del grafo, así que la
         * configuración debe estar fija desde este momento para que
         * ambas cosas sean coherentes.
         */
        $snapshot =
            $this->snapshotBuilder
            ->build($template);

        /*
         * Se ejecuta sobre la plantilla hidratada, nunca sobre la viva:
         * así el estado inicial se construye con exactamente la misma
         * configuración que ejecutará el motor después.
         */
        $frozenTemplate =
            $this->hydrator
            ->hydrate($snapshot);

        return DB::transaction(
            function () use (
                $universe,
                $universeTournament,
                $data,
                $assignments,
                $competitors,
                $snapshot,
                $frozenTemplate,
                $template
            ) {

                $sequence =
                    $this->nextSequence(
                        $universe->id
                    );

                $instance =
                    TournamentInstance::query()
                    ->create([

                        'universe_id' =>
                        $universe->id,

                        'universe_tournament_id' =>
                        $universeTournament->id,

                        'universe_season_id' =>
                        $data['universe_season_id']
                            ?? null,

                        'tournament_template_id' =>
                        $template->id,

                        'sequence_number' =>
                        $sequence,

                        'code' =>
                        TournamentInstance::formatCode(
                            $sequence
                        ),

                        'name' =>
                        $data['name'],

                        'status' =>
                        'DRAFT',

                        'runtime_status' =>
                        'READY',
                    ]);

                TournamentInstanceSnapshot::query()
                    ->create([

                        'tournament_instance_id' =>
                        $instance->id,

                        'schema_version' =>
                        TournamentSnapshotBuilder::SCHEMA_VERSION,

                        'hash' =>
                        $this->snapshotBuilder
                            ->hash($snapshot),

                        'snapshot' =>
                        $snapshot,
                    ]);

                $state =
                    $this->stateFactory
                    ->create(
                        $frozenTemplate,
                        (int) $universe->user_id,
                        $assignments,
                        $competitors
                    );

                TournamentInstanceState::query()
                    ->create([

                        'tournament_instance_id' =>
                        $instance->id,

                        'schema_version' =>
                        1,

                        'revision' =>
                        0,

                        'state' =>
                        $state,
                    ]);

                $this->freezeParticipants(
                    $instance,
                    $state,
                    $competitors
                );

                $instance->update([

                    'participant_count' =>
                    count(
                        $state['participants'] ?? []
                    ),
                ]);

                $this->projector
                    ->project(
                        $instance->fresh(),
                        $state
                    );

                return $instance->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Congelar participantes
    |--------------------------------------------------------------------------
    |
    | El nombre y el seed se copian ahora. Si mañana el competidor cambia
    | de alias o se retira del Universo, esta competición conserva lo que
    | tenía cuando se jugó.
    |
    */

    private function freezeParticipants(
        TournamentInstance $instance,
        array $state,
        $competitors
    ): void {

        $rows = [];

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            $competitorId =
                $participant['universe_competitor_id']
                ?? null;

            $competitor =
                $competitorId
                ? $competitors->get((int) $competitorId)
                : null;

            $rows[] = [

                'tournament_instance_id' =>
                $instance->id,

                'runtime_key' =>
                (string) $key,

                'universe_competitor_id' =>
                $competitorId,

                'entity_id' =>
                $competitor?->entity_id,

                'name' =>
                mb_substr(
                    (string) ($participant['name'] ?? 'Competidor'),
                    0,
                    150
                ),

                'seed' =>
                (int) ($participant['seed'] ?? 0),

                'source_start_id' =>
                $participant['source_start_id'] ?? null,

                'status' =>
                $participant['status'] ?? 'WAITING',

                'matches' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'points' => 0,

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        TournamentInstanceParticipant::query()
            ->insert($rows);
    }

    /*
    |--------------------------------------------------------------------------
    | Transiciones de estado
    |--------------------------------------------------------------------------
    */

    public function pause(
        TournamentInstance $instance
    ): void {

        $this->assertOpen($instance);

        if (! $instance->isRunning()) {

            throw ValidationException::withMessages([
                'instance' => [
                    'Solo se puede pausar una competición en curso.',
                ],
            ]);
        }

        $instance->update([
            'status' => 'PAUSED',
        ]);
    }

    public function resume(
        TournamentInstance $instance
    ): void {

        $this->assertOpen($instance);

        if ($instance->status !== 'PAUSED') {

            throw ValidationException::withMessages([
                'instance' => [
                    'Solo se puede reanudar una competición pausada.',
                ],
            ]);
        }

        $instance->update([
            'status' => 'RUNNING',
        ]);
    }

    public function cancel(
        TournamentInstance $instance
    ): void {

        $this->assertOpen($instance);

        $instance->update([
            'status' => 'CANCELLED',
        ]);
    }

    /*
     * Solo una competición que nunca arrancó puede borrarse: una vez
     * jugada es historia del Universo.
     */
    public function delete(
        TournamentInstance $instance
    ): void {

        if (! $instance->isDraft()) {

            throw ValidationException::withMessages([
                'instance' => [
                    'Solo se puede eliminar una competición que todavía no ha comenzado. '
                        . 'Si ya no la quieres en curso, cancélala.',
                ],
            ]);
        }

        $instance->delete();
    }

    private function assertOpen(
        TournamentInstance $instance
    ): void {

        if ($instance->isClosed()) {

            throw ValidationException::withMessages([
                'instance' => [
                    'Esta competición ya está cerrada.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Secuencia
    |--------------------------------------------------------------------------
    */

    private function nextSequence(
        int $universeId
    ): int {

        return (
            (int)
            TournamentInstance::withTrashed()
                ->where(
                    'universe_id',
                    $universeId
                )
                ->max('sequence_number')
        )
            + 1;
    }
}

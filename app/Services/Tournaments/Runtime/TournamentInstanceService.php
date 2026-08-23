<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\TournamentInstanceSnapshot;
use App\Models\TournamentInstanceState;
use App\Models\Universe;
use App\Models\UniverseEntity;
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
        TournamentInstanceProjector $projector,

        /* Fase 11 */
        private readonly
        \App\Services\Games\GameRegistry $gameRegistry,

        private readonly
        \App\Services\Games\UniverseGameService $universeGames
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

        $universeEntityIds =
            collect($assignments)
            ->flatten()
            ->map(
                fn($id) => (int) $id
            )
            ->unique()
            ->values();

        if ($universeEntityIds->isEmpty()) {

            throw ValidationException::withMessages([
                'assignments' => [
                    'Selecciona al menos un competidor para la competición.',
                ],
            ]);
        }

        $universeEntities =
            UniverseEntity::query()
            ->where(
                'universe_id',
                $universe->id
            )
            ->whereIn(
                'id',
                $universeEntityIds
            )
            /*
             * Lo que necesita TournamentParticipantResolver para
             * resolver versión y atributos sin caer en N+1.
             */
            /*
             * Ya no hace falta cargar nada de Biblioteca: la entidad del
             * Universo lleva su propia copia.
             */
            ->get()
            ->keyBy('id');

        if ($universeEntities->count() !== $universeEntityIds->count()) {

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

        /*
         * Juego de la competicion: el del torneo, y si no eligio ninguno
         * el que el Universo tenga por defecto.
         */
        $gameKey =
            $this->gameRegistry->has($universeTournament->game_key)
            ? strtoupper((string) $universeTournament->game_key)
            : $this->universeGames->defaultKey($universe);

        /*
         * Modificadores temporales del torneo (Fase 12). Se congelan
         * ahora: cambiarlos despues no altera una competicion en curso,
         * exactamente igual que con la plantilla y las stats.
         */
        $modifiers =
            $universeTournament
            ->modifiers()
            ->where('is_active', true)
            ->get()
            ->map(
                fn($modifier) => [
                    'rule_id' => (string) $modifier->id,
                    'scope' => $modifier->scope,
                    'scope_value' => $modifier->scope_value,

                    /* Solo los usa un bonus que hay que ganarse jugando */
                    'award_phase' => $modifier->award_phase,
                    'selector_type' => $modifier->selector_type,
                    'selector_from' => $modifier->selector_from,
                    'selector_to' => $modifier->selector_to,

                    'target' => $modifier->target,
                    'universe_entity_id' => $modifier->universe_entity_id,
                    'game_key' => $modifier->game_key,
                    'stat_key' => $modifier->stat_key,
                    'operation' => $modifier->operation,
                    'amount' => (float) $modifier->amount,
                    'label' => $modifier->label,
                ]
            )
            ->all();

        return DB::transaction(
            function () use (
                $universe,
                $universeTournament,
                $gameKey,
                $modifiers,
                $data,
                $assignments,
                $universeEntities,
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

                        /*
                         * Juego congelado (Fase 11). Se copia del torneo
                         * del Universo al arrancar, igual que el snapshot
                         * de la plantilla: cambiarlo despues no debe
                         * alterar una competicion ya en curso.
                         */
                        'game_key' =>
                        $gameKey,
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
                        $universeEntities,
                        $gameKey,
                        $modifiers
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
                    $universeEntities
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
        $universeEntities
    ): void {

        $rows = [];

        foreach (
            ($state['participants'] ?? [])
            as
            $key => $participant
        ) {

            $universeEntityId =
                $participant['universe_entity_id']
                ?? null;

            $universeEntity =
                $universeEntityId
                ? $universeEntities->get((int) $universeEntityId)
                : null;

            $rows[] = [

                'tournament_instance_id' =>
                $instance->id,

                'runtime_key' =>
                (string) $key,

                'universe_entity_id' =>
                $universeEntityId,

                /*
                 * Contexto de Biblioteca congelado (Fase 7). Los nombres
                 * se copian, no se leen por join: renombrar la Entidad o
                 * su versión no debe alterar un torneo ya jugado.
                 */
                'source_entity_id' =>
                $participant['source_entity_id'] ?? null,

                'entity_version_id' =>
                $participant['entity_version_id'] ?? null,

                'entity_version_name' =>
                $participant['entity_version_name'] ?? null,

                'entity_type_name' =>
                $participant['entity_type_name'] ?? null,

                'attribute_snapshot' =>
                json_encode(
                    $participant['attributes'] ?? [],
                    JSON_UNESCAPED_UNICODE
                ),

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

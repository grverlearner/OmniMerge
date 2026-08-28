<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceState;
use App\Services\Tournaments\CompetitionLab\CompetitionLabService;
use App\Services\Universes\UniverseActivityRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| TournamentInstanceRuntimeService
|--------------------------------------------------------------------------
|
| La capa de persistencia y orquestación del motor existente.
|
| No decide nada sobre competición: eso lo hace CompetitionLabService,
| que es el mismo motor que usa el Competition Lab. Aquí solo se hace lo
| que el Lab resolvía con un token en sessionStorage:
|
|   1. cargar el estado desde la base de datos
|   2. reconstruir la configuración congelada
|   3. delegar la acción en el motor
|   4. guardar el estado nuevo y proyectarlo a las tablas consultables
|
| Por eso una competición sobrevive a cerrar el navegador, cerrar sesión
| o reiniciar el servidor: no hay estado en ninguna sesión.
|
*/

class TournamentInstanceRuntimeService
{
    /*
     * Acciones que arrancan el recorrido del Tournament Graph.
     */
    private const START_ACTIONS = [
        'START_TOURNAMENT',
    ];

    public function __construct(
        private readonly
        CompetitionLabService $engine,

        private readonly
        TournamentSnapshotHydrator $hydrator,

        private readonly
        TournamentInstanceProjector $projector,

        private readonly
        UniverseActivityRecorder $activity,

        /* Fase 11 */
        private readonly
        \App\Services\Games\GameEncounterRecorder $encounterRecorder,

        /* Fase 12 */
        private readonly
        \App\Services\Rewards\RewardProcessor $rewards,

        private readonly
        \App\Services\Rewards\PhaseBonusGranter $phaseBonuses
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Estado actual
    |--------------------------------------------------------------------------
    */

    public function payload(
        TournamentInstance $instance
    ): array {

        $state =
            $instance->state;

        return [

            'state' =>
            $state?->state ?? null,

            'revision' =>
            (int) ($state?->revision ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ejecutar una acción
    |--------------------------------------------------------------------------
    |
    | Toda la operación va en una transacción con bloqueo de la fila de
    | estado: dos pestañas abiertas no pueden pisarse los resultados.
    |
    */

    public function act(
        TournamentInstance $instance,
        string $action,
        array $payload = [],
        ?int $expectedRevision = null
    ): array {

        if ($instance->isClosed()) {

            $this->fail(
                'Esta competición está '
                    . mb_strtolower($instance->status_label)
                    . ' y ya no admite acciones.'
            );
        }

        if (
            $instance->status === 'PAUSED'
        ) {
            $this->fail(
                'La competición está pausada. Reanúdala para continuar.'
            );
        }

        if (
            $instance->isDraft()
            &&
            ! in_array(
                $action,
                self::START_ACTIONS,
                true
            )
        ) {
            $this->fail(
                'La competición todavía no ha comenzado. Inicia el recorrido primero.'
            );
        }

        return DB::transaction(
            function () use (
                $instance,
                $action,
                $payload,
                $expectedRevision
            ) {

                /** @var TournamentInstanceState $stateRow */
                $stateRow =
                    TournamentInstanceState::query()
                    ->where(
                        'tournament_instance_id',
                        $instance->id
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $stateRow) {
                    $this->fail(
                        'Esta competición no tiene estado guardado.'
                    );
                }

                /*
                 * Bloqueo optimista. Si el cliente trae una revisión
                 * antigua es que otra pestaña ya avanzó la competición;
                 * se rechaza en vez de sobrescribir resultados.
                 */
                if (
                    $expectedRevision !== null
                    &&
                    $expectedRevision !== (int) $stateRow->revision
                ) {
                    $this->fail(
                        'Esta competición avanzó desde otra ventana. '
                            . 'Recarga la página para ver el estado actual.'
                    );
                }

                /*
                 * La competicion se pasa a proposito: su formato de batalla
                 * -al mejor de N, o N juegos fijos- pisa el de las
                 * plantillas al hidratar. Ver CompetitionBattleFormat.
                 */
                $template =
                    $this->hydrator
                    ->hydrate(
                        $instance->snapshot?->snapshot
                            ?? [],
                        $instance
                    );

                /*
                 * El motor. Exactamente el mismo que usa el Lab.
                 */
                $state =
                    $this->engine
                    ->applyAction(
                        $stateRow->state ?? [],
                        $template,
                        $action,
                        $payload
                    );

                /*
                 * Enfrentamientos resueltos por el Game Engine (Fase 11).
                 * El motor es puro y solo los acumulo en el estado; aqui,
                 * que si hay base de datos, se guardan y se sacan del
                 * estado para no arrastrarlos indefinidamente.
                 */
                $state =
                    $this->encounterRecorder
                    ->drain(
                        $instance,
                        $state
                    );

                /*
                 * Bonos que se ganan jugando (Fase 12).
                 *
                 * Va aqui, entre el motor y el guardado, porque una fase
                 * puede haber terminado en ESTA misma accion: el podio se
                 * concede en el mismo movimiento que lo decide, y cuando
                 * el jugador abre la fase siguiente el bonus ya esta
                 * puesto. Es idempotente, asi que llamarlo en cada accion
                 * no concede nada dos veces.
                 */
                $state =
                    $this->phaseBonuses
                    ->grant($state);

                $state['updated_at'] =
                    now()->toIso8601String();

                $stateRow->update([

                    'state' =>
                    $state,

                    'revision' =>
                    (int) $stateRow->revision + 1,
                ]);

                $this->syncInstance(
                    $instance,
                    $state,
                    $action
                );

                $this->projector
                    ->project(
                        $instance,
                        $state
                    );

                /*
                 * Consecuencias permanentes (Fase 12).
                 *
                 * Va DESPUES de proyectar porque las recompensas por
                 * posicion necesitan los desenlaces ya escritos, y es
                 * idempotente: se puede llamar en cada accion sin que
                 * aplique nada dos veces.
                 */
                $this->rewards
                    ->process(
                        $instance->refresh()
                    );

                return [

                    'state' =>
                    $state,

                    'revision' =>
                    (int) $stateRow->revision,

                    'instance' => [

                        'status' =>
                        $instance->status,

                        'status_label' =>
                        $instance->status_label,

                        'runtime_status' =>
                        $instance->runtime_status,
                    ],
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reflejar el motor en la fila de la competición
    |--------------------------------------------------------------------------
    |
    | status         → ciclo de vida visible para el usuario
    | runtime_status → detalle del motor (BLOCKED, AWAITING_DECISION...)
    |
    */

    private function syncInstance(
        TournamentInstance $instance,
        array $state,
        string $action
    ): void {

        $graphStatus =
            $state['graph_runtime']['status']
            ?? null;

        $changes = [

            'runtime_status' =>
            $graphStatus
                ?? ($state['status'] ?? null),
        ];

        if (
            in_array(
                $action,
                self::START_ACTIONS,
                true
            )
            &&
            $instance->isDraft()
        ) {

            $changes['status'] =
                'RUNNING';

            $changes['started_at'] =
                now();

            $justStarted = true;
        }

        if (
            $graphStatus === 'COMPLETED'
            &&
            $instance->status !== 'COMPLETED'
        ) {

            $changes['status'] =
                'COMPLETED';

            $changes['completed_at'] =
                $instance->completed_at
                ?? now();

            $justCompleted = true;
        }

        $instance->update(
            $changes
        );

        /*
         * Cronica del Universo (Fase 10). Se registra DESPUES de
         * guardar y solo en la transicion, para que reanudar o
         * reproyectar no duplique entradas.
         */
        if (isset($justStarted)) {
            $this->activity->competitionStarted($instance);
        }

        if (isset($justCompleted)) {

            $this->activity->competitionCompleted(
                $instance,

                $instance
                    ->participants()
                    ->where('outcome', 'CHAMPION')
                    ->orderByDesc('points')
                    ->first()
            );
        }
    }

    private function fail(
        string $message
    ): never {

        throw ValidationException::withMessages([
            'instance' => [
                $message,
            ],
        ]);
    }
}

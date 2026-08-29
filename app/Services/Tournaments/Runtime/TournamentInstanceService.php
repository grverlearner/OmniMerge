<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentTemplate;
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

        /*
         * Que forma tiene ESTA edicion.
         *
         * Un torneo es una marca -"la Copa"- y su plantilla es la forma con
         * la que suele jugarse, no una condena. Las temporadas cambian: la
         * cuarta edicion puede necesitar una fase previa que la primera no
         * tenia, porque ahora se apunta el triple de gente.
         *
         * Por eso la competicion puede elegir otra plantilla. Si no elige,
         * se juega con la del torneo, que es lo habitual.
         */
        $template =
            $this->resolveTemplate($universeTournament, $data);

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
        /*
         * El formato de batalla que se acaba de elegir, ya en juego.
         *
         * La competicion todavia no existe como fila -se crea mas abajo-
         * asi que viaja en memoria: lo unico que hace falta aqui es que el
         * estado inicial se construya con el mismo formato con el que
         * despues se jugara.
         */
        $chosenFormat = new TournamentInstance([
            'series_format' => $data['series_format'] ?? 'BEST_OF',
            'best_of' => (int) ($data['best_of'] ?? 1),
            'fixed_games' => (int) ($data['fixed_games'] ?? 1),
        ]);

        $frozenTemplate =
            $this->hydrator
            ->hydrate($snapshot, $chosenFormat);

        /*
         * Juego de la competicion: el del torneo, y si no eligio ninguno
         * el que el Universo tenga por defecto.
         */
        /*
         * El de la EDICION cuando el torneo deja elegir.
         *
         * Un torneo de juego unico impone el suyo: cambiarlo por edicion
         * haria que "la Copa" dejase de significar lo mismo cada ano. Uno
         * de juego variado existe justamente para lo contrario.
         */
        $chosenGame = strtoupper((string) ($data['game_key'] ?? ''));

        $gameKey = match (true) {

            ($universeTournament->game_mode ?: 'SINGLE') === 'VARIED'
                && $this->gameRegistry->has($chosenGame)
                => $chosenGame,

            $this->gameRegistry->has($universeTournament->game_key)
                => strtoupper((string) $universeTournament->game_key),

            default => $this->universeGames->defaultKey($universe),
        };

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

                        /*
                         * La plantilla con la que se juega ESTA edicion,
                         * que puede no ser la del torneo. Ver
                         * resolveTemplate().
                         */
                        'tournament_template_id' =>
                        $template->id,

                        /*
                         * El formato de batalla, congelado igual que el
                         * resto: cambiarlo despues no altera una
                         * competicion ya en curso.
                         */
                        'series_format' =>
                        $data['series_format'] ?? 'BEST_OF',

                        'best_of' =>
                        max(1, (int) ($data['best_of'] ?? 1)),

                        'fixed_games' =>
                        max(1, (int) ($data['fixed_games'] ?? 1)),

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
                        $modifiers,

                        /*
                         * Las reglas de participacion del torneo. Con ellas
                         * cada competidor sale con la version que encaja:
                         * un torneo de Shippuden ensena caras de Shippuden.
                         */
                        $universeTournament->eligibility
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

    /*
    |--------------------------------------------------------------------------
    | Rehacer el reparto de una edicion que todavia no empezo
    |--------------------------------------------------------------------------
    |
    | Al crear una edicion se dibuja su cuadro con la gente que entra, y por
    | eso cambiar competidores DESPUES es peligroso: dejaria enfrentamientos
    | apuntando a quien ya no esta.
    |
    | Pero eso solo vale una vez que se ha jugado algo. Una edicion en
    | borrador que nadie ha empezado no tiene nada que estropear: su cuadro
    | es un dibujo en limpio, y volver a dibujarlo con otra gente es
    | exactamente lo que hace falta cuando se te olvido meter a alguien.
    |
    | Se rehace desde el MISMO snapshot -la forma no cambia- y se reconstruye
    | el estado inicial entero. Lo que se borra son los participantes viejos:
    | dejarlos convertiria "quito a uno" en "ahora hay uno de sobra".
    */
    public function reassign(
        TournamentInstance $instance,
        array $assignments
    ): TournamentInstance {

        if (! $this->canReassign($instance)) {

            throw ValidationException::withMessages([
                'assignments' => [
                    'Esta edición ya empezó a jugarse: sus competidores no se '
                        . 'pueden cambiar sin invalidar lo ya jugado.',
                ],
            ]);
        }

        $universe = $instance->universe;

        $universeEntityIds = collect($assignments)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($universeEntityIds->isEmpty()) {

            throw ValidationException::withMessages([
                'assignments' => [
                    'Selecciona al menos un competidor para la competición.',
                ],
            ]);
        }

        $universeEntities = UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->whereIn('id', $universeEntityIds)
            ->get()
            ->keyBy('id');

        if ($universeEntities->count() !== $universeEntityIds->count()) {

            throw ValidationException::withMessages([
                'assignments' => [
                    'Alguno de los competidores seleccionados ya no pertenece a este Universo.',
                ],
            ]);
        }

        $snapshot = $instance->snapshot?->snapshot ?? [];

        if ($snapshot === []) {

            throw ValidationException::withMessages([
                'assignments' => [
                    'Esta edición no tiene su configuración congelada: no se puede rehacer.',
                ],
            ]);
        }

        /*
         * Se hidrata con la propia competicion para que el estado nuevo
         * nazca con el mismo formato de batalla con el que se jugara.
         */
        $frozenTemplate = $this->hydrator->hydrate($snapshot, $instance);

        $modifiers = $instance->universeTournament
            ?->modifiers()
            ->where('is_active', true)
            ->get()
            ->map(fn ($modifier) => [
                'rule_id' => (string) $modifier->id,
                'scope' => $modifier->scope,
                'scope_value' => $modifier->scope_value,
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
            ])
            ->all() ?? [];

        return DB::transaction(function () use (
            $instance,
            $universe,
            $assignments,
            $universeEntities,
            $frozenTemplate,
            $modifiers
        ) {

            $state = $this->stateFactory->create(
                $frozenTemplate,
                (int) $universe->user_id,
                $assignments,
                $universeEntities,
                $instance->game_key,
                $modifiers,
                $instance->universeTournament?->eligibility
            );

            TournamentInstanceState::query()
                ->where('tournament_instance_id', $instance->id)
                ->update([
                    'state' => $state,

                    /*
                     * La revision sube: si otra pestana tenia abierta la
                     * edicion, su siguiente accion se rechaza en vez de
                     * jugar sobre un cuadro que ya no existe.
                     */
                    'revision' => DB::raw('revision + 1'),
                ]);

            /*
             * Fuera los viejos. Sin esto, quitar a un competidor lo dejaria
             * en la lista para siempre.
             */
            TournamentInstanceParticipant::query()
                ->where('tournament_instance_id', $instance->id)
                ->delete();

            $this->freezeParticipants($instance, $state, $universeEntities);

            $instance->update([
                'participant_count' => count($state['participants'] ?? []),
            ]);

            $this->projector->project($instance->fresh(), $state);

            return $instance->fresh();
        });
    }

    /*
     * Si todavia se puede rehacer el reparto.
     *
     * Borrador y sin nada jugado. runtime_status READY significa que el
     * grafo ni siquiera arranco; en cuanto arranca hay rondas dibujadas y
     * cambiar la gente dejaria huecos.
     */
    public function canReassign(TournamentInstance $instance): bool
    {
        return $instance->status === 'DRAFT'
            && in_array($instance->runtime_status, ['READY', 'NOT_STARTED'], true);
    }

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
    /*
     * La plantilla con la que se juega esta competicion.
     *
     * La elegida, si es del mismo dueno que el torneo y esta activa; y si no
     * se eligio ninguna, la del torneo.
     *
     * La comprobacion de dueno no es paranoia: el id viaja en un formulario,
     * y sin ella cualquiera podria jugar una competicion con la plantilla de
     * otra persona.
     */
    private function resolveTemplate(
        UniverseTournament $universeTournament,
        array $data
    ): ?TournamentTemplate {

        $chosen = (int) ($data['tournament_template_id'] ?? 0);

        $default = $universeTournament->tournamentTemplate;

        if ($chosen === 0 || ($default && $chosen === (int) $default->id)) {
            return $default;
        }

        $template = TournamentTemplate::query()
            ->when(
                $default,
                fn ($query) => $query->where('user_id', $default->user_id)
            )
            ->where('status', 'ACTIVE')
            ->find($chosen);

        if (! $template) {

            throw ValidationException::withMessages([
                'tournament_template_id' => [
                    'Esa plantilla de torneo no esta disponible.',
                ],
            ]);
        }

        return $template;
    }

}

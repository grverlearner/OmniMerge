<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationValidator;
use App\Services\Tournaments\SingleElimination\SingleEliminationConfigurationInspector;
use App\Services\Tournaments\CompetitionLab\Runtime\PlacementPlanner;
use App\Services\Tournaments\SingleElimination\SingleEliminationSettingsService;
use Illuminate\Validation\ValidationException;

class SingleEliminationLabEngine
implements LabPhaseEngine
{
    public function __construct(
        private readonly
        SingleEliminationValidator $validator,

        private readonly
        SingleEliminationConfigurationInspector $inspector,

        private readonly
        SingleEliminationGraphRuntime $graphRuntime,

        private readonly
        SingleEliminationSettingsService $settingsService,

        private readonly
        PlacementPlanner $placement
    ) {}

    public function supports(
        string $phaseType
    ): bool {
        return
            $phaseType
            ===
            'SINGLE_ELIMINATION';
    }

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array {
        $phase->loadMissing([
            'singleEliminationSetting',
            'singleEliminationRoundRules',
            'exits',
        ]);

        /*
         * Ver nota equivalente en RoundRobinLabEngine::prepare(): si
         * loadMissing() ya trajo la fila, se usa tal cual; ensure() solo
         * entra en juego cuando realmente no existe (fase nunca visitada en
         * su pestaña "Reglas", ej. colocada directamente como Node del
         * Tournament Graph).
         */
        $settings =
            $phase->singleEliminationSetting
            ?? $this->settingsService->ensure($phase);

        $participantIds =
            array_values(
                $participantIds
            );

        if (
            count($participantIds)
            !==
            count(array_unique($participantIds))
        ) {
            $this->fail(
                'La entrada de Single Elimination contiene participantes duplicados.'
            );
        }

        foreach (
            $participantIds
            as
            $participantId
        ) {
            if (
                ! isset(
                    $participants[$participantId]
                )
            ) {
                $this->fail(
                    "El participante {$participantId} no pertenece al Lab."
                );
            }
        }

        if (
            $settings->configuration_mode
            ===
            'ADVANCED'
            &&
            /*
             * Se lee la relación cargada, no una consulta nueva: cuando
             * la fase viene de un snapshot inmutable (Tournament Runtime
             * persistente) una consulta traería la estructura VIVA y
             * alteraría una competición ya iniciada.
             */
            $phase->singleEliminationRounds
                ->where('status', 'ACTIVE')
                ->isNotEmpty()
        ) {
            return $this->graphRuntime
                ->prepare(
                    $phase,
                    $participantIds
                );
        }

        if (
            $settings->configuration_mode
            ===
            'ADVANCED'
        ) {
            $this->fail(
                'La configuración avanzada necesita una estructura interna activa y validada antes de ejecutarse en Competition Lab.'
            );
        }

        $errors =
            $this->validator
            ->validate(
                $phase,
                $settings,
                count($participantIds)
            );

        $diagnostic =
            $this->inspector
            ->inspect(
                $phase,
                $settings,
                $phase->singleEliminationRoundRules,
                count($participantIds)
            );

        $errors =
            array_values(
                array_unique([
                    ...$errors,
                    ...$diagnostic['errors'],
                ])
            );

        if ($errors !== []) {
            $this->fail(
                implode(
                    ' ',
                    $errors
                )
            );
        }

        $randomContext =
            'PHASE:'
            .
            (string) (
                $phase->getKey()
                ??
                'UNSAVED'
            );

        if ($settings->seeding_mode === 'RANDOM') {
            $participantIds =
                $this->randomOrder(
                    $participantIds,
                    $randomContext . ':SEED'
                );
        } elseif ($settings->seeding_mode === 'RANKING') {
            $this->validateRankingSeeds(
                $participantIds,
                $participants
            );

            usort(
                $participantIds,
                fn($left, $right) =>
                    (int) $participants[$left]['seed']
                    <=>
                    (int) $participants[$right]['seed']
            );
        }

        $seedMap = [];
        foreach ($participantIds as $index => $participantId) {
            $seedMap[$participantId] = $index + 1;
        }

        $manualByeIds = collect($participantIds)
            ->filter(fn($participantId) => (bool) ($participants[$participantId]['manual_bye'] ?? false))
            ->values()
            ->all();

        $roundSeriesRules =
            $phase
            ->singleEliminationRoundRules
            ->mapWithKeys(
                fn($rule) => [
                    (int)
                    $rule->participants_in_round
                    =>
                    [
                        'series_format' =>
                        $rule->series_format
                            ?:
                            'BEST_OF',

                        'best_of' =>
                        (int)
                        $rule->best_of,

                        'fixed_games' =>
                        (int)
                        $rule->fixed_games,
                    ],
                ]
            )
            ->all();

        $runtime = [
            'engine' =>
            'SINGLE_ELIMINATION',

            'mode' =>
            'BASIC',

            'status' =>
            'RUNNING',

            'participant_ids' =>
            $participantIds,

            'initial_participant_count' =>
            count($participantIds),

            'random_context' =>
            $randomContext,

            'target_survivors' =>
            max(
                1,
                (int)
                $settings->target_survivors
            ),

            'default_series_format' =>
            $settings->series_format
                ?:
                'BEST_OF',

            'default_best_of' =>
            (int)
            $settings->default_best_of,

            'default_fixed_games' =>
            (int)
            $settings->fixed_games,

            'round_series_rules' =>
            $roundSeriesRules,

            'reseed_each_round' =>
            (bool)
            $settings->reseed_each_round,

            'pairing_mode' =>
            $settings->pairing_mode,

            'bye_assignment' =>
            $settings->bye_assignment,

            'manual_bye_ids' =>
            $manualByeIds,

            'seed' =>
            $seedMap,

            'rounds' =>
            [],

            'standings' =>
            [],

            'survivor_ids' =>
            [],

            'eliminated_ids' =>
            [],

            'eliminations' =>
            [],

            'current_round' =>
            1,

            /*
             * Compatibilidad: matches_* cuenta únicamente
             * encuentros competitivos, no avances por BYE.
             */
            'matches_total' =>
            0,

            'matches_completed' =>
            0,

            'competitive_matches_total' =>
            0,

            'competitive_matches_completed' =>
            0,

            'structural_matches_total' =>
            0,

            'bye_count' =>
            0,

            /*
             * Que puestos pide esta fase, dicho ANTES de jugar nada.
             *
             * Un cuadro no decide quien es tercero: dos pierden en
             * semifinales y ahi acaban los dos. Si la fase tiene una salida
             * «#3 lugar», ese puesto hay que DISPUTARLO, y anunciarlo desde
             * el minuto cero evita la sensacion de que la configuracion no
             * se aplico —que es exactamente lo que parecia cuando el cuadro
             * arrancaba igual que siempre—.
             */
            'placement_wanted' =>
            $this->placement->wantedFromExits(
                $phase->exits
                    ->mapWithKeys(
                        fn ($exit) => [
                            $exit->id => [
                                'name' => $exit->name,
                                'selector_type' => $exit->selector_type,
                                'selector_from' => $exit->selector_from,
                                'selector_to' => $exit->selector_to,
                            ],
                        ]
                    )
                    ->all()
            ),

            /*
             * El cuadro y la fase ya no terminan a la vez: entre uno y otra
             * caben los desempates de puestos.
             */
            'bracket_status' =>
            'RUNNING',
        ];

        $runtime['rounds'][] =
            $this->makeRound(
                $participantIds,
                1,
                $runtime
            );

        return $this->advanceAutomatic(
            $runtime
        );
    }

    public function submit(
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB
    ): array {
        if (
            ($runtime['mode'] ?? null)
            ===
            'STRUCTURE_GRAPH'
        ) {
            return $this->graphRuntime
                ->submitScore(
                    $runtime,
                    $matchId,
                    $scoreA,
                    $scoreB
                );
        }

        if (
            ($runtime['status'] ?? null)
            !==
            'RUNNING'
        ) {
            $this->fail(
                'La fase ya está completada.'
            );
        }

        if (
            $scoreA < 0
            ||
            $scoreB < 0
        ) {
            $this->fail(
                'Los scores no pueden ser negativos.'
            );
        }

        if (
            $scoreA
            ===
            $scoreB
        ) {
            $this->fail(
                'Single Elimination no permite empates.'
            );
        }

        $found =
            false;

        foreach (
            $runtime['rounds']
            as
            &$round
        ) {
            foreach (
                $round['matches']
                as
                &$match
            ) {
                if (
                    $match['id']
                    !==
                    $matchId
                ) {
                    continue;
                }

                if (
                    $match['status']
                    ===
                    'COMPLETED'
                ) {
                    $this->fail(
                        'El encuentro ya tiene resultado.'
                    );
                }

                if (
                    ! $match['participant_a_id']
                    ||
                    ! $match['participant_b_id']
                ) {
                    $this->fail(
                        'No se puede registrar un resultado en un BYE.'
                    );
                }

                $match['score_a'] =
                    $scoreA;

                $match['score_b'] =
                    $scoreB;

                $match['winner_id'] =
                    $scoreA > $scoreB
                    ? $match['participant_a_id']
                    : $match['participant_b_id'];

                $match['loser_id'] =
                    $scoreA > $scoreB
                    ? $match['participant_b_id']
                    : $match['participant_a_id'];

                $match['status'] =
                    'COMPLETED';

                /*
                 * Un desempate de puestos no elimina a nadie: los que lo
                 * juegan ya cayeron del cuadro y solo estan decidiendo en
                 * que orden quedan. Anotarlo como eliminacion haria saltar
                 * el control de doble eliminacion —con razon— y ademas
                 * moveria la clasificacion base sobre la que se apoyan las
                 * propias bandas.
                 */
                if (! isset($round['placement'])) {

                    $this->recordElimination(
                        $runtime,
                        $match['loser_id'],
                        (int) $round['number'],
                        $matchId
                    );
                }

                $runtime['competitive_matches_completed'] =
                    (int) (
                        $runtime['competitive_matches_completed']
                        ??
                        0
                    )
                    +
                    1;

                $runtime['matches_completed'] =
                    $runtime['competitive_matches_completed'];

                $found =
                    true;

                break 2;
            }
        }

        unset(
            $match,
            $round
        );

        if (! $found) {
            $this->fail(
                'El encuentro no existe en esta fase.'
            );
        }

        return $this->advanceAutomatic(
            $runtime
        );
    }

    public function submitSelection(
        array $runtime,
        string $matchId,
        array $qualifierIds
    ): array {
        if (
            ($runtime['mode'] ?? null)
            !==
            'STRUCTURE_GRAPH'
        ) {
            $this->fail(
                'La selección de varios clasificados solo está disponible en un grafo interno.'
            );
        }

        return $this->graphRuntime
            ->submitSelection(
                $runtime,
                $matchId,
                $qualifierIds
            );
    }

    public function simulateSelection(
        array $runtime,
        string $matchId
    ): array {
        if (
            ($runtime['mode'] ?? null)
            !==
            'STRUCTURE_GRAPH'
        ) {
            $this->fail(
                'La simulación por selección solo está disponible en un grafo interno.'
            );
        }

        return $this->graphRuntime
            ->simulate(
                $runtime,
                $matchId
            );
    }

    private function advanceAutomatic(
        array $runtime
    ): array {
        /*
         * Si el cuadro ya termino, lo que queda por jugar son desempates de
         * puestos. Tienen su propia progresion —no alimentan una ronda
         * siguiente, parten una banda en dos— asi que no pasan por el bucle
         * de abajo, que asume que los ganadores de una ronda son los
         * participantes de la siguiente.
         */
        if (($runtime['bracket_status'] ?? null) === 'COMPLETED') {
            return $this->advancePlacementRounds($runtime);
        }

        while (true) {
            $roundIndex =
                count(
                    $runtime['rounds']
                ) - 1;

            $round =
                &$runtime['rounds'][$roundIndex];

            foreach (
                $round['matches']
                as
                &$match
            ) {
                if (
                    $match['status']
                    !==
                    'PENDING'
                ) {
                    continue;
                }

                if (
                    $match['participant_a_id']
                    &&
                    ! $match['participant_b_id']
                ) {
                    $match['winner_id'] =
                        $match['participant_a_id'];

                    $match['status'] =
                        'BYE';

                    $runtime['bye_count'] =
                        (int) (
                            $runtime['bye_count']
                            ??
                            0
                        )
                        +
                        1;
                } elseif (
                    ! $match['participant_a_id']
                    &&
                    $match['participant_b_id']
                ) {
                    $match['winner_id'] =
                        $match['participant_b_id'];

                    $match['status'] =
                        'BYE';

                    $runtime['bye_count'] =
                        (int) (
                            $runtime['bye_count']
                            ??
                            0
                        )
                        +
                        1;
                }
            }

            unset($match);

            $hasPending =
                collect(
                    $round['matches']
                )
                ->contains(
                    fn($match) =>
                    $match['status']
                        ===
                        'PENDING'
                );

            if ($hasPending) {
                return $runtime;
            }

            $round['status'] =
                'COMPLETED';

            $winners =
                collect(
                    $round['matches']
                )
                ->pluck(
                    'winner_id'
                )
                ->filter()
                ->values()
                ->all();

            $losers =
                collect(
                    $round['matches']
                )
                ->pluck(
                    'loser_id'
                )
                ->filter()
                ->values()
                ->all();

            $runtime['eliminated_ids'] =
                array_values(
                    array_unique([
                        ...$runtime['eliminated_ids'],
                        ...$losers,
                    ])
                );

            $round['survivors_after'] =
                count($winners);

            $round['eliminated_count'] =
                count($losers);

            $target =
                (int)
                $runtime['target_survivors'];

            if (count($winners) < $target) {
                $this->fail(
                    'El runtime produjo menos supervivientes que el objetivo configurado.'
                );
            }

            if (count($winners) === $target) {

                $runtime['bracket_status'] =
                    'COMPLETED';

                $runtime['survivor_ids'] =
                    $winners;

                $runtime['standings'] =
                    $this->standings(
                        $runtime,
                        $winners
                    );

                /*
                 * El cuadro se acabo, pero la fase quiza no: si pide un
                 * «#3 lugar» todavia hay que jugarlo.
                 */
                return $this->advancePlacementRounds(
                    $runtime
                );
            }

            unset($round);

            $roundNumber =
                count(
                    $runtime['rounds']
                ) + 1;

            $runtime['rounds'][] =
                $this->makeRound(
                    $winners,
                    $roundNumber,
                    $runtime
                );

            $runtime['current_round'] =
                $roundNumber;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Los desempates de puestos
    |--------------------------------------------------------------------------
    |
    | Ver PlacementPlanner para la aritmetica —donde hay que cortar y quien
    | queda en que banda—. Aqui solo se convierte ese plan en rondas y
    | emparejamientos de este motor.
    |
    | Se llama cada vez que el cuadro avanza una vez terminado: cierra los
    | desempates ya jugados, parte su banda en dos, y abre los que la nueva
    | reparticion vuelve a necesitar. Cuando no queda ninguno, la fase se da
    | por terminada con la clasificacion ya desempatada.
    |
    */
    private function advancePlacementRounds(
        array $runtime
    ): array {

        $cortes =
            $this->placement
            ->cuts(
                $runtime['placement_wanted']
                ??
                []
            );

        if (! isset($runtime['placement'])) {
            $runtime['placement'] = [
                'cuts' => $cortes,

                'bands' => $this->placement->bands(
                    $runtime['standings']
                ),

                'splits' => [],
                'sequence' => 0,
                'done' => $cortes === [],
            ];
        }

        if ($cortes === []) {
            $runtime['status'] = 'COMPLETED';

            return $runtime;
        }

        /* 1. Los desempates ya jugados parten su banda en dos */
        foreach ($runtime['placement']['splits'] as $indice => $split) {

            if (($split['status'] ?? null) === 'CLOSED') {
                continue;
            }

            $ronda =
                collect($runtime['rounds'])
                ->firstWhere(
                    'number',
                    $split['round_number']
                );

            $jugados =
                collect($ronda['matches'] ?? [])
                ->every(
                    fn ($match) =>
                    in_array($match['status'], ['COMPLETED', 'BYE'], true)
                );

            if (! $jugados) {
                continue;
            }

            $arriba = $split['byes'];
            $abajo = [];

            foreach (($ronda['matches'] ?? []) as $match) {

                if ($match['winner_id'] !== null) {
                    $arriba[] = $match['winner_id'];
                }

                if ($match['loser_id'] !== null) {
                    $abajo[] = $match['loser_id'];
                }
            }

            $runtime['placement']['bands'] =
                $this->placement->replaceBand(
                    $runtime['placement']['bands'],
                    (int) $split['from'],
                    (int) $split['to'],
                    $arriba,
                    $abajo
                );

            $runtime['placement']['splits'][$indice]['status'] = 'CLOSED';

            foreach ($runtime['rounds'] as $posicion => $otra) {
                if ((int) $otra['number'] === (int) $split['round_number']) {
                    $runtime['rounds'][$posicion]['status'] = 'COMPLETED';
                }
            }
        }

        /* 2. Y la nueva reparticion abre los que ahora hacen falta */
        $abiertos = [];

        foreach ($runtime['placement']['splits'] as $split) {
            if (($split['status'] ?? null) !== 'CLOSED') {
                $abiertos[] = (int) $split['from'];
            }
        }

        foreach ($runtime['placement']['bands'] as $banda) {

            if (in_array((int) $banda['from'], $abiertos, true)) {
                continue;
            }

            if (! $this->placement->needsSplit($banda, $cortes)) {
                continue;
            }

            $runtime = $this->openPlacementRound($runtime, $banda);
        }

        $pendientes =
            collect($runtime['placement']['splits'])
            ->contains(
                fn ($split) =>
                ($split['status'] ?? null) !== 'CLOSED'
            );

        $runtime['placement']['done'] = ! $pendientes;

        if ($pendientes) {
            $runtime['status'] = 'RUNNING';

            return $runtime;
        }

        $runtime['status'] = 'COMPLETED';

        $runtime['standings'] =
            $this->placement
            ->standings(
                $runtime['standings'],
                $runtime['placement']['bands']
            );

        return $runtime;
    }

    /*
     * Abre el desempate de una banda como una ronda mas.
     */
    private function openPlacementRound(
        array $runtime,
        array $banda
    ): array {

        ['pairs' => $parejas, 'byes' => $byes] =
            $this->placement
            ->pairings(
                $banda
            );

        if ($parejas === []) {
            return $runtime;
        }

        $runtime['placement']['sequence']++;
        $secuencia = (int) $runtime['placement']['sequence'];

        $roundNumber =
            collect($runtime['rounds'])
            ->max('number')
            + 1;

        $etiqueta =
            $this->placement
            ->label(
                (int) $banda['from'],
                (int) $banda['to']
            );

        /*
         * Un desempate se juega como se juega el final del cuadro: si la
         * final va a BO3, el partido por el tercer puesto tambien.
         */
        $serie =
            $runtime['round_series_rules'][2]
            ?? [
                'series_format' => $runtime['default_series_format'],
                'best_of' => $runtime['default_best_of'],
                'fixed_games' => $runtime['default_fixed_games'],
            ];

        $matches = [];

        foreach ($parejas as $i => $pareja) {

            $matches[] = [
                'id' => 'SE-P' . $secuencia . '-M' . ($i + 1),
                'number' => $i + 1,
                'participant_a_id' => $pareja[0],
                'participant_b_id' => $pareja[1],
                'score_a' => null,
                'score_b' => null,
                'winner_id' => null,
                'loser_id' => null,

                'series_format' => $serie['series_format'],
                'best_of' => (int) $serie['best_of'],
                'fixed_games' => (int) $serie['fixed_games'],

                'series_label' => $serie['series_format'] === 'FIXED_GAMES'
                    ? $serie['fixed_games'] . ' '
                        . ((int) $serie['fixed_games'] === 1
                            ? 'enfrentamiento fijo'
                            : 'enfrentamientos fijos')
                    : 'BO' . $serie['best_of'],

                'status' => 'PENDING',
            ];
        }

        $runtime['rounds'][] = [
            'number' => $roundNumber,
            'participants_in_round' => count($banda['ids']),
            'participants_count' => count($banda['ids']),
            'label' => $etiqueta,

            /*
             * El proyector guarda esto como `group_label`, y es lo que
             * permite a la pantalla del cuadro separar los desempates de las
             * rondas normales sin adivinar por el numero de ronda.
             */
            'group_name' => $etiqueta,

            'status' => 'RUNNING',
            'survivors_after' => null,
            'eliminated_count' => 0,
            'matches' => $matches,

            'placement' => [
                'from' => (int) $banda['from'],
                'to' => (int) $banda['to'],
            ],
        ];

        $runtime['placement']['splits'][] = [
            'id' => $secuencia,
            'from' => (int) $banda['from'],
            'to' => (int) $banda['to'],
            'label' => $etiqueta,
            'round_number' => $roundNumber,
            'byes' => $byes,
            'status' => 'OPEN',
        ];

        $runtime['current_round'] = $roundNumber;

        $runtime['structural_matches_total'] =
            (int) ($runtime['structural_matches_total'] ?? 0)
            + count($matches);

        $runtime['competitive_matches_total'] =
            (int) ($runtime['competitive_matches_total'] ?? 0)
            + count($matches);

        $runtime['matches_total'] =
            $runtime['competitive_matches_total'];

        return $runtime;
    }

    private function makeRound(
        array $participantIds,
        int $roundNumber,
        array &$runtime
    ): array {
        $roundSize =
            $this->roundSize(
                count($participantIds)
            );

        $roundSeries =
            $runtime['round_series_rules'][$roundSize]
            ??
            [
                'series_format' =>
                $runtime['default_series_format'],

                'best_of' =>
                $runtime['default_best_of'],

                'fixed_games' =>
                $runtime['default_fixed_games'],
            ];

        $byeCount =
            $roundNumber === 1
            ? max(
                0,
                $roundSize - count($participantIds)
            )
            : 0;

        $byeIds =
            $this->selectByeIds(
                $participantIds,
                $byeCount,
                $runtime,
                $roundNumber
            );

        $pairings =
            $this->buildRoundPairings(
                $participantIds,
                $byeIds,
                $runtime,
                $roundNumber,
                $roundSize
            );

        $matches = [];
        $competitiveMatches = 0;

        foreach (
            $pairings
            as
            [$participantA, $participantB]
        ) {
            if (
                $participantA !== null
                &&
                $participantB !== null
            ) {
                $competitiveMatches++;
            }

            $matches[] = [
                'id' =>
                'SE-R'
                .
                $roundNumber
                .
                '-M'
                .
                (count($matches) + 1),

                'number' =>
                count($matches) + 1,

                'participant_a_id' =>
                $participantA,

                'participant_b_id' =>
                $participantB,

                'score_a' =>
                null,

                'score_b' =>
                null,

                'winner_id' =>
                null,

                'loser_id' =>
                null,

                'series_format' =>
                $roundSeries['series_format'],

                'best_of' =>
                (int)
                $roundSeries['best_of'],

                'fixed_games' =>
                (int)
                $roundSeries['fixed_games'],

                'series_label' =>
                $roundSeries['series_format']
                    ===
                    'FIXED_GAMES'
                    ? $roundSeries['fixed_games']
                    .
                    ' '
                    .
                    (
                        (int) $roundSeries['fixed_games'] === 1
                        ? 'enfrentamiento fijo'
                        : 'enfrentamientos fijos'
                    )
                    : 'BO'
                    .
                    $roundSeries['best_of'],

                'status' =>
                'PENDING',
            ];
        }

        $runtime['structural_matches_total'] =
            (int) (
                $runtime['structural_matches_total']
                ??
                0
            )
            +
            count($matches);

        $runtime['competitive_matches_total'] =
            (int) (
                $runtime['competitive_matches_total']
                ??
                0
            )
            +
            $competitiveMatches;

        $runtime['matches_total'] =
            $runtime['competitive_matches_total'];

        return [
            'number' =>
            $roundNumber,

            /*
             * Capacidad nominal utilizada por las reglas por ronda.
             */
            'participants_in_round' =>
            $roundSize,

            /*
             * Cantidad real de participantes activos al iniciar la ronda.
             */
            'participants_count' =>
            count($participantIds),

            'label' =>
            count($participantIds)
                ===
                2
                ? 'Final'
                : 'Ronda '
                .
                $roundNumber,

            'status' =>
            'RUNNING',

            'survivors_after' =>
            null,

            'eliminated_count' =>
            0,

            'matches' =>
            $matches,
        ];
    }

    private function selectByeIds(
        array $participantIds,
        int $byeCount,
        array $runtime,
        int $roundNumber
    ): array {
        if ($byeCount <= 0) {
            return [];
        }

        $policy =
            $runtime['bye_assignment']
            ??
            'TOP_SEEDS';

        if ($policy === 'MANUAL') {
            $manual =
                array_values(
                    array_intersect(
                        $runtime['manual_bye_ids'] ?? [],
                        $participantIds
                    )
                );

            if (
                count($manual)
                !==
                count(array_unique($manual))
            ) {
                $this->fail(
                    'La selección manual de BYEs contiene participantes duplicados.'
                );
            }

            if (count($manual) !== $byeCount) {
                $this->fail(
                    "La primera ronda necesita exactamente {$byeCount} BYE manual(es)."
                );
            }

            return $manual;
        }

        $candidates =
            $participantIds;

        if ($policy === 'RANDOM') {
            $candidates =
                $this->randomOrder(
                    $candidates,
                    ($runtime['random_context'] ?? 'SE')
                    .
                    ':BYE:'
                    .
                    $roundNumber
                );
        } else {
            usort(
                $candidates,
                fn($left, $right) =>
                    ($runtime['seed'][$left] ?? PHP_INT_MAX)
                    <=>
                    ($runtime['seed'][$right] ?? PHP_INT_MAX)
            );
        }

        return array_slice(
            $candidates,
            0,
            $byeCount
        );
    }

    private function buildRoundPairings(
        array $participantIds,
        array $byeIds,
        array $runtime,
        int $roundNumber,
        int $roundSize
    ): array {
        if ($roundNumber === 1) {
            if (
                ($runtime['pairing_mode'] ?? null)
                ===
                'STANDARD_SEEDED'
            ) {
                return $this->buildSeededFirstRoundPairings(
                    $participantIds,
                    $byeIds,
                    $runtime,
                    $roundSize
                );
            }

            return $this->buildSimpleFirstRoundPairings(
                $participantIds,
                $byeIds,
                $runtime,
                $roundNumber
            );
        }

        if (
            $runtime['reseed_each_round']
            ??
            false
        ) {
            return $this->buildReseededPairings(
                $participantIds,
                $runtime
            );
        }

        /*
         * Sin reseed se conserva la ruta fija del bracket:
         * winner M1 vs winner M2, winner M3 vs winner M4, etc.
         */
        return $this->pairSequentially(
            $participantIds
        );
    }

    private function buildSeededFirstRoundPairings(
        array $participantIds,
        array $byeIds,
        array $runtime,
        int $roundSize
    ): array {
        $participantsBySeed = [];

        foreach (
            $runtime['seed']
            as
            $participantId => $seed
        ) {
            $participantsBySeed[(int) $seed] =
                $participantId;
        }

        $slots = [];

        foreach (
            $this->canonicalSeedOrder($roundSize)
            as
            $seed
        ) {
            $slots[] =
                $participantsBySeed[$seed]
                ??
                null;
        }

        if ($byeIds !== []) {
            $slots =
                $this->relocateSeededByes(
                    $slots,
                    $byeIds,
                    $runtime
                );
        }

        return array_map(
            fn($pair) => [
                $pair[0] ?? null,
                $pair[1] ?? null,
            ],
            array_chunk($slots, 2)
        );
    }

    private function relocateSeededByes(
        array $slots,
        array $desiredByeIds,
        array $runtime
    ): array {
        $currentByeIds =
            $this->byeRecipientsFromSlots(
                $slots
            );

        $sortBySeed =
            function (array &$ids) use ($runtime): void {
                usort(
                    $ids,
                    fn($left, $right) =>
                        ($runtime['seed'][$left] ?? PHP_INT_MAX)
                        <=>
                        ($runtime['seed'][$right] ?? PHP_INT_MAX)
                );
            };

        $desiredByeIds =
            array_values(
                array_unique(
                    $desiredByeIds
                )
            );

        $sortBySeed($desiredByeIds);
        $sortBySeed($currentByeIds);

        if (
            count($desiredByeIds)
            !==
            count($currentByeIds)
        ) {
            $this->fail(
                'La topología del bracket no coincide con la cantidad de BYEs requerida.'
            );
        }

        $gainBye =
            array_values(
                array_diff(
                    $desiredByeIds,
                    $currentByeIds
                )
            );

        $loseBye =
            array_values(
                array_diff(
                    $currentByeIds,
                    $desiredByeIds
                )
            );

        $sortBySeed($gainBye);
        $sortBySeed($loseBye);

        foreach (
            $gainBye
            as
            $index => $participantId
        ) {
            $currentHost =
                $loseBye[$index]
                ??
                null;

            if ($currentHost === null) {
                $this->fail(
                    'No fue posible ubicar los BYEs dentro del bracket sembrado.'
                );
            }

            $participantSlot =
                array_search(
                    $participantId,
                    $slots,
                    true
                );

            $hostSlot =
                array_search(
                    $currentHost,
                    $slots,
                    true
                );

            if (
                $participantSlot === false
                ||
                $hostSlot === false
            ) {
                $this->fail(
                    'No fue posible ubicar un participante dentro del bracket sembrado.'
                );
            }

            [
                $slots[$participantSlot],
                $slots[$hostSlot],
            ] = [
                $slots[$hostSlot],
                $slots[$participantSlot],
            ];
        }

        $resolved =
            $this->byeRecipientsFromSlots(
                $slots
            );

        sort($resolved);

        $expected =
            $desiredByeIds;

        sort($expected);

        if ($resolved !== $expected) {
            $this->fail(
                'Los BYEs no pudieron integrarse correctamente en la topología del bracket.'
            );
        }

        return $slots;
    }

    private function byeRecipientsFromSlots(
        array $slots
    ): array {
        $recipients = [];

        foreach (
            array_chunk($slots, 2)
            as
            $pair
        ) {
            $left =
                $pair[0]
                ??
                null;

            $right =
                $pair[1]
                ??
                null;

            if (
                $left !== null
                &&
                $right === null
            ) {
                $recipients[] =
                    $left;
            } elseif (
                $left === null
                &&
                $right !== null
            ) {
                $recipients[] =
                    $right;
            }
        }

        return $recipients;
    }

    private function buildSimpleFirstRoundPairings(
        array $participantIds,
        array $byeIds,
        array $runtime,
        int $roundNumber
    ): array {
        $ordered =
            $participantIds;

        if (
            ($runtime['pairing_mode'] ?? null)
            ===
            'RANDOM'
        ) {
            $ordered =
                $this->randomOrder(
                    $ordered,
                    ($runtime['random_context'] ?? 'SE')
                    .
                    ':PAIR:'
                    .
                    $roundNumber
                );
        }

        $byeSet =
            array_fill_keys(
                $byeIds,
                true
            );

        $byePairs = [];
        $playing = [];

        foreach (
            $ordered
            as
            $participantId
        ) {
            if (isset($byeSet[$participantId])) {
                $byePairs[] = [
                    $participantId,
                    null,
                ];
            } else {
                $playing[] =
                    $participantId;
            }
        }

        return [
            ...$byePairs,
            ...$this->pairSequentially($playing),
        ];
    }

    private function buildReseededPairings(
        array $participantIds,
        array $runtime
    ): array {
        $ordered =
            $participantIds;

        usort(
            $ordered,
            fn($left, $right) =>
                ($runtime['seed'][$left] ?? PHP_INT_MAX)
                <=>
                ($runtime['seed'][$right] ?? PHP_INT_MAX)
        );

        if (count($ordered) % 2 !== 0) {
            $this->fail(
                'El reseeding produjo una cantidad impar de participantes.'
            );
        }

        $pairs = [];
        $left = 0;
        $right = count($ordered) - 1;

        while ($left < $right) {
            $pairs[] = [
                $ordered[$left++],
                $ordered[$right--],
            ];
        }

        return $pairs;
    }

    private function pairSequentially(
        array $participantIds
    ): array {
        if (count($participantIds) % 2 !== 0) {
            $this->fail(
                'El bracket intentó crear una ronda con una cantidad impar sin BYE.'
            );
        }

        $pairs = [];

        for (
            $index = 0;
            $index < count($participantIds);
            $index += 2
        ) {
            $pairs[] = [
                $participantIds[$index],
                $participantIds[$index + 1],
            ];
        }

        return $pairs;
    }

    /**
     * Orden canónico de slots para un bracket sembrado.
     *
     * 8 => [1, 8, 4, 5, 2, 7, 3, 6]
     */
    private function canonicalSeedOrder(
        int $size
    ): array {
        if (
            $size < 2
            ||
            ! $this->isPowerOfTwo($size)
        ) {
            $this->fail(
                'El bracket sembrado necesita una capacidad potencia de 2.'
            );
        }

        $order = [
            1,
            2,
        ];

        for (
            $current = 4;
            $current <= $size;
            $current *= 2
        ) {
            $next = [];

            foreach (
                $order
                as
                $seed
            ) {
                $next[] =
                    $seed;

                $next[] =
                    $current + 1 - $seed;
            }

            $order =
                $next;
        }

        return $order;
    }

    private function randomOrder(
        array $participantIds,
        string $context
    ): array {
        usort(
            $participantIds,
            fn($left, $right) =>
                strcmp(
                    hash(
                        'sha256',
                        $context . ':' . $left
                    ),
                    hash(
                        'sha256',
                        $context . ':' . $right
                    )
                )
        );

        return $participantIds;
    }

    private function validateRankingSeeds(
        array $participantIds,
        array $participants
    ): void {
        $seen = [];

        foreach (
            $participantIds
            as
            $participantId
        ) {
            $rawSeed =
                $participants[$participantId]['seed']
                ??
                null;

            $seed =
                filter_var(
                    $rawSeed,
                    FILTER_VALIDATE_INT
                );

            if (
                $seed === false
                ||
                $seed < 1
            ) {
                $this->fail(
                    "El participante {$participantId} necesita un seed entero positivo para usar Ranking."
                );
            }

            if (isset($seen[$seed])) {
                $this->fail(
                    "El seed {$seed} está repetido entre {$seen[$seed]} y {$participantId}."
                );
            }

            $seen[$seed] =
                $participantId;
        }
    }

    private function recordElimination(
        array &$runtime,
        string|int $participantId,
        int $roundNumber,
        string $matchId
    ): void {
        foreach (
            $runtime['eliminations'] ?? []
            as
            $event
        ) {
            if (
                ($event['participant_id'] ?? null)
                ===
                $participantId
            ) {
                $this->fail(
                    "El participante {$participantId} ya había sido eliminado en esta fase."
                );
            }
        }

        $runtime['eliminations'][] = [
            'participant_id' =>
            $participantId,

            'round_number' =>
            $roundNumber,

            'match_id' =>
            $matchId,

            'source' =>
            'MATCH_RESULT',
        ];

        $runtime['eliminated_ids'] =
            array_values(
                array_unique([
                    ...($runtime['eliminated_ids'] ?? []),
                    $participantId,
                ])
            );
    }

    private function standings(
        array $runtime,
        array $survivors
    ): array {
        $standings = [];
        $survivorCount =
            count($survivors);

        $orderedSurvivors =
            $survivors;

        usort(
            $orderedSurvivors,
            fn($left, $right) =>
                ($runtime['seed'][$left] ?? PHP_INT_MAX)
                <=>
                ($runtime['seed'][$right] ?? PHP_INT_MAX)
        );

        foreach (
            $orderedSurvivors
            as
            $participantId
        ) {
            $standings[] = [
                /*
                 * position se conserva para consumidores existentes.
                 */
                'position' =>
                1,

                'position_from' =>
                1,

                'position_to' =>
                $survivorCount,

                'participant_id' =>
                $participantId,

                'status' =>
                'SURVIVOR',

                'placement_status' =>
                $survivorCount === 1
                    ? 'RANKED'
                    : 'UNRANKED_SURVIVOR',
            ];
        }

        $eventsByRound = [];

        foreach (
            $runtime['eliminations'] ?? []
            as
            $event
        ) {
            $eventsByRound[(int) $event['round_number']][] =
                $event;
        }

        krsort(
            $eventsByRound,
            SORT_NUMERIC
        );

        foreach (
            $eventsByRound
            as
            $roundNumber => $events
        ) {
            $round =
                collect($runtime['rounds'])
                ->firstWhere(
                    'number',
                    $roundNumber
                );

            if (! is_array($round)) {
                $this->fail(
                    'No fue posible calcular la clasificación de una eliminación.'
                );
            }

            $positionFrom =
                (int) (
                    $round['survivors_after']
                    ??
                    0
                )
                +
                1;

            $positionTo =
                (int) (
                    $round['participants_count']
                    ??
                    $positionFrom
                );

            usort(
                $events,
                fn($left, $right) =>
                    ($runtime['seed'][$left['participant_id']] ?? PHP_INT_MAX)
                    <=>
                    ($runtime['seed'][$right['participant_id']] ?? PHP_INT_MAX)
            );

            foreach (
                $events
                as
                $event
            ) {
                $standings[] = [
                    'position' =>
                    $positionFrom,

                    'position_from' =>
                    $positionFrom,

                    'position_to' =>
                    $positionTo,

                    'participant_id' =>
                    $event['participant_id'],

                    'status' =>
                    'ELIMINATED',

                    'placement_status' =>
                    $positionFrom === $positionTo
                        ? 'RANKED'
                        : 'TIED_BAND',

                    'eliminated_round' =>
                    $roundNumber,

                    'match_id' =>
                    $event['match_id'],
                ];
            }
        }

        return $standings;
    }

    private function roundSize(int $participants): int
    {
        $size = 2;

        while ($size < max(2, $participants)) {
            $size *= 2;
        }

        return $size;
    }

    private function isPowerOfTwo(
        int $value
    ): bool {
        return
            $value > 0
            &&
            (
                $value
                &
                ($value - 1)
            )
            ===
            0;
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'engine' => [
                $message,
            ],
        ]);
    }
}

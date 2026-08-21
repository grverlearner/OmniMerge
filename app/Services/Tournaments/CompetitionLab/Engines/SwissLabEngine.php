<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseSwissSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Runtime\CutoffPolicyResolver;
use App\Services\Tournaments\Swiss\SwissPairingCalculator;
use App\Services\Tournaments\Swiss\SwissSettingsService;
use App\Services\Tournaments\Swiss\SwissValidator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SwissLabEngine
implements LabPhaseEngine, SupportsManualDecision
{
    public function __construct(
        private readonly
        SwissValidator $validator,

        private readonly
        SwissPairingCalculator $pairingCalculator,

        private readonly
        CutoffPolicyResolver $cutoffResolver,

        private readonly
        SwissSettingsService $settingsService
    ) {}

    public function supports(
        string $phaseType
    ): bool {
        return
            $phaseType
            ===
            'SWISS';
    }

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array {
        $phase->loadMissing([
            'swissSetting',
            'swissRoundRules',
            'swissTiebreakers',
            'swissAdvancementRules.phaseExit',
        ]);

        /*
         * Ver nota equivalente en RoundRobinLabEngine::prepare(): si
         * loadMissing() ya trajo la fila, se usa tal cual; ensure() solo
         * entra en juego cuando realmente no existe (fase nunca visitada en
         * su pestaña "Reglas", ej. colocada directamente como Node del
         * Tournament Graph).
         */
        $settings =
            $phase->swissSetting
            ?? $this->settingsService->ensure($phase);

        $participantIds =
            array_values(
                array_unique(
                    $participantIds
                )
            );

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

        $validation =
            $this->validator
            ->validate(
                $phase,
                $settings,
                count($participantIds)
            );

        if (
            $validation['errors']
            !==
            []
        ) {
            $this->fail(
                implode(
                    ' ',
                    $validation['errors']
                )
            );
        }

        $records =
            [];

        foreach (
            $participantIds
            as
            $index =>
            $participantId
        ) {
            $records[$participantId] = [
                'participant_id' =>
                $participantId,

                'seed' =>
                $index + 1,

                'input_order' =>
                $index + 1,

                'side_a_count' =>
                0,

                'side_b_count' =>
                0,

                'float_count' =>
                0,

                'played' =>
                0,

                'wins' =>
                0,

                'draws' =>
                0,

                'losses' =>
                0,

                'points' =>
                0,

                'pairing_score' =>
                0,

                'score_for' =>
                0,

                'score_against' =>
                0,

                'score_difference' =>
                0,

                'opponents' =>
                [],

                'bye_count' =>
                0,

                'opponent_score_sum' =>
                0,

                'sonneborn_berger' =>
                0,

                'cumulative_score' =>
                0,

                'position' =>
                $index + 1,

                'status' =>
                'ACTIVE',

                'exit_id' =>
                null,

                'exit_name' =>
                null,
            ];
        }

        $runtime = [
            'engine' =>
            'SWISS',

            'status' =>
            'RUNNING',

            'pairing_settings' =>
            $settings->getAttributes(),

            'completion_mode' =>
            $settings->completion_mode,

            'round_limit' =>
            (int)
            $settings->round_limit,

            'qualification_wins' =>
            $settings->qualification_wins
                ? (int)
                $settings->qualification_wins
                : null,

            'elimination_losses' =>
            $settings->elimination_losses
                ? (int)
                $settings->elimination_losses
                : null,

            'pairing_algorithm' =>
            $settings->pairing_algorithm,

            'pairing_basis' =>
            $settings->pairing_basis,

            'rematch_policy' =>
            $settings->rematch_policy,

            'floater_policy' =>
            $settings->floater_policy,

            'allow_draws' =>
            (bool)
            $settings->allow_draws,

            'points' => [
                'win' =>
                (float)
                $settings->win_points,

                'draw' =>
                (float)
                $settings->draw_points,

                'loss' =>
                (float)
                $settings->loss_points,

                'bye' =>
                (float)
                $settings->bye_points,
            ],

            'default_best_of' =>
            (int)
            $settings->default_best_of,

            'bye_policy' =>
            $settings->bye_policy,

            'max_byes_per_participant' =>
            (int)
            $settings->max_byes_per_participant,

            'cutoff_tie_policy' =>
            $settings->cutoff_tie_policy,

            'fallback_policy' =>
            $settings->fallback_policy,

            'resolved_cutoffs' =>
            [],

            'records' =>
            $records,

            'standings' =>
            array_values(
                $records
            ),

            'round_rules' =>
            $phase
                ->swissRoundRules
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->map(
                    fn($rule) => [
                        'trigger_type' =>
                        $rule->trigger_type,

                        'round_number' =>
                        $rule->round_number,

                        'record_wins' =>
                        $rule->record_wins,

                        'record_draws' =>
                        $rule->record_draws,

                        'record_losses' =>
                        $rule->record_losses,

                        'best_of' =>
                        (int)
                        $rule->best_of,

                        'allow_draws_override' =>
                        $rule->allow_draws_override,

                        'sort_order' =>
                        (int)
                        $rule->sort_order,
                    ]
                )
                ->sortBy('sort_order')
                ->values()
                ->all(),

            'tiebreakers' =>
            $phase
                ->swissTiebreakers
                ->map(
                    fn($tiebreaker) => [
                        'criterion' =>
                        $tiebreaker->criterion,

                        'parameter_int' =>
                        $tiebreaker->parameter_int,

                        'direction' =>
                        $tiebreaker
                            ->effective_direction,
                    ]
                )
                ->all(),

            'advancement_rules' =>
            $phase
                ->swissAdvancementRules
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->map(
                    fn($rule) => [
                        'id' =>
                        (int)
                        $rule->id,

                        'exit_id' =>
                        $rule->phase_exit_id
                            ? (int)
                            $rule->phase_exit_id
                            : null,

                        'exit_name' =>
                        $rule->phaseExit?->name
                            ??
                            'Sin salida',

                        'rule_type' =>
                        $rule->rule_type,

                        'threshold_wins' =>
                        $rule->threshold_wins,

                        'threshold_losses' =>
                        $rule->threshold_losses,

                        'record_wins' =>
                        $rule->record_wins,

                        'record_draws' =>
                        $rule->record_draws,

                        'record_losses' =>
                        $rule->record_losses,

                        'rank_from' =>
                        $rule->rank_from,

                        'rank_to' =>
                        $rule->rank_to,

                        'take' =>
                        $rule->take,

                        'sort_order' =>
                        (int)
                        $rule->sort_order,
                    ]
                )
                ->sortBy('sort_order')
                ->values()
                ->all(),

            'rounds' =>
            [],

            'current_round' =>
            0,

            'matches_total' =>
            0,

            'matches_completed' =>
            0,

            'outcomes' =>
            [],

            'rule_results' =>
            [],

            'survivor_ids' =>
            [],

            'eliminated_ids' =>
            [],

            'warnings' =>
            $validation['warnings'],

            'pairing_relaxations' =>
            [],
        ];

        return $this->generateNextRound(
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
            $runtime['status']
            !==
            'RUNNING'
        ) {
            $this->fail(
                'La fase Swiss ya terminó.'
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

                $allowDraws =
                    $match['allow_draws'];

                if (
                    $scoreA === $scoreB
                    &&
                    ! $allowDraws
                ) {
                    $this->fail(
                        'Este encuentro Swiss necesita un ganador.'
                    );
                }

                $match['score_a'] =
                    $scoreA;

                $match['score_b'] =
                    $scoreB;

                $match['winner_id'] =
                    $scoreA === $scoreB
                    ? null
                    : (
                        $scoreA > $scoreB
                        ? $match['participant_a_id']
                        : $match['participant_b_id']
                    );

                $match['status'] =
                    'COMPLETED';

                $runtime['matches_completed']++;

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
                'El encuentro no existe en esta fase Swiss.'
            );
        }

        $currentIndex =
            count(
                $runtime['rounds']
            ) - 1;

        $currentRound =
            &$runtime['rounds'][$currentIndex];

        $hasPending =
            collect(
                $currentRound['matches']
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

        $currentRound['status'] =
            'COMPLETED';

        unset($currentRound);

        $runtime =
            $this->applyCompletedRound(
                $runtime,
                $currentIndex
            );

        $runtime =
            $this->applyDynamicRules(
                $runtime
            );

        if (
            $this->shouldFinish(
                $runtime
            )
        ) {
            return $this->complete(
                $runtime
            );
        }

        return $this->generateNextRound(
            $runtime
        );
    }

    public function resolveManualDecision(
        array $runtime,
        array $payload
    ): array {
        $decision = $runtime['manual_decision'] ?? null;

        if (! is_array($decision) || ($payload['decision_id'] ?? null) !== ($decision['id'] ?? null)) {
            $this->fail('La decisión Swiss ya no corresponde al estado actual.');
        }

        $eligible = array_values($decision['eligible_participant_ids'] ?? []);
        $selected = array_values(array_unique($payload['selected_participant_ids'] ?? []));
        $required = (int) ($decision['required_selection_count'] ?? 0);

        if (count($selected) !== $required) {
            $this->fail("Debes seleccionar exactamente {$required} participante(s).");
        }

        foreach ($selected as $participantId) {
            if (! in_array($participantId, $eligible, true)) {
                $this->fail('La decisión contiene un participante no elegible.');
            }
        }

        $type = $decision['type'] ?? '';
        unset($runtime['manual_decision']);
        $runtime['status'] = 'RUNNING';

        if ($type === 'SWISS_BYE') {
            $byeId = $selected[0] ?? null;
            if (! $byeId || ! isset($runtime['records'][$byeId])) {
                $this->fail('Selecciona un participante válido para BYE.');
            }

            $runtime['records'][$byeId]['status'] = 'MANUAL_BYE_HOLDER';
            $runtime = $this->generateNextRound($runtime);
            $runtime['records'][$byeId]['status'] = 'ACTIVE';
            $runtime['records'][$byeId]['bye_count']++;
            $runtime['records'][$byeId]['points'] += $runtime['points']['bye'];
            $runtime['records'][$byeId]['pairing_score'] += $runtime['points']['bye'];
            $runtime['records'][$byeId]['cumulative_score'] += $runtime['records'][$byeId]['points'];

            $last = count($runtime['rounds']) - 1;
            if ($last >= 0) {
                $runtime['rounds'][$last]['bye_participant_id'] = $byeId;
            }

            return $this->recalculateStandings($runtime);
        }

        if (in_array($type, ['CUTOFF_SELECTION', 'PLAYOFF_SELECTION'], true)) {
            $key = data_get($decision, 'context.decision_key');
            if (! $key) {
                $this->fail('El desempate no contiene una clave de resolución.');
            }

            $runtime['resolved_cutoffs'][$key] = array_values(array_unique([
                ...data_get($decision, 'context.guaranteed_participant_ids', []),
                ...$selected,
            ]));

            return $this->complete($runtime);
        }

        if ($type === 'SWISS_FALLBACK') {
            $selectedMap = array_fill_keys($selected, true);
            foreach ($eligible as $participantId) {
                $runtime['records'][$participantId]['status'] = isset($selectedMap[$participantId])
                    ? 'QUALIFIED'
                    : 'ELIMINATED';

                if (isset($selectedMap[$participantId])) {
                    $runtime['records'][$participantId]['exit_id'] = data_get($decision, 'context.exit_id');
                    $runtime['records'][$participantId]['exit_name'] = data_get($decision, 'context.exit_name', 'Clasificados');
                }
            }

            $runtime['manual_fallback_resolved'] = true;
            return $this->complete($runtime);
        }

        $this->fail('El tipo de decisión Swiss no está soportado.');
    }

    private function generateNextRound(
        array $runtime
    ): array {
        $roundNumber =
            $runtime['current_round']
            +
            1;

        $activeRecords = collect($runtime['records'])
            ->where('status', 'ACTIVE')
            ->values();

        if ($activeRecords->count() < 2) {
            return $this->complete($runtime);
        }

        $settings = (new PhaseSwissSetting())->forceFill(
            $runtime['pairing_settings'] ?? []
        );

        $calculatorParticipants = $activeRecords
            ->map(function (array $record): array {
                return [
                    'id' => $record['participant_id'],
                    'seed' => $record['seed'],
                    'input_order' => $record['input_order'] ?? $record['seed'],
                    'label' => (string) $record['participant_id'],
                    'wins' => $record['wins'],
                    'draws' => $record['draws'],
                    'losses' => $record['losses'],
                    'standing_score' => $record['points'],
                    'pairing_score' => $record['pairing_score'],
                    'opponents' => $record['opponents'],
                    'bye_count' => $record['bye_count'],
                    'side_a_count' => $record['side_a_count'] ?? 0,
                    'side_b_count' => $record['side_b_count'] ?? 0,
                    'float_count' => $record['float_count'] ?? 0,
                    'standing_position' => $record['position'] ?? $record['seed'],
                    'active' => true,
                ];
            })
            ->all();

        $calculated = $this->pairingCalculator->generateRound(
            $calculatorParticipants,
            $settings,
            $roundNumber
        );

        if (! ($calculated['valid'] ?? false)) {
            $this->fail(implode(' ', $calculated['errors'] ?? [
                'No fue posible generar la ronda Swiss.',
            ]));
        }

        if ($calculated['manual_bye_required'] ?? false) {
            $eligibleIds = $activeRecords
                ->filter(fn(array $record) =>
                    (int) $record['bye_count'] < (int) $runtime['max_byes_per_participant']
                )
                ->pluck('participant_id')
                ->values()
                ->all();

            if ($eligibleIds === []) {
                $this->fail('No existe un participante elegible para recibir el BYE manual.');
            }

            $runtime['status'] = 'AWAITING_DECISION';
            $runtime['manual_decision'] = [
                'id' => 'DEC-' . substr(hash(
                    'sha256',
                    'SWISS_BYE:' . $roundNumber . ':' . implode('|', $eligibleIds)
                ), 0, 20),
                'scope' => 'ENGINE',
                'type' => 'SWISS_BYE',
                'title' => 'Asignar BYE Swiss',
                'description' => 'Selecciona exactamente un participante elegible para descansar esta ronda.',
                'eligible_participant_ids' => $eligibleIds,
                'required_selection_count' => 1,
                'context' => [
                    'round_number' => $roundNumber,
                ],
            ];

            return $runtime;
        }

        foreach ($calculated['warnings'] ?? [] as $warning) {
            if (! in_array($warning, $runtime['pairing_relaxations'], true)) {
                $runtime['pairing_relaxations'][] = $warning;
            }
        }

        $byeId = $calculated['bye']['id'] ?? null;

        $pairings = collect($calculated['pairings'] ?? [])
            ->map(fn(array $pairing) => [
                $pairing['participant_a']['id'],
                $pairing['participant_b']['id'],
            ])
            ->values()
            ->all();

        foreach ($calculated['pairings'] ?? [] as $pairing) {
            $leftId = $pairing['participant_a']['id'];
            $rightId = $pairing['participant_b']['id'];
            $runtime['records'][$leftId]['side_a_count'] =
                ($runtime['records'][$leftId]['side_a_count'] ?? 0) + 1;
            $runtime['records'][$rightId]['side_b_count'] =
                ($runtime['records'][$rightId]['side_b_count'] ?? 0) + 1;
        }

        $matches =
            [];

        foreach (
            $pairings
            as
            $index =>
            $pairing
        ) {
            $rule = $this->roundRuleForMatch(
                $runtime,
                $roundNumber,
                $pairing[0],
                $pairing[1]
            );

            $bestOf = (int) ($rule['best_of'] ?? $runtime['default_best_of']);
            $allowDraws = array_key_exists('allow_draws_override', $rule ?? [])
                && $rule['allow_draws_override'] !== null
                ? (bool) $rule['allow_draws_override']
                : (bool) $runtime['allow_draws'];

            $matches[] = [
                'id' =>
                'SW-R'
                    .
                    $roundNumber
                    .
                    '-M'
                    .
                    ($index + 1),

                'participant_a_id' =>
                $pairing[0],

                'participant_b_id' =>
                $pairing[1],

                'score_a' =>
                null,

                'score_b' =>
                null,

                'winner_id' =>
                null,

                'series_format' =>
                'BEST_OF',

                'best_of' =>
                $bestOf,

                'fixed_games' =>
                1,

                'allow_draws' =>
                $allowDraws,

                'status' =>
                'PENDING',
            ];
        }

        $runtime['rounds'][] = [
            'number' =>
            $roundNumber,

            'label' =>
            'Ronda Swiss '
                .
                $roundNumber,

            'status' =>
            'RUNNING',

            'bye_participant_id' =>
            $byeId,

            'matches' =>
            $matches,
        ];

        $runtime['current_round'] =
            $roundNumber;

        $runtime['matches_total'] +=
            count(
                $matches
            );

        if ($byeId) {
            $runtime['records'][$byeId]['bye_count']++;

            $runtime['records'][$byeId]['points'] +=
                $runtime['points']['bye'];

            $runtime['records'][$byeId]['pairing_score'] +=
                $runtime['points']['bye'];

            $runtime['records'][$byeId]['cumulative_score'] +=
                $runtime['records'][$byeId]['points'];
        }

        return $runtime;
    }

    private function applyCompletedRound(
        array $runtime,
        int $roundIndex
    ): array {
        $round =
            $runtime['rounds'][$roundIndex];

        foreach (
            $round['matches']
            as
            $match
        ) {
            $left =
                &$runtime['records'][$match['participant_a_id']];

            $right =
                &$runtime['records'][$match['participant_b_id']];

            $left['played']++;
            $right['played']++;

            $left['opponents'][] =
                $right['participant_id'];

            $right['opponents'][] =
                $left['participant_id'];

            $left['score_for'] +=
                $match['score_a'];

            $left['score_against'] +=
                $match['score_b'];

            $right['score_for'] +=
                $match['score_b'];

            $right['score_against'] +=
                $match['score_a'];

            if (
                $match['score_a']
                ===
                $match['score_b']
            ) {
                $left['draws']++;
                $right['draws']++;

                $left['points'] +=
                    $runtime['points']['draw'];

                $right['points'] +=
                    $runtime['points']['draw'];
            } elseif (
                $match['score_a']
                >
                $match['score_b']
            ) {
                $left['wins']++;
                $right['losses']++;

                $left['points'] +=
                    $runtime['points']['win'];

                $right['points'] +=
                    $runtime['points']['loss'];
            } else {
                $right['wins']++;
                $left['losses']++;

                $right['points'] +=
                    $runtime['points']['win'];

                $left['points'] +=
                    $runtime['points']['loss'];
            }

            $left['pairing_score'] =
                $left['points'];

            $right['pairing_score'] =
                $right['points'];

            $left['cumulative_score'] +=
                $left['points'];

            $right['cumulative_score'] +=
                $right['points'];

            unset(
                $left,
                $right
            );
        }

        return $this->recalculateStandings(
            $runtime
        );
    }

    private function recalculateStandings(
        array $runtime
    ): array {
        foreach (
            $runtime['records']
            as
            &$record
        ) {
            $record['score_difference'] =
                $record['score_for']
                -
                $record['score_against'];

            $opponentScores =
                collect(
                    $record['opponents']
                )
                ->map(
                    fn($opponentId) =>
                    $runtime['records'][$opponentId]['points']
                        ??
                        0
                )
                ->values();

            $record['opponent_score_sum'] =
                $opponentScores->sum();

            $cutRule = collect($runtime['tiebreakers'])
                ->firstWhere('criterion', 'OPPONENT_SCORE_CUT_LOWEST');
            $cut = max(0, (int) ($cutRule['parameter_int'] ?? 1));
            $record['opponent_score_cut_lowest'] = $opponentScores
                ->sort()
                ->values()
                ->slice($cut)
                ->sum();

            $games = $this->gameMetrics($runtime, $record['participant_id']);
            $record['game_wins'] = $games['game_wins'];
            $record['game_losses'] = $games['game_losses'];
            $record['game_difference'] = $games['game_difference'];

            $record['sonneborn_berger'] =
                $this->sonnebornBerger(
                    $record,
                    $runtime
                );
        }

        unset($record);

        $standings =
            collect(
                $runtime['records']
            )
            ->sort(
                fn($left, $right) =>
                $this->compareRecords(
                    $left,
                    $right,
                    $runtime
                )
            )
            ->values()
            ->all();

        foreach (
            $standings
            as
            $index =>
            &$record
        ) {
            $record['position'] =
                $index + 1;

            $runtime['records'][$record['participant_id']]['position'] =
                $index + 1;
        }

        unset($record);

        $runtime['standings'] =
            $standings;

        return $runtime;
    }

    private function applyDynamicRules(
        array $runtime
    ): array {
        if (($runtime['completion_mode'] ?? null) === 'RECORD_THRESHOLDS') {
            $qualificationRule = collect($runtime['advancement_rules'])
                ->firstWhere('rule_type', 'WIN_THRESHOLD');
            $eliminationRule = collect($runtime['advancement_rules'])
                ->firstWhere('rule_type', 'LOSS_THRESHOLD');

            foreach ($runtime['records'] as $participantId => &$record) {
                if ($record['status'] !== 'ACTIVE') {
                    continue;
                }

                if (
                    $runtime['qualification_wins'] !== null
                    && $record['wins'] >= (int) $runtime['qualification_wins']
                ) {
                    $record['status'] = 'QUALIFIED';
                    $record['exit_id'] = $qualificationRule['exit_id'] ?? null;
                    $record['exit_name'] = $qualificationRule['exit_name'] ?? 'Clasificados';
                    continue;
                }

                if (
                    $runtime['elimination_losses'] !== null
                    && $record['losses'] >= (int) $runtime['elimination_losses']
                ) {
                    $record['status'] = 'ELIMINATED';
                    $record['exit_id'] = $eliminationRule['exit_id'] ?? null;
                    $record['exit_name'] = $eliminationRule['exit_name'] ?? 'Eliminados';
                }
            }
            unset($record);
        }

        foreach (
            $runtime['advancement_rules']
            as
            $rule
        ) {
            if (
                ! in_array(
                    $rule['rule_type'],
                    [
                        'WIN_THRESHOLD',
                        'LOSS_THRESHOLD',
                        'EXACT_RECORD',
                    ],
                    true
                )
            ) {
                continue;
            }

            foreach (
                $runtime['records']
                as
                $participantId =>
                &$record
            ) {
                if (
                    $record['status']
                    !==
                    'ACTIVE'
                ) {
                    continue;
                }

                $matches =
                    match ($rule['rule_type']) {
                        'WIN_THRESHOLD' =>
                        $record['wins']
                            >=
                            (int)
                            $rule['threshold_wins'],

                        'LOSS_THRESHOLD' =>
                        $record['losses']
                            >=
                            (int)
                            $rule['threshold_losses'],

                        'EXACT_RECORD' =>
                        $record['wins']
                            ===
                            (int)
                            $rule['record_wins']
                            &&
                            $record['draws']
                            ===
                            (int)
                            $rule['record_draws']
                            &&
                            $record['losses']
                            ===
                            (int)
                            $rule['record_losses'],

                        default =>
                        false,
                    };

                if (! $matches) {
                    continue;
                }

                $record['status'] =
                    $rule['rule_type']
                    ===
                    'LOSS_THRESHOLD'
                    ? 'ELIMINATED'
                    : 'QUALIFIED';

                $record['exit_id'] =
                    $rule['exit_id'];

                $record['exit_name'] =
                    $rule['exit_name'];
            }

            unset($record);
        }

        $runtime['standings'] =
            collect(
                $runtime['standings']
            )
            ->map(
                fn($standing) =>
                $runtime['records'][$standing['participant_id']]
            )
            ->all();

        $outcomes = [];
        foreach ($runtime['records'] as $participantId => $record) {
            if ($record['status'] === 'ACTIVE' || ! $record['exit_id']) {
                continue;
            }

            $exitId = (int) $record['exit_id'];
            $outcomes[$exitId] ??= [
                'exit_id' => $exitId,
                'exit_name' => $record['exit_name'] ?? 'Salida',
                'participant_ids' => [],
            ];
            $outcomes[$exitId]['participant_ids'][] = $participantId;
        }

        $runtime['outcomes'] = array_values($outcomes);
        $runtime['survivor_ids'] = collect($runtime['records'])
            ->where('status', 'QUALIFIED')
            ->pluck('participant_id')
            ->values()
            ->all();
        $runtime['eliminated_ids'] = collect($runtime['records'])
            ->where('status', 'ELIMINATED')
            ->pluck('participant_id')
            ->values()
            ->all();

        return $runtime;
    }

    private function shouldFinish(
        array $runtime
    ): bool {
        if (
            $runtime['current_round']
            >=
            $runtime['round_limit']
        ) {
            return true;
        }

        return
            collect(
                $runtime['records']
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->count()
            <
            2;
    }

    private function complete(
        array $runtime
    ): array {
        $runtime =
            $this->recalculateStandings(
                $runtime
            );

        $activeIds = collect($runtime['records'])
            ->where('status', 'ACTIVE')
            ->pluck('participant_id')
            ->values()
            ->all();

        if (
            ($runtime['completion_mode'] ?? null) === 'RECORD_THRESHOLDS'
            && ($runtime['fallback_policy'] ?? 'FINAL_RANKING') === 'MANUAL_RESOLUTION'
            && $activeIds !== []
            && ! ($runtime['manual_fallback_resolved'] ?? false)
        ) {
            $finalRule = collect($runtime['advancement_rules'])
                ->first(fn($rule) => in_array(
                    $rule['rule_type'],
                    ['FINAL_TOP_N', 'FINAL_RANK_POSITION', 'FINAL_RANK_RANGE'],
                    true
                ));

            $required = match ($finalRule['rule_type'] ?? null) {
                'FINAL_TOP_N' => max(1, (int) ($finalRule['take'] ?? 1)),
                'FINAL_RANK_POSITION' => 1,
                'FINAL_RANK_RANGE' => max(1, (int) ($finalRule['rank_to'] ?? 1) - (int) ($finalRule['rank_from'] ?? 1) + 1),
                default => 1,
            };
            $required = min($required, count($activeIds));

            $runtime['status'] = 'AWAITING_DECISION';
            $runtime['manual_decision'] = [
                'id' => 'DEC-' . substr(hash('sha256', 'SWISS_FALLBACK:' . implode('|', $activeIds)), 0, 20),
                'scope' => 'ENGINE',
                'type' => 'SWISS_FALLBACK',
                'title' => 'Resolver participantes Swiss restantes',
                'description' => 'El máximo de rondas terminó sin resolver todos los récords. Selecciona quiénes clasifican.',
                'eligible_participant_ids' => $activeIds,
                'required_selection_count' => $required,
                'context' => [
                    'exit_id' => $finalRule['exit_id'] ?? null,
                    'exit_name' => $finalRule['exit_name'] ?? 'Clasificados',
                ],
            ];

            return $runtime;
        }

        $selected =
            collect(
                $runtime['records']
            )
            ->reject(
                fn($record) =>
                $record['status']
                    ===
                    'ACTIVE'
            )
            ->pluck(
                'participant_id'
            )
            ->flip()
            ->all();

        foreach (
            $runtime['advancement_rules']
            as
            $rule
        ) {
            if (
                in_array(
                    $rule['rule_type'],
                    [
                        'WIN_THRESHOLD',
                        'LOSS_THRESHOLD',
                        'EXACT_RECORD',
                    ],
                    true
                )
            ) {
                continue;
            }

            $available =
                collect(
                    $runtime['standings']
                )
                ->filter(
                    fn($record) =>
                    ! isset(
                        $selected[$record['participant_id']]
                    )
                )
                ->values();

            if (in_array($rule['rule_type'], ['FINAL_TOP_N', 'FINAL_BOTTOM_N'], true)) {
                $ordered = $rule['rule_type'] === 'FINAL_BOTTOM_N'
                    ? $available->reverse()->values()
                    : $available->values();
                $decisionKey = 'SWISS:CUTOFF:' . $rule['id'];
                $resolvedIds = $runtime['resolved_cutoffs'][$decisionKey] ?? null;

                if (is_array($resolvedIds)) {
                    $selection = $ordered
                        ->filter(fn($row) => in_array($row['participant_id'], $resolvedIds, true))
                        ->values();
                } else {
                    $resolved = $this->cutoffResolver->resolve(
                        $ordered->all(),
                        (int) $rule['take'],
                        $runtime['cutoff_tie_policy'] ?? 'USE_TIEBREAKERS',
                        fn(array $left, array $right): bool =>
                            $this->competitivelyTied($left, $right, $runtime),
                        $decisionKey,
                        'Resolver empate Swiss en el último cupo'
                    );

                    if ($resolved['decision'] !== null) {
                        $runtime['status'] = 'AWAITING_DECISION';
                        $runtime['manual_decision'] = $resolved['decision'];
                        return $runtime;
                    }

                    $selection = collect($resolved['selected']);
                }
            } else {
                $selection =
                    match ($rule['rule_type']) {
                        'FINAL_RANK_POSITION' =>
                        $available
                            ->where(
                                'position',
                                (int)
                                $rule['rank_from']
                            ),

                        'FINAL_RANK_RANGE' =>
                        $available
                            ->whereBetween(
                                'position',
                                [
                                    (int)
                                    $rule['rank_from'],
                                    (int)
                                    $rule['rank_to'],
                                ]
                            ),

                        'REMAINING' =>
                        $available,

                        default =>
                        collect(),
                    };
            }

            foreach (
                $selection
                as
                $record
            ) {
                $participantId =
                    $record['participant_id'];

                $selected[$participantId] =
                    true;

                $runtime['records'][$participantId]['status'] =
                    $rule['rule_type']
                    ===
                    'FINAL_BOTTOM_N'
                    ? 'ELIMINATED'
                    : 'QUALIFIED';

                $runtime['records'][$participantId]['exit_id'] =
                    $rule['exit_id'];

                $runtime['records'][$participantId]['exit_name'] =
                    $rule['exit_name'];
            }
        }

        $outcomes =
            [];

        foreach (
            $runtime['records']
            as
            $participantId =>
            &$record
        ) {
            if (
                $record['status']
                ===
                'ACTIVE'
            ) {
                $record['status'] =
                    'ELIMINATED';
            }

            $exitKey =
                $record['exit_id']
                ? 'EXIT_'
                .
                $record['exit_id']
                : 'NO_EXIT';

            $outcomes[$exitKey] ??= [
                'exit_id' =>
                $record['exit_id'],

                'exit_name' =>
                $record['exit_name']
                    ??
                    'Sin salida',

                'participant_ids' =>
                [],
            ];

            $outcomes[$exitKey]['participant_ids'][] =
                $participantId;
        }

        unset($record);

        $runtime['standings'] =
            collect(
                $runtime['standings']
            )
            ->map(
                fn($record) =>
                $runtime['records'][$record['participant_id']]
            )
            ->all();

        $runtime['outcomes'] =
            array_values(
                $outcomes
            );

        $runtime['survivor_ids'] =
            collect(
                $runtime['records']
            )
            ->where(
                'status',
                'QUALIFIED'
            )
            ->pluck(
                'participant_id'
            )
            ->all();

        $runtime['eliminated_ids'] =
            collect(
                $runtime['records']
            )
            ->where(
                'status',
                'ELIMINATED'
            )
            ->pluck(
                'participant_id'
            )
            ->all();

        $runtime['status'] =
            'COMPLETED';

        return $runtime;
    }

    private function pairParticipants(
        array $participantIds,
        array &$runtime
    ): array {
        $pairings =
            [];

        while ($participantIds !== []) {
            $left =
                array_shift(
                    $participantIds
                );

            $opponentIndex =
                $this->findOpponentIndex(
                    $left,
                    $participantIds,
                    $runtime
                );

            if ($opponentIndex === null) {
                if (
                    $runtime['rematch_policy']
                    ===
                    'STRICT_NO_REMATCH'
                ) {
                    $this->fail(
                        'No existe un emparejamiento Swiss válido sin repetir rivales.'
                    );
                }

                $opponentIndex =
                    0;

                $runtime['pairing_relaxations'][] = [
                    'round' =>
                    $runtime['current_round']
                        +
                        1,

                    'participant_id' =>
                    $left,

                    'message' =>
                    'Se permitió un rematch porque no existía otro emparejamiento posible.',
                ];
            }

            $right =
                $participantIds[$opponentIndex];

            array_splice(
                $participantIds,
                $opponentIndex,
                1
            );

            $pairings[] = [
                $left,
                $right,
            ];
        }

        return $pairings;
    }

    private function findOpponentIndex(
        string $left,
        array $candidates,
        array $runtime
    ): ?int {
        if (
            $runtime['rematch_policy']
            ===
            'ALLOW_REMATCH'
        ) {
            return
                isset($candidates[0])
                ? 0
                : null;
        }

        foreach (
            $candidates
            as
            $index =>
            $candidate
        ) {
            if (
                ! in_array(
                    $candidate,
                    $runtime['records'][$left]['opponents'],
                    true
                )
            ) {
                return $index;
            }
        }

        return null;
    }

    private function selectBye(
        array $activeIds,
        array $runtime
    ): ?string {
        if (
            $runtime['bye_policy']
            ===
            'DISABLED'
        ) {
            return null;
        }

        $eligible =
            collect(
                $activeIds
            )
            ->filter(
                fn($participantId) =>
                $runtime['records'][$participantId]['bye_count']
                    <
                    $runtime['max_byes_per_participant']
            );

        if ($eligible->isEmpty()) {
            return null;
        }

        if (
            $runtime['bye_policy']
            ===
            'RANDOM_ELIGIBLE'
        ) {
            return $eligible
                ->shuffle()
                ->first();
        }

        if (
            $runtime['bye_policy']
            ===
            'LOWEST_SEED_WITHOUT_BYE'
        ) {
            return $eligible
                ->sortByDesc(
                    fn($participantId) =>
                    $runtime['records'][$participantId]['seed']
                )
                ->first();
        }

        return $eligible
            ->sortByDesc(
                fn($participantId) =>
                $runtime['records'][$participantId]['position']
            )
            ->first();
    }

    private function compareRecords(
        array $left,
        array $right,
        array $runtime
    ): int {
        $criteria = [
            [
                'criterion' =>
                $runtime['pairing_basis']
                    ===
                    'WIN_LOSS_RECORD'
                    ? 'WINS'
                    : 'POINTS',

                'direction' =>
                'DESC',
            ],

            ...$runtime['tiebreakers'],

            [
                'criterion' =>
                'SEED',

                'direction' =>
                'ASC',
            ],
        ];

        $fieldMap = [
            'POINTS' =>
            'points',

            'WINS' =>
            'wins',

            'FEWEST_LOSSES' =>
            'losses',

            'OPPONENT_SCORE_SUM' =>
            'opponent_score_sum',

            'OPPONENT_SCORE_CUT_LOWEST' =>
            'opponent_score_cut_lowest',

            'SONNEBORN_BERGER' =>
            'sonneborn_berger',

            'CUMULATIVE_SCORE' =>
            'cumulative_score',

            'SCORE_DIFFERENCE' =>
            'score_difference',

            'SCORE_FOR' =>
            'score_for',

            'GAME_DIFFERENCE' =>
            'game_difference',

            'GAME_WINS' =>
            'game_wins',

            'SEED' =>
            'seed',
        ];

        foreach (
            $criteria
            as
            $criterion
        ) {
            if (($criterion['criterion'] ?? null) === 'HEAD_TO_HEAD') {
                $leftValue = $this->headToHeadScore(
                    $left['participant_id'],
                    $right['participant_id'],
                    $runtime
                );
                $rightValue = $this->headToHeadScore(
                    $right['participant_id'],
                    $left['participant_id'],
                    $runtime
                );

                if ($leftValue == $rightValue) {
                    continue;
                }

                return $rightValue <=> $leftValue;
            }

            $field =
                $fieldMap[$criterion['criterion']]
                ??
                null;

            if (
                ! $field
                ||
                ($left[$field] ?? 0)
                ==
                ($right[$field] ?? 0)
            ) {
                continue;
            }

            return
                $criterion['direction']
                ===
                'ASC'
                ? $left[$field]
                <=>
                $right[$field]
                : $right[$field]
                <=>
                $left[$field];
        }

        return strcmp(
            $left['participant_id'],
            $right['participant_id']
        );
    }

    private function sonnebornBerger(
        array $record,
        array $runtime
    ): float {
        $score =
            0;

        foreach (
            $runtime['rounds']
            as
            $round
        ) {
            foreach (
                $round['matches']
                as
                $match
            ) {
                if (
                    $match['status']
                    !==
                    'COMPLETED'
                ) {
                    continue;
                }

                if (
                    $match['participant_a_id']
                    ===
                    $record['participant_id']
                ) {
                    $opponentId =
                        $match['participant_b_id'];

                    $factor =
                        $match['score_a']
                        ===
                        $match['score_b']
                        ? 0.5
                        : (
                            $match['score_a']
                            >
                            $match['score_b']
                            ? 1
                            : 0
                        );
                } elseif (
                    $match['participant_b_id']
                    ===
                    $record['participant_id']
                ) {
                    $opponentId =
                        $match['participant_a_id'];

                    $factor =
                        $match['score_a']
                        ===
                        $match['score_b']
                        ? 0.5
                        : (
                            $match['score_b']
                            >
                            $match['score_a']
                            ? 1
                            : 0
                        );
                } else {
                    continue;
                }

                $score +=
                    (
                        $runtime['records'][$opponentId]['points']
                        ??
                        0
                    )
                    *
                    $factor;
            }
        }

        return $score;
    }

    private function roundRuleForMatch(
        array $runtime,
        int $roundNumber,
        string $leftId,
        string $rightId
    ): ?array {
        $left = $runtime['records'][$leftId];
        $right = $runtime['records'][$rightId];

        return collect($runtime['round_rules'])
            ->first(function (array $rule) use ($runtime, $roundNumber, $left, $right): bool {
                return match ($rule['trigger_type']) {
                    'ROUND_NUMBER' =>
                        (int) ($rule['round_number'] ?? 0) === $roundNumber,

                    'EXACT_RECORD' =>
                        $this->recordMatchesRule($left, $rule)
                        || $this->recordMatchesRule($right, $rule),

                    'QUALIFICATION_MATCH' =>
                        ($runtime['completion_mode'] ?? null) === 'RECORD_THRESHOLDS'
                        && $runtime['qualification_wins'] !== null
                        && (
                            $left['wins'] + 1 >= (int) $runtime['qualification_wins']
                            || $right['wins'] + 1 >= (int) $runtime['qualification_wins']
                        ),

                    'ELIMINATION_MATCH' =>
                        ($runtime['completion_mode'] ?? null) === 'RECORD_THRESHOLDS'
                        && $runtime['elimination_losses'] !== null
                        && (
                            $left['losses'] + 1 >= (int) $runtime['elimination_losses']
                            || $right['losses'] + 1 >= (int) $runtime['elimination_losses']
                        ),

                    'QUALIFICATION_OR_ELIMINATION' =>
                        ($runtime['completion_mode'] ?? null) === 'RECORD_THRESHOLDS'
                        && (
                            (
                                $runtime['qualification_wins'] !== null
                                && (
                                    $left['wins'] + 1 >= (int) $runtime['qualification_wins']
                                    || $right['wins'] + 1 >= (int) $runtime['qualification_wins']
                                )
                            )
                            || (
                                $runtime['elimination_losses'] !== null
                                && (
                                    $left['losses'] + 1 >= (int) $runtime['elimination_losses']
                                    || $right['losses'] + 1 >= (int) $runtime['elimination_losses']
                                )
                            )
                        ),

                    default => false,
                };
            });
    }

    private function recordMatchesRule(array $record, array $rule): bool
    {
        return
            $record['wins'] === (int) ($rule['record_wins'] ?? -1)
            && $record['draws'] === (int) ($rule['record_draws'] ?? -1)
            && $record['losses'] === (int) ($rule['record_losses'] ?? -1);
    }

    private function competitivelyTied(
        array $left,
        array $right,
        array $runtime
    ): bool {
        $criteria = [
            $runtime['pairing_basis'] === 'WIN_LOSS_RECORD' ? 'WINS' : 'POINTS',
            ...collect($runtime['tiebreakers'])->pluck('criterion')->all(),
        ];

        foreach ($criteria as $criterion) {
            if ($criterion === 'SEED') {
                continue;
            }

            if ($criterion === 'HEAD_TO_HEAD') {
                $leftValue = $this->headToHeadScore($left['participant_id'], $right['participant_id'], $runtime);
                $rightValue = $this->headToHeadScore($right['participant_id'], $left['participant_id'], $runtime);
            } else {
                $field = match ($criterion) {
                    'POINTS' => 'points',
                    'WINS' => 'wins',
                    'FEWEST_LOSSES' => 'losses',
                    'OPPONENT_SCORE_SUM' => 'opponent_score_sum',
                    'OPPONENT_SCORE_CUT_LOWEST' => 'opponent_score_cut_lowest',
                    'SONNEBORN_BERGER' => 'sonneborn_berger',
                    'CUMULATIVE_SCORE' => 'cumulative_score',
                    'SCORE_DIFFERENCE' => 'score_difference',
                    'SCORE_FOR' => 'score_for',
                    'GAME_DIFFERENCE' => 'game_difference',
                    'GAME_WINS' => 'game_wins',
                    default => null,
                };

                if (! $field) {
                    continue;
                }

                $leftValue = $left[$field] ?? 0;
                $rightValue = $right[$field] ?? 0;
            }

            if ($leftValue != $rightValue) {
                return false;
            }
        }

        return true;
    }

    private function gameMetrics(array $runtime, string $participantId): array
    {
        $wins = 0;
        $losses = 0;

        foreach ($runtime['series'] ?? [] as $series) {
            if (($series['participant_a_id'] ?? null) === $participantId) {
                $wins += (int) ($series['game_wins_a'] ?? 0);
                $losses += (int) ($series['game_wins_b'] ?? 0);
            } elseif (($series['participant_b_id'] ?? null) === $participantId) {
                $wins += (int) ($series['game_wins_b'] ?? 0);
                $losses += (int) ($series['game_wins_a'] ?? 0);
            }
        }

        return [
            'game_wins' => $wins,
            'game_losses' => $losses,
            'game_difference' => $wins - $losses,
        ];
    }

    private function headToHeadScore(
        string $participantId,
        string $opponentId,
        array $runtime
    ): float {
        $score = 0.0;

        foreach ($runtime['rounds'] as $round) {
            foreach ($round['matches'] as $match) {
                if (($match['status'] ?? null) !== 'COMPLETED') {
                    continue;
                }

                $isA = $match['participant_a_id'] === $participantId
                    && $match['participant_b_id'] === $opponentId;
                $isB = $match['participant_b_id'] === $participantId
                    && $match['participant_a_id'] === $opponentId;

                if (! $isA && ! $isB) {
                    continue;
                }

                $mine = $isA ? $match['score_a'] : $match['score_b'];
                $theirs = $isA ? $match['score_b'] : $match['score_a'];
                $score += $mine === $theirs
                    ? (float) $runtime['points']['draw']
                    : ($mine > $theirs
                        ? (float) $runtime['points']['win']
                        : (float) $runtime['points']['loss']);
            }
        }

        return $score;
    }

    private function firstRoundOrder(
        array $participantIds,
        string $mode
    ): array {
        if (
            $mode
            ===
            'RANDOM'
        ) {
            shuffle(
                $participantIds
            );
        }

        if (
            $mode
            ===
            'TOP_VS_BOTTOM'
        ) {
            $result =
                [];

            while ($participantIds !== []) {
                $result[] =
                    array_shift(
                        $participantIds
                    );

                if ($participantIds !== []) {
                    $result[] =
                        array_pop(
                            $participantIds
                        );
                }
            }

            return $result;
        }

        if (
            $mode
            ===
            'SEEDED_HALVES'
        ) {
            $half =
                (int)
                ceil(
                    count($participantIds)
                        /
                        2
                );

            $top =
                array_slice(
                    $participantIds,
                    0,
                    $half
                );

            $bottom =
                array_slice(
                    $participantIds,
                    $half
                );

            $result =
                [];

            foreach (
                $top
                as
                $index =>
                $participantId
            ) {
                $result[] =
                    $participantId;

                if (
                    isset(
                        $bottom[$index]
                    )
                ) {
                    $result[] =
                        $bottom[$index];
                }
            }

            return $result;
        }

        return $participantIds;
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

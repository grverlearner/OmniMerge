<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseSwissSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\Swiss\SwissPairingCalculator;
use App\Services\Tournaments\Swiss\SwissValidator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SwissLabEngine
implements LabPhaseEngine
{
    public function __construct(
        private readonly
        SwissValidator $validator,

        private readonly
        SwissPairingCalculator $pairingCalculator
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

        $settings =
            $phase->swissSetting;

        if (! $settings) {
            $this->fail(
                'La fase no tiene configuración Swiss.'
            );
        }

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
            $this->fail(
                'La política de BYE manual todavía necesita una selección explícita antes de ejecutar el Lab.'
            );
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

        $bestOf =
            $this->bestOfForRound(
                $runtime,
                $roundNumber
            );

        $allowDraws =
            $this->allowDrawsForRound(
                $runtime,
                $roundNumber
            );

        foreach (
            $pairings
            as
            $index =>
            $pairing
        ) {
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

                'best_of' =>
                $bestOf,

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

            $selection =
                match ($rule['rule_type']) {
                    'FINAL_TOP_N' =>
                    $available
                        ->take(
                            (int)
                            $rule['take']
                        ),

                    'FINAL_BOTTOM_N' =>
                    $available
                        ->reverse()
                        ->take(
                            (int)
                            $rule['take']
                        ),

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
            'opponent_score_sum',

            'SONNEBORN_BERGER' =>
            'sonneborn_berger',

            'CUMULATIVE_SCORE' =>
            'cumulative_score',

            'SCORE_DIFFERENCE' =>
            'score_difference',

            'SCORE_FOR' =>
            'score_for',

            'GAME_DIFFERENCE' =>
            'score_difference',

            'GAME_WINS' =>
            'score_for',

            'SEED' =>
            'seed',
        ];

        foreach (
            $criteria
            as
            $criterion
        ) {
            $field =
                $fieldMap[$criterion['criterion']]
                ??
                null;

            if (
                ! $field
                ||
                $left[$field]
                ==
                $right[$field]
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

    private function bestOfForRound(
        array $runtime,
        int $roundNumber
    ): int {
        $rule =
            collect(
                $runtime['round_rules']
            )
            ->first(
                fn($rule) =>
                $rule['trigger_type']
                    ===
                    'ROUND_NUMBER'
                    &&
                    (int)
                    $rule['round_number']
                    ===
                    $roundNumber
            );

        return
            $rule['best_of']
            ??
            $runtime['default_best_of'];
    }

    private function allowDrawsForRound(
        array $runtime,
        int $roundNumber
    ): bool {
        $rule =
            collect(
                $runtime['round_rules']
            )
            ->first(
                fn($rule) =>
                $rule['trigger_type']
                    ===
                    'ROUND_NUMBER'
                    &&
                    (int)
                    $rule['round_number']
                    ===
                    $roundNumber
            );

        return
            $rule['allow_draws_override']
            ??
            $runtime['allow_draws'];
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

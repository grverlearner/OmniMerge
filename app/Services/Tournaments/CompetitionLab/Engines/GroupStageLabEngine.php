<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Runtime\CutoffPolicyResolver;
use App\Services\Tournaments\GroupStage\GroupStageAllocator;
use App\Services\Tournaments\GroupStage\GroupStageSettingsService;
use Illuminate\Validation\ValidationException;

class GroupStageLabEngine
implements LabPhaseEngine, SupportsManualDecision
{
    public function __construct(
        private readonly
        GroupStageAllocator $allocator,

        private readonly
        CutoffPolicyResolver $cutoffResolver,

        private readonly
        GroupStageSettingsService $settingsService
    ) {}

    public function supports(
        string $phaseType
    ): bool {
        return
            $phaseType
            ===
            'GROUP_STAGE';
    }

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array {
        $phase->loadMissing([
            'groupStageSetting',
            'groupStageGroups',
            'groupStageTiebreakers',
            'groupStageAdvancementRules.phaseExit',
            'groupStageAdvancementRules.group',
        ]);

        /*
         * Ver nota equivalente en RoundRobinLabEngine::prepare(): si
         * loadMissing() ya trajo la fila, se usa tal cual; ensure() solo
         * entra en juego cuando realmente no existe (fase nunca visitada en
         * su pestaña "Reglas", ej. colocada directamente como Node del
         * Tournament Graph).
         */
        $settings =
            $phase->groupStageSetting
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

        $allocation =
            $this->allocator
            ->allocate(
                $phase,
                $settings,
                $phase->groupStageGroups,
                count($participantIds)
            );

        if (! $allocation['valid']) {
            $this->fail(
                implode(
                    ' ',
                    $allocation['errors']
                        ??
                        [
                            'No fue posible distribuir los participantes.',
                        ]
                )
            );
        }

        $orderedIds =
            $this->orderParticipants(
                $participantIds,
                $settings->distribution_mode
            );

        $groups =
            [];

        $rounds =
            [];

        $cursor =
            0;

        foreach (
            $allocation['groups']
            as
            $groupIndex =>
            $blueprint
        ) {
            $memberSeeds =
                collect(
                    $blueprint['members']
                        ??
                        []
                )
                ->pluck('seed')
                ->filter()
                ->values();

            if ($memberSeeds->isNotEmpty()) {
                $groupParticipantIds =
                    $memberSeeds
                    ->map(
                        fn($seed) =>
                        $orderedIds[(int)
                            $seed
                            -
                            1]
                            ??
                            null
                    )
                    ->filter()
                    ->values()
                    ->all();
            } else {
                $groupParticipantIds =
                    array_slice(
                        $orderedIds,
                        $cursor,
                        (int)
                        $blueprint['size']
                    );
            }

            $cursor +=
                (int)
                $blueprint['size'];

            $groupKey =
                'GROUP_'
                .
                ($groupIndex + 1);

            $groupRounds =
                $this->schedule(
                    $groupParticipantIds,
                    (int)
                    $settings->internal_cycles,
                    (int)
                    $settings->internal_best_of,
                    $groupKey,
                    $blueprint['name'],
                    (string) ($settings->internal_series_format ?: 'BEST_OF'),
                    (int) ($settings->internal_fixed_games ?: 1)
                );

            $standings =
                $this->emptyStandings(
                    $groupParticipantIds,
                    $groupKey,
                    $blueprint['name'],
                    $participantIds
                );

            $groups[$groupKey] = [
                'id' =>
                $groupKey,

                'definition_id' =>
                $blueprint['definition_id'],

                'code' =>
                $blueprint['code'],

                'name' =>
                $blueprint['name'],

                'status' =>
                'RUNNING',

                'participant_ids' =>
                $groupParticipantIds,

                'round_numbers' =>
                collect($groupRounds)
                    ->pluck('number')
                    ->all(),

                'standings' =>
                $standings,
            ];

            $rounds = [
                ...$rounds,
                ...$groupRounds,
            ];
        }

        $tiebreakers =
            $phase
            ->groupStageTiebreakers
            ->map(
                fn($tiebreaker) => [
                    'criterion' =>
                    $tiebreaker->criterion,

                    'normalization' =>
                    $tiebreaker->normalization,

                    'direction' =>
                    $tiebreaker
                        ->effective_direction,
                ]
            )
            ->all();

        $advancementRules =
            $phase
            ->groupStageAdvancementRules
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

                    'group_definition_id' =>
                    $rule->phase_group_stage_group_id
                        ? (int)
                        $rule->phase_group_stage_group_id
                        : null,

                    'rule_type' =>
                    $rule->rule_type,

                    'position_from' =>
                    $rule->position_from,

                    'position_to' =>
                    $rule->position_to,

                    'take' =>
                    $rule->take,

                    'sort_order' =>
                    (int)
                    $rule->sort_order,
                ]
            )
            ->sortBy('sort_order')
            ->values()
            ->all();

        return [
            'engine' =>
            'GROUP_STAGE',

            'status' =>
            'RUNNING',

            'allow_draws' =>
            (bool)
            $settings->internal_allow_draws,

            'points' => [
                'win' =>
                (float)
                $settings->internal_win_points,

                'draw' =>
                (float)
                $settings->internal_draw_points,

                'loss' =>
                (float)
                $settings->internal_loss_points,
            ],

            'cross_group_normalization' =>
            $settings->cross_group_normalization,

            'cutoff_tie_policy' =>
            $settings->cutoff_tie_policy,

            'resolved_cutoffs' =>
            [],

            'groups' =>
            $groups,

            'rounds' =>
            $rounds,

            'standings' =>
            collect($groups)
                ->flatMap(
                    fn($group) =>
                    $group['standings']
                )
                ->values()
                ->all(),

            'tiebreakers' =>
            $tiebreakers,

            'advancement_rules' =>
            $advancementRules,

            'outcomes' =>
            [],

            'rule_results' =>
            [],

            'survivor_ids' =>
            [],

            'eliminated_ids' =>
            [],

            'matches_total' =>
            collect($rounds)
                ->sum(
                    fn($round) =>
                    count(
                        $round['matches']
                    )
                ),

            'matches_completed' =>
            0,

            'current_round' =>
            1,
        ];
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
                'La fase de grupos ya terminó.'
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
            $scoreA === $scoreB
            &&
            ! $runtime['allow_draws']
        ) {
            $this->fail(
                'Esta fase de grupos no permite empates.'
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
                'El encuentro no existe en esta fase de grupos.'
            );
        }

        $runtime =
            $this->recalculate(
                $runtime
            );

        if (
            $runtime['matches_completed']
            ===
            $runtime['matches_total']
        ) {
            $runtime =
                $this->complete(
                    $runtime
                );
        }

        return $runtime;
    }

    private function recalculate(
        array $runtime
    ): array {
        foreach (
            $runtime['groups']
            as
            $groupKey =>
            &$group
        ) {
            $standings =
                $this->emptyStandings(
                    $group['participant_ids'],
                    $groupKey,
                    $group['name'],
                    $group['participant_ids']
                );

            $rows =
                collect($standings)
                ->keyBy('participant_id')
                ->all();

            foreach (
                $runtime['rounds']
                as
                &$round
            ) {
                if (
                    $round['group_id']
                    !==
                    $groupKey
                ) {
                    continue;
                }

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

                    $left =
                        &$rows[$match['participant_a_id']];

                    $right =
                        &$rows[$match['participant_b_id']];

                    $left['played']++;
                    $right['played']++;

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

                    unset(
                        $left,
                        $right
                    );
                }

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

                if (! $hasPending) {
                    $round['status'] =
                        'COMPLETED';
                }
            }

            unset($round);

            foreach (
                $rows
                as
                &$row
            ) {
                $row['score_difference'] =
                    $row['score_for']
                    -
                    $row['score_against'];

                $games = $this->gameMetrics($runtime, $row['participant_id']);
                $row['game_wins'] = $games['game_wins'];
                $row['game_losses'] = $games['game_losses'];
                $row['game_difference'] = $games['game_difference'];
            }

            unset($row);

            uasort(
                $rows,
                fn($left, $right) =>
                $this->compareRows(
                    $left,
                    $right,
                    $runtime['tiebreakers']
                )
            );

            $position =
                1;

            foreach (
                $rows
                as
                &$row
            ) {
                $row['position'] =
                    $position++;
            }

            unset($row);

            $group['standings'] =
                array_values(
                    $rows
                );

            $groupRounds =
                collect(
                    $runtime['rounds']
                )
                ->where(
                    'group_id',
                    $groupKey
                );

            $group['status'] =
                $groupRounds
                ->every(
                    fn($round) =>
                    $round['status']
                        ===
                        'COMPLETED'
                )
                ? 'COMPLETED'
                : 'RUNNING';
        }

        unset($group);

        $runtime['standings'] =
            collect(
                $runtime['groups']
            )
            ->flatMap(
                fn($group) =>
                $group['standings']
            )
            ->values()
            ->all();

        $pendingRound =
            collect(
                $runtime['rounds']
            )
            ->first(
                fn($round) =>
                $round['status']
                    !==
                    'COMPLETED'
            );

        $runtime['current_round'] =
            $pendingRound['number']
            ??
            count(
                $runtime['rounds']
            );

        return $runtime;
    }

    private function complete(
        array $runtime
    ): array {
        $selected =
            [];

        $outcomes =
            [];

        $ruleResults =
            [];

        foreach (
            $runtime['advancement_rules']
            as
            $rule
        ) {
            $eligible =
                collect(
                    $runtime['standings']
                )
                ->reject(
                    fn($row) =>
                    isset(
                        $selected[$row['participant_id']]
                    )
                )
                ->values();

            $selection =
                $this->selectForRule(
                    $eligible,
                    $runtime['groups'],
                    $runtime,
                    $rule
                );

            $cutoffPool = $this->cutoffPoolForRule(
                $eligible,
                $runtime['groups'],
                $runtime,
                $rule
            );

            if ($cutoffPool !== null && (int) ($rule['take'] ?? 0) > 0) {
                $decisionKey = 'GROUP_STAGE:CUTOFF:' . $rule['id'];
                $resolvedIds = $runtime['resolved_cutoffs'][$decisionKey] ?? null;

                if (is_array($resolvedIds)) {
                    $selection = $cutoffPool
                        ->filter(fn($row) => in_array($row['participant_id'], $resolvedIds, true))
                        ->values();
                } else {
                    $resolved = $this->cutoffResolver->resolve(
                        $cutoffPool->all(),
                        (int) $rule['take'],
                        $runtime['cutoff_tie_policy'] ?? 'USE_TIEBREAKERS',
                        fn(array $left, array $right): bool =>
                            $this->competitivelyTied($left, $right, $runtime),
                        $decisionKey,
                        'Resolver empate entre grupos'
                    );

                    if ($resolved['decision'] !== null) {
                        $runtime['status'] = 'AWAITING_DECISION';
                        $runtime['manual_decision'] = $resolved['decision'];
                        return $runtime;
                    }

                    $selection = collect($resolved['selected']);
                }
            }

            $exitKey =
                $rule['exit_id']
                ? 'EXIT_'
                .
                $rule['exit_id']
                : 'NO_EXIT';

            $outcomes[$exitKey] ??= [
                'exit_id' =>
                $rule['exit_id'],

                'exit_name' =>
                $rule['exit_name'],

                'participant_ids' =>
                [],
            ];

            foreach (
                $selection
                as
                $row
            ) {
                $participantId =
                    $row['participant_id'];

                $selected[$participantId] =
                    true;

                $outcomes[$exitKey]['participant_ids'][] =
                    $participantId;
            }

            $ruleResults[] = [
                'rule_id' =>
                $rule['id'],

                'rule_type' =>
                $rule['rule_type'],

                'exit_id' =>
                $rule['exit_id'],

                'selected_ids' =>
                $selection
                    ->pluck('participant_id')
                    ->values()
                    ->all(),
            ];
        }

        $allIds =
            collect(
                $runtime['standings']
            )
            ->pluck(
                'participant_id'
            )
            ->all();

        $unselected =
            array_values(
                array_diff(
                    $allIds,
                    array_keys($selected)
                )
            );

        $runtime['outcomes'] =
            array_values(
                $outcomes
            );

        $runtime['rule_results'] =
            $ruleResults;

        $runtime['survivor_ids'] =
            array_values(
                array_keys($selected)
            );

        $runtime['eliminated_ids'] =
            $unselected;

        $runtime['status'] =
            'COMPLETED';

        return $runtime;
    }

    private function selectForRule(
        $eligible,
        array $groups,
        array $runtime,
        array $rule
    ) {
        $type =
            $rule['rule_type'];

        if (
            $type
            ===
            'REMAINING'
        ) {
            return $eligible;
        }

        if (
            str_starts_with(
                $type,
                'EACH_GROUP_'
            )
        ) {
            return $eligible
                ->filter(
                    function (
                        $row
                    ) use (
                        $type,
                        $rule,
                        $groups
                    ) {
                        $groupSize =
                            count(
                                $groups[$row['group_id']]['participant_ids']
                            );

                        return match ($type) {
                            'EACH_GROUP_TOP_N' =>
                            $row['position']
                                <=
                                (int)
                                $rule['take'],

                            'EACH_GROUP_BOTTOM_N' =>
                            $row['position']
                                >
                                $groupSize
                                -
                                (int)
                                $rule['take'],

                            'EACH_GROUP_POSITION' =>
                            $row['position']
                                ===
                                (int)
                                $rule['position_from'],

                            'EACH_GROUP_RANGE' =>
                            $row['position']
                                >=
                                (int)
                                $rule['position_from']
                                &&
                                $row['position']
                                <=
                                (int)
                                $rule['position_to'],

                            default =>
                            false,
                        };
                    }
                )
                ->values();
        }

        if (
            in_array(
                $type,
                [
                    'SPECIFIC_GROUP_POSITION',
                    'SPECIFIC_GROUP_RANGE',
                ],
                true
            )
        ) {
            return $eligible
                ->filter(
                    function (
                        $row
                    ) use (
                        $type,
                        $rule,
                        $groups
                    ) {
                        $group =
                            $groups[$row['group_id']];

                        if (
                            (int)
                            $group['definition_id']
                            !==
                            (int)
                            $rule['group_definition_id']
                        ) {
                            return false;
                        }

                        if (
                            $type
                            ===
                            'SPECIFIC_GROUP_POSITION'
                        ) {
                            return
                                $row['position']
                                ===
                                (int)
                                $rule['position_from'];
                        }

                        return
                            $row['position']
                            >=
                            (int)
                            $rule['position_from']
                            &&
                            $row['position']
                            <=
                            (int)
                            $rule['position_to'];
                    }
                )
                ->values();
        }

        if (
            in_array(
                $type,
                [
                    'CROSS_GROUP_POSITION_TOP_N',
                    'CROSS_GROUP_POSITION_BOTTOM_N',
                ],
                true
            )
        ) {
            $selection =
                $eligible
                ->where(
                    'position',
                    (int)
                    $rule['position_from']
                )
                ->values();

            $selection =
                $this->sortCrossGroup(
                    $selection,
                    $runtime
                );

            if (
                $type
                ===
                'CROSS_GROUP_POSITION_BOTTOM_N'
            ) {
                $selection =
                    $selection
                    ->reverse()
                    ->values();
            }

            return $selection
                ->take(
                    (int)
                    $rule['take']
                )
                ->values();
        }

        $selection =
            $this->sortCrossGroup(
                $eligible,
                $runtime
            );

        if (
            $type
            ===
            'WORST_REMAINING'
        ) {
            $selection =
                $selection
                ->reverse()
                ->values();
        }

        if (
            in_array(
                $type,
                [
                    'BEST_REMAINING',
                    'WORST_REMAINING',
                ],
                true
            )
        ) {
            return $selection
                ->take(
                    (int)
                    $rule['take']
                )
                ->values();
        }

        return collect();
    }

    private function sortCrossGroup(
        $rows,
        array $runtime
    ) {
        return $rows
            ->sort(
                fn($left, $right) =>
                $this->compareRows(
                    $left,
                    $right,
                    $runtime['tiebreakers'],
                    true,
                    $runtime['cross_group_normalization']
                )
            )
            ->values();
    }

    private function compareRows(
        array $left,
        array $right,
        array $tiebreakers,
        bool $crossGroup = false,
        string $normalization = 'RAW'
    ): int {
        $criteria = [
            [
                'criterion' =>
                'POINTS',

                'direction' =>
                'DESC',

                'normalization' =>
                'DEFAULT',
            ],

            ...$tiebreakers,

            [
                'criterion' =>
                'SEED',

                'direction' =>
                'ASC',

                'normalization' =>
                'RAW',
            ],
        ];

        $fieldMap = [
            'POINTS' =>
            'points',

            'WINS' =>
            'wins',

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
            $field =
                $fieldMap[$criterion['criterion']]
                ??
                null;

            if (! $field) {
                continue;
            }

            $leftValue =
                $left[$field];

            $rightValue =
                $right[$field];

            $effectiveNormalization =
                $criterion['normalization']
                ===
                'DEFAULT'
                ? $normalization
                : $criterion['normalization'];

            if (
                $crossGroup
                &&
                $effectiveNormalization
                ===
                'PER_MATCH'
                &&
                in_array(
                    $field,
                    [
                        'points',
                        'wins',
                        'score_difference',
                        'score_for',
                    ],
                    true
                )
            ) {
                $leftValue =
                    $left['played'] > 0
                    ? $leftValue
                    /
                    $left['played']
                    : 0;

                $rightValue =
                    $right['played'] > 0
                    ? $rightValue
                    /
                    $right['played']
                    : 0;
            }

            if (
                $leftValue
                ==
                $rightValue
            ) {
                continue;
            }

            return
                $criterion['direction']
                ===
                'ASC'
                ? $leftValue
                <=>
                $rightValue
                : $rightValue
                <=>
                $leftValue;
        }

        return strcmp(
            $left['participant_id'],
            $right['participant_id']
        );
    }

    private function emptyStandings(
        array $groupParticipantIds,
        string $groupKey,
        string $groupName,
        array $seedOrder
    ): array {
        $rows =
            [];

        foreach (
            $groupParticipantIds
            as
            $index =>
            $participantId
        ) {
            $globalSeed =
                array_search(
                    $participantId,
                    $seedOrder,
                    true
                );

            $rows[] = [
                'participant_id' =>
                $participantId,

                'group_id' =>
                $groupKey,

                'group_name' =>
                $groupName,

                'seed' =>
                $globalSeed === false
                    ? $index + 1
                    : $globalSeed + 1,

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

                'score_for' =>
                0,

                'score_against' =>
                0,

                'score_difference' =>
                0,

                'position' =>
                $index + 1,
            ];
        }

        return $rows;
    }

    private function schedule(
        array $participantIds,
        int $cycles,
        int $bestOf,
        string $groupKey,
        string $groupName,
        string $seriesFormat = 'BEST_OF',
        int $fixedGames = 1
    ): array {
        $rotation =
            $participantIds;

        if (
            count($rotation)
            %
            2
            !==
            0
        ) {
            $rotation[] =
                null;
        }

        $slotCount =
            count(
                $rotation
            );

        $rounds =
            [];

        for (
            $cycle = 1;
            $cycle <= max(1, $cycles);
            $cycle++
        ) {
            $cycleRotation =
                $rotation;

            for (
                $turn = 1;
                $turn < $slotCount;
                $turn++
            ) {
                $matches =
                    [];

                for (
                    $index = 0;
                    $index < $slotCount / 2;
                    $index++
                ) {
                    $left =
                        $cycleRotation[$index];

                    $right =
                        $cycleRotation[$slotCount
                            -
                            1
                            -
                            $index];

                    if (
                        ! $left
                        ||
                        ! $right
                    ) {
                        continue;
                    }

                    if (
                        $cycle
                        %
                        2
                        ===
                        0
                    ) {
                        [
                            $left,
                            $right,
                        ] = [
                            $right,
                            $left,
                        ];
                    }

                    $matches[] = [
                        'id' =>
                        'GS-'
                            .
                            $groupKey
                            .
                            '-R'
                            .
                            $turn
                            .
                            '-C'
                            .
                            $cycle
                            .
                            '-M'
                            .
                            (
                                count($matches)
                                +
                                1
                            ),

                        'participant_a_id' =>
                        $left,

                        'participant_b_id' =>
                        $right,

                        'score_a' =>
                        null,

                        'score_b' =>
                        null,

                        'winner_id' =>
                        null,

                        /* Formato decidido por la configuracion del grupo */
                        'series_format' =>
                        $seriesFormat,

                        'best_of' =>
                        $bestOf,

                        'fixed_games' =>
                        $fixedGames,

                        'status' =>
                        'PENDING',
                    ];
                }

                $rounds[] = [
                    'number' =>
                    count($rounds)
                        +
                        1,

                    'group_id' =>
                    $groupKey,

                    'group_name' =>
                    $groupName,

                    'cycle' =>
                    $cycle,

                    'label' =>
                    $groupName
                        .
                        ' · Jornada '
                        .
                        $turn
                        .
                        (
                            $cycles > 1
                            ? ' · Ciclo '
                            .
                            $cycle
                            : ''
                        ),

                    'status' =>
                    'PENDING',

                    'matches' =>
                    $matches,
                ];

                $fixed =
                    array_shift(
                        $cycleRotation
                    );

                $last =
                    array_pop(
                        $cycleRotation
                    );

                array_unshift(
                    $cycleRotation,
                    $fixed,
                    $last
                );
            }
        }

        return $rounds;
    }

    public function resolveManualDecision(
        array $runtime,
        array $payload
    ): array {
        $decision = $runtime['manual_decision'] ?? null;

        if (! is_array($decision) || ($payload['decision_id'] ?? null) !== ($decision['id'] ?? null)) {
            $this->fail('La decisión de grupos ya no corresponde al estado actual.');
        }

        if (! in_array($decision['type'] ?? '', ['CUTOFF_SELECTION', 'PLAYOFF_SELECTION'], true)) {
            $this->fail('La decisión pendiente no pertenece a un desempate de grupos.');
        }

        $eligible = array_values($decision['eligible_participant_ids'] ?? []);
        $selected = array_values(array_unique($payload['selected_participant_ids'] ?? []));
        $required = (int) ($decision['required_selection_count'] ?? 0);

        if (count($selected) !== $required) {
            $this->fail("Debes seleccionar exactamente {$required} participante(s).");
        }

        foreach ($selected as $participantId) {
            if (! in_array($participantId, $eligible, true)) {
                $this->fail('La selección contiene un participante no elegible.');
            }
        }

        $key = data_get($decision, 'context.decision_key');
        if (! $key) {
            $this->fail('El desempate no contiene una clave de resolución.');
        }

        $runtime['resolved_cutoffs'][$key] = array_values(array_unique([
            ...data_get($decision, 'context.guaranteed_participant_ids', []),
            ...$selected,
        ]));
        unset($runtime['manual_decision']);
        $runtime['status'] = 'RUNNING';

        return $this->complete($runtime);
    }

    private function cutoffPoolForRule(
        $eligible,
        array $groups,
        array $runtime,
        array $rule
    ) {
        $type = $rule['rule_type'];

        if (in_array($type, ['CROSS_GROUP_POSITION_TOP_N', 'CROSS_GROUP_POSITION_BOTTOM_N'], true)) {
            $pool = $this->sortCrossGroup(
                $eligible->where('position', (int) $rule['position_from'])->values(),
                $runtime
            );

            return $type === 'CROSS_GROUP_POSITION_BOTTOM_N'
                ? $pool->reverse()->values()
                : $pool;
        }

        if (in_array($type, ['BEST_REMAINING', 'WORST_REMAINING'], true)) {
            $pool = $this->sortCrossGroup($eligible, $runtime);
            return $type === 'WORST_REMAINING'
                ? $pool->reverse()->values()
                : $pool;
        }

        return null;
    }

    private function competitivelyTied(array $left, array $right, array $runtime): bool
    {
        $criteria = [
            ['criterion' => 'POINTS', 'direction' => 'DESC', 'normalization' => 'DEFAULT'],
            ...$runtime['tiebreakers'],
        ];

        foreach ($criteria as $criterion) {
            if (($criterion['criterion'] ?? null) === 'SEED') {
                continue;
            }

            $field = match ($criterion['criterion'] ?? null) {
                'POINTS' => 'points',
                'WINS' => 'wins',
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
            $normalization = ($criterion['normalization'] ?? 'DEFAULT') === 'DEFAULT'
                ? ($runtime['cross_group_normalization'] ?? 'RAW')
                : $criterion['normalization'];

            if (
                $normalization === 'PER_MATCH'
                && in_array($field, ['points', 'wins', 'score_difference', 'score_for', 'game_difference', 'game_wins'], true)
            ) {
                $leftValue = ($left['played'] ?? 0) > 0 ? $leftValue / $left['played'] : 0;
                $rightValue = ($right['played'] ?? 0) > 0 ? $rightValue / $right['played'] : 0;
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

    private function orderParticipants(
        array $participantIds,
        string $mode
    ): array {
        if (
            $mode
            ===
            'RANDOM'
            ||
            $mode
            ===
            'POT_DRAW'
        ) {
            shuffle(
                $participantIds
            );

            return $participantIds;
        }

        if (
            $mode
            ===
            'SNAKE_SEEDED'
        ) {
            return $participantIds;
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

<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Runtime\CutoffPolicyResolver;
use App\Services\Tournaments\RoundRobin\RoundRobinValidator;
use Illuminate\Validation\ValidationException;

class RoundRobinLabEngine
implements LabPhaseEngine, SupportsManualDecision
{
    public function __construct(
        private readonly
        RoundRobinValidator $validator,

        private readonly
        CutoffPolicyResolver $cutoffResolver
    ) {}
    
    public function supports(
        string $phaseType
    ): bool {
        return
            $phaseType
            ===
            'ROUND_ROBIN';
    }

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array {
        $phase->loadMissing([
            'roundRobinSetting',
            'roundRobinTiebreakers',
            'exits',
        ]);

        $settings =
            $phase->roundRobinSetting;

        if (! $settings) {
            $this->fail(
                'La fase no tiene una configuración Round Robin.'
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

        $errors =
            $this->validator
            ->validate(
                $phase,
                $settings,
                count($participantIds)
            );

        if ($errors !== []) {
            $this->fail(
                implode(
                    ' ',
                    $errors
                )
            );
        }

        if ($settings->initial_order_mode === 'RANDOM') {
            usort(
                $participantIds,
                fn($left, $right) => strcmp(
                    hash('sha256', $phase->id . ':' . $left),
                    hash('sha256', $phase->id . ':' . $right)
                )
            );
        } elseif ($settings->initial_order_mode === 'RANKING') {
            usort(
                $participantIds,
                fn($left, $right) =>
                    (int) ($participants[$left]['seed'] ?? PHP_INT_MAX)
                    <=>
                    (int) ($participants[$right]['seed'] ?? PHP_INT_MAX)
            );
        }

        $standings =
            [];

        foreach (
            $participantIds
            as
            $index =>
            $participantId
        ) {
            $standings[$participantId] = [
                'participant_id' =>
                $participantId,

                'seed' =>
                $index + 1,

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

        $rounds =
            $this->schedule(
                $participantIds,
                (int)
                $settings->cycles,
                (int)
                $settings->default_best_of
            );

        return [
            'engine' =>
            'ROUND_ROBIN',

            'status' =>
            'RUNNING',

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
            ],

            'cutoff_tie_policy' =>
            $settings->cutoff_tie_policy,

            'resolved_cutoffs' =>
            [],

            'cutoff_exits' =>
            $phase->exits
                ->where('status', 'ACTIVE')
                ->whereIn('selector_type', ['TOP_N', 'BOTTOM_N'])
                ->sortBy(fn($exit) => sprintf('%010d-%010d-%010d', $exit->priority, $exit->sort_order, $exit->id))
                ->map(fn($exit) => [
                    'id' => (int) $exit->id,
                    'name' => $exit->name,
                    'selector_type' => $exit->selector_type,
                    'take' => (int) $exit->selector_from,
                ])
                ->values()
                ->all(),

            'tiebreakers' =>
            $phase
                ->roundRobinTiebreakers
                ->map(
                    fn($tiebreaker) => [
                        'criterion' =>
                        $tiebreaker->criterion,

                        'direction' =>
                        $tiebreaker
                            ->effective_direction,
                    ]
                )
                ->all(),

            'rounds' =>
            $rounds,

            'standings' =>
            array_values(
                $standings
            ),

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

            'survivor_ids' =>
            [],

            'eliminated_ids' =>
            [],
        ];
    }

    public function submit(
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB
    ): array {
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
            $scoreA === $scoreB
            &&
            ! $runtime['allow_draws']
        ) {
            $this->fail(
                'Esta fase Round Robin no permite empates.'
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
                'El encuentro no existe en esta fase.'
            );
        }

        $runtime =
            $this->rank(
                $runtime
            );

        foreach (
            $runtime['rounds']
            as
            &$round
        ) {
            $hasPending =
                collect(
                    $round['matches']
                )
                ->contains(
                    fn($match) =>
                    $match['status']
                        !==
                        'COMPLETED'
                );

            if (! $hasPending) {
                $round['status'] =
                    'COMPLETED';
            }
        }

        unset($round);

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

        if (
            $runtime['matches_completed']
            ===
            $runtime['matches_total']
        ) {
            $runtime = $this->finalizeCutoffs($runtime);
        }

        return $runtime;
    }

    private function schedule(
        array $participantIds,
        int $cycles,
        int $bestOf
    ): array {
        if (
            count($participantIds)
            %
            2
            !==
            0
        ) {
            $participantIds[] =
                null;
        }

        $rounds =
            [];

        $globalRound =
            1;

        $slotCount =
            count(
                $participantIds
            );

        for (
            $cycle = 1;
            $cycle <= max(1, $cycles);
            $cycle++
        ) {
            $rotation =
                $participantIds;

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
                    $participantA =
                        $rotation[$index];

                    $participantB =
                        $rotation[$slotCount
                            -
                            1
                            -
                            $index];

                    if (
                        ! $participantA
                        ||
                        ! $participantB
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
                            $participantA,
                            $participantB,
                        ] = [
                            $participantB,
                            $participantA,
                        ];
                    }

                    $matches[] = [
                        'id' =>
                        'RR-R'
                            .
                            $globalRound
                            .
                            '-M'
                            .
                            (
                                count($matches)
                                +
                                1
                            ),

                        'number' =>
                        count($matches)
                            +
                            1,

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

                        'series_format' =>
                        'BEST_OF',

                        'best_of' =>
                        $bestOf,

                        'fixed_games' =>
                        1,

                        'status' =>
                        'PENDING',
                    ];
                }

                $rounds[] = [
                    'number' =>
                    $globalRound,

                    'cycle' =>
                    $cycle,

                    'label' =>
                    'Jornada '
                        .
                        $globalRound,

                    'status' =>
                    'PENDING',

                    'matches' =>
                    $matches,
                ];

                $fixed =
                    array_shift(
                        $rotation
                    );

                $last =
                    array_pop(
                        $rotation
                    );

                array_unshift(
                    $rotation,
                    $fixed,
                    $last
                );

                $globalRound++;
            }
        }

        return $rounds;
    }

    private function rank(
        array $runtime
    ): array {
        $rows =
            [];

        foreach (
            $runtime['standings']
            as
            $row
        ) {
            foreach (
                [
                    'played',
                    'wins',
                    'draws',
                    'losses',
                    'points',
                    'score_for',
                    'score_against',
                    'score_difference',
                ]
                as
                $key
            ) {
                $row[$key] =
                    0;
            }

            $rows[$row['participant_id']] = $row;
        }

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

                $participantA =
                    &$rows[$match['participant_a_id']];

                $participantB =
                    &$rows[$match['participant_b_id']];

                $participantA['played']++;
                $participantB['played']++;

                $participantA['score_for'] +=
                    $match['score_a'];

                $participantA['score_against'] +=
                    $match['score_b'];

                $participantB['score_for'] +=
                    $match['score_b'];

                $participantB['score_against'] +=
                    $match['score_a'];

                if (
                    $match['score_a']
                    ===
                    $match['score_b']
                ) {
                    $participantA['draws']++;
                    $participantB['draws']++;

                    $participantA['points'] +=
                        $runtime['points']['draw'];

                    $participantB['points'] +=
                        $runtime['points']['draw'];
                } elseif (
                    $match['score_a']
                    >
                    $match['score_b']
                ) {
                    $participantA['wins']++;
                    $participantB['losses']++;

                    $participantA['points'] +=
                        $runtime['points']['win'];

                    $participantB['points'] +=
                        $runtime['points']['loss'];
                } else {
                    $participantB['wins']++;
                    $participantA['losses']++;

                    $participantB['points'] +=
                        $runtime['points']['win'];

                    $participantA['points'] +=
                        $runtime['points']['loss'];
                }

                unset(
                    $participantA,
                    $participantB
                );
            }
        }

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

        $criteria = [
            [
                'criterion' =>
                'POINTS',

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

        uasort(
            $rows,
            function (
                array $left,
                array $right
            ) use (
                $criteria,
                $fieldMap
            ): int {
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

        $runtime['standings'] =
            array_values(
                $rows
            );

        return $runtime;
    }

    public function resolveManualDecision(
        array $runtime,
        array $payload
    ): array {
        $decision = $runtime['manual_decision'] ?? null;

        if (! is_array($decision) || ($payload['decision_id'] ?? null) !== ($decision['id'] ?? null)) {
            $this->fail('La decisión Round Robin ya no corresponde al estado actual.');
        }

        if (! in_array($decision['type'] ?? '', ['CUTOFF_SELECTION', 'PLAYOFF_SELECTION'], true)) {
            $this->fail('La decisión pendiente no pertenece a un corte Round Robin.');
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

        return $this->finalizeCutoffs($runtime);
    }

    private function finalizeCutoffs(array $runtime): array
    {
        $runtime['outcomes'] = [];
        $consumed = [];

        foreach ($runtime['cutoff_exits'] ?? [] as $exit) {
            $pool = collect($runtime['standings'])
                ->reject(fn($row) => isset($consumed[$row['participant_id']]))
                ->values();

            if ($exit['selector_type'] === 'BOTTOM_N') {
                $pool = $pool->reverse()->values();
            }

            $decisionKey = 'ROUND_ROBIN:CUTOFF:' . $exit['id'];
            $resolvedIds = $runtime['resolved_cutoffs'][$decisionKey] ?? null;

            if (is_array($resolvedIds)) {
                $selectedRows = $pool
                    ->filter(fn($row) => in_array($row['participant_id'], $resolvedIds, true))
                    ->values();
            } else {
                $resolved = $this->cutoffResolver->resolve(
                    $pool->all(),
                    (int) $exit['take'],
                    $runtime['cutoff_tie_policy'] ?? 'USE_TIEBREAKERS',
                    fn(array $left, array $right): bool =>
                        $this->competitivelyTied($left, $right, $runtime),
                    $decisionKey,
                    'Resolver empate Round Robin'
                );

                if ($resolved['decision'] !== null) {
                    $runtime['status'] = 'AWAITING_DECISION';
                    $runtime['manual_decision'] = $resolved['decision'];
                    return $runtime;
                }

                $selectedRows = collect($resolved['selected']);
            }

            $ids = $selectedRows->pluck('participant_id')->values()->all();
            foreach ($ids as $participantId) {
                $consumed[$participantId] = true;
            }

            $runtime['outcomes'][] = [
                'exit_id' => $exit['id'],
                'exit_name' => $exit['name'],
                'participant_ids' => $ids,
            ];
        }

        $runtime['status'] = 'COMPLETED';
        $runtime['survivor_ids'] = collect($runtime['standings'])
            ->pluck('participant_id')
            ->values()
            ->all();

        return $runtime;
    }

    private function competitivelyTied(array $left, array $right, array $runtime): bool
    {
        $criteria = [
            ['criterion' => 'POINTS'],
            ...$runtime['tiebreakers'],
        ];

        foreach ($criteria as $criterion) {
            if (($criterion['criterion'] ?? null) === 'SEED') {
                continue;
            }

            $field = match ($criterion['criterion'] ?? null) {
                'POINTS' => 'points',
                'WINS' => 'wins',
                'FEWEST_LOSSES' => 'losses',
                'SCORE_DIFFERENCE' => 'score_difference',
                'SCORE_FOR' => 'score_for',
                'GAME_DIFFERENCE' => 'game_difference',
                'GAME_WINS' => 'game_wins',
                default => null,
            };

            if ($field && ($left[$field] ?? 0) != ($right[$field] ?? 0)) {
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

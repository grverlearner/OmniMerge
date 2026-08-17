<?php

namespace App\Services\Tournaments\CompetitionLab\Runtime;

final class CutoffPolicyResolver
{
    /**
     * @param array<int,array<string,mixed>> $rankedRows
     * @param callable(array,array):bool $areCompetitivelyTied
     * @return array{selected:array,decision:?array}
     */
    public function resolve(
        array $rankedRows,
        int $take,
        string $policy,
        callable $areCompetitivelyTied,
        string $decisionKey,
        string $title = 'Resolver empate en el corte'
    ): array {
        $rankedRows = array_values($rankedRows);
        $take = max(0, min($take, count($rankedRows)));

        if ($take === 0 || $take >= count($rankedRows)) {
            return [
                'selected' => array_slice($rankedRows, 0, $take),
                'decision' => null,
            ];
        }

        $boundary = $rankedRows[$take - 1];
        $tieStart = $take - 1;
        $tieEnd = $take - 1;

        while (
            $tieStart > 0
            && $areCompetitivelyTied($rankedRows[$tieStart - 1], $boundary)
        ) {
            $tieStart--;
        }

        while (
            $tieEnd + 1 < count($rankedRows)
            && $areCompetitivelyTied($rankedRows[$tieEnd + 1], $boundary)
        ) {
            $tieEnd++;
        }

        if ($tieEnd < $take) {
            return [
                'selected' => array_slice($rankedRows, 0, $take),
                'decision' => null,
            ];
        }

        $guaranteed = array_slice($rankedRows, 0, $tieStart);
        $tied = array_slice($rankedRows, $tieStart, $tieEnd - $tieStart + 1);
        $needed = $take - count($guaranteed);
        $policy = strtoupper($policy ?: 'USE_TIEBREAKERS');

        if ($policy === 'INCLUDE_ALL_TIED') {
            return [
                'selected' => [...$guaranteed, ...$tied],
                'decision' => null,
            ];
        }

        if ($policy === 'RANDOM_RESOLUTION') {
            usort(
                $tied,
                static fn(array $left, array $right): int =>
                    strcmp(
                        hash('sha256', $decisionKey . ':' . ($left['participant_id'] ?? '')),
                        hash('sha256', $decisionKey . ':' . ($right['participant_id'] ?? ''))
                    )
            );

            return [
                'selected' => [
                    ...$guaranteed,
                    ...array_slice($tied, 0, $needed),
                ],
                'decision' => null,
            ];
        }

        if (in_array($policy, ['MANUAL_RESOLUTION', 'REQUIRE_PLAYOFF'], true)) {
            return [
                'selected' => $guaranteed,
                'decision' => [
                    'id' => 'DEC-' . substr(hash('sha256', $decisionKey), 0, 20),
                    'scope' => 'ENGINE',
                    'type' => $policy === 'REQUIRE_PLAYOFF'
                        ? 'PLAYOFF_SELECTION'
                        : 'CUTOFF_SELECTION',
                    'title' => $policy === 'REQUIRE_PLAYOFF'
                        ? 'Registrar resultado del playoff'
                        : $title,
                    'description' => $policy === 'REQUIRE_PLAYOFF'
                        ? 'El empate exige un playoff. Selecciona los participantes que obtuvieron las plazas después de disputarlo.'
                        : 'La cadena competitiva no pudo separar el último cupo. Selecciona quiénes avanzan.',
                    'eligible_participant_ids' => array_values(array_map(
                        static fn(array $row) => $row['participant_id'],
                        $tied
                    )),
                    'required_selection_count' => $needed,
                    'context' => [
                        'decision_key' => $decisionKey,
                        'guaranteed_participant_ids' => array_values(array_map(
                            static fn(array $row) => $row['participant_id'],
                            $guaranteed
                        )),
                    ],
                ],
            ];
        }

        // USE_TIEBREAKERS: la tabla ya viene ordenada por todos los criterios
        // competitivos. El fallback estable solo hace reproducible la vista.
        return [
            'selected' => array_slice($rankedRows, 0, $take),
            'decision' => null,
        ];
    }
}

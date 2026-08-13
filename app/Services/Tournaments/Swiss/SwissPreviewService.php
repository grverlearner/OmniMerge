<?php

namespace App\Services\Tournaments\Swiss;

use App\Models\PhaseSwissSetting;
use App\Models\PhaseTemplate;
use Illuminate\Support\Collection;

class SwissPreviewService
{
    public function __construct(
        private readonly
        SwissValidator $validator,

        private readonly
        SwissPairingCalculator $pairingCalculator,

        private readonly
        SwissRecordMapService $recordMapService,

        private readonly
        SwissAdvancementForecastService $advancementForecastService
    ) {}

    public function preview(
        PhaseTemplate $phaseTemplate,
        PhaseSwissSetting $settings,
        Collection $advancementRules,
        Collection $roundRules,
        int $participants
    ): array {
        $validation =
            $this
            ->validator
            ->validate(
                $phaseTemplate,
                $settings,
                $participants
            );

        if (
            $validation['errors']
            !==
            []
        ) {
            return [
                'valid' => false,

                'errors' =>
                $validation['errors'],

                'warnings' =>
                $validation['warnings'],

                'participants' =>
                $participants,
            ];
        }

        $mockParticipants =
            $this->mockParticipants(
                $participants
            );

        $firstRound =
            $this
            ->pairingCalculator
            ->generateRound(
                $mockParticipants,
                $settings,
                1
            );

        $roundLimit =
            $settings->round_limit;

        $maxSeriesUpperBound =
            intdiv(
                $participants,
                2
            )
            *
            $roundLimit;

        $byeRoundsUpperBound =
            $participants % 2 !== 0
            ? $roundLimit
            : 0;

        $decisiveMaximum =
            $settings->completion_mode
            ===
            'RECORD_THRESHOLDS'
            ? (
                $settings
                ->qualification_wins
                +
                $settings
                ->elimination_losses
                -
                1
            )
            : null;

        return [
            'valid' => true,

            'errors' => [],

            'warnings' =>
            array_merge(
                $validation['warnings'],
                $firstRound['warnings']
                    ??
                    []
            ),

            'participants' =>
            $participants,

            'round_limit' =>
            $roundLimit,

            'dynamic_pairing_from_round' =>
            2,

            'max_series_upper_bound' =>
            $maxSeriesUpperBound,

            'bye_rounds_upper_bound' =>
            $byeRoundsUpperBound,

            'unique_opponents_available' =>
            max(
                0,
                $participants - 1
            ),

            'max_decisive_rounds' =>
            $decisiveMaximum,

            'first_round' =>
            $firstRound,

            'record_map' =>
            $this
                ->recordMapService
                ->build(
                    $settings
                ),

            'advancement' =>
            $this
                ->advancementForecastService
                ->forecast(
                    $participants,
                    $advancementRules
                ),

            'round_rules' =>
            $roundRules
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->values()
                ->map(
                    fn($rule) => [
                        'trigger' =>
                        $rule
                            ->trigger_summary,

                        'best_of' =>
                        $rule
                            ->best_of_label,

                        'draws' =>
                        $rule
                            ->draw_override_label,
                    ]
                )
                ->all(),
        ];
    }

    private function mockParticipants(
        int $participants
    ): array {
        $result = [];

        for (
            $seed = 1;
            $seed <= $participants;
            $seed++
        ) {
            $result[] = [
                'id' =>
                $seed,

                'seed' =>
                $seed,

                'input_order' =>
                $seed,

                'label' =>
                'Seed '
                    .
                    $seed,

                'wins' =>
                0,

                'draws' =>
                0,

                'losses' =>
                0,

                'standing_score' =>
                0,

                'pairing_score' =>
                0,

                'opponents' =>
                [],

                'bye_count' =>
                0,

                'side_a_count' =>
                0,

                'side_b_count' =>
                0,

                'float_count' =>
                0,

                'active' =>
                true,
            ];
        }

        return $result;
    }
}

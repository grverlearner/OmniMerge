<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationConfigurationInspector;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundAvailabilityService;
use App\Services\Tournaments\SingleElimination\SingleEliminationValidator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SingleEliminationConfigurationInspectorTest extends TestCase
{
    public function test_it_detects_obsolete_and_redundant_rules(): void
    {
        $phase =
            new PhaseTemplate();

        $phase->forceFill([
            'phase_type' =>
            'SINGLE_ELIMINATION',

            'min_participants' =>
            2,

            'max_participants' =>
            8,

            'exact_participants' =>
            8,

            'participant_multiple' =>
            null,

            'allow_byes' =>
            false,
        ]);

        $settings =
            new PhaseSingleEliminationSetting();

        $settings->forceFill([
            'completion_mode' =>
            'WINNER',

            'target_survivors' =>
            1,

            'series_format' =>
            'BEST_OF',

            'default_best_of' =>
            3,

            'fixed_games' =>
            1,
        ]);

        $obsolete =
            new PhaseSingleEliminationRoundRule();

        $obsolete->forceFill([
            'id' =>
            10,

            'participants_in_round' =>
            512,

            'series_format' =>
            'BEST_OF',

            'best_of' =>
            5,

            'fixed_games' =>
            1,
        ]);

        $redundant =
            new PhaseSingleEliminationRoundRule();

        $redundant->forceFill([
            'id' =>
            11,

            'participants_in_round' =>
            4,

            'series_format' =>
            'BEST_OF',

            'best_of' =>
            3,

            'fixed_games' =>
            1,
        ]);

        $inspector =
            new SingleEliminationConfigurationInspector(
                new SingleEliminationRoundAvailabilityService(),
                new SingleEliminationValidator()
            );

        $result =
            $inspector
            ->inspect(
                $phase,
                $settings,
                new Collection([
                    $obsolete,
                    $redundant,
                ]),
                8
            );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertSame(
            [10],
            $result['obsolete_rule_ids']
        );

        $this->assertSame(
            [11],
            $result['redundant_rule_ids']
        );

        $this->assertSame(
            [
                8,
                4,
                2,
            ],
            $result['possible_round_sizes']
        );
    }
}

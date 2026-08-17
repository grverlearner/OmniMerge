<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSingleEliminationRoundRule;
use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationGraphRuntime;
use App\Services\Tournaments\CompetitionLab\Engines\SingleEliminationLabEngine;
use App\Services\Tournaments\SingleElimination\SingleEliminationAdvancedCalculator;
use App\Services\Tournaments\SingleElimination\SingleEliminationBracketCalculator;
use App\Services\Tournaments\SingleElimination\SingleEliminationConfigurationInspector;
use App\Services\Tournaments\SingleElimination\SingleEliminationRoundAvailabilityService;
use App\Services\Tournaments\SingleElimination\SingleEliminationValidator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;

class SingleEliminationSeriesContractTest extends TestCase
{
    public function test_validator_rejects_even_best_of_in_persisted_configuration(): void
    {
        $settings =
            $this->settings([
                'series_format' =>
                'BEST_OF',

                'default_best_of' =>
                4,
            ]);

        $errors =
            (new SingleEliminationValidator())
            ->validate(
                $this->phase(
                    4,
                    $settings
                ),
                $settings,
                4
            );

        $this->assertTrue(
            collect($errors)
                ->contains(
                    fn(string $error): bool =>
                    str_contains(
                        $error,
                        'BO1, BO3, BO5, BO7 o BO9'
                    )
                )
        );
    }

    public function test_validator_rejects_invalid_fixed_games_in_persisted_configuration(): void
    {
        $settings =
            $this->settings([
                'series_format' =>
                'FIXED_GAMES',

                'fixed_games' =>
                0,
            ]);

        $errors =
            (new SingleEliminationValidator())
            ->validate(
                $this->phase(
                    4,
                    $settings
                ),
                $settings,
                4
            );

        $this->assertTrue(
            collect($errors)
                ->contains(
                    fn(string $error): bool =>
                    str_contains(
                        $error,
                        'entre 1 y 99 juegos'
                    )
                )
        );
    }

    public function test_inspector_marks_invalid_round_series_rule_as_error(): void
    {
        $settings =
            $this->settings();

        $phase =
            $this->phase(
                4,
                $settings
            );

        $invalidRule =
            new PhaseSingleEliminationRoundRule();

        $invalidRule->forceFill([
            'id' =>
            77,

            'participants_in_round' =>
            2,

            'series_format' =>
            'BEST_OF',

            'best_of' =>
            4,

            'fixed_games' =>
            1,
        ]);

        $inspector =
            $this->inspector();

        $result =
            $inspector->inspect(
                $phase,
                $settings,
                new EloquentCollection([
                    $invalidRule,
                ]),
                4
            );

        $this->assertFalse(
            $result['valid']
        );

        $this->assertSame(
            [
                77,
            ],
            $result['invalid_rule_ids']
        );

        $this->assertTrue(
            collect($result['errors'])
                ->contains(
                    fn(string $error): bool =>
                    str_contains(
                        $error,
                        'Final debe usar BO1, BO3, BO5, BO7 o BO9'
                    )
                )
        );
    }

    public function test_final_round_override_matches_preview_and_basic_runtime(): void
    {
        $settings =
            $this->settings([
                'series_format' =>
                'BEST_OF',

                'default_best_of' =>
                1,
            ]);

        $phase =
            $this->phase(
                4,
                $settings
            );

        $finalRule =
            new PhaseSingleEliminationRoundRule();

        $finalRule->forceFill([
            'id' =>
            88,

            'participants_in_round' =>
            2,

            'series_format' =>
            'BEST_OF',

            'best_of' =>
            5,

            'fixed_games' =>
            1,
        ]);

        $roundRules =
            new EloquentCollection([
                $finalRule,
            ]);

        $phase->setRelation(
            'singleEliminationRoundRules',
            $roundRules
        );

        $preview =
            (new SingleEliminationBracketCalculator(
                new SingleEliminationValidator(),
                new SingleEliminationAdvancedCalculator()
            ))
            ->calculate(
                $phase,
                $settings,
                4,
                $roundRules
            );

        $this->assertTrue(
            $preview['valid']
        );

        $this->assertSame(
            'BO5',
            $preview['rounds'][1]['series_label']
        );

        $runtime =
            $this->engine()
            ->prepare(
                $phase,
                [
                    'P1',
                    'P2',
                    'P3',
                    'P4',
                ],
                $this->participants()
            );

        $this->assertSame(
            1,
            $runtime['rounds'][0]['matches'][0]['best_of']
        );

        $engine =
            $this->engine();

        /*
         * Repreparamos con la misma definición para ejecutar con la misma
         * instancia de engine y completar la primera ronda BO1.
         */
        $runtime =
            $engine->prepare(
                $phase,
                [
                    'P1',
                    'P2',
                    'P3',
                    'P4',
                ],
                $this->participants()
            );

        $runtime =
            $engine->submit(
                $runtime,
                'SE-R1-M1',
                1,
                0
            );

        $runtime =
            $engine->submit(
                $runtime,
                'SE-R1-M2',
                1,
                0
            );

        $final =
            $runtime['rounds'][1]['matches'][0];

        $this->assertSame(
            'BEST_OF',
            $final['series_format']
        );

        $this->assertSame(
            5,
            $final['best_of']
        );

        $this->assertSame(
            'BO5',
            $final['series_label']
        );
    }

    private function inspector(): SingleEliminationConfigurationInspector
    {
        $validator =
            new SingleEliminationValidator();

        return new SingleEliminationConfigurationInspector(
            new SingleEliminationRoundAvailabilityService(),
            $validator
        );
    }

    private function engine(): SingleEliminationLabEngine
    {
        return new SingleEliminationLabEngine(
            new SingleEliminationValidator(),
            $this->inspector(),
            $this->createMock(
                SingleEliminationGraphRuntime::class
            )
        );
    }

    private function phase(
        int $participants,
        PhaseSingleEliminationSetting $settings
    ): PhaseTemplate {
        $phase =
            new PhaseTemplate();

        $phase->forceFill([
            'id' =>
            4300 + $participants,

            'phase_type' =>
            'SINGLE_ELIMINATION',

            'min_participants' =>
            2,

            'max_participants' =>
            512,

            'exact_participants' =>
            $participants,

            'participant_multiple' =>
            null,

            'allow_byes' =>
            true,
        ]);

        $phase->setRelation(
            'singleEliminationSetting',
            $settings
        );

        if (
            ! $phase->relationLoaded(
                'singleEliminationRoundRules'
            )
        ) {
            $phase->setRelation(
                'singleEliminationRoundRules',
                new EloquentCollection()
            );
        }

        return $phase;
    }

    private function settings(
        array $overrides = []
    ): PhaseSingleEliminationSetting {
        $settings =
            new PhaseSingleEliminationSetting();

        $settings->forceFill(
            array_merge(
                [
                    'configuration_mode' =>
                    'BASIC',

                    'input_mode' =>
                    'POOL',

                    'routing_mode' =>
                    'AUTOMATIC',

                    'entrants_per_match' =>
                    2,

                    'qualifiers_per_match' =>
                    1,

                    'encounter_profile' =>
                    'DUEL',

                    'remainder_policy' =>
                    'BYE',

                    'completion_mode' =>
                    'WINNER',

                    'target_survivors' =>
                    1,

                    'seeding_mode' =>
                    'RANKING',

                    'pairing_mode' =>
                    'STANDARD_SEEDED',

                    'bye_assignment' =>
                    'TOP_SEEDS',

                    'reseed_each_round' =>
                    false,

                    'series_format' =>
                    'BEST_OF',

                    'default_best_of' =>
                    1,

                    'fixed_games' =>
                    1,
                ],
                $overrides
            )
        );

        return $settings;
    }

    private function participants(): array
    {
        return [
            'P1' => [
                'id' => 'P1',
                'name' => 'Participant 1',
                'seed' => 1,
            ],
            'P2' => [
                'id' => 'P2',
                'name' => 'Participant 2',
                'seed' => 2,
            ],
            'P3' => [
                'id' => 'P3',
                'name' => 'Participant 3',
                'seed' => 3,
            ],
            'P4' => [
                'id' => 'P4',
                'name' => 'Participant 4',
                'seed' => 4,
            ],
        ];
    }
}

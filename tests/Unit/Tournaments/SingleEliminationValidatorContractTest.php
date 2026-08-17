<?php

namespace Tests\Unit\Tournaments;

use App\Models\PhaseSingleEliminationSetting;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\SingleElimination\SingleEliminationValidator;
use PHPUnit\Framework\TestCase;

class SingleEliminationValidatorContractTest extends TestCase
{
    public function test_basic_rejects_advanced_input_and_routing_modes(): void
    {
        $errors = $this->validator()->validate(
            $this->phase(8),
            $this->settings([
                'input_mode' => 'GROUPED',
                'routing_mode' => 'MANUAL',
            ]),
            8
        );

        $this->assertContains(
            'En modo básico la entrada debe usar Bolsa común (POOL).',
            $errors
        );
        $this->assertContains(
            'En modo básico el enrutamiento debe ser Automático.',
            $errors
        );
    }

    public function test_basic_rejects_multi_competitor_and_unsupported_remainder_policy(): void
    {
        $errors = $this->validator()->validate(
            $this->phase(8),
            $this->settings([
                'encounter_profile' => 'MULTI_COMPETITOR',
                'remainder_policy' => 'BALANCED',
            ]),
            8
        );

        $this->assertContains(
            'En modo básico el perfil de encuentro debe ser Duelo 2 → 1.',
            $errors
        );
        $this->assertContains(
            'En modo básico los sobrantes solo admiten BYE o Rechazar.',
            $errors
        );
    }

    public function test_reject_policy_requires_power_of_two_even_when_phase_allows_byes(): void
    {
        $errors = $this->validator()->validate(
            $this->phase(5, true),
            $this->settings([
                'remainder_policy' => 'REJECT',
            ]),
            5
        );

        $this->assertContains(
            'La cantidad de participantes debe ser una potencia de 2 cuando los BYEs no pueden utilizarse.',
            $errors
        );
    }

    public function test_bye_policy_is_rejected_when_phase_contract_disallows_byes(): void
    {
        $errors = $this->validator()->validate(
            $this->phase(5, false),
            $this->settings([
                'remainder_policy' => 'BYE',
            ]),
            5
        );

        $this->assertContains(
            'La política BYE no puede usarse porque la Fase no permite BYEs.',
            $errors
        );
    }

    public function test_reseed_is_only_supported_with_standard_seeded_pairing(): void
    {
        $errors = $this->validator()->validate(
            $this->phase(8),
            $this->settings([
                'pairing_mode' => 'RANDOM',
                'reseed_each_round' => true,
            ]),
            8
        );

        $this->assertContains(
            'El reseeding del modo básico solo es compatible con Pairing Seeded estándar.',
            $errors
        );
    }

    public function test_valid_basic_contract_has_no_errors(): void
    {
        $errors = $this->validator()->validate(
            $this->phase(5),
            $this->settings(),
            5
        );

        $this->assertSame([], $errors);
    }

    private function validator(): SingleEliminationValidator
    {
        return new SingleEliminationValidator();
    }

    private function phase(
        int $participants,
        bool $allowByes = true
    ): PhaseTemplate {
        $phase = new PhaseTemplate();
        $phase->forceFill([
            'phase_type' => 'SINGLE_ELIMINATION',
            'min_participants' => 2,
            'max_participants' => 512,
            'exact_participants' => $participants,
            'participant_multiple' => null,
            'allow_byes' => $allowByes,
        ]);

        return $phase;
    }

    private function settings(array $overrides = []): PhaseSingleEliminationSetting
    {
        $settings = new PhaseSingleEliminationSetting();
        $settings->forceFill(array_merge([
            'configuration_mode' => 'BASIC',
            'input_mode' => 'POOL',
            'routing_mode' => 'AUTOMATIC',
            'entrants_per_match' => 2,
            'qualifiers_per_match' => 1,
            'encounter_profile' => 'DUEL',
            'remainder_policy' => 'BYE',
            'completion_mode' => 'WINNER',
            'target_survivors' => 1,
            'seeding_mode' => 'INPUT_ORDER',
            'pairing_mode' => 'STANDARD_SEEDED',
            'bye_assignment' => 'TOP_SEEDS',
            'reseed_each_round' => false,
            'series_format' => 'BEST_OF',
            'default_best_of' => 1,
            'fixed_games' => 1,
        ], $overrides));

        return $settings;
    }
}

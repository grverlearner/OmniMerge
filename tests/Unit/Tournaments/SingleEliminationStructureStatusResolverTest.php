<?php

namespace Tests\Unit\Tournaments;

use App\Services\Tournaments\SingleElimination\Structure\SingleEliminationStructureStatusResolver;
use PHPUnit\Framework\TestCase;

class SingleEliminationStructureStatusResolverTest extends TestCase
{
    private SingleEliminationStructureStatusResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver =
            new SingleEliminationStructureStatusResolver();
    }

    public function test_empty_graph_is_not_generated_instead_of_invalid(): void
    {
        $validation =
            $this->resolver
            ->forPayload(
                $this->validation(
                    valid: false,
                    executable: false,
                    stats: [
                        'input_gates' => 0,
                        'rounds' => 0,
                        'encounters' => 0,
                        'connections' => 0,
                    ],
                    errors: [
                        $this->issue(
                            'NO_INPUT_GATES'
                        ),
                    ]
                ),
                'NOT_GENERATED',
                null,
                'empty-fingerprint'
            );

        $this->assertSame(
            'NOT_GENERATED',
            $validation['structure_status']
        );

        $this->assertFalse(
            $validation['has_structure']
        );

        $this->assertSame(
            [],
            $validation['errors']
        );

        $this->assertSame(
            0,
            $validation['counts']['errors']
        );

        $this->assertFalse(
            $validation['runtime_ready']
        );
    }

    public function test_valid_requires_matching_validated_fingerprint(): void
    {
        $validation =
            $this->resolver
            ->forPayload(
                $this->validation(),
                'VALID',
                'same',
                'same'
            );

        $this->assertSame(
            'VALID',
            $validation['structure_status']
        );

        $this->assertTrue(
            $validation['fingerprint_matches']
        );

        $this->assertTrue(
            $validation['runtime_ready']
        );

        $this->assertSame(
            'Lista para ejecutar',
            $validation['status']['label']
        );
    }

    public function test_fingerprint_mismatch_becomes_stale_even_if_persisted_status_is_valid(): void
    {
        $validation =
            $this->resolver
            ->forPayload(
                $this->validation(),
                'VALID',
                'old',
                'new'
            );

        $this->assertSame(
            'STALE',
            $validation['structure_status']
        );

        $this->assertFalse(
            $validation['runtime_ready']
        );

        $this->assertFalse(
            $validation['fingerprint_matches']
        );

        $this->assertSame(
            'STRUCTURE_FINGERPRINT_STALE',
            $validation['warnings'][0]['code']
        );

        $this->assertNotEmpty(
            $validation['warnings'][0]['action']
        );
    }

    public function test_missing_validated_fingerprint_keeps_structure_pending_validation(): void
    {
        $validation =
            $this->resolver
            ->forPayload(
                $this->validation(),
                'GENERATED',
                null,
                'current'
            );

        $this->assertSame(
            'GENERATED',
            $validation['structure_status']
        );

        $this->assertFalse(
            $validation['runtime_ready']
        );

        $this->assertSame(
            'INDIGO',
            $validation['status']['tone']
        );
    }

    public function test_structurally_valid_but_non_executable_graph_is_blocked(): void
    {
        $validation =
            $this->resolver
            ->afterValidation(
                $this->validation(
                    executable: false,
                    warnings: [
                        $this->issue(
                            'INPUT_PLACEHOLDER_REQUIRES_REVIEW',
                            'INPUT_GATE',
                            7,
                            'IN-001'
                        ),
                    ]
                ),
                'GENERATED',
                'fingerprint'
            );

        $this->assertSame(
            'BLOCKED',
            $validation['structure_status']
        );

        $this->assertFalse(
            $validation['runtime_ready']
        );

        $this->assertSame(
            'FUCHSIA',
            $validation['status']['tone']
        );

        $this->assertSame(
            'IN-001',
            $validation['warnings'][0]['element']
        );

        $this->assertStringContainsString(
            'runtime',
            strtolower(
                $validation['warnings'][0]['impact']
            )
        );
    }

    public function test_explicit_validation_can_promote_graph_to_valid(): void
    {
        $validation =
            $this->resolver
            ->afterValidation(
                $this->validation(),
                'GENERATED',
                'fingerprint'
            );

        $this->assertSame(
            'VALID',
            $validation['structure_status']
        );

        $this->assertTrue(
            $validation['runtime_ready']
        );

        $this->assertTrue(
            $validation['fingerprint_matches']
        );
    }

    private function validation(
        bool $valid = true,
        bool $executable = true,
        ?array $stats = null,
        array $errors = [],
        array $warnings = [],
        array $recommendations = []
    ): array {
        return [
            'valid' => $valid,
            'executable' => $executable,

            'errors' =>
                $errors,

            'warnings' =>
                $warnings,

            'recommendations' =>
                $recommendations,

            'blocking_issues' =>
                [],

            'counts' => [
                'errors' =>
                    count($errors),

                'warnings' =>
                    count($warnings),

                'recommendations' =>
                    count($recommendations),
            ],

            'stats' =>
                $stats
                ?? [
                    'input_gates' => 1,
                    'rounds' => 2,
                    'encounters' => 3,
                    'connections' => 4,
                ],
        ];
    }

    private function issue(
        string $code,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $entityCode = null
    ): array {
        return [
            'code' =>
                $code,

            'message' =>
                'Mensaje de prueba.',

            'entity_type' =>
                $entityType,

            'entity_id' =>
                $entityId,

            'entity_code' =>
                $entityCode,
        ];
    }
}

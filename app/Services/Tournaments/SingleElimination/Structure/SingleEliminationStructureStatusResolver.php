<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

final class SingleEliminationStructureStatusResolver
{
    public function afterValidation(
        array $validation,
        ?string $persistedStatus,
        string $fingerprint
    ): array {
        $hasStructure =
            $this->hasStructure(
                $validation
            );

        $status =
            ! $hasStructure
                ? 'NOT_GENERATED'
                : (
                    ! ($validation['valid'] ?? false)
                    ? 'INVALID'
                    : (
                        ! ($validation['executable'] ?? false)
                        ? 'BLOCKED'
                        : 'VALID'
                    )
                );

        return $this->finalize(
            $validation,
            $status,
            $persistedStatus,
            $fingerprint,
            $hasStructure,
            $hasStructure
        );
    }

    public function forPayload(
        array $validation,
        ?string $persistedStatus,
        ?string $storedFingerprint,
        string $currentFingerprint
    ): array {
        $persistedStatus =
            strtoupper(
                trim(
                    (string) (
                        $persistedStatus
                        ?: 'NOT_GENERATED'
                    )
                )
            );

        $storedFingerprint =
            trim(
                (string) $storedFingerprint
            );

        $hasStructure =
            $this->hasStructure(
                $validation
            );

        $fingerprintMatches =
            $hasStructure
            &&
            $storedFingerprint !== ''
            &&
            hash_equals(
                $storedFingerprint,
                $currentFingerprint
            );

        if (! $hasStructure) {
            $status =
                'NOT_GENERATED';
        } elseif (
            $persistedStatus === 'STALE'
        ) {
            $status =
                'STALE';
        } elseif (
            $storedFingerprint !== ''
            &&
            ! $fingerprintMatches
        ) {
            /*
             * Una estructura que cambió después de ser validada ya
             * no representa el snapshot aprobado, aunque el validador
             * pueda seguir considerándola matemáticamente coherente.
             */
            $status =
                'STALE';
        } elseif (
            $persistedStatus === 'GENERATED'
            ||
            $storedFingerprint === ''
        ) {
            $status =
                'GENERATED';
        } elseif (
            ! ($validation['valid'] ?? false)
        ) {
            $status =
                'INVALID';
        } elseif (
            ! ($validation['executable'] ?? false)
        ) {
            $status =
                'BLOCKED';
        } elseif (
            $persistedStatus === 'VALID'
        ) {
            $status =
                'VALID';
        } else {
            /*
             * Nunca elevamos silenciosamente INVALID/BLOCKED a VALID.
             * Si el código o los datos hicieron que el grafo vuelva a
             * ser compatible, debe pasar otra validación explícita.
             */
            $status =
                'GENERATED';
        }

        return $this->finalize(
            $validation,
            $status,
            $persistedStatus,
            $currentFingerprint,
            $hasStructure,
            $fingerprintMatches
        );
    }

    private function finalize(
        array $validation,
        string $status,
        ?string $persistedStatus,
        string $fingerprint,
        bool $hasStructure,
        bool $fingerprintMatches
    ): array {
        if (! $hasStructure) {
            /*
             * "Todavía no generado" es un estado del ciclo de vida,
             * no una estructura inválida. El validator puede reportar
             * NO_INPUT_GATES/NO_ROUNDS/NO_ENCOUNTERS, pero mostrarlos
             * como errores rojos antes de generar sería engañoso.
             */
            $validation['errors'] = [];
            $validation['warnings'] = [];
            $validation['recommendations'] = [];
            $validation['blocking_issues'] = [];
            $validation['valid'] = false;
            $validation['executable'] = false;
        } else {
            $validation['errors'] =
                $this->enrichIssues(
                    $validation['errors']
                    ?? [],
                    'ERROR'
                );

            $validation['warnings'] =
                $this->enrichIssues(
                    $validation['warnings']
                    ?? [],
                    'WARNING'
                );

            $validation['recommendations'] =
                $this->enrichIssues(
                    $validation['recommendations']
                    ?? [],
                    'RECOMMENDATION'
                );

            $validation['blocking_issues'] =
                $this->enrichIssues(
                    $validation['blocking_issues']
                    ?? [],
                    'WARNING'
                );

            if (
                $status === 'STALE'
                &&
                ! $fingerprintMatches
            ) {
                $validation['warnings'][] =
                    $this->enrichIssue(
                        [
                            'code' =>
                                'STRUCTURE_FINGERPRINT_STALE',

                            'message' =>
                                'La estructura actual ya no coincide con el fingerprint de la última validación.',

                            'entity_type' =>
                                'PHASE_TEMPLATE',

                            'entity_id' =>
                                null,

                            'entity_code' =>
                                null,
                        ],
                        'WARNING'
                    );
            }
        }

        $validation['counts'] = [
            'errors' =>
                count(
                    $validation['errors']
                    ?? []
                ),

            'warnings' =>
                count(
                    $validation['warnings']
                    ?? []
                ),

            'recommendations' =>
                count(
                    $validation['recommendations']
                    ?? []
                ),
        ];

        $runtimeReady =
            $status === 'VALID'
            &&
            ($validation['valid'] ?? false)
            &&
            ($validation['executable'] ?? false)
            &&
            $fingerprintMatches;

        $validation['structure_status'] =
            $status;

        $validation['persisted_structure_status'] =
            $persistedStatus
            ?: 'NOT_GENERATED';

        $validation['has_structure'] =
            $hasStructure;

        $validation['fingerprint'] =
            $fingerprint;

        $validation['fingerprint_matches'] =
            $fingerprintMatches;

        $validation['runtime_ready'] =
            $runtimeReady;

        $validation['status'] =
            $this->statusMetadata(
                $status,
                $runtimeReady
            );

        return $validation;
    }

    private function hasStructure(
        array $validation
    ): bool {
        $stats =
            $validation['stats']
            ?? [];

        foreach (
            [
                'input_gates',
                'rounds',
                'encounters',
                'connections',
            ]
            as
            $key
        ) {
            if (
                (int) ($stats[$key] ?? 0)
                >
                0
            ) {
                return true;
            }
        }

        return false;
    }

    private function enrichIssues(
        array $issues,
        string $severity
    ): array {
        return array_values(
            array_map(
                fn(array $issue) =>
                    $this->enrichIssue(
                        $issue,
                        $severity
                    ),
                $issues
            )
        );
    }

    private function enrichIssue(
        array $issue,
        string $severity
    ): array {
        $code =
            (string) (
                $issue['code']
                ?? 'STRUCTURE_DIAGNOSTIC'
            );

        $entityType =
            (string) (
                $issue['entity_type']
                ?? ''
            );

        $entityId =
            $issue['entity_id']
            ?? null;

        $entityCode =
            trim(
                (string) (
                    $issue['entity_code']
                    ?? ''
                )
            );

        $element =
            $entityCode !== ''
                ? $entityCode
                : (
                    $entityType !== ''
                    && $entityId !== null
                    ? $entityType
                        . ' #'
                        . $entityId
                    : 'Estructura general'
                );

        [$title, $impact, $action] =
            $this->guidance(
                $code,
                $severity
            );

        return [
            ...$issue,

            'severity' =>
                $severity,

            'title' =>
                $issue['title']
                ?? $title,

            'element' =>
                $issue['element']
                ?? $element,

            'impact' =>
                $issue['impact']
                ?? $impact,

            'action' =>
                $issue['action']
                ?? $action,
        ];
    }

    private function guidance(
        string $code,
        string $severity
    ): array {
        return match ($code) {
            'NO_INPUT_GATES' => [
                'Falta una entrada',
                'El grafo no tiene un punto desde el que puedan entrar participantes.',
                'Genera la estructura o crea y conecta una puerta de entrada válida.',
            ],

            'NO_ROUNDS' => [
                'Faltan rondas',
                'No existe una secuencia competitiva que pueda procesar participantes.',
                'Genera la estructura o crea al menos una ronda con sus encuentros.',
            ],

            'NO_ENCOUNTERS' => [
                'Faltan encuentros',
                'La estructura no contiene unidades competitivas capaces de producir resultados.',
                'Agrega encuentros a las rondas y vuelve a ejecutar la validación.',
            ],

            'INPUT_PLACEHOLDER_REQUIRES_REVIEW' => [
                'Entrada provisional',
                'La estructura puede ser coherente, pero el runtime no debe consumir una puerta provisional sin revisión.',
                'Revisa esa puerta en Structure Graph y reemplaza o confirma su distribución antes de ejecutar.',
            ],

            'STRUCTURE_FINGERPRINT_STALE' => [
                'Validación desactualizada',
                'El snapshot estructural actual es distinto del que fue validado anteriormente.',
                'Revisa los cambios y ejecuta una nueva validación; regenera si las reglas también cambiaron.',
            ],

            default =>
            match ($severity) {
                'ERROR' => [
                    'Error estructural',
                    'Este problema impide considerar válida la estructura.',
                    'Corrige el elemento indicado y vuelve a ejecutar la validación.',
                ],

                'WARNING' => [
                    'Advertencia estructural',
                    'La definición necesita revisión antes de considerarse lista para ejecutar.',
                    'Revisa el elemento y la ruta afectada antes de continuar.',
                ],

                default => [
                    'Recomendación',
                    'No bloquea la estructura, pero puede mejorar su claridad o consistencia.',
                    'Revísala cuando quieras simplificar o reforzar la definición.',
                ],
            },
        };
    }

    private function statusMetadata(
        string $status,
        bool $runtimeReady
    ): array {
        return match ($status) {
            'NOT_GENERATED' => [
                'code' => 'NOT_GENERATED',
                'label' => 'Sin estructura',
                'headline' => 'Todavía no existe un grafo interno',
                'description' => 'Genera una estructura desde las reglas o construye una personalizada antes de validarla.',
                'action' => 'Generar estructura',
                'tone' => 'SLATE',
                'runtime_ready' => false,
            ],

            'GENERATED' => [
                'code' => 'GENERATED',
                'label' => 'Pendiente de validación',
                'headline' => 'La estructura cambió y necesita validación',
                'description' => 'El grafo existe, pero todavía no hay un snapshot validado que pueda usar el runtime.',
                'action' => 'Ejecutar validación',
                'tone' => 'INDIGO',
                'runtime_ready' => false,
            ],

            'VALID' => [
                'code' => 'VALID',
                'label' => 'Lista para ejecutar',
                'headline' => 'Estructura validada y ejecutable',
                'description' => 'Contrato, rutas, liveness y fingerprint representan el grafo que puede consumir el runtime.',
                'action' => 'Continuar',
                'tone' => 'EMERALD',
                'runtime_ready' => $runtimeReady,
            ],

            'INVALID' => [
                'code' => 'INVALID',
                'label' => 'Requiere correcciones',
                'headline' => 'La estructura contiene errores bloqueantes',
                'description' => 'Hay reglas, capacidades o rutas que impiden validar el grafo actual.',
                'action' => 'Revisar errores',
                'tone' => 'RED',
                'runtime_ready' => false,
            ],

            'STALE' => [
                'code' => 'STALE',
                'label' => 'Estructura desactualizada',
                'headline' => 'La estructura ya no representa el snapshot validado',
                'description' => 'Las reglas o el propio grafo cambiaron después de la última validación.',
                'action' => 'Revalidar o regenerar',
                'tone' => 'AMBER',
                'runtime_ready' => false,
            ],

            'BLOCKED' => [
                'code' => 'BLOCKED',
                'label' => 'Válida, pero no ejecutable',
                'headline' => 'La estructura es coherente, pero tiene bloqueos de runtime',
                'description' => 'No hay errores estructurales, pero una capacidad pendiente impide ejecutarla de forma segura.',
                'action' => 'Resolver bloqueos',
                'tone' => 'FUCHSIA',
                'runtime_ready' => false,
            ],

            default => [
                'code' => $status,
                'label' => $status,
                'headline' => 'Estado estructural desconocido',
                'description' => 'Vuelve a validar la estructura para obtener un estado reconocido.',
                'action' => 'Ejecutar validación',
                'tone' => 'SLATE',
                'runtime_ready' => false,
            ],
        };
    }
}

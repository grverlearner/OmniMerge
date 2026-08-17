<?php

namespace App\Services\Tournaments\SingleElimination\Structure;

final class SingleEliminationStructureExecutionPolicy
{
    private const BLOCKING_WARNING_CODES = [
        'INPUT_PLACEHOLDER_REQUIRES_REVIEW',
    ];

    public function apply(
        array $validation
    ): array {
        $blockingIssues =
            collect(
                $validation['warnings']
                ??
                []
            )
            ->filter(
                fn(array $issue) =>
                in_array(
                    $issue['code'] ?? null,
                    self::BLOCKING_WARNING_CODES,
                    true
                )
            )
            ->values()
            ->all();

        $validation['blocking_issues'] =
            $blockingIssues;

        $validation['executable'] =
            (bool) ($validation['valid'] ?? false)
            &&
            (bool) ($validation['executable'] ?? false)
            &&
            $blockingIssues === [];

        return $validation;
    }
}

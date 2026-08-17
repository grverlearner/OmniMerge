<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

interface SupportsManualDecision
{
    public function resolveManualDecision(
        array $runtime,
        array $payload
    ): array;
}

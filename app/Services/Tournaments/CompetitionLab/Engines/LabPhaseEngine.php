<?php

namespace App\Services\Tournaments\CompetitionLab\Engines;

use App\Models\PhaseTemplate;

interface LabPhaseEngine
{
    public function supports(
        string $phaseType
    ): bool;

    public function prepare(
        PhaseTemplate $phase,
        array $participantIds,
        array $participants
    ): array;

    public function submit(
        array $runtime,
        string $matchId,
        int $scoreA,
        int $scoreB
    ): array;
}

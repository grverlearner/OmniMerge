<?php

namespace App\Services\Tournaments\CompetitionLab;

class SimulatorParticipantFactory
{
    /**
     * Genera participantes ficticios para el Simulador de fase, sin depender
     * de ningún TournamentStart real. Cada entrada de $participants puede
     * traer 'name' y 'seed' opcionales (editados por el usuario); el resto
     * se completa automáticamente.
     */
    public function generate(array $participants): array
    {
        $generated = [];

        foreach ($participants as $index => $input) {
            $position = $index + 1;

            $previewId = sprintf('P%04d', $position);

            $name = trim((string) ($input['name'] ?? ''));

            if ($name === '') {
                $name = 'Participante ' . str_pad((string) $position, 2, '0', STR_PAD_LEFT);
            }

            $seed = (int) ($input['seed'] ?? $position);

            $generated[] = [
                'preview_id' => $previewId,
                'name' => $name,
                'seed' => max(1, $seed),
                'entity_id' => null,
                'entity_version_id' => null,
                'image_url' => null,
                'manual_bye' => false,
            ];
        }

        return $generated;
    }
}

<?php

namespace App\Services\Tournaments\CompetitionLab;

class SimulatorParticipantFactory
{
    public function __construct(
        private readonly
        \App\Services\Tournaments\Preview\PreviewCastService $cast
    ) {}

    /**
     * Genera participantes ficticios para el Simulador de fase, sin depender
     * de ningún TournamentStart real. Cada entrada de $participants puede
     * traer 'name' y 'seed' opcionales (editados por el usuario); el resto
     * se completa automáticamente.
     *
     * A los que el usuario no bautizó se les presta la cara de una entidad
     * suya. Simular con "Participante 01" contra "Participante 02" no deja
     * ver nada; con caras conocidas se entiende de un vistazo. Es solo
     * decorado: `entity_id` sigue a null y nada de esto se guarda.
     */
    public function generate(array $participants, $user = null): array
    {
        $generated = [];

        $borrowed = $user
            ? $this->cast->borrow($user, count($participants))
            : collect();

        foreach ($participants as $index => $input) {
            $position = $index + 1;

            $previewId = sprintf('P%04d', $position);

            $name = trim((string) ($input['name'] ?? ''));

            $stand = $borrowed->get($index);

            $imageUrl = null;

            if ($name === '') {

                if ($stand && ($stand['is_borrowed'] ?? false)) {
                    $name = $stand['name'];
                    $imageUrl = $stand['image_url'];
                } else {
                    $name = 'Participante ' . str_pad((string) $position, 2, '0', STR_PAD_LEFT);
                }
            }

            $seed = (int) ($input['seed'] ?? $position);

            $generated[] = [
                'preview_id' => $previewId,
                'name' => $name,
                'seed' => max(1, $seed),
                'entity_id' => null,
                'entity_version_id' => null,
                'image_url' => $imageUrl,
                'manual_bye' => false,
            ];
        }

        return $generated;
    }
}

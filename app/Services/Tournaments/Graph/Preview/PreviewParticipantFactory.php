<?php

namespace App\Services\Tournaments\Graph\Preview;

use App\Models\TournamentStart;

class PreviewParticipantFactory
{
    public function __construct(
        private readonly \App\Services\Tournaments\Preview\PreviewCastService $cast
    ) {}

    /**
     * @param  ?" + B + "App" + B + "Models" + B + "User $user  a quien pedirle prestadas las caras
     */
    public function generate(
        TournamentStart $start,
        int $count,
        ?string $prefix = null,
        $user = null
    ): array {

        /*
         * Caras prestadas de las entidades del usuario. Es decorado: nadie
         * queda inscrito y `entity_id` sigue siendo null. Sin esto, el
         * laboratorio muestra una lista de "P1, P2, P3" imposible de seguir
         * cuando el grafo tiene varias fases.
         */
        $borrowed = $user
            ? $this->cast->borrow($user, $count)
            : collect();
        $prefix =
            trim(
                (string) $prefix
            );

        if ($prefix === '') {
            $prefix =
                $this->prefixFrom(
                    $start->name
                );
        }

        $participants = [];

        for (
            $position = 1;
            $position <= $count;
            $position++
        ) {
            $previewId =
                sprintf(
                    'S%03d-P%04d',
                    $start->id,
                    $position
                );

            $participants[] = [
                'preview_id' =>
                $previewId,

                'name' =>
                $prefix
                    .
                    ' '
                    .
                    str_pad(
                        (string) $position,
                        2,
                        '0',
                        STR_PAD_LEFT
                    ),

                'source_start_id' =>
                (int) $start->id,

                'source_start_name' =>
                $start->name,

                'initial_position' =>
                $position,

                'seed' =>
                $position,

                'entity_id' =>
                null,

                'entity_version_id' =>
                null,

                /*
                 * Solo se toma la imagen: el nombre lo pone el prefijo del
                 * Start, que es lo que identifica de donde entra cada uno.
                 */
                'image_url' =>
                $borrowed->get($position - 1)['image_url'] ?? null,

                'borrowed_name' =>
                $borrowed->get($position - 1)['name'] ?? null,

                'journey' => [
                    [
                        'type' =>
                        'START',

                        'id' =>
                        (int) $start->id,

                        'code' =>
                        $start->code,

                        'name' =>
                        $start->name,
                    ],
                ],
            ];
        }

        return $participants;
    }

    public function reorder(
        array $participants,
        string $strategy,
        int $seed
    ): array {
        $participants =
            array_values(
                $participants
            );

        if (
            $strategy
            !==
            'SEEDED_RANDOM'
        ) {
            return $participants;
        }

        usort(
            $participants,
            function (
                array $left,
                array $right
            ) use ($seed) {
                return strcmp(
                    hash(
                        'sha256',
                        $seed
                            .
                            ':'
                            .
                            $left['preview_id']
                    ),
                    hash(
                        'sha256',
                        $seed
                            .
                            ':'
                            .
                            $right['preview_id']
                    )
                );
            }
        );

        foreach (
            $participants
            as
            $index => &$participant
        ) {
            $participant['seed'] =
                $index + 1;
        }

        unset($participant);

        return $participants;
    }

    public function appendJourney(
        array $participants,
        array $location
    ): array {
        return array_map(
            function (
                array $participant
            ) use ($location) {
                $participant['journey'][] =
                    $location;

                return $participant;
            },
            $participants
        );
    }

    private function prefixFrom(
        string $name
    ): string {
        $words =
            preg_split(
                '/\s+/',
                trim($name)
            )
            ?:
            [];

        $prefix =
            collect($words)
            ->filter()
            ->take(2)
            ->map(
                fn($word) =>
                mb_strtoupper(
                    mb_substr(
                        $word,
                        0,
                        1
                    )
                )
            )
            ->implode('');

        return $prefix !== ''
            ? $prefix
            : 'P';
    }
}

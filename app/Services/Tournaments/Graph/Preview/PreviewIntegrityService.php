<?php

namespace App\Services\Tournaments\Graph\Preview;

class PreviewIntegrityService
{
    public function inspect(
        array $initialParticipants,
        array $terminalParticipants,
        array $stoppedParticipants
    ): array {
        $initialIds =
            $this->ids(
                $initialParticipants
            );

        $terminalAppearances =
            $this->ids(
                $terminalParticipants
            );

        $stoppedIds =
            $this->ids(
                $stoppedParticipants
            );

        $terminalUniqueIds =
            array_values(
                array_unique(
                    $terminalAppearances
                )
            );

        $completedOrStopped =
            array_values(
                array_unique([
                    ...$terminalUniqueIds,
                    ...$stoppedIds,
                ])
            );

        $lostIds =
            array_values(
                array_diff(
                    $initialIds,
                    $completedOrStopped
                )
            );

        $appearanceCounts =
            array_count_values(
                $terminalAppearances
            );

        $duplicatedIds =
            array_keys(
                array_filter(
                    $appearanceCounts,
                    fn($count) =>
                    $count > 1
                )
            );

        $errors = [];
        $warnings = [];

        if ($lostIds !== []) {
            $errors[] = [
                'code' =>
                'PARTICIPANTS_LOST',

                'message' =>
                count($lostIds)
                    .
                    ' participantes desaparecieron del flujo sin alcanzar un terminal ni quedar detenidos.',
            ];
        }

        if ($stoppedIds !== []) {
            $warnings[] = [
                'code' =>
                'PARTICIPANTS_STOPPED',

                'message' =>
                count(
                    array_unique(
                        $stoppedIds
                    )
                )
                    .
                    ' participantes quedaron detenidos antes de alcanzar un resultado final.',
            ];
        }

        if ($duplicatedIds !== []) {
            $warnings[] = [
                'code' =>
                'TERMINAL_DUPLICATION',

                'message' =>
                count($duplicatedIds)
                    .
                    ' participantes aparecen en más de un destino final.',
            ];
        }

        return [
            'initial_unique' =>
            count(
                array_unique(
                    $initialIds
                )
            ),

            'terminal_unique' =>
            count(
                $terminalUniqueIds
            ),

            'terminal_appearances' =>
            count(
                $terminalAppearances
            ),

            'stopped_unique' =>
            count(
                array_unique(
                    $stoppedIds
                )
            ),

            'lost_unique' =>
            count(
                $lostIds
            ),

            'duplicated_unique' =>
            count(
                $duplicatedIds
            ),

            'lost_ids' =>
            $lostIds,

            'duplicated_ids' =>
            $duplicatedIds,

            'errors' =>
            $errors,

            'warnings' =>
            $warnings,
        ];
    }

    private function ids(
        array $participants
    ): array {
        return array_values(
            array_filter(
                array_map(
                    fn($participant) =>
                    $participant['preview_id']
                        ??
                        null,
                    $participants
                )
            )
        );
    }
}

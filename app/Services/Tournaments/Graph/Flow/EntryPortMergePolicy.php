<?php

namespace App\Services\Tournaments\Graph\Flow;

final class EntryPortMergePolicy
{
    public static function merge(
        ?string $policy,
        array $orderedConnectionIds,
        array $receivedConnectionIds,
        array $payloads
    ): array {
        $policy = strtoupper($policy ?: 'APPEND');
        $orderedConnectionIds = self::normalizeIds($orderedConnectionIds);
        $receivedConnectionIds = self::normalizeIds($receivedConnectionIds);

        if ($policy === 'FIRST_AVAILABLE') {
            return self::firstPayload(
                $orderedConnectionIds,
                $payloads
            );
        }

        if ($policy === 'PRIORITY') {
            return self::firstPayload(
                $orderedConnectionIds,
                $payloads
            );
        }

        return self::appendPayloads(
            $orderedConnectionIds,
            $payloads
        );
    }

    public static function allFinal(
        array $connectionIds,
        array $connectionStates
    ): bool {
        $connectionIds = self::normalizeIds($connectionIds);

        if ($connectionIds === []) {
            return false;
        }

        foreach ($connectionIds as $connectionId) {
            $status = $connectionStates[$connectionId]['status'] ?? 'PENDING';

            if (! in_array($status, ['ROUTED', 'CLOSED_EMPTY'], true)) {
                return false;
            }
        }

        return true;
    }

    private static function firstPayload(
        array $connectionIds,
        array $payloads
    ): array {
        foreach ($connectionIds as $connectionId) {
            $participants = self::normalizeIds(
                $payloads[$connectionId] ?? []
            );

            if ($participants !== []) {
                return $participants;
            }
        }

        return [];
    }

    private static function appendPayloads(
        array $connectionIds,
        array $payloads
    ): array {
        $participants = [];

        foreach ($connectionIds as $connectionId) {
            foreach (self::normalizeIds($payloads[$connectionId] ?? []) as $participantId) {
                if (! in_array($participantId, $participants, true)) {
                    $participants[] = $participantId;
                }
            }
        }

        return $participants;
    }

    /*
     * Quita huecos y repetidos de una lista de participantes.
     *
     * Se hacia con array_unique, y array_unique compara sus elementos
     * CONVERTIDOS A TEXTO. Con ids sueltos eso funciona; pero por aqui
     * pasan tambien participantes completos -arrays-, y todos los arrays se
     * convierten a la misma cadena, "Array".
     *
     * O sea que no es que avisara y ya: colapsaba la lista entera a UN solo
     * participante. Tres que llegaban por una conexion salian uno, y los
     * otros dos desaparecian sin dejar rastro ni error. En el preview de un
     * torneo eso se veia como "16 entraron, 1 llego al final, 15 perdidos".
     *
     * Comparar en estricto no convierte nada: dos arrays son el mismo si
     * tienen las mismas claves con los mismos valores.
     */
    private static function normalizeIds(array $ids): array
    {
        $out = [];

        foreach ($ids as $id) {

            if ($id === null || $id === '' || $id === []) {
                continue;
            }

            if (! in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }
}

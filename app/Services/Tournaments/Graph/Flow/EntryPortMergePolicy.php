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

    private static function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            $ids,
            static fn($id) => $id !== null && $id !== ''
        )));
    }
}

<?php

namespace App\Services\Universes;

use App\Models\TournamentStart;
use App\Models\Universe;
use App\Models\UniverseTournament;

/*
|--------------------------------------------------------------------------
| EditionParticipants
|--------------------------------------------------------------------------
|
| Quién entra en UNA edición.
|
| Dos fuentes posibles:
|
|   TOURNAMENT  lo que el torneo configuró en su sala: sus condiciones, su
|               reparto por puertas —si es para la misma plantilla— y sus
|               caras. La edición no guarda nada propio.
|
|   CUSTOM      lo suyo, solo para esta edición:
|                 scope TOURNAMENT  parte de los que el torneo deja entrar
|                                   y los estrecha con sus condiciones
|                 scope UNIVERSE    parte del universo entero
|               con su propio reparto y sus propias caras, que se suman a
|               las que el torneo eligió a mano.
|
| La pantalla lo calcula en el acto con el mismo criterio
| (resources/js/universes/participant-room.js), pero lo que vale es esto:
| al crear o rehacer una edición se recalcula aquí desde el diseño.
|
| Ver docs/md/80-Participantes-De-Cada-Edicion.md
|
*/

class EditionParticipants
{
    public function __construct(
        private readonly UniverseTournamentEligibility $eligibility,
        private readonly CompetitionStartRouting $routing,
    ) {
    }

    public function normalize(?array $design): array
    {
        $design ??= [];

        return [
            'source' => strtoupper((string) ($design['source'] ?? 'TOURNAMENT')) === 'CUSTOM' ? 'CUSTOM' : 'TOURNAMENT',
            'scope' => strtoupper((string) ($design['scope'] ?? 'TOURNAMENT')) === 'UNIVERSE' ? 'UNIVERSE' : 'TOURNAMENT',
        ]
            + $this->eligibility->normalize($design)
            + ['doors' => $this->routing->normalizeDoors($design['doors'] ?? null)];
    }

    /*
     * El reparto del torneo, si vale para esta plantilla. Uno pensado para
     * las puertas de otra plantilla no sirve: se reparte solo, equilibrado.
     */
    public function tournamentDoors(UniverseTournament $tournament, ?int $templateId): array
    {
        $doors = $this->routing->normalizeDoors($tournament->eligibility['doors'] ?? null);

        if ($doors['template_id'] && $templateId && (int) $doors['template_id'] !== (int) $templateId) {
            return $this->routing->normalizeDoors(null);
        }

        return $doors;
    }

    /* Las puertas de una plantilla, en orden, con sus plazas */
    public function startsOf(int $templateId): array
    {
        return TournamentStart::query()
            ->where('tournament_template_id', $templateId)
            ->where('status', 'ACTIVE')
            ->orderBy('sequence_number')
            ->get()
            ->mapWithKeys(fn ($s) => [(int) $s->id => $s->expected_participants ? (int) $s->expected_participants : null])
            ->all();
    }

    /*
     * Quién entra y por qué puerta.
     *
     * @return array{assignments: array<int,array<int,int>>, leftovers: array<int,int>, overflow: array}
     */
    public function resolve(Universe $universe, UniverseTournament $tournament, ?array $design, int $templateId): array
    {
        $d = $this->normalize($design);
        $torneo = (array) ($tournament->eligibility ?? []);
        $puertas = $this->startsOf($templateId);

        if ($d['source'] === 'TOURNAMENT') {
            return $this->routing->plan($universe, $this->tournamentDoors($tournament, $templateId), $puertas, $torneo);
        }

        $pool = $this->eligibility->matching($universe, $d['scope'] === 'TOURNAMENT' ? $torneo : null);

        /* Las condiciones y la mano de la edición, sobre ese punto de partida */
        $pool = $this->eligibility->matchingWithin($pool, $d);

        return $this->routing->planWithin($pool, $d['doors'], $puertas);
    }

    /*
     * Las reglas con las que se elige la cara de cada uno.
     *
     * Las condiciones de la edición si escribió alguna; si no, las del
     * torneo. Las caras elegidas a mano en la edición ganan a las del
     * torneo, y las del torneo que la edición no tocó siguen valiendo.
     */
    public function faceContext(UniverseTournament $tournament, ?array $design): array
    {
        $torneo = (array) ($tournament->eligibility ?? []);
        $d = $this->normalize($design);

        if ($d['source'] === 'TOURNAMENT') {
            return $torneo;
        }

        $base = $this->eligibility->normalize($torneo);
        $reglas = ($d['rules'] !== [] || $d['groups'] !== []) ? $d : $base;

        return [
            'mode' => $reglas['mode'],
            'rules' => $reglas['rules'],
            'groups' => $reglas['groups'],
            'faces' => $d['faces'] + $base['faces'],
            'face_mode' => $d['face_mode'],
        ];
    }

    /* La regla de cada puerta, cuando el reparto es por reglas */
    public function doorRules(UniverseTournament $tournament, ?array $design, int $templateId): array
    {
        $d = $this->normalize($design);

        $doors = $d['source'] === 'TOURNAMENT'
            ? $this->tournamentDoors($tournament, $templateId)
            : $d['doors'];

        return $doors['mode'] === 'RULES'
            ? collect($doors['rules'])->keyBy('start_id')->all()
            : [];
    }

    /*
     * Una edición de antes de esto: tenía gente repartida pero ningún
     * diseño. Se convierte en uno propio, a mano, para poder retocarla sin
     * perder el reparto que ya tenía.
     */
    public function fromAssignments(array $assignments): array
    {
        return $this->normalize([
            'source' => 'CUSTOM',
            'scope' => 'UNIVERSE',
            'doors' => ['mode' => 'MANUAL', 'manual' => $assignments],
        ]);
    }
}

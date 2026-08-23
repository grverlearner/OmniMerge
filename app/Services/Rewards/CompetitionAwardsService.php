<?php

namespace App\Services\Rewards;

use App\Models\TournamentInstance;
use App\Models\UniverseStatChange;
use App\Services\Games\GameRegistry;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| CompetitionAwardsService
|--------------------------------------------------------------------------
|
| Qué se ha llevado cada competidor de esta competición.
|
| El problema
| -----------
| Los premios existían pero eran invisibles. Un bonus temporal solo se
| notaba como un número distinto dentro de una batalla, y una recompensa
| permanente solo se veía yendo a la ficha del competidor y comparando de
| memoria. No había ningún sitio donde mirar y entender qué había cambiado
| y por qué.
|
| Y sobre todo: no se distinguía lo que DURA de lo que NO. Un +1 que se
| evapora al acabar el torneo y un +1 que queda grabado en el competidor
| se parecen mucho en una tarjeta y no se parecen en nada de verdad.
|
| Las dos naturalezas
| -------------------
| TEMPORAL   vive en el estado de la competición. Se aplica al preparar
|            cada enfrentamiento y desaparece con ella. No hay rastro en
|            el competidor.
|
| PERMANENTE está escrito en universe_stat_changes: cambió el valor
|            guardado y se queda. Lleva su valor antes y después, porque
|            eso es lo que hace auditable un cambio permanente.
|
*/

class CompetitionAwardsService
{
    public function __construct(
        private readonly GameRegistry $registry
    ) {}

    /**
     * Todo lo concedido, agrupado por competidor.
     *
     * @return Collection<int, array>
     */
    public function forInstance(TournamentInstance $instance): Collection
    {
        $temporary = $this->temporary($instance);

        $permanent = $this->permanent($instance);

        $names =
            $instance->participants()
            ->get()
            ->keyBy('universe_entity_id');

        return $temporary
            ->keys()
            ->merge($permanent->keys())
            ->unique()
            ->map(
                function ($entityId) use ($temporary, $permanent, $names) {

                    $participant = $names->get($entityId);

                    return [
                        'universe_entity_id' => $entityId,

                        'name' =>
                        $participant?->participant_name
                            ?? $participant?->universeEntity?->display_label
                            ?? 'Competidor',

                        'image_url' =>
                        $participant?->universeEntity?->image_url,

                        'temporary' => $temporary->get($entityId, collect())->values(),
                        'permanent' => $permanent->get($entityId, collect())->values(),
                    ];
                }
            )
            /* Primero quien más se llevó */
            ->sortByDesc(
                fn(array $row) =>
                count($row['permanent']) * 100 + count($row['temporary'])
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Temporal: lo que vive en el estado
    |--------------------------------------------------------------------------
    */

    private function temporary(TournamentInstance $instance): Collection
    {
        $modifiers = $instance->state?->state['modifiers'] ?? [];

        return collect($modifiers)
            ->filter(
                function ($modifier) {

                    /*
                     * Una regla de podio sin resolver no es un premio de
                     * nadie: es la promesa de uno. Solo cuentan las
                     * entradas que ya apuntan a un competidor.
                     */
                    return ($modifier['target'] ?? null) === 'ENTITY'
                        && ! empty($modifier['universe_entity_id']);
                }
            )
            ->map(
                fn(array $modifier) => [

                    'universe_entity_id' =>
                    (int) $modifier['universe_entity_id'],

                    'stat_key' =>
                    (string) ($modifier['stat_key'] ?? ''),

                    'stat_label' =>
                    $this->statLabel(
                        $modifier['game_key'] ?? $instance->game_key,
                        (string) ($modifier['stat_key'] ?? '')
                    ),

                    'effect' =>
                    $this->effect($modifier),

                    'label' =>
                    $modifier['label'] ?? null,

                    /* De dónde salió: ganado jugando, o puesto de antemano */
                    'earned' =>
                    isset($modifier['granted_key']),

                    'phase' =>
                    $modifier['granted_phase'] ?? null,

                    'position' =>
                    $modifier['granted_position'] ?? null,

                    'scope' =>
                    $modifier['scope'] ?? 'TOURNAMENT',

                    'scope_value' =>
                    $modifier['scope_value'] ?? null,
                ]
            )
            ->groupBy('universe_entity_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Permanente: lo que quedó escrito
    |--------------------------------------------------------------------------
    */

    private function permanent(TournamentInstance $instance): Collection
    {
        return UniverseStatChange::query()
            ->where('tournament_instance_id', $instance->id)
            ->with('reward.trophy')
            ->orderBy('id')
            ->get()
            ->map(
                fn(UniverseStatChange $change) => [

                    'universe_entity_id' =>
                    (int) $change->universe_entity_id,

                    'stat_key' =>
                    (string) $change->stat_key,

                    'stat_label' =>
                    $this->statLabel($change->game_key, (string) $change->stat_key),

                    'game_key' =>
                    $change->game_key,

                    'before' =>
                    (float) $change->value_before,

                    'after' =>
                    (float) $change->value_after,

                    'delta' =>
                    (float) $change->delta,

                    'reason' =>
                    $change->reason,

                    'source' =>
                    $change->source_type,

                    'trophy' =>
                    $change->reward?->trophy?->name,
                ]
            )
            ->groupBy('universe_entity_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Piezas
    |--------------------------------------------------------------------------
    */

    /**
     * El nombre humano de la stat. El esquema lo declara el Game Engine,
     * así que se pregunta ahí en vez de traducir a mano.
     */
    private function statLabel(?string $gameKey, string $statKey): string
    {
        if (! $gameKey || $statKey === '') {
            return $statKey;
        }

        try {
            $definition = $this->registry->definition($gameKey);
        } catch (\Throwable) {
            return $statKey;
        }

        foreach (($definition['stats'] ?? []) as $stat) {

            if (($stat['key'] ?? null) === $statKey) {
                return $stat['label'] ?? $statKey;
            }
        }

        return $statKey;
    }

    private function effect(array $modifier): string
    {
        $amount =
            rtrim(
                rtrim(
                    number_format((float) ($modifier['amount'] ?? 0), 3, '.', ''),
                    '0'
                ),
                '.'
            );

        return match ($modifier['operation'] ?? 'ADD') {
            'SUBTRACT' => '−' . $amount,
            'MULTIPLY' => '×' . $amount,
            'SET' => '= ' . $amount,
            default => '+' . $amount,
        };
    }
}

<?php

namespace App\Services\Universes;

use App\Models\Entity;
use App\Models\Universe;
use App\Models\UniverseCompetitor;
use Illuminate\Support\Facades\DB;

class UniverseCompetitorService
{
    /*
    |--------------------------------------------------------------------------
    | Alta masiva
    |--------------------------------------------------------------------------
    |
    | Idempotente: las Entidades que ya son competidoras del Universo
    | se ignoran en silencio, de forma que reenviar el formulario no
    | produce duplicados ni errores.
    |
    | Solo se aceptan Entidades del mismo propietario que el Universo.
    |
    */

    public function addEntities(
        Universe $universe,
        array $entityIds
    ): int {

        $entityIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $entityIds
                        )
                    )
                )
            );

        if (! $entityIds) {
            return 0;
        }

        /*
         * Filtro de propiedad: nunca se incorpora una Entity
         * que no pertenezca al dueño del Universo.
         */
        $ownedIds =
            Entity::query()
            ->where(
                'user_id',
                $universe->user_id
            )
            ->whereIn(
                'id',
                $entityIds
            )
            ->pluck('id')
            ->all();

        if (! $ownedIds) {
            return 0;
        }

        /*
         * Filtro de duplicados.
         */
        $existingIds =
            $universe
            ->competitors()
            ->whereIn(
                'entity_id',
                $ownedIds
            )
            ->pluck('entity_id')
            ->all();

        $newIds =
            array_values(
                array_diff(
                    $ownedIds,
                    $existingIds
                )
            );

        if (! $newIds) {
            return 0;
        }

        $now = now();

        $rows = [];

        foreach ($newIds as $entityId) {

            $rows[] = [

                'universe_id' =>
                $universe->id,

                'entity_id' =>
                $entityId,

                'display_name' =>
                null,

                'status' =>
                'ACTIVE',

                'notes' =>
                null,

                'created_at' =>
                $now,

                'updated_at' =>
                $now,
            ];
        }

        DB::transaction(
            function () use ($rows) {

                UniverseCompetitor::query()
                    ->insert($rows);
            }
        );

        return count($rows);
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar contexto
    |--------------------------------------------------------------------------
    */

    public function update(
        UniverseCompetitor $competitor,
        array $data
    ): UniverseCompetitor {

        $data['display_name'] =
            trim(
                (string)
                ($data['display_name'] ?? '')
            )
            ?: null;

        $competitor->update(
            $data
        );

        return $competitor->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Quitar del Universo
    |--------------------------------------------------------------------------
    |
    | Borrado real de la asociación. La Entity de la Biblioteca
    | permanece intacta.
    |
    */

    public function remove(
        UniverseCompetitor $competitor
    ): void {

        $competitor->delete();
    }
}

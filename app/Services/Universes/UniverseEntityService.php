<?php

namespace App\Services\Universes;

use App\Models\UniverseEntity;

/*
|--------------------------------------------------------------------------
| UniverseEntityService
|--------------------------------------------------------------------------
|
| Mantenimiento de una entidad ya importada al Universo.
|
| La importación en sí vive en UniverseEntityImporter, que es quien hace
| la copia desde la Biblioteca.
|
*/

class UniverseEntityService
{
    /*
    |--------------------------------------------------------------------------
    | Actualizar contexto
    |--------------------------------------------------------------------------
    |
    | Solo toca lo que pertenece al Universo. Nada de esto viaja de vuelta
    | a la Biblioteca.
    |
    */

    public function update(
        UniverseEntity $entity,
        array $data
    ): UniverseEntity {

        $data['display_name'] =
            trim((string) ($data['display_name'] ?? ''))
            ?: null;

        $entity->update($data);

        return $entity->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Quitar del Universo
    |--------------------------------------------------------------------------
    |
    | Borra la copia del Universo. La Entidad de la Biblioteca queda
    | intacta: era el origen, no la misma fila.
    |
    | Las competiciones ya jugadas conservan su historial: los
    | participantes guardan el nombre congelado y solo pierden el enlace
    | (universe_entity_id queda a nulo por nullOnDelete).
    |
    */

    public function remove(
        UniverseEntity $entity
    ): void {

        $entity->delete();
    }
}

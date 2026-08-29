<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\UniverseEntity;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| CompetitionStartRouting
|--------------------------------------------------------------------------
|
| Quien entra por cada puerta.
|
| Un grafo puede tener varios puntos de entrada -"los campeones entran en
| semifinales, el resto desde la primera ronda"- y hasta ahora repartirlos
| era marcar competidor por competidor en cada caja.
|
| Aqui se describe con una regla: "los que lleven doujutsu -> sharingan
| entran por la puerta de invitados". La regla se guarda con la edicion,
| asi que la siguiente puede copiarse entera sin volver a marcar a nadie.
|
| ---------------------------------------------------------------
|
| Forma de `start_rules`:
|
|   [
|     { "start_id": 3, "mode": "ALL", "rules": [ {attribute, values[]} ] },
|     ...
|   ]
|
| El resultado sigue siendo el mismo `assignments[startId][] = entityId`
| que ya entendia el servicio de creacion: esto es una forma de escribirlo,
| no un mecanismo nuevo.
|
*/
class CompetitionStartRouting
{
    public function __construct(
        private readonly UniverseTournamentEligibility $eligibility,
    ) {
    }

    /*
     * Limpia lo que llego del formulario.
     *
     * @return array<int,array{start_id:int,mode:string,rules:array}>
     */
    public function normalize(?array $startRules): array
    {
        return collect($startRules ?? [])
            ->map(function ($row) {

                if (! is_array($row)) {
                    return null;
                }

                $startId = (int) ($row['start_id'] ?? 0);

                if ($startId <= 0) {
                    return null;
                }

                /*
                 * La fila entera, no solo mode y rules: una puerta habla el
                 * mismo lenguaje que un torneo -grupos y mano incluidos-, y
                 * copiar campo a campo dejaba fuera lo que se anadiese
                 * despues. Aqui ya paso: los grupos se perdian en silencio.
                 */
                $clean = $this->eligibility->normalize($row);

                return ['start_id' => $startId] + $clean;
            })
            ->filter()
            /* Una puerta, una regla: la ultima gana */
            ->keyBy('start_id')
            ->values()
            ->all();
    }

    /*
     * A quien manda cada regla, sin repetir a nadie.
     *
     * Un competidor solo entra por una puerta. Cuando dos reglas lo
     * reclaman gana la PRIMERA, que es el orden en el que estan escritas
     * en la pantalla: si "los campeones" va antes que "todos los demas",
     * un campeon entra por la puerta de campeones y no por las dos.
     *
     * @param  array<int,int>  $capacities  start_id => plazas, o null
     * @return array{
     *     assignments: array<int,array<int,int>>,
     *     leftovers: array<int,int>,
     *     overflow: array<int,array<int,int>>
     * }
     */
    public function route(
        Universe $universe,
        array $startRules,
        array $capacities = []
    ): array {

        $rules = $this->normalize($startRules);

        $pool = $this->eligibility
            ->matching($universe, null)
            ->keyBy('id');

        $taken = [];

        $assignments = [];

        $overflow = [];

        foreach ($rules as $row) {

            $startId = $row['start_id'];

            $matched = $pool
                ->reject(fn (UniverseEntity $entity) => isset($taken[$entity->id]))
                ->pipe(
                    fn (Collection $available) => $this->filter($available, $row)
                );

            $capacity = $capacities[$startId] ?? null;

            $ids = $matched->pluck('id')->map(fn ($id) => (int) $id)->all();

            /*
             * Sobrar no es un error que deba tragarse en silencio: la
             * pantalla tiene que poder decir "caben 8 y la regla trae 11".
             */
            if ($capacity !== null && count($ids) > $capacity) {
                $overflow[$startId] = array_slice($ids, $capacity);
                $ids = array_slice($ids, 0, $capacity);
            }

            foreach ($ids as $id) {
                $taken[$id] = true;
            }

            $assignments[$startId] = $ids;
        }

        return [
            'assignments' => $assignments,

            /* Los que ninguna regla reclamo */
            'leftovers' => $pool
                ->reject(fn (UniverseEntity $entity) => isset($taken[$entity->id]))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),

            'overflow' => $overflow,
        ];
    }

    /*
     * Una regla sin condiciones significa "todos los que queden": es la
     * puerta general, y es lo que hace que el caso normal -un solo inicio-
     * no necesite configurar nada.
     */
    private function filter(Collection $entities, array $row): Collection
    {
        return $this->eligibility->matchingWithin($entities, $row);
    }
}

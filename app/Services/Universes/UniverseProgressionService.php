<?php

namespace App\Services\Universes;

use App\Models\UniverseEntity;

/*
|--------------------------------------------------------------------------
| UniverseProgressionService
|--------------------------------------------------------------------------
|
| PREPARACIÓN, no funcionalidad activa.
|
| Da forma y punto de entrada a la progresión de un participante dentro
| del Universo, para que una recompensa futura ("ganar un torneo → +5 al
| límite superior") tenga dónde escribir sin rehacer el modelo.
|
| Hoy NADIE llama a adjust(): ningún motor modifica valores todavía. Lo
| único que se ejecuta es initialize(), al importar, para que los
| valores numéricos ya copiados tengan una línea base visible.
|
| Ver docs/md/28-Fase-10-Universo-Vivo.md §"Progresión preparada"
|
*/

class UniverseProgressionService
{
    private const DEFAULT_MIN = 1;
    private const DEFAULT_MAX = 100;

    /*
    |--------------------------------------------------------------------------
    | Línea base
    |--------------------------------------------------------------------------
    |
    | Se construye a partir de los atributos NUMÉRICOS ya congelados al
    | importar. Los de texto no entran: no tiene sentido "progresar" en
    | un atributo que vale "Konoha".
    |
    */

    public function initialize(
        UniverseEntity $entity
    ): array {

        $progression = [];

        foreach (($entity->attribute_snapshot ?? []) as $attribute) {

            $value = $attribute['numeric'] ?? null;

            if ($value === null) {
                continue;
            }

            $progression[$attribute['name']] = [
                'initial' => (float) $value,
                'current' => (float) $value,
                'min' => self::DEFAULT_MIN,
                'max' => self::DEFAULT_MAX,
            ];
        }

        return $progression;
    }

    /*
    |--------------------------------------------------------------------------
    | Ajuste
    |--------------------------------------------------------------------------
    |
    | $changes = ['Poder' => ['current' => +5], 'Velocidad' => ['max' => +10]]
    |
    | Respeta los límites y no deja que current salga de [min, max].
    | Existe para el futuro; ninguna parte del sistema lo invoca aún.
    |
    */

    public function adjust(
        UniverseEntity $entity,
        array $changes
    ): UniverseEntity {

        $progression = $entity->progression ?? [];

        foreach ($changes as $name => $deltas) {

            if (! isset($progression[$name])) {
                continue;
            }

            foreach (['min', 'max', 'current'] as $key) {

                if (! array_key_exists($key, $deltas)) {
                    continue;
                }

                $progression[$name][$key] =
                    (float) $progression[$name][$key]
                    + (float) $deltas[$key];
            }

            $progression[$name]['current'] = max(
                $progression[$name]['min'],
                min(
                    $progression[$name]['max'],
                    $progression[$name]['current']
                )
            );
        }

        $entity->update([
            'progression' => $progression,
        ]);

        return $entity->fresh();
    }
}

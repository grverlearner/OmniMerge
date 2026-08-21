<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\PhaseTemplate;
use App\Models\Snapshots\SnapshotPhaseTemplate;
use App\Models\Snapshots\SnapshotTournamentTemplate;
use App\Models\TournamentTemplate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| TournamentSnapshotHydrator
|--------------------------------------------------------------------------
|
| Reconstruye la configuración congelada como un árbol de modelos
| Eloquent en memoria, con todas las relaciones ya establecidas.
|
| El motor recibe un TournamentTemplate normal y funciona exactamente
| igual que con la plantilla viva: no hay una segunda implementación
| del motor ni una segunda forma de leer la configuración.
|
| TournamentTemplate y PhaseTemplate se sustituyen por sus subclases
| Snapshot*, que anulan load()/loadMissing() para que el motor no pueda
| refrescar contra la configuración actual.
|
*/

class TournamentSnapshotHydrator
{
    /*
     * Clases que deben reemplazarse al rehidratar.
     */
    private const SUBSTITUTIONS = [

        TournamentTemplate::class =>
        SnapshotTournamentTemplate::class,

        PhaseTemplate::class =>
        SnapshotPhaseTemplate::class,
    ];

    public function hydrate(
        array $snapshot
    ): TournamentTemplate {

        $version =
            (int)
            ($snapshot['schema_version'] ?? 0);

        if (
            $version
            !==
            TournamentSnapshotBuilder::SCHEMA_VERSION
        ) {

            throw ValidationException::withMessages([
                'snapshot' => [
                    'Esta competición se creó con un formato de configuración '
                        . 'antiguo (versión ' . $version . ') que ya no puede '
                        . 'ejecutarse.',
                ],
            ]);
        }

        $root =
            $snapshot['root'] ?? null;

        if (! is_array($root)) {

            throw ValidationException::withMessages([
                'snapshot' => [
                    'La configuración congelada de esta competición está incompleta.',
                ],
            ]);
        }

        $template =
            $this->build($root);

        if (! $template instanceof TournamentTemplate) {

            throw ValidationException::withMessages([
                'snapshot' => [
                    'La configuración congelada no corresponde a una plantilla de torneo.',
                ],
            ]);
        }

        return $template;
    }

    private function build(
        array $node
    ): Model {

        $class =
            $node['class'] ?? null;

        if (
            ! is_string($class)
            ||
            ! class_exists($class)
        ) {

            throw ValidationException::withMessages([
                'snapshot' => [
                    'La configuración congelada contiene un elemento no reconocible.',
                ],
            ]);
        }

        $class =
            self::SUBSTITUTIONS[$class]
            ?? $class;

        /** @var Model $model */
        $model =
            new $class();

        $model->setRawAttributes(
            $node['attributes'] ?? [],
            true
        );

        /*
         * exists = true para que las claves y las comparaciones se
         * comporten igual que con un modelo real. El modelo nunca se
         * guarda: las subclases Snapshot* bloquean save().
         */
        $model->exists = true;

        foreach (
            ($node['relations'] ?? [])
            as
            $name => $related
        ) {

            if ($related === null) {

                $model->setRelation(
                    $name,
                    null
                );

                continue;
            }

            /*
             * Una lista es una relación "muchos"; un mapa con 'class'
             * es una relación "uno".
             */
            if (
                array_key_exists(
                    'class',
                    $related
                )
            ) {

                $model->setRelation(
                    $name,
                    $this->build($related)
                );

                continue;
            }

            $model->setRelation(
                $name,
                new EloquentCollection(
                    array_map(
                        fn(array $item) =>
                        $this->build($item),
                        $related
                    )
                )
            );
        }

        return $model;
    }
}

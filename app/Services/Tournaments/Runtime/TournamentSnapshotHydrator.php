<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\PhaseTemplate;
use App\Models\Snapshots\SnapshotPhaseTemplate;
use App\Models\Snapshots\SnapshotTournamentTemplate;
use App\Models\TournamentInstance;
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

    /*
     * @param  ?TournamentInstance  $competition  Si se pasa, su formato de
     *         batalla pisa el de las plantillas: cuantos juegos dura un
     *         enfrentamiento lo decide la competicion, no la forma del
     *         torneo. Ver CompetitionBattleFormat.
     */
    public function hydrate(
        array $snapshot,
        ?TournamentInstance $competition = null
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

        if ($competition !== null) {
            $this->applyBattleFormat($template, $competition);
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
    /*
     * El formato de batalla de la competicion, sobre cada fase.
     *
     * Se hace aqui y no en los cinco motores porque aqui es donde el
     * torneo se reconstruye para jugarse: un solo sitio, y ningun motor
     * puede quedarse atras.
     *
     * Los modelos que salen del hidratador son Snapshot* y tienen save()
     * bloqueado, asi que esto no puede tocar la plantilla de nadie ni
     * aunque quisiera.
     */
    private function applyBattleFormat(
        TournamentTemplate $template,
        TournamentInstance $competition
    ): void {

        /*
         * Al CREAR una competicion todavia no hay fila ni fases: llega una
         * competicion en memoria que solo trae el formato elegido, y
         * preguntar por sus fases seria una consulta con id nulo.
         */
        if ($competition->exists) {
            $competition->loadMissing('phases');
        } else {
            $competition->setRelation('phases', collect());
        }

        $formats = app(CompetitionBattleFormat::class);

        foreach ($template->graphNodes as $node) {

            $phase = $node->phaseTemplate;

            if ($phase === null) {
                continue;
            }

            $formats->applyTo($phase, $competition, (int) $node->id);
        }
    }

}

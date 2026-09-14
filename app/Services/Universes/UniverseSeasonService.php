<?php

namespace App\Services\Universes;

use App\Models\Universe;
use App\Models\UniverseSeason;
use Illuminate\Support\Facades\DB;

class UniverseSeasonService
{
    public function __construct(
        private readonly
        UniverseActivityRecorder $activity
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Numeración correlativa
    |--------------------------------------------------------------------------
    */

    public function nextNumber(
        Universe $universe
    ): int {

        return (
            (int)
            UniverseSeason::withTrashed()
                ->where(
                    'universe_id',
                    $universe->id
                )
                ->max('number')
        )
            +
            1;
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function create(
        Universe $universe,
        array $data
    ): UniverseSeason {

        return DB::transaction(
            function () use (
                $universe,
                $data
            ) {

                $data['number'] =
                    $this->nextNumber(
                        $universe
                    );

                $season =
                    $universe
                    ->seasons()
                    ->create($data);

                if (
                    $season->status === 'ACTIVE'
                ) {

                    $this->demoteOtherActiveSeasons(
                        $universe,
                        $season->id
                    );
                }

                return $season;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear varias de golpe
    |--------------------------------------------------------------------------
    |
    | Un mundo con historia necesita diez temporadas, no una. Crearlas de una
    | en una es diez veces el mismo formulario, asi que esto las monta juntas.
    |
    | La estructura es deliberadamente pequena —cuatro decisiones— porque cada
    | opcion de mas es una pregunta que hay que contestar diez veces mentalmente
    | antes de pulsar:
    |
    |   cuantas    de 1 a 50
    |   patron     el nombre, con {n} sustituido por el numero de cada una
    |   fechas     opcional: cuando empieza la primera y cuanto dura cada una;
    |              la siguiente arranca donde termino la anterior
    |   activa     si la primera de la tanda queda en curso
    |
    | La numeracion no se pregunta: sigue siendo correlativa al universo, que
    | es lo unico que garantiza que la recurrencia de los torneos cuadre.
    |
    | @return \Illuminate\Support\Collection<int, UniverseSeason>
    */

    public function createMany(
        Universe $universe,
        array $data
    ) {

        return DB::transaction(
            function () use ($universe, $data) {

                $cuantas = max(1, min(50, (int) ($data['count'] ?? 1)));

                $patron = trim((string) ($data['name_pattern'] ?? 'Temporada {n}'));

                if ($patron === '') {
                    $patron = 'Temporada {n}';
                }

                $duracion = (int) ($data['duration'] ?? 0);

                $unidad = (string) ($data['duration_unit'] ?? 'months');

                $cursor =
                    ! empty($data['starts_at'])
                    ? \Carbon\Carbon::parse($data['starts_at'])->startOfDay()
                    : null;

                $primera = (string) ($data['first_status'] ?? 'PLANNED');

                $numero = $this->nextNumber($universe);

                $creadas = collect();

                for ($i = 0; $i < $cuantas; $i++) {

                    $fin = null;

                    if ($cursor && $duracion > 0) {

                        /*
                         * Termina un dia antes de que empiece la siguiente: dos
                         * temporadas no pueden compartir el mismo dia.
                         */
                        $fin = (clone $cursor)
                            ->add($unidad, $duracion)
                            ->subDay()
                            ->endOfDay();
                    }

                    $temporada =
                        $universe
                        ->seasons()
                        ->create([
                            'number' => $numero,

                            'name' => str_replace(
                                ['{n}', '{N}'],
                                (string) $numero,
                                $patron
                            ),

                            'description' =>
                            $data['description'] ?? null,

                            'status' =>
                            $i === 0 && $primera === 'ACTIVE'
                                ? 'ACTIVE'
                                : 'PLANNED',

                            'starts_at' => $cursor?->copy(),

                            'ends_at' => $fin,
                        ]);

                    $creadas->push($temporada);

                    if ($temporada->status === 'ACTIVE') {

                        $this->demoteOtherActiveSeasons(
                            $universe,
                            $temporada->id
                        );
                    }

                    $numero++;

                    if ($cursor && $duracion > 0) {
                        $cursor = (clone $cursor)->add($unidad, $duracion);
                    }
                }

                return $creadas;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        UniverseSeason $season,
        array $data
    ): UniverseSeason {

        return DB::transaction(
            function () use (
                $season,
                $data
            ) {

                $season->update($data);

                if (
                    $season->status === 'ACTIVE'
                ) {

                    $this->demoteOtherActiveSeasons(
                        $season->universe,
                        $season->id
                    );
                }

                return $season->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activar
    |--------------------------------------------------------------------------
    |
    | Un Universo solo puede tener una temporada en curso. Activar
    | una temporada finaliza la anterior.
    |
    */

    public function activate(
        UniverseSeason $season
    ): void {

        DB::transaction(
            function () use ($season) {

                $this->demoteOtherActiveSeasons(
                    $season->universe,
                    $season->id
                );

                $season->update([

                    'status' =>
                    'ACTIVE',
                ]);

                $this->activity->seasonStarted($season);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Finalizar
    |--------------------------------------------------------------------------
    */

    public function complete(
        UniverseSeason $season
    ): void {

        $season->update([

            'status' =>
            'COMPLETED',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Archivar
    |--------------------------------------------------------------------------
    */

    public function archive(
        UniverseSeason $season
    ): void {

        $season->update([

            'status' =>
            'ARCHIVED',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function delete(
        UniverseSeason $season
    ): void {

        $season->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Regla de temporada única en curso
    |--------------------------------------------------------------------------
    */

    private function demoteOtherActiveSeasons(
        Universe $universe,
        int $keepSeasonId
    ): void {

        $universe
            ->seasons()
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereKeyNot(
                $keepSeasonId
            )
            ->update([

                'status' =>
                'COMPLETED',
            ]);
    }
}

<?php

namespace App\Http\Requests\Universes;

use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| StoreTournamentInstanceRequest
|--------------------------------------------------------------------------
|
| Crear una competición real a partir de un torneo configurado del
| Universo, repartiendo competidores entre los puntos de entrada del
| Tournament Graph.
|
*/

class StoreTournamentInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $universe =
            $this->route('universe');

        return
            $universe
            instanceof
            Universe

            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $universe
                )
                ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([

            'name' =>
            trim(
                (string)
                $this->input('name')
            ),

            'universe_season_id' =>
            $this->input('universe_season_id')
                ?: null,
        ]);
    }

    public function rules(): array
    {
        $universe =
            $this->route('universe');

        $universeId =
            $universe instanceof Universe
            ? $universe->id
            : 0;

        return [

            'universe_tournament_id' => [
                'required',
                'integer',

                Rule::exists('universe_tournaments', 'id')
                    ->where(
                        'universe_id',
                        $universeId
                    )
                    ->whereNull('deleted_at'),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            /*
             * Opcional e informativa: no se aplica ninguna regla de
             * temporada en esta fase.
             */
            'universe_season_id' => [
                'nullable',
                'integer',

                Rule::exists('universe_seasons', 'id')
                    ->where(
                        'universe_id',
                        $universeId
                    )
                    ->whereNull('deleted_at'),
            ],

            /*
             * assignments[startId][] = universeCompetitorId
             */
            'assignments' => [
                'required',
                'array',
                'min:1',
            ],

            'assignments.*' => [
                'array',
            ],

            'assignments.*.*' => [
                'integer',

                Rule::exists('universe_competitors', 'id')
                    ->where(
                        'universe_id',
                        $universeId
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'universe_tournament_id.required' =>
            'Indica qué torneo del Universo se va a jugar.',

            'universe_tournament_id.exists' =>
            'Ese torneo no pertenece a este Universo.',

            'name.required' =>
            'Dale un nombre a esta competición.',

            'assignments.required' =>
            'Asigna competidores a al menos un punto de entrada.',

            'assignments.*.*.exists' =>
            'Alguno de los competidores seleccionados no pertenece a este Universo.',

            'universe_season_id.exists' =>
            'Esa temporada no pertenece a este Universo.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Asignaciones limpias
    |--------------------------------------------------------------------------
    |
    | Descarta entradas vacías y competidores repetidos: un mismo
    | competidor no puede entrar dos veces a la misma competición.
    |
    */

    public function assignments(): array
    {
        $clean = [];

        $seen = [];

        foreach (
            (array) $this->validated('assignments')
            as
            $startId => $competitorIds
        ) {

            $ids = [];

            foreach ((array) $competitorIds as $competitorId) {

                $competitorId = (int) $competitorId;

                if (
                    $competitorId <= 0
                    ||
                    isset($seen[$competitorId])
                ) {
                    continue;
                }

                $seen[$competitorId] = true;

                $ids[] = $competitorId;
            }

            if ($ids === []) {
                continue;
            }

            $clean[(int) $startId] = $ids;
        }

        return $clean;
    }
}

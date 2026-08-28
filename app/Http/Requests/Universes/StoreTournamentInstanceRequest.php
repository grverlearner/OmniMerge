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

            /*
             * Con que forma se juega ESTA edicion.
             *
             * Opcional: sin elegir nada se usa la del torneo, que es lo
             * habitual. Elegir otra es lo que permite que la cuarta
             * temporada tenga una fase previa que la primera no tenia.
             *
             * Que la plantilla sea de quien debe lo comprueba el servicio,
             * que es quien sabe de quien es el torneo.
             */
            'tournament_template_id' => [
                'nullable',
                'integer',

                Rule::exists('tournament_templates', 'id')
                    ->whereNull('deleted_at'),
            ],

            /*
             * El formato de batalla. Se decide AQUI y solo aqui: cuantos
             * juegos dura un enfrentamiento describe como se juega esta
             * edicion, no la forma del torneo.
             */
            'series_format' => [
                'required',
                Rule::in(['BEST_OF', 'FIXED_GAMES']),
            ],

            'best_of' => [
                'required_if:series_format,BEST_OF',
                'nullable',
                'integer',
                'min:1',
                'max:15',

                /*
                 * Impar: al mejor de 4 se empata a 2 y no hay forma de
                 * decidirlo.
                 */
                function (string $attribute, $value, $fail) {
                    if ($value !== null && (int) $value % 2 === 0) {
                        $fail('Al mejor de un número par no se puede decidir: usa un impar.');
                    }
                },
            ],

            'fixed_games' => [
                'required_if:series_format,FIXED_GAMES',
                'nullable',
                'integer',
                'min:1',
                'max:15',
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

                Rule::exists('universe_entities', 'id')
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
            $startId => $universeEntityIds
        ) {

            $ids = [];

            foreach ((array) $universeEntityIds as $universeEntityId) {

                $universeEntityId = (int) $universeEntityId;

                if (
                    $universeEntityId <= 0
                    ||
                    isset($seen[$universeEntityId])
                ) {
                    continue;
                }

                $seen[$universeEntityId] = true;

                $ids[] = $universeEntityId;
            }

            if ($ids === []) {
                continue;
            }

            $clean[(int) $startId] = $ids;
        }

        return $clean;
    }
}

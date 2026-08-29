<?php

namespace App\Http\Requests\Universes;

use App\Http\Requests\Universes\Concerns\ValidatesCompetitionConfiguration;
use App\Models\TournamentInstance;
use App\Models\Universe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| UpdateTournamentInstanceRequest
|--------------------------------------------------------------------------
|
| Retocar una edicion que todavia no ha empezado.
|
| Se puede cambiar casi todo -el nombre, el juego, como se pelea en cada
| fase, que premios se dan-, pero NO la plantilla ni quien compite: eso
| congelo el estado inicial al crearla, y cambiarlo aqui dejaria un cuadro
| dibujado para otra gente.
|
| Cuando haga falta cambiar la forma, lo honesto es crear otra edicion
| copiando esta, que es exactamente para lo que existe copiar.
|
*/
class UpdateTournamentInstanceRequest extends FormRequest
{
    use ValidatesCompetitionConfiguration;

    public function authorize(): bool
    {
        $universe = $this->route('universe');

        $competition = $this->route('competition');

        return $universe instanceof Universe
            && $competition instanceof TournamentInstance
            && (int) $competition->universe_id === (int) $universe->id
            && ($this->user()?->can('update', $universe) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([

            'name' => trim((string) $this->input('name')),

            'universe_season_id' => $this->input('universe_season_id') ?: null,

            'battle_participants' => $this->input('battle_participants') !== ''
                ? $this->input('battle_participants')
                : null,
        ]);
    }

    public function rules(): array
    {
        $universe = $this->route('universe');

        $universeId = $universe instanceof Universe ? $universe->id : 0;

        return [

            'name' => ['required', 'string', 'max:150'],

            'universe_season_id' => [
                'nullable',
                'integer',
                Rule::exists('universe_seasons', 'id')
                    ->where('universe_id', $universeId)
                    ->whereNull('deleted_at'),
            ],

            'series_format' => ['required', Rule::in(['BEST_OF', 'FIXED_GAMES'])],

            'best_of' => [
                'required_if:series_format,BEST_OF',
                'nullable',
                'integer',
                'min:1',
                'max:15',

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

        ]
        + $this->configurationRules($universeId)

        /*
         * Una edicion en borrador todavia puede cambiar quien compite. Si
         * ya empezo, el servicio lo rechaza: aqui solo se comprueba que lo
         * que llega sea valido, no si toca.
         */
        + $this->assignmentRules($universeId);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dale un nombre a esta edición.',
            'universe_season_id.exists' => 'Esa temporada no pertenece a este Universo.',
        ];
    }
}

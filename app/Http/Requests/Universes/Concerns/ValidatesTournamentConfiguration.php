<?php

namespace App\Http\Requests\Universes\Concerns;

use App\Models\UniverseTournamentReward;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| ValidatesTournamentConfiguration
|--------------------------------------------------------------------------
|
| Las reglas de la configuracion de un torneo oficial, compartidas por el
| alta y la edicion.
|
| Van en un trait y no copiadas en los dos requests porque dos listas de
| reglas para la misma entidad divergen, y la que se quede corta lo hara en
| silencio: un torneo creado con un campo sin validar y editado con el campo
| validado es un fallo que solo aparece meses despues.
|
*/
trait ValidatesTournamentConfiguration
{
    /**
     * @return array<string,mixed>
     */
    protected function configurationRules(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | El juego
            |------------------------------------------------------------------
            |
            | SINGLE: se juega siempre al mismo, y `game_key` es obligatorio.
            | VARIED: cada edicion elige, y `game_key` es solo la sugerencia.
            */

            'game_mode' => [
                'required',
                Rule::in(['SINGLE', 'VARIED']),
            ],

            'game_key' => [
                'required_if:game_mode,SINGLE',
                'nullable',
                'string',
                'max:60',
            ],


            /*
            |------------------------------------------------------------------
            | La batalla
            |------------------------------------------------------------------
            */

            /*
             * Cuantos caben en una batalla. Vacio = lo decide cada fase,
             * que es lo que hace falta cuando hay grupos de cuatro y una
             * final a dos.
             */
            'battle_participants' => [
                'nullable',
                'integer',
                'min:2',
                'max:16',
            ],

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
                 * Impar: al mejor de un par se empata a la mitad y no hay
                 * forma de decidirlo.
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

            /*
             * Como se decide quien gana una batalla. Ver la vista, que lo
             * explica con un ejemplo dibujado.
             */
            'decision_mode' => [
                'required',
                Rule::in(['SERIES_THEN_POINTS', 'POINTS_ONLY']),
            ],

            'allow_draws' => ['boolean'],

            /*
             * Si una edicion puede bajar estas decisiones a cada fase.
             *
             * Es distinto de game_mode: aquel dice si el juego puede
             * cambiar ENTRE ediciones, y esto si puede cambiar DENTRO de
             * una. Un torneo puede querer lo segundo sin lo primero -"la
             * Copa es siempre a numero mas alto, pero los grupos se juegan
             * a otra cosa"- y al reves.
             */
            'allow_phase_game' => ['boolean'],
            'allow_phase_battle' => ['boolean'],


            /*
            |------------------------------------------------------------------
            | Quien puede competir
            |------------------------------------------------------------------
            |
            | El contenido no se valida contra un catalogo cerrado: el
            | catalogo sale de las entidades del universo y cambia cuando
            | alguien importa una nueva. Una regla que hoy no casa con nadie
            | manana puede casar, y rechazarla seria adivinar el futuro.
            |
            | Lo que si se acota es la FORMA, para que el servicio no reciba
            | basura.
            */

            'eligibility_mode' => ['nullable', Rule::in(['ALL', 'ANY', 'NONE', 'ONE'])],

            'eligibility' => ['nullable', 'array', 'max:20'],
            'eligibility.*.attribute' => ['nullable', 'string', 'max:120'],
            'eligibility.*.values' => ['nullable', 'array', 'max:60'],
            'eligibility.*.values.*' => ['nullable', 'string', 'max:120'],

            /*
             * Los grupos: una condicion con su propio modo dentro.
             *
             * Un solo nivel a proposito. Con grupos ya se escribe
             * «(A y B) o (C)», que es hasta donde llega lo que alguien
             * quiere expresar de verdad; permitir grupos dentro de grupos
             * daria una pantalla que nadie sabria leer.
             */
            'eligibility_groups' => ['nullable', 'array', 'max:10'],
            'eligibility_groups.*.mode' => ['nullable', Rule::in(['ALL', 'ANY', 'NONE', 'ONE'])],
            'eligibility_groups.*.rules' => ['nullable', 'array', 'max:20'],
            'eligibility_groups.*.rules.*.attribute' => ['nullable', 'string', 'max:120'],
            'eligibility_groups.*.rules.*.values' => ['nullable', 'array', 'max:60'],
            'eligibility_groups.*.rules.*.values.*' => ['nullable', 'string', 'max:120'],

            /*
             * Y la mano: quien entra o queda fuera pase lo que pase.
             * Ninguna regla escrita con atributos captura «este si, porque
             * lo digo yo».
             */
            'eligibility_include' => ['nullable', 'array', 'max:500'],
            'eligibility_include.*' => ['integer'],

            'eligibility_exclude' => ['nullable', 'array', 'max:500'],
            'eligibility_exclude.*' => ['integer'],


            /*
            |------------------------------------------------------------------
            | Los premios
            |------------------------------------------------------------------
            |
            | Viajan DENTRO del formulario del torneo y no por su propia
            | ruta a proposito: al CREAR un torneo todavia no hay fila a la
            | que colgarlos, y un formulario que funciona al editar pero no
            | al crear obliga a guardar dos veces para dejar algo completo.
            |
            | Aqui se valida la FORMA. Que la estadistica exista en el juego
            | elegido lo sabe el motor, no un request.
            */

            'rewards' => ['nullable', 'array', 'max:30'],

            'rewards.*.trigger' => [
                'nullable',
                Rule::in(array_keys(UniverseTournamentReward::TRIGGERS)),
            ],

            'rewards.*.threshold' => ['nullable', 'integer', 'min:1', 'max:999'],

            'rewards.*.universe_trophy_id' => ['nullable', 'integer'],

            'rewards.*.stat_key' => ['nullable', 'string', 'max:60'],

            'rewards.*.operation' => [
                'nullable',
                Rule::in(array_keys(UniverseTournamentReward::OPERATIONS)),
            ],

            'rewards.*.amount' => ['nullable', 'numeric', 'between:-9999,9999'],

            'rewards.*.label' => ['nullable', 'string', 'max:150'],
        ];
    }

    /*
     * Los premios, limpios y en el orden en que se escribieron.
     *
     * Se descarta el que no da NADA -ni trofeo ni estadistica-: una fila
     * vacia es una que el usuario abrio y no llego a rellenar, no un premio
     * que no otorga nada.
     *
     * @return array<int,array<string,mixed>>
     */
    public function rewardsPayload(): array
    {
        return collect($this->input('rewards', []))
            ->map(fn ($row) => [
                'trigger' => (string) ($row['trigger'] ?? 'POSITION'),

                'threshold' => ($row['threshold'] ?? '') === ''
                    ? null
                    : (int) $row['threshold'],

                'universe_trophy_id' => ($row['universe_trophy_id'] ?? '') === ''
                    ? null
                    : (int) $row['universe_trophy_id'],

                'stat_key' => trim((string) ($row['stat_key'] ?? '')) ?: null,

                'operation' => (string) ($row['operation'] ?? 'ADD'),

                'amount' => ($row['amount'] ?? '') === '' ? 0 : (float) $row['amount'],

                'label' => trim((string) ($row['label'] ?? '')) ?: null,
            ])
            ->filter(fn (array $row) =>
                $row['universe_trophy_id'] !== null || $row['stat_key'] !== null)
            ->values()
            ->all();
    }

    /*
     * Las reglas de elegibilidad, en la forma que espera el servicio.
     *
     * El formulario las manda como listas paralelas porque es lo que un
     * formulario sabe hacer; el dominio las quiere como una estructura.
     * Traducir aqui evita que el controlador tenga que saber de las dos.
     */
    public function eligibilityPayload(): array
    {
        $rules = collect($this->input('eligibility', []))
            ->map(fn ($row) => [
                'attribute' => (string) ($row['attribute'] ?? ''),
                'values' => array_values(array_filter(
                    (array) ($row['values'] ?? []),
                    fn ($v) => trim((string) $v) !== ''
                )),
            ])
            ->filter(fn (array $row) => trim($row['attribute']) !== '')
            ->values()
            ->all();

        $grupos = collect($this->input('eligibility_groups', []))
            ->map(fn ($grupo) => [
                'mode' => strtoupper((string) ($grupo['mode'] ?? 'ALL')),
                'rules' => collect($grupo['rules'] ?? [])
                    ->map(fn ($row) => [
                        'attribute' => (string) ($row['attribute'] ?? ''),
                        'values' => array_values(array_filter(
                            (array) ($row['values'] ?? []),
                            fn ($v) => trim((string) $v) !== ''
                        )),
                    ])
                    ->filter(fn (array $row) => trim($row['attribute']) !== '')
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $g) => $g['rules'] !== [])
            ->values()
            ->all();

        $ids = fn (string $campo) => collect($this->input($campo, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return [
            'mode' => strtoupper((string) $this->input('eligibility_mode', 'ALL')),
            'rules' => $rules,
            'groups' => $grupos,
            'include' => $ids('eligibility_include'),
            'exclude' => $ids('eligibility_exclude'),
        ];
    }
}

<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentPhaseNode;
use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePhaseEntryPortRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route(
                'tournamentTemplate'
            );

        $node =
            $this->route(
                'node'
            );

        return
            $template
            instanceof
            TournamentTemplate
            &&
            $node
            instanceof
            TournamentPhaseNode
            &&
            $node
            ->tournament_template_id
            ===
            $template->id
            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $template
                )
                ??
                false
            );
    }


    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' =>
            trim(
                (string)
                $this->input(
                    'name'
                )
            ),

            'merge_policy' =>
            strtoupper(
                (string)
                $this->input(
                    'merge_policy',
                    'APPEND'
                )
            ),

            /*
             * Los numericos que llegan vacios se normalizan a null.
             *
             * Un formulario manda cadena vacia cuando no rellenas un
             * numero, y `nullable` no la trata como ausente: la regla
             * `gte:min_participants` de mas abajo la comparaba contra un
             * hueco y fallaba diciendo que el maximo tenia que ser mayor
             * que un minimo que nadie habia escrito.
             */
            ...array_reduce(
                ['min_participants', 'max_participants', 'exact_participants'],
                function (array $carry, string $field) {
                    $value = $this->input($field);

                    $carry[$field] = ($value === '' || $value === null) ? null : $value;

                    return $carry;
                },
                []
            ),

            'is_required' =>
            $this->boolean(
                'is_required'
            ),

            'accepts_multiple_connections' =>
            $this->boolean(
                'accepts_multiple_connections'
            ),

            'status' =>
            strtoupper(
                (string)
                $this->input(
                    'status',
                    'ACTIVE'
                )
            ),
        ]);
    }


    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'merge_policy' => [
                'required',

                Rule::in([
                    'APPEND',
                    'WAIT_ALL',
                    'FIRST_AVAILABLE',
                    'PRIORITY',
                ]),
            ],

            'is_required' => [
                'boolean',
            ],

            'accepts_multiple_connections' => [
                'boolean',
            ],

            'min_participants' => [
                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'max_participants' => [
                'nullable',
                'integer',
                'min:1',
                'max:512',

                /*
                 * Solo tiene sentido comparar cuando hay minimo. Poner solo
                 * el maximo -"caben hasta 8"- es una puerta perfectamente
                 * valida, y antes se rechazaba.
                 */
                ...($this->filled('min_participants') ? ['gte:min_participants'] : []),
            ],

            'exact_participants' => [
                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:10000',
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],
        ];
    }


    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (
                Validator $validator
            ) {
                if (
                    $this->filled(
                        'exact_participants'
                    )
                    &&
                    (
                        $this->filled(
                            'min_participants'
                        )
                        ||
                        $this->filled(
                            'max_participants'
                        )
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'exact_participants',
                            'Si utilizas participantes exactos, deja mínimo y máximo vacíos.'
                        );
                }
            }
        );
    }
}

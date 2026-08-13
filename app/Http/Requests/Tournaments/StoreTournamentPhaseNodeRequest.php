<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTournamentPhaseNodeRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route(
                'tournamentTemplate'
            );

        return
            $template
            instanceof
            TournamentTemplate
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
                    'name',
                    ''
                )
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
            'phase_template_id' => [
                'required',
                'integer',
                'exists:phase_templates,id',
            ],

            'name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'x_position' => [
                'nullable',
                'integer',
                'min:0',
                'max:10000',
            ],

            'y_position' => [
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
                $phaseTemplate =
                    PhaseTemplate::query()
                    ->find(
                        $this->integer(
                            'phase_template_id'
                        )
                    );


                if (
                    ! $phaseTemplate
                ) {
                    return;
                }


                /*
                 * Durante T6 colocamos en el builder
                 * PhaseTemplates propiedad del usuario.
                 */

                if (
                    $phaseTemplate->user_id
                    !==
                    $this->user()?->id
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'phase_template_id',
                            'Solo puedes colocar en el grafo Fases de tu propia biblioteca.'
                        );
                }
            }
        );
    }
}

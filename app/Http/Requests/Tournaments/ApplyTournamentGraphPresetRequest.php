<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyTournamentGraphPresetRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route(
                'tournamentTemplate'
            );

        return
            $template instanceof TournamentTemplate
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
        $regionNames =
            $this->input(
                'region_names'
            );

        if (is_string($regionNames)) {
            $regionNames =
                collect(
                    preg_split(
                        '/\r\n|\r|\n/',
                        $regionNames
                    )
                )
                ->map(
                    fn($name) =>
                    trim(
                        (string) $name
                    )
                )
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $phaseTemplateIds =
            collect(
                $this->input(
                    'phase_template_ids',
                    []
                )
            )
            ->filter(
                fn($id) =>
                $id !== null
                    &&
                    $id !== ''
            )
            ->map(
                fn($id) =>
                (int) $id
            )
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'preset' =>
            strtoupper(
                trim(
                    (string) $this->input(
                        'preset'
                    )
                )
            ),

            'phase_template_ids' =>
            $phaseTemplateIds,

            'primary_phase_template_id' =>
            $this->filled(
                'primary_phase_template_id'
            )
                ? $this->integer(
                    'primary_phase_template_id'
                )
                : null,

            'secondary_phase_template_id' =>
            $this->filled(
                'secondary_phase_template_id'
            )
                ? $this->integer(
                    'secondary_phase_template_id'
                )
                : null,

            'region_names' =>
            $regionNames,

            'start_name' =>
            trim(
                (string) $this->input(
                    'start_name',
                    'Participantes'
                )
            ),

            'terminal_name' =>
            trim(
                (string) $this->input(
                    'terminal_name',
                    'Resultado final'
                )
            ),

            'terminal_type' =>
            strtoupper(
                (string) $this->input(
                    'terminal_type',
                    'CHAMPION'
                )
            ),

            'expected_participants' =>
            $this->filled(
                'expected_participants'
            )
                ? $this->integer(
                    'expected_participants'
                )
                : null,

            'participants_per_region' =>
            $this->filled(
                'participants_per_region'
            )
                ? $this->integer(
                    'participants_per_region'
                )
                : null,
        ]);
    }

    public function rules(): array
    {
        $userId =
            $this->user()?->id;

        $ownedPhaseTemplate = Rule::exists(
            'phase_templates',
            'id'
        )->where(
            fn($query) =>
            $query
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereNull(
                    'deleted_at'
                )
        );

        return [
            'preset' => [
                'required',

                Rule::in([
                    'LINEAR',
                    'GROUPS_KNOCKOUT',
                    'SWISS_PLAYOFFS',
                    'MULTI_QUALIFIER',
                ]),
            ],

            'phase_template_ids' => [
                Rule::requiredIf(
                    $this->input('preset')
                        ===
                        'LINEAR'
                ),

                'array',
                'min:1',
                'max:12',
            ],

            'phase_template_ids.*' => [
                'integer',
                $ownedPhaseTemplate,
            ],

            'primary_phase_template_id' => [
                Rule::requiredIf(
                    in_array(
                        $this->input('preset'),
                        [
                            'GROUPS_KNOCKOUT',
                            'SWISS_PLAYOFFS',
                            'MULTI_QUALIFIER',
                        ],
                        true
                    )
                ),

                'nullable',
                'integer',
                $ownedPhaseTemplate,
            ],

            'secondary_phase_template_id' => [
                Rule::requiredIf(
                    in_array(
                        $this->input('preset'),
                        [
                            'GROUPS_KNOCKOUT',
                            'SWISS_PLAYOFFS',
                            'MULTI_QUALIFIER',
                        ],
                        true
                    )
                ),

                'nullable',
                'integer',
                'different:primary_phase_template_id',
                $ownedPhaseTemplate,
            ],

            'region_names' => [
                Rule::requiredIf(
                    $this->input('preset')
                        ===
                        'MULTI_QUALIFIER'
                ),

                'array',
                'min:2',
                'max:12',
            ],

            'region_names.*' => [
                'string',
                'max:100',
                'distinct',
            ],

            'start_name' => [
                'required',
                'string',
                'max:120',
            ],

            'terminal_name' => [
                'required',
                'string',
                'max:120',
            ],

            'terminal_type' => [
                'required',

                Rule::in([
                    'CHAMPION',
                    'QUALIFIED',
                    'ELIMINATED',
                    'SECONDARY',
                    'PLACEMENT',
                    'CUSTOM',
                ]),
            ],

            'expected_participants' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'participants_per_region' => [
                Rule::requiredIf(
                    $this->input('preset')
                        ===
                        'MULTI_QUALIFIER'
                ),

                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'preset.required' =>
            'Selecciona una estructura inicial.',

            'phase_template_ids.required' =>
            'Selecciona al menos una Fase para el flujo lineal.',

            'phase_template_ids.min' =>
            'Selecciona al menos una Fase.',

            'primary_phase_template_id.required' =>
            'Selecciona la primera Plantilla de Fase.',

            'secondary_phase_template_id.required' =>
            'Selecciona la segunda Plantilla de Fase.',

            'secondary_phase_template_id.different' =>
            'Las dos posiciones del preset deben utilizar Plantillas diferentes.',

            'region_names.required' =>
            'Escribe los nombres de los clasificatorios.',

            'region_names.min' =>
            'El preset regional necesita al menos dos clasificatorios.',

            'region_names.max' =>
            'Puedes generar como máximo doce clasificatorios en una sola operación.',

            'region_names.*.distinct' =>
            'No repitas el nombre de una región.',

            'participants_per_region.required' =>
            'Indica cuántos participantes comienza aportando cada región.',
        ];
    }

    public function attributes(): array
    {
        return [
            'preset' =>
            'estructura',

            'phase_template_ids' =>
            'Fases del recorrido',

            'primary_phase_template_id' =>
            'primera Fase',

            'secondary_phase_template_id' =>
            'segunda Fase',

            'region_names' =>
            'regiones',

            'participants_per_region' =>
            'participantes por región',
        ];
    }
}

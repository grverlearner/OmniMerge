<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdatePhaseTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate =
            $this->route('phaseTemplate');

        return $phaseTemplate
            instanceof PhaseTemplate
            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $phaseTemplate
                ) ?? false
            );
    }

    protected function prepareForValidation(): void
    {
        $phaseTemplate =
            $this->route('phaseTemplate');

        $capacityMode = strtoupper(
            (string) $this->input(
                'capacity_mode',
                $this->capacityModeFor(
                    $phaseTemplate
                )
            )
        );

        $contract = match ($capacityMode) {
            'EXACT' => [
                'min_participants' =>
                $this->input('exact_participants'),

                'max_participants' =>
                $this->input('exact_participants'),
            ],

            'OPEN' => [
                'exact_participants' => null,
                'max_participants' => null,
            ],

            default => [
                'exact_participants' => null,
            ],
        };

        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'phase_type' => strtoupper(
                (string) $this->input(
                    'phase_type',
                    $phaseTemplate
                        instanceof PhaseTemplate
                        ? $phaseTemplate->phase_type
                        : ''
                )
            ),

            'participant_mode' => strtoupper(
                (string) $this->input(
                    'participant_mode'
                )
            ),

            'capacity_mode' => $capacityMode,

            'status' => strtoupper(
                (string) $this->input('status')
            ),

            'visibility' => strtoupper(
                (string) $this->input(
                    'visibility'
                )
            ),

            'allow_byes' =>
            $this->boolean('allow_byes'),

            'allow_cloning' =>
            $this->boolean('allow_cloning'),

            'remove_image' =>
            $this->boolean('remove_image'),

            ...$contract,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'nullable',
                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('4mb'),
            ],

            'remove_image' => [
                'boolean',
            ],

            'phase_type' => [
                'required',
                Rule::in([
                    'SINGLE_ELIMINATION',
                    'ROUND_ROBIN',
                    'GROUP_STAGE',
                    'LEAGUE',
                    'SWISS',
                    'CUSTOM',
                ]),
                $this->unchangedPhaseTypeRule(),
            ],

            'participant_mode' => [
                'required',
                Rule::in([
                    'INDIVIDUAL',
                    'TEAM',
                    'FLEXIBLE',
                ]),
            ],

            'capacity_mode' => [
                'required',
                Rule::in([
                    'EXACT',
                    'RANGE',
                    'OPEN',
                ]),
            ],

            'min_participants' => [
                'required',
                'integer',
                'min:2',
                'max:512',
            ],

            'max_participants' => [
                Rule::requiredIf(
                    $this->input('capacity_mode')
                        === 'RANGE'
                ),
                'nullable',
                'integer',
                'min:2',
                'max:512',
                'gte:min_participants',
            ],

            'exact_participants' => [
                Rule::requiredIf(
                    $this->input('capacity_mode')
                        === 'EXACT'
                ),
                'nullable',
                'integer',
                'min:2',
                'max:512',
            ],

            'participant_multiple' => [
                'nullable',
                'integer',
                'min:2',
                'max:512',
            ],

            'allow_byes' => [
                'boolean',
            ],

            'best_of' => [
                'nullable',
                Rule::in([
                    1,
                    3,
                    5,
                    7,
                    9,
                ]),
            ],

            /*
             * Cómo se ve la fase.
             *
             * No es configuración del motor y por eso vive aquí y no en la
             * Super Edición: es lo que permite reconocerla de un vistazo en
             * una biblioteca de cuarenta fases —un icono, un color y una
             * frase—. La Super Edición decide cómo FUNCIONA; esto, cómo se
             * encuentra.
             */
            'icon' => [
                'nullable',
                'string',
                'max:8',
            ],

            'accent' => [
                'nullable',
                Rule::in([
                    'amber', 'violet', 'cyan', 'emerald', 'rose', 'sky', 'slate',
                ]),
            ],

            'summary' => [
                'nullable',
                'string',
                'max:120',
            ],

            'status' => [
                'required',
                Rule::in([
                    'DRAFT',
                    'ACTIVE',
                    'ARCHIVED',
                ]),
            ],

            'visibility' => [
                'required',
                Rule::in([
                    'PRIVATE',
                    'PUBLIC',
                    'UNLISTED',
                ]),
            ],

            'allow_cloning' => [
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
            'El nombre de la Fase es obligatorio.',

            'capacity_mode.in' =>
            'Selecciona un contrato exacto, por rango o abierto.',

            'min_participants.min' =>
            'Una Fase debe admitir al menos 2 participantes.',

            'max_participants.required' =>
            'Indica el máximo del rango.',

            'max_participants.gte' =>
            'El máximo no puede ser menor que el mínimo.',

            'exact_participants.required' =>
            'Indica la cantidad exacta de participantes.',

            'best_of.in' =>
            'Selecciona un Best of válido.',
        ];
    }

    private function capacityModeFor(
        mixed $phaseTemplate
    ): string {
        if (! $phaseTemplate instanceof PhaseTemplate) {
            return 'OPEN';
        }

        if ($phaseTemplate->exact_participants !== null) {
            return 'EXACT';
        }

        return $phaseTemplate->max_participants === null
            ? 'OPEN'
            : 'RANGE';
    }

    private function unchangedPhaseTypeRule(): Closure
    {
        return function (
            string $attribute,
            mixed $value,
            Closure $fail
        ): void {
            $phaseTemplate =
                $this->route('phaseTemplate');

            if (
                $phaseTemplate
                instanceof PhaseTemplate
                &&
                $value !== $phaseTemplate->phase_type
            ) {
                $fail(
                    'El tipo de Fase no puede cambiarse después de crearla porque ya define su Engine.'
                );
            }
        };
    }
}

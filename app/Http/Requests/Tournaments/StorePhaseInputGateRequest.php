<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhaseInputGateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate = $this->route('phaseTemplate');

        return $phaseTemplate instanceof PhaseTemplate
            && ($this->user()?->can('update', $phaseTemplate) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $capacityMode = strtoupper(
            (string) $this->input('capacity_mode', 'EXACT')
        );

        $exact = $this->nullableInteger(
            'exact_participants'
        );

        $minimum = $this->nullableInteger(
            'min_participants'
        );

        $maximum = $this->nullableInteger(
            'max_participants'
        );

        if ($capacityMode === 'EXACT') {
            $minimum = $exact;
            $maximum = $exact;
        }

        if ($capacityMode === 'RANGE') {
            $exact = null;
        }

        if ($capacityMode === 'FLEXIBLE') {
            $exact = null;
            $minimum = null;
            $maximum = null;
        }

        $targetSlotIds = collect(
            (array) $this->input(
                'target_slot_ids',
                []
            )
        )
            ->filter(
                fn($value) =>
                $value !== null
                    &&
                    $value !== ''
            )
            ->values()
            ->all();

        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'description' => $this->filled('description')
                ? trim(
                    (string) $this->input('description')
                )
                : null,

            'input_type' => strtoupper(
                (string) $this->input(
                    'input_type',
                    'POOL'
                )
            ),

            'merge_policy' => strtoupper(
                (string) $this->input(
                    'merge_policy',
                    'APPEND'
                )
            ),

            'distribution_mode' => strtoupper(
                (string) $this->input(
                    'distribution_mode',
                    'INPUT_ORDER'
                )
            ),

            'empty_behavior' => strtoupper(
                (string) $this->input(
                    'empty_behavior',
                    'ERROR'
                )
            ),

            'capacity_mode' => $capacityMode,

            'exact_participants' => $exact,

            'min_participants' => $minimum,

            'max_participants' => $maximum,

            'is_required' => $this->boolean(
                'is_required'
            ),

            'accepts_batch' => $this->boolean(
                'accepts_batch'
            ),

            'accepts_multiple_connections' => $this->boolean(
                'accepts_multiple_connections'
            ),

            'is_locked' => $this->boolean(
                'is_locked'
            ),

            'status' => strtoupper(
                (string) $this->input(
                    'status',
                    'ACTIVE'
                )
            ),

            'target_slot_ids' => $targetSlotIds,
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

            'input_type' => [
                'required',

                Rule::in([
                    'POOL',
                    'PER_SEED',
                    'GROUPED',
                    'HYBRID',
                    'CUSTOM',
                ]),
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

            'distribution_mode' => [
                'required',

                Rule::in([
                    'INPUT_ORDER',
                    'RANKING',
                    'RANDOM',
                    'BALANCED',
                    'EXTREMES',
                    'MANUAL',
                    'CUSTOM',
                ]),
            ],

            'empty_behavior' => [
                'required',

                Rule::in([
                    'ERROR',
                    'WAIT',
                    'SKIP',
                    'ALLOW_EMPTY',
                    'MANUAL',
                ]),
            ],

            'capacity_mode' => [
                'required',

                Rule::in([
                    'EXACT',
                    'RANGE',
                    'FLEXIBLE',
                ]),
            ],

            'exact_participants' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input('capacity_mode')
                        ===
                        'EXACT'
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'min_participants' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input('capacity_mode')
                        ===
                        'RANGE'
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
            ],

            'max_participants' => [
                Rule::requiredIf(
                    fn() =>
                    $this->input('capacity_mode')
                        ===
                        'RANGE'
                ),

                'nullable',
                'integer',
                'min:1',
                'max:512',
                'gte:min_participants',
            ],

            'is_required' => [
                'required',
                'boolean',
            ],

            'accepts_batch' => [
                'required',
                'boolean',
            ],

            'accepts_multiple_connections' => [
                'required',
                'boolean',
            ],

            'priority' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],

            'is_locked' => [
                'required',
                'boolean',
            ],

            'target_slot_ids' => [
                'present',
                'array',
                'max:512',
            ],

            'target_slot_ids.*' => [
                'integer',
                'distinct',
                'exists:phase_single_elimination_slots,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'exact_participants.required' =>
            'Indica la capacidad exacta de la puerta.',

            'min_participants.required' =>
            'Indica la capacidad mínima de la puerta.',

            'max_participants.required' =>
            'Indica la capacidad máxima de la puerta.',

            'max_participants.gte' =>
            'La capacidad máxima no puede ser menor que la mínima.',

            'target_slot_ids.*.exists' =>
            'Uno de los slots seleccionados ya no existe.',
        ];
    }

    private function nullableInteger(
        string $key
    ): ?int {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}

<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSingleEliminationStructureElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate =
            $this->route(
                'phaseTemplate'
            );

        return
            $phaseTemplate
            instanceof PhaseTemplate

            &&
            $phaseTemplate->phase_type
            ===
            'SINGLE_ELIMINATION'

            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $phaseTemplate
                )
                ??
                false
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_locked' => $this->boolean('is_locked'),
            'allows_incomplete' => $this->boolean('allows_incomplete'),
            'is_required' => $this->boolean('is_required'),
            'accepts_batch' => $this->boolean('accepts_batch'),
            'accepts_multiple_connections' => $this->boolean('accepts_multiple_connections'),
            'is_splittable' => $this->boolean('is_splittable'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:120',
            ],

            'label' => [
                'nullable',
                'string',
                'max:160',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'status' => [
                'required',

                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                ]),
            ],

            'is_locked' => ['boolean'],

            // ROUND
            'stage_number' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'branch_code' => ['nullable', Rule::in(['MAIN', 'SECONDARY', 'REPECHAGE', 'CUSTOM'])],
            'round_type' => ['nullable', Rule::in(['PRELIMINARY', 'MAIN', 'REPECHAGE', 'PLACEMENT', 'CUSTOM'])],
            'participants_expected' => ['nullable', 'integer', 'min:1', 'max:512'],
            'qualifiers_expected' => ['nullable', 'integer', 'min:1', 'max:512'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],

            // ENCOUNTER
            'position' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'entrants_count' => ['nullable', 'integer', 'min:2', 'max:64'],
            'qualifiers_count' => ['nullable', 'integer', 'min:1', 'max:63'],
            'min_entrants_to_start' => ['nullable', 'integer', 'min:1', 'max:64'],
            'encounter_profile' => ['nullable', Rule::in(['DUEL', 'MULTI_COMPETITOR', 'CUSTOM'])],
            'activation_policy' => ['nullable', Rule::in(['ALL_SLOTS_FILLED', 'MINIMUM_REACHED', 'MANUAL'])],
            'allows_incomplete' => ['boolean'],
            'series_format' => ['nullable', Rule::in(['NONE', 'BEST_OF', 'FIXED_GAMES'])],
            'best_of' => ['nullable', Rule::in([1, 3, 5, 7, 9, 11])],
            'fixed_games' => ['nullable', 'integer', 'min:1', 'max:99'],

            // INPUT_GATE
            'input_type' => ['nullable', Rule::in(['POOL', 'PER_SEED', 'GROUPED', 'HYBRID', 'CUSTOM'])],
            'merge_policy' => ['nullable', Rule::in(['APPEND', 'WAIT_ALL', 'FIRST_AVAILABLE', 'PRIORITY'])],
            'distribution_mode' => ['nullable', Rule::in(['INPUT_ORDER', 'RANKING', 'RANDOM', 'BALANCED', 'EXTREMES', 'MANUAL', 'CUSTOM'])],
            'empty_behavior' => ['nullable', 'string', 'max:80'],
            'min_participants' => ['nullable', 'integer', 'min:0', 'max:512'],
            'max_participants' => ['nullable', 'integer', 'min:0', 'max:512'],
            'exact_participants' => ['nullable', 'integer', 'min:0', 'max:512'],
            'is_required' => ['boolean'],
            'accepts_batch' => ['boolean'],
            'accepts_multiple_connections' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:99999'],

            // SLOT
            'slot_type' => ['nullable', Rule::in(['PARTICIPANT', 'BYE', 'OPTIONAL', 'MANUAL'])],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:64'],
            'source_policy' => ['nullable', Rule::in(['SINGLE', 'FIRST_AVAILABLE', 'PRIORITY', 'CONDITIONAL', 'MANUAL'])],
            'assignment_rule' => ['nullable', 'string', 'max:120'],

            // RESULT
            'result_type' => ['nullable', Rule::in(['WINNER', 'LOSER', 'POSITION', 'TOP_N', 'QUALIFIED', 'ELIMINATED', 'SURVIVOR', 'SCORE_THRESHOLD', 'MANUAL', 'CUSTOM'])],
            'position_from' => ['nullable', 'integer', 'min:1', 'max:64'],
            'position_to' => ['nullable', 'integer', 'min:1', 'max:64'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:64'],
            'flow_mode' => ['nullable', 'string', 'max:80'],
            'participant_status' => ['nullable', 'string', 'max:80'],
            'is_splittable' => ['boolean'],

            // CONNECTION. Endpoints can be changed atomically by the same inspector.
            'source_type' => ['nullable', Rule::in(['INPUT_GATE', 'RESULT'])],
            'source_input_gate_id' => ['nullable', 'integer', 'min:1'],
            'source_result_id' => ['nullable', 'integer', 'min:1'],
            'target_type' => ['nullable', Rule::in(['SLOT', 'PHASE_EXIT'])],
            'target_slot_id' => ['nullable', 'integer', 'min:1'],
            'target_phase_exit_id' => ['nullable', 'integer', 'min:1'],
            'allocation_mode' => ['nullable', Rule::in(['ALL', 'TAKE_N', 'POSITION', 'REMAINDER', 'CONDITIONAL'])],
            'allocation_value' => ['nullable', 'numeric', 'min:0', 'max:512'],
            'condition_type' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' =>
            'nombre',

            'label' =>
            'etiqueta',

            'description' =>
            'descripción',

            'status' =>
            'estado',

            'is_locked' =>
            'protección',
        ];
    }
}

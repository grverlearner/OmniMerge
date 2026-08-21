<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InitializeRoundRobinSimulatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phaseTemplate = $this->route('phaseTemplate');

        return
            $phaseTemplate instanceof PhaseTemplate
            && $phaseTemplate->phase_type === 'ROUND_ROBIN'
            && ($this->user()?->can('update', $phaseTemplate) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $participants = collect($this->input('participants', []))
            ->map(fn($participant) => [
                'name' => trim((string) ($participant['name'] ?? '')),
                'seed' => isset($participant['seed']) && $participant['seed'] !== ''
                    ? (int) $participant['seed']
                    : null,
            ])
            ->values()
            ->all();

        $this->merge([
            'participants' => $participants,
        ]);
    }

    public function rules(): array
    {
        return [
            'participants' => [
                'required',
                'array',
                'min:2',
                'max:256',
            ],

            'participants.*.name' => [
                'nullable',
                'string',
                'max:60',
            ],

            'participants.*.seed' => [
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $phaseTemplate = $this->route('phaseTemplate');

                if (! $phaseTemplate instanceof PhaseTemplate) {
                    return;
                }

                $count = count($this->input('participants', []));

                if ($count < $phaseTemplate->min_participants) {
                    $validator->errors()->add(
                        'participants',
                        'Esta fase necesita al menos ' . $phaseTemplate->min_participants . ' participantes.'
                    );
                }

                if (
                    $phaseTemplate->max_participants !== null
                    && $count > $phaseTemplate->max_participants
                ) {
                    $validator->errors()->add(
                        'participants',
                        'Esta fase admite como máximo ' . $phaseTemplate->max_participants . ' participantes.'
                    );
                }

                if (
                    $phaseTemplate->exact_participants !== null
                    && $count !== $phaseTemplate->exact_participants
                ) {
                    $validator->errors()->add(
                        'participants',
                        'Esta fase necesita exactamente ' . $phaseTemplate->exact_participants . ' participantes.'
                    );
                }
            },
        ];
    }
}

<?php

namespace App\Http\Requests\Tournaments;

use App\Models\PhaseTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSingleEliminationGraphEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $phase = $this->route('phaseTemplate');

        return $phase instanceof PhaseTemplate
            && $phase->phase_type === 'SINGLE_ELIMINATION'
            && ($this->user()?->can('update', $phase) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'encounter_profile' => strtoupper((string) $this->input('encounter_profile')),
            'resolution_mode' => strtoupper((string) $this->input('resolution_mode')),
            'qualifier_ordering' => strtoupper((string) $this->input('qualifier_ordering')),
            'series_format' => strtoupper((string) $this->input('series_format')),
        ]);
    }

    public function rules(): array
    {
        return [
            'round_id' => ['required', 'integer', 'exists:phase_single_elimination_rounds,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'entrants_count' => ['required', 'integer', 'min:2', 'max:64'],
            'qualifiers_count' => ['required', 'integer', 'min:1', 'max:63'],
            'encounter_profile' => ['required', Rule::in(['DUEL', 'MULTI_COMPETITOR'])],
            'resolution_mode' => ['required', Rule::in(['SCORE', 'RANKING', 'MANUAL_SELECTION'])],
            'qualifier_ordering' => ['required', Rule::in(['ORDERED', 'UNORDERED'])],
            'series_format' => ['required', Rule::in(['NONE', 'BEST_OF', 'FIXED_GAMES'])],
            'best_of' => ['nullable', 'integer', Rule::in([1, 3, 5, 7, 9, 11])],
            'fixed_games' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $entrants = (int) $this->input('entrants_count');
            $qualifiers = (int) $this->input('qualifiers_count');

            if ($qualifiers >= $entrants) {
                $validator->errors()->add(
                    'qualifiers_count',
                    'Los clasificados deben ser menores que los participantes.'
                );
            }

            if ($this->input('encounter_profile') === 'DUEL' && ($entrants !== 2 || $qualifiers !== 1)) {
                $validator->errors()->add(
                    'encounter_profile',
                    'El perfil Duelo exige exactamente una relación 2 → 1.'
                );
            }

            if ($this->input('resolution_mode') === 'SCORE' && ($entrants !== 2 || $qualifiers !== 1)) {
                $validator->errors()->add(
                    'resolution_mode',
                    'El marcador A/B solo puede resolver un encuentro 2 → 1. Para relaciones K → Q usa Ranking o Selección manual.'
                );
            }

            if ($this->input('series_format') === 'BEST_OF' && ! $this->filled('best_of')) {
                $validator->errors()->add('best_of', 'Selecciona la cantidad de juegos de la serie.');
            }

            if ($this->input('series_format') === 'FIXED_GAMES' && ! $this->filled('fixed_games')) {
                $validator->errors()->add('fixed_games', 'Indica la cantidad fija de juegos.');
            }
        });
    }
}

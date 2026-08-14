<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RunTournamentFlowPreviewRequest
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
        $starts =
            collect(
                $this->input(
                    'starts',
                    []
                )
            )
            ->map(
                fn($start) => [
                    'start_id' =>
                    isset($start['start_id'])
                        ? (int) $start['start_id']
                        : null,

                    'count' =>
                    isset($start['count'])
                        &&
                        $start['count'] !== ''
                        ? (int) $start['count']
                        : null,

                    'prefix' =>
                    trim(
                        (string) (
                            $start['prefix']
                            ??
                            ''
                        )
                    ),
                ]
            )
            ->values()
            ->all();

        $seed =
            $this->filled('seed')
            ? $this->integer('seed')
            : random_int(
                1,
                999999999
            );

        $this->merge([
            'participant_mode' =>
            strtoupper(
                (string) $this->input(
                    'participant_mode',
                    'GENERATED'
                )
            ),

            'resolution_strategy' =>
            strtoupper(
                (string) $this->input(
                    'resolution_strategy',
                    'ORDERED'
                )
            ),

            'seed' =>
            $seed,

            'starts' =>
            $starts,
        ]);
    }

    public function rules(): array
    {
        return [
            'participant_mode' => [
                'required',
                Rule::in([
                    'GENERATED',
                ]),
            ],

            'resolution_strategy' => [
                'required',
                Rule::in([
                    'ORDERED',
                    'SEEDED_RANDOM',
                ]),
            ],

            'seed' => [
                'required',
                'integer',
                'min:1',
                'max:2147483647',
            ],

            'starts' => [
                'required',
                'array',
                'min:1',
                'max:32',
            ],

            'starts.*.start_id' => [
                'required',
                'integer',
                'distinct',
                'exists:tournament_starts,id',
            ],

            'starts.*.count' => [
                'required',
                'integer',
                'min:1',
                'max:512',
            ],

            'starts.*.prefix' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $template =
                    $this->route(
                        'tournamentTemplate'
                    );

                if (! $template instanceof TournamentTemplate) {
                    return;
                }

                $validStartIds =
                    $template
                    ->graphStarts()
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->pluck('id')
                    ->map(
                        fn($id) =>
                        (int) $id
                    );

                foreach (
                    $this->input(
                        'starts',
                        []
                    )
                    as
                    $index => $start
                ) {
                    if (
                        ! $validStartIds->contains(
                            (int) (
                                $start['start_id']
                                ??
                                0
                            )
                        )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "starts.$index.start_id",
                                'El inicio seleccionado no pertenece a este torneo.'
                            );
                    }
                }

                $total =
                    collect(
                        $this->input(
                            'starts',
                            []
                        )
                    )
                    ->sum(
                        fn($start) =>
                        (int) (
                            $start['count']
                            ??
                            0
                        )
                    );

                if ($total > 512) {
                    $validator
                        ->errors()
                        ->add(
                            'starts',
                            'El Preview admite como máximo 512 participantes en total.'
                        );
                }

                if (
                    $validStartIds->count()
                    !==
                    count(
                        $this->input(
                            'starts',
                            []
                        )
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'starts',
                            'Debes configurar todos los inicios activos del torneo.'
                        );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'starts.required' =>
            'Configura los participantes de cada inicio.',

            'starts.*.count.required' =>
            'Indica cuántos participantes cargará este inicio.',

            'starts.*.count.min' =>
            'Cada inicio necesita al menos un participante.',

            'starts.*.start_id.distinct' =>
            'No repitas el mismo inicio.',

            'seed.required' =>
            'Indica una semilla para que el Preview sea reproducible.',
        ];
    }
}

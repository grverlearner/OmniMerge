<?php

namespace App\Http\Requests\Tournaments;

use App\Models\TournamentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InitializeCompetitionLabRequest
extends FormRequest
{
    public function authorize(): bool
    {
        $template =
            $this->route('tournamentTemplate');

        return
            $template instanceof TournamentTemplate
            &&
            (
                $this->user()
                ?->can('update', $template)
                ??
                false
            );
    }

    protected function prepareForValidation(): void
    {
        $starts =
            collect(
                $this->input('starts', [])
            )
            ->map(fn($start) => [
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
            ])
            ->values()
            ->all();

        $this->merge([
            'participant_mode' =>
            strtoupper(
                (string) $this->input(
                    'participant_mode',
                    'GENERATED'
                )
            ),

            'ordering_strategy' =>
            strtoupper(
                (string) $this->input(
                    'ordering_strategy',
                    'ORDERED'
                )
            ),

            'seed' =>
            $this->filled('seed')
                ? $this->integer('seed')
                : random_int(1, 999999999),

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

            'ordering_strategy' => [
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

                if (
                    ! $template
                        instanceof
                        TournamentTemplate
                ) {
                    return;
                }

                $activeStartIds =
                    $template
                    ->graphStarts()
                    ->where('status', 'ACTIVE')
                    ->pluck('id')
                    ->map(fn($id) => (int) $id);

                $receivedStartIds =
                    collect(
                        $this->input('starts', [])
                    )
                    ->pluck('start_id')
                    ->map(fn($id) => (int) $id);

                foreach (
                    $receivedStartIds
                    as
                    $index => $startId
                ) {
                    if (
                        ! $activeStartIds->contains(
                            $startId
                        )
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "starts.$index.start_id",
                                'El inicio no pertenece a esta plantilla.'
                            );
                    }
                }

                if (
                    $activeStartIds->sort()->values()->all()
                    !==
                    $receivedStartIds->sort()->values()->all()
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'starts',
                            'Configura exactamente todos los inicios activos del torneo.'
                        );
                }

                $total =
                    collect(
                        $this->input('starts', [])
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
                            'El Competition Lab admite como máximo 512 participantes.'
                        );
                }

                if (
                    $total
                    <
                    $template->min_participants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'starts',
                            'El Lab necesita al menos '
                                .
                                $template->min_participants
                                .
                                ' participantes.'
                        );
                }

                if (
                    $template->max_participants !== null
                    &&
                    $total
                    >
                    $template->max_participants
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'starts',
                            'La plantilla admite como máximo '
                                .
                                $template->max_participants
                                .
                                ' participantes.'
                        );
                }
            },
        ];
    }
}

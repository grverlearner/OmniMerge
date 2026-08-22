<?php

namespace App\Http\Requests\Universes;

use App\Models\TournamentInstance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| TournamentInstanceActionRequest
|--------------------------------------------------------------------------
|
| Cada acción del motor sobre una competición persistente.
|
| Las acciones son exactamente las que ya entiende el motor del
| Competition Lab. Se excluyen a propósito RESET (borraría una
| competición real jugada) y START/PAUSE/RESUME del Lab, que aquí son
| transiciones del ciclo de vida con sus propias rutas.
|
*/

class TournamentInstanceActionRequest extends FormRequest
{
    public const ACTIONS = [

        /* Recorrido del Tournament Graph */
        'START_TOURNAMENT',
        'STEP_RUNTIME',
        'RUN_TOURNAMENT',

        /* Fases */
        'PREPARE_NODE',
        'RESOLVE_MANUAL_DECISION',

        /* Resultados */
        'SUBMIT_MATCH_RESULT',
        'SUBMIT_ENCOUNTER_RESULT',
        'SIMULATE_MATCH',
        'SIMULATE_ROUND',

        /* Simulación interactiva del juego (Fase 11) */
        'ADVANCE_TO_PLAYABLE',
        'PREPARE_ENCOUNTER',
        'ROLL_ENCOUNTER',
        'ADVANCE_ENCOUNTER',
    ];

    public function authorize(): bool
    {
        $instance =
            $this->route('competition');

        return
            $instance
            instanceof
            TournamentInstance

            &&
            (
                $this->user()
                ?->can(
                    'update',
                    $instance
                )
                ?? false
            );
    }

    public function rules(): array
    {
        return [

            'action' => [
                'required',
                'string',

                Rule::in(
                    self::ACTIONS
                ),
            ],

            /*
             * Revisión que tenía el cliente. Si no coincide con la
             * guardada, otra ventana ya avanzó la competición y la
             * acción se rechaza en vez de pisar resultados.
             */
            'revision' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'action.in' =>
            'Esa acción no está disponible en una competición real.',
        ];
    }

    /*
     * Todo lo que no sea action/revision/_token es carga útil del motor
     * (node_id, match_id, score_a, decisiones manuales...).
     */
    public function payload(): array
    {
        return $this->except([
            'action',
            'revision',
            '_token',
            '_method',
        ]);
    }
}

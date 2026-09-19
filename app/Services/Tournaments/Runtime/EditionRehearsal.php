<?php

namespace App\Services\Tournaments\Runtime;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceState;
use App\Services\Tournaments\CompetitionLab\CompetitionLabService;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Ensayo de una edición antes de dejarla congelada
|--------------------------------------------------------------------------
|
| Una edición congela a sus competidores al crearse. Si con esos
| competidores alguna fase no puede jugarse —la puerta A pide diez y le
| llegan nueve, la fase de grupos pide dieciséis y la liguilla solo le
| manda ocho—, eso no se descubría hasta pulsar «jugar», con la edición ya
| cerrada y sin más salida que cancelarla.
|
| Aquí se juega entera en memoria con el mismo motor que la va a jugar de
| verdad, sin guardar nada. Los resultados son al azar y dan igual: lo que
| se comprueba es la forma —cuántos llegan a cada fase y si el recorrido
| puede terminar—, y eso no depende de quién gane.
|
| Una decisión manual pendiente detiene el ensayo sin dar error: el motor
| no puede adivinarla, y pedirla no es un fallo de la edición.
|
*/

class EditionRehearsal
{
    /* Un torneo de diecinueve fases cabe de sobra; es solo un cortafuegos */
    private const MAX_STEPS = 5000;

    public function __construct(
        private readonly TournamentSnapshotHydrator $hydrator,
        private readonly CompetitionPhasePlan $phasePlan,
        private readonly CompetitionLabService $engine,
    ) {}

    /*
     * Null si la edición se puede jugar. Si no, por qué, dicho para una
     * persona.
     */
    public function problem(TournamentInstance $instance): ?string
    {
        $state = TournamentInstanceState::query()
            ->where('tournament_instance_id', $instance->id)
            ->value('state');

        if (! is_array($state)) {
            return null;
        }

        $template = $this->hydrator->hydrate(
            $instance->snapshot?->snapshot ?? [],
            $instance
        );

        try {
            $state = $this->engine->applyAction(
                $this->phasePlan->applyToState($state, $instance),
                $template,
                'START_TOURNAMENT',
                []
            );
        } catch (ValidationException $e) {
            return $this->explain($state, $e);
        }

        for ($paso = 0; $paso < self::MAX_STEPS; $paso++) {

            $estado = $state['graph_runtime']['status'] ?? null;

            if (in_array($estado, ['COMPLETED', 'AWAITING_DECISION'], true)) {
                return null;
            }

            if ($estado === 'BLOCKED') {
                return $this->blocked($state);
            }

            $antes = $state;

            try {
                $state = $this->engine->applyAction(
                    $this->phasePlan->applyToState($state, $instance),
                    $template,
                    'STEP_RUNTIME',
                    []
                );
            } catch (ValidationException $e) {
                return $this->explain($antes, $e);
            }
        }

        return null;
    }

    /*
     * La fase que iba a evaluarse cuando el motor protestó, y cuántos le
     * habrían llegado: sin eso, «requiere exactamente 16» no dice dónde.
     */
    private function explain(array $state, ValidationException $e): string
    {
        $motivo = collect($e->errors())->flatten()->first() ?: $e->getMessage();

        $operacion = $state['graph_runtime']['operation_queue'][0] ?? null;

        $nodo = isset($operacion['node_id'])
            ? ($state['nodes'][$operacion['node_id']] ?? null)
            : null;

        if (! $nodo) {
            return 'Con estos competidores la edición no se puede jugar: ' . $motivo;
        }

        $llegan = collect($nodo['entry_ports'] ?? [])
            ->sum(fn ($puerto) => count($puerto['participant_ids'] ?? []));

        return 'Con estos competidores la edición no se puede jugar: a la fase «'
            . ($nodo['name'] ?? 'sin nombre') . '» le llegarían '
            . $llegan . ' ' . ($llegan === 1 ? 'competidor' : 'competidores') . '. '
            . $motivo
            . ' Ajusta quién entra o elige otra forma para esta edición.';
    }

    private function blocked(array $state): string
    {
        $errores = collect($state['graph_runtime']['diagnostics'] ?? [])
            ->where('level', 'ERROR')
            ->pluck('message')
            ->unique()
            ->take(2)
            ->implode(' ');

        return 'Con estos competidores la edición se quedaría bloqueada a mitad y no llegaría a terminar. '
            . ($errores !== '' ? $errores : 'El recorrido no puede avanzar.');
    }
}

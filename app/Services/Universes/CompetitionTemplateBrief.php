<?php

namespace App\Services\Universes;

use App\Models\TournamentTemplate;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorRegistry;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| CompetitionTemplateBrief
|--------------------------------------------------------------------------
|
| Como esta hecha una plantilla, en pequeno.
|
| Elegir con que forma se juega una edicion es la decision mas grande de la
| pantalla, y hasta ahora se tomaba leyendo un desplegable con el nombre.
| Un nombre no dice si hay grupos, ni cuantas rondas, ni por donde entra la
| gente: dos plantillas llamadas "Copa 2024" y "Copa 2025" pueden no
| parecerse en nada.
|
| Esto arma la ficha que se ve ANTES de elegir: por donde se entra, que
| fases hay y con que forma, y donde se acaba.
|
| Es deliberadamente barato. No analiza el flujo ni reparte competidores
| -eso es lo que hace la Super Edicion, y cuesta-. Aqui se pintan diez
| plantillas de golpe, asi que cada una tiene que costar una consulta y no
| un analisis.
|
*/
class CompetitionTemplateBrief
{
    public function __construct(
        private readonly PhaseSuperEditorRegistry $phases,
    ) {
    }

    /*
     * @param  Collection<int,TournamentTemplate>  $templates
     * @return array<int,array<string,mixed>>
     */
    public function briefs(Collection $templates, ?int $defaultId = null): array
    {
        $templates->load([
            'graphNodes.phaseTemplate',
            'graphNodes.entryPorts',
            'graphStarts',
            'graphTerminals',
            'graphConnections',
        ]);

        return $templates
            ->map(fn (TournamentTemplate $t) => $this->brief($t, $defaultId))
            ->values()
            ->all();
    }

    public function brief(TournamentTemplate $template, ?int $defaultId = null): array
    {
        $nodes = $template->graphNodes
            ->where('status', 'ACTIVE')
            ->sortBy('sequence_number')
            ->values();

        $starts = $template->graphStarts
            ->where('status', 'ACTIVE')
            ->sortBy('sequence_number')
            ->values();

        $terminals = $template->graphTerminals
            ->where('status', 'ACTIVE')
            ->sortBy('sequence_number')
            ->values();

        $levels = $this->levels($template, $nodes);

        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'is_default' => $defaultId !== null && (int) $template->id === $defaultId,

            'min_participants' => $template->min_participants,
            'max_participants' => $template->max_participants,

            'counts' => [
                'phases' => $nodes->count(),
                'starts' => $starts->count(),
                'terminals' => $terminals->count(),
                'connections' => $template->graphConnections->count(),
            ],

            'starts' => $starts
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'capacity' => $s->expected_participants
                        ? (int) $s->expected_participants
                        : null,
                    'source_type' => $s->source_type,
                ])
                ->all(),

            'terminals' => $terminals
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'code' => $t->code,
                    'name' => $t->name,
                    'type' => $t->terminal_type,
                    'capacity' => $t->expected_participants
                        ? (int) $t->expected_participants
                        : null,
                ])
                ->all(),

            'phases' => $nodes
                ->map(fn ($node) => $this->phase($node, $levels[$node->id] ?? 0))
                ->all(),

            /* Cuantas columnas tiene el dibujo */
            'depth' => $levels === [] ? 1 : max(1, max($levels) + 1),
        ];
    }

    /*
     * Una fase, con la forma que tiene.
     */
    private function phase($node, int $level): array
    {
        $phase = $node->phaseTemplate;

        /*
         * Los motores que todavia no saben dibujarse -Swiss, League- se
         * quedan sin esquema en vez de con uno prestado: una caja que dice
         * "8 participantes" es verdad, y una forma inventada no.
         */
        $outline = ($phase && $this->phases->supports($phase))
            ? $this->phases->for($phase)->outline($phase)
            : null;

        return [
            'id' => $node->id,
            'code' => $node->code,
            'name' => $node->name,
            'level' => $level,

            'phase_template_id' => $phase?->id,
            'phase_type' => $phase?->phase_type,
            'phase_type_label' => $phase?->type_label ?? 'Sin fase',

            'min' => $phase?->min_participants,
            'max' => $phase?->max_participants,

            'entries' => $node->entryPorts
                ->where('status', 'ACTIVE')
                ->map(fn ($port) => [
                    'id' => $port->id,
                    'name' => $port->name,
                    'min' => $port->min_participants,
                    'max' => $port->max_participants,
                ])
                ->values()
                ->all(),

            'outline' => $outline,

            /* La frase que resume su forma */
            'shape' => $outline
                ? $outline['label']
                : ($phase?->type_label ?? 'Fase sin configurar'),
        ];
    }

    /*
     * En que columna va cada fase.
     *
     * Un recorrido no es una lista: dos fases pueden jugarse a la vez y
     * caer en la misma columna. Se calcula subiendo por las conexiones:
     * una fase esta un paso mas alla de la mas lejana que la alimenta.
     *
     * @return array<int,int>  node_id => nivel
     */
    private function levels(TournamentTemplate $template, Collection $nodes): array
    {
        /* node_id => [node_id, ...] de quien lo alimenta */
        $feeders = [];

        $portToNode = [];

        foreach ($nodes as $node) {
            $feeders[$node->id] = [];

            foreach ($node->entryPorts as $port) {
                $portToNode[$port->id] = $node->id;
            }
        }

        foreach ($template->graphConnections as $connection) {

            if ($connection->target_type !== 'ENTRY_PORT') {
                continue;
            }

            $target = $portToNode[$connection->target_entry_port_id] ?? null;

            if ($target === null || $connection->source_type !== 'PHASE_EXIT') {
                continue;
            }

            $source = $connection->source_node_id;

            if ($source && isset($feeders[$target])) {
                $feeders[$target][] = (int) $source;
            }
        }

        $levels = [];

        /*
         * Iterativo y con tope, no recursivo: un grafo mal construido
         * puede tener un ciclo, y aqui se esta dibujando una ficha, no
         * validando. Mejor una columna rara que una pila desbordada.
         */
        foreach (array_keys($feeders) as $id) {
            $levels[$id] = 0;
        }

        for ($pass = 0; $pass < count($feeders) + 1; $pass++) {

            $changed = false;

            foreach ($feeders as $id => $sources) {

                foreach ($sources as $source) {

                    $candidate = ($levels[$source] ?? 0) + 1;

                    if ($candidate > ($levels[$id] ?? 0)) {
                        $levels[$id] = $candidate;
                        $changed = true;
                    }
                }
            }

            if (! $changed) {
                break;
            }
        }

        return $levels;
    }
}

<?php

namespace App\Services\Universes;

use App\Models\TournamentInstance;
use App\Models\TournamentInstanceParticipant;
use App\Models\TournamentStart;
use App\Models\TournamentTemplate;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Models\UniverseTournament;

/*
|--------------------------------------------------------------------------
| ParticipantRoomBuilder
|--------------------------------------------------------------------------
|
| Todo lo que la sala de participantes necesita, en una sola entrega.
|
| La sala calcula en el navegador -marcar un valor tiene que mover las caras
| en el mismo clic-, así que recibe el universo entero: cada competidor con
| sus atributos ya traducidos a nombres y sus versiones con lo que las
| activa; el catálogo en árbol; las puertas de la plantilla con sus plazas;
| lo guardado; y lo que calcula el SERVIDOR con eso, para poder avisar si
| la pantalla y el servidor dijesen cosas distintas.
|
| Dos salas con el mismo componente:
|
|   build()       la del torneo, que se guarda en el torneo
|   forEdition()  la de una edición, dentro de su diseñador: parte de lo del
|                 torneo y puede tener lo suyo
|
| Ver docs/md/79-Sala-De-Participantes.md y 80-Participantes-De-Cada-Edicion.md
|
*/

class ParticipantRoomBuilder
{
    public function __construct(
        private readonly UniverseTournamentEligibility $eligibility,
        private readonly UniverseEntityVersionResolver $versions,
        private readonly CompetitionStartRouting $routing,
    ) {
    }

    public function build(Universe $universe, UniverseTournament $tournament): array
    {
        $guardado = (array) ($tournament->eligibility ?? []);

        $diseno = $this->eligibility->normalize($guardado);
        $doors = $this->routing->normalizeDoors($guardado['doors'] ?? null);

        $entidades = $this->entities($universe);
        $starts = $this->starts((int) $tournament->tournament_template_id);
        $plazas = collect($starts)->mapWithKeys(fn ($s) => [$s['id'] => $s['capacity']])->all();

        /* -------------------------- lo que dice el servidor */

        $dentro = $this->eligibility->matching($universe, $guardado);
        $plan = $this->routing->plan($universe, $doors, $plazas, $guardado);

        $puertaDe = [];
        foreach ($plan['assignments'] as $startId => $ids) {
            foreach ($ids as $id) {
                $puertaDe[$id] = $startId;
            }
        }

        $reglasPuerta = collect($doors['rules'])->keyBy('start_id');

        $caras = $dentro->mapWithKeys(fn (UniverseEntity $e) => [
            (int) $e->id => $this->versions->face(
                $e,
                $guardado,
                $doors['mode'] === 'RULES' && isset($puertaDe[$e->id]) ? ($reglasPuerta[$puertaDe[$e->id]] ?? null) : null
            ),
        ])->all();

        return [
            'context' => 'TOURNAMENT',
            'tournament' => $this->tournamentInfo($tournament),
            'templateId' => (int) $tournament->tournament_template_id,
            'catalog' => $this->eligibility->catalog($universe),
            'roster' => $this->roster($universe, $entidades),
            'starts' => $starts,
            'design' => $diseno + ['doors' => $doors],
            'server' => [
                'matching' => $dentro->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'plan' => $plan,
                'faces' => $caras,
            ],
            'modes' => UniverseTournamentEligibility::MODES,
            'fromLabels' => UniverseEntityVersionResolver::FROM,
        ];
    }

    /*
     * La sala de una edición.
     *
     * El diseño sale, por orden: de lo que se quedó a medias tras un error
     * de validación, de la propia edición, de la edición que se copia, o del
     * reparto que tenía una edición de antes de que esto existiera. Si no
     * hay nada, la edición usa lo del torneo.
     */
    public function forEdition(
        Universe $universe,
        UniverseTournament $tournament,
        ?TournamentInstance $competition,
        ?TournamentInstance $source,
        ?TournamentTemplate $template
    ): array {

        $participantes = app(EditionParticipants::class);
        $templateId = (int) ($template?->id ?? $tournament->tournament_template_id);
        $guardado = (array) ($tournament->eligibility ?? []);
        $origen = $competition ?? $source;

        $antiguo = old('participant_design');

        $diseno = match (true) {
            is_string($antiguo) && is_array(json_decode($antiguo, true)) => json_decode($antiguo, true),
            (bool) $origen?->participant_design => $origen->participant_design,
            $origen !== null => $this->legacyDesign($origen, $participantes),
            default => null,
        };

        return [
            'context' => 'EDITION',
            'tournament' => $this->tournamentInfo($tournament),
            'templateId' => $templateId,
            'catalog' => $this->eligibility->catalog($universe),
            'roster' => $this->roster($universe, $this->entities($universe)),
            'starts' => $this->starts($templateId),
            'base' => $this->eligibility->normalize($guardado) + ['doors' => $this->routing->normalizeDoors($guardado['doors'] ?? null)],
            'design' => $participantes->normalize($diseno),
            'server' => [],
            'modes' => UniverseTournamentEligibility::MODES,
            'fromLabels' => UniverseEntityVersionResolver::FROM,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Piezas comunes
    |--------------------------------------------------------------------------
    */

    private function entities(Universe $universe)
    {
        return UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    private function roster(Universe $universe, $entidades): array
    {
        $versiones = $entidades->map(fn (UniverseEntity $e) => $this->versions->options($e));

        return collect($this->eligibility->roster($universe))
            ->map(fn (array $c) => $c + [
                'code' => $entidades[$c['id']]->code ?? null,
                'versions' => $versiones[$c['id']] ?? [],
            ])
            ->values()
            ->all();
    }

    private function starts(int $templateId): array
    {
        return TournamentStart::query()
            ->where('tournament_template_id', $templateId)
            ->where('status', 'ACTIVE')
            ->orderBy('sequence_number')
            ->get()
            ->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => $s->name ?: 'Entrada',
                'code' => $s->code,
                'description' => $s->description,
                'capacity' => $s->expected_participants ? (int) $s->expected_participants : null,
            ])
            ->values()
            ->all();
    }

    private function tournamentInfo(UniverseTournament $tournament): array
    {
        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'image_url' => $tournament->image_url,
            'template_id' => $tournament->tournament_template_id,
            'template_name' => $tournament->tournamentTemplate?->name,
            'editions' => $tournament->instances()->count(),
            'room_url' => route('universes.tournaments.participants', [$tournament->universe_id, $tournament->id]),
        ];
    }

    /* El reparto que ya tenía una edición sin diseño guardado */
    private function legacyDesign(TournamentInstance $edicion, EditionParticipants $participantes): ?array
    {
        $reparto = TournamentInstanceParticipant::query()
            ->where('tournament_instance_id', $edicion->id)
            ->whereNotNull('source_start_id')
            ->whereNotNull('universe_entity_id')
            ->orderBy('seed')
            ->get(['source_start_id', 'universe_entity_id'])
            ->groupBy('source_start_id')
            ->map(fn ($filas) => $filas->pluck('universe_entity_id')->map(fn ($id) => (int) $id)->values()->all())
            ->all();

        return $reparto === [] ? null : $participantes->fromAssignments($reparto);
    }
}

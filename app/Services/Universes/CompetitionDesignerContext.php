<?php

namespace App\Services\Universes;

use App\Models\TournamentInstance;
use App\Models\TournamentTemplate;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Models\UniverseTournament;
use App\Models\UniverseTrophy;
use App\Services\Games\GameRegistry;
use App\Services\Games\UniverseGameService;
use App\Services\Tournaments\Runtime\CompetitionPhasePlan;

/*
|--------------------------------------------------------------------------
| CompetitionDesignerContext
|--------------------------------------------------------------------------
|
| Todo lo que la pantalla de una edicion necesita saber, en un sitio.
|
| Alta y edicion piden exactamente lo mismo -que hereda del torneo, que
| plantillas hay y como estan hechas, quien puede competir, que trofeos se
| pueden dar-, y tenerlo dos veces garantiza que un dia dejen de coincidir.
|
| Lo que NO hace: decidir. Aqui solo se reune; quien manda sobre que es
| CompetitionPhasePlan, y quien reparte competidores es
| CompetitionStartRouting.
|
*/
class CompetitionDesignerContext
{
    public function __construct(
        private readonly GameRegistry $games,
        private readonly UniverseGameService $universeGames,
        private readonly UniverseTournamentEligibility $eligibility,
        private readonly CompetitionTemplateBrief $briefs,
        private readonly CompetitionPhasePlan $plan,
    ) {
    }

    /*
     * @param  TournamentInstance|null  $competition  null al crear
     * @param  TournamentInstance|null  $source       de que edicion se copia
     */
    public function build(
        Universe $universe,
        UniverseTournament $tournament,
        ?TournamentInstance $competition = null,
        ?TournamentInstance $source = null
    ): array {

        $template = $this->chosenTemplate($tournament, $competition, $source);

        $candidates = $this->candidateTemplates($tournament);

        return [
            'inherited' => $this->inherited($tournament),

            /*
             * Los valores del formulario. Se llama asi y no 'competition'
             * porque la vista ya tiene un $competition -el modelo-, y dos
             * cosas distintas con el mismo nombre en la misma pantalla se
             * confunden el dia que alguien las toca.
             */
            'designerValues' => $this->values($universe, $tournament, $competition, $source),

            'games' => $this->gamesFor($universe, $tournament),

            'templateBriefs' => $this->briefs->briefs(
                $candidates,
                $tournament->tournament_template_id
            ),

            'chosenTemplate' => $template
                ? $this->briefs->brief($template, $tournament->tournament_template_id)
                : null,

            'phaseSettings' => $this->phaseSettings($competition, $source),

            /*
             * Los modos con los que una fase de grupos puede ordenar su lista
             * unica, con su explicacion. Solo se ofrecen en fases GROUP_STAGE.
             */
            'overallRankingModes' =>
            \App\Services\Tournaments\GroupStage\GroupStageOverallRanking::MODES,

            'competitors' => $this->competitors($universe),

            'eligibilityCatalog' => $this->eligibility->catalog($universe),

            /*
             * Los del universo, con una marca: cuales entrega ya el TORNEO.
             *
             * Sin esa marca la lista era un catalogo plano y no se veia lo
             * que el usuario habia configurado arriba, que es justo lo que
             * viene a comprobar al abrir esta pantalla.
             */
            'sharedTrophies' => $this->trophyList(
                UniverseTrophy::query()
                    ->where('universe_id', $universe->id)
                    ->shared()
                    ->orderBy('name')
                    ->get(),
                $tournament->rewards()
                    ->whereNotNull('universe_trophy_id')
                    ->pluck('universe_trophy_id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            ),

            'ownTrophies' => $this->trophyList(
                $competition
                    ? $competition->trophies()->orderBy('name')->get()
                    : collect()
            ),

            'inheritedRewards' => $tournament
                ->rewards()
                ->with('trophy')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'label' => $r->label,
                    'trigger' => $r->trigger,
                    'threshold' => $r->threshold,
                    'stat_key' => $r->stat_key,
                    'operation' => $r->operation,
                    'amount' => $r->amount,
                    'trophy' => $r->trophy ? [
                        'id' => $r->trophy->id,
                        'name' => $r->trophy->name,
                        'icon' => $r->trophy->icon,
                        'image_url' => $r->trophy->image_url,
                    ] : null,
                ])
                ->all(),

            'ownRewards' => $this->ownRewards($competition, $source),

            'previousEditions' => $this->previousEditions($tournament, $competition),

            /*
             * Si todavia se puede cambiar quien compite.
             *
             * No es "estoy editando": es "esto no ha empezado". Una edicion
             * en borrador tiene su cuadro dibujado en limpio y rehacerlo no
             * estropea nada; una empezada tiene rondas jugadas y cambiar la
             * gente dejaria enfrentamientos apuntando a quien ya no esta.
             */
            'canReassign' => $competition === null
                || app(\App\Services\Tournaments\Runtime\TournamentInstanceService::class)
                    ->canReassign($competition),

            /* Quien entra hoy por cada puerta, para poder retocarlo */
            /*
             * Y también el de la edición que se está copiando.
             *
             * Todo lo demás —fases, juego, batalla, reglas de entrada— usa
             * `$competition ?? $source`; esto usaba solo `$competition`, así
             * que al crear una edición nueva a partir de otra el reparto por
             * puertas era lo único que NO se heredaba: aparecía vacío y había
             * que rehacerlo a mano.
             */
            'currentAssignments' => $this->currentAssignments($competition ?? $source),

            'decisionModes' => CompetitionPhasePlan::DECISION_MODES,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Lo que viene dado por el torneo
    |--------------------------------------------------------------------------
    |
    | Una edicion no puede abrir lo que el torneo cerro. Esto es lo que la
    | pantalla necesita para saber que ensenar apagado y por que.
    |
    */

    private function inherited(UniverseTournament $tournament): array
    {
        return [
            'id' => $tournament->id,
            'name' => $tournament->name,

            'game_mode' => $tournament->game_mode ?: 'SINGLE',
            'game_key' => $tournament->game_key,

            /* Si la edicion puede bajar la decision a cada fase */
            'allow_phase_game' => (bool) $tournament->allow_phase_game,
            'allow_phase_battle' => (bool) $tournament->allow_phase_battle,

            'battle_participants' => $tournament->battle_participants,
            'series_format' => $tournament->series_format ?: 'BEST_OF',
            'best_of' => (int) ($tournament->best_of ?: 1),
            'fixed_games' => (int) ($tournament->fixed_games ?: 1),
            'decision_mode' => $tournament->decision_mode ?: 'SERIES_THEN_POINTS',
            'allow_draws' => (bool) $tournament->allow_draws,

            'template_id' => $tournament->tournament_template_id,

            'eligibility' => $this->eligibility->normalize($tournament->eligibility),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Los valores actuales del formulario
    |--------------------------------------------------------------------------
    |
    | Tres origenes, de mas fuerte a mas debil: lo que se quedo a medias
    | tras un error de validacion, lo guardado -o lo copiado de otra
    | edicion-, y lo que hereda del torneo.
    |
    */

    private function values(
        Universe $universe,
        UniverseTournament $tournament,
        ?TournamentInstance $competition,
        ?TournamentInstance $source
    ): array {

        $base = $competition ?? $source;

        $gameKey = $base?->game_key
            ?: ($tournament->game_key ?: $this->universeGames->defaultKey($universe));

        return [
            'id' => $competition?->id,

            /*
             * El nombre NO se copia.
             *
             * Al editar es el suyo; al crear -aunque se copie de otra
             * edicion- se propone uno nuevo. Dos ediciones llamadas igual
             * son indistinguibles en cualquier lista, y copiar es
             * justamente la via por la que eso pasaria siempre.
             */
            'name' => old('name', $competition?->name ?? $this->suggestName($tournament)),
            'description' => old('description', $base?->description),
            'image_url' => $competition?->image
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($competition->image)
                : null,

            'universe_season_id' => old(
                'universe_season_id',
                $competition?->universe_season_id ?? $universe->activeSeason()?->id
            ),

            'tournament_template_id' => (int) old(
                'tournament_template_id',
                $base?->tournament_template_id ?: $tournament->tournament_template_id
            ),

            'game_key' => old('game_key', $gameKey),
            'game_scope' => old('game_scope', $base?->game_scope ?: 'COMPETITION'),

            'battle_scope' => old('battle_scope', $base?->battle_scope ?: 'COMPETITION'),

            'series_format' => old(
                'series_format',
                $base?->series_format ?: ($tournament->series_format ?: 'BEST_OF')
            ),

            'best_of' => (int) old(
                'best_of',
                $base?->best_of ?: ($tournament->best_of ?: 1)
            ),

            'fixed_games' => (int) old(
                'fixed_games',
                $base?->fixed_games ?: ($tournament->fixed_games ?: 1)
            ),

            'battle_participants' => old(
                'battle_participants',
                $base?->battle_participants ?? $tournament->battle_participants
            ),

            'decision_mode' => old(
                'decision_mode',
                $base?->decision_mode ?: ($tournament->decision_mode ?: 'SERIES_THEN_POINTS')
            ),

            'allow_draws' => (bool) old(
                'allow_draws',
                $base?->allow_draws ?? $tournament->allow_draws
            ),

            'start_rules' => old('start_rules', $base?->start_rules ?? []),

            'copied_from_instance_id' => $source?->id,

            'status' => $competition?->status,
            'is_editable' => $competition === null || $competition->status === 'DRAFT',
        ];
    }

    /*
     * "Copa Doujutsu — 3ª edición". Se propone, no se impone.
     */
    private function suggestName(UniverseTournament $tournament): string
    {
        $played = $tournament->instances()->count();

        return $tournament->name . ' — edición ' . ($played + 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Los juegos que puede elegir
    |--------------------------------------------------------------------------
    */

    private function gamesFor(Universe $universe, UniverseTournament $tournament): array
    {
        $enabled = $this->universeGames
            ->enabled($universe)
            ->pluck('game_key')
            ->map(fn ($k) => strtoupper((string) $k))
            ->all();

        /*
         * De juego unico, pero sin juego elegido.
         *
         * Pasa siempre en los torneos creados antes de que el torneo
         * pudiera elegir uno. Sin esto la pantalla se quedaba sin ninguna
         * opcion marcable: ni el suyo, porque no tiene, ni otro, porque es
         * de juego unico. Manda el del universo, que es el que la
         * competicion iba a usar de todas formas.
         */
        $single = strtoupper((string) ($tournament->game_key ?: ''))
            ?: strtoupper($this->universeGames->defaultKey($universe));

        return $this->games
            ->definitions()
            ->map(fn (array $g) => [
                'key' => $g['key'],
                'name' => $g['name'],
                'icon' => $g['icon'] ?? null,
                'accent' => $g['accent'] ?? 'violet',
                'tagline' => $g['tagline'] ?? ($g['description'] ?? null),
                'stats' => collect($g['stats'] ?? [])
                    ->map(fn ($s, $k) => [
                        'key' => is_string($k) ? $k : ($s['key'] ?? ''),
                        'label' => is_array($s) ? ($s['label'] ?? $s['name'] ?? $k) : (string) $s,
                    ])
                    ->values()
                    ->all(),

                'min_participants' => $g['min_participants'] ?? 2,
                'max_participants' => $g['max_participants'] ?? null,
                'allows_draws' => (bool) ($g['allows_draws'] ?? false),

                'enabled_here' => $enabled === [] || in_array($g['key'], $enabled, true),

                /*
                 * Un torneo de juego unico no deja elegir: su juego es su
                 * juego, y una edicion que lo cambiase ya no seria la misma
                 * competicion.
                 */
                'allowed' => ($tournament->game_mode ?: 'SINGLE') === 'VARIED'
                    || $single === $g['key'],
            ])
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Plantillas
    |--------------------------------------------------------------------------
    */

    private function candidateTemplates(UniverseTournament $tournament)
    {
        $default = $tournament->tournamentTemplate;

        if (! $default) {
            return collect();
        }

        return TournamentTemplate::query()
            ->where('user_id', $default->user_id)
            ->where('status', 'ACTIVE')
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$default->id])
            ->orderBy('name')
            ->get();
    }

    private function chosenTemplate(
        UniverseTournament $tournament,
        ?TournamentInstance $competition,
        ?TournamentInstance $source
    ): ?TournamentTemplate {

        $id = (int) old(
            'tournament_template_id',
            $competition?->tournament_template_id
                ?: ($source?->tournament_template_id ?: $tournament->tournament_template_id)
        );

        if ($id <= 0) {
            return $tournament->tournamentTemplate;
        }

        return TournamentTemplate::query()
            ->with([
                'graphNodes.phaseTemplate',
                'graphNodes.entryPorts',
                'graphStarts',
                'graphTerminals',
                'graphConnections',
            ])
            ->find($id)
            ?? $tournament->tournamentTemplate;
    }

    /*
    |--------------------------------------------------------------------------
    | La excepcion de cada fase
    |--------------------------------------------------------------------------
    |
    | Solo lo que la fase dice DE VERDAD. Un nulo aqui significa "lo que
    | diga la competicion", y rellenarlo con el valor heredado haria que
    | cambiar el de la competicion dejase de afectar a nadie.
    |
    */

    private function phaseSettings(
        ?TournamentInstance $competition,
        ?TournamentInstance $source
    ): array {

        if ($old = old('phases')) {
            return (array) $old;
        }

        $base = $competition ?? $source;

        if (! $base) {
            return [];
        }

        return $base->phases
            ->mapWithKeys(fn ($phase) => [
                (int) $phase->node_id => [
                    'game_key' => $phase->game_key,
                    'series_format' => $phase->series_format,
                    'best_of' => $phase->best_of,
                    'fixed_games' => $phase->fixed_games,
                    'battle_participants' => $phase->battle_participants,
                    'decision_mode' => $phase->decision_mode,
                    'allow_draws' => $phase->allow_draws,

                    /* Solo lo usa una fase de grupos. Nulo = el de la plantilla */
                    'overall_ranking_mode' => $phase->overall_ranking_mode,
                ],
            ])
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Competidores
    |--------------------------------------------------------------------------
    */

    /*
     * Los competidores del universo, con sus atributos.
     *
     * Se reutiliza el mismo roster que arma la galeria del torneo en vez de
     * construir otro: aquel ya trae, por cada atributo, LA CLAVE con la que
     * casa una regla y EL TEXTO con el que se lee. Tener dos versiones de
     * lo mismo garantizaba que un dia dejasen de coincidir -y de hecho la
     * de aqui se quedo sin los textos, asi que las fichas no podian
     * ensenar los atributos-.
     */
    private function competitors(Universe $universe): array
    {
        return $this->eligibility->roster($universe);
    }


    /*
    |--------------------------------------------------------------------------
    | Premios propios de la edicion
    |--------------------------------------------------------------------------
    */

    private function ownRewards(
        ?TournamentInstance $competition,
        ?TournamentInstance $source
    ): array {

        if (($old = old('rewards')) !== null) {
            return array_values((array) $old);
        }

        $base = $competition ?? $source;

        if (! $base) {
            return [];
        }

        return $base->rewards()
            ->when(
                /*
                 * Al copiar solo vienen los que su dueno marco para
                 * arrastrarse. Un premio de aniversario no deberia
                 * aparecer solo en la edicion siguiente.
                 */
                $competition === null,
                fn ($q) => $q->where('carry_forward', true)
            )
            ->get()
            ->map(fn ($r) => [
                'id' => $competition ? $r->id : null,
                'node_id' => $r->node_id,
                'trigger' => $r->trigger,
                'threshold' => $r->threshold,
                'stat_key' => $r->stat_key,
                'operation' => $r->operation,
                'amount' => $r->amount,
                'universe_trophy_id' => $r->universe_trophy_id,
                'label' => $r->label,
                'carry_forward' => (bool) $r->carry_forward,
            ])
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Quien entra hoy por cada puerta
    |--------------------------------------------------------------------------
    |
    | Sale del ESTADO y no de la tabla de participantes porque el estado es
    | quien sabe por que puerta entro cada uno: la fila de participante solo
    | dice que esta dentro.
    |
    | @return array<int,array<int,int>>  start_id => [universe_entity_id]
    */
    private function currentAssignments(?TournamentInstance $competition): array
    {
        if (($old = old('assignments')) !== null) {
            return (array) $old;
        }

        if (! $competition) {
            return [];
        }

        $state = $competition->state?->state ?? [];

        $out = [];

        foreach (($state['participants'] ?? []) as $participant) {

            $start = (int) ($participant['source_start_id'] ?? 0);
            $entity = (int) ($participant['universe_entity_id'] ?? 0);

            if ($start <= 0 || $entity <= 0) {
                continue;
            }

            $out[$start][] = $entity;
        }

        return $out;
    }

    /*
     * @param  array<int,int>  $fromTournament  ids que el torneo ya entrega
     */
    private function trophyList($trophies, array $fromTournament = []): array
    {
        return collect($trophies)
            ->map(fn (UniverseTrophy $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'icon' => $t->icon,
                'image_url' => $t->image_url,
                'tier' => $t->tier,
                'tier_label' => UniverseTrophy::TIERS[$t->tier] ?? $t->tier,
                'own' => $t->tournament_instance_id !== null,

                /* Lo entrega el torneo, asi que esta edicion lo hereda */
                'inherited' => in_array((int) $t->id, $fromTournament, true),
            ])
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Ediciones anteriores
    |--------------------------------------------------------------------------
    |
    | De donde copiar. La siguiente edicion casi nunca se disena de cero:
    | se parte de la anterior y se cambia lo que cambio.
    |
    */

    private function previousEditions(
        UniverseTournament $tournament,
        ?TournamentInstance $competition
    ): array {

        return $tournament->instances()
            ->when(
                $competition,
                fn ($q) => $q->whereKeyNot($competition->id)
            )
            ->with('season')
            ->orderByDesc('sequence_number')
            ->limit(12)
            ->get()
            ->map(fn (TournamentInstance $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'code' => $i->code,
                'status' => $i->status,
                'season' => $i->season?->name,
                'participants' => $i->participant_count,
                'template_id' => $i->tournament_template_id,
                'created_at' => $i->created_at?->format('d/m/Y'),
            ])
            ->all();
    }
}

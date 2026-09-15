<?php

namespace App\Support\Universes;

use App\Models\Universe;

/*
|--------------------------------------------------------------------------
| UniverseSettings
|--------------------------------------------------------------------------
|
| Acceso tipado a universes.settings.
|
| Regla: aquí solo entra configuración que TIENE comportamiento real. Cada
| clave dice abajo dónde se usa; una opción que no hace nada no se ofrece.
|
|   identidad       color, icono, lema y encuadre de la portada
|                   → sidebar, cabecera, Resumen y «Mis universos»
|   vocabulario     cómo se llaman aquí las entidades, las temporadas…
|                   → sidebar y Resumen
|   menú e inicio   qué secciones se ven y a dónde lleva entrar al mundo
|                   → sidebar y todos los enlaces a un universo
|   resumen         qué bloques enseña el Resumen y en qué orden
|   clasificación   puntos, mínimo para aparecer y desempates
|                   → UniverseRankingService
|   competiciones   con qué nace un torneo nuevo de este mundo
|                   → diseñador de torneos y su sala de participantes
|   explorar        con qué criterio y modo se abre el mapa
|
| Guardar solo acepta claves conocidas y cada valor pasa por su tipo:
| el JSON no se llena de basura ni de valores que la pantalla no espera.
|
| Ver docs/md/81-Configuracion-Del-Universo.md
|
*/

class UniverseSettings
{
    public const ICONS = [
        'orbita', 'globo', 'trofeo', 'espadas', 'medalla', 'chispa', 'libro', 'dado', 'brujula',
        'capas', 'grafo', 'matraz', 'calendario', 'historial', 'barras', 'usuario', 'puerta', 'cuadricula',
    ];

    public const PALETTE = [
        '#a78bfa', '#818cf8', '#38bdf8', '#22d3ee', '#2dd4bf', '#34d399', '#a3e635',
        '#facc15', '#fbbf24', '#fb923c', '#f87171', '#fb7185', '#f472b6', '#e879f9',
    ];

    /* Secciones del sidebar que se pueden esconder: [ruta, etiqueta por defecto, icono] */
    public const NAV = [
        'explorer' => ['universes.explorer', 'Explorar', 'brujula'],
        'games' => ['universes.games.index', 'Juegos', 'dado'],
        'entities' => ['universes.entities.index', 'Entidades', 'chispa'],
        'seasons' => ['universes.seasons.index', 'Temporadas', 'calendario'],
        'tournaments' => ['universes.tournaments.index', 'Torneos', 'trofeo'],
        'competitions' => ['universes.competitions.index', 'Competiciones', 'espadas'],
        'history' => ['universes.history', 'Historial', 'historial'],
        'trophies' => ['universes.trophies.index', 'Trofeos', 'medalla'],
        'ranking' => ['universes.ranking', 'Clasificación', 'barras'],
    ];

    /* A dónde lleva entrar en el universo */
    public const HOMES = [
        'show' => ['universes.show', 'Resumen'],
        'explorer' => ['universes.explorer', 'Explorar'],
        'competitions' => ['universes.competitions.index', 'Competiciones'],
        'tournaments' => ['universes.tournaments.index', 'Torneos'],
        'ranking' => ['universes.ranking', 'Clasificación'],
        'entities' => ['universes.entities.index', 'Entidades'],
    ];

    /* Los bloques del Resumen: [etiqueta, zona] */
    public const BLOCKS = [
        'atencion' => ['Lo que espera por ti', 'top'],
        'cifras' => ['Las cifras del mundo', 'top'],
        'en-juego' => ['Lo que se está jugando', 'top'],
        'temporada' => ['La temporada en curso', 'left'],
        'linea' => ['La línea del tiempo', 'left'],
        'actividad' => ['Qué ha pasado', 'left'],
        'ranking' => ['Quién manda', 'right'],
        'campeones' => ['Los últimos en ganar', 'right'],
        'juegos' => ['Con qué se juega', 'right'],
    ];

    public const TIEBREAKS = [
        'TITLES' => 'Más títulos',
        'WINS' => 'Más victorias',
        'WIN_RATE' => 'Mejor porcentaje de victorias',
        'FEWER_MATCHES' => 'Menos partidas para los mismos puntos',
        'NAME' => 'Orden alfabético',
    ];

    public const COVER_POSITIONS = ['top' => 'Arriba', 'center' => 'Centro', 'bottom' => 'Abajo'];

    public const EXPLORER_MODES = ['cuadros' => 'Cuadros', 'cruce' => 'Cruce', 'compartidos' => 'Compartidos', 'todo' => 'Todo junto'];

    public const LABELS = [
        'label_entities' => 'Entidades',
        'label_season' => 'Temporada',
        'label_seasons' => 'Temporadas',
        'label_tournaments' => 'Torneos',
        'label_competitions' => 'Competiciones',
    ];

    private const DEFAULTS = [

        /* ---------------------------------------------- clasificación */
        'points_champion' => 10,
        'points_win' => 3,
        'points_draw' => 1,
        'points_loss' => 0,
        'points_participation' => 1,
        'ranking_min_competitions' => 0,
        'ranking_tiebreaks' => ['TITLES', 'WINS', 'WIN_RATE'],

        /* ---------------------------------------------- identidad */
        'accent' => '#a78bfa',
        'icon' => 'orbita',
        'tagline' => '',
        'cover_position' => 'center',

        /* ---------------------------------------------- vocabulario */
        'label_entities' => 'Entidades',
        'label_season' => 'Temporada',
        'label_seasons' => 'Temporadas',
        'label_tournaments' => 'Torneos',
        'label_competitions' => 'Competiciones',

        /* ---------------------------------------------- menú e inicio */
        'hidden_nav' => [],
        'home' => 'show',

        /* ---------------------------------------------- resumen */
        'summary_order' => ['atencion', 'cifras', 'en-juego', 'temporada', 'linea', 'actividad', 'ranking', 'campeones', 'juegos'],
        'summary_hidden' => [],

        /* ---------------------------------------------- torneos nuevos */
        'default_series_format' => 'BEST_OF',
        'default_best_of' => 3,
        'default_fixed_games' => 2,
        'default_decision_mode' => 'SERIES_THEN_POINTS',
        'default_allow_draws' => false,
        'default_recurrence' => 'EVERY_SEASON',
        'default_face_mode' => 'AUTO',
        'default_door_strategy' => 'BALANCED',

        /* ---------------------------------------------- explorar */
        'explorer_default_criterion' => '',
        'explorer_default_mode' => 'cuadros',
    ];

    public function __construct(
        private readonly Universe $universe
    ) {}

    public function all(): array
    {
        $guardado = (array) ($this->universe->settings ?? []);
        $out = self::DEFAULTS;

        foreach ($guardado as $clave => $valor) {
            if (array_key_exists($clave, self::DEFAULTS)) {
                $out[$clave] = $this->sanitize($clave, $valor);
            }
        }

        return $out;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return $this->all()[$key] ?? $fallback ?? self::DEFAULTS[$key] ?? null;
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    /*
     * Guarda solo las claves conocidas, cada una con su tipo. Lo que no llega
     * se queda como estaba.
     */
    public function save(array $values): void
    {
        $actual = $this->all();

        foreach (self::DEFAULTS as $key => $default) {
            if (array_key_exists($key, $values)) {
                $actual[$key] = $this->sanitize($key, $values[$key]);
            }
        }

        $this->universe->update(['settings' => $actual]);
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    /*
    |--------------------------------------------------------------------------
    | Lecturas con sentido
    |--------------------------------------------------------------------------
    */

    public function accent(): string
    {
        return $this->get('accent');
    }

    public function icon(): string
    {
        return $this->get('icon');
    }

    public function tagline(): string
    {
        return (string) $this->get('tagline');
    }

    /* Para object-position */
    public function coverPosition(): string
    {
        return $this->get('cover_position');
    }

    public function label(string $key): string
    {
        return (string) ($this->get($key) ?: (self::LABELS[$key] ?? $key));
    }

    public function navVisible(string $key): bool
    {
        return ! in_array($key, $this->get('hidden_nav'), true);
    }

    public function homeUrl(): string
    {
        [$ruta] = self::HOMES[$this->get('home')] ?? self::HOMES['show'];

        if ($this->get('home') !== 'show' && ! $this->navVisible($this->get('home'))) {
            $ruta = 'universes.show';
        }

        return route($ruta, $this->universe);
    }

    /* Los bloques visibles de una zona del Resumen, en su orden */
    public function summaryBlocks(string $zone): array
    {
        $ocultos = $this->get('summary_hidden');

        return array_values(array_filter(
            $this->get('summary_order'),
            fn ($b) => (self::BLOCKS[$b][1] ?? null) === $zone && ! in_array($b, $ocultos, true)
        ));
    }

    /* Con qué nace un torneo nuevo */
    public function competitionDefaults(): array
    {
        return [
            'series_format' => $this->get('default_series_format'),
            'best_of' => $this->get('default_best_of'),
            'fixed_games' => $this->get('default_fixed_games'),
            'decision_mode' => $this->get('default_decision_mode'),
            'allow_draws' => $this->get('default_allow_draws'),
            'recurrence_mode' => $this->get('default_recurrence'),
            'face_mode' => $this->get('default_face_mode'),
            'door_strategy' => $this->get('default_door_strategy'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cada valor, en su tipo
    |--------------------------------------------------------------------------
    */

    private function sanitize(string $key, mixed $value): mixed
    {
        $defecto = self::DEFAULTS[$key];

        return match ($key) {
            'points_champion', 'points_win', 'points_draw', 'points_loss', 'points_participation'
                => $this->entero($value, 0, 1000, $defecto),

            'ranking_min_competitions' => $this->entero($value, 0, 100, $defecto),

            'ranking_tiebreaks' => $this->lista($value, array_keys(self::TIEBREAKS)) ?: [],

            'accent' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $defecto,

            'icon' => in_array($value, self::ICONS, true) ? $value : $defecto,

            'tagline' => mb_substr(trim(strip_tags((string) $value)), 0, 120),

            'cover_position' => array_key_exists((string) $value, self::COVER_POSITIONS) ? $value : $defecto,

            'label_entities', 'label_season', 'label_seasons', 'label_tournaments', 'label_competitions'
                => mb_substr(trim(strip_tags((string) $value)), 0, 30) ?: $defecto,

            'hidden_nav' => $this->lista($value, array_keys(self::NAV)),

            'home' => array_key_exists((string) $value, self::HOMES) ? $value : $defecto,

            'summary_order' => (function () use ($value) {
                $orden = $this->lista($value, array_keys(self::BLOCKS));

                /* Un bloque que falta en el orden va al final, no desaparece */
                return [...$orden, ...array_values(array_diff(array_keys(self::BLOCKS), $orden))];
            })(),

            'summary_hidden' => $this->lista($value, array_keys(self::BLOCKS)),

            'default_series_format' => in_array($value, ['BEST_OF', 'FIXED_GAMES'], true) ? $value : $defecto,
            'default_best_of' => in_array((int) $value, [1, 3, 5, 7, 9], true) ? (int) $value : $defecto,
            'default_fixed_games' => $this->entero($value, 1, 20, $defecto),
            'default_decision_mode' => array_key_exists((string) $value, \App\Services\Tournaments\Runtime\CompetitionPhasePlan::DECISION_MODES) ? $value : $defecto,
            'default_allow_draws' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'default_recurrence' => in_array($value, ['EVERY_SEASON', 'MANUAL'], true) ? $value : $defecto,
            'default_face_mode' => in_array($value, ['AUTO', 'BASE'], true) ? $value : $defecto,
            'default_door_strategy' => in_array($value, ['BALANCED', 'IN_ORDER', 'RANDOM'], true) ? $value : $defecto,

            'explorer_default_criterion' => mb_substr((string) $value, 0, 150),
            'explorer_default_mode' => array_key_exists((string) $value, self::EXPLORER_MODES) ? $value : $defecto,

            default => $defecto,
        };
    }

    private function entero(mixed $value, int $min, int $max, int $defecto): int
    {
        return is_numeric($value) ? max($min, min($max, (int) $value)) : $defecto;
    }

    /* Una lista sin repetidos y solo con valores permitidos, en el orden recibido */
    private function lista(mixed $value, array $permitidos): array
    {
        return array_values(array_unique(array_filter(
            array_map('strval', (array) $value),
            fn ($v) => in_array($v, $permitidos, true)
        )));
    }
}

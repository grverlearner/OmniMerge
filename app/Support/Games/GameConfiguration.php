<?php

namespace App\Support\Games;

/*
|--------------------------------------------------------------------------
| GameConfiguration
|--------------------------------------------------------------------------
|
| Lo que un Universo decide sobre un juego.
|
| El Game Engine declara QUÉ estadísticas existen y en qué rango absoluto
| tienen sentido. El Universo decide, dentro de eso, con qué valores entra
| un competidor nuevo y hasta dónde puede llegar en ese mundo.
|
| Un Universo de recién llegados puede repartir 0–3; uno de veteranos,
| 5–20. Es el mismo juego y el mismo motor.
|
| Vive en el JSON `universe_games.configuration`, que ya existía sin usar:
| una columna por opción habría obligado a migrar cada vez que un juego
| nuevo inventara un ajuste propio.
|
*/

class GameConfiguration
{
    public function __construct(
        private readonly array $definition,
        private readonly array $stored = []
    ) {}

    /**
     * Ajustes de una estadística, mezclando lo que declara el motor con
     * lo que decidió el Universo.
     *
     * @return array{default: float, min: float, max: float, label: string, help: ?string}
     */
    public function stat(string $key): array
    {
        $schema =
            collect($this->definition['stats'] ?? [])
            ->firstWhere('key', $key)
            ?? [];

        $override =
            $this->stored['stats'][$key] ?? [];

        /* Los limites del motor son el techo: el Universo acota dentro */
        $engineMin = (float) ($schema['min'] ?? 0);
        $engineMax = (float) ($schema['max'] ?? 9999);

        $min = $this->clamp(
            (float) ($override['min'] ?? $engineMin),
            $engineMin,
            $engineMax
        );

        $max = $this->clamp(
            (float) ($override['max'] ?? $engineMax),
            $engineMin,
            $engineMax
        );

        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        $default = $this->clamp(
            (float) ($override['default'] ?? $schema['default'] ?? $min),
            $min,
            $max
        );

        return [
            'key' => $key,
            'label' => $schema['label'] ?? $key,
            'help' => $schema['help'] ?? null,
            'step' => $schema['step'] ?? 0.1,

            'default' => $default,
            'min' => $min,
            'max' => $max,

            /* Para poder mostrar de dónde sale cada límite */
            'engine_min' => $engineMin,
            'engine_max' => $engineMax,
            'is_customised' => $override !== [],
        ];
    }

    /**
     * Todas las estadísticas del juego, ya resueltas.
     *
     * @return array<int, array>
     */
    public function stats(): array
    {
        return collect($this->definition['stats'] ?? [])
            ->map(fn(array $schema) => $this->stat($schema['key']))
            ->all();
    }

    /**
     * Valores con los que entra un competidor nuevo en este Universo.
     *
     * @return array<string, float>
     */
    public function initialStats(): array
    {
        $values = [];

        foreach ($this->stats() as $stat) {
            $values[$stat['key']] = $stat['default'];
        }

        return $values;
    }

    /**
     * Acota unas estadísticas a los límites de ESTE Universo.
     *
     * Se aplica después de que el motor normalice: el motor garantiza que
     * los valores son coherentes entre sí, el Universo que están dentro
     * de su rango.
     *
     * @return array<string, float>
     */
    public function clampStats(array $stats): array
    {
        foreach ($this->stats() as $stat) {

            if (! array_key_exists($stat['key'], $stats)) {
                continue;
            }

            $stats[$stat['key']] = $this->clamp(
                (float) $stats[$stat['key']],
                $stat['min'],
                $stat['max']
            );
        }

        return $stats;
    }

    /*
    |--------------------------------------------------------------------------
    | Opciones sueltas
    |--------------------------------------------------------------------------
    |
    | Espacio para lo que cada juego necesite sin tocar la base de datos.
    |
    */

    public function option(string $key, mixed $fallback = null): mixed
    {
        return $this->stored['options'][$key] ?? $fallback;
    }

    /**
     * Cómo queda el JSON tras aplicar unos cambios.
     */
    public static function merge(
        array $stored,
        array $stats,
        array $options = []
    ): array {

        return [
            'stats' => $stats,
            'options' => array_merge(
                $stored['options'] ?? [],
                $options
            ),
        ];
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return round(max($min, min($max, $value)), 4);
    }
}

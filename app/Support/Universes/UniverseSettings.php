<?php

namespace App\Support\Universes;

use App\Models\Universe;

/*
|--------------------------------------------------------------------------
| UniverseSettings
|--------------------------------------------------------------------------
|
| Acceso tipado a universes.settings, la columna JSON que ya existía y
| nunca se había usado.
|
| Regla: aquí solo entra configuración que TIENE comportamiento real.
| Nada de opciones decorativas que no hagan nada.
|
| Ver docs/md/28-Fase-10-Universo-Vivo.md
|
*/

class UniverseSettings
{
    private const DEFAULTS = [

        /*
         * Puntos del ranking. Se usan de verdad en
         * UniverseRankingService.
         */
        'points_champion' => 10,
        'points_win' => 3,
        'points_draw' => 1,
        'points_loss' => 0,
        'points_participation' => 1,
    ];

    public function __construct(
        private readonly Universe $universe
    ) {}

    public function all(): array
    {
        return array_merge(
            self::DEFAULTS,
            (array) ($this->universe->settings ?? [])
        );
    }

    public function get(
        string $key,
        mixed $fallback = null
    ): mixed {

        return $this->all()[$key]
            ?? $fallback
            ?? self::DEFAULTS[$key]
            ?? null;
    }

    public function int(
        string $key
    ): int {

        return (int) $this->get($key);
    }

    /*
     * Guarda solo las claves conocidas: evita que el JSON se llene de
     * campos sueltos que nadie lee.
     */
    public function save(
        array $values
    ): void {

        $clean = [];

        foreach (self::DEFAULTS as $key => $default) {

            if (array_key_exists($key, $values)) {
                $clean[$key] = (int) $values[$key];
            }
        }

        $this->universe->update([
            'settings' => array_merge($this->all(), $clean),
        ]);
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }
}

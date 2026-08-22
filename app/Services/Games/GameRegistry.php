<?php

namespace App\Services\Games;

use App\Services\Games\Contracts\GameEngine;
use App\Services\Games\Engines\HighestNumberGameEngine;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| GameRegistry
|--------------------------------------------------------------------------
|
| Único punto donde OmniMerge sabe qué juegos existen.
|
| Los juegos NO viven en una tabla: se declaran aquí. Añadir un juego es
| escribir su engine y sumarlo a ENGINES — aparece automáticamente en el
| catálogo, en la ficha del competidor y en el simulador, sin migración,
| sin seed que sincronizar y sin tocar ninguna vista.
|
| Lo que sí se guarda en base de datos es qué juegos usa cada Universo
| (universe_games) y las estadísticas de cada competidor.
|
*/

class GameRegistry
{
    /**
     * @var array<int, class-string<GameEngine>>
     */
    private const ENGINES = [
        HighestNumberGameEngine::class,
    ];

    public const DEFAULT_KEY = HighestNumberGameEngine::KEY;

    /** @var array<string, GameEngine>|null */
    private ?array $resolved = null;

    /**
     * @return array<string, GameEngine>
     */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $engines = [];

        foreach (self::ENGINES as $class) {

            /** @var GameEngine $engine */
            $engine = app($class);

            $engines[$engine->definition()['key']] = $engine;
        }

        return $this->resolved = $engines;
    }

    public function has(?string $key): bool
    {
        return $key !== null
            && array_key_exists(
                strtoupper($key),
                $this->all()
            );
    }

    /**
     * Devuelve el engine pedido, o el juego por defecto si la clave ya no
     * existe. Nunca revienta: una competición antigua que apunta a un
     * juego retirado debe poder seguir abriéndose.
     */
    public function engine(?string $key): GameEngine
    {
        $engines = $this->all();

        $normalized =
            strtoupper(
                (string) $key
            );

        return $engines[$normalized]
            ?? $engines[self::DEFAULT_KEY]
            ?? reset($engines);
    }

    public function definition(?string $key): array
    {
        return $this->engine($key)->definition();
    }

    /**
     * @return Collection<int, array>
     */
    public function definitions(): Collection
    {
        return collect($this->all())
            ->map(
                fn(GameEngine $engine) =>
                $engine->definition()
            )
            ->values();
    }

    /**
     * Claves válidas, para reglas de validación.
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys(
            $this->all()
        );
    }

    /**
     * Comprueba si un juego admite ese número de participantes.
     */
    public function supportsParticipants(
        ?string $key,
        int $count
    ): bool {

        $definition =
            $this->definition($key);

        $minimum =
            (int) ($definition['minimum_participants'] ?? 2);

        $maximum =
            $definition['maximum_participants'] ?? null;

        if ($count < $minimum) {
            return false;
        }

        return $maximum === null
            || $count <= (int) $maximum;
    }
}

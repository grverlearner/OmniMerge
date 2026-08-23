<?php

namespace App\Services\Games;

use App\Models\Universe;
use App\Support\Games\GameConfiguration;
use App\Models\UniverseGame;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| UniverseGameService
|--------------------------------------------------------------------------
|
| Qué juegos usa un Universo.
|
| El catálogo real vive en GameRegistry (código). Este servicio solo
| gestiona la decisión del usuario sobre cada juego, y se encarga de que
| un Universo creado antes de que existiera un juego lo reciba igualmente
| la próxima vez que abra la sección.
|
*/

class UniverseGameService
{
    public function __construct(
        private readonly GameRegistry $registry
    ) {}

    /**
     * Sincroniza el Universo con el catálogo de código: da de alta los
     * juegos que aún no tenga y garantiza que exista exactamente un juego
     * por defecto.
     *
     * Es idempotente: se puede llamar en cada visita sin efectos raros.
     */
    public function sync(Universe $universe): Collection
    {
        $existing =
            $universe->games()
            ->get()
            ->keyBy('game_key');

        foreach ($this->registry->keys() as $key) {

            if ($existing->has($key)) {
                continue;
            }

            $existing->put(
                $key,
                $universe->games()->create([
                    'game_key' => $key,
                    'is_enabled' => true,
                    'is_default' => false,
                    'configuration' => [],
                ])
            );
        }

        /*
         * Juegos que ya no existen en código quedan ocultos, pero no se
         * borran: una competición antigua todavía los referencia.
         */
        $available =
            $existing->filter(
                fn(UniverseGame $game) =>
                $this->registry->has($game->game_key)
            );

        $this->guaranteeDefault($universe, $available);

        return $available
            ->sortByDesc('is_default')
            ->values();
    }

    public function enabled(Universe $universe): Collection
    {
        return $this->sync($universe)
            ->filter(
                fn(UniverseGame $game) =>
                $game->is_enabled
            )
            ->values();
    }

    /**
     * Juego por defecto del Universo. Es el que se propone al crear un
     * torneo y el que se usa si un torneo antiguo no eligió ninguno.
     */
    public function defaultKey(Universe $universe): string
    {
        $default =
            $this->sync($universe)
            ->firstWhere('is_default', true);

        return $default?->game_key
            ?? GameRegistry::DEFAULT_KEY;
    }

    public function setDefault(
        Universe $universe,
        string $gameKey
    ): void {

        if (! $this->registry->has($gameKey)) {
            return;
        }

        $universe->games()
            ->update(['is_default' => false]);

        $universe->games()
            ->updateOrCreate(
                ['game_key' => strtoupper($gameKey)],
                [
                    'is_default' => true,
                    'is_enabled' => true,
                ]
            );
    }

    public function toggle(
        Universe $universe,
        string $gameKey,
        bool $enabled
    ): void {

        if (! $this->registry->has($gameKey)) {
            return;
        }

        $universe->games()
            ->updateOrCreate(
                ['game_key' => strtoupper($gameKey)],
                ['is_enabled' => $enabled]
            );

        /*
         * Deshabilitar el juego por defecto dejaría al Universo sin
         * ninguno: se traslada la marca a otro habilitado.
         */
        if (! $enabled) {
            $this->guaranteeDefault(
                $universe,
                $universe->games()->get()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Configuracion del juego dentro del Universo
    |--------------------------------------------------------------------------
    */

    public function configuration(
        Universe $universe,
        string $gameKey
    ): GameConfiguration {

        $record = $universe->games()
            ->where('game_key', strtoupper($gameKey))
            ->first();

        return new GameConfiguration(
            $this->registry->definition($gameKey),
            $record?->configuration ?? []
        );
    }

    public function saveConfiguration(
        Universe $universe,
        string $gameKey,
        array $stats,
        array $options = []
    ): GameConfiguration {

        if (! $this->registry->has($gameKey)) {
            return $this->configuration($universe, $gameKey);
        }

        $key = strtoupper($gameKey);

        $record = $universe->games()
            ->firstOrCreate(
                ['game_key' => $key],
                ['is_enabled' => true, 'is_default' => false]
            );

        $record->configuration = GameConfiguration::merge(
            $record->configuration ?? [],
            $stats,
            $options
        );

        $record->save();

        return $this->configuration($universe, $key);
    }

    private function guaranteeDefault(
        Universe $universe,
        Collection $games
    ): void {

        $current =
            $games->first(
                fn(UniverseGame $game) =>
                $game->is_default && $game->is_enabled
            );

        if ($current) {
            return;
        }

        $candidate =
            $games->first(
                fn(UniverseGame $game) =>
                $game->is_enabled
                    && $this->registry->has($game->game_key)
            )
            ?? $games->first();

        if (! $candidate) {
            return;
        }

        $universe->games()
            ->whereKeyNot($candidate->id)
            ->update(['is_default' => false]);

        $candidate->forceFill([
            'is_default' => true,
            'is_enabled' => true,
        ])->save();
    }
}

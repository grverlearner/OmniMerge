<?php

namespace App\Services\Games\Contracts;

/*
|--------------------------------------------------------------------------
| GameEngine
|--------------------------------------------------------------------------
|
| Contrato de un juego de OmniMerge.
|
| Un Game Engine responde a una sola pregunta: "¿cómo se resuelve un
| enfrentamiento?". No sabe nada de torneos, fases, series ni Universos —
| esas decisiones ya las toman el Tournament Graph y MatchSeriesRuntime.
|
| Por qué roll() y adjudicate() están separados
| ---------------------------------------------
| Porque es lo que hace posible el simulador interactivo: el usuario puede
| generar el resultado de un participante, verlo, y después generar el del
| otro. Si la resolución fuera un solo método, la interfaz tendría que
| conocer las tripas del juego para poder pausar a la mitad.
|
| Un juego futuro que solo pueda resolverse de golpe (por ejemplo un
| combate por turnos entre todos a la vez) deja roll() devolviendo un valor
| neutro y hace el trabajo real en adjudicate().
|
| IMPORTANTE: un engine es una FUNCIÓN PURA. No consulta base de datos, no
| escribe, no mira la sesión. Recibe los datos ya congelados en el estado
| del torneo y devuelve un resultado. Esto es lo que permite que el motor
| de torneos siga siendo reproducible y persistible (Fase 6).
|
| Ver docs/md/29-Fase-11-Motor-De-Juegos.md
|
*/

interface GameEngine
{
    /**
     * Todo lo que el catálogo y el simulador necesitan saber del juego:
     * identidad, límites de participantes, reglas, esquema de estadísticas
     * y qué controles ofrecer en pantalla.
     *
     * La interfaz se dibuja desde aquí. Ningún juego debería necesitar que
     * se toque una vista para aparecer correctamente.
     */
    public function definition(): array;

    /**
     * Estadísticas iniciales de un competidor que todavía no las tiene.
     */
    public function defaultStats(array $context = []): array;

    /**
     * Sanea unas estadísticas cualesquiera hasta dejarlas jugables.
     * Nunca lanza: un competidor con datos raros debe poder competir.
     */
    public function normalizeStats(array $stats): array;

    /**
     * Genera el resultado de UN participante dentro de un enfrentamiento.
     *
     * @param  array $participant  ['id', 'name', 'stats' => [...]]
     * @return array{value: float, display: string, detail: array, stats_used: array}
     */
    public function roll(array $participant, array $config = []): array;

    /**
     * Decide el resultado del enfrentamiento a partir de las tiradas.
     *
     * @param  array $rolls  lista de ['id', 'name', 'value', 'display']
     * @return array{ranking: array, winner_id: ?string, tied_ids: array, is_draw: bool, summary: string}
     */
    public function adjudicate(array $rolls, array $config = []): array;
}

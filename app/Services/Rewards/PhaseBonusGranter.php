<?php

namespace App\Services\Rewards;

/*
|--------------------------------------------------------------------------
| PhaseBonusGranter
|--------------------------------------------------------------------------
|
| Los bonos que se ganan jugando.
|
| Qué resuelve
| ------------
| Un torneo por fases es más interesante cuando lo que haces en una fase
| cambia cómo llegas a la siguiente. "Los 3 primeros de la liga entran a
| los grupos con +1 de techo" convierte una jornada intrascendente en una
| pelea por el podio.
|
| Hasta ahora eso no se podía expresar: un bonus temporal se decidía de
| antemano (a un competidor concreto, o a todos) y las recompensas
| permanentes solo se aplican cuando el torneo entero acaba. Faltaba el
| medio: algo que se resuelve al cerrar una fase y ya está activo en la
| siguiente.
|
| Cómo funciona
| -------------
| No hay maquinaria nueva. Un bonus ganado se convierte, en el momento en
| que se concede, en exactamente el mismo tipo de entrada que ya entiende
| EncounterRuntime: target ENTITY, con el id del competidor. A partir de
| ahí es un bonus normal y corriente y se aplica solo.
|
| La única diferencia es CUÁNDO aparece esa entrada en el estado: cuando
| la fase termina, no cuando la competición empieza.
|
| Por qué se guarda en el estado y no en una tabla
| -----------------------------------------------
| Porque es temporal por definición: vive lo que vive la competición. Y
| porque una vez concedido debe ser inmutable — que alguien edite la
| regla mañana no puede cambiar lo que ya se ganó. Congelarlo en el
| estado, junto al resto de la configuración congelada, da las dos cosas
| gratis.
|
| Idempotencia
| ------------
| Se llama en cada acción del runtime, así que tiene que poder llamarse
| mil veces sin conceder nada dos veces. Cada entrada concedida lleva un
| granted_key = regla + fase + competidor, y esa clave es el candado.
|
*/

class PhaseBonusGranter
{
    /**
     * Concede los bonos de las fases que hayan terminado.
     *
     * Recibe y devuelve el estado: es una función pura sobre el JSON,
     * igual que el motor. No toca la base de datos.
     */
    public function grant(array $state): array
    {
        $rules =
            collect($state['modifiers'] ?? [])
            ->filter(
                fn($modifier) =>
                ($modifier['target'] ?? null) === 'PHASE_PODIUM'
            );

        if ($rules->isEmpty()) {
            return $state;
        }

        /* Lo ya concedido, para no repetirlo */
        $granted =
            collect($state['modifiers'] ?? [])
            ->pluck('granted_key')
            ->filter()
            ->flip();

        foreach (($state['nodes'] ?? []) as $node) {

            if (! $this->isFinished($node)) {
                continue;
            }

            $phaseName = (string) ($node['name'] ?? '');

            foreach ($rules as $rule) {

                if (! $this->ruleTargets($rule, $node)) {
                    continue;
                }

                $podium =
                    $this->slice(
                        $node,
                        $rule
                    );

                foreach ($podium as [$participantKey, $position]) {

                    $entityId =
                        $state['participants'][$participantKey]['universe_entity_id']
                        ?? null;

                    if ($entityId === null) {
                        continue;
                    }

                    $key =
                        ($rule['rule_id'] ?? $this->fingerprint($rule))
                        . ':' . $node['id']
                        . ':' . $participantKey;

                    if ($granted->has($key)) {
                        continue;
                    }

                    $state['modifiers'][] =
                        $this->award(
                            $rule,
                            $key,
                            (int) $entityId,
                            $position,
                            $phaseName
                        );

                    $granted->put($key, true);
                }
            }
        }

        return $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Piezas
    |--------------------------------------------------------------------------
    */

    /**
     * Una fase da su podio cuando ha terminado de jugarse. Antes no: un
     * bonus concedido a mitad de liga premiaría a quien va primero en la
     * jornada 3, que no es lo que nadie quiso decir.
     */
    private function isFinished(array $node): bool
    {
        $runtime = $node['runtime'] ?? null;

        if (! is_array($runtime)) {
            return false;
        }

        return in_array(
            $runtime['status'] ?? null,
            ['COMPLETED', 'ROUTED', 'CLOSED'],
            true
        );
    }

    /**
     * Si la regla habla de esta fase. Sin fase indicada, vale para
     * cualquiera que termine: "los 3 primeros de cada fase".
     */
    private function ruleTargets(array $rule, array $node): bool
    {
        $target = trim((string) ($rule['award_phase'] ?? ''));

        if ($target === '') {
            return true;
        }

        $candidates = [
            (string) ($node['name'] ?? ''),
            (string) ($node['code'] ?? ''),
            (string) ($node['id'] ?? ''),
        ];

        foreach ($candidates as $candidate) {

            if (
                mb_strtolower(trim($candidate))
                === mb_strtolower($target)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * El trozo de la clasificación que se lleva el bonus.
     *
     * La tabla ya la calcula el motor y ya viene ordenada: aquí solo se
     * corta. En fase de grupos se corta DENTRO DE CADA GRUPO, porque un
     * grupo es la unidad donde se compite de verdad — "el 2º" son cuatro
     * competidores, uno por grupo.
     *
     * @return list<array{0: string, 1: int}>  clave y puesto
     */
    private function slice(array $node, array $rule): array
    {
        $runtime = $node['runtime'] ?? [];

        if (! empty($runtime['groups'])) {

            $found = [];

            foreach ($runtime['groups'] as $group) {

                foreach (
                    $this->cut($group['standings'] ?? [], $rule)
                    as
                    $entry
                ) {
                    $found[] = $entry;
                }
            }

            return $found;
        }

        return $this->cut($runtime['standings'] ?? [], $rule);
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function cut(array $standings, array $rule): array
    {
        $total = count($standings);

        if ($total === 0) {
            return [];
        }

        $from = max(1, (int) ($rule['selector_from'] ?? 1));
        $to = (int) ($rule['selector_to'] ?? 0);

        /*
         * Todo se expresa como un rango de puestos [primero, último],
         * contando desde 1. Los cuatro selectores solo se diferencian en
         * cómo llegan a ese par.
         */
        [$first, $last] = match ($rule['selector_type'] ?? 'TOP_N') {

            'RANK_POSITION' =>
            [$from, $from],

            'RANK_RANGE' =>
            [$from, max($from, $to)],

            'BOTTOM_N' =>
            [max(1, $total - $from + 1), $total],

            default =>
            [1, $from],
        };

        $found = [];

        foreach ($standings as $index => $row) {

            $position = $index + 1;

            if ($position < $first || $position > $last) {
                continue;
            }

            $key = (string) ($row['participant_id'] ?? '');

            if ($key === '') {
                continue;
            }

            $found[] = [$key, $position];
        }

        return $found;
    }

    /**
     * La entrada concedida. Es un bonus normal: target ENTITY con el id
     * ya resuelto. Lo único añadido es la trazabilidad — de qué fase
     * salió, en qué puesto — para poder contarlo en pantalla.
     */
    private function award(
        array $rule,
        string $key,
        int $entityId,
        int $position,
        string $phaseName
    ): array {

        $ordinal = match ($position) {
            1 => '1º',
            2 => '2º',
            3 => '3º',
            default => $position . 'º',
        };

        return [

            'scope' =>
            $rule['scope'] ?? 'TOURNAMENT',

            'scope_value' =>
            $rule['scope_value'] ?? null,

            'target' =>
            'ENTITY',

            'universe_entity_id' =>
            $entityId,

            'game_key' =>
            $rule['game_key'] ?? null,

            'stat_key' =>
            $rule['stat_key'] ?? null,

            'operation' =>
            $rule['operation'] ?? 'ADD',

            'amount' =>
            (float) ($rule['amount'] ?? 0),

            'label' =>
            $rule['label']
                ?: $ordinal . ' · ' . $phaseName,

            /* Marcas de origen: el candado y el relato */
            'granted_key' => $key,
            'granted_phase' => $phaseName,
            'granted_position' => $position,
        ];
    }

    /**
     * Identidad de una regla congelada. El estado no guarda el id de la
     * fila, así que se deriva de lo que la define; dos reglas idénticas
     * serían la misma regla de todas formas.
     */
    private function fingerprint(array $rule): string
    {
        return substr(
            md5(
                json_encode([
                    $rule['award_phase'] ?? null,
                    $rule['selector_type'] ?? null,
                    $rule['selector_from'] ?? null,
                    $rule['selector_to'] ?? null,
                    $rule['game_key'] ?? null,
                    $rule['stat_key'] ?? null,
                    $rule['operation'] ?? null,
                    $rule['amount'] ?? null,
                ])
            ),
            0,
            10
        );
    }
}

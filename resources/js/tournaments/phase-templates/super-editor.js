/*
|--------------------------------------------------------------------------
| phaseSuperEditor
|--------------------------------------------------------------------------
|
| El cerebro de la Super Edicion.
|
| Reparte el trabajo en dos mitades muy distintas, y esa division es la
| decision importante de todo el editor:
|
|   SERVIDOR  la matematica. Cuantas jornadas, quien se empareja con quien,
|             cuantos descansos, que puestos reclama cada puerta, si la
|             configuracion es valida. Sale del calculador de siempre, el
|             mismo que usan el simulador y Fase de grupos.
|
|   CLIENTE   quien ocupa cada semilla, y que pasa si se juega. El
|             calendario empareja SEMILLAS -la 1 contra la 8-, nunca
|             personas, asi que barajar, ordenar a mano o sembrar por
|             puerta es solo permutar una lista. Y simular un resultado no
|             toca el calendario en absoluto.
|
| Solo se pide al servidor cuando cambia algo que altera la matematica:
| cantidad de participantes y numero de vueltas.
|
| La simulacion es de mentira y no se guarda NUNCA. Existe para poder ver
| como se llena la tabla, como se ordena con los criterios reales y a quien
| se lleva cada puerta de salida, sin montar un torneo para averiguarlo.
|
*/

export default function phaseSuperEditor(config) {

    return {

        payload: config.payload,

        previewUrl: config.previewUrl,

        /* Controles que SI obligan a recalcular */
        participants: config.payload.contract.resolved,
        cycles: config.payload.settings.cycles,

        /* Controles que solo cambian quien ocupa que semilla */
        orderMode: config.payload.settings.initial_order_mode,
        rankingSource: config.payload.settings.ranking_source,

        /* Hasta que jornada se juega */
        roundLimit: config.payload.settings.round_limit,

        points: {
            win: config.payload.settings.win_points,
            draw: config.payload.settings.draw_points,
            loss: config.payload.settings.loss_points,
        },

        allowDraws: config.payload.settings.allow_draws,

        pinParticipants: config.payload.contract.is_pinned,

        /*
         * order[posicion - 1] = indice del reparto.
         *
         * Toda la reactividad del editor pasa por este array.
         */
        order: [],

        manualOrder: null,

        /*
         * Resultados simulados, por "jornada:indice".
         *
         * Se guardan por SEMILLA y no por participante a proposito: asi
         * reordenar la parrilla no arrastra resultados de una posicion a
         * otra, y cambiar el orden borra lo simulado, que es lo honesto
         * -con otra parrilla, esos partidos ni siquiera existen-.
         */
        results: {},

        loading: false,
        dirty: false,

        focusedRound: null,

        refreshTimer: null,


        init() {
            this.rebuildOrder();

            this.$watch('participants', () => this.scheduleRefresh());
            this.$watch('cycles', () => this.scheduleRefresh());

            this.$watch('orderMode', () => {
                this.dirty = true;
                this.clearResults();
                this.rebuildOrder();
            });

            this.$watch('rankingSource', () => {
                this.dirty = true;

                if (this.orderMode === 'RANKING') {
                    this.clearResults();
                    this.rebuildOrder();
                }
            });

            this.$watch('roundLimit', () => {
                this.dirty = true;

                /*
                 * Recortar la liga invalida lo que ya se habia simulado por
                 * encima del corte: esas jornadas dejan de jugarse.
                 */
                this.dropResultsBeyondLimit();
            });
        },


        /*
        |----------------------------------------------------------------
        | Servidor
        |----------------------------------------------------------------
        */

        scheduleRefresh() {
            this.dirty = true;

            clearTimeout(this.refreshTimer);

            this.refreshTimer = setTimeout(() => this.refresh(), 280);
        },

        refresh() {
            const url = new URL(this.previewUrl, window.location.origin);

            url.searchParams.set('participants', this.participants);
            url.searchParams.set('cycles', this.cycles);

            this.loading = true;

            return fetch(url, { headers: { Accept: 'application/json' } })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('respuesta ' + response.status);
                    }

                    return response.json();
                })
                .then((payload) => {
                    this.payload = payload;

                    if (this.manualOrder && this.manualOrder.length !== payload.cast.length) {
                        this.manualOrder = null;
                    }

                    /*
                     * Otro calendario, otros partidos: lo simulado ya no
                     * corresponde a nada.
                     */
                    this.clearResults();

                    this.roundLimit = payload.settings.round_limit;

                    this.rebuildOrder();
                })
                .catch(() => {
                    this.payload.diagnostics = {
                        status: 'INVALID',
                        errors: ['No fue posible calcular la estructura. Revisa la conexión.'],
                        warnings: [],
                    };
                })
                .finally(() => {
                    this.loading = false;
                });
        },


        /*
        |----------------------------------------------------------------
        | Quien ocupa cada semilla
        |----------------------------------------------------------------
        */

        get castSize() {
            return this.payload.cast.length;
        },

        rebuildOrder() {
            const size = this.castSize;

            const identity = Array.from({ length: size }, (_, i) => i);

            if (this.orderMode === 'RANDOM') {
                this.order = this.shuffled(identity);

                return;
            }

            if (this.orderMode === 'RANKING') {
                this.order = this.demoRanking(identity);

                return;
            }

            if (this.orderMode === 'MANUAL') {
                this.order = this.manualOrder ? [...this.manualOrder] : identity;
                this.manualOrder = [...this.order];

                return;
            }

            if (this.orderMode === 'BY_GATE') {
                this.order = this.seatByGates(identity);

                return;
            }

            this.order = identity;
        },

        /*
         * Sentar a la gente segun los puestos que reclama cada puerta.
         *
         * Es el mismo reparto que hace RoundRobinSeedRuleResolver en el
         * servidor: el mapa de puestos llega ya resuelto en el payload, asi
         * que aqui solo se reparte la cola de llegada.
         */
        seatByGates(identity) {
            const assignments = this.payload.seed_map?.assignments ?? [];

            if (!assignments.length) {
                return identity;
            }

            const grid = new Array(identity.length).fill(null);
            const queue = [...identity];

            for (const seeds of assignments) {
                for (const seed of seeds) {
                    if (!queue.length) break;

                    grid[seed - 1] = queue.shift();
                }
            }

            for (let i = 0; i < grid.length && queue.length; i++) {
                if (grid[i] === null) {
                    grid[i] = queue.shift();
                }
            }

            return grid.map((v, i) => (v === null ? i : v));
        },

        shuffled(list) {
            const out = [...list];

            for (let i = out.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));

                [out[i], out[j]] = [out[j], out[i]];
            }

            return out;
        },

        demoRanking(identity) {
            const salt = this.rankingSource === 'UNIVERSAL' ? 7 : 3;

            return identity
                .slice()
                .sort((a, b) => ((a * salt) % identity.length) - ((b * salt) % identity.length));
        },

        reshuffle() {
            this.clearResults();
            this.order = this.shuffled(this.order);
        },

        move(position, delta) {
            const target = position + delta;

            if (target < 0 || target >= this.order.length) {
                return;
            }

            [this.order[position], this.order[target]] =
                [this.order[target], this.order[position]];

            this.manualOrder = [...this.order];

            this.clearResults();

            this.dirty = true;
        },

        atSeed(seed) {
            return this.payload.cast[this.order[seed - 1]] ?? null;
        },


        /*
        |----------------------------------------------------------------
        | Puertas
        |----------------------------------------------------------------
        |
        | En una liga todos se enfrentan a todos, asi que una puerta no
        | decide quien pasa: decide POR DONDE ENTRA cada uno, es decir que
        | numero de la parrilla ocupa. Y eso si cambia el calendario, porque
        | el 1 abre contra el ultimo y el 2 contra el penultimo.
        |
        */

        get activeGates() {
            return this.payload.gates.filter((gate) => gate.status === 'ACTIVE');
        },

        /* Que puerta reclama este puesto de la parrilla */
        gateOfSeed(seed) {
            const map = this.payload.seed_map?.seed_map ?? {};

            const index = map[seed];

            if (index === undefined || index === null) {
                return null;
            }

            return this.payload.gates[index] ?? null;
        },


        /*
        |----------------------------------------------------------------
        | Jornadas
        |----------------------------------------------------------------
        */

        get rounds() {
            return this.payload.rounds ?? [];
        },

        /* Solo las que se juegan de verdad, segun el recorte */
        get playedRounds() {
            const limit = this.roundLimit ?? this.rounds.length;

            return this.rounds.filter((round) => round.number <= limit);
        },

        get maxRounds() {
            return this.structure.total_rounds ?? this.rounds.length;
        },

        get isTrimmed() {
            return (this.roundLimit ?? this.maxRounds) < this.maxRounds;
        },

        get visibleRounds() {
            const rounds = this.playedRounds;

            return this.focusedRound === null
                ? rounds
                : rounds.filter((round) => round.number === this.focusedRound);
        },

        /* Jornadas que existen pero que el recorte deja fuera */
        get droppedRounds() {
            const limit = this.roundLimit ?? this.rounds.length;

            return this.rounds.filter((round) => round.number > limit);
        },

        matchNumber(roundNumber, pairIndex) {
            let count = 0;

            for (const round of this.rounds) {
                if (round.number === roundNumber) break;

                count += round.pairings.length;
            }

            return count + pairIndex + 1;
        },


        /*
        |----------------------------------------------------------------
        | Simulacion
        |----------------------------------------------------------------
        |
        | Marcadores inventados, para poder mirar como se llena la tabla y a
        | quien se lleva cada salida. No se guardan, no se envian y no
        | tienen nada que ver con un torneo real: cambiar el orden o la
        | cantidad de participantes los borra, porque con otra parrilla esos
        | partidos ni existen.
        |
        */

        key(roundNumber, pairIndex) {
            return roundNumber + ':' + pairIndex;
        },

        resultOf(roundNumber, pairIndex) {
            return this.results[this.key(roundNumber, pairIndex)] ?? null;
        },

        get hasResults() {
            return Object.keys(this.results).length > 0;
        },

        get playedCount() {
            return Object.keys(this.results).length;
        },

        get totalPlayable() {
            return this.playedRounds.reduce((sum, r) => sum + r.pairings.length, 0);
        },

        /*
         * Un marcador cualquiera. Si la fase no admite empates se repite
         * hasta que haya ganador, igual que hace el motor.
         */
        rollScore() {
            let a = Math.floor(Math.random() * 5);
            let b = Math.floor(Math.random() * 5);

            if (!this.allowDraws && a === b) {
                Math.random() < 0.5 ? a++ : b++;
            }

            return { a, b };
        },

        simulateMatch(roundNumber, pairIndex) {
            this.results[this.key(roundNumber, pairIndex)] = this.rollScore();
        },

        simulateRound(roundNumber) {
            const round = this.rounds.find((r) => r.number === roundNumber);

            if (!round) return;

            round.pairings.forEach((_, index) => {
                this.results[this.key(roundNumber, index)] = this.rollScore();
            });
        },

        simulateAll() {
            this.playedRounds.forEach((round) => {
                round.pairings.forEach((_, index) => {
                    this.results[this.key(round.number, index)] = this.rollScore();
                });
            });
        },

        clearResults() {
            this.results = {};
        },

        dropResultsBeyondLimit() {
            const limit = this.roundLimit ?? this.rounds.length;

            Object.keys(this.results).forEach((key) => {
                if (parseInt(key.split(':')[0], 10) > limit) {
                    delete this.results[key];
                }
            });
        },


        /*
        |----------------------------------------------------------------
        | Clasificacion
        |----------------------------------------------------------------
        |
        | Se ordena con los MISMOS criterios y en el MISMO orden que el
        | motor, que llegan en el payload. Llevar aqui una lista propia
        | garantizaria que las dos se separen a la primera correccion.
        |
        */

        get standings() {
            const rows = [];

            for (let seed = 1; seed <= this.castSize; seed++) {
                rows.push({
                    seed,
                    PLAYED: 0, WINS: 0, DRAWS: 0, LOSSES: 0,
                    SCORE_FOR: 0, SCORE_AGAINST: 0, SCORE_DIFFERENCE: 0,
                    POINTS: 0,
                });
            }

            const at = (seed) => rows[seed - 1];

            this.playedRounds.forEach((round) => {
                round.pairings.forEach((pair, index) => {

                    const result = this.resultOf(round.number, index);

                    if (!result) return;

                    const a = at(pair.seed_a);
                    const b = at(pair.seed_b);

                    if (!a || !b) return;

                    a.PLAYED++; b.PLAYED++;

                    a.SCORE_FOR += result.a; a.SCORE_AGAINST += result.b;
                    b.SCORE_FOR += result.b; b.SCORE_AGAINST += result.a;

                    if (result.a > result.b) {
                        a.WINS++; b.LOSSES++;
                        a.POINTS += this.points.win; b.POINTS += this.points.loss;
                    } else if (result.a < result.b) {
                        b.WINS++; a.LOSSES++;
                        b.POINTS += this.points.win; a.POINTS += this.points.loss;
                    } else {
                        a.DRAWS++; b.DRAWS++;
                        a.POINTS += this.points.draw; b.POINTS += this.points.draw;
                    }
                });
            });

            rows.forEach((row) => {
                row.SCORE_DIFFERENCE = row.SCORE_FOR - row.SCORE_AGAINST;
                row.POINTS = Math.round(row.POINTS * 100) / 100;
            });

            return rows;
        },

        /* La tabla, ya ordenada. Sin resultados, el orden es el de parrilla. */
        get classified() {
            const rows = this.standings;

            if (!this.hasResults) {
                return rows;
            }

            const keys = this.payload.catalog.ranking_keys ?? [];

            return [...rows].sort((left, right) => {
                for (const { key, direction } of keys) {

                    const a = key === 'SEED' ? left.seed : (left[key] ?? 0);
                    const b = key === 'SEED' ? right.seed : (right[key] ?? 0);

                    if (a !== b) {
                        return direction === 'ASC' ? a - b : b - a;
                    }
                }

                return left.seed - right.seed;
            });
        },


        /*
        |----------------------------------------------------------------
        | Salidas
        |----------------------------------------------------------------
        */

        exitOfPosition(position) {
            const total = this.castSize;

            for (const exit of this.payload.exits) {
                if (exit.status !== 'ACTIVE' || !exit.positions) {
                    continue;
                }

                const { from, to, anchor } = exit.positions;

                const start = anchor === 'BOTTOM' ? total - to + 1 : from;
                const end = anchor === 'BOTTOM' ? total : to;

                if (position >= start && position <= end) {
                    return exit;
                }
            }

            return this.payload.exits.find(
                (exit) => exit.status === 'ACTIVE'
                    && ['REMAINING', 'ELIMINATED', 'ALL'].includes(exit.selector_type)
            ) ?? null;
        },


        /*
        |----------------------------------------------------------------
        | Estado
        |----------------------------------------------------------------
        */

        get diagnostics() {
            return this.payload.diagnostics;
        },

        get isValid() {
            return this.diagnostics.status !== 'INVALID';
        },

        get contract() {
            return this.payload.contract;
        },

        get structure() {
            return this.payload.structure;
        },

        get showsRanking() {
            return this.orderMode === 'RANKING';
        },

        get showsManual() {
            return this.orderMode === 'MANUAL';
        },

        get showsGateOrder() {
            return this.orderMode === 'BY_GATE';
        },

        get contractWarning() {
            const c = this.contract;
            const n = parseInt(this.participants) || 0;

            if (c.exact !== null && n !== c.exact) {
                return 'Esta fase está fijada en ' + c.exact + ' participantes exactos.';
            }

            if (n < c.min) {
                return 'La fase necesita al menos ' + c.min + '.';
            }

            if (c.max !== null && n > c.max) {
                return 'La fase admite como máximo ' + c.max + '.';
            }

            if (c.multiple && n % c.multiple !== 0) {
                return 'La cantidad debe ser múltiplo de ' + c.multiple + '.';
            }

            return null;
        },

        /*
         * Con el calendario entero, sembrar por puerta solo cambia el orden
         * de los partidos: todo el mundo acaba jugando contra todo el mundo.
         * Recortar jornadas es lo que hace que el puesto inicial decida
         * contra quien te llegas a enfrentar.
         */
        get gateOrderIsCosmetic() {
            return this.orderMode === 'BY_GATE' && !this.isTrimmed;
        },
    };
}

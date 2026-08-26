/*
|--------------------------------------------------------------------------
| Super Edicion — Round Robin
|--------------------------------------------------------------------------
|
| Lo propio de una liga: una sola parrilla, una sola lista de jornadas y una
| sola tabla.
|
*/

export function roundRobinEditor(config) {

    return {

        cycles: config.payload.settings.cycles,

        orderMode: config.payload.settings.initial_order_mode,
        rankingSource: config.payload.settings.ranking_source,

        roundLimit: config.payload.settings.round_limit,

        points: {
            win: config.payload.settings.win_points,
            draw: config.payload.settings.draw_points,
            loss: config.payload.settings.loss_points,
        },

        allowDraws: config.payload.settings.allow_draws,

        manualOrder: null,

        focusedRound: null,


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
                this.dropResultsBeyondLimit();
            });
        },

        previewParams() {
            return { cycles: this.cycles };
        },

        afterRefresh(payload) {
            if (this.manualOrder && this.manualOrder.length !== payload.cast.length) {
                this.manualOrder = null;
            }

            this.roundLimit = payload.settings.round_limit ?? this.roundLimit;
        },


        /*
        |----------------------------------------------------------------
        | Quien ocupa cada semilla
        |----------------------------------------------------------------
        */

        rebuildOrder() {
            const identity = this.identity();

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


        /*
        |----------------------------------------------------------------
        | Puertas
        |----------------------------------------------------------------
        */

        get activeGates() {
            return this.payload.gates.filter((gate) => gate.status === 'ACTIVE');
        },

        gateOfSeed(seed) {
            const map = this.payload.seed_map?.seed_map ?? {};

            const index = map[seed];

            if (index === undefined || index === null) {
                return null;
            }

            return this.payload.gates[index] ?? null;
        },

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
        | Jornadas
        |----------------------------------------------------------------
        */

        get rounds() {
            return this.payload.rounds ?? [];
        },

        get playedRounds() {
            const limit = this.roundLimit ?? this.rounds.length;

            return this.rounds.filter((round) => round.number <= limit);
        },

        get maxRounds() {
            return Math.max(1, this.structure.total_rounds ?? this.rounds.length);
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
        */

        key(roundNumber, pairIndex) {
            return roundNumber + ':' + pairIndex;
        },

        resultOf(roundNumber, pairIndex) {
            return this.resultAt(this.key(roundNumber, pairIndex));
        },

        get totalPlayable() {
            return this.playedRounds.reduce((sum, r) => sum + r.pairings.length, 0);
        },

        simulateMatch(roundNumber, pairIndex) {
            this.simulateKey(this.key(roundNumber, pairIndex));
        },

        simulateRound(roundNumber) {
            const round = this.rounds.find((r) => r.number === roundNumber);

            if (!round) return;

            round.pairings.forEach((_, index) => {
                this.simulateKey(this.key(roundNumber, index));
            });
        },

        simulateAll() {
            this.playedRounds.forEach((round) => {
                round.pairings.forEach((_, index) => {
                    this.simulateKey(this.key(round.number, index));
                });
            });
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
        */

        get standings() {
            const matches = [];

            this.playedRounds.forEach((round) => {
                round.pairings.forEach((pair, index) => {
                    matches.push({
                        seedA: pair.seed_a,
                        seedB: pair.seed_b,
                        result: this.resultOf(round.number, index),
                    });
                });
            });

            return this.tally(
                Array.from({ length: this.castSize }, (_, i) => i + 1),
                matches
            );
        },

        get classified() {
            const rows = this.standings;

            return this.hasResults ? this.rank(rows) : rows;
        },


        /*
        |----------------------------------------------------------------
        | Estado
        |----------------------------------------------------------------
        */

        get showsRanking() {
            return this.orderMode === 'RANKING';
        },

        get showsManual() {
            return this.orderMode === 'MANUAL';
        },

        get showsGateOrder() {
            return this.orderMode === 'BY_GATE';
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

/*
|--------------------------------------------------------------------------
| Super Edicion — Fase de grupos
|--------------------------------------------------------------------------
|
| Una fase de grupos es una liga pequena repetida N veces y jugada en
| paralelo, asi que casi todo se hereda de la base. Lo propio de aqui:
|
|   - hay N tablas, no una
|   - las jornadas de todos los grupos corren a la vez
|   - una puerta de entrada reparte GRUPOS, no puestos de una parrilla
|   - la salida puede mirar cada grupo por separado o compararlos entre si
|
| El reparto en grupos lo decide el SERVIDOR (GroupStageAllocator) porque
| depende de reglas que ya existen y que el motor ejecuta. Aqui solo se
| pinta quien cae donde y que pasaria si se juega.
|
*/

export function groupStageEditor(config) {

    return {

        /* Construccion */
        groupCountMode: config.payload.settings.group_count_mode,
        groupCount: config.payload.settings.group_count,
        targetGroupSize: config.payload.settings.target_group_size,
        minGroupSize: config.payload.settings.min_group_size,
        maxGroupSize: config.payload.settings.max_group_size,
        remainderPolicy: config.payload.settings.remainder_policy,

        /* Reparto */
        distributionMode: config.payload.settings.distribution_mode,

        /* Interior de cada grupo */
        cycles: config.payload.settings.internal_cycles,
        allowDraws: config.payload.settings.internal_allow_draws,

        points: {
            win: config.payload.settings.win_points,
            draw: config.payload.settings.draw_points,
            loss: config.payload.settings.loss_points,
        },

        roundLimit: config.payload.settings.round_limit,

        /* Que grupo esta abierto abajo; null = todos */
        focusedGroup: null,
        focusedRound: null,

        /* Barajado local para el modo aleatorio del preview */
        shuffleSalt: 0,

        /*
         * El ultimo reparto que si se pudo calcular.
         *
         * Al pasar a "grupos personalizados" la estructura desaparece porque
         * los grupos automaticos no tienen cupo. Guardar el reparto anterior
         * permite ofrecer adoptarlo en vez de dejar la pantalla en blanco:
         * casi siempre se quiere partir de lo que ya habia.
         */
        lastGroupSizes: (config.payload.groups ?? []).map((g) => g.size),


        init() {
            this.rebuildOrder();

            [
                'participants', 'groupCountMode', 'groupCount', 'targetGroupSize',
                'minGroupSize', 'maxGroupSize', 'remainderPolicy',
                'distributionMode', 'cycles',
            ].forEach((field) => {
                this.$watch(field, () => this.scheduleRefresh());
            });

            this.$watch('roundLimit', () => {
                this.dirty = true;
                this.dropResultsBeyondLimit();
            });
        },

        previewParams() {
            return {
                group_count_mode: this.groupCountMode,
                group_count: this.groupCount,
                target_group_size: this.targetGroupSize,
                min_group_size: this.minGroupSize,
                max_group_size: this.maxGroupSize,
                remainder_policy: this.remainderPolicy,
                distribution_mode: this.distributionMode,
                internal_cycles: this.cycles,
            };
        },

        afterRefresh(payload) {
            if ((payload.groups ?? []).length) {
                this.lastGroupSizes = payload.groups.map((g) => g.size);
            }

            /*
             * Con una configuracion imposible no hay jornadas que contar y
             * el servidor devuelve null. Se conserva lo que hubiera en vez
             * de vaciar el control: en cuanto se arregla la configuracion,
             * el numero sigue ahi.
             */
            this.roundLimit = payload.settings.round_limit ?? this.roundLimit;

            /*
             * El servidor puede corregir la construccion: pedir 5 grupos en
             * una fase que solo admite 4 devuelve 4, y el panel tiene que
             * ensenar lo que de verdad se va a jugar.
             */
            this.groupCount = payload.settings.group_count;
            this.targetGroupSize = payload.settings.target_group_size;
        },


        /*
        |----------------------------------------------------------------
        | Quien ocupa cada semilla
        |----------------------------------------------------------------
        |
        | Aqui el reparto EN GRUPOS lo hace el servidor: es el repartidor de
        | siempre y depende de reglas que el motor ejecuta. Lo que decide el
        | navegador es solo quien es cada semilla, igual que en liga.
        |
        */

        rebuildOrder() {
            const identity = this.identity();

            /*
             * En reparto aleatorio se baraja el mapa semilla->persona para
             * poder ver otra distribucion sin volver al servidor. El sorteo
             * de verdad lo hace el motor al arrancar la fase.
             */
            this.order = this.distributionMode === 'RANDOM'
                ? this.shuffled(identity)
                : identity;
        },

        reshuffle() {
            this.clearResults();
            this.shuffleSalt++;
            this.order = this.shuffled(this.order);
        },


        /*
        |----------------------------------------------------------------
        | Grupos
        |----------------------------------------------------------------
        */

        get groups() {
            return this.payload.groups ?? [];
        },

        get isCustom() {
            return this.groupCountMode === 'CUSTOM_GROUPS';
        },

        /*
         * Personalizado elegido, pero sin un reparto que dibujar: o no hay
         * grupos, o los que hay no tienen cupo.
         */
        get customNeedsSetup() {
            return this.isCustom && this.groups.length === 0;
        },

        /*
         * El reparto que se ofrece adoptar.
         *
         * Normalmente es el que habia antes de cambiar de modo. Si se llega
         * directo a la pantalla ya en personalizado no hay nada que
         * recordar, asi que se propone un reparto parejo con el numero de
         * grupos que dice el panel: es mejor punto de partida que una
         * pantalla en blanco.
         */
        get adoptableSizes() {
            /*
             * La calcula el servidor, que es quien sabe de puertas: un grupo
             * con puertas apuntandole necesita al menos tantos sitios como
             * gente le mandan, y proponer menos seria proponer algo que no
             * cabe. Sin puertas sale un reparto parejo.
             */
            const suggested = this.payload.suggested_sizes ?? [];

            return suggested.length ? suggested : this.lastGroupSizes;
        },

        /* Si alguna puerta influyo en la propuesta, conviene decirlo */
        get suggestionFollowsGates() {
            return this.activeGates.some(
                (gate) => gate.entry_from && gate.target_group_code
            );
        },

        /* En personalizado la cantidad la manda la suma de los cupos */
        get participantsAreDerived() {
            return this.contract.is_derived === true;
        },

        get canAdoptSplit() {
            return this.customNeedsSetup && this.adoptableSizes.length > 0;
        },

        get isManualDraw() {
            return this.distributionMode === 'MANUAL';
        },

        /* En que grupo cayo una semilla */
        groupOfSeed(seed) {
            return this.groups.find((group) => group.seeds.includes(seed)) ?? null;
        },

        /* El orden de llegada de una semilla es la semilla misma */
        entrants() {
            return Array.from({ length: this.castSize }, (_, i) => i + 1);
        },


        /*
        |----------------------------------------------------------------
        | Puertas
        |----------------------------------------------------------------
        |
        | Una puerta de entrada dice que TRAMO de los que llegan va a que
        | grupo. Por eso se lee junto a la fila de entrantes: el tramo se ve
        | pintado del color de su grupo destino.
        |
        */

        get activeGates() {
            return this.payload.gates.filter((gate) => gate.status === 'ACTIVE');
        },

        /* Que puerta reclama a este entrante */
        gateOfEntrant(entrant) {
            return this.activeGates.find((gate) => {
                if (!gate.entry_from) {
                    return false;
                }

                const to = gate.entry_to ?? gate.entry_from;

                return entrant >= gate.entry_from && entrant <= to;
            }) ?? null;
        },

        /* Que grupo pide una puerta para este entrante, si lo pide */
        gateTargetOfEntrant(entrant) {
            const gate = this.gateOfEntrant(entrant);

            if (!gate?.target_group_code) {
                return null;
            }

            return this.groups.find((g) => g.code === gate.target_group_code) ?? null;
        },

        /*
         * Las puertas piden un grupo, pero el reparto lo ejecuta el motor
         * al arrancar. Cuando lo que pide una puerta no coincide con donde
         * cayo la semilla en el preview, conviene decirlo en vez de dejar
         * dos verdades en pantalla.
         */
        get gateConflicts() {
            const out = [];

            this.entrants().forEach((entrant) => {
                const wanted = this.gateTargetOfEntrant(entrant);

                if (!wanted) return;

                const actual = this.groupOfSeed(entrant);

                if (actual && actual.code !== wanted.code) {
                    out.push({ entrant, wanted: wanted.name, actual: actual.name });
                }
            });

            return out;
        },

        get activeExits() {
            return this.payload.exits.filter((exit) => exit.status === 'ACTIVE');
        },

        rulesOfExit(exitId) {
            return (this.payload.rules ?? []).filter((rule) => rule.exit_id === exitId);
        },

        /*
         * Quien sale por cada puerta, resuelto entero.
         *
         * Se recorren los criterios EN SU ORDEN, y cada uno se lleva a los
         * que ningun criterio anterior haya reclamado ya. Es exactamente lo
         * que hace GroupStageAdvancementCalculator en el servidor: sin ese
         * "ya reclamado", «los que sobren» se llevaria a todo el mundo.
         *
         * Antes solo se resolvian los criterios por puesto dentro de cada
         * grupo, asi que «todos los restantes» y los que comparan entre
         * grupos no marcaban a nadie en la estructura: se veia media tabla
         * pintada y media en blanco, sin explicacion.
         *
         * @return { [semilla]: salida }
         */
        get exitAssignment() {
            const out = {};
            const taken = new Set();

            /* Donde quedo cada uno: su grupo y su puesto dentro de el */
            const placed = new Map();

            this.groups.forEach((group) => {
                this.standingsOf(group).forEach((row, index) => {
                    placed.set(row.seed, { group, position: index + 1 });
                });
            });

            const exitOf = (id) =>
                this.payload.exits.find((exit) => exit.id === id) ?? null;

            (this.payload.rules ?? []).forEach((rule) => {

                if (rule.status !== 'ACTIVE') {
                    return;
                }

                const exit = exitOf(rule.exit_id);

                if (!exit || exit.status !== 'ACTIVE') {
                    return;
                }

                this.seedsForRule(rule, placed, taken).forEach((seed) => {
                    out[seed] = exit;
                    taken.add(seed);
                });
            });

            return out;
        },

        /*
         * Que semillas se lleva un criterio, entre las que quedan libres.
         *
         * Los criterios que comparan entre grupos usan crossTable, que
         * ordena con la misma cadena que el motor. Sin resultados esa
         * cadena acaba en la semilla, que es unica, asi que el orden es
         * estable y se puede pintar antes de jugar nada.
         */
        seedsForRule(rule, placed, taken) {
            const free = (seed) => !taken.has(seed);

            const inGroup = (entry) =>
                !rule.group_id || entry.group.definition_id === rule.group_id;

            /* Los que siguen libres, con su grupo y su puesto */
            const eligible = [...placed.entries()]
                .filter(([seed]) => free(seed))
                .map(([seed, entry]) => ({ seed, ...entry }));

            const byPosition = (test) => eligible
                .filter((e) => inGroup(e) && test(e))
                .map((e) => e.seed);

            /* Los libres, ordenados por la cadena de desempate */
            const ranked = this.crossTable
                .filter((row) => free(row.seed))
                .filter((row) => inGroup({ group: row.group }));

            const take = Math.max(0, parseInt(rule.take) || 0);
            const from = Math.max(0, parseInt(rule.position_from) || 0);
            const to = Math.max(0, parseInt(rule.position_to) || 0);

            switch (rule.rule_type) {

                case 'EACH_GROUP_TOP_N':
                    return byPosition((e) => e.position <= take);

                case 'EACH_GROUP_BOTTOM_N':
                    return byPosition((e) => e.position > e.group.size - take);

                case 'EACH_GROUP_POSITION':
                case 'SPECIFIC_GROUP_POSITION':
                    return byPosition((e) => e.position === from);

                case 'EACH_GROUP_RANGE':
                case 'SPECIFIC_GROUP_RANGE':
                    return byPosition((e) => e.position >= from && e.position <= to);

                /* Los mejores N de un mismo puesto, comparando entre grupos */
                case 'CROSS_GROUP_POSITION_TOP_N':
                    return ranked
                        .filter((row) => row.position === from)
                        .slice(0, take)
                        .map((row) => row.seed);

                case 'CROSS_GROUP_POSITION_BOTTOM_N':
                    return ranked
                        .filter((row) => row.position === from)
                        .slice(-take)
                        .map((row) => row.seed);

                case 'BEST_REMAINING':
                    return ranked.slice(0, take).map((row) => row.seed);

                case 'WORST_REMAINING':
                    return ranked.slice(-take).map((row) => row.seed);

                /* Todo el que no se haya llevado nadie */
                case 'REMAINING':
                    return ranked.map((row) => row.seed);

                default:
                    return [];
            }
        },

        /* La salida que se lleva un puesto concreto de un grupo */
        exitOfGroupPosition(group, position) {
            const row = this.standingsOf(group)[position - 1];

            return row ? (this.exitAssignment[row.seed] ?? null) : null;
        },

        /* Cuanta gente saca una salida, segun lo que hay en pantalla */
        emitsOf(exit) {
            return Object.values(this.exitAssignment)
                .filter((e) => e && e.id === exit.id)
                .length;
        },

        /* Quienes salen por ella, con su grupo y su puesto */
        membersOfExit(exit) {
            const out = [];

            this.groups.forEach((group) => {
                this.standingsOf(group).forEach((row, index) => {
                    if (this.exitAssignment[row.seed]?.id === exit.id) {
                        out.push({ seed: row.seed, group, position: index + 1 });
                    }
                });
            });

            return out;
        },


        /*
        |----------------------------------------------------------------
        | Jornadas
        |----------------------------------------------------------------
        |
        | Todos los grupos juegan su jornada 1 a la vez, luego la 2, etc.
        | Por eso la zona inferior se organiza por JORNADA y dentro por
        | grupo, y no al reves: asi se ve lo que ocurre el mismo dia.
        |
        */

        get maxRounds() {
            return Math.max(1, this.structure.max_rounds ?? 0);
        },

        get isTrimmed() {
            return (this.roundLimit ?? this.maxRounds) < this.maxRounds;
        },

        get roundNumbers() {
            const limit = this.roundLimit ?? this.maxRounds;

            return Array.from({ length: limit }, (_, i) => i + 1);
        },

        get visibleRoundNumbers() {
            return this.focusedRound === null
                ? this.roundNumbers
                : this.roundNumbers.filter((n) => n === this.focusedRound);
        },

        get visibleGroups() {
            return this.focusedGroup === null
                ? this.groups
                : this.groups.filter((g) => g.index === this.focusedGroup);
        },

        /* La jornada N de un grupo, si ese grupo llega a tenerla */
        roundOf(group, number) {
            return group.rounds.find((round) => round.number === number) ?? null;
        },

        get totalPlayable() {
            const limit = this.roundLimit ?? this.maxRounds;

            return this.groups.reduce((sum, group) => sum
                + group.rounds
                    .filter((r) => r.number <= limit)
                    .reduce((s, r) => s + r.pairings.length, 0), 0);
        },


        /*
        |----------------------------------------------------------------
        | Simulacion
        |----------------------------------------------------------------
        */

        key(groupIndex, roundNumber, pairIndex) {
            return groupIndex + ':' + roundNumber + ':' + pairIndex;
        },

        resultOf(groupIndex, roundNumber, pairIndex) {
            return this.resultAt(this.key(groupIndex, roundNumber, pairIndex));
        },

        simulateMatch(groupIndex, roundNumber, pairIndex) {
            this.simulateKey(this.key(groupIndex, roundNumber, pairIndex));
        },

        /* Una jornada de un grupo */
        simulateGroupRound(group, roundNumber) {
            const round = this.roundOf(group, roundNumber);

            if (!round) return;

            round.pairings.forEach((_, index) => {
                this.simulateKey(this.key(group.index, roundNumber, index));
            });
        },

        /* La misma jornada en todos los grupos */
        simulateRound(roundNumber) {
            this.groups.forEach((group) => this.simulateGroupRound(group, roundNumber));
        },

        /* Un grupo entero */
        simulateGroup(group) {
            const limit = this.roundLimit ?? this.maxRounds;

            group.rounds
                .filter((round) => round.number <= limit)
                .forEach((round) => this.simulateGroupRound(group, round.number));
        },

        simulateAll() {
            this.groups.forEach((group) => this.simulateGroup(group));
        },

        dropResultsBeyondLimit() {
            const limit = this.roundLimit ?? this.maxRounds;

            Object.keys(this.results).forEach((key) => {
                if (parseInt(key.split(':')[1], 10) > limit) {
                    delete this.results[key];
                }
            });
        },

        groupPlayedCount(group) {
            const limit = this.roundLimit ?? this.maxRounds;

            let count = 0;

            group.rounds
                .filter((r) => r.number <= limit)
                .forEach((round) => {
                    round.pairings.forEach((_, index) => {
                        if (this.resultOf(group.index, round.number, index)) count++;
                    });
                });

            return count;
        },


        /*
        |----------------------------------------------------------------
        | Clasificacion
        |----------------------------------------------------------------
        */

        /* La tabla de UN grupo, ya ordenada */
        standingsOf(group) {
            const limit = this.roundLimit ?? this.maxRounds;

            const matches = [];

            group.rounds
                .filter((round) => round.number <= limit)
                .forEach((round) => {
                    round.pairings.forEach((pair, index) => {
                        matches.push({
                            seedA: pair.seed_a,
                            seedB: pair.seed_b,
                            result: this.resultOf(group.index, round.number, index),
                        });
                    });
                });

            const rows = this.tally(group.seeds, matches);

            return this.hasResults ? this.rank(rows) : rows;
        },

        /*
         * La comparacion entre grupos: todo el mundo en una sola lista.
         *
         * Es lo que necesitan los criterios del tipo "los mejores terceros"
         * o "los N mejores restantes", que no se pueden decidir mirando un
         * grupo aislado.
         */
        get crossTable() {
            const rows = [];

            this.groups.forEach((group) => {
                this.standingsOf(group).forEach((row, position) => {
                    rows.push({
                        ...row,
                        group,
                        position: position + 1,
                    });
                });
            });

            return this.rank(rows);
        },

        /* Los mejores N de una misma posicion, entre grupos */
        bestOfPosition(position) {
            return this.crossTable.filter((row) => row.position === position);
        },
    };
}

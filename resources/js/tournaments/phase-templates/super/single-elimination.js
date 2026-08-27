/*
|--------------------------------------------------------------------------
| Super Edicion — Eliminacion directa
|--------------------------------------------------------------------------
|
| Un cuadro no es una lista: es un arbol. Lo propio de aqui es que quien
| ocupa un hueco de la segunda ronda NO se sabe hasta que se juega la
| primera, asi que casi todo pasa por una resolucion recursiva:
|
|   un puesto del cuadro   -> la persona que lo ocupa
|   ganador del partido k  -> se resuelve el partido k y se pregunta otra vez
|   perdedor del partido k -> lo mismo, por el otro lado
|
| Un hueco vacio no es un error: es un partido que todavia no se ha jugado.
|
| El arbol lo calcula el servidor (SingleEliminationBracketPlanner) y llega
| ya emparejado por PUESTOS. Aqui solo se decide quien es cada puesto y que
| pasaria si se juega, igual que en los otros dos motores.
|
*/

export function singleEliminationEditor(config) {

    return {

        completionMode: config.payload.settings.completion_mode,
        targetSurvivors: config.payload.settings.target_survivors,

        seedingMode: config.payload.settings.seeding_mode,
        pairingMode: config.payload.settings.pairing_mode,
        byeAssignment: config.payload.settings.bye_assignment,

        /*
         * Que grupos de puestos se ordenan jugando. El partido por el tercer
         * puesto es uno de ellos -'P3'-, no un caso aparte.
         */
        placements: [...(config.payload.settings.placements ?? [])],

        rankingSource: config.payload.settings.ranking_source,

        manualOrder: null,

        /* Un cuadro no tiene puntos: gana uno y el otro se va */
        points: { win: 1, draw: 0, loss: 0 },
        allowDraws: false,

        /* Que ronda esta abierta abajo; null = todas */
        focusedRound: null,


        init() {
            this.rebuildOrder();

            [
                'participants', 'completionMode', 'targetSurvivors',
                'pairingMode', 'byeAssignment', 'placements',
            ].forEach((field) => this.$watch(field, () => this.scheduleRefresh()));

            this.$watch('seedingMode', () => {
                this.dirty = true;
                this.clearResults();
                this.rebuildOrder();
            });

            this.$watch('rankingSource', () => {
                this.dirty = true;

                if (this.seedingMode === 'RANKING') {
                    this.clearResults();
                    this.rebuildOrder();
                }
            });
        },

        previewParams() {
            return {
                completion_mode: this.completionMode,
                target_survivors: this.targetSurvivors,
                seeding_mode: this.seedingMode,
                pairing_mode: this.pairingMode,
                bye_assignment: this.byeAssignment,

                /*
                 * Un guion cuando no hay ninguno. La cadena vacia no
                 * sobrevive a `filled()` en el servidor, asi que desactivar
                 * el ultimo grupo no llegaria nunca y el preview seguiria
                 * ensenando el cuadro de clasificacion recien apagado.
                 */
                placements: this.placements.join(',') || '-',
            };
        },

        afterRefresh(payload) {
            if (this.manualOrder && this.manualOrder.length !== payload.cast.length) {
                this.manualOrder = null;
            }

            this.targetSurvivors = payload.settings.target_survivors;

            /*
             * El servidor manda sobre que grupos existen -bajar los
             * supervivientes cambia la forma del cuadro y puede dejar una
             * clave sin grupo al que apuntar-, asi que se sincroniza.
             *
             * Pero SOLO cuando de verdad cambia algo.
             *
             * Asignar un array nuevo con el mismo contenido es una
             * referencia nueva, y `$watch` mira la referencia: la asignacion
             * disparaba otro refresco, que volvia a asignar, que volvia a
             * disparar. Un bucle cada 280 ms que no paraba nunca, y que se
             * veia como si la pantalla se refrescara sola sin tocarla.
             *
             * Los otros dos motores no lo sufren porque aqui solo asignan
             * numeros, y asignar el mismo numero no dispara nada.
             */
            const incoming = payload.settings.placements ?? [];

            if (incoming.join(',') !== this.placements.join(',')) {
                this.placements = [...incoming];
            }
        },


        /*
        |----------------------------------------------------------------
        | Grupos de puestos
        |----------------------------------------------------------------
        |
        | Un cuadro decide el primero y el segundo, y de ahi para abajo solo
        | sabe agrupar: los dos que caen en semifinales comparten el tercer
        | puesto porque nunca se han jugado entre ellos.
        |
        | Activar un grupo es decir "quiero saber el orden exacto de estos",
        | y entonces se juega un cuadro de clasificacion entre ellos.
        |
        */

        get groups() {
            return this.payload.groups ?? [];
        },

        /* Los que se pueden ordenar: los de uno solo ya estan decididos */
        get openGroups() {
            return this.groups.filter((group) => !group.auto);
        },

        isOrdered(key) {
            return this.placements.includes(key);
        },

        togglePlacement(key) {
            this.placements = this.isOrdered(key)
                ? this.placements.filter((k) => k !== key)
                : [...this.placements, key];

            this.dirty = true;
        },

        /* Los puestos que el cuadro sabe decidir tal y como esta configurado */
        get decidedPositions() {
            return this.structure.decided ?? [];
        },

        decides(position) {
            return this.decidedPositions.includes(position);
        },

        /*
         * Los grupos que quedan empatados, con quien esta dentro. Decirlo es
         * mas honesto que repartir puestos a dedo: los cuatro que caen en
         * cuartos son cuartofinalistas, no un quinto, un sexto, un septimo y
         * un octavo.
         */
        get tiedGroups() {
            return this.groups
                .filter((group) => !group.auto && !group.enabled)
                .map((group) => ({
                    ...group,
                    members: (group.sides ?? [])
                        .map((side) => this.occupant(side))
                        .filter(Boolean),
                }))
                .filter((group) => group.members.length > 0);
        },


        /*
        |----------------------------------------------------------------
        | Quien ocupa cada puesto del cuadro
        |----------------------------------------------------------------
        */

        rebuildOrder() {
            const identity = this.identity();

            if (this.seedingMode === 'RANDOM') {
                this.order = this.shuffled(identity);

                return;
            }

            if (this.seedingMode === 'RANKING') {
                this.order = this.demoRanking(identity);

                return;
            }

            if (this.seedingMode === 'MANUAL') {
                this.order = this.manualOrder ? [...this.manualOrder] : this.seatByGates(identity);
                this.manualOrder = [...this.order];

                return;
            }

            this.order = identity;
        },

        /*
         * Las puertas reclaman puestos del cuadro, y quien llega se sienta
         * donde le toca. Solo mandan en modo manual: con cualquier otro
         * decide el algoritmo.
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

        gateOfSeed(seed) {
            const index = (this.payload.seed_map?.seed_map ?? {})[seed];

            return index === undefined || index === null
                ? null
                : (this.payload.gates[index] ?? null);
        },


        /*
        |----------------------------------------------------------------
        | El arbol
        |----------------------------------------------------------------
        */

        get rounds() {
            return this.payload.rounds ?? [];
        },

        /* Los cuadros de clasificacion activos */
        get placementBrackets() {
            return this.payload.placements ?? [];
        },

        get placementMatches() {
            return this.placementBrackets.flatMap((bracket) =>
                bracket.rounds.flatMap((round) =>
                    round.matches.map((match) => ({ ...match, round, bracket }))));
        },

        /*
         * Todos los partidos, por su indice global.
         *
         * Los de clasificacion entran en el mismo indice a proposito: sus
         * lados apuntan a partidos del cuadro principal -el perdedor de las
         * semifinales-, asi que la resolucion tiene que poder saltar de un
         * sitio a otro sin saber de que cuadro viene cada uno.
         */
        get matchIndex() {
            const map = {};

            this.allMatches.forEach((match) => {
                map[match.index] = match;
            });

            return map;
        },

        get allMatches() {
            return [
                ...this.rounds.flatMap((round) =>
                    round.matches.map((match) => ({ ...match, round }))),
                ...this.placementMatches,
            ];
        },


        /*
        |----------------------------------------------------------------
        | Ramas
        |----------------------------------------------------------------
        |
        | Cuando la fase termina con varios en pie, cada superviviente sale
        | de un trozo distinto del cuadro. Con un solo superviviente no hay
        | ramas que distinguir y el servidor no manda ninguna.
        |
        */

        get branches() {
            return this.payload.branches ?? [];
        },

        get hasBranches() {
            return this.branches.length > 1;
        },

        branchOfSeed(seed) {
            return this.branches.find((branch) => branch.seeds.includes(seed)) ?? null;
        },

        /* Quien sale de una rama: el que gane su ultimo enfrentamiento */
        survivorOfBranch(branch) {
            return branch
                ? this.winnerOf(this.matchIndex[branch.root])
                : null;
        },

        seedOfBranchSurvivor(branch) {
            if (!branch) return null;

            const root = this.matchIndex[branch.root];
            const side = this.decisionOf(root);

            return side ? this.seedOf(root[side]) : null;
        },

        /*
         * Quien esta en un lado de un partido.
         *
         * Devuelve null cuando todavia no se sabe: un hueco vacio es un
         * partido sin jugar, no un error.
         */
        occupant(side) {
            if (!side) {
                return null;
            }

            if (side.type === 'BYE') {
                return null;
            }

            if (side.type === 'SEED') {
                return this.atSeed(side.seed);
            }

            const feeder = this.matchIndex[side.from];

            if (!feeder) {
                return null;
            }

            const decided = this.decisionOf(feeder);

            if (!decided) {
                return null;
            }

            return side.type === 'WINNER'
                ? this.occupant(feeder[decided])
                : this.occupant(feeder[decided === 'a' ? 'b' : 'a']);
        },

        /* El puesto del cuadro que ocupa un lado, para poder etiquetarlo */
        seedOf(side) {
            if (!side) return null;

            if (side.type === 'SEED') return side.seed;
            if (side.type === 'BYE') return null;

            const feeder = this.matchIndex[side.from];

            if (!feeder) return null;

            const decided = this.decisionOf(feeder);

            if (!decided) return null;

            return side.type === 'WINNER'
                ? this.seedOf(feeder[decided])
                : this.seedOf(feeder[decided === 'a' ? 'b' : 'a']);
        },

        /*
         * Que lado gano un partido: 'a', 'b' o null.
         *
         * Acepta null a proposito: x-if desmonta su contenido DESPUES de que
         * los hijos se reevaluen, asi que un partido que deja de existir
         * -el del tercer puesto al apagarlo- llega aqui como null antes de
         * que el bloque desaparezca.
         *
         * Un descanso no se juega: si un lado esta vacio, el otro pasa solo.
         * Por eso hay cuadros donde la primera ronda ya tiene ganadores sin
         * que nadie haya pulsado nada.
         */
        decisionOf(match) {
            if (!match) return null;

            if (match.a?.type === 'BYE' && match.b?.type !== 'BYE') return 'b';
            if (match.b?.type === 'BYE' && match.a?.type !== 'BYE') return 'a';

            return this.results[match.index] ?? null;
        },

        /* Un partido se puede jugar cuando los dos lados tienen a alguien */
        isPlayable(match) {
            if (!match) return false;

            return this.occupant(match.a) !== null && this.occupant(match.b) !== null;
        },

        isBye(match) {
            return match?.a?.type === 'BYE' || match?.b?.type === 'BYE';
        },

        winnerOf(match) {
            const side = this.decisionOf(match);

            return side ? this.occupant(match?.[side]) : null;
        },

        loserOf(match) {
            const side = this.decisionOf(match);

            return side ? this.occupant(match?.[side === 'a' ? 'b' : 'a']) : null;
        },


        /*
        |----------------------------------------------------------------
        | Simulacion
        |----------------------------------------------------------------
        */

        get playableMatches() {
            return this.allMatches.filter((m) => !this.isBye(m));
        },

        get totalPlayable() {
            return this.playableMatches.length;
        },

        get playedCount() {
            return Object.keys(this.results).length;
        },

        simulateMatch(match) {
            if (!match || !this.isPlayable(match)) {
                return;
            }

            this.results[match.index] = Math.random() < 0.5 ? 'a' : 'b';
        },

        simulateRound(round) {
            round.matches.forEach((match) => this.simulateMatch(match));
        },

        /*
         * Todo, ronda a ronda: la segunda no se puede jugar hasta que la
         * primera decide quien la ocupa.
         */
        simulateAll() {
            this.rounds.forEach((round) => this.simulateRound(round));

            /*
             * Y despues los de clasificacion, tambien por niveles: la
             * segunda ronda de un cuadro de clasificacion no se puede jugar
             * hasta que la primera dice quien la ocupa.
             */
            this.placementBrackets.forEach((bracket) => {
                bracket.rounds.forEach((round) => this.simulateRound(round));
            });
        },

        simulateBracket(bracket) {
            bracket.rounds.forEach((round) => this.simulateRound(round));
        },

        clearResults() {
            this.results = {};
        },


        /*
        |----------------------------------------------------------------
        | Como acaba
        |----------------------------------------------------------------
        */

        get finalRound() {
            return this.rounds[this.rounds.length - 1] ?? null;
        },

        get champion() {
            if (this.completionMode !== 'WINNER' || !this.finalRound) {
                return null;
            }

            return this.winnerOf(this.finalRound.matches[0]);
        },

        /*
         * Los puestos finales: quien acaba primero, segundo, tercero...
         *
         * Salen de dos sitios y de ningun otro:
         *
         *   1. Los grupos de un solo miembro, que ya estan decididos sin
         *      jugar nada mas -el campeon lo decide la final-.
         *
         *   2. Los duelos de clasificacion que reparten puesto. Cada puesto
         *      lo decide exactamente uno, el que enfrenta a dos, y viene
         *      marcado con `awards`.
         *
         * Lo que no sale de ahi no se inventa: un grupo empatado se queda
         * empatado, y sus miembros no aparecen aqui.
         *
         * @return { [semilla]: puesto }
         */
        get finalPositions() {
            const out = {};

            this.groups.forEach((group) => {
                if (!group.auto) return;

                const seed = this.seedOf((group.sides ?? [])[0]);

                if (seed) out[seed] = group.from;
            });

            this.placementMatches.forEach((match) => {
                if (!match.awards) return;

                const side = this.decisionOf(match);

                if (!side) return;

                const winner = this.seedOf(match[side]);
                const loser = this.seedOf(match[side === 'a' ? 'b' : 'a']);

                if (winner) out[winner] = match.awards.win;
                if (loser) out[loser] = match.awards.lose;
            });

            return out;
        },

        /* Los que siguen en pie cuando la fase termina */
        get survivorSeeds() {
            if (!this.finalRound) return [];

            return this.finalRound.matches
                .map((match) => {
                    const side = this.decisionOf(match);

                    return side ? this.seedOf(match[side]) : null;
                })
                .filter(Boolean);
        },

        /*
         * Los que se quedaron por el camino: quien pierde un enfrentamiento
         * DEL CUADRO PRINCIPAL. Los de clasificacion no eliminan a nadie,
         * solo ordenan a los que ya estaban fuera.
         */
        get eliminatedSeeds() {
            const out = new Set();

            this.rounds.forEach((round) => {
                round.matches.forEach((match) => {
                    const side = this.decisionOf(match);

                    if (!side) return;

                    const seed = this.seedOf(match[side === 'a' ? 'b' : 'a']);

                    if (seed) out.add(seed);
                });
            });

            return [...out];
        },

        /*
        |----------------------------------------------------------------
        | Salidas
        |----------------------------------------------------------------
        */

        get activeExits() {
            return this.payload.exits.filter((exit) => exit.status === 'ACTIVE');
        },

        /* La salida que se lleva un puesto final concreto */
        exitOfPosition(position) {
            for (const exit of this.activeExits) {
                if (!exit.positions) continue;

                if (position >= exit.positions.from && position <= exit.positions.to) {
                    return exit;
                }
            }

            return null;
        },

        /* La salida que recoge a quien salga de una rama concreta */
        exitOfBranch(number) {
            return this.activeExits.find((exit) => exit.branch === number) ?? null;
        },

        /* La salida que se lleva a quien ocupa este puesto del cuadro */
        exitOfSeed(seed) {
            const position = this.finalPositions[seed];

            if (position) {
                const byPosition = this.exitOfPosition(position);

                if (byPosition) return byPosition;
            }

            /*
             * Y si no, por rama: pero solo se lleva al que SALE de ella, no
             * a todo el que empezo ahi. Los demas de esa rama estan
             * eliminados y no cruzan esa puerta.
             */
            const branch = this.branchOfSeed(seed);

            if (branch && this.seedOfBranchSurvivor(branch) === seed) {
                return this.exitOfBranch(branch.number);
            }

            const survivors = this.survivorSeeds;

            if (survivors.includes(seed)) {
                return this.activeExits.find((e) => e.selector_type === 'SURVIVORS') ?? null;
            }

            if (this.eliminatedSeeds.includes(seed)) {
                return this.activeExits.find((e) => e.selector_type === 'ELIMINATED') ?? null;
            }

            return null;
        },

        /*
         * Quien cruza una salida ahora mismo. Cada tipo se resuelve por su
         * lado porque preguntan cosas distintas: una habla de puestos, otra
         * de una rama del cuadro y otra de seguir vivo.
         */
        membersOfExit(exit) {
            const wrap = (seeds) => seeds
                .filter(Boolean)
                .map((seed) => ({ seed, position: this.finalPositions[seed] ?? null }))
                .sort((a, b) => (a.position ?? 999) - (b.position ?? 999));

            if (exit.selector_type === 'BRACKET_BRANCH') {
                const branch = this.branches.find((b) => b.number === exit.branch);

                return wrap([this.seedOfBranchSurvivor(branch)]);
            }

            if (exit.selector_type === 'SURVIVORS') {
                return wrap(this.survivorSeeds);
            }

            if (exit.selector_type === 'ELIMINATED') {
                return wrap(this.eliminatedSeeds);
            }

            return wrap(
                Object.entries(this.finalPositions)
                    .filter(([, position]) => this.exitOfPosition(position)?.id === exit.id)
                    .map(([seed]) => parseInt(seed, 10))
            );
        },

        emitsOf(exit) {
            return this.membersOfExit(exit).length;
        },


        /*
        |----------------------------------------------------------------
        | Estado
        |----------------------------------------------------------------
        */

        get showsRanking() {
            return this.seedingMode === 'RANKING';
        },

        get showsManual() {
            return this.seedingMode === 'MANUAL';
        },

        get endsWithWinner() {
            return this.completionMode === 'WINNER';
        },

        /*
         * Que grupo impide que una salida se resuelva, con los valores que
         * hay AHORA MISMO en el formulario. Devuelve null cuando no hay
         * problema.
         *
         * Vive en el motor y no en el formulario a proposito: Alpine encadena
         * scopes para EVALUAR expresiones, pero no para el `this` de un
         * metodo declarado en un x-data anidado. Escrito dentro del
         * formulario, `this.groups` seria undefined.
         */
        blockingGroup(type, from, to) {
            const range = {
                TOP_N: [1, Number(from)],
                RANK_POSITION: [Number(from), Number(from)],
                RANK_RANGE: [Number(from), Number(to)],
            }[type];

            if (!range) {
                return null;
            }

            const [lo, hi] = range;

            if (!Number.isFinite(lo) || !Number.isFinite(hi) || hi < lo) {
                return null;
            }

            for (let position = lo; position <= hi; position++) {

                if (this.decides(position)) continue;

                const group = this.groups.find(
                    (g) => position >= g.from && position <= g.to);

                /* Fuera del cuadro no hay grupo que activar: no es esto */
                if (group) return group;
            }

            return null;
        },

        /* Cuantos duelos extra cuestan los grupos que se estan ordenando */
        get placementCost() {
            return this.placementMatches.length;
        },

        /* Cuantos huecos del cuadro quedan sin nadie detras */
        get byeCount() {
            return this.structure.byes ?? 0;
        },
    };
}

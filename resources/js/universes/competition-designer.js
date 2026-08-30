/*
 * EL DISEÑADOR DE UNA EDICIÓN
 *
 * Un torneo es una marca —«la Copa»—. Una competición es la edición que se
 * juega este año, y no todas son iguales: cambia el juego, cambia cuántos
 * juegos dura un enfrentamiento, y a veces cambia hasta dentro de la misma
 * edición —grupos a un juego, final al mejor de cinco—.
 *
 * Lo que esta pantalla NO puede hacer es abrir lo que el torneo cerró. Por
 * eso casi todo lo de aquí tiene dos caras: el valor, y de dónde viene.
 *
 * ---------------------------------------------------------------
 *
 * Esto refleja lo que hace CompetitionPhasePlan en el servidor, y esa
 * duplicación es a propósito: sin ella, saber con qué se juega la final
 * obligaría a guardar y volver. El servidor sigue siendo quien manda —lo
 * de aquí no se guarda, solo se enseña—.
 */
export default function competitionDesigner(config) {
    return {
        open: config.open ?? 'identity',

        /* ------------------------------------------------ lo que hereda */
        inherited: config.inherited ?? {},

        /* ------------------------------------------------ identidad */
        name: config.competition?.name ?? '',
        imagePreview: config.competition?.image_url ?? null,

        /* ------------------------------------------------ la forma */
        templates: config.templates ?? [],
        templateId: Number(config.competition?.tournament_template_id ?? 0),
        templateOpen: false,

        /* ------------------------------------------------ el juego */
        games: config.games ?? [],
        gameKey: config.competition?.game_key ?? '',
        gameScope: config.competition?.game_scope ?? 'COMPETITION',

        /* ------------------------------------------------ la batalla */
        battleScope: config.competition?.battle_scope ?? 'COMPETITION',
        seriesFormat: config.competition?.series_format ?? 'BEST_OF',
        bestOf: Number(config.competition?.best_of ?? 3),
        fixedGames: Number(config.competition?.fixed_games ?? 2),
        battleParticipants: config.competition?.battle_participants ?? '',
        decisionMode: config.competition?.decision_mode ?? 'SERIES_THEN_POINTS',
        allowDraws: Boolean(config.competition?.allow_draws),

        /* ------------------------------------------------ por fase */
        phases: config.phaseSettings ?? {},
        openPhase: null,

        /* ------------------------------------------------ las puertas */
        competitors: config.competitors ?? [],
        catalog: config.catalog ?? [],
        startRules: config.startRules ?? [],

        /*
         * Quién entra hoy por cada puerta.
         *
         * Al editar viene relleno con el reparto que ya tiene la edición,
         * porque retocarlo es justo lo que se viene a hacer: empezar de una
         * caja vacía obligaría a volver a marcar a los veinte.
         */
        manual: config.currentAssignments ?? {},

        canReassign: config.canReassign !== false,

        /*
         * Con reparto ya hecho se abre en «uno a uno»: es lo que hay
         * delante. Con reglas guardadas y sin reparto, en «con una regla».
         */
        assignMode: Object.keys(config.currentAssignments ?? {}).length
            ? 'MANUAL'
            : (config.startRules?.length ? 'RULES' : 'MANUAL'),
        search: '',
        openDoor: null,

        /* Qué puerta tiene abierto su selector a dedo */
        openDoorHand: null,

        /*
         * Cómo se ven los competidores al elegirlos.
         *
         *   TILES    cuadritos: solo la cara, para abarcar muchos de golpe
         *   GALLERY  cara con sus atributos debajo
         *   LIST     una línea por competidor, con todo su catálogo
         *
         * Se recuerda en el navegador: es una preferencia de cómo miras, no
         * un dato de la competición.
         */
        pickerView: 'GALLERY',
        pickerSize: 3,

        /*
         * El orden de cada puerta, congelado al abrirla.
         *
         * Los marcados van primero, pero SOLO se recalcula al abrir. Si se
         * reordenase en cada clic, la ficha que acabas de marcar saltaría a
         * la cabeza y perderías de vista dónde estabas: pasaba de la
         * posición 7 a la 1 en cuanto la tocabas.
         */
        doorOrder: {},

        /* ------------------------------------------------ premios */
        inheritedRewards: config.inheritedRewards ?? [],

        /* ------------------------------------------------ servicios */
        previewUrl: config.previewUrl,
        csrf: config.csrf,

        init() {
            /*
             * Una edición nueva empieza con una puerta por cada entrada de
             * la plantilla, aunque estén vacías: sin la fila, no hay dónde
             * escribir la regla ni dónde ver cuánto falta.
             */
            this.syncDoors();

            this.recallView();

            /* Al abrir una puerta se congela su orden */
            this.$watch('openDoor', (id) => {
                if (id !== null && id !== undefined) this.freezeOrder(id);
            });

            this.$watch('templateId', () => {
                this.syncDoors();
                this.openPhase = null;
            });

            if (this.assignMode === 'RULES') {
                this.refreshRouting();
            }
        },

        /*
        |----------------------------------------------------------------------
        | Los bloques
        |----------------------------------------------------------------------
        */

        toggle(block) {
            this.open = this.open === block ? null : block;
        },

        isOpen(block) {
            return this.open === block;
        },

        /*
        |----------------------------------------------------------------------
        | La forma elegida
        |----------------------------------------------------------------------
        */

        get template() {
            return this.templates.find((t) => Number(t.id) === Number(this.templateId)) ?? null;
        },

        get templatePhases() {
            return this.template?.phases ?? [];
        },

        get templateStarts() {
            return this.template?.starts ?? [];
        },

        /* Las fases repartidas en columnas, que es como se juegan */
        get columns() {
            const depth = this.template?.depth ?? 1;

            return Array.from({ length: depth }, (_, level) =>
                this.templatePhases.filter((p) => Number(p.level) === level)
            );
        },

        /* Lo mismo, para una plantilla que no es la elegida */
        columnsOf(brief) {
            const depth = brief?.depth ?? 1;

            return Array.from({ length: depth }, (_, level) =>
                (brief?.phases ?? []).filter((p) => Number(p.level) === level)
            );
        },

        pickTemplate(id) {
            this.templateId = Number(id);
            this.templateOpen = false;
        },

        /*
        |----------------------------------------------------------------------
        | El juego
        |----------------------------------------------------------------------
        */

        get allowedGames() {
            return this.games.filter((g) => g.allowed);
        },

        get canChooseGame() {
            return this.inherited.game_mode === 'VARIED';
        },

        get game() {
            return this.games.find((g) => g.key === this.gameKey)
                ?? this.allowedGames[0]
                ?? null;
        },

        get gameStats() {
            return this.game?.stats ?? [];
        },

        get gameSummary() {
            if (!this.game) return 'sin juego';

            return this.gameScope === 'PHASE'
                ? this.game.name + ', salvo donde se diga'
                : this.game.name;
        },

        /*
        |----------------------------------------------------------------------
        | La batalla
        |----------------------------------------------------------------------
        */

        get battleLabel() {
            if (this.seriesFormat === 'FIXED_GAMES') {
                return this.fixedGames === 1
                    ? 'Un solo juego'
                    : this.fixedGames + ' juegos fijos';
            }

            return this.bestOf === 1 ? 'A un juego' : 'Al mejor de ' + this.bestOf;
        },

        get battleSummary() {
            const partes = [this.battleLabel];

            if (this.battleParticipants) {
                partes.push(this.battleParticipants + ' por batalla');
            }

            if (this.decisionMode === 'POINTS_ONLY') {
                partes.push('solo anotaciones');
            }

            return partes.join(' · ');
        },

        /*
         * Un «al mejor de» par no puede decidirse: al mejor de 4 se empata
         * a 2 y no hay forma de romperlo. Se sube al impar en vez de
         * dejar que el servidor lo rechace después.
         */
        get bestOfIsEven() {
            return this.seriesFormat === 'BEST_OF' && this.bestOf % 2 === 0;
        },

        fixBestOf() {
            if (this.bestOfIsEven) this.bestOf += 1;
        },

        /*
        |----------------------------------------------------------------------
        | Lo que aplica a cada fase
        |----------------------------------------------------------------------
        |
        | Refleja CompetitionPhasePlan. Devuelve también DE DÓNDE viene cada
        | cosa, que es lo que permite que la pantalla diga «esta fase manda»
        | en vez de repetir el mismo número en veinte sitios sin explicar
        | cuál gana.
        */

        phaseOf(nodeId) {
            if (!this.phases[nodeId]) {
                this.phases[nodeId] = {
                    game_key: '',
                    series_format: '',
                    best_of: '',
                    fixed_games: '',
                    battle_participants: '',
                    decision_mode: '',
                    allow_draws: '',
                };
            }

            return this.phases[nodeId];
        },

        planFor(nodeId) {
            const own = this.phases[nodeId] ?? {};

            const perGame = this.gameScope === 'PHASE' && own.game_key;
            const perBattle = this.battleScope === 'PHASE';

            const format = perBattle && own.series_format
                ? own.series_format
                : this.seriesFormat;

            const bestOf = perBattle && own.best_of ? Number(own.best_of) : this.bestOf;

            const fixed = perBattle && own.fixed_games
                ? Number(own.fixed_games)
                : this.fixedGames;

            const label = format === 'FIXED_GAMES'
                ? (fixed === 1 ? 'Un solo juego' : fixed + ' juegos fijos')
                : (bestOf === 1 ? 'A un juego' : 'Al mejor de ' + bestOf);

            const gameKey = perGame ? own.game_key : this.gameKey;

            const decision = perBattle && own.decision_mode
                ? own.decision_mode
                : this.decisionMode;

            const draws = perBattle && own.allow_draws !== ''
                ? own.allow_draws === '1' || own.allow_draws === true
                : this.allowDraws;

            const participants = perBattle && own.battle_participants
                ? Number(own.battle_participants)
                : this.battleParticipants;

            return {
                game: this.games.find((g) => g.key === gameKey) ?? this.game,
                gameFrom: perGame ? 'PHASE' : 'COMPETITION',
                label,
                battleFrom: (perBattle && (own.series_format || own.best_of || own.fixed_games
                    || own.decision_mode || own.allow_draws !== '' || own.battle_participants))
                    ? 'PHASE'
                    : 'COMPETITION',
                decision,
                draws,
                participants,
            };
        },

        /* Si esta fase dice algo propio, para poder marcarla en la lista */
        phaseOverrides(nodeId) {
            const own = this.phases[nodeId] ?? {};

            return Object.entries(own)
                .filter(([k, v]) => v !== '' && v !== null && v !== undefined)
                .map(([k]) => k);
        },

        clearPhase(nodeId) {
            this.phases[nodeId] = {
                game_key: '',
                series_format: '',
                best_of: '',
                fixed_games: '',
                battle_participants: '',
                decision_mode: '',
                allow_draws: '',
            };
        },

        /*
        |----------------------------------------------------------------------
        | Los premios de cada fase, desde el bloque de fases
        |----------------------------------------------------------------------
        |
        | El componente de premios vive anidado dentro del bloque 07, así que
        | desde aquí no se le puede llamar. Lo que se hace es al revés: el
        | hijo publica su propio estado en el padre con un x-effect, y aquí
        | solo se lee.
        |
        | Es el mismo truco que ya hacía falta para bajarle las stats del
        | juego: Alpine encadena scopes para EVALUAR expresiones, pero no
        | para el `this` de un método declarado en un x-data anidado.
        */
        prizes: null,

        phasePrizes(nodeId) {
            if (!this.prizes) return [];

            return this.prizes.rewards.filter(
                (r) => String(r.node_id) === String(nodeId)
            );
        },

        /* Lo que se lleva quien gane esta fase, en corto */
        phasePrizeText(nodeId) {
            const propios = this.phasePrizes(nodeId);

            if (propios.length === 0) return 'sin premio propio';

            return propios.length === 1
                ? '1 premio'
                : propios.length + ' premios';
        },

        /* Añadir uno colgado de esta fase, sin salir del bloque */
        addPhasePrize(nodeId) {
            if (!this.prizes) return;

            this.prizes.addReward({ node_id: nodeId });

            /* Y se lleva al usuario donde se rellena */
            this.open = 'prizes';
        },

        get phasesSummary() {
            const con = this.templatePhases
                .filter((p) => this.phaseOverrides(p.id).length)
                .length;

            if (this.gameScope !== 'PHASE' && this.battleScope !== 'PHASE') {
                return 'todas iguales';
            }

            return con === 0
                ? 'ninguna excepción todavía'
                : con + (con === 1 ? ' fase distinta' : ' fases distintas');
        },

        /*
        |----------------------------------------------------------------------
        | Las puertas
        |----------------------------------------------------------------------
        */

        /*
         * Una fila por entrada de la plantilla. Al cambiar de plantilla se
         * conservan las reglas de las puertas que sigan existiendo: cambiar
         * de forma no debería borrar el trabajo de repartir.
         */
        syncDoors() {
            const starts = this.templateStarts;

            const previas = {};

            this.startRules.forEach((r) => {
                previas[r.start_id] = r;
            });

            this.startRules = starts.map((s) => previas[s.id] ?? {
                start_id: s.id,
                mode: 'ALL',
                rules: [],
            });

            starts.forEach((s) => {
                if (!this.manual[s.id]) this.manual[s.id] = [];
            });

            /*
             * Un reparto guardado puede apuntar a una puerta que la forma
             * elegida ya no tiene. Se descarta, porque meter a alguien por
             * una puerta inexistente no es meterlo por ninguna.
             */
            const vivas = starts.map((s) => Number(s.id));

            Object.keys(this.manual).forEach((key) => {
                if (!vivas.includes(Number(key))) delete this.manual[key];
            });
        },

        /*
         * La regla de una puerta, creándola si falta.
         *
         * Nunca devuelve undefined a propósito: la vista la lee dentro de
         * un x-for sobre las puertas, y ese render ocurre ANTES de que
         * init() llame a syncDoors(). Devolver undefined ahí reventaba el
         * bloque entero —«Cannot read properties of undefined»— y dejaba
         * las puertas sin pintar.
         */
        doorRule(startId) {
            let row = this.startRules.find((r) => Number(r.start_id) === Number(startId));

            if (!row) {
                row = { start_id: Number(startId), mode: 'ALL', rules: [], groups: [], include: [], exclude: [] };
                this.startRules.push(row);
            }

            /*
             * Las filas guardadas antes de que existieran los grupos y la
             * mano no los traen. Rellenarlos aqui evita que la vista tenga
             * que preguntar «¿y si no hay?» en cada expresion.
             */
            row.groups ??= [];
            row.include ??= [];
            row.exclude ??= [];

            return row;
        },

        /*
        |----------------------------------------------------------------------
        | Los grupos de una puerta
        |----------------------------------------------------------------------
        |
        | Una puerta habla el mismo lenguaje que un torneo: cuatro modos,
        | grupos y mano. Que sea el mismo no es comodidad, es que quien
        | reparte por puertas y quien decide quien compite evaluan con el
        | mismo codigo en el servidor.
        */

        addDoorGroup(startId) {
            this.doorRule(startId).groups.push({ mode: 'ALL', rules: [] });
        },

        removeDoorGroup(startId, gi) {
            this.doorRule(startId).groups.splice(gi, 1);
            this.refreshRouting();
        },

        addDoorGroupRule(startId, gi) {
            this.doorRule(startId).groups[gi].rules.push({ attribute: '', values: [] });
        },

        removeDoorGroupRule(startId, gi, ri) {
            this.doorRule(startId).groups[gi].rules.splice(ri, 1);
            this.refreshRouting();
        },

        toggleDoorGroupValue(startId, gi, ri, value) {
            const regla = this.doorRule(startId).groups[gi].rules[ri];

            const i = regla.values.indexOf(value);

            if (i === -1) regla.values.push(value);
            else regla.values.splice(i, 1);

            this.refreshRouting();
        },

        /*
        |----------------------------------------------------------------------
        | A dedo, por puerta
        |----------------------------------------------------------------------
        */

        doorHandState(startId, id) {
            const row = this.doorRule(startId);

            if (row.include.includes(id)) return 'IN';
            if (row.exclude.includes(id)) return 'OUT';
            return 'RULE';
        },

        /* Un boton, tres estados: normal, dentro pase lo que pase, fuera */
        cycleDoorHand(startId, id) {
            const row = this.doorRule(startId);

            if (row.include.includes(id)) {
                row.include = row.include.filter((x) => x !== id);
                row.exclude = [...row.exclude, id];
            } else if (row.exclude.includes(id)) {
                row.exclude = row.exclude.filter((x) => x !== id);
            } else {
                row.include = [...row.include, id];
            }

            this.refreshRouting();
        },

        /*
         * En qué posición de startRules está esta puerta.
         *
         * Los campos ocultos se nombran start_rules[i][...] y ese `i` tiene
         * que ser el índice real del array, no el id de la puerta: PHP
         * reindexa, y un hueco convertiría la lista en un mapa.
         */
        startIndex(startId) {
            return this.startRules.findIndex(
                (r) => Number(r.start_id) === Number(startId)
            );
        },

        doorHandCount(startId) {
            const row = this.doorRule(startId);

            return row.include.length + row.exclude.length;
        },

        clearDoorHand(startId) {
            const row = this.doorRule(startId);

            row.include = [];
            row.exclude = [];

            this.refreshRouting();
        },

        addDoorRule(startId) {
            this.doorRule(startId).rules.push({ attribute: '', values: [] });
        },

        removeDoorRule(startId, index) {
            this.doorRule(startId).rules.splice(index, 1);
        },

        /*
         * El catalogo es una LISTA de atributos, no un mapa: cada entrada
         * trae su nombre, cuantas entidades lo llevan y sus valores. Se
         * busca por nombre en vez de indexar.
         */
        attributeOf(name) {
            return this.catalog.find((a) => a.name === name) ?? null;
        },

        get attributeNames() {
            return this.catalog.map((a) => a.name);
        },

        valuesFor(attribute) {
            return this.attributeOf(attribute)?.values ?? [];
        },

        toggleValue(rule, value) {
            const i = rule.values.indexOf(value);

            if (i === -1) rule.values.push(value);
            else rule.values.splice(i, 1);
        },

        /*
         * A quién manda cada regla, según el servidor.
         *
         * Se pregunta y no se calcula aquí porque quien reparte de verdad
         * al guardar es CompetitionStartRouting: enseñar un número que
         * luego no coincide es peor que no enseñar ninguno.
         */
        routing: { assignments: {}, leftovers: [], overflow: {} },
        routingBusy: false,

        async refreshRouting() {
            if (this.assignMode !== 'RULES') return;

            this.routingBusy = true;

            const capacities = {};

            this.templateStarts.forEach((s) => {
                if (s.capacity) capacities[s.id] = s.capacity;
            });

            try {
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        start_rules: this.startRules,
                        capacities,
                    }),
                });

                if (response.ok) this.routing = await response.json();
            } finally {
                this.routingBusy = false;
            }
        },

        /* Cuántos entran por esta puerta, con la forma que se esté usando */
        inDoor(startId) {
            return this.assignMode === 'RULES'
                ? (this.routing.assignments?.[startId] ?? []).length
                : (this.manual[startId] ?? []).length;
        },

        overflowOf(startId) {
            return (this.routing.overflow?.[startId] ?? []).length;
        },

        get totalIn() {
            return this.templateStarts
                .reduce((sum, s) => sum + this.inDoor(s.id), 0);
        },

        get leftovers() {
            if (this.assignMode === 'RULES') {
                return this.routing.leftovers?.length ?? 0;
            }

            const taken = Object.values(this.manual).flat().length;

            return this.competitors.length - taken;
        },

        /* Cuánto falta para llenar una puerta, o cuánto sobra */
        doorRoom(startId) {
            const start = this.templateStarts.find((s) => Number(s.id) === Number(startId));

            if (!start?.capacity) return null;

            return start.capacity - this.inDoor(startId);
        },

        doorTone(startId) {
            return {
                OVER: 'text-rose-400',
                FULL: 'text-emerald-400',
                FREE: 'text-slate-500',
                EMPTY: 'text-slate-500',
            }[this.doorState(startId)] ?? 'text-amber-400';
        },

        /*
         * En qué situación está una puerta. Un solo sitio decide esto, y de
         * él salen el color, la palabra y la barra: antes cada uno lo
         * calculaba por su cuenta y podían no coincidir.
         *
         *   FREE     sin límite de plazas
         *   EMPTY    tiene límite y no hay nadie
         *   PARTIAL  van entrando
         *   FULL     justo las que caben
         *   OVER     hay más de los que caben
         */
        doorState(startId) {
            const room = this.doorRoom(startId);

            if (room === null) return 'FREE';
            if (room < 0) return 'OVER';
            if (room === 0) return 'FULL';
            if (this.inDoor(startId) === 0) return 'EMPTY';

            return 'PARTIAL';
        },

        doorIsFull(startId) {
            const estado = this.doorState(startId);

            return estado === 'FULL' || estado === 'OVER';
        },

        doorHint(startId) {
            const room = this.doorRoom(startId);

            return {
                FREE: 'sin límite',
                FULL: '✓ llena',
                OVER: 'sobra' + (Math.abs(room) === 1 ? '' : 'n') + ' ' + Math.abs(room),
                EMPTY: 'faltan ' + room,
                PARTIAL: 'faltan ' + room,
            }[this.doorState(startId)];
        },

        /*
         * Quiénes están dentro de una puerta, como competidores enteros.
         *
         * Es lo que faltaba. Un número —«2»— no dice si marcaste a quien
         * querías; con la cara y el nombre delante, sí. Sirve igual con
         * reglas (lo reparte el servidor) que a mano (lo marcas tú), porque
         * la pregunta es la misma: quién ha quedado aquí.
         */
        doorRoster(startId) {
            const ids = this.assignMode === 'RULES'
                ? (this.routing.assignments?.[startId] ?? [])
                : (this.manual[startId] ?? []);

            return ids
                .map((id) => this.competitors.find((c) => Number(c.id) === Number(id)))
                .filter(Boolean);
        },

        /*
         * Los que la regla eligió y se quedaron en la puerta por falta de
         * plazas. Decir «19 no caben» sin decir quiénes no ayudaba a nadie.
         */
        doorOverflowRoster(startId) {
            return (this.routing.overflow?.[startId] ?? [])
                .map((id) => this.competitors.find((c) => Number(c.id) === Number(id)))
                .filter(Boolean);
        },

        get doorsSummary() {
            return this.totalIn + ' de ' + this.competitors.length + ' competidores';
        },

        /*
        |----------------------------------------------------------------------
        | Reparto a mano
        |----------------------------------------------------------------------
        */

        isPicked(startId, competitorId) {
            return (this.manual[startId] ?? []).includes(competitorId);
        },

        /* Un competidor entra por UNA puerta: marcarlo en otra lo mueve */
        pick(startId, competitorId) {
            Object.keys(this.manual).forEach((key) => {
                if (Number(key) === Number(startId)) return;

                this.manual[key] = this.manual[key].filter((id) => id !== competitorId);
            });

            const lista = this.manual[startId] ?? [];

            const i = lista.indexOf(competitorId);

            if (i === -1) lista.push(competitorId);
            else lista.splice(i, 1);

            this.manual[startId] = lista;
        },

        whereIs(competitorId) {
            const entry = Object.entries(this.manual)
                .find(([, ids]) => ids.includes(competitorId));

            if (!entry) return null;

            return this.templateStarts.find((s) => Number(s.id) === Number(entry[0]))?.name ?? null;
        },

        /*
         * Congela el orden de una puerta: los ya marcados primero.
         *
         * Se llama al abrirla y al pulsar «reordenar», nunca en cada clic.
         */
        freezeOrder(startId) {
            const dentro = this.manual[startId] ?? [];

            this.doorOrder[startId] = [...this.competitors]
                .sort((a, b) => {
                    const ia = dentro.includes(a.id) ? 0 : 1;
                    const ib = dentro.includes(b.id) ? 0 : 1;
                    return ia - ib;
                })
                .map((c) => c.id);
        },

        /* Los competidores de una puerta, en su orden congelado */
        competitorsFor(startId) {
            const orden = this.doorOrder[startId];

            const visibles = this.visibleCompetitors;

            if (!orden) return visibles;

            const porId = new Map(visibles.map((c) => [c.id, c]));

            return orden.map((id) => porId.get(id)).filter(Boolean);
        },

        /*
        |----------------------------------------------------------------------
        | Cómo se miran
        |----------------------------------------------------------------------
        */

        setView(modo) {
            this.pickerView = modo;
            this.rememberView();
        },

        setSize(n) {
            this.pickerSize = Math.max(1, Math.min(6, Number(n)));
            this.rememberView();
        },

        /*
         * Cuántas columnas tiene la rejilla, según modo y tamaño.
         *
         * Literales y no interpolación: Tailwind lee el código fuente, así
         * que una clase construida al vuelo no llega nunca al CSS.
         */
        get pickerGrid() {
            if (this.pickerView === 'LIST') return 'grid-cols-1';

            const cuadritos = [
                'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                'grid-cols-5 sm:grid-cols-8 lg:grid-cols-10',
                'grid-cols-6 sm:grid-cols-10 lg:grid-cols-12',
                'grid-cols-7 sm:grid-cols-11 lg:grid-cols-[repeat(14,minmax(0,1fr))]',
                'grid-cols-8 sm:grid-cols-12 lg:grid-cols-[repeat(16,minmax(0,1fr))]',
                'grid-cols-10 sm:grid-cols-[repeat(14,minmax(0,1fr))] lg:grid-cols-[repeat(20,minmax(0,1fr))]',
            ];

            const galeria = [
                'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                'grid-cols-2 sm:grid-cols-4 lg:grid-cols-5',
                'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                'grid-cols-3 sm:grid-cols-6 lg:grid-cols-9',
                'grid-cols-4 sm:grid-cols-7 lg:grid-cols-11',
                'grid-cols-5 sm:grid-cols-8 lg:grid-cols-12',
            ];

            const escala = this.pickerView === 'TILES' ? cuadritos : galeria;

            return escala[this.pickerSize - 1] ?? escala[2];
        },

        rememberView() {
            try {
                localStorage.setItem(
                    'omnimerge.competidores.vista',
                    JSON.stringify({ view: this.pickerView, size: this.pickerSize })
                );
            } catch (e) {
                /* Sin almacenamiento se sigue eligiendo, solo no se recuerda */
            }
        },

        recallView() {
            try {
                const guardado = JSON.parse(
                    localStorage.getItem('omnimerge.competidores.vista') ?? 'null'
                );

                if (guardado?.view) this.pickerView = guardado.view;
                if (guardado?.size) this.pickerSize = guardado.size;
            } catch (e) {
                /* Un valor corrupto no debe dejar la pantalla sin galería */
            }
        },

        /* Los atributos de un competidor, ya legibles */
        chipsOf(competitor) {
            return (competitor.attributes ?? []).flatMap((a) =>
                (a.labels ?? a.values ?? []).map((v) => ({
                    key: a.name + ':' + v,
                    attribute: a.label ?? a.name,
                    value: v,
                }))
            );
        },

        get visibleCompetitors() {
            const q = this.search.trim().toLowerCase();

            if (!q) return this.competitors;

            return this.competitors.filter((c) =>
                c.name.toLowerCase().includes(q)
                || c.attributes.some((a) =>
                    a.name.includes(q) || a.values.some((v) => v.includes(q))
                )
            );
        },

        pickVisible(startId, value) {
            this.visibleCompetitors.forEach((c) => {
                const dentro = this.isPicked(startId, c.id);

                if (value && !dentro) this.pick(startId, c.id);
                if (!value && dentro) this.pick(startId, c.id);
            });
        },
    };
}

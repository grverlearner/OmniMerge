/*
|--------------------------------------------------------------------------
| El diseñador de un torneo oficial
|--------------------------------------------------------------------------
|
| Un torneo de universo es una MARCA: "la Copa". Lo que se configura aquí es
| lo que todas sus ediciones heredan salvo que una diga otra cosa.
|
| La pantalla son seis bloques y este módulo los sostiene:
|
|   identidad    nombre, descripción, cara
|   el juego     uno fijo para siempre, o uno distinto por edición
|   la batalla   cuántos caben, cuántos juegos, y cómo se decide quién gana
|   temporadas   cada cuánto aparece
|   premios      trofeo y recompensas
|   quién entra  reglas por atributos de los competidores del universo
|
| Lo único que habla con el servidor es la vista previa de participantes:
| sin ver quién queda dentro, elegir atributos es escribir a ciegas.
|
*/

export default function tournamentDesigner(config) {

    return {

        /* Qué bloque está abierto. Uno cada vez: son largos */
        open: config.open ?? 'identity',

        games: config.games ?? [],

        catalog: config.catalog ?? [],

        previewUrl: config.previewUrl,

        csrf: config.csrf,


        /*
        |----------------------------------------------------------------
        | Lo que se está configurando
        |----------------------------------------------------------------
        */

        gameMode: config.gameMode ?? 'SINGLE',
        gameKey: config.gameKey ?? '',

        battleParticipants: config.battleParticipants ?? '',

        seriesFormat: config.seriesFormat ?? 'BEST_OF',
        bestOf: config.bestOf ?? 3,
        fixedGames: config.fixedGames ?? 2,

        decisionMode: config.decisionMode ?? 'SERIES_THEN_POINTS',
        allowDraws: config.allowDraws ?? false,

        /*
         * Si una edicion puede bajar estas decisiones a cada fase. Es
         * distinto de gameMode: aquel dice si el juego cambia ENTRE
         * ediciones, esto si puede cambiar DENTRO de una.
         */
        allowPhaseGame: config.allowPhaseGame ?? false,
        allowPhaseBattle: config.allowPhaseBattle ?? false,

        recurrenceMode: config.recurrenceMode ?? 'EVERY_SEASON',

        eligibilityMode: config.eligibilityMode ?? 'ALL',
        rules: config.rules ?? [],

        preview: config.preview ?? { total: 0, matching: 0, sample: [] },

        /*
         * Todos los competidores del universo, con sus atributos.
         *
         * Se filtran AQUI para que la galería responda en el acto: al
         * marcar un valor del catálogo no puede haber un viaje al servidor
         * de por medio, porque entonces no es en el acto.
         */
        roster: config.roster ?? [],

        /*
         * Grupos: una condición con su propio modo dentro.
         *
         * Un solo nivel a propósito. Con esto ya se escribe «(A y B) o (C)»,
         * que es hasta donde llega lo que alguien quiere expresar de verdad;
         * grupos dentro de grupos darían una pantalla que nadie sabría leer.
         */
        groups: config.groups ?? [],

        /*
         * Y la mano. Ninguna regla escrita con atributos va a capturar
         * «este sí, porque lo digo yo».
         */
        include: config.include ?? [],
        exclude: config.exclude ?? [],

        pickerOpen: false,
        pickerSearch: '',

        loadingPreview: false,

        previewTimer: null,


        init() {
            /* Un torneo nuevo sin juego elegido arranca con el del universo */
            if (!this.gameKey && this.games.length) {
                this.gameKey = config.defaultGameKey || this.games[0].key;
            }
        },

        toggle(section) {
            this.open = this.open === section ? null : section;
        },

        isOpen(section) {
            return this.open === section;
        },


        /*
        |----------------------------------------------------------------
        | El juego
        |----------------------------------------------------------------
        */

        get game() {
            return this.games.find((g) => g.key === this.gameKey) ?? null;
        },

        pickGame(key) {
            this.gameKey = key;
        },

        /*
         * Cuántos caben en una batalla, según el juego.
         *
         * Cada juego declara su mínimo y su máximo: Highest Number admite
         * de 2 en adelante, y otro podría exigir exactamente 2. Ofrecer un
         * número que el juego no admite es ofrecer una batalla que no se
         * puede jugar.
         */
        get participantChoices() {
            const min = this.game?.minimum_participants ?? 2;
            const max = this.game?.maximum_participants ?? 8;

            const out = [];

            for (let n = min; n <= Math.min(max, 8); n++) out.push(n);

            return out;
        },

        get gameSummary() {
            if (!this.game) return 'Sin juego elegido';

            return this.gameMode === 'VARIED'
                ? 'Cada edición elige · sugerido ' + this.game.name
                : this.game.name;
        },


        /*
        |----------------------------------------------------------------
        | La batalla
        |----------------------------------------------------------------
        */

        get battleSummary() {
            const juegos = this.seriesFormat === 'FIXED_GAMES'
                ? (this.fixedGames === 1 ? 'un juego' : this.fixedGames + ' juegos fijos')
                : (this.bestOf === 1 ? 'un juego' : 'al mejor de ' + this.bestOf);

            const quienes = this.battleParticipants
                ? this.battleParticipants + ' por batalla'
                : 'según la fase';

            return quienes + ' · ' + juegos;
        },

        /*
         * El ejemplo dibujado del modo de decisión.
         *
         * Está elegido a propósito para que los dos modos den GANADORES
         * DISTINTOS: con un ejemplo donde ambos coinciden no se entiende
         * qué se está eligiendo.
         *
         *   juego 1   A gana 3–2
         *   juego 2   B gana 0–3
         *   juego 3   A gana 2–1
         *
         *   marcador     2–1  → gana A
         *   anotaciones  5–6  → gana B
         */
        get example() {
            const games = [
                { n: 1, a: 3, b: 2 },
                { n: 2, a: 0, b: 3 },
                { n: 3, a: 2, b: 1 },
            ];

            const wins = games.reduce(
                (acc, g) => ({
                    a: acc.a + (g.a > g.b ? 1 : 0),
                    b: acc.b + (g.b > g.a ? 1 : 0),
                }),
                { a: 0, b: 0 }
            );

            const points = games.reduce(
                (acc, g) => ({ a: acc.a + g.a, b: acc.b + g.b }),
                { a: 0, b: 0 }
            );

            return {
                games,
                wins,
                points,

                bySeries: wins.a === wins.b
                    ? (points.a === points.b ? null : (points.a > points.b ? 'A' : 'B'))
                    : (wins.a > wins.b ? 'A' : 'B'),

                byPoints: points.a === points.b
                    ? null
                    : (points.a > points.b ? 'A' : 'B'),
            };
        },

        get exampleWinner() {
            return this.decisionMode === 'POINTS_ONLY'
                ? this.example.byPoints
                : this.example.bySeries;
        },


        /*
        |----------------------------------------------------------------
        | Quién puede competir
        |----------------------------------------------------------------
        */

        attributeOf(name) {
            return this.catalog.find((a) => a.name === name) ?? null;
        },

        /* Los atributos que todavía no se están usando en ninguna regla */
        get availableAttributes() {
            const used = this.rules.map((r) => r.attribute);

            return this.catalog.filter((a) => !used.includes(a.name));
        },

        addRule(name) {
            if (!name || this.rules.some((r) => r.attribute === name)) return;

            this.rules.push({ attribute: name, values: [] });

            this.schedulePreview();
        },

        removeRule(index) {
            this.rules.splice(index, 1);

            this.schedulePreview();
        },

        toggleValue(index, value) {
            const rule = this.rules[index];

            if (!rule) return;

            rule.values = rule.values.includes(value)
                ? rule.values.filter((v) => v !== value)
                : [...rule.values, value];

            this.schedulePreview();
        },

        hasValue(index, value) {
            return (this.rules[index]?.values ?? []).includes(value);
        },

        /*
         * Cómo se lee una regla: "tiene Doujutsu" o "Doujutsu · Sharingan".
         *
         * Sin valores concretos la regla es "que lo tenga, con el valor que
         * sea", y decirlo así evita confundirla con una regla vacía.
         */
        ruleText(index) {
            const rule = this.rules[index];

            if (!rule) return '';

            const attribute = this.attributeOf(rule.attribute);
            const label = attribute?.label ?? rule.attribute;

            if (rule.values.length === 0) return 'Tiene ' + label;

            const names = rule.values.map(
                (v) => attribute?.values.find((o) => o.value === v)?.label ?? v
            );

            return label + ' · ' + names.join(' o ');
        },

        /*
         * Cuánta gente deja fuera la regla actual. Se pide al servidor
         * porque quien sabe de verdad quién cumple qué es el mismo servicio
         * que lo aplicará al montar la competición: si lo calculara aquí,
         * la pantalla y el torneo podrían no coincidir.
         */
        schedulePreview() {
            clearTimeout(this.previewTimer);

            this.previewTimer = setTimeout(() => this.refreshPreview(), 250);
        },

        async refreshPreview() {
            this.loadingPreview = true;

            try {
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        mode: this.eligibilityMode,
                        rules: this.rules,
                    }),
                });

                this.preview = await response.json();
            } catch (e) {
                /* Sin respuesta se conserva la última: mejor que vaciarla */
            } finally {
                this.loadingPreview = false;
            }
        },

        setEligibilityMode(mode) {
            this.eligibilityMode = mode;

            this.schedulePreview();
        },

        /* Cómo se lee el modo, para explicarlo donde se elige */
        modeHelp(mode) {
            return {
                ALL: 'Hay que cumplirlas todas.',
                ANY: 'Basta con cumplir una.',
                NONE: 'No se puede cumplir ninguna.',
                ONE: 'Exactamente una, ni ninguna ni dos.',
            }[mode] ?? '';
        },

        get eligibilitySummary() {
            const abierto = this.rules.length === 0
                && this.groups.length === 0
                && this.handCount === 0;

            if (abierto) {
                return 'Abierto a los ' + this.roster.length;
            }

            return this.matching.length + ' de ' + this.roster.length + ' competidores';
        },

        /* La galería enseña a los que entran o a los que no */
        rosterView: 'IN',

        get shown() {
            return this.rosterView === 'OUT' ? this.excluded : this.matching;
        },

        /* Ninguno cumple: la regla existe pero deja el torneo vacío */
        get eligibilityEmpty() {
            return (this.rules.length > 0 || this.groups.length > 0)
                && this.matching.length === 0;
        },

        /*
        |----------------------------------------------------------------------
        | Quiénes quedan dentro, calculado aquí
        |----------------------------------------------------------------------
        |
        | Espeja lo que hace UniverseTournamentEligibility::passes() en el
        | servidor. Esa duplicación es deliberada: sin ella, marcar un valor
        | y ver el resultado costaría una petición, y la galería iría
        | siempre un paso por detrás de lo que se acaba de tocar.
        |
        | El servidor sigue siendo la autoridad. refreshPreview() se sigue
        | llamando, y si su recuento no coincide con este, la pantalla lo
        | dice en vez de esconderlo.
        */

        /* Si este competidor cumple una regla concreta */
        passesRule(competitor, rule) {
            const owned = competitor.attributes.find((a) => a.name === rule.attribute);

            if (!owned) return false;

            /* Sin valores marcados basta con tener el atributo */
            if (rule.values.length === 0) return true;

            return rule.values.some((v) => owned.values.includes(v));
        },

        /*
         * Cómo se combinan varias condiciones. Espeja combine() del
         * servidor: mismos cuatro modos, y «sin condiciones entra todo el
         * mundo».
         */
        combine(mode, results) {
            if (results.length === 0) return true;

            if (mode === 'ANY') return results.includes(true);
            if (mode === 'NONE') return !results.includes(true);

            /* Exactamente una: ni ninguna ni dos */
            if (mode === 'ONE') return results.filter(Boolean).length === 1;

            return !results.includes(false);
        },

        get matching() {
            const abierto = this.rules.length === 0
                && this.groups.length === 0
                && this.include.length === 0
                && this.exclude.length === 0;

            if (abierto) return this.roster;

            return this.roster.filter((c) => {

                /* La mano gana, y excluir gana sobre incluir */
                if (this.exclude.includes(c.id)) return false;
                if (this.include.includes(c.id)) return true;

                const results = this.rules.map((r) => this.passesRule(c, r));

                this.groups.forEach((g) => {
                    results.push(this.combine(
                        g.mode,
                        g.rules.map((r) => this.passesRule(c, r))
                    ));
                });

                return this.combine(this.eligibilityMode, results);
            });
        },

        /*
        |----------------------------------------------------------------------
        | Los grupos
        |----------------------------------------------------------------------
        */

        addGroup() {
            this.groups.push({ mode: 'ALL', rules: [] });
        },

        removeGroup(i) {
            this.groups.splice(i, 1);
            this.schedulePreview();
        },

        addGroupRule(i, name) {
            if (!name) return;

            const grupo = this.groups[i];

            if (grupo.rules.some((r) => r.attribute === name)) return;

            grupo.rules.push({ attribute: name, values: [] });
            this.schedulePreview();
        },

        removeGroupRule(i, ri) {
            this.groups[i].rules.splice(ri, 1);
            this.schedulePreview();
        },

        toggleGroupValue(i, ri, value) {
            const rule = this.groups[i].rules[ri];

            rule.values = rule.values.includes(value)
                ? rule.values.filter((v) => v !== value)
                : [...rule.values, value];

            this.schedulePreview();
        },

        hasGroupValue(i, ri, value) {
            return (this.groups[i]?.rules[ri]?.values ?? []).includes(value);
        },

        setGroupMode(i, mode) {
            this.groups[i].mode = mode;
            this.schedulePreview();
        },

        /* Cómo se lee un grupo, para poder verlo de un vistazo */
        groupText(i) {
            const g = this.groups[i];

            if (!g || g.rules.length === 0) return 'grupo vacío';

            const partes = g.rules.map((r) => {
                const attr = this.attributeOf(r.attribute);
                const label = attr?.label ?? r.attribute;

                if (r.values.length === 0) return 'tiene ' + label;

                return label + ' ' + r.values
                    .map((v) => attr?.values.find((o) => o.value === v)?.label ?? v)
                    .join('/');
            });

            const nexo = { ALL: ' y ', ANY: ' o ', NONE: ' ni ', ONE: ' xor ' }[g.mode] ?? ' y ';

            return (g.mode === 'NONE' ? 'ni ' : '') + partes.join(nexo);
        },

        /*
        |----------------------------------------------------------------------
        | A dedo
        |----------------------------------------------------------------------
        */

        isIncluded(id) { return this.include.includes(id); },
        isExcluded(id) { return this.exclude.includes(id); },

        /*
         * Marcar a alguien lo mueve entre tres estados: normal → dentro →
         * fuera → normal. Un solo botón, porque uno para incluir y otro
         * para excluir en cada ficha duplica la rejilla entera.
         */
        cycleHand(id) {
            if (this.isIncluded(id)) {
                this.include = this.include.filter((x) => x !== id);
                this.exclude = [...this.exclude, id];
            } else if (this.isExcluded(id)) {
                this.exclude = this.exclude.filter((x) => x !== id);
            } else {
                this.include = [...this.include, id];
            }

            this.schedulePreview();
        },

        handState(id) {
            if (this.isIncluded(id)) return 'IN';
            if (this.isExcluded(id)) return 'OUT';
            return 'RULE';
        },

        clearHand() {
            this.include = [];
            this.exclude = [];
            this.schedulePreview();
        },

        get handCount() {
            return this.include.length + this.exclude.length;
        },

        get pickerList() {
            const q = this.pickerSearch.trim().toLowerCase();

            if (!q) return this.roster;

            return this.roster.filter((c) =>
                c.name.toLowerCase().includes(q)
                || c.attributes.some((a) =>
                    a.name.includes(q) || a.values.some((v) => v.includes(q))
                )
            );
        },

        get excluded() {
            const dentro = this.matching.map((c) => c.id);

            return this.roster.filter((c) => !dentro.includes(c.id));
        },

        /*
         * Qué valores de este competidor son los que le dejan entrar.
         *
         * Sirve para encender justo esas etiquetas en su ficha: ver el
         * número de los que pasan no dice POR QUÉ pasan, y con dos reglas
         * encima eso deja de ser evidente.
         */
        matchedValues(competitor) {
            const claves = [];

            this.rules.forEach((rule) => {
                if (!this.passesRule(competitor, rule)) return;

                const owned = competitor.attributes.find((a) => a.name === rule.attribute);

                if (!owned) return;

                owned.values.forEach((v, i) => {
                    if (rule.values.length === 0 || rule.values.includes(v)) {
                        claves.push(owned.name + ':' + v);
                    }
                });
            });

            return claves;
        },

        isMatched(competitor, attribute, index) {
            return this.matchedValues(competitor)
                .includes(attribute.name + ':' + attribute.values[index]);
        },

        /*
         * El servidor contó otra cosa.
         *
         * No debería pasar nunca; si pasa, es que el espejo de arriba se
         * desalineó del servicio real, y callarlo dejaría al usuario
         * creando un torneo con menos gente de la que ve.
         */
        get previewDisagrees() {
            return !this.loadingPreview
                && this.preview.total === this.roster.length
                && this.preview.matching !== this.matching.length;
        },
    };
}

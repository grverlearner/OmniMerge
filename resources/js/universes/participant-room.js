/*
|--------------------------------------------------------------------------
| La sala de participantes
|--------------------------------------------------------------------------
|
| Quién entra en un torneo, por qué puerta y con qué cara.
|
| Todo se calcula aquí, en el acto, con las MISMAS reglas que el servidor:
|
|   quién entra     UniverseTournamentEligibility
|   por qué puerta  CompetitionStartRouting
|   con qué cara    UniverseEntityVersionResolver
|   en una edición  EditionParticipants
|
| Dos contextos con el mismo componente:
|
|   TOURNAMENT  la sala del torneo, que se guarda en el torneo
|   EDITION     la de una edición, dentro de su diseñador. Usa lo del torneo
|               (source TOURNAMENT) o tiene lo suyo (source CUSTOM), que
|               parte de lo del torneo o del universo entero (scope).
|
| Ver docs/md/79-Sala-De-Participantes.md y 80-Participantes-De-Cada-Edicion.md
|
*/

const PALETA = ['#f43f5e', '#38bdf8', '#a3e635', '#f59e0b', '#a78bfa', '#2dd4bf', '#fb923c', '#e879f9'];

const VISTAS = ['stage', 'doors', 'values', 'list'];

const UNIONES = { ALL: ' y ', ANY: ' o ', NONE: ' ni ', ONE: ' o bien ' };

const clone = (x) => JSON.parse(JSON.stringify(x ?? null));

function normalizeDoors(d) {
    const manual = {};

    Object.entries(d?.manual ?? {}).forEach(([puerta, ids]) => {
        manual[Number(puerta)] = (ids ?? []).map(Number);
    });

    return {
        mode: d?.mode ?? 'AUTO',
        strategy: d?.strategy ?? 'BALANCED',
        attribute: d?.attribute ?? '',
        value_doors: { ...(Array.isArray(d?.value_doors) ? {} : (d?.value_doors ?? {})) },
        seed: Number(d?.seed ?? 1) || 1,
        fill_rest: Boolean(d?.fill_rest),
        rules: clone(d?.rules ?? []).map((r) => ({
            start_id: Number(r.start_id),
            mode: r.mode ?? 'ALL',
            rules: r.rules ?? [],
            groups: r.groups ?? [],
            include: r.include ?? [],
            exclude: r.exclude ?? [],
        })),
        manual,
        template_id: d?.template_id ? Number(d.template_id) : null,
    };
}

function normalizeRules(d) {
    return {
        mode: d?.mode ?? 'ALL',
        rules: clone(d?.rules ?? []),
        groups: clone(d?.groups ?? []),
        include: [...(d?.include ?? [])].map(Number),
        exclude: [...(d?.exclude ?? [])].map(Number),
        faces: { ...(Array.isArray(d?.faces) ? {} : (d?.faces ?? {})) },
        face_mode: d?.face_mode ?? 'AUTO',
    };
}

function normalizeStarts(starts) {
    return (starts ?? []).map((s) => ({
        id: Number(s.id),
        name: s.name || 'Entrada',
        code: s.code ?? null,
        description: s.description ?? null,
        capacity: s.capacity ? Number(s.capacity) : null,
        /* A que fases lleva y cuantos piden: lo que permite avisar en el acto */
        feeds: Array.isArray(s.feeds) ? s.feeds : [],
    }));
}

export default function participantRoom(sala) {
    const d = sala.design ?? {};
    const reglas = normalizeRules(d);

    return {
        context: sala.context ?? 'TOURNAMENT',
        tournament: sala.tournament ?? {},
        catalog: sala.catalog ?? [],
        roster: sala.roster ?? [],
        starts: normalizeStarts(sala.starts),
        templateId: Number(sala.templateId ?? sala.tournament?.template_id ?? 0),
        modes: sala.modes ?? {},
        fromLabels: sala.fromLabels ?? {},
        server: sala.server ?? {},

        /* Lo del torneo, en una edición */
        base: sala.base ? { ...normalizeRules(sala.base), doors: normalizeDoors(sala.base.doors) } : null,
        baseIn: {},

        /* ---------------------------------------------- lo que se decide */

        source: d.source ?? 'TOURNAMENT',
        scope: d.scope ?? 'TOURNAMENT',
        mode: reglas.mode,
        rules: reglas.rules,
        groups: reglas.groups,
        include: reglas.include,
        exclude: reglas.exclude,
        faces: reglas.faces,
        faceMode: reglas.face_mode,
        doors: normalizeDoors(d.doors),

        /* ------------------------------------------------ la pantalla */

        panel: 'who',
        view: 'stage',
        size: 7,
        search: '',
        issuesOnly: false,
        faceFilter: null,
        groupAttr: null,
        openAttr: null,
        attrSearch: '',
        ficha: null,
        brush: null,
        doorRulePick: {},

        roomOpen: false,
        snapshot: '',
        customized: false,
        notice: '',

        /* El ensayo del diseño en pantalla: se juega en memoria en el servidor */
        rehearsal: { loading: false, ok: null, problem: null, forJson: null },

        saved: '',
        submitting: false,
        serverMismatch: false,
        tree: {},
        calc: { inMap: {}, reasons: {}, plan: { assignments: {}, leftovers: [], overflow: {} }, doorOf: {}, faces: {}, count: 0 },

        init() {
            this.roster.forEach((c) => {
                c.owned = Object.fromEntries((c.attributes ?? []).map((a) => [a.name, a.values ?? []]));
            });

            this.buildTree();
            this.baseIn = this.computeIn(this.base);
            this.customized = this.source === 'CUSTOM';

            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.sala') ?? '{}');
                if (VISTAS.includes(g.view)) this.view = g.view;
                if (g.size >= 4 && g.size <= 12) this.size = g.size;
                if (['who', 'doors', 'faces'].includes(g.panel)) this.panel = g.panel;
            } catch (e) {
                /* sin preferencias guardadas */
            }

            ['view', 'size', 'panel'].forEach((k) => this.$watch(k, () => {
                localStorage.setItem('omnimerge.sala', JSON.stringify({ view: this.view, size: this.size, panel: this.panel }));
            }));

            this.groupAttr = this.catalog[0]?.name ?? null;

            this.saved = this.designJson;
            this.recalc();

            this.$watch('designJson', () => this.recalc());

            if (this.isEdition) {
                /* Con la sala abierta, la página de debajo no se mueve */
                this.$watch('roomOpen', (abierta) => document.documentElement.classList.toggle('overflow-hidden', abierta));
                return;
            }

            this.checkServer();

            /* Salir con cambios: el modal de OmniMerge (ver OmniUnsaved en app.js) */
            window.OmniUnsaved?.watch(() => this.dirty && !this.submitting);
        },

        /*
        |----------------------------------------------------------------------
        | El diseño, tal y como viaja
        |----------------------------------------------------------------------
        */

        get design() {
            const diseno = {
                mode: this.mode,
                rules: this.rules,
                groups: this.groups,
                include: this.include,
                exclude: this.exclude,
                faces: this.faces,
                face_mode: this.faceMode,
                doors: this.doors,
            };

            return this.isEdition ? { source: this.source, scope: this.scope, ...diseno } : diseno;
        },

        get designJson() {
            return JSON.stringify(this.design);
        },

        get dirty() {
            return this.isEdition
                ? this.roomOpen && this.designJson !== this.snapshot
                : this.designJson !== this.saved;
        },

        restore(json) {
            const s = JSON.parse(json);
            const r = normalizeRules(s);

            if (this.isEdition) {
                this.source = s.source ?? 'TOURNAMENT';
                this.scope = s.scope ?? 'TOURNAMENT';
            }

            this.mode = r.mode;
            this.rules = r.rules;
            this.groups = r.groups;
            this.include = r.include;
            this.exclude = r.exclude;
            this.faces = r.faces;
            this.faceMode = r.face_mode;
            this.doors = normalizeDoors(s.doors);
        },

        discard() {
            this.restore(this.saved);
        },

        prepareSubmit() {
            this.submitting = true;
        },

        /*
        |----------------------------------------------------------------------
        | Una edición: lo del torneo o lo suyo
        |----------------------------------------------------------------------
        */

        get isEdition() {
            return this.context === 'EDITION';
        },

        get inherits() {
            return this.isEdition && this.source === 'TOURNAMENT';
        },

        get editable() {
            return !this.inherits;
        },

        /* El reparto del torneo, si es para esta plantilla; si no, solo y equilibrado */
        get tournamentDoors() {
            const d = this.base?.doors ?? normalizeDoors(null);
            return this.doorsMismatch ? normalizeDoors(null) : d;
        },

        get doorsMismatch() {
            const d = this.base?.doors;
            return Boolean(d?.template_id && this.templateId && Number(d.template_id) !== this.templateId);
        },

        /* Las reglas con las que se elige la cara: igual que EditionParticipants::faceContext() */
        get effRules() {
            if (!this.isEdition) return this;
            if (this.inherits) return this.base;
            return this.hasConditions(this) ? this : this.base;
        },

        get effFaces() {
            if (!this.isEdition) return this.faces;
            if (this.inherits) return this.base?.faces ?? {};
            return { ...(this.base?.faces ?? {}), ...this.faces };
        },

        get effFaceMode() {
            return this.inherits ? (this.base?.face_mode ?? 'AUTO') : this.faceMode;
        },

        get effDoors() {
            return this.inherits ? this.tournamentDoors : this.doors;
        },

        useTournament() {
            this.source = 'TOURNAMENT';
            this.brush = null;
            this.notice = '';
        },

        /* Pasar a lo propio: la primera vez parte del reparto y la cara del torneo */
        customize() {
            if (!this.isEdition || this.source === 'CUSTOM') return;

            if (!this.customized) {
                this.rules = [];
                this.groups = [];
                this.include = [];
                this.exclude = [];
                this.faces = {};
                this.faceMode = this.base?.face_mode ?? 'AUTO';
                this.doors = normalizeDoors(clone(this.tournamentDoors));
                this.scope = 'TOURNAMENT';
                this.customized = true;
            }

            this.source = 'CUSTOM';
            this.notice = 'Esta edición tiene ahora su propia configuración. Parte de lo del torneo, y el torneo no se toca.';
        },

        ensureCustom() {
            if (this.inherits) this.customize();
        },

        setScope(s) {
            this.scope = s;
        },

        /* Las puertas de la forma elegida en el diseñador de la edición */
        setStarts(starts, templateId) {
            const nuevas = normalizeStarts(starts);
            const plantilla = Number(templateId) || this.templateId;

            if (JSON.stringify(nuevas) === JSON.stringify(this.starts) && plantilla === this.templateId) return;

            this.starts = nuevas;
            this.templateId = plantilla;
            this.brush = null;
            this.recalc();
        },

        openRoom(panel = null) {
            this.snapshot = this.designJson;
            this.notice = '';
            if (panel) this.panel = panel;
            this.roomOpen = true;
        },

        cancelRoom() {
            this.restore(this.snapshot);
            this.customized = this.source === 'CUSTOM' || this.customized;
            this.ficha = null;
            this.roomOpen = false;
        },

        applyRoom() {
            this.ficha = null;
            this.roomOpen = false;
        },

        /*
        |----------------------------------------------------------------------
        | El catálogo en árbol
        |----------------------------------------------------------------------
        */

        buildTree() {
            const tree = {};

            this.catalog.forEach((a) => {
                const nodos = {};
                a.values.forEach((v) => { nodos[v.value] = { parent: v.parent, children: [] }; });
                a.values.forEach((v) => {
                    if (v.parent !== null && nodos[v.parent]) nodos[v.parent].children.push(v.value);
                });
                tree[a.name] = nodos;
            });

            this.tree = tree;
        },

        expand(attr, values) {
            const nodos = this.tree[attr];
            if (!nodos) return values;

            const fuera = new Set();
            const pendientes = [...values];

            while (pendientes.length) {
                const v = pendientes.shift();
                if (fuera.has(v)) continue;
                fuera.add(v);
                (nodos[v]?.children ?? []).forEach((h) => pendientes.push(h));
            }

            return [...fuera];
        },

        attr(name) {
            return this.catalog.find((a) => a.name === name) ?? null;
        },

        attrLabel(name) {
            return this.attr(name)?.label ?? name;
        },

        valueOf(attr, value) {
            return this.attr(attr)?.values.find((v) => v.value === value) ?? null;
        },

        valueLabel(attr, value) {
            return this.valueOf(attr, value)?.label ?? value;
        },

        get filteredCatalog() {
            const q = this.attrSearch.trim().toLowerCase();
            if (!q) return this.catalog;

            return this.catalog.filter((a) => a.label.toLowerCase().includes(q)
                || a.values.some((v) => v.label.toLowerCase().includes(q)));
        },

        /*
        |----------------------------------------------------------------------
        | Quién cumple — igual que UniverseTournamentEligibility
        |----------------------------------------------------------------------
        */

        ruleHolds(rule, owned) {
            const mine = owned[rule.attribute];
            if (!mine) return false;
            if (!(rule.values ?? []).length) return true;

            const valores = rule.descendants === false ? rule.values : this.expand(rule.attribute, rule.values);

            return valores.some((v) => mine.includes(v));
        },

        combine(mode, results) {
            if (!results.length) return true;

            switch (mode) {
                case 'ANY': return results.includes(true);
                case 'NONE': return !results.includes(true);
                case 'ONE': return results.filter(Boolean).length === 1;
                default: return !results.includes(false);
            }
        },

        evaluate(owned, rs) {
            const results = (rs.rules ?? []).map((r) => this.ruleHolds(r, owned));

            (rs.groups ?? []).forEach((g) => {
                if (!(g.rules ?? []).length) return;
                results.push(this.combine(g.mode ?? 'ALL', g.rules.map((r) => this.ruleHolds(r, owned))));
            });

            return results.length ? this.combine(rs.mode ?? 'ALL', results) : true;
        },

        isOpen(rs) {
            return !(rs.rules ?? []).length
                && !(rs.groups ?? []).some((g) => (g.rules ?? []).length)
                && !(rs.include ?? []).length
                && !(rs.exclude ?? []).length;
        },

        passes(c, rs) {
            if ((rs.exclude ?? []).includes(c.id)) return false;
            if ((rs.include ?? []).includes(c.id)) return true;

            return this.evaluate(c.owned, rs);
        },

        /* Quién deja entrar un diseño, contando la mano */
        computeIn(rs) {
            if (!rs) return {};

            const abierto = this.isOpen(rs);
            const dentro = {};

            this.roster.forEach((c) => {
                if (abierto || this.passes(c, rs)) dentro[c.id] = true;
            });

            return dentro;
        },

        positiveSelections(rs) {
            const out = {};
            if (!rs || rs.mode === 'NONE') return out;

            const recoger = (lista) => (lista ?? []).forEach((r) => {
                if (!(r.values ?? []).length) return;
                const valores = r.descendants === false ? r.values : this.expand(r.attribute, r.values);
                out[r.attribute] = [...new Set([...(out[r.attribute] ?? []), ...valores])];
            });

            recoger(rs.rules);
            (rs.groups ?? []).forEach((g) => { if (g.mode !== 'NONE') recoger(g.rules); });

            return out;
        },

        hasConditions(rs) {
            return (rs.rules ?? []).length > 0 || (rs.groups ?? []).some((g) => (g.rules ?? []).length);
        },

        /*
        |----------------------------------------------------------------------
        | Todo lo que se deriva del diseño, de una vez
        |----------------------------------------------------------------------
        */

        recalc() {
            const inMap = {};
            const reasons = {};
            const pool = [];

            if (this.inherits) {
                this.roster.forEach((c) => {
                    if (this.baseIn[c.id]) {
                        inMap[c.id] = true;
                        reasons[c.id] = 'TOURNAMENT';
                        pool.push(c);
                    } else {
                        reasons[c.id] = 'TOURNAMENT_OUT';
                    }
                });
            } else {
                const rs = this.design;
                const open = this.isOpen(rs);
                const limitado = this.isEdition && this.scope === 'TOURNAMENT';

                this.roster.forEach((c) => {
                    if (limitado && !this.baseIn[c.id]) {
                        reasons[c.id] = 'TOURNAMENT_OUT';
                        return;
                    }

                    let r;

                    if (rs.exclude.includes(c.id)) r = 'HAND_OUT';
                    else if (rs.include.includes(c.id)) r = 'HAND_IN';
                    else if (open) r = 'OPEN';
                    else r = this.evaluate(c.owned, rs) ? 'RULES' : 'NO_MATCH';

                    reasons[c.id] = r;

                    if (r !== 'HAND_OUT' && r !== 'NO_MATCH') {
                        inMap[c.id] = true;
                        pool.push(c);
                    }
                });
            }

            const plan = this.planFor(pool);
            const doorOf = {};

            Object.entries(plan.assignments).forEach(([sid, ids]) => ids.forEach((id) => { doorOf[id] = Number(sid); }));

            const puertas = this.effDoors;
            const filas = {};
            puertas.rules.forEach((row) => { filas[row.start_id] = row; });

            const faces = {};
            this.roster.forEach((c) => {
                const fila = puertas.mode === 'RULES' && doorOf[c.id] ? (filas[doorOf[c.id]] ?? null) : null;
                faces[c.id] = this.faceOf(c, fila);
            });

            this.calc = { inMap, reasons, plan, doorOf, faces, count: pool.length };

            if (this.isEdition) {
                this.$dispatch('sala-resumen', {
                    entran: pool.length,
                    total: this.roster.length,
                    fuente: this.source,
                    sinPuerta: this.starts.length ? pool.filter((c) => !doorOf[c.id]).length : 0,
                });
            }
        },

        /*
        |----------------------------------------------------------------------
        | Por qué puerta — igual que CompetitionStartRouting::planWithin()
        |----------------------------------------------------------------------
        */

        hash(texto) {
            let h = 2166136261;

            for (const byte of new TextEncoder().encode(texto)) {
                h ^= byte;
                h = Math.imul(h, 16777619) >>> 0;
            }

            return h >>> 0;
        },

        /*
         * Lo fijado a mano entra primero en su puerta, en cualquier modo; el
         * reparto de siempre rellena las plazas libres. Espejo exacto de
         * CompetitionStartRouting::withPins().
         */
        planFor(pool) {
            const doors = this.effDoors;
            const starts = this.starts.map((s) => [s.id, s.capacity ?? null]);

            if (doors.mode === 'MANUAL') return this.planBase(pool, doors, starts);

            const enPool = new Set(pool.map((c) => c.id));
            const vistos = {};
            const fijados = {};
            const sobran = [];
            const libres = [];
            let hay = false;

            starts.forEach(([p, cap]) => {
                let lista = (doors.manual?.[p] ?? []).map(Number).filter((id) => enPool.has(id) && !vistos[id]);

                if (cap != null && lista.length > cap) {
                    sobran.push(...lista.slice(cap));
                    lista = lista.slice(0, cap);
                }

                lista.forEach((id) => { vistos[id] = true; });
                if (lista.length) hay = true;

                fijados[p] = lista;
                libres.push([p, cap == null ? null : cap - lista.length]);
            });

            if (!hay) return this.planBase(pool, doors, starts);

            const base = this.planBase(pool.filter((c) => !vistos[c.id]), { ...doors, manual: {} }, libres);
            const r = { assignments: {}, leftovers: [...sobran], overflow: {} };

            starts.forEach(([p], i) => {
                const auto = base.assignments[p] ?? [];
                const libre = libres[i][1];
                const sitio = libre == null ? auto.length : Math.max(0, libre);

                r.assignments[p] = [...fijados[p], ...auto.slice(0, sitio)];

                const fuera = [...auto.slice(sitio), ...(base.overflow[p] ?? [])];
                if (fuera.length) r.overflow[p] = fuera;

                r.leftovers.push(...auto.slice(sitio));
            });

            r.leftovers = [...new Set([...r.leftovers, ...(base.leftovers ?? [])])];

            return r;
        },

        planBase(pool, doors, starts) {
            const ids = pool.map((c) => c.id);
            const porId = Object.fromEntries(pool.map((c) => [c.id, c]));

            if (!starts.length) return { assignments: {}, leftovers: ids, overflow: {} };

            let mode = doors.mode;
            let strategy = doors.strategy;

            if (starts.length === 1) {
                mode = mode === 'MANUAL' ? 'MANUAL' : 'AUTO';
                if (strategy === 'BY_ATTRIBUTE') strategy = 'IN_ORDER';
            }

            const caps = Object.fromEntries(starts);
            const vacio = () => ({ assignments: Object.fromEntries(starts.map(([id]) => [id, []])), leftovers: [], overflow: {} });

            if (mode === 'MANUAL') {
                const vistos = {};
                const r = { assignments: {}, leftovers: [], overflow: {} };

                starts.forEach(([p, cap]) => {
                    let lista = (doors.manual[p] ?? []).filter((id) => porId[id] && !vistos[id]);

                    if (cap && lista.length > cap) {
                        r.overflow[p] = lista.slice(cap);
                        lista = lista.slice(0, cap);
                    }

                    lista.forEach((id) => { vistos[id] = true; });
                    r.assignments[p] = lista;
                });

                r.leftovers = ids.filter((id) => !vistos[id]);

                return r;
            }

            if (mode === 'RULES') {
                const reparto = this.routeWithin(pool, doors.rules, caps);

                if (!doors.fill_rest || !reparto.leftovers.length) return reparto;

                const restantes = reparto.leftovers;
                reparto.leftovers = [];
                reparto.overflow = {};

                return this.spread(reparto, restantes, starts, 'BALANCED', 1);
            }

            if (strategy !== 'BY_ATTRIBUTE') {
                return this.spread(vacio(), ids, starts, strategy, doors.seed);
            }

            /* por atributo */
            const reparto = vacio();
            const puertas = starts.map(([id]) => id);
            const grupos = {};
            const sinValor = [];
            const ordenSobrantes = [];

            ids.forEach((id) => {
                const v = porId[id].owned[doors.attribute]?.[0];
                if (v === undefined) sinValor.push(id);
                else (grupos[v] ??= []).push(id);
            });

            const claves = Object.keys(grupos)
                .sort((a, b) => (grupos[b].length - grupos[a].length) || (a < b ? -1 : a > b ? 1 : 0));

            let turno = 0;

            claves.forEach((v) => {
                let puerta = doors.value_doors[v] !== undefined ? Number(doors.value_doors[v]) : null;

                if (!puertas.includes(puerta)) {
                    puerta = puertas[turno % puertas.length];
                    turno++;
                }

                grupos[v].forEach((id) => {
                    const cap = caps[puerta];

                    if (cap != null && reparto.assignments[puerta].length >= cap) {
                        if (!reparto.overflow[puerta]) {
                            reparto.overflow[puerta] = [];
                            ordenSobrantes.push(puerta);
                        }
                        reparto.overflow[puerta].push(id);
                    } else {
                        reparto.assignments[puerta].push(id);
                    }
                });
            });

            reparto.leftovers = [...ordenSobrantes.flatMap((p) => reparto.overflow[p]), ...sinValor];

            if (doors.fill_rest && reparto.leftovers.length) {
                const restantes = reparto.leftovers;
                reparto.leftovers = [];
                reparto.overflow = {};

                return this.spread(reparto, restantes, starts, 'BALANCED', 1);
            }

            return reparto;
        },

        routeWithin(pool, filas, caps) {
            const tomados = {};
            const assignments = {};
            const overflow = {};

            filas.forEach((row) => {
                const libres = pool.filter((c) => !tomados[c.id]);
                const casan = this.isOpen(row) ? libres : libres.filter((c) => this.passes(c, row));

                let ids = casan.map((c) => c.id);
                const cap = caps[row.start_id];

                if (cap && ids.length > cap) {
                    overflow[row.start_id] = ids.slice(cap);
                    ids = ids.slice(0, cap);
                }

                ids.forEach((id) => { tomados[id] = true; });
                assignments[row.start_id] = ids;
            });

            return {
                assignments,
                leftovers: pool.filter((c) => !tomados[c.id]).map((c) => c.id),
                overflow,
            };
        },

        spread(reparto, ids, starts, strategy, seed) {
            const lista = [...ids];

            if (strategy === 'RANDOM') {
                lista.sort((a, b) => (this.hash(`${seed}:${a}`) - this.hash(`${seed}:${b}`)) || (a - b));
            }

            const puertas = starts.map(([id]) => id);
            const caps = Object.fromEntries(starts);
            const restantes = [];
            let turno = 0;

            lista.forEach((id) => {
                let colocado = false;

                for (let i = 0; i < puertas.length; i++) {
                    const indice = strategy === 'IN_ORDER' ? i : (turno + i) % puertas.length;
                    const puerta = puertas[indice];
                    const cap = caps[puerta];

                    reparto.assignments[puerta] ??= [];

                    if (cap == null || reparto.assignments[puerta].length < cap) {
                        reparto.assignments[puerta].push(id);
                        colocado = true;
                        turno = indice + 1;
                        break;
                    }
                }

                if (!colocado) restantes.push(id);
            });

            reparto.leftovers = [...(reparto.leftovers ?? []), ...restantes];

            return reparto;
        },

        /*
        |----------------------------------------------------------------------
        | Con qué cara — igual que UniverseEntityVersionResolver::choose()
        |----------------------------------------------------------------------
        */

        faceOf(c, puerta, ignorarMano = false) {
            const versiones = c.versions ?? [];
            const manual = ignorarMano ? {} : { ...this.effFaces, ...(puerta?.faces ?? {}) };

            const conVersion = (v, from) => ({
                name: v.name || c.name,
                image_url: v.image_url || c.image_url,
                version_id: v.id,
                version_name: v.version_name || v.name,
                from,
                image_missing: !v.image_url,
            });

            const sinVersion = (from) => ({
                name: c.name,
                image_url: c.image_url,
                version_id: null,
                version_name: null,
                from,
                image_missing: !c.image_url,
            });

            if (Object.prototype.hasOwnProperty.call(manual, c.id)) {
                const elegida = Number(manual[c.id]);
                if (elegida === 0) return sinVersion('MANUAL');

                const v = versiones.find((x) => x.id === elegida);
                if (v) return conVersion(v, 'MANUAL');
            }

            if (!versiones.length) return sinVersion('ENTITY');

            if (this.effFaceMode !== 'BASE') {
                for (const reglas of [puerta, this.effRules].filter(Boolean)) {

                    const pedidos = this.positiveSelections(reglas);

                    if (Object.keys(pedidos).length) {
                        const activadas = versiones
                            .map((v) => ({ v, puntos: this.activationScore(v, pedidos) }))
                            .filter((x) => x.puntos > 0)
                            .sort((a, b) => (b.puntos - a.puntos)
                                || ((b.v.priority ?? 0) - (a.v.priority ?? 0))
                                || (Number(b.v.is_base) - Number(a.v.is_base)));

                        if (activadas.length) return conVersion(activadas[0].v, 'CATALOG');
                    }

                    if (this.hasConditions(reglas)) {
                        const casan = versiones.filter((v) => this.evaluate(v.owned ?? {}, reglas));

                        if (casan.length && casan.length < versiones.length) {
                            const mejor = [...casan].sort((a, b) => ((b.priority ?? 0) - (a.priority ?? 0)) || (Number(b.is_base) - Number(a.is_base)))[0];
                            return conVersion(mejor, 'ATTRIBUTES');
                        }
                    }
                }
            }

            const base = versiones.find((v) => v.is_base);
            if (base) return conVersion(base, 'BASE');

            const defecto = versiones.find((v) => v.is_default);
            if (defecto) return conVersion(defecto, 'DEFAULT');

            return sinVersion('ENTITY');
        },

        activationScore(v, pedidos) {
            const grupos = {};
            (v.activation ?? []).forEach((l) => { (grupos[l.group] ??= []).push(l); });

            let mejor = 0;

            Object.values(grupos).forEach((grupo) => {
                let resultado = null;
                let casados = 0;

                grupo.forEach((l) => {
                    const casa = (pedidos[l.attribute] ?? []).includes(l.value);
                    casados += casa ? 1 : 0;
                    resultado = resultado === null ? casa : (l.operator === 'OR' ? (resultado || casa) : (resultado && casa));
                });

                if (resultado) mejor = Math.max(mejor, casados);
            });

            return mejor;
        },

        /* Si la pantalla y el servidor cuentan lo mismo, recién abierta */
        checkServer() {
            const ordenar = (lista) => [...(lista ?? [])].map(Number).sort((a, b) => a - b).join(',');

            const plan = (p) => JSON.stringify(this.starts.map((s) => (p?.assignments?.[s.id] ?? []).map(Number)));

            const entran = ordenar(this.roster.filter((c) => this.calc.inMap[c.id]).map((c) => c.id));

            const caras = this.roster
                .filter((c) => this.calc.inMap[c.id])
                .some((c) => (this.server.faces?.[c.id]?.version_id ?? null) !== (this.calc.faces[c.id]?.version_id ?? null));

            this.serverMismatch = entran !== ordenar(this.server.matching)
                || plan(this.calc.plan) !== plan(this.server.plan)
                || caras;
        },

        /*
        |----------------------------------------------------------------------
        | Lecturas para la pantalla
        |----------------------------------------------------------------------
        */

        isIn(id) {
            return Boolean(this.calc.inMap[id]);
        },

        face(c) {
            return this.calc.faces[c.id] ?? { name: c.name, image_url: c.image_url, from: 'ENTITY', image_missing: !c.image_url };
        },

        reasonText(id) {
            return {
                HAND_IN: 'Metido a mano',
                HAND_OUT: 'Sacado a mano',
                OPEN: this.isEdition && this.scope === 'TOURNAMENT' ? 'Sin condiciones propias: lo permite el torneo' : 'Sin condiciones: entra todo el universo',
                RULES: 'Cumple las condiciones',
                NO_MATCH: 'No cumple las condiciones',
                TOURNAMENT: 'Lo permite el torneo',
                TOURNAMENT_OUT: 'No cumple las reglas del torneo',
            }[this.calc.reasons[id]] ?? '';
        },

        /* Una regla dicha para una persona */
        ruleText(rs) {
            if (!rs) return '';

            const una = (r) => ((r.values ?? []).length
                ? `${this.attrLabel(r.attribute)} → ${r.values.map((v) => this.valueLabel(r.attribute, v)).join(' o ')}`
                : `tiene ${this.attrLabel(r.attribute)}`);

            const partes = (rs.rules ?? []).map(una);

            (rs.groups ?? []).filter((g) => (g.rules ?? []).length).forEach((g) => {
                partes.push('(' + g.rules.map(una).join(UNIONES[g.mode] ?? ' y ') + ')');
            });

            if (!partes.length) return 'Sin condiciones';

            const prefijo = { NONE: 'Ninguno de: ', ONE: 'Solo una de: ' }[rs.mode] ?? '';

            return prefijo + partes.join(UNIONES[rs.mode] ?? ' y ');
        },

        doorsText(d) {
            if (!this.starts.length) return 'La forma elegida no tiene puertas';
            if (this.starts.length === 1) return 'Una sola puerta: todos por ella';

            if (d.mode === 'RULES') return 'Una condición por puerta';
            if (d.mode === 'MANUAL') return 'Repartido a mano';

            return {
                BALANCED: 'Se reparte solo, equilibrado',
                IN_ORDER: 'Se reparte solo, en orden',
                RANDOM: 'Se reparte solo, al azar',
                BY_ATTRIBUTE: `Se reparte solo, por ${this.attrLabel(d.attribute) || 'atributo'}`,
            }[d.strategy] ?? 'Se reparte solo';
        },

        get totalIn() {
            return this.calc.count;
        },

        get totalOut() {
            return this.roster.length - this.calc.count;
        },

        get placed() {
            return Object.values(this.calc.plan.assignments).reduce((s, ids) => s + ids.length, 0);
        },

        /*
         * Cuantos le llegan a cada fase de entrada con el reparto de ahora.
         *
         * Cada puerta lleva a una o varias fases: a todas, «toma N» o un
         * porcentaje, en su orden. Y cada fase pide un minimo y un maximo.
         * Sin esto la sala decia «caben 10» con 9 dentro y nadie se enteraba
         * de que la fase no iba a arrancar hasta crear la edicion.
         */
        get phaseNeeds() {
            const nodos = {};

            this.starts.forEach((s) => {
                const total = this.doorCount(s.id);
                let quedan = total;

                (s.feeds ?? []).forEach((f) => {
                    let toma;

                    if (f.mode === 'TAKE_N') toma = Math.min(Number(f.value ?? 0), quedan);
                    else if (f.mode === 'PERCENTAGE') toma = Math.min(Math.floor((total * Number(f.value ?? 0)) / 100), quedan);
                    else toma = quedan;

                    quedan -= toma;

                    const n = (nodos[f.node_id] ??= { node_id: f.node_id, name: f.node_name, min: f.min, max: f.max, llegan: 0, puertas: [] });

                    n.llegan += toma;

                    if (!n.puertas.includes(s.id)) n.puertas.push(s.id);
                });
            });

            return Object.values(nodos).map((n) => ({
                ...n,
                falta: n.min != null && n.llegan < n.min ? n.min - n.llegan : 0,
                sobra: n.max != null && n.llegan > n.max ? n.llegan - n.max : 0,
            }));
        },

        get phaseProblems() {
            return this.phaseNeeds.filter((n) => n.falta || n.sobra);
        },

        phaseProblemText(n) {
            const pide = n.min != null && n.min === n.max ? `exactamente ${n.min}` : (n.falta ? `al menos ${n.min}` : `como mucho ${n.max}`);

            return `A «${n.name}» le llegarían ${n.llegan} y pide ${pide}: así no puede arrancar.`;
        },

        /*
         * Ensaya el diseño que hay en pantalla sin guardarlo. El resultado
         * vale para ESE diseño: en cuanto se toca algo, deja de mostrarse.
         */
        async rehearse() {
            const json = this.designJson;

            this.rehearsal = { loading: true, ok: null, problem: null, forJson: json };

            try {
                const r = await fetch(`${this.tournament.room_url}/rehearse`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        design: json,
                        context: this.isEdition ? 'EDITION' : 'TOURNAMENT',
                        template_id: this.templateId,
                    }),
                });

                const j = await r.json();

                this.rehearsal = { loading: false, ok: Boolean(j.ok), problem: j.problem ?? null, forJson: json };
            } catch (e) {
                this.rehearsal = { loading: false, ok: false, problem: 'No se pudo hacer el ensayo. Vuelve a intentarlo.', forJson: json };
            }
        },

        get rehearsalCurrent() {
            return this.rehearsal.forJson === this.designJson;
        },

        /* La puerta alimenta una fase que no va a poder arrancar */
        doorBlocksPhase(startId) {
            return this.phaseProblems.some((n) => n.puertas.includes(Number(startId)));
        },

        get unplaced() {
            return this.roster.filter((c) => this.isIn(c.id) && !this.calc.doorOf[c.id]);
        },

        get noImage() {
            return this.roster.filter((c) => this.isIn(c.id) && this.face(c).image_missing);
        },

        get withVersions() {
            return this.roster.filter((c) => this.isIn(c.id) && (c.versions ?? []).length);
        },

        get withVersionFace() {
            return this.roster.filter((c) => this.isIn(c.id) && this.face(c).version_id).length;
        },

        get baseCount() {
            return Object.keys(this.baseIn).length;
        },

        get capacityTotal() {
            return this.starts.length && this.starts.every((s) => s.capacity) ? this.starts.reduce((s, x) => s + x.capacity, 0) : null;
        },

        fromCount(from) {
            return this.roster.filter((c) => this.isIn(c.id) && this.face(c).from === from).length;
        },

        matchesFilters(c) {
            const q = this.search.trim().toLowerCase();

            if (q) {
                const texto = [c.name, c.type ?? '', ...(c.attributes ?? []).flatMap((a) => [a.label, ...(a.labels ?? [])])].join(' ').toLowerCase();
                if (!texto.includes(q)) return false;
            }

            if (this.faceFilter && this.face(c).from !== this.faceFilter) return false;

            if (this.issuesOnly) {
                const problema = this.isIn(c.id) && (this.face(c).image_missing || (this.starts.length && !this.calc.doorOf[c.id]));
                if (!problema) return false;
            }

            return true;
        },

        get inList() {
            return this.roster.filter((c) => this.isIn(c.id) && this.matchesFilters(c));
        },

        get outList() {
            return this.roster.filter((c) => !this.isIn(c.id) && this.matchesFilters(c));
        },

        get gridClass() {
            return {
                4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                5: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-5',
                6: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                7: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                8: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                9: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-9',
                10: 'grid-cols-4 sm:grid-cols-7 lg:grid-cols-10',
                11: 'grid-cols-5 sm:grid-cols-8 lg:grid-cols-11',
                12: 'grid-cols-5 sm:grid-cols-8 lg:grid-cols-12',
            }[this.size];
        },

        initials(nombre) {
            return (nombre ?? '?').split(/\s+/).map((p) => p[0] ?? '').join('').slice(0, 2).toUpperCase();
        },

        /* Si un valor de un competidor es de los que pide alguna regla */
        valueWanted(attr, value) {
            const fuente = this.inherits ? this.base : this;
            const todas = [...(fuente.rules ?? []), ...(fuente.groups ?? []).flatMap((g) => g.rules ?? [])].filter((r) => r.attribute === attr);

            return todas.some((r) => !(r.values ?? []).length
                || (r.descendants === false ? r.values : this.expand(attr, r.values)).includes(value));
        },

        /*
        |----------------------------------------------------------------------
        | Quién entra: reglas, grupos y mano
        |----------------------------------------------------------------------
        */

        setMode(m) {
            this.ensureCustom();
            this.mode = m;
        },

        ruleFor(attr) {
            return this.rules.find((r) => r.attribute === attr) ?? null;
        },

        addRule(attr, value = null) {
            this.ensureCustom();

            let regla = this.ruleFor(attr);

            if (!regla) {
                regla = { attribute: attr, values: [], descendants: true };
                this.rules.push(regla);
            }

            if (value !== null && !regla.values.includes(value)) regla.values.push(value);
        },

        toggleValue(regla, value) {
            const i = regla.values.indexOf(value);
            if (i >= 0) regla.values.splice(i, 1);
            else regla.values.push(value);
        },

        removeRule(i) {
            this.rules.splice(i, 1);
        },

        addGroup() {
            this.groups.push({ mode: 'ANY', rules: [] });
        },

        addGroupRule(gi, attr) {
            if (!attr) return;
            const g = this.groups[gi];
            if (!g.rules.some((r) => r.attribute === attr)) g.rules.push({ attribute: attr, values: [], descendants: true });
        },

        removeGroup(gi) {
            this.groups.splice(gi, 1);
        },

        clearAll() {
            this.rules = [];
            this.groups = [];
            this.include = [];
            this.exclude = [];
        },

        handOf(id) {
            const fuente = this.inherits ? this.base : this;
            if ((fuente?.include ?? []).includes(id)) return 'IN';
            if ((fuente?.exclude ?? []).includes(id)) return 'OUT';
            return null;
        },

        setHand(id, estado) {
            this.ensureCustom();

            this.include = this.include.filter((x) => x !== id);
            this.exclude = this.exclude.filter((x) => x !== id);

            if (estado === 'IN') this.include.push(id);
            if (estado === 'OUT') this.exclude.push(id);
        },

        handVisible(estado) {
            const visibles = [...this.inList, ...this.outList].map((c) => c.id);
            visibles.forEach((id) => this.setHand(id, estado));
        },

        dropWithoutImage() {
            this.noImage.forEach((c) => this.setHand(c.id, 'OUT'));
        },

        /*
        |----------------------------------------------------------------------
        | Caras
        |----------------------------------------------------------------------
        */

        FROM_TONES: {
            MANUAL: '#fbbf24',
            CATALOG: '#a78bfa',
            ATTRIBUTES: '#38bdf8',
            BASE: '#34d399',
            DEFAULT: '#2dd4bf',
            ENTITY: '#94a3b8',
        },

        fromTone(from) {
            return this.FROM_TONES[from] ?? '#94a3b8';
        },

        fromLabel(from) {
            return this.fromLabels[from] ?? from;
        },

        doorRowFor(c) {
            const puerta = this.calc.doorOf[c.id];
            return this.effDoors.mode === 'RULES' && puerta ? this.doorRowOf(puerta) : null;
        },

        /* Lo que elegiría la sala sola, sin la mano: se enseña junto a la elección manual */
        autoFace(c) {
            return this.faceOf(c, this.doorRowFor(c), true);
        },

        assignTo(id, startId) {
            this.ensureCustom();

            const manual = {};

            Object.entries(this.doors.manual).forEach(([p, ids]) => { manual[p] = ids.filter((x) => x !== id); });

            if (startId) (manual[startId] ??= []).push(id);

            this.doors.manual = manual;
        },

        faceChoice(id) {
            const caras = this.inherits ? (this.base?.faces ?? {}) : this.faces;
            return Object.prototype.hasOwnProperty.call(caras, id) ? Number(caras[id]) : null;
        },

        setFace(id, valor) {
            this.ensureCustom();

            const caras = { ...this.faces };

            if (valor === null) delete caras[id];
            else caras[id] = Number(valor);

            this.faces = caras;
        },

        /*
        |----------------------------------------------------------------------
        | Puertas
        |----------------------------------------------------------------------
        */

        doorIndex(startId) {
            return this.starts.findIndex((s) => s.id === Number(startId));
        },

        doorColor(startId) {
            const i = this.doorIndex(startId);
            return i >= 0 ? PALETA[i % PALETA.length] : '#64748b';
        },

        doorShort(startId) {
            const i = this.doorIndex(startId);
            return i >= 0 ? `P${i + 1}` : '—';
        },

        doorName(startId) {
            return this.starts.find((s) => s.id === Number(startId))?.name ?? 'Sin puerta';
        },

        doorMembers(startId) {
            return (this.calc.plan.assignments[startId] ?? [])
                .map((id) => this.roster.find((c) => c.id === id))
                .filter((c) => c && this.matchesFilters(c));
        },

        doorCount(startId) {
            return (this.calc.plan.assignments[startId] ?? []).length;
        },

        doorOverflow(startId) {
            return (this.calc.plan.overflow[startId] ?? []).length;
        },

        doorFill(startId) {
            const s = this.starts.find((x) => x.id === startId);
            if (!s?.capacity) return this.doorCount(startId) ? 100 : 0;
            return Math.min(100, Math.round((this.doorCount(startId) / s.capacity) * 100));
        },

        doorState(startId) {
            const s = this.starts.find((x) => x.id === startId);
            const n = this.doorCount(startId);

            if (!s?.capacity) return { text: `${n} entran · sin límite`, tone: '#94a3b8' };
            if (this.doorOverflow(startId)) return { text: `llena · sobran ${this.doorOverflow(startId)}`, tone: '#fbbf24' };
            if (n === s.capacity) return { text: 'completa', tone: '#34d399' };
            if (this.doorBlocksPhase(startId)) return { text: `faltan ${s.capacity - n} · la fase no arranca`, tone: '#f43f5e' };
            return { text: `faltan ${s.capacity - n}`, tone: n ? '#38bdf8' : '#f43f5e' };
        },

        setDoorsMode(m) {
            this.ensureCustom();
            this.doors.mode = m;
            this.brush = null;
        },

        reshuffle() {
            this.ensureCustom();
            this.doors.strategy = 'RANDOM';
            this.doors.seed = (this.doors.seed % 99991) + 1;
        },

        valueDoor(value) {
            const p = this.doors.value_doors[value];
            return p !== undefined ? Number(p) : '';
        },

        setValueDoor(value, startId) {
            const mapa = { ...this.doors.value_doors };

            if (startId === '' || startId === null) delete mapa[value];
            else mapa[value] = Number(startId);

            this.doors.value_doors = mapa;
        },

        doorRowOf(startId) {
            return this.effDoors.rules.find((r) => r.start_id === Number(startId)) ?? null;
        },

        ensureDoorRow(startId) {
            let fila = this.doors.rules.find((r) => r.start_id === Number(startId)) ?? null;

            if (!fila) {
                fila = { start_id: Number(startId), mode: 'ALL', rules: [], groups: [], include: [], exclude: [] };
                this.doors.rules.push(fila);
            }

            return fila;
        },

        addDoorRule(startId, attr) {
            if (!attr) return;
            const fila = this.ensureDoorRow(startId);
            if (!fila.rules.some((r) => r.attribute === attr)) fila.rules.push({ attribute: attr, values: [], descendants: true });
            this.doorRulePick = { ...this.doorRulePick, [startId]: '' };
        },

        removeDoorRule(startId, ri) {
            const fila = this.doorRowOf(startId);
            if (fila) fila.rules.splice(ri, 1);
        },

        doorRuleOrder(startId) {
            const i = this.effDoors.rules.findIndex((r) => r.start_id === Number(startId));
            return i >= 0 ? i + 1 : null;
        },

        moveDoorRow(startId, paso) {
            const i = this.doors.rules.findIndex((r) => r.start_id === Number(startId));
            const j = i + paso;
            if (i < 0 || j < 0 || j >= this.doors.rules.length) return;

            const filas = [...this.doors.rules];
            [filas[i], filas[j]] = [filas[j], filas[i]];
            this.doors.rules = filas;
        },

        doorRuleText(startId) {
            const fila = this.doorRowOf(startId);
            if (!fila || !fila.rules.length) return 'Sin condiciones: se lleva a todos los que queden';

            return this.ruleText(fila);
        },

        /* A mano: con una puerta elegida como pincel, pulsar una cara la mete ahí */
        paint(id) {
            if (!this.brush) return;

            const actual = this.calc.doorOf[id] ?? null;
            const manual = {};

            Object.entries(this.doors.manual).forEach(([p, ids]) => { manual[p] = ids.filter((x) => x !== id); });

            if (actual !== this.brush) (manual[this.brush] ??= []).push(id);

            this.doors.manual = manual;
        },

        manualFromPlan() {
            const manual = {};
            Object.entries(this.calc.plan.assignments).forEach(([p, ids]) => { manual[p] = [...ids]; });

            this.doors.manual = manual;
            this.doors.mode = 'MANUAL';
            this.brush = this.starts[0]?.id ?? null;
        },

        clearManual() {
            this.doors.manual = {};
        },

        cardClick(c) {
            if (this.editable && this.panel === 'doors' && this.doors.mode === 'MANUAL' && this.brush && this.isIn(c.id)) {
                this.paint(c.id);
                return;
            }

            this.ficha = c.id;
        },

        get fichaC() {
            return this.roster.find((c) => c.id === this.ficha) ?? null;
        },

        /*
        |----------------------------------------------------------------------
        | Vista por valor
        |----------------------------------------------------------------------
        */

        get groupValues() {
            return this.attr(this.groupAttr)?.values ?? [];
        },

        valueMembers(value) {
            return this.roster.filter((c) => (c.owned[this.groupAttr] ?? []).includes(value) && this.matchesFilters(c));
        },

        get withoutGroupAttr() {
            return this.roster.filter((c) => !c.owned[this.groupAttr] && this.matchesFilters(c));
        },

        valueTone(v, i) {
            return v.color || PALETA[i % PALETA.length];
        },
    };
}

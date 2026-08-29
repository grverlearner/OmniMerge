/*
 * EL PANEL DE COMPETIDORES
 *
 * Buscar, filtrar por atributo y mirar de cinco maneras distintas.
 *
 * Todo ocurre en la pantalla, sin recargar. Filtrar por «aldea → hoja» y
 * esperar un viaje al servidor por cada valor que marcas convierte explorar
 * en rellenar un formulario. El servidor manda la lista entera —un universo
 * son cientos de entidades, no millones— y aquí se recorta.
 *
 * El servidor ya sabe hacer lo mismo (UniverseEntityBrowser), y esa
 * duplicación tiene un motivo: el enlace que compartes lleva los filtros en
 * la URL, así que al abrirlo la página llega ya filtrada sin depender de
 * que el JavaScript haya arrancado.
 */
export default function entityBrowser(config) {
    return {
        entities: config.entities ?? [],
        catalog: config.catalog ?? [],
        types: config.types ?? [],

        /* ------------------------------------------------ filtros */
        search: config.filters?.search ?? '',
        status: config.filters?.status ?? '',
        type: config.filters?.type ?? '',
        only: config.filters?.only ?? '',

        /* attr[clave] = [valor, …] y has = [clave, …] */
        attrs: config.filters?.attributes ?? {},
        has: config.filters?.has ?? [],

        sort: config.filters?.sort ?? 'name',

        /* ------------------------------------------------ cómo se mira */
        view: config.filters?.view ?? 'GRID',
        size: config.filters?.size ?? 3,

        openFilters: false,
        openAttribute: null,

        /* La ficha abierta en un lado, sin salir del panel */
        peek: null,

        init() {
            this.recallView();
        },

        /*
        |----------------------------------------------------------------------
        | Lo que se ve
        |----------------------------------------------------------------------
        */

        get shown() {
            let lista = this.entities;

            const q = this.search.trim().toLowerCase();

            if (q) lista = lista.filter((e) => this.matchesSearch(e, q));

            if (this.status) lista = lista.filter((e) => e.status === this.status);

            if (this.type) lista = lista.filter((e) => e.type === this.type);

            if (this.has.length) {
                lista = lista.filter((e) =>
                    this.has.every((k) => e.attributes.some((a) => a.key === k))
                );
            }

            const pedidos = Object.entries(this.attrs).filter(([, v]) => v.length);

            if (pedidos.length) {
                lista = lista.filter((e) =>
                    pedidos.every(([clave, valores]) => {
                        const suyo = e.attributes.find((a) => a.key === clave);
                        return suyo && valores.some((v) => suyo.keys.includes(v));
                    })
                );
            }

            if (this.only) lista = lista.filter((e) => this.matchesOnly(e));

            return this.sorted(lista);
        },

        matchesSearch(e, q) {
            if (e.name.toLowerCase().includes(q)) return true;
            if ((e.code ?? '').toLowerCase().includes(q)) return true;
            if ((e.type ?? '').toLowerCase().includes(q)) return true;

            /* Por atributo y por valor: «sharingan» encuentra a quien lo lleva */
            if (e.attributes.some((a) =>
                a.key.includes(q) || a.keys.some((v) => v.includes(q))
            )) return true;

            return e.versions.some((v) => (v.name ?? '').toLowerCase().includes(q));
        },

        matchesOnly(e) {
            return {
                TROPHIES: e.record.trophies > 0,
                TITLES: e.record.titles > 0,
                VERSIONS: e.versions.length > 1,
                PLAYED: e.record.tournaments > 0,
                NEVER: e.record.tournaments === 0,
                LIBRARY: e.from_library,
            }[this.only] ?? true;
        },

        sorted(lista) {
            const copia = [...lista];

            const porNombre = (a, b) => a.name.localeCompare(b.name, 'es');

            const desc = (leer) => (a, b) => {
                const d = leer(b) - leer(a);
                return d !== 0 ? d : porNombre(a, b);
            };

            return copia.sort({
                name: porNombre,
                recent: desc((e) => e.id),
                titles: desc((e) => e.record.titles),
                wins: desc((e) => e.record.wins),
                tournaments: desc((e) => e.record.tournaments),
                trophies: desc((e) => e.record.trophies),
                attributes: desc((e) => e.attributes.length),

                /*
                 * Quien no ha jugado no tiene 0%: no tiene porcentaje.
                 * Tratarlo como 0 lo pondría junto a los que pierden
                 * siempre, que es una acusación falsa.
                 */
                winrate: desc((e) => e.record.winrate ?? -1),
            }[this.sort] ?? porNombre);
        },

        /*
        |----------------------------------------------------------------------
        | Filtrar
        |----------------------------------------------------------------------
        */

        attributeOf(key) {
            return this.catalog.find((a) => a.key === key) ?? null;
        },

        valuesOf(key) {
            return this.attributeOf(key)?.values ?? [];
        },

        isValueOn(key, value) {
            return (this.attrs[key] ?? []).includes(value);
        },

        toggleValue(key, value) {
            const actuales = [...(this.attrs[key] ?? [])];

            const i = actuales.indexOf(value);

            if (i === -1) actuales.push(value);
            else actuales.splice(i, 1);

            if (actuales.length) this.attrs[key] = actuales;
            else delete this.attrs[key];

            /* Reasignar para que Alpine vea el cambio del objeto */
            this.attrs = { ...this.attrs };
        },

        isHas(key) {
            return this.has.includes(key);
        },

        toggleHas(key) {
            const i = this.has.indexOf(key);

            if (i === -1) this.has.push(key);
            else this.has.splice(i, 1);
        },

        get activeFilters() {
            return (this.search ? 1 : 0)
                + (this.status ? 1 : 0)
                + (this.type ? 1 : 0)
                + (this.only ? 1 : 0)
                + this.has.length
                + Object.values(this.attrs).filter((v) => v.length).length;
        },

        clearFilters() {
            this.search = '';
            this.status = '';
            this.type = '';
            this.only = '';
            this.has = [];
            this.attrs = {};
        },

        /* Cómo se lee el filtro puesto, para poder quitarlo de uno en uno */
        get chips() {
            const out = [];

            if (this.search) out.push({ label: '«' + this.search + '»', clear: () => (this.search = '') });
            if (this.status) out.push({ label: this.status === 'ACTIVE' ? 'activos' : 'retirados', clear: () => (this.status = '') });
            if (this.type) out.push({ label: this.type, clear: () => (this.type = '') });

            if (this.only) {
                out.push({
                    label: {
                        TROPHIES: 'con trofeos',
                        TITLES: 'campeones',
                        VERSIONS: 'con versiones',
                        PLAYED: 'han jugado',
                        NEVER: 'sin jugar',
                        LIBRARY: 'de la Biblioteca',
                    }[this.only] ?? this.only,
                    clear: () => (this.only = ''),
                });
            }

            this.has.forEach((k) => out.push({
                label: 'tiene ' + (this.attributeOf(k)?.label ?? k),
                clear: () => this.toggleHas(k),
            }));

            Object.entries(this.attrs).forEach(([k, valores]) => {
                const attr = this.attributeOf(k);

                valores.forEach((v) => out.push({
                    label: (attr?.label ?? k) + ': ' + (attr?.values.find((o) => o.key === v)?.label ?? v),
                    clear: () => this.toggleValue(k, v),
                }));
            });

            return out;
        },

        /*
        |----------------------------------------------------------------------
        | Cómo se mira
        |----------------------------------------------------------------------
        */

        setView(v) {
            this.view = v;
            this.rememberView();
        },

        setSize(n) {
            this.size = Math.max(1, Math.min(5, Number(n)));
            this.rememberView();
        },

        /*
         * Las clases de la rejilla, literales.
         *
         * Tailwind lee el código fuente: una clase construida al vuelo
         * —'grid-cols-' + n— no llega nunca al CSS.
         *
         * Y por encima de 12 no hay clase que valga: la escala de Tailwind
         * termina en grid-cols-12, así que los tamaños más pequeños no
         * hacían nada —pedías 20 columnas y salían 12—. De ahí arriba se
         * usa valor arbitrario, que Tailwind sí genera porque también lo
         * lee del código.
         */
        get grid() {
            if (this.view === 'TABLE' || this.view === 'LIST') return 'grid-cols-1';

            const escalas = {
                GRID: [
                    'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                    'grid-cols-4 sm:grid-cols-6 lg:grid-cols-9',
                    'grid-cols-5 sm:grid-cols-8 lg:grid-cols-12',
                    'grid-cols-6 sm:grid-cols-10 lg:grid-cols-[repeat(15,minmax(0,1fr))]',
                    'grid-cols-8 sm:grid-cols-12 lg:grid-cols-[repeat(20,minmax(0,1fr))]',
                ],
                GALLERY: [
                    'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
                    'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                    'grid-cols-2 sm:grid-cols-4 lg:grid-cols-5',
                    'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                    'grid-cols-4 sm:grid-cols-6 lg:grid-cols-9',
                ],
                CARD: [
                    'grid-cols-1',
                    'grid-cols-1 lg:grid-cols-2',
                    'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
                    'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                    'grid-cols-2 sm:grid-cols-4 lg:grid-cols-5',
                ],
            };

            const escala = escalas[this.view] ?? escalas.GRID;

            return escala[this.size - 1] ?? escala[2];
        },

        rememberView() {
            try {
                localStorage.setItem(
                    'omnimerge.entidades.vista',
                    JSON.stringify({ view: this.view, size: this.size })
                );
            } catch (e) {
                /* Sin almacenamiento se sigue mirando, solo no se recuerda */
            }
        },

        recallView() {
            /*
             * Lo que venga en la URL manda: si alguien comparte un enlace
             * con ?view=TABLE, esa es la intención del enlace, no la
             * preferencia de quien lo abre.
             */
            if (new URLSearchParams(location.search).has('view')) return;

            try {
                const guardado = JSON.parse(
                    localStorage.getItem('omnimerge.entidades.vista') ?? 'null'
                );

                if (guardado?.view) this.view = guardado.view;
                if (guardado?.size) this.size = guardado.size;
            } catch (e) {
                /* Un valor corrupto no debe dejar el panel sin rejilla */
            }
        },

        /*
        |----------------------------------------------------------------------
        | Leer una ficha
        |----------------------------------------------------------------------
        */

        chipsOf(entity) {
            return entity.attributes.flatMap((a) =>
                a.values.map((v) => ({
                    key: a.key + ':' + v,
                    attribute: a.name,
                    value: v,
                    featured: a.featured,
                }))
            );
        },

        recordText(entity) {
            const r = entity.record;

            if (r.tournaments === 0) return 'no ha competido todavía';

            return r.tournaments + (r.tournaments === 1 ? ' torneo' : ' torneos')
                + ' · ' + r.wins + '–' + r.losses
                + (r.winrate !== null ? ' · ' + r.winrate + '%' : '');
        },

        activeVersion(entity) {
            return entity.versions.find((v) => v.active)
                ?? entity.versions.find((v) => v.is_base)
                ?? null;
        },
    };
}

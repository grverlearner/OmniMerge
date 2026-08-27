/*
|--------------------------------------------------------------------------
| Super Edicion — Torneo
|--------------------------------------------------------------------------
|
| Un torneo no es una fase: es el grafo que las une. Inicios por donde entra
| la gente, nodos que son fases, conexiones que llevan a los que salen de una
| a la entrada de otra, y terminales donde acaba el recorrido.
|
| Todo el grafo llega ya calculado del servidor -niveles, bifurcaciones,
| convergencias, validacion- y aqui no se recalcula nada. Lo que hace este
| modulo es lo que solo se puede hacer en el navegador:
|
|   elegir que se mira      el mapa entero, o el recorrido de una fase
|   resolver vecinos        quien viene antes y quien va despues
|   pintar por nivel        el color dice a que altura del torneo estas
|
| Las piezas se direccionan con una llave de texto -"NODE:7", "START:3",
| "TERMINAL:4"- porque una conexion puede salir de un inicio O de un nodo, y
| llegar a una puerta O a un terminal. Con llaves, "de que a que" es una sola
| pregunta en vez de cuatro.
|
*/

export default function tournamentSuperEditor(config) {

    return {

        payload: config.payload,

        previewUrl: config.previewUrl,

        /*
         * MAP   el torneo entero, para verlo
         * PATH  una fase y sus vecinas, para entenderla
         * EDIT  lo mismo que PATH, pero para trabajar dentro
         */
        view: 'MAP',

        /*
         * El panel de la izquierda se pliega.
         *
         * En el taller estorba: lo que se edita está en el centro y el panel
         * le quita un tercio de ancho justo cuando más falta hace. Se pliega
         * solo al entrar ahí, y se puede volver a abrir.
         */
        panelOpen: true,

        /* La fase que se mira en la vista de recorrido */
        focus: null,

        /* La pieza abierta en el panel izquierdo */
        selected: null,

        /* Que bloque del panel esta desplegado */
        openBlock: 'NODES',

        /* Formularios abiertos: 'START', 'NODE', 'TERMINAL', 'LINK' o null */
        creating: null,

        editing: null,

        loading: false,


        init() {
            /*
             * La fase que se esta mirando se recuerda.
             *
             * Cada formulario de esta pantalla recarga la pagina -asi es
             * como funciona todo el CRUD del grafo, con redirecciones- y sin
             * recordarla, conectar una ruta en la cuarta fase te devolvia a
             * la primera. El trabajo seguia hecho, pero habia que volver a
             * buscar donde estabas cada vez.
             *
             * La llave lleva el id del torneo: dos torneos distintos no
             * comparten fases, y sin eso una fase de uno "existiria" en el
             * otro solo porque el numero coincide.
             */
            this.focus = this.recallFocus() ?? this.nodes[0]?.key ?? null;

            /*
             * Al entrar siempre se quiere la vista general primero, salvo
             * que se pidiera otra la ultima vez.
             */
            try {
                const saved = localStorage.getItem('omnimerge.torneo.vista');

                if (['MAP', 'PATH', 'EDIT'].includes(saved)) this.view = saved;

                const panel = localStorage.getItem('omnimerge.torneo.panel');

                if (panel !== null) this.panelOpen = panel === '1';
            } catch (e) {
                /* Sin almacenamiento: se abre en el mapa con el panel abierto */
            }

            if (this.view === 'EDIT') this.panelOpen = false;

            /* Guardar cada vez que cambie, venga de donde venga */
            this.$watch('focus', (key) => this.rememberFocus(key));
        },

        get focusStorageKey() {
            return 'omnimerge.torneo.' + (this.payload.tournament?.id ?? 0) + '.fase';
        },

        recallFocus() {
            try {
                const saved = localStorage.getItem(this.focusStorageKey);

                /* Solo si esa fase sigue existiendo: pudo borrarse */
                return this.nodes.some((n) => n.key === saved) ? saved : null;
            } catch (e) {
                return null;
            }
        },

        rememberFocus(key) {
            try {
                if (key) localStorage.setItem(this.focusStorageKey, key);
            } catch (e) {
                /* Se sigue viendo bien, solo que no sobrevive a la recarga */
            }
        },

        setView(view) {
            const venia = this.view;

            this.view = view;

            /*
             * El taller siempre se abre con el panel plegado.
             *
             * Es lo predecible: entrar al taller da sitio, y punto. Se puede
             * volver a abrir mientras se está dentro y se queda abierto,
             * pero al salir y volver a entrar el taller vuelve a darte la
             * pantalla entera. Recargar estando en el taller hace lo mismo,
             * asi que lo que se ve y lo que se recuerda no se contradicen.
             */
            if (view === 'EDIT' && venia !== 'EDIT') this.panelOpen = false;

            try {
                localStorage.setItem('omnimerge.torneo.vista', view);
            } catch (e) {
                /* La vista funciona igual, solo que no se recuerda */
            }
        },

        togglePanel() {
            this.panelOpen = !this.panelOpen;

            try {
                localStorage.setItem('omnimerge.torneo.panel', this.panelOpen ? '1' : '0');
            } catch (e) {
                /* Se pliega igual, solo que no se recuerda */
            }
        },


        /*
        |----------------------------------------------------------------
        | Las piezas
        |----------------------------------------------------------------
        */

        get starts() {
            return (this.payload.starts ?? []).map((s) => ({ ...s, key: 'START:' + s.id }));
        },

        get nodes() {
            return (this.payload.nodes ?? []).map((n) => ({ ...n, key: 'NODE:' + n.id }));
        },

        get terminals() {
            return (this.payload.terminals ?? []).map((t) => ({ ...t, key: 'TERMINAL:' + t.id }));
        },

        get links() {
            return this.payload.links ?? [];
        },

        get pieces() {
            const map = {};

            [...this.starts, ...this.nodes, ...this.terminals]
                .forEach((p) => { map[p.key] = p; });

            return map;
        },

        pieceOf(key) {
            return this.pieces[key] ?? null;
        },

        kindOf(key) {
            return (key ?? '').split(':')[0];
        },

        /*
         * El color de una pieza.
         *
         * Los inicios y los terminales tienen el suyo porque no son una fase
         * mas: son los extremos del recorrido. Las fases heredan el color de
         * su nivel, asi que dos fases que se juegan a la vez se ven iguales
         * y eso es exactamente lo que hay que ver.
         */
        colorOf(key) {
            const kind = this.kindOf(key);

            if (kind === 'START') return this.payload.palette.start;
            if (kind === 'TERMINAL') return this.payload.palette.terminal;

            const column = (this.payload.map?.columns ?? [])
                .find((c) => c.keys.includes(key));

            return column?.color ?? this.payload.palette.levels[0];
        },


        /*
        |----------------------------------------------------------------
        | El mapa
        |----------------------------------------------------------------
        */

        get columns() {
            return (this.payload.map?.columns ?? []).map((column) => ({
                ...column,
                pieces: column.keys.map((k) => this.pieceOf(k)).filter(Boolean),
            }));
        },

        /* Una fase que reparte a varios sitios: el camino se abre */
        isBranching(key) {
            return (this.payload.map?.branching ?? []).includes(key);
        },

        /* Una fase a la que llegan varios: el camino se junta */
        isConverging(key) {
            return (this.payload.map?.converging ?? []).includes(key);
        },


        /*
        |----------------------------------------------------------------
        | Vecinos
        |----------------------------------------------------------------
        */

        before(key) {
            return (this.payload.neighbours?.[key]?.before ?? [])
                .map((k) => this.pieceOf(k))
                .filter(Boolean);
        },

        after(key) {
            return (this.payload.neighbours?.[key]?.after ?? [])
                .map((k) => this.pieceOf(k))
                .filter(Boolean);
        },

        get focused() {
            return this.pieceOf(this.focus);
        },

        get focusBefore() {
            return this.before(this.focus);
        },

        get focusAfter() {
            return this.after(this.focus);
        },

        /*
         * Las conexiones que unen dos piezas concretas.
         *
         * Es lo que da sentido a la flecha del recorrido: no es "de la fase A
         * a la fase B", es "de la salida Ganador de A a la entrada general de
         * B, repartiendo todo".
         */
        linksBetween(fromKey, toKey) {
            const entriesOf = (key) => {
                const node = this.pieceOf(key);

                return this.kindOf(key) === 'NODE'
                    ? (node?.entries ?? []).map((e) => 'ENTRY:' + e.id)
                    : [key];
            };

            const targets = entriesOf(toKey);

            return this.links.filter((l) => l.from === fromKey && targets.includes(l.to));
        },

        /* La salida de la fase por la que se va cada conexion */
        exitOf(link) {
            const node = this.pieceOf(link.from);

            return (node?.exits ?? []).find((e) => e.id === link.exit_id) ?? null;
        },

        entryOf(link) {
            for (const node of this.nodes) {
                const entry = (node.entries ?? []).find((e) => 'ENTRY:' + e.id === link.to);

                if (entry) return entry;
            }

            return null;
        },


        /*
        |----------------------------------------------------------------
        | Forma de cada fase
        |----------------------------------------------------------------
        */

        outlineOf(key) {
            return this.payload.outlines?.[key] ?? null;
        },

        /*
         * Caras prestadas, para ver el recorrido con gente dentro. El indice
         * da la vuelta a proposito: no representan a nadie en concreto, solo
         * enseñan que por ahi pasan competidores.
         */
        face(index) {
            const cast = this.payload.cast ?? [];

            return cast.length ? cast[((index % cast.length) + cast.length) % cast.length] : null;
        },

        facesFor(key, count = 4) {
            const seed = (this.pieceOf(key)?.id ?? 0) * 3;

            return Array.from({ length: count }, (_, i) => this.face(seed + i)).filter(Boolean);
        },



        /*
        |----------------------------------------------------------------
        | Cuántos entran y cuántos salen
        |----------------------------------------------------------------
        |
        | Los números los calcula el servidor, del mismo pronóstico que
        | produce los avisos del diagnóstico. Aquí solo se les da forma, y
        | con una sola función para que en todas las pantallas se lean
        | igual.
        |
        */

        get flow() {
            return this.payload.flow ?? {};
        },

        /* "16", "4–8", "12+" o "—". Nunca una frase larga */
        amount(forecast) {
            if (!forecast) return '—';

            if (forecast.exact !== null && forecast.exact !== undefined) {
                return String(forecast.exact);
            }

            if (forecast.known === false) {
                return forecast.min + '+';
            }

            return forecast.min + '–' + (forecast.max ?? '?');
        },

        entryFlow(entryId) {
            return this.flow.entries?.[entryId] ?? null;
        },

        exitFlow(nodeKey, exitId) {
            return this.flow.exits?.[nodeKey + ':' + exitId] ?? null;
        },

        nodeFlow(nodeId) {
            return this.flow.nodes?.[nodeId] ?? null;
        },

        terminalFlow(terminalId) {
            return this.flow.terminals?.[terminalId] ?? null;
        },

        startFlow(startId) {
            return this.flow.starts?.[startId] ?? null;
        },

        /*
         * Lo que queda por meter, dicho en una frase corta.
         *
         * Es la única pregunta que importa al conectar, así que se contesta
         * con palabras y no con un rango: "faltan 4" se entiende, "0–4" hay
         * que interpretarlo.
         */
        room(left) {
            if (!left) return null;

            if (left.over) return 'se pasa de ' + Math.abs(left.max);
            if (left.full) return 'lleno';

            if (left.exact !== null && left.exact !== undefined) {
                return 'faltan ' + left.exact;
            }

            return 'faltan hasta ' + left.max;
        },

        /* El color de esa frase: rojo si se pasa, verde si está lleno */
        roomTone(left) {
            if (!left) return 'text-slate-600';

            if (left.over) return 'text-rose-300';
            if (left.full) return 'text-emerald-300';

            return 'text-amber-300';
        },

        /*
        |----------------------------------------------------------------
        | Moverse entre fases
        |----------------------------------------------------------------
        |
        | Sin salir de la estructura. El desplegable de arriba servia, pero
        | obligaba a subir a una esquina para algo que se hace todo el rato,
        | y no decia donde estabas dentro del recorrido.
        |
        */

        goTo(key) {
            if (this.kindOf(key) !== 'NODE') return;

            this.focus = key;
        },

        get focusIndex() {
            return this.nodes.findIndex((n) => n.key === this.focus);
        },

        /* La fase anterior y la siguiente EN LA LISTA, no en el grafo */
        step(delta) {
            if (this.nodes.length === 0) return;

            const next = (this.focusIndex + delta + this.nodes.length) % this.nodes.length;

            this.focus = this.nodes[next].key;
        },

        /*
         * A que fase lleva una ruta, si lleva a alguna.
         *
         * Una ruta puede acabar en un terminal, y entonces no hay a donde
         * saltar: devuelve null y el boton no se dibuja.
         */
        nodeBehind(key) {
            if (this.kindOf(key) === 'NODE') return key;

            if (this.kindOf(key) === 'ENTRY') {
                const node = this.nodes.find(
                    (n) => (n.entries ?? []).some((e) => 'ENTRY:' + e.id === key));

                return node?.key ?? null;
            }

            return null;
        },

        linkTarget(link) {
            return this.nodeBehind(link.to);
        },

        linkOrigin(link) {
            return this.nodeBehind(link.from);
        },


        /*
        |----------------------------------------------------------------
        | El taller
        |----------------------------------------------------------------
        |
        | Lo que hace falta para editar el recorrido SIN salir de él: qué
        | rutas cuelgan de cada puerta, cuáles están sueltas, y qué se puede
        | conectar con qué sin crear un imposible.
        |
        */

        /*
         * Las rutas que salen por una salida concreta DE UNA FASE CONCRETA.
         *
         * Hacen falta las dos cosas, y esto antes solo miraba la salida.
         *
         * Una salida pertenece a la PLANTILLA de la fase, no al nodo del
         * torneo. Dos fases en paralelo que usan la misma plantilla —dos
         * llaves iguales, lo mas normal del mundo— comparten los ids de sus
         * salidas, asi que filtrar solo por salida devolvia tambien las
         * rutas de la hermana: conectabas una llave y parecia que se
         * conectaban todas.
         *
         * Una ruta pertenece al par (fase, salida). Siempre.
         */
        linksFromExit(nodeKey, exitId) {
            return this.links.filter(
                (l) => l.from === nodeKey && l.exit_id === exitId);
        },

        /* Las rutas que entran por una puerta concreta */
        linksToEntry(entryId) {
            return this.links.filter((l) => l.entry_id === entryId);
        },

        /*
         * Las salidas por las que no se va nadie.
         *
         * Es el error más fácil de cometer y el más difícil de ver: la fase
         * reparte plazas por una salida que no lleva a ningún sitio, así que
         * esa gente se queda en el limbo.
         */
        unconnectedExits(node) {
            return (node?.exits ?? []).filter(
                (e) => this.linksFromExit(node.key, e.id).length === 0);
        },

        /* Las puertas a las que no llega nadie */
        emptyEntries(node) {
            return (node?.entries ?? []).filter((e) => this.linksToEntry(e.id).length === 0);
        },

        /*
         * A dónde se puede mandar gente: cualquier puerta de cualquier fase,
         * y cualquier final. Se ofrece la propia fase también, porque un
         * torneo puede tener una repesca que vuelve a la misma.
         */
        get destinations() {
            const out = [];

            this.nodes.forEach((node) => {
                (node.entries ?? []).forEach((entry) => {

                    const flow = this.entryFlow(entry.id);

                    out.push({
                        value: 'ENTRY:' + entry.id,
                        id: entry.id,
                        type: 'ENTRY_PORT',
                        group: node.name,
                        label: entry.name,
                        hint: this.roomHint(flow, entry.contract),
                        left: flow?.left ?? null,
                    });
                });
            });

            this.terminals.forEach((terminal) => {

                const flow = this.terminalFlow(terminal.id);

                out.push({
                    value: 'TERMINAL:' + terminal.id,
                    id: terminal.id,
                    type: 'TERMINAL',
                    group: 'Finales del torneo',
                    label: terminal.name,
                    hint: this.roomHint(flow, terminal.terminal_type_label),
                    left: flow?.left ?? null,
                });
            });

            return out;
        },

        /*
         * Los destinos que todavía admiten gente.
         *
         * Ofrecer uno lleno solo invita a pasarse: la ruta se crearía, el
         * diagnóstico se quejaría, y habría que deshacerla. Mejor no
         * ofrecerlo.
         *
         * Un destino SIN cupo declarado no está lleno: admite lo que le
         * echen, así que se queda. Y los que ya se pasaron tampoco se
         * ofrecen, por el mismo motivo.
         */
        get openDestinations() {
            return this.destinations.filter((d) => !this.isFull(d));
        },

        isFull(option) {
            const left = option?.left;

            if (!left) return false;

            return left.full === true || left.over === true;
        },

        /* "faltan 4" cuando se sabe, y si no lo que hubiera */
        roomHint(flow, fallback) {
            const dicho = this.room(flow?.left);

            return dicho ?? (fallback || '');
        },

        /* De dónde puede venir gente: entradas del torneo y salidas de fases */
        get origins() {
            const out = this.starts.map((start) => ({
                value: 'START:' + start.id,
                id: start.id,
                type: 'START',
                group: 'Entradas del torneo',
                label: start.name,
                hint: start.source_type_label,
            }));

            this.nodes.forEach((node) => {
                (node.exits ?? []).forEach((exit) => {
                    out.push({
                        /*
                         * La llave lleva la FASE ademas de la salida.
                         *
                         * Con solo la salida, dos fases paralelas que usan
                         * la misma plantilla generaban dos opciones con el
                         * mismo valor: elegir una podia crear la ruta desde
                         * la otra, porque `find` devuelve la primera.
                         */
                        value: 'EXIT:' + node.id + ':' + exit.id,
                        id: exit.id,
                        nodeId: node.id,
                        nodeKey: node.key,
                        type: 'PHASE_EXIT',
                        group: node.name,
                        label: exit.name,
                        hint: exit.selector,
                    });
                });
            });

            return out;
        },

        /*
         * La ruta de "todos" que ya tiene un origen, si la tiene.
         *
         * Un origen que ya reparte TODOS no admite una segunda rama: no
         * puedes mandar a todo el mundo a una fase y ademas repartir a
         * otras. El servidor lo rechaza, y con razon, pero enterarse al
         * pulsar Conectar es tarde: para entonces ya has elegido destino y
         * cantidad.
         *
         * El alcance es el mismo que usa el servidor: una entrada concreta,
         * o un par fase+salida concreto.
         */
        catchAllFrom(originValue) {
            const partes = (originValue ?? '').split(':');
            const kind = partes[0];

            if (kind === 'START') {
                return this.links.find(
                    (l) => l.from === 'START:' + partes[1] && l.allocation_mode === 'ALL') ?? null;
            }

            /* 'EXIT:nodo:salida' — hacen falta los dos, ver linksFromExit */
            if (kind === 'EXIT') {
                return this.links.find(
                    (l) => l.from === 'NODE:' + partes[1]
                        && String(l.exit_id) === partes[2]
                        && l.allocation_mode === 'ALL') ?? null;
            }

            return null;
        },

        /* La opción elegida en un desplegable de origen o destino */
        optionOf(list, value) {
            return list.find((o) => o.value === value) ?? null;
        },

        /* Qué formulario del taller está abierto: 'exit:12', 'entry:7', null */
        workbench: null,

        openBench(key) {
            this.workbench = this.workbench === key ? null : key;
        },

        atBench(key) {
            return this.workbench === key;
        },


        /*
        |----------------------------------------------------------------
        | Selección y formularios
        |----------------------------------------------------------------
        */

        select(key) {
            this.selected = this.selected === key ? null : key;

            /* Elegir una fase en el panel tambien la enfoca en el recorrido */
            if (this.kindOf(key) === 'NODE') this.focus = key;
        },

        isSelected(key) {
            return this.selected === key;
        },

        openCreate(kind) {
            this.creating = this.creating === kind ? null : kind;
            this.editing = null;
        },

        openEdit(key) {
            this.editing = this.editing === key ? null : key;
            this.creating = null;
        },

        isEditing(key) {
            return this.editing === key;
        },


        /*
        |----------------------------------------------------------------
        | Diagnóstico
        |----------------------------------------------------------------
        */

        get validation() {
            return this.payload.validation ?? { valid: true, errors: [], warnings: [], stats: {} };
        },

        get stats() {
            return this.validation.stats ?? {};
        },

        get isValid() {
            return this.validation.valid;
        },

        /* Las conexiones que tocan una pieza, para poder avisar antes de borrar */
        linksTouching(key) {
            const entries = this.kindOf(key) === 'NODE'
                ? (this.pieceOf(key)?.entries ?? []).map((e) => 'ENTRY:' + e.id)
                : [];

            return this.links.filter(
                (l) => l.from === key || l.to === key || entries.includes(l.to)
            );
        },
    };
}

/*
|--------------------------------------------------------------------------
| La ficha de un torneo
|--------------------------------------------------------------------------
|
| Presenta el recorrido y lo simula. No edita nada.
|
| Comparte el payload con la Super Edicion —el mismo grafo, los mismos
| niveles, las mismas siluetas— porque es la misma informacion leida para
| otra cosa. Lo unico propio de aqui es la simulacion.
|
| La simulacion tampoco tiene motor propio: la ejecuta en el servidor
| TournamentFlowPreviewService, que ya recorria el grafo repartiendo
| participantes sinteticos. Este modulo solo la pide, la guarda y la enseña.
|
*/

export default function tournamentDossier(config) {

    return {

        payload: config.payload,

        simulateUrl: config.simulateUrl,

        csrf: config.csrf,

        /* Cuántos entran a la simulación */
        participants: config.payload.tournament?.max_participants
            ?? config.payload.tournament?.min_participants
            ?? 16,

        running: false,

        /* El resultado de la última simulación, o null si no se ha corrido */
        result: null,

        /* Problemas que impidieron simular, del propio grafo */
        blocked: [],

        /* Qué participante se está siguiendo por el recorrido */
        tracking: null,


        /*
        |----------------------------------------------------------------
        | Las piezas del grafo
        |----------------------------------------------------------------
        |
        | Igual que en la Super Edición: las llaves de texto hacen que "de
        | qué a qué" sea una sola pregunta en vez de cuatro.
        |
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

        colorOf(key) {
            const kind = this.kindOf(key);

            if (kind === 'START') return this.payload.palette.start;
            if (kind === 'TERMINAL') return this.payload.palette.terminal;

            const column = (this.payload.map?.columns ?? [])
                .find((c) => c.keys.includes(key));

            return column?.color ?? this.payload.palette.levels[0];
        },

        get columns() {
            return (this.payload.map?.columns ?? []).map((column) => ({
                ...column,
                pieces: column.keys.map((k) => this.pieceOf(k)).filter(Boolean),
            }));
        },

        isBranching(key) {
            return (this.payload.map?.branching ?? []).includes(key);
        },

        isConverging(key) {
            return (this.payload.map?.converging ?? []).includes(key);
        },

        outlineOf(key) {
            return this.payload.outlines?.[key] ?? null;
        },

        linksFrom(key) {
            return this.links.filter((l) => l.from === key);
        },

        face(index) {
            const cast = this.payload.cast ?? [];

            return cast.length ? cast[((index % cast.length) + cast.length) % cast.length] : null;
        },

        facesFor(key, count = 4) {
            const seed = (this.pieceOf(key)?.id ?? 0) * 3;

            return Array.from({ length: count }, (_, i) => this.face(seed + i)).filter(Boolean);
        },

        get validation() {
            return this.payload.validation ?? { valid: true, errors: [], warnings: [], stats: {} };
        },

        get stats() {
            return this.validation.stats ?? {};
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
        | Simular
        |----------------------------------------------------------------
        */

        async simulate() {
            if (this.running) return;

            this.running = true;
            this.blocked = [];
            this.tracking = null;

            try {
                const response = await fetch(this.simulateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ participants: this.participants }),
                });

                const data = await response.json();

                if (data.ok) {
                    this.result = data.result;
                } else {
                    /*
                     * El grafo tiene problemas bloqueantes y el servicio se
                     * niega a ejecutar. Es lo correcto: simular un torneo
                     * roto daría un resultado inventado.
                     */
                    this.result = null;
                    this.blocked = data.messages ?? ['No se pudo simular.'];
                }
            } catch (e) {
                this.result = null;
                this.blocked = ['No fue posible simular. Revisa la conexión.'];
            } finally {
                this.running = false;
            }
        },

        clear() {
            this.result = null;
            this.blocked = [];
            this.tracking = null;
        },

        get hasResult() {
            return this.result !== null;
        },


        /*
        |----------------------------------------------------------------
        | Leer el resultado
        |----------------------------------------------------------------
        */

        get summary() {
            return this.result?.summary ?? {};
        },

        /* Lo que le pasó a cada fase */
        get runNodes() {
            return Object.entries(this.result?.nodes ?? {})
                .map(([id, node]) => ({ ...node, id: parseInt(id, 10), key: 'NODE:' + id }));
        },

        runNodeOf(key) {
            return this.runNodes.find((n) => n.key === key) ?? null;
        },

        /* Dónde acabó cada uno */
        get runTerminals() {
            return Object.values(this.result?.terminals ?? {});
        },

        runTerminalOf(key) {
            const id = parseInt((key ?? '').split(':')[1], 10);

            return this.runTerminals.find((t) => t.id === id) ?? null;
        },

        /* Cuántos pasaron por una ruta */
        flowThrough(linkId) {
            return (this.result?.connections ?? {})[linkId]?.length ?? null;
        },

        get participantsRun() {
            return this.result?.participants ?? [];
        },

        get timeline() {
            return this.result?.timeline ?? [];
        },

        /*
         * Los que se perdieron por el camino.
         *
         * No es un detalle: significa que el recorrido tiene un agujero por
         * el que se cae gente, y es la razón principal por la que existe
         * este simulador.
         */
        get lostIds() {
            return this.summary.lost_ids ?? [];
        },

        isLost(participant) {
            return this.lostIds.includes(participant.preview_id);
        },

        /* Seguir a uno concreto por el recorrido */
        track(participant) {
            this.tracking = this.tracking?.preview_id === participant.preview_id
                ? null
                : participant;
        },

        isTracked(participant) {
            return this.tracking?.preview_id === participant.preview_id;
        },

        /* Por dónde pasó el que se está siguiendo */
        get trackedJourney() {
            return this.tracking?.journey ?? [];
        },

        /* Si el que se sigue pisó esta pieza del mapa */
        tracksThrough(key) {
            if (!this.tracking) return false;

            const [kind, id] = key.split(':');

            return this.trackedJourney.some(
                (step) => step.type === kind && String(step.id) === id
            );
        },

        get runErrors() {
            return this.result?.errors ?? [];
        },

        get runWarnings() {
            return this.result?.warnings ?? [];
        },
    };
}

/*
|--------------------------------------------------------------------------
| Super Edicion — base compartida
|--------------------------------------------------------------------------
|
| Lo que hace igual cualquier editor de fase, sea liga o fase de grupos:
|
|   - hablar con el servidor cuando cambia algo que altera la matematica
|   - saber quien ocupa cada semilla
|   - inventar marcadores para poder mirar como queda una tabla
|   - contar puntos y ordenar con los criterios que manda el servidor
|
| Lo que NO esta aqui: como se reparte la gente y como se dibuja. Eso es lo
| propio de cada motor y vive en su modulo.
|
| El reparto de trabajo, que es la decision de fondo de todo el editor:
|
|   SERVIDOR  la matematica. Cuantas jornadas, quien se empareja con quien,
|             en que grupo cae cada uno, si la configuracion es valida.
|             Sale de los calculadores de siempre.
|
|   CLIENTE   quien ocupa cada semilla y que pasa si se juega. Los
|             calendarios emparejan SEMILLAS, nunca personas, asi que
|             barajar o simular no toca ni una linea de matematica.
|
*/

export function superEditorBase(config) {

    return {

        payload: config.payload,

        previewUrl: config.previewUrl,

        /*
         * Solo lectura: la ficha de la fase.
         *
         * Reutiliza las mismas vistas del editor -el escenario, las
         * jornadas- porque dibujar un cuadro dos veces seria mantenerlo dos
         * veces. Lo unico que sobra ahi son los controles que CAMBIAN la
         * configuracion: en la ficha no hay boton de guardar, asi que
         * moverlos cambiaria el preview sin guardar nada y solo confundiria.
         *
         * Simular NO es cambiar la configuracion: los resultados inventados
         * nunca se guardaron, ni aqui ni en el editor, y ver como se llena
         * el cuadro es media razon de que esta pantalla exista.
         */
        readonly: config.readonly ?? false,

        participants: config.payload.contract.resolved,

        pinParticipants: config.payload.contract.is_pinned,

        /*
         * order[posicion - 1] = indice del reparto.
         *
         * Toda la reactividad pasa por aqui: cambiar el orden es reescribir
         * este array y nada mas.
         */
        order: [],

        /*
         * Resultados inventados. No se guardan NUNCA: existen para poder
         * ver como se llena una tabla, como ordena la cadena de desempate y
         * a quien se lleva cada puerta de salida, sin montar un torneo.
         */
        results: {},

        loading: false,
        dirty: false,

        refreshTimer: null,


        /*
        |----------------------------------------------------------------
        | Servidor
        |----------------------------------------------------------------
        */

        scheduleRefresh(extra = {}) {
            this.dirty = true;

            clearTimeout(this.refreshTimer);

            this.refreshTimer = setTimeout(() => this.refresh(extra), 280);
        },

        refresh(extra = {}) {
            const url = new URL(this.previewUrl, window.location.origin);

            url.searchParams.set('participants', this.participants);

            Object.entries(this.previewParams()).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    url.searchParams.set(key, value);
                }
            });

            Object.entries(extra).forEach(([key, value]) => {
                url.searchParams.set(key, value);
            });

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

                    /*
                     * Otro reparto, otros partidos: lo simulado ya no
                     * corresponde a nada.
                     */
                    this.clearResults();

                    this.afterRefresh(payload);

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

        /* Cada motor manda sus propios controles al preview */
        previewParams() {
            return {};
        },

        afterRefresh() {},


        /*
        |----------------------------------------------------------------
        | Reparto prestado
        |----------------------------------------------------------------
        */

        get castSize() {
            return this.payload.cast.length;
        },

        atSeed(seed) {
            if (!seed) {
                return null;
            }

            return this.payload.cast[this.order[seed - 1]] ?? null;
        },

        shuffled(list) {
            const out = [...list];

            for (let i = out.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));

                [out[i], out[j]] = [out[j], out[i]];
            }

            return out;
        },

        identity() {
            return Array.from({ length: this.castSize }, (_, i) => i);
        },


        /*
        |----------------------------------------------------------------
        | Simulacion
        |----------------------------------------------------------------
        */

        get hasResults() {
            return Object.keys(this.results).length > 0;
        },

        get playedCount() {
            return Object.keys(this.results).length;
        },

        /*
         * Un marcador cualquiera. Si la fase no admite empates se fuerza un
         * ganador, igual que hace el motor.
         */
        rollScore() {
            let a = Math.floor(Math.random() * 5);
            let b = Math.floor(Math.random() * 5);

            if (!this.allowDraws && a === b) {
                Math.random() < 0.5 ? a++ : b++;
            }

            return { a, b };
        },

        resultAt(key) {
            return this.results[key] ?? null;
        },

        simulateKey(key) {
            this.results[key] = this.rollScore();
        },

        clearResults() {
            this.results = {};
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

        emptyRow(seed) {
            return {
                seed,
                PLAYED: 0, WINS: 0, DRAWS: 0, LOSSES: 0,
                SCORE_FOR: 0, SCORE_AGAINST: 0, SCORE_DIFFERENCE: 0,
                POINTS: 0,
            };
        },

        /*
         * Cuenta una lista de partidos sobre un conjunto de semillas.
         *
         * matches = [{ seedA, seedB, result }]
         */
        tally(seeds, matches) {
            const rows = new Map();

            seeds.forEach((seed) => rows.set(seed, this.emptyRow(seed)));

            matches.forEach(({ seedA, seedB, result }) => {

                if (!result) return;

                const a = rows.get(seedA);
                const b = rows.get(seedB);

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

            const out = [...rows.values()];

            out.forEach((row) => {
                row.SCORE_DIFFERENCE = row.SCORE_FOR - row.SCORE_AGAINST;
                row.POINTS = Math.round(row.POINTS * 100) / 100;
            });

            return out;
        },

        /* Ordena con la cadena del servidor */
        rank(rows) {
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

        /*
         * Aviso de contrato. No se corrige el numero por su cuenta: se dice
         * lo que pasa y se deja decidir.
         */
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
    };
}

/*
 * Une varios objetos CONSERVANDO sus getters.
 *
 * Object.assign y el spread copian el VALOR que devuelve un getter en ese
 * instante, no el getter: `structure` o `classified` quedarian congelados
 * en su primer valor y la pantalla no reaccionaria a nada. Con los
 * descriptores se copia la propiedad entera.
 */
export function mergeParts(...parts) {
    const out = {};

    parts.forEach((part) => {
        Object.defineProperties(out, Object.getOwnPropertyDescriptors(part));
    });

    return out;
}

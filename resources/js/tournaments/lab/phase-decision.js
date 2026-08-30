/*
|--------------------------------------------------------------------------
| La decisión que el motor está esperando
|--------------------------------------------------------------------------
|
| Una fase configurada «a mano» —grupos manuales, orden manual, BYEs
| elegidos— no arranca sola: el motor la deja en AWAITING_DECISION y espera
| a que alguien decida. El backend siempre supo resolverlo
| (RESOLVE_MANUAL_DECISION), pero la pantalla de juego no lo ofrecía: ponía
| «abrir la fase», y abrir exige un recorrido en marcha, así que respondía
| «el Tournament Graph Runtime no está en ejecución» —cierto, y sin ninguna
| pista de qué hacer—.
|
| Esto responde esa pregunta, con las caras delante.
|
| ---------------------------------------------------------------
|
| Hace su propia llamada en vez de pedírsela al componente de la arena.
| Un x-data anidado hereda el ámbito para EVALUAR expresiones, pero no para
| el `this` de sus propios métodos: `this.execute` sería undefined. Por eso
| la URL y la revisión viajan en la configuración.
|
*/
export default function phaseDecision(config = {}) {
    return {
        nodeId: config.nodeId,
        decisionId: config.decisionId,
        type: config.type,
        participants: config.participants ?? [],
        groups: config.groups ?? [],
        byeCount: config.byeCount ?? 0,

        actionUrl: config.actionUrl,
        revision: config.revision ?? 0,

        /* clave del competidor → clave del grupo */
        assignments: {},

        /* el que está seleccionado esperando grupo */
        selected: null,

        ordered: [],
        byes: [],

        sending: false,
        error: '',

        init() {
            this.ordered = [...this.participants];
        },

        /*
        |----------------------------------------------------------------------
        | Grupos
        |----------------------------------------------------------------------
        */

        inGroup(groupKey) {
            return this.participants.filter(
                (c) => this.assignments[c.key] === groupKey
            );
        },

        get unassigned() {
            return this.participants.filter((c) => !this.assignments[c.key]);
        },

        /*
         * Poner al seleccionado en un grupo. Sin nadie seleccionado no hace
         * nada: pulsar un grupo por curiosidad no debería mover a nadie.
         */
        dropInto(groupKey) {
            if (!this.selected) return;

            this.assignments[this.selected] = groupKey;
            this.selected = null;
        },

        takeOut(key) {
            delete this.assignments[key];
        },

        clearAll() {
            this.assignments = {};
            this.selected = null;
        },

        /*
         * Reparto automático respetando las capacidades. No es una
         * decisión disfrazada: es el punto de partida que casi siempre se
         * quiere, y se puede mover a mano después. Con doce competidores y
         * cuatro grupos, hacerlo clic a clic desde cero es un castigo.
         */
        spreadEvenly() {
            this.assignments = {};
            this.selected = null;

            const cola = [...this.participants];

            this.groups.forEach((g) => {
                for (let i = 0; i < g.size && cola.length; i++) {
                    this.assignments[cola.shift().key] = g.key;
                }
            });
        },

        /*
        |----------------------------------------------------------------------
        | Orden y descansos
        |----------------------------------------------------------------------
        */

        move(index, delta) {
            const destino = index + delta;

            if (destino < 0 || destino >= this.ordered.length) return;

            const lista = [...this.ordered];

            [lista[index], lista[destino]] = [lista[destino], lista[index]];

            this.ordered = lista;
        },

        isBye(key) {
            return this.byes.includes(key);
        },

        toggleBye(key) {
            this.byes = this.isBye(key)
                ? this.byes.filter((x) => x !== key)
                : [...this.byes, key];
        },

        /*
        |----------------------------------------------------------------------
        | Enviar
        |----------------------------------------------------------------------
        */

        get ready() {
            if (this.type === 'GROUP_ASSIGNMENT') {
                return this.unassigned.length === 0
                    && this.groups.every(
                        (g) => this.inGroup(g.key).length === g.size
                    );
            }

            if (this.byeCount > 0) {
                return this.byes.length === this.byeCount;
            }

            return this.ordered.length === this.participants.length;
        },

        /* Qué falta exactamente, no un «revisa los datos» */
        get readyMessage() {
            if (this.ready) return 'Todo listo.';

            if (this.type === 'GROUP_ASSIGNMENT') {
                if (this.unassigned.length) {
                    return 'Faltan ' + this.unassigned.length
                        + (this.unassigned.length === 1
                            ? ' competidor por colocar.'
                            : ' competidores por colocar.');
                }

                const malos = this.groups.filter(
                    (g) => this.inGroup(g.key).length !== g.size
                );

                return malos
                    .map((g) => g.name + ' necesita ' + g.size)
                    .join(' · ');
            }

            if (this.byeCount > 0) {
                return 'Marca ' + this.byeCount + ' descanso(s).';
            }

            return '';
        },

        payload() {
            if (this.type === 'GROUP_ASSIGNMENT') {
                return { group_assignments: this.assignments };
            }

            const datos = {
                ordered_participant_ids: this.ordered.map((c) => c.key),
            };

            if (this.byeCount > 0) {
                datos.selected_participant_ids = this.byes;
            }

            return datos;
        },

        async submit() {
            if (!this.ready || this.sending) return;

            this.sending = true;
            this.error = '';

            try {
                const response = await fetch(this.actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        action: 'RESOLVE_MANUAL_DECISION',
                        revision: this.revision,
                        node_id: this.nodeId,
                        decision_id: this.decisionId,
                        ...this.payload(),
                    }),
                });

                const cuerpo = await response.json();

                if (!response.ok) {
                    this.error =
                        Object.values(cuerpo.errors ?? {}).flat().join(' ')
                        || cuerpo.message
                        || 'No fue posible registrar la decisión.';

                    return;
                }

                /*
                 * Recargar a propósito: al resolverla, la fase se prepara y
                 * aparecen los enfrentamientos, la clasificación y el
                 * cuadro. Reconstruir todo eso en el cliente sería duplicar
                 * el proyector del servidor.
                 */
                window.location.reload();
            } catch (e) {
                this.error = 'No fue posible contactar con el servidor.';
            } finally {
                this.sending = false;
            }
        },
    };
}

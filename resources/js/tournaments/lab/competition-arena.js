import competitionLab from './competition-lab';

/*
|--------------------------------------------------------------------------
| competitionArena
|--------------------------------------------------------------------------
|
| La capa de puesta en escena sobre el motor persistente.
|
| Existe como componente propio y no como Object.assign en la plantilla por
| un motivo concreto: Object.assign NO copia getters, copia el valor que
| devuelven en ese instante. `battle` y `liveEncounter` quedaban congelados
| a null en el momento de construir el componente y la pantalla de batalla
| no mostraba nada nunca.
|
| Aquí los getters se definen sobre el objeto final, así que reaccionan.
|
*/

export default function competitionArena(config) {

    const lab = competitionLab(config);

    return {

        ...lab,

        stage: config.initialStage ?? 2,

        readonly: config.readonly ?? false,

        battles: config.battles ?? {},

        selectedBattle: null,

        /*
         * Dialogo de ajuste manual de stats.
         *
         * Vive aqui y no en la vista porque tocar una stat guardada es un
         * cambio permanente: el estado arranca sin confirmar y se
         * reinicia en cada apertura, para que la casilla marcada de la
         * vez anterior nunca herede a la siguiente.
         */
        adjust: { open: false, entityId: null, name: '', confirmed: false },

        openAdjust(entityId, name) {
            this.adjust = { open: true, entityId, name, confirmed: false };
        },

        /*
         * Enfrentamientos resueltos durante ESTA sesion, por batalla.
         *
         * Hace falta porque al avanzar al siguiente el Runtime limpia
         * state.encounter, y el payload del servidor solo se refresca al
         * recargar: sin esto, el resultado que acabas de sacar desaparece
         * de la pantalla en cuanto pulsas "siguiente".
         */
        sessionEncounters: {},

        /* Batalla abierta, con lo ya jugado que vino del servidor */
        get battle() {
            if (!this.selectedBattle) {
                return null;
            }

            return this.battles[this.selectedBattle] ?? null;
        },

        /*
         * El enfrentamiento en curso lo manda el Runtime en cada acción.
         * Solo se muestra si pertenece a la batalla que estamos viendo.
         */
        get liveEncounter() {
            const encounter = this.state?.encounter ?? null;

            if (!encounter) {
                return null;
            }

            return encounter.battle_key === this.selectedBattle
                ? encounter
                : null;
        },

        get hasLiveEncounter() {
            return this.liveEncounter !== null;
        },

        /*
         * Lo jugado en esta batalla: lo que trajo el servidor mas lo que
         * se ha resuelto sin recargar. Se deduplica por numero.
         */
        get playedEncounters() {
            const fromServer = this.battle?.encounters ?? [];

            const fromSession = this.selectedBattle
                ? (this.sessionEncounters[this.selectedBattle] ?? [])
                : [];

            const byNumber = new Map();

            fromServer.forEach((encounter) => byNumber.set(encounter.number, encounter));
            fromSession.forEach((encounter) => byNumber.set(encounter.number, encounter));

            return Array.from(byNumber.values())
                .sort((a, b) => a.number - b.number);
        },

        /* Guarda un enfrentamiento ya resuelto para no perderlo de vista */
        rememberEncounter(encounter) {
            if (!encounter || encounter.status !== 'RESOLVED') {
                return;
            }

            const key = encounter.battle_key;

            if (!this.sessionEncounters[key]) {
                this.sessionEncounters[key] = [];
            }

            const already = this.sessionEncounters[key]
                .some((row) => row.number === encounter.number);

            if (already) {
                return;
            }

            this.sessionEncounters[key].push({
                number: encounter.number,
                is_draw: encounter.is_draw,
                is_tiebreak: encounter.is_tiebreak ?? false,
                summary: encounter.summary,
                values: encounter.participants.map((participant) => ({
                    key: participant.id,
                    name: participant.name,
                    display: participant.display,
                    position: participant.position,
                    is_winner: participant.is_winner,
                })),
            });
        },

        init() {
            /*
             * Lo que hace competitionLab en modo persistente. No se puede
             * delegar en lab.init porque el spread lo deja sobrescrito.
             */
            if (this.state) {
                this.afterStateChange();
            }

            const stage = new URLSearchParams(window.location.search).get('stage');

            if (stage) {
                this.stage = parseInt(stage, 10);
            }

            const battle = new URLSearchParams(window.location.search).get('battle');

            if (battle && this.battles[battle]) {
                this.selectedBattle = battle;
            }
        },

        /*
         * Abrir una batalla. Si es jugable se le pide al motor que prepare
         * su enfrentamiento; si ya está jugada, solo se muestra.
         */
        openBattle(key) {
            if (!this.battles[key]) {
                return;
            }

            this.selectedBattle = key;
            this.stage = 3;

            const battle = this.battles[key];

            if (battle.is_playable && !this.readonly) {
                this.execute('PREPARE_ENCOUNTER', { match_id: key });
            }
        },

        /*
         * Volver recarga a propósito: el cuadro, las clasificaciones y el
         * resto de batallas cambian con cada resultado, y reconstruirlos
         * en el cliente sería duplicar el proyector del servidor.
         */
        backToStructure() {
            window.location.href = window.location.pathname + '?stage=2';
        },

        /* Abre la fase hasta que haya una batalla jugable */
        openArena() {
            return this.execute('ADVANCE_TO_PLAYABLE')
                .then(() => window.location.reload());
        },

        startAndOpen() {
            return this.execute('START_TOURNAMENT')
                .then(() => this.execute('ADVANCE_TO_PLAYABLE'))
                .then(() => window.location.reload());
        },

        rollOne(participantId) {
            return this.execute('ROLL_ENCOUNTER', {
                participant_id: participantId,
                match_id: this.selectedBattle,
            }).then(() => this.rememberEncounter(this.liveEncounter));
        },

        rollAll() {
            return this.execute('ROLL_ENCOUNTER', {
                all: true,
                match_id: this.selectedBattle,
            }).then(() => this.rememberEncounter(this.liveEncounter));
        },

        nextEncounter() {
            const encounter = this.liveEncounter;

            /* Se guarda ANTES de avanzar: despues ya no existe */
            this.rememberEncounter(encounter);

            if (encounter?.battle_completed) {
                this.backToStructure();

                return Promise.resolve();
            }

            return this.execute('ADVANCE_ENCOUNTER', {
                match_id: this.selectedBattle,
            }).then(() => {
                /*
                 * Si el motor no dejo listo el siguiente enfrentamiento de
                 * ESTA batalla, quedarse aqui seria dejar la pantalla sin
                 * controles y sin explicacion —que es justo el sintoma que
                 * se arreglo en el servidor—. Se vuelve a la estructura,
                 * que si sabe representar lo que ha pasado.
                 */
                if (!this.error && !this.hasLiveEncounter) {
                    this.backToStructure();
                }
            });
        },
    };
}

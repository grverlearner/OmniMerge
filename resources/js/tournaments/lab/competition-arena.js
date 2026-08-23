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

        /*
         * Indice ligero: nombre, ronda, grupo y estado de cada batalla.
         * Lo justo para pintar la cabecera en cuanto se pulsa.
         */
        battles: config.battles ?? {},

        /*
         * El detalle de las que se han abierto, cacheado por clave.
         *
         * Antes venia todo dentro de la pagina —las 238 batallas con sus
         * trofeos, atributos, historial de duelos y valores de cada
         * juego—, unos 5 segundos de servidor y 4 MB de HTML para mirar
         * una. Ahora se pide al abrir y se guarda: volver a una batalla
         * ya vista no cuesta nada.
         */
        battleDetails: {},

        battleUrl: config.battleUrl ?? null,

        battleLoading: false,

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

        /* Batalla abierta, con su detalle ya cargado */
        get battle() {
            if (!this.selectedBattle) {
                return null;
            }

            return this.battleDetails[this.selectedBattle] ?? null;
        },

        /* Lo poco que se sabe de una batalla sin haberla abierto */
        get battleSummary() {
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
         * Como va la BATALLA para un competidor, ahora mismo.
         *
         * El enfrentamiento en curso ya se ve —el numero grande, quien
         * gana este—, pero la batalla es la serie entera: en un BO5 ganar
         * este juego puede significar cerrarla o solo empatarla, y eso no
         * se veia por ninguna parte hasta bajar al historial y contar a
         * mano.
         *
         * Sale del Runtime, que actualiza el marcador de la serie en
         * cuanto un enfrentamiento se resuelve, asi que cambia solo.
         *
         * Devuelve null cuando no hay serie a dos —los encuentros de
         * seleccion, donde compiten N a la vez, no tienen batalla que
         * marcar—, y la vista simplemente no pinta nada.
         */
        seriesStandingOf(participantId) {
            const series = this.liveEncounter?.series;

            const sides = this.battle?.participants ?? [];

            if (!series || sides.length < 2) {
                return null;
            }

            const isA = sides[0]?.key === participantId;
            const isB = sides[1]?.key === participantId;

            if (!isA && !isB) {
                return null;
            }

            const wins = isA ? (series.score_a ?? 0) : (series.score_b ?? 0);
            const rivalWins = isA ? (series.score_b ?? 0) : (series.score_a ?? 0);

            const isFixed = series.format === 'FIXED_GAMES';
            const played = series.games_played ?? 0;
            const draws = series.draws ?? 0;

            /*
             * Un enfrentamiento anterior a este campo no trae el total.
             * Se deduce de lo jugado en vez de mentir con un cero.
             */
            const total = series.total_games ?? Math.max(played, wins + rivalWins + draws);

            const losses = Math.max(0, played - wins - draws);

            /*
             * Un punto por enfrentamiento: ganados, empatados, perdidos y
             * los que faltan. Es un recuento, no un orden cronologico
             * —para eso esta el historial de abajo.
             */
            const pips = [];

            for (let i = 0; i < wins; i++) { pips.push('win'); }
            for (let i = 0; i < draws; i++) { pips.push('draw'); }
            for (let i = 0; i < losses; i++) { pips.push('loss'); }

            while (pips.length < total) { pips.push('pending'); }

            const target = isFixed ? null : (series.wins_required ?? null);

            return {
                wins,
                rivalWins,
                isFixed,
                target,
                total,
                played,
                pips,
                leading: wins > rivalWins,
                tied: wins === rivalWins,

                /* Le falta ganar uno para llevarse la batalla */
                matchPoint: target !== null && wins === target - 1,

                /* Ya la tiene ganada */
                won: target !== null && wins >= target,
            };
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

                /* Se llega por URL: el detalle tambien hay que traerlo */
                this.loadBattle(battle);
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

            return this.loadBattle(key).then((battle) => {

                /*
                 * Si se puede jugar lo decide el servidor, en el mismo
                 * sitio que lo decide para todo lo demas. Duplicar aqui
                 * la regla —pendiente, ni cerrada, ni pausada— seria
                 * tener dos verdades que se pueden separar.
                 */
                if (battle?.is_playable && !this.readonly) {
                    return this.execute('PREPARE_ENCOUNTER', { match_id: key });
                }
            });
        },

        /*
         * El detalle de una batalla. Ya visto, del cache; nuevo, del
         * servidor. Si falla no se deja la pantalla en blanco: se avisa
         * y se vuelve a la estructura.
         */
        loadBattle(key) {
            if (this.battleDetails[key]) {
                return Promise.resolve(this.battleDetails[key]);
            }

            if (!this.battleUrl) {
                return Promise.resolve(null);
            }

            this.battleLoading = true;

            return fetch(this.battleUrl.replace('__BATTLE__', encodeURIComponent(key)), {
                headers: { Accept: 'application/json' },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('respuesta ' + response.status);
                    }

                    return response.json();
                })
                .then((battle) => {
                    this.battleDetails[key] = battle;

                    return battle;
                })
                .catch(() => {
                    this.error = 'No fue posible cargar esta batalla.';

                    return null;
                })
                .finally(() => {
                    this.battleLoading = false;
                });
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

            /*
             * Incluso con la batalla terminada se avisa al motor.
             *
             * Antes se volvia directo a la estructura, y el motor se
             * quedaba con su ultima operacion sin ejecutar: al acabar la
             * FINAL, nadie enrutaba al ganador, la competicion nunca
             * pasaba a COMPLETED y ni el resultado ni los premios llegaban
             * a existir. La partida se ganaba y no se cerraba.
             */
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

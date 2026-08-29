/*
 * TROFEOS Y PREMIOS DE UNA EDICIÓN
 *
 * Hay dos capas y no se mezclan:
 *
 *   los del TORNEO      los hereda toda edición. Se ven, no se tocan.
 *                       Corregirlos desde aquí cambiaría también las
 *                       ediciones ya jugadas que ya los entregaron.
 *   los de ESTA edición  se crean, se corrigen y se retiran aquí mismo.
 *
 * Y un premio de edición puede colgar de una FASE —«quien gane los grupos
 * se lleva esto»—, que es algo que un premio de torneo no sabe decir,
 * porque el torneo no sabe con qué plantilla se jugará cada año.
 *
 * Todo se hace en esta pantalla: crear un trofeo con su imagen incluida.
 * Mandar a otra ventana en mitad de configurar una edición hace perder lo
 * que se lleva escrito.
 */
export default function competitionPrizes(config) {
    return {
        /* Los del universo y del torneo: se ven, no se tocan */
        shared: config.sharedTrophies ?? [],

        /* Los inventados para esta edición */
        own: config.ownTrophies ?? [],

        inheritedRewards: config.inheritedRewards ?? [],

        rewards: config.ownRewards ?? [],

        /* Qué premio está abierto para editar */
        expanded: [],

        pickerOpen: false,

        /* Las fases de la forma elegida, para poder colgar un premio */
        phases: [],

        /* Estadísticas del juego elegido arriba */
        gameStats: [],

        storeUrl: config.storeUrl,
        updateUrlTemplate: config.updateUrlTemplate,
        competitionId: config.competitionId ?? null,
        csrf: config.csrf,

        init() {
            /* Los ya guardados nacen plegados; los nuevos, abiertos */
            this.expanded = [];
        },

        /*
        |----------------------------------------------------------------------
        | Los trofeos
        |----------------------------------------------------------------------
        */

        get allTrophies() {
            return [...this.own, ...this.shared];
        },

        /*
         * Los que el TORNEO ya entrega. Esta edición los hereda y no los
         * elige: aparecen siempre, se juegue lo que se juegue.
         */
        get inheritedTrophies() {
            return this.shared.filter((t) => t.inherited);
        },

        /* Los que añade ESTA edición, por sus propios premios */
        get ownGivenTrophies() {
            const ids = [...new Set(
                this.rewards
                    .map((r) => String(r.universe_trophy_id))
                    .filter((id) => id && id !== 'null')
            )];

            return ids
                .map((id) => this.allTrophies.find((t) => String(t.id) === id))
                .filter(Boolean)
                /* Los heredados ya salen arriba: repetirlos sería mentir */
                .filter((t) => !t.inherited);
        },

        /* Todo lo que se entregará al terminar, venga de donde venga */
        get givenTrophies() {
            return [...this.inheritedTrophies, ...this.ownGivenTrophies];
        },

        get availableTrophies() {
            const used = this.givenTrophies.map((t) => String(t.id));

            return this.allTrophies.filter((t) => !used.includes(String(t.id)));
        },

        trophyOf(reward) {
            if (!reward.universe_trophy_id) return null;

            return this.allTrophies
                .find((t) => String(t.id) === String(reward.universe_trophy_id)) ?? null;
        },

        isOwnTrophy(trophy) {
            return trophy ? this.own.some((t) => String(t.id) === String(trophy.id)) : false;
        },

        /* De dónde viene un trofeo, para poder decirlo en su ficha */
        trophyOrigin(trophy) {
            if (!trophy) return '';
            if (trophy.inherited) return 'lo da el torneo';
            if (this.isOwnTrophy(trophy)) return 'solo en esta edición';
            return 'del universo';
        },

        /* Cuántos premios se entregan en total, heredados incluidos */
        get totalRewards() {
            return this.inheritedRewards.length + this.rewards.length;
        },

        /* Los premios propios que cuelgan de una fase concreta */
        rewardsOfPhase(nodeId) {
            return this.rewards.filter((r) => String(r.node_id) === String(nodeId));
        },

        useTrophy(trophy) {
            this.addReward({
                universe_trophy_id: trophy.id,
                label: trophy.name,
            });

            this.pickerOpen = false;
        },

        rewardsWithTrophy(trophyId) {
            return this.rewards
                .filter((r) => String(r.universe_trophy_id) === String(trophyId))
                .length;
        },

        /*
        |----------------------------------------------------------------------
        | El taller del trofeo
        |----------------------------------------------------------------------
        |
        | Un <form> dentro de otro no existe en HTML, y un trofeo lleva
        | imagen. Así que este bloque se envía por su cuenta y devuelve el
        | trofeo guardado, que entra en la lista sin recargar.
        */

        trophyOpen: false,
        trophyEditing: null,
        trophyBusy: false,
        trophyErrors: {},
        trophyFile: null,
        trophyPreview: null,

        trophyForm: {
            name: '',
            description: '',
            icon: '🏆',
            tier: 'GOLD',
        },

        openTrophy(trophy = null) {
            /*
             * Un trofeo del torneo no se corrige desde una edición: se
             * hereda tal cual. Se dice en vez de abrir un formulario que
             * el servidor iba a rechazar.
             */
            if (trophy && !this.isOwnTrophy(trophy)) {
                this.trophyErrors = {
                    general: ['Este trofeo es del torneo, no de esta edición: se hereda '
                        + 'tal cual. Para uno propio, crea uno nuevo aquí.'],
                };

                this.trophyOpen = true;
                this.trophyEditing = null;

                return;
            }

            this.trophyEditing = trophy;
            this.trophyErrors = {};
            this.trophyFile = null;
            this.trophyPreview = trophy?.image_url ?? null;

            this.trophyForm = {
                name: trophy?.name ?? '',
                description: trophy?.description ?? '',
                icon: trophy?.icon ?? '🏆',
                tier: trophy?.tier ?? 'GOLD',
            };

            this.trophyOpen = true;
        },

        closeTrophy() {
            this.trophyOpen = false;
            this.trophyEditing = null;
            this.trophyErrors = {};
        },

        pickTrophyImage(event) {
            const file = event.target.files?.[0];

            if (!file) return;

            this.trophyFile = file;
            this.trophyPreview = URL.createObjectURL(file);
        },

        async saveTrophy() {
            this.trophyBusy = true;
            this.trophyErrors = {};

            const body = new FormData();

            Object.entries(this.trophyForm).forEach(([k, v]) => body.append(k, v ?? ''));

            if (this.trophyFile) body.append('image', this.trophyFile);

            /*
             * El ámbito viaja siempre: es lo que marca el trofeo como de
             * esta edición al crearlo, y lo que impide corregir uno del
             * torneo al editar.
             */
            if (this.competitionId) {
                body.append('tournament_instance_id', this.competitionId);
            }

            let url = this.storeUrl;

            if (this.trophyEditing) {
                url = this.updateUrlTemplate.replace('__ID__', this.trophyEditing.id);
                body.append('_method', 'PUT');
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                    },
                    body,
                });

                const data = await response.json();

                if (!response.ok) {
                    this.trophyErrors = data.errors ?? { general: [data.message ?? 'No se pudo guardar.'] };
                    return;
                }

                this.absorb(data.trophy);
                this.closeTrophy();
            } catch (e) {
                this.trophyErrors = { general: ['No se pudo guardar el trofeo.'] };
            } finally {
                this.trophyBusy = false;
            }
        },

        /* El trofeo devuelto entra en la lista sin recargar */
        absorb(trophy) {
            const lista = trophy.own ? this.own : this.shared;

            const i = lista.findIndex((t) => String(t.id) === String(trophy.id));

            if (i === -1) lista.push(trophy);
            else lista[i] = trophy;

            /* Recién creado: se ofrece ya para entregarlo */
            if (i === -1 && trophy.own) this.useTrophy(trophy);
        },

        firstError(field) {
            return this.trophyErrors[field]?.[0] ?? null;
        },

        /*
        |----------------------------------------------------------------------
        | Los premios
        |----------------------------------------------------------------------
        */

        addReward(preset = {}) {
            this.rewards.push({
                node_id: '',
                trigger: 'POSITION',
                threshold: '',
                universe_trophy_id: '',
                stat_key: '',
                operation: 'ADD',
                amount: '',
                label: '',
                carry_forward: true,
                ...preset,
            });

            /* Recién creado está vacío: se abre para poder rellenarlo */
            this.expanded.push(this.rewards.length - 1);
        },

        removeReward(index) {
            this.rewards.splice(index, 1);

            /*
             * Los índices por encima del borrado se desplazan uno. Sin
             * recolocarlos, borrar el primero deja abierto el que ocupaba
             * su sitio en vez del que estaba abierto.
             */
            this.expanded = this.expanded
                .filter((i) => i !== index)
                .map((i) => (i > index ? i - 1 : i));
        },

        toggleReward(index) {
            this.expanded = this.isExpanded(index)
                ? this.expanded.filter((i) => i !== index)
                : [...this.expanded, index];
        },

        isExpanded(index) {
            return this.expanded.includes(index);
        },

        /* El podio de golpe: es lo que casi toda edición tiene */
        addPodium() {
            [
                ['Campeón', 1],
                ['Subcampeón', 2],
                ['Tercer puesto', 3],
            ].forEach(([label, puesto]) => {
                this.addReward({ label, trigger: 'POSITION', threshold: puesto });
            });
        },

        needsThreshold(reward) {
            return ['POSITION', 'WIN_COUNT', 'ENCOUNTER_WIN_COUNT'].includes(reward.trigger);
        },

        thresholdLabel(reward) {
            return {
                POSITION: 'Puesto',
                WIN_COUNT: 'Batallas',
                ENCOUNTER_WIN_COUNT: 'Enfrentamientos',
            }[reward.trigger] ?? 'Cuántos';
        },

        phaseName(nodeId) {
            if (!nodeId) return null;

            return this.phases.find((p) => String(p.id) === String(nodeId))?.name ?? null;
        },

        /* El resumen de una línea: cuándo se gana */
        rewardWhen(reward) {
            const cuando = {
                POSITION: 'Puesto ' + (reward.threshold || '?'),
                PARTICIPATION: 'Por participar',
                UNBEATEN: 'Invicto',
                WIN_COUNT: (reward.threshold || '?') + ' batallas',
                ENCOUNTER_WIN_COUNT: (reward.threshold || '?') + ' enfrentamientos',
            }[reward.trigger] ?? reward.trigger;

            const fase = this.phaseName(reward.node_id);

            return fase ? cuando + ' · ' + fase : cuando;
        },

        /* Y lo que da, también en corto */
        rewardGives(reward) {
            const partes = [];

            const trophy = this.trophyOf(reward);

            if (trophy) partes.push((trophy.icon || '🏆') + ' ' + trophy.name);

            if (reward.stat_key) {
                const stat = this.gameStats.find((s) => s.key === reward.stat_key);

                const signo = { ADD: '+', SUBTRACT: '−', MULTIPLY: '×', SET: '=' }[reward.operation] ?? '';

                partes.push(signo + (reward.amount || 0) + ' ' + (stat?.label ?? reward.stat_key));
            }

            return partes.length ? partes.join(' · ') : 'no da nada';
        },

        rewardGivesNothing(reward) {
            return !reward.universe_trophy_id && !reward.stat_key;
        },

        /* Cómo se leerá, en una frase */
        rewardText(reward) {
            if (this.rewardGivesNothing(reward)) {
                return 'Este premio no otorga nada todavía, así que se descartará al guardar.';
            }

            const fase = this.phaseName(reward.node_id);

            const quien = {
                POSITION: 'Quien acabe en el puesto ' + (reward.threshold || 1),
                PARTICIPATION: 'Todo el que participe',
                UNBEATEN: 'Quien termine invicto',
                WIN_COUNT: 'Quien gane ' + (reward.threshold || 1) + ' batallas',
                ENCOUNTER_WIN_COUNT: 'Quien gane ' + (reward.threshold || 1) + ' enfrentamientos',
            }[reward.trigger] ?? reward.trigger;

            return quien
                + (fase ? ' de «' + fase + '»' : ' de esta edición')
                + ' recibe ' + this.rewardGives(reward) + '.';
        },
    };
}

/*
|--------------------------------------------------------------------------
| Trofeos y premios, sin salir de la pantalla
|--------------------------------------------------------------------------
|
| Dos cosas distintas que viajan por caminos distintos, y la razón importa:
|
|   TROFEOS      son del universo y se comparten entre torneos. Llevan
|                imagen, así que se guardan por su cuenta con un FormData.
|                Un formulario dentro de otro no existe en HTML.
|
|   RECOMPENSAS  son de ESTE torneo. Viajan dentro del formulario grande,
|                como las reglas de participación: al crear un torneo aún no
|                hay fila a la que colgarlas.
|
| Este módulo vive anidado dentro del diseñador, así que lee `game` del
| ámbito padre por la cadena de scopes de Alpine.
|
*/

export default function tournamentPrizes(config) {

    return {

        trophies: config.trophies ?? [],

        rewards: (config.rewards ?? []).map((r) => ({
            trigger: r.trigger ?? 'POSITION',
            threshold: r.threshold ?? '',
            universe_trophy_id: r.universe_trophy_id ?? '',
            stat_key: r.stat_key ?? '',
            operation: r.operation ?? 'ADD',
            amount: r.amount ?? '',
            label: r.label ?? '',
        })),

        storeUrl: config.storeUrl,
        updateUrlTemplate: config.updateUrlTemplate,
        csrf: config.csrf,

        /* El catálogo del universo, plegado: aquí solo importa ESTE torneo */
        pickerOpen: false,

        /*
         * Qué premios están abiertos para editar.
         *
         * Los ya guardados se ven resumidos en una línea: un premio
         * configurado no necesita seis campos a la vista, necesita decir qué
         * hace. Los recién creados se abren solos, porque están vacíos y hay
         * que rellenarlos.
         */
        expanded: [],


        /*
        |----------------------------------------------------------------
        | El taller del trofeo
        |----------------------------------------------------------------
        */

        trophyOpen: false,
        trophySaving: false,
        trophyError: null,
        trophyPreview: null,
        trophyFile: null,

        trophyForm: { id: null, name: '', description: '', icon: '🏆', tier: 'GOLD' },

        openTrophy(trophy) {
            this.trophyError = null;
            this.trophyFile = null;

            if (trophy) {
                this.trophyForm = {
                    id: trophy.id,
                    name: trophy.name ?? '',
                    description: trophy.description ?? '',
                    icon: trophy.icon ?? '🏆',
                    tier: trophy.tier ?? 'GOLD',
                };

                this.trophyPreview = trophy.image_url ?? null;
            } else {
                this.trophyForm = { id: null, name: '', description: '', icon: '🏆', tier: 'GOLD' };
                this.trophyPreview = null;
            }

            this.trophyOpen = true;
        },

        closeTrophy() {
            this.trophyOpen = false;
            this.trophyError = null;
        },

        pickTrophyImage(event) {
            const file = event.target.files?.[0];

            if (!file) return;

            this.trophyFile = file;
            this.trophyPreview = URL.createObjectURL(file);
        },

        /*
         * Se guarda al momento y sin tocar el resto del torneo.
         *
         * Va por POST incluso al editar: el navegador manda la imagen en un
         * FormData, y un PUT con multipart no llega parseado a PHP. La
         * intención se declara con _method dentro del cuerpo.
         */
        async saveTrophy() {
            if (this.trophySaving || !this.trophyForm.name) return;

            this.trophySaving = true;
            this.trophyError = null;

            const body = new FormData();

            body.append('_token', this.csrf);
            body.append('name', this.trophyForm.name);
            body.append('description', this.trophyForm.description ?? '');
            body.append('icon', this.trophyForm.icon ?? '');
            body.append('tier', this.trophyForm.tier);

            if (this.trophyFile) body.append('image', this.trophyFile);

            const editing = this.trophyForm.id !== null;

            if (editing) body.append('_method', 'PUT');

            const url = editing
                ? this.updateUrlTemplate.replace('__ID__', this.trophyForm.id)
                : this.storeUrl;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body,
                });

                const data = await response.json();

                if (!response.ok || !data.ok) {
                    this.trophyError = this.firstError(data)
                        ?? 'No se pudo guardar el trofeo.';

                    return;
                }

                this.absorb(data.trophy);
                this.trophyOpen = false;
                this.trophyFile = null;
            } catch (e) {
                this.trophyError = 'No fue posible guardar. Revisa la conexión.';
            } finally {
                this.trophySaving = false;
            }
        },

        /* El trofeo guardado entra en la vitrina, sea nuevo o editado */
        absorb(trophy) {
            const at = this.trophies.findIndex((t) => t.id === trophy.id);

            const esNuevo = at === -1;

            if (esNuevo) {
                this.trophies.push(trophy);
            } else {
                this.trophies[at] = trophy;
            }

            this.trophies.sort((a, b) => a.name.localeCompare(b.name));

            /*
             * Un trofeo recién creado se entrega.
             *
             * «Trofeos de este torneo» sale de los premios que los otorgan,
             * porque no hay otra forma de que un trofeo se entregue. Sin
             * este paso, crear uno lo metía en el catálogo y lo dejaba
             * fuera de la lista de al lado: parecía que no se había
             * guardado, cuando lo que faltaba era el premio que lo da.
             */
            if (esNuevo) {
                this.useTrophy(trophy);
            }
        },

        firstError(data) {
            const bag = data?.errors ?? {};
            const first = Object.values(bag)[0];

            return Array.isArray(first) ? first[0] : (data?.message ?? null);
        },


        /*
        |----------------------------------------------------------------
        | Las recompensas
        |----------------------------------------------------------------
        */

        addReward(preset = {}) {
            this.rewards.push({
                trigger: 'POSITION',
                threshold: '',
                universe_trophy_id: '',
                stat_key: '',
                operation: 'ADD',
                amount: '',
                label: '',
                ...preset,
            });

            /* Recién creado está vacío: se abre para poder rellenarlo */
            this.expanded.push(this.rewards.length - 1);
        },

        toggleReward(index) {
            this.expanded = this.isExpanded(index)
                ? this.expanded.filter((i) => i !== index)
                : [...this.expanded, index];
        },

        isExpanded(index) {
            return this.expanded.includes(index);
        },

        /*
         * El podio de golpe.
         *
         * Los tres primeros puestos son lo que casi todo torneo tiene, y
         * crearlos uno a uno rellenando el mismo campo tres veces es
         * trabajo que la pantalla puede ahorrarse.
         */
        addPodium() {
            [
                { threshold: 1, label: 'Campeón' },
                { threshold: 2, label: 'Subcampeón' },
                { threshold: 3, label: 'Tercer puesto' },
            ].forEach((p) => this.addReward({ trigger: 'POSITION', ...p }));
        },

        removeReward(index) {
            this.rewards.splice(index, 1);

            /*
             * Los índices por encima del borrado se desplazan uno. Sin
             * recolocarlos, borrar el primero dejaba abierto el que ocupaba
             * su sitio en vez del que estaba abierto.
             */
            this.expanded = this.expanded
                .filter((i) => i !== index)
                .map((i) => (i > index ? i - 1 : i));
        },

        /*
         * Los trofeos que este torneo entrega.
         *
         * Un trofeo es "de este torneo" cuando algún premio suyo lo otorga:
         * no hay otra forma de que se entregue. Por eso esta lista sale de
         * las recompensas y no de una selección aparte que habría que
         * mantener en sincronía con ellas.
         */
        get tournamentTrophies() {
            const ids = [...new Set(
                this.rewards
                    .map((r) => String(r.universe_trophy_id))
                    .filter((id) => id && id !== 'null')
            )];

            return ids
                .map((id) => this.trophies.find((t) => String(t.id) === id))
                .filter(Boolean);
        },

        /* Los del universo que este torneo todavía no entrega */
        get availableTrophies() {
            const used = this.tournamentTrophies.map((t) => t.id);

            return this.trophies.filter((t) => !used.includes(t.id));
        },

        /* Cuántos premios entregan un trofeo concreto */
        rewardsWithTrophy(trophyId) {
            return this.rewards.filter(
                (r) => String(r.universe_trophy_id) === String(trophyId)
            ).length;
        },

        /*
         * Elegir un trofeo para este torneo CREA el premio que lo entrega.
         *
         * Es la única manera de que un trofeo sea de un torneo. Guardar una
         * lista de "trofeos elegidos" aparte de los premios daría dos sitios
         * donde decir lo mismo, y acabarían discrepando.
         */
        useTrophy(trophy) {
            this.addReward({
                universe_trophy_id: trophy.id,
                label: trophy.name,
            });

            this.pickerOpen = false;
        },

        trophyOf(reward) {
            if (!reward.universe_trophy_id) return null;

            return this.trophies.find(
                (t) => String(t.id) === String(reward.universe_trophy_id)
            ) ?? null;
        },

        /*
         * Las estadísticas que se pueden premiar salen del juego elegido
         * arriba: cada motor declara las suyas. Premiar una que el juego no
         * lleva sería prometer algo que nadie puede cobrar.
         *
         * Es una PROPIEDAD y no un getter, y la rellena un x-effect de la
         * vista. Alpine encadena scopes para EVALUAR expresiones, pero no
         * para el `this` de un método declarado en un x-data anidado:
         * escrito como getter, `this.game` era undefined y este bloque no
         * ofrecía ninguna estadística.
         */
        gameStats: [],

        /* Solo algunos disparadores necesitan un número */
        needsThreshold(reward) {
            return ['POSITION', 'WIN_COUNT', 'ENCOUNTER_WIN_COUNT'].includes(reward.trigger);
        },

        thresholdLabel(reward) {
            return {
                POSITION: 'Qué puesto',
                WIN_COUNT: 'Cuántas batallas',
                ENCOUNTER_WIN_COUNT: 'Cuántos enfrentamientos',
            }[reward.trigger] ?? 'Cuántos';
        },

        /* Un premio que no da ni trofeo ni estadística no premia nada */
        rewardGivesNothing(reward) {
            return !reward.universe_trophy_id && !reward.stat_key;
        },

        /*
         * El resumen de una línea: cuándo se otorga.
         *
         * Es lo que se ve cuando el premio está plegado, junto a lo que da.
         */
        rewardWhen(reward) {
            return {
                POSITION: 'Puesto ' + (reward.threshold || '?'),
                PARTICIPATION: 'Por participar',
                UNBEATEN: 'Invicto',
                WIN_COUNT: (reward.threshold || '?') + ' batallas',
                ENCOUNTER_WIN_COUNT: (reward.threshold || '?') + ' enfrentamientos',
            }[reward.trigger] ?? reward.trigger;
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

        /*
         * Cómo se leerá este premio, en una frase.
         *
         * Un premio son seis campos sueltos, y leerlos por separado no dice
         * qué va a pasar. La frase sí.
         */
        rewardText(reward) {
            if (this.rewardGivesNothing(reward)) {
                return 'Este premio no da nada todavía: elige un trofeo, una estadística, o ambos.';
            }

            const cuando = {
                POSITION: 'Quien acabe en el puesto ' + (reward.threshold || '?'),
                PARTICIPATION: 'Todo el que participe',
                UNBEATEN: 'Quien termine invicto',
                WIN_COUNT: 'Quien gane ' + (reward.threshold || '?') + ' batallas',
                ENCOUNTER_WIN_COUNT: 'Quien gane ' + (reward.threshold || '?') + ' enfrentamientos',
            }[reward.trigger] ?? 'Quien cumpla';

            const partes = [];

            const trophy = this.trophyOf(reward);

            if (trophy) partes.push('el trofeo «' + trophy.name + '»');

            if (reward.stat_key) {
                const stat = this.gameStats.find((s) => s.key === reward.stat_key);

                const verbo = {
                    ADD: 'suma',
                    SUBTRACT: 'resta',
                    MULTIPLY: 'multiplica por',
                    SET: 'fija en',
                }[reward.operation] ?? 'cambia';

                partes.push(verbo + ' ' + (reward.amount || 0) + ' en ' + (stat?.label ?? reward.stat_key));
            }

            return cuando + ' recibe ' + partes.join(' y ') + '.';
        },
    };
}

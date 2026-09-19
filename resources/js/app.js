import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import tournamentFlowBuilder from './tournaments/graph/flow-builder';
import competitionLab from './tournaments/lab/competition-lab';
import competitionArena from './tournaments/lab/competition-arena';
import phaseDecision from './tournaments/lab/phase-decision';
import singleEliminationWorkspace from './tournaments/single-elimination/workspace';
import singleEliminationStructureVisualizer from './tournaments/single-elimination/structure-visualizer';
import singleEliminationSimulator from './tournaments/single-elimination/simulator';
import roundRobinSimulator from './tournaments/round-robin/simulator';
import groupStageSimulator from './tournaments/group-stage/simulator';
import phaseTemplateDesigner from './tournaments/phase-templates/designer';
import phaseSuperEditor from './tournaments/phase-templates/super-editor';
import tournamentSuperEditor from './tournaments/super/tournament-editor';
import tournamentDossier from './tournaments/super/tournament-dossier';
import tournamentDesigner from './universes/tournament-designer';
import tournamentPrizes from './universes/tournament-prizes';
import competitionDesigner from './universes/competition-designer';
import competitionPrizes from './universes/competition-prizes';
import participantRoom from './universes/participant-room';
import entityBrowser from './universes/entity-browser';
import exitCriterionFields from './tournaments/phase-templates/super/criterion-fields';
import tournamentTemplateDesigner from './tournaments/templates/designer';
import omniSidebar from './layout/sidebar';

window.Alpine = Alpine;
window.tournamentFlowBuilder = tournamentFlowBuilder;
window.singleEliminationWorkspace = singleEliminationWorkspace;
window.singleEliminationStructureVisualizer = singleEliminationStructureVisualizer;
window.singleEliminationSimulator = singleEliminationSimulator;
window.roundRobinSimulator = roundRobinSimulator;
window.groupStageSimulator = groupStageSimulator;
window.phaseTemplateDesigner = phaseTemplateDesigner;
window.phaseSuperEditor = phaseSuperEditor;
window.tournamentSuperEditor = tournamentSuperEditor;
window.tournamentDossier = tournamentDossier;
window.tournamentDesigner = tournamentDesigner;
window.tournamentPrizes = tournamentPrizes;
window.competitionDesigner = competitionDesigner;
window.competitionPrizes = competitionPrizes;
window.participantRoom = participantRoom;
window.entityBrowser = entityBrowser;
window.exitCriterionFields = exitCriterionFields;
window.tournamentTemplateDesigner = tournamentTemplateDesigner;
window.omniSidebar = omniSidebar;


window.Alpine =
    Alpine;


/*
|--------------------------------------------------------------------------
| Formularios que ya fueron confirmados
|--------------------------------------------------------------------------
|
| WeakSet permite saber que un segundo evento submit
| proviene del botón del modal y no del usuario.
|
*/

const approvedConfirmForms =
    new WeakSet();


const pendingConfirmRequests =
    new Map();

let confirmRequestSequence =
    0;


window.OmniConfirm = {

    ready:
        false,


    /*
     * Un aviso con un solo boton: lo que antes era alert().
     *
     * El alert() del navegador es una caja gris que congela la pagina y
     * no se parece a nada de OmniMerge. Esto usa el mismo modal que las
     * confirmaciones, sin «Cancelar», y devuelve una Promise que se
     * resuelve al cerrarlo.
     */
    notice(message, options = {}) {

        return this.request({
            title: 'Atención',
            variant: 'warning',
            actionLabel: 'Entendido',
            ...options,
            message,
            notice: true,
        });
    },


    approve(form) {

        approvedConfirmForms.add(
            form
        );
    },


    consume(form) {

        if (
            !approvedConfirmForms.has(
                form
            )
        ) {

            return false;
        }


        approvedConfirmForms.delete(
            form
        );


        return true;
    },


    forget(form) {

        approvedConfirmForms.delete(
            form
        );
    },


    markReady() {

        this.ready =
            true;
    },


    request(options = {}) {

        options =
            options
            &&
            typeof options
            ===
            'object'
                ? options
                : {};


        /*
         * No dejamos una Promise pendiente cuando el layout
         * actual no tiene OmniConfirm inicializado.
         */
        if (!this.ready) {

            console.warn(
                '[OmniConfirm] El modal global todavía no está disponible.'
            );

            return Promise.resolve(
                false
            );
        }


        const requestId =
            `omni-confirm-request-${++confirmRequestSequence}`;

        const triggerElement =
            document.activeElement
            instanceof
            HTMLElement
                ? document.activeElement
                : null;


        return new Promise(
            (resolve) => {

                pendingConfirmRequests.set(
                    requestId,
                    resolve
                );


                window.dispatchEvent(
                    new CustomEvent(
                        'omni-confirm:open',

                        {
                            detail: {
                                requestId,
                                options,
                                triggerElement,
                            },
                        }
                    )
                );
            }
        );
    },


    settle(
        requestId,
        accepted
    ) {
        if (
            !requestId
            ||
            !pendingConfirmRequests.has(
                requestId
            )
        ) {

            return false;
        }


        const resolve =
            pendingConfirmRequests.get(
                requestId
            );


        pendingConfirmRequests.delete(
            requestId
        );


        resolve(
            Boolean(
                accepted
            )
        );


        return true;
    },
};


/*
|--------------------------------------------------------------------------
| Componente Alpine global
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'alpine:init',

    () => {

        /*
        |----------------------------------------------------------------------
        | x-keep-selected
        |----------------------------------------------------------------------
        |
        | Mantiene seleccionada la opción de un <select> cuyas opciones las
        | pinta Alpine.
        |
        | El problema que resuelve destruía datos. Un <select> con x-model
        | se enlaza ANTES de que su <template x-for> haya insertado los
        | <option>: no encuentra el valor, se queda en «», y x-model no
        | vuelve a intentarlo porque el valor enlazado no ha cambiado.
        |
        | Al guardar, ese «» viajaba como si el usuario hubiera elegido
        | «ninguno». En los premios de un torneo eso significaba que abrir la
        | pantalla y pulsar guardar BORRABA el trofeo de cada premio —y con
        | él el premio entero, porque una fila que no da nada se descarta—.
        |
        | Dos disparadores, y hacen falta los dos:
        |
        |   el efecto   cuando cambia el valor enlazado
        |   el observer cuando cambian las OPCIONES, que es el caso que
        |               rompía: al elegir otro juego se repintan las
        |               estadísticas y el valor volvería a perderse
        |
        | Solo toca el DOM. El estado de Alpine no se altera nunca, así que
        | esto no puede inventar una selección que el usuario no hizo: si el
        | valor guardado no está entre las opciones, el select se queda como
        | está.
        */
        Alpine.directive(
            'keep-selected',

            (el, { expression }, { evaluateLater, effect, cleanup }) => {

                const leer = evaluateLater(expression);

                const aplicar = () => leer((valor) => {

                    const quiero =
                        valor === null || valor === undefined
                            ? ''
                            : String(valor);

                    if (el.value === quiero) {
                        return;
                    }

                    if ([...el.options].some((o) => o.value === quiero)) {
                        el.value = quiero;
                    }
                });

                effect(() => aplicar());

                const observador = new MutationObserver(() => aplicar());

                observador.observe(el, { childList: true, subtree: true });

                cleanup(() => observador.disconnect());
            }
        );

        Alpine.data(
            'tournamentFlowBuilder',
            tournamentFlowBuilder
        );
        Alpine.data(
            'competitionLab',
            competitionLab
        );

        Alpine.data(
            'competitionArena',
            competitionArena
        );

        Alpine.data(
            'phaseDecision',
            phaseDecision
        );
        Alpine.data(
            'singleEliminationWorkspace',
            singleEliminationWorkspace
        );
        Alpine.data(
            'singleEliminationStructureVisualizer',
            singleEliminationStructureVisualizer
        );
        Alpine.data(
            'singleEliminationSimulator',
            singleEliminationSimulator
        );
        Alpine.data(
            'roundRobinSimulator',
            roundRobinSimulator
        );
        Alpine.data(
            'groupStageSimulator',
            groupStageSimulator
        );
        Alpine.data(
            'phaseTemplateDesigner',
            phaseTemplateDesigner
        );
        Alpine.data(
            'competitionDesigner',
            competitionDesigner
        );
        Alpine.data(
            'competitionPrizes',
            competitionPrizes
        );
        Alpine.data(
            'entityBrowser',
            entityBrowser
        );
        Alpine.data(
            'tournamentTemplateDesigner',
            tournamentTemplateDesigner
        );
        Alpine.data(
            'omniSidebar',
            omniSidebar
        );

        Alpine.data(
            'omniConfirmModal',

            () => ({

                open:
                    false,

                submitting:
                    false,

                form:
                    null,

                requestId:
                    null,

                restoreFocusTo:
                    null,

                /* Solo aviso: un boton, sin «Cancelar» */
                notice:
                    false,


                init() {

                    window
                        .OmniConfirm
                        .markReady();
                },


                /*
                |--------------------------------------------------------------------------
                | Contenido
                |--------------------------------------------------------------------------
                */

                title:
                    'Confirmar acción',

                message:
                    '¿Deseas continuar?',

                detail:
                    '',

                subject:
                    '',

                image:
                    '',


                /*
                |--------------------------------------------------------------------------
                | Botones
                |--------------------------------------------------------------------------
                */

                actionLabel:
                    'Confirmar',

                cancelLabel:
                    'Cancelar',


                /*
                |--------------------------------------------------------------------------
                | Apariencia
                |--------------------------------------------------------------------------
                */

                variant:
                    'warning',

                icon:
                    '!',


                /*
                |--------------------------------------------------------------------------
                | Abrir
                |--------------------------------------------------------------------------
                */

                openFromEvent(event) {

                    const requestId =
                        event.detail
                            ?.requestId;


                    /*
                     * El modal atiende una sola confirmación.
                     * Una segunda solicitud programática no puede quedar
                     * pendiente y cualquier segundo formulario se ignora.
                     */
                    if (
                        this.open
                        ||
                        this.submitting
                    ) {
                        if (requestId) {

                            window
                                .OmniConfirm
                                .settle(
                                    requestId,
                                    false
                                );
                        }

                        return;
                    }


                    if (requestId) {

                        const options =
                            event.detail
                                ?.options
                            ??
                            {};

                        const allowedVariants = [
                            'danger',
                            'warning',
                            'primary',
                            'violet',
                            'success',
                        ];


                        this.form =
                            null;

                        this.requestId =
                            requestId;

                        this.restoreFocusTo =
                            event.detail
                                ?.triggerElement
                            instanceof
                            HTMLElement
                                ? event.detail
                                    .triggerElement
                                : null;


                        this.title =
                            options.title
                            ||
                            'Confirmar acción';

                        this.message =
                            options.message
                            ||
                            '¿Deseas continuar?';

                        this.detail =
                            options.detail
                            ||
                            '';

                        this.subject =
                            options.subject
                            ||
                            '';

                        this.image =
                            options.image
                            ||
                            '';

                        this.actionLabel =
                            options.actionLabel
                            ||
                            'Confirmar';

                        this.cancelLabel =
                            options.cancelLabel
                            ||
                            'Cancelar';

                        this.notice =
                            options.notice === true;

                        this.variant =
                            allowedVariants.includes(
                                options.variant
                            )
                                ? options.variant
                                : 'warning';

                        this.icon =
                            options.icon
                            ||
                            this.defaultIcon(
                                this.variant
                            );


                        this.submitting =
                            false;

                        this.open =
                            true;


                        document.body
                            .classList
                            .add(
                                'overflow-hidden'
                            );


                        this.$nextTick(
                            () => {

                                this.$refs
                                    .confirmAction
                                    ?.focus();
                            }
                        );


                        return;
                    }


                    this.notice =
                        false;

                    const form =
                        event.detail
                            ?.form;


                    if (
                        !(
                            form
                            instanceof
                            HTMLFormElement
                        )
                    ) {

                        return;
                    }


                    const data =
                        form.dataset;


                    this.form =
                        form;

                    this.requestId =
                        null;

                    this.restoreFocusTo =
                        event.detail
                            ?.triggerElement
                        instanceof
                        HTMLElement
                            ? event.detail
                                .triggerElement
                            : (
                                document.activeElement
                                instanceof
                                HTMLElement
                                    ? document.activeElement
                                    : null
                            );


                    this.title =
                        data.confirmTitle
                        ||
                        'Confirmar acción';


                    this.message =
                        data.confirmMessage
                        ||
                        '¿Deseas continuar?';


                    this.detail =
                        data.confirmDetail
                        ||
                        '';


                    this.subject =
                        data.confirmSubject
                        ||
                        '';


                    this.image =
                        data.confirmImage
                        ||
                        '';


                    this.actionLabel =
                        data.confirmAction
                        ||
                        'Confirmar';


                    this.cancelLabel =
                        data.confirmCancel
                        ||
                        'Cancelar';


                    this.variant =
                        data.confirmVariant
                        ||
                        'warning';


                    this.icon =
                        data.confirmIcon
                        ||
                        this.defaultIcon(
                            this.variant
                        );


                    this.submitting =
                        false;


                    this.open =
                        true;


                    /*
                     * Evitar scroll de la página
                     * mientras el modal está abierto.
                     */
                    document.body
                        .classList
                        .add(
                            'overflow-hidden'
                        );


                    this.$nextTick(
                        () => {

                            this.$refs
                                .confirmAction
                                ?.focus();
                        }
                    );
                },


                /*
                |--------------------------------------------------------------------------
                | Iconos
                |--------------------------------------------------------------------------
                */

                defaultIcon(variant) {

                    return {

                        danger:
                            '×',

                        warning:
                            '!',

                        primary:
                            '✓',

                        violet:
                            '★',

                        success:
                            '✓',

                    }[
                        variant
                    ]
                        ||
                        '?';
                },


                /*
                |--------------------------------------------------------------------------
                | Etiqueta de variante
                |--------------------------------------------------------------------------
                */

                variantLabel() {

                    return {

                        danger:
                            'Acción destructiva',

                        warning:
                            'Requiere confirmación',

                        primary:
                            'Acción',

                        violet:
                            'Configuración',

                        success:
                            'Confirmación',

                    }[
                        this.variant
                    ]
                        ||
                        'Confirmación';
                },


                /*
                |--------------------------------------------------------------------------
                | Cerrar
                |--------------------------------------------------------------------------
                */

                finish(
                    accepted = false
                ) {
                    const requestId =
                        this.requestId;

                    const restoreFocusTo =
                        this.restoreFocusTo;


                    this.open =
                        false;

                    this.submitting =
                        false;

                    this.form =
                        null;

                    this.requestId =
                        null;

                    this.restoreFocusTo =
                        null;


                    document.body
                        .classList
                        .remove(
                            'overflow-hidden'
                        );


                    if (requestId) {

                        window
                            .OmniConfirm
                            .settle(
                                requestId,
                                accepted
                            );
                    }


                    if (
                        restoreFocusTo
                        instanceof
                        HTMLElement
                        &&
                        restoreFocusTo.isConnected
                    ) {

                        this.$nextTick(
                            () => {

                                restoreFocusTo.focus({
                                    preventScroll:
                                        true,
                                });
                            }
                        );
                    }
                },


                close() {

                    if (
                        this.submitting
                        ||
                        !this.open
                    ) {

                        return;
                    }


                    this.finish(
                        false
                    );
                },

                /*
                |--------------------------------------------------------------------------
                | Aprobar y ejecutar acción
                |--------------------------------------------------------------------------
                |
                | IMPORTANTE:
                | Este método NO utiliza window.confirm().
                |
                | - En formularios autoriza exactamente un nuevo submit.
                | - En solicitudes JavaScript resuelve la Promise en true.
                |
                */

                approveAndSubmit() {

                    if (
                        this.submitting
                        ||
                        !this.open
                    ) {

                        return;
                    }


                    if (this.requestId) {

                        this.submitting =
                            true;


                        this.finish(
                            true
                        );


                        return;
                    }


                    if (!this.form) {

                        return;
                    }


                    this.submitting =
                        true;


                    const form =
                        this.form;


                    /*
                    |--------------------------------------------------------------------------
                    | Autorizar exactamente este submit
                    |--------------------------------------------------------------------------
                    */

                    window
                        .OmniConfirm
                        .approve(
                            form
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Cerrar modal visualmente
                    |--------------------------------------------------------------------------
                    */

                    this.open =
                        false;

                    this.form =
                        null;

                    this.requestId =
                        null;

                    this.restoreFocusTo =
                        null;


                    document.body
                        .classList
                        .remove(
                            'overflow-hidden'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Enviar normalmente el formulario
                    |--------------------------------------------------------------------------
                    |
                    | requestSubmit() mantiene:
                    |
                    | - validación HTML;
                    | - evento submit;
                    | - CSRF;
                    | - _method de Laravel.
                    |
                    */

                    form.requestSubmit();


                    /*
                    |--------------------------------------------------------------------------
                    | Limpiar autorización si HTML bloqueó el envío
                    |--------------------------------------------------------------------------
                    */

                    setTimeout(
                        () => {

                            window
                                .OmniConfirm
                                .forget(
                                    form
                                );


                            this.submitting =
                                false;
                        },
                        0
                    );
                },
            })
        );
    }
);


/*
|--------------------------------------------------------------------------
| Interceptor global
|--------------------------------------------------------------------------
|
| Cualquier:
|
| <form data-omni-confirm>
|
| utilizará automáticamente OmniConfirm.
|
*/

/*
|--------------------------------------------------------------------------
| Cambios sin guardar
|--------------------------------------------------------------------------
|
| Cada pantalla que edita algo avisaba al salir con el dialogo nativo del
| navegador («¿Salir del sitio? Es posible que los cambios no se guarden»),
| cada una con su propio beforeunload.
|
| Ahora se registran aqui con OmniUnsaved.watch(() => hayCambios) y:
|
|   - salir por un enlace de la aplicacion abre el modal de OmniMerge,
|     que dice lo que pasa y deja elegir
|   - cerrar la pestana o recargar sigue usando el aviso del navegador:
|     ahi ningun navegador deja pintar un modal propio
|
*/

const unsavedWatchers =
    new Set();

let leavingApproved =
    false;

window.OmniUnsaved = {

    watch(isDirty) {

        const watcher = { isDirty };

        unsavedWatchers.add(watcher);

        return () => unsavedWatchers.delete(watcher);
    },

    dirty() {

        return [...unsavedWatchers].some((watcher) => {
            try {
                return Boolean(watcher.isDirty());
            } catch (e) {
                return false;
            }
        });
    },

    /* Para salir a proposito sin volver a preguntar */
    allowLeaving() {

        leavingApproved = true;
    },
};

window.addEventListener('beforeunload', (event) => {

    if (leavingApproved || !window.OmniUnsaved.dirty()) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
});

document.addEventListener('click', async (event) => {

    if (
        event.defaultPrevented
        || event.button !== 0
        || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
    ) {
        return;
    }

    const link = event.target?.closest?.('a[href]');

    if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) {
        return;
    }

    const destino = new URL(link.href, window.location.href);

    if (destino.origin !== window.location.origin) {
        return;
    }

    /* Un ancla dentro de la misma pagina no es salir */
    if (
        destino.pathname === window.location.pathname
        && destino.search === window.location.search
        && destino.hash
    ) {
        return;
    }

    if (!window.OmniUnsaved.dirty()) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const salir = await window.OmniConfirm.request({
        title: 'Tienes cambios sin guardar',
        message: 'Si sales ahora, lo que has cambiado en esta pantalla se pierde.',
        detail: 'Quédate y pulsa Guardar si quieres conservarlo.',
        actionLabel: 'Salir sin guardar',
        cancelLabel: 'Seguir editando',
        variant: 'warning',
    });

    if (salir) {
        leavingApproved = true;
        window.location.assign(destino.href);
    }
}, true);


document.addEventListener(
    'submit',

    (event) => {

        const form =
            event.target;


        if (
            !(
                form
                instanceof
                HTMLFormElement
            )
        ) {

            return;
        }


        /*
         * Formularios normales:
         * comportamiento normal.
         */
        if (
            !form.matches(
                '[data-omni-confirm]'
            )
        ) {

            return;
        }


        /*
         * Si ya fue aprobado por OmniConfirm,
         * permitir que Laravel lo reciba.
         */
        if (
            window
                .OmniConfirm
                .consume(
                    form
                )
        ) {

            return;
        }


        /*
         * Primera solicitud:
         * detener y abrir modal.
         */
        event.preventDefault();


        window.dispatchEvent(
            new CustomEvent(
                'omni-confirm:open',

                {
                    detail: {
                        form:
                            form,

                        triggerElement:
                            document.activeElement
                            instanceof
                            HTMLElement
                                ? document.activeElement
                                : null,
                    },
                }
            )
        );
    },

    true
);


Alpine.plugin(collapse);

Alpine.start();

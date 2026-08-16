import './bootstrap';
import Alpine from 'alpinejs';
import tournamentFlowBuilder from './tournaments/graph/flow-builder';
import competitionLab from './tournaments/lab/competition-lab';
import singleEliminationWorkspace from './tournaments/single-elimination/workspace';
import singleEliminationStructureVisualizer from './tournaments/single-elimination/structure-visualizer';
import phaseTemplateDesigner from './tournaments/phase-templates/designer';

window.Alpine = Alpine;
window.tournamentFlowBuilder = tournamentFlowBuilder;
window.singleEliminationWorkspace = singleEliminationWorkspace;
window.singleEliminationStructureVisualizer = singleEliminationStructureVisualizer;
window.phaseTemplateDesigner = phaseTemplateDesigner;


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


window.OmniConfirm = {

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
};


/*
|--------------------------------------------------------------------------
| Componente Alpine global
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'alpine:init',

    () => {
        Alpine.data(
            'tournamentFlowBuilder',
            tournamentFlowBuilder
        );
        Alpine.data(
            'competitionLab',
            competitionLab
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
            'phaseTemplateDesigner',
            phaseTemplateDesigner
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

                close() {

                    if (
                        this.submitting
                    ) {

                        return;
                    }


                    this.open =
                        false;


                    this.form =
                        null;


                    document.body
                        .classList
                        .remove(
                            'overflow-hidden'
                        );
                },

                /*
                |--------------------------------------------------------------------------
                | Aprobar y ejecutar acción
                |--------------------------------------------------------------------------
                |
                | IMPORTANTE:
                | Este método NO utiliza window.confirm().
                | Solamente autoriza el formulario que ya fue
                | aceptado desde nuestro modal de OmniMerge.
                |
                */

                approveAndSubmit() {

                    if (
                        !this.form
                        ||
                        this.submitting
                    ) {

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
                    },
                }
            )
        );
    },

    true
);


Alpine.start();
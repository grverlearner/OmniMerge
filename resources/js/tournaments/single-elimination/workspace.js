export default function singleEliminationWorkspace(
    configuration = {}
) {
    return {
        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        view:
            configuration.initialView
            || 'summary',

        dirty:
            Boolean(
                configuration.hasErrors
            ),

        submitting:
            false,

        initialSnapshot:
            '',

        sections: {
            completion:
                true,

            distribution:
                true,

            byes:
                false,

            series:
                false,

            reseed:
                false,
        },

        draft: {
            completionMode:
                configuration.completionMode
                || 'WINNER',

            targetSurvivors:
                Number(
                    configuration.targetSurvivors
                    || 1
                ),

            seedingMode:
                configuration.seedingMode
                || 'INPUT_ORDER',

            pairingMode:
                configuration.pairingMode
                || 'STANDARD_SEEDED',

            byeAssignment:
                configuration.byeAssignment
                || 'TOP_SEEDS',

            defaultBestOf:
                Number(
                    configuration.defaultBestOf
                    || 1
                ),

            reseedEachRound:
                Boolean(
                    configuration.reseedEachRound
                ),
        },

        beforeUnloadHandler:
            null,

        /*
        |--------------------------------------------------------------------------
        | Inicializar
        |--------------------------------------------------------------------------
        */

        init() {
            this.$nextTick(
                () => {
                    this.initialSnapshot =
                        this.formSnapshot();

                    this.syncDraft();

                    if (
                        configuration.hasErrors
                    ) {
                        this.dirty =
                            true;
                    }
                }
            );

            this.beforeUnloadHandler =
                (event) => {
                    if (
                        !this.dirty
                        ||
                        this.submitting
                    ) {
                        return;
                    }

                    event.preventDefault();

                    event.returnValue =
                        '';
                };

            window.addEventListener(
                'beforeunload',
                this.beforeUnloadHandler
            );
        },

        /*
        |--------------------------------------------------------------------------
        | Obtener formulario
        |--------------------------------------------------------------------------
        */

        settingsForm() {
            return this.$refs
                .settingsForm
                || null;
        },

        /*
        |--------------------------------------------------------------------------
        | Snapshot
        |--------------------------------------------------------------------------
        */

        formSnapshot() {
            const form =
                this.settingsForm();

            if (
                !form
            ) {
                return '';
            }

            const entries =
                Array
                    .from(
                        new FormData(
                            form
                        )
                            .entries()
                    )
                    .filter(
                        ([key]) =>
                            ![
                                '_token',
                                '_method',
                            ]
                                .includes(
                                    key
                                )
                    )
                    .map(
                        ([key, value]) => [
                            String(key),
                            String(value),
                        ]
                    )
                    .sort(
                        ([keyA, valueA], [keyB, valueB]) =>
                            `${keyA}:${valueA}`
                                .localeCompare(
                                    `${keyB}:${valueB}`
                                )
                    );

            return JSON.stringify(
                entries
            );
        },

        /*
        |--------------------------------------------------------------------------
        | Detectar modificaciones
        |--------------------------------------------------------------------------
        */

        markSettingsDirty() {
            this.syncDraft();

            this.dirty =
                this.formSnapshot()
                !==
                this.initialSnapshot;
        },

        /*
        |--------------------------------------------------------------------------
        | Sincronizar resumen temporal
        |--------------------------------------------------------------------------
        */

        syncDraft() {
            const form =
                this.settingsForm();

            if (
                !form
            ) {
                return;
            }

            const fieldValue =
                (name, fallback = '') =>
                    form.elements
                        .namedItem(
                            name
                        )
                        ?.value
                    ?? fallback;

            this.draft.completionMode =
                fieldValue(
                    'completion_mode',
                    'WINNER'
                );

            this.draft.targetSurvivors =
                Number(
                    fieldValue(
                        'target_survivors',
                        1
                    )
                );

            this.draft.seedingMode =
                fieldValue(
                    'seeding_mode',
                    'INPUT_ORDER'
                );

            this.draft.pairingMode =
                fieldValue(
                    'pairing_mode',
                    'STANDARD_SEEDED'
                );

            this.draft.byeAssignment =
                fieldValue(
                    'bye_assignment',
                    'TOP_SEEDS'
                );

            this.draft.defaultBestOf =
                Number(
                    fieldValue(
                        'default_best_of',
                        1
                    )
                );

            const reseed =
                form.elements
                    .namedItem(
                        'reseed_each_round'
                    );

            this.draft.reseedEachRound =
                Boolean(
                    reseed
                    &&
                    reseed.checked
                );
        },

        /*
        |--------------------------------------------------------------------------
        | Guardar
        |--------------------------------------------------------------------------
        */

        submitSettings() {
            const form =
                this.settingsForm();

            if (
                !form
                ||
                this.submitting
            ) {
                return;
            }

            if (
                !form.reportValidity()
            ) {
                this.submitting =
                    false;

                return;
            }

            this.submitting =
                true;

            form.requestSubmit();
        },

        /*
        |--------------------------------------------------------------------------
        | Descartar
        |--------------------------------------------------------------------------
        */

        discardSettings() {
            const form =
                this.settingsForm();

            if (
                !form
                ||
                this.submitting
            ) {
                return;
            }

            form.reset();

            form
                .querySelectorAll(
                    'input, select, textarea'
                )
                .forEach(
                    (field) => {
                        field.dispatchEvent(
                            new Event(
                                'change',
                                {
                                    bubbles:
                                        true,
                                }
                            )
                        );
                    }
                );

            this.$nextTick(
                () => {
                    this.syncDraft();

                    this.dirty =
                        false;
                }
            );
        },

        /*
        |--------------------------------------------------------------------------
        | Vistas
        |--------------------------------------------------------------------------
        */

        setView(view) {
            if (
                ![
                    'summary',
                    'blocks',
                    'table',
                ]
                    .includes(
                        view
                    )
            ) {
                return;
            }

            this.view =
                view;
        },

        /*
        |--------------------------------------------------------------------------
        | Secciones
        |--------------------------------------------------------------------------
        */

        toggleSection(section) {
            if (
                !Object.prototype
                    .hasOwnProperty
                    .call(
                        this.sections,
                        section
                    )
            ) {
                return;
            }

            this.sections[section] =
                !this.sections[section];
        },

        openSection(section) {
            if (
                Object.prototype
                    .hasOwnProperty
                    .call(
                        this.sections,
                        section
                    )
            ) {
                this.sections[section] =
                    true;
            }

            this.$nextTick(
                () => {
                    document
                        .getElementById(
                            `single-elimination-${section}`
                        )
                        ?.scrollIntoView({
                            behavior:
                                'smooth',

                            block:
                                'start',
                        });
                }
            );
        },

        /*
        |--------------------------------------------------------------------------
        | Etiquetas temporales
        |--------------------------------------------------------------------------
        */

        completionLabel() {
            if (
                this.draft.completionMode
                ===
                'WINNER'
            ) {
                return '1 ganador';
            }

            return `${this.draft.targetSurvivors} supervivientes`;
        },

        seedingLabel() {
            return {
                INPUT_ORDER:
                    'Orden de entrada',

                RANDOM:
                    'Aleatorio',

                RANKING:
                    'Ranking',

                MANUAL:
                    'Manual',
            }[
                this.draft.seedingMode
            ]
                || this.draft.seedingMode;
        },

        pairingLabel() {
            return {
                STANDARD_SEEDED:
                    'Seeded estándar',

                SEQUENTIAL:
                    'Secuencial',

                RANDOM:
                    'Aleatorio',
            }[
                this.draft.pairingMode
            ]
                || this.draft.pairingMode;
        },

        byeLabel() {
            return {
                TOP_SEEDS:
                    'Mejores seeds',

                RANDOM:
                    'Aleatorio',

                MANUAL:
                    'Manual',
            }[
                this.draft.byeAssignment
            ]
                || this.draft.byeAssignment;
        },
    };
}
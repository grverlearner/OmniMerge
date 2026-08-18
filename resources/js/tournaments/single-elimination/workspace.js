export default function singleEliminationWorkspace(
    configuration = {}
) {
    return {
        view:
            configuration.initialView
            || 'summary',

        dirty:
            Boolean(
                configuration.hasErrors
            ),

        submitting:
            false,

        previewLoading:
            false,

        previewError:
            '',

        previewMessage:
            '',

        previewUrl:
            configuration.previewUrl
            || '',

        previewTimer:
            null,

        previewController:
            null,

        initialSnapshot:
            '',

        sections: {
            mode: true,
            advanced: true,
            completion: true,
            distribution: true,
            byes: false,
            series: false,
            reseed: false,
        },

        draft: {
            configurationMode:
                configuration.configurationMode
                || 'BASIC',

            inputMode:
                configuration.inputMode
                || 'POOL',

            routingMode:
                configuration.routingMode
                || 'AUTOMATIC',

            entrantsPerMatch:
                Number(
                    configuration.entrantsPerMatch
                    || 2
                ),

            qualifiersPerMatch:
                Number(
                    configuration.qualifiersPerMatch
                    || 1
                ),

            encounterProfile:
                configuration.encounterProfile
                || 'DUEL',

            remainderPolicy:
                configuration.remainderPolicy
                || 'REJECT',
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

            seriesFormat:
                configuration.seriesFormat
                || 'BEST_OF',

            defaultBestOf:
                Number(
                    configuration.defaultBestOf
                    || 1
                ),

            fixedGames:
                Number(
                    configuration.fixedGames
                    || 1
                ),

            reseedEachRound:
                Boolean(
                    configuration.reseedEachRound
                ),
        },

        beforeUnloadHandler:
            null,

        navigationClickHandler:
            null,

        navigationConfirmationOpen:
            false,

        intentionalNavigation:
            false,

        phaseName:
            configuration.phaseName
            || 'Eliminación Simple',

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
                        ||
                        this.intentionalNavigation
                    ) {
                        return;
                    }

                    event.preventDefault();
                    event.returnValue = '';
                };

            this.navigationClickHandler =
                (event) => {
                    this.handleNavigationClick(
                        event
                    );
                };

            window.addEventListener(
                'beforeunload',
                this.beforeUnloadHandler
            );

            document.addEventListener(
                'click',
                this.navigationClickHandler,
                true
            );
        },

        destroy() {
            if (this.beforeUnloadHandler) {
                window.removeEventListener(
                    'beforeunload',
                    this.beforeUnloadHandler
                );
            }

            if (this.navigationClickHandler) {
                document.removeEventListener(
                    'click',
                    this.navigationClickHandler,
                    true
                );
            }

            this.stopPendingPreview();
        },

        navigationDestination(event) {
            if (
                event.defaultPrevented
                ||
                event.button !== 0
                ||
                event.ctrlKey
                ||
                event.metaKey
                ||
                event.shiftKey
                ||
                event.altKey
            ) {
                return null;
            }

            const eventTarget =
                event.target
                instanceof Element
                    ? event.target
                    : null;

            const anchor =
                eventTarget
                    ?.closest(
                        'a[href]'
                    )
                ?? null;

            if (!anchor) {
                return null;
            }

            if (
                anchor.hasAttribute(
                    'download'
                )
                ||
                anchor.hasAttribute(
                    'data-omni-unsaved-ignore'
                )
            ) {
                return null;
            }

            const target =
                (
                    anchor.getAttribute(
                        'target'
                    )
                    || ''
                )
                    .trim()
                    .toLowerCase();

            if (
                target
                &&
                target !== '_self'
            ) {
                return null;
            }

            let destination;

            try {
                destination =
                    new URL(
                        anchor.href,
                        window.location.href
                    );
            } catch {
                return null;
            }

            if (
                ![
                    'http:',
                    'https:',
                ].includes(
                    destination.protocol
                )
                ||
                destination.origin
                !==
                window.location.origin
            ) {
                return null;
            }

            const current =
                new URL(
                    window.location.href
                );

            /*
             * Un cambio únicamente de hash permanece dentro
             * del mismo documento y no pierde el formulario.
             */
            if (
                destination.pathname
                ===
                current.pathname
                &&
                destination.search
                ===
                current.search
            ) {
                return null;
            }

            return destination.href;
        },

        handleNavigationClick(event) {
            if (
                !this.dirty
                ||
                this.submitting
                ||
                this.intentionalNavigation
            ) {
                return;
            }

            const destination =
                this.navigationDestination(
                    event
                );

            if (!destination) {
                return;
            }

            /*
             * preventDefault debe ocurrir antes del await.
             * Así el navegador nunca abandona la página
             * mientras OmniConfirm está esperando respuesta.
             */
            event.preventDefault();

            if (
                this.navigationConfirmationOpen
            ) {
                return;
            }

            void this.confirmNavigation(
                destination
            );
        },

        async confirmNavigation(
            destination
        ) {
            if (
                this.navigationConfirmationOpen
                ||
                !destination
            ) {
                return;
            }

            this.navigationConfirmationOpen =
                true;

            try {
                const accepted =
                    await window
                        .OmniConfirm
                        ?.request?.({
                            title:
                                'Cambios sin guardar',

                            message:
                                'Tienes cambios en las reglas que todavía no se han guardado.',

                            detail:
                                'Si sales ahora, OmniMerge descartará los cambios locales de esta pantalla.',

                            subject:
                                this.phaseName,

                            actionLabel:
                                'Descartar y salir',

                            cancelLabel:
                                'Seguir editando',

                            variant:
                                'warning',

                            icon:
                                '!',
                        });

                if (!accepted) {
                    return;
                }

                this.intentionalNavigation =
                    true;

                this.stopPendingPreview();

                window.location.assign(
                    destination
                );
            } finally {
                /*
                 * Si la navegación fue aceptada dejamos el flag
                 * activo hasta que el documento se descargue.
                 * Así beforeunload no abre un segundo diálogo.
                 */
                if (
                    !this.intentionalNavigation
                ) {
                    this.navigationConfirmationOpen =
                        false;
                }
            }
        },

        settingsForm() {
            return this.$refs
                .settingsForm
                || null;
        },

        formSnapshot() {
            const form =
                this.settingsForm();

            if (!form) {
                return '';
            }

            const entries =
                Array
                    .from(
                        new FormData(form)
                            .entries()
                    )
                    .filter(
                        ([key]) =>
                            ![
                                '_token',
                                '_method',
                            ]
                                .includes(key)
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

        markSettingsDirty() {
            this.syncDraft();

            this.dirty =
                Boolean(
                    configuration.hasErrors
                )
                ||
                this.formSnapshot()
                !==
                this.initialSnapshot;

            this.schedulePreview();
        },

        syncDraft() {
            const form =
                this.settingsForm();

            if (!form) {
                return;
            }

            const fieldValue =
                (name, fallback = '') =>
                    form.elements
                        .namedItem(name)
                        ?.value
                    ??
                    fallback;
            this.draft.configurationMode =
                fieldValue(
                    'configuration_mode',
                    'BASIC'
                );

            this.draft.inputMode =
                fieldValue(
                    'input_mode',
                    'POOL'
                );

            this.draft.routingMode =
                fieldValue(
                    'routing_mode',
                    'AUTOMATIC'
                );

            this.draft.entrantsPerMatch =
                Number(
                    fieldValue(
                        'entrants_per_match',
                        2
                    )
                );

            this.draft.qualifiersPerMatch =
                Number(
                    fieldValue(
                        'qualifiers_per_match',
                        1
                    )
                );

            this.draft.encounterProfile =
                fieldValue(
                    'encounter_profile',
                    'DUEL'
                );

            this.draft.remainderPolicy =
                fieldValue(
                    'remainder_policy',
                    'REJECT'
                );

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

            this.draft.seriesFormat =
                fieldValue(
                    'series_format',
                    'BEST_OF'
                );

            this.draft.defaultBestOf =
                Number(
                    fieldValue(
                        'default_best_of',
                        1
                    )
                );

            this.draft.fixedGames =
                Number(
                    fieldValue(
                        'fixed_games',
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

            if (!form.reportValidity()) {
                return;
            }

            this.submitting = true;
            form.requestSubmit();
        },

        async discardSettings() {
            const form =
                this.settingsForm();

            if (
                !form
                ||
                this.submitting
                ||
                !this.dirty
                ||
                this.navigationConfirmationOpen
            ) {
                return;
            }

            this.navigationConfirmationOpen =
                true;

            let accepted =
                false;

            try {
                accepted =
                    Boolean(
                        await window
                            .OmniConfirm
                            ?.request?.({
                                title:
                                    'Descartar cambios',

                                message:
                                    '¿Quieres recuperar la última configuración guardada?',

                                detail:
                                    'Los cambios locales realizados en las reglas de esta pantalla se perderán.',

                                subject:
                                    this.phaseName,

                                actionLabel:
                                    'Descartar cambios',

                                cancelLabel:
                                    'Seguir editando',

                                variant:
                                    'warning',

                                icon:
                                    '↺',
                            })
                    );
            } finally {
                this.navigationConfirmationOpen =
                    false;
            }

            if (!accepted) {
                return;
            }

            /*
             * Tras una validación fallida, Laravel renderiza old()
             * como valores por defecto del formulario. Un form.reset()
             * volvería justamente a esos valores rechazados, no a lo
             * persistido. Recargar el GET limpia ese estado y recupera
             * la configuración realmente guardada.
             */
            if (
                configuration.hasErrors
            ) {
                this.intentionalNavigation =
                    true;

                this.stopPendingPreview();

                window.location.reload();

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
                                    bubbles: true,
                                }
                            )
                        );
                    }
                );

            this.$nextTick(
                () => {
                    this.syncDraft();
                    this.dirty = false;
                    this.schedulePreview(0);
                }
            );
        },

        stopPendingPreview() {
            window.clearTimeout(
                this.previewTimer
            );

            this.previewTimer =
                null;

            this.previewController
                ?.abort();

            this.previewController =
                null;
        },

        schedulePreview(delay = 400) {
            if (!this.previewUrl) {
                return;
            }

            window.clearTimeout(
                this.previewTimer
            );

            this.previewTimer =
                window.setTimeout(
                    () =>
                        this.refreshPreview(),
                    delay
                );
        },

        async refreshPreview() {
            const form =
                this.settingsForm();

            if (
                !form
                ||
                !this.previewUrl
            ) {
                return;
            }

            this.previewController
                ?.abort();

            this.previewController =
                new AbortController();

            const payload =
                new FormData(form);

            payload.delete('_method');

            if (
                !payload.has(
                    'reseed_each_round'
                )
            ) {
                payload.set(
                    'reseed_each_round',
                    '0'
                );
            }

            payload.set(
                'participants',
                String(
                    this.previewParticipantCount()
                )
            );

            this.previewLoading = true;
            this.previewError = '';
            this.previewMessage =
                'Actualizando vista previa...';

            try {
                const response =
                    await fetch(
                        this.previewUrl,
                        {
                            method:
                                'POST',

                            headers: {
                                Accept:
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        ?.content
                                    ||
                                    '',
                            },

                            body:
                                payload,

                            signal:
                                this.previewController
                                    .signal,
                        }
                    );

                const data =
                    await response.json();

                if (!response.ok) {
                    throw new Error(
                        this.firstValidationMessage(
                            data
                        )
                    );
                }

                const container =
                    this.$refs
                        .previewContainer;

                if (
                    container
                    &&
                    typeof data.html
                    ===
                    'string'
                ) {
                    container.innerHTML =
                        data.html;

                    window.Alpine
                        ?.initTree(
                            container
                        );
                }

                const diagnosticContainer =
                    this.$refs
                        .diagnosticContainer;

                if (
                    diagnosticContainer
                    &&
                    typeof data.diagnostic_html
                    ===
                    'string'
                ) {
                    diagnosticContainer.innerHTML =
                        data.diagnostic_html;
                }

                this.previewMessage =
                    data.valid
                        ? 'Vista previa actualizada.'
                        : 'La configuración necesita revisión.';
            } catch (error) {
                if (
                    error.name
                    ===
                    'AbortError'
                ) {
                    return;
                }

                this.previewError =
                    error.message
                    ||
                    'No se pudo actualizar la vista previa.';

                this.previewMessage = '';
            } finally {
                this.previewLoading =
                    false;
            }
        },

        previewParticipantCount() {
            const input =
                this.$refs
                    .previewContainer
                    ?.querySelector(
                        '[data-preview-participants]'
                    );

            const value =
                Number(
                    input?.value
                    ||
                    configuration.previewParticipants
                    ||
                    2
                );

            return Math.min(
                512,
                Math.max(
                    2,
                    value
                )
            );
        },

        firstValidationMessage(data) {
            const errors =
                data?.errors
                ||
                {};

            const firstGroup =
                Object.values(errors)[0];

            if (
                Array.isArray(firstGroup)
                &&
                firstGroup.length > 0
            ) {
                return firstGroup[0];
            }

            return data?.message
                ||
                'Revisa los datos de la configuración.';
        },

        setView(view) {
            if (
                ![
                    'summary',
                    'blocks',
                    'table',
                ]
                    .includes(view)
            ) {
                return;
            }

            this.view = view;
        },

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

        configurationModeLabel() {
            return this.draft.configurationMode
                ===
                'ADVANCED'
                ? 'Avanzado'
                : 'Básico';
        },

        formatLabel() {
            return `${this.draft.entrantsPerMatch} → ${this.draft.qualifiersPerMatch}`;
        },

        encounterProfileLabel() {
            return {
                DUEL:
                    'Duelo',

                MULTI_COMPETITOR:
                    'Multicompetidor',

                CUSTOM:
                    'Personalizado',
            }[
                this.draft.encounterProfile
            ]
                ||
                this.draft.encounterProfile;
        },

        inputModeLabel() {
            return {
                POOL:
                    'Bolsa común',

                PER_SEED:
                    'Por seed',

                GROUPED:
                    'Agrupada',

                HYBRID:
                    'Híbrida',

                CUSTOM:
                    'Personalizada',
            }[
                this.draft.inputMode
            ]
                ||
                this.draft.inputMode;
        },

        routingModeLabel() {
            return {
                AUTOMATIC:
                    'Automático',

                POSITIONAL:
                    'Por posición',

                MANUAL:
                    'Manual',

                CUSTOM:
                    'Personalizado',
            }[
                this.draft.routingMode
            ]
                ||
                this.draft.routingMode;
        },

        remainderPolicyLabel() {
            return {
                BYE:
                    'BYE',

                PRELIMINARY:
                    'Preliminar',

                BALANCED:
                    'Balanceada',

                INCOMPLETE_MATCH:
                    'Encuentro incompleto',

                MANUAL:
                    'Manual',

                REJECT:
                    'Rechazar',
            }[
                this.draft.remainderPolicy
            ]
                ||
                this.draft.remainderPolicy;
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
                ||
                this.draft.seedingMode;
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
                ||
                this.draft.pairingMode;
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
                ||
                this.draft.byeAssignment;
        },

        seriesLabel() {
            if (
                this.draft.seriesFormat
                ===
                'FIXED_GAMES'
            ) {
                return `${this.draft.fixedGames} ${this.draft.fixedGames === 1
                    ? 'enfrentamiento fijo'
                    : 'enfrentamientos fijos'
                    }`;
            }

            return `BO${this.draft.defaultBestOf}`;
        },
    };
}
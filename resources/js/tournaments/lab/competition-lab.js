export default function competitionLab(config) {
    return {
        state:
            config.initialState
            ??
            null,

        stateToken:
            config.initialToken
            ??
            null,

        actionUrl:
            config.actionUrl,

        storageKey:
            config.storageKey,

        /*
         * Modo persistente (Tournament Runtime, Fase 6).
         *
         * En el Competition Lab el estado vive en un token cifrado dentro
         * de sessionStorage. En una competición real vive en la base de
         * datos: no hay token, no se toca sessionStorage, y cada acción
         * viaja con la revisión que tenemos para que dos pestañas no se
         * pisen los resultados.
         */
        persistent:
            config.persistent
            ??
            false,

        revision:
            config.revision
            ??
            null,

        loading:
            false,

        error:
            '',

        labMode:
            null,

        selectedParticipantId:
            null,

        selectedNodeId:
            null,

        selectedGroupId:
            null,

        selectedForEngine:
            [],

        resultForms:
            {},

        expandedRounds:
            [],

        decisionDraft: {
            ordered_participant_ids: [],
            selected_participant_ids: [],
            group_assignments: {},
        },

        init() {
            /*
             * Competición persistente: el servidor manda. No hay nada que
             * restaurar ni que guardar en el navegador.
             */
            if (this.persistent) {
                if (this.state) {
                    this.afterStateChange();
                }

                return;
            }

            if (
                this.state
                &&
                this.stateToken
            ) {
                this.persist();
                this.afterStateChange();

                return;
            }

            const stored =
                sessionStorage.getItem(
                    this.storageKey
                );

            if (!stored) {
                return;
            }

            try {
                const payload =
                    JSON.parse(
                        stored
                    );

                this.state =
                    payload.state;

                this.stateToken =
                    payload.stateToken;

                this.labMode =
                    payload.labMode
                    ??
                    (
                        this.state
                            ?.graph_runtime
                            ? 'AUTOMATIC'
                            : null
                    );

                this.afterStateChange();
            } catch {
                sessionStorage.removeItem(
                    this.storageKey
                );
            }
        },

        persist() {
            /*
             * En modo persistente el estado ya está guardado en base de
             * datos por el servidor: duplicarlo en sessionStorage solo
             * podría desincronizarlo.
             */
            if (this.persistent) {
                return;
            }

            if (
                !this.state
                ||
                !this.stateToken
            ) {
                return;
            }

            sessionStorage.setItem(
                this.storageKey,
                JSON.stringify({
                    state:
                        this.state,

                    stateToken:
                        this.stateToken,

                    labMode:
                        this.labMode,
                })
            );
        },

        async execute(
            action,
            data = {}
        ) {
            if (
                (
                    !this.stateToken
                    &&
                    !this.persistent
                )
                ||
                this.loading
            ) {
                return;
            }

            this.loading =
                true;

            this.error =
                '';

            try {
                const response =
                    await fetch(
                        this.actionUrl,
                        {
                            method:
                                'POST',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                Accept:
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        ?.content
                                    ??
                                    '',
                            },

                            body:
                                JSON.stringify(
                                    this.persistent
                                        ? {
                                            action,

                                            revision:
                                                this.revision,

                                            ...data,
                                        }
                                        : {
                                            action,

                                            state_token:
                                                this.stateToken,

                                            ...data,
                                        }
                                ),
                        }
                    );

                const payload =
                    await response.json();

                if (!response.ok) {
                    const errors =
                        payload.errors
                        ??
                        {};

                    this.error =
                        Object
                            .values(
                                errors
                            )
                            .flat()
                            .join(' ')
                        ||
                        payload.message
                        ||
                        'No fue posible ejecutar la acción.';

                    return;
                }

                this.state =
                    payload.state;

                if (this.persistent) {
                    /*
                     * El servidor devuelve la revisión ya incrementada:
                     * la siguiente acción viajará con ella.
                     */
                    this.revision =
                        payload.revision
                        ??
                        this.revision;
                } else {
                    this.stateToken =
                        payload.state_token;
                }

                this.resultForms =
                    {};

                this.afterStateChange();
                this.persist();

                await this.$nextTick();
            } catch {
                this.error =
                    'No fue posible comunicarse con el Competition Lab.';
            } finally {
                this.loading =
                    false;
            }
        },

        afterStateChange() {
            this.syncDecisionDraft();

            if (
                this.state
                    ?.graph_runtime
            ) {
                this.labMode =
                    'AUTOMATIC';
            }

            const activeNode =
                this.activeNode();

            if (activeNode) {
                this.selectedNodeId =
                    String(
                        activeNode.id
                    );

                if (
                    activeNode
                        ?.runtime
                        ?.engine
                    ===
                    'GROUP_STAGE'
                ) {
                    this.selectedGroupId =
                        Object.keys(
                            activeNode.runtime.groups
                            ??
                            {}
                        )[0]
                        ??
                        null;
                }
            }
        },

        chooseMode(mode) {
            this.labMode =
                mode;

            this.error =
                '';

            this.persist();
        },

        leaveMode() {
            if (
                this.state
                    ?.graph_runtime
            ) {
                return;
            }

            this.labMode =
                null;

            this.selectedNodeId =
                null;

            this.selectedForEngine =
                [];

            this.persist();
        },

        async startManualMode() {
            this.labMode =
                'MANUAL';

            await this.execute(
                'START'
            );
        },

        async startTournament() {
            this.labMode =
                'AUTOMATIC';

            await this.execute(
                'START_TOURNAMENT'
            );
        },

        async stepRuntime() {
            await this.execute(
                'STEP_RUNTIME'
            );
        },

        async runTournament() {
            await this.execute(
                'RUN_TOURNAMENT',
                {
                    maximum_operations:
                        1000,
                }
            );
        },

        async resetLab() {
            if (
                !confirm(
                    '¿Reiniciar completamente el Competition Lab? Se eliminarán todos los resultados temporales.'
                )
            ) {
                return;
            }

            await this.execute(
                'RESET'
            );

            this.labMode =
                null;

            this.selectedNodeId =
                null;

            this.selectedParticipantId =
                null;

            this.selectedGroupId =
                null;

            this.selectedForEngine =
                [];

            this.expandedRounds =
                [];

            this.persist();
        },

        removeLocalState() {
            /*
             * Una competición real no se descarta desde el navegador:
             * se cancela o se elimina desde el servidor.
             */
            if (this.persistent) {
                return;
            }

            sessionStorage.removeItem(
                this.storageKey
            );

            this.state =
                null;

            this.stateToken =
                null;

            this.labMode =
                null;

            this.selectedParticipantId =
                null;

            this.selectedNodeId =
                null;
        },

        graphRuntime() {
            return this.state
                ?.graph_runtime
                ??
                null;
        },

        runtimeDiagnostics() {
            return this.graphRuntime()
                ?.diagnostics
                ??
                [];
        },

        runtimeQueue() {
            return this.graphRuntime()
                ?.operation_queue
                ??
                [];
        },

        participants() {
            return Object.values(
                this.state
                    ?.participants
                ??
                {}
            );
        },

        starts() {
            return Object.values(
                this.state
                    ?.starts
                ??
                {}
            );
        },

        nodes() {
            return Object.values(
                this.state
                    ?.nodes
                ??
                {}
            );
        },

        terminals() {
            return Object.values(
                this.state
                    ?.terminals
                ??
                {}
            );
        },

        connections() {
            return Object.values(
                this.state
                    ?.connections
                ??
                {}
            );
        },

        engineNodes() {
            return this.nodes()
                .filter(
                    node =>
                        [
                            'SINGLE_ELIMINATION',
                            'ROUND_ROBIN',
                            'GROUP_STAGE',
                            'SWISS',
                        ].includes(
                            node.phase_type
                        )
                );
        },

        activeNode() {
            return this.nodes()
                .find(
                    node =>
                        [
                            'RUNNING',
                            'READY',
                            'COMPLETED',
                            'AWAITING_DECISION',
                        ].includes(
                            node.status
                        )
                )
                ??
                null;
        },

        selectedNode() {
            if (
                !this.selectedNodeId
            ) {
                return null;
            }

            return this.state
                ?.nodes
                ?.[this.selectedNodeId]
                ??
                null;
        },

        /*
        |--------------------------------------------------------------------------
        | Caras y simulacion por fase
        |--------------------------------------------------------------------------
        */

        /* Los participantes que hay ahora mismo dentro de una fase */
        nodeParticipants(node) {
            const ids = node.participant_ids || [];

            return ids
                .map((id) => this.state?.participants?.[id])
                .filter(Boolean);
        },

        participantImageOf(participant) {
            return participant?.image_url ?? null;
        },

        participantInitialsOf(participant) {
            const name = participant?.borrowed_name || participant?.name || '';

            return name
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map((word) => word[0])
                .join('')
                .toUpperCase() || '-';
        },

        /* Color por motor, para distinguir las fases de un vistazo */
        nodeAccent(node) {
            switch (node.phase_type) {
                case 'SINGLE_ELIMINATION':
                    return {
                        chip: 'bg-violet-100 text-violet-700',
                        bar: 'from-violet-500 to-fuchsia-500',
                        ring: 'border-violet-300',
                        icon: '🏆',
                    };
                case 'ROUND_ROBIN':
                    return {
                        chip: 'bg-cyan-100 text-cyan-700',
                        bar: 'from-cyan-500 to-sky-500',
                        ring: 'border-cyan-300',
                        icon: '🔄',
                    };
                case 'GROUP_STAGE':
                    return {
                        chip: 'bg-indigo-100 text-indigo-700',
                        bar: 'from-indigo-500 to-blue-500',
                        ring: 'border-indigo-300',
                        icon: '▦',
                    };
                case 'SWISS':
                    return {
                        chip: 'bg-emerald-100 text-emerald-700',
                        bar: 'from-emerald-500 to-teal-500',
                        ring: 'border-emerald-300',
                        icon: '⇄',
                    };
                default:
                    return {
                        chip: 'bg-slate-100 text-slate-700',
                        bar: 'from-slate-500 to-slate-600',
                        ring: 'border-slate-300',
                        icon: '◆',
                    };
            }
        },

        /* Una fase esta en juego y se puede resolver de golpe */
        nodeIsRunnable(node) {
            return ['RUNNING', 'WAITING_INPUTS', 'READY'].includes(node.status);
        },

        async runNode(node) {
            if (!this.nodeIsRunnable(node)) {
                return;
            }

            await this.execute('RUN_NODE', { node_id: node.id });
        },

        selectNode(id) {
            this.selectedNodeId =
                String(id);

            const node =
                this.selectedNode();

            if (
                node
                    ?.runtime
                    ?.engine
                ===
                'GROUP_STAGE'
            ) {
                this.selectedGroupId =
                    Object.keys(
                        node.runtime.groups
                        ??
                        {}
                    )[0]
                    ??
                    null;
            }
        },

        selectParticipant(id) {
            this.selectedParticipantId =
                id;
        },

        selectedParticipant() {
            if (
                !this.selectedParticipantId
            ) {
                return null;
            }

            return this.state
                ?.participants
                ?.[
                this.selectedParticipantId
            ]
                ??
                null;
        },

        toggleEngineParticipant(id) {
            this.selectedForEngine =
                this.selectedForEngine
                    .includes(id)
                    ? this.selectedForEngine
                        .filter(
                            participantId =>
                                participantId
                                !==
                                id
                        )
                    : [
                        ...this.selectedForEngine,
                        id,
                    ];
        },

        selectAllAvailableParticipants() {
            this.selectedForEngine =
                this.participants()
                    .filter(
                        participant =>
                            [
                                'ACTIVE',
                                'WAITING',
                            ].includes(
                                participant.status
                            )
                    )
                    .map(
                        participant =>
                            participant.lab_id
                    );
        },

        clearParticipantSelection() {
            this.selectedForEngine =
                [];
        },

        async prepareSelectedNode() {
            if (
                !this.selectedNodeId
                ||
                this.selectedForEngine.length < 2
            ) {
                this.error =
                    'Selecciona una fase y al menos dos participantes.';

                return;
            }

            await this.execute(
                'PREPARE_NODE',
                {
                    node_id:
                        Number(
                            this.selectedNodeId
                        ),

                    participant_ids:
                        this.selectedForEngine,
                }
            );
        },

        rounds() {
            return this.selectedNode()
                ?.runtime
                ?.rounds
                ??
                [];
        },

        visibleRounds() {
            const node =
                this.selectedNode();

            if (
                !node
                    ?.runtime
            ) {
                return [];
            }

            if (
                node.runtime.engine
                ===
                'GROUP_STAGE'
                &&
                this.selectedGroupId
            ) {
                return this.rounds()
                    .filter(
                        round =>
                            round.group_id
                            ===
                            this.selectedGroupId
                    );
            }

            return this.rounds();
        },

        pendingRound() {
            return this.rounds()
                .find(
                    round =>
                        round.matches
                            ?.some(
                                match =>
                                    match.status
                                    ===
                                    'PENDING'
                            )
                )
                ??
                null;
        },

        roundIsExpanded(round) {
            return (
                round.status
                !==
                'COMPLETED'
                ||
                this.expandedRounds
                    .includes(
                        this.roundKey(
                            round
                        )
                    )
            );
        },

        toggleRound(round) {
            const key =
                this.roundKey(
                    round
                );

            this.expandedRounds =
                this.expandedRounds
                    .includes(key)
                    ? this.expandedRounds
                        .filter(
                            item =>
                                item !== key
                        )
                    : [
                        ...this.expandedRounds,
                        key,
                    ];
        },

        roundKey(round) {
            return `${round.group_id ?? 'PHASE'}-${round.number}`;
        },

        groups() {
            return Object.values(
                this.selectedNode()
                    ?.runtime
                    ?.groups
                ??
                {}
            );
        },

        standings() {
            const runtime =
                this.selectedNode()
                    ?.runtime;

            if (!runtime) {
                return [];
            }

            if (
                runtime.engine
                ===
                'GROUP_STAGE'
                &&
                this.selectedGroupId
            ) {
                return runtime
                    .groups
                    ?.[this.selectedGroupId]
                    ?.standings
                    ??
                    [];
            }

            return runtime.standings
                ??
                [];
        },

        outcomes() {
            return this.selectedNode()
                ?.runtime
                ?.normalized_outcomes
                ??
                this.selectedNode()
                    ?.runtime
                    ?.outcomes
                ??
                [];
        },

        runtimeWarnings() {
            return this.selectedNode()
                ?.runtime
                ?.warnings
                ??
                [];
        },

        participantName(id) {
            if (!id) {
                return 'BYE';
            }

            return this.state
                ?.participants
                ?.[id]
                ?.name
                ??
                id;
        },

        /*
         * Contexto de Biblioteca del participante (Fase 7).
         *
         * Devuelve una cadena vacía cuando el participante es sintético
         * (Competition Lab), de modo que la tarjeta se ve igual que
         * antes y solo se enriquece cuando hay una Entidad detrás.
         */
        participantSubtitle(id) {
            const participant =
                this.state
                    ?.participants
                    ?.[id];

            if (!participant) {
                return '';
            }

            const featured =
                (participant.attributes ?? [])
                    .find(attribute => attribute.featured)
                ??
                (participant.attributes ?? [])[0];

            if (featured) {
                return featured.name
                    + ' '
                    + featured.display;
            }

            return participant.entity_type_name ?? '';
        },

        participantImage(id) {
            return this.state
                ?.participants
                ?.[id]
                ?.image_url
                ??
                null;
        },

        pendingDecisionNode() {
            return this.nodes().find(node =>
                node?.runtime?.status === 'AWAITING_DECISION'
                && node?.runtime?.manual_decision
            ) ?? null;
        },

        pendingDecision() {
            return this.pendingDecisionNode()?.runtime?.manual_decision ?? null;
        },

        syncDecisionDraft() {
            const decision = this.pendingDecision();
            if (!decision) {
                this.decisionDraft = {
                    ordered_participant_ids: [],
                    selected_participant_ids: [],
                    group_assignments: {},
                };
                return;
            }

            const eligible = [...(decision.eligible_participant_ids ?? [])];
            this.decisionDraft = {
                ordered_participant_ids: eligible,
                selected_participant_ids: [],
                group_assignments: Object.fromEntries(
                    eligible.map(id => [id, ''])
                ),
            };

            const node = this.pendingDecisionNode();
            if (node) {
                this.selectedNodeId = String(node.id);
            }
        },

        moveDecisionParticipant(index, delta) {
            const target = index + delta;
            const items = this.decisionDraft.ordered_participant_ids;
            if (target < 0 || target >= items.length) {
                return;
            }

            [items[index], items[target]] = [items[target], items[index]];
            this.decisionDraft.ordered_participant_ids = [...items];
        },

        decisionNeedsSelection() {
            const decision = this.pendingDecision();
            if (!decision) {
                return false;
            }

            return Number(
                decision.required_selection_count
                ?? decision.constraints?.bye_count
                ?? 0
            ) > 0;
        },

        decisionSelected(participantId) {
            return this.decisionDraft.selected_participant_ids.includes(participantId);
        },

        toggleDecisionSelection(participantId) {
            const selected = this.decisionDraft.selected_participant_ids;
            if (selected.includes(participantId)) {
                this.decisionDraft.selected_participant_ids = selected.filter(id => id !== participantId);
                return;
            }

            const required = Number(
                this.pendingDecision()?.required_selection_count
                ?? this.pendingDecision()?.constraints?.bye_count
                ?? 0
            );

            if (required > 0 && selected.length >= required) {
                return;
            }

            this.decisionDraft.selected_participant_ids = [...selected, participantId];
        },

        decisionGroupCount(groupKey) {
            return Object.values(this.decisionDraft.group_assignments ?? {})
                .filter(value => value === groupKey)
                .length;
        },

        async resolveManualDecision() {
            const node = this.pendingDecisionNode();
            const decision = this.pendingDecision();

            if (!node || !decision) {
                this.error = 'No existe una decisión manual pendiente.';
                return;
            }

            await this.execute('RESOLVE_MANUAL_DECISION', {
                node_id: Number(node.id),
                decision_id: decision.id,
                ordered_participant_ids: this.decisionDraft.ordered_participant_ids,
                selected_participant_ids: this.decisionDraft.selected_participant_ids,
                group_assignments: this.decisionDraft.group_assignments,
            });
        },

        seriesFor(match) {
            return this.selectedNode()?.runtime?.series?.[match.id] ?? null;
        },

        seriesProgressLabel(match) {
            const series = this.seriesFor(match);
            if (!series) {
                return match.series_label ?? (match.best_of ? `BO${match.best_of}` : '');
            }

            return `Juegos ${series.game_wins_a}-${series.game_wins_b}`
                + (series.game_draws ? ` · ${series.game_draws} empate(s)` : '')
                + ` · ${series.status === 'COMPLETED' ? 'Serie cerrada' : 'Serie en curso'}`;
        },

        resultForm(match) {
            this.resultForms[match.id] ??= {
                score_a:
                    match.score_a
                    ??
                    0,

                score_b:
                    match.score_b
                    ??
                    0,

                qualifier_ids:
                    [
                        ...(match.qualifier_ids ?? []),
                    ],
            };

            return this.resultForms[
                match.id
            ];
        },

        usesQualifierSelection(match) {
            return (
                this.selectedNode()
                    ?.runtime
                    ?.mode
                ===
                'STRUCTURE_GRAPH'
                &&
                (
                    match.resolution_mode
                    !==
                    'SCORE'
                    ||
                    (match.participant_ids ?? []).length
                    !==
                    2
                    ||
                    Number(match.qualifiers_count ?? 1)
                    !==
                    1
                )
            );
        },

        qualifierIsSelected(match, participantId) {
            return this.resultForm(match)
                .qualifier_ids
                .includes(participantId);
        },

        toggleQualifier(match, participantId) {
            const form =
                this.resultForm(match);

            /*
             * Si el participante ya estaba seleccionado,
             * se elimina de la lista.
             */
            if (
                form.qualifier_ids
                    .includes(participantId)
            ) {
                form.qualifier_ids =
                    form.qualifier_ids
                        .filter(
                            id =>
                                id !== participantId
                        );

                return;
            }

            /*
             * No permite seleccionar más participantes
             * que la cantidad Q configurada.
             */
            if (
                form.qualifier_ids.length
                >=
                Number(
                    match.qualifiers_count
                    ??
                    1
                )
            ) {
                return;
            }

            /*
             * El orden de selección representa:
             * Clasificado 1, Clasificado 2, etc.
             */
            form.qualifier_ids = [
                ...form.qualifier_ids,
                participantId,
            ];
        },

        async submitQualifiers(match) {
            const qualifierIds =
                this.resultForm(match)
                    .qualifier_ids;

            await this.execute(
                'SUBMIT_ENCOUNTER_RESULT',
                {
                    node_id:
                        Number(
                            this.selectedNodeId
                        ),

                    match_id:
                        match.id,

                    qualifier_ids:
                        qualifierIds,
                }
            );
        },

        async submitResult(match) {
            const form =
                this.resultForm(
                    match
                );

            await this.execute(
                'SUBMIT_MATCH_RESULT',
                {
                    node_id:
                        Number(
                            this.selectedNodeId
                        ),

                    match_id:
                        match.id,

                    score_a:
                        Number(
                            form.score_a
                        ),

                    score_b:
                        Number(
                            form.score_b
                        ),
                }
            );
        },

        async simulateMatch(match) {
            await this.execute(
                'SIMULATE_MATCH',
                {
                    node_id:
                        Number(
                            this.selectedNodeId
                        ),

                    match_id:
                        match.id,
                }
            );
        },

        async simulatePendingRound() {
            if (
                !this.selectedNodeId
            ) {
                this.error =
                    'Selecciona una fase.';

                return;
            }

            await this.execute(
                'SIMULATE_ROUND',
                {
                    node_id:
                        Number(
                            this.selectedNodeId
                        ),
                }
            );
        },

        recordLabel(row) {
            return `${row.wins ?? 0}W · ${row.draws ?? 0}D · ${row.losses ?? 0}L`;
        },

        runtimeProgress() {
            const total =
                Number(
                    this.state
                        ?.summary
                        ?.matches
                    ??
                    0
                );

            const completed =
                Number(
                    this.state
                        ?.summary
                        ?.completed_matches
                    ??
                    0
                );

            if (total < 1) {
                const routed =
                    this.connections()
                        .filter(
                            connection =>
                                [
                                    'ROUTED',
                                    'CLOSED_EMPTY',
                                ].includes(
                                    connection.status
                                )
                        )
                        .length;

                const connections =
                    this.connections()
                        .length;

                return connections > 0
                    ? Math.round(
                        routed
                        /
                        connections
                        *
                        100
                    )
                    : 0;
            }

            return Math.min(
                100,
                Math.round(
                    completed
                    /
                    total
                    *
                    100
                )
            );
        },

        nextOperationLabel() {
            const operation =
                this.runtimeQueue()[0];

            if (!operation) {
                const node =
                    this.activeNode();

                return node
                    ? `Ejecutar ${node.name}`
                    : 'Comprobar finalización';
            }

            return {
                DISPATCH_START:
                    'Despachar participantes desde un Start',

                EVALUATE_NODE:
                    'Comprobar si una fase puede comenzar',

                ROUTE_NODE:
                    'Enviar clasificados por las conexiones',
            }[operation.type]
                ??
                operation.type;
        },

        statusLabel(status) {
            return {
                READY:
                    'Preparado',

                ACTIVE:
                    'Activo',

                WAITING:
                    'Esperando',

                WAITING_INPUTS:
                    'Esperando entradas',

                RUNNING:
                    'En ejecución',

                AWAITING_DECISION:
                    'Esperando decisión',

                COMPLETED:
                    'Completado',

                ROUTED:
                    'Enrutado',

                DISPATCHED:
                    'Despachado',

                FINISHED:
                    'Finalizado',

                BLOCKED:
                    'Bloqueado',

                STRANDED:
                    'Sin ruta',

                PENDING:
                    'Pendiente',

                CLOSED:
                    'Cerrado',

                CLOSED_EMPTY:
                    'Cerrado sin participantes',

                OVER_CAPACITY:
                    'Capacidad excedida',

                SKIPPED:
                    'Omitido',

                EMPTY:
                    'Vacío',

                RECEIVING:
                    'Recibiendo',
            }[status]
                ??
                status;
        },

        statusClass(status) {
            return {
                READY:
                    'bg-violet-100 text-violet-700',

                ACTIVE:
                    'bg-sky-100 text-sky-700',

                WAITING:
                    'bg-slate-100 text-slate-600',

                WAITING_INPUTS:
                    'bg-sky-100 text-sky-700',

                RUNNING:
                    'bg-amber-100 text-amber-700',

                AWAITING_DECISION:
                    'bg-fuchsia-100 text-fuchsia-700',

                COMPLETED:
                    'bg-emerald-100 text-emerald-700',

                ROUTED:
                    'bg-emerald-100 text-emerald-700',

                DISPATCHED:
                    'bg-emerald-100 text-emerald-700',

                FINISHED:
                    'bg-emerald-100 text-emerald-700',

                QUALIFIED:
                    'bg-emerald-100 text-emerald-700',

                BLOCKED:
                    'bg-red-100 text-red-700',

                ELIMINATED:
                    'bg-red-100 text-red-700',

                STRANDED:
                    'bg-red-100 text-red-700',

                OVER_CAPACITY:
                    'bg-red-100 text-red-700',

                PENDING:
                    'bg-slate-100 text-slate-600',

                CLOSED_EMPTY:
                    'bg-slate-100 text-slate-500',

                SKIPPED:
                    'bg-slate-100 text-slate-500',
            }[status]
                ??
                'bg-slate-100 text-slate-600';
        },
    };
}
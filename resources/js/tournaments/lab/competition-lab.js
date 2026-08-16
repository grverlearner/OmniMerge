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

        init() {
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
                !this.stateToken
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
                                JSON.stringify({
                                    action,

                                    state_token:
                                        this.stateToken,

                                    ...data,
                                }),
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

                this.stateToken =
                    payload.state_token;

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
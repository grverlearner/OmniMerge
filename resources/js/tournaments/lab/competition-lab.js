export default function competitionLab(config) {
    return {
        state: config.initialState ?? null,
        stateToken: config.initialToken ?? null,
        actionUrl: config.actionUrl,
        storageKey: config.storageKey,

        loading: false,
        error: '',
        selectedParticipantId: null,
        selectedNodeId: null,
        selectedForEngine: [],
        resultForms: {},

        init() {
            if (this.state && this.stateToken) {
                this.persist();
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
                    JSON.parse(stored);

                this.state =
                    payload.state;

                this.stateToken =
                    payload.stateToken;
            } catch {
                sessionStorage.removeItem(
                    this.storageKey
                );
            }
        },

        persist() {
            if (!this.state || !this.stateToken) {
                return;
            }

            sessionStorage.setItem(
                this.storageKey,
                JSON.stringify({
                    state: this.state,
                    stateToken: this.stateToken,
                })
            );
        },

        async execute(action, data = {}) {
            if (!this.stateToken || this.loading) {
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const response =
                    await fetch(
                        this.actionUrl,
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        ?.content
                                    ?? '',
                            },

                            body: JSON.stringify({
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
                        Object.values(errors)
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

                this.resultForms = {};

                this.persist();

                /*
                |--------------------------------------------------------------------------
                | Esperar el render inmediato de Alpine
                |--------------------------------------------------------------------------
                */

                await this.$nextTick();
            } catch {
                this.error =
                    'No fue posible comunicarse con el Competition Lab.';
            } finally {
                this.loading = false;
            }
        },

        removeLocalState() {
            sessionStorage.removeItem(
                this.storageKey
            );

            this.state = null;
            this.stateToken = null;
            this.selectedParticipantId = null;
        },

        selectParticipant(id) {
            this.selectedParticipantId = id;
        },

        selectedParticipant() {
            if (
                !this.state
                ||
                !this.selectedParticipantId
            ) {
                return null;
            }

            return this.state
                .participants[
                this.selectedParticipantId
            ]
                ??
                null;
        },

        participants() {
            return Object.values(
                this.state?.participants
                ??
                {}
            );
        },

        starts() {
            return Object.values(
                this.state?.starts
                ??
                {}
            );
        },

        nodes() {
            return Object.values(
                this.state?.nodes
                ??
                {}
            );
        },

        terminals() {
            return Object.values(
                this.state?.terminals
                ??
                {}
            );
        },
        engineNodes() {
            return this.nodes().filter(
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

        selectedNode() {
            return this.state
                ?.nodes
                ?.[this.selectedNodeId]
                ??
                null;
        },

        toggleEngineParticipant(id) {
            this.selectedForEngine =
                this.selectedForEngine.includes(id)
                    ? this.selectedForEngine.filter(
                        item =>
                            item !== id
                    )
                    : [
                        ...this.selectedForEngine,
                        id,
                    ];
        },

        async prepareSelectedNode() {
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

        standings() {
            return this.selectedNode()
                ?.runtime
                ?.standings
                ??
                [];
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

        outcomes() {
            return this.selectedNode()
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

        recordLabel(row) {
            if (
                this.selectedNode()
                    ?.runtime
                    ?.engine
                !==
                'SWISS'
            ) {
                return null;
            }

            return `${row.wins}W · ${row.draws}D · ${row.losses}L`;
        },

        statusClass(status) {
            return {
                ACTIVE:
                    'bg-sky-100 text-sky-700',

                RUNNING:
                    'bg-sky-100 text-sky-700',

                QUALIFIED:
                    'bg-emerald-100 text-emerald-700',

                COMPLETED:
                    'bg-emerald-100 text-emerald-700',

                ELIMINATED:
                    'bg-red-100 text-red-700',

                PENDING:
                    'bg-slate-100 text-slate-600',
            }[status]
                ??
                'bg-slate-100 text-slate-600';
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
            const matchId =
                match.id;

            this.resultForms[matchId] ??= {
                score_a:
                    match.score_a
                    ??
                    0,

                score_b:
                    match.score_b
                    ??
                    0,
            };

            return this.resultForms[
                matchId
            ];
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
    };
}
export default function groupStageSimulator(config) {
    return {
        state: null,
        stateToken: null,

        initializeUrl: config.initializeUrl,
        actionUrl: config.actionUrl,
        storageKey: config.storageKey,

        minParticipants: config.minParticipants,
        maxParticipants: config.maxParticipants,
        exactParticipants: config.exactParticipants,

        loading: false,
        error: '',

        builderParticipants: [],

        resultForms: {},
        expandedRounds: [],

        decisionDraft: {
            ordered_participant_ids: [],
            selected_participant_ids: [],
            group_assignments: {},
        },

        init() {
            this.quickFill(this.exactParticipants ?? Math.max(this.minParticipants, 8));

            const stored = sessionStorage.getItem(this.storageKey);

            if (!stored) {
                return;
            }

            try {
                const payload = JSON.parse(stored);

                this.state = payload.state;
                this.stateToken = payload.stateToken;

                this.syncDecisionDraft();
            } catch {
                sessionStorage.removeItem(this.storageKey);
            }
        },

        persist() {
            if (!this.state || !this.stateToken) {
                sessionStorage.removeItem(this.storageKey);

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

        /*
        |--------------------------------------------------------------------------
        | Constructor de participantes
        |--------------------------------------------------------------------------
        */

        quickFill(count) {
            const participants = [];

            for (let position = 1; position <= count; position++) {
                const existing = this.builderParticipants[position - 1];

                participants.push({
                    name: existing?.name ?? `Participante ${String(position).padStart(2, '0')}`,
                    seed: existing?.seed ?? position,
                });
            }

            this.builderParticipants = participants;
        },

        addParticipant() {
            if (this.maxParticipants && this.builderParticipants.length >= this.maxParticipants) {
                return;
            }

            const position = this.builderParticipants.length + 1;

            this.builderParticipants.push({
                name: `Participante ${String(position).padStart(2, '0')}`,
                seed: position,
            });
        },

        removeParticipant(index) {
            if (this.builderParticipants.length <= 4) {
                return;
            }

            this.builderParticipants.splice(index, 1);
        },

        canGenerate() {
            const count = this.builderParticipants.length;

            if (count < this.minParticipants) {
                return false;
            }

            if (this.maxParticipants && count > this.maxParticipants) {
                return false;
            }

            if (this.exactParticipants && count !== this.exactParticipants) {
                return false;
            }

            return !this.loading;
        },

        async generateSimulation() {
            if (!this.canGenerate()) {
                return;
            }

            this.loading = true;
            this.error = '';

            let response;

            try {
                response = await this.post(this.initializeUrl, {
                    participants: this.builderParticipants,
                });
            } finally {
                /*
                 * loading se libera ANTES de encadenar PREPARE_PHASE:
                 * execute() tiene su propio guard de re-entrada basado en
                 * this.loading, así que si se dejara en true aquí, la
                 * llamada interna se autobloquearía en silencio.
                 */
                this.loading = false;
            }

            if (!response) {
                return;
            }

            this.state = response.state;
            this.stateToken = response.state_token;

            this.persist();

            await this.execute('PREPARE_PHASE');
        },

        newSimulation() {
            if (!confirm('¿Descartar esta simulación y volver a elegir participantes?')) {
                return;
            }

            this.state = null;
            this.stateToken = null;
            this.error = '';
            this.resultForms = {};
            this.expandedRounds = [];

            sessionStorage.removeItem(this.storageKey);
        },

        async resetSimulation() {
            await this.execute('RESET');
            await this.execute('PREPARE_PHASE');
        },

        /*
        |--------------------------------------------------------------------------
        | Comunicación con el servidor
        |--------------------------------------------------------------------------
        */

        async post(url, data) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify(data),
                });

                const payload = await response.json();

                if (!response.ok) {
                    const errors = payload.errors ?? {};

                    this.error =
                        Object.values(errors).flat().join(' ')
                        || payload.message
                        || 'No fue posible completar la acción.';

                    return null;
                }

                return payload;
            } catch {
                this.error = 'No fue posible comunicarse con el simulador.';

                return null;
            }
        },

        async execute(action, data = {}) {
            if (!this.stateToken || this.loading) {
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const payload = await this.post(this.actionUrl, {
                    action,
                    state_token: this.stateToken,
                    ...data,
                });

                if (!payload) {
                    return;
                }

                this.state = payload.state;
                this.stateToken = payload.state_token;
                this.resultForms = {};

                this.syncDecisionDraft();
                this.persist();

                await this.$nextTick();
            } finally {
                this.loading = false;
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Grupos / jornadas / encuentros
        |--------------------------------------------------------------------------
        */

        runtime() {
            return this.state?.runtime ?? null;
        },

        groupsList() {
            const groups = this.runtime()?.groups ?? {};

            return Object.values(groups);
        },

        rounds() {
            return this.runtime()?.rounds ?? [];
        },

        groupRounds(group) {
            return this.rounds().filter((round) => round.group_id === group.id);
        },

        standings() {
            return this.runtime()?.standings ?? [];
        },

        isCompleted() {
            return this.runtime()?.status === 'COMPLETED';
        },

        isRunning() {
            return this.runtime()?.status === 'RUNNING';
        },

        isAwaitingDecision() {
            return this.runtime()?.status === 'AWAITING_DECISION';
        },

        participantName(id) {
            if (!id) {
                return 'BYE';
            }

            return this.state?.participants?.[id]?.name ?? id;
        },

        roundKey(round) {
            return `${round.group_id}-R${round.number}`;
        },

        roundIsExpanded(round) {
            return round.status !== 'COMPLETED' || this.expandedRounds.includes(this.roundKey(round));
        },

        toggleRound(round) {
            const key = this.roundKey(round);

            this.expandedRounds = this.expandedRounds.includes(key)
                ? this.expandedRounds.filter((item) => item !== key)
                : [...this.expandedRounds, key];
        },

        pendingRound() {
            return this.rounds().find((round) =>
                round.matches.some((match) => match.status === 'PENDING')
            ) ?? null;
        },

        /**
         * Un grupo con cantidad impar de participantes deja un slot vacío en
         * cada jornada -el motor no lo marca explícitamente-, así que se
         * calcula por diferencia dentro del propio grupo.
         */
        restingParticipantId(round, group) {
            if (group.participant_ids.length % 2 === 0) {
                return null;
            }

            const playing = new Set();

            for (const match of round.matches) {
                playing.add(match.participant_a_id);
                playing.add(match.participant_b_id);
            }

            return group.participant_ids.find((id) => !playing.has(id)) ?? null;
        },

        seriesFor(match) {
            return this.runtime()?.series?.[match.id] ?? null;
        },

        seriesProgressLabel(match) {
            const series = this.seriesFor(match);

            if (!series) {
                return match.best_of > 1 ? `BO${match.best_of}` : '';
            }

            return (
                `Juegos ${series.game_wins_a}-${series.game_wins_b}`
                + ` · ${series.status === 'COMPLETED' ? 'Serie cerrada' : 'Serie en curso'}`
            );
        },

        resultForm(match) {
            this.resultForms[match.id] ??= {
                score_a: match.score_a ?? 0,
                score_b: match.score_b ?? 0,
            };

            return this.resultForms[match.id];
        },

        async submitResult(match) {
            const form = this.resultForm(match);

            await this.execute('SUBMIT_MATCH_RESULT', {
                match_id: match.id,
                score_a: Number(form.score_a),
                score_b: Number(form.score_b),
            });
        },

        async simulateMatch(match) {
            await this.execute('SIMULATE_MATCH', {
                match_id: match.id,
            });
        },

        async simulateRound() {
            await this.execute('SIMULATE_ROUND');
        },

        /**
         * SIMULATE_ROUND (server) resuelve la primera "ronda" del runtime
         * plano, que en Group Stage equivale a la jornada de UN solo grupo
         * (cada grupo tiene su propio Round Robin interno, con numeración
         * de ronda independiente). Para que el botón por tarjeta de grupo
         * avance realmente ese grupo -y no otro elegido arbitrariamente por
         * orden de aparición-, se resuelven aquí sus encuentros pendientes
         * uno por uno reutilizando SIMULATE_MATCH, que sí es genérico.
         */
        async simulateGroupRound(group) {
            const round = this.groupRounds(group).find((candidate) =>
                candidate.matches.some((match) => match.status === 'PENDING')
            );

            if (!round) {
                return;
            }

            for (const match of round.matches) {
                if (match.status === 'PENDING') {
                    await this.simulateMatch(match);
                }
            }
        },

        groupHasPendingRound(group) {
            return this.groupRounds(group).some((round) =>
                round.matches.some((match) => match.status === 'PENDING')
            );
        },

        /*
        |--------------------------------------------------------------------------
        | Decisión manual (asignación de grupos / desempate entre grupos)
        |--------------------------------------------------------------------------
        */

        pendingDecision() {
            return this.isAwaitingDecision() ? this.runtime()?.manual_decision ?? null : null;
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

            this.decisionDraft = {
                ordered_participant_ids: [...(decision.eligible_participant_ids ?? [])],
                selected_participant_ids: [],
                group_assignments: {},
            };
        },

        isGroupAssignmentDecision() {
            return this.pendingDecision()?.type === 'GROUP_ASSIGNMENT';
        },

        decisionGroups() {
            return this.pendingDecision()?.groups ?? [];
        },

        assignToGroup(participantId, groupKey) {
            this.decisionDraft.group_assignments = {
                ...this.decisionDraft.group_assignments,
                [participantId]: groupKey,
            };
        },

        participantGroupAssignment(participantId) {
            return this.decisionDraft.group_assignments[participantId] ?? '';
        },

        groupAssignmentCount(groupKey) {
            return Object.values(this.decisionDraft.group_assignments)
                .filter((value) => value === groupKey).length;
        },

        groupAssignmentComplete() {
            const groups = this.decisionGroups();
            const eligible = this.pendingDecision()?.eligible_participant_ids ?? [];

            const everyoneAssigned = eligible.every(
                (participantId) => !!this.decisionDraft.group_assignments[participantId]
            );

            if (!everyoneAssigned) {
                return false;
            }

            return groups.every((group) => this.groupAssignmentCount(group.key) === group.size);
        },

        decisionNeedsSelection() {
            const decision = this.pendingDecision();

            if (!decision) {
                return false;
            }

            return Number(decision.required_selection_count ?? decision.constraints?.bye_count ?? 0) > 0;
        },

        decisionSelected(participantId) {
            return this.decisionDraft.selected_participant_ids.includes(participantId);
        },

        toggleDecisionSelection(participantId) {
            const selected = this.decisionDraft.selected_participant_ids;

            if (selected.includes(participantId)) {
                this.decisionDraft.selected_participant_ids = selected.filter((id) => id !== participantId);

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

        canResolveManualDecision() {
            if (this.isGroupAssignmentDecision()) {
                return this.groupAssignmentComplete();
            }

            return true;
        },

        async resolveManualDecision() {
            const decision = this.pendingDecision();

            if (!decision) {
                this.error = 'No existe una decisión manual pendiente.';

                return;
            }

            await this.execute('RESOLVE_MANUAL_DECISION', {
                decision_id: decision.id,
                ordered_participant_ids: this.decisionDraft.ordered_participant_ids,
                selected_participant_ids: this.decisionDraft.selected_participant_ids,
                group_assignments: this.decisionDraft.group_assignments,
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Puertas de salida
        |--------------------------------------------------------------------------
        */

        exitOutcomes() {
            return this.state?.exits_summary?.outcomes ?? [];
        },

        unassignedAfterExits() {
            return this.state?.exits_summary?.unassigned_ids ?? [];
        },

        /*
        |--------------------------------------------------------------------------
        | Etiquetas visuales
        |--------------------------------------------------------------------------
        */

        statusLabel(status) {
            return {
                WAITING: 'En espera',
                COMPETING: 'Compitiendo',
                QUALIFIED: 'Clasificado',
                ELIMINATED: 'Eliminado',
                PENDING: 'Pendiente',
                COMPLETED: 'Completado',
                RUNNING: 'En ejecución',
                AWAITING_DECISION: 'Esperando decisión',
            }[status] ?? status;
        },

        statusClass(status) {
            return {
                WAITING: 'bg-slate-100 text-slate-600',
                COMPETING: 'bg-sky-100 text-sky-700',
                QUALIFIED: 'bg-emerald-100 text-emerald-700',
                ELIMINATED: 'bg-red-100 text-red-700',
                PENDING: 'bg-amber-100 text-amber-700',
                COMPLETED: 'bg-emerald-100 text-emerald-700',
                RUNNING: 'bg-amber-100 text-amber-700',
                AWAITING_DECISION: 'bg-fuchsia-100 text-fuchsia-700',
            }[status] ?? 'bg-slate-100 text-slate-600';
        },
    };
}

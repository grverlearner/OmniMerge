export default function tournamentFlowBuilder(initialPayload) {
    return {
        payload: initialPayload,
        selected: null,
        selectedKind: null,
        activeView: 'FLOW',
        showCreateMenu: false,
        showConnectionForm: false,
        showPresetForm: false,
        presetType: 'LINEAR',
        showProblems: false,
        search: '',

        connection: {
            source_type: 'START',
            source_start_id: '',
            source_node_id: '',
            source_phase_exit_id: '',
            target_type: 'ENTRY_PORT',
            target_entry_port_id: '',
            target_terminal_id: '',
            allocation_mode: 'ALL',
            allocation_value: '',
            priority: 10,
            label: '',
        },

        init() {
            this.normalizePayload();

            if (this.payload.nodes.length > 0) {
                this.selectItem(
                    'NODE',
                    this.payload.nodes[0]
                );
            }
        },

        normalizePayload() {
            this.payload.starts ??= [];
            this.payload.nodes ??= [];
            this.payload.terminals ??= [];
            this.payload.connections ??= [];
            this.payload.analysis ??= {
                levels: [],
                branching_nodes: [],
                converging_nodes: [],
                stats: {},
            };
            this.payload.validation ??= {
                valid: false,
                errors: [],
                warnings: [],
                stats: {},
            };
        },

        selectItem(kind, item) {
            this.selectedKind = kind;
            this.selected = item;
        },

        clearSelection() {
            this.selectedKind = null;
            this.selected = null;
        },

        isSelected(kind, id) {
            return this.selectedKind === kind
                && Number(this.selected?.id) === Number(id);
        },

        nodeById(id) {
            return this.payload.nodes.find(
                node => Number(node.id) === Number(id)
            );
        },

        startById(id) {
            return this.payload.starts.find(
                start => Number(start.id) === Number(id)
            );
        },

        terminalById(id) {
            return this.payload.terminals.find(
                terminal => Number(terminal.id) === Number(id)
            );
        },

        levelNodes(level) {
            const ids = level.node_ids ?? [];

            return ids
                .map(id => this.nodeById(id))
                .filter(Boolean)
                .filter(node => this.matchesSearch(node));
        },

        unreachableNodes() {
            const ids =
                this.payload.analysis.unreachable_node_ids ?? [];

            return ids
                .map(id => this.nodeById(id))
                .filter(Boolean)
                .filter(node => this.matchesSearch(node));
        },

        matchesSearch(item) {
            if (!this.search.trim()) {
                return true;
            }

            const needle = this.search
                .trim()
                .toLowerCase();

            return [
                item.name,
                item.code,
                item.phase_template_name,
                item.phase_type_label,
                item.description,
            ]
                .filter(Boolean)
                .some(value =>
                    String(value)
                        .toLowerCase()
                        .includes(needle)
                );
        },

        nodeIncomingConnections(node) {
            const entryIds = (node.entries ?? [])
                .map(entry => Number(entry.id));

            return this.payload.connections.filter(
                connection =>
                    connection.target_type === 'ENTRY_PORT'
                    &&
                    entryIds.includes(
                        Number(
                            connection.target_entry_port_id
                        )
                    )
            );
        },

        nodeOutgoingConnections(node) {
            return this.payload.connections.filter(
                connection =>
                    connection.source_type === 'PHASE_EXIT'
                    &&
                    Number(connection.source_node_id)
                    === Number(node.id)
            );
        },

        startConnections(start) {
            return this.payload.connections.filter(
                connection =>
                    connection.source_type === 'START'
                    &&
                    Number(connection.source_start_id)
                    === Number(start.id)
            );
        },

        terminalConnections(terminal) {
            return this.payload.connections.filter(
                connection =>
                    connection.target_type === 'TERMINAL'
                    &&
                    Number(connection.target_terminal_id)
                    === Number(terminal.id)
            );
        },

        outgoingForExit(nodeId, exitId) {
            return this.payload.connections.filter(
                connection =>
                    connection.source_type === 'PHASE_EXIT'
                    &&
                    Number(connection.source_node_id)
                    === Number(nodeId)
                    &&
                    Number(connection.source_phase_exit_id)
                    === Number(exitId)
            );
        },

        openConnectionFromStart(start) {
            this.resetConnection();

            this.connection.source_type = 'START';
            this.connection.source_start_id = start.id;

            this.showConnectionForm = true;
        },

        openConnectionFromExit(node, exit) {
            this.resetConnection();

            this.connection.source_type = 'PHASE_EXIT';
            this.connection.source_node_id = node.id;
            this.connection.source_phase_exit_id = exit.id;

            this.showConnectionForm = true;
        },

        resetConnection() {
            this.connection = {
                source_type: 'START',
                source_start_id: '',
                source_node_id: '',
                source_phase_exit_id: '',
                target_type: 'ENTRY_PORT',
                target_entry_port_id: '',
                target_terminal_id: '',
                allocation_mode: 'ALL',
                allocation_value: '',
                priority: 10,
                label: '',
            };
        },

        changeTargetType() {
            if (this.connection.target_type === 'ENTRY_PORT') {
                this.connection.target_terminal_id = '';
            } else {
                this.connection.target_entry_port_id = '';
            }
        },

        changeAllocationMode() {
            if (
                !['TAKE_N', 'PERCENTAGE'].includes(
                    this.connection.allocation_mode
                )
            ) {
                this.connection.allocation_value = '';
            }
        },

        allocationNeedsValue() {
            return ['TAKE_N', 'PERCENTAGE'].includes(
                this.connection.allocation_mode
            );
        },

        connectionCanSubmit() {
            const hasSource =
                this.connection.source_type === 'START'
                    ? Boolean(this.connection.source_start_id)
                    : Boolean(
                        this.connection.source_node_id
                        &&
                        this.connection.source_phase_exit_id
                    );

            const hasTarget =
                this.connection.target_type === 'ENTRY_PORT'
                    ? Boolean(
                        this.connection.target_entry_port_id
                    )
                    : Boolean(
                        this.connection.target_terminal_id
                    );

            const hasAllocationValue =
                !this.allocationNeedsValue()
                ||
                this.connection.allocation_value !== '';

            return hasSource
                && hasTarget
                && hasAllocationValue;
        },

        availableEntryPorts() {
            return this.payload.nodes.flatMap(node =>
                (node.entries ?? []).map(entry => ({
                    ...entry,
                    node_id: node.id,
                    node_name: node.name,
                    label: `${node.name} · ${entry.name}`,
                }))
            );
        },

        selectedTitle() {
            return this.selected?.name ?? 'Sin selección';
        },

        selectedSubtitle() {
            if (!this.selected) {
                return '';
            }

            if (this.selectedKind === 'START') {
                return this.selected.source_type_label;
            }

            if (this.selectedKind === 'TERMINAL') {
                return this.selected.terminal_type_label;
            }

            if (this.selectedKind === 'CONNECTION') {
                return `${this.selected.source_label} → ${this.selected.target_label}`;
            }

            return this.selected.phase_type_label;
        },

        problemsCount() {
            return (
                (this.payload.validation.errors?.length ?? 0)
                +
                (this.payload.validation.warnings?.length ?? 0)
            );
        },

        hasErrors() {
            return (
                this.payload.validation.errors?.length ?? 0
            ) > 0;
        },

        branchCount(nodeId) {
            const branch = (
                this.payload.analysis.branching_nodes ?? []
            ).find(
                item => Number(item.id) === Number(nodeId)
            );

            return branch?.branches ?? 0;
        },

        convergenceCount(nodeId) {
            const convergence = (
                this.payload.analysis.converging_nodes ?? []
            ).find(
                item => Number(item.id) === Number(nodeId)
            );

            return convergence?.incoming_routes ?? 0;
        },

        /*
         * El modal no puede responder a tiempo para un submit síncrono, así
         * que se detiene SIEMPRE el envío y se relanza si el usuario acepta.
         * `requestSubmit()` conserva la validación del navegador y vuelve a
         * pasar por aquí, por eso se marca el formulario como ya aprobado.
         */
        deleteWithConfirmation(event, message) {
            event.preventDefault();

            const form = event.target;

            if (form.dataset.omniApproved === '1') {
                delete form.dataset.omniApproved;

                form.submit();

                return;
            }

            window.OmniConfirm.request({
                variant: 'danger',
                icon: '×',
                title: 'Eliminar del recorrido',
                message: message,
                actionLabel: 'Sí, eliminar',
            }).then((seguro) => {
                if (seguro) {
                    form.dataset.omniApproved = '1';
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
            });
        },
    };
}
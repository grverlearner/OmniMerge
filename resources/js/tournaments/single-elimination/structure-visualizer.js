export default function singleEliminationStructureVisualizer(
    initialPayload = {},
    configuration = {}
) {
    return {
        payload: initialPayload,

        view:
            initialPayload.options?.default_view
            || "blocks",

        density: "comfortable",

        query: "",

        severityFilter: "ALL",

        generationFilter: "ALL",

        roundFilter: "ALL",

        selectedKey: null,

        selected: null,

        inspectorOpen: false,

        problemsOpen: false,

        traceMode: "DIRECT",

        expandedRounds: {},

        itemIndex: {},

        connectionIndex: {},

        adjacencyForward: {},

        adjacencyBackward: {},

        highlightedKeys: [],

        traceDirectKeys: [],

        traceUpstreamKeys: [],

        traceDownstreamKeys: [],

        preferenceKey:
            `omnimerge:single-elimination-visualizer:${initialPayload.phase?.id || "unknown"}`,

        updateUrlTemplate:
            configuration.updateUrlTemplate
            || "",

        initialSelection:
            configuration.initialSelection
            || "",

        initialView:
            configuration.initialView
            || "",

        init() {
            this.buildIndexes();
            this.restorePreferences();

            if (
                [
                    "compact",
                    "blocks",
                    "table",
                ].includes(this.initialView)
            ) {
                this.view =
                    this.initialView;
            }

            const firstRound =
                this.payload.rounds?.[0];

            if (firstRound) {
                this.expandedRounds[firstRound.key] =
                    true;
            }

            if (this.initialSelection) {
                this.$nextTick(
                    () => {
                        this.select(
                            this.initialSelection,
                            false
                        );
                    }
                );
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Índices del grafo
        |--------------------------------------------------------------------------
        */

        buildIndexes() {
            this.itemIndex = {};
            this.connectionIndex = {};
            this.adjacencyForward = {};
            this.adjacencyBackward = {};

            const register =
                (item) => {
                    if (!item?.key) {
                        return;
                    }

                    this.itemIndex[item.key] =
                        item;
                };

            register(
                this.payload.phase
            );

            (this.payload.input_gates || [])
                .forEach(register);

            (this.payload.rounds || [])
                .forEach(
                    (round) => {
                        register(round);

                        (round.encounters || [])
                            .forEach(
                                (encounter) => {
                                    register(encounter);

                                    (encounter.slots || [])
                                        .forEach(register);

                                    (encounter.results || [])
                                        .forEach(register);

                                    /*
                                    * Un encuentro consume slots y produce resultados.
                                    * Esta arista existe lógicamente, aunque no tenga
                                    * un registro CONNECTION propio.
                                    */
                                    (encounter.slots || [])
                                        .forEach(
                                            (slot) =>
                                                (encounter.results || [])
                                                    .forEach(
                                                        (result) => {
                                                            this.addAdjacency(
                                                                this.adjacencyForward,
                                                                slot.key,
                                                                result.key,
                                                                null
                                                            );

                                                            this.addAdjacency(
                                                                this.adjacencyBackward,
                                                                result.key,
                                                                slot.key,
                                                                null
                                                            );
                                                        }
                                                    )
                                        );
                                }
                            );
                    }
                );

            (this.payload.exits || [])
                .forEach(register);

            (this.payload.connections || [])
                .forEach(
                    (connection) => {
                        register(connection);

                        this.connectionIndex[connection.key] =
                            connection;

                        this.addAdjacency(
                            this.adjacencyForward,
                            connection.source_key,
                            connection.target_key,
                            connection.key
                        );

                        this.addAdjacency(
                            this.adjacencyBackward,
                            connection.target_key,
                            connection.source_key,
                            connection.key
                        );
                    }
                );
        },

        addAdjacency(
            index,
            source,
            target,
            connectionKey
        ) {
            if (!source || !target) {
                return;
            }

            if (!index[source]) {
                index[source] = [];
            }

            index[source].push({
                target,
                connectionKey,
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Preferencias del usuario
        |--------------------------------------------------------------------------
        */

        restorePreferences() {
            try {
                const stored =
                    JSON.parse(
                        localStorage.getItem(
                            this.preferenceKey
                        )
                        || "{}"
                    );

                if (
                    [
                        "compact",
                        "blocks",
                        "table",
                    ].includes(stored.view)
                ) {
                    this.view =
                        stored.view;
                }

                if (
                    [
                        "comfortable",
                        "dense",
                    ].includes(stored.density)
                ) {
                    this.density =
                        stored.density;
                }
            } catch (error) {
                /*
                 * Las preferencias visuales nunca deben impedir
                 * que el grafo sea mostrado.
                 */
            }
        },

        persistPreferences() {
            try {
                localStorage.setItem(
                    this.preferenceKey,
                    JSON.stringify({
                        view:
                            this.view,

                        density:
                            this.density,
                    })
                );
            } catch (error) {
                /*
                 * El visualizador continuará aunque el navegador
                 * haya bloqueado localStorage.
                 */
            }
        },

        setView(view) {
            if (
                ![
                    "compact",
                    "blocks",
                    "table",
                ].includes(view)
            ) {
                return;
            }

            this.view =
                view;

            this.persistPreferences();
            this.syncUrl();
        },

        setDensity(density) {
            if (
                ![
                    "comfortable",
                    "dense",
                ].includes(density)
            ) {
                return;
            }

            this.density =
                density;

            this.persistPreferences();
        },

        /*
        |--------------------------------------------------------------------------
        | Selección e inspector
        |--------------------------------------------------------------------------
        */

        select(
            key,
            scroll = true
        ) {
            const item =
                this.itemIndex[key];

            if (!item) {
                return;
            }

            this.selectedKey =
                key;

            this.selected =
                item;

            this.inspectorOpen =
                true;

            if (item.round_key) {
                this.expandedRounds[item.round_key] =
                    true;
            }

            if (item.kind === "ROUND") {
                this.expandedRounds[item.key] =
                    true;
            }

            this.rebuildTrace();
            this.syncUrl();

            if (scroll) {
                this.$nextTick(
                    () =>
                        this.scrollToKey(key)
                );
            }
        },

        closeInspector() {
            this.inspectorOpen =
                false;

            /*
             * Cerrar el inspector no elimina
             * el trazado seleccionado.
             */
            this.syncUrl();
        },

        openInspector() {
            if (!this.selected) {
                return;
            }

            this.inspectorOpen =
                true;
        },

        clearSelection() {
            this.inspectorOpen =
                false;

            this.selectedKey =
                null;

            this.selected =
                null;

            this.highlightedKeys =
                [];

            this.traceDirectKeys =
                [];

            this.traceUpstreamKeys =
                [];

            this.traceDownstreamKeys =
                [];

            this.syncUrl();
        },

        syncUrl() {
            const url =
                new URL(
                    window.location.href
                );

            url.searchParams.set(
                "view",
                this.view
            );

            if (this.selectedKey) {
                url.searchParams.set(
                    "selected",
                    this.selectedKey
                );
            } else {
                url.searchParams.delete(
                    "selected"
                );
            }

            window.history.replaceState(
                {},
                "",
                url
            );
        },

        scrollToKey(key) {
            const selector =
                `[data-structure-key="${key}"]`;

            const element =
                document.querySelector(
                    selector
                );

            element?.scrollIntoView({
                behavior:
                    "smooth",

                block:
                    "center",
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Seguimiento de rutas
        |--------------------------------------------------------------------------
        */

        setTraceMode(mode) {
            if (
                ![
                    "DIRECT",
                    "UPSTREAM",
                    "DOWNSTREAM",
                    "FULL",
                ].includes(mode)
            ) {
                return;
            }

            this.traceMode =
                mode;

            this.rebuildTrace();
        },

        rebuildTrace() {
            if (!this.selected) {
                this.highlightedKeys = [];
                this.traceDirectKeys = [];
                this.traceUpstreamKeys = [];
                this.traceDownstreamKeys = [];

                return;
            }

            const highlighted =
                new Set([
                    this.selected.key,
                ]);

            const direct =
                new Set();

            const upstream =
                new Set();

            const downstream =
                new Set();

            const originKeys =
                this.traceOriginKeys(
                    this.selected
                );

            originKeys.forEach(
                (key) => highlighted.add(key)
            );

            if (
                this.selected.kind
                ===
                "ROUND"
            ) {
                (this.selected.encounters || [])
                    .forEach(
                        (encounter) =>
                            highlighted.add(
                                encounter.key
                            )
                    );
            }

            if (
                this.selected.kind
                ===
                "ENCOUNTER"
            ) {
                [
                    ...(this.selected.slots || []),
                    ...(this.selected.results || []),
                ].forEach(
                    (child) =>
                        highlighted.add(
                            child.key
                        )
                );
            }

            if (
                this.selected.kind
                ===
                "CONNECTION"
            ) {
                highlighted.add(
                    this.selected.source_key
                );

                highlighted.add(
                    this.selected.target_key
                );

                direct.add(
                    this.selected.key
                );

                direct.add(
                    this.selected.source_key
                );

                direct.add(
                    this.selected.target_key
                );

                if (
                    [
                        "UPSTREAM",
                        "FULL",
                    ].includes(this.traceMode)
                ) {
                    this.walkGraph(
                        this.selected.source_key,
                        this.adjacencyBackward,
                        highlighted,
                        upstream
                    );
                }

                if (
                    [
                        "DOWNSTREAM",
                        "FULL",
                    ].includes(this.traceMode)
                ) {
                    this.walkGraph(
                        this.selected.target_key,
                        this.adjacencyForward,
                        highlighted,
                        downstream
                    );
                }

                this.storeTraceSets(
                    highlighted,
                    direct,
                    upstream,
                    downstream
                );

                return;
            }

            if (
                this.traceMode
                ===
                "DIRECT"
            ) {
                originKeys.forEach(
                    (originKey) =>
                        this.collectDirect(
                            originKey,
                            highlighted,
                            direct
                        )
                );
            }

            if (
                [
                    "UPSTREAM",
                    "FULL",
                ].includes(this.traceMode)
            ) {
                originKeys.forEach(
                    (originKey) =>
                        this.walkGraph(
                            originKey,
                            this.adjacencyBackward,
                            highlighted,
                            upstream
                        )
                );
            }

            if (
                [
                    "DOWNSTREAM",
                    "FULL",
                ].includes(this.traceMode)
            ) {
                originKeys.forEach(
                    (originKey) =>
                        this.walkGraph(
                            originKey,
                            this.adjacencyForward,
                            highlighted,
                            downstream
                        )
                );
            }

            this.storeTraceSets(
                highlighted,
                direct,
                upstream,
                downstream
            );
        },

        traceOriginKeys(item) {
            if (!item) {
                return [];
            }

            if (item.kind === "ROUND") {
                return (item.encounters || [])
                    .flatMap(
                        (encounter) => [
                            ...(encounter.slots || []),
                            ...(encounter.results || []),
                        ]
                    )
                    .map(
                        (child) => child.key
                    );
            }

            if (item.kind === "ENCOUNTER") {
                return [
                    ...(item.slots || []),
                    ...(item.results || []),
                ].map(
                    (child) => child.key
                );
            }

            if (item.kind === "CONNECTION") {
                return [
                    item.source_key,
                    item.target_key,
                ].filter(Boolean);
            }

            return item.key
                ? [item.key]
                : [];
        },

        collectDirect(
            originKey,
            highlighted,
            direct
        ) {
            highlighted.add(originKey);
            direct.add(originKey);

            [
                ...(this.adjacencyForward[originKey] || []),
                ...(this.adjacencyBackward[originKey] || []),
            ].forEach(
                (edge) => {
                    highlighted.add(
                        edge.target
                    );

                    direct.add(
                        edge.target
                    );

                    if (edge.connectionKey) {
                        highlighted.add(
                            edge.connectionKey
                        );

                        direct.add(
                            edge.connectionKey
                        );
                    }
                }
            );
        },

        walkGraph(
            originKey,
            adjacency,
            highlighted,
            direction
        ) {
            const queue =
                [originKey];

            const visited =
                new Set();

            while (queue.length > 0) {
                const current =
                    queue.shift();

                if (visited.has(current)) {
                    continue;
                }

                visited.add(current);
                highlighted.add(current);
                direction.add(current);

                (adjacency[current] || [])
                    .forEach(
                        (edge) => {
                            if (edge.connectionKey) {
                                highlighted.add(
                                    edge.connectionKey
                                );

                                direction.add(
                                    edge.connectionKey
                                );
                            }

                            highlighted.add(
                                edge.target
                            );

                            direction.add(
                                edge.target
                            );

                            if (
                                !visited.has(
                                    edge.target
                                )
                            ) {
                                queue.push(
                                    edge.target
                                );
                            }
                        }
                    );
            }
        },

        storeTraceSets(
            highlighted,
            direct,
            upstream,
            downstream
        ) {
            this.highlightedKeys =
                Array.from(highlighted);

            this.traceDirectKeys =
                Array.from(direct);

            this.traceUpstreamKeys =
                Array.from(upstream);

            this.traceDownstreamKeys =
                Array.from(downstream);
        },

        isHighlighted(key) {
            return this.highlightedKeys
                .includes(key);
        },

        itemTraceKeys(item) {
            if (!item) {
                return [];
            }

            const keys =
                new Set([
                    item.key,
                    ...(item.route_keys || []),
                ]);

            if (item.kind === "ROUND") {
                (item.encounters || [])
                    .forEach(
                        (encounter) => {
                            keys.add(
                                encounter.key
                            );

                            [
                                ...(encounter.slots || []),
                                ...(encounter.results || []),
                            ].forEach(
                                (child) =>
                                    keys.add(
                                        child.key
                                    )
                            );
                        }
                    );
            }

            if (item.kind === "ENCOUNTER") {
                [
                    ...(item.slots || []),
                    ...(item.results || []),
                ].forEach(
                    (child) =>
                        keys.add(
                            child.key
                        )
                );
            }

            return Array.from(keys)
                .filter(Boolean);
        },

        isDimmed(item) {
            if (
                !this.selectedKey
                ||
                this.highlightedKeys.length === 0
            ) {
                return false;
            }

            return !this.itemTraceKeys(item)
                .some(
                    (key) =>
                        this.isHighlighted(key)
                );
        },

        traceState(item) {
            if (
                !item
                ||
                item.key === this.selectedKey
            ) {
                return "SELECTED";
            }

            const keys =
                this.itemTraceKeys(item);

            const hasUpstream =
                keys.some(
                    (key) =>
                        this.traceUpstreamKeys
                            .includes(key)
                );

            const hasDownstream =
                keys.some(
                    (key) =>
                        this.traceDownstreamKeys
                            .includes(key)
                );

            if (
                hasUpstream
                &&
                hasDownstream
            ) {
                return "BOTH";
            }

            if (hasUpstream) {
                return "UPSTREAM";
            }

            if (hasDownstream) {
                return "DOWNSTREAM";
            }

            if (
                keys.some(
                    (key) =>
                        this.traceDirectKeys
                            .includes(key)
                )
            ) {
                return "DIRECT";
            }

            return "NONE";
        },

        traceClass(item) {
            return {
                DIRECT:
                    "ring-2 ring-violet-400 ring-offset-2",

                UPSTREAM:
                    "ring-2 ring-sky-400 ring-offset-2",

                DOWNSTREAM:
                    "ring-2 ring-emerald-400 ring-offset-2",

                BOTH:
                    "ring-2 ring-amber-400 ring-offset-2",
            }[this.traceState(item)]
                || "";
        },

        traceConnections() {
            return (this.payload.connections || [])
                .filter(
                    (connection) =>
                        this.highlightedKeys
                            .includes(
                                connection.key
                            )
                );
        },

        traceNodeCount() {
            return this.highlightedKeys
                .filter(
                    (key) =>
                        !key.startsWith(
                            "CONNECTION:"
                        )
                )
                .length;
        },

        traceModeLabel() {
            return {
                DIRECT:
                    "Rutas directas",

                UPSTREAM:
                    "Solo origen",

                DOWNSTREAM:
                    "Solo destino",

                FULL:
                    "Ruta completa",
            }[this.traceMode]
                || this.traceMode;
        },

        traceModeDescription() {
            return {
                DIRECT:
                    "Muestra únicamente las rutas que tocan el elemento seleccionado.",

                UPSTREAM:
                    "Retrocede hasta encontrar las puertas o resultados que lo alimentan.",

                DOWNSTREAM:
                    "Avanza hasta los encuentros y salidas alcanzables.",

                FULL:
                    "Combina el recorrido anterior y posterior.",
            }[this.traceMode]
                || "";
        },

        /*
        |--------------------------------------------------------------------------
        | Rondas desplegables
        |--------------------------------------------------------------------------
        */

        toggleRound(roundKey) {
            this.expandedRounds[roundKey] =
                !this.expandedRounds[roundKey];
        },

        isRoundExpanded(roundKey) {
            return Boolean(
                this.expandedRounds[roundKey]
            );
        },

        expandAllRounds() {
            (this.payload.rounds || [])
                .forEach(
                    (round) => {
                        this.expandedRounds[round.key] =
                            true;
                    }
                );
        },

        collapseAllRounds() {
            this.expandedRounds =
                {};
        },

        /*
        |--------------------------------------------------------------------------
        | Búsqueda y filtros
        |--------------------------------------------------------------------------
        */

        visibleRounds() {
            return (this.payload.rounds || [])
                .map(
                    (round) => ({
                        ...round,

                        visible_encounters:
                            (round.encounters || [])
                                .filter(
                                    (encounter) =>
                                        this.matchesEncounter(
                                            encounter
                                        )
                                ),
                    })
                )
                .filter(
                    (round) =>
                        this.roundFilter === "ALL"
                        ||
                        round.key === this.roundFilter
                )
                .filter(
                    (round) =>
                        round.visible_encounters.length > 0
                        ||
                        this.noActiveFilters()
                );
        },

        matchesEncounter(encounter) {
            if (
                this.roundFilter !== "ALL"
                &&
                encounter.round_key !== this.roundFilter
            ) {
                return false;
            }

            if (
                this.severityFilter !== "ALL"
                &&
                encounter.issue_level !== this.severityFilter
            ) {
                return false;
            }

            if (
                this.generationFilter !== "ALL"
                &&
                encounter.generation_source !== this.generationFilter
            ) {
                return false;
            }

            const needle =
                this.normalizedQuery();

            if (!needle) {
                return true;
            }

            const searchable = [
                encounter.name,
                encounter.code,
                encounter.global_label,
                `Encuentro global ${encounter.global_number}`,
                `Encuentro de ronda ${encounter.local_number}`,
                encounter.round_name,
                encounter.profile,
                encounter.series,

                ...(encounter.slots || [])
                    .flatMap(
                        (slot) => [
                            slot.name,
                            slot.code,
                            slot.global_label,
                            `Slot global ${slot.global_number}`,
                            `Slot de encuentro ${slot.local_number}`,
                        ]
                    ),

                ...(encounter.source_labels || []),
                ...(encounter.destination_labels || []),
            ]
                .join(" ")
                .toLocaleLowerCase();

            return searchable.includes(
                needle
            );
        },

        visibleConnections() {
            const needle =
                this.normalizedQuery();

            return (this.payload.connections || [])
                .filter(
                    (connection) => {
                        if (
                            this.severityFilter !== "ALL"
                            &&
                            connection.issue_level !== this.severityFilter
                        ) {
                            return false;
                        }

                        if (
                            this.generationFilter !== "ALL"
                            &&
                            connection.generation_source !== this.generationFilter
                        ) {
                            return false;
                        }

                        if (
                            this.roundFilter !== "ALL"
                            &&
                            connection.source_round_key !== this.roundFilter
                            &&
                            connection.target_round_key !== this.roundFilter
                        ) {
                            return false;
                        }

                        if (!needle) {
                            return true;
                        }

                        return [
                            connection.code,
                            connection.name,
                            connection.source_label,
                            connection.target_label,
                            connection.allocation,
                        ]
                            .join(" ")
                            .toLocaleLowerCase()
                            .includes(needle);
                    }
                );
        },

        normalizedQuery() {
            return String(
                this.query
                || ""
            )
                .trim()
                .toLocaleLowerCase();
        },

        noActiveFilters() {
            return !this.normalizedQuery()
                &&
                this.severityFilter === "ALL"
                &&
                this.generationFilter === "ALL"
                &&
                this.roundFilter === "ALL";
        },

        clearFilters() {
            this.query = "";
            this.severityFilter = "ALL";
            this.generationFilter = "ALL";
            this.roundFilter = "ALL";
        },

        /*
        |--------------------------------------------------------------------------
        | Problemas de validación
        |--------------------------------------------------------------------------
        */

        goToIssue(issue) {
            this.problemsOpen =
                false;

            if (!issue.entity_key) {
                return;
            }

            const item =
                this.itemIndex[issue.entity_key];

            if (
                item
                &&
                item.kind === "CONNECTION"
            ) {
                this.setView("table");
            } else {
                this.setView("blocks");
            }

            this.select(
                issue.entity_key
            );
        },

        nextIssue() {
            const issues =
                this.payload.issues
                || [];

            if (issues.length === 0) {
                return;
            }

            const currentIndex =
                issues.findIndex(
                    (issue) =>
                        issue.entity_key
                        ===
                        this.selectedKey
                );

            const next =
                issues[
                (currentIndex + 1)
                %
                issues.length
                ];

            this.goToIssue(next);
        },

        /*
        |--------------------------------------------------------------------------
        | Edición desde inspector
        |--------------------------------------------------------------------------
        */

        elementUpdateUrl() {
            if (
                !this.selected
                ||
                !this.updateUrlTemplate
            ) {
                return "#";
            }

            return this.updateUrlTemplate
                .replace(
                    "__TYPE__",
                    encodeURIComponent(
                        this.selected.kind
                    )
                )
                .replace(
                    "__ID__",
                    encodeURIComponent(
                        this.selected.id
                    )
                );
        },

        selectedHasName() {
            return [
                "INPUT_GATE",
                "ROUND",
                "ENCOUNTER",
                "RESULT",
                "PHASE_EXIT",
            ].includes(
                this.selected?.kind
            );
        },

        selectedHasLabel() {
            return this.selected?.kind
                ===
                "CONNECTION";
        },

        selectedHasDescription() {
            return ![
                "SLOT",
            ].includes(
                this.selected?.kind
            );
        },

        selectedIsLockable() {
            return this.selected?.kind
                !==
                "PHASE_EXIT";
        },

        selectedEditable() {
            return this.selected
                &&
                this.selected.kind
                !==
                "PHASE_TEMPLATE";
        },

        /*
        |--------------------------------------------------------------------------
        | Etiquetas visuales
        |--------------------------------------------------------------------------
        */

        severityLabel(level) {
            return {
                ERROR:
                    "Error",

                WARNING:
                    "Advertencia",

                RECOMMENDATION:
                    "Recomendación",

                NONE:
                    "Sin problemas",
            }[level]
                ||
                level;
        },

        statusLabel(status) {
            return {
                ACTIVE:
                    "Activo",

                INACTIVE:
                    "Inactivo",
            }[status]
                ||
                status;
        },
    };
}
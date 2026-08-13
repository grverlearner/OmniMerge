<x-tournament-layout>

    <x-slot name="header">
        Tournament Graph · {{ $tournamentTemplate->name }}
    </x-slot>


    @include('tournaments.partials.template-navigation')


    {{-- ========================================================= --}}
    {{-- ERRORS --}}
    {{-- ========================================================= --}}

    @if ($errors->any())

        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-5">

            <p class="font-black text-red-800">
                Revisa la configuración
            </p>

            @foreach ($errors->all() as $error)
                <p class="mt-1 text-xs text-red-600">
                    • {{ $error }}
                </p>
            @endforeach

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- HERO --}}
    {{-- ========================================================= --}}

    <section
        class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 p-7 text-white shadow-xl">

        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-amber-400/20 blur-3xl">
        </div>

        <div class="relative flex flex-col justify-between gap-6 xl:flex-row xl:items-end">

            <div>

                <div
                    class="inline-flex rounded-full border border-amber-300/20 bg-amber-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
                    ◇ Tournament Graph Foundation
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight">
                    {{ $tournamentTemplate->name }}
                </h1>

                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-300">
                    Diseña visualmente el flujo completo del torneo.
                    Arrastra Nodes, selecciona una salida y después una entrada
                    para crear conexiones, divide caminos y vuelve a unirlos.
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <form method="POST"
                    action="{{ route('tournaments.graph.validate', $tournamentTemplate) }}">

                    @csrf

                    <button type="submit"
                        class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black text-white transition hover:bg-white/15">

                        ✓ Validar

                    </button>

                </form>


                <form method="POST"
                    action="{{ route('tournaments.graph.auto-layout', $tournamentTemplate) }}">

                    @csrf

                    <button type="submit" class="rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white">

                        ✦ Auto-layout

                    </button>

                </form>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- STATS --}}
    {{-- ========================================================= --}}

    <section class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-5">

        @foreach ([['Starts', $graphValidation['stats']['starts']], ['Nodes', $graphValidation['stats']['nodes']], ['Conexiones', $graphValidation['stats']['connections']], ['Terminales', $graphValidation['stats']['terminals']], ['Estado', $graphValidation['valid'] ? 'Válido' : 'Incompleto']] as [$label, $value])
            <article class="rounded-2xl border border-slate-200 bg-white p-4">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                    {{ $label }}
                </p>

                <p
                    class="mt-2 text-lg font-black
                    {{ $label === 'Estado' ? ($graphValidation['valid'] ? 'text-emerald-600' : 'text-amber-600') : 'text-slate-900' }}">

                    {{ $value }}

                </p>

            </article>
        @endforeach

    </section>


    {{-- ========================================================= --}}
    {{-- MAIN WORKSPACE --}}
    {{-- ========================================================= --}}

    <div x-data="tournamentGraphBuilder(@js($graphPayload))" @pointermove.window="moveDrag($event)" @pointerup.window="endDrag()"
        class="mt-6 grid gap-5 2xl:grid-cols-[330px_minmax(0,1fr)_360px]">


        {{-- ===================================================== --}}
        {{-- LEFT TOOLBOX --}}
        {{-- ===================================================== --}}

        <aside class="space-y-4">


            {{-- ADD PHASE --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-5">

                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-amber-600">
                    Phase Library
                </p>

                <h2 class="mt-2 font-black text-slate-900">
                    Agregar Fase
                </h2>

                <form method="POST"
                    action="{{ route('tournaments.graph.nodes.store', $tournamentTemplate) }}"
                    class="mt-5 space-y-3">

                    @csrf


                    <div>

                        <label class="text-[10px] font-black uppercase text-slate-500">
                            PhaseTemplate
                        </label>

                        <select name="phase_template_id" required
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm">

                            <option value="">
                                Seleccionar...
                            </option>

                            @foreach ($availablePhaseTemplates as $phaseTemplate)
                                <option value="{{ $phaseTemplate->id }}">

                                    {{ $phaseTemplate->name }}
                                    ·
                                    {{ $phaseTemplate->type_label }}

                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label class="text-[10px] font-black uppercase text-slate-500">
                            Alias en este torneo
                        </label>

                        <input type="text" name="name" placeholder="Opcional"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm">

                    </div>


                    <input type="hidden" name="status" value="ACTIVE">


                    <button type="submit"
                        class="w-full rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white">

                        + Colocar en canvas

                    </button>

                </form>

            </section>


            {{-- START --}}

            <section class="rounded-3xl border border-emerald-200 bg-emerald-50/50 p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">
                    Tournament Start
                </p>

                <form method="POST"
                    action="{{ route('tournaments.graph.starts.store', $tournamentTemplate) }}"
                    class="mt-4 space-y-3">

                    @csrf


                    <input type="text" name="name" required placeholder="Ej. Participantes iniciales"
                        class="w-full rounded-xl border-emerald-200 bg-white text-sm">


                    <select name="source_type" class="w-full rounded-xl border-emerald-200 bg-white text-sm">

                        <option value="MAIN_POOL">
                            Pool principal
                        </option>

                        <option value="SEEDED_POOL">
                            Pool con seeds
                        </option>

                        <option value="QUALIFIER_POOL">
                            Clasificados previos
                        </option>

                        <option value="INVITED_POOL">
                            Invitados
                        </option>

                        <option value="CUSTOM">
                            Personalizado
                        </option>

                    </select>


                    <input type="number" name="expected_participants" min="1" max="512"
                        placeholder="Participantes esperados"
                        class="w-full rounded-xl border-emerald-200 bg-white text-sm">


                    <input type="hidden" name="status" value="ACTIVE">


                    <button type="submit"
                        class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-xs font-black text-white">

                        + Crear inicio

                    </button>

                </form>

            </section>


            {{-- TERMINAL --}}

            <section class="rounded-3xl border border-rose-200 bg-rose-50/50 p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-rose-700">
                    Tournament Terminal
                </p>

                <form method="POST"
                    action="{{ route('tournaments.graph.terminals.store', $tournamentTemplate) }}"
                    class="mt-4 space-y-3">

                    @csrf


                    <input type="text" name="name" required placeholder="Ej. Campeón"
                        class="w-full rounded-xl border-rose-200 bg-white text-sm">


                    <select name="terminal_type" class="w-full rounded-xl border-rose-200 bg-white text-sm">

                        <option value="CHAMPION">
                            Campeón
                        </option>

                        <option value="QUALIFIED">
                            Clasificados
                        </option>

                        <option value="ELIMINATED">
                            Eliminados
                        </option>

                        <option value="SECONDARY">
                            Ruta secundaria
                        </option>

                        <option value="PLACEMENT">
                            Posición final
                        </option>

                        <option value="CUSTOM">
                            Personalizado
                        </option>

                    </select>


                    <input type="number" name="expected_participants" min="1" max="512"
                        placeholder="Cantidad esperada" class="w-full rounded-xl border-rose-200 bg-white text-sm">


                    <input type="hidden" name="status" value="ACTIVE">


                    <button type="submit"
                        class="w-full rounded-xl bg-rose-600 px-4 py-3 text-xs font-black text-white">

                        + Crear terminal

                    </button>

                </form>

            </section>

        </aside>


        {{-- ===================================================== --}}
        {{-- CANVAS --}}
        {{-- ===================================================== --}}

        <section class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-slate-100">

            {{-- TOOLBAR --}}

            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">

                <div class="flex items-center gap-2">

                    <button type="button" @click="zoom = Math.max(.5, zoom - .1)"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-600">

                        −

                    </button>

                    <span class="min-w-[58px] text-center text-xs font-black text-slate-600"
                        x-text="Math.round(zoom * 100) + '%'">
                    </span>

                    <button type="button" @click="zoom = Math.min(1.5, zoom + .1)"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-600">

                        +

                    </button>

                    <button type="button" @click="zoom = 1"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-[10px] font-black text-slate-500">

                        100%

                    </button>

                </div>


                <div class="flex items-center gap-2">

                    <template x-if="pendingSource">

                        <div class="rounded-lg bg-violet-100 px-3 py-2 text-[10px] font-black text-violet-700">

                            Origen:
                            <span x-text="pendingSource.label"></span>

                        </div>

                    </template>


                    <button type="button" x-show="pendingSource" @click="pendingSource = null"
                        class="rounded-lg bg-slate-200 px-3 py-2 text-[10px] font-black text-slate-600">

                        Cancelar conexión

                    </button>

                </div>

            </div>


            {{-- SCROLL AREA --}}

            <div class="relative h-[780px] overflow-auto">

                <div class="relative h-[1600px] w-[2600px] origin-top-left" :style="`transform: scale(${zoom});`">


                    {{-- GRID --}}

                    <div class="pointer-events-none absolute inset-0 opacity-[0.45]"
                        style="
                            background-image:
                                linear-gradient(to right, #cbd5e1 1px, transparent 1px),
                                linear-gradient(to bottom, #cbd5e1 1px, transparent 1px);
                            background-size: 32px 32px;
                        ">
                    </div>


                    {{-- SVG CONNECTIONS --}}

                    <svg class="pointer-events-none absolute inset-0 h-full w-full overflow-visible"
                        viewBox="0 0 2600 1600">

                        <defs>

                            <marker id="graph-arrow" markerWidth="10" markerHeight="10" refX="8"
                                refY="3" orient="auto" markerUnits="strokeWidth">

                                <path d="M0,0 L0,6 L9,3 z" fill="#8b5cf6">
                                </path>

                            </marker>

                        </defs>


                        <template x-for="connection in connections" :key="'line-' + connection.id">

                            <path :d="connectionPath(connection)" fill="none" stroke="#8b5cf6" stroke-width="3"
                                stroke-linecap="round" marker-end="url(#graph-arrow)"
                                :opacity="connection.status === 'ACTIVE' ? 0.85 : 0.3">
                            </path>

                        </template>

                    </svg>


                    {{-- ================================================= --}}
                    {{-- STARTS --}}
                    {{-- ================================================= --}}

                    <template x-for="start in starts" :key="'start-' + start.id">

                        <article
                            class="absolute w-[260px] select-none rounded-2xl border-2 border-emerald-300 bg-white shadow-lg shadow-emerald-950/5"
                            :style="`left:${start.x}px;top:${start.y}px;`">


                            <div @pointerdown.prevent="startDrag($event, 'start', start)"
                                class="cursor-grab rounded-t-2xl bg-emerald-600 p-4 text-white active:cursor-grabbing">

                                <div class="flex items-start justify-between gap-3">

                                    <div>

                                        <p class="text-[9px] font-black uppercase tracking-wider text-emerald-200">
                                            START ·
                                            <span x-text="start.code"></span>
                                        </p>

                                        <p class="mt-1 font-black" x-text="start.name">
                                        </p>

                                    </div>

                                    <span class="text-lg">
                                        ▶
                                    </span>

                                </div>

                            </div>


                            <div class="relative p-4">

                                <p class="text-xs font-bold text-slate-500" x-text="start.type">
                                </p>

                                <p x-show="start.expected" class="mt-1 text-[10px] text-slate-400">

                                    Esperados:
                                    <span x-text="start.expected"></span>

                                </p>


                                <button type="button" @click.stop="chooseStartSource(start)"
                                    class="absolute -right-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-emerald-500 text-[9px] font-black text-white shadow"
                                    :class="pendingSource?.type === 'START' && pendingSource?.start_id === start.id ?
                                        'ring-4 ring-emerald-200' :
                                        ''">

                                    →

                                </button>


                                <button type="button"
                                    @click="deleteResource(start.delete_url, '¿Eliminar este Start?')"
                                    class="mt-3 text-[10px] font-black text-red-500">

                                    Eliminar

                                </button>

                            </div>

                        </article>

                    </template>


                    {{-- ================================================= --}}
                    {{-- PHASE NODES --}}
                    {{-- ================================================= --}}

                    <template x-for="node in nodes" :key="'node-' + node.id">

                        <article @click="selectedNodeId = node.id"
                            class="absolute w-[330px] select-none overflow-visible rounded-2xl border-2 bg-white shadow-xl transition"
                            :class="selectedNodeId === node.id ?
                                'border-amber-400 ring-4 ring-amber-100' :
                                'border-slate-200'"
                            :style="`left:${node.x}px;top:${node.y}px;`">


                            {{-- HEADER --}}

                            <div @pointerdown.prevent="startDrag($event, 'node', node)"
                                class="cursor-grab rounded-t-[14px] bg-slate-950 p-4 text-white active:cursor-grabbing">

                                <div class="flex items-start justify-between gap-3">

                                    <div class="min-w-0">

                                        <p class="font-mono text-[9px] font-black text-slate-400" x-text="node.code">
                                        </p>

                                        <h3 class="mt-1 truncate text-sm font-black" x-text="node.name">
                                        </h3>

                                        <p class="mt-1 text-[10px] font-bold text-amber-300"
                                            x-text="node.phase_type_label">
                                        </p>

                                    </div>


                                    <div class="rounded-xl bg-white/10 px-2.5 py-2 text-lg">
                                        ◇
                                    </div>

                                </div>

                            </div>


                            {{-- PORTS --}}

                            <div class="grid grid-cols-2 gap-3 p-4">

                                {{-- ENTRIES --}}

                                <div>

                                    <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-emerald-600">
                                        Entradas
                                    </p>


                                    <template x-for="(entry, entryIndex) in node.entries" :key="'entry-' + entry.id">

                                        <button type="button" @click.stop="chooseEntryTarget(node, entry)"
                                            class="relative mb-2 block w-full rounded-lg border border-emerald-100 bg-emerald-50 px-2 py-2 text-left">

                                            <span
                                                class="absolute -left-[18px] top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-emerald-500 text-[8px] font-black text-white shadow">

                                                →

                                            </span>

                                            <span class="block truncate text-[10px] font-black text-emerald-800"
                                                x-text="entry.name">
                                            </span>

                                            <span class="mt-0.5 block truncate text-[8px] text-emerald-600"
                                                x-text="entry.merge_label">
                                            </span>

                                        </button>

                                    </template>

                                </div>


                                {{-- EXITS --}}

                                <div>

                                    <p
                                        class="mb-2 text-right text-[9px] font-black uppercase tracking-wider text-violet-600">
                                        Salidas
                                    </p>


                                    <template x-for="(exit, exitIndex) in node.exits" :key="'exit-' + exit.id">

                                        <button type="button" @click.stop="chooseExitSource(node, exit)"
                                            class="relative mb-2 block w-full rounded-lg border border-violet-100 bg-violet-50 px-2 py-2 text-right"
                                            :class="pendingSource?.type === 'PHASE_EXIT' &&
                                                pendingSource?.node_id === node.id &&
                                                pendingSource?.phase_exit_id === exit.id ?
                                                'ring-2 ring-violet-300' :
                                                ''">

                                            <span class="block truncate text-[10px] font-black text-violet-800"
                                                x-text="exit.name">
                                            </span>

                                            <span class="mt-0.5 block truncate text-[8px] text-violet-600"
                                                x-text="exit.timing">
                                            </span>


                                            <span
                                                class="absolute -right-[18px] top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-violet-500 text-[8px] font-black text-white shadow">

                                                →

                                            </span>

                                        </button>

                                    </template>

                                </div>

                            </div>


                            <div class="border-t border-slate-100 px-4 py-3">

                                <p class="text-[9px] font-bold text-slate-400" x-text="node.contract">
                                </p>

                            </div>

                        </article>

                    </template>


                    {{-- ================================================= --}}
                    {{-- TERMINALS --}}
                    {{-- ================================================= --}}

                    <template x-for="terminal in terminals" :key="'terminal-' + terminal.id">

                        <article
                            class="absolute w-[260px] select-none rounded-2xl border-2 border-rose-300 bg-white shadow-lg shadow-rose-950/5"
                            :style="`left:${terminal.x}px;top:${terminal.y}px;`">


                            <div @pointerdown.prevent="startDrag($event, 'terminal', terminal)"
                                class="cursor-grab rounded-t-2xl bg-rose-600 p-4 text-white active:cursor-grabbing">

                                <div class="flex items-start gap-3">

                                    <span class="text-lg">
                                        ◎
                                    </span>

                                    <div>

                                        <p class="text-[9px] font-black uppercase tracking-wider text-rose-200">
                                            TERMINAL ·
                                            <span x-text="terminal.code"></span>
                                        </p>

                                        <p class="mt-1 font-black" x-text="terminal.name">
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="relative p-4">

                                <button type="button" @click.stop="chooseTerminalTarget(terminal)"
                                    class="absolute -left-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-[9px] font-black text-white shadow">

                                    →

                                </button>


                                <p class="text-xs font-bold text-slate-500" x-text="terminal.type">
                                </p>


                                <button type="button"
                                    @click="deleteResource(terminal.delete_url, '¿Eliminar este Terminal?')"
                                    class="mt-3 text-[10px] font-black text-red-500">

                                    Eliminar

                                </button>

                            </div>

                        </article>

                    </template>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- RIGHT INSPECTOR --}}
        {{-- ===================================================== --}}

        <aside class="space-y-4">


            {{-- VALIDATION --}}

            <section
                class="rounded-3xl border
                {{ $graphValidation['valid'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-amber-200 bg-amber-50/50' }}
                p-5">

                <div class="flex items-center justify-between gap-3">

                    <div>

                        <p
                            class="text-[10px] font-black uppercase tracking-wider
                            {{ $graphValidation['valid'] ? 'text-emerald-700' : 'text-amber-700' }}">

                            Graph Validator

                        </p>

                        <h3 class="mt-1 font-black text-slate-900">

                            {{ $graphValidation['valid'] ? 'Estructura válida' : 'Requiere atención' }}

                        </h3>

                    </div>


                    <div class="text-2xl">

                        {{ $graphValidation['valid'] ? '✓' : '!' }}

                    </div>

                </div>


                @if (!empty($graphValidation['errors']))

                    <div class="mt-4 space-y-2">

                        @foreach ($graphValidation['errors'] as $error)
                            <div class="rounded-xl border border-red-200 bg-white p-3">

                                <p class="text-[9px] font-black uppercase text-red-500">
                                    {{ $error['code'] }}
                                </p>

                                <p class="mt-1 text-xs leading-5 text-red-700">
                                    {{ $error['message'] }}
                                </p>

                            </div>
                        @endforeach

                    </div>

                @endif


                @if (!empty($graphValidation['warnings']))

                    <details class="mt-4">

                        <summary class="cursor-pointer text-xs font-black text-amber-700">
                            {{ count($graphValidation['warnings']) }}
                            advertencias
                        </summary>

                        <div class="mt-3 space-y-2">

                            @foreach ($graphValidation['warnings'] as $warning)
                                <div class="rounded-xl border border-amber-200 bg-white p-3">

                                    <p class="text-[9px] font-black uppercase text-amber-500">
                                        {{ $warning['code'] }}
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-amber-700">
                                        {{ $warning['message'] }}
                                    </p>

                                </div>
                            @endforeach

                        </div>

                    </details>

                @endif

            </section>


            {{-- SELECTED NODE --}}

            <section x-show="selectedNode()" style="display:none;"
                class="rounded-3xl border border-slate-200 bg-white p-5">

                <template x-if="selectedNode()">

                    <div>

                        <p class="text-[10px] font-black uppercase tracking-wider text-amber-600">
                            Node Inspector
                        </p>

                        <h3 class="mt-2 font-black text-slate-900" x-text="selectedNode().name">
                        </h3>

                        <p class="mt-1 text-xs text-slate-400" x-text="selectedNode().phase_template_name">
                        </p>


                        <div class="mt-4 flex gap-2">

                            <button type="button" @click="postAction(selectedNode().duplicate_url)"
                                class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-[10px] font-black text-slate-600">

                                Duplicar

                            </button>

                            <button type="button"
                                @click="deleteResource(
                                    selectedNode().delete_url,
                                    'Eliminar este Node y todas sus conexiones?'
                                )"
                                class="flex-1 rounded-xl bg-red-50 px-3 py-2 text-[10px] font-black text-red-600">

                                Eliminar

                            </button>

                        </div>


                        <div class="mt-5 border-t border-slate-100 pt-5">

                            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">
                                Entradas
                            </p>


                            <template x-for="entry in selectedNode().entries" :key="'inspector-entry-' + entry.id">

                                <div class="mt-2 rounded-xl border border-slate-200 p-3">

                                    <div class="flex items-start justify-between gap-2">

                                        <div>

                                            <p class="text-xs font-black text-slate-800" x-text="entry.name">
                                            </p>

                                            <p class="mt-1 text-[9px] text-slate-400"
                                                x-text="entry.merge_label + ' · ' + entry.contract">
                                            </p>

                                        </div>

                                        <button type="button" x-show="selectedNode().entries.length > 1"
                                            @click="deleteResource(
                                                entry.delete_url,
                                                '¿Eliminar esta entrada?'
                                            )"
                                            class="text-[10px] font-black text-red-500">

                                            ×

                                        </button>

                                    </div>

                                </div>

                            </template>


                            {{-- ADD ENTRY --}}

                            <div class="mt-4 rounded-2xl bg-emerald-50 p-4">

                                <p class="text-[10px] font-black text-emerald-800">
                                    Nueva entrada
                                </p>

                                <input type="text" x-model="entryDraft.name" placeholder="Ej. Repechaje"
                                    class="mt-3 w-full rounded-xl border-emerald-200 bg-white text-xs">


                                <select x-model="entryDraft.merge_policy"
                                    class="mt-2 w-full rounded-xl border-emerald-200 bg-white text-xs">

                                    <option value="APPEND">
                                        Combinar
                                    </option>

                                    <option value="WAIT_ALL">
                                        Esperar todas
                                    </option>

                                    <option value="FIRST_AVAILABLE">
                                        Primera disponible
                                    </option>

                                    <option value="PRIORITY">
                                        Prioridad
                                    </option>

                                </select>


                                <label class="mt-3 flex items-center gap-2 text-[10px] font-bold text-emerald-800">

                                    <input type="checkbox" x-model="entryDraft.required" class="rounded">

                                    Requerida

                                </label>


                                <label class="mt-2 flex items-center gap-2 text-[10px] font-bold text-emerald-800">

                                    <input type="checkbox" x-model="entryDraft.multiple" class="rounded">

                                    Permitir varias conexiones

                                </label>


                                <button type="button" @click="createEntryPort()"
                                    class="mt-3 w-full rounded-xl bg-emerald-600 px-3 py-2.5 text-[10px] font-black text-white">

                                    + Agregar entrada

                                </button>

                            </div>

                        </div>

                    </div>

                </template>

            </section>


            {{-- CONNECTIONS --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-violet-600">
                    Connections
                </p>

                <h3 class="mt-2 font-black text-slate-900">
                    Rutas creadas
                </h3>


                <div class="mt-4 max-h-[520px] space-y-3 overflow-auto">

                    <template x-for="connection in connections" :key="'connection-card-' + connection.id">

                        <article class="rounded-2xl border border-slate-200 p-4">

                            <p class="truncate text-[10px] font-black text-slate-700"
                                x-text="connection.source_label">
                            </p>

                            <p class="my-1 text-center text-violet-500">
                                ↓
                            </p>

                            <p class="truncate text-[10px] font-black text-slate-700"
                                x-text="connection.target_label">
                            </p>


                            <div class="mt-3 grid grid-cols-[1fr_90px] gap-2">

                                <select x-model="connection.allocation_mode"
                                    class="rounded-lg border-slate-200 text-[10px]">

                                    <option value="ALL">
                                        Todo
                                    </option>

                                    <option value="TAKE_N">
                                        Tomar N
                                    </option>

                                    <option value="PERCENTAGE">
                                        %
                                    </option>

                                    <option value="REMAINDER">
                                        Restante
                                    </option>

                                </select>


                                <input type="number" x-model="connection.allocation_value"
                                    x-show="[
                                        'TAKE_N',
                                        'PERCENTAGE'
                                    ].includes(
                                        connection.allocation_mode
                                    )"
                                    class="rounded-lg border-slate-200 text-[10px]" placeholder="Valor">

                            </div>


                            <div class="mt-3 flex gap-2">

                                <button type="button" @click="saveConnection(connection)"
                                    class="flex-1 rounded-lg bg-violet-600 px-2 py-2 text-[9px] font-black text-white">

                                    Guardar

                                </button>

                                <button type="button"
                                    @click="deleteResource(
                                        connection.delete_url,
                                        '¿Eliminar esta conexión?'
                                    )"
                                    class="rounded-lg bg-red-50 px-3 py-2 text-[9px] font-black text-red-600">

                                    ×

                                </button>

                            </div>

                        </article>

                    </template>


                    <div x-show="connections.length === 0"
                        class="rounded-xl border border-dashed border-violet-200 bg-violet-50 p-4 text-center">

                        <p class="text-xs font-black text-violet-800">
                            No hay conexiones
                        </p>

                        <p class="mt-1 text-[10px] leading-5 text-violet-600">
                            Haz clic en una salida y después en una entrada.
                        </p>

                    </div>

                </div>

            </section>

        </aside>


        {{-- ===================================================== --}}
        {{-- CONNECTION MODAL --}}
        {{-- ===================================================== --}}

        <div x-show="connectionModal" x-transition.opacity style="display:none;"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">


            <div @click.outside="connectionModal = false" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">

                <p class="text-[10px] font-black uppercase tracking-wider text-violet-600">
                    Nueva Connection
                </p>

                <h3 class="mt-2 text-xl font-black text-slate-900">
                    Configurar flujo
                </h3>


                <div class="mt-5 rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs font-black text-slate-700" x-text="pendingSource?.label">
                    </p>

                    <p class="my-2 text-center text-violet-500">
                        ↓
                    </p>

                    <p class="text-xs font-black text-slate-700" x-text="pendingTarget?.label">
                    </p>

                </div>


                <div class="mt-5">

                    <label class="text-[10px] font-black uppercase text-slate-500">
                        Distribución
                    </label>

                    <select x-model="connectionDraft.allocation_mode"
                        class="mt-2 w-full rounded-xl border-slate-300 text-sm">

                        <option value="ALL">
                            Todo el flujo
                        </option>

                        <option value="TAKE_N">
                            Tomar N participantes
                        </option>

                        <option value="PERCENTAGE">
                            Porcentaje
                        </option>

                        <option value="REMAINDER">
                            Participantes restantes
                        </option>

                    </select>

                </div>


                <div x-show="[
                    'TAKE_N',
                    'PERCENTAGE'
                ].includes(
                    connectionDraft.allocation_mode
                )"
                    class="mt-4">

                    <label class="text-[10px] font-black uppercase text-slate-500">
                        Valor
                    </label>

                    <input type="number" x-model="connectionDraft.allocation_value" min="0.01" step="0.01"
                        class="mt-2 w-full rounded-xl border-slate-300">

                </div>


                <div class="mt-4">

                    <label class="text-[10px] font-black uppercase text-slate-500">
                        Etiqueta opcional
                    </label>

                    <input type="text" x-model="connectionDraft.label" placeholder="Ej. Ruta de ganadores"
                        class="mt-2 w-full rounded-xl border-slate-300">

                </div>


                <div class="mt-6 flex gap-3">

                    <button type="button" @click="cancelConnection()"
                        class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-xs font-black text-slate-600">

                        Cancelar

                    </button>

                    <button type="button" @click="createConnection()" :disabled="saving"
                        class="flex-1 rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white disabled:opacity-50">

                        <span x-show="!saving">
                            Crear conexión
                        </span>

                        <span x-show="saving">
                            Guardando...
                        </span>

                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ARCHITECTURAL NOTE --}}
    {{-- ========================================================= --}}

    <section class="mt-8 rounded-3xl bg-slate-950 p-6 text-white">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
            Regla arquitectónica
        </p>

        <h3 class="mt-2 text-xl font-black">
            Las Fases no conocen su destino
        </h3>

        <p class="mt-3 max-w-4xl text-sm leading-7 text-slate-300">
            PhaseExit solo declara quién abandona una Fase.
            TournamentPhaseConnection decide adónde se dirige ese flujo.
            PhaseEntryPort describe cómo una Fase contextual recibe participantes.
            Así un mismo PhaseTemplate puede reutilizarse en muchos torneos y
            ocupar posiciones distintas dentro del mismo torneo.
        </p>

    </section>


    {{-- ========================================================= --}}
    {{-- JAVASCRIPT / ALPINE --}}
    {{-- ========================================================= --}}

    <script>
        function tournamentGraphBuilder(payload) {
            return {
                nodes: payload.nodes ?? [],
                starts: payload.starts ?? [],
                terminals: payload.terminals ?? [],
                connections: payload.connections ?? [],

                connectionStoreUrl: payload.connection_store_url,

                zoom: 1,

                selectedNodeId: null,

                pendingSource: null,
                pendingTarget: null,

                connectionModal: false,

                saving: false,

                drag: null,

                connectionDraft: {
                    allocation_mode: 'ALL',
                    allocation_value: null,
                    label: '',
                    priority: 10,
                },

                entryDraft: {
                    name: '',
                    merge_policy: 'APPEND',
                    required: true,
                    multiple: true,
                },


                /*
                |--------------------------------------------------------------------------
                | CSRF
                |--------------------------------------------------------------------------
                */

                csrf() {
                    return document
                        .querySelector(
                            'meta[name="csrf-token"]'
                        )
                        ?.getAttribute(
                            'content'
                        ) ?? '';
                },


                /*
                |--------------------------------------------------------------------------
                | Selected Node
                |--------------------------------------------------------------------------
                */

                selectedNode() {
                    return this.nodes.find(
                        node =>
                        node.id ===
                        this.selectedNodeId
                    ) ?? null;
                },


                /*
                |--------------------------------------------------------------------------
                | Drag
                |--------------------------------------------------------------------------
                */

                startDrag(
                    event,
                    kind,
                    item
                ) {
                    this.drag = {
                        kind,
                        item,

                        pointerX: event.clientX,

                        pointerY: event.clientY,

                        startX: item.x,

                        startY: item.y,
                    };
                },


                moveDrag(event) {
                    if (!this.drag) {
                        return;
                    }

                    const dx =
                        (
                            event.clientX -
                            this.drag.pointerX
                        ) /
                        this.zoom;

                    const dy =
                        (
                            event.clientY -
                            this.drag.pointerY
                        ) /
                        this.zoom;


                    this.drag.item.x =
                        Math.max(
                            0,
                            Math.round(
                                this.drag.startX +
                                dx
                            )
                        );

                    this.drag.item.y =
                        Math.max(
                            0,
                            Math.round(
                                this.drag.startY +
                                dy
                            )
                        );
                },


                async endDrag() {
                    if (!this.drag) {
                        return;
                    }

                    const item =
                        this.drag.item;

                    this.drag =
                        null;


                    try {
                        await this.request(
                            item.position_url,
                            'PATCH', {
                                x_position: item.x,

                                y_position: item.y,
                            }
                        );
                    } catch (error) {
                        console.error(error);
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | Connection Source
                |--------------------------------------------------------------------------
                */

                chooseStartSource(start) {
                    this.pendingSource = {
                        type: 'START',

                        start_id: start.id,

                        node_id: null,

                        phase_exit_id: null,

                        label: start.name,
                    };
                },


                chooseExitSource(
                    node,
                    exit
                ) {
                    this.pendingSource = {
                        type: 'PHASE_EXIT',

                        start_id: null,

                        node_id: node.id,

                        phase_exit_id: exit.id,

                        label: node.name +
                            ' · ' +
                            exit.name,
                    };
                },


                /*
                |--------------------------------------------------------------------------
                | Connection Target
                |--------------------------------------------------------------------------
                */

                chooseEntryTarget(
                    node,
                    entry
                ) {
                    if (!this.pendingSource) {
                        alert(
                            'Primero selecciona un Start o una PhaseExit.'
                        );

                        return;
                    }


                    if (
                        this.pendingSource.type ===
                        'PHASE_EXIT' &&
                        this.pendingSource.node_id ===
                        node.id
                    ) {
                        alert(
                            'Un Node no puede conectarse consigo mismo.'
                        );

                        return;
                    }


                    this.pendingTarget = {
                        type: 'ENTRY_PORT',

                        entry_port_id: entry.id,

                        terminal_id: null,

                        label: node.name +
                            ' · ' +
                            entry.name,
                    };


                    this.openConnectionModal();
                },


                chooseTerminalTarget(
                    terminal
                ) {
                    if (!this.pendingSource) {
                        alert(
                            'Primero selecciona un Start o una PhaseExit.'
                        );

                        return;
                    }


                    this.pendingTarget = {
                        type: 'TERMINAL',

                        entry_port_id: null,

                        terminal_id: terminal.id,

                        label: terminal.name,
                    };


                    this.openConnectionModal();
                },


                openConnectionModal() {
                    this.connectionDraft = {
                        allocation_mode: 'ALL',

                        allocation_value: null,

                        label: '',

                        priority: 10,
                    };

                    this.connectionModal =
                        true;
                },


                cancelConnection() {
                    this.connectionModal =
                        false;

                    this.pendingTarget =
                        null;

                    this.pendingSource =
                        null;
                },


                async createConnection() {
                    if (
                        !this.pendingSource ||
                        !this.pendingTarget
                    ) {
                        return;
                    }


                    this.saving =
                        true;


                    try {
                        const data = {
                            source_type: this.pendingSource.type,

                            source_start_id: this.pendingSource.start_id,

                            source_node_id: this.pendingSource.node_id,

                            source_phase_exit_id: this.pendingSource.phase_exit_id,

                            target_type: this.pendingTarget.type,

                            target_entry_port_id: this.pendingTarget.entry_port_id,

                            target_terminal_id: this.pendingTarget.terminal_id,

                            allocation_mode: this.connectionDraft.allocation_mode,

                            allocation_value: this.connectionDraft.allocation_value,

                            label: this.connectionDraft.label,

                            priority: this.connectionDraft.priority,

                            status: 'ACTIVE',
                        };


                        await this.request(
                            this.connectionStoreUrl,
                            'POST',
                            data
                        );


                        window.location.reload();
                    } catch (error) {
                        this.showError(
                            error
                        );
                    } finally {
                        this.saving =
                            false;
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | Connection Update
                |--------------------------------------------------------------------------
                */

                async saveConnection(
                    connection
                ) {
                    try {
                        await this.request(
                            connection.update_url,
                            'PUT', {
                                label: connection.label,

                                allocation_mode: connection.allocation_mode,

                                allocation_value: connection.allocation_value,

                                priority: connection.priority ??
                                    10,

                                status: connection.status ??
                                    'ACTIVE',
                            }
                        );


                        window.location.reload();
                    } catch (error) {
                        this.showError(
                            error
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | Entry Ports
                |--------------------------------------------------------------------------
                */

                async createEntryPort() {
                    const node =
                        this.selectedNode();

                    if (!node) {
                        return;
                    }


                    if (
                        !this.entryDraft
                        .name
                        .trim()
                    ) {
                        alert(
                            'Escribe un nombre para la entrada.'
                        );

                        return;
                    }


                    try {
                        await this.request(
                            node.entry_store_url,
                            'POST', {
                                name: this.entryDraft.name,

                                description: null,

                                merge_policy: this.entryDraft.merge_policy,

                                is_required: this.entryDraft.required,

                                accepts_multiple_connections: this.entryDraft.multiple,

                                min_participants: null,

                                max_participants: null,

                                exact_participants: null,

                                status: 'ACTIVE',
                            }
                        );


                        window.location.reload();
                    } catch (error) {
                        this.showError(
                            error
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | Generic actions
                |--------------------------------------------------------------------------
                */

                async deleteResource(
                    url,
                    message
                ) {
                    if (
                        !confirm(
                            message
                        )
                    ) {
                        return;
                    }


                    try {
                        await this.request(
                            url,
                            'DELETE', {}
                        );


                        window.location.reload();
                    } catch (error) {
                        this.showError(
                            error
                        );
                    }
                },


                async postAction(
                    url
                ) {
                    try {
                        await this.request(
                            url,
                            'POST', {}
                        );


                        window.location.reload();
                    } catch (error) {
                        this.showError(
                            error
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | SVG Geometry
                |--------------------------------------------------------------------------
                */

                connectionPath(
                    connection
                ) {
                    const source =
                        this.sourcePoint(
                            connection
                        );

                    const target =
                        this.targetPoint(
                            connection
                        );


                    if (
                        !source ||
                        !target
                    ) {
                        return '';
                    }


                    const distance =
                        Math.max(
                            80,
                            Math.abs(
                                target.x -
                                source.x
                            ) *
                            0.45
                        );


                    return [
                        'M',
                        source.x,
                        source.y,

                        'C',
                        source.x + distance,
                        source.y,

                        target.x - distance,
                        target.y,

                        target.x,
                        target.y,
                    ].join(' ');
                },


                sourcePoint(
                    connection
                ) {
                    if (
                        connection.source_type ===
                        'START'
                    ) {
                        const start =
                            this.starts.find(
                                item =>
                                item.id ===
                                connection.source_start_id
                            );

                        if (!start) {
                            return null;
                        }

                        return {
                            x: start.x +
                                260,

                            y: start.y +
                                78,
                        };
                    }


                    const node =
                        this.nodes.find(
                            item =>
                            item.id ===
                            connection.source_node_id
                        );

                    if (!node) {
                        return null;
                    }


                    const index =
                        Math.max(
                            0,
                            node.exits.findIndex(
                                exit =>
                                exit.id ===
                                connection.source_phase_exit_id
                            )
                        );


                    return {
                        x: node.x +
                            330,

                        y: node.y +
                            118 +
                            (index * 42),
                    };
                },


                targetPoint(
                    connection
                ) {
                    if (
                        connection.target_type ===
                        'TERMINAL'
                    ) {
                        const terminal =
                            this.terminals.find(
                                item =>
                                item.id ===
                                connection.target_terminal_id
                            );

                        if (!terminal) {
                            return null;
                        }

                        return {
                            x: terminal.x,

                            y: terminal.y +
                                78,
                        };
                    }


                    for (
                        const node of
                            this.nodes
                    ) {
                        const index =
                            node.entries.findIndex(
                                entry =>
                                entry.id ===
                                connection.target_entry_port_id
                            );


                        if (
                            index !==
                            -1
                        ) {
                            return {
                                x: node.x,

                                y: node.y +
                                    118 +
                                    (index * 42),
                            };
                        }
                    }


                    return null;
                },


                /*
                |--------------------------------------------------------------------------
                | HTTP
                |--------------------------------------------------------------------------
                */

                async request(
                    url,
                    method,
                    body
                ) {
                    const response =
                        await fetch(
                            url, {
                                method,

                                headers: {
                                    'Content-Type': 'application/json',

                                    'Accept': 'application/json',

                                    'X-CSRF-TOKEN': this.csrf(),

                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                body: method === 'GET' ?
                                    undefined :
                                    JSON.stringify(
                                        body
                                    ),
                            }
                        );


                    const data =
                        await response
                        .json()
                        .catch(
                            () => ({})
                        );


                    if (
                        !response.ok
                    ) {
                        const error =
                            new Error(
                                data.message ??
                                'No se pudo completar la operación.'
                            );

                        error.payload =
                            data;

                        throw error;
                    }


                    return data;
                },


                showError(error) {
                    const payload =
                        error.payload ??
                        {};


                    if (
                        payload.errors
                    ) {
                        const messages =
                            Object
                            .values(
                                payload.errors
                            )
                            .flat()
                            .join(
                                '\n'
                            );

                        alert(
                            messages
                        );

                        return;
                    }


                    alert(
                        error.message ??
                        'Ocurrió un error.'
                    );
                },
            };
        }
    </script>

</x-tournament-layout>

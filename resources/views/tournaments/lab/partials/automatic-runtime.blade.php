<section x-show="
        labMode === 'AUTOMATIC'
        ||
        graphRuntime()
    " class="space-y-5">

    <section
        class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-slate-900 to-violet-950 p-6 text-white shadow-xl">

        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-violet-500/20 blur-3xl">
        </div>

        <div class="relative">

            <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-start">

                <div>

                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">
                        Simulación automática
                    </p>

                    <h2 class="mt-2 text-2xl font-black">
                        Recorrido completo del torneo
                    </h2>

                    <p class="mt-2 max-w-2xl text-xs leading-6 text-slate-300">
                        El Runtime moverá automáticamente a los competidores
                        desde los Starts hasta los terminales.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">

                    <span class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black">

                        Estado:
                        <span
                            x-text="statusLabel(
                                graphRuntime()?.status ?? state?.status
                            )">
                        </span>
                    </span>

                    <button type="button" x-show="!graphRuntime() && state?.status === 'READY'" @click="leaveMode()"
                        class="rounded-xl border border-white/20 px-4 py-3 text-xs font-black text-white">

                        ← Cambiar modo
                    </button>
                </div>
            </div>

            <div class="mt-6">

                <div class="flex items-center justify-between text-[10px] font-bold text-slate-300">

                    <span>
                        Progreso estimado
                    </span>

                    <span x-text="`${runtimeProgress()}%`">
                    </span>
                </div>

                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10">

                    <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-emerald-400 transition-all duration-500"
                        :style="`width: ${runtimeProgress()}%`">
                    </div>
                </div>
            </div>

            <div x-show="graphRuntime()?.status === 'RUNNING'"
                class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">

                <p class="text-[8px] font-black uppercase text-slate-400">
                    Próxima operación
                </p>

                <p class="mt-1 text-xs font-black text-white" x-text="nextOperationLabel()">
                </p>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">

                <button type="button" x-show="!graphRuntime() && state?.status === 'READY'" @click="startTournament()"
                    :disabled="loading"
                    class="rounded-xl bg-emerald-600 px-5 py-3 text-xs font-black text-white disabled:opacity-40">

                    ▶ Iniciar recorrido
                </button>

                <button type="button" x-show="graphRuntime()?.status === 'RUNNING'" @click="stepRuntime()"
                    :disabled="loading"
                    class="rounded-xl bg-sky-600 px-5 py-3 text-xs font-black text-white disabled:opacity-40">

                    Avanzar un paso
                </button>

                <button type="button" x-show="graphRuntime()?.status === 'RUNNING'" @click="runTournament()"
                    :disabled="loading"
                    class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white disabled:opacity-40">

                    ⚡ Simular hasta terminar
                </button>

                <button type="button" x-show="graphRuntime()" @click="resetLab()" :disabled="loading"
                    class="rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-xs font-black text-white">

                    Reiniciar
                </button>
            </div>
        </div>
    </section>

    <div x-show="loading" class="rounded-2xl border border-violet-200 bg-violet-50 p-4">

        <div class="flex items-center gap-3">

            <div class="h-5 w-5 animate-spin rounded-full border-2 border-violet-200 border-t-violet-600">
            </div>

            <p class="text-xs font-black text-violet-800">
                Procesando la simulación…
            </p>
        </div>
    </div>

    <section x-show="graphRuntime()" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">

        <div class="space-y-5">

            <section class="rounded-3xl border border-slate-200 bg-white p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-[9px] font-black uppercase tracking-[0.14em] text-violet-600">
                            Ruta del torneo
                        </p>

                        <h3 class="mt-1 font-black text-slate-950">
                            Estados del Tournament Graph
                        </h3>
                    </div>

                    <span class="text-[9px] font-bold text-slate-400">
                        Selecciona una fase para inspeccionarla
                    </span>
                </div>

                <div class="mt-5 space-y-5">

                    <div>

                        <p class="text-[9px] font-black uppercase text-emerald-600">
                            1 · Starts
                        </p>

                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">

                            <template x-for="start in starts()" :key="start.id">

                                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3">

                                    <div class="flex items-start justify-between gap-2">

                                        <div>

                                            <p class="text-[8px] font-black text-emerald-600" x-text="start.code">
                                            </p>

                                            <p class="mt-1 text-xs font-black text-slate-900" x-text="start.name">
                                            </p>
                                        </div>

                                        <span class="rounded-full px-2 py-1 text-[8px] font-black"
                                            :class="statusClass(start.status)" x-text="statusLabel(start.status)">
                                        </span>
                                    </div>

                                    <p class="mt-2 text-[9px] text-slate-500">
                                        <span x-text="start.participant_count">
                                        </span>
                                        participantes
                                    </p>
                                </article>
                            </template>
                        </div>
                    </div>

                    <div>

                        <p class="text-[9px] font-black uppercase text-violet-600">
                            2 · Fases
                        </p>

                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">

                            <template x-for="node in nodes()" :key="node.id">

                                <div class="overflow-hidden rounded-2xl border transition"
                                    :class="String(selectedNodeId) === String(node.id)
                                        ? nodeAccent(node).ring + ' bg-white ring-2 ring-offset-1'
                                        : 'border-slate-200 bg-white hover:' + nodeAccent(node).ring">

                                    {{-- Franja de color por motor: cada fase se
                                         distingue de un vistazo --}}
                                    <div class="h-1 w-full bg-gradient-to-r"
                                        :class="nodeAccent(node).bar"></div>

                                    <button type="button" @click="selectNode(node.id)"
                                        class="w-full p-3 text-left">

                                        <div class="flex items-start justify-between gap-2">

                                            <div class="flex min-w-0 items-start gap-2">

                                                <span class="text-base leading-none"
                                                    x-text="nodeAccent(node).icon"></span>

                                                <div class="min-w-0">
                                                    <p class="font-mono text-[8px] font-black text-slate-400"
                                                        x-text="node.code"></p>

                                                    <p class="mt-0.5 truncate text-xs font-black text-slate-900"
                                                        x-text="node.name"></p>
                                                </div>

                                            </div>

                                            <span class="shrink-0 rounded-full px-2 py-1 text-[8px] font-black"
                                                :class="statusClass(node.status)"
                                                x-text="statusLabel(node.status)"></span>
                                        </div>

                                        <span class="mt-2 inline-block rounded-full px-2 py-0.5 text-[8px] font-black"
                                            :class="nodeAccent(node).chip"
                                            x-text="node.phase_type_label"></span>


                                        {{-- QUIEN ESTA DENTRO --}}

                                        <div class="mt-2.5">

                                            <template x-if="nodeParticipants(node).length">
                                                <div class="flex flex-wrap items-center gap-1">

                                                    <template x-for="participant in nodeParticipants(node).slice(0, 8)"
                                                        :key="participant.lab_id ?? participant.preview_id">

                                                        <span class="h-6 w-6 overflow-hidden rounded-md bg-slate-100 ring-1 ring-slate-200"
                                                            :title="participant.borrowed_name || participant.name">

                                                            <template x-if="participantImageOf(participant)">
                                                                <img :src="participantImageOf(participant)" alt=""
                                                                    class="h-full w-full object-cover">
                                                            </template>

                                                            <template x-if="!participantImageOf(participant)">
                                                                <span class="flex h-full w-full items-center justify-center text-[7px] font-black text-slate-400"
                                                                    x-text="participantInitialsOf(participant)"></span>
                                                            </template>
                                                        </span>
                                                    </template>

                                                    <span x-show="nodeParticipants(node).length > 8"
                                                        class="text-[9px] font-black text-slate-400"
                                                        x-text="'+' + (nodeParticipants(node).length - 8)"></span>

                                                </div>
                                            </template>

                                            <template x-if="!nodeParticipants(node).length">
                                                <p class="text-[9px] italic text-slate-400">
                                                    Todavía sin participantes
                                                </p>
                                            </template>

                                        </div>


                                        <p class="mt-2 text-[9px] font-bold text-slate-500">
                                            <span x-text="node.participant_ids?.length ?? 0"></span>
                                            participantes
                                        </p>

                                    </button>


                                    {{--
                                        Resolver SOLO esta fase. Es el punto
                                        intermedio que faltaba entre "un paso"
                                        y "todo el torneo".
                                    --}}
                                    <div x-show="nodeIsRunnable(node)" x-cloak
                                        class="border-t border-slate-100 p-2">

                                        <button type="button" @click.stop="runNode(node)"
                                            :disabled="loading"
                                            class="w-full rounded-xl bg-gradient-to-r px-3 py-2 text-[10px] font-black text-white transition hover:opacity-90 disabled:opacity-40"
                                            :class="nodeAccent(node).bar">
                                            <span x-show="!loading">⚡ Simular solo esta fase</span>
                                            <span x-show="loading" x-cloak>Simulando…</span>
                                        </button>

                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    <div>

                        <p class="text-[9px] font-black uppercase text-rose-600">
                            3 · Terminales
                        </p>

                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">

                            <template x-for="terminal in terminals()" :key="terminal.id">

                                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-3">

                                    <div class="flex items-start justify-between gap-2">

                                        <div>

                                            <p class="text-[8px] font-black text-rose-600" x-text="terminal.code">
                                            </p>

                                            <p class="mt-1 text-xs font-black text-slate-900" x-text="terminal.name">
                                            </p>
                                        </div>

                                        <span class="rounded-full px-2 py-1 text-[8px] font-black"
                                            :class="statusClass(terminal.status)"
                                            x-text="statusLabel(terminal.status)">
                                        </span>
                                    </div>

                                    <p class="mt-2 text-[9px] text-slate-500">

                                        <span x-text="terminal.participant_ids.length">
                                        </span>
                                        participantes
                                    </p>
                                </article>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            @include('tournaments.lab.partials.participants-inspector')
        </div>

        <aside class="space-y-5">

            <section class="rounded-3xl border border-slate-200 bg-white p-5">

                <p class="text-[9px] font-black uppercase tracking-[0.14em] text-violet-600">
                    Fase seleccionada
                </p>

                <template x-if="selectedNode()">

                    <div>

                        <h3 class="mt-2 text-lg font-black text-slate-950" x-text="selectedNode().name">
                        </h3>

                        <p class="mt-1 text-[10px] text-slate-500" x-text="selectedNode().phase_type_label">
                        </p>

                        <div class="mt-4 grid grid-cols-2 gap-2">

                            <div class="rounded-xl bg-slate-50 p-3">

                                <p class="text-[8px] font-black uppercase text-slate-400">
                                    Estado
                                </p>

                                <p class="mt-1 text-xs font-black" x-text="statusLabel(selectedNode().status)">
                                </p>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-3">

                                <p class="text-[8px] font-black uppercase text-slate-400">
                                    Partidos
                                </p>

                                <p class="mt-1 text-xs font-black">

                                    <span x-text="selectedNode().runtime?.matches_completed ?? 0">
                                    </span>
                                    /
                                    <span x-text="selectedNode().runtime?.matches_total ?? 0">
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div x-show="pendingRound()" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">

                            <p class="text-[8px] font-black uppercase text-amber-600">
                                Ronda activa
                            </p>

                            <p class="mt-1 text-xs font-black text-amber-950" x-text="pendingRound()?.label">
                            </p>
                        </div>
                    </div>
                </template>

                <div x-show="!selectedNode()" class="py-8 text-center text-xs text-slate-500">

                    Selecciona una fase del recorrido.
                </div>
            </section>

            <section x-show="runtimeDiagnostics().length" class="rounded-3xl border border-red-200 bg-red-50 p-5">

                <p class="text-[9px] font-black uppercase tracking-[0.14em] text-red-700">
                    Problemas detectados
                </p>

                <div class="mt-3 space-y-2">

                    <template x-for="(diagnostic, index) in runtimeDiagnostics()"
                        :key="`${diagnostic.code}-${index}`">

                        <article class="rounded-xl border border-red-200 bg-white p-3">

                            <p class="text-[8px] font-black text-red-600" x-text="diagnostic.code">
                            </p>

                            <p class="mt-1 text-[10px] leading-5 text-red-900" x-text="diagnostic.message">
                            </p>
                        </article>
                    </template>
                </div>
            </section>
        </aside>
    </section>
</section>

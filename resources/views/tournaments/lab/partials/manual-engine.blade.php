<section x-show="
        labMode === 'MANUAL'
        &&
        !graphRuntime()
    " class="space-y-5">

    <section class="rounded-[30px] border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-6">

        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">

            <div>

                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-sky-600">
                    Prueba manual de fase
                </p>

                <h2 class="mt-2 text-xl font-black text-slate-950">
                    Ejecuta un motor de forma aislada
                </h2>

                <p class="mt-2 max-w-2xl text-xs leading-6 text-slate-500">
                    Esta herramienta no recorre las conexiones. Selecciona
                    manualmente una fase y sus competidores.
                </p>
            </div>

            <button type="button" x-show="state?.status === 'READY'" @click="leaveMode()"
                class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-black text-slate-600">

                ← Cambiar modo
            </button>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">

            <button type="button" x-show="state?.status === 'READY'" @click="startManualMode()" :disabled="loading"
                class="rounded-xl bg-emerald-600 px-5 py-3 text-xs font-black text-white">

                ▶ Iniciar prueba manual
            </button>

            <button type="button" x-show="state?.status === 'RUNNING'" @click="execute('PAUSE')" :disabled="loading"
                class="rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-white">

                Pausar
            </button>

            <button type="button" x-show="state?.status === 'PAUSED'" @click="execute('RESUME')" :disabled="loading"
                class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white">

                Reanudar
            </button>

            <button type="button" @click="resetLab()" :disabled="loading"
                class="rounded-xl border border-red-200 bg-white px-5 py-3 text-xs font-black text-red-600">

                Reiniciar
            </button>
        </div>

        <div x-show="state?.status !== 'READY'" class="mt-5 grid gap-2 md:grid-cols-3">
            <div class="rounded-2xl border border-white bg-white/80 p-3"
                :class="selectedNodeId ? 'text-emerald-800' : 'text-sky-800'">
                <p class="text-[9px] font-black uppercase">1. Seleccionar fase</p>
                <p class="mt-1 text-[10px] opacity-70" x-text="selectedNodeId ? 'Fase seleccionada' : 'Elige qué fase probarás'">
                </p>
            </div>
            <div class="rounded-2xl border border-white bg-white/80 p-3"
                :class="selectedNode()?.runtime ? 'text-emerald-800' : 'text-slate-500'">
                <p class="text-[9px] font-black uppercase">2. Preparar participantes</p>
                <p class="mt-1 text-[10px] opacity-70"
                    x-text="selectedNode()?.runtime ? 'Participantes preparados' : 'Selecciona y confirma el grupo'">
                </p>
            </div>
            <div class="rounded-2xl border border-white bg-white/80 p-3"
                :class="selectedNode()?.runtime ? 'text-violet-800' : 'text-slate-400'">
                <p class="text-[9px] font-black uppercase">3. Ejecutar encuentros</p>
                <p class="mt-1 text-[10px] opacity-70">Registra o simula resultados</p>
            </div>
        </div>
    </section>

    <section x-show="state?.status !== 'READY'" class="rounded-3xl border border-slate-200 bg-white p-5">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">

            <div class="flex-1">

                <p class="mb-2 text-[9px] font-black uppercase tracking-[0.16em] text-sky-600">
                    Paso 1 · Selección
                </p>

                <label class="text-[9px] font-black uppercase text-slate-500">
                    Fase que deseas probar
                </label>

                <select x-model="selectedNodeId"
                    @change="
                        selectedForEngine = [];
                        selectNode(selectedNodeId);
                    "
                    class="mt-2 w-full rounded-xl border-slate-200 text-sm">

                    <option value="">
                        Selecciona una fase
                    </option>

                    <template x-for="node in engineNodes()" :key="node.id">

                        <option :value="node.id"
                            x-text="`${node.code} · ${node.name} · ${node.phase_type_label}`">
                        </option>
                    </template>
                </select>
            </div>

            <button type="button" @click="selectAllAvailableParticipants()"
                class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-black text-sky-700">

                Seleccionar todos
            </button>

            <button type="button" @click="clearParticipantSelection()"
                class="rounded-xl border border-slate-200 px-4 py-3 text-xs font-black text-slate-500">

                Limpiar
            </button>
        </div>

        <div x-show="
                selectedNode()
                &&
                !selectedNode().runtime
            "
            class="mt-5">

            <div class="mb-3 flex items-center justify-between gap-3">
                <p class="text-[9px] font-black uppercase tracking-[0.16em] text-sky-600">
                    Paso 2 · Participantes de prueba
                </p>
                <p class="text-[9px] font-bold text-slate-400">Mínimo 2</p>
            </div>

            <div class="flex max-h-[220px] flex-wrap gap-2 overflow-y-auto">

                <template x-for="participant in participants()" :key="participant.lab_id">

                    <button type="button"
                        @click="toggleEngineParticipant(
                            participant.lab_id
                        )"
                        class="rounded-xl border px-3 py-2 text-[10px] font-black"
                        :class="selectedForEngine.includes(
                                participant.lab_id
                            ) ?
                            'border-sky-500 bg-sky-600 text-white' :
                            'border-slate-200 bg-slate-50 text-slate-600'"
                        x-text="participant.name">
                    </button>
                </template>
            </div>

            <div class="mt-4 flex items-center justify-between">

                <p class="text-xs font-bold text-slate-500">

                    <span x-text="selectedForEngine.length">
                    </span>
                    seleccionados
                </p>

                <button type="button" @click="prepareSelectedNode()"
                    :disabled="loading
                        ||
                        selectedForEngine.length < 2"
                    class="rounded-xl bg-sky-600 px-5 py-3 text-xs font-black text-white disabled:opacity-40">

                    Preparar fase
                </button>
            </div>
        </div>
    </section>

    <section x-show="selectedNode()?.runtime" class="rounded-3xl border border-violet-200 bg-violet-50 p-5">

        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">

            <div>

                <p class="text-[9px] font-black uppercase text-violet-600">
                    Paso 3 · Motor activo
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950" x-text="selectedNode()?.name">
                </h3>

                <p class="mt-1 text-xs text-slate-500" x-text="selectedNode()?.phase_type_label">
                </p>
            </div>

            <button type="button" @click="simulatePendingRound()"
                :disabled="loading
                    ||
                    !pendingRound() ||
                    selectedNode()?.runtime?.status === 'COMPLETED'"
                class="rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-white disabled:opacity-40">

                ⚡ Simular ronda actual
            </button>
        </div>

        <div x-show="
                selectedNode()?.runtime?.engine === 'GROUP_STAGE'
            " class="mt-5">

            <p class="text-[9px] font-black uppercase text-slate-500">
                Grupo visible
            </p>

            <div class="mt-2 flex flex-wrap gap-2">

                <template x-for="group in groups()" :key="group.id">

                    <button type="button" @click="selectedGroupId = group.id"
                        class="rounded-xl border px-4 py-2 text-xs font-black"
                        :class="selectedGroupId === group.id ?
                            'border-violet-500 bg-violet-600 text-white' :
                            'border-violet-200 bg-white text-violet-700'"
                        x-text="group.name">
                    </button>
                </template>
            </div>
        </div>

        <div class="mt-5 space-y-3">

            <template x-for="round in visibleRounds()" :key="roundKey(round)">

                <article class="rounded-2xl border border-slate-200 bg-white p-4">

                    <button type="button" @click="toggleRound(round)"
                        class="flex w-full items-center justify-between text-left">

                        <div>

                            <p class="font-black text-slate-900" x-text="round.label">
                            </p>

                            <p class="mt-1 text-[9px] text-slate-500">

                                <span
                                    x-text="
                                        round.matches.filter(
                                            match =>
                                                match.status === 'COMPLETED'
                                        ).length
                                    ">
                                </span>
                                /
                                <span x-text="round.matches.length">
                                </span>
                                encuentros
                            </p>
                        </div>

                        <span class="rounded-full px-2 py-1 text-[8px] font-black" :class="statusClass(round.status)"
                            x-text="statusLabel(round.status)">
                        </span>
                    </button>

                    <div x-show="roundIsExpanded(round)" class="mt-4 grid gap-3 xl:grid-cols-2">

                        <template x-for="match in round.matches" :key="match.id">

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">

                                <p class="text-[8px] font-black text-violet-600"
                                    x-text="match.label ?? match.id">
                                </p>
                                <p class="mt-1 text-[9px] font-bold text-slate-400"
                                    x-text="seriesProgressLabel(match)">
                                </p>
                                <div x-show="!usesQualifierSelection(match)"
                                    class="mt-3 grid grid-cols-[minmax(0,1fr)_50px_14px_50px_minmax(0,1fr)] items-center gap-2">

                                    <div class="min-w-0 text-right">

                                        <p class="truncate text-[10px] font-black"
                                            x-text="participantName(
                                                match.participant_a_id
                                            )">
                                        </p>

                                        {{-- Contexto de la Entidad, si es real --}}
                                        <p class="mt-0.5 truncate text-[8px] font-bold text-violet-500"
                                            x-show="participantSubtitle(match.participant_a_id)"
                                            x-text="participantSubtitle(match.participant_a_id)">
                                        </p>
                                    </div>

                                    <template x-if="match.status === 'PENDING'">

                                        <input type="number" min="0"
                                            x-model.number="resultForm(match).score_a"
                                            class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                    </template>

                                    <template x-if="match.status !== 'PENDING'">

                                        <span
                                            class="rounded-lg bg-violet-100 p-2 text-center text-xs font-black text-violet-800"
                                            x-text="match.score_a ?? '—'">
                                        </span>
                                    </template>

                                    <span class="text-center text-slate-400">
                                        –
                                    </span>

                                    <template x-if="match.status === 'PENDING'">

                                        <input type="number" min="0"
                                            x-model.number="resultForm(match).score_b"
                                            class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                    </template>

                                    <template x-if="match.status !== 'PENDING'">

                                        <span
                                            class="rounded-lg bg-violet-100 p-2 text-center text-xs font-black text-violet-800"
                                            x-text="match.score_b ?? '—'">
                                        </span>
                                    </template>

                                    <div class="min-w-0">

                                        <p class="truncate text-[10px] font-black"
                                            x-text="participantName(
                                                match.participant_b_id
                                            )">
                                        </p>

                                        <p class="mt-0.5 truncate text-[8px] font-bold text-violet-500"
                                            x-show="participantSubtitle(match.participant_b_id)"
                                            x-text="participantSubtitle(match.participant_b_id)">
                                        </p>
                                    </div>
                                </div>

                                <div x-show="usesQualifierSelection(match)" class="mt-3">

                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-[9px] font-black uppercase text-slate-500">
                                            Clasifican
                                            <span x-text="match.qualifiers_count"></span>
                                        </p>

                                        <p class="text-[9px] font-bold text-violet-700">
                                            <span x-text="resultForm(match).qualifier_ids.length"></span>
                                            / <span x-text="match.qualifiers_count"></span> elegidos
                                        </p>
                                    </div>

                                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                        <template x-for="participantId in match.participant_ids" :key="participantId">
                                            <button type="button"
                                                @click="match.status === 'PENDING' && toggleQualifier(match, participantId)"
                                                :disabled="match.status !== 'PENDING' || loading"
                                                class="flex items-center justify-between rounded-xl border px-3 py-2 text-left text-[10px] font-black"
                                                :class="qualifierIsSelected(match, participantId)
                                                    ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                                                    : 'border-slate-200 bg-white text-slate-600'">

                                                <span class="truncate" x-text="participantName(participantId)"></span>
                                                <span x-show="qualifierIsSelected(match, participantId)" class="text-emerald-600">✓</span>
                                            </button>
                                        </template>
                                    </div>

                                    <p x-show="match.status === 'WAITING'" class="mt-3 text-[9px] font-bold text-amber-700">
                                        Esperando que los encuentros anteriores completen todos sus slots.
                                    </p>
                                </div>

                                <div x-show="match.status === 'PENDING'" class="mt-3 flex justify-end gap-2">

                                    <button type="button" @click="simulateMatch(match)" :disabled="loading"
                                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[9px] font-black text-amber-700">

                                        Simular
                                    </button>

                                    <button type="button" x-show="!usesQualifierSelection(match)"
                                        @click="submitResult(match)" :disabled="loading"
                                        class="rounded-lg bg-violet-600 px-3 py-2 text-[9px] font-black text-white">

                                        Guardar
                                    </button>

                                    <button type="button" x-show="usesQualifierSelection(match)"
                                        @click="submitQualifiers(match)"
                                        :disabled="loading || resultForm(match).qualifier_ids.length !== Number(match.qualifiers_count)"
                                        class="rounded-lg bg-emerald-600 px-3 py-2 text-[9px] font-black text-white disabled:opacity-40">

                                        Guardar clasificados
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </article>
            </template>
        </div>

        <div x-show="standings().length" class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white">

            <table class="w-full text-left text-xs">

                <thead class="bg-slate-50 text-[8px] font-black uppercase text-slate-400">

                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Participante</th>
                        <th class="p-3">Record</th>
                        <th class="p-3">PJ</th>
                        <th class="p-3">Pts.</th>
                        <th class="p-3">Dif.</th>
                        <th class="p-3">Estado</th>
                    </tr>
                </thead>

                <tbody>

                    <template x-for="row in standings()" :key="row.participant_id">

                        <tr class="border-t border-slate-100">

                            <td class="p-3 font-black" x-text="row.position">
                            </td>

                            <td class="p-3 font-black"
                                x-text="participantName(
                                    row.participant_id
                                )">
                            </td>

                            <td class="p-3" x-text="recordLabel(row)">
                            </td>

                            <td class="p-3" x-text="row.played ?? 0">
                            </td>

                            <td class="p-3 font-black text-violet-600" x-text="row.points ?? 0">
                            </td>

                            <td class="p-3" x-text="row.score_difference ?? 0">
                            </td>

                            <td class="p-3">

                                <span x-show="row.status" class="rounded-full px-2 py-1 text-[8px] font-black"
                                    :class="statusClass(row.status)" x-text="statusLabel(row.status)">
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    @include('tournaments.lab.partials.participants-inspector')
</section>

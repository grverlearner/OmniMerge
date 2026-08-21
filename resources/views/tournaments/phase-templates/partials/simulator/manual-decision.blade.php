{{-- Decisión manual pendiente (seeding manual / BYE manual) --}}

<section x-show="pendingDecision()" x-cloak
    class="rounded-[30px] border border-amber-300 bg-gradient-to-br from-amber-50 via-white to-violet-50 p-5 shadow-sm sm:p-6">

    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
        <div>
            <div class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-[9px] font-black uppercase tracking-[0.16em] text-amber-800">
                Decisión manual pendiente
            </div>
            <h2 class="mt-3 text-xl font-black text-slate-950" x-text="pendingDecision()?.title"></h2>
            <p class="mt-2 max-w-3xl text-xs leading-6 text-slate-600" x-text="pendingDecision()?.description"></p>
        </div>
    </div>

    <div x-show="pendingDecision()?.constraints?.requires_order" class="mt-5">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-[9px] font-black uppercase tracking-wider text-violet-700">Orden / seeds</p>
            <p class="text-[9px] font-bold text-slate-400">Usa ↑ ↓ para ordenar</p>
        </div>
        <div class="grid gap-2 md:grid-cols-2">
            <template x-for="(participantId, index) in decisionDraft.ordered_participant_ids" :key="participantId">
                <div class="flex items-center gap-2 rounded-2xl border border-violet-200 bg-white p-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-[10px] font-black text-violet-700"
                        x-text="index + 1"></span>
                    <span class="min-w-0 flex-1 truncate text-xs font-black text-slate-800" x-text="participantName(participantId)"></span>
                    <button type="button" @click="moveDecisionParticipant(index, -1)" :disabled="index === 0"
                        class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-black text-slate-600 disabled:opacity-30">↑</button>
                    <button type="button" @click="moveDecisionParticipant(index, 1)"
                        :disabled="index === decisionDraft.ordered_participant_ids.length - 1"
                        class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-black text-slate-600 disabled:opacity-30">↓</button>
                </div>
            </template>
        </div>
    </div>

    <div x-show="decisionNeedsSelection()" class="mt-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-[9px] font-black uppercase tracking-wider text-emerald-700">BYE manual</p>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-[9px] font-black text-emerald-700"
                x-text="`${decisionDraft.selected_participant_ids.length} / ${Number(pendingDecision()?.required_selection_count ?? pendingDecision()?.constraints?.bye_count ?? 0)}`"></span>
        </div>
        <p class="mt-1 text-[10px] leading-5 text-slate-500">
            Elige quién avanza automáticamente sin jugar la primera ronda.
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <template x-for="participantId in pendingDecision()?.eligible_participant_ids ?? []" :key="participantId">
                <button type="button" @click="toggleDecisionSelection(participantId)"
                    class="rounded-xl border px-3 py-2 text-[10px] font-black transition"
                    :class="decisionSelected(participantId)
                        ? 'border-emerald-500 bg-emerald-600 text-white'
                        : 'border-slate-200 bg-white text-slate-600'">
                    <span x-text="participantName(participantId)"></span>
                    <span x-show="decisionSelected(participantId)"> ✓</span>
                </button>
            </template>
        </div>
    </div>

    {{-- ASIGNACIÓN MANUAL DE GRUPOS (Group Stage) --}}

    <div x-show="typeof isGroupAssignmentDecision === 'function' && isGroupAssignmentDecision()" class="mt-5">

        <div class="mb-3 flex flex-wrap items-center gap-2">
            <template x-for="group in decisionGroups()" :key="group.key">
                <span class="rounded-full border px-3 py-1.5 text-[9px] font-black"
                    :class="groupAssignmentCount(group.key) === group.size
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                        : 'border-slate-200 bg-white text-slate-500'">
                    <span x-text="group.name"></span>:
                    <span x-text="groupAssignmentCount(group.key)"></span>/<span x-text="group.size"></span>
                </span>
            </template>
        </div>

        <div class="grid gap-2 md:grid-cols-2">
            <template x-for="participantId in pendingDecision()?.eligible_participant_ids ?? []" :key="participantId">
                <div class="flex items-center gap-2 rounded-2xl border border-violet-200 bg-white p-3">
                    <span class="min-w-0 flex-1 truncate text-xs font-black text-slate-800"
                        x-text="participantName(participantId)"></span>

                    <select class="rounded-lg border-slate-200 bg-white text-[10px] font-bold"
                        :value="participantGroupAssignment(participantId)"
                        @change="assignToGroup(participantId, $event.target.value)">
                        <option value="">Sin grupo</option>
                        <template x-for="group in decisionGroups()" :key="group.key">
                            <option :value="group.key" x-text="group.name"></option>
                        </template>
                    </select>
                </div>
            </template>
        </div>
    </div>

    <div class="mt-5 flex flex-col gap-3 border-t border-amber-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-[10px] leading-5 text-slate-500">
            Esta decisión solo pertenece a esta simulación temporal. No modifica la
            configuración real de la fase.
        </p>
        <button type="button" @click="resolveManualDecision()"
            :disabled="loading || (typeof canResolveManualDecision === 'function' && !canResolveManualDecision())"
            class="shrink-0 rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-white shadow-lg shadow-amber-500/20 disabled:opacity-40">
            Confirmar y continuar
        </button>
    </div>
</section>

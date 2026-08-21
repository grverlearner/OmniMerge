{{-- Bracket con resultados en vivo --}}

<section x-show="!runtime()" x-cloak class="rounded-3xl border border-slate-200 bg-white p-8 text-center">
    <p class="text-sm font-black text-slate-500">Generando la simulación…</p>
</section>

<section x-show="runtime() && rounds().length" x-cloak class="rounded-3xl border border-violet-200 bg-violet-50 p-5">

    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
        <div>
            <p class="text-[9px] font-black uppercase text-violet-600">
                Bracket
            </p>
            <h3 class="mt-1 text-xl font-black text-slate-950">
                <span x-show="isRunning()">En ejecución</span>
                <span x-show="isCompleted()">Simulación completada</span>
                <span x-show="isAwaitingDecision()">Esperando decisión manual</span>
            </h3>
        </div>

        <button type="button" @click="simulateRound()"
            :disabled="loading || !pendingRound() || isCompleted()"
            class="rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-white transition disabled:cursor-not-allowed disabled:opacity-40">
            <span x-show="!loading">⚡ Simular ronda actual</span>
            <span x-show="loading" x-cloak>Simulando…</span>
        </button>
    </div>

    <div class="mt-5 space-y-3">
        <template x-for="round in rounds()" :key="roundKey(round)">
            <article class="rounded-2xl border border-slate-200 bg-white p-4">

                <button type="button" @click="toggleRound(round)"
                    class="flex w-full items-center justify-between text-left">
                    <div>
                        <p class="font-black text-slate-900" x-text="round.label"></p>
                        <p class="mt-1 text-[9px] text-slate-500">
                            <span x-text="round.matches.filter(match => ['COMPLETED', 'BYE'].includes(match.status)).length"></span>
                            / <span x-text="round.matches.length"></span> resueltos
                        </p>
                    </div>

                    <span class="rounded-full px-2 py-1 text-[8px] font-black" :class="statusClass(round.status)"
                        x-text="statusLabel(round.status)"></span>
                </button>

                <div x-show="roundIsExpanded(round)" class="mt-4 grid gap-3 xl:grid-cols-2">
                    <template x-for="match in round.matches" :key="match.id">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">

                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[8px] font-black text-violet-600" x-text="match.label ?? match.id"></p>
                                <span class="rounded-full px-2 py-0.5 text-[8px] font-black" :class="statusClass(match.status)"
                                    x-text="statusLabel(match.status)"></span>
                            </div>

                            <p class="mt-1 text-[9px] font-bold text-slate-400" x-text="seriesProgressLabel(match)"></p>

                            {{-- Duelo simple: score --}}
                            <div x-show="!usesQualifierSelection(match) && match.status !== 'BYE'"
                                class="mt-3 grid grid-cols-[minmax(0,1fr)_50px_14px_50px_minmax(0,1fr)] items-center gap-2">

                                <p class="truncate text-right text-[10px] font-black" x-text="participantName(match.participant_a_id)"></p>

                                <template x-if="match.status === 'PENDING'">
                                    <input type="number" min="0" x-model.number="resultForm(match).score_a"
                                        class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                </template>
                                <template x-if="match.status !== 'PENDING'">
                                    <span class="rounded-lg bg-violet-100 p-2 text-center text-xs font-black text-violet-800"
                                        x-text="match.score_a ?? '—'"></span>
                                </template>

                                <span class="text-center text-slate-400">–</span>

                                <template x-if="match.status === 'PENDING'">
                                    <input type="number" min="0" x-model.number="resultForm(match).score_b"
                                        class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                </template>
                                <template x-if="match.status !== 'PENDING'">
                                    <span class="rounded-lg bg-violet-100 p-2 text-center text-xs font-black text-violet-800"
                                        x-text="match.score_b ?? '—'"></span>
                                </template>

                                <p class="truncate text-[10px] font-black" x-text="participantName(match.participant_b_id)"></p>
                            </div>

                            {{-- BYE --}}
                            <div x-show="match.status === 'BYE'" class="mt-3 rounded-lg bg-violet-100 p-2 text-center text-xs font-black text-violet-800">
                                <span x-text="participantName(match.participant_a_id ?? match.participant_b_id)"></span>
                                avanza por BYE
                            </div>

                            {{-- K → Q: selección de clasificados --}}
                            <div x-show="usesQualifierSelection(match)" class="mt-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[9px] font-black uppercase text-slate-500">
                                        Clasifican <span x-text="match.qualifiers_count"></span>
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
                                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[9px] font-black text-amber-700 transition disabled:cursor-not-allowed disabled:opacity-40">
                                    <span x-show="!loading">Simular</span>
                                    <span x-show="loading" x-cloak>Simulando…</span>
                                </button>

                                <button type="button" x-show="!usesQualifierSelection(match)" @click="submitResult(match)" :disabled="loading"
                                    class="rounded-lg bg-violet-600 px-3 py-2 text-[9px] font-black text-white transition disabled:cursor-not-allowed disabled:opacity-40">
                                    <span x-show="!loading">Guardar</span>
                                    <span x-show="loading" x-cloak>Guardando…</span>
                                </button>

                                <button type="button" x-show="usesQualifierSelection(match)" @click="submitQualifiers(match)"
                                    :disabled="loading || resultForm(match).qualifier_ids.length !== Number(match.qualifiers_count)"
                                    class="rounded-lg bg-emerald-600 px-3 py-2 text-[9px] font-black text-white transition disabled:cursor-not-allowed disabled:opacity-40">
                                    <span x-show="!loading">Guardar clasificados</span>
                                    <span x-show="loading" x-cloak>Guardando…</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </article>
        </template>
    </div>

    {{-- CLASIFICACIÓN FINAL --}}

    <div x-show="isCompleted() && standings().length" class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-[8px] font-black uppercase text-slate-400">
                <tr>
                    <th class="p-3">Posición</th>
                    <th class="p-3">Participante</th>
                    <th class="p-3">Estado</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in standings()" :key="row.participant_id">
                    <tr class="border-t border-slate-100">
                        <td class="p-3 font-black">
                            <span x-text="row.position_from === row.position_to ? row.position_from : `${row.position_from}-${row.position_to}`"></span>
                        </td>
                        <td class="p-3 font-black" x-text="participantName(row.participant_id)"></td>
                        <td class="p-3">
                            <span class="rounded-full px-2 py-1 text-[8px] font-black" :class="statusClass(row.status)"
                                x-text="statusLabel(row.status)"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</section>

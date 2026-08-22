{{-- Jornadas, encuentros y clasificación en vivo --}}

<section x-show="!runtime()" x-cloak class="rounded-3xl border border-slate-200 bg-white p-8 text-center">
    <p class="text-sm font-black text-slate-500">Generando la simulación…</p>
</section>

{{-- ACCIONES EN BLOQUE --}}

<div x-show="runtime() && pendingCount() > 0" x-cloak
    class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">

    <div class="flex flex-wrap items-center justify-between gap-3">

        <p class="text-[11px] font-bold text-amber-800">
            Quedan <strong class="font-black"><span x-text="pendingCount()"></span></strong>
            encuentros por jugar.
        </p>

        <div class="flex flex-wrap items-center gap-2">

            <button type="button" @click="setAllRounds(!allExpanded())"
                class="rounded-lg border border-amber-300 px-3 py-1.5 text-[10px] font-black text-amber-700 transition hover:bg-amber-100">
                <span x-show="allExpanded()">▴ Plegar jornadas</span>
                <span x-show="!allExpanded()" x-cloak>▾ Desplegar jornadas</span>
            </button>

            <template x-for="cycle in cycles()" :key="cycle">
                <button type="button" @click="simulateCycle(cycle)"
                    x-show="cyclePendingCount(cycle) > 0"
                    :disabled="loading"
                    class="rounded-lg border border-cyan-300 bg-cyan-50 px-3 py-1.5 text-[10px] font-black text-cyan-700 transition hover:bg-cyan-100 disabled:opacity-40">
                    ⚡⚡ Ciclo <span x-text="cycle"></span>
                    <span class="opacity-70">(<span x-text="cyclePendingCount(cycle)"></span>)</span>
                </button>
            </template>

            <button type="button" @click="simulateEverything()" :disabled="loading"
                class="rounded-lg bg-amber-600 px-3 py-1.5 text-[10px] font-black text-white transition hover:bg-amber-700 disabled:opacity-40">
                <span x-show="!loading">⚡⚡⚡ Toda la fase</span>
                <span x-show="loading" x-cloak>Simulando…</span>
            </button>

        </div>

    </div>

</div>


<section x-show="runtime() && rounds().length" x-cloak class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">

    {{-- JORNADAS --}}

    <div class="rounded-3xl border border-cyan-200 bg-cyan-50 p-5">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div>
                <p class="text-[9px] font-black uppercase text-cyan-600">
                    Calendario
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
                <span x-show="!loading">⚡ Simular jornada actual</span>
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

                            {{-- Simular ESTA jornada, no la primera pendiente --}}
                            <button type="button" @click.stop="simulateThisRound(round)"
                                x-show="roundHasPending(round)" :disabled="loading"
                                class="mt-1 rounded-lg bg-amber-100 px-2 py-0.5 text-[9px] font-black text-amber-700 transition hover:bg-amber-200 disabled:opacity-40">
                                ⚡ Simular jornada
                            </button>
                            <p class="mt-1 text-[9px] text-slate-500">
                                Ciclo <span x-text="round.cycle"></span> ·
                                <span x-text="round.matches.filter(match => match.status === 'COMPLETED').length"></span>
                                / <span x-text="round.matches.length"></span> resueltos
                            </p>
                        </div>

                        <span class="rounded-full px-2 py-1 text-[8px] font-black" :class="statusClass(round.status)"
                            x-text="statusLabel(round.status)"></span>
                    </button>

                    <div x-show="roundIsExpanded(round)" class="mt-4 grid gap-3 xl:grid-cols-2">

                        <div x-show="restingParticipantId(round)"
                            class="rounded-xl border border-dashed border-violet-300 bg-violet-50 p-3 text-center xl:col-span-2">
                            <span class="text-[10px] font-black text-violet-700">
                                Descansa: <span x-text="participantName(restingParticipantId(round))"></span>
                            </span>
                        </div>

                        <template x-for="match in round.matches" :key="match.id">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">

                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[8px] font-black text-cyan-600" x-text="match.id"></p>
                                    <span class="rounded-full px-2 py-0.5 text-[8px] font-black"
                                        :class="statusClass(match.status)" x-text="statusLabel(match.status)"></span>
                                </div>

                                <p x-show="match.best_of > 1" class="mt-1 text-[9px] font-bold text-slate-400"
                                    x-text="seriesProgressLabel(match)"></p>

                                <div class="mt-3 grid grid-cols-[minmax(0,1fr)_50px_14px_50px_minmax(0,1fr)] items-center gap-2">

                                    {{-- Contendiente A, con su cara --}}
                                    <div class="flex min-w-0 items-center justify-end gap-1.5">
                                        <p class="min-w-0 truncate text-right text-[10px] font-black"
                                            x-text="participantName(match.participant_a_id)"></p>

                                        <div class="h-7 w-7 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                                            <template x-if="participantImage(match.participant_a_id)">
                                                <img :src="participantImage(match.participant_a_id)" alt=""
                                                    class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!participantImage(match.participant_a_id)">
                                                <span class="flex h-full w-full items-center justify-center text-[8px] font-black text-slate-400"
                                                    x-text="participantInitials(match.participant_a_id)"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <template x-if="match.status === 'PENDING'">
                                        <input type="number" min="0" x-model.number="resultForm(match).score_a"
                                            class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                    </template>
                                    <template x-if="match.status !== 'PENDING'">
                                        <span
                                            class="rounded-lg bg-cyan-100 p-2 text-center text-xs font-black text-cyan-800"
                                            x-text="match.score_a ?? '—'"></span>
                                    </template>

                                    <span class="text-center text-slate-400">–</span>

                                    <template x-if="match.status === 'PENDING'">
                                        <input type="number" min="0" x-model.number="resultForm(match).score_b"
                                            class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                    </template>
                                    <template x-if="match.status !== 'PENDING'">
                                        <span
                                            class="rounded-lg bg-cyan-100 p-2 text-center text-xs font-black text-cyan-800"
                                            x-text="match.score_b ?? '—'"></span>
                                    </template>

                                    {{-- Contendiente B, con su cara --}}
                                    <div class="flex min-w-0 items-center gap-1.5">
                                        <div class="h-7 w-7 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                                            <template x-if="participantImage(match.participant_b_id)">
                                                <img :src="participantImage(match.participant_b_id)" alt=""
                                                    class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!participantImage(match.participant_b_id)">
                                                <span class="flex h-full w-full items-center justify-center text-[8px] font-black text-slate-400"
                                                    x-text="participantInitials(match.participant_b_id)"></span>
                                            </template>
                                        </div>

                                        <p class="min-w-0 truncate text-[10px] font-black"
                                            x-text="participantName(match.participant_b_id)"></p>
                                    </div>
                                </div>

                                <div x-show="match.status === 'PENDING'" class="mt-3 flex justify-end gap-2">
                                    <button type="button" @click="simulateMatch(match)" :disabled="loading"
                                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[9px] font-black text-amber-700 transition disabled:cursor-not-allowed disabled:opacity-40">
                                        <span x-show="!loading">Simular</span>
                                        <span x-show="loading" x-cloak>Simulando…</span>
                                    </button>

                                    <button type="button" @click="submitResult(match)" :disabled="loading"
                                        class="rounded-lg bg-cyan-600 px-3 py-2 text-[9px] font-black text-white transition disabled:cursor-not-allowed disabled:opacity-40">
                                        <span x-show="!loading">Guardar</span>
                                        <span x-show="loading" x-cloak>Guardando…</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </article>
            </template>
        </div>
    </div>

    {{-- CLASIFICACIÓN EN VIVO --}}

    <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-5 xl:sticky xl:top-28">
        <p class="text-[9px] font-black uppercase tracking-wider text-cyan-600">
            Clasificación
        </p>
        <h3 class="mt-1 font-black text-slate-950">
            Standings en vivo
        </h3>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-[11px]">
                <thead class="text-[8px] font-black uppercase text-slate-400">
                    <tr>
                        <th class="py-2 pr-2">#</th>
                        <th class="py-2 pr-2">Participante</th>
                        <th class="py-2 pr-2 text-center">PJ</th>
                        <th class="py-2 pr-2 text-center">PG</th>
                        <th class="py-2 pr-2 text-center">PE</th>
                        <th class="py-2 pr-2 text-center">PP</th>
                        <th class="py-2 pr-2 text-center">DIF</th>
                        <th class="py-2 text-center">PTS</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in standings()" :key="row.participant_id">
                        <tr class="border-t border-slate-100">
                            <td class="py-2 pr-2 font-black text-slate-400" x-text="row.position"></td>
                            <td class="max-w-[110px] truncate py-2 pr-2 font-black text-slate-800"
                                x-text="participantName(row.participant_id)"></td>
                            <td class="py-2 pr-2 text-center" x-text="row.played"></td>
                            <td class="py-2 pr-2 text-center text-emerald-600" x-text="row.wins"></td>
                            <td class="py-2 pr-2 text-center text-slate-500" x-text="row.draws"></td>
                            <td class="py-2 pr-2 text-center text-red-600" x-text="row.losses"></td>
                            <td class="py-2 pr-2 text-center" x-text="row.score_difference"></td>
                            <td class="py-2 text-center font-black text-cyan-700" x-text="row.points"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </aside>
</section>

{{-- Grupos, calendario por grupo y clasificación en vivo --}}

<section x-show="!runtime()" x-cloak class="rounded-3xl border border-slate-200 bg-white p-8 text-center">
    <p class="text-sm font-black text-slate-500">Generando la simulación…</p>
</section>

<section x-show="runtime() && groupsList().length" x-cloak class="space-y-5">

    <div class="flex flex-col justify-between gap-4 rounded-3xl border border-indigo-200 bg-indigo-50 p-5 lg:flex-row lg:items-center">
        <div>
            <p class="text-[9px] font-black uppercase text-indigo-600">
                Group Stage
            </p>
            <h3 class="mt-1 text-xl font-black text-slate-950">
                <span x-show="isRunning()">En ejecución</span>
                <span x-show="isCompleted()">Simulación completada</span>
                <span x-show="isAwaitingDecision()">Esperando decisión manual</span>
            </h3>
        </div>

        <button type="button" @click="simulateRound()"
            :disabled="loading || !pendingRound() || isCompleted()"
            title="Resuelve la próxima jornada pendiente de un solo grupo (no de todos a la vez)."
            class="rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-white transition disabled:cursor-not-allowed disabled:opacity-40">
            <span x-show="!loading">⚡ Simular un encuentro grupal pendiente</span>
            <span x-show="loading" x-cloak>Simulando…</span>
        </button>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">

        <template x-for="group in groupsList()" :key="group.id">
            <article class="rounded-3xl border border-slate-200 bg-white p-5">

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="font-mono text-[9px] font-black text-indigo-500" x-text="group.code"></p>
                        <h4 class="mt-1 text-lg font-black text-slate-950" x-text="group.name"></h4>
                    </div>

                    <span class="rounded-full px-2 py-1 text-[8px] font-black" :class="statusClass(group.status)"
                        x-text="statusLabel(group.status)"></span>
                </div>

                <button type="button" @click="simulateGroupRound(group)"
                    :disabled="loading || !groupHasPendingRound(group)"
                    class="mt-3 w-full rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-[10px] font-black text-indigo-700 transition disabled:cursor-not-allowed disabled:opacity-40">
                    <span x-show="!loading">⚡ Simular jornada de este grupo</span>
                    <span x-show="loading" x-cloak>Simulando…</span>
                </button>

                {{-- STANDINGS DEL GRUPO --}}

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
                            <template x-for="row in group.standings" :key="row.participant_id">
                                <tr class="border-t border-slate-100">
                                    <td class="py-2 pr-2 font-black text-slate-400" x-text="row.position"></td>
                                    <td class="max-w-[110px] truncate py-2 pr-2 font-black text-slate-800"
                                        x-text="participantName(row.participant_id)"></td>
                                    <td class="py-2 pr-2 text-center" x-text="row.played"></td>
                                    <td class="py-2 pr-2 text-center text-emerald-600" x-text="row.wins"></td>
                                    <td class="py-2 pr-2 text-center text-slate-500" x-text="row.draws"></td>
                                    <td class="py-2 pr-2 text-center text-red-600" x-text="row.losses"></td>
                                    <td class="py-2 pr-2 text-center" x-text="row.score_difference"></td>
                                    <td class="py-2 text-center font-black text-indigo-700" x-text="row.points"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- CALENDARIO DEL GRUPO --}}

                <div class="mt-4 space-y-2">
                    <template x-for="round in groupRounds(group)" :key="roundKey(round)">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">

                            <button type="button" @click="toggleRound(round)"
                                class="flex w-full items-center justify-between text-left">
                                <div>
                                    <p class="text-xs font-black text-slate-900" x-text="round.label"></p>
                                    <p class="mt-0.5 text-[9px] text-slate-500">
                                        <span x-text="round.matches.filter(match => match.status === 'COMPLETED').length"></span>
                                        / <span x-text="round.matches.length"></span> resueltos
                                    </p>
                                </div>

                                <span class="rounded-full px-2 py-1 text-[8px] font-black" :class="statusClass(round.status)"
                                    x-text="statusLabel(round.status)"></span>
                            </button>

                            <div x-show="roundIsExpanded(round)" class="mt-3 space-y-2">

                                <div x-show="restingParticipantId(round, group)"
                                    class="rounded-xl border border-dashed border-violet-300 bg-violet-50 p-2 text-center">
                                    <span class="text-[9px] font-black text-violet-700">
                                        Descansa: <span x-text="participantName(restingParticipantId(round, group))"></span>
                                    </span>
                                </div>

                                <template x-for="match in round.matches" :key="match.id">
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">

                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-[8px] font-black text-indigo-600" x-text="match.id"></p>
                                            <span class="rounded-full px-2 py-0.5 text-[8px] font-black"
                                                :class="statusClass(match.status)" x-text="statusLabel(match.status)"></span>
                                        </div>

                                        <p x-show="match.best_of > 1" class="mt-1 text-[9px] font-bold text-slate-400"
                                            x-text="seriesProgressLabel(match)"></p>

                                        <div class="mt-3 grid grid-cols-[minmax(0,1fr)_50px_14px_50px_minmax(0,1fr)] items-center gap-2">

                                            <p class="truncate text-right text-[10px] font-black"
                                                x-text="participantName(match.participant_a_id)"></p>

                                            <template x-if="match.status === 'PENDING'">
                                                <input type="number" min="0" x-model.number="resultForm(match).score_a"
                                                    class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                            </template>
                                            <template x-if="match.status !== 'PENDING'">
                                                <span
                                                    class="rounded-lg bg-indigo-100 p-2 text-center text-xs font-black text-indigo-800"
                                                    x-text="match.score_a ?? '—'"></span>
                                            </template>

                                            <span class="text-center text-slate-400">–</span>

                                            <template x-if="match.status === 'PENDING'">
                                                <input type="number" min="0" x-model.number="resultForm(match).score_b"
                                                    class="rounded-lg border-slate-200 p-2 text-center text-xs">
                                            </template>
                                            <template x-if="match.status !== 'PENDING'">
                                                <span
                                                    class="rounded-lg bg-indigo-100 p-2 text-center text-xs font-black text-indigo-800"
                                                    x-text="match.score_b ?? '—'"></span>
                                            </template>

                                            <p class="truncate text-[10px] font-black"
                                                x-text="participantName(match.participant_b_id)"></p>
                                        </div>

                                        <div x-show="match.status === 'PENDING'" class="mt-3 flex justify-end gap-2">
                                            <button type="button" @click="simulateMatch(match)" :disabled="loading"
                                                class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[9px] font-black text-amber-700 transition disabled:cursor-not-allowed disabled:opacity-40">
                                                <span x-show="!loading">Simular</span>
                                                <span x-show="loading" x-cloak>Simulando…</span>
                                            </button>

                                            <button type="button" @click="submitResult(match)" :disabled="loading"
                                                class="rounded-lg bg-indigo-600 px-3 py-2 text-[9px] font-black text-white transition disabled:cursor-not-allowed disabled:opacity-40">
                                                <span x-show="!loading">Guardar</span>
                                                <span x-show="loading" x-cloak>Guardando…</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

            </article>
        </template>

    </div>

    {{-- CLASIFICACIÓN GLOBAL (todas las posiciones, útil para reglas cruzadas) --}}

    <aside class="rounded-3xl border border-slate-200 bg-white p-5">
        <p class="text-[9px] font-black uppercase tracking-wider text-indigo-600">
            Clasificación combinada
        </p>
        <h3 class="mt-1 font-black text-slate-950">
            Todas las posiciones, de todos los grupos
        </h3>
        <p class="mt-2 text-[10px] leading-5 text-slate-500">
            Útil para verificar reglas que comparan posiciones entre grupos (ej. mejores terceros).
        </p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-[11px]">
                <thead class="text-[8px] font-black uppercase text-slate-400">
                    <tr>
                        <th class="py-2 pr-2">Grupo</th>
                        <th class="py-2 pr-2">#</th>
                        <th class="py-2 pr-2">Participante</th>
                        <th class="py-2 pr-2 text-center">PTS</th>
                        <th class="py-2 text-center">DIF</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in standings()" :key="row.group_id + ':' + row.participant_id">
                        <tr class="border-t border-slate-100">
                            <td class="py-2 pr-2 text-[9px] font-black text-indigo-500" x-text="row.group_name"></td>
                            <td class="py-2 pr-2 font-black text-slate-400" x-text="row.position"></td>
                            <td class="max-w-[140px] truncate py-2 pr-2 font-black text-slate-800"
                                x-text="participantName(row.participant_id)"></td>
                            <td class="py-2 pr-2 text-center font-black text-indigo-700" x-text="row.points"></td>
                            <td class="py-2 text-center" x-text="row.score_difference"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </aside>

</section>

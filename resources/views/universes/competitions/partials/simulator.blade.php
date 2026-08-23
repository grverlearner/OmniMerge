{{--
    Simulador de enfrentamientos (Fase 11)

    Esta pantalla NO sabe qué es Highest Number. Todo lo específico del
    juego llega dentro de state.encounter: los controles que ofrecer, la
    etiqueta del valor, las estadísticas de cada participante y el
    resultado. Un juego nuevo se pinta aquí solo.
--}}

<section x-show="state?.game" x-cloak class="mt-6">

    {{-- ARRANQUE: todavía no hay enfrentamiento preparado --}}

    <template x-if="!state?.encounter && state?.status === 'RUNNING'">

        <div
            class="flex flex-col items-center gap-4 rounded-[30px] border-2 border-dashed border-slate-300 bg-white p-8 text-center sm:flex-row sm:justify-between sm:text-left">

            <div class="flex items-center gap-4">

                <div class="text-4xl" x-text="state?.game?.icon ?? '🎲'"></div>

                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                        Simulación a mano
                    </p>

                    <h3 class="mt-1 text-lg font-black text-slate-900">
                        Juega el siguiente enfrentamiento tú
                    </h3>

                    <p class="mt-1 max-w-lg text-xs leading-relaxed text-slate-500">
                        Genera el resultado de cada competidor por separado y ve
                        quién gana, en vez de dejar que el motor lo resuelva solo.
                    </p>
                </div>

            </div>

            <button type="button" @click="execute('PREPARE_ENCOUNTER')" :disabled="loading"
                class="shrink-0 rounded-xl bg-slate-950 px-5 py-3 text-xs font-black text-white transition hover:bg-slate-800 disabled:opacity-40">
                Preparar enfrentamiento
            </button>

        </div>
    </template>


    {{-- ARENA --}}

    <template x-if="state?.encounter">

        <div
            class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-slate-900 to-violet-950 text-white shadow-2xl">

            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl">
            </div>

            <div
                class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-emerald-500/10 blur-3xl">
            </div>


            {{-- CABECERA: GAME · BATTLE --}}

            <div class="relative flex flex-wrap items-center justify-between gap-4 border-b border-white/10 px-7 py-5">

                <div class="flex items-center gap-3">

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-xl backdrop-blur">
                        <span x-text="state.encounter.game.icon"></span>
                    </div>

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.18em] text-violet-300">
                            Juego
                        </p>
                        <p class="text-sm font-black" x-text="state.encounter.game.name"></p>
                    </div>

                </div>


                <div class="flex flex-wrap items-center gap-2 text-[10px] font-black">

                    <span class="rounded-full bg-white/10 px-3 py-1.5 uppercase tracking-wider text-white/70"
                        x-show="state.encounter.phase_name" x-text="state.encounter.phase_name">
                    </span>

                    <span class="rounded-full bg-white/10 px-3 py-1.5 uppercase tracking-wider text-white/70"
                        x-text="state.encounter.series.label">
                    </span>

                    <span class="rounded-full bg-violet-500/30 px-3 py-1.5 uppercase tracking-wider text-violet-100">
                        Enfrentamiento <span x-text="state.encounter.number"></span>
                    </span>

                </div>

            </div>


            {{-- MARCADOR DE LA BATALLA --}}

            <div class="relative flex items-center justify-center gap-3 border-b border-white/5 bg-black/20 px-7 py-3">

                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/40">
                    Batalla
                </p>

                <p class="font-mono text-lg font-black tabular-nums">
                    <span x-text="state.encounter.series.score_a"></span>
                    <span class="text-white/30">–</span>
                    <span x-text="state.encounter.series.score_b"></span>
                </p>

                <p class="text-[9px] font-bold text-white/40" x-show="state.encounter.series.wins_required">
                    a <span x-text="state.encounter.series.wins_required"></span> victorias
                </p>

            </div>


            {{-- PARTICIPANTES --}}

            <div class="relative px-7 py-7">

                <div class="grid gap-4"
                    :class="state.encounter.participants.length > 2 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2'">

                    <template x-for="participant in state.encounter.participants" :key="participant.id">

                        <div class="relative overflow-hidden rounded-3xl border p-5 transition-all duration-300"
                            :class="participant.is_winner ?
                                'border-emerald-400/50 bg-emerald-400/10 shadow-lg shadow-emerald-500/10' :
                                (state.encounter.status === 'RESOLVED' ?
                                    'border-white/5 bg-white/[0.02] opacity-60' :
                                    'border-white/10 bg-white/5')">

                            {{-- CORONA --}}

                            <div class="absolute right-4 top-4 text-xl" x-show="participant.is_winner"
                                x-transition.scale>
                                🏆
                            </div>


                            {{-- IDENTIDAD --}}

                            <div class="flex items-center gap-3">

                                <div class="h-12 w-12 shrink-0 overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/20">
                                    <template x-if="participant.image_url">
                                        <img :src="participant.image_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                </div>

                                <div class="min-w-0 flex-1">

                                    <p class="truncate text-sm font-black" x-text="participant.name"></p>

                                    <p class="truncate font-mono text-[10px] text-white/40"
                                        x-text="participant.stats_label"></p>

                                </div>

                            </div>


                            {{-- VALOR GENERADO --}}

                            <div class="mt-5 flex items-end justify-between gap-3">

                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/40"
                                        x-text="state.encounter.controls.value_label ?? 'Resultado'">
                                    </p>

                                    <p class="mt-0.5 font-mono text-4xl font-black tabular-nums transition-colors"
                                        :class="participant.rolled ?
                                            (participant.is_winner ? 'text-emerald-300' : 'text-white') :
                                            'text-white/20'"
                                        x-text="participant.rolled ?
                                            participant.display :
                                            (state.encounter.controls.pending_label ?? '?')">
                                    </p>
                                </div>


                                {{-- POSICIÓN, cuando hay más de dos --}}

                                <div class="shrink-0 text-right"
                                    x-show="state.encounter.status === 'RESOLVED' &&
                                        state.encounter.participants.length > 2">

                                    <p class="text-[9px] font-black uppercase tracking-wider text-white/40">
                                        Puesto
                                    </p>
                                    <p class="font-mono text-lg font-black" x-text="participant.position"></p>

                                </div>

                            </div>


                            {{-- BOTÓN INDIVIDUAL --}}

                            <button type="button"
                                x-show="state.encounter.status === 'ROLLING' &&
                                    !participant.rolled &&
                                    state.encounter.controls.per_participant"
                                @click="execute('ROLL_ENCOUNTER', { participant_id: participant.id })"
                                :disabled="loading"
                                class="mt-4 w-full rounded-xl bg-white/10 px-4 py-2.5 text-[11px] font-black text-white transition hover:bg-white/20 disabled:opacity-40">

                                <span x-text="state.encounter.controls.roll_label ?? 'Generar'"></span>
                                <span x-text="participant.name"></span>

                            </button>


                            <div class="mt-4 rounded-xl border border-white/5 px-4 py-2.5 text-center text-[11px] font-black text-white/30"
                                x-show="state.encounter.status === 'ROLLING' && participant.rolled">
                                Listo
                            </div>

                        </div>
                    </template>

                </div>


                {{-- RESULTADO --}}

                <div class="mt-6 rounded-3xl border border-white/10 bg-black/30 p-5"
                    x-show="state.encounter.status === 'RESOLVED'" x-transition>

                    <div class="flex flex-wrap items-center justify-between gap-4">

                        <div class="flex items-center gap-3">

                            <div class="text-2xl" x-text="state.encounter.is_draw ? '🤝' : '🏆'"></div>

                            <div>
                                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-emerald-300">
                                    Resultado del enfrentamiento
                                </p>

                                <p class="mt-0.5 text-base font-black" x-text="state.encounter.summary"></p>

                                <p class="mt-1 text-[10px] text-white/40" x-show="state.encounter.tiebreaks > 0">
                                    Hubo empate: se repitió la tirada
                                    <span x-text="state.encounter.tiebreaks"></span> vez/veces.
                                </p>
                            </div>

                        </div>


                        {{--
                            El match_id solo mientras la serie sigue viva: asi
                            el siguiente enfrentamiento es de ESTA batalla y no
                            del primer partido pendiente de la fase. Terminada
                            la serie se manda sin el, que es lo que "Continuar"
                            promete: pasar a lo siguiente que haya.
                        --}}
                        <button type="button"
                            @click="execute('ADVANCE_ENCOUNTER', state.encounter.battle_completed
                                ? {}
                                : { match_id: state.encounter.battle_key })"
                            :disabled="loading"
                            class="rounded-xl bg-emerald-500 px-5 py-3 text-xs font-black text-white shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-400 disabled:opacity-40">

                            <span x-show="!state.encounter.battle_completed">Siguiente enfrentamiento →</span>
                            <span x-show="state.encounter.battle_completed">Continuar →</span>

                        </button>

                    </div>

                </div>


                {{-- CONTROLES GENERALES --}}

                <div class="mt-6 flex flex-wrap items-center gap-3" x-show="state.encounter.status === 'ROLLING'">

                    <button type="button" x-show="state.encounter.controls.all"
                        @click="execute('ROLL_ENCOUNTER', { all: true })" :disabled="loading"
                        class="rounded-xl bg-violet-500 px-5 py-3 text-xs font-black text-white shadow-lg shadow-violet-500/20 transition hover:bg-violet-400 disabled:opacity-40"
                        x-text="loading ? 'Generando…' : (state.encounter.controls.all_label ?? 'Generar todos')">
                    </button>

                    <p class="text-[10px] leading-relaxed text-white/40">
                        <span x-text="state.encounter.game.win_condition"></span>
                    </p>

                </div>

            </div>

        </div>
    </template>

</section>

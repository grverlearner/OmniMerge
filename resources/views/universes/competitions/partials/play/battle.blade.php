{{--
    ETAPA 3 · La batalla.

    Dos fuentes vivas: `battle` (lo ya jugado, del servidor) y
    `liveEncounter` (el enfrentamiento en curso, que es el Runtime en vivo).

    Los controles salen de la definición del GameEngine, no de Highest
    Number: un juego futuro con otros controles se pinta solo.
--}}

<div class="px-5 py-6">

    {{-- SIN BATALLA SELECCIONADA --}}

    <template x-if="!battle">
        <div class="flex min-h-[70vh] items-center justify-center">
            <div class="max-w-md text-center">
                <div class="text-6xl opacity-25">⚔</div>
                <h3 class="mt-6 text-xl font-black text-white">Ninguna batalla seleccionada</h3>
                <p class="mt-2 text-sm text-slate-400">
                    Vuelve a la estructura y pulsa cualquier enfrentamiento.
                </p>
                <button type="button" @click="stage = 2"
                    class="mt-6 rounded-xl bg-violet-500 px-6 py-3 text-xs font-black text-white hover:bg-violet-400">
                    Ver estructura
                </button>
            </div>
        </div>
    </template>


    <template x-if="battle">
        <div class="mx-auto max-w-7xl">

            {{-- CABECERA --}}

            <div class="mb-5 flex items-center gap-3">

                <button type="button" @click="backToStructure()"
                    class="shrink-0 rounded-xl border border-slate-800 px-3 py-2 text-xs font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
                    ← Volver a la fase
                </button>

                <div class="min-w-0 flex-1 text-center">
                    <p class="truncate text-[10px] font-black uppercase tracking-[0.25em] text-slate-500"
                        x-text="battle.label"></p>
                </div>

                {{--
                    Tipo de batalla, visible ANTES de jugarla. Saber si son
                    dos fijos o un BO3 cambia como se juega, y antes solo
                    aparecia una vez disputada.
                --}}
                <span class="flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2"
                    :class="battle.series.is_fixed
                        ? 'border-sky-500/40 bg-sky-950/40'
                        : 'border-violet-500/40 bg-violet-950/40'">

                    <span class="text-base"
                        x-text="battle.series.is_fixed ? '⊟' : '⧉'"></span>

                    <span class="text-left">
                        <span class="block text-[9px] font-black uppercase tracking-wider"
                            :class="battle.series.is_fixed ? 'text-sky-400' : 'text-violet-400'"
                            x-text="battle.series.label
                                ? (battle.series.is_fixed ? 'Cantidad fija' : 'Al mejor de')
                                : 'Formato'"></span>

                        <span class="block text-xs font-black text-white"
                            x-text="battle.series.label ?? 'sin definir'"></span>
                    </span>

                </span>

            </div>


            {{-- ============================================ --}}
            {{-- CARA A CARA --}}
            {{-- ============================================ --}}

            <div class="grid items-start gap-4 lg:grid-cols-[1fr_180px_1fr]">

                {{-- IZQUIERDA --}}

                <template x-for="side in [battle.participants[0]]" :key="side ? side.key : 'a'">
                    <div class="text-center lg:text-right">
                        @include('universes.competitions.partials.play.fighter', ['align' => 'right'])
                    </div>
                </template>


                {{-- MARCADOR --}}

                <div class="order-first flex flex-col items-center justify-center lg:order-none">

                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">Serie</p>

                    <p class="mt-1 font-mono text-5xl font-black leading-none text-white">
                        <span x-text="battle.series.score[0]"></span><span
                            class="mx-1 text-slate-700">–</span><span x-text="battle.series.score[1]"></span>
                    </p>

                    <p class="mt-3 max-w-[160px] text-center text-[10px] leading-snug text-slate-500">
                        <template x-if="battle.series.is_fixed">
                            <span>
                                Se juegan los <span class="font-black text-sky-300"
                                    x-text="battle.series.label"></span>.
                                Decide el acumulado.
                            </span>
                        </template>
                        <template x-if="!battle.series.is_fixed && battle.series.wins_required">
                            <span>
                                Gana quien llegue a
                                <span class="font-black text-slate-300" x-text="battle.series.wins_required"></span>.
                            </span>
                        </template>
                        <template x-if="!battle.series.label">
                            <span>Formato sin definir.</span>
                        </template>
                    </p>

                    <p class="mt-2 text-[10px] text-slate-600">
                        <span x-text="battle.series.played"></span> jugados
                        <template x-if="battle.series.remaining !== null">
                            <span> · <span x-text="battle.series.remaining"></span> restantes</span>
                        </template>
                    </p>

                    {{-- Historial previo entre ambos --}}
                    <template x-if="battle.head_to_head">
                        <div class="mt-4 w-full rounded-xl border px-3 py-2.5 text-center"
                            :class="battle.head_to_head.total > 0
                                ? 'border-slate-700 bg-slate-900'
                                : 'border-dashed border-slate-800 bg-slate-900/40'">

                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                Historial entre ambos
                            </p>

                            <template x-if="battle.head_to_head.total === 0">
                                <p class="mt-1.5 text-[10px] font-black leading-tight text-amber-400">
                                    ★ Primera vez que se enfrentan
                                </p>
                            </template>

                            <template x-if="battle.head_to_head.total > 0">
                                <div>
                                    <p class="mt-1 font-mono text-xl font-black text-white">
                                        <span x-text="battle.head_to_head.left_wins"></span><span
                                            class="mx-1 text-slate-700">–</span><span
                                            x-text="battle.head_to_head.right_wins"></span>
                                    </p>

                                    <p class="text-[9px] text-slate-600">
                                        en <span x-text="battle.head_to_head.total"></span> batallas
                                    </p>

                                    <div class="mt-2 space-y-1 border-t border-slate-800 pt-2">
                                        <template x-for="(previous, i) in battle.head_to_head.recent" :key="i">
                                            <p class="truncate text-[9px] leading-tight text-slate-500">
                                                <template x-if="previous.is_draw">
                                                    <span class="font-black text-amber-500">Empate</span>
                                                </template>
                                                <template x-if="!previous.is_draw && previous.winner">
                                                    <span>
                                                        ganó <span class="font-black text-slate-300"
                                                            x-text="previous.winner"></span>
                                                    </span>
                                                </template>
                                                <span class="text-slate-700" x-text="previous.score ? ' ' + previous.score : ''"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                </div>


                {{-- DERECHA --}}

                <template x-for="side in [battle.participants[1]]" :key="side ? side.key : 'b'">
                    <div class="text-center lg:text-left">
                        @include('universes.competitions.partials.play.fighter', ['align' => 'left'])
                    </div>
                </template>

            </div>


            {{-- ============================================ --}}
            {{-- SIMULADOR --}}
            {{-- ============================================ --}}

            <template x-if="hasLiveEncounter">
                <div class="mt-8 rounded-3xl border border-violet-500/40 bg-violet-950/30 p-6">

                    <div class="mb-5 flex items-center justify-between gap-3">

                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-violet-300">
                                Enfrentamiento <span x-text="liveEncounter.number"></span>
                            </p>

                            {{-- Sin esto, unos "2 fijos" que acaban en 3 parecen un BO3 --}}
                            <template x-if="liveEncounter.is_tiebreak">
                                <p class="mt-1 text-[10px] font-black text-amber-400">
                                    ⚡ DESEMPATE · los
                                    <span x-text="battle.series.label"></span>
                                    acabaron igualados y hace falta un ganador
                                </p>
                            </template>
                        </div>

                        <span class="text-lg" x-text="liveEncounter.game.icon"></span>

                    </div>


                    <div class="grid gap-3"
                        :class="liveEncounter.participants.length > 2
                            ? 'sm:grid-cols-2 lg:grid-cols-3'
                            : 'sm:grid-cols-2'">

                        <template x-for="participant in liveEncounter.participants" :key="participant.id">
                            <div class="relative rounded-2xl border p-4 text-center transition duration-300"
                                :class="participant.is_winner
                                    ? 'border-emerald-400 bg-emerald-500/15 shadow-lg shadow-emerald-900/40 ring-2 ring-emerald-400/40'
                                    : (liveEncounter.status === 'RESOLVED'
                                        ? 'border-slate-800 bg-slate-900/40 opacity-60'
                                        : (participant.rolled
                                            ? 'border-slate-700 bg-slate-900'
                                            : 'border-slate-800 bg-slate-900/50'))">

                                <div class="mx-auto h-16 w-16 overflow-hidden rounded-2xl bg-slate-800">
                                    <template x-if="participant.image_url">
                                        <img :src="participant.image_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                </div>

                                <p class="mt-2 truncate text-xs font-black text-white" x-text="participant.name"></p>

                                <p class="text-[10px] text-slate-500" x-text="participant.stats_label"></p>

                                <template x-if="participant.modifiers && participant.modifiers.length">
                                    <div class="mt-1 flex flex-wrap justify-center gap-1">
                                        <template x-for="modifier in participant.modifiers" :key="modifier.label">
                                            <span class="rounded bg-sky-500/25 px-1.5 py-0.5 text-[9px] font-black text-sky-300"
                                                x-text="modifier.label"></span>
                                        </template>
                                    </div>
                                </template>

                                {{--
                                    El valor con el que se compite y, al lado,
                                    de donde salio. En Rounded Number el 3 es
                                    lo que cuenta pero el 2.68 es lo que
                                    explica el 3: un empate entre 2.51 y 3.49
                                    no es el mismo empate que entre 3.0 y 3.0.
                                --}}
                                <div class="mt-3 flex items-baseline justify-center gap-1.5">

                                    <p class="font-mono text-4xl font-black leading-none transition duration-300"
                                        :class="participant.is_winner
                                            ? 'text-emerald-300 drop-shadow-[0_0_12px_rgba(52,211,153,0.5)]'
                                            : (participant.rolled ? 'text-white' : 'text-slate-700')"
                                        x-text="participant.rolled
                                            ? participant.display
                                            : (liveEncounter.controls.pending_label || '?')"></p>

                                    <template x-if="participant.rolled && participant.detail?.raw">
                                        <span class="font-mono text-[11px] font-bold leading-none text-slate-500"
                                            :title="'Generó ' + participant.detail.raw + ' y se redondeó a ' + participant.display"
                                            x-text="'(' + participant.detail.raw + ')'"></span>
                                    </template>

                                </div>

                                <template x-if="participant.is_winner">
                                    <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-400 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-950">
                                        👑 Gana
                                    </p>
                                </template>

                                <template x-if="!participant.rolled
                                    && liveEncounter.status === 'ROLLING'
                                    && liveEncounter.controls.per_participant
                                    && !readonly">
                                    <button type="button" @click="rollOne(participant.id)" :disabled="loading"
                                        class="mt-3 w-full truncate rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400 disabled:opacity-40"
                                        x-text="(liveEncounter.controls.roll_label || 'Generar') + ' ' + participant.name"></button>
                                </template>

                            </div>
                        </template>

                    </div>


                    <div class="mt-5 text-center">

                        <template x-if="liveEncounter.status === 'ROLLING'
                            && liveEncounter.controls.all
                            && !readonly">
                            <button type="button" @click="rollAll()" :disabled="loading"
                                class="rounded-xl border border-slate-600 px-6 py-2.5 text-xs font-black text-slate-200 transition hover:border-slate-400 hover:bg-slate-800 disabled:opacity-40"
                                x-text="liveEncounter.controls.all_label || 'Generar todos'"></button>
                        </template>

                        <template x-if="liveEncounter.status === 'RESOLVED'">
                            <div>
                                <p class="text-base font-black text-white" x-text="liveEncounter.summary"></p>

                                <template x-if="liveEncounter.tiebreaks > 0">
                                    <p class="mt-1 text-[10px] text-amber-400">
                                        Hubo empate: repitieron la tirada
                                        <span x-text="liveEncounter.tiebreaks"></span> vez/veces.
                                    </p>
                                </template>

                                <template x-if="!readonly">
                                    <button type="button" @click="nextEncounter()" :disabled="loading"
                                        class="mt-4 rounded-xl bg-violet-500 px-8 py-3 text-xs font-black text-white transition hover:bg-violet-400 disabled:opacity-40"
                                        x-text="liveEncounter.battle_completed
                                            ? 'Batalla terminada · volver a la fase'
                                            : 'Siguiente enfrentamiento →'"></button>
                                </template>
                            </div>
                        </template>

                    </div>

                </div>
            </template>


            {{-- ============================================ --}}
            {{-- ENFRENTAMIENTOS ANTERIORES --}}
            {{-- ============================================ --}}

            <template x-if="playedEncounters.length">
                <div class="mt-8">

                    <p class="mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">
                        Enfrentamientos de esta batalla
                    </p>

                    <div class="space-y-2">
                        <template x-for="encounter in playedEncounters" :key="encounter.number">
                            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/50 px-4 py-3">

                                <span class="shrink-0 rounded-lg bg-slate-800 px-2 py-1 font-mono text-[10px] font-black text-slate-400">
                                    #<span x-text="encounter.number"></span>
                                </span>

                                <div class="flex min-w-0 flex-1 flex-wrap gap-x-6 gap-y-1">
                                    <template x-for="value in encounter.values" :key="value.key">
                                        <span class="text-xs"
                                            :class="value.is_winner ? 'font-black text-emerald-400' : 'text-slate-400'">
                                            <span x-text="value.name"></span>
                                            <span class="ml-1 font-mono" x-text="value.display"></span>
                                            <template x-if="value.raw">
                                                <span class="ml-0.5 font-mono text-[9px] font-bold text-slate-600"
                                                    x-text="'(' + value.raw + ')'"></span>
                                            </template>
                                        </span>
                                    </template>
                                </div>

                                <template x-if="encounter.is_tiebreak">
                                    <span class="shrink-0 rounded bg-amber-500/20 px-2 py-0.5 text-[9px] font-black text-amber-400">
                                        ⚡ DESEMPATE
                                    </span>
                                </template>

                                <template x-if="encounter.is_draw">
                                    <span class="shrink-0 text-[10px] font-black text-amber-400">Empate</span>
                                </template>

                                <template x-if="!encounter.values.length">
                                    <span class="text-[10px] italic text-slate-600">
                                        Sin detalle (competición anterior al motor de juegos)
                                    </span>
                                </template>

                            </div>
                        </template>
                    </div>


                    {{-- Acumulado: lo que decide en FIXED --}}

                    <template x-if="battle.series.is_fixed">
                        <div class="mt-4 rounded-2xl border border-sky-500/40 bg-sky-950/30 px-6 py-4 text-center">

                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-sky-300">
                                Resultado acumulado
                            </p>

                            <p class="mt-2 flex items-center justify-center gap-3 font-mono text-2xl font-black text-white">
                                <span x-text="battle.participants[0].name" class="text-sm font-black"></span>
                                <span class="text-sky-300" x-text="battle.series.score[0]"></span>
                                <span class="text-slate-700">–</span>
                                <span class="text-sky-300" x-text="battle.series.score[1]"></span>
                                <span x-text="battle.participants[1].name" class="text-sm font-black"></span>
                            </p>

                        </div>
                    </template>

                </div>
            </template>


            {{-- BATALLA TERMINADA --}}

            <template x-if="battle.status === 'COMPLETED'">
                <div class="mt-8 rounded-3xl border border-emerald-500/40 bg-emerald-950/30 p-8 text-center">

                    <template x-for="side in battle.participants.filter(p => p.is_winner)" :key="side.key">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-emerald-400">Ganador</p>
                            <p class="mt-2 text-3xl font-black text-white" x-text="side.name"></p>
                        </div>
                    </template>

                    <p class="mt-3 font-mono text-lg font-black text-slate-400">
                        <span x-text="battle.series.score[0]"></span> – <span x-text="battle.series.score[1]"></span>
                    </p>

                    <button type="button" @click="backToStructure()"
                        class="mt-6 rounded-xl bg-slate-800 px-6 py-3 text-xs font-black text-slate-200 transition hover:bg-slate-700">
                        Volver a la fase
                    </button>

                </div>
            </template>

        </div>
    </template>

</div>

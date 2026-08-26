@php
    /*
     * Zona inferior — jornadas, enfrentamientos y simulación.
     *
     * Toda la programación de la fase. No se genera aquí: el calendario
     * llega del calculador de siempre y esta zona solo pone cara a cada
     * semilla.
     *
     * La simulación es de mentira y no se guarda. Existe porque una tabla a
     * cero no dice nada: hasta que no hay resultados no se ve cómo ordena
     * la cadena de desempate ni a quién se lleva cada puerta de salida.
     * Antes había que montar un torneo entero para averiguarlo.
     *
     * Tres alcances, porque son tres preguntas distintas:
     *
     *   un partido    ¿y si este acaba así?
     *   una jornada   ¿cómo queda la tabla después de esta?
     *   todo          ¿cómo acaba la liga?
     */
@endphp

<section class="shrink-0 border-t border-slate-800 bg-slate-900/60"
    x-data="{ open: true }">

    {{-- BARRA --}}

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-1.5">

        <button type="button" @click="open = !open"
            class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-[0.16em] text-slate-400 transition hover:text-slate-100">
            <span x-text="open ? '▾' : '▸'"></span>
            Jornadas
        </button>

        <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-400"
            x-text="playedRounds.length"></span>

        <span x-show="isTrimmed" x-cloak
            class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
            recortada · <span x-text="droppedRounds.length"></span> fuera
        </span>

        <span class="text-[9px] text-slate-600">
            <span x-text="totalPlayable"></span> enfrentamientos
        </span>


        {{-- SIMULACIÓN GLOBAL --}}

        <div class="ml-auto flex items-center gap-1.5" x-show="open">

            <button type="button" @click="simulateAll()"
                class="rounded-md bg-amber-500 px-2.5 py-1 text-[10px] font-black text-slate-950 transition hover:bg-amber-400">
                ⚡ Simular todo
            </button>

            <button type="button" x-show="hasResults" x-cloak @click="clearResults()"
                class="rounded-md border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-400">
                Limpiar
            </button>

            <select x-model.number="focusedRound"
                class="rounded-md border-slate-700 bg-slate-950 py-0.5 pl-2 pr-6 text-[10px] text-slate-300 focus:border-amber-500 focus:ring-amber-500">
                <option :value="null">Todas</option>
                <template x-for="round in playedRounds" :key="'sel' + round.number">
                    <option :value="round.number" x-text="round.label"></option>
                </template>
            </select>

        </div>

    </div>


    {{-- JORNADAS --}}

    <div x-show="open" x-cloak
        class="arena-scroll max-h-[32vh] overflow-y-auto border-t border-slate-800 px-3 py-2">

        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">

            <template x-for="round in visibleRounds" :key="round.number">
                <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950/50">

                    <div class="flex items-center justify-between gap-2 border-b border-slate-800 bg-slate-900/60 px-2 py-1">

                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-300"
                            x-text="round.label"></span>

                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-[9px] text-slate-600"
                                x-show="cycles > 1" x-text="round.cycle_label"></span>

                            <button type="button" @click="simulateRound(round.number)"
                                class="rounded px-1.5 py-0.5 text-[9px] font-black text-amber-400 transition hover:bg-amber-500/20"
                                title="Simular esta jornada">
                                ⚡
                            </button>
                        </div>

                    </div>

                    <div class="divide-y divide-slate-800/60">

                        <template x-for="(pair, pairIndex) in round.pairings" :key="pairIndex">
                            <div class="flex items-center gap-1.5 px-2 py-1"
                                :class="resultOf(round.number, pairIndex) ? 'bg-slate-900/40' : ''">

                                {{-- Local --}}
                                <div class="flex min-w-0 flex-1 items-center justify-end gap-1">
                                    <span class="truncate text-[10px] font-bold"
                                        :class="(() => {
                                            const r = resultOf(round.number, pairIndex);
                                            if (!r) return 'text-slate-300';
                                            return r.a > r.b ? 'text-emerald-300' : r.a < r.b ? 'text-slate-500' : 'text-slate-300';
                                        })()"
                                        x-text="atSeed(pair.seed_a)?.short"
                                        :title="atSeed(pair.seed_a)?.name"></span>

                                    <div class="h-5 w-5 shrink-0 overflow-hidden rounded bg-slate-800 ring-1"
                                        :class="gateOfSeed(pair.seed_a) ? gateOfSeed(pair.seed_a).color.ring : 'ring-slate-700'">
                                        <template x-if="atSeed(pair.seed_a)?.image_url">
                                            <img :src="atSeed(pair.seed_a).image_url" alt=""
                                                class="h-full w-full object-cover">
                                        </template>
                                    </div>
                                </div>

                                {{-- Marcador o botón --}}
                                <button type="button"
                                    @click="simulateMatch(round.number, pairIndex)"
                                    class="shrink-0 rounded px-1 font-mono text-[10px] font-black transition"
                                    :class="resultOf(round.number, pairIndex)
                                        ? 'text-slate-100 hover:bg-slate-700'
                                        : 'text-slate-600 hover:bg-amber-500/20 hover:text-amber-400'"
                                    :title="resultOf(round.number, pairIndex) ? 'Volver a simular' : 'Simular'">
                                    <template x-if="resultOf(round.number, pairIndex)">
                                        <span>
                                            <span x-text="resultOf(round.number, pairIndex).a"></span>–<span
                                                x-text="resultOf(round.number, pairIndex).b"></span>
                                        </span>
                                    </template>

                                    <template x-if="!resultOf(round.number, pairIndex)">
                                        <span>vs</span>
                                    </template>
                                </button>

                                {{-- Visitante --}}
                                <div class="flex min-w-0 flex-1 items-center gap-1">
                                    <div class="h-5 w-5 shrink-0 overflow-hidden rounded bg-slate-800 ring-1"
                                        :class="gateOfSeed(pair.seed_b) ? gateOfSeed(pair.seed_b).color.ring : 'ring-slate-700'">
                                        <template x-if="atSeed(pair.seed_b)?.image_url">
                                            <img :src="atSeed(pair.seed_b).image_url" alt=""
                                                class="h-full w-full object-cover">
                                        </template>
                                    </div>

                                    <span class="truncate text-[10px] font-bold"
                                        :class="(() => {
                                            const r = resultOf(round.number, pairIndex);
                                            if (!r) return 'text-slate-300';
                                            return r.b > r.a ? 'text-emerald-300' : r.b < r.a ? 'text-slate-500' : 'text-slate-300';
                                        })()"
                                        x-text="atSeed(pair.seed_b)?.short"
                                        :title="atSeed(pair.seed_b)?.name"></span>
                                </div>

                                <span class="w-7 shrink-0 text-right font-mono text-[8px] text-slate-700"
                                    x-text="'#' + String(matchNumber(round.number, pairIndex)).padStart(2, '0')"></span>

                            </div>
                        </template>

                        {{-- Descanso --}}
                        <template x-if="round.rest_seed">
                            <div class="flex items-center gap-1.5 bg-amber-500/5 px-2 py-1">
                                <span class="text-[9px] font-black text-amber-400/70">descansa</span>

                                <div class="h-5 w-5 shrink-0 overflow-hidden rounded bg-slate-800">
                                    <template x-if="atSeed(round.rest_seed)?.image_url">
                                        <img :src="atSeed(round.rest_seed).image_url" alt=""
                                            class="h-full w-full object-cover">
                                    </template>
                                </div>

                                <span class="truncate text-[10px] font-bold text-amber-200/70"
                                    x-text="atSeed(round.rest_seed)?.short"></span>
                            </div>
                        </template>

                    </div>

                </div>
            </template>


            {{-- JORNADAS RECORTADAS --}}

            <template x-for="round in droppedRounds" :key="'drop' + round.number">
                <div class="overflow-hidden rounded-lg border border-dashed border-slate-800 bg-slate-950/20 opacity-40">

                    <div class="flex items-center justify-between gap-2 border-b border-slate-800/60 px-2 py-1">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600"
                            x-text="round.label"></span>

                        <span class="text-[8px] font-black uppercase text-slate-600">no se juega</span>
                    </div>

                    <div class="px-2 py-1">
                        <p class="text-[9px] text-slate-700">
                            <span x-text="round.pairings.length"></span> enfrentamientos fuera del recorte
                        </p>
                    </div>

                </div>
            </template>

        </div>

        <template x-if="structure.has_more_rounds">
            <p class="mt-2 text-center text-[9px] text-slate-600">
                Se dibujan <span x-text="rounds.length"></span> de
                <span x-text="structure.total_rounds"></span> jornadas.
                El resto sigue el mismo patrón.
            </p>
        </template>

        <template x-if="rounds.length === 0">
            <p class="py-6 text-center text-[10px] text-slate-600">
                Sin estructura que mostrar: revisa la configuración.
            </p>
        </template>

    </div>

</section>

@php
    /*
     * Zona inferior — jornadas y simulación.
     *
     * Se organiza por JORNADA y dentro por grupo, no al revés: todos los
     * grupos juegan su jornada 1 el mismo día, luego la 2. Agrupar primero
     * por grupo enseñaría el torneo en un orden que no ocurre.
     *
     * Cuatro alcances de simulación, porque son cuatro preguntas distintas:
     *
     *   un partido        ¿y si este acaba así?
     *   una jornada       ¿cómo quedan las tablas después de esta?
     *   un grupo entero   ¿cómo acaba este grupo?
     *   todo              ¿cómo acaba la fase?
     *
     * Nada de esto se guarda: son marcadores inventados para poder ver cómo
     * se llenan las tablas y a quién se lleva cada salida.
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
            x-text="roundNumbers.length"></span>

        <span x-show="isTrimmed" x-cloak
            class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
            recortada
        </span>

        <span class="text-[9px] text-slate-600">
            <span x-text="playedCount"></span>/<span x-text="totalPlayable"></span> simulados
        </span>


        <div class="ml-auto flex flex-wrap items-center gap-1.5" x-show="open">

            {{-- Filtro por grupo --}}
            <select x-model.number="focusedGroup"
                class="rounded-md border-slate-700 bg-slate-950 py-0.5 pl-2 pr-6 text-[10px] text-slate-300 focus:border-amber-500 focus:ring-amber-500">
                <option :value="null">Todos los grupos</option>
                <template x-for="g in groups" :key="'fg' + g.index">
                    <option :value="g.index" x-text="g.name"></option>
                </template>
            </select>

            {{-- Filtro por jornada --}}
            <select x-model.number="focusedRound"
                class="rounded-md border-slate-700 bg-slate-950 py-0.5 pl-2 pr-6 text-[10px] text-slate-300 focus:border-amber-500 focus:ring-amber-500">
                <option :value="null">Todas</option>
                <template x-for="n in roundNumbers" :key="'fr' + n">
                    <option :value="n" x-text="'Jornada ' + n"></option>
                </template>
            </select>

            <button type="button" @click="simulateAll()"
                class="rounded-md bg-amber-500 px-2.5 py-1 text-[10px] font-black text-slate-950 transition hover:bg-amber-400">
                ⚡ Simular todo
            </button>

            <button type="button" x-show="hasResults" x-cloak @click="clearResults()"
                class="rounded-md border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-400">
                Limpiar
            </button>

        </div>

    </div>


    {{-- JORNADAS --}}

    <div x-show="open" x-cloak
        class="arena-scroll max-h-[34vh] overflow-y-auto border-t border-slate-800 px-3 py-2">

        <div class="space-y-2">

            <template x-for="number in visibleRoundNumbers" :key="'rn' + number">
                <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950/40">

                    {{-- Cabecera de la jornada --}}

                    <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-900/60 px-2 py-1">

                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-300"
                            x-text="'Jornada ' + number"></span>

                        <span class="font-mono text-[8px] text-slate-600"
                            x-text="groups.filter(g => roundOf(g, number)).length + ' grupos'"></span>

                        <button type="button" @click="simulateRound(number)"
                            class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black text-amber-400 transition hover:bg-amber-500/20"
                            title="Simular esta jornada en todos los grupos">
                            ⚡ jornada
                        </button>

                    </div>

                    {{-- Los grupos que juegan esa jornada --}}

                    <div class="grid gap-px bg-slate-800/50 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">

                        <template x-for="group in visibleGroups" :key="'rg' + number + '-' + group.index">
                            <template x-if="roundOf(group, number)">
                                <div class="bg-slate-950/60 p-1.5">

                                    <div class="mb-1 flex items-center gap-1">
                                        <span class="h-2.5 w-1 rounded-full" :class="group.color.dot"></span>

                                        <span class="min-w-0 flex-1 truncate text-[9px] font-black"
                                            :class="group.color.text"
                                            x-text="group.name"></span>

                                        <button type="button" @click="simulateGroupRound(group, number)"
                                            class="rounded px-1 text-[9px] transition hover:bg-amber-500/20"
                                            :class="group.color.text"
                                            title="Simular esta jornada de este grupo">⚡</button>
                                    </div>

                                    <div class="space-y-px">

                                        <template x-for="(pair, pairIndex) in roundOf(group, number).pairings"
                                            :key="'p' + group.index + '-' + number + '-' + pairIndex">

                                            <div class="flex items-center gap-1 rounded px-1 py-0.5"
                                                :class="resultOf(group.index, number, pairIndex) ? 'bg-slate-900/60' : ''">

                                                {{-- Local --}}
                                                <div class="flex min-w-0 flex-1 items-center justify-end gap-1">
                                                    <span class="truncate text-[9px] font-bold"
                                                        :class="(() => {
                                                            const r = resultOf(group.index, number, pairIndex);
                                                            if (!r) return 'text-slate-400';
                                                            return r.a > r.b ? 'text-emerald-300' : r.a < r.b ? 'text-slate-600' : 'text-slate-400';
                                                        })()"
                                                        x-text="atSeed(pair.seed_a)?.short"
                                                        :title="atSeed(pair.seed_a)?.name"></span>

                                                    <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm bg-slate-800 ring-1"
                                                        :class="group.color.ring">
                                                        <template x-if="atSeed(pair.seed_a)?.image_url">
                                                            <img :src="atSeed(pair.seed_a).image_url" alt=""
                                                                class="h-full w-full object-cover">
                                                        </template>
                                                    </span>
                                                </div>

                                                {{-- Marcador / botón --}}
                                                <button type="button"
                                                    @click="simulateMatch(group.index, number, pairIndex)"
                                                    class="shrink-0 rounded px-1 font-mono text-[9px] font-black transition"
                                                    :class="resultOf(group.index, number, pairIndex)
                                                        ? 'text-slate-100 hover:bg-slate-700'
                                                        : 'text-slate-700 hover:bg-amber-500/20 hover:text-amber-400'"
                                                    :title="resultOf(group.index, number, pairIndex) ? 'Volver a simular' : 'Simular'">

                                                    <template x-if="resultOf(group.index, number, pairIndex)">
                                                        <span>
                                                            <span x-text="resultOf(group.index, number, pairIndex).a"></span>–<span
                                                                x-text="resultOf(group.index, number, pairIndex).b"></span>
                                                        </span>
                                                    </template>

                                                    <template x-if="!resultOf(group.index, number, pairIndex)">
                                                        <span>vs</span>
                                                    </template>
                                                </button>

                                                {{-- Visitante --}}
                                                <div class="flex min-w-0 flex-1 items-center gap-1">
                                                    <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm bg-slate-800 ring-1"
                                                        :class="group.color.ring">
                                                        <template x-if="atSeed(pair.seed_b)?.image_url">
                                                            <img :src="atSeed(pair.seed_b).image_url" alt=""
                                                                class="h-full w-full object-cover">
                                                        </template>
                                                    </span>

                                                    <span class="truncate text-[9px] font-bold"
                                                        :class="(() => {
                                                            const r = resultOf(group.index, number, pairIndex);
                                                            if (!r) return 'text-slate-400';
                                                            return r.b > r.a ? 'text-emerald-300' : r.b < r.a ? 'text-slate-600' : 'text-slate-400';
                                                        })()"
                                                        x-text="atSeed(pair.seed_b)?.short"
                                                        :title="atSeed(pair.seed_b)?.name"></span>
                                                </div>

                                            </div>
                                        </template>

                                        {{-- Descanso --}}
                                        <template x-if="roundOf(group, number).rest_seed">
                                            <div class="flex items-center gap-1 rounded bg-amber-500/5 px-1 py-0.5">
                                                <span class="text-[8px] font-black text-amber-400/70">descansa</span>

                                                <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm bg-slate-800">
                                                    <template x-if="atSeed(roundOf(group, number).rest_seed)?.image_url">
                                                        <img :src="atSeed(roundOf(group, number).rest_seed).image_url"
                                                            alt="" class="h-full w-full object-cover">
                                                    </template>
                                                </span>

                                                <span class="truncate text-[9px] font-bold text-amber-200/70"
                                                    x-text="atSeed(roundOf(group, number).rest_seed)?.short"></span>
                                            </div>
                                        </template>

                                    </div>

                                </div>
                            </template>
                        </template>

                    </div>

                </div>
            </template>

            <template x-if="roundNumbers.length === 0">
                <p class="py-6 text-center text-[10px] text-slate-600">
                    Sin estructura que mostrar: revisa la configuración.
                </p>
            </template>

        </div>

    </div>

</section>

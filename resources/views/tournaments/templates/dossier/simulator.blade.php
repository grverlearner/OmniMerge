@php
    /*
     * EL SIMULADOR — qué pasaría si se jugara.
     *
     * Mete N participantes por las entradas y los deja recorrer el grafo
     * entero: cada fase reparte por sus salidas, cada ruta lleva a los que
     * le tocan, y al final cada uno acaba en un final o se pierde.
     *
     * No hay motor nuevo. Lo ejecuta TournamentFlowPreviewService en el
     * servidor, que ya hacía exactamente esto; aquí solo se pide y se pinta.
     *
     * Lo que de verdad importa de una simulación no es quién gana —los
     * resultados son aleatorios— sino si el RECORRIDO cuadra:
     *
     *   · ¿llegan todos a algún sitio, o se pierde gente por el camino?
     *   · ¿alguna fase se queda sin nadie?
     *   · ¿algún final se queda vacío?
     *
     * Por eso los perdidos salen en rojo y grandes: son el síntoma de que
     * hay un agujero en el recorrido, y es la razón principal de que este
     * simulador exista.
     *
     * Nada de esto se guarda. Los participantes son sintéticos.
     */
@endphp

<section class="mb-4">

    <div class="mb-2 flex items-center gap-2">
        <span class="h-3 w-1 rounded-full bg-amber-500"></span>
        <h2 class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
            El simulador
        </h2>
        <span class="text-[10px] text-slate-600">— qué pasaría si se jugara hoy</span>
    </div>


    <div class="overflow-hidden rounded-2xl border border-amber-500/25 bg-slate-900/40">

        {{-- ============ LOS MANDOS ============ --}}

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/50 px-3 py-2">

            <label class="flex items-center gap-1.5">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Entran
                </span>

                <input type="number" min="2" max="512" x-model.number="participants"
                    class="w-16 rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-center text-[11px] font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

                <span class="text-[9px] text-slate-600">por cada entrada</span>
            </label>

            <button type="button" @click="simulate()" :disabled="running"
                class="rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">
                <span x-show="!running">⚡ Simular el torneo</span>
                <span x-show="running" x-cloak>Simulando…</span>
            </button>

            <button type="button" @click="clear()" x-show="hasResult || blocked.length" x-cloak
                class="rounded-lg border border-slate-700 px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-400">
                Limpiar
            </button>

            <p class="ml-auto text-[9px] leading-relaxed text-slate-600">
                Participantes de mentira. Nada de esto se guarda.
            </p>

        </div>


        {{-- ============ NO SE PUEDE SIMULAR ============ --}}

        <template x-if="blocked.length">
            <div class="border-b border-slate-800 px-3 py-3">
                <p class="text-[11px] font-black text-rose-300">No se puede simular todavía</p>

                <div class="mt-1 space-y-1">
                    <template x-for="(mensaje, i) in blocked" :key="'bk' + i">
                        <p class="flex items-start gap-1.5 text-[10px] leading-relaxed text-rose-200/80">
                            <span class="shrink-0">✕</span>
                            <span x-text="mensaje"></span>
                        </p>
                    </template>
                </div>

                <p class="mt-2 text-[9px] leading-relaxed text-slate-500">
                    Simular un recorrido roto daría un resultado inventado. Arregla
                    los problemas del recorrido y vuelve a intentarlo.
                </p>
            </div>
        </template>


        {{-- ============ SIN SIMULAR TODAVÍA ============ --}}

        <template x-if="!hasResult && !blocked.length">
            <div class="px-3 py-8 text-center">
                <p class="text-[11px] font-black text-slate-400">Todavía no se ha simulado nada</p>
                <p class="mx-auto mt-1 max-w-md text-[10px] leading-relaxed text-slate-600">
                    Al simular, cada participante recorre el torneo entero: entra por una
                    puerta, juega cada fase, y acaba en un final. Sirve para ver si el
                    recorrido cuadra antes de montarlo de verdad.
                </p>
            </div>
        </template>


        {{-- ============ EL RESULTADO ============ --}}

        <template x-if="hasResult">
            <div>

                {{-- Las cifras que importan --}}

                <div class="grid gap-1.5 border-b border-slate-800 p-3 sm:grid-cols-2 lg:grid-cols-5">

                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">Entraron</p>
                        <p class="font-mono text-2xl font-black text-slate-100"
                            x-text="summary.initial_unique ?? 0"></p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">Llegaron al final</p>
                        <p class="font-mono text-2xl font-black text-emerald-300"
                            x-text="summary.terminal_unique ?? 0"></p>
                    </div>

                    {{--
                        Los perdidos van destacados: son el síntoma de que el
                        recorrido tiene un agujero, y es lo único de aquí que
                        obliga a hacer algo.
                    --}}
                    <div class="rounded-xl border px-3 py-2"
                        :class="(summary.lost_unique ?? 0) > 0
                            ? 'border-rose-500/50 bg-rose-500/10'
                            : 'border-slate-800 bg-slate-950/60'">
                        <p class="text-[8px] font-black uppercase tracking-wider"
                            :class="(summary.lost_unique ?? 0) > 0 ? 'text-rose-300' : 'text-slate-500'">
                            Se perdieron
                        </p>
                        <p class="font-mono text-2xl font-black"
                            :class="(summary.lost_unique ?? 0) > 0 ? 'text-rose-300' : 'text-slate-600'"
                            x-text="summary.lost_unique ?? 0"></p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">Fases jugadas</p>
                        <p class="font-mono text-2xl font-black text-sky-300"
                            x-text="summary.nodes_processed ?? 0"></p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">Duplicados</p>
                        <p class="font-mono text-2xl font-black"
                            :class="(summary.duplicated_unique ?? 0) > 0 ? 'text-amber-300' : 'text-slate-600'"
                            x-text="summary.duplicated_unique ?? 0"></p>
                    </div>

                </div>


                {{-- Lo que el propio motor señala --}}

                <template x-if="runErrors.length || runWarnings.length">
                    <div class="grid gap-1 border-b border-slate-800 px-3 py-2 lg:grid-cols-2">

                        <template x-for="(problema, i) in runErrors" :key="'re' + i">
                            <p class="flex items-start gap-1.5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-2 py-1">
                                <span class="shrink-0 text-[10px] text-rose-400">✕</span>
                                <span class="text-[9px] leading-relaxed text-rose-200" x-text="problema.message"></span>
                            </p>
                        </template>

                        <template x-for="(problema, i) in runWarnings" :key="'rw' + i">
                            <p class="flex items-start gap-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-2 py-1">
                                <span class="shrink-0 text-[10px] text-amber-400">!</span>
                                <span class="text-[9px] leading-relaxed text-amber-200" x-text="problema.message"></span>
                            </p>
                        </template>

                    </div>
                </template>


                <div class="grid gap-2 p-3 xl:grid-cols-[1.4fr_1fr]">

                    {{-- ======== QUIÉN ACABÓ DÓNDE ======== --}}

                    <div>

                        <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Dónde acabó cada uno
                        </p>

                        <div class="space-y-1.5">

                            <template x-for="terminal in runTerminals" :key="'rt' + terminal.id">
                                <div class="rounded-xl border p-2"
                                    :class="terminal.count > 0
                                        ? 'border-rose-400/40 bg-rose-500/5'
                                        : 'border-dashed border-slate-700'">

                                    <div class="flex items-center gap-1.5">
                                        <span class="h-3.5 w-1 shrink-0 rounded-full bg-rose-400"></span>

                                        <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-100"
                                            x-text="terminal.name"></span>

                                        <span class="shrink-0 rounded bg-slate-950/70 px-1.5 py-0.5 text-[9px] font-black text-rose-300"
                                            x-text="terminal.type"></span>

                                        <span class="shrink-0 font-mono text-sm font-black"
                                            :class="terminal.count > 0 ? 'text-rose-300' : 'text-slate-600'"
                                            x-text="terminal.count"></span>
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap gap-1" x-show="terminal.count > 0">
                                        <template x-for="p in terminal.participants" :key="'rtp' + terminal.id + p.preview_id">
                                            <button type="button" @click="track(p)"
                                                class="flex items-center gap-1 rounded px-1.5 py-0.5 text-[9px] font-bold transition"
                                                :class="isTracked(p)
                                                    ? 'bg-amber-500 text-slate-950'
                                                    : 'bg-slate-800 text-slate-300 hover:bg-slate-700'">
                                                <span class="h-3.5 w-3.5 overflow-hidden rounded-sm bg-slate-900">
                                                    <template x-if="p.image_url">
                                                        <img :src="p.image_url" alt="" class="h-full w-full object-cover">
                                                    </template>
                                                </span>
                                                <span x-text="p.name"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <p class="mt-1 text-[9px] text-slate-600" x-show="terminal.count === 0">
                                        No llegó nadie hasta aquí.
                                    </p>
                                </div>
                            </template>

                        </div>


                        {{-- Los que se cayeron por el camino --}}

                        <template x-if="lostIds.length">
                            <div class="mt-2 rounded-xl border border-rose-500/50 bg-rose-500/10 p-2">
                                <p class="text-[10px] font-black text-rose-300">
                                    <span x-text="lostIds.length"></span>
                                    se quedaron por el camino
                                </p>

                                <p class="mt-0.5 text-[9px] leading-relaxed text-rose-200/70">
                                    Salieron de una fase por una salida que no lleva a ningún
                                    sitio. Es un agujero del recorrido, no del azar.
                                </p>

                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    <template x-for="p in participantsRun.filter(x => isLost(x))"
                                        :key="'lost' + p.preview_id">
                                        <button type="button" @click="track(p)"
                                            class="rounded px-1.5 py-0.5 text-[9px] font-bold transition"
                                            :class="isTracked(p)
                                                ? 'bg-amber-500 text-slate-950'
                                                : 'bg-rose-500/20 text-rose-200 hover:bg-rose-500/30'"
                                            x-text="p.name"></button>
                                    </template>
                                </div>
                            </div>
                        </template>

                    </div>


                    {{-- ======== LO QUE FUE PASANDO ======== --}}

                    <div>

                        <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Lo que fue pasando
                        </p>

                        <div class="arena-scroll max-h-[340px] space-y-1 overflow-y-auto rounded-xl border border-slate-800 bg-slate-950/40 p-2">

                            <template x-for="(evento, i) in timeline" :key="'tl' + i">
                                <p class="flex items-start gap-1.5">
                                    <span class="shrink-0 font-mono text-[9px]"
                                        :class="{
                                            SUCCESS: 'text-emerald-400',
                                            INFO: 'text-slate-500',
                                            WARNING: 'text-amber-400',
                                            ERROR: 'text-rose-400',
                                        }[evento.level] ?? 'text-slate-500'"
                                        x-text="{
                                            SUCCESS: '✓',
                                            INFO: '·',
                                            WARNING: '!',
                                            ERROR: '✕',
                                        }[evento.level] ?? '·'"></span>

                                    <span class="text-[9px] leading-relaxed text-slate-400"
                                        x-text="evento.message"></span>
                                </p>
                            </template>

                            <template x-if="timeline.length === 0">
                                <p class="py-4 text-center text-[9px] text-slate-600">
                                    Sin eventos que contar.
                                </p>
                            </template>

                        </div>


                        {{-- El viaje del que estás siguiendo --}}

                        <template x-if="tracking">
                            <div class="mt-2 rounded-xl border border-amber-500/40 bg-amber-500/5 p-2">

                                <div class="flex items-center gap-1.5">
                                    <span class="h-5 w-5 overflow-hidden rounded bg-slate-800">
                                        <template x-if="tracking.image_url">
                                            <img :src="tracking.image_url" alt="" class="h-full w-full object-cover">
                                        </template>
                                    </span>

                                    <span class="text-[10px] font-black text-amber-200" x-text="tracking.name"></span>

                                    <span class="ml-auto font-mono text-[9px] text-amber-300/60"
                                        x-text="'salió en el puesto ' + tracking.initial_position"></span>
                                </div>

                                <div class="mt-1.5 space-y-0.5">
                                    <template x-for="(paso, i) in trackedJourney" :key="'jn' + i">
                                        <p class="flex items-center gap-1.5 text-[9px]">
                                            <span class="w-4 shrink-0 text-right font-mono text-amber-400/60"
                                                x-text="(i + 1)"></span>
                                            <span class="rounded bg-slate-950/70 px-1 py-0.5 font-mono text-[8px] uppercase text-slate-500"
                                                x-text="{
                                                    START: 'entra',
                                                    CONNECTION: 'ruta',
                                                    NODE: 'fase',
                                                    TERMINAL: 'final',
                                                }[paso.type] ?? paso.type"></span>
                                            <span class="truncate text-slate-300" x-text="paso.name ?? paso.code"></span>
                                        </p>
                                    </template>
                                </div>

                                <p class="mt-1.5 text-[9px] text-amber-300/60">
                                    En el recorrido de arriba se marca por dónde pasó.
                                </p>
                            </div>
                        </template>

                    </div>

                </div>

            </div>
        </template>

    </div>

</section>

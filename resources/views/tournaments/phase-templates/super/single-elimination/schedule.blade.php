@php
    /*
     * Zona inferior — todos los enfrentamientos, en lista.
     *
     * El árbol de arriba enseña la FORMA del cuadro; esta lista enseña el
     * ORDEN en que se juega, que es otra pregunta. Con 32 participantes el
     * árbol no cabe entero en pantalla y aquí sí se puede repasar todo.
     *
     * Simular desde aquí es lo mismo que simular arriba: el resultado va al
     * mismo sitio y el árbol se recoloca solo.
     */
@endphp

{{--
    Abierto o cerrado, se recuerda.

    Cualquier formulario del editor -crear una puerta, guardar la
    configuracion- recarga la pagina entera, y un `x-data` arranca de cero
    en cada carga: el panel volvia a desplegarse solo aunque acabaras de
    cerrarlo. Se guarda en el navegador de quien mira, que es donde vive una
    preferencia de este tipo: no es dato de la fase y no tiene por que
    viajar a la base de datos ni ser igual para todo el mundo.

    Todo entre try/catch porque en una ventana privada leer localStorage
    puede lanzar, y quedarse sin recordar la preferencia es aceptable pero
    romper el panel no.
--}}

<section class="shrink-0 border-t border-slate-800 bg-slate-900/60"
    x-data="{
        open: true,

        init() {
            try {
                const saved = localStorage.getItem('omnimerge.super.schedule.open');

                if (saved !== null) this.open = saved === '1';
            } catch (e) {
                /* Sin almacenamiento: se queda como estaba */
            }
        },

        toggle() {
            this.open = !this.open;

            try {
                localStorage.setItem('omnimerge.super.schedule.open', this.open ? '1' : '0');
            } catch (e) {
                /* Igual: se pliega, solo que no se recuerda */
            }
        },
    }">

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-1.5">

        <button type="button" @click="toggle()"
            class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-[0.16em] text-slate-400 transition hover:text-slate-100">
            <span x-text="open ? '▾' : '▸'"></span>
            Enfrentamientos
        </button>

        <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-400"
            x-text="totalPlayable"></span>

        <span class="text-[9px] text-slate-600">
            <span x-text="playedCount"></span> jugados
        </span>

        <template x-if="byeCount > 0">
            <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
                <span x-text="byeCount"></span> pasan directo
            </span>
        </template>

        <div class="ml-auto flex items-center gap-1.5" x-show="open">

            <select x-model.number="focusedRound"
                class="rounded-md border-slate-700 bg-slate-950 py-0.5 pl-2 pr-6 text-[10px] text-slate-300 focus:border-amber-500 focus:ring-amber-500">
                <option :value="null">Todas las rondas</option>
                <template x-for="round in rounds" :key="'fr' + round.number">
                    <option :value="round.number" x-text="round.label"></option>
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


    <div x-show="open" x-cloak
        class="arena-scroll max-h-[30vh] overflow-y-auto border-t border-slate-800 px-3 py-2">

        <div class="space-y-2">

            <template x-for="round in rounds.filter(r => focusedRound === null || r.number === focusedRound)"
                :key="'sr' + round.number">
                <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-950/40">

                    <div class="flex items-center gap-2 border-b border-slate-800 px-2 py-1"
                        :class="round.color.soft">

                        <span class="h-2.5 w-1 rounded-full" :class="round.color.dot"></span>

                        <span class="text-[9px] font-black uppercase tracking-wider"
                            :class="round.color.text" x-text="round.label"></span>

                        <span class="font-mono text-[8px] text-slate-600"
                            x-text="round.matches.length + ' duelos'"></span>

                        <button type="button" @click="simulateRound(round)"
                            class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black transition hover:bg-amber-500/20"
                            :class="round.color.text">⚡ ronda</button>

                    </div>

                    <div class="grid gap-px bg-slate-800/50 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">

                        <template x-for="match in round.matches" :key="'sm' + match.index">
                            <div class="flex items-center gap-1 bg-slate-950/60 px-2 py-1">

                                <span class="w-6 shrink-0 font-mono text-[8px] text-slate-700"
                                    x-text="'#' + String(match.index + 1).padStart(2, '0')"></span>

                                {{-- Local --}}
                                <span class="flex min-w-0 flex-1 items-center justify-end gap-1">
                                    <span class="truncate text-[9px] font-bold"
                                        :class="(() => {
                                            if (match.a.type === 'BYE') return 'text-slate-700 italic';
                                            if (!occupant(match.a)) return 'text-slate-700';
                                            const d = decisionOf(match);
                                            if (!d) return 'text-slate-400';
                                            return d === 'a' ? 'text-emerald-300' : 'text-slate-600';
                                        })()"
                                        x-text="match.a.type === 'BYE' ? 'nadie' : (occupant(match.a)?.short ?? '· · ·')"></span>

                                    <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm bg-slate-800">
                                        <template x-if="occupant(match.a)?.image_url">
                                            <img :src="occupant(match.a).image_url" alt=""
                                                class="h-full w-full object-cover">
                                        </template>
                                    </span>
                                </span>

                                <span class="shrink-0 font-mono text-[8px] text-slate-700">vs</span>

                                {{-- Visitante --}}
                                <span class="flex min-w-0 flex-1 items-center gap-1">
                                    <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm bg-slate-800">
                                        <template x-if="occupant(match.b)?.image_url">
                                            <img :src="occupant(match.b).image_url" alt=""
                                                class="h-full w-full object-cover">
                                        </template>
                                    </span>

                                    <span class="truncate text-[9px] font-bold"
                                        :class="(() => {
                                            if (match.b.type === 'BYE') return 'text-slate-700 italic';
                                            if (!occupant(match.b)) return 'text-slate-700';
                                            const d = decisionOf(match);
                                            if (!d) return 'text-slate-400';
                                            return d === 'b' ? 'text-emerald-300' : 'text-slate-600';
                                        })()"
                                        x-text="match.b.type === 'BYE' ? 'nadie' : (occupant(match.b)?.short ?? '· · ·')"></span>
                                </span>

                                <button type="button" @click="simulateMatch(match)"
                                    :disabled="!isPlayable(match) || isBye(match)"
                                    class="shrink-0 rounded px-1 text-[9px] font-black transition"
                                    :class="isBye(match)
                                        ? 'text-amber-500/50'
                                        : (!isPlayable(match)
                                            ? 'cursor-not-allowed text-slate-700'
                                            : 'text-amber-400 hover:bg-amber-500/20')"
                                    :title="isBye(match) ? 'Pasa directo, no se juega' : ''"
                                    x-text="isBye(match) ? '→' : '⚡'"></button>

                            </div>
                        </template>

                    </div>

                </div>
            </template>


            {{--
                Los duelos de clasificación van aparte: no pertenecen a
                ninguna ronda del cuadro. Se juegan para separar a los que el
                cuadro dejó empatados, y cada uno dice qué puesto reparte.
            --}}

            <template x-for="bracket in placementBrackets" :key="'sp' + bracket.key">
                <div x-show="focusedRound === null"
                    class="overflow-hidden rounded-lg border border-orange-400/40 bg-slate-950/40">

                    <div class="flex items-center gap-2 border-b border-slate-800 bg-orange-500/10 px-2 py-1">
                        <span class="text-[9px]">🎖</span>

                        <span class="text-[9px] font-black uppercase tracking-wider text-orange-300"
                            x-text="bracket.label"></span>

                        <span class="font-mono text-[8px] text-slate-600"
                            x-text="'puestos ' + bracket.from + '–' + bracket.to"></span>

                        <button type="button" @click="simulateBracket(bracket)"
                            class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black text-orange-300 transition hover:bg-amber-500/20">⚡</button>
                    </div>

                    <template x-for="round in bracket.rounds" :key="'spr' + bracket.key + round.number">
                        <div class="grid gap-px bg-slate-800/50 sm:grid-cols-2 xl:grid-cols-3">

                            <template x-for="match in round.matches" :key="'spm' + match.index">
                                <div class="flex items-center gap-1 bg-slate-950/60 px-2 py-1">

                                    <span class="min-w-0 flex-1 truncate text-right text-[9px] font-bold"
                                        :class="decisionOf(match) === 'a' ? 'text-emerald-300' : 'text-slate-400'"
                                        x-text="match.a.type === 'BYE'
                                            ? 'nadie'
                                            : (occupant(match.a)?.short ?? '· · ·')"></span>

                                    <span class="font-mono text-[8px] text-slate-700">vs</span>

                                    <span class="min-w-0 flex-1 truncate text-[9px] font-bold"
                                        :class="decisionOf(match) === 'b' ? 'text-emerald-300' : 'text-slate-400'"
                                        x-text="match.b.type === 'BYE'
                                            ? 'nadie'
                                            : (occupant(match.b)?.short ?? '· · ·')"></span>

                                    <span class="shrink-0 font-mono text-[8px] text-slate-600"
                                        x-text="match.awards
                                            ? (match.awards.win + 'º/' + match.awards.lose + 'º')
                                            : ''"></span>

                                    <button type="button" @click="simulateMatch(match)"
                                        :disabled="!isPlayable(match) || isBye(match)"
                                        class="shrink-0 rounded px-1 text-[9px] font-black transition"
                                        :class="!isPlayable(match) || isBye(match)
                                            ? 'cursor-not-allowed text-slate-700'
                                            : 'text-amber-400 hover:bg-amber-500/20'"
                                        x-text="isBye(match) ? '→' : '⚡'"></button>

                                </div>
                            </template>

                        </div>
                    </template>

                </div>
            </template>

            <template x-if="rounds.length === 0">
                <p class="py-6 text-center text-[10px] text-slate-600">
                    Sin cuadro que mostrar: revisa la configuración.
                </p>
            </template>

        </div>

    </div>

</section>

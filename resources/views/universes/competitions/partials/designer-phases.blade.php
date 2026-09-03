@php
    /*
     * 05 · FASE POR FASE — las excepciones.
     *
     * Aquí no se configura la FORMA de una fase —cuántas rondas tiene un
     * cuadro o cómo se cruzan sus puestos es de la plantilla, y no cambia
     * entre ediciones—. Aquí se configura lo que sí cambia: con qué juego
     * se juega esa fase concreta y cómo se pelea dentro.
     *
     * Cada fase se ve plegada, diciendo qué le aplica de verdad. Solo se
     * abre la que se está tocando: veinte fases con siete campos cada una
     * es un muro, no una pantalla.
     *
     * Un campo vacío significa «lo que diga la competición». Es el caso
     * normal, y por eso es el valor por defecto: rellenarlos todos con lo
     * heredado haría que cambiar el general dejase de afectar a nadie.
     */
@endphp

<section x-show="isOpen('phases')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-cyan-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-cyan-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">05</span>
        <span class="text-[11px]">⧉</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-cyan-300">Fase por fase</h2>
        <span class="ml-auto text-[10px] text-slate-600">Lo que cambia dentro del recorrido</span>
    </div>

    <div class="p-4">

        {{--
            Aunque todas las fases se jueguen igual, este bloque sigue
            sirviendo: los PREMIOS por fase no dependen de ningún permiso
            del torneo. Por eso el aviso ya no sustituye a la lista, sino
            que la acompaña.
        --}}

        <template x-if="gameScope !== 'PHASE' && battleScope !== 'PHASE'">
            <p class="mb-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-[10px] leading-relaxed text-slate-500">
                Todas las fases se juegan igual, así que aquí solo puedes repartir
                premios. Para que además peleen distinto, marca
                <span class="font-bold text-emerald-300">«cada fase el suyo»</span> en el juego
                o <span class="font-bold text-amber-300">«cada fase la suya»</span> en la batalla.
                @if (! $inherited['allow_phase_game'] && ! $inherited['allow_phase_battle'])
                    Ese permiso lo da el torneo, y ahora mismo está cerrado.
                @endif
            </p>
        </template>

        <template x-if="true">
            <div class="space-y-1.5">

                <template x-if="templatePhases.length === 0">
                    <p class="rounded-xl border border-dashed border-slate-700 px-3 py-4 text-center text-[10px] text-slate-600">
                        La forma elegida no tiene ninguna fase todavía.
                    </p>
                </template>

                <template x-for="ph in templatePhases" :key="'cfg' + ph.id">
                    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50">

                        {{-- La fila resumida --}}

                        <div class="flex items-center gap-2 px-3 py-2">

                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 font-mono text-[9px] font-black text-cyan-300"
                                x-text="'L' + ph.level"></span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-1.5">
                                    <span class="truncate text-[11px] font-black text-slate-200" x-text="ph.name"></span>

                                    <template x-if="phaseOverrides(ph.id).length">
                                        <span class="shrink-0 rounded bg-cyan-500/20 px-1.5 py-0.5 text-[8px] font-black text-cyan-300">
                                            excepción
                                        </span>
                                    </template>
                                </span>

                                <span class="flex flex-wrap items-center gap-1.5 text-[9px]">
                                    <span class="text-slate-600" x-text="ph.shape"></span>
                                    <span class="text-slate-800">·</span>
                                    <span :class="planFor(ph.id).gameFrom === 'PHASE' ? 'text-emerald-300' : 'text-slate-500'"
                                        x-text="planFor(ph.id).game?.name ?? '—'"></span>
                                    <span class="text-slate-800">·</span>
                                    <span :class="planFor(ph.id).battleFrom === 'PHASE' ? 'text-amber-300' : 'text-slate-500'"
                                        x-text="planFor(ph.id).label"></span>

                                    <span class="text-slate-800">·</span>
                                    <span :class="phasePrizes(ph.id).length ? 'text-violet-300' : 'text-slate-600'"
                                        x-text="'🏆 ' + phasePrizeText(ph.id)"></span>
                                </span>
                            </span>

                            <button type="button" @click="openPhase = openPhase === ph.id ? null : ph.id"
                                class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-cyan-500 hover:text-cyan-300"
                                x-text="openPhase === ph.id ? 'listo' : '✎ ajustar'"></button>
                        </div>


                        {{--
                            El detalle. x-show y no x-if: los campos tienen
                            que seguir viajando en el envío aunque estén
                            plegados, o ajustar una fase y plegarla la
                            borraría.
                        --}}

                        <div x-show="openPhase === ph.id" x-cloak
                            class="space-y-2 border-t border-slate-800 bg-slate-900/40 p-3">

                            {{-- Su juego --}}

                            <div x-show="gameScope === 'PHASE'">
                                <p class="text-[9px] font-black uppercase tracking-wider text-emerald-300">
                                    Con qué se juega esta fase
                                </p>

                                <select :name="'phases[' + ph.id + '][game_key]'"
                                    x-model="phaseOf(ph.id).game_key" x-keep-selected="phaseOf(ph.id).game_key"
                                    class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">— lo que diga la edición —</option>
                                    <template x-for="g in allowedGames" :key="'pg' + ph.id + '-' + g.key">
                                        <option :value="g.key" x-text="(g.icon ?? '🎲') + ' ' + g.name"></option>
                                    </template>
                                </select>
                            </div>


                            {{-- ============================================ --}}
                            {{-- SU ORDEN GENERAL · solo fase de grupos --}}
                            {{-- ============================================ --}}

                            {{--
                                Una fase de grupos produce varias tablas, y hace
                                falta una sola lista para repartir plazas y
                                premios por puesto. Cómo se construye lo decide
                                la plantilla, pero es exactamente el tipo de cosa
                                que una edición quiere cambiar sin tocarla —igual
                                que ya cambia el juego o el formato—.

                                Solo aparece en GROUP_STAGE: en un cuadro o en una
                                liga la pregunta no existe, y ofrecerla sería
                                ofrecer un control que no hace nada.
                            --}}

                            <div x-show="ph.phase_type === 'GROUP_STAGE'" x-cloak
                                class="rounded-lg border border-cyan-500/25 bg-cyan-500/5 p-2.5">

                                <div class="flex flex-wrap items-center gap-2">

                                    <p class="text-[9px] font-black uppercase tracking-wider text-cyan-300">
                                        ≡ Orden general de la fase
                                    </p>

                                    {{-- En qué estado está ahora mismo --}}
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black"
                                        :class="phaseOf(ph.id).overall_ranking_mode
                                            ? 'bg-cyan-500/25 text-cyan-200'
                                            : 'bg-slate-800 text-slate-400'"
                                        x-text="phaseOf(ph.id).overall_ranking_mode
                                            ? 'esta edición'
                                            : 'heredado de la plantilla'"></span>

                                    <span class="text-[9px] text-slate-400"
                                        x-text="overallRankingModes[
                                            phaseOf(ph.id).overall_ranking_mode || ph.overall_ranking_mode
                                        ]?.label ?? '—'"></span>
                                </div>

                                <select :name="'phases[' + ph.id + '][overall_ranking_mode]'"
                                    x-model="phaseOf(ph.id).overall_ranking_mode"
                                    x-keep-selected="phaseOf(ph.id).overall_ranking_mode"
                                    class="mt-1.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">

                                    <option value="">
                                        — heredar de la plantilla —
                                    </option>

                                    <template x-for="(modo, clave) in overallRankingModes" :key="'orm' + ph.id + clave">
                                        <option :value="clave" x-text="modo.label"></option>
                                    </template>
                                </select>

                                <p class="mt-1.5 text-[9px] leading-relaxed text-slate-500"
                                    x-text="overallRankingModes[
                                        phaseOf(ph.id).overall_ranking_mode || ph.overall_ranking_mode
                                    ]?.help ?? ''"></p>

                                <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
                                    Decide quién va primero de toda la fase, no dentro de
                                    su grupo. De esa lista salen las plazas y los premios
                                    por puesto.
                                </p>
                            </div>


                            {{-- Su batalla --}}

                            <div x-show="battleScope === 'PHASE'" class="space-y-2">

                                <div class="grid gap-2 sm:grid-cols-3">

                                    <label class="block">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-amber-300">Formato</span>

                                        <select :name="'phases[' + ph.id + '][series_format]'"
                                            x-model="phaseOf(ph.id).series_format"
                                            class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                            <option value="">— heredar —</option>
                                            <option value="BEST_OF">Al mejor de</option>
                                            <option value="FIXED_GAMES">Juegos fijos</option>
                                        </select>
                                    </label>

                                    <label class="block" x-show="phaseOf(ph.id).series_format !== 'FIXED_GAMES'">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Al mejor de</span>

                                        <input type="number" min="1" max="15" step="2"
                                            :name="'phases[' + ph.id + '][best_of]'"
                                            x-model="phaseOf(ph.id).best_of" placeholder="—"
                                            class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center font-mono text-[12px] font-black text-slate-100 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">
                                    </label>

                                    <label class="block" x-show="phaseOf(ph.id).series_format === 'FIXED_GAMES'" x-cloak>
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Juegos</span>

                                        <input type="number" min="1" max="15"
                                            :name="'phases[' + ph.id + '][fixed_games]'"
                                            x-model="phaseOf(ph.id).fixed_games" placeholder="—"
                                            class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center font-mono text-[12px] font-black text-slate-100 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">
                                    </label>

                                    <label class="block">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuántos compiten</span>

                                        <input type="number" min="2" max="64"
                                            :name="'phases[' + ph.id + '][battle_participants]'"
                                            x-model="phaseOf(ph.id).battle_participants" placeholder="—"
                                            class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center font-mono text-[12px] font-black text-slate-100 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">
                                    </label>
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">

                                    <label class="block">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Qué decide al ganador</span>

                                        <select :name="'phases[' + ph.id + '][decision_mode]'"
                                            x-model="phaseOf(ph.id).decision_mode"
                                            class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                            <option value="">— heredar —</option>
                                            @foreach ($decisionModes as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    {{--
                                        Tres estados y no dos: sí, no, y
                                        «lo que diga la edición». Un checkbox
                                        no sabe decir el tercero.
                                    --}}
                                    <label class="block">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">¿Puede empatar?</span>

                                        <select :name="'phases[' + ph.id + '][allow_draws]'"
                                            x-model="phaseOf(ph.id).allow_draws"
                                            class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                            <option value="">— heredar —</option>
                                            <option value="1">Sí, un empate vale</option>
                                            <option value="0">No, alguien tiene que ganar</option>
                                        </select>
                                    </label>
                                </div>
                            </div>


                            {{--
                                QUÉ SE LLEVA QUIEN GANE ESTA FASE

                                Un premio de fase no es un premio de torneo:
                                «puesto 1» aquí significa primero de ESTA
                                fase, no del torneo entero. Y eso el torneo
                                no puede decirlo, porque no sabe con qué
                                plantilla se jugará cada año.

                                Se listan y se crean aquí, pero se rellenan
                                en el bloque 07: son los mismos premios, y
                                dos formularios para lo mismo acabarían
                                discrepando.
                            --}}

                            <div class="rounded-xl border border-violet-500/25 bg-violet-500/5 p-2">

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[11px]">🏆</span>

                                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                                        Qué se lleva quien gane esta fase
                                    </p>

                                    <button type="button" @click="addPhasePrize(ph.id)"
                                        class="ml-auto rounded-lg border border-violet-500/40 px-2 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500/20">
                                        + premio para esta fase
                                    </button>
                                </div>

                                <div class="mt-1.5 space-y-1">
                                    <template x-for="(pr, pi) in phasePrizes(ph.id)" :key="'pp' + ph.id + '-' + pi">
                                        <div class="flex items-center gap-2 rounded-lg bg-slate-950/60 px-2 py-1">

                                            <span class="min-w-0 flex-1 truncate text-[10px] font-black text-slate-200"
                                                x-text="pr.label || 'Sin nombre'"></span>

                                            <span class="shrink-0 truncate text-[9px] text-slate-500"
                                                x-text="prizes ? prizes.rewardGives(pr) : ''"></span>

                                            <button type="button" @click="open = 'prizes'"
                                                class="shrink-0 text-[10px] text-slate-600 transition hover:text-violet-300"
                                                title="Editarlo en el bloque de premios">✎</button>
                                        </div>
                                    </template>

                                    <p class="text-[9px] leading-relaxed text-slate-600"
                                        x-show="phasePrizes(ph.id).length === 0">
                                        Nadie se lleva nada por ganar esta fase en concreto. Los
                                        premios de la edición entera se dan igual al terminar.
                                    </p>
                                </div>
                            </div>


                            {{-- Qué le aplica de verdad, ya con lo escrito --}}

                            <div class="flex flex-wrap items-center gap-2 rounded-lg bg-slate-950/70 px-2 py-1.5">

                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Le aplica</span>

                                <span class="text-[10px] text-slate-300" x-text="planFor(ph.id).game?.name ?? '—'"></span>
                                <span class="text-slate-700">·</span>
                                <span class="text-[10px] text-slate-300" x-text="planFor(ph.id).label"></span>
                                <span class="text-slate-700">·</span>
                                <span class="text-[10px] text-slate-300"
                                    x-text="planFor(ph.id).decision === 'POINTS_ONLY' ? 'solo anotaciones' : 'marcador'"></span>
                                <span class="text-slate-700">·</span>
                                <span class="text-[10px] text-slate-300"
                                    x-text="planFor(ph.id).draws ? 'admite empate' : 'exige ganador'"></span>

                                <button type="button" @click="clearPhase(ph.id)"
                                    x-show="phaseOverrides(ph.id).length"
                                    class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black text-slate-500 transition hover:text-rose-400">
                                    quitar la excepción
                                </button>
                            </div>

                        </div>

                    </div>
                </template>

            </div>
        </template>

    </div>
</section>

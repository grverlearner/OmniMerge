@php
    /*
     * EL RECORRIDO — una fase, con lo que tiene antes y lo que tiene después.
     *
     * El mapa contesta «cómo es el torneo». Esta vista contesta otra cosa:
     * «qué le pasa exactamente a la gente que llega a ESTA fase». Por eso la
     * fase elegida va en el centro y grande, con sus puertas abiertas, y a
     * los lados solo lo justo para saber de dónde viene y a dónde va.
     *
     * Si vienen de varios sitios se ven todos apilados a la izquierda, y si
     * reparte a varios se ven todos a la derecha: es justo el caso donde una
     * lista plana deja de explicar nada.
     *
     * Entre columna y columna van las rutas, y una ruta no es una flecha
     * suelta: dice por qué salida se va, a qué entrada llega y cuántos
     * pasan. Es la única forma de ver que «Ganador → Entrada general,
     * todos» y «Eliminados → De baja, los que sobren» son cosas distintas.
     */
@endphp

<div class="p-3">

    <template x-if="!focused">
        <div class="rounded-xl border border-dashed border-slate-700 px-6 py-10 text-center">
            <p class="text-[11px] font-black text-slate-400">No hay ninguna fase que mirar</p>
            <p class="mt-1 text-[10px] leading-relaxed text-slate-600">
                Añade una fase en el panel de la izquierda.
            </p>
        </div>
    </template>


    <template x-if="focused">
        <div>

        @include('tournaments.super.partials.phase-nav')

        <div class="grid gap-2 xl:grid-cols-[1fr_1.6fr_1fr]">

            {{-- ==================== LO QUE VIENE ANTES ==================== --}}

            <div class="flex flex-col">

                <div class="mb-1.5 flex items-center gap-1.5 rounded-lg bg-slate-800/40 px-2 py-1">
                    <span class="text-[10px] text-slate-500">◀</span>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Antes</span>
                    <span class="ml-auto font-mono text-[9px] text-slate-600" x-text="focusBefore.length"></span>
                </div>

                <div class="flex flex-1 flex-col justify-center gap-2">

                    <template x-for="piece in focusBefore" :key="'pb' + piece.key">
                        <div>
                            {{-- La pieza anterior, en pequeño --}}

                            <button type="button"
                                @click="kindOf(piece.key) === 'NODE' ? focus = piece.key : select(piece.key)"
                                class="w-full rounded-xl border p-2 text-left transition"
                                :class="colorOf(piece.key).border + ' ' + colorOf(piece.key).wash + ' hover:' + colorOf(piece.key).soft">

                                <div class="flex items-center gap-1">
                                    <span class="h-3 w-1 shrink-0 rounded-full" :class="colorOf(piece.key).dot"></span>
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-100"
                                        x-text="piece.name"></span>
                                    <span class="shrink-0 font-mono text-[8px] text-slate-600" x-text="piece.code"></span>
                                </div>

                                <p class="mt-0.5 truncate pl-2 text-[9px]" :class="colorOf(piece.key).text"
                                    x-text="piece.phase_type_label ?? piece.source_type_label"></p>

                                <div class="mt-1 pl-2" x-show="kindOf(piece.key) === 'NODE'">
                                    @include('tournaments.super.partials.outline', ['piece' => 'piece'])
                                </div>

                                <div class="mt-1.5 flex -space-x-1.5 pl-2">
                                    <template x-for="(face, fi) in facesFor(piece.key, 5)" :key="'pbf' + piece.key + fi">
                                        <span class="h-5 w-5 overflow-hidden rounded-full border border-slate-900 bg-slate-800">
                                            <template x-if="face.image_url">
                                                <img :src="face.image_url" alt="" class="h-full w-full object-cover">
                                            </template>
                                        </span>
                                    </template>
                                </div>
                            </button>

                            {{-- Y por qué ruta llega hasta aquí --}}

                            <template x-for="link in linksBetween(piece.key, focus)" :key="'pbl' + link.id">
                                <div class="ml-3 mt-1 rounded-lg border border-violet-500/30 bg-violet-500/5 px-2 py-1">
                                    <div class="flex items-center gap-1">
                                        <span class="text-[10px] text-violet-400">↳</span>
                                        <span class="min-w-0 flex-1 truncate text-[9px] font-bold text-violet-200"
                                            x-text="link.from_label"></span>
                                    </div>
                                    <div class="flex items-center gap-1 pl-3">
                                        <span class="min-w-0 flex-1 truncate text-[9px] text-slate-400"
                                            x-text="'→ ' + link.to_label"></span>
                                        <span class="shrink-0 rounded bg-violet-500/20 px-1 font-mono text-[8px] font-black text-violet-200"
                                            x-text="link.allocation"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="focusBefore.length === 0">
                        <p class="rounded-xl border border-dashed border-rose-500/40 px-3 py-6 text-center text-[9px] leading-relaxed text-rose-300/70">
                            No llega nadie a esta fase.<br>
                            <span class="text-slate-600">Conéctala desde una entrada u otra fase.</span>
                        </p>
                    </template>

                </div>

            </div>


            {{-- ==================== LA FASE, EN EL CENTRO ==================== --}}

            <div class="rounded-2xl border-2 p-3"
                :class="colorOf(focus).border + ' ' + colorOf(focus).wash">

                {{-- Identidad --}}

                <div class="flex flex-wrap items-center gap-2">
                    <span class="h-8 w-1.5 rounded-full" :class="colorOf(focus).solid"></span>

                    <div class="min-w-0 flex-1">
                        <p class="text-[9px] font-black uppercase tracking-[0.16em]"
                            :class="colorOf(focus).text" x-text="focused.phase_type_label"></p>
                        <h3 class="truncate text-lg font-black text-slate-100" x-text="focused.name"></h3>
                    </div>

                    <template x-if="isConverging(focus)">
                        <span class="rounded bg-violet-500/15 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-violet-300">
                            ⑂ se junta
                        </span>
                    </template>

                    <template x-if="isBranching(focus)">
                        <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-300">
                            ⑃ se abre
                        </span>
                    </template>

                    <a :href="'/tournaments/phases/' + focused.phase_template_id + '/super'"
                        class="shrink-0 rounded-lg border border-slate-700 px-2 py-1 text-[9px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                        ✎ editar fase
                    </a>
                </div>

                <p class="mt-1 text-[10px] text-slate-500">
                    <span x-text="focused.participant_contract"></span>
                    · <span x-text="focused.phase_template_name"></span>
                </p>


                {{-- La silueta, en grande --}}

                <div class="mt-2 flex items-center justify-center rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-4">
                    <div class="scale-[2.2]">
                        @include('tournaments.super.partials.outline', ['piece' => 'focused'])
                    </div>
                </div>


                {{-- Quién está dentro --}}

                <div class="mt-2 flex flex-wrap items-center gap-1 rounded-xl border border-slate-800 bg-slate-950/40 px-2 py-1.5">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Compiten</span>

                    <div class="flex flex-wrap gap-0.5">
                        <template x-for="(face, fi) in facesFor(focus, 12)" :key="'pcf' + fi">
                            <span class="h-6 w-6 overflow-hidden rounded-md bg-slate-800 ring-1"
                                :class="colorOf(focus).ring" :title="face.name">
                                <template x-if="face.image_url">
                                    <img :src="face.image_url" alt="" class="h-full w-full object-cover">
                                </template>
                            </span>
                        </template>
                    </div>

                    <span class="ml-auto text-[9px] text-slate-600">caras prestadas</span>
                </div>


                {{-- Las puertas: entran a la izquierda, salen a la derecha --}}

                <div class="mt-2 grid gap-2 sm:grid-cols-2">

                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-2">
                        <p class="text-[9px] font-black uppercase tracking-wider text-emerald-300">
                            ▼ Puertas de entrada
                        </p>

                        <div class="mt-1 space-y-1">
                            <template x-for="entry in focused.entries" :key="'pe' + entry.id">
                                <div class="rounded-lg bg-slate-950/60 px-2 py-1">
                                    <div class="flex items-center gap-1">
                                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                                            x-text="entry.name"></span>
                                        <span class="shrink-0 font-mono text-[8px] text-emerald-300"
                                            x-text="entry.contract"></span>
                                    </div>
                                    <p class="text-[8px] text-slate-600">
                                        <span x-text="entry.merge_policy_label"></span>
                                        · <span x-text="entry.incoming_count"></span> rutas
                                    </p>
                                </div>
                            </template>

                            <template x-if="focused.entries.length === 0">
                                <p class="py-1 text-center text-[9px] text-slate-600">Sin puertas.</p>
                            </template>
                        </div>
                    </div>

                    <div class="rounded-xl border border-violet-500/30 bg-violet-500/5 p-2">
                        <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                            ▲ Puertas de salida
                        </p>

                        <div class="mt-1 space-y-1">
                            <template x-for="exit in focused.exits" :key="'px' + exit.id">
                                <div class="rounded-lg px-2 py-1"
                                    :class="links.some(l => l.exit_id === exit.id)
                                        ? 'bg-slate-950/60'
                                        : 'border border-dashed border-amber-500/40 bg-amber-500/5'">

                                    <div class="flex items-center gap-1">
                                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                                            x-text="exit.name"></span>
                                        <span class="shrink-0 font-mono text-[8px] text-violet-300"
                                            x-text="exit.flow_forecast_label"></span>
                                    </div>

                                    <p class="text-[8px] text-slate-600" x-text="exit.selector"></p>

                                    {{-- Una salida sin ruta no lleva a nadie a ningún sitio --}}
                                    <template x-if="!links.some(l => l.exit_id === exit.id)">
                                        <p class="mt-0.5 text-[8px] font-bold text-amber-300">
                                            sin conectar — nadie sale por aquí
                                        </p>
                                    </template>
                                </div>
                            </template>

                            <template x-if="focused.exits.length === 0">
                                <p class="py-1 text-center text-[9px] text-slate-600">Sin salidas.</p>
                            </template>
                        </div>
                    </div>

                </div>

            </div>


            {{-- ==================== LO QUE VIENE DESPUÉS ==================== --}}

            <div class="flex flex-col">

                <div class="mb-1.5 flex items-center gap-1.5 rounded-lg bg-slate-800/40 px-2 py-1">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Después</span>
                    <span class="font-mono text-[9px] text-slate-600" x-text="focusAfter.length"></span>
                    <span class="ml-auto text-[10px] text-slate-500">▶</span>
                </div>

                <div class="flex flex-1 flex-col justify-center gap-2">

                    <template x-for="piece in focusAfter" :key="'pa' + piece.key">
                        <div>
                            {{-- Primero la ruta, que es lo que explica el salto --}}

                            <template x-for="link in linksBetween(focus, piece.key)" :key="'pal' + link.id">
                                <div class="mb-1 ml-3 rounded-lg border border-violet-500/30 bg-violet-500/5 px-2 py-1">
                                    <div class="flex items-center gap-1">
                                        <span class="text-[10px] text-violet-400">↳</span>
                                        <span class="min-w-0 flex-1 truncate text-[9px] font-bold text-violet-200"
                                            x-text="link.from_label"></span>
                                        <span class="shrink-0 rounded bg-violet-500/20 px-1 font-mono text-[8px] font-black text-violet-200"
                                            x-text="link.allocation"></span>
                                    </div>
                                </div>
                            </template>

                            <button type="button"
                                @click="kindOf(piece.key) === 'NODE' ? focus = piece.key : select(piece.key)"
                                class="w-full rounded-xl border p-2 text-left transition"
                                :class="colorOf(piece.key).border + ' ' + colorOf(piece.key).wash + ' hover:' + colorOf(piece.key).soft">

                                <div class="flex items-center gap-1">
                                    <span class="h-3 w-1 shrink-0 rounded-full" :class="colorOf(piece.key).dot"></span>
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-100"
                                        x-text="piece.name"></span>
                                    <span class="shrink-0 font-mono text-[8px] text-slate-600" x-text="piece.code"></span>
                                </div>

                                <p class="mt-0.5 truncate pl-2 text-[9px]" :class="colorOf(piece.key).text"
                                    x-text="piece.phase_type_label ?? piece.terminal_type_label"></p>

                                <div class="mt-1 pl-2" x-show="kindOf(piece.key) === 'NODE'">
                                    @include('tournaments.super.partials.outline', ['piece' => 'piece'])
                                </div>

                                {{-- Cuántos caben en este final --}}

                                <template x-if="kindOf(piece.key) === 'TERMINAL'">
                                    <p class="mt-1 pl-2 font-mono text-[9px] text-slate-500"
                                        x-text="piece.expected_participants
                                            ? piece.expected_participants + ' caben aquí'
                                            : piece.flow_forecast_label"></p>
                                </template>

                                <div class="mt-1.5 flex -space-x-1.5 pl-2" x-show="kindOf(piece.key) === 'NODE'">
                                    <template x-for="(face, fi) in facesFor(piece.key, 5)" :key="'paf' + piece.key + fi">
                                        <span class="h-5 w-5 overflow-hidden rounded-full border border-slate-900 bg-slate-800">
                                            <template x-if="face.image_url">
                                                <img :src="face.image_url" alt="" class="h-full w-full object-cover">
                                            </template>
                                        </span>
                                    </template>
                                </div>
                            </button>
                        </div>
                    </template>

                    <template x-if="focusAfter.length === 0">
                        <p class="rounded-xl border border-dashed border-rose-500/40 px-3 py-6 text-center text-[9px] leading-relaxed text-rose-300/70">
                            De esta fase no sale nadie.<br>
                            <span class="text-slate-600">Conecta sus salidas a otra fase o a un final.</span>
                        </p>
                    </template>

                </div>

            </div>

        </div>

        </div>
    </template>

</div>

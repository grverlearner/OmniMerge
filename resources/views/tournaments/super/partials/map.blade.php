@php
    /*
     * EL MAPA — el torneo entero, de principio a fin.
     *
     * Se lee de izquierda a derecha, que es como avanza la gente:
     *
     *   ENTRAN → nivel 1 → nivel 2 → … → SALEN
     *
     * Las columnas del medio son los NIVELES del grafo, no las fases una a
     * una: dos fases que se juegan a la vez están en la misma columna y con
     * el mismo color, y eso es exactamente lo que hay que ver de un vistazo.
     * El nivel lo calcula el análisis de flujo, no esta vista.
     *
     * Los símbolos de cada tarjeta dicen la forma del camino:
     *
     *   ⑃  de aquí sale gente hacia varios sitios — el camino se abre
     *   ⑂  aquí llega gente de varios sitios — el camino se junta
     *
     * Las caras son prestadas de tus universos y tu biblioteca. No son
     * inscritos y no se guardan: están para que el recorrido se vea con
     * gente dentro y no con cajas vacías.
     */
@endphp

<div class="p-3">

    <div class="arena-scroll overflow-x-auto">

        <div class="flex min-w-max items-stretch gap-2">

            <template x-for="(column, ci) in columns" :key="'mc' + ci">
                <div class="flex items-stretch">

                    {{-- ============ UNA COLUMNA ============ --}}

                    <div class="flex w-[212px] shrink-0 flex-col">

                        <div class="mb-1.5 flex items-center gap-1.5 rounded-lg px-2 py-1"
                            :class="column.color.soft">

                            <span class="h-3 w-1 rounded-full" :class="column.color.dot"></span>

                            <span class="min-w-0 flex-1 truncate text-[10px] font-black uppercase tracking-wider"
                                :class="column.color.text" x-text="column.label"></span>

                            <span class="shrink-0 font-mono text-[9px] text-slate-500"
                                x-text="column.pieces.length"></span>
                        </div>

                        <p class="mb-1.5 truncate px-1 text-[9px] text-slate-600" x-text="column.hint"></p>

                        <div class="flex flex-1 flex-col gap-1.5">

                            <template x-for="piece in column.pieces" :key="'mp' + piece.key">
                                <button type="button" @click="select(piece.key); if (kindOf(piece.key) === 'NODE') setView('PATH')"
                                    class="rounded-xl border p-2 text-left transition"
                                    :class="isSelected(piece.key)
                                        ? colorOf(piece.key).border + ' ' + colorOf(piece.key).soft + ' ring-1 ' + colorOf(piece.key).ring
                                        : 'border-slate-800 bg-slate-900/50 hover:border-slate-700'">

                                    {{-- Nombre y símbolos del camino --}}

                                    <div class="flex items-center gap-1">
                                        <span class="h-3.5 w-1 shrink-0 rounded-full" :class="colorOf(piece.key).dot"></span>

                                        <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-100"
                                            x-text="piece.name"></span>

                                        <template x-if="isConverging(piece.key)">
                                            <span class="shrink-0 text-[10px] text-violet-400" title="Aquí se junta el camino">⑂</span>
                                        </template>

                                        <template x-if="isBranching(piece.key)">
                                            <span class="shrink-0 text-[10px] text-amber-400" title="Aquí se abre el camino">⑃</span>
                                        </template>
                                    </div>


                                    {{-- Qué es --}}

                                    <p class="mt-0.5 truncate pl-2 text-[9px]" :class="colorOf(piece.key).text"
                                        x-text="piece.phase_type_label ?? piece.source_type_label ?? piece.terminal_type_label"></p>


                                    {{-- La silueta de la fase --}}

                                    <div class="mt-1 pl-2" x-show="kindOf(piece.key) === 'NODE'">
                                        @include('tournaments.super.partials.outline', ['piece' => 'piece'])
                                    </div>


                                    {{-- Caras, para ver que por aquí pasa gente --}}

                                    <div class="mt-1.5 flex items-center gap-1 pl-2">
                                        <div class="flex -space-x-1.5">
                                            <template x-for="(face, fi) in facesFor(piece.key, 4)" :key="'mf' + piece.key + fi">
                                                <span class="h-4 w-4 overflow-hidden rounded-full border border-slate-900 bg-slate-800">
                                                    <template x-if="face.image_url">
                                                        <img :src="face.image_url" alt="" class="h-full w-full object-cover">
                                                    </template>
                                                </span>
                                            </template>
                                        </div>

                                    </div>

                                    {{-- Cuántos entran y cuánto queda por llenar --}}

                                    <template x-if="kindOf(piece.key) === 'NODE' && nodeFlow(piece.id)">
                                        <p class="mt-1 flex items-center gap-1.5 pl-2">
                                            <span class="font-mono text-[9px] text-slate-500">
                                                <span class="text-emerald-300" x-text="amount(nodeFlow(piece.id).receives)"></span>
                                                / <span x-text="nodeFlow(piece.id).fits ?? '∞'"></span>
                                            </span>

                                            <template x-if="room(nodeFlow(piece.id).left)">
                                                <span class="text-[8px] font-black"
                                                    :class="roomTone(nodeFlow(piece.id).left)"
                                                    x-text="room(nodeFlow(piece.id).left)"></span>
                                            </template>
                                        </p>
                                    </template>

                                    <template x-if="kindOf(piece.key) === 'START' && startFlow(piece.id)">
                                        <p class="mt-1 pl-2 font-mono text-[9px] text-slate-500">
                                            tiene <span class="text-slate-300" x-text="startFlow(piece.id).holds ?? '∞'"></span>
                                            · encamina <span class="text-emerald-300" x-text="amount(startFlow(piece.id).routed)"></span>
                                        </p>
                                    </template>

                                    <template x-if="kindOf(piece.key) === 'TERMINAL' && terminalFlow(piece.id)">
                                        <p class="mt-1 flex items-center gap-1.5 pl-2">
                                            <span class="font-mono text-[9px] text-slate-500">
                                                <span class="text-rose-300" x-text="amount(terminalFlow(piece.id).arriving)"></span>
                                                / <span x-text="terminalFlow(piece.id).fits ?? '∞'"></span>
                                            </span>

                                            <template x-if="room(terminalFlow(piece.id).left)">
                                                <span class="text-[8px] font-black"
                                                    :class="roomTone(terminalFlow(piece.id).left)"
                                                    x-text="room(terminalFlow(piece.id).left)"></span>
                                            </template>
                                        </p>
                                    </template>


                                    {{-- Por dónde sale la gente de aquí --}}

                                    <template x-if="after(piece.key).length">
                                        <div class="mt-1.5 space-y-0.5 border-t border-slate-800 pt-1 pl-2">
                                            <template x-for="link in links.filter(l => l.from === piece.key)"
                                                :key="'ml' + link.id">
                                                <p class="flex items-center gap-1 truncate text-[8px]">
                                                    <span class="text-violet-400">↳</span>
                                                    <span class="min-w-0 flex-1 truncate text-slate-500"
                                                        x-text="link.to_label"></span>
                                                    <span class="shrink-0 rounded bg-slate-800 px-1 font-mono text-violet-300"
                                                        x-text="flow.connections?.[link.id]
                                                            ? amount(flow.connections[link.id])
                                                            : link.allocation"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </template>

                                </button>
                            </template>

                        </div>

                    </div>


                    {{-- ============ LA FLECHA A LA COLUMNA SIGUIENTE ============ --}}

                    <div class="flex w-6 shrink-0 items-center justify-center"
                        x-show="ci < columns.length - 1">
                        <span class="text-sm" :class="column.color.text">→</span>
                    </div>

                </div>
            </template>


            <template x-if="columns.length === 0">
                <div class="rounded-xl border border-dashed border-slate-700 px-6 py-10 text-center">
                    <p class="text-[11px] font-black text-slate-400">El torneo está vacío</p>
                    <p class="mt-1 text-[10px] leading-relaxed text-slate-600">
                        Empieza por una entrada y una fase, en el panel de la izquierda.
                    </p>
                </div>
            </template>

        </div>

    </div>


    {{-- ============ LEYENDA ============ --}}

    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-slate-800 bg-slate-900/40 px-3 py-1.5">
        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cómo leerlo</span>

        <span class="flex items-center gap-1 text-[9px] text-slate-500">
            <span class="text-amber-400">⑃</span> el camino se abre
        </span>

        <span class="flex items-center gap-1 text-[9px] text-slate-500">
            <span class="text-violet-400">⑂</span> el camino se junta
        </span>

        <span class="flex items-center gap-1 text-[9px] text-slate-500">
            <span class="h-2 w-2 rounded-full bg-emerald-400"></span> entra
        </span>

        <span class="flex items-center gap-1 text-[9px] text-slate-500">
            <span class="h-2 w-2 rounded-full bg-rose-400"></span> acaba
        </span>

        <span class="ml-auto text-[9px] text-slate-600">
            Pulsa una fase para verla en el recorrido.
        </span>
    </div>

</div>

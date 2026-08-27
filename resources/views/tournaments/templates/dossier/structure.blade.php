@php
    /*
     * LA ESTRUCTURA — el recorrido entero, de principio a fin.
     *
     * Se lee de izquierda a derecha: ENTRAN → nivel 1 → nivel 2 → … → SALEN.
     * Las columnas del medio son NIVELES, no fases una a una: dos fases que
     * se juegan a la vez comparten columna y color.
     *
     * Aquí no hay ni un botón de editar. Lo que sí hay son etiquetas y
     * colores que explican, que es a lo que viene esta pantalla:
     *
     *   ⑃  de aquí sale gente hacia varios sitios — el camino se abre
     *   ⑂  aquí llega gente de varios sitios — el camino se junta
     *   ▮  verde = por donde entran · rosa = donde acaban
     *
     * Cuando hay una simulación corriendo, las mismas tarjetas se llenan
     * con lo que pasó: cuántos recibió cada fase, cuántos mandó, y quién
     * acabó en cada final. Y si estás siguiendo a alguien, se marca por
     * dónde pasó.
     */
@endphp

<section class="mb-4">

    <div class="mb-2 flex flex-wrap items-center gap-2">
        <span class="h-3 w-1 rounded-full bg-slate-600"></span>

        <h2 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
            El recorrido
        </h2>

        <span class="text-[10px] text-slate-600">— por dónde pasa la gente, de principio a fin</span>

        <template x-if="tracking">
            <span class="ml-auto flex items-center gap-1.5 rounded-full bg-amber-500/15 px-2 py-1 text-[9px] font-black text-amber-300">
                siguiendo a <span x-text="tracking.name"></span>
                <button type="button" @click="tracking = null" class="text-amber-200 hover:text-white">×</button>
            </span>
        </template>
    </div>


    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40 p-3">

        <div class="arena-scroll overflow-x-auto">

            <div class="flex min-w-max items-stretch gap-2">

                <template x-for="(column, ci) in columns" :key="'sc' + ci">
                    <div class="flex items-stretch">

                        <div class="flex w-[218px] shrink-0 flex-col">

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

                                <template x-for="piece in column.pieces" :key="'sp' + piece.key">
                                    <div class="rounded-xl border p-2 transition"
                                        :class="tracking
                                            ? (tracksThrough(piece.key)
                                                ? colorOf(piece.key).border + ' ' + colorOf(piece.key).soft + ' ring-1 ' + colorOf(piece.key).ring
                                                : 'border-slate-800 bg-slate-900/30 opacity-40')
                                            : 'border-slate-800 bg-slate-900/50'">

                                        {{-- Nombre y forma del camino --}}

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

                                        <p class="mt-0.5 truncate pl-2 text-[9px]" :class="colorOf(piece.key).text"
                                            x-text="piece.phase_type_label ?? piece.source_type_label ?? piece.terminal_type_label"></p>

                                        {{-- La silueta de la fase --}}

                                        <div class="mt-1 pl-2" x-show="kindOf(piece.key) === 'NODE'">
                                            @include('tournaments.super.partials.outline', ['piece' => 'piece'])
                                        </div>

                                        {{-- Caras, o lo que pasó si se simuló --}}

                                        <template x-if="!hasResult">
                                            <div class="mt-1.5 flex items-center gap-1 pl-2">
                                                <div class="flex -space-x-1.5">
                                                    <template x-for="(face, fi) in facesFor(piece.key, 4)" :key="'sf' + piece.key + fi">
                                                        <span class="h-4 w-4 overflow-hidden rounded-full border border-slate-900 bg-slate-800">
                                                            <template x-if="face.image_url">
                                                                <img :src="face.image_url" alt="" class="h-full w-full object-cover">
                                                            </template>
                                                        </span>
                                                    </template>
                                                </div>

                                            </div>
                                        </template>

                                        {{--
                                            Antes de simular, lo que el recorrido
                                            promete: cuántos le llegan a cada pieza
                                            y cuánto le queda por llenar.
                                        --}}

                                        <template x-if="!hasResult && kindOf(piece.key) === 'NODE' && nodeFlow(piece.id)">
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

                                        <template x-if="!hasResult && kindOf(piece.key) === 'START' && startFlow(piece.id)">
                                            <p class="mt-1 pl-2 font-mono text-[9px] text-slate-500">
                                                tiene <span class="text-slate-300" x-text="startFlow(piece.id).holds ?? '∞'"></span>
                                                · encamina <span class="text-emerald-300" x-text="amount(startFlow(piece.id).routed)"></span>
                                            </p>
                                        </template>

                                        <template x-if="!hasResult && kindOf(piece.key) === 'TERMINAL' && terminalFlow(piece.id)">
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

                                        {{-- Lo que pasó de verdad --}}

                                        <template x-if="hasResult && kindOf(piece.key) === 'NODE' && runNodeOf(piece.key)">
                                            <div class="mt-1.5 flex items-center gap-1.5 rounded-lg bg-slate-950/70 px-2 py-1">
                                                <span class="font-mono text-[9px] font-black text-emerald-300"
                                                    x-text="'▼ ' + runNodeOf(piece.key).received"></span>
                                                <span class="font-mono text-[9px] font-black text-violet-300"
                                                    x-text="'▲ ' + runNodeOf(piece.key).sent"></span>
                                                <span class="ml-auto rounded px-1 text-[8px] font-black uppercase"
                                                    :class="runNodeOf(piece.key).status === 'PROCESSED'
                                                        ? 'bg-emerald-500/15 text-emerald-300'
                                                        : 'bg-amber-500/15 text-amber-300'"
                                                    x-text="runNodeOf(piece.key).status === 'PROCESSED' ? 'jugada' : runNodeOf(piece.key).status"></span>
                                            </div>
                                        </template>

                                        <template x-if="hasResult && kindOf(piece.key) === 'TERMINAL' && runTerminalOf(piece.key)">
                                            <div class="mt-1.5">
                                                <div class="flex items-center gap-1.5 rounded-lg bg-slate-950/70 px-2 py-1">
                                                    <span class="font-mono text-sm font-black text-rose-300"
                                                        x-text="runTerminalOf(piece.key).count"></span>
                                                    <span class="text-[9px] text-slate-500">llegaron aquí</span>
                                                </div>

                                                <div class="mt-1 flex flex-wrap gap-0.5">
                                                    <template x-for="p in runTerminalOf(piece.key).participants.slice(0, 12)"
                                                        :key="'st' + piece.key + p.preview_id">
                                                        <button type="button" @click="track(p)"
                                                            class="rounded px-1 py-0.5 text-[8px] font-bold transition"
                                                            :class="isTracked(p)
                                                                ? 'bg-amber-500 text-slate-950'
                                                                : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                                            x-text="p.name"></button>
                                                    </template>

                                                    <template x-if="runTerminalOf(piece.key).participants.length > 12">
                                                        <span class="px-1 py-0.5 text-[8px] text-slate-600"
                                                            x-text="'+' + (runTerminalOf(piece.key).participants.length - 12)"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- A dónde lleva cada salida --}}

                                        <template x-if="linksFrom(piece.key).length">
                                            <div class="mt-1.5 space-y-0.5 border-t border-slate-800 pt-1 pl-2">
                                                <template x-for="link in linksFrom(piece.key)" :key="'sl' + link.id">
                                                    <p class="flex items-center gap-1 truncate text-[8px]">
                                                        <span class="text-violet-400">↳</span>
                                                        <span class="min-w-0 flex-1 truncate text-slate-500"
                                                            x-text="link.to_label"></span>

                                                        <template x-if="hasResult && flowThrough(link.id) !== null">
                                                            <span class="shrink-0 rounded bg-emerald-500/20 px-1 font-mono font-black text-emerald-300"
                                                                x-text="flowThrough(link.id)"></span>
                                                        </template>

                                                        <template x-if="!hasResult">
                                                            <span class="shrink-0 rounded bg-slate-800 px-1 font-mono text-violet-300"
                                                                x-text="flow.connections?.[link.id]
                                                                    ? amount(flow.connections[link.id])
                                                                    : link.allocation"></span>
                                                        </template>
                                                    </p>
                                                </template>
                                            </div>
                                        </template>

                                    </div>
                                </template>

                            </div>

                        </div>


                        <div class="flex w-6 shrink-0 items-center justify-center"
                            x-show="ci < columns.length - 1">
                            <span class="text-sm" :class="column.color.text">→</span>
                        </div>

                    </div>
                </template>


                <template x-if="columns.length === 0">
                    <div class="rounded-xl border border-dashed border-slate-700 px-6 py-10 text-center">
                        <p class="text-[11px] font-black text-slate-400">Este torneo está vacío</p>
                        <p class="mt-1 text-[10px] leading-relaxed text-slate-600">
                            Todavía no tiene ni entradas ni fases.
                        </p>
                    </div>
                </template>

            </div>

        </div>


        {{-- ============ CÓMO LEERLO ============ --}}

        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-slate-800 pt-2">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cómo leerlo</span>

            <span class="flex items-center gap-1 text-[9px] text-slate-500">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span> por aquí entran
            </span>

            <span class="flex items-center gap-1 text-[9px] text-slate-500">
                <span class="h-2 w-2 rounded-full bg-rose-400"></span> aquí acaban
            </span>

            <span class="flex items-center gap-1 text-[9px] text-slate-500">
                <span class="text-amber-400">⑃</span> el camino se abre
            </span>

            <span class="flex items-center gap-1 text-[9px] text-slate-500">
                <span class="text-violet-400">⑂</span> el camino se junta
            </span>

            <span class="flex items-center gap-1 text-[9px] text-slate-500">
                <span class="font-mono text-emerald-300">▼</span> recibe
                <span class="font-mono text-violet-300">▲</span> manda
            </span>

            <span class="ml-auto text-[9px] text-slate-600">
                El color de una fase dice a qué nivel del torneo pertenece.
            </span>
        </div>

    </div>

</section>

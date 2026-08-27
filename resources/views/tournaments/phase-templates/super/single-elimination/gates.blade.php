@php
    /*
     * Panel derecho — puertas.
     *
     *   ENTRADA  qué puestos del CUADRO reclama. En una eliminación directa
     *            eso es todo lo que hay que decidir antes de jugar: el
     *            puesto decide contra quién se abre y en qué mitad se cae.
     *
     *   SALIDA   qué puesto FINAL se lleva. El campeón es el 1, el
     *            finalista el 2, y el tercero solo existe si se juega el
     *            partido que lo decide.
     */
@endphp

<div class="divide-y divide-slate-800"
    x-data="{ newGate: false, editGate: null, newExit: false, editExit: null }">

    {{-- ================= ENTRADAS ================= --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Entrada · puesto del cuadro
            </p>

            <button type="button" @click="newGate = !newGate; editGate = null"
                class="rounded-md border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-400">
                <span x-show="!newGate">+ Puerta</span>
                <span x-show="newGate" x-cloak>Cerrar</span>
            </button>

        </div>

        <div x-show="newGate" x-cloak class="mt-2 rounded-lg border border-emerald-500/40 bg-emerald-500/5 p-2">
            @include('tournaments.phase-templates.super.single-elimination.gate-form', ['gate' => null])
        </div>

        <div class="mt-2 space-y-1.5">

            <template x-for="gate in payload.gates" :key="'gt' + gate.id">
                <div class="rounded-lg border bg-slate-950/40 px-2 py-1.5"
                    :class="gate.status === 'ACTIVE' ? gate.color.border : 'border-slate-800 opacity-50'">

                    <div class="flex items-center gap-1.5">
                        <span class="h-4 w-1 shrink-0 rounded-full" :class="gate.color.dot"></span>

                        <span class="font-mono text-[10px] font-black" :class="gate.color.text"
                            x-text="'#' + gate.number"></span>

                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                            x-text="gate.name"></span>

                        <button type="button"
                            @click="editGate = editGate === gate.id ? null : gate.id; newGate = false"
                            class="shrink-0 text-[10px] text-slate-500 transition hover:text-emerald-400">✎</button>

                        <form method="POST" class="shrink-0"
                            :action="@js(route('tournaments.phase-templates.super.gates.destroy', [$phaseTemplate, '__ID__'])).replace('__ID__', gate.id)"
                            @submit="confirm('¿Eliminar esta puerta?') || $event.preventDefault()">
                            @csrf
                            @include('tournaments.phase-templates.super.partials.preview-state')
                            @method('DELETE')
                            <button class="text-[10px] text-slate-600 transition hover:text-rose-400">×</button>
                        </form>
                    </div>

                    <p class="mt-1 pl-2.5 font-mono text-[9px]" :class="gate.color.text"
                        x-text="gate.rule_label"></p>

                    <div x-show="editGate === gate.id" x-cloak class="mt-2 border-t border-slate-800 pt-2">
                        <template x-if="editGate === gate.id">
                            <div>
                                @include('tournaments.phase-templates.super.single-elimination.gate-form', ['gate' => 'alpine'])
                            </div>
                        </template>
                    </div>

                </div>
            </template>

            <template x-if="payload.gates.length === 0">
                <div x-show="!newGate" class="rounded-lg border border-dashed border-slate-700 px-2 py-3 text-center">
                    <p class="text-[9px] leading-relaxed text-slate-500">
                        Sin puertas, entran en su orden de llegada.
                    </p>
                </div>
            </template>

        </div>

        <p x-show="!showsManual && payload.gates.length > 0" x-cloak
            class="mt-1.5 rounded-md bg-amber-500/10 px-2 py-1 text-[9px] font-bold leading-relaxed text-amber-300">
            Las puertas solo mandan con orden <strong>Manual</strong>. Con otro
            orden se guardan, pero decide el algoritmo.
        </p>

    </section>


    {{-- ================= SALIDAS ================= --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Salida · puesto final
            </p>

            <button type="button" @click="newExit = !newExit; editExit = null"
                class="rounded-md border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-400">
                <span x-show="!newExit">+ Puerta</span>
                <span x-show="newExit" x-cloak>Cerrar</span>
            </button>

        </div>

        <div x-show="newExit" x-cloak class="mt-2 rounded-lg border border-violet-500/40 bg-violet-500/5 p-2">
            @include('tournaments.phase-templates.super.single-elimination.exit-form', ['exit' => null])
        </div>

        <div class="mt-2 space-y-1.5">

            <template x-for="exit in payload.exits" :key="'xt' + exit.id">
                <div class="rounded-lg border bg-slate-950/40 px-2 py-1.5"
                    :class="exit.status === 'ACTIVE' ? exit.color.border : 'border-slate-800 opacity-50'">

                    <div class="flex items-center gap-1.5">
                        <span class="h-4 w-1 shrink-0 rounded-full" :class="exit.color.dot"></span>

                        <span class="font-mono text-[10px] font-black" :class="exit.color.text"
                            x-text="'#' + exit.number"></span>

                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                            x-text="exit.name"></span>

                        <span class="shrink-0 rounded bg-slate-800 px-1 font-mono text-[9px] font-black"
                            :class="emitsOf(exit) ? 'text-slate-300' : 'text-slate-600'"
                            :title="hasResults ? 'Según lo simulado' : 'Cuántos caben'"
                            x-text="hasResults ? emitsOf(exit) : (exit.capacity ?? '—')"></span>

                        <button type="button"
                            @click="editExit = editExit === exit.id ? null : exit.id; newExit = false"
                            class="shrink-0 text-[10px] text-slate-500 transition hover:text-violet-400">✎</button>

                        <form method="POST" class="shrink-0"
                            :action="@js(route('tournaments.phase-templates.super.exits.destroy', [$phaseTemplate, '__ID__'])).replace('__ID__', exit.id)"
                            @submit="confirm('¿Eliminar esta salida?') || $event.preventDefault()">
                            @csrf
                            @include('tournaments.phase-templates.super.partials.preview-state')
                            @method('DELETE')
                            <button class="text-[10px] text-slate-600 transition hover:text-rose-400">×</button>
                        </form>
                    </div>

                    <p class="mt-1 pl-2.5 text-[9px] leading-tight text-slate-500" x-text="exit.summary"></p>

                    <template x-if="exit.positions">
                        <p class="mt-0.5 pl-2.5 font-mono text-[9px]" :class="exit.color.text"
                            x-text="exit.positions.from === exit.positions.to
                                ? 'puesto ' + exit.positions.from
                                : 'puestos ' + exit.positions.from + '–' + exit.positions.to"></p>
                    </template>

                    {{-- De qué rama recoge, si es de ese tipo --}}

                    <template x-if="exit.branch">
                        <p class="mt-0.5 flex items-center gap-1 pl-2.5">
                            <span class="flex h-3 w-3 items-center justify-center rounded text-[8px] font-black text-slate-950"
                                :class="(branches[exit.branch - 1]?.color?.solid) ?? 'bg-slate-600'"
                                x-text="branches[exit.branch - 1]?.letter ?? '?'"></span>
                            <span class="font-mono text-[9px] text-slate-500"
                                x-text="branches[exit.branch - 1]
                                    ? ('sale de ' + branches[exit.branch - 1].label.toLowerCase())
                                    : 'rama que ya no existe'"></span>
                        </p>
                    </template>

                    {{--
                        Pedir un puesto que nadie decide.

                        Antes solo se avisaba del tercero, porque era el único
                        caso que existía. Ahora el servidor dice exactamente
                        qué grupo hay que ordenar, y el botón lo ordena.
                    --}}
                    <template x-if="!exit.is_ready">
                        <div class="mt-1 rounded bg-amber-500/10 px-1.5 py-1">
                            <p class="text-[9px] font-bold leading-relaxed text-amber-300"
                                x-text="exit.blocked_hint"></p>

                            <template x-if="exit.blocked_by">
                                <button type="button" @click="togglePlacement(exit.blocked_by)"
                                    class="mt-1 rounded border border-amber-500/50 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-300 transition hover:bg-amber-500/20">
                                    Ordenarlo ahora
                                </button>
                            </template>
                        </div>
                    </template>

                    <div x-show="editExit === exit.id" x-cloak class="mt-2 border-t border-slate-800 pt-2">
                        <template x-if="editExit === exit.id">
                            <div>
                                @include('tournaments.phase-templates.super.single-elimination.exit-form', ['exit' => 'alpine'])
                            </div>
                        </template>
                    </div>

                </div>
            </template>

            <template x-if="payload.exits.length === 0">
                <div x-show="!newExit" class="rounded-lg border border-dashed border-rose-500/40 px-2 py-3 text-center">
                    <p class="text-[9px] leading-relaxed text-rose-300/70">
                        Sin salidas nadie avanza a la siguiente fase.
                    </p>
                </div>
            </template>

        </div>

    </section>

</div>

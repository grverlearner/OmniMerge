@php
    /*
     * Panel derecho — puertas, con su edición dentro.
     *
     * Los dos conceptos NO son lo mismo, y en una liga la diferencia es
     * clara:
     *
     *   ENTRADA  qué puestos de la PARRILLA INICIAL reclama. En una liga
     *            todos se enfrentan a todos, así que una puerta no decide
     *            quién pasa: decide por dónde entra cada uno. Y eso cambia
     *            el calendario, porque el 1 abre contra el último.
     *
     *   SALIDA   qué puestos de la CLASIFICACIÓN FINAL se lleva. Aquí ya
     *            se ha jugado y hay tabla.
     *
     * Antes esto mandaba a otra pantalla. En un editor cuya razón de ser es
     * que todo reacciona a la vez, salir fuera era lo único que no
     * reaccionaba: se configuraba a ciegas y había que volver para ver el
     * efecto.
     */
@endphp

<div class="divide-y divide-slate-800"
    x-data="{ newGate: false, editGate: null, newExit: false, editExit: null }">

    {{-- ================= ENTRADAS ================= --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Entrada · parrilla
            </p>

            <button type="button" @click="newGate = !newGate; editGate = null"
                class="rounded-md border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-400">
                <span x-show="!newGate">+ Puerta</span>
                <span x-show="newGate" x-cloak>Cerrar</span>
            </button>

        </div>


        {{-- ALTA --}}

        <div x-show="newGate" x-cloak class="mt-2 rounded-lg border border-emerald-500/40 bg-emerald-500/5 p-2">
            @include('tournaments.phase-templates.super.partials.gate-form', ['gate' => null])
        </div>


        <div class="mt-2 space-y-1.5">

            <template x-for="gate in payload.gates" :key="gate.id">
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
                            @method('DELETE')
                            <button class="text-[10px] text-slate-600 transition hover:text-rose-400">×</button>
                        </form>
                    </div>

                    <div class="mt-1 flex items-center gap-2 pl-2.5">
                        <span class="font-mono text-[9px]" :class="gate.color.text"
                            x-text="gate.rule_label"></span>

                        <template x-if="gate.is_required">
                            <span class="rounded bg-amber-500/20 px-1 text-[8px] font-black text-amber-300">oblig.</span>
                        </template>
                    </div>

                    {{-- EDICIÓN --}}
                    <div x-show="editGate === gate.id" x-cloak
                        class="mt-2 border-t border-slate-800 pt-2">
                        <template x-if="editGate === gate.id">
                            <div>
                                @include('tournaments.phase-templates.super.partials.gate-form', ['gate' => 'alpine'])
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

        <p class="mt-1.5 text-[9px] leading-relaxed text-slate-600">
            Reparte los puestos de salida de la parrilla, no la clasificación.
        </p>

    </section>


    {{-- ================= SALIDAS ================= --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Salida · clasificación
            </p>

            <button type="button" @click="newExit = !newExit; editExit = null"
                class="rounded-md border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-400">
                <span x-show="!newExit">+ Puerta</span>
                <span x-show="newExit" x-cloak>Cerrar</span>
            </button>

        </div>


        {{-- ALTA --}}

        <div x-show="newExit" x-cloak class="mt-2 rounded-lg border border-violet-500/40 bg-violet-500/5 p-2">
            @include('tournaments.phase-templates.super.partials.exit-form', ['exit' => null])
        </div>


        <div class="mt-2 space-y-1.5">

            <template x-for="exit in payload.exits" :key="exit.id">
                <div class="rounded-lg border bg-slate-950/40 px-2 py-1.5"
                    :class="exit.status === 'ACTIVE' ? exit.color.border : 'border-slate-800 opacity-50'">

                    <div class="flex items-center gap-1.5">
                        <span class="h-4 w-1 shrink-0 rounded-full" :class="exit.color.dot"></span>

                        <span class="font-mono text-[10px] font-black" :class="exit.color.text"
                            x-text="'#' + exit.number"></span>

                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                            x-text="exit.name"></span>

                        <button type="button"
                            @click="editExit = editExit === exit.id ? null : exit.id; newExit = false"
                            class="shrink-0 text-[10px] text-slate-500 transition hover:text-violet-400">✎</button>

                        <form method="POST" class="shrink-0"
                            :action="@js(route('tournaments.phase-templates.super.exits.destroy', [$phaseTemplate, '__ID__'])).replace('__ID__', exit.id)"
                            @submit="confirm('¿Eliminar esta salida?') || $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <button class="text-[10px] text-slate-600 transition hover:text-rose-400">×</button>
                        </form>
                    </div>

                    <template x-if="exit.positions">
                        <p class="mt-1 pl-2.5 font-mono text-[9px]" :class="exit.color.text">
                            <span x-show="exit.positions.anchor === 'TOP'"
                                x-text="'puestos ' + exit.positions.from + '–' + Math.min(exit.positions.to, castSize)"></span>

                            <span x-show="exit.positions.anchor === 'BOTTOM'"
                                x-text="'puestos ' + Math.max(1, castSize - exit.positions.to + 1) + '–' + castSize"></span>
                        </p>
                    </template>

                    <template x-if="!exit.positions">
                        <p class="mt-1 pl-2.5 text-[9px] text-slate-500" x-text="exit.summary"></p>
                    </template>

                    {{-- EDICIÓN --}}
                    <div x-show="editExit === exit.id" x-cloak
                        class="mt-2 border-t border-slate-800 pt-2">
                        <template x-if="editExit === exit.id">
                            <div>
                                @include('tournaments.phase-templates.super.partials.exit-form', ['exit' => 'alpine'])
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

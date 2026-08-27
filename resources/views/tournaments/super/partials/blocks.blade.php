@php
    /*
     * El panel izquierdo: los bloques del torneo.
     *
     * Cuatro cosas, y el torneo entero es la suma de las cuatro:
     *
     *   Entradas   por dónde llega la gente
     *   Fases      qué se juega, en orden
     *   Finales    dónde acaba cada uno
     *   Rutas      qué conecta con qué
     *
     * Todo se crea, edita y borra sin salir de aquí. Los formularios apuntan
     * a las rutas del grafo que ya existían —no se ha escrito ni un
     * controlador nuevo— y como todas responden con `back()`, se vuelve a
     * esta misma pantalla.
     *
     * Las rutas van al final a propósito: no se puede conectar lo que
     * todavía no existe, y el orden del panel enseña ese orden de trabajo.
     */
@endphp

<div class="divide-y divide-slate-800">

    {{-- ==================== ENTRADAS ==================== --}}

    <section class="p-3">

        <div class="flex items-center gap-1.5">
            <span class="h-3 w-1 rounded-full bg-emerald-400"></span>
            <h2 class="text-[10px] font-black uppercase tracking-[0.16em] text-emerald-300">Entradas</h2>
            <span class="font-mono text-[9px] text-slate-600" x-text="starts.length"></span>

            <button type="button" @click="openCreate('START')"
                class="ml-auto rounded-md border border-emerald-500/40 px-1.5 py-0.5 text-[9px] font-black text-emerald-300 transition hover:bg-emerald-500/20">
                + nueva
            </button>
        </div>

        <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
            Por dónde entran los competidores al torneo.
        </p>

        <div x-show="creating === 'START'" x-cloak class="mt-2">
            @include('tournaments.super.forms.start', ['start' => null])
        </div>

        <div class="mt-2 space-y-1">

            <template x-for="start in starts" :key="start.key">
                <div>
                    <button type="button" @click="select(start.key)"
                        class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                        :class="isSelected(start.key)
                            ? 'border-emerald-400/60 bg-emerald-500/10'
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-1 shrink-0 rounded-full bg-emerald-400"></span>
                            <span class="truncate text-[11px] font-black text-slate-200" x-text="start.name"></span>
                            <span class="ml-auto shrink-0 font-mono text-[9px] text-slate-600" x-text="start.code"></span>
                        </div>

                        <p class="mt-0.5 pl-2.5 text-[9px] text-slate-500">
                            <span x-text="start.source_type_label"></span>
                            <span x-show="start.expected_participants">
                                · <span x-text="start.expected_participants"></span> esperados
                            </span>
                            · <span x-text="start.outgoing_count"></span> rutas
                        </p>
                    </button>

                    <div x-show="isSelected(start.key)" x-cloak class="mt-1 space-y-1">
                        @include('tournaments.super.forms.start', ['start' => 'alpine'])
                        @include('tournaments.super.forms.delete', ['piece' => 'start', 'aviso' => '¿Eliminar esta entrada?'])
                    </div>
                </div>
            </template>

            <template x-if="starts.length === 0">
                <p class="rounded-lg border border-dashed border-rose-500/40 px-2 py-3 text-center text-[9px] leading-relaxed text-rose-300/70">
                    Sin entradas nadie llega al torneo.
                </p>
            </template>

        </div>

    </section>


    {{-- ==================== FASES ==================== --}}

    <section class="p-3">

        <div class="flex items-center gap-1.5">
            <span class="h-3 w-1 rounded-full bg-sky-400"></span>
            <h2 class="text-[10px] font-black uppercase tracking-[0.16em] text-sky-300">Fases</h2>
            <span class="font-mono text-[9px] text-slate-600" x-text="nodes.length"></span>

            <button type="button" @click="openCreate('NODE')"
                class="ml-auto rounded-md border border-sky-500/40 px-1.5 py-0.5 text-[9px] font-black text-sky-300 transition hover:bg-sky-500/20">
                + añadir
            </button>
        </div>

        <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
            Cada fase que se juega. El color dice a qué nivel del torneo pertenece.
        </p>

        <div x-show="creating === 'NODE'" x-cloak class="mt-2">
            @include('tournaments.super.forms.node', ['node' => null])
        </div>

        <div class="mt-2 space-y-1">

            <template x-for="node in nodes" :key="node.key">
                <div>
                    <button type="button" @click="select(node.key)"
                        class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                        :class="isSelected(node.key)
                            ? colorOf(node.key).border + ' ' + colorOf(node.key).soft
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-1 shrink-0 rounded-full" :class="colorOf(node.key).dot"></span>
                            <span class="truncate text-[11px] font-black text-slate-200" x-text="node.name"></span>

                            <template x-if="isBranching(node.key)">
                                <span class="shrink-0 text-[9px] text-amber-400" title="Reparte a varios sitios">⑃</span>
                            </template>

                            <template x-if="isConverging(node.key)">
                                <span class="shrink-0 text-[9px] text-violet-400" title="Le llegan varios">⑂</span>
                            </template>

                            <span class="ml-auto shrink-0 font-mono text-[9px] text-slate-600" x-text="node.code"></span>
                        </div>

                        <p class="mt-0.5 truncate pl-2.5 text-[9px] text-slate-500">
                            <span :class="colorOf(node.key).text" x-text="node.phase_type_label"></span>
                            · <span x-text="node.participant_contract"></span>
                        </p>

                        <p class="mt-0.5 pl-2.5 font-mono text-[8px] text-slate-600">
                            <span x-text="node.entries.length"></span> entradas ·
                            <span x-text="node.exits.length"></span> salidas
                        </p>
                    </button>

                    <div x-show="isSelected(node.key)" x-cloak class="mt-1 space-y-1">
                        @include('tournaments.super.forms.node', ['node' => 'alpine'])

                        <a :href="'/tournaments/phases/' + node.phase_template_id + '/super'"
                            class="block rounded-md border border-slate-700 px-2 py-1 text-center text-[9px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                            ✎ Editar esta fase
                        </a>

                        @include('tournaments.super.forms.delete', ['piece' => 'node', 'aviso' => '¿Quitar esta fase del torneo?'])
                    </div>
                </div>
            </template>

            <template x-if="nodes.length === 0">
                <p class="rounded-lg border border-dashed border-slate-700 px-2 py-3 text-center text-[9px] leading-relaxed text-slate-500">
                    Todavía no hay fases.<br>
                    <span class="text-slate-600">Añade una para empezar el recorrido.</span>
                </p>
            </template>

        </div>

    </section>


    {{-- ==================== FINALES ==================== --}}

    <section class="p-3">

        <div class="flex items-center gap-1.5">
            <span class="h-3 w-1 rounded-full bg-rose-400"></span>
            <h2 class="text-[10px] font-black uppercase tracking-[0.16em] text-rose-300">Finales</h2>
            <span class="font-mono text-[9px] text-slate-600" x-text="terminals.length"></span>

            <button type="button" @click="openCreate('TERMINAL')"
                class="ml-auto rounded-md border border-rose-500/40 px-1.5 py-0.5 text-[9px] font-black text-rose-300 transition hover:bg-rose-500/20">
                + nuevo
            </button>
        </div>

        <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
            Dónde acaba cada competidor.
        </p>

        <div x-show="creating === 'TERMINAL'" x-cloak class="mt-2">
            @include('tournaments.super.forms.terminal', ['terminal' => null])
        </div>

        <div class="mt-2 space-y-1">

            <template x-for="terminal in terminals" :key="terminal.key">
                <div>
                    <button type="button" @click="select(terminal.key)"
                        class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                        :class="isSelected(terminal.key)
                            ? 'border-rose-400/60 bg-rose-500/10'
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-1 shrink-0 rounded-full bg-rose-400"></span>
                            <span class="truncate text-[11px] font-black text-slate-200" x-text="terminal.name"></span>
                            <span class="ml-auto shrink-0 font-mono text-[9px] text-slate-600" x-text="terminal.code"></span>
                        </div>

                        <p class="mt-0.5 pl-2.5 text-[9px] text-slate-500">
                            <span x-text="terminal.terminal_type_label"></span>
                            · <span x-text="terminal.incoming_count"></span> llegan
                        </p>
                    </button>

                    <div x-show="isSelected(terminal.key)" x-cloak class="mt-1 space-y-1">
                        @include('tournaments.super.forms.terminal', ['terminal' => 'alpine'])
                        @include('tournaments.super.forms.delete', ['piece' => 'terminal', 'aviso' => '¿Eliminar este final?'])
                    </div>
                </div>
            </template>

            <template x-if="terminals.length === 0">
                <p class="rounded-lg border border-dashed border-rose-500/40 px-2 py-3 text-center text-[9px] leading-relaxed text-rose-300/70">
                    Sin finales, el recorrido no termina en ningún sitio.
                </p>
            </template>

        </div>

    </section>


    {{-- ==================== RUTAS ==================== --}}

    <section class="p-3">

        <div class="flex items-center gap-1.5">
            <span class="h-3 w-1 rounded-full bg-violet-400"></span>
            <h2 class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-300">Rutas</h2>
            <span class="font-mono text-[9px] text-slate-600" x-text="links.length"></span>

            <button type="button" @click="openCreate('LINK')"
                class="ml-auto rounded-md border border-violet-500/40 px-1.5 py-0.5 text-[9px] font-black text-violet-300 transition hover:bg-violet-500/20">
                + conectar
            </button>
        </div>

        <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
            Qué salida alimenta qué entrada. Sin rutas las fases están sueltas.
        </p>

        <div x-show="creating === 'LINK'" x-cloak class="mt-2">
            @include('tournaments.super.forms.connection', ['link' => null])
        </div>

        <div class="mt-2 space-y-1">

            <template x-for="link in links" :key="'l' + link.id">
                <div>
                    <button type="button" @click="openEdit('LINK:' + link.id)"
                        class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                        :class="isEditing('LINK:' + link.id)
                            ? 'border-violet-400/60 bg-violet-500/10'
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                        <div class="flex items-center gap-1">
                            <span class="h-2.5 w-1 shrink-0 rounded-full"
                                :class="colorOf(link.from).dot"></span>

                            <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-300"
                                x-text="link.from_label"></span>
                        </div>

                        <div class="flex items-center gap-1 pl-2">
                            <span class="text-[9px] text-violet-400">↳</span>

                            <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-300"
                                x-text="link.to_label"></span>

                            <span class="shrink-0 rounded bg-slate-800 px-1 py-0.5 font-mono text-[8px] text-violet-300"
                                x-text="link.allocation"></span>
                        </div>
                    </button>

                    <div x-show="isEditing('LINK:' + link.id)" x-cloak class="mt-1 space-y-1">
                        @include('tournaments.super.forms.connection', ['link' => 'alpine'])

                        <form method="POST" :action="link.delete_url"
                            @submit="confirm('¿Eliminar esta ruta?') || $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <button class="w-full rounded-md border border-slate-800 px-2 py-1 text-[9px] font-black text-slate-500 transition hover:border-rose-500 hover:text-rose-400">
                                Eliminar ruta
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <template x-if="links.length === 0">
                <p class="rounded-lg border border-dashed border-slate-700 px-2 py-3 text-center text-[9px] leading-relaxed text-slate-500">
                    Nada está conectado todavía.
                </p>
            </template>

        </div>

    </section>

</div>

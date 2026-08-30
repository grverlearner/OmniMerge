@php
    /*
     * EL TALLER — el recorrido, pero para meter mano.
     *
     * El recorrido enseña qué le pasa a la gente que llega a una fase. Aquí
     * se cambia, sin salir de la pantalla y sin volver al panel: cada puerta
     * trae sus rutas debajo, y cada ruta se retoca o se borra donde está.
     *
     * El reparto es el mismo que en el recorrido —entradas a la izquierda,
     * la fase en el centro, salidas a la derecha— a propósito: quien ya sabe
     * leer una vista sabe usar la otra. Lo único que cambia es que aquí todo
     * tiene un botón.
     *
     * Lo que se puede hacer sin irse:
     *
     *   · conectar una salida a donde sea, o una puerta desde donde sea
     *   · cambiar cuántos pasan por una ruta y en qué orden se sirve
     *   · borrar una ruta
     *   · crear, editar y borrar las puertas de entrada de la fase
     *   · renombrar la fase dentro del torneo
     *
     * Arriba, en rojo, lo que está suelto: una salida sin ruta deja gente en
     * el limbo y una puerta sin ruta deja una fase que nadie alimenta. Son
     * los dos errores fáciles de cometer y difíciles de ver.
     */
@endphp

<div class="p-3">

    <template x-if="!focused">
        <div class="rounded-xl border border-dashed border-slate-700 px-6 py-10 text-center">
            <p class="text-[11px] font-black text-slate-400">No hay ninguna fase que editar</p>
            <p class="mt-1 text-[10px] leading-relaxed text-slate-600">
                Añade una fase desde el panel de la izquierda.
            </p>
        </div>
    </template>


    <template x-if="focused">
        <div>

            @include('tournaments.super.partials.phase-nav')


            {{-- ==================== LO QUE ESTÁ SUELTO ==================== --}}

            <div class="mb-2 flex flex-wrap items-center gap-2 rounded-xl border px-3 py-1.5"
                :class="unconnectedExits(focused).length || emptyEntries(focused).length
                    ? 'border-amber-500/40 bg-amber-500/5'
                    : 'border-emerald-500/30 bg-emerald-500/5'">

                <span class="text-[10px] font-black uppercase tracking-wider"
                    :class="unconnectedExits(focused).length || emptyEntries(focused).length
                        ? 'text-amber-300' : 'text-emerald-300'"
                    x-text="unconnectedExits(focused).length || emptyEntries(focused).length
                        ? 'Esta fase tiene cabos sueltos'
                        : 'Esta fase está bien conectada'"></span>

                <template x-if="emptyEntries(focused).length">
                    <span class="rounded bg-rose-500/15 px-1.5 py-0.5 text-[9px] font-black text-rose-300">
                        <span x-text="emptyEntries(focused).length"></span>
                        <span x-text="emptyEntries(focused).length === 1 ? 'puerta sin nadie' : 'puertas sin nadie'"></span>
                    </span>
                </template>

                <template x-if="unconnectedExits(focused).length">
                    <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
                        <span x-text="unconnectedExits(focused).length"></span>
                        <span x-text="unconnectedExits(focused).length === 1 ? 'salida sin ruta' : 'salidas sin ruta'"></span>
                    </span>
                </template>

                <span class="ml-auto font-mono text-[9px] text-slate-600">
                    <span x-text="focused.entries.length"></span> puertas ·
                    <span x-text="focused.exits.length"></span> salidas
                </span>
            </div>


            <div class="grid gap-2 xl:grid-cols-[1.15fr_1fr_1.15fr]">

                {{-- ==================== POR DÓNDE ENTRAN ==================== --}}

                <div>

                    <div class="mb-1.5 flex items-center gap-1.5 rounded-lg bg-emerald-500/10 px-2 py-1">
                        <span class="text-[10px] text-emerald-400">▼</span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-300">
                            Entran por
                        </span>

                        <button type="button" @click="openBench('new-entry')"
                            class="ml-auto rounded border border-emerald-500/40 px-1.5 py-0.5 text-[9px] font-black text-emerald-300 transition hover:bg-emerald-500/20">
                            + puerta
                        </button>
                    </div>

                    <div x-show="atBench('new-entry')" x-cloak class="mb-2">
                        @include('tournaments.super.forms.entry-port', ['entry' => null])
                    </div>

                    <div class="space-y-2">

                        <template x-for="entry in focused.entries" :key="'we' + entry.id">
                            <div class="rounded-xl border p-2"
                                :class="linksToEntry(entry.id).length
                                    ? 'border-slate-800 bg-slate-900/50'
                                    : 'border-rose-500/40 bg-rose-500/5'">

                                {{-- La puerta --}}

                                <div class="flex items-center gap-1.5">
                                    <span class="h-3.5 w-1 shrink-0 rounded-full bg-emerald-400"></span>

                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-100"
                                        x-text="entry.name"></span>

                                    <template x-if="entry.is_required">
                                        <span class="shrink-0 rounded bg-rose-500/15 px-1 py-0.5 text-[8px] font-black uppercase text-rose-300">
                                            obligatoria
                                        </span>
                                    </template>

                                    <button type="button" @click="openBench('entry:' + entry.id)"
                                        class="shrink-0 text-[10px] text-slate-500 transition hover:text-emerald-300"
                                        title="Editar la puerta">✎</button>
                                </div>

                                <p class="mt-0.5 pl-2.5 text-[9px] text-slate-500">
                                    <span x-text="entry.merge_policy_label"></span>
                                </p>

                                {{--
                                    El número que hace falta para conectar.

                                    Antes aquí ponía el contrato en frase y la
                                    ruta decía «Todo», que juntos no contestan
                                    la única pregunta que se hace al conectar:
                                    cuántos me quedan por meter.
                                --}}
                                <template x-if="entryFlow(entry.id)">
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 rounded-lg bg-slate-950/70 px-2 py-1">

                                        <span class="text-[9px] text-slate-500">
                                            cabe
                                            <span class="font-mono font-black text-slate-300"
                                                x-text="entryFlow(entry.id).fits ?? '∞'"></span>
                                        </span>

                                        <span class="text-[9px] text-slate-500">
                                            llegan
                                            <span class="font-mono font-black text-emerald-300"
                                                x-text="amount(entryFlow(entry.id).arriving)"></span>
                                        </span>

                                        <template x-if="room(entryFlow(entry.id).left)">
                                            <span class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black"
                                                :class="roomTone(entryFlow(entry.id).left) + ' '
                                                    + (entryFlow(entry.id).left.over ? 'bg-rose-500/15'
                                                        : entryFlow(entry.id).left.full ? 'bg-emerald-500/15'
                                                        : 'bg-amber-500/15')"
                                                x-text="room(entryFlow(entry.id).left)"></span>
                                        </template>

                                        {{-- Si el cupo lo pone la fase y no la puerta, se dice --}}
                                        <template x-if="entryFlow(entry.id).from_phase">
                                            <span class="w-full text-[8px] text-slate-600">
                                                el cupo lo pone la fase
                                            </span>
                                        </template>

                                    </div>
                                </template>

                                {{-- Editarla --}}

                                <div x-show="atBench('entry:' + entry.id)" x-cloak class="mt-1.5 space-y-1">
                                    @include('tournaments.super.forms.entry-port', ['entry' => 'alpine'])

                                    <form method="POST" :action="entry.delete_url"
                                        data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                        data-confirm-title="Eliminar la puerta de entrada"
                                        data-confirm-message="Se irán con ella todas las rutas que llegaban aquí."
                                        :data-confirm-subject="entry.name"
                                        data-confirm-action="Sí, eliminar la puerta">
                                        @csrf
                                        @method('DELETE')
                                        <button class="w-full rounded-md border border-slate-800 px-2 py-1 text-[9px] font-black text-slate-500 transition hover:border-rose-500 hover:text-rose-400">
                                            Eliminar puerta
                                        </button>
                                    </form>
                                </div>

                                {{-- Quién llama a esta puerta --}}

                                <div class="mt-1.5 space-y-1 border-t border-slate-800 pt-1.5">

                                    <template x-for="link in linksToEntry(entry.id)" :key="'wel' + link.id">
                                        <div>
                                            <div class="flex items-center gap-1 rounded-lg bg-slate-950/60 px-2 py-1">
                                                <span class="text-[10px] text-violet-400">↳</span>

                                                <span class="min-w-0 flex-1 truncate text-[9px] font-bold text-slate-300"
                                                    x-text="link.from_label"></span>

                                                {{--
                                                    Saltar a la fase de la que viene esta gente.
                                                    Solo aparece si al otro lado hay una fase: de
                                                    una entrada del torneo no hay a donde ir.
                                                --}}
                                                <template x-if="linkOrigin(link)">
                                                    <button type="button" @click="goTo(linkOrigin(link))"
                                                        class="shrink-0 rounded px-1 text-[10px] text-slate-500 transition hover:bg-slate-800 hover:text-sky-300"
                                                        :title="'Ir a ' + (pieceOf(linkOrigin(link))?.name ?? '')">↰</button>
                                                </template>

                                                <span class="shrink-0 rounded bg-violet-500/20 px-1 font-mono text-[8px] font-black text-violet-200"
                                                    x-text="link.allocation"></span>

                                                {{--
                                                    La prioridad solo importa cuando hay más de
                                                    una ruta compitiendo por la misma gente. Con
                                                    una sola es ruido.

                                                    La condición nombra SOLO la variable de esta
                                                    columna: `entry` no existe en la de salidas
                                                    ni `exit` en la de entradas, y nombrar la que
                                                    no toca revienta la expresión entera —el `?.`
                                                    no salva a una variable que no está definida—.
                                                --}}
                                                <template x-if="linksToEntry(entry.id).length > 1">
                                                    <span class="shrink-0 rounded bg-slate-800 px-1 font-mono text-[8px] text-slate-400"
                                                        :title="'Prioridad ' + link.priority"
                                                        x-text="'#' + link.priority"></span>
                                                </template>

                                                <button type="button" @click="openBench('link:' + link.id)"
                                                    class="shrink-0 text-[10px] text-slate-500 transition hover:text-violet-300"
                                                    title="Ajustar la ruta">✎</button>

                                                <form method="POST" class="shrink-0" :action="link.delete_url"
                                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                                    data-confirm-title="Eliminar la ruta"
                                                    data-confirm-message="Los participantes dejarán de pasar por aquí."
                                                    :data-confirm-subject="link.label ?? "Ruta""
                                                    data-confirm-action="Sí, eliminar la ruta">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-[11px] text-slate-600 transition hover:text-rose-400"
                                                        title="Eliminar la ruta">×</button>
                                                </form>
                                            </div>

                                            <div x-show="atBench('link:' + link.id)" x-cloak>
                                                @include('tournaments.super.forms.link-edit')
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="linksToEntry(entry.id).length === 0">
                                        <p class="px-2 py-1 text-[9px] leading-relaxed text-rose-300/70">
                                            No llega nadie por aquí.
                                        </p>
                                    </template>

                                    <button type="button" @click="openBench('to:' + entry.id)"
                                        class="w-full rounded-md border border-dashed border-violet-500/40 px-2 py-1 text-[9px] font-black text-violet-300 transition hover:bg-violet-500/10">
                                        + conectar algo a esta puerta
                                    </button>

                                    <div x-show="atBench('to:' + entry.id)" x-cloak>
                                        @include('tournaments.super.forms.quick-link', ['lado' => 'TO'])
                                    </div>

                                </div>

                            </div>
                        </template>

                        <template x-if="focused.entries.length === 0">
                            <p class="rounded-xl border border-dashed border-rose-500/40 px-3 py-6 text-center text-[9px] leading-relaxed text-rose-300/70">
                                Esta fase no tiene puertas de entrada.<br>
                                <span class="text-slate-600">Nadie puede llegar a ella.</span>
                            </p>
                        </template>

                    </div>

                </div>


                {{-- ==================== LA FASE ==================== --}}

                <div class="rounded-2xl border-2 p-3"
                    :class="colorOf(focus).border + ' ' + colorOf(focus).wash">

                    <div class="flex items-center gap-2">
                        <span class="h-7 w-1.5 rounded-full" :class="colorOf(focus).solid"></span>

                        <div class="min-w-0 flex-1">
                            <p class="text-[9px] font-black uppercase tracking-[0.16em]"
                                :class="colorOf(focus).text" x-text="focused.phase_type_label"></p>
                            <h3 class="truncate text-base font-black text-slate-100" x-text="focused.name"></h3>
                        </div>

                        <template x-if="isConverging(focus)">
                            <span class="shrink-0 text-[11px] text-violet-400" title="Aquí se junta el camino">⑂</span>
                        </template>

                        <template x-if="isBranching(focus)">
                            <span class="shrink-0 text-[11px] text-amber-400" title="Aquí se abre el camino">⑃</span>
                        </template>
                    </div>

                    <p class="mt-0.5 text-[9px] text-slate-500">
                        <span x-text="focused.phase_template_name"></span>
                    </p>

                    <template x-if="nodeFlow(focused.id)">
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 rounded-lg bg-slate-950/70 px-2 py-1.5">

                            <span class="text-[10px] text-slate-500">
                                admite
                                <span class="font-mono text-sm font-black text-slate-200"
                                    x-text="nodeFlow(focused.id).fits ?? '∞'"></span>
                            </span>

                            <span class="text-[10px] text-slate-500">
                                le llegan
                                <span class="font-mono text-sm font-black text-emerald-300"
                                    x-text="amount(nodeFlow(focused.id).receives)"></span>
                            </span>

                            <template x-if="room(nodeFlow(focused.id).left)">
                                <span class="ml-auto rounded px-2 py-0.5 text-[10px] font-black"
                                    :class="roomTone(nodeFlow(focused.id).left) + ' '
                                        + (nodeFlow(focused.id).left.over ? 'bg-rose-500/15'
                                            : nodeFlow(focused.id).left.full ? 'bg-emerald-500/15'
                                            : 'bg-amber-500/15')"
                                    x-text="room(nodeFlow(focused.id).left)"></span>
                            </template>

                        </div>
                    </template>

                    {{-- La silueta --}}

                    <div class="mt-2 flex items-center justify-center rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-3">
                        <div class="scale-150">
                            @include('tournaments.super.partials.outline', ['piece' => 'focused'])
                        </div>
                    </div>

                    {{-- Quién compite --}}

                    <div class="mt-2 flex flex-wrap gap-0.5">
                        <template x-for="(face, fi) in facesFor(focus, 10)" :key="'wcf' + fi">
                            <span class="h-6 w-6 overflow-hidden rounded-md bg-slate-800 ring-1"
                                :class="colorOf(focus).ring" :title="face.name">
                                <template x-if="face.image_url">
                                    <img :src="face.image_url" alt="" class="h-full w-full object-cover">
                                </template>
                            </span>
                        </template>
                    </div>

                    {{-- Renombrarla dentro del torneo --}}

                    <div class="mt-2 space-y-1">

                        <button type="button" @click="openBench('node')"
                            class="w-full rounded-md border border-slate-700 px-2 py-1 text-[9px] font-black text-slate-400 transition hover:border-sky-500 hover:text-sky-300">
                            ✎ Renombrar en este torneo
                        </button>

                        <div x-show="atBench('node')" x-cloak>
                            <form method="POST" class="space-y-1.5 rounded-lg border border-sky-500/40 bg-slate-950/70 p-2"
                                :action="focused.update_url">

                                @csrf
                                @method('PUT')

                                <input type="text" name="name" required maxlength="120" :value="focused.name"
                                    class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-sky-500 focus:ring-sky-500">

                                <input type="text" name="description" maxlength="255" :value="focused.description"
                                    placeholder="Descripción (opcional)"
                                    class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-300 focus:border-sky-500 focus:ring-sky-500">

                                <p class="text-[9px] leading-relaxed text-slate-600">
                                    El nombre es de este torneo. La fase en sí no cambia.
                                </p>

                                <button class="w-full rounded-md bg-sky-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-sky-500">
                                    Guardar
                                </button>
                            </form>
                        </div>

                        <a :href="'/tournaments/phases/' + focused.phase_template_id + '/super'"
                            class="block rounded-md border border-slate-700 px-2 py-1 text-center text-[9px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                            ⛯ Editar la fase en sí
                        </a>
                    </div>

                </div>


                {{-- ==================== POR DÓNDE SALEN ==================== --}}

                <div>

                    <div class="mb-1.5 flex items-center gap-1.5 rounded-lg bg-violet-500/10 px-2 py-1">
                        <span class="text-[10px] text-violet-400">▲</span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-violet-300">
                            Salen por
                        </span>

                        <a :href="'/tournaments/phases/' + focused.phase_template_id + '/super'"
                            class="ml-auto rounded border border-violet-500/40 px-1.5 py-0.5 text-[9px] font-black text-violet-300 transition hover:bg-violet-500/20"
                            title="Las salidas se crean en la fase, no en el torneo">
                            + salida
                        </a>
                    </div>

                    <div class="space-y-2">

                        <template x-for="exit in focused.exits" :key="'wx' + exit.id">
                            <div class="rounded-xl border p-2"
                                :class="linksFromExit(focus, exit.id).length
                                    ? 'border-slate-800 bg-slate-900/50'
                                    : 'border-amber-500/40 bg-amber-500/5'">

                                <div class="flex items-center gap-1.5">
                                    <span class="h-3.5 w-1 shrink-0 rounded-full bg-violet-400"></span>

                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-100"
                                        x-text="exit.name"></span>

                                    <span class="shrink-0 font-mono text-[8px] text-violet-300"
                                        x-text="exit.flow_forecast_label"></span>
                                </div>

                                <p class="mt-0.5 pl-2.5 text-[9px] text-slate-500" x-text="exit.selector"></p>

                                <template x-if="exitFlow(focus, exit.id)">
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 rounded-lg bg-slate-950/70 px-2 py-1">

                                        <span class="text-[9px] text-slate-500">
                                            produce
                                            <span class="font-mono font-black text-violet-300"
                                                x-text="amount(exitFlow(focus, exit.id).produces)"></span>
                                        </span>

                                        <span class="text-[9px] text-slate-500">
                                            encamina
                                            <span class="font-mono font-black"
                                                :class="linksFromExit(focus, exit.id).length ? 'text-emerald-300' : 'text-slate-600'"
                                                x-text="amount(exitFlow(focus, exit.id).routed)"></span>
                                        </span>

                                        <template x-if="room(exitFlow(focus, exit.id).left)">
                                            <span class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black"
                                                :class="roomTone(exitFlow(focus, exit.id).left) + ' '
                                                    + (exitFlow(focus, exit.id).left.over ? 'bg-rose-500/15'
                                                        : exitFlow(focus, exit.id).left.full ? 'bg-emerald-500/15'
                                                        : 'bg-amber-500/15')"
                                                x-text="exitFlow(focus, exit.id).left.full
                                                    ? 'todo encaminado'
                                                    : room(exitFlow(focus, exit.id).left) + ' por encaminar'"></span>
                                        </template>

                                    </div>
                                </template>

                                {{-- A dónde lleva --}}

                                <div class="mt-1.5 space-y-1 border-t border-slate-800 pt-1.5">

                                    <template x-for="link in linksFromExit(focus, exit.id)" :key="'wxl' + link.id">
                                        <div>
                                            <div class="flex items-center gap-1 rounded-lg bg-slate-950/60 px-2 py-1">
                                                <span class="text-[10px] text-violet-400">↳</span>

                                                <span class="min-w-0 flex-1 truncate text-[9px] font-bold text-slate-300"
                                                    x-text="link.to_label"></span>

                                                {{-- Saltar a la fase a la que lleva, si lleva a una --}}
                                                <template x-if="linkTarget(link)">
                                                    <button type="button" @click="goTo(linkTarget(link))"
                                                        class="shrink-0 rounded px-1 text-[10px] text-slate-500 transition hover:bg-slate-800 hover:text-sky-300"
                                                        :title="'Ir a ' + (pieceOf(linkTarget(link))?.name ?? '')">↳</button>
                                                </template>

                                                <span class="shrink-0 rounded bg-violet-500/20 px-1 font-mono text-[8px] font-black text-violet-200"
                                                    x-text="link.allocation"></span>

                                                {{--
                                                    La prioridad solo importa cuando hay más de
                                                    una ruta compitiendo por la misma gente. Con
                                                    una sola es ruido.

                                                    La condición nombra SOLO la variable de esta
                                                    columna: `entry` no existe en la de salidas
                                                    ni `exit` en la de entradas, y nombrar la que
                                                    no toca revienta la expresión entera —el `?.`
                                                    no salva a una variable que no está definida—.
                                                --}}
                                                <template x-if="linksFromExit(focus, exit.id).length > 1">
                                                    <span class="shrink-0 rounded bg-slate-800 px-1 font-mono text-[8px] text-slate-400"
                                                        :title="'Prioridad ' + link.priority"
                                                        x-text="'#' + link.priority"></span>
                                                </template>

                                                <button type="button" @click="openBench('link:' + link.id)"
                                                    class="shrink-0 text-[10px] text-slate-500 transition hover:text-violet-300"
                                                    title="Ajustar la ruta">✎</button>

                                                <form method="POST" class="shrink-0" :action="link.delete_url"
                                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                                    data-confirm-title="Eliminar la ruta"
                                                    data-confirm-message="Los participantes dejarán de pasar por aquí."
                                                    :data-confirm-subject="link.label ?? "Ruta""
                                                    data-confirm-action="Sí, eliminar la ruta">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-[11px] text-slate-600 transition hover:text-rose-400"
                                                        title="Eliminar la ruta">×</button>
                                                </form>
                                            </div>

                                            <div x-show="atBench('link:' + link.id)" x-cloak>
                                                @include('tournaments.super.forms.link-edit')
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="linksFromExit(focus, exit.id).length === 0">
                                        <p class="px-2 py-1 text-[9px] font-bold leading-relaxed text-amber-300">
                                            Nadie sale por aquí: esta gente se queda en el limbo.
                                        </p>
                                    </template>

                                    <button type="button" @click="openBench('from:' + exit.id)"
                                        class="w-full rounded-md border border-dashed border-violet-500/40 px-2 py-1 text-[9px] font-black text-violet-300 transition hover:bg-violet-500/10">
                                        + conectar esta salida
                                    </button>

                                    <div x-show="atBench('from:' + exit.id)" x-cloak>
                                        @include('tournaments.super.forms.quick-link', ['lado' => 'FROM'])
                                    </div>

                                </div>

                            </div>
                        </template>

                        <template x-if="focused.exits.length === 0">
                            <p class="rounded-xl border border-dashed border-amber-500/40 px-3 py-6 text-center text-[9px] leading-relaxed text-amber-300/70">
                                Esta fase no tiene salidas.<br>
                                <span class="text-slate-600">Créalas en su propia Super Edición.</span>
                            </p>
                        </template>

                    </div>

                </div>

            </div>

        </div>
    </template>

</div>

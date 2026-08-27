@php
    /*
     * Alta o edición de una ruta.
     *
     * Una ruta va DE una salida A una entrada. El origen puede ser una
     * entrada del torneo o la salida de una fase; el destino, la puerta de
     * una fase o un final. Cuatro combinaciones, y el formulario enseña solo
     * los campos de la que estés eligiendo: pedir un `source_phase_exit_id`
     * cuando el origen es una entrada del torneo era la forma más rápida de
     * crear una ruta imposible.
     *
     * Los desplegables se llenan desde el payload, así que solo ofrecen
     * piezas que existen de verdad.
     */
    $editando = ($link ?? null) === 'alpine';
@endphp

<form method="POST" class="space-y-1.5 rounded-lg border border-violet-500/30 bg-slate-950/60 p-2"
    @if ($editando)
        :action="link.update_url"
    @else
        action="{{ route('tournaments.graph.connections.store', $tournamentTemplate) }}"
    @endif
    x-data="{
        origen: @if ($editando) (link.exit_id ? 'PHASE_EXIT' : 'START') @else 'START' @endif,
        nodoOrigen: @if ($editando) String(link.from).startsWith('NODE:') ? String(link.from).slice(5) : '' @else '' @endif,
        destino: @if ($editando) (link.terminal_id ? 'TERMINAL' : 'ENTRY_PORT') @else 'ENTRY_PORT' @endif,
        reparto: @if ($editando) link.allocation_mode @else 'ALL' @endif,

        get salidas() {
            const nodo = nodes.find((n) => n.key === 'NODE:' + this.nodoOrigen);

            return nodo?.exits ?? [];
        },
    }">

    @csrf
    @if ($editando) @method('PUT') @endif


    {{-- ============ DE DÓNDE SALE ============ --}}

    <div class="rounded-md border border-slate-800 bg-slate-950/60 p-1.5">

        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Sale de</p>

        <select name="source_type" x-model="origen"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            <option value="START">Una entrada del torneo</option>
            <option value="PHASE_EXIT">La salida de una fase</option>
        </select>

        <select name="source_start_id" x-show="origen === 'START'" :disabled="origen !== 'START'"
            class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            <template x-for="s in starts" :key="'cs' + s.id">
                <option :value="s.id" x-text="s.name"></option>
            </template>
        </select>

        <div x-show="origen === 'PHASE_EXIT'" x-cloak class="mt-1 space-y-1">

            <select name="source_node_id" x-model="nodoOrigen" :disabled="origen !== 'PHASE_EXIT'"
                class="w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                <option value="">— qué fase —</option>
                <template x-for="n in nodes" :key="'cn' + n.id">
                    <option :value="n.id" x-text="n.name"></option>
                </template>
            </select>

            <select name="source_phase_exit_id" :disabled="origen !== 'PHASE_EXIT'"
                class="w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                <option value="">— por qué salida —</option>
                <template x-for="e in salidas" :key="'ce' + e.id">
                    <option :value="e.id" x-text="e.name + ' · ' + e.selector"></option>
                </template>
            </select>

            <p x-show="nodoOrigen && salidas.length === 0" x-cloak
                class="text-[9px] leading-relaxed text-amber-300">
                Esa fase no tiene salidas activas. Créalas en su Super Edición.
            </p>
        </div>

    </div>


    {{-- ============ A DÓNDE LLEGA ============ --}}

    <div class="rounded-md border border-slate-800 bg-slate-950/60 p-1.5">

        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Llega a</p>

        <select name="target_type" x-model="destino"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            <option value="ENTRY_PORT">La entrada de una fase</option>
            <option value="TERMINAL">Un final del torneo</option>
        </select>

        <select name="target_entry_port_id" x-show="destino === 'ENTRY_PORT'" :disabled="destino !== 'ENTRY_PORT'"
            class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            <option value="">— qué entrada —</option>
            <template x-for="n in nodes" :key="'cte' + n.id">
                <optgroup :label="n.name">
                    <template x-for="e in n.entries" :key="'ctp' + e.id">
                        <option :value="e.id" x-text="e.name + ' · ' + e.contract"></option>
                    </template>
                </optgroup>
            </template>
        </select>

        <select name="target_terminal_id" x-show="destino === 'TERMINAL'" :disabled="destino !== 'TERMINAL'" x-cloak
            class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            <option value="">— qué final —</option>
            <template x-for="t in terminals" :key="'ctt' + t.id">
                <option :value="t.id" x-text="t.name + ' · ' + t.terminal_type_label"></option>
            </template>
        </select>

    </div>


    {{-- ============ CUÁNTOS PASAN ============ --}}

    <div class="grid grid-cols-3 gap-1.5">

        <label class="col-span-2">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuántos pasan</span>
            <select name="allocation_mode" x-model="reparto"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                <option value="ALL">Todos los que salen</option>
                <option value="TAKE_N">Solo los N primeros</option>
                <option value="PERCENTAGE">Un porcentaje</option>
                <option value="REMAINDER">Los que sobren</option>
            </select>
        </label>

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Valor</span>
            <input type="number" name="allocation_value" min="1" max="1000"
                :disabled="reparto === 'ALL' || reparto === 'REMAINDER'"
                @if ($editando) :value="link.allocation_value" @endif
                placeholder="—"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-[10px] font-bold text-slate-100 focus:border-violet-500 focus:ring-violet-500 disabled:opacity-30">
        </label>

    </div>

    <label class="flex items-center gap-1.5">
        <span class="shrink-0 text-[9px] font-black uppercase tracking-wider text-slate-500">Prioridad</span>
        <input type="number" name="priority" min="1" max="999"
            @if ($editando) :value="link.priority ?? 10" @else value="10" @endif
            class="w-12 rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-[10px] text-slate-100">
        <span class="text-[9px] leading-tight text-slate-600">
            Si dos rutas compiten por el mismo, gana la del número más bajo.
        </span>
    </label>

    <button class="w-full rounded-md bg-violet-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
        {{ $editando ? 'Guardar ruta' : 'Crear ruta' }}
    </button>

</form>

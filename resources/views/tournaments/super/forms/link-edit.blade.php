@php
    /*
     * Retocar una ruta que ya existe, sin salir del taller.
     *
     * Se editan el reparto y la prioridad, que es lo que de verdad se
     * ajusta una y otra vez: «de aquí pasan 4, no todos», «esta ruta se
     * sirve antes que aquella».
     *
     * Los extremos NO se editan aquí. Cambiar a dónde va una ruta es
     * cambiar el recorrido, no afinarlo, y hacerlo con dos desplegables
     * escondidos en una fila pequeña es la mejor forma de romper un torneo
     * sin darse cuenta. Para eso está borrar y volver a conectar, que se ve.
     *
     * Los extremos viajan igualmente en campos ocultos porque el servidor
     * los valida siempre: una ruta sin origen no es una ruta.
     */
@endphp

<form method="POST" class="mt-1 rounded-lg border border-violet-500/40 bg-slate-950/70 p-2"
    :action="link.update_url"
    x-data="{ reparto: link.allocation_mode }">

    @csrf
    @method('PUT')

    {{-- Los extremos, tal y como están --}}
    <input type="hidden" name="source_type" :value="link.exit_id ? 'PHASE_EXIT' : 'START'">
    <input type="hidden" name="source_start_id"
        :value="String(link.from).startsWith('START:') ? String(link.from).slice(6) : ''">
    <input type="hidden" name="source_node_id"
        :value="String(link.from).startsWith('NODE:') ? String(link.from).slice(5) : ''">
    <input type="hidden" name="source_phase_exit_id" :value="link.exit_id ?? ''">
    <input type="hidden" name="target_type" :value="link.terminal_id ? 'TERMINAL' : 'ENTRY_PORT'">
    <input type="hidden" name="target_entry_port_id" :value="link.entry_id ?? ''">
    <input type="hidden" name="target_terminal_id" :value="link.terminal_id ?? ''">

    {{--
        Cuántos pasan, y en qué orden se sirve esta ruta.

        El campo del número aparece SOLO cuando el reparto lo usa, y con la
        etiqueta de lo que significa en cada caso —«cuántos» no es lo mismo
        que «qué porcentaje»—. Antes estaba siempre, gris y rotulado
        «Valor», que es una caja muerta que no explica nada: con «todos»
        seleccionado parecía que no hubiera forma de poner una cantidad.

        La prioridad estaba fija en 10 y escondida. Importa cuando varias
        rutas se reparten la misma gente: la del número más bajo se sirve
        primero, y con eso se decide quién se queda con los mejores.
    --}}

    <div class="mt-1.5 rounded-md border border-slate-800 bg-slate-950/60 p-1.5">

        <label class="block">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Cuántos pasan
            </span>

            <select name="allocation_mode" x-model="reparto"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                <option value="ALL">Todos los que salen</option>
                <option value="TAKE_N">Solo los N primeros</option>
                <option value="PERCENTAGE">Un porcentaje</option>
                <option value="REMAINDER">Los que sobren</option>
            </select>
        </label>

        <label class="mt-1.5 block" x-show="reparto === 'TAKE_N' || reparto === 'PERCENTAGE'" x-cloak>
            <span class="text-[9px] font-black uppercase tracking-wider text-violet-300"
                x-text="reparto === 'TAKE_N' ? 'Cuántos exactamente' : 'Qué porcentaje'"></span>

            <div class="mt-0.5 flex items-center gap-1.5">
                <input type="number" name="allocation_value"
                    :min="1" :max="reparto === 'PERCENTAGE' ? 100 : 1000"
                    :disabled="reparto !== 'TAKE_N' && reparto !== 'PERCENTAGE'"
                    :value="link.allocation_value"
                    class="w-20 rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-center text-[11px] font-black text-slate-100 focus:border-violet-500 focus:ring-violet-500">

                <span class="text-[9px] text-slate-500"
                    x-text="reparto === 'TAKE_N' ? 'participantes' : '% de los que salen'"></span>
            </div>
        </label>

        <p class="mt-1 text-[9px] leading-relaxed text-slate-600"
            x-text="{
                ALL: 'Pasa todo el que salga por ahí.',
                TAKE_N: 'Pasan los N primeros según la clasificación de la fase.',
                PERCENTAGE: 'Pasa ese porcentaje, redondeando hacia abajo.',
                REMAINDER: 'Pasa lo que no se hayan llevado las otras rutas.',
            }[reparto]"></p>

    </div>


    <label class="mt-1.5 flex items-center gap-1.5 rounded-md border border-slate-800 bg-slate-950/60 p-1.5">

        <span class="shrink-0 text-[9px] font-black uppercase tracking-wider text-slate-500">
            Prioridad
        </span>

        <input type="number" name="priority" min="1" max="999" :value="link.priority ?? 10"
            class="w-14 shrink-0 rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-[11px] font-black text-slate-100 focus:border-violet-500 focus:ring-violet-500">

        <span class="text-[9px] leading-relaxed text-slate-600">
            Si varias rutas se reparten la misma gente, se sirve antes la del
            número más bajo.
        </span>

    </label>

    <button class="mt-1.5 w-full rounded-md bg-violet-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
        Guardar ruta
    </button>

</form>

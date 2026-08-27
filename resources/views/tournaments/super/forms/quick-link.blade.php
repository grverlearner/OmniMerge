@php
    /*
     * Conectar algo, con un extremo ya puesto.
     *
     * En el taller siempre se conecta DESDE algo o HACIA algo concreto —esta
     * salida, esta puerta— y por eso el formulario del panel, que pregunta
     * los dos extremos desde cero, sobra aquí: la mitad de sus preguntas ya
     * tienen respuesta antes de abrirlo.
     *
     * $lado dice qué extremo viene dado:
     *
     *   'FROM'  se sale de una salida de la fase, y se elige el destino
     *   'TO'    se llega a una puerta de la fase, y se elige el origen
     *
     * El otro extremo se elige de una lista de piezas que EXISTEN, con lo
     * que no hay forma de escribir una ruta imposible.
     */
@endphp

<form method="POST" class="rounded-lg border border-violet-500/40 bg-slate-950/70 p-2"
    action="{{ route('tournaments.graph.connections.store', $tournamentTemplate) }}"
    x-data="{
        /* La otra punta, por su llave */
        otro: '',
        reparto: 'ALL',

        /*
         * La opción entera, no trozos de su llave.
         *
         * Antes esto partia la cadena y se quedaba con los pedazos, y eso
         * dejo de funcionar en cuanto una llave necesito tres partes
         * -'EXIT:fase:salida'-. Leer el objeto elegido no se rompe cuando
         * cambia el formato de la llave.
         */
        /* Enseñar también los que ya están llenos, si se pide */
        verLlenos: false,

        get lista() {
            @if ($lado === 'FROM')
                return this.verLlenos ? destinations : openDestinations;
            @else
                return origins;
            @endif
        },

        get elegido() {
            return optionOf({{ $lado === 'FROM' ? 'destinations' : 'origins' }}, this.otro);
        },

        get tipo() {
            return this.elegido?.type ?? '';
        },

        get id() {
            return this.elegido?.id ?? '';
        },

        get nodo() {
            return this.elegido?.nodeId ?? '';
        },
    }">

    @csrf

    @if ($lado === 'FROM')
        {{-- El origen viene dado: esta salida de esta fase --}}
        <input type="hidden" name="source_type" value="PHASE_EXIT">
        <input type="hidden" name="source_node_id" :value="focused.id">
        <input type="hidden" name="source_phase_exit_id" :value="exit.id">

        <input type="hidden" name="target_type" :value="tipo || 'ENTRY_PORT'">
        <input type="hidden" name="target_entry_port_id" :value="tipo === 'ENTRY_PORT' ? id : ''">
        <input type="hidden" name="target_terminal_id" :value="tipo === 'TERMINAL' ? id : ''">
    @else
        {{-- El destino viene dado: esta puerta de esta fase --}}
        <input type="hidden" name="target_type" value="ENTRY_PORT">
        <input type="hidden" name="target_entry_port_id" :value="entry.id">

        <input type="hidden" name="source_type" :value="tipo || 'START'">
        <input type="hidden" name="source_start_id" :value="tipo === 'START' ? id : ''">
        <input type="hidden" name="source_node_id" :value="tipo === 'PHASE_EXIT' ? nodo : ''">
        <input type="hidden" name="source_phase_exit_id" :value="tipo === 'PHASE_EXIT' ? id : ''">
    @endif


    <label class="block">
        <span class="text-[9px] font-black uppercase tracking-wider text-violet-300">
            {{ $lado === 'FROM' ? '¿A dónde va?' : '¿De dónde viene?' }}
        </span>

        <select x-model="otro" required
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            <option value="">— elige —</option>

            <template x-for="grupo in [...new Set(lista.map(o => o.group))]" :key="'g' + grupo">
                <optgroup :label="grupo">
                    <template x-for="opcion in lista.filter(o => o.group === grupo)"
                        :key="opcion.value">
                        <option :value="opcion.value"
                            x-text="opcion.label + (opcion.hint ? ' · ' + opcion.hint : '')"></option>
                    </template>
                </optgroup>
            </template>
        </select>

        @if ($lado === 'FROM')
            {{--
                Los destinos llenos no se ofrecen: conectarlos crearía una
                ruta que el diagnóstico rechazaría acto seguido.

                Pero no desaparecen del todo. Esconder una opción sin dejar
                forma de llegar a ella convierte un atajo en un callejón, y
                hay motivos legítimos para querer una: cambiar el reparto de
                lo que ya llega, por ejemplo.
            --}}
            <div class="mt-1 flex items-center gap-1.5">
                <template x-if="destinations.length > openDestinations.length">
                    <label class="flex cursor-pointer items-center gap-1">
                        <input type="checkbox" x-model="verLlenos"
                            class="h-2.5 w-2.5 rounded border-slate-600 bg-slate-950 text-violet-500 focus:ring-violet-500">
                        <span class="text-[9px] text-slate-500">
                            ver también
                            <span x-text="destinations.length - openDestinations.length"></span>
                            que ya están llenos
                        </span>
                    </label>
                </template>

                <template x-if="openDestinations.length === 0 && !verLlenos">
                    <span class="text-[9px] font-bold text-amber-300">
                        Todos los destinos están llenos.
                    </span>
                </template>
            </div>
        @endif
    </label>


    {{--
        El aviso que antes solo llegaba al pulsar Conectar.

        Un origen que ya reparte «todos» no admite una segunda rama, y
        enterarse después de haber elegido destino y cantidad es tarde.
    --}}

    @php
        $origen = $lado === 'FROM' ? "'EXIT:' + focused.id + ':' + exit.id" : 'otro';
    @endphp

    <template x-if="catchAllFrom({{ $origen }})">
        <div class="mt-1.5 rounded-md bg-amber-500/10 px-2 py-1.5">
            <p class="text-[9px] font-bold leading-relaxed text-amber-300">
                @if ($lado === 'FROM')
                    Esta salida ya manda <strong>todos</strong> a
                    <span x-text="catchAllFrom({{ $origen }}).to_label"></span>.
                @else
                    Ese origen ya manda <strong>todos</strong> a
                    <span x-text="catchAllFrom({{ $origen }}).to_label"></span>.
                @endif
            </p>

            <p class="mt-0.5 text-[9px] leading-relaxed text-amber-200/70">
                Para repartir entre varios destinos, cambia primero esa ruta
                a una cantidad o un porcentaje. Mandar a todos a un sitio y
                además a otro no puede cumplirse.
            </p>
        </div>
    </template>


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

        <input type="number" name="priority" min="1" max="999" value="10"
            class="w-14 shrink-0 rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-[11px] font-black text-slate-100 focus:border-violet-500 focus:ring-violet-500">

        <span class="text-[9px] leading-relaxed text-slate-600">
            Si varias rutas se reparten la misma gente, se sirve antes la del
            número más bajo.
        </span>

    </label>

    <button :disabled="!otro"
        class="mt-1.5 w-full rounded-md bg-violet-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-40">
        Conectar
    </button>

</form>

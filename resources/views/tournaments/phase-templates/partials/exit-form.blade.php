@php
    $editingExit = isset($phaseExit) && $phaseExit;

    $exitAction = $editingExit
        ? route('tournaments.phase-exits.update', [$phaseTemplate, $phaseExit])
        : route('tournaments.phase-exits.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $exitAction }}" class="space-y-4">

    @csrf

    @if (!empty($returnTo))
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
    @endif

    @if ($editingExit)
        @method('PUT')
    @endif

    {{-- NAME --}}

    <div>

        <label class="text-xs font-black uppercase text-slate-500">
            Nombre *
        </label>

        <input type="text" name="name" value="{{ old('name', $editingExit ? $phaseExit->name : '') }}"
            placeholder="Ej. Clasificados"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

        <x-input-error :messages="$errors->get('name')" class="mt-2" />

    </div>

    {{-- DESCRIPTION --}}

    <div>

        <label class="text-xs font-black uppercase text-slate-500">
            Descripción
        </label>

        <textarea name="description" rows="3" placeholder="Explica quiénes salen por esta puerta..."
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">{{ old('description', $editingExit ? $phaseExit->description : '') }}</textarea>

    </div>

    {{-- SELECTOR --}}

    <div x-data="{
        selector: @js(old('selector_type', $editingExit ? $phaseExit->selector_type : ($phaseTemplate->phase_type === 'SINGLE_ELIMINATION' ? 'SURVIVORS' : (in_array($phaseTemplate->phase_type, ['GROUP_STAGE', 'SWISS'], true) ? 'ENGINE_RULES' : 'MATCH_WINNERS')))),
    
        timing: @js(old('exit_timing', $editingExit ? $phaseExit->exit_timing : 'PHASE_END'))
    }">

        <label class="text-xs font-black uppercase text-slate-500">
            ¿Quién pertenece a esta salida?
        </label>
        <p class="mt-1 text-[11px] leading-5 text-slate-400">
            El selector decide qué participantes produce esta puerta; la conexión del Tournament Graph decidirá a dónde irán después.
        </p>

        <select name="selector_type" x-model="selector"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

            @if ($phaseTemplate->phase_type === 'SINGLE_ELIMINATION')
                <optgroup label="Eliminación directa">

                    <option value="SURVIVORS">
                        Supervivientes al finalizar
                    </option>

                    <option value="ELIMINATED">
                        Todos los eliminados
                    </option>

                    <option value="ELIMINATED_IN_ROUND">
                        Eliminados en una ronda específica
                    </option>

                </optgroup>
            @endif

            @if ($phaseTemplate->phase_type === 'GROUP_STAGE')
                <optgroup label="Fase de grupos">

                    <option value="ENGINE_RULES">
                        Definida por reglas del Group Stage Engine
                    </option>

                </optgroup>
            @endif

            @if ($phaseTemplate->phase_type === 'SWISS')
                <optgroup label="Sistema Suizo">

                    <option value="ENGINE_RULES">
                        Definida por reglas del Swiss Engine
                    </option>

                </optgroup>
            @endif

            <optgroup label="Selectores generales">

                <option value="MATCH_WINNERS">
                    Ganadores de enfrentamientos
                </option>

                <option value="MATCH_LOSERS">
                    Perdedores de enfrentamientos
                </option>

                <option value="TOP_N">
                    Mejores N
                </option>

                <option value="BOTTOM_N">
                    Últimos N
                </option>

                <option value="RANK_POSITION">
                    Posición específica
                </option>

                <option value="RANK_RANGE">
                    Rango de posiciones
                </option>

                <option value="ALL">
                    Todos
                </option>

                <option value="REMAINING">
                    Restantes
                </option>

            </optgroup>

        </select>

        {{-- GENERIC SELECTOR FROM --}}

        <div x-show="['TOP_N', 'BOTTOM_N', 'RANK_POSITION', 'RANK_RANGE'].includes(selector)" class="mt-4">

            <label class="text-xs font-black uppercase text-slate-500"
                x-text="
                    selector === 'RANK_RANGE'
                        ? 'Desde posición'
                        : selector === 'RANK_POSITION'
                            ? 'Posición'
                            : 'Cantidad'
                ">
            </label>

            <input type="number" name="selector_from" min="1" max="512"
                value="{{ old('selector_from', $editingExit ? $phaseExit->selector_from : '') }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

            <p class="mt-1 text-[11px] leading-5 text-slate-400"
                x-text="['TOP_N', 'BOTTOM_N'].includes(selector)
                    ? 'Indica cuántos participantes se seleccionan.'
                    : 'Indica la primera posición de la clasificación que pertenece a esta salida.'">
            </p>

        </div>

        {{-- RANGE TO --}}

        <div x-show="selector === 'RANK_RANGE'" class="mt-4">

            <label class="text-xs font-black uppercase text-slate-500">
                Hasta posición
            </label>

            <input type="number" name="selector_to" min="1" max="512"
                value="{{ old('selector_to', $editingExit ? $phaseExit->selector_to : '') }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

        </div>

        {{-- ELIMINATION ROUND --}}

        <div x-show="selector === 'ELIMINATED_IN_ROUND'" class="mt-4">

            <label class="text-xs font-black uppercase text-slate-500">
                ¿En qué ronda?
            </label>

            <select name="selector_round_size"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

                @foreach ([
        2 => 'Final',
        4 => 'Semifinal',
        8 => 'Cuartos de final',
        16 => 'Ronda de 16',
        32 => 'Ronda de 32',
        64 => 'Ronda de 64',
        128 => 'Ronda de 128',
        256 => 'Ronda de 256',
        512 => 'Ronda de 512',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected((int) old('selector_round_size', $editingExit ? $phaseExit->selector_round_size : 4) === $value)>
                        {{ $label }}
                    </option>
                @endforeach

            </select>

            <p class="mt-2 text-[11px] leading-4 text-violet-600">
                Ejemplo: los eliminados en Semifinal pueden
                utilizarse después para una Fase de tercer puesto.
            </p>

        </div>

        {{-- TIMING --}}

        <div class="mt-4">

            <label class="text-xs font-black uppercase text-slate-500">
                Momento en que se publica la salida
            </label>
            <p class="mt-1 text-[11px] leading-5 text-slate-400">
                Controla si los participantes pueden abandonar la fase durante la ejecución o únicamente cuando la fase termina.
            </p>

            <select name="exit_timing" x-model="timing" :disabled="selector === 'ELIMINATED_IN_ROUND'"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

                <option value="PHASE_END">
                    Al finalizar la Fase
                </option>

                <option value="ON_ELIMINATION">
                    Al producirse la eliminación
                </option>

                <option value="ON_RULE_TRIGGER">
                    Al activarse una regla del Engine
                </option>

            </select>

            <input x-show="selector === 'ELIMINATED_IN_ROUND'" type="hidden" name="exit_timing" value="ON_ELIMINATION">

        </div>

    </div>

    {{-- PRIORITY + STATUS --}}

    <div class="grid grid-cols-2 gap-3">

        <div>

            <label class="text-xs font-black uppercase text-slate-500">
                Prioridad de la salida
            </label>
            <p class="mt-1 text-[11px] leading-5 text-slate-400">
                Permite mantener un orden explícito entre varias salidas de la misma fase.
            </p>

            <input type="number" name="priority" min="1" max="999"
                value="{{ old('priority', $editingExit ? $phaseExit->priority : 10) }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

        </div>

        <div>

            <label class="text-xs font-black uppercase text-slate-500">
                Estado
            </label>

            <select name="status"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

                <option value="ACTIVE" @selected(old('status', $editingExit ? $phaseExit->status : 'ACTIVE') === 'ACTIVE')>
                    Activa
                </option>

                <option value="INACTIVE" @selected(old('status', $editingExit ? $phaseExit->status : 'ACTIVE') === 'INACTIVE')>
                    Inactiva
                </option>

            </select>

        </div>

    </div>

    <button type="submit"
        class="w-full rounded-xl bg-amber-500 px-4 py-3 text-sm font-black text-white transition hover:bg-amber-600">
        {{ $editingExit ? 'Guardar salida' : '+ Agregar salida' }}
    </button>

</form>

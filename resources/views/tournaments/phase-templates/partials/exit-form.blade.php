@php
    $editingExit = isset($phaseExit) && $phaseExit;

    $exitAction = $editingExit
        ? route('tournaments.phase-exits.update', [$phaseTemplate, $phaseExit])
        : route('tournaments.phase-exits.store', $phaseTemplate);
@endphp

<form method="POST" action="{{ $exitAction }}" class="space-y-4">

    @csrf

    @if ($editingExit)
        @method('PUT')
    @endif

    <div>
        <label class="text-xs font-black uppercase text-slate-500">Nombre *</label>

        <input type="text" name="name" value="{{ old('name', $editingExit ? $phaseExit->name : '') }}"
            placeholder="Ej. Ganadores"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
    </div>

    <div>
        <label class="text-xs font-black uppercase text-slate-500">Descripción</label>

        <textarea name="description" rows="3" placeholder="Explica quiénes deben salir por esta puerta..."
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">{{ old('description', $editingExit ? $phaseExit->description : '') }}</textarea>
    </div>

    <div x-data="{
        selector: @js(old('selector_type', $editingExit ? $phaseExit->selector_type : 'MATCH_WINNERS'))
    }">

        <label class="text-xs font-black uppercase text-slate-500">¿Quién sale por aquí?</label>

        <select name="selector_type" x-model="selector"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">

            <option value="MATCH_WINNERS">Ganadores de enfrentamientos</option>
            <option value="MATCH_LOSERS">Perdedores de enfrentamientos</option>
            <option value="TOP_N">Mejores N</option>
            <option value="BOTTOM_N">Últimos N</option>
            <option value="RANK_POSITION">Posición específica</option>
            <option value="RANK_RANGE">Rango de posiciones</option>
            <option value="ALL">Todos</option>
            <option value="REMAINING">Restantes</option>
        </select>

        <div x-show="['TOP_N', 'BOTTOM_N', 'RANK_POSITION', 'RANK_RANGE'].includes(selector)" class="mt-4">

            <label class="text-xs font-black uppercase text-slate-500"
                x-text="selector === 'RANK_RANGE'
                    ? 'Desde posición'
                    : selector === 'RANK_POSITION'
                        ? 'Posición'
                        : 'Cantidad'">
            </label>

            <input type="number" name="selector_from" min="1" max="512"
                value="{{ old('selector_from', $editingExit ? $phaseExit->selector_from : '') }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
        </div>

        <div x-show="selector === 'RANK_RANGE'" class="mt-4">
            <label class="text-xs font-black uppercase text-slate-500">Hasta posición</label>

            <input type="number" name="selector_to" min="1" max="512"
                value="{{ old('selector_to', $editingExit ? $phaseExit->selector_to : '') }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
        </div>

    </div>

    <div class="grid grid-cols-2 gap-3">

        <div>
            <label class="text-xs font-black uppercase text-slate-500">Prioridad</label>

            <input type="number" name="priority" min="1" max="999"
                value="{{ old('priority', $editingExit ? $phaseExit->priority : 10) }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
        </div>

        <div>
            <label class="text-xs font-black uppercase text-slate-500">Estado</label>

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

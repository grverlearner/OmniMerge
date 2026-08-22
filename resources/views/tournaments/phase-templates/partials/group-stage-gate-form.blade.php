@php
    /*
     * Formulario de una puerta de entrada de fase de grupos.
     *
     * $gate  null para crear, la puerta para editar.
     */

    $gate = $gate ?? null;
    $isEdit = $gate !== null;

    $target = $gate?->settings['target_group_code'] ?? null;

    $inputTypes = [
        'STANDARD' => 'Estándar',
        'SEEDED' => 'Cabezas de serie',
        'QUALIFIER' => 'Clasificados de otra fase',
        'WILDCARD' => 'Invitados',
    ];

    $mergePolicies = [
        'APPEND' => 'Añadir según llegan',
        'WAIT_ALL' => 'Esperar a todos',
        'FIRST_AVAILABLE' => 'El primero disponible',
        'PRIORITY' => 'Por prioridad',
    ];

    $modes = [
        'SEQUENTIAL' => 'En orden',
        'BALANCED' => 'Equilibrado',
        'SNAKE' => 'Serpiente',
        'RANDOM' => 'Al azar',
    ];
@endphp

<form method="POST"
    action="{{ $isEdit
        ? route('tournaments.group-stage.gates.update', [$phaseTemplate, $gate])
        : route('tournaments.group-stage.gates.store', $phaseTemplate) }}"
    class="space-y-4">

    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif


    <div class="grid gap-3 sm:grid-cols-2">

        <div class="sm:col-span-2">
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Nombre *</label>
            <input type="text" name="name" value="{{ old('name', $gate?->name) }}"
                placeholder="Ej. Clasificados de la liga"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-400 focus:ring-emerald-400">
        </div>

        <div>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Tipo</label>
            <select name="input_type"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-400 focus:ring-emerald-400">
                @foreach ($inputTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('input_type', $gate?->input_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Cuándo abre</label>
            <select name="merge_policy"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-400 focus:ring-emerald-400">
                @foreach ($mergePolicies as $value => $label)
                    <option value="{{ $value }}" @selected(old('merge_policy', $gate?->merge_policy) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

    </div>


    {{-- A QUÉ GRUPO ENVÍA --}}

    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-3"
        x-data="{ mode: '{{ $target ? 'GROUP' : 'AUTO' }}' }">

        <label class="text-[10px] font-black uppercase tracking-wider text-indigo-700">
            ¿A qué grupo envía?
        </label>

        <div class="mt-2 flex gap-2">

            <button type="button" @click="mode = 'AUTO'"
                :class="mode === 'AUTO' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                Reparto automático
            </button>

            <button type="button" @click="mode = 'GROUP'"
                :class="mode === 'GROUP' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                Un grupo concreto
            </button>

        </div>

        <div x-show="mode === 'AUTO'" class="mt-3">

            <input type="hidden" name="target_group_code" value="" x-bind:disabled="mode !== 'AUTO'">

            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Cómo reparte</label>

            <select name="distribution_mode"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                @foreach ($modes as $value => $label)
                    <option value="{{ $value }}" @selected(old('distribution_mode', $gate?->distribution_mode) === $value)>{{ $label }}</option>
                @endforeach
            </select>

        </div>

        <div x-show="mode === 'GROUP'" x-cloak class="mt-3">

            <select name="target_group_code" x-bind:disabled="mode !== 'GROUP'"
                class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">

                <option value="">— elige un grupo —</option>

                @foreach ($groupDefinitions as $group)
                    <option value="{{ $group->code }}" @selected($target === $group->code)>
                        {{ $group->name }}
                    </option>
                @endforeach

            </select>

            <p class="mt-1.5 text-[10px] text-indigo-700">
                Todo lo que entre por esta puerta irá a ese grupo.
            </p>

            {{-- El modo de reparto viaja igualmente: la validación lo exige --}}
            <input type="hidden" name="distribution_mode"
                value="{{ old('distribution_mode', $gate?->distribution_mode ?? 'SEQUENTIAL') }}"
                x-bind:disabled="mode !== 'GROUP'">

        </div>

    </div>


    {{-- CAPACIDAD --}}

    <div class="grid gap-3 sm:grid-cols-4">

        <div>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Mínimo</label>
            <input type="number" name="min_participants" min="0" max="512"
                value="{{ old('min_participants', $gate?->min_participants) }}"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-400 focus:ring-emerald-400">
        </div>

        <div>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Máximo</label>
            <input type="number" name="max_participants" min="1" max="512"
                value="{{ old('max_participants', $gate?->max_participants) }}"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-400 focus:ring-emerald-400">
        </div>

        <div>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Si llega vacía</label>
            <select name="empty_behavior"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-400 focus:ring-emerald-400">
                <option value="ALLOW" @selected(old('empty_behavior', $gate?->empty_behavior) === 'ALLOW')>Permitir</option>
                <option value="BLOCK" @selected(old('empty_behavior', $gate?->empty_behavior) === 'BLOCK')>Bloquear la fase</option>
            </select>
        </div>

        <div>
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Estado</label>
            <select name="status"
                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-400 focus:ring-emerald-400">
                <option value="ACTIVE" @selected(old('status', $gate?->status ?? 'ACTIVE') === 'ACTIVE')>Activa</option>
                <option value="INACTIVE" @selected(old('status', $gate?->status) === 'INACTIVE')>Inactiva</option>
            </select>
        </div>

    </div>


    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $gate?->is_required))
            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
        <span class="text-xs font-bold text-slate-600">
            Obligatoria: la fase no arranca sin lo que entre por aquí
        </span>
    </label>


    <div class="flex items-center gap-2">

        <button class="rounded-xl bg-slate-950 px-5 py-2.5 text-xs font-black text-white hover:bg-slate-800">
            {{ $isEdit ? 'Guardar cambios' : 'Crear puerta' }}
        </button>

        @if ($isEdit)
            <button type="button" @click="editing = null"
                class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600 hover:bg-slate-50">
                Cancelar
            </button>
        @endif

    </div>

</form>

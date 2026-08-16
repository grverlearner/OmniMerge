@php
    $editingGate = isset($phaseInputGate) && $phaseInputGate;

    $gateFormKey = $editingGate ? 'input-gate-edit-' . $phaseInputGate->id : 'input-gate-create';

    $submittedGateForm = old('gate_form_key') === $gateFormKey;

    $gateValue = function (string $key, mixed $default = null) use ($submittedGateForm) {
        return $submittedGateForm ? old($key, $default) : $default;
    };

    $gateAction = $editingGate
        ? route('tournaments.single-elimination.input-gates.update', [$phaseTemplate, $phaseInputGate])
        : route('tournaments.single-elimination.input-gates.store', $phaseTemplate);

    $defaultExact = $editingGate ? $phaseInputGate->exact_participants : $phaseTemplate->exact_participants;

    $defaultMinimum = $editingGate ? $phaseInputGate->min_participants : $phaseTemplate->min_participants;

    $defaultMaximum = $editingGate ? $phaseInputGate->max_participants : $phaseTemplate->max_participants;

    $defaultCapacityMode =
        $defaultExact !== null
            ? 'EXACT'
            : ($defaultMinimum !== null || $defaultMaximum !== null
                ? 'RANGE'
                : 'FLEXIBLE');

    $currentTargetIds = $editingGate
        ? $phaseInputGate->outgoingConnections
            ->where('target_type', 'SLOT')
            ->sortBy('allocation_value')
            ->pluck('target_slot_id')
            ->filter()
            ->values()
            ->all()
        : [];

    $selectedTargetIds = collect($submittedGateForm ? old('target_slot_ids', []) : $currentTargetIds)->map(
        fn($slotId) => (int) $slotId,
    );

    $globalEncounterNumber = 0;
    $globalSlotNumber = 0;
@endphp

<form method="POST" action="{{ $gateAction }}" class="space-y-5" x-data="{
    capacityMode: @js($gateValue('capacity_mode', $defaultCapacityMode))
}">
    @csrf

    <input type="hidden" name="gate_form_key" value="{{ $gateFormKey }}">

    @if ($editingGate)
        @method('PUT')
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <label>
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Nombre *
            </span>

            <input type="text" name="name" maxlength="120" required
                value="{{ $gateValue('name', $editingGate ? $phaseInputGate->name : 'Entrada principal') }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm font-bold focus:border-fuchsia-400 focus:ring-fuchsia-400">
        </label>

        <label>
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Prioridad *
            </span>

            <input type="number" name="priority" min="1" max="999" required
                value="{{ $gateValue('priority', $editingGate ? $phaseInputGate->priority : 10) }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm font-bold focus:border-fuchsia-400 focus:ring-fuchsia-400">
        </label>
    </div>

    <label class="block">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">
            Descripción
        </span>

        <textarea name="description" rows="3" maxlength="2000"
            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">{{ $gateValue('description', $editingGate ? $phaseInputGate->description : '') }}</textarea>
    </label>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <label>
            <span class="text-[10px] font-black uppercase text-slate-500">
                Tipo
            </span>

            <select name="input_type" class="mt-2 w-full rounded-xl border-slate-300 text-xs font-bold">
                @foreach ([
        'POOL' => 'Bolsa general',
        'PER_SEED' => 'Una puerta por seed',
        'GROUPED' => 'Agrupada',
        'HYBRID' => 'Híbrida',
        'CUSTOM' => 'Personalizada',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($gateValue('input_type', $editingGate ? $phaseInputGate->input_type : $settings->input_mode ?? 'POOL') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="text-[10px] font-black uppercase text-slate-500">
                Unión
            </span>

            <select name="merge_policy" class="mt-2 w-full rounded-xl border-slate-300 text-xs font-bold">
                @foreach ([
        'APPEND' => 'Acumular',
        'WAIT_ALL' => 'Esperar todas',
        'FIRST_AVAILABLE' => 'Primera disponible',
        'PRIORITY' => 'Prioridad',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($gateValue('merge_policy', $editingGate ? $phaseInputGate->merge_policy : 'APPEND') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="text-[10px] font-black uppercase text-slate-500">
                Distribución
            </span>

            <select name="distribution_mode" class="mt-2 w-full rounded-xl border-slate-300 text-xs font-bold">
                @foreach ([
        'INPUT_ORDER' => 'Orden de entrada',
        'RANKING' => 'Ranking',
        'RANDOM' => 'Aleatoria',
        'BALANCED' => 'Balanceada',
        'EXTREMES' => 'Extremos',
        'MANUAL' => 'Manual',
        'CUSTOM' => 'Personalizada',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($gateValue('distribution_mode', $editingGate ? $phaseInputGate->distribution_mode : $settings->seeding_mode ?? 'INPUT_ORDER') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="text-[10px] font-black uppercase text-slate-500">
                Si está vacía
            </span>

            <select name="empty_behavior" class="mt-2 w-full rounded-xl border-slate-300 text-xs font-bold">
                @foreach ([
        'ERROR' => 'Marcar error',
        'WAIT' => 'Esperar',
        'SKIP' => 'Omitir',
        'ALLOW_EMPTY' => 'Permitir vacío',
        'MANUAL' => 'Manual',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($gateValue('empty_behavior', $editingGate ? $phaseInputGate->empty_behavior : 'ERROR') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase text-fuchsia-700">
                    Contrato de capacidad
                </p>

                <p class="mt-1 text-[11px] text-fuchsia-800">
                    Cantidad que esta puerta puede recibir.
                </p>
            </div>

            <select name="capacity_mode" x-model="capacityMode"
                class="rounded-xl border-fuchsia-200 bg-white text-xs font-black">
                <option value="EXACT">Exacta</option>
                <option value="RANGE">Rango</option>
                <option value="FLEXIBLE">Flexible</option>
            </select>
        </div>

        <div x-show="capacityMode === 'EXACT'" class="mt-4">
            <input type="number" name="exact_participants" min="1" max="512"
                value="{{ $gateValue('exact_participants', $defaultExact) }}" placeholder="Participantes exactos"
                class="w-full rounded-xl border-fuchsia-200 bg-white text-sm font-bold">
        </div>

        <div x-show="capacityMode === 'RANGE'" class="mt-4 grid grid-cols-2 gap-3">
            <input type="number" name="min_participants" min="1" max="512"
                value="{{ $gateValue('min_participants', $defaultMinimum) }}" placeholder="Mínimo"
                class="rounded-xl border-fuchsia-200 bg-white text-sm font-bold">

            <input type="number" name="max_participants" min="1" max="512"
                value="{{ $gateValue('max_participants', $defaultMaximum) }}" placeholder="Máximo"
                class="rounded-xl border-fuchsia-200 bg-white text-sm font-bold">
        </div>

        <p x-show="capacityMode === 'FLEXIBLE'"
            class="mt-4 rounded-xl bg-white p-3 text-[11px] font-bold text-fuchsia-700">
            Sin mínimo, máximo ni cantidad exacta.
        </p>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        @foreach ([['is_required', 'Obligatoria', $editingGate ? $phaseInputGate->is_required : true], ['accepts_batch', 'Acepta lotes', $editingGate ? $phaseInputGate->accepts_batch : true], ['accepts_multiple_connections', 'Varias rutas externas', $editingGate ? $phaseInputGate->accepts_multiple_connections : true]] as [$name, $label, $default])
            <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4">
                <input type="hidden" name="{{ $name }}" value="0">

                <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $gateValue($name, $default))
                    class="rounded border-fuchsia-300 text-fuchsia-600">

                <span class="text-xs font-black text-slate-800">
                    {{ $label }}
                </span>
            </label>
        @endforeach
    </div>

    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
        <p class="text-[10px] font-black uppercase text-indigo-700">
            Mapeo puerta → slots
        </p>

        <p class="mt-1 text-[11px] leading-5 text-indigo-800">
            El orden de selección define Posición 1, Posición 2, etc.
            También puedes enviar un seed directamente a rondas posteriores.
        </p>

        @if ($rounds->isEmpty())
            <p
                class="mt-4 rounded-xl border border-dashed border-indigo-300 bg-white p-4 text-center text-xs font-bold text-indigo-700">
                Primero genera la estructura para disponer de slots.
            </p>
        @else
            <div class="mt-4 max-h-[420px] space-y-3 overflow-y-auto">
                @foreach ($rounds as $round)
                    <div class="rounded-2xl border border-indigo-100 bg-white p-3">
                        <p class="text-xs font-black text-slate-800">
                            {{ $round->name }}
                        </p>

                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            @foreach ($round->encounters as $encounter)
                                @php
                                    $globalEncounterNumber++;
                                @endphp

                                @foreach ($encounter->slots as $slot)
                                    @php
                                        $globalSlotNumber++;
                                    @endphp

                                    <label
                                        class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-3 hover:border-indigo-300">
                                        <input type="checkbox" name="target_slot_ids[]" value="{{ $slot->id }}"
                                            @checked($selectedTargetIds->contains($slot->id))
                                            class="mt-0.5 rounded border-indigo-300 text-indigo-600">

                                        <span>
                                            <span class="block text-[10px] font-black text-slate-800">
                                                Encuentro global #{{ $globalEncounterNumber }}
                                                · Slot {{ $slot->position }}
                                            </span>

                                            <span class="mt-1 block text-[9px] text-slate-400">
                                                Slot global #{{ $globalSlotNumber }}
                                                · {{ $slot->code }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <label>
            <span class="text-[10px] font-black uppercase text-slate-500">
                Estado
            </span>

            <select name="status" class="mt-2 w-full rounded-xl border-slate-300 text-sm font-bold">
                <option value="ACTIVE" @selected($gateValue('status', $editingGate ? $phaseInputGate->status : 'ACTIVE') === 'ACTIVE')>
                    Activa
                </option>

                <option value="INACTIVE" @selected($gateValue('status', $editingGate ? $phaseInputGate->status : 'ACTIVE') === 'INACTIVE')>
                    Inactiva
                </option>
            </select>
        </label>

        <label class="flex cursor-pointer gap-3 rounded-2xl border border-violet-200 bg-violet-50 p-4">
            <input type="hidden" name="is_locked" value="0">

            <input type="checkbox" name="is_locked" value="1" @checked((bool) $gateValue('is_locked', $editingGate ? $phaseInputGate->is_locked : true))
                class="mt-0.5 rounded border-violet-300 text-violet-600">

            <span>
                <span class="block text-xs font-black text-violet-900">
                    Proteger personalización
                </span>

                <span class="mt-1 block text-[10px] text-violet-700">
                    Regenerar exigirá confirmación.
                </span>
            </span>
        </label>
    </div>

    <button type="submit"
        class="w-full rounded-xl bg-fuchsia-600 px-5 py-3 text-xs font-black text-white hover:bg-fuchsia-700">
        {{ $editingGate ? 'Guardar puerta y mapeo' : '+ Crear puerta de entrada' }}
    </button>
</form>

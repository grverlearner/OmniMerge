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
                Prioridad de la puerta *
            </span>

            <input type="number" name="priority" min="1" max="999" required
                value="{{ $gateValue('priority', $editingGate ? $phaseInputGate->priority : 10) }}"
                class="mt-2 w-full rounded-xl border-slate-300 text-sm font-bold focus:border-fuchsia-400 focus:ring-fuchsia-400">

            <span class="mt-1 block text-[10px] leading-4 text-slate-500">
                Permite ordenar esta puerta respecto de otras entradas cuando la estructura necesita una precedencia explícita.
            </span>
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
                Tipo de entrada
            </span>
            <span class="mt-1 block text-[10px] leading-4 text-slate-400">
                Describe cómo se organiza conceptualmente el conjunto que llega por esta puerta.
            </span>

            <select name="input_type" class="mt-2 w-full rounded-xl border-slate-300 text-xs font-bold">
                @foreach ([
        'POOL' => 'Bolsa general',
        'PER_SEED' => 'Una puerta por seed',
        'GROUPED' => 'Agrupada',
        'HYBRID' => 'Híbrida',
        'CUSTOM' => 'Personalizada',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($gateValue('input_type', $editingGate ? $phaseInputGate->input_type : $settings->input_mode ?? 'POOL') === $value) @disabled($value === 'CUSTOM' && $gateValue('input_type', $editingGate ? $phaseInputGate->input_type : $settings->input_mode ?? 'POOL') !== 'CUSTOM')>
                        {{ $label }}{{ $value === 'CUSTOM' ? ' · Próximamente' : '' }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="text-[10px] font-black uppercase text-slate-500">
                Política de unión
            </span>
            <span class="mt-1 block text-[10px] leading-4 text-slate-400">
                Indica cómo se combinan los participantes cuando llegan varias conexiones a esta misma puerta.
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
                Distribución hacia los slots
            </span>
            <span class="mt-1 block text-[10px] leading-4 text-slate-400">
                Define el orden con el que los participantes recibidos serán enviados a los slots seleccionados.
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
                    <option value="{{ $value }}" @selected($gateValue('distribution_mode', $editingGate ? $phaseInputGate->distribution_mode : $settings->seeding_mode ?? 'INPUT_ORDER') === $value) @disabled(in_array($value, ['MANUAL', 'CUSTOM'], true) && $gateValue('distribution_mode', $editingGate ? $phaseInputGate->distribution_mode : $settings->seeding_mode ?? 'INPUT_ORDER') !== $value)>
                        {{ $label }}{{ in_array($value, ['MANUAL', 'CUSTOM'], true) ? ' · Próximamente' : '' }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="text-[10px] font-black uppercase text-slate-500">
                Si la puerta queda vacía
            </span>
            <span class="mt-1 block text-[10px] leading-4 text-slate-400">
                Decide qué hará la ejecución si esta entrada no recibe ningún participante.
            </span>

            <select name="empty_behavior" class="mt-2 w-full rounded-xl border-slate-300 text-xs font-bold">
                @foreach ([
        'ERROR' => 'Marcar error',
        'WAIT' => 'Esperar',
        'SKIP' => 'Omitir',
        'ALLOW_EMPTY' => 'Permitir vacío',
        'MANUAL' => 'Manual',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($gateValue('empty_behavior', $editingGate ? $phaseInputGate->empty_behavior : 'ERROR') === $value) @disabled($value === 'MANUAL' && $gateValue('empty_behavior', $editingGate ? $phaseInputGate->empty_behavior : 'ERROR') !== 'MANUAL')>
                        {{ $label }}{{ $value === 'MANUAL' ? ' · Próximamente' : '' }}
                    </option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-xs font-black text-amber-900">
            Contratos manuales y personalizados · Próximamente
        </p>
        <p class="mt-1 text-[10px] leading-5 text-amber-800">
            OmniMerge conserva estas opciones en el dominio para estructuras heredadas, pero Competition Lab
            todavía no ejecuta su semántica completa. Las configuraciones nuevas deben usar modos automáticos.
        </p>
    </div>

    <div class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50 p-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase text-fuchsia-700">
                    Contrato de capacidad
                </p>

                <p class="mt-1 text-[11px] text-fuchsia-800">
                    Cantidad que esta puerta puede recibir.
                </p>
            </div>

            <label class="min-w-[170px]">
                <span class="text-[8px] font-black uppercase tracking-wider text-fuchsia-700">Modo de capacidad</span>
                <select name="capacity_mode" x-model="capacityMode"
                    class="mt-1 w-full rounded-xl border-fuchsia-200 bg-white text-xs font-black">
                    <option value="EXACT">Exacta</option>
                    <option value="RANGE">Rango</option>
                    <option value="FLEXIBLE">Flexible</option>
                </select>
            </label>
        </div>

        <div x-show="capacityMode === 'EXACT'" class="mt-4">
            <label class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-fuchsia-700">Participantes exactos</span>
                <input type="number" name="exact_participants" min="1" max="512"
                    value="{{ $gateValue('exact_participants', $defaultExact) }}" placeholder="Ej. 8"
                    class="mt-1.5 w-full rounded-xl border-fuchsia-200 bg-white text-sm font-bold">
                <span class="mt-1 block text-[10px] leading-4 text-fuchsia-800/70">
                    La puerta solo será compatible con esta cantidad concreta.
                </span>
            </label>
        </div>

        <div x-show="capacityMode === 'RANGE'" class="mt-4 grid grid-cols-2 gap-3">
            <label>
                <span class="text-[9px] font-black uppercase tracking-wider text-fuchsia-700">Mínimo aceptado</span>
                <input type="number" name="min_participants" min="1" max="512"
                    value="{{ $gateValue('min_participants', $defaultMinimum) }}" placeholder="Ej. 4"
                    class="mt-1.5 w-full rounded-xl border-fuchsia-200 bg-white text-sm font-bold">
            </label>

            <label>
                <span class="text-[9px] font-black uppercase tracking-wider text-fuchsia-700">Máximo aceptado</span>
                <input type="number" name="max_participants" min="1" max="512"
                    value="{{ $gateValue('max_participants', $defaultMaximum) }}" placeholder="Ej. 8"
                    class="mt-1.5 w-full rounded-xl border-fuchsia-200 bg-white text-sm font-bold">
            </label>
        </div>

        <p x-show="capacityMode === 'FLEXIBLE'"
            class="mt-4 rounded-xl bg-white p-3 text-[11px] font-bold text-fuchsia-700">
            Sin mínimo, máximo ni cantidad exacta.
        </p>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        @foreach ([
            ['is_required', 'Obligatoria', 'El grafo exige que esta puerta cumpla su contrato antes de continuar.', $editingGate ? $phaseInputGate->is_required : true],
            ['accepts_batch', 'Acepta lotes', 'Permite recibir varios participantes dentro de una misma entrega.', $editingGate ? $phaseInputGate->accepts_batch : true],
            ['accepts_multiple_connections', 'Varias rutas externas', 'Permite que más de una conexión alimente esta misma puerta.', $editingGate ? $phaseInputGate->accepts_multiple_connections : true],
        ] as [$name, $label, $description, $default])
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                <input type="hidden" name="{{ $name }}" value="0">

                <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $gateValue($name, $default))
                    class="mt-0.5 rounded border-fuchsia-300 text-fuchsia-600">

                <span>
                    <span class="block text-xs font-black text-slate-800">{{ $label }}</span>
                    <span class="mt-1 block text-[10px] leading-4 text-slate-500">{{ $description }}</span>
                </span>
            </label>
        @endforeach
    </div>

    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase text-indigo-700">
                    Mapeo puerta → slots
                </p>

                <p class="mt-1 max-w-2xl text-[11px] leading-5 text-indigo-800">
                    Solamente puedes seleccionar slots disponibles o slots que ya
                    pertenecen a esta puerta. Los ocupados por otra fuente están bloqueados.
                </p>
            </div>

            {{-- Leyenda --}}
            <div class="flex flex-wrap gap-2 text-[9px] font-black">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Disponible
                </span>

                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-indigo-700">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    Esta puerta
                </span>

                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1.5 text-red-700">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    Ocupado
                </span>
            </div>
        </div>

        @if ($rounds->isEmpty())
            <p
                class="mt-4 rounded-xl border border-dashed border-indigo-300 bg-white p-4 text-center text-xs font-bold text-indigo-700">
                Primero genera la estructura para disponer de slots.
            </p>
        @else
            <div class="mt-4 max-h-[520px] space-y-3 overflow-y-auto pr-1">
                @foreach ($rounds as $round)
                    <div class="rounded-2xl border border-indigo-100 bg-white p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs font-black text-slate-800">
                                {{ $round->name }}
                            </p>

                            <span
                                class="rounded-full bg-slate-100 px-2.5 py-1 text-[8px] font-black uppercase text-slate-500">
                                {{ $round->encounters->sum(fn($encounter) => $encounter->slots->count()) }}
                                slots
                            </span>
                        </div>

                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            @foreach ($round->encounters as $encounter)
                                @php
                                    $globalEncounterNumber++;
                                @endphp

                                @foreach ($encounter->slots as $slot)
                                    @php
                                        $globalSlotNumber++;

                                        /*
                                         * Solamente se consideran conexiones activas.
                                         */
                                        $activeIncomingConnections = $slot->incomingConnections
                                            ->where('status', 'ACTIVE')
                                            ->values();

                                        /*
                                         * Determinar si el slot ya pertenece
                                         * a la puerta que se está editando.
                                         */
                                        $belongsToCurrentGate =
                                            $editingGate &&
                                            $activeIncomingConnections->contains(
                                                fn($connection) => $connection->source_type === 'INPUT_GATE' &&
                                                    (int) $connection->source_input_gate_id ===
                                                        (int) $phaseInputGate->id,
                                            );

                                        /*
                                         * Buscar una conexión que no pertenezca
                                         * a la puerta actual.
                                         */
                                        $occupyingConnection = $activeIncomingConnections->first(
                                            fn($connection) => !(
                                                $editingGate &&
                                                $connection->source_type === 'INPUT_GATE' &&
                                                (int) $connection->source_input_gate_id === (int) $phaseInputGate->id
                                            ),
                                        );

                                        $occupiedByOther = $occupyingConnection !== null;

                                        $isSelected = $selectedTargetIds->contains($slot->id);

                                        $occupancyLabel = match ($occupyingConnection?->source_type) {
                                            'INPUT_GATE' => 'Puerta ocupada',

                                            'RESULT' => 'Ocupado por un resultado anterior',

                                            default => 'Slot ocupado',
                                        };
                                    @endphp

                                    <label @class([
                                        'relative flex gap-3 rounded-xl border p-3 transition',
                                    
                                        'cursor-not-allowed border-red-200 bg-red-50/80 opacity-80' => $occupiedByOther,
                                    
                                        'cursor-pointer border-indigo-300 bg-indigo-50 ring-1 ring-indigo-200' =>
                                            !$occupiedByOther && $belongsToCurrentGate,
                                    
                                        'cursor-pointer border-emerald-200 bg-emerald-50/50 hover:border-emerald-400 hover:bg-emerald-50' =>
                                            !$occupiedByOther && !$belongsToCurrentGate,
                                    ])>

                                        <input type="checkbox" name="target_slot_ids[]" value="{{ $slot->id }}"
                                            @checked($isSelected) @disabled($occupiedByOther)
                                            @class([
                                                'mt-0.5 rounded',
                                            
                                                'border-red-300 text-red-500' => $occupiedByOther,
                                            
                                                'border-indigo-300 text-indigo-600' => $belongsToCurrentGate,
                                            
                                                'border-emerald-300 text-emerald-600' =>
                                                    !$occupiedByOther && !$belongsToCurrentGate,
                                            ])>

                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-center justify-between gap-2">

                                                <span @class([
                                                    'block text-[10px] font-black',
                                                
                                                    'text-red-800' => $occupiedByOther,
                                                
                                                    'text-indigo-800' => !$occupiedByOther && $belongsToCurrentGate,
                                                
                                                    'text-slate-800' => !$occupiedByOther && !$belongsToCurrentGate,
                                                ])>
                                                    Encuentro global
                                                    #{{ $globalEncounterNumber }}
                                                    · Slot {{ $slot->position }}
                                                </span>

                                                @if ($occupiedByOther)
                                                    <span
                                                        class="rounded-full bg-red-100 px-2 py-1 text-[8px] font-black uppercase text-red-700">
                                                        Ocupado
                                                    </span>
                                                @elseif ($belongsToCurrentGate)
                                                    <span
                                                        class="rounded-full bg-indigo-100 px-2 py-1 text-[8px] font-black uppercase text-indigo-700">
                                                        Esta puerta
                                                    </span>
                                                @else
                                                    <span
                                                        class="rounded-full bg-emerald-100 px-2 py-1 text-[8px] font-black uppercase text-emerald-700">
                                                        Disponible
                                                    </span>
                                                @endif
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

@php
    $customParticipants = (int) data_get(
        $settings->settings,
        'custom_graph_participants',
        $defaultParticipants,
    );
    $isCustomGraph = $settings->structure_mode === 'MANUAL';
    $encounters = $rounds->flatMap->encounters->values();
    $activeExits = $exits
        ->where('status', 'ACTIVE')
        ->where('resolution_mode', 'INTERNAL_GRAPH')
        ->values();
    $manualRouteGroups = $connections
        ->filter(fn($connection) =>
            $connection->source_type === 'RESULT'
            && !data_get($connection->settings, 'automatic_elimination_route', false)
        )
        ->groupBy(fn($connection) => data_get($connection->settings, 'route_group', 'connection:' . $connection->id));

    $initialBuilderStep = $rounds->isEmpty()
        ? 1
        : ($encounters->isEmpty()
            ? 2
            : ($manualRouteGroups->isEmpty() ? 3 : 4));
@endphp

<section class="mt-6 overflow-hidden rounded-[30px] border border-fuchsia-200 bg-white shadow-sm">
    <div class="bg-gradient-to-r from-fuchsia-950 via-violet-950 to-indigo-950 p-6 text-white">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-fuchsia-300">
                    Diseñador de flujo interno
                </p>

                <h2 class="mt-2 text-2xl font-black">
                    Grafo personalizado de encuentros
                </h2>

                <p class="mt-2 max-w-3xl text-xs leading-6 text-slate-300">
                    Crea encuentros K → Q diferentes, une clasificados de varias fuentes y envía los resultados
                    a otro encuentro o a una puerta de salida. Las rutas mueven participantes individuales;
                    nunca forman equipos “3 contra 1”.
                </p>
            </div>

            <span class="w-fit rounded-full border border-white/10 bg-white/10 px-3 py-2 text-[10px] font-black">
                {{ $isCustomGraph ? 'MODO MANUAL ACTIVO' : 'MODO AUTOMÁTICO' }}
            </span>
        </div>
    </div>

    <div x-data="{ builderStep: @js($initialBuilderStep) }" class="p-5 sm:p-6">
        @unless ($isCustomGraph)
            <form method="POST"
                action="{{ route('tournaments.single-elimination.graph.initialize', $phaseTemplate) }}"
                class="rounded-3xl border border-fuchsia-200 bg-fuchsia-50 p-5"
                data-omni-confirm data-confirm-variant="warning" data-confirm-icon="◇"
                data-confirm-title="Iniciar grafo personalizado"
                data-confirm-message="La estructura actual será reemplazada solamente si marcas la confirmación correspondiente."
                data-confirm-subject="{{ $phaseTemplate->name }}" data-confirm-action="Iniciar diseñador">
                @csrf

                <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)] lg:items-end">
                    <label>
                        <span class="text-[9px] font-black uppercase text-fuchsia-700">
                            Participantes del grafo
                        </span>

                        <input type="number" name="participants" min="2" max="512"
                            value="{{ old('participants', $defaultParticipants) }}" required
                            class="mt-2 w-full rounded-xl border-fuchsia-200 bg-white text-sm font-black">
                    </label>

                    <div>
                        @if ($rounds->isNotEmpty() || $inputGates->isNotEmpty())
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <input type="hidden" name="replace_structure" value="0">
                                <input type="checkbox" name="replace_structure" value="1"
                                    class="mt-0.5 rounded border-amber-300 text-amber-600">

                                <span>
                                    <span class="block text-xs font-black text-amber-900">
                                        Reemplazar la estructura existente
                                    </span>
                                    <span class="mt-1 block text-[10px] leading-5 text-amber-700">
                                        Elimina rondas, encuentros, puertas y rutas internas actuales para comenzar desde cero.
                                    </span>
                                </span>
                            </label>
                        @endif
                    </div>
                </div>

                <button type="submit"
                    class="mt-4 rounded-xl bg-fuchsia-600 px-5 py-3 text-xs font-black text-white">
                    Iniciar grafo personalizado
                </button>
            </form>
        @else
            <div class="grid gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Participantes', $customParticipants, 'text-fuchsia-600'],
                    ['Etapas', $rounds->count(), 'text-violet-600'],
                    ['Encuentros', $encounters->count(), 'text-indigo-600'],
                    ['Rutas manuales', $manualRouteGroups->count(), 'text-cyan-600'],
                ] as [$label, $value, $colorClass])
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-[8px] font-black uppercase {{ $colorClass }}">{{ $label }}</p>
                        <p class="mt-1 text-xl font-black text-slate-900">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-2">
                <div class="grid min-w-[720px] grid-cols-4 gap-2">
                    @foreach ([
                        [1, 'Etapas', 'Organiza el recorrido', $rounds->count()],
                        [2, 'Encuentros', 'Define quién compite', $encounters->count()],
                        [3, 'Conexiones', 'Mueve clasificados', $manualRouteGroups->count()],
                        [4, 'Revisión', 'Comprueba el resultado', null],
                    ] as [$step, $label, $description, $count])
                        <button type="button" @click="builderStep = {{ $step }}"
                            :class="builderStep === {{ $step }}
                                ? 'border-fuchsia-300 bg-white text-fuchsia-900 shadow-sm'
                                : 'border-transparent text-slate-500 hover:bg-white/70'"
                            class="rounded-xl border px-4 py-3 text-left transition">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-[9px] font-black uppercase tracking-wider">
                                    {{ $step }}. {{ $label }}
                                </span>
                                @if ($count !== null)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[8px] font-black text-slate-600">
                                        {{ $count }}
                                    </span>
                                @endif
                            </span>
                            <span class="mt-1 block text-[9px] font-bold opacity-60">{{ $description }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-5">
                {{-- Etapa --}}
                <form x-cloak x-show="builderStep === 1" x-transition method="POST"
                    action="{{ route('tournaments.single-elimination.graph.stages.store', $phaseTemplate) }}"
                    class="max-w-3xl rounded-3xl border border-violet-200 bg-violet-50 p-5">
                    @csrf

                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-700">1. Nueva etapa</p>
                    <input name="name" value="{{ old('name') }}" placeholder="Ej.: Clasificatorias"
                        maxlength="120" required
                        class="mt-3 w-full rounded-xl border-violet-200 bg-white text-sm font-bold">

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <input type="number" name="stage_number" min="1" max="100"
                            value="{{ old('stage_number', ($rounds->max('stage_number') ?? 0) + 1) }}"
                            placeholder="Etapa" required
                            class="rounded-xl border-violet-200 bg-white text-sm">

                        <select name="branch_code" class="rounded-xl border-violet-200 bg-white text-sm">
                            <option value="MAIN">Principal</option>
                            <option value="SECONDARY">Secundaria</option>
                            <option value="REPECHAGE">Repechaje</option>
                            <option value="CUSTOM">Personalizada</option>
                        </select>
                    </div>

                    <textarea name="description" rows="2" maxlength="2000" placeholder="Descripción opcional"
                        class="mt-3 w-full rounded-xl border-violet-200 bg-white text-sm">{{ old('description') }}</textarea>

                    <button class="mt-3 w-full rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white">
                        Agregar etapa
                    </button>
                </form>

                {{-- Encuentro --}}
                <form x-cloak x-show="builderStep === 2" x-transition method="POST"
                    action="{{ route('tournaments.single-elimination.graph.encounters.store', $phaseTemplate) }}"
                    class="max-w-3xl rounded-3xl border border-indigo-200 bg-indigo-50 p-5">
                    @csrf

                    <p class="text-[9px] font-black uppercase tracking-wider text-indigo-700">2. Nuevo encuentro</p>

                    <select name="round_id" required class="mt-3 w-full rounded-xl border-indigo-200 bg-white text-sm font-bold">
                        <option value="">Selecciona una etapa</option>
                        @foreach ($rounds as $round)
                            <option value="{{ $round->id }}" @selected((int) old('round_id') === (int) $round->id)>
                                Etapa {{ $round->stage_number }} · {{ $round->name }}
                            </option>
                        @endforeach
                    </select>

                    <input name="name" value="{{ old('name') }}" placeholder="Ej.: Clasificatoria A"
                        maxlength="120" required
                        class="mt-3 w-full rounded-xl border-indigo-200 bg-white text-sm font-bold">

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <label>
                            <span class="text-[8px] font-black uppercase text-indigo-600">Participantes</span>
                            <input type="number" name="entrants_count" min="2" max="64"
                                value="{{ old('entrants_count', 2) }}" required
                                class="mt-1 w-full rounded-xl border-indigo-200 bg-white text-sm">
                        </label>

                        <label>
                            <span class="text-[8px] font-black uppercase text-indigo-600">Clasifican</span>
                            <input type="number" name="qualifiers_count" min="1" max="63"
                                value="{{ old('qualifiers_count', 1) }}" required
                                class="mt-1 w-full rounded-xl border-indigo-200 bg-white text-sm">
                        </label>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <select name="encounter_profile" class="rounded-xl border-indigo-200 bg-white text-xs font-bold">
                            <option value="DUEL">Duelo</option>
                            <option value="MULTI_COMPETITOR">Multicompetidor</option>
                        </select>

                        <select name="resolution_mode" class="rounded-xl border-indigo-200 bg-white text-xs font-bold">
                            <option value="SCORE">Marcador</option>
                            <option value="RANKING">Clasificación ordenada</option>
                            <option value="MANUAL_SELECTION">Selección manual</option>
                        </select>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <select name="qualifier_ordering" class="rounded-xl border-indigo-200 bg-white text-xs font-bold">
                            <option value="ORDERED">Clasificados ordenados</option>
                            <option value="UNORDERED">Sin orden</option>
                        </select>

                        <select name="series_format" class="rounded-xl border-indigo-200 bg-white text-xs font-bold">
                            <option value="NONE">Sin serie</option>
                            <option value="BEST_OF">Best of</option>
                            <option value="FIXED_GAMES">Juegos fijos</option>
                        </select>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <select name="best_of" class="rounded-xl border-indigo-200 bg-white text-xs">
                            @foreach ([1, 3, 5, 7, 9, 11] as $bestOf)
                                <option value="{{ $bestOf }}">BO{{ $bestOf }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="fixed_games" min="1" max="99" value="1"
                            class="rounded-xl border-indigo-200 bg-white text-xs" placeholder="Juegos fijos">
                    </div>

                    <button @disabled($rounds->isEmpty())
                        class="mt-3 w-full rounded-xl bg-indigo-600 px-4 py-3 text-xs font-black text-white disabled:opacity-40">
                        Crear encuentro y slots
                    </button>
                </form>

                {{-- Ruta --}}
                <form x-cloak x-show="builderStep === 3" x-transition method="POST"
                    x-data="{ targetType: 'ENCOUNTER' }"
                    action="{{ route('tournaments.single-elimination.graph.routes.store', $phaseTemplate) }}"
                    class="max-w-3xl rounded-3xl border border-cyan-200 bg-cyan-50 p-5">
                    @csrf

                    <p class="text-[9px] font-black uppercase tracking-wider text-cyan-700">3. Nueva ruta</p>

                    <select name="source_encounter_id" required
                        class="mt-3 w-full rounded-xl border-cyan-200 bg-white text-sm font-bold">
                        <option value="">Encuentro de origen</option>
                        @foreach ($encounters as $encounter)
                            <option value="{{ $encounter->id }}">
                                {{ $encounter->name }} · {{ $encounter->competitive_format_label }}
                            </option>
                        @endforeach
                    </select>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <input type="number" name="source_position_from" min="1" max="64" value="1"
                            placeholder="Desde clasificado" required
                            class="rounded-xl border-cyan-200 bg-white text-sm">
                        <input type="number" name="quantity" min="1" max="64" value="1"
                            placeholder="Cantidad" required
                            class="rounded-xl border-cyan-200 bg-white text-sm">
                    </div>

                    <select name="target_type" x-model="targetType"
                        class="mt-3 w-full rounded-xl border-cyan-200 bg-white text-sm font-bold">
                        <option value="ENCOUNTER">Enviar a otro encuentro</option>
                        <option value="PHASE_EXIT">Enviar a una salida</option>
                    </select>

                    <div x-show="targetType === 'ENCOUNTER'" class="mt-3 grid grid-cols-[minmax(0,1fr)_110px] gap-2">
                        <select name="target_encounter_id" :required="targetType === 'ENCOUNTER'"
                            class="rounded-xl border-cyan-200 bg-white text-xs font-bold">
                            <option value="">Encuentro de destino</option>
                            @foreach ($encounters as $encounter)
                                <option value="{{ $encounter->id }}">
                                    E{{ $encounter->round->stage_number }} · {{ $encounter->name }}
                                </option>
                            @endforeach
                        </select>

                        <input type="number" name="target_slot_from" min="1" max="64" value="1"
                            :required="targetType === 'ENCOUNTER'" placeholder="Desde slot"
                            class="rounded-xl border-cyan-200 bg-white text-xs">
                    </div>

                    <select x-show="targetType === 'PHASE_EXIT'" name="target_phase_exit_id"
                        :required="targetType === 'PHASE_EXIT'"
                        class="mt-3 w-full rounded-xl border-cyan-200 bg-white text-xs font-bold">
                        <option value="">Puerta de salida</option>
                        @foreach ($activeExits as $exit)
                            <option value="{{ $exit->id }}">
                                {{ $exit->name }} · {{ $exit->exact_participants ?? '?' }} esperados
                            </option>
                        @endforeach
                    </select>

                    <button @disabled($encounters->isEmpty())
                        class="mt-3 w-full rounded-xl bg-cyan-600 px-4 py-3 text-xs font-black text-white disabled:opacity-40">
                        Conectar clasificados
                    </button>
                </form>
            </div>

            {{-- Etapas y encuentros existentes --}}
            <div x-cloak x-show="builderStep === 1 || builderStep === 2 || builderStep === 4" x-transition
                class="mt-6 space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ $rounds->count() }} {{ $rounds->count() === 1 ? 'etapa creada' : 'etapas creadas' }}
                        </p>
                        <h3 class="mt-1 text-lg font-black text-slate-900">
                            Recorrido actual
                        </h3>
                    </div>
                    <p class="max-w-lg text-[10px] leading-4 text-slate-500">
                        Abre un encuentro solamente cuando necesites modificarlo. Los slots muestran también
                        si están disponibles o qué tipo de conexión los ocupa.
                    </p>
                </div>
                @foreach ($rounds as $round)
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[9px] font-black uppercase text-violet-600">
                                    Etapa {{ $round->stage_number }} · {{ $round->branch_label }}
                                </p>
                                <h3 class="mt-1 font-black text-slate-950">{{ $round->name }}</h3>
                                <p class="mt-1 text-[10px] text-slate-500">
                                    {{ $round->participants_expected }} entradas ·
                                    {{ $round->qualifiers_expected }} clasificados ·
                                    {{ $round->encounters->count() }} encuentros
                                </p>
                            </div>

                            <form method="POST"
                                action="{{ route('tournaments.single-elimination.graph.stages.destroy', [$phaseTemplate, $round]) }}"
                                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                data-confirm-title="Eliminar etapa"
                                data-confirm-message="Solo puede eliminarse cuando no contiene encuentros."
                                data-confirm-subject="{{ $round->name }}" data-confirm-action="Eliminar">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-xl border border-red-200 bg-white px-3 py-2 text-[9px] font-black text-red-600">
                                    Eliminar etapa
                                </button>
                            </form>
                        </div>

                        <div class="mt-4 grid gap-3 xl:grid-cols-2">
                            @forelse ($round->encounters as $encounter)
                                <details class="rounded-2xl border border-indigo-200 bg-white p-4">
                                    <summary class="cursor-pointer list-none">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="font-black text-slate-900">{{ $encounter->name }}</p>
                                                <p class="mt-1 text-[10px] font-bold text-indigo-600">
                                                    {{ $encounter->competitive_format_label }} ·
                                                    {{ $encounter->profile_label }} ·
                                                    {{ data_get($encounter->settings, 'resolution_mode', 'RANKING') }}
                                                </p>
                                            </div>
                                            <span class="rounded-full bg-indigo-100 px-2 py-1 text-[8px] font-black text-indigo-700">
                                                Editar
                                            </span>
                                        </div>
                                    </summary>

                                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                        @foreach ($encounter->slots as $slot)
                                            @php
                                                $slotConnection = $slot->incomingConnections
                                                    ->where('status', 'ACTIVE')
                                                    ->first();
                                                $slotSource = match ($slotConnection?->source_type) {
                                                    'INPUT_GATE' => 'Entrada',
                                                    'RESULT' => 'Resultado previo',
                                                    default => 'Disponible',
                                                };
                                            @endphp

                                            <div @class([
                                                'rounded-xl border px-3 py-2',
                                                'border-emerald-200 bg-emerald-50' => !$slotConnection,
                                                'border-red-200 bg-red-50' => $slotConnection,
                                            ])>
                                                <p @class([
                                                    'text-[9px] font-black',
                                                    'text-emerald-700' => !$slotConnection,
                                                    'text-red-700' => $slotConnection,
                                                ])>
                                                    Slot {{ $slot->position }} · {{ $slotConnection ? 'Ocupado' : 'Libre' }}
                                                </p>
                                                <p class="mt-0.5 text-[8px] font-bold text-slate-500">
                                                    {{ $slotSource }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>

                                    <form method="POST"
                                        action="{{ route('tournaments.single-elimination.graph.encounters.update', [$phaseTemplate, $encounter]) }}"
                                        class="mt-4 space-y-3" data-omni-confirm data-confirm-variant="warning"
                                        data-confirm-icon="◇" data-confirm-title="Actualizar encuentro"
                                        data-confirm-message="Si cambias K → Q se reconstruirán sus slots y resultados."
                                        data-confirm-subject="{{ $encounter->name }}" data-confirm-action="Actualizar">
                                        @csrf
                                        @method('PUT')

                                        <input name="name" value="{{ $encounter->name }}" maxlength="120" required
                                            class="w-full rounded-xl border-slate-200 text-sm font-bold">

                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="number" name="entrants_count" min="2" max="64"
                                                value="{{ $encounter->entrants_count }}" required
                                                class="rounded-xl border-slate-200 text-sm">
                                            <input type="number" name="qualifiers_count" min="1" max="63"
                                                value="{{ $encounter->qualifiers_count }}" required
                                                class="rounded-xl border-slate-200 text-sm">
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <select name="encounter_profile" class="rounded-xl border-slate-200 text-xs">
                                                <option value="DUEL" @selected($encounter->encounter_profile === 'DUEL')>Duelo</option>
                                                <option value="MULTI_COMPETITOR" @selected($encounter->encounter_profile === 'MULTI_COMPETITOR')>Multicompetidor</option>
                                            </select>
                                            <select name="resolution_mode" class="rounded-xl border-slate-200 text-xs">
                                                @foreach (['SCORE' => 'Marcador', 'RANKING' => 'Ranking', 'MANUAL_SELECTION' => 'Selección manual'] as $value => $label)
                                                    <option value="{{ $value }}" @selected(data_get($encounter->settings, 'resolution_mode') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <select name="qualifier_ordering" class="rounded-xl border-slate-200 text-xs">
                                                <option value="ORDERED" @selected(data_get($encounter->settings, 'qualifier_ordering', 'ORDERED') === 'ORDERED')>Ordenados</option>
                                                <option value="UNORDERED" @selected(data_get($encounter->settings, 'qualifier_ordering') === 'UNORDERED')>Sin orden</option>
                                            </select>
                                            <select name="series_format" class="rounded-xl border-slate-200 text-xs">
                                                @foreach (['NONE' => 'Sin serie', 'BEST_OF' => 'Best of', 'FIXED_GAMES' => 'Juegos fijos'] as $value => $label)
                                                    <option value="{{ $value }}" @selected($encounter->series_format === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <select name="best_of" class="rounded-xl border-slate-200 text-xs">
                                                @foreach ([1, 3, 5, 7, 9, 11] as $bestOf)
                                                    <option value="{{ $bestOf }}" @selected((int) $encounter->best_of === $bestOf)>BO{{ $bestOf }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" name="fixed_games" min="1" max="99"
                                                value="{{ $encounter->fixed_games ?? 1 }}"
                                                class="rounded-xl border-slate-200 text-xs">
                                        </div>

                                        <textarea name="description" rows="2" maxlength="2000"
                                            class="w-full rounded-xl border-slate-200 text-xs">{{ $encounter->description }}</textarea>

                                        <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-xs font-black text-white">
                                            Guardar encuentro
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('tournaments.single-elimination.graph.encounters.destroy', [$phaseTemplate, $encounter]) }}"
                                        class="mt-2" data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                        data-confirm-title="Eliminar encuentro"
                                        data-confirm-message="Se eliminarán sus slots, resultados y conexiones."
                                        data-confirm-subject="{{ $encounter->name }}" data-confirm-action="Eliminar">
                                        @csrf
                                        @method('DELETE')
                                        <button class="w-full rounded-xl border border-red-200 px-4 py-2 text-[9px] font-black text-red-600">
                                            Eliminar encuentro
                                        </button>
                                    </form>
                                </details>
                            @empty
                                <p class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-xs font-bold text-slate-400">
                                    Esta etapa todavía no contiene encuentros.
                                </p>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>

            <div x-cloak x-show="builderStep === 3 || builderStep === 4" x-transition
                class="mt-6 rounded-3xl border border-cyan-200 bg-cyan-50 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[9px] font-black uppercase text-cyan-700">Rutas competitivas</p>
                        <p class="mt-1 text-xs text-cyan-800">
                            Cada tarjeta puede contener varias conexiones individuales agrupadas.
                        </p>
                    </div>

                    <a href="{{ route('tournaments.single-elimination.structure.io', $phaseTemplate) }}"
                        class="rounded-xl bg-fuchsia-600 px-4 py-3 text-[10px] font-black text-white">
                        Configurar entradas y salidas →
                    </a>
                </div>

                <div class="mt-4 grid gap-2 lg:grid-cols-2">
                    @forelse ($manualRouteGroups as $routeGroup)
                        @php
                            $firstRoute = $routeGroup->first();
                            $quantity = $routeGroup->count();
                        @endphp

                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-cyan-200 bg-white p-4">
                            <div>
                                <p class="text-xs font-black text-slate-900">
                                    {{ $firstRoute->sourceResult?->encounter?->name ?? 'Origen' }}
                                    →
                                    {{ $firstRoute->targetSlot?->encounter?->name ?? $firstRoute->targetPhaseExit?->name ?? 'Destino' }}
                                </p>
                                <p class="mt-1 text-[10px] font-bold text-cyan-700">
                                    {{ $quantity }} {{ $quantity === 1 ? 'clasificado' : 'clasificados' }}
                                </p>
                            </div>

                            <form method="POST"
                                action="{{ route('tournaments.single-elimination.graph.routes.destroy', [$phaseTemplate, $firstRoute]) }}"
                                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                data-confirm-title="Eliminar ruta" data-confirm-message="Se liberarán sus posiciones y slots."
                                data-confirm-subject="{{ $firstRoute->label }}" data-confirm-action="Eliminar">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-xl border border-red-200 px-3 py-2 text-[9px] font-black text-red-600">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-cyan-300 bg-white p-5 text-center text-xs font-bold text-cyan-700 lg:col-span-2">
                            Todavía no existen rutas entre clasificados y encuentros posteriores.
                        </p>
                    @endforelse
                </div>
            </div>

            <div x-cloak x-show="builderStep !== 4"
                class="mt-5 flex justify-end">
                <button type="button" @click="builderStep = Math.min(4, builderStep + 1)"
                    class="rounded-xl border border-fuchsia-200 bg-white px-4 py-3 text-xs font-black text-fuchsia-700 transition hover:bg-fuchsia-50">
                    Continuar al siguiente paso →
                </button>
            </div>
        @endunless
    </div>
</section>

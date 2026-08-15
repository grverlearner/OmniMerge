<x-tournament-layout>
    <x-slot name="header">
        Estructura interna · {{ $phaseTemplate->name }}
    </x-slot>

    @php
        $structureStatus = $settings->structure_status ?? 'NOT_GENERATED';

        $statusClasses = match ($structureStatus) {
            'VALID' => 'border-emerald-300 bg-emerald-100 text-emerald-800',
            'INVALID' => 'border-red-300 bg-red-100 text-red-800',
            'STALE' => 'border-amber-300 bg-amber-100 text-amber-800',
            'GENERATED' => 'border-indigo-300 bg-indigo-100 text-indigo-800',
            default => 'border-slate-300 bg-slate-100 text-slate-700',
        };

        $defaultParticipants = $phaseTemplate->exact_participants ?? $phaseTemplate->min_participants;
    @endphp

    <div class="pb-16">
        {{-- NAVEGACIÓN --}}

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('tournaments.single-elimination.show', $phaseTemplate) }}"
                class="inline-flex items-center gap-2 text-sm font-black text-slate-400 transition hover:text-violet-600">
                ← Volver a Eliminación Simple
            </a>

            <div class="flex items-center gap-2">
                <span
                    class="rounded-full border px-3 py-1.5 text-[10px] font-black uppercase tracking-wider {{ $statusClasses }}">
                    {{ $settings->structure_status_label }}
                </span>

                <span
                    class="rounded-full border border-slate-200 bg-white px-3 py-1.5 font-mono text-[10px] font-bold text-slate-400">
                    v{{ $settings->structure_version ?? 0 }}
                </span>
            </div>
        </div>

        {{-- HERO --}}

        <section
            class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-violet-950 to-indigo-950 p-6 text-white shadow-xl sm:p-8">
            <div
                class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-fuchsia-400/15 blur-3xl">
            </div>

            <div
                class="pointer-events-none absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-indigo-400/10 blur-3xl">
            </div>

            <div class="relative flex flex-col justify-between gap-7 xl:flex-row xl:items-end">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-violet-200">
                        ◇ Grafo interno de la Fase
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">
                        {{ $phaseTemplate->name }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                        Estructura persistente de puertas, rondas, encuentros,
                        slots, resultados, conexiones y salidas.
                    </p>

                    @if ($settings->structure_generated_at)
                        <p class="mt-3 text-[10px] font-bold uppercase tracking-wider text-violet-300">
                            Generada {{ $settings->structure_generated_at->diffForHumans() }}
                        </p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:min-w-[560px]">
                    @foreach ([['Entradas', $validation['stats']['input_gates'], 'text-fuchsia-300'], ['Rondas', $validation['stats']['rounds'], 'text-violet-300'], ['Encuentros', $validation['stats']['encounters'], 'text-indigo-300'], ['Conexiones', $validation['stats']['connections'], 'text-cyan-300']] as [$label, $value, $color])
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur">
                            <p class="text-[8px] font-black uppercase tracking-wider {{ $color }}">
                                {{ $label }}
                            </p>

                            <p class="mt-1 text-xl font-black">
                                {{ $value }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- MENSAJES --}}

        @if (session('warning'))
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="font-black text-red-900">
                    No se pudo generar la estructura
                </p>

                <div class="mt-2 space-y-1">
                    @foreach ($errors->all() as $error)
                        <p class="text-xs leading-5 text-red-700">
                            • {{ $error }}
                        </p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ACCIONES --}}

        <section class="mt-6 grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <form method="POST"
                action="{{ route('tournaments.single-elimination.structure.generate', $phaseTemplate) }}"
                class="rounded-3xl border border-violet-200 bg-white p-5 shadow-sm" data-omni-confirm
                data-confirm-variant="warning" data-confirm-icon="◇" data-confirm-title="Generar estructura interna"
                data-confirm-message="Se reconstruirán las rondas, encuentros, slots, resultados y conexiones generadas."
                data-confirm-subject="{{ $phaseTemplate->name }}" data-confirm-action="Generar estructura">
                @csrf

                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                            Compilador estructural
                        </p>

                        <h2 class="mt-1 text-xl font-black text-slate-900">
                            Generar desde la Etapa 3
                        </h2>

                        <p class="mt-2 max-w-xl text-xs leading-5 text-slate-500">
                            Utiliza K → Q, los overrides, la política de sobrantes,
                            las entradas, el seeding y el pairing actuales.
                        </p>
                    </div>

                    <span
                        class="w-fit rounded-full border border-violet-200 bg-violet-50 px-3 py-2 text-[10px] font-black text-violet-700">
                        {{ $settings->configuration_mode }}
                    </span>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-[220px_minmax(0,1fr)]">
                    <div>
                        <label for="structure_participants"
                            class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Participantes
                        </label>

                        <input id="structure_participants" type="number" name="participants" min="2"
                            max="512" value="{{ old('participants', $defaultParticipants) }}" @readonly($phaseTemplate->exact_participants !== null)
                            class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm font-black text-slate-900 focus:border-violet-400 focus:ring-violet-400 disabled:bg-slate-100">
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-black text-slate-800">
                            Contrato de la Fase
                        </p>

                        <p class="mt-1 text-[11px] leading-5 text-slate-500">
                            {{ $phaseTemplate->participant_contract_label }}
                        </p>

                        <p class="mt-2 text-[11px] font-bold text-violet-700">
                            {{ $settings->entrants_per_match }}
                            →
                            {{ $settings->qualifiers_per_match }}
                            ·
                            {{ $settings->encounter_profile_label }}
                        </p>
                    </div>
                </div>

                @if (($settings->structure_version ?? 0) > 0)
                    <label
                        class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <input type="checkbox" name="replace_manual" value="1"
                            class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">

                        <span>
                            <span class="block text-xs font-black text-amber-900">
                                Reemplazar personalizaciones protegidas
                            </span>

                            <span class="mt-1 block text-[11px] leading-5 text-amber-700">
                                Actívalo solamente si deseas eliminar elementos manuales o bloqueados.
                            </span>
                        </span>
                    </label>
                @endif

                <div class="mt-5 flex flex-wrap gap-2">
                    <button type="submit"
                        class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-violet-500/20 transition hover:bg-violet-700">
                        {{ ($settings->structure_version ?? 0) > 0 ? 'Regenerar estructura' : 'Generar estructura' }}
                    </button>

                    <a href="{{ route('tournaments.single-elimination.show', $phaseTemplate) }}"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-black text-slate-600 transition hover:bg-slate-50">
                        Editar reglas
                    </a>
                </div>
            </form>

            <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
                    Comprobación
                </p>

                <h2 class="mt-1 text-lg font-black text-slate-900">
                    Validar grafo
                </h2>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Revisa capacidades, rutas, ciclos, resultados obligatorios y salidas.
                </p>

                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="rounded-xl bg-red-50 p-3 text-center">
                        <p class="text-lg font-black text-red-700">
                            {{ $validation['counts']['errors'] }}
                        </p>

                        <p class="text-[8px] font-black uppercase text-red-500">
                            Errores
                        </p>
                    </div>

                    <div class="rounded-xl bg-amber-50 p-3 text-center">
                        <p class="text-lg font-black text-amber-700">
                            {{ $validation['counts']['warnings'] }}
                        </p>

                        <p class="text-[8px] font-black uppercase text-amber-500">
                            Avisos
                        </p>
                    </div>

                    <div class="rounded-xl bg-cyan-50 p-3 text-center">
                        <p class="text-lg font-black text-cyan-700">
                            {{ $validation['counts']['recommendations'] }}
                        </p>

                        <p class="text-[8px] font-black uppercase text-cyan-500">
                            Consejos
                        </p>
                    </div>
                </div>

                <form method="POST"
                    action="{{ route('tournaments.single-elimination.structure.validate', $phaseTemplate) }}"
                    class="mt-4">
                    @csrf

                    <button type="submit"
                        class="w-full rounded-xl bg-slate-900 px-4 py-3 text-xs font-black text-white transition hover:bg-slate-800">
                        Ejecutar validación
                    </button>
                </form>
            </aside>
        </section>

        {{-- DIAGNÓSTICO --}}

        <div class="mt-6">
            @include('tournaments.phase-templates.partials.single-elimination-internal-diagnostic')
        </div>

        @if ($rounds->isEmpty())
            <section
                class="mt-8 rounded-[32px] border border-dashed border-violet-300 bg-violet-50/40 p-10 text-center">
                <span
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-violet-100 text-2xl font-black text-violet-700">
                    ◇
                </span>

                <h2 class="mt-4 text-xl font-black text-slate-900">
                    Todavía no existe estructura interna
                </h2>

                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                    Revisa primero la configuración matemática y luego utiliza
                    “Generar estructura”.
                </p>
            </section>
        @else
            {{-- ENTRADAS --}}

            <section class="mt-8">
                <div class="mb-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-fuchsia-600">
                        Puertas de entrada
                    </p>

                    <h2 class="mt-1 text-2xl font-black text-slate-900">
                        Contrato interno
                    </h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($inputGates as $gate)
                        <article class="rounded-3xl border border-fuchsia-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-900">
                                        {{ $gate->name }}
                                    </p>

                                    <p class="mt-1 font-mono text-[9px] font-bold text-slate-400">
                                        {{ $gate->code }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-fuchsia-100 px-2.5 py-1 text-[9px] font-black uppercase text-fuchsia-700">
                                    {{ $gate->type_label }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-[8px] font-black uppercase text-slate-400">
                                        Capacidad
                                    </p>

                                    <p class="mt-1 text-xs font-black text-slate-800">
                                        {{ $gate->contract_label }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-[8px] font-black uppercase text-slate-400">
                                        Distribución
                                    </p>

                                    <p class="mt-1 text-xs font-black text-slate-800">
                                        {{ $gate->distribution_label }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-3 text-[10px] font-bold text-fuchsia-600">
                                {{ $gate->contextualEntryPorts->count() }}
                                {{ $gate->contextualEntryPorts->count() === 1 ? 'puerto contextual' : 'puertos contextuales' }}
                            </p>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- RONDAS Y ENCUENTROS --}}

            <section class="mt-10">
                <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                            Estructura competitiva
                        </p>

                        <h2 class="mt-1 text-2xl font-black text-slate-900">
                            Rondas y encuentros
                        </h2>
                    </div>

                    <p class="text-xs font-bold text-slate-400">
                        {{ $validation['stats']['slots'] }} slots ·
                        {{ $validation['stats']['results'] }} resultados
                    </p>
                </div>

                <div class="space-y-5">
                    @foreach ($rounds as $round)
                        <details class="group overflow-hidden rounded-3xl border border-violet-200 bg-white shadow-sm"
                            @if ($loop->first) open @endif>
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between gap-4 bg-gradient-to-r from-violet-50 to-indigo-50 p-5">
                                <div class="flex min-w-0 items-center gap-4">
                                    <span
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-600 text-sm font-black text-white">
                                        {{ $round->stage_number }}
                                    </span>

                                    <div class="min-w-0">
                                        <p class="truncate font-black text-slate-900">
                                            {{ $round->name }}
                                        </p>

                                        <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-violet-600">
                                            {{ $round->type_label }}
                                            ·
                                            {{ $round->encounters->count() }}
                                            {{ $round->encounters->count() === 1 ? 'encuentro' : 'encuentros' }}
                                        </p>
                                    </div>
                                </div>

                                <span class="text-xl font-black text-violet-400 transition group-open:rotate-180">
                                    ⌄
                                </span>
                            </summary>

                            <div class="grid gap-4 p-5 xl:grid-cols-2">
                                @foreach ($round->encounters as $encounter)
                                    <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-black text-slate-900">
                                                    {{ $encounter->name }}
                                                </p>

                                                <p class="mt-1 font-mono text-[9px] font-bold text-slate-400">
                                                    {{ $encounter->code }}
                                                </p>
                                            </div>

                                            <span
                                                class="rounded-full bg-indigo-100 px-2.5 py-1 text-[9px] font-black uppercase text-indigo-700">
                                                {{ $encounter->competitive_format_label }}
                                            </span>
                                        </div>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <span
                                                class="rounded-lg bg-white px-2 py-1 text-[9px] font-black text-slate-600">
                                                {{ $encounter->profile_label }}
                                            </span>

                                            <span
                                                class="rounded-lg bg-white px-2 py-1 text-[9px] font-black text-violet-600">
                                                {{ $encounter->series_label }}
                                            </span>

                                            @if ($encounter->allows_incomplete)
                                                <span
                                                    class="rounded-lg bg-amber-100 px-2 py-1 text-[9px] font-black text-amber-700">
                                                    Incompleto permitido
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-4">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                                Slots
                                            </p>

                                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                @foreach ($encounter->slots as $slot)
                                                    @php
                                                        $incoming = $connections
                                                            ->where('target_slot_id', $slot->id)
                                                            ->first();
                                                    @endphp

                                                    <div @class([
                                                        'rounded-xl border p-3',
                                                        'border-indigo-200 bg-indigo-50' => $incoming,
                                                        'border-amber-200 bg-amber-50' => !$incoming && !$slot->is_required,
                                                        'border-red-200 bg-red-50' => !$incoming && $slot->is_required,
                                                    ])>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <p class="text-[10px] font-black text-slate-800">
                                                                Slot {{ $slot->position }}
                                                            </p>

                                                            <span
                                                                class="text-[8px] font-black uppercase text-slate-400">
                                                                {{ $slot->type_label }}
                                                            </span>
                                                        </div>

                                                        <p class="mt-1 truncate text-[9px] font-bold text-slate-500">
                                                            {{ $incoming?->source_label ?? 'Sin fuente' }}
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                                Resultados
                                            </p>

                                            <div class="mt-2 space-y-2">
                                                @foreach ($encounter->results as $result)
                                                    @php
                                                        $resultRoutes = $connections->where(
                                                            'source_result_id',
                                                            $result->id,
                                                        );
                                                    @endphp

                                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p class="text-[10px] font-black text-slate-800">
                                                                    {{ $result->name }}
                                                                </p>

                                                                <p class="mt-0.5 text-[9px] font-bold text-slate-400">
                                                                    {{ $result->quantity_label }}
                                                                </p>
                                                            </div>

                                                            <span @class([
                                                                'rounded-full px-2 py-1 text-[8px] font-black uppercase',
                                                                'bg-emerald-100 text-emerald-700' =>
                                                                    $result->participant_status === 'ACTIVE',
                                                                'bg-red-100 text-red-700' => $result->participant_status === 'ELIMINATED',
                                                                'bg-slate-100 text-slate-600' => !in_array(
                                                                    $result->participant_status,
                                                                    ['ACTIVE', 'ELIMINATED'],
                                                                    true),
                                                            ])>
                                                                {{ $result->participant_status }}
                                                            </span>
                                                        </div>

                                                        <div class="mt-2 space-y-1">
                                                            @forelse ($resultRoutes as $route)
                                                                <p
                                                                    class="truncate text-[9px] font-bold text-indigo-600">
                                                                    → {{ $route->target_label }}
                                                                </p>
                                                            @empty
                                                                <p class="text-[9px] font-bold text-red-500">
                                                                    Sin destino
                                                                </p>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>

            {{-- TABLA DE CONEXIONES --}}

            <section class="mt-10 overflow-hidden rounded-3xl border border-indigo-200 bg-white">
                <div class="border-b border-indigo-100 bg-indigo-50/60 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-600">
                        Tabla de rutas
                    </p>

                    <h2 class="mt-1 text-xl font-black text-slate-900">
                        Conexiones internas
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-slate-400">
                                    Código
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-slate-400">
                                    Origen
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-slate-400">
                                    Destino
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-slate-400">
                                    Asignación
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($connections as $connection)
                                <tr class="transition hover:bg-indigo-50/40">
                                    <td
                                        class="whitespace-nowrap px-4 py-3 font-mono text-[9px] font-bold text-slate-400">
                                        {{ $connection->code }}
                                    </td>

                                    <td class="px-4 py-3 text-xs font-bold text-slate-700">
                                        {{ $connection->source_label }}
                                    </td>

                                    <td class="px-4 py-3 text-xs font-bold text-indigo-700">
                                        {{ $connection->target_label }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-[10px] font-black text-slate-500">
                                        {{ $connection->allocation_label }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- SALIDAS --}}

            <section class="mt-10">
                <div class="mb-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-600">
                        Contrato externo
                    </p>

                    <h2 class="mt-1 text-2xl font-black text-slate-900">
                        Puertas de salida
                    </h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($exits as $exit)
                        <article class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-900">
                                        {{ $exit->name }}
                                    </p>

                                    <p class="mt-1 font-mono text-[9px] font-bold text-slate-400">
                                        {{ $exit->code }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-emerald-100 px-2.5 py-1 text-[9px] font-black uppercase text-emerald-700">
                                    {{ $exit->selector_label }}
                                </span>
                            </div>

                            <p class="mt-3 text-xs leading-5 text-slate-500">
                                {{ $exit->selection_summary }}
                            </p>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-[8px] font-black uppercase text-slate-400">
                                        Capacidad
                                    </p>

                                    <p class="mt-1 text-xs font-black text-slate-800">
                                        {{ $exit->contract_label }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-[8px] font-black uppercase text-slate-400">
                                        Rutas
                                    </p>

                                    <p class="mt-1 text-xs font-black text-slate-800">
                                        {{ $exit->incomingInternalConnections->count() }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-tournament-layout>

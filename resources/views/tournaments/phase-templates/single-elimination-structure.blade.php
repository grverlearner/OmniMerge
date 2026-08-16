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

        $elementUpdateUrl = route('tournaments.single-elimination.structure.elements.update', [
            'phaseTemplate' => $phaseTemplate,

            'elementType' => '__TYPE__',

            'element' => '__ID__',
        ]);

        $initialSelection = request()->query('selected', '');

        $initialView = request()->query('view', '');
    @endphp

    <div x-data="singleEliminationStructureVisualizer(
        @js($visualizer), {
            updateUrlTemplate: @js($elementUpdateUrl),
            initialSelection: @js($initialSelection),
            initialView: @js($initialView)
        }
    )" class="pb-16">
        {{-- Navegación --}}
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('tournaments.single-elimination.show', $phaseTemplate) }}"
                class="inline-flex items-center gap-2 text-sm font-black text-slate-400 transition hover:text-violet-600">
                ← Volver a Eliminación Simple
            </a>

            <div class="flex flex-wrap items-center gap-2">
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

        {{-- Cabecera principal --}}
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
                        ◇ Visualizador estructural
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">
                        {{ $phaseTemplate->name }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                        Explora puertas, rondas, encuentros, slots, resultados y
                        conexiones internas sin depender de zoom ni de un lienzo infinito.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if ($settings->structure_generated_at)
                            <span
                                class="rounded-full bg-white/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-wider text-violet-200">
                                Generada
                                {{ $settings->structure_generated_at->diffForHumans() }}
                            </span>
                        @endif

                        @if ($settings->structure_validated_at)
                            <span
                                class="rounded-full bg-white/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-wider text-cyan-200">
                                Validada
                                {{ $settings->structure_validated_at->diffForHumans() }}
                            </span>
                        @endif

                        <span
                            class="rounded-full bg-white/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-wider text-slate-300">
                            {{ $settings->configuration_mode }}
                        </span>
                    </div>
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

        {{-- Mensaje de éxito --}}
        @if (session('success'))
            <div
                class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Mensaje de advertencia --}}
        @if (session('warning'))
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">
                {{ session('warning') }}
            </div>
        @endif

        {{-- Errores de formularios --}}
        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="font-black text-red-900">
                    No se pudo completar la acción
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

        {{-- Generación y validación --}}
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
                            Utiliza K → Q, overrides, política de sobrantes,
                            modos de entrada, seeding y emparejamiento actuales.
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
                            Contrato de la fase
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
                                Actívalo solamente si deseas eliminar elementos
                                manuales o bloqueados mediante una regeneración.
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
                    Revisa capacidades, rutas, ciclos, resultados obligatorios,
                    fuentes, destinos y puertas de salida.
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

                <button type="button" @click="problemsOpen = true"
                    class="mt-4 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-black text-slate-700 transition hover:border-violet-300 hover:text-violet-700">
                    Ver diagnóstico
                </button>

                <form method="POST"
                    action="{{ route('tournaments.single-elimination.structure.validate', $phaseTemplate) }}"
                    class="mt-2">
                    @csrf

                    <button type="submit"
                        class="w-full rounded-xl bg-slate-900 px-4 py-3 text-xs font-black text-white transition hover:bg-slate-800">
                        Ejecutar validación
                    </button>
                </form>
            </aside>
        </section>

        @if ($rounds->isEmpty())
            {{-- Estado inicial sin estructura --}}
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
                    Revisa primero la configuración matemática y después
                    utiliza el botón “Generar estructura”.
                </p>

                <a href="{{ route('tournaments.single-elimination.show', $phaseTemplate) }}"
                    class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-violet-500/20">
                    Revisar configuración
                </a>
            </section>
        @else
            {{-- Barra de herramientas --}}
            @include('tournaments.phase-templates.partials.single-elimination-visualizer-toolbar')

            {{-- Vista compacta --}}
            @include('tournaments.phase-templates.partials.single-elimination-visualizer-compact')

            {{-- Vista detallada --}}
            @include('tournaments.phase-templates.partials.single-elimination-visualizer-blocks')

            {{-- Vista tabular --}}
            @include('tournaments.phase-templates.partials.single-elimination-visualizer-table')

            {{-- Inspector y diagnóstico --}}
            @include('tournaments.phase-templates.partials.single-elimination-visualizer-inspector')
        @endif
    </div>
</x-tournament-layout>

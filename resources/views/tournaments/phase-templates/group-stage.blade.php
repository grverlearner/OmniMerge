<x-tournament-layout>

    <x-slot name="header">
        Group Stage · {{ $phaseTemplate->name }}
    </x-slot>

    <div class="mb-5">
        <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}"
            class="inline-flex items-center gap-2 text-sm font-black text-slate-400 transition hover:text-indigo-600">
            ← Volver a la Fase
        </a>
    </div>


    {{-- HERO --}}

    <section
        class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950 p-7 text-white shadow-xl sm:p-8">

        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-indigo-400/20 blur-3xl">
        </div>

        <div class="relative flex flex-col justify-between gap-7 lg:flex-row lg:items-end">

            <div class="max-w-3xl">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-indigo-300/20 bg-indigo-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-indigo-300">
                    ▦ Group Stage Engine
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">{{ $phaseTemplate->name }}</h1>
                <p class="mt-3 font-mono text-xs text-white/50">{{ $phaseTemplate->code }}</p>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                    Divide participantes en grupos, controla cómo se distribuyen,
                    reutiliza Round Robin dentro de cada grupo y define múltiples
                    caminos de clasificación mediante reglas independientes.
                </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                <p class="text-[9px] font-black uppercase tracking-wider text-indigo-300">Estructura</p>
                <p class="mt-2 text-lg font-black">{{ $settings->group_count_mode_label }}</p>
            </div>

        </div>
    </section>


    {{-- QUICK STATS --}}

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">

        @foreach ([['Modo', $settings->group_count_mode_label, '▦'], ['Distribución', $settings->distribution_mode_label, '⇄'], ['Engine', $settings->internal_engine_label, '↻'], ['Ciclos', $settings->internal_cycles, '×'], ['Cross Rank', $settings->cross_group_normalization_label, '#']] as [$label, $value, $icon])
            <article class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p>
                        <p class="mt-2 truncate text-sm font-black text-slate-800">{{ $value }}</p>
                    </div>

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xs font-black text-indigo-700">
                        {{ $icon }}
                    </div>
                </div>
            </article>
        @endforeach

    </section>


    {{-- CONFIG + PREVIEW --}}

    <section class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_500px]">

        <div>
            <div class="mb-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600">Engine Configuration</p>
                <h2 class="mt-2 text-2xl font-black text-slate-900">Configuración de grupos</h2>
            </div>

            @include('tournaments.phase-templates.partials.group-stage-settings-form')
        </div>

        <aside>
            <div class="xl:sticky xl:top-28">
                @include('tournaments.phase-templates.partials.group-stage-preview')
            </div>
        </aside>

    </section>


    {{-- GROUP DEFINITIONS --}}

    <section class="mt-10">

        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600">Group Definitions</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">Definiciones de grupos</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Son definiciones reutilizables de la plantilla. Todavía no contienen participantes reales.
            </p>
        </div>

        @if ($settings->group_count_mode === 'TARGET_GROUP_SIZE')

            <div class="mt-5 rounded-3xl border border-dashed border-indigo-300 bg-indigo-50 p-6">
                <p class="font-black text-indigo-900">Grupos dinámicos</p>
                <p class="mt-2 text-sm leading-6 text-indigo-700">
                    La cantidad depende del número de participantes de cada ejecución.
                    Los nombres se generan automáticamente en el preview.
                </p>
            </div>
        @else
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                @foreach ($activeGroupDefinitions as $group)
                    <article x-data="{ editing: false }" class="rounded-3xl border border-slate-200 bg-white p-5">

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-mono text-[9px] font-black text-indigo-500">{{ $group->code }}</p>
                                <h3 class="mt-1 text-lg font-black text-slate-900">{{ $group->name }}</h3>

                                <p class="mt-2 text-xs text-slate-500">
                                    Capacidad:
                                    <strong>{{ $group->capacity ?? 'Automática' }}</strong>
                                </p>
                            </div>

                            <button type="button" @click="editing = !editing"
                                class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-500">
                                ✎
                            </button>
                        </div>

                        <div x-show="editing" x-transition style="display: none;"
                            class="mt-5 border-t border-slate-100 pt-5">
                            @include('tournaments.phase-templates.partials.group-stage-group-form', [
                                'group' => $group,
                            ])
                        </div>

                        @if ($settings->group_count_mode === 'CUSTOM_GROUPS')
                            <form method="POST"
                                action="{{ route('tournaments.group-stage.groups.destroy', [$phaseTemplate, $group]) }}"
                                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                data-confirm-title="Eliminar grupo"
                                data-confirm-message="Esta definición de grupo será eliminada."
                                data-confirm-subject="{{ $group->name }}" data-confirm-action="Eliminar grupo"
                                class="mt-4">

                                @csrf
                                @method('DELETE')

                                <button type="submit" class="text-xs font-black text-red-500">Eliminar grupo</button>
                            </form>
                        @endif

                    </article>
                @endforeach

            </div>

            @if ($settings->group_count_mode === 'CUSTOM_GROUPS')
                <div class="mt-5 max-w-md rounded-3xl border border-indigo-200 bg-indigo-50/60 p-5">
                    <p class="text-[10px] font-black uppercase tracking-wider text-indigo-600">Nuevo grupo</p>

                    <div class="mt-4">
                        @include('tournaments.phase-templates.partials.group-stage-group-form', [
                            'group' => null,
                        ])
                    </div>
                </div>
            @endif

        @endif

    </section>


    {{-- ADVANCEMENT RULES --}}

    <section class="mt-10">

        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">Advancement Engine</p>
                <h2 class="mt-2 text-2xl font-black text-slate-900">Reglas de clasificación</h2>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Se evalúan en orden. Una vez que un participante es seleccionado,
                    las reglas posteriores no vuelven a seleccionarlo.
                </p>
            </div>

            <div class="rounded-xl bg-amber-50 px-4 py-2.5 text-xs font-black text-amber-700">
                {{ $advancementRules->count() }} reglas
            </div>
        </div>

        @if ($phaseExits->isEmpty())

            <div class="mt-6 rounded-3xl border border-dashed border-amber-300 bg-amber-50 p-7 text-center">
                <p class="font-black text-amber-900">Primero necesitas puertas de salida</p>

                <p class="mt-2 text-sm text-amber-700">
                    Crea puertas como Clasificados, Repechaje o Eliminados y utiliza
                    el selector “Reglas del Engine”.
                </p>

                <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}#exits"
                    class="mt-4 inline-flex rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white">
                    Crear puertas →
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_410px]">

                <div class="space-y-4">

                    @forelse ($advancementRules as $rule)
                        <article x-data="{ editing: false }" class="rounded-3xl border border-slate-200 bg-white p-5">

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">

                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 font-black text-amber-700">
                                    {{ $loop->iteration }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-black text-slate-900">{{ $rule->rule_type_label }}</h3>

                                        <span
                                            class="rounded-full bg-indigo-50 px-2.5 py-1 text-[9px] font-black text-indigo-700">
                                            → {{ $rule->phaseExit?->name ?? 'Sin puerta' }}
                                        </span>

                                        @if ($rule->status !== 'ACTIVE')
                                            <span
                                                class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-black text-slate-500">
                                                INACTIVA
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ $rule->rule_summary }}</p>
                                </div>

                                <div class="flex shrink-0 gap-1">

                                    @if (!$loop->first)
                                        <form method="POST"
                                            action="{{ route('tournaments.group-stage.advancement-rules.move-up', [$phaseTemplate, $rule]) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-black text-slate-500">↑</button>
                                        </form>
                                    @endif

                                    @if (!$loop->last)
                                        <form method="POST"
                                            action="{{ route('tournaments.group-stage.advancement-rules.move-down', [$phaseTemplate, $rule]) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-black text-slate-500">↓</button>
                                        </form>
                                    @endif

                                    <button type="button" @click="editing = !editing"
                                        class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-black text-slate-500">
                                        ✎
                                    </button>

                                    <form method="POST"
                                        action="{{ route('tournaments.group-stage.advancement-rules.destroy', [$phaseTemplate, $rule]) }}"
                                        data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                        data-confirm-title="Eliminar regla"
                                        data-confirm-message="Esta regla dejará de utilizarse."
                                        data-confirm-subject="{{ $rule->rule_type_label }}"
                                        data-confirm-action="Eliminar regla">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="rounded-lg bg-red-50 px-2.5 py-2 text-xs font-black text-red-600">×</button>
                                    </form>

                                </div>
                            </div>

                            <div x-show="editing" x-transition style="display: none;"
                                class="mt-5 border-t border-slate-100 pt-5">
                                @include(
                                    'tournaments.phase-templates.partials.group-stage-advancement-rule-form',
                                    ['advancementRule' => $rule]
                                )
                            </div>

                        </article>

                    @empty

                        <div class="rounded-3xl border border-dashed border-amber-300 bg-white p-8 text-center">
                            <p class="font-black text-slate-800">No existen reglas de clasificación</p>
                            <p class="mt-2 text-sm text-slate-500">Por ahora ningún participante sabe por qué puerta
                                abandonará Group Stage.</p>
                        </div>
                    @endforelse

                </div>

                <aside class="h-fit rounded-3xl border border-amber-200 bg-amber-50/60 p-5 xl:sticky xl:top-28">
                    <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Nueva regla</p>
                    <h3 class="mt-2 font-black text-slate-900">Definir quién clasifica</h3>

                    <div class="mt-5">
                        @include('tournaments.phase-templates.partials.group-stage-advancement-rule-form', [
                            'advancementRule' => null,
                        ])
                    </div>
                </aside>

            </div>

        @endif

    </section>


    {{-- CROSS GROUP TIEBREAKERS --}}

    <section class="mt-10">

        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-fuchsia-600">Cross Group Ranking</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">Desempates entre grupos</h2>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Estos criterios se utilizan cuando Group Stage compara participantes
                que ocuparon posiciones equivalentes en grupos distintos.
            </p>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

            <div class="space-y-3">

                @foreach ($tiebreakers as $tiebreaker)
                    <article x-data="{ editing: false }" class="rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-fuchsia-100 text-sm font-black text-fuchsia-700">
                                {{ $loop->iteration }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-black text-slate-900">{{ $tiebreaker->criterion_label }}</p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $tiebreaker->normalization_label }}
                                    ·
                                    {{ $tiebreaker->direction_label }}
                                </p>
                            </div>

                            <div class="flex gap-1">

                                @if (!$loop->first)
                                    <form method="POST"
                                        action="{{ route('tournaments.group-stage.tiebreakers.move-up', [$phaseTemplate, $tiebreaker]) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">↑</button>
                                    </form>
                                @endif

                                @if (!$loop->last)
                                    <form method="POST"
                                        action="{{ route('tournaments.group-stage.tiebreakers.move-down', [$phaseTemplate, $tiebreaker]) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">↓</button>
                                    </form>
                                @endif

                                <button type="button" @click="editing = !editing"
                                    class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">✎</button>

                            </div>
                        </div>

                        <div x-show="editing" x-transition style="display: none;"
                            class="mt-4 border-t border-slate-100 pt-4">
                            @include('tournaments.phase-templates.partials.group-stage-tiebreaker-form', [
                                'tiebreaker' => $tiebreaker,
                            ])

                            <form method="POST"
                                action="{{ route('tournaments.group-stage.tiebreakers.destroy', [$phaseTemplate, $tiebreaker]) }}"
                                class="mt-3">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="text-xs font-black text-red-500">Eliminar
                                    criterio</button>
                            </form>
                        </div>

                    </article>
                @endforeach

            </div>

            <aside class="h-fit rounded-3xl border border-fuchsia-200 bg-fuchsia-50/60 p-5">
                <p class="text-[10px] font-black uppercase tracking-wider text-fuchsia-600">Nuevo criterio</p>

                <div class="mt-5">
                    @include('tournaments.phase-templates.partials.group-stage-tiebreaker-form', [
                        'tiebreaker' => null,
                    ])
                </div>
            </aside>

        </div>

    </section>


    {{-- ARCHITECTURE NOTE --}}

    <section class="mt-10 rounded-3xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-300">Arquitectura OmniMerge</p>
        <h2 class="mt-2 text-xl font-black">Group Stage decide quién sale, no adónde va</h2>

        <div class="mt-5 flex flex-wrap items-center gap-2 text-xs font-black">
            <span class="rounded-xl bg-white/10 px-3 py-2">Grupo</span>
            <span class="text-indigo-400">→</span>
            <span class="rounded-xl bg-white/10 px-3 py-2">Ranking</span>
            <span class="text-indigo-400">→</span>
            <span class="rounded-xl bg-white/10 px-3 py-2">Advancement Rule</span>
            <span class="text-indigo-400">→</span>
            <span class="rounded-xl bg-indigo-500/20 px-3 py-2 text-indigo-300">PhaseExit</span>
            <span class="text-indigo-400">→</span>
            <span class="rounded-xl border border-dashed border-white/20 px-3 py-2 text-slate-400">Tournament Graph
                después</span>
        </div>
    </section>

</x-tournament-layout>

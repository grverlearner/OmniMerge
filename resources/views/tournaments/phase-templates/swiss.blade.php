<x-tournament-layout>

    <x-slot name="header">
        Swiss · {{ $phaseTemplate->name }}
    </x-slot>


    @if ($errors->any())

        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-5">

            <p class="font-black text-red-800">
                Revisa los datos ingresados
            </p>

            @foreach ($errors->all() as $error)
                <p class="mt-1 text-xs text-red-600">
                    • {{ $error }}
                </p>
            @endforeach

        </div>

    @endif


    @include('tournaments.phase-templates.partials.workspace-navigation', [
        'current' => 'rules',
    ])


    {{-- ========================================================= --}}
    {{-- HERO --}}
    {{-- ========================================================= --}}

    <section
        class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-violet-950 to-fuchsia-950 p-7 text-white shadow-xl sm:p-8">

        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-violet-400/20 blur-3xl">
        </div>

        <div class="relative flex flex-col justify-between gap-7 lg:flex-row lg:items-end">

            <div class="max-w-3xl">

                <div
                    class="inline-flex items-center gap-2 rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">

                    ◆ Reglas del Sistema Suizo

                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
                    {{ $phaseTemplate->name }}
                </h1>

                <p class="mt-3 font-mono text-xs text-white/50">
                    {{ $phaseTemplate->code }}
                </p>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                    Genera enfrentamientos dinámicos según rendimiento,
                    evita o controla rematches, administra BYEs y permite
                    avanzar por clasificación final o por umbrales de récord.
                </p>

            </div>


            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">

                <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                    Finalización
                </p>

                <p class="mt-2 text-lg font-black">
                    {{ $settings->completion_mode_label }}
                </p>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- QUICK STATS --}}
    {{-- ========================================================= --}}

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">

        @foreach ([['Finalización', $settings->completion_mode_label, '◆'], ['Pairing', $settings->pairing_algorithm_label, '⇄'], ['Base', $settings->pairing_basis_label, '#'], ['Rematch', $settings->rematch_policy_label, '↺'], ['Default', 'BO' . $settings->default_best_of, '×']] as [$label, $value, $icon])
            <article class="rounded-2xl border border-slate-200 bg-white p-4">

                <div class="flex items-center justify-between gap-3">

                    <div class="min-w-0">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ $label }}
                        </p>

                        <p class="mt-2 truncate text-sm font-black text-slate-800">
                            {{ $value }}
                        </p>

                    </div>

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-xs font-black text-violet-700">
                        {{ $icon }}
                    </div>

                </div>

            </article>
        @endforeach

    </section>


    {{-- ========================================================= --}}
    {{-- CONFIG + PREVIEW --}}
    {{-- ========================================================= --}}

    <section class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_500px]">

        <div>

            <div class="mb-4">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Engine Configuration
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Configuración Swiss
                </h2>

            </div>

            @include('tournaments.phase-templates.partials.swiss-settings-form')

        </div>


        <aside>

            <div class="xl:sticky xl:top-28">

                @include('tournaments.phase-templates.partials.swiss-preview')

            </div>

        </aside>

    </section>


    {{-- ========================================================= --}}
    {{-- MATCH RULES --}}
    {{-- ========================================================= --}}

    <section class="mt-10">

        <div>

            <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-600">
                Match Context Rules
            </p>

            <h2 class="mt-2 text-2xl font-black text-slate-900">
                Best Of según el contexto
            </h2>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                El BO general puede sobrescribirse para una ronda,
                un récord concreto o para partidos donde está en juego
                clasificación o eliminación.
            </p>

        </div>


        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">

            <div class="space-y-3">

                @forelse ($roundRules as $roundRule)
                    <article x-data="{ editing: false }" class="rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-xs font-black text-cyan-700">
                                BO{{ $roundRule->best_of }}
                            </div>

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <p class="font-black text-slate-900">
                                        {{ $roundRule->trigger_label }}
                                    </p>

                                    @if ($roundRule->status !== 'ACTIVE')
                                        <span
                                            class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-black text-slate-500">
                                            INACTIVA
                                        </span>
                                    @endif

                                </div>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $roundRule->trigger_summary }}
                                    ·
                                    {{ $roundRule->draw_override_label }}
                                </p>

                            </div>


                            <div class="flex gap-1">

                                <button type="button" @click="editing = !editing"
                                    class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-black text-slate-500">

                                    ✎

                                </button>


                                <form method="POST"
                                    action="{{ route('tournaments.swiss.round-rules.destroy', [$phaseTemplate, $roundRule]) }}"
                                    data-omni-confirm data-confirm-variant="danger" data-confirm-title="Eliminar regla"
                                    data-confirm-message="Este override Swiss será eliminado."
                                    data-confirm-action="Eliminar">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="rounded-lg bg-red-50 px-2.5 py-2 text-xs font-black text-red-600">

                                        ×

                                    </button>

                                </form>

                            </div>

                        </div>


                        <div x-show="editing" x-transition style="display: none;"
                            class="mt-5 border-t border-slate-100 pt-5">

                            @include('tournaments.phase-templates.partials.swiss-round-rule-form', [
                                'roundRule' => $roundRule,
                            ])

                        </div>

                    </article>

                @empty

                    <div class="rounded-2xl border border-dashed border-cyan-300 bg-white p-7 text-center">

                        <p class="font-black text-slate-800">
                            Se utiliza el Best Of general
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            Agrega overrides si una serie de clasificación,
                            eliminación o una ronda concreta necesita otro formato.
                        </p>

                    </div>
                @endforelse

            </div>


            <aside class="h-fit rounded-3xl border border-cyan-200 bg-cyan-50/60 p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-cyan-600">
                    Nueva regla
                </p>

                <div class="mt-5">

                    @include('tournaments.phase-templates.partials.swiss-round-rule-form', [
                        'roundRule' => null,
                    ])

                </div>

            </aside>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- TIEBREAKERS --}}
    {{-- ========================================================= --}}

    <section class="mt-10">

        <div>

            <p class="text-xs font-black uppercase tracking-[0.18em] text-fuchsia-600">
                Ranking Chain
            </p>

            <h2 class="mt-2 text-2xl font-black text-slate-900">
                Desempates Swiss
            </h2>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Swiss puede considerar no solo el score propio,
                sino también la fuerza de los rivales enfrentados.
            </p>

        </div>


        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

            <div class="space-y-3">

                @forelse ($tiebreakers as $tiebreaker)
                    <article x-data="{ editing: false }" class="rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-fuchsia-100 text-sm font-black text-fuchsia-700">
                                {{ $loop->iteration }}
                            </div>

                            <div class="min-w-0 flex-1">

                                <p class="font-black text-slate-900">
                                    {{ $tiebreaker->summary }}
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    {{ $tiebreaker->criterion_description }}
                                </p>

                                <p class="mt-2 text-[10px] font-black uppercase text-fuchsia-600">
                                    {{ $tiebreaker->direction_label }}
                                </p>

                            </div>


                            <div class="flex gap-1">

                                @if (!$loop->first)
                                    <form method="POST"
                                        action="{{ route('tournaments.swiss.tiebreakers.move-up', [$phaseTemplate, $tiebreaker]) }}">

                                        @csrf
                                        @method('PATCH')

                                        <button class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                            ↑
                                        </button>

                                    </form>
                                @endif


                                @if (!$loop->last)
                                    <form method="POST"
                                        action="{{ route('tournaments.swiss.tiebreakers.move-down', [$phaseTemplate, $tiebreaker]) }}">

                                        @csrf
                                        @method('PATCH')

                                        <button class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                            ↓
                                        </button>

                                    </form>
                                @endif


                                <button type="button" @click="editing = !editing"
                                    class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">

                                    ✎

                                </button>

                            </div>

                        </div>


                        <div x-show="editing" x-transition style="display: none;"
                            class="mt-4 border-t border-slate-100 pt-4">

                            @include('tournaments.phase-templates.partials.swiss-tiebreaker-form', [
                                'tiebreaker' => $tiebreaker,
                            ])


                            <form method="POST"
                                action="{{ route('tournaments.swiss.tiebreakers.destroy', [$phaseTemplate, $tiebreaker]) }}"
                                class="mt-3">

                                @csrf
                                @method('DELETE')

                                <button type="submit" class="text-xs font-black text-red-500">

                                    Eliminar criterio

                                </button>

                            </form>

                        </div>

                    </article>

                @empty

                    <div class="rounded-2xl border border-dashed border-fuchsia-300 bg-white p-7 text-center">

                        <p class="font-black text-slate-800">
                            No hay desempates configurados
                        </p>

                    </div>
                @endforelse

            </div>


            <aside class="h-fit rounded-3xl border border-fuchsia-200 bg-fuchsia-50/60 p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-fuchsia-600">
                    Nuevo criterio
                </p>

                <div class="mt-5">

                    @include('tournaments.phase-templates.partials.swiss-tiebreaker-form', [
                        'tiebreaker' => null,
                    ])

                </div>

            </aside>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- ADVANCEMENT --}}
    {{-- ========================================================= --}}

    <section class="mt-10">

        <div>

            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-600">
                Advancement Engine
            </p>

            <h2 class="mt-2 text-2xl font-black text-slate-900">
                Reglas de salida
            </h2>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Puedes clasificar durante el Swiss al alcanzar un récord
                o esperar al final y utilizar la clasificación.
            </p>

        </div>


        @if ($phaseExits->isEmpty())

            <div class="mt-6 rounded-3xl border border-dashed border-emerald-300 bg-emerald-50 p-7 text-center">

                <p class="font-black text-emerald-900">
                    Primero crea puertas de salida
                </p>

                <p class="mt-2 text-sm text-emerald-700">
                    Para Swiss se recomienda utilizar el selector
                    “Reglas del Engine”.
                </p>

                <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}#exits"
                    class="mt-4 inline-flex rounded-xl bg-emerald-600 px-4 py-3 text-xs font-black text-white">

                    Crear puertas →

                </a>

            </div>
        @else
            <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_410px]">

                <div class="space-y-4">

                    @forelse ($advancementRules as $rule)
                        <article x-data="{ editing: false }" class="rounded-3xl border border-slate-200 bg-white p-5">

                            <div class="flex items-start gap-4">

                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-black text-emerald-700">
                                    {{ $loop->iteration }}
                                </div>


                                <div class="min-w-0 flex-1">

                                    <div class="flex flex-wrap items-center gap-2">

                                        <p class="font-black text-slate-900">
                                            {{ $rule->rule_type_label }}
                                        </p>

                                        <span
                                            class="rounded-full bg-violet-50 px-2.5 py-1 text-[9px] font-black text-violet-700">
                                            → {{ $rule->phaseExit?->name ?? 'Sin puerta' }}
                                        </span>

                                        <span
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-black text-slate-500">
                                            {{ $rule->timing_label }}
                                        </span>

                                    </div>

                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $rule->rule_summary }}
                                    </p>

                                </div>


                                <div class="flex shrink-0 gap-1">

                                    @if (!$loop->first)
                                        <form method="POST"
                                            action="{{ route('tournaments.swiss.advancement-rules.move-up', [$phaseTemplate, $rule]) }}">

                                            @csrf
                                            @method('PATCH')

                                            <button class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                                ↑
                                            </button>

                                        </form>
                                    @endif


                                    @if (!$loop->last)
                                        <form method="POST"
                                            action="{{ route('tournaments.swiss.advancement-rules.move-down', [$phaseTemplate, $rule]) }}">

                                            @csrf
                                            @method('PATCH')

                                            <button class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                                ↓
                                            </button>

                                        </form>
                                    @endif


                                    <button type="button" @click="editing = !editing"
                                        class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs">

                                        ✎

                                    </button>


                                    <form method="POST"
                                        action="{{ route('tournaments.swiss.advancement-rules.destroy', [$phaseTemplate, $rule]) }}"
                                        data-omni-confirm data-confirm-variant="danger"
                                        data-confirm-title="Eliminar regla" data-confirm-action="Eliminar">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            class="rounded-lg bg-red-50 px-2 py-1.5 text-xs font-black text-red-600">
                                            ×
                                        </button>

                                    </form>

                                </div>

                            </div>


                            <div x-show="editing" x-transition style="display: none;"
                                class="mt-5 border-t border-slate-100 pt-5">

                                @include(
                                    'tournaments.phase-templates.partials.swiss-advancement-rule-form',
                                    [
                                        'advancementRule' => $rule,
                                    ]
                                )

                            </div>

                        </article>

                    @empty

                        <div class="rounded-3xl border border-dashed border-emerald-300 bg-white p-8 text-center">

                            <p class="font-black text-slate-800">
                                No hay reglas de salida
                            </p>

                        </div>
                    @endforelse

                </div>


                <aside class="h-fit rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5 xl:sticky xl:top-28">

                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">
                        Nueva regla
                    </p>

                    <div class="mt-5">

                        @include('tournaments.phase-templates.partials.swiss-advancement-rule-form', [
                            'advancementRule' => null,
                        ])

                    </div>

                </aside>

            </div>

        @endif

    </section>


    {{-- ========================================================= --}}
    {{-- ARCHITECTURE --}}
    {{-- ========================================================= --}}

    <section class="mt-10 rounded-3xl bg-slate-950 p-6 text-white">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-300">
            Arquitectura OmniMerge
        </p>

        <h2 class="mt-2 text-xl font-black">
            Swiss decide quién se enfrenta y quién sale; no el destino
        </h2>

        <div class="mt-5 flex flex-wrap items-center gap-2 text-xs font-black">

            <span class="rounded-xl bg-white/10 px-3 py-2">
                Score
            </span>

            <span class="text-violet-400">
                →
            </span>

            <span class="rounded-xl bg-white/10 px-3 py-2">
                Pairing
            </span>

            <span class="text-violet-400">
                →
            </span>

            <span class="rounded-xl bg-white/10 px-3 py-2">
                Result
            </span>

            <span class="text-violet-400">
                →
            </span>

            <span class="rounded-xl bg-white/10 px-3 py-2">
                Advancement Rule
            </span>

            <span class="text-violet-400">
                →
            </span>

            <span class="rounded-xl bg-violet-500/20 px-3 py-2 text-violet-300">
                PhaseExit
            </span>

            <span class="text-violet-400">
                →
            </span>

            <span class="rounded-xl border border-dashed border-white/20 px-3 py-2 text-slate-400">
                Tournament Graph después
            </span>

        </div>

    </section>

</x-tournament-layout>

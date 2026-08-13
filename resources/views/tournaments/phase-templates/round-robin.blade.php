<x-tournament-layout>

    <x-slot name="header">
        Round Robin · {{ $phaseTemplate->name }}
    </x-slot>


    {{-- ========================================================= --}}
    {{-- VOLVER --}}
    {{-- ========================================================= --}}

    <div class="mb-5">

        <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}"
            class="inline-flex items-center gap-2 text-sm font-black text-slate-400 transition hover:text-cyan-600">
            ← Volver a la Fase
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- HERO --}}
    {{-- ========================================================= --}}

    <section
        class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-cyan-950 to-emerald-950 p-7 text-white shadow-xl sm:p-8">

        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl"></div>

        <div class="relative flex flex-col justify-between gap-7 lg:flex-row lg:items-end">

            <div class="max-w-3xl">

                <div
                    class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-cyan-300">
                    ↻ Round Robin Engine
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
                    {{ $phaseTemplate->name }}
                </h1>

                <p class="mt-3 font-mono text-xs text-white/50">
                    {{ $phaseTemplate->code }}
                </p>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                    Configura el calendario todos-contra-todos,
                    los ciclos, puntuación, empates y la cadena de
                    criterios que construirá la futura clasificación.
                </p>

            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">

                <p class="text-[9px] font-black uppercase tracking-wider text-cyan-300">
                    Modalidad actual
                </p>

                <p class="mt-2 text-lg font-black">
                    {{ $settings->cycles_label }}
                </p>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- QUICK STATS --}}
    {{-- ========================================================= --}}

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">

        @foreach ([['Ciclos', $settings->cycles, '↻'], ['Orden', $settings->initial_order_mode_label, '①'], ['Empates', $settings->allow_draws ? 'Permitidos' : 'No', '='], ['Default', 'BO' . $settings->default_best_of, '×'], ['Puntuación', $settings->win_points . ' / ' . $settings->draw_points . ' / ' . $settings->loss_points, 'PTS']] as [$label, $value, $icon])
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
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-xs font-black text-cyan-700">
                        {{ $icon }}
                    </div>

                </div>

            </article>
        @endforeach

    </section>


    {{-- ========================================================= --}}
    {{-- SETTINGS + PREVIEW --}}
    {{-- ========================================================= --}}

    <section class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_480px]">

        <div>

            <div class="mb-4">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-600">
                    Engine Configuration
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Configuración Round Robin
                </h2>

            </div>

            @include('tournaments.phase-templates.partials.round-robin-settings-form')

        </div>

        <aside>

            <div class="xl:sticky xl:top-28">

                @include('tournaments.phase-templates.partials.round-robin-preview')

            </div>

        </aside>

    </section>


    {{-- ========================================================= --}}
    {{-- SCORING EXPLANATION --}}
    {{-- ========================================================= --}}

    <section
        class="mt-10 rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-600">
            Scoring Engine
        </p>

        <h2 class="mt-2 text-2xl font-black text-slate-900">
            Cómo se construirá la clasificación
        </h2>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">

            <article class="rounded-2xl bg-white p-5 shadow-sm">

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700">
                    W
                </div>

                <p class="mt-4 text-sm font-black text-slate-900">
                    Victoria
                </p>

                <p class="mt-1 text-2xl font-black text-emerald-600">
                    {{ $settings->win_points }}
                </p>

                <p class="text-xs text-slate-400">
                    puntos
                </p>

            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm">

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 font-black text-amber-700">
                    D
                </div>

                <p class="mt-4 text-sm font-black text-slate-900">
                    Empate
                </p>

                <p class="mt-1 text-2xl font-black text-amber-600">
                    {{ $settings->draw_points }}
                </p>

                <p class="text-xs text-slate-400">
                    puntos
                </p>

            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 font-black text-red-700">
                    L
                </div>

                <p class="mt-4 text-sm font-black text-slate-900">
                    Derrota
                </p>

                <p class="mt-1 text-2xl font-black text-red-600">
                    {{ $settings->loss_points }}
                </p>

                <p class="text-xs text-slate-400">
                    puntos
                </p>

            </article>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- STANDINGS DEFINITION --}}
    {{-- ========================================================= --}}

    <section class="mt-10">

        <div>

            <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-600">
                Standings Definition
            </p>

            <h2 class="mt-2 text-2xl font-black text-slate-900">
                Estructura de clasificación
            </h2>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Todavía no existen resultados reales, pero el motor ya define
                qué estadísticas podrá utilizar cuando Competition Runtime
                comience a producir resultados.
            </p>

        </div>

        <div class="mt-5 overflow-x-auto rounded-3xl border border-slate-200 bg-white">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr>

                        @foreach ($standingsColumns as $label)
                            <th
                                class="whitespace-nowrap px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-slate-400">
                                {{ $label }}
                            </th>
                        @endforeach

                    </tr>

                </thead>

                <tbody>

                    @foreach (range(1, 4) as $position)
                        <tr class="border-t border-slate-100">

                            <td class="px-4 py-3 text-xs font-black text-slate-400">
                                {{ $position }}
                            </td>

                            <td class="px-4 py-3">

                                <div class="flex items-center gap-2">

                                    <div class="h-8 w-8 rounded-lg bg-slate-100"></div>

                                    <span class="text-xs font-black text-slate-400">
                                        Participante {{ $position }}
                                    </span>

                                </div>

                            </td>

                            @foreach (range(1, count($standingsColumns) - 2) as $column)
                                <td class="px-4 py-3 text-xs font-bold text-slate-300">
                                    —
                                </td>
                            @endforeach

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- TIEBREAKERS --}}
    {{-- ========================================================= --}}

    <section class="mt-10">

        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">

            <div>

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Ranking Chain
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Criterios de desempate
                </h2>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Puntos siempre es el criterio principal.
                    Los siguientes criterios se evalúan en orden
                    cuando dos o más participantes tienen los mismos puntos.
                </p>

            </div>

            <div class="rounded-xl bg-violet-50 px-4 py-2.5 text-xs font-black text-violet-700">
                {{ $tiebreakers->count() }}
                criterios adicionales
            </div>

        </div>


        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

            <div class="space-y-3">

                {{-- POINTS FIJO --}}

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-sm font-black text-white">
                            1
                        </div>

                        <div>

                            <div class="flex flex-wrap items-center gap-2">

                                <p class="font-black text-emerald-950">
                                    Puntos
                                </p>

                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-[9px] font-black uppercase text-emerald-700">
                                    Criterio principal
                                </span>

                            </div>

                            <p class="mt-1 text-xs leading-5 text-emerald-700">
                                {{ $primaryCriterion['description'] }}
                            </p>

                        </div>

                    </div>

                </article>


                @forelse ($tiebreakers as $tiebreaker)
                    <article x-data="{ editing: false }" class="rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">

                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-sm font-black text-violet-700">
                                {{ $loop->iteration + 1 }}
                            </div>

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <p class="font-black text-slate-900">
                                        {{ $tiebreaker->criterion_label }}
                                    </p>

                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-black uppercase text-slate-500">
                                        {{ $tiebreaker->direction_label }}
                                    </span>

                                </div>

                                <p class="mt-2 text-xs leading-5 text-slate-500">
                                    {{ $tiebreaker->criterion_description }}
                                </p>

                            </div>


                            <div class="flex shrink-0 gap-1">

                                @if (!$loop->first)
                                    <form method="POST"
                                        action="{{ route('tournaments.round-robin.tiebreakers.move-up', [$phaseTemplate, $tiebreaker]) }}">

                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                            class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-black text-slate-500">
                                            ↑
                                        </button>

                                    </form>
                                @endif


                                @if (!$loop->last)
                                    <form method="POST"
                                        action="{{ route('tournaments.round-robin.tiebreakers.move-down', [$phaseTemplate, $tiebreaker]) }}">

                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                            class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-black text-slate-500">
                                            ↓
                                        </button>

                                    </form>
                                @endif


                                <button type="button" @click="editing = !editing"
                                    class="rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-black text-slate-500">
                                    ✎
                                </button>


                                <form method="POST"
                                    action="{{ route('tournaments.round-robin.tiebreakers.destroy', [$phaseTemplate, $tiebreaker]) }}"
                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                    data-confirm-title="Eliminar criterio"
                                    data-confirm-message="Este criterio dejará de formar parte de la cadena de desempate."
                                    data-confirm-subject="{{ $tiebreaker->criterion_label }}"
                                    data-confirm-action="Eliminar criterio">

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

                            @include('tournaments.phase-templates.partials.round-robin-tiebreaker-form', [
                                'tiebreaker' => $tiebreaker,
                            ])

                        </div>

                    </article>

                @empty

                    <div class="rounded-2xl border border-dashed border-violet-300 bg-white p-7 text-center">

                        <p class="font-black text-slate-800">
                            No hay criterios adicionales
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            Si dos participantes terminan con los mismos puntos,
                            todavía no existe una cadena automática para separarlos.
                        </p>

                    </div>
                @endforelse

            </div>


            {{-- ADD --}}

            <aside class="h-fit rounded-3xl border border-violet-200 bg-violet-50/60 p-5 xl:sticky xl:top-28">

                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                    Nuevo criterio
                </p>

                <h3 class="mt-2 font-black text-slate-900">
                    Agregar desempate
                </h3>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    El orden importa. Puedes mover cada criterio hacia arriba o abajo.
                </p>

                @if (count($availableCriteria) > 0)
                    <div class="mt-5">

                        @include('tournaments.phase-templates.partials.round-robin-tiebreaker-form', [
                            'tiebreaker' => null,
                        ])

                    </div>
                @else
                    <div class="mt-5 rounded-xl bg-white p-4 text-xs text-slate-500">
                        Ya estás utilizando todos los criterios disponibles.
                    </div>
                @endif

            </aside>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- OUTPUTS --}}
    {{-- ========================================================= --}}

    <section
        class="mt-10 rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-cyan-50 p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
            Output Contract
        </p>

        <h2 class="mt-2 text-2xl font-black text-slate-900">
            Las puertas siguen perteneciendo a la Fase
        </h2>

        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">
            Round Robin utilizará especialmente selectores como
            TOP_N, BOTTOM_N, RANK_POSITION, RANK_RANGE y REMAINING.
            El destino de esas salidas se decidirá posteriormente
            dentro del Tournament Graph.
        </p>

        <div class="mt-5 flex flex-wrap gap-2">

            <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-700 shadow-sm">
                TOP_N
            </span>

            <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-orange-700 shadow-sm">
                BOTTOM_N
            </span>

            <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-violet-700 shadow-sm">
                RANK_RANGE
            </span>

            <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 shadow-sm">
                REMAINING
            </span>

        </div>

        <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}#exits"
            class="mt-5 inline-flex rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white">
            Configurar puertas de salida →
        </a>

    </section>

</x-tournament-layout>

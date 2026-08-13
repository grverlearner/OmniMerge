<x-tournament-layout>

    <x-slot name="header">
        Single Elimination · {{ $phaseTemplate->name }}
    </x-slot>

    {{-- VOLVER --}}

    <div class="mb-5">

        <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}"
            class="inline-flex items-center gap-2 text-sm font-black text-slate-400 transition hover:text-amber-600">
            ← Volver a la Fase
        </a>

    </div>

    {{-- HERO --}}

    <section
        class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-amber-950 to-orange-950 p-7 text-white shadow-xl sm:p-8">

        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-amber-400/15 blur-3xl">
        </div>

        <div class="relative flex flex-col justify-between gap-7 lg:flex-row lg:items-end">

            <div class="max-w-3xl">

                <div
                    class="inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-amber-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
                    ⚔ Single Elimination Engine
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
                    {{ $phaseTemplate->name }}
                </h1>

                <p class="mt-3 font-mono text-xs text-white/50">
                    {{ $phaseTemplate->code }}
                </p>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                    Configura cómo se construye el bracket,
                    cuándo termina la Fase, cómo se ordenan
                    los participantes, cómo se asignan los BYEs
                    y qué Best of utiliza cada ronda.
                </p>

            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">

                <p class="text-[9px] font-black uppercase tracking-wider text-amber-300">
                    Objetivo actual
                </p>

                <p class="mt-2 text-lg font-black">
                    @if ($settings->completion_mode === 'WINNER')
                        1 ganador
                    @else
                        {{ $settings->target_survivors }}
                        supervivientes
                    @endif
                </p>

            </div>

        </div>

    </section>

    {{-- INFO QUICK --}}

    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">

        @foreach ([['Seeding', $settings->seeding_mode_label, '①'], ['Pairing', $settings->pairing_mode_label, '⇄'], ['Default', 'BO' . $settings->default_best_of, '×'], ['BYE', $phaseTemplate->allow_byes ? $settings->bye_assignment_label : 'Desactivado', '◇']] as [$label, $value, $icon])
            <article class="rounded-2xl border border-slate-200 bg-white p-4">

                <div class="flex items-center justify-between gap-3">

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ $label }}
                        </p>

                        <p class="mt-2 text-sm font-black text-slate-800">
                            {{ $value }}
                        </p>
                    </div>

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 font-black text-amber-700">
                        {{ $icon }}
                    </div>

                </div>

            </article>
        @endforeach

    </section>

    {{-- CONFIG + PREVIEW --}}

    <section class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_440px]">

        <div>

            <div class="mb-4">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
                    Engine Configuration
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Configuración del bracket
                </h2>

            </div>

            @include('tournaments.phase-templates.partials.single-elimination-settings-form')

        </div>

        <aside>

            <div class="xl:sticky xl:top-28">

                @include('tournaments.phase-templates.partials.single-elimination-preview')

            </div>

        </aside>

    </section>

    {{-- BEST OF EXPLANATION --}}

    <section class="mt-10 rounded-3xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600">
            Series
        </p>

        <h2 class="mt-2 text-2xl font-black text-slate-900">
            Cómo funciona Best of
        </h2>

        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">
            Best of define cuántas partidas o combates puede contener
            una serie entre dos participantes. La serie termina en
            cuanto uno consigue la mayoría necesaria.
        </p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">

            @foreach ([[1, 1], [3, 2], [5, 3], [7, 4], [9, 5]] as [$bestOf, $wins])
                <div class="rounded-2xl border border-indigo-100 bg-white p-4 text-center">

                    <p class="text-2xl font-black text-indigo-700">
                        BO{{ $bestOf }}
                    </p>

                    <p class="mt-2 text-xs font-bold text-slate-500">
                        {{ $wins }}
                        {{ $wins === 1 ? 'victoria' : 'victorias' }}
                    </p>

                </div>
            @endforeach

        </div>

    </section>

    {{-- ROUND OVERRIDES --}}

    <section class="mt-10">

        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">

            <div>

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Round Overrides
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Best of por ronda
                </h2>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Todas las rondas utilizan BO{{ $settings->default_best_of }}
                    por defecto. Aquí puedes sobrescribir únicamente
                    las rondas que necesiten una regla diferente.
                </p>

            </div>

        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_400px]">

            {{-- EXISTING --}}

            <div class="space-y-3">

                @forelse ($roundRules as $roundRule)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">

                        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

                            <div>

                                <div class="flex items-center gap-2">

                                    <p class="font-black text-slate-900">
                                        {{ $roundRule->round_label }}
                                    </p>

                                    <span
                                        class="rounded-full bg-violet-100 px-2 py-1 text-[9px] font-black text-violet-700">
                                        Override
                                    </span>

                                </div>

                                <p class="mt-1 text-xs text-slate-400">
                                    BO{{ $roundRule->best_of }}
                                    ·
                                    necesita
                                    {{ $roundRule->wins_required }}
                                    {{ $roundRule->wins_required === 1 ? 'victoria' : 'victorias' }}
                                </p>

                            </div>

                            <form method="POST"
                                action="{{ route('tournaments.single-elimination.round-rules.destroy', [$phaseTemplate, $roundRule]) }}"
                                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                data-confirm-title="Eliminar override"
                                data-confirm-message="Esta ronda volverá a utilizar el Best of por defecto."
                                data-confirm-subject="{{ $roundRule->round_label }}"
                                data-confirm-action="Eliminar override">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600">
                                    Eliminar
                                </button>

                            </form>

                        </div>

                        <div class="mt-4 border-t border-slate-100 pt-4">

                            @include(
                                'tournaments.phase-templates.partials.single-elimination-round-rule-form',
                                [
                                    'roundRule' => $roundRule,
                                ]
                            )

                        </div>

                    </article>

                @empty

                    <div class="rounded-3xl border border-dashed border-violet-300 bg-white p-8 text-center">

                        <p class="text-lg font-black text-slate-800">
                            No existen overrides
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            Todas las rondas utilizan actualmente
                            BO{{ $settings->default_best_of }}.
                        </p>

                    </div>
                @endforelse

            </div>

            {{-- CREATE --}}

            <aside class="h-fit rounded-3xl border border-violet-200 bg-violet-50/60 p-5">

                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                    Nueva regla
                </p>

                <h3 class="mt-2 font-black text-slate-900">
                    Sobrescribir una ronda
                </h3>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Ejemplo: Semifinal BO3 y Final BO5
                    mientras las rondas anteriores continúan en BO1.
                </p>

                @if (count($availableRoundSizes) > 0)
                    <div class="mt-5">

                        @include(
                            'tournaments.phase-templates.partials.single-elimination-round-rule-form',
                            [
                                'roundRule' => null,
                            ]
                        )

                    </div>
                @else
                    <div class="mt-5 rounded-xl bg-white p-4 text-xs text-slate-500">
                        Ya configuraste todos los tamaños de ronda disponibles.
                    </div>
                @endif

            </aside>

        </div>

    </section>

    {{-- SEEDING EXPLANATION --}}

    <section class="mt-10 grid gap-4 lg:grid-cols-3">

        <article class="rounded-2xl border border-slate-200 bg-white p-5">

            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 font-black text-amber-700">
                ①
            </div>

            <h3 class="mt-4 font-black text-slate-900">
                Seeding
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Define cómo se obtiene el orden inicial:
                entrada, aleatorio, ranking o manual.
            </p>

        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">

            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 font-black text-indigo-700">
                ⇄
            </div>

            <h3 class="mt-4 font-black text-slate-900">
                Pairing
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Decide cómo se emparejan los seeds.
                El seeding y el pairing son conceptos separados.
            </p>

        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5">

            <div
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 font-black text-emerald-700">
                ◇
            </div>

            <h3 class="mt-4 font-black text-slate-900">
                BYE
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Un BYE permite avanzar sin disputar una serie
                cuando el bracket contiene espacios libres.
            </p>

        </article>

    </section>

</x-tournament-layout>

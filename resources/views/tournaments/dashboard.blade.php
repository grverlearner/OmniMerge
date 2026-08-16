<x-tournament-layout>

    <x-slot name="header">
        Dashboard de Torneos
    </x-slot>

    {{-- HERO --}}

    <section
        class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-amber-950 to-orange-950 p-7 text-white shadow-2xl shadow-amber-950/20 sm:p-9">

        <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-amber-400/15 blur-3xl">
        </div>

        <div class="relative flex flex-col justify-between gap-8 lg:flex-row lg:items-end">

            <div class="max-w-3xl">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-amber-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
                    🏆 Diseñador de torneos y fases
                </div>

                <h2 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
                    Construye el lenguaje
                    de tus competiciones.
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                    Las Fases definen qué ocurre dentro de cada etapa.
                    Los torneos las conectan
                    para formar recorridos, bifurcaciones, repechajes
                    y caminos competitivos completos.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">

                <a href="{{ route('tournaments.templates.index') }}"
                    class="rounded-xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-black text-white transition hover:bg-white/15">
                    🏆 Torneos
                </a>

                <a href="{{ route('tournaments.phase-templates.index') }}"
                    class="rounded-xl bg-amber-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-amber-500/20 transition hover:bg-amber-300">
                    ⌘ Fases
                </a>
            </div>

        </div>
    </section>

    {{-- STATS --}}

    <section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">

        @foreach ([['Torneos', $statistics['tournaments'], '🏆'], ['Torneos activos', $statistics['active_tournaments'], '●'], ['Fases', $statistics['phases'], '⌘'], ['Fases activas', $statistics['active_phases'], '◆'], ['Fases públicas', $statistics['public_phases'], '◎'], ['Puertas', $statistics['phase_exits'], '→']] as [$label, $value, $icon])
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ $label }}
                        </p>

                        <p class="mt-2 text-3xl font-black text-slate-900">
                            {{ number_format($value) }}
                        </p>
                    </div>

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        {{ $icon }}
                    </div>
                </div>
            </article>
        @endforeach

    </section>

    {{-- DOMINIOS --}}

    <section class="mt-8 grid gap-5 lg:grid-cols-2">

        {{-- TORNEOS --}}

        <article class="rounded-3xl border border-slate-200 bg-white p-6">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
                        Orquestación
                    </p>

                    <h3 class="mt-2 text-2xl font-black text-slate-900">
                        🏆 Torneos
                    </h3>
                </div>

                <a href="{{ route('tournaments.templates.index') }}" class="text-xs font-black text-amber-600">
                    Abrir →
                </a>
            </div>

            <p class="mt-3 text-sm leading-6 text-slate-500">
                Los Torneos definirán el camino competitivo.
                El grafo de Nodes y conexiones se construirá
                después de estabilizar la Biblioteca de Fases.
            </p>

            <div class="mt-6 space-y-2">

                @forelse ($recentTemplates as $template)
                    <a href="{{ route('tournaments.templates.show', $template) }}"
                        class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4 transition hover:bg-amber-50">

                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-800">
                                {{ $template->name }}
                            </p>

                            <p class="mt-1 font-mono text-[9px] text-slate-400">
                                {{ $template->code }}
                            </p>
                        </div>

                        <span class="text-amber-600">→</span>
                    </a>

                @empty

                    <div
                        class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-sm text-slate-400">
                        Todavía no tienes Torneos.
                    </div>
                @endforelse

            </div>
        </article>

        {{-- FASES --}}

        <article class="rounded-3xl border border-amber-200 bg-gradient-to-br from-white to-amber-50/60 p-6">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
                        Phase Library
                    </p>

                    <h3 class="mt-2 text-2xl font-black text-slate-900">
                        ⌘ Fases
                    </h3>
                </div>

                <a href="{{ route('tournaments.phase-templates.index') }}" class="text-xs font-black text-amber-600">
                    Abrir →
                </a>
            </div>

            <p class="mt-3 text-sm leading-6 text-slate-500">
                Cada Fase es un mecanismo competitivo reutilizable
                con contrato de entrada y puertas de salida.
            </p>

            <div class="mt-6 space-y-2">

                @forelse ($recentPhases as $phase)
                    <a href="{{ route('tournaments.phase-templates.show', $phase) }}"
                        class="flex items-center justify-between gap-4 rounded-2xl bg-white p-4 shadow-sm transition hover:shadow-md">

                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-800">
                                {{ $phase->name }}
                            </p>

                            <p class="mt-1 text-[10px] font-semibold text-slate-400">
                                {{ $phase->type_label }}
                                ·
                                {{ $phase->exits_count }} salidas
                            </p>
                        </div>

                        <span class="text-amber-600">→</span>
                    </a>

                @empty

                    <div class="rounded-2xl border border-dashed border-amber-300 bg-white/70 p-5 text-center">

                        <p class="text-sm font-black text-slate-700">
                            Crea tu primera Fase reutilizable
                        </p>

                        <a href="{{ route('tournaments.phase-templates.create') }}"
                            class="mt-3 inline-flex rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-white">
                            + Nueva Fase
                        </a>
                    </div>
                @endforelse

            </div>
        </article>

    </section>

    {{-- ROADMAP --}}

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
            Arquitectura
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Camino del sistema competitivo
        </h3>

        <div class="mt-6 grid gap-3 md:grid-cols-5">

            @foreach ([['⌘', 'PhaseTemplate', 'Define la etapa.', true], ['→', 'PhaseExit', 'Define quién sale.', true], ['◆', 'Phase Node', 'Usa la Fase.', false], ['⇢', 'Connection', 'Define el destino.', false], ['⚗', 'Competition Lab', 'Ejecuta pruebas.', false]] as [$icon, $title, $text, $done])
                <article
                    class="{{ $done ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }}
                    rounded-2xl border p-4">

                    <span class="text-xl">{{ $icon }}</span>

                    <p class="mt-3 text-sm font-black text-slate-800">
                        {{ $title }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ $text }}
                    </p>

                    <span
                        class="{{ $done ? 'text-emerald-600' : 'text-slate-400' }}
                        mt-3 inline-flex text-[9px] font-black uppercase">

                        {{ $done ? 'Disponible' : 'Planificado' }}
                    </span>
                </article>
            @endforeach
        </div>

    </section>

</x-tournament-layout>

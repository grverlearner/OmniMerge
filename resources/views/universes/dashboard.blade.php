<x-universe-layout>

    <x-slot name="header">
        Dashboard de Universos
    </x-slot>

    {{-- HERO --}}

    <section
        class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950 p-7 text-white shadow-2xl shadow-indigo-950/20 sm:p-9">

        <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-violet-400/15 blur-3xl">
        </div>

        <div class="relative flex flex-col justify-between gap-8 lg:flex-row lg:items-end">

            <div class="max-w-3xl">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">
                    🌌 Contenedor de tus torneos
                </div>

                <h2 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
                    Organiza tus torneos
                    en Universos.
                </h2>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                    Un Universo agrupa varias plantillas de torneo bajo un
                    mismo nombre. Crea uno para empezar a organizar tus
                    competiciones.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">

                <a href="{{ route('universes.index') }}"
                    class="rounded-xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-black text-white transition hover:bg-white/15">
                    🌌 Todos mis Universos
                </a>

                <a href="{{ route('universes.create') }}"
                    class="rounded-xl bg-violet-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-violet-500/20 transition hover:bg-violet-300">
                    + Nuevo Universo
                </a>
            </div>

        </div>
    </section>

    {{-- STATS --}}

    <section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-5">

        @foreach ([['Universos', $statistics['total'], '🌌'], ['Activos', $statistics['active'], '●'], ['Borradores', $statistics['draft'], '◆'], ['Competidores', $statistics['competitors'], '✦'], ['Torneos', $statistics['tournaments'], '🏆']] as [$label, $value, $icon])
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
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                        {{ $icon }}
                    </div>
                </div>
            </article>
        @endforeach

    </section>

    {{-- RECIENTES --}}

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600">
                    Actividad
                </p>

                <h3 class="mt-2 text-2xl font-black text-slate-900">
                    🌌 Universos recientes
                </h3>
            </div>

            <a href="{{ route('universes.index') }}" class="text-xs font-black text-indigo-600">
                Ver todos →
            </a>
        </div>

        <p class="mt-3 text-sm leading-6 text-slate-500">
            Cada Universo reúne competidores, temporadas y torneos.
            Entra a uno para trabajar dentro de él.
        </p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">

            @forelse ($recentUniverses as $recentUniverse)
                <a href="{{ route('universes.show', $recentUniverse) }}"
                    class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4 transition hover:bg-indigo-50">

                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-slate-800">
                            {{ $recentUniverse->name }}
                        </p>

                        <p class="mt-1 font-mono text-[9px] text-slate-400">
                            {{ $recentUniverse->code }}
                            ·
                            {{ $recentUniverse->competitors_count }} competidores
                            ·
                            {{ $recentUniverse->universe_tournaments_count }} torneos
                        </p>
                    </div>

                    <span class="text-indigo-600">→</span>
                </a>

            @empty

                <div
                    class="col-span-full rounded-2xl border border-dashed border-slate-200 p-8 text-center">

                    <div class="text-3xl">
                        🌌
                    </div>

                    <p class="mt-3 text-sm font-black text-slate-700">
                        Todavía no tienes Universos.
                    </p>

                    <a href="{{ route('universes.create') }}"
                        class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white">
                        + Crear tu primer Universo
                    </a>
                </div>
            @endforelse

        </div>
    </section>

    {{-- ROADMAP --}}

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600">
            Arquitectura
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Qué contiene un Universo
        </h3>

        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">
            El Universo no copia nada: da contexto. Las entidades siguen en tu
            Biblioteca y las plantillas siguen en tu Biblioteca de Torneos.
        </p>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">

            @foreach ([['✦', 'Competidores', 'Entidades de tu Biblioteca con contexto dentro de este Universo.', true], ['◷', 'Temporadas', 'El tiempo propio del Universo.', true], ['🏆', 'Torneos', 'Plantillas adoptadas, con nombre y contexto propios.', true], ['📊', 'Resultados y rankings', 'Cuando las competiciones puedan jugarse de verdad.', false]] as [$icon, $title, $text, $done])
                <article
                    class="{{ $done ? 'border-indigo-200 bg-indigo-50/60' : 'border-slate-200 bg-slate-50' }}
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

</x-universe-layout>

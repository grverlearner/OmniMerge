@php
    /*
     * Crear una plantilla de torneo.
     *
     * Aquí solo nace: nombre, cara, tipo y cuánta gente admite. El recorrido
     * —entradas, fases, enlaces, finales— se monta después en la Super
     * Edición, porque hasta que la plantilla no existe no hay grafo que
     * construir.
     */
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">Nueva plantilla</x-slot>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 px-5 py-4">

        <div class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('tournaments.templates.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-amber-400">
                    ← Mis plantillas
                </a>

                <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                    Nueva plantilla
                </h1>

                <p class="mt-1 max-w-2xl text-[11px] leading-4 text-slate-500">
                    Una plantilla describe un recorrido reutilizable: por dónde entra la gente, qué
                    fases atraviesa y en qué finales acaba. Define cómo funciona el torneo, no quién
                    lo juega.
                </p>
            </div>

            <span
                class="shrink-0 rounded-lg border border-slate-800 bg-slate-950 px-3 py-1.5 font-mono text-[11px] font-black text-amber-300">
                {{ $previewCode }}
            </span>

        </div>

    </section>

    <div class="mt-4">

        <form method="POST" action="{{ route('tournaments.templates.store') }}" enctype="multipart/form-data">

            @csrf

            @include('tournaments.partials.template-form')

        </form>

    </div>

</x-tournament-layout>

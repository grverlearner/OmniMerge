@php
    /*
     * Crear una fase.
     *
     * Aquí solo nace la pieza: nombre, cara, motor y cuánta gente admite.
     * Lo que decide cómo se juega —emparejamientos, calendario, salidas—
     * se configura después, en la Super Edición, porque hasta que la fase
     * no existe no hay nada que configurar.
     *
     * Va dentro del layout del módulo, con su sidebar: es una pantalla de
     * trabajo, no una arena. La pantalla completa sin navegación se reserva
     * para lo que se juega.
     */
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">Nueva fase</x-slot>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 px-5 py-4">

        <div class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('tournaments.phase-templates.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-amber-400">
                    ← Biblioteca de fases
                </a>

                <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                    Nueva fase
                </h1>

                <p class="mt-1 max-w-2xl text-[11px] leading-4 text-slate-500">
                    Una fase es una pieza reutilizable: define por dónde entra la gente, qué se
                    hace con ella y por dónde sale. Los torneos se montan encajándolas.
                </p>
            </div>

            <span
                class="shrink-0 rounded-lg border border-slate-800 bg-slate-950 px-3 py-1.5 font-mono text-[11px] font-black text-amber-300">
                {{ $previewCode }}
            </span>

        </div>

    </section>

    <div class="mt-4">

        <form method="POST" action="{{ route('tournaments.phase-templates.store') }}" enctype="multipart/form-data">

            @csrf

            @include('tournaments.phase-templates.partials.form')

        </form>

    </div>

</x-tournament-layout>

@php
    /*
     * La definición de una plantilla ya creada.
     *
     * Misma pantalla que al crearla. El recorrido se sigue montando en la
     * Super Edición, a la que se llega desde el panel de la derecha.
     */
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">{{ $tournamentTemplate->name }}</x-slot>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 px-5 py-4">

        <div class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('tournaments.templates.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-amber-400">
                    ← Mis plantillas
                </a>

                <h1 class="mt-1.5 truncate text-2xl font-black tracking-tight text-white">
                    {{ $tournamentTemplate->name }}
                </h1>

                <p class="mt-1 text-[11px] text-slate-500">
                    Definición · lo que la plantilla es y cómo se reconoce.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span
                    class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-1.5 font-mono text-[11px] font-black text-amber-300">
                    {{ $tournamentTemplate->code }}
                </span>

                <a href="{{ route('tournaments.templates.show', $tournamentTemplate) }}"
                    class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                    Ver ficha
                </a>

                <a href="{{ route('tournaments.super.show', $tournamentTemplate) }}"
                    class="rounded-lg border border-violet-500/40 bg-violet-500/10 px-3 py-1.5 text-[11px] font-black text-violet-300 transition hover:bg-violet-500/20">
                    ⚙ Super Edición
                </a>
            </div>

        </div>

    </section>

    @if (session('success'))
        <div class="mt-4 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-xs font-black text-emerald-300"
            role="status">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="mt-4">

        <form method="POST" action="{{ route('tournaments.templates.update', $tournamentTemplate) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            @include('tournaments.partials.template-form')

        </form>

    </div>

</x-tournament-layout>

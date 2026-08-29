@php
    /*
     * Crear una edición de un torneo oficial.
     *
     * La pantalla entera vive en el diseñador: aquí solo está el formulario
     * que lo envuelve. Crear y editar comparten ese diseñador para que no
     * puedan divergir — dos formularios para la misma entidad acaban
     * ofreciendo cosas distintas sin que nadie lo decida.
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Nueva edición</x-slot>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('universes.tournaments.show', [$universe, $universeTournament]) }}"
            class="rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">←</a>

        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.18em] text-amber-300">
                {{ $universe->name }} · {{ $universeTournament->name }}
            </p>
            <h1 class="text-lg font-black text-slate-100">Una edición nueva</h1>
        </div>

        <p class="ml-auto max-w-sm text-[10px] leading-relaxed text-slate-500">
            El torneo es la <span class="font-bold text-slate-300">marca</span>; esto es lo
            que se juega este año. Cambia el juego, cómo se pelea y quién entra —las reglas
            del torneo siguen siendo suyas—.
        </p>
    </div>

    @if (session('error'))
        <p class="mb-3 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-[11px] text-rose-300">
            {{ session('error') }}
        </p>
    @endif

    <form method="POST" enctype="multipart/form-data"
        action="{{ route('universes.competitions.store', $universe) }}">

        @csrf

        <input type="hidden" name="universe_tournament_id" value="{{ $universeTournament->id }}">

        @include('universes.competitions.partials.designer')

    </form>

</x-universe-layout>

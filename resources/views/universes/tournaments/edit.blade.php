@php
    /*
     * Editar un torneo oficial.
     *
     * Mismo diseñador que el alta. Lo único que cambia es a dónde va el
     * formulario y que ya hay algo que enseñar.
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">{{ $universeTournament->name }}</x-slot>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('universes.tournaments.show', [$universe, $universeTournament]) }}"
            class="rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">←</a>

        <div class="min-w-0">
            <p class="text-[9px] font-black uppercase tracking-[0.18em] text-amber-300">
                {{ $universe->name }} · torneo oficial
            </p>
            <h1 class="truncate text-lg font-black text-slate-100">{{ $universeTournament->name }}</h1>
        </div>

        <p class="ml-auto max-w-sm text-[10px] leading-relaxed text-slate-500">
            Lo que cambies aquí lo heredarán las ediciones
            <span class="font-bold text-slate-300">futuras</span>. Las ya jugadas quedaron
            congeladas con su configuración.
        </p>
    </div>

    <form method="POST" enctype="multipart/form-data"
        action="{{ route('universes.tournaments.update', [$universe, $universeTournament]) }}">

        @csrf
        @method('PUT')

        @include('universes.tournaments.partials.designer')

    </form>

</x-universe-layout>

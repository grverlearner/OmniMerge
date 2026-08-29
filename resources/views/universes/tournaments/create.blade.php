@php
    /*
     * Crear un torneo oficial del universo.
     *
     * La pantalla entera vive en el diseñador: aquí solo está el formulario
     * que lo envuelve. Crear y editar comparten ese diseñador para que no
     * puedan divergir — dos formularios para la misma entidad acaban
     * ofreciendo cosas distintas sin que nadie lo decida.
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Nuevo torneo</x-slot>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('universes.tournaments.index', $universe) }}"
            class="rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">←</a>

        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.18em] text-amber-300">
                {{ $universe->name }} · torneo oficial
            </p>
            <h1 class="text-lg font-black text-slate-100">Un torneo nuevo</h1>
        </div>

        <p class="ml-auto max-w-sm text-[10px] leading-relaxed text-slate-500">
            Estás creando una <span class="font-bold text-slate-300">marca</span>, no una
            edición: «la Copa del Fuego», no «la Copa del Fuego 2024». Las ediciones vienen
            después.
        </p>
    </div>

    <form method="POST" enctype="multipart/form-data"
        action="{{ route('universes.tournaments.store', $universe) }}">

        @csrf

        @include('universes.tournaments.partials.designer', ['universeTournament' => null])

    </form>

</x-universe-layout>

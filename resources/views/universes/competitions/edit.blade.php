@php
    /*
     * Retocar una edición que todavía no ha empezado.
     *
     * Mismo diseñador que al crear: la forma y los competidores salen
     * bloqueados desde dentro —eso congeló el estado inicial— y el resto
     * sigue siendo editable.
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Editar edición</x-slot>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
            class="rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">←</a>

        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.18em] text-amber-300">
                {{ $universe->name }} · {{ $universeTournament->name }} · {{ $competition->code }}
            </p>
            <h1 class="text-lg font-black text-slate-100">{{ $competition->name }}</h1>
        </div>

        <p class="ml-auto max-w-sm text-[10px] leading-relaxed text-slate-500">
            Todavía no ha empezado, así que aún se puede cambiar cómo se pelea y qué se
            lleva quien gane. La forma y los competidores ya están dibujados.
        </p>
    </div>

    @if (session('error'))
        <p class="mb-3 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-[11px] text-rose-300">
            {{ session('error') }}
        </p>
    @endif

    <form method="POST" enctype="multipart/form-data"
        action="{{ route('universes.competitions.update', [$universe, $competition]) }}">

        @csrf
        @method('PUT')

        @include('universes.competitions.partials.designer')

    </form>

</x-universe-layout>

@php
    /*
     * Editar un tipo de entidad.
     *
     * La cabecera lleva el color del tipo y cuántas entidades lo llevan
     * puesto, porque es lo que decide si un cambio aquí es cosmético o afecta
     * a media biblioteca.
     */

    $color = $entityType->color ?: '#6366f1';

    $cuantas = $entityType->entities()->count();
@endphp

<x-app-layout :title="'Editar ' . $entityType->name" surface="dark">

    <x-slot name="header">Editar {{ $entityType->name }}</x-slot>

    <section class="overflow-hidden rounded-2xl border bg-slate-900/50" style="border-color: {{ $color }}44">

        <div class="relative">

            <span class="pointer-events-none absolute inset-0"
                style="background: radial-gradient(70% 120% at 12% 0%, {{ $color }}22, transparent 65%)"></span>

            <div class="relative flex flex-wrap items-center gap-4 px-5 py-4">

                <span class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                    style="border-color: {{ $color }}55">
                    @if ($entityType->image_url)
                        <img src="{{ $entityType->image_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-2xl"
                            style="color: {{ $color }}">{{ $entityType->icon ?: '◇' }}</span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <a href="{{ route('entity-types.show', $entityType) }}"
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                        ← Volver al tipo
                    </a>

                    <h1 class="mt-1 truncate text-2xl font-black tracking-tight text-white">
                        {{ $entityType->name }}
                    </h1>

                    <p class="mt-0.5 flex flex-wrap items-center gap-2 text-[10px]">
                        <span class="font-mono text-indigo-300">{{ $entityType->code }}</span>
                        <span class="text-slate-800">·</span>
                        <span class="text-slate-500">
                            lo llevan <strong class="text-slate-300">{{ $cuantas }}</strong>
                            {{ $cuantas === 1 ? 'entidad' : 'entidades' }}
                        </span>
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a href="{{ route('entity-types.show', $entityType) }}"
                        class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                        Ver sus entidades
                    </a>

                    @can('create', App\Models\EntityType::class)
                        <a href="{{ route('entity-types.create') }}"
                            class="rounded-lg border border-dashed border-slate-700 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-300">
                            + Otro tipo
                        </a>
                    @endcan
                </div>

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

        <form method="POST" action="{{ route('entity-types.update', $entityType) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            @include('entity-types.partials.form')

        </form>

    </div>

</x-app-layout>

@php
    /*
     * Editar la entidad original.
     *
     * «Original» es la palabra importante: si la entidad tiene una Base
     * activa, lo que ve el resto de la aplicación es esa versión y no esto.
     * La cabecera lo dice y ofrece el salto, porque es el error más fácil de
     * cometer aquí —editar durante media hora la cara que nadie mira—.
     */

    $baseActiva = $entity->baseVersionSetting?->entityVersion;

    $tipo = $entity->entityType;

    $colorTipo = $tipo?->color ?: '#6366f1';
@endphp

<x-app-layout :title="'Editar ' . $entity->name" surface="dark">

    <x-slot name="header">Editar {{ $entity->name }}</x-slot>

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex flex-wrap items-center gap-4 px-5 py-4">

            <span class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($entity->image_url)
                    <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-2xl font-black"
                        style="color: {{ $colorTipo }}44">
                        {{ $tipo?->icon ?: mb_strtoupper(mb_substr($entity->name, 0, 1)) }}
                    </span>
                @endif
            </span>

            <div class="min-w-0 flex-1">
                <a href="{{ route('entities.show', $entity) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                    ← Volver a la entidad
                </a>

                <h1 class="mt-1 truncate text-2xl font-black tracking-tight text-white">
                    {{ $entity->name }}
                </h1>

                <p class="mt-0.5 flex flex-wrap items-center gap-2 text-[10px]">
                    <span class="font-mono text-indigo-300">{{ $entity->code }}</span>
                    <span class="text-slate-800">·</span>
                    <span class="font-mono text-slate-600">{{ $entity->slug }}</span>
                    <span class="text-slate-800">·</span>
                    <span class="text-slate-500">estás editando la <strong class="text-slate-300">original</strong></span>
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('entities.attributes.edit', $entity) }}"
                    class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-cyan-500 hover:text-cyan-300">
                    Características
                </a>

                <a href="{{ route('entity-versions.index', $entity) }}"
                    class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                    Versiones
                </a>

                <a href="{{ route('entities.presentation.edit', $entity) }}"
                    class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-sky-500 hover:text-sky-300">
                    Presentación
                </a>
            </div>

        </div>

        {{-- El aviso que evita media hora de trabajo en la cara equivocada --}}
        @if ($baseActiva)
            <div class="flex flex-wrap items-center gap-3 border-t border-violet-500/25 bg-violet-500/5 px-5 py-3">
                <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-violet-500/30 bg-slate-950">
                    @if ($baseActiva->image_url)
                        <img src="{{ $baseActiva->image_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-sm text-violet-300">★</span>
                    @endif
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-4 text-slate-400">
                    Esta entidad tiene una <strong class="text-violet-300">Base activa</strong>:
                    <strong class="text-white">{{ $baseActiva->name }}</strong>. Lo que se ve en
                    torneos, listas y comunidad sale de ahí, no de lo que edites en esta pantalla.
                </p>

                <a href="{{ route('entity-versions.edit', [$entity, $baseActiva]) }}"
                    class="shrink-0 rounded-lg bg-violet-500 px-3 py-1.5 text-[11px] font-black text-white transition hover:bg-violet-400">
                    Editar esa versión →
                </a>
            </div>
        @endif

    </section>

    @if (session('success'))
        <div class="mt-4 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-xs font-black text-emerald-300"
            role="status">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="mt-4">

        <form method="POST" action="{{ route('entities.update', $entity) }}" enctype="multipart/form-data">

            @csrf
            @method('PUT')

            @include('entities.partials.form')

        </form>

    </div>

</x-app-layout>

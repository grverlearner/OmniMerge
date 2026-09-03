@php
    /*
     * Crear una entidad.
     *
     * Una pantalla, no un asistente por pasos: casi todo es opcional y quien
     * ya sabe lo que quiere puede rellenar el nombre y darle a crear.
     */
@endphp

<x-app-layout title="Nueva entidad" surface="dark">

    <x-slot name="header">Nueva entidad</x-slot>

    <section
        class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40 px-5 py-4">

        <span class="pointer-events-none absolute -right-24 -top-28 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl"></span>

        <div class="relative flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('entities.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                    ← Entidades
                </a>

                <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                    Nueva entidad
                </h1>

                <p class="mt-1 max-w-2xl text-[11px] leading-4 text-slate-500">
                    Una entidad es cualquier cosa que quieras hacer competir: un personaje, un país,
                    un equipo. Solo el nombre es obligatorio; lo demás se puede completar después.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span
                    class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-1.5 font-mono text-[11px] font-black text-indigo-300">
                    {{ $previewCode }}
                </span>

                <a href="{{ route('entities.bulk.create') }}"
                    class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300">
                    ¿Muchas a la vez? →
                </a>
            </div>

        </div>

    </section>

    <div class="mt-4">

        <form method="POST" action="{{ route('entities.store') }}" enctype="multipart/form-data">

            @csrf

            @include('entities.partials.form')

        </form>

    </div>

</x-app-layout>

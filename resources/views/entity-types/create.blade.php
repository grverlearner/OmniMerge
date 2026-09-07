@php
    /*
     * Crear un tipo de entidad.
     *
     * Es una pantalla corta a propósito: un tipo es un nombre, un icono y un
     * color. Lo que la hace útil no es lo que pide, sino lo que enseña —dónde
     * va a salir eso que estás eligiendo—.
     */
@endphp

<x-app-layout title="Nuevo tipo de entidad" surface="dark">

    <x-slot name="header">Nuevo tipo</x-slot>

    <section
        class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40 px-5 py-4">

        <span class="pointer-events-none absolute -right-24 -top-28 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl"></span>

        <div class="relative flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('entity-types.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                    ← Tipos de entidad
                </a>

                <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                    Nuevo tipo de entidad
                </h1>

                <p class="mt-1 max-w-2xl text-[11px] leading-4 text-slate-500">
                    «Personaje», «País», «Equipo». Es una etiqueta para organizarte: sirve para
                    filtrar y para reconocer de un vistazo, y no limita qué características puede
                    tener una entidad.
                </p>
            </div>

            <a href="{{ route('entities.index') }}"
                class="shrink-0 rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300">
                Ver mis entidades →
            </a>

        </div>

    </section>

    <div class="mt-4">

        <form method="POST" action="{{ route('entity-types.store') }}" enctype="multipart/form-data">

            @csrf

            @include('entity-types.partials.form')

        </form>

    </div>

</x-app-layout>

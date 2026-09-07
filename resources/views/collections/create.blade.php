@php
    /*
     * Nueva colección.
     *
     * La cáscara es fina a propósito: todo el trabajo vive en el formulario
     * compartido, que es el mismo que usa la edición. Aquí solo van la cabecera
     * y la explicación de qué es una colección, que hace falta la primera vez y
     * estorba a partir de la segunda —por eso está plegada—.
     */
@endphp

<x-app-layout title="Nueva colección" surface="dark">

    <x-slot name="header">Colecciones</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('collections.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Colecciones
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Nueva colección</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Junta entidades por el motivo que quieras. No las duplica ni las mueve: solo las agrupa.
                </p>
            </div>

            <a href="{{ route('collections.index') }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </header>


        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-rose-200">Falta algo antes de poder guardar:</p>
                <ul class="mt-1 space-y-0.5 text-[11px] leading-relaxed text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- QUÉ ES UNA COLECCIÓN --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Qué es una colección
                    <span class="font-bold text-slate-500">— y en qué se diferencia de un tipo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)]">

                    <svg viewBox="0 0 250 116" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- Las entidades, que existen una sola vez --}}
                        <rect x="90" y="46" width="26" height="26" rx="4" />
                        <circle cx="103" cy="59" r="6" opacity=".7" />

                        <rect x="124" y="46" width="26" height="26" rx="4" />
                        <circle cx="137" cy="59" r="6" opacity=".7" />

                        <rect x="158" y="46" width="26" height="26" rx="4" />
                        <circle cx="171" cy="59" r="6" opacity=".7" />

                        <text x="137" y="88" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700" opacity=".7">las mismas entidades</text>

                        {{-- Dos colecciones que las abarcan, solapándose --}}
                        <rect x="80" y="30" width="80" height="54" rx="10" stroke-dasharray="5 4" opacity=".85" />
                        <text x="96" y="24" fill="currentColor" stroke="none" font-size="8" font-weight="700">Colección A</text>

                        <rect x="114" y="36" width="80" height="54" rx="10" stroke-dasharray="5 4" opacity=".5" />
                        <text x="176" y="102" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700" opacity=".6">Colección B</text>

                        {{-- El tipo, que es una sola caja --}}
                        <rect x="6" y="40" width="54" height="36" rx="5" />
                        <path d="M14 52h38M14 62h24" opacity=".45" />
                        <text x="33" y="34" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">Un tipo</text>
                        <text x="33" y="88" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".6">solo uno</text>
                    </svg>

                    <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            Una entidad <strong class="text-white">es</strong> de un tipo —uno solo—, pero
                            <strong class="text-violet-300">está</strong> en las colecciones que tú quieras, a
                            la vez.
                        </p>

                        <p>
                            Por eso una colección no es una carpeta: no saca a la entidad de ningún sitio ni la
                            duplica. «Naruto» puede estar en <em>Franquicia Naruto</em>, en <em>Equipo 7</em> y
                            en <em>Mis favoritas</em> al mismo tiempo, y sigue siendo la misma ficha.
                        </p>

                        <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            Puedes crearla vacía y llenarla después: no hace falta decidirlo todo ahora.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        <form method="POST" action="{{ route('collections.store') }}" enctype="multipart/form-data">
            @csrf

            @include('collections.partials.form')
        </form>

    </div>

</x-app-layout>

@php
    /*
     * Editar un universo.
     *
     * Comparte la seccion de identidad con crear -misma pieza, una sola
     * implementacion- y no ofrece lo que solo tiene sentido al nacer: las
     * temporadas, los puntos, el juego y los habitantes ya tienen su propio
     * panel dentro del universo, y duplicarlos aqui seria una segunda forma de
     * hacer lo mismo.
     *
     * Lo que si vive aqui es lo que solo se puede hacer con un mundo que ya
     * existe: archivarlo y borrarlo.
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Ajustes de {{ $universe->name }}</x-slot>

    <div class="mx-auto max-w-4xl space-y-3">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Ajustes
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Cambiar quién es este mundo
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] leading-relaxed text-slate-500">
                    Su calendario, lo que premia, con qué se juega y quién lo habita tienen su
                    propio panel dentro del universo.
                </p>
            </div>

            <a href="{{ route('universes.show', $universe) }}"
                class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-slate-200">
                <x-omni-icon name="flecha-izquierda" size="h-3.5 w-3.5" />
                Volver al resumen
            </a>
        </header>


        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-[12px] font-bold text-rose-200">
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- IDENTIDAD --}}
        {{-- ===================================================== --}}

        <form method="POST" action="{{ route('universes.update', $universe) }}"
            enctype="multipart/form-data"
            x-data="{
                nombre: @js(old('name', $universe->name)),
                descripcion: @js(old('description', $universe->description ?? '')),
                estado: @js(old('status', $universe->status)),
                portada: @js($universe->image_url),
                quitarPortada: false,

                cargarPortada(evento) {
                    const fichero = evento.target.files[0];
                    if (! fichero) return;
                    this.portada = URL.createObjectURL(fichero);
                    this.quitarPortada = false;
                },

                limpiarPortada() {
                    this.portada = null;
                    this.quitarPortada = true;
                },
            }"
            class="space-y-3">

            @csrf
            @method('PUT')

            @include('universes.partials.universe-form')

            <div class="flex flex-wrap justify-end gap-2">
                <a href="{{ route('universes.show', $universe) }}"
                    class="rounded-xl border border-slate-800 px-4 py-2.5 text-[12px] font-black text-slate-400 transition hover:text-slate-200">
                    Cancelar
                </a>

                <button type="submit"
                    class="rounded-xl bg-violet-500 px-5 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
                    Guardar los cambios
                </button>
            </div>
        </form>


        {{-- ===================================================== --}}
        {{-- ARCHIVAR --}}
        {{-- ===================================================== --}}

        @can('update', $universe)
            @if ($universe->status !== 'ARCHIVED')
                <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/50 px-4 py-3">

                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-slate-400">
                        <x-omni-icon name="capas" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-black text-slate-200">Archivar este mundo</p>
                        <p class="text-[10px] leading-3 text-slate-500">
                            Deja de pedir tu atención y se va al fondo de la estantería.
                            <strong class="text-slate-400">No se borra nada</strong>: sigue
                            entero y puedes sacarlo cuando quieras.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('universes.archive', $universe) }}" class="shrink-0">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                            class="rounded-xl border border-slate-700 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-500 hover:text-slate-200">
                            Archivar
                        </button>
                    </form>
                </section>
            @endif
        @endcan


        {{-- ===================================================== --}}
        {{-- BORRAR --}}
        {{-- ===================================================== --}}

        @can('delete', $universe)
            <section x-data="{ confirmando: false }"
                class="overflow-hidden rounded-2xl border border-rose-500/25 bg-rose-500/5">

                <div class="flex flex-wrap items-center gap-3 px-4 py-3">

                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-300">
                        <x-omni-icon name="cerrar" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-black text-white">Borrar este mundo</p>

                        {{--
                            Se dice exactamente qué se pierde, con sus números.
                            «Esta acción no se puede deshacer» no informa de
                            nada; «se van 22 competidores y 17 competiciones» sí.
                        --}}
                        @php
                            $seVan = [
                                [$universe->entities()->count(), 'competidor', 'competidores'],
                                [$universe->seasons()->count(), 'temporada', 'temporadas'],
                                [$universe->universeTournaments()->count(), 'torneo', 'torneos'],
                                [$universe->tournamentInstances()->count(), 'competición', 'competiciones'],
                            ];
                        @endphp

                        <p class="text-[10px] leading-3 text-rose-200/70">
                            Se van con él
                            @foreach ($seVan as $indice => [$cuantos, $singular, $plural])
                                <strong class="text-rose-200">{{ $cuantos }}</strong>
                                {{ $cuantos === 1 ? $singular : $plural }}@if ($indice === count($seVan) - 2)
                                    y
                                @elseif ($indice < count($seVan) - 1),
                                @endif
                            @endforeach
                            con toda su historia. No se puede deshacer.
                        </p>
                    </div>

                    <button type="button" @click="confirmando = true" x-show="! confirmando"
                        class="shrink-0 rounded-xl border border-rose-500/40 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                        Borrar
                    </button>
                </div>

                <div x-show="confirmando" x-cloak x-collapse
                    class="border-t border-rose-500/20 bg-rose-500/5 px-4 py-3">

                    <p class="text-[11px] font-bold text-rose-100">
                        Esto borra «{{ $universe->name }}» y todo lo que hay dentro. ¿Seguro?
                    </p>

                    <div class="mt-2 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('universes.destroy', $universe) }}">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="rounded-xl bg-rose-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-rose-400">
                                Sí, borrarlo para siempre
                            </button>
                        </form>

                        <button type="button" @click="confirmando = false"
                            class="rounded-xl px-3 py-2 text-[11px] font-black text-slate-400 transition hover:text-slate-200">
                            No, dejarlo
                        </button>
                    </div>
                </div>
            </section>
        @endcan
    </div>

</x-universe-layout>

@php
    /*
     * Mis universos: la estanteria de mundos.
     *
     * Lo que habia era una rejilla de tarjetas con tres contadores. Lo que
     * faltaba es lo que hace que una estanteria sirva:
     *
     *   · comparar lo que hay dentro de cada mundo
     *   · saber cual pide atencion sin entrar en el
     *   · reconocerlos por su gente, no solo por su nombre
     *
     * Cinco formas de mirar, y un filtro por lo que le PASA al universo -si
     * algo se esta jugando, si algo esta atascado, si esta vacio- que es la
     * pregunta que uno se hace de verdad al abrir esta pantalla.
     *
     * Ver docs/md/71-Universos-Mis-Universos.md
     */

    $tonosEstado = [
        'ACTIVE' => ['#34d399', 'En marcha'],
        'DRAFT' => ['#60a5fa', 'Borrador'],
        'ARCHIVED' => ['#64748b', 'Archivado'],
    ];

    $hayFiltros = $search !== '' || $status !== '' || $con !== '';

    /* Para la vista de pulso: la misma escala para todos los mundos */
    $techoPulso = max(
        1,
        collect($universes->items())->max('tournament_instances_count') ?: 1
    );
@endphp

<x-universe-layout surface="dark">

    <x-slot name="header">Mis universos</x-slot>

    <div x-data="estanteriaDeMundos()" class="space-y-3">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    OmniMerge · Universos
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Tus mundos
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Cada universo tiene su propia gente, su propio tiempo y su propia
                    clasificación. <strong class="text-slate-400">No se mezclan</strong>: el mismo
                    personaje puede ser el número uno en uno y el último en otro.
                </p>
            </div>

            @can('create', App\Models\Universe::class)
                <a href="{{ route('universes.create') }}"
                    class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
                    <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                    Crear un mundo
                </a>
            @endcan
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- CIFRAS DEL CONJUNTO --}}
        {{-- ===================================================== --}}

        @if ($stats['total'] > 0)
            <section class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">

                @php
                    $cifras = [
                        ['Mundos', $stats['total'], '#a78bfa', null, $stats['active'] . ' en marcha'],
                        ['Borradores', $stats['draft'], '#60a5fa', 'DRAFT', 'sin publicar'],
                        ['Archivados', $stats['archived'], '#64748b', 'ARCHIVED', 'fuera de juego'],
                        ['Habitantes', $stats['entities'], '#22d3ee', null, 'en todos tus mundos'],
                        ['Competiciones', $stats['competitions'], '#34d399', null, $stats['running'] . ' en juego'],
                        ['Atascadas', $stats['stuck'], '#fb7185', null, 'esperan una decisión'],
                    ];
                @endphp

                @foreach ($cifras as [$etiqueta, $valor, $tono, $filtro, $pie])
                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                        <span class="block font-mono text-2xl font-black leading-none"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>

                        <span class="mt-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">
                            {{ $etiqueta }}
                        </span>

                        <span class="block truncate text-[9px] text-slate-600">{{ $pie }}</span>
                    </div>
                @endforeach
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        @if ($stats['total'] > 0)
            <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

                <div class="flex flex-wrap items-center gap-2">

                    <form method="GET" action="{{ route('universes.index') }}"
                        class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                        <label class="relative min-w-[150px] flex-1">
                            <span class="sr-only">Buscar un mundo</span>
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>
                            <input type="search" name="search" value="{{ $search }}"
                                placeholder="Buscar por nombre, código o descripción…"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                        </label>

                        <select name="status" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">De cualquier estado</option>
                            @foreach (['ACTIVE' => 'En marcha', 'DRAFT' => 'Borrador', 'ARCHIVED' => 'Archivado'] as $valor => $texto)
                                <option value="{{ $valor }}" @selected($status === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>

                        {{--
                            El filtro que no existía: por lo que le PASA al mundo,
                            no por lo que es.
                        --}}
                        <select name="con" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Pase lo que pase en ellos</option>
                            @foreach (['jugando' => 'Con algo jugándose', 'atascados' => 'Con algo atascado', 'sin_jugar' => 'Donde no se ha jugado nada', 'vacios' => 'Vacíos, sin habitantes'] as $valor => $texto)
                                <option value="{{ $valor }}" @selected($con === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>

                        <select name="sort" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['newest' => 'Los más nuevos', 'oldest' => 'Los más antiguos', 'movement' => 'Los que se movieron antes', 'name_asc' => 'Por nombre (A-Z)', 'name_desc' => 'Por nombre (Z-A)', 'entities_desc' => 'Con más habitantes', 'competitions_desc' => 'Con más partidas jugadas', 'tournaments_desc' => 'Con más torneos'] as $valor => $texto)
                                <option value="{{ $valor }}" @selected($sort === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>

                        <select name="per_page" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach ([12, 24, 48] as $valor)
                                <option value="{{ $valor }}" @selected($perPage === $valor)>{{ $valor }} por página</option>
                            @endforeach
                        </select>

                        <button type="submit"
                            class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Buscar
                        </button>

                        @if ($hayFiltros)
                            <a href="{{ route('universes.index') }}"
                                class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                                Quitar filtros
                            </a>
                        @endif
                    </form>


                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ([['galeria', 'galeria', 'Galería: cada mundo con su gente'], ['cuadricula', 'cuadricula', 'Cuadrícula: compacta'], ['lista', 'panel', 'Lista: una línea por mundo'], ['tabla', 'capas', 'Tabla: todas las cifras'], ['pulso', 'barras', 'Pulso: comparar los mundos entre sí']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="vista === '{{ $modo }}'"
                                :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

                    <span x-show="vista === 'galeria' || vista === 'cuadricula'" x-cloak
                        class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        <button type="button" @click="columnas = Math.max(2, columnas - 1)" :disabled="columnas === 2"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                        </button>
                        <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="columnas"></span>
                        <button type="button" @click="columnas = Math.min(6, columnas + 1)" :disabled="columnas === 6"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                        </button>
                    </span>
                </div>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- LOS MUNDOS --}}
        {{-- ===================================================== --}}

        @if ($universes->isEmpty())

            <section class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">

                <span class="inline-flex text-slate-700"><x-omni-icon name="globo" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-[15px] font-black text-white">
                    {{ $hayFiltros ? 'Ningún mundo encaja con lo que has filtrado' : 'Todavía no has creado ningún mundo' }}
                </h2>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Prueba a quitar algún filtro: puede que ninguno esté en ese estado o que no
                        tenga nada de lo que buscas.
                    @else
                        Un universo es un mundo con su propia gente, su propio calendario y su propia
                        clasificación. Trae entidades desde tu Biblioteca, define torneos y deja que
                        compitan.
                    @endif
                </p>

                @if ($hayFiltros)
                    <a href="{{ route('universes.index') }}"
                        class="mt-4 inline-block rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Quitar filtros
                    </a>
                @else
                    @can('create', App\Models\Universe::class)
                        <a href="{{ route('universes.create') }}"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
                            <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                            Crear el primero
                        </a>
                    @endcan
                @endif
            </section>

        @else

            {{-- ---------- GALERÍA ---------- --}}

            <section x-show="vista === 'galeria'" class="grid gap-3" :class="rejilla">
                @foreach ($universes as $mundo)
                    @include('universes.partials.mundo-tarjeta')
                @endforeach
            </section>


            {{-- ---------- CUADRÍCULA ---------- --}}

            @include('universes.partials.mundo-cuadricula')


            {{-- ---------- LISTA ---------- --}}

            <section x-show="vista === 'lista'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                @foreach ($universes as $mundo)
                    @include('universes.partials.mundo-fila')
                @endforeach
            </section>


            {{-- ---------- TABLA ---------- --}}

            @include('universes.partials.mundo-tabla')


            {{-- ---------- PULSO ---------- --}}

            @include('universes.partials.mundo-pulso')


            @if ($universes->hasPages())
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 px-4 py-2.5">
                    {{ $universes->links() }}
                </div>
            @endif
        @endif
    </div>


    <script>
        function estanteriaDeMundos() {

            return {

                vista: 'galeria',
                columnas: 3,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.mundos') ?? '{}');

                        if (['galeria', 'cuadricula', 'lista', 'tabla', 'pulso'].includes(g.vista)) {
                            this.vista = g.vista;
                        }

                        if (g.columnas >= 2 && g.columnas <= 6) this.columnas = g.columnas;
                    } catch (e) {
                        /* sin memoria, valores de fábrica */
                    }

                    ['vista', 'columnas'].forEach((campo) => {
                        this.$watch(campo, () => {
                            localStorage.setItem('omnimerge.mundos', JSON.stringify({
                                vista: this.vista,
                                columnas: this.columnas,
                            }));
                        });
                    });
                },

                /*
                 * Las clases se escriben enteras y literales: Tailwind lee los
                 * ficheros fuente, así que una clase compuesta al vuelo
                 * -grid-cols-${n}- no existiría en el CSS generado.
                 */
                get rejilla() {
                    return {
                        2: 'sm:grid-cols-2',
                        3: 'sm:grid-cols-2 xl:grid-cols-3',
                        4: 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
                        5: 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
                        6: 'sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6',
                    }[this.columnas] ?? 'sm:grid-cols-2 xl:grid-cols-3';
                },

                get rejillaCompacta() {
                    return {
                        2: 'grid-cols-2',
                        3: 'grid-cols-2 sm:grid-cols-3',
                        4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                        5: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-5',
                        6: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                    }[this.columnas] ?? 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4';
                },
            };
        }
    </script>

</x-universe-layout>

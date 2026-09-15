@php
    /*
     * El Resumen del Universo.
     *
     * La pantalla que ata el resto: que se esta jugando, quien manda, que toca
     * y que espera por ti. Lo ultimo es lo que no habia, y es lo que convierte
     * un resumen en un puesto de mando: el universo ya sabia que una
     * competicion estaba bloqueada o que una edicion termino sin repartir sus
     * premios, y eso no llegaba nunca aqui.
     *
     * Ver docs/md/70-Universos-Resumen.md
     */

    $tonoEstadoUniverso = [
        'ACTIVE' => ['#34d399', 'En marcha'],
        'DRAFT' => ['#60a5fa', 'Borrador'],
        'ARCHIVED' => ['#64748b', 'Archivado'],
    ];

    [$tonoUniverso, $textoUniverso] =
        $tonoEstadoUniverso[$universe->status] ?? ['#94a3b8', $universe->status];

    $tonoTipoActividad = [
        'SEASON_STARTED' => ['#a78bfa', 'Temporadas'],
        'COMPETITION_STARTED' => ['#34d399', 'Empiezan'],
        'COMPETITION_COMPLETED' => ['#22d3ee', 'Terminan'],
        'CHAMPION_CROWNED' => ['#fbbf24', 'Campeones'],
        'ENTITIES_IMPORTED' => ['#fb7185', 'Llegan competidores'],
    ];

    $puedeEditar = auth()->user()?->can('update', $universe) ?? false;

    $meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">{{ $universe->name }}</x-slot>

    <div x-data="resumenDelUniverso()" class="space-y-3">

        {{-- ===================================================== --}}
        {{-- LA PORTADA DEL MUNDO --}}
        {{-- ===================================================== --}}

        <section class="relative overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $universe->accent }}55; box-shadow: 0 0 60px -30px {{ $universe->accent }}">

            <span class="pointer-events-none absolute inset-x-0 top-0 h-1" style="background-color: {{ $universe->accent }}"></span>

            {{-- El mosaico de caras detrás: el mundo es su gente --}}
            @if ($mosaico->isNotEmpty())
                <div class="pointer-events-none absolute inset-0 grid grid-cols-6 opacity-[0.16] sm:grid-cols-9 lg:grid-cols-[repeat(18,minmax(0,1fr))]">
                    @foreach ($mosaico as $cara)
                        <span class="block aspect-square overflow-hidden">
                            <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </span>
                    @endforeach
                </div>

                <div class="pointer-events-none absolute inset-0"
                    style="background: linear-gradient(105deg, #020617 22%, #020617dd 52%, #020617aa 100%)"></div>
            @endif

            <div class="relative flex flex-wrap items-center gap-4 p-4">

                {{-- La cara del universo --}}
                <span class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border-2 bg-slate-950"
                    style="border-color: {{ $universe->accent }}">
                    @if ($universe->image_url)
                        <img src="{{ $universe->image_url }}" alt="" class="h-full w-full object-cover" style="object-position: {{ $universe->ajustes()->coverPosition() }}">
                    @else
                        <span class="flex h-full w-full items-center justify-center" style="color: {{ $universe->accent }}; background-color: {{ $universe->accent }}1a">
                            <x-omni-icon :name="$universe->ajustes()->icon()" size="h-9 w-9" />
                        </span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="rounded-lg px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                            style="color: {{ $tonoUniverso }}; background-color: {{ $tonoUniverso }}1f">
                            {{ $textoUniverso }}
                        </span>

                        <span class="font-mono text-[10px] text-slate-600">{{ $universe->code }}</span>

                        @if ($activeSeason)
                            <a href="{{ route('universes.seasons.show', [$universe, $activeSeason]) }}"
                                class="rounded-lg bg-violet-500/15 px-2 py-0.5 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                {{ $universe->ajustes()->label('label_season') }} {{ $activeSeason->number }} · {{ $activeSeason->name }}
                            </a>
                        @else
                            <span class="rounded-lg bg-amber-500/15 px-2 py-0.5 text-[10px] font-black text-amber-300">
                                Sin temporada en marcha
                            </span>
                        @endif

                        @if ($statistics['competitions_running'] > 0)
                            <a href="{{ route('universes.competitions.index', $universe) }}"
                                class="flex items-center gap-1 rounded-lg bg-emerald-500/15 px-2 py-0.5 text-[10px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-white">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                                {{ $statistics['competitions_running'] }} en juego
                            </a>
                        @endif
                    </div>

                    <h1 class="mt-1.5 text-2xl font-black leading-tight tracking-tight text-white">
                        {{ $universe->name }}
                    </h1>

                    @if ($universe->ajustes()->tagline())
                        <p class="mt-0.5 text-[13px] font-black" style="color: {{ $universe->accent }}">
                            {{ $universe->ajustes()->tagline() }}
                        </p>
                    @endif

                    @if ($universe->description)
                        <p class="mt-1 line-clamp-2 max-w-2xl text-[11px] leading-relaxed text-slate-400">
                            {{ $universe->description }}
                        </p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                    <a href="{{ route('universes.explorer', $universe) }}"
                        class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        <x-omni-icon name="globo" size="h-3.5 w-3.5" />
                        Ver el mapa
                    </a>

                    @if ($puedeEditar)
                        <a href="{{ route('universes.edit', $universe) }}"
                            class="rounded-xl border border-slate-800 bg-slate-950/70 p-2 text-slate-500 transition hover:border-violet-500 hover:text-violet-300"
                            title="Ajustes del universo">
                            <x-omni-icon name="engranaje" size="h-4 w-4" />
                        </a>
                    @endif
                </div>
            </div>
        </section>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{--
            Los bloques del Resumen, en el orden y con los que este universo
            eligió en su configuración. Arriba van a lo ancho; debajo, en dos
            columnas.
        --}}
        @php
            $ajustesResumen = $universe->ajustes();
            $bloquesArriba = $ajustesResumen->summaryBlocks('top');
            $bloquesIzquierda = $ajustesResumen->summaryBlocks('left');
            $bloquesDerecha = $ajustesResumen->summaryBlocks('right');
        @endphp

        @foreach ($bloquesArriba as $bloque)
            @include('universes.partials.resumen.' . $bloque)
        @endforeach

        @if ($bloquesIzquierda || $bloquesDerecha)
            <div class="grid gap-3 {{ $bloquesIzquierda && $bloquesDerecha ? 'lg:grid-cols-[minmax(0,1fr)_340px]' : '' }}">

                @if ($bloquesIzquierda)
                    <div class="min-w-0 space-y-3">
                        @foreach ($bloquesIzquierda as $bloque)
                            @include('universes.partials.resumen.' . $bloque)
                        @endforeach
                    </div>
                @endif

                @if ($bloquesDerecha)
                    <div class="min-w-0 {{ $bloquesIzquierda ? 'space-y-3' : 'grid items-start gap-3 lg:grid-cols-3' }}">
                        @foreach ($bloquesDerecha as $bloque)
                            @include('universes.partials.resumen.' . $bloque)
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if (! $bloquesArriba && ! $bloquesIzquierda && ! $bloquesDerecha)
            <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-[12px] text-slate-500">
                Este Resumen tiene todos sus bloques escondidos.
                @if ($puedeEditar)
                    <a href="{{ route('universes.edit', $universe) }}#resumen" class="font-black text-violet-300 underline">Elegir cuáles se ven</a>
                @endif
            </p>
        @endif
    </div>


    <script>
        function resumenDelUniverso() {

            return {

                /* El filtro del historial: por tipo, sin recargar */
                tipoActividad: '',

                /* Qué tarjeta de premios tiene la confirmación abierta */
                confirmando: null,
            };
        }
    </script>

</x-universe-layout>

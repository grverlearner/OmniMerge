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
            style="border-color: {{ $tonoUniverso }}44">

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
                <span class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border bg-slate-950"
                    style="border-color: {{ $tonoUniverso }}55">
                    @if ($universe->image_url)
                        <img src="{{ $universe->image_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-slate-700">
                            <x-omni-icon name="globo" size="h-8 w-8" />
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
                                Temporada {{ $activeSeason->number }} · {{ $activeSeason->name }}
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


        {{-- ===================================================== --}}
        {{-- LO QUE ESPERA POR TI --}}
        {{-- ===================================================== --}}

        @include('universes.partials.resumen.atencion')


        {{-- ===================================================== --}}
        {{-- EL PULSO --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">

            @php
                $cifras = [
                    ['Competidores', $statistics['entities'], '#a78bfa', route('universes.entities.index', $universe), $statistics['have_competed'] . ' han competido'],
                    ['Temporadas', $statistics['seasons'], '#60a5fa', route('universes.seasons.index', $universe), $activeSeason ? 'la ' . $activeSeason->number . ' en curso' : 'ninguna en curso'],
                    ['Torneos', $statistics['tournaments'], '#22d3ee', route('universes.tournaments.index', $universe), 'definidos en este mundo'],
                    ['Competiciones', $statistics['competitions'], '#34d399', route('universes.competitions.index', $universe), $statistics['competitions_done'] . ' terminadas'],
                    ['En juego', $statistics['competitions_running'], '#fb7185', route('universes.competitions.index', $universe), 'ahora mismo'],
                    ['Trofeos dados', $statistics['trophy_awards'], '#fbbf24', route('universes.trophies.index', $universe), $statistics['trophies'] . ' en la vitrina'],
                ];
            @endphp

            @foreach ($cifras as [$etiqueta, $valor, $tono, $destino, $pie])
                <a href="{{ $destino }}"
                    class="group rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 transition hover:-translate-y-0.5"
                    style="--t: {{ $tono }}"
                    onmouseover="this.style.borderColor='{{ $tono }}66'"
                    onmouseout="this.style.borderColor=''">

                    <span class="block font-mono text-2xl font-black leading-none"
                        style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>

                    <span class="mt-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">
                        {{ $etiqueta }}
                    </span>

                    <span class="block truncate text-[9px] text-slate-600">{{ $pie }}</span>
                </a>
            @endforeach
        </section>


        {{-- ===================================================== --}}
        {{-- LO QUE SE ESTÁ JUGANDO --}}
        {{-- ===================================================== --}}

        @include('universes.partials.resumen.en-juego')


        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_340px]">

            <div class="min-w-0 space-y-3">

                {{-- ---------- LA TEMPORADA ---------- --}}

                @include('universes.partials.resumen.temporada')

                {{-- ---------- LA LÍNEA DEL TIEMPO ---------- --}}

                @include('universes.partials.resumen.linea')

                {{-- ---------- QUÉ HA PASADO ---------- --}}

                @include('universes.partials.resumen.actividad')
            </div>


            <div class="min-w-0 space-y-3">

                {{-- ---------- QUIÉN MANDA ---------- --}}

                @include('universes.partials.resumen.ranking')

                {{-- ---------- LOS ÚLTIMOS EN GANAR ---------- --}}

                @include('universes.partials.resumen.campeones')

                {{-- ---------- CON QUÉ SE JUEGA ---------- --}}

                @include('universes.partials.resumen.juegos')
            </div>
        </div>
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

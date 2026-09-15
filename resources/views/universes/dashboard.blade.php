@php
    /*
     * El centro de mando de todos los mundos.
     *
     * Lo que habia: un hero, cinco contadores, los universos recientes y un
     * bloque de «hoja de ruta» que prometia resultados y rankings «cuando las
     * competiciones puedan jugarse de verdad» —cosa que lleva jugandose desde
     * hace tiempo—. Una promesa caducada en la primera pantalla del modulo.
     *
     * Lo que es ahora: que esta pasando AHORA MISMO en todos tus mundos a la
     * vez, y donde hace falta que entres. La estanteria («Mis universos») sirve
     * para encontrar y comparar; esto sirve para actuar.
     *
     * Ver docs/md/73-Universos-Centro.md
     */

    $tonosEstado = [
        'ACTIVE' => ['#34d399', 'En marcha'],
        'DRAFT' => ['#60a5fa', 'Borrador'],
        'ARCHIVED' => ['#64748b', 'Archivado'],
    ];

    $tonoTipoActividad = [
        'SEASON_STARTED' => ['#a78bfa', 'Temporadas'],
        'COMPETITION_STARTED' => ['#34d399', 'Empiezan'],
        'COMPETITION_COMPLETED' => ['#22d3ee', 'Terminan'],
        'CHAMPION_CROWNED' => ['#fbbf24', 'Campeones'],
        'ENTITIES_IMPORTED' => ['#fb7185', 'Llegan competidores'],
    ];

    $meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    $urgentes = $atencion->where('urgente', true)->count();
@endphp

<x-universe-layout surface="dark">

    <x-slot name="header">Universos</x-slot>

    <div x-data="{ tipoActividad: '' }" class="space-y-3">

        {{-- ===================================================== --}}
        {{-- LA PORTADA --}}
        {{-- ===================================================== --}}

        <section class="relative overflow-hidden rounded-2xl border border-violet-500/25 bg-slate-900/50">

            @if ($mosaico->isNotEmpty())
                <div class="pointer-events-none absolute inset-0 grid grid-cols-8 opacity-[0.14] sm:grid-cols-12 lg:grid-cols-[repeat(24,minmax(0,1fr))]">
                    @foreach ($mosaico as $cara)
                        <span class="block aspect-square overflow-hidden">
                            <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </span>
                    @endforeach
                </div>

                <div class="pointer-events-none absolute inset-0"
                    style="background: linear-gradient(105deg, #020617 26%, #020617dd 58%, #020617aa 100%)"></div>
            @endif

            <div class="relative flex flex-wrap items-end gap-4 p-4">

                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-400/70">
                        OmniMerge · Universos
                    </p>

                    <h1 class="mt-1 text-2xl font-black tracking-tight text-white">
                        @if ($statistics['universos'] === 0)
                            Todavía no tienes ningún mundo
                        @elseif ($statistics['en_juego'] > 0)
                            Hay {{ $statistics['en_juego'] }}
                            {{ $statistics['en_juego'] === 1 ? 'competición' : 'competiciones' }} en juego
                        @elseif ($urgentes > 0)
                            {{ $urgentes === 1 ? 'Un mundo te está esperando' : $urgentes . ' cosas te están esperando' }}
                        @else
                            Tus mundos están al día
                        @endif
                    </h1>

                    <p class="mt-0.5 max-w-2xl text-[11px] leading-relaxed text-slate-400">
                        @if ($statistics['universos'] === 0)
                            Un universo es un mundo con su propia gente, su propio calendario y su
                            propia clasificación.
                        @else
                            {{ $statistics['habitantes'] }} competidores repartidos en
                            {{ $statistics['universos'] }}
                            {{ $statistics['universos'] === 1 ? 'mundo' : 'mundos' }}, que han
                            disputado {{ number_format($statistics['partidas'], 0, ',', '.') }}
                            enfrentamientos.
                        @endif
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-1.5">
                    <a href="{{ route('universes.index') }}"
                        class="flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        <x-omni-icon name="cuadricula" size="h-3.5 w-3.5" />
                        Mis mundos
                    </a>

                    @can('create', App\Models\Universe::class)
                        <a href="{{ route('universes.create') }}"
                            class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                            Crear un mundo
                        </a>
                    @endcan
                </div>
            </div>
        </section>


        @if ($statistics['universos'] === 0)

            @include('universes.partials.centro.sin-mundos')

        @else

            {{-- ===================================================== --}}
            {{-- LAS CIFRAS DE TODO --}}
            {{-- ===================================================== --}}

            <section class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-8">
                @php
                    $cifras = [
                        ['Mundos', $statistics['universos'], '#a78bfa', $statistics['activos'] . ' en marcha'],
                        ['Habitantes', $statistics['habitantes'], '#22d3ee', 'en todos ellos'],
                        ['Temporadas', $statistics['temporadas'], '#60a5fa', 'de calendario'],
                        ['Torneos', $statistics['torneos'], '#818cf8', 'definidos'],
                        ['Competiciones', $statistics['competiciones'], '#34d399', $statistics['terminadas'] . ' terminadas'],
                        ['En juego', $statistics['en_juego'], '#fb7185', 'ahora mismo'],
                        ['Enfrentamientos', $statistics['partidas'], '#fbbf24', 'jugados en total'],
                        ['Te esperan', $atencion->count(), '#f472b6', $urgentes . ' frenan el juego'],
                    ];
                @endphp

                @foreach ($cifras as [$etiqueta, $valor, $tono, $pie])
                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-2.5 py-2">
                        <span class="block font-mono text-xl font-black leading-none"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">
                            {{ number_format($valor, 0, ',', '.') }}
                        </span>
                        <span class="mt-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">
                            {{ $etiqueta }}
                        </span>
                        <span class="block truncate text-[9px] text-slate-600">{{ $pie }}</span>
                    </div>
                @endforeach
            </section>


            {{-- ===================================================== --}}
            {{-- LO QUE ESPERA POR TI, EN TODOS LOS MUNDOS --}}
            {{-- ===================================================== --}}

            @include('universes.partials.centro.atencion')


            {{-- ===================================================== --}}
            {{-- EN JUEGO AHORA --}}
            {{-- ===================================================== --}}

            @include('universes.partials.centro.en-juego')


            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_340px]">

                <div class="min-w-0 space-y-3">

                    @include('universes.partials.centro.pulso')

                    @include('universes.partials.centro.actividad')
                </div>

                <div class="min-w-0 space-y-3">

                    @include('universes.partials.centro.seguir')

                    @include('universes.partials.centro.mandan')

                    @include('universes.partials.centro.campeones')
                </div>
            </div>
        @endif
    </div>

</x-universe-layout>

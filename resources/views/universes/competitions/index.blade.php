@php
    /*
     * Las competiciones de un universo.
     *
     * Una competición es una edición jugada de un torneo, dentro de una
     * temporada. Lo que había: una lista por fecha con un filtro de estado.
     *
     * Lo que no decía, y es lo que uno viene a saber:
     *
     *   · qué está esperando por mí     → una competición parada a mitad, con
     *                                     una decisión pendiente, se veía igual
     *                                     que cualquier otra
     *   · qué está preparado sin lanzar → configurar no es jugar
     *   · de qué torneo y temporada sale → sin imágenes y sin etiquetas
     *
     * Ahora las tres se contestan antes de la lista, y la lista se puede mirar
     * agrupada por torneo o por temporada.
     */

    $tonoEstado = [
        'DRAFT' => ['#fbbf24', 'bg-amber-500/15 text-amber-300', 'Preparada'],
        'RUNNING' => ['#34d399', 'bg-emerald-500/15 text-emerald-300', 'En curso'],
        'PAUSED' => ['#fb7185', 'bg-rose-500/15 text-rose-300', 'Pausada'],
        'COMPLETED' => ['#22d3ee', 'bg-cyan-500/15 text-cyan-300', 'Finalizada'],
        'CANCELLED' => ['#475569', 'bg-slate-800 text-slate-500', 'Cancelada'],
    ];

    $hayFiltros = $search || $status || $tournamentId || $seasonId || $gameKey;

    $enPagina = $competitions->getCollection();

    $porTorneo = $enPagina->groupBy('universe_tournament_id');
    $porTemporada = $enPagina->groupBy('universe_season_id');
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Competiciones</x-slot>

    <div x-data="competicionesDelUniverso()" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Competiciones
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Lo que se juega aquí
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Cada competición es una <strong class="text-slate-400">edición</strong> de un torneo,
                    jugada dentro de una temporada. El torneo es la receta; esto es la partida.
                </p>
            </div>

            @can('update', $universe)
                <a href="{{ route('universes.competitions.create', $universe) }}"
                    class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                    <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                    Nueva competición
                </a>
            @endcan
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-[12px] font-bold text-rose-200">
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- LO QUE ESPERA POR TI --}}
        {{-- ===================================================== --}}

        {{--
            La sección que faltaba. Una competición parada esperando una
            decisión no avanza sola, y se veía igual que cualquier otra fila de
            la lista. Esto es lo primero de la página a propósito.
        --}}

        @if ($esperando->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-rose-500/40 bg-rose-500/5">

                <div class="flex flex-wrap items-center gap-3 border-b border-rose-500/20 px-4 py-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                        <x-omni-icon name="chispa" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Te están esperando</h2>
                        <p class="text-[10px] leading-relaxed text-rose-200/70">
                            {{ $esperando->count() }}
                            {{ $esperando->count() === 1 ? 'competición se ha parado' : 'competiciones se han parado' }}
                            a mitad y no {{ $esperando->count() === 1 ? 'avanza' : 'avanzan' }} sin ti.
                        </p>
                    </div>
                </div>

                <div class="space-y-1.5 p-3">
                    @foreach ($esperando as $parada)
                        @php $suTorneo = $parada->universeTournament; @endphp

                        <div class="flex flex-wrap items-center gap-2.5 rounded-xl border border-rose-500/30 bg-slate-950 p-2">

                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                @if ($parada->image_url ?: $suTorneo?->image_url)
                                    <img src="{{ $parada->image_url ?: $suTorneo->image_url }}" alt=""
                                        loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-sm">🏆</span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('universes.competitions.show', [$universe, $parada]) }}"
                                    class="block truncate text-[12px] font-black text-white transition hover:underline">
                                    {{ $parada->name }}
                                </a>
                                <p class="truncate text-[10px] text-slate-500">
                                    @if ($parada->season)
                                        <span class="font-mono text-violet-300">T{{ $parada->season->number }}</span>
                                        <span class="text-slate-700">·</span>
                                    @endif
                                    {{ $suTorneo?->name ?? 'sin torneo' }}
                                </p>
                            </div>

                            <span class="shrink-0 rounded-lg border border-rose-500/40 px-2 py-1 text-[10px] font-black text-rose-300">
                                {{ $parada->runtime_status_label }}
                            </span>

                            <a href="{{ route('universes.competitions.play', [$universe, $parada]) }}"
                                class="shrink-0 rounded-xl bg-rose-500 px-3 py-1.5 text-[11px] font-black text-white transition hover:bg-rose-400">
                                Atender →
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- LO QUE ESTÁ PREPARADO Y SIN LANZAR --}}
        {{-- ===================================================== --}}

        @if ($preparadas->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-amber-500/30 bg-amber-500/5">

                <div class="flex flex-wrap items-center gap-3 border-b border-amber-500/20 px-4 py-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                        <x-omni-icon name="calendario" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Lo que se viene</h2>
                        <p class="text-[10px] leading-relaxed text-amber-200/70">
                            {{ $statistics['draft'] }}
                            {{ $statistics['draft'] === 1 ? 'competición está preparada y sin empezar' : 'competiciones están preparadas y sin empezar' }}.
                            Configurar no es jugar: hasta que no se lanzan, no ha pasado nada.
                        </p>
                    </div>
                </div>

                <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($preparadas as $lista)
                        @php $suTorneo = $lista->universeTournament; @endphp

                        <div class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2">

                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                @if ($lista->image_url ?: $suTorneo?->image_url)
                                    <img src="{{ $lista->image_url ?: $suTorneo->image_url }}" alt=""
                                        loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-sm">🏆</span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('universes.competitions.show', [$universe, $lista]) }}"
                                    class="block truncate text-[11px] font-black text-white">{{ $lista->name }}</a>
                                <p class="truncate text-[9px] text-slate-600">
                                    @if ($lista->season)
                                        T{{ $lista->season->number }} ·
                                    @endif
                                    {{ $lista->participant_count }} comp.
                                </p>
                            </div>

                            <a href="{{ route('universes.competitions.play', [$universe, $lista]) }}"
                                class="shrink-0 rounded-lg bg-amber-500/20 px-2 py-1 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-slate-950"
                                title="Empezar a jugarla">
                                ▶
                            </a>
                        </div>
                    @endforeach
                </div>

                @if ($statistics['draft'] > $preparadas->count())
                    <a href="{{ route('universes.competitions.index', ['universe' => $universe, 'status' => 'DRAFT']) }}"
                        class="block border-t border-amber-500/20 px-4 py-2 text-[10px] font-black text-amber-300/80 transition hover:text-amber-200">
                        Ver las {{ $statistics['draft'] }} preparadas →
                    </a>
                @endif
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach ([['Todas', $statistics['total'], null, '#a78bfa'], ['En curso', $statistics['running'], ['status' => 'RUNNING'], '#34d399'], ['Preparadas', $statistics['draft'], ['status' => 'DRAFT'], '#fbbf24'], ['Finalizadas', $statistics['completed'], ['status' => 'COMPLETED'], '#22d3ee'], ['Canceladas', $statistics['cancelled'], ['status' => 'CANCELLED'], '#64748b']] as [$etiqueta, $valor, $filtro, $tono])

                @if ($filtro)
                    <a href="{{ route('universes.competitions.index', array_merge(['universe' => $universe], $filtro)) }}"
                        class="rounded-xl border bg-slate-900/50 px-3 py-2 transition hover:-translate-y-0.5"
                        style="border-color: {{ ($status === ($filtro['status'] ?? null)) ? $tono : '#1e293b' }}">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </a>
                @else
                    <a href="{{ route('universes.competitions.index', $universe) }}"
                        class="rounded-xl border bg-slate-900/50 px-3 py-2 transition hover:-translate-y-0.5"
                        style="border-color: {{ $status === '' ? $tono : '#1e293b' }}">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </a>
                @endif
            @endforeach
        </section>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('universes.competitions.index', $universe) }}"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar competición</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre o código…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <select name="status" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Cualquier estado', 'DRAFT' => 'Preparadas', 'RUNNING' => 'En curso o pausadas', 'COMPLETED' => 'Finalizadas', 'CANCELLED' => 'Canceladas'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($status === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="tournament" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Cualquier torneo</option>
                        @foreach ($torneos as $unTorneo)
                            <option value="{{ $unTorneo->id }}" @selected($tournamentId === $unTorneo->id)>
                                {{ $unTorneo->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="season" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Cualquier temporada</option>
                        @foreach ($temporadas as $unaTemporada)
                            <option value="{{ $unaTemporada->id }}" @selected($seasonId === $unaTemporada->id)>
                                T{{ $unaTemporada->number }} · {{ $unaTemporada->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="game" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Cualquier juego</option>
                        @foreach ($juegos as $clave => $definicion)
                            <option value="{{ $clave }}" @selected($gameKey === $clave)>{{ $definicion['name'] }}</option>
                        @endforeach
                    </select>

                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['newest' => 'Las más nuevas', 'oldest' => 'Las más antiguas', 'season' => 'Por temporada', 'participants' => 'Con más competidores', 'name_asc' => 'Nombre (A–Z)'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="per_page" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach ([12, 24, 48, 96] as $cuantos)
                            <option value="{{ $cuantos }}" @selected($perPage === $cuantos)>{{ $cuantos }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Buscar
                    </button>

                    @if ($hayFiltros)
                        <a href="{{ route('universes.competitions.index', $universe) }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>


                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['grid', 'cuadricula', 'Cuadrícula: la ficha completa'], ['list', 'menu', 'Lista: una línea cada una'], ['table', 'controles', 'Tabla: para comparar'], ['tournament', 'trofeo', 'Por torneo: agrupadas por el torneo del que salen'], ['season', 'calendario', 'Por temporada: agrupadas por el tramo en que se jugaron']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="vista === '{{ $modo }}'"
                            :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="vista === 'grid'" x-cloak
                    class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    <button type="button" @click="tamano = Math.max(4, tamano - 1)" :disabled="tamano === 4"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>
                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="tamano"></span>
                    <button type="button" @click="tamano = Math.min(9, tamano + 1)" :disabled="tamano === 9"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>
            </div>
        </section>


        @if ($competitions->isEmpty())

            <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

                <span class="inline-flex text-slate-700"><x-omni-icon name="espadas" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">
                    {{ $hayFiltros ? 'Ninguna competición encaja con lo que has filtrado' : 'Todavía no se ha jugado nada aquí' }}
                </p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Prueba a quitar algún filtro.
                    @else
                        Una competición nace de un torneo y se juega dentro de una temporada. Necesitas
                        al menos un torneo configurado y competidores en el mundo.
                    @endif
                </p>

                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @if ($hayFiltros)
                        <a href="{{ route('universes.competitions.index', $universe) }}"
                            class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Quitar filtros
                        </a>
                    @endif

                    @can('update', $universe)
                        <a href="{{ route('universes.competitions.create', $universe) }}"
                            class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            Montar una
                        </a>
                    @endcan
                </div>
            </section>

        @else

            <p class="px-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                {{ $competitions->total() }}
                {{ $competitions->total() === 1 ? 'competición' : 'competiciones' }}
            </p>


            {{-- ---------- CUADRÍCULA ---------- --}}

            <div x-show="vista === 'grid'" class="grid gap-3" :class="columnas">
                @foreach ($competitions as $competicion)
                    @include('universes.competitions.partials.tarjeta', [
                        'competicion' => $competicion,
                        'juegos' => $juegos,
                    ])
                @endforeach
            </div>


            {{-- ---------- LISTA ---------- --}}

            <div x-show="vista === 'list'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                @foreach ($competitions as $competicion)
                    @include('universes.competitions.partials.fila', [
                        'competicion' => $competicion,
                        'juegos' => $juegos,
                        'tonoEstado' => $tonoEstado,
                    ])
                @endforeach
            </div>


            {{-- ---------- TABLA ---------- --}}

            <div x-show="vista === 'table'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Competición</th>
                                <th class="px-3 py-2.5">Torneo</th>
                                <th class="px-3 py-2.5">Temporada</th>
                                <th class="px-3 py-2.5">Juego</th>
                                <th class="px-3 py-2.5 text-right">Compiten</th>
                                <th class="px-3 py-2.5">Empezó</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-3 py-2.5">Dentro</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($competitions as $competicion)
                                @php
                                    [$tono, $clase, $etiqueta] = $tonoEstado[$competicion->status] ?? ['#94a3b8', 'bg-slate-800 text-slate-400', $competicion->status];
                                    $suTorneo = $competicion->universeTournament;
                                    $teEspera = in_array($competicion->runtime_status, ['AWAITING_DECISION', 'BLOCKED'], true);
                                @endphp

                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2">
                                        <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
                                            class="flex items-center gap-2">
                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                @if ($competicion->image_url ?: $suTorneo?->image_url)
                                                    <img src="{{ $competicion->image_url ?: $suTorneo->image_url }}"
                                                        alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-[11px]">🏆</span>
                                                @endif
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-[12px] font-black text-white">{{ $competicion->name }}</span>
                                                <span class="block font-mono text-[9px] text-slate-600">{{ $competicion->code }}</span>
                                            </span>
                                        </a>
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-400">{{ $suTorneo?->name ?? '—' }}</td>

                                    <td class="px-3 py-2 text-[11px]">
                                        @if ($competicion->season)
                                            <span class="font-mono text-violet-300">T{{ $competicion->season->number }}</span>
                                            <span class="text-slate-500">{{ $competicion->season->name }}</span>
                                        @else
                                            <span class="text-slate-700">—</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-500">
                                        {{ $juegos[$competicion->game_key]['name'] ?? ($competicion->game_key ?: '—') }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400">
                                        {{ $competicion->participant_count }}
                                    </td>

                                    <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                        {{ $competicion->started_at?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2">
                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $clase }}">
                                            {{ $etiqueta }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-2 text-[10px] {{ $teEspera ? 'font-black text-rose-300' : 'text-slate-500' }}">
                                        {{ $competicion->runtime_status_label ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            {{-- ---------- POR TORNEO ---------- --}}

            <div x-show="vista === 'tournament'" x-cloak class="space-y-3">

                <p class="text-[10px] leading-relaxed text-slate-500">
                    Las de esta página, agrupadas por el torneo del que salen. Es la forma de ver la
                    historia de un torneo edición a edición.
                </p>

                @foreach ($porTorneo as $idTorneo => $suyas)
                    @php $suTorneo = $suyas->first()->universeTournament; @endphp

                    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                        <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-3 py-2">

                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($suTorneo?->image_url)
                                    <img src="{{ $suTorneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-sm">🏆</span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                @if ($suTorneo)
                                    <a href="{{ route('universes.tournaments.show', [$universe, $suTorneo]) }}"
                                        class="block truncate text-[12px] font-black text-white transition hover:underline">
                                        {{ $suTorneo->name }}
                                    </a>
                                    <p class="truncate text-[9px] text-slate-600">{{ $suTorneo->recurrence_label }}</p>
                                @else
                                    <p class="text-[12px] font-black text-slate-500">Sin torneo</p>
                                    <p class="text-[9px] text-slate-600">competiciones sueltas</p>
                                @endif
                            </div>

                            <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[11px] font-black text-slate-400">
                                {{ $suyas->count() }}
                            </span>
                        </div>

                        <div class="divide-y divide-slate-800/70">
                            @foreach ($suyas as $competicion)
                                @include('universes.competitions.partials.fila', [
                                    'competicion' => $competicion,
                                    'juegos' => $juegos,
                                    'tonoEstado' => $tonoEstado,
                                ])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>


            {{-- ---------- POR TEMPORADA ---------- --}}

            <div x-show="vista === 'season'" x-cloak class="space-y-3">

                <p class="text-[10px] leading-relaxed text-slate-500">
                    Las de esta página, agrupadas por el tramo del mundo en que se jugaron. Es la forma de
                    ver qué pasó en cada temporada.
                </p>

                @foreach ($porTemporada as $idTemporada => $suyas)
                    @php $laTemporada = $suyas->first()->season; @endphp

                    <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
                        style="border-color: {{ $laTemporada ? '#a78bfa40' : '#1e293b' }}">

                        <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-3 py-2">

                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border font-mono text-[12px] font-black"
                                style="border-color: {{ $laTemporada ? '#a78bfa55' : '#1e293b' }}; color: {{ $laTemporada ? '#a78bfa' : '#475569' }}">
                                {{ $laTemporada?->number ?? '—' }}
                            </span>

                            <div class="min-w-0 flex-1">
                                @if ($laTemporada)
                                    <a href="{{ route('universes.seasons.show', [$universe, $laTemporada]) }}"
                                        class="block truncate text-[12px] font-black text-white transition hover:underline">
                                        {{ $laTemporada->name }}
                                    </a>
                                    <p class="truncate text-[9px] text-slate-600">{{ $laTemporada->period_label }}</p>
                                @else
                                    <p class="text-[12px] font-black text-slate-500">Sin temporada</p>
                                    <p class="text-[9px] text-slate-600">
                                        se jugaron fuera de cualquier tramo del mundo
                                    </p>
                                @endif
                            </div>

                            <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[11px] font-black text-slate-400">
                                {{ $suyas->count() }}
                            </span>
                        </div>

                        <div class="divide-y divide-slate-800/70">
                            @foreach ($suyas as $competicion)
                                @include('universes.competitions.partials.fila', [
                                    'competicion' => $competicion,
                                    'juegos' => $juegos,
                                    'tonoEstado' => $tonoEstado,
                                ])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>


            <div>{{ $competitions->links() }}</div>

        @endif

    </div>


    <script>
        function competicionesDelUniverso() {

            return {

                vista: 'grid',
                tamano: 6,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.competitions.view') ?? '{}');
                        if (['grid', 'list', 'table', 'tournament', 'season'].includes(g.vista)) {
                            this.vista = g.vista;
                        }
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.competitions.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
                    } catch (e) {}
                },

                /* La ficha es apaisada y no cabe en nueve columnas. */
                get columnas() {
                    return {
                        4: 'sm:grid-cols-1 lg:grid-cols-2',
                        5: 'sm:grid-cols-2',
                        6: 'sm:grid-cols-2 lg:grid-cols-3',
                        7: 'sm:grid-cols-2 lg:grid-cols-3',
                        8: 'sm:grid-cols-3 lg:grid-cols-4',
                        9: 'sm:grid-cols-3 lg:grid-cols-4',
                    }[this.tamano];
                },
            };
        }
    </script>

</x-universe-layout>

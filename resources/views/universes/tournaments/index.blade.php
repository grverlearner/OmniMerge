@php
    /*
     * Los torneos de un universo.
     *
     * Un torneo aquí es una **plantilla del mundo**: qué formato se juega, con
     * qué juego, quién puede entrar y qué se lleva el que gana. Lo que se juega
     * de verdad son sus ediciones, que viven en Competiciones.
     *
     * Lo que había: una lista paginada sin filtros, sin orden y sin decir lo
     * único que distingue un torneo vivo de una ficha guardada —cuántas veces
     * se ha jugado y quién ganó la última—.
     *
     * Ahora: filtros, orden, cinco formas de mirar, el acento del juego de cada
     * uno, y una vitrina de campeones con sus caras.
     */

    $tonosJuego = [
        'emerald' => '#34d399',
        'amber' => '#fbbf24',
        'violet' => '#a78bfa',
        'cyan' => '#22d3ee',
        'rose' => '#fb7185',
        'blue' => '#60a5fa',
    ];

    $tonoEstado = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'DRAFT' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $comunes = array_filter([
        'search' => $search,
        'status' => $status,
        'game' => $gameKey,
        'played' => $played,
        'sort' => $sort !== 'newest' ? $sort : null,
        'per_page' => $perPage !== 24 ? $perPage : null,
    ]);

    $hayFiltros = $search || $status || $gameKey || $played;

    /* El tono de un torneo sale del juego que usa. */
    $tonoDe = function ($torneo) use ($juegos, $tonosJuego) {
        $definicion = $juegos[$torneo->game_key] ?? null;

        return $tonosJuego[$definicion['accent'] ?? 'violet'] ?? '#a78bfa';
    };
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Torneos</x-slot>

    <div x-data="torneosDelUniverso()" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Torneos
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Los torneos de este mundo
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Cada uno define <strong class="text-slate-400">cómo se juega</strong>: el formato, el
                    juego, quién puede entrar y qué se lleva el ganador. Lo que se juega de verdad son sus
                    ediciones.
                </p>
            </div>

            @can('update', $universe)
                <a href="{{ route('universes.tournaments.create', $universe) }}"
                    class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                    <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                    Nuevo torneo
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
        {{-- TORNEO Y COMPETICIÓN NO SON LO MISMO --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Torneo y competición no son lo mismo
                    <span class="font-bold text-slate-500">— la receta y la cena, en un dibujo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                <div class="grid gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">

                    <svg viewBox="0 0 300 140" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- La plantilla del torneo --}}
                        <rect x="8" y="34" width="84" height="62" rx="6" stroke-dasharray="5 4" />
                        <path d="M20 50h60M20 60h44M20 70h52M20 80h30" opacity=".35" />
                        <text x="50" y="28" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">El torneo</text>
                        <text x="50" y="110" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">se configura una vez</text>

                        {{-- Sus ediciones --}}
                        <path d="M98 65h16M114 65l-6-4M114 65l-6 4" opacity=".7" />

                        <rect x="120" y="26" width="70" height="24" rx="5" opacity=".9" />
                        <circle cx="132" cy="38" r="4" opacity=".7" />
                        <path d="M142 36h34" opacity=".35" />

                        <rect x="120" y="54" width="70" height="24" rx="5" opacity=".9" />
                        <circle cx="132" cy="66" r="4" opacity=".7" />
                        <path d="M142 64h34" opacity=".35" />

                        <rect x="120" y="82" width="70" height="24" rx="5" opacity=".45" stroke-dasharray="4 3" />
                        <circle cx="132" cy="94" r="4" opacity=".35" />
                        <path d="M142 92h24" opacity=".2" />

                        <text x="155" y="20" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">Sus ediciones</text>
                        <text x="155" y="120" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">una por temporada</text>

                        {{-- El campeón --}}
                        <path d="M196 65h16M212 65l-6-4M212 65l-6 4" opacity=".7" />
                        <circle cx="252" cy="56" r="16" stroke-width="2" />
                        <path d="M246 56l4 4 8-9" stroke-width="2" />
                        <path d="M240 82h24M244 90h16" opacity=".4" />
                        <text x="252" y="30" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">Un campeón</text>
                        <text x="252" y="110" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">y sus recompensas</text>

                        <path d="M8 128h284" opacity=".15" />
                        <text x="150" y="138" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".5">cambiar el torneo no toca las ediciones ya jugadas</text>
                    </svg>

                    <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            Un <strong class="text-white">torneo</strong> de este panel es una receta: qué
                            formato se juega, con qué juego se resuelven las batallas, quién puede
                            apuntarse y qué se lleva el que gana.
                        </p>

                        <p>
                            Cada vez que se lanza nace una <strong class="text-violet-300">edición</strong>
                            —una competición— que se juega de verdad y guarda su resultado. Las ediciones
                            viven en el panel de Competiciones.
                        </p>

                        <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-400">
                            <strong class="text-slate-200">Cambiar el torneo no toca lo ya jugado.</strong>
                            Cada edición se queda con la configuración que tenía al lanzarse, así que
                            retocar la receta afecta a las siguientes, no a las de ayer.
                        </p>

                        <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            Un torneo en <strong class="text-amber-300">borrador</strong> no se puede
                            lanzar todavía: le falta terminar de configurarse.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach ([['Torneos', $statistics['total'], null, '#a78bfa'], ['Activos', $statistics['active'], ['status' => 'ACTIVE'], '#34d399'], ['En borrador', $statistics['draft'], ['status' => 'DRAFT'], '#fbbf24'], ['Sin jugar', $statistics['sin_jugar'], ['played' => 'no'], '#fb7185'], ['Ediciones', $statistics['ediciones'], null, '#22d3ee']] as [$etiqueta, $valor, $filtro, $tono])

                @if ($filtro)
                    <a href="{{ route('universes.tournaments.index', array_merge(['universe' => $universe], $filtro)) }}"
                        class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 transition hover:border-slate-700">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </a>
                @else
                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </div>
                @endif
            @endforeach
        </section>


        @if ($statistics['sin_jugar'] > 0 && $played !== 'no')
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-rose-500/25 bg-rose-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                    <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-rose-200/80">
                    <strong class="text-rose-200">{{ $statistics['sin_jugar'] }}</strong>
                    {{ $statistics['sin_jugar'] === 1 ? 'torneo está configurado y no se ha jugado nunca' : 'torneos están configurados y no se han jugado nunca' }}.
                    Configurar no es lanzar: hasta que no nace una edición, no ha pasado nada.
                </p>

                <a href="{{ route('universes.tournaments.index', ['universe' => $universe, 'played' => 'no']) }}"
                    class="shrink-0 rounded-xl border border-rose-500/40 px-2.5 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                    Verlos →
                </a>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('universes.tournaments.index', $universe) }}"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar torneo</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar torneo…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <select name="status" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Cualquier estado', 'ACTIVE' => 'Activos', 'DRAFT' => 'En borrador', 'ARCHIVED' => 'Archivados'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($status === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="game" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Cualquier juego</option>
                        @foreach ($juegos as $clave => $definicion)
                            <option value="{{ $clave }}" @selected($gameKey === $clave)>
                                {{ $definicion['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="played" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Jugados o no', 'yes' => 'Con ediciones', 'no' => 'Sin jugar nunca'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($played === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['newest' => 'Los más nuevos', 'oldest' => 'Los más antiguos', 'editions' => 'Los más jugados', 'name_asc' => 'Nombre (A–Z)', 'name_desc' => 'Nombre (Z–A)'] as $valor => $etiqueta)
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
                        <a href="{{ route('universes.tournaments.index', $universe) }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>


                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['grid', 'cuadricula', 'Cuadrícula: la ficha completa'], ['gallery', 'galeria', 'Galería: solo las portadas'], ['list', 'menu', 'Lista: una línea cada uno'], ['table', 'controles', 'Tabla: para comparar'], ['champions', 'medalla', 'Campeones: quién ganó la última edición de cada uno']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="vista === '{{ $modo }}'"
                            :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="['gallery', 'grid'].includes(vista)" x-cloak
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


        @if ($universeTournaments->isEmpty())

            <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

                <span class="inline-flex text-slate-700"><x-omni-icon name="trofeo" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">
                    {{ $hayFiltros ? 'Ningún torneo encaja con lo que has filtrado' : 'Este universo no tiene torneos' }}
                </p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Prueba a quitar algún filtro.
                    @else
                        Un torneo define cómo se compite aquí: el formato, el juego y qué se lleva el que
                        gana. Después se lanza tantas veces como quieras.
                    @endif
                </p>

                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @if ($hayFiltros)
                        <a href="{{ route('universes.tournaments.index', $universe) }}"
                            class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Quitar filtros
                        </a>
                    @endif

                    @can('update', $universe)
                        <a href="{{ route('universes.tournaments.create', $universe) }}"
                            class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            Crear el primero
                        </a>
                    @endcan
                </div>
            </section>

        @else

            <p class="px-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                {{ $universeTournaments->total() }}
                {{ $universeTournaments->total() === 1 ? 'torneo' : 'torneos' }}
            </p>


            {{-- ---------- CUADRÍCULA ---------- --}}

            <div x-show="vista === 'grid'" class="grid gap-3" :class="columnasAnchas">
                @foreach ($universeTournaments as $torneo)
                    @include('universes.tournaments.partials.tarjeta', [
                        'torneo' => $torneo,
                        'tono' => $tonoDe($torneo),
                        'juegos' => $juegos,
                        'tonoEstado' => $tonoEstado,
                    ])
                @endforeach
            </div>


            {{-- ---------- GALERÍA ---------- --}}

            <div x-show="vista === 'gallery'" x-cloak class="grid gap-2" :class="columnas">
                @foreach ($universeTournaments as $torneo)
                    @php $tono = $tonoDe($torneo); @endphp

                    <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                        title="{{ $torneo->name }}"
                        class="group relative overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                        style="border-color: {{ $tono }}40">

                        <span class="block aspect-[4/3] overflow-hidden bg-slate-900">
                            @if ($torneo->image_url)
                                <img src="{{ $torneo->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-2xl"
                                    style="color: {{ $tono }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $tono }}22, transparent 70%)">
                                    {{ $juegos[$torneo->game_key]['icon'] ?? '🏆' }}
                                </span>
                            @endif
                        </span>

                        @if ($torneo->instances_count > 0)
                            <span class="absolute left-1 top-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black"
                                style="color: {{ $tono }}">×{{ $torneo->instances_count }}</span>
                        @else
                            <span class="absolute left-1 top-1 rounded bg-rose-500/85 px-1 text-[8px] font-black uppercase text-white">
                                sin jugar
                            </span>
                        @endif

                        <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                            {{ $torneo->name }}
                        </span>
                        <span class="block truncate px-1.5 pb-1 text-center text-[9px] text-slate-600">
                            {{ $torneo->status_label }}
                        </span>
                    </a>
                @endforeach
            </div>


            {{-- ---------- LISTA ---------- --}}

            <div x-show="vista === 'list'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                @foreach ($universeTournaments as $torneo)
                    @php
                        $tono = $tonoDe($torneo);
                        $ultima = $torneo->instances->first();
                    @endphp

                    <div class="flex items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

                        <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                            class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                            style="border-color: {{ $tono }}40">
                            @if ($torneo->image_url)
                                <img src="{{ $torneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-sm">
                                    {{ $juegos[$torneo->game_key]['icon'] ?? '🏆' }}
                                </span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                    class="truncate text-[12px] font-black text-white">{{ $torneo->name }}</a>

                                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoEstado[$torneo->status] ?? 'bg-slate-800 text-slate-500' }}">
                                    {{ $torneo->status_label }}
                                </span>
                            </div>

                            <p class="truncate text-[10px] text-slate-500">
                                <span style="color: {{ $tono }}">{{ $juegos[$torneo->game_key]['name'] ?? ($torneo->game_key ?: 'Sin juego') }}</span>
                                <span class="text-slate-700">·</span>
                                {{ $torneo->tournamentTemplate?->name ?? 'Sin plantilla' }}
                                <span class="text-slate-700">·</span>
                                {{ $torneo->recurrence_label }}
                            </p>
                        </div>

                        @if ($ultima)
                            <span class="hidden shrink-0 text-right sm:block">
                                <span class="block font-mono text-[10px] text-slate-400">{{ $torneo->instances_count }}
                                    {{ $torneo->instances_count === 1 ? 'edición' : 'ediciones' }}</span>
                                <span class="block text-[9px] text-slate-600">
                                    última {{ $ultima->created_at?->diffForHumans(null, true) }}
                                </span>
                            </span>
                        @else
                            <span class="hidden shrink-0 rounded-lg border border-rose-500/30 px-2 py-0.5 text-[9px] font-black text-rose-300 sm:block">
                                sin jugar
                            </span>
                        @endif

                        <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
                            Ver →
                        </a>
                    </div>
                @endforeach
            </div>


            {{-- ---------- TABLA ---------- --}}

            <div x-show="vista === 'table'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Torneo</th>
                                <th class="px-3 py-2.5">Juego</th>
                                <th class="px-3 py-2.5">Formato</th>
                                <th class="px-3 py-2.5">Repetición</th>
                                <th class="px-3 py-2.5 text-right">Ediciones</th>
                                <th class="px-3 py-2.5 text-right">Premios</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-3 py-2.5"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($universeTournaments as $torneo)
                                @php $tono = $tonoDe($torneo); @endphp

                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2">
                                        <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                            class="flex items-center gap-2">
                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                                                style="border-color: {{ $tono }}40">
                                                @if ($torneo->image_url)
                                                    <img src="{{ $torneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-[11px]">
                                                        {{ $juegos[$torneo->game_key]['icon'] ?? '🏆' }}
                                                    </span>
                                                @endif
                                            </span>
                                            <span class="truncate text-[12px] font-black text-white">{{ $torneo->name }}</span>
                                        </a>
                                    </td>

                                    <td class="px-3 py-2 text-[11px]" style="color: {{ $tono }}">
                                        {{ $juegos[$torneo->game_key]['name'] ?? ($torneo->game_key ?: 'Sin juego') }}
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-500">
                                        {{ $torneo->tournamentTemplate?->name ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-500">{{ $torneo->recurrence_label }}</td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $torneo->instances_count > 0 ? 'text-slate-300' : 'text-rose-400' }}">
                                        {{ $torneo->instances_count }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $torneo->rewards_count > 0 ? 'text-amber-300' : 'text-slate-700' }}">
                                        {{ $torneo->rewards_count }}
                                    </td>

                                    <td class="px-3 py-2">
                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$torneo->status] ?? 'bg-slate-800 text-slate-500' }}">
                                            {{ $torneo->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-2 text-right">
                                        <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                            class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">Ver →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            {{-- ---------- CAMPEONES ---------- --}}

            {{--
                La forma de mirar que contesta lo que uno quiere saber de un
                mundo con historia: quién ganó la última edición de cada torneo.
                Los que nunca se han jugado salen igualmente, y con su hueco
                vacío dicen más que si se escondieran.
            --}}

            <div x-show="vista === 'champions'" x-cloak class="space-y-2.5">

                <p class="text-[10px] leading-relaxed text-slate-500">
                    Quién ganó la última edición de cada torneo. Los que nunca se han jugado también salen:
                    su hueco vacío es la información.
                </p>

                @foreach ($universeTournaments as $torneo)
                    @php
                        $tono = $tonoDe($torneo);
                        $ultima = $torneo->instances->first();
                        /* La ultima edicion que llego a tener un primero. */
    $edicionCampeona = $torneo->instances->first(fn($e) => $e->participants->isNotEmpty());

    $campeon = $edicionCampeona?->participants->first();
                        $cara = $campeon?->universeEntity;
                    @endphp

                    <article class="flex flex-wrap items-center gap-3 overflow-hidden rounded-2xl border bg-slate-900/50 p-3"
                        style="border-color: {{ $campeon ? $tono . '55' : '#1e293b' }}">

                        <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                            class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                            style="border-color: {{ $tono }}40">
                            @if ($torneo->image_url)
                                <img src="{{ $torneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-lg">
                                    {{ $juegos[$torneo->game_key]['icon'] ?? '🏆' }}
                                </span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                class="block truncate text-[13px] font-black text-white transition hover:underline">
                                {{ $torneo->name }}
                            </a>

                            <p class="truncate text-[10px] text-slate-500">
                                {{ $torneo->instances_count }}
                                {{ $torneo->instances_count === 1 ? 'edición' : 'ediciones' }}
                                @if ($ultima?->season)
                                    <span class="text-slate-700">·</span>
                                    última en {{ $ultima->season->name ?? 'temporada ' . $ultima->season->number }}
                                @endif
                            </p>
                        </div>

                        @if ($campeon)
                            <div class="flex shrink-0 items-center gap-2.5 rounded-xl border px-2.5 py-1.5"
                                style="border-color: {{ $tono }}40; background-color: {{ $tono }}10">

                                <span class="text-lg">🏆</span>

                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    @if ($cara?->image_url)
                                        <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                    @endif
                                </span>

                                <span class="min-w-0">
                                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        {{-- Si el campeón no es de la última edición, se dice de cuál es --}}
                                        @if ($edicionCampeona && $ultima && $edicionCampeona->is($ultima))
                                            campeón
                                        @else
                                            campeón de {{ $edicionCampeona?->name ?? 'una edición anterior' }}
                                        @endif
                                    </span>
                                    <span class="block max-w-[160px] truncate text-[12px] font-black"
                                        style="color: {{ $tono }}">
                                        {{ $cara?->display_label ?? $campeon->display_name ?? $campeon->name ?? 'Sin nombre' }}
                                    </span>
                                </span>
                            </div>
                        @elseif ($ultima)
                            <span class="shrink-0 rounded-xl border border-slate-800 px-3 py-2 text-[10px] font-black text-slate-600"
                                title="Se lanzó, pero todavía no ha terminado">
                                aún sin campeón
                            </span>
                        @else
                            <span class="shrink-0 rounded-xl border border-rose-500/30 px-3 py-2 text-[10px] font-black text-rose-300">
                                nunca se ha jugado
                            </span>
                        @endif
                    </article>
                @endforeach
            </div>


            <div>{{ $universeTournaments->links() }}</div>

        @endif

    </div>


    <script>
        function torneosDelUniverso() {

            return {

                vista: 'grid',
                tamano: 6,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.universeTournaments.view') ?? '{}');
                        if (['grid', 'gallery', 'list', 'table', 'champions'].includes(g.vista)) this.vista = g.vista;
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.universeTournaments.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
                    } catch (e) {}
                },

                get columnas() {
                    return {
                        4: 'grid-cols-2 sm:grid-cols-4',
                        5: 'grid-cols-2 sm:grid-cols-5',
                        6: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                        7: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                        8: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                        9: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-9',
                    }[this.tamano];
                },

                /* La ficha completa es apaisada y no cabe en nueve columnas. */
                get columnasAnchas() {
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

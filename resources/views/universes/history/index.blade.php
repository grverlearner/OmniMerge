@php
    /*
     * El historial de un universo.
     *
     * Lo que había: una lista de las competiciones jugadas. Correcto y corto,
     * porque la historia de un mundo es más que su lista de partidas.
     *
     * Existía ya —y no lo miraba nadie desde aquí— un registro de actividad con
     * lo que va pasando: temporadas que empiezan, competiciones que arrancan y
     * terminan, campeones coronados, entidades importadas. Esa es la crónica
     * del mundo, y ahora es lo primero que se ve.
     *
     * Y la pregunta que un historial debería contestar antes que ninguna otra
     * —quién ha ganado qué— tiene su propia vista, con caras y trofeos.
     */

    $tonoTipo = [
        'SEASON_STARTED' => ['#a78bfa', 'Temporadas'],
        'COMPETITION_STARTED' => ['#34d399', 'Competiciones que empiezan'],
        'COMPETITION_COMPLETED' => ['#22d3ee', 'Competiciones que terminan'],
        'CHAMPION_CROWNED' => ['#fbbf24', 'Campeones'],
        'ENTITIES_IMPORTED' => ['#fb7185', 'Competidores que llegan'],
    ];

    $tonoEstado = [
        'RUNNING' => ['#34d399', 'bg-emerald-500/15 text-emerald-300'],
        'PAUSED' => ['#fb7185', 'bg-rose-500/15 text-rose-300'],
        'COMPLETED' => ['#22d3ee', 'bg-cyan-500/15 text-cyan-300'],
        'CANCELLED' => ['#475569', 'bg-slate-800 text-slate-500'],
    ];

    $hayFiltros = $search || $type || $seasonId || $engine;

    /* La crónica se agrupa por día: es como se lee una historia. */
    $porDia = $actividad->groupBy(fn($evento) => $evento->occurred_at?->format('Y-m-d') ?? 'sin-fecha');

    $maximoTitulos = $salon->max(fn($fila) => $fila['titulos']->count()) ?: 1;

    /*
     * La aplicación corre con el idioma en «en», así que `translatedFormat`
     * escribiría los meses en inglés en medio de una pantalla en castellano.
     * Se escriben aquí en vez de cambiar el idioma de toda la aplicación por
     * una fecha.
     */
    $meses = [
        1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Historial</x-slot>

    <div x-data="historialDelUniverso()" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Historial
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Lo que ha pasado en este mundo
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Las temporadas que empezaron, las competiciones que se jugaron y quién se llevó cada
                    una. Nada de esto se puede tocar desde aquí: es lo que ya pasó.
                </p>
            </div>
        </header>


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ([['Jugadas', $statistics['played'], '#a78bfa', 'Competiciones que llegaron a arrancar'], ['Terminadas', $statistics['completed'], '#22d3ee', 'Y que llegaron al final'], ['Campeones', $statistics['campeones'], '#fbbf24', 'Competidores distintos que han ganado algo'], ['Encuentros', $statistics['matches'], '#34d399', 'Cruces resueltos'], ['Batallas', $statistics['encuentros'], '#f472b6', 'Veces que un motor de juego decidió algo'], ['Eventos', $statistics['eventos'], '#60a5fa', 'Cosas anotadas en la crónica']] as [$etiqueta, $valor, $tono, $ayuda])
                <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2" title="{{ $ayuda }}">
                    <span class="block font-mono text-xl font-black"
                        style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                </div>
            @endforeach
        </section>


        @if ($statistics['played'] === 0 && $statistics['eventos'] === 0)

            <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="historial" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">Este mundo todavía no tiene historia</p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    La historia se escribe sola: empieza una temporada, lanza una competición y aquí
                    aparecerá lo que fue pasando.
                </p>

                <a href="{{ route('universes.competitions.index', $universe) }}"
                    class="mt-3 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                    Ir a competiciones →
                </a>
            </section>

        @else

            {{-- ===================================================== --}}
            {{-- FILTROS Y FORMA DE MIRAR --}}
            {{-- ===================================================== --}}

            <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

                <div class="flex flex-wrap items-center gap-2">

                    <form method="GET" action="{{ route('universes.history', $universe) }}"
                        class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                        <label class="relative min-w-[150px] flex-1">
                            <span class="sr-only">Buscar</span>
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>
                            <input type="search" name="search" value="{{ $search }}"
                                placeholder="Buscar en la historia…"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                        </label>

                        <select name="season" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Toda la historia</option>
                            @foreach ($seasons as $temporada)
                                <option value="{{ $temporada->id }}" @selected($seasonId === $temporada->id)>
                                    T{{ $temporada->number }} · {{ $temporada->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="type" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Cualquier cosa</option>
                            @foreach ($tipos as $unTipo)
                                <option value="{{ $unTipo->type }}" @selected($type === $unTipo->type)>
                                    {{ $tonoTipo[$unTipo->type][1] ?? $unTipo->type }} ({{ $unTipo->total }})
                                </option>
                            @endforeach
                        </select>

                        <select name="sort" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="newest" @selected($sort !== 'oldest')>De lo más reciente</option>
                            <option value="oldest" @selected($sort === 'oldest')>Desde el principio</option>
                        </select>

                        <button type="submit"
                            class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Buscar
                        </button>

                        @if ($hayFiltros)
                            <a href="{{ route('universes.history', $universe) }}"
                                class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                                Quitar filtros
                            </a>
                        @endif
                    </form>


                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ([['cronica', 'historial', 'Crónica: todo lo que pasó, día a día'], ['champions', 'medalla', 'Salón de la fama: quién ha ganado qué'], ['palmares', 'trofeo', 'Palmarés: las competiciones jugadas'], ['seasons', 'calendario', 'Por temporada: el mundo contado por tramos'], ['table', 'controles', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="vista === '{{ $modo }}'"
                                :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

                    <span x-show="vista === 'palmares'" x-cloak
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


            {{-- ===================================================== --}}
            {{-- LA CRÓNICA --}}
            {{-- ===================================================== --}}

            {{--
                El registro de actividad, que existía y no se enseñaba en
                ninguna parte. Agrupado por día, que es como se lee una
                historia, y con la cara de lo que menciona cada evento.
            --}}

            <section x-show="vista === 'cronica'" class="space-y-4">

                @if ($actividad->isEmpty())
                    <p class="rounded-2xl border border-dashed border-slate-800 py-10 text-center text-[11px] leading-relaxed text-slate-600">
                        {{ $hayFiltros ? 'Nada encaja con lo que has filtrado.' : 'Todavía no hay nada anotado en la crónica.' }}
                    </p>
                @else
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Todo lo que ha ido pasando, día a día. Se anotan solas: las temporadas al empezar,
                        las competiciones al arrancar y al terminar, y los competidores al llegar al mundo.
                    </p>

                    @foreach ($porDia as $dia => $eventos)
                        <div class="relative pl-6">

                            <span class="absolute inset-y-0 left-[9px] w-px bg-slate-800"></span>

                            <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-500">
                                @if ($dia === 'sin-fecha')
                                    Sin fecha
                                @else
                                    @php $fecha = \Carbon\Carbon::parse($dia); @endphp
                                    {{ $fecha->day }} de {{ $meses[$fecha->month] }} de {{ $fecha->year }}
                                @endif
                                <span class="ml-1 font-mono text-slate-700">{{ $eventos->count() }}</span>
                            </p>

                            <div class="space-y-1.5">
                                @foreach ($eventos as $evento)
                                    @php
                                        [$tono, $etiquetaTipo] = $tonoTipo[$evento->type] ?? ['#94a3b8', $evento->type];
                                        $competicion = $evento->tournamentInstance;
                                        $competidor = $evento->universeEntity;
                                        $cara = $competidor?->image_url
                                            ?: ($competicion?->image_url ?: $competicion?->universeTournament?->image_url);
                                    @endphp

                                    <div class="relative flex flex-wrap items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-900/50 p-2">

                                        <span class="absolute -left-[21px] top-4 h-2.5 w-2.5 rounded-full border-2 border-slate-950"
                                            style="background-color: {{ $tono }}"></span>

                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-slate-950"
                                            style="border-color: {{ $tono }}40">
                                            @if ($cara)
                                                <img src="{{ $cara }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-sm">{{ $evento->icon ?: '•' }}</span>
                                            @endif
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[12px] font-black text-white">{{ $evento->message }}</p>

                                            <p class="flex flex-wrap items-center gap-1.5 truncate text-[9px] text-slate-600">
                                                <span class="rounded px-1 py-0.5 font-black uppercase tracking-wider"
                                                    style="background-color: {{ $tono }}22; color: {{ $tono }}">
                                                    {{ $etiquetaTipo }}
                                                </span>

                                                @if ($evento->season)
                                                    <a href="{{ route('universes.seasons.show', [$universe, $evento->season]) }}"
                                                        class="font-mono text-violet-400 transition hover:underline">
                                                        T{{ $evento->season->number }}
                                                    </a>
                                                @endif

                                                @if ($competicion?->universeTournament)
                                                    <span class="truncate">{{ $competicion->universeTournament->name }}</span>
                                                @endif
                                            </p>
                                        </div>

                                        <span class="shrink-0 font-mono text-[9px] text-slate-700">
                                            {{ $evento->occurred_at?->format('H:i') }}
                                        </span>

                                        @if ($competicion)
                                            <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
                                                class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-white">
                                                Ver →
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if ($actividad->count() >= 120)
                        <p class="px-1 text-[10px] text-slate-600">
                            Se enseñan los 120 eventos más recientes. Filtra por temporada o por tipo para
                            llegar más atrás.
                        </p>
                    @endif
                @endif
            </section>


            {{-- ===================================================== --}}
            {{-- SALÓN DE LA FAMA --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'champions'" x-cloak class="space-y-2.5">

                @if ($salon->isEmpty())
                    <p class="rounded-2xl border border-dashed border-slate-800 py-10 text-center text-[11px] leading-relaxed text-slate-600">
                        Nadie ha ganado nada todavía. Hace falta que una competición llegue al final.
                    </p>
                @else
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Quién ha ganado qué, contado sobre los campeonatos de verdad. Se cuenta sobre la
                        historia entera del mundo, no sobre lo que haya filtrado arriba.
                    </p>

                    @foreach ($salon as $posicion => $fila)
                        @php
                            $competidor = $fila['competidor'];
                            $cuantos = $fila['titulos']->count();
                            $ancho = max((int) round(($cuantos / $maximoTitulos) * 100), 8);
                            $esPrimero = $posicion === 0;
                        @endphp

                        <article class="overflow-hidden rounded-2xl border bg-slate-900/50"
                            style="border-color: {{ $esPrimero ? '#fbbf24' : '#1e293b' }}">

                            <div class="flex flex-wrap items-center gap-3 p-3">

                                <span class="w-6 shrink-0 text-center font-mono text-[14px] font-black {{ $esPrimero ? 'text-amber-300' : 'text-slate-700' }}">
                                    {{ $posicion + 1 }}
                                </span>

                                <span class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                                    style="border-color: {{ $esPrimero ? '#fbbf2455' : '#1e293b' }}">
                                    @if ($competidor?->image_url)
                                        <img src="{{ $competidor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1">
                                    @if ($competidor)
                                        <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                                            class="block truncate text-[14px] font-black text-white transition hover:underline">
                                            {{ $fila['nombre'] }}
                                        </a>
                                    @else
                                        <p class="truncate text-[14px] font-black text-slate-400">{{ $fila['nombre'] }}</p>
                                    @endif

                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-950">
                                        <div class="h-full rounded-full"
                                            style="width: {{ $ancho }}%; background-color: {{ $esPrimero ? '#fbbf24' : '#64748b' }}"></div>
                                    </div>
                                </div>

                                <span class="shrink-0 text-right">
                                    <span class="block font-mono text-2xl font-black {{ $esPrimero ? 'text-amber-300' : 'text-slate-400' }}">
                                        {{ $cuantos }}
                                    </span>
                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                        {{ $cuantos === 1 ? 'título' : 'títulos' }}
                                    </span>
                                </span>
                            </div>

                            {{-- Qué ganó exactamente --}}
                            <div class="flex flex-wrap gap-1.5 border-t border-slate-800 px-3 py-2">
                                @foreach ($fila['titulos'] as $titulo)
                                    @php $suComp = $titulo->tournamentInstance; @endphp

                                    <a href="{{ $suComp ? route('universes.competitions.show', [$universe, $suComp]) : '#' }}"
                                        class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-2 transition hover:border-amber-500/50">

                                        <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                            @if ($suComp?->image_url ?: $suComp?->universeTournament?->image_url)
                                                <img src="{{ $suComp->image_url ?: $suComp->universeTournament->image_url }}"
                                                    alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[10px]">🏆</span>
                                            @endif
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block max-w-[170px] truncate text-[10px] font-black text-slate-300">
                                                {{ $suComp?->name ?? 'Competición' }}
                                            </span>
                                            <span class="block text-[8px] text-slate-600">
                                                @if ($suComp?->season)
                                                    T{{ $suComp->season->number }} ·
                                                @endif
                                                {{ $suComp?->completed_at?->format('d/m/Y') ?? 'sin fecha' }}
                                            </span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                @endif
            </section>


            {{-- ===================================================== --}}
            {{-- PALMARÉS --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'palmares'" x-cloak class="space-y-3">

                @if ($competitions->isEmpty())
                    <p class="rounded-2xl border border-dashed border-slate-800 py-10 text-center text-[11px] text-slate-600">
                        {{ $hayFiltros ? 'Ninguna competición encaja con lo que has filtrado.' : 'Todavía no se ha jugado nada.' }}
                    </p>
                @else
                    <div class="grid gap-3" :class="columnas">
                        @foreach ($competitions as $competicion)
                            @php
                                [$tono, $clase] = $tonoEstado[$competicion->status] ?? ['#94a3b8', 'bg-slate-800 text-slate-400'];
                                $campeon = $champions[$competicion->id] ?? null;
                                $suCara = $campeon?->universeEntity;
                                $portada = $competicion->image_url ?: $competicion->universeTournament?->image_url;
                            @endphp

                            <article class="group overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
                                style="border-color: {{ $campeon ? '#fbbf2440' : $tono . '33' }}">

                                <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
                                    class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

                                    @if ($portada)
                                        <img src="{{ $portada }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        <span class="absolute inset-x-0 bottom-0 h-3/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-4xl"
                                            style="color: {{ $tono }}66">🏆</span>
                                    @endif

                                    @if ($competicion->season)
                                        <span class="absolute left-2 top-2 rounded-lg border border-violet-500/50 px-1.5 py-0.5 font-mono text-[9px] font-black text-violet-200"
                                            style="background-color: #020617d9">
                                            T{{ $competicion->season->number }}
                                        </span>
                                    @endif

                                    <span class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $clase }}">
                                        {{ $competicion->status_label }}
                                    </span>

                                    <span class="absolute inset-x-0 bottom-0 p-2">
                                        <span class="block truncate text-[13px] font-black text-white">{{ $competicion->name }}</span>
                                        <span class="block truncate text-[9px] text-slate-400">
                                            {{ $competicion->universeTournament?->name ?? 'sin torneo' }}
                                        </span>
                                    </span>
                                </a>

                                <div class="p-2.5">
                                    @if ($campeon)
                                        <div class="flex items-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 p-1.5">
                                            <span class="shrink-0 text-sm">🏆</span>

                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                @if ($suCara?->image_url)
                                                    <img src="{{ $suCara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                                @endif
                                            </span>

                                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-amber-200">
                                                {{ $suCara?->display_label ?? $campeon->display_name ?? $campeon->name ?? 'Campeón' }}
                                            </span>
                                        </div>
                                    @else
                                        <p class="rounded-xl border border-slate-800 px-2 py-1.5 text-center text-[10px] text-slate-600">
                                            {{ $competicion->status === 'CANCELLED' ? 'Se canceló sin campeón.' : 'Todavía sin campeón.' }}
                                        </p>
                                    @endif

                                    <p class="mt-1.5 flex items-center justify-between font-mono text-[9px] text-slate-600">
                                        <span>{{ $competicion->participant_count }} compitieron</span>
                                        <span>{{ $competicion->started_at?->format('d/m/Y') ?? '—' }}</span>
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div>{{ $competitions->links() }}</div>
                @endif
            </section>


            {{-- ===================================================== --}}
            {{-- POR TEMPORADA --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'seasons'" x-cloak class="space-y-3">

                <p class="text-[10px] leading-relaxed text-slate-500">
                    El mundo contado por tramos: qué se jugó en cada temporada y quién se lo llevó. Una
                    temporada sin nada jugado también sale: su hueco es parte de la historia.
                </p>

                @foreach ($porTemporada as $bloque)
                    @php
                        $temporada = $bloque['temporada'];
                        $suyas = $bloque['competiciones'];
                        $susCampeones = $bloque['campeones'];
                    @endphp

                    <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
                        style="border-color: {{ $suyas->isNotEmpty() ? '#a78bfa40' : '#1e293b' }}">

                        <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-3 py-2">

                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border font-mono text-[12px] font-black"
                                style="border-color: {{ $suyas->isNotEmpty() ? '#a78bfa55' : '#1e293b' }}; color: {{ $suyas->isNotEmpty() ? '#a78bfa' : '#475569' }}">
                                {{ $temporada->number }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
                                    class="block truncate text-[12px] font-black text-white transition hover:underline">
                                    {{ $temporada->name }}
                                </a>
                                <p class="truncate text-[9px] text-slate-600">{{ $temporada->period_label }}</p>
                            </div>

                            <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[11px] font-black text-slate-400">
                                {{ $suyas->count() }}
                            </span>
                        </div>

                        @if ($suyas->isEmpty())
                            <p class="px-3 py-4 text-center text-[10px] text-slate-600">
                                No se jugó nada en esta temporada.
                            </p>
                        @else
                            <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($suyas as $competicion)
                                    @php
                                        $campeon = $susCampeones[$competicion->id] ?? null;
                                        $suCara = $campeon?->universeEntity;
                                    @endphp

                                    <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
                                        class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-slate-600">

                                        <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                            @if ($suCara?->image_url)
                                                <img src="{{ $suCara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @elseif ($competicion->image_url ?: $competicion->universeTournament?->image_url)
                                                <img src="{{ $competicion->image_url ?: $competicion->universeTournament->image_url }}"
                                                    alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[11px]">🏆</span>
                                            @endif
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-white">{{ $competicion->name }}</span>
                                            <span class="block truncate text-[9px] {{ $campeon ? 'text-amber-300' : 'text-slate-600' }}">
                                                {{ $campeon
                                                    ? '🏆 ' . ($suCara?->display_label ?? $campeon->display_name ?? 'Campeón')
                                                    : $competicion->status_label }}
                                            </span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach
            </section>


            {{-- ===================================================== --}}
            {{-- TABLA --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'table'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[840px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Competición</th>
                                <th class="px-3 py-2.5">Torneo</th>
                                <th class="px-3 py-2.5">Temporada</th>
                                <th class="px-3 py-2.5">Campeón</th>
                                <th class="px-3 py-2.5 text-right">Compitieron</th>
                                <th class="px-3 py-2.5">Empezó</th>
                                <th class="px-3 py-2.5">Terminó</th>
                                <th class="px-3 py-2.5">Estado</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($competitions as $competicion)
                                @php
                                    [$tono, $clase] = $tonoEstado[$competicion->status] ?? ['#94a3b8', 'bg-slate-800 text-slate-400'];
                                    $campeon = $champions[$competicion->id] ?? null;
                                    $suCara = $campeon?->universeEntity;
                                @endphp

                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2">
                                        <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
                                            class="truncate text-[12px] font-black text-white transition hover:underline">
                                            {{ $competicion->name }}
                                        </a>
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-400">
                                        {{ $competicion->universeTournament?->name ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 text-[11px]">
                                        @if ($competicion->season)
                                            <span class="font-mono text-violet-300">T{{ $competicion->season->number }}</span>
                                        @else
                                            <span class="text-slate-700">—</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2">
                                        @if ($campeon)
                                            <span class="flex items-center gap-1.5">
                                                <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-950">
                                                    @if ($suCara?->image_url)
                                                        <img src="{{ $suCara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                    @else
                                                        <span class="flex h-full w-full items-center justify-center text-[10px] text-slate-700">◍</span>
                                                    @endif
                                                </span>
                                                <span class="truncate text-[11px] font-black text-amber-300">
                                                    {{ $suCara?->display_label ?? $campeon->display_name ?? 'Campeón' }}
                                                </span>
                                            </span>
                                        @else
                                            <span class="text-[11px] text-slate-700">—</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400">
                                        {{ $competicion->participant_count }}
                                    </td>

                                    <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                        {{ $competicion->started_at?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                        {{ $competicion->completed_at?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2">
                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $clase }}">
                                            {{ $competicion->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-800 px-4 py-3">{{ $competitions->links() }}</div>
            </section>

        @endif

    </div>


    <script>
        function historialDelUniverso() {

            return {

                vista: 'cronica',
                tamano: 6,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.history.view') ?? '{}');
                        if (['cronica', 'champions', 'palmares', 'seasons', 'table'].includes(g.vista)) {
                            this.vista = g.vista;
                        }
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.history.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
                    } catch (e) {}
                },

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

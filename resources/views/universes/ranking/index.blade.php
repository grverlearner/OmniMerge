@php
    /*
     * La clasificación de un universo.
     *
     * Contextual por definición: la misma entidad de la biblioteca puede ser
     * la número uno aquí y la dieciocho en otro mundo.
     *
     * Lo que había: una tabla ordenada por puntos, filtrable por temporada, y
     * el sistema de puntos. Correcto, y dejando sin usar dos cosas que el
     * servicio ya sabía hacer desde que se escribió:
     *
     *   · clasificar por juego   → quién manda en Highest Number, que no tiene
     *                              por qué ser quien manda en general
     *   · clasificar por torneo  → quién domina una competición a lo largo de
     *                              sus ediciones
     *
     * Y faltaba lo que convierte una tabla de puntos en algo que se entiende:
     * **de dónde salen esos puntos**.
     */

    $hayFiltros = $search || $gameKey || $tournamentId || $seasonId;

    $maximoPuntos = $ranking->max('points') ?: 1;

    $puedeEditar = auth()->user()?->can('update', $universe) ?? false;

    /* Los cinco conceptos que suman, con su color */
    $conceptos = [
        ['titles', 'points_champion', 'Títulos', '#fbbf24'],
        ['wins', 'points_win', 'Victorias', '#34d399'],
        ['draws', 'points_draw', 'Empates', '#94a3b8'],
        ['losses', 'points_loss', 'Derrotas', '#fb7185'],
        ['tournaments', 'points_participation', 'Participación', '#60a5fa'],
    ];

    $medallas = ['#fbbf24', '#cbd5e1', '#f59e0b'];
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Clasificación</x-slot>

    <div x-data="clasificacionDelUniverso()" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Clasificación
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Quién manda en este mundo
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Se calcula sola con lo que se ha jugado aquí.
                    <strong class="text-slate-400">Es de este universo</strong>: el mismo personaje puede
                    ser el primero aquí y el último en otro.
                </p>
            </div>

            @if ($puedeEditar)
                <button type="button" @click="abrirPuntos = ! abrirPuntos"
                    class="flex items-center gap-1.5 rounded-xl border border-violet-500/40 bg-violet-500/10 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                    <x-omni-icon name="controles" size="h-3.5 w-3.5" />
                    Sistema de puntos
                </button>
            @endif
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
        {{-- EL SISTEMA DE PUNTOS --}}
        {{-- ===================================================== --}}

        @if ($puedeEditar)
            @include('universes.ranking.partials.puntos', [
                'universe' => $universe,
                'settings' => $settings,
                'conceptos' => $conceptos,
            ])
        @endif


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ([['Clasificados', $statistics['clasificados'], '#a78bfa', 'Competidores que han jugado algo'], ['Con título', $statistics['con_titulo'], '#fbbf24', 'Los que han ganado alguna competición'], ['Partidas', $statistics['partidas'], '#34d399', 'Enfrentamientos sumados de todos'], ['Sin ganar nunca', $statistics['sin_ganar'], '#fb7185', 'Han competido y no han ganado ni una']] as [$etiqueta, $valor, $tono, $ayuda])
                <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2" title="{{ $ayuda }}">
                    <span class="block font-mono text-xl font-black"
                        style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                </div>
            @endforeach
        </section>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('universes.ranking', $universe) }}"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar competidor</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar competidor…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <select name="season" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">De toda la historia</option>
                        @foreach ($seasons as $temporada)
                            <option value="{{ $temporada->id }}" @selected($seasonId === $temporada->id)>
                                T{{ $temporada->number }} · {{ $temporada->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="game" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Con cualquier juego</option>
                        @foreach ($juegos as $clave => $definicion)
                            <option value="{{ $clave }}" @selected($gameKey === $clave)>
                                {{ $definicion['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="tournament" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">De cualquier torneo</option>
                        @foreach ($torneos as $unTorneo)
                            <option value="{{ $unTorneo->id }}" @selected($tournamentId === $unTorneo->id)>
                                {{ $unTorneo->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['points' => 'Por puntos', 'titles' => 'Por títulos', 'win_rate' => 'Por % de victorias', 'played' => 'Por competiciones jugadas', 'name' => 'Por nombre'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Buscar
                    </button>

                    @if ($hayFiltros)
                        <a href="{{ route('universes.ranking', $universe) }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>


                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['podio', 'medalla', 'Podio: los tres primeros en grande'], ['table', 'controles', 'Tabla: la clasificación completa'], ['cards', 'cuadricula', 'Tarjetas: con sus caras'], ['points', 'barras', 'De dónde salen los puntos de cada uno']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="vista === '{{ $modo }}'"
                            :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="vista === 'cards'" x-cloak
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


            @if ($sort !== 'points')
                <p class="mt-1.5 px-1 text-[10px] leading-4 text-amber-300/70">
                    Mirando ordenado por otra cosa, pero <strong class="text-amber-200">el número de
                    posición sigue siendo el del ranking por puntos</strong>: renumerar sería inventarse
                    otra clasificación.
                </p>
            @endif
        </section>


        @if ($ranking->isEmpty())

            <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

                <span class="inline-flex text-slate-700"><x-omni-icon name="barras" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">
                    {{ $hayFiltros ? 'Nadie encaja con lo que has filtrado' : 'Todavía no hay clasificación' }}
                </p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Puede que nadie haya jugado con ese juego, en ese torneo o en esa temporada.
                    @else
                        La clasificación se calcula sola con lo que se juega. En cuanto termine una
                        competición aparecerán aquí sus participantes.
                    @endif
                </p>

                @if ($hayFiltros)
                    <a href="{{ route('universes.ranking', $universe) }}"
                        class="mt-3 inline-block rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Quitar filtros
                    </a>
                @endif
            </section>

        @else

            {{-- ===================================================== --}}
            {{-- PODIO --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'podio'" class="space-y-3">

                @if ($sort === 'points')
                    {{--
                        Acotado y con la imagen en banda de alto fijo: con
                        `aspect-square` a todo el ancho, cada cara medía 353 px
                        en una pantalla de 1440 y el podio se comía la página
                        entera para decir tres nombres.
                    --}}
                    <div class="mx-auto grid max-w-3xl gap-3 sm:grid-cols-3">
                        @foreach ($podio as $indice => $fila)
                            @php
                                $tono = $medallas[$indice] ?? '#94a3b8';
                                $competidor = $fila->entity;
                                $alto = ['pt-0', 'pt-5', 'pt-9'][$indice] ?? 'pt-9';
                            @endphp

                            <div class="{{ $alto }}">
                                <article class="overflow-hidden rounded-2xl border bg-slate-900/50"
                                    style="border-color: {{ $tono }}66">

                                    <div class="relative h-32 overflow-hidden sm:h-36"
                                        style="background: radial-gradient(120% 110% at 50% 0%, {{ $tono }}28, #020617 70%)">

                                        @if ($competidor?->image_url)
                                            <img src="{{ $competidor->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-4xl text-slate-800">◍</span>
                                        @endif

                                        <span class="absolute left-2 top-2 flex h-8 w-8 items-center justify-center rounded-xl font-mono text-[14px] font-black"
                                            style="background-color: {{ $tono }}; color: #020617">
                                            {{ $fila->position }}
                                        </span>

                                        @if ($fila->titles > 0)
                                            <span class="absolute right-2 top-2 rounded-lg bg-slate-950/85 px-1.5 py-0.5 font-mono text-[10px] font-black"
                                                style="color: {{ $tono }}" title="Títulos ganados">
                                                🏆{{ $fila->titles }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="p-2.5">
                                        <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                                            class="block truncate text-[13px] font-black text-white transition hover:underline">
                                            {{ $competidor?->display_label ?? 'Sin nombre' }}
                                        </a>

                                        <p class="font-mono text-xl font-black" style="color: {{ $tono }}">
                                            {{ $fila->points }}
                                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-600">pts</span>
                                        </p>

                                        <div class="mt-1.5 grid grid-cols-3 gap-1 text-center">
                                            @foreach ([['G', $fila->wins, '#34d399'], ['E', $fila->draws, '#94a3b8'], ['P', $fila->losses, '#fb7185']] as [$letra, $numero, $tonoR])
                                                <span class="rounded-lg border border-slate-800 bg-slate-950 py-1">
                                                    <span class="block font-mono text-[13px] font-black"
                                                        style="color: {{ $numero > 0 ? $tonoR : '#475569' }}">{{ $numero }}</span>
                                                    <span class="block text-[8px] font-black uppercase text-slate-600">{{ $letra }}</span>
                                                </span>
                                            @endforeach
                                        </div>

                                        @if ($fila->win_rate !== null)
                                            <p class="mt-1.5 text-center text-[10px] text-slate-500">
                                                gana el <strong class="text-slate-300">{{ $fila->win_rate }}%</strong>
                                                de lo que juega
                                            </p>
                                        @endif
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-2xl border border-dashed border-slate-800 px-4 py-4 text-center text-[11px] text-slate-500">
                        El podio es el de los tres primeros por puntos. Estás mirando ordenado por otra
                        cosa, así que se enseña solo la tabla.
                    </p>
                @endif


                {{-- Y detrás, el resto --}}
                @if ($ranking->count() > 3)
                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                        @foreach ($ranking->skip($sort === 'points' ? 3 : 0) as $fila)
                            @include('universes.ranking.partials.fila', [
                                'universe' => $universe,
                                'fila' => $fila,
                                'maximoPuntos' => $maximoPuntos,
                            ])
                        @endforeach
                    </div>
                @endif
            </section>


            {{-- ===================================================== --}}
            {{-- TABLA --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'table'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">#</th>
                                <th class="px-3 py-2.5">Competidor</th>
                                <th class="px-3 py-2.5 text-right">Puntos</th>
                                <th class="px-3 py-2.5 text-right">Títulos</th>
                                <th class="px-3 py-2.5 text-right">Jugadas</th>
                                <th class="px-3 py-2.5 text-right">G</th>
                                <th class="px-3 py-2.5 text-right">E</th>
                                <th class="px-3 py-2.5 text-right">P</th>
                                <th class="px-3 py-2.5 text-right">% victorias</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($ranking as $fila)
                                @php
                                    $competidor = $fila->entity;
                                    $tono = $medallas[$fila->position - 1] ?? '#94a3b8';
                                @endphp

                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2 font-mono text-[12px] font-black"
                                        style="color: {{ $fila->position <= 3 ? $tono : '#475569' }}">
                                        {{ $fila->position }}
                                    </td>

                                    <td class="px-3 py-2">
                                        <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                                            class="flex items-center gap-2">
                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                @if ($competidor?->image_url)
                                                    <img src="{{ $competidor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                                @endif
                                            </span>
                                            <span class="truncate text-[12px] font-black text-white">
                                                {{ $competidor?->display_label ?? 'Sin nombre' }}
                                            </span>
                                        </a>
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[13px] font-black text-violet-300">
                                        {{ $fila->points }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $fila->titles > 0 ? 'text-amber-300' : 'text-slate-700' }}">
                                        {{ $fila->titles }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400">
                                        {{ $fila->tournaments }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $fila->wins > 0 ? 'text-emerald-300' : 'text-slate-700' }}">
                                        {{ $fila->wins }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-500">{{ $fila->draws }}</td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $fila->losses > 0 ? 'text-rose-300' : 'text-slate-700' }}">
                                        {{ $fila->losses }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400">
                                        {{ $fila->win_rate === null ? '—' : $fila->win_rate . '%' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>


            {{-- ===================================================== --}}
            {{-- TARJETAS --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'cards'" x-cloak class="grid gap-2.5" :class="columnas">
                @foreach ($ranking as $fila)
                    @php
                        $competidor = $fila->entity;
                        $tono = $medallas[$fila->position - 1] ?? '#a78bfa';
                    @endphp

                    <article class="overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
                        style="border-color: {{ $fila->position <= 3 ? $tono . '66' : '#1e293b' }}">

                        <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                            class="relative block aspect-square overflow-hidden bg-slate-950">
                            @if ($competidor?->image_url)
                                <img src="{{ $competidor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-3xl text-slate-800">◍</span>
                            @endif

                            <span class="absolute left-1 top-1 flex h-7 w-7 items-center justify-center rounded-lg font-mono text-[12px] font-black"
                                style="background-color: {{ $fila->position <= 3 ? $tono : '#020617d9' }}; color: {{ $fila->position <= 3 ? '#020617' : '#94a3b8' }}">
                                {{ $fila->position }}
                            </span>

                            @if ($fila->titles > 0)
                                <span class="absolute right-1 top-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-amber-300">
                                    🏆{{ $fila->titles }}
                                </span>
                            @endif
                        </a>

                        <div class="p-2">
                            <p class="truncate text-[11px] font-black text-white">
                                {{ $competidor?->display_label ?? 'Sin nombre' }}
                            </p>

                            <p class="font-mono text-[14px] font-black text-violet-300">
                                {{ $fila->points }}
                                <span class="text-[8px] uppercase tracking-wider text-slate-600">pts</span>
                            </p>

                            <p class="font-mono text-[9px] text-slate-600">
                                {{ $fila->wins }}G · {{ $fila->draws }}E · {{ $fila->losses }}P
                            </p>
                        </div>
                    </article>
                @endforeach
            </section>


            {{-- ===================================================== --}}
            {{-- DE DÓNDE SALEN LOS PUNTOS --}}
            {{-- ===================================================== --}}

            {{--
                La vista que convierte una tabla de puntos en algo que se
                entiende. Cada barra se parte en los cinco conceptos que suman,
                con el mismo color que tienen en el sistema de puntos, y debajo
                la cuenta escrita.
            --}}

            <section x-show="vista === 'points'" x-cloak class="space-y-2">

                <p class="text-[10px] leading-relaxed text-slate-500">
                    De dónde sale el total de cada uno, con el sistema de puntos que tiene puesto este
                    universo ahora mismo. Cambiar los valores recalcula esto solo.
                </p>

                @foreach ($ranking as $fila)
                    @php
                        $competidor = $fila->entity;

                        $trozos = collect($conceptos)
                            ->map(
                                fn($c) => [
                                    'etiqueta' => $c[2],
                                    'tono' => $c[3],
                                    'cuantos' => (int) $fila->{$c[0]},
                                    'cada' => (int) ($settings[$c[1]] ?? 0),
                                    'suma' => (int) $fila->{$c[0]} * (int) ($settings[$c[1]] ?? 0),
                                ]
                            )
                            ->filter(fn($t) => $t['suma'] > 0)
                            ->values();

                        $total = max($fila->points, 1);
                    @endphp

                    <article class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">

                        <div class="flex flex-wrap items-center gap-2.5">

                            <span class="w-6 shrink-0 text-center font-mono text-[12px] font-black text-slate-600">
                                {{ $fila->position }}
                            </span>

                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($competidor?->image_url)
                                    <img src="{{ $competidor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                @endif
                            </span>

                            <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                                class="min-w-0 flex-1 truncate text-[12px] font-black text-white transition hover:underline">
                                {{ $competidor?->display_label ?? 'Sin nombre' }}
                            </a>

                            <span class="shrink-0 font-mono text-[15px] font-black text-violet-300">
                                {{ $fila->points }}
                            </span>
                        </div>

                        @if ($trozos->isNotEmpty())
                            <div class="mt-2 flex h-2.5 overflow-hidden rounded-full bg-slate-950">
                                @foreach ($trozos as $trozo)
                                    <span class="block h-full"
                                        style="width: {{ round($trozo['suma'] / $total * 100, 2) }}%; background-color: {{ $trozo['tono'] }}"
                                        title="{{ $trozo['etiqueta'] }}: {{ $trozo['suma'] }} puntos"></span>
                                @endforeach
                            </div>

                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                                @foreach ($trozos as $trozo)
                                    <span class="flex items-center gap-1 text-[10px]">
                                        <span class="h-2 w-2 rounded-sm" style="background-color: {{ $trozo['tono'] }}"></span>
                                        <span class="text-slate-500">{{ $trozo['etiqueta'] }}</span>
                                        <span class="font-mono text-slate-600">
                                            {{ $trozo['cuantos'] }}×{{ $trozo['cada'] }}
                                        </span>
                                        <span class="font-mono font-black" style="color: {{ $trozo['tono'] }}">
                                            {{ $trozo['suma'] }}
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-[10px] text-slate-600">
                                Cero puntos: con el sistema actual, nada de lo que ha hecho suma.
                            </p>
                        @endif
                    </article>
                @endforeach
            </section>

        @endif


        {{-- ===================================================== --}}
        {{-- LOS ÚLTIMOS CAMPEONES --}}
        {{-- ===================================================== --}}

        @if ($campeones->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-amber-500/25 bg-amber-500/5">

                <div class="border-b border-amber-500/20 px-4 py-2.5">
                    <h2 class="text-[13px] font-black text-white">Los últimos en ganar</h2>
                    <p class="text-[10px] text-amber-200/60">
                        Quién se llevó las competiciones más recientes. No es la clasificación: es lo último
                        que pasó.
                        {{--
                            Esta franja sale de todo el universo, no del filtro de arriba. Callarlo
                            haría creer que son los campeones de lo filtrado.
                        --}}
                        @if ($hayFiltros)
                            <strong class="text-amber-200">Esto es de todo el universo</strong>, sin los
                            filtros que tienes puestos.
                        @endif
                    </p>
                </div>

                <div class="space-y-2 p-3">
                    @foreach ($campeones as $ganadores)
                        @php
                            /* Todas las filas del grupo son de la misma competición */
                            $suEdicion = $ganadores->first()?->tournamentInstance;

                            /*
                             * Un torneo que se queda en grupos marca campeón a todo
                             * el que clasifica. Decirlo evita que parezca que hubo
                             * seis finales distintas.
                             */
                             $compartido = $ganadores->count() > 1;
                        @endphp

                        {{--
                            En fila: la competición a un lado y sus ganadores al
                            otro. Apilado, cada competición de un solo ganador
                            gastaba 195 px de alto para enseñar una cara.
                        --}}
                        <article class="flex flex-wrap items-start gap-3 rounded-xl border border-slate-800 bg-slate-950 p-2.5">

                            <div class="w-full shrink-0 sm:w-48">
                                <span class="block text-[11px] font-black leading-snug text-amber-200">
                                    {{ $suEdicion?->name ?: 'Competición sin nombre' }}
                                </span>

                                <span class="mt-1 flex flex-wrap items-center gap-1.5">
                                    @if ($suEdicion?->season)
                                        <span class="rounded bg-slate-900 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-400">
                                            T{{ $suEdicion->season->number }}
                                        </span>
                                    @endif

                                    @if ($suEdicion?->completed_at)
                                        <span class="font-mono text-[9px] text-slate-600">
                                            {{ $suEdicion->completed_at->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </span>

                                @if ($compartido)
                                    <span class="mt-1 block text-[9px] leading-3 text-slate-600">
                                        {{ $ganadores->count() }} ganadores: no hubo una final, se llevaron
                                        el título todos los que clasificaron.
                                    </span>
                                @endif
                            </div>

                            <div class="grid min-w-0 flex-1 grid-cols-3 gap-2 sm:grid-cols-6 lg:grid-cols-9">
                                @foreach ($ganadores as $campeon)
                                    @php
                                        $suEntidad = $campeon->universeEntity;

                                        /* Si no queda entidad a la que ir, no se finge un enlace */
                                        $etiqueta = $suEntidad ? 'a' : 'span';
                                    @endphp

                                    <{{ $etiqueta }}
                                        @if ($suEntidad) href="{{ route('universes.entities.show', [$universe, $suEntidad]) }}" @endif
                                        class="group block overflow-hidden rounded-lg border border-slate-800 bg-slate-900 transition {{ $suEntidad ? 'hover:-translate-y-0.5 hover:border-amber-500/50' : 'opacity-60' }}">

                                        <span class="relative block h-20 overflow-hidden bg-slate-950">
                                            @if ($suEntidad?->image_url)
                                                <img src="{{ $suEntidad->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-xl text-slate-800">◍</span>
                                            @endif

                                            <span class="absolute right-1 top-1 text-sm">🏆</span>
                                        </span>

                                        <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300">
                                            {{ $suEntidad?->display_label ?? 'Ya no está aquí' }}
                                        </span>
                                    </{{ $etiqueta }}>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>

            </section>
        @endif

    </div>


    <script>
        function clasificacionDelUniverso() {

            return {

                vista: 'podio',
                tamano: 6,
                abrirPuntos: {{ $errors->any() ? 'true' : 'false' }},

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.ranking.view') ?? '{}');
                        if (['podio', 'table', 'cards', 'points'].includes(g.vista)) this.vista = g.vista;
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.ranking.view',
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
            };
        }
    </script>

</x-universe-layout>

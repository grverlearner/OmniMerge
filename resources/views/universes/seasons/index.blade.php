@php
    /*
     * Las temporadas de un universo.
     *
     * Una temporada es un tramo de tiempo del mundo. Los torneos dicen cada
     * cuánto se juegan —cada temporada, cada tres, una sola vez— y esa
     * recurrencia no se veía en ninguna parte: se configuraba y desaparecía.
     *
     * Ahora el **calendario** la enseña: qué torneo toca en cada temporada, y
     * qué tocaría en las que todavía no existen. Y se pueden crear diez de
     * golpe, porque un mundo con historia necesita diez, no una.
     */

    $tonoEstado = [
        'ACTIVE' => ['bg-emerald-500/15 text-emerald-300', '#34d399', 'En curso'],
        'PLANNED' => ['bg-slate-800 text-slate-400', '#64748b', 'Planeada'],
        'COMPLETED' => ['bg-cyan-500/15 text-cyan-300', '#22d3ee', 'Terminada'],
        'ARCHIVED' => ['bg-slate-800 text-slate-600', '#475569', 'Archivada'],
    ];

    $hayFiltros = $search || $status || $played;

    $puedeEditar = auth()->user()?->can('update', $universe) ?? false;
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Temporadas</x-slot>

    <div x-data="temporadasDelUniverso({
        siguienteNumero: {{ ($seasons->max('number') ?? 0) + 1 }},
        hayTorneos: @js($torneos->isNotEmpty()),
    })" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Temporadas
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    El tiempo de este mundo
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Cada temporada es un tramo. Los torneos se reparten entre ellas según la recurrencia
                    que tengan configurada, y las competiciones se juegan dentro de una.
                </p>
            </div>

            @if ($puedeEditar)
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="abrirLote = true"
                        class="flex items-center gap-1.5 rounded-xl border border-cyan-500/40 bg-cyan-500/10 px-3 py-2 text-[11px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-white">
                        <x-omni-icon name="capas" size="h-3.5 w-3.5" />
                        Crear varias
                    </button>

                    <a href="{{ route('universes.seasons.create', $universe) }}"
                        class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                        Nueva temporada
                    </a>
                </div>
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
        {{-- LA QUE ESTÁ EN CURSO --}}
        {{-- ===================================================== --}}

        @if ($activeSeason)
            <section class="overflow-hidden rounded-2xl border border-emerald-500/40 bg-emerald-500/5">
                <div class="flex flex-wrap items-center gap-3 p-4">

                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-emerald-500/40 bg-emerald-500/10 font-mono text-lg font-black text-emerald-300">
                        {{ $activeSeason->number }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-emerald-400/70">
                            Ahora mismo
                        </p>
                        <a href="{{ route('universes.seasons.show', [$universe, $activeSeason]) }}"
                            class="block truncate text-[16px] font-black text-white transition hover:underline">
                            {{ $activeSeason->name }}
                        </a>
                        <p class="truncate text-[10px] text-slate-500">{{ $activeSeason->period_label }}</p>
                    </div>

                    <a href="{{ route('universes.seasons.show', [$universe, $activeSeason]) }}"
                        class="shrink-0 rounded-xl border border-emerald-500/40 px-3 py-2 text-[11px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-white">
                        Ver qué pasa en ella →
                    </a>
                </div>
            </section>
        @elseif ($statistics['total'] > 0)
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-500/25 bg-amber-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                    <x-omni-icon name="calendario" size="h-3.5 w-3.5" />
                </span>
                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-amber-200/80">
                    <strong class="text-amber-200">Ninguna temporada está en curso.</strong>
                    Mientras no haya una activa, las competiciones nuevas no saben a qué tramo del mundo
                    pertenecen. Activa una desde su ficha o desde la lista.
                </p>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach ([['Temporadas', $statistics['total'], null, '#a78bfa'], ['Planeadas', $statistics['planned'], ['status' => 'PLANNED'], '#94a3b8'], ['Terminadas', $statistics['completed'], ['status' => 'COMPLETED'], '#22d3ee'], ['Sin competiciones', $statistics['sin_jugar'], ['played' => 'no'], '#fb7185'], ['Competiciones', $statistics['competiciones'], null, '#fbbf24']] as [$etiqueta, $valor, $filtro, $tono])

                @if ($filtro)
                    <a href="{{ route('universes.seasons.index', array_merge(['universe' => $universe], $filtro)) }}"
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


        {{-- ===================================================== --}}
        {{-- CREAR VARIAS DE GOLPE --}}
        {{-- ===================================================== --}}

        @if ($puedeEditar)
            @include('universes.seasons.partials.crear-en-lote', ['universe' => $universe])
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('universes.seasons.index', $universe) }}"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar temporada</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar temporada…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <select name="status" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Cualquier estado', 'ACTIVE' => 'En curso', 'PLANNED' => 'Planeadas', 'COMPLETED' => 'Terminadas', 'ARCHIVED' => 'Archivadas'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($status === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="played" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Con o sin competiciones', 'yes' => 'Con competiciones', 'no' => 'Sin ninguna'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($played === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['number_desc' => 'De la última a la primera', 'number_asc' => 'De la primera a la última', 'competitions' => 'Las más jugadas'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Buscar
                    </button>

                    @if ($hayFiltros)
                        <a href="{{ route('universes.seasons.index', $universe) }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>


                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['calendar', 'calendario', 'Calendario: qué torneo toca en cada temporada'], ['timeline', 'historial', 'Línea de tiempo: el mundo en orden'], ['grid', 'cuadricula', 'Cuadrícula: la ficha de cada una'], ['gallery', 'galeria', 'Galería: solo los números'], ['list', 'menu', 'Lista: una línea cada una'], ['table', 'controles', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
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


        {{-- ===================================================== --}}
        {{-- CALENDARIO --}}
        {{-- ===================================================== --}}

        {{--
            La vista que faltaba. Al configurar un torneo se dice cada cuánto
            toca; hasta ahora eso se guardaba y no se veía. Aquí se ve, y además
            se proyectan cuatro temporadas por delante: saber qué tocaría en la
            11 es lo que dice si merece la pena crearla.
        --}}

        <section x-show="vista === 'calendar'" class="space-y-2">

            <p class="text-[10px] leading-relaxed text-slate-500">
                Qué torneo toca en cada temporada, según la recurrencia que tenga configurada cada uno.
                Las cuatro últimas todavía no existen: es lo que pasaría si las crearas.
            </p>

            @if ($torneos->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-800 py-10 text-center">
                    <p class="text-[11px] leading-relaxed text-slate-600">
                        Este universo no tiene torneos, así que no hay nada que repartir entre temporadas.
                    </p>

                    @if ($seasons->isNotEmpty())
                        <button type="button" @click="vista = 'grid'"
                            class="mt-3 rounded-xl border border-violet-500/40 px-3 py-1.5 text-[11px] font-black text-violet-300 transition hover:bg-violet-500/10">
                            Ver las {{ $seasons->total() }} temporadas y ponerlas en curso
                        </button>
                    @endif
                </div>
            @else
                @foreach ($calendario as $fila)
                    <div class="flex flex-wrap items-center gap-3 rounded-2xl border p-3 {{ $fila['existe'] ? 'border-slate-800 bg-slate-900/50' : 'border-dashed border-slate-800/60 bg-slate-900/20' }}">

                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border font-mono text-[13px] font-black {{ $fila['existe'] ? 'border-violet-500/40 bg-violet-500/10 text-violet-300' : 'border-slate-800 text-slate-600' }}">
                            {{ $fila['numero'] }}
                        </span>

                        <div class="min-w-0 flex-1">
                            @if (! $fila['existe'])
                                <p class="text-[11px] font-black text-slate-600">Temporada {{ $fila['numero'] }}</p>
                                <p class="text-[9px] text-slate-700">todavía no existe</p>
                            @else
                                @php $laTemporada = $seasons->firstWhere('number', $fila['numero']); @endphp

                                @if ($laTemporada)
                                    <a href="{{ route('universes.seasons.show', [$universe, $laTemporada]) }}"
                                        class="block truncate text-[12px] font-black text-white transition hover:underline">
                                        {{ $laTemporada->name }}
                                    </a>
                                    <p class="truncate text-[9px] text-slate-600">{{ $laTemporada->period_label }}</p>
                                @else
                                    <p class="text-[12px] font-black text-slate-400">Temporada {{ $fila['numero'] }}</p>
                                    <p class="text-[9px] text-slate-600">en otra página de la lista</p>
                                @endif
                            @endif
                        </div>

                        @if ($fila['torneos']->isEmpty())
                            <span class="shrink-0 text-[10px] text-slate-700">no toca ningún torneo</span>
                        @else
                            <div class="flex min-w-0 flex-wrap items-center justify-end gap-1.5">
                                @foreach ($fila['torneos'] as $torneo)
                                    <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                        title="{{ $torneo->name }} · {{ $torneo->recurrence_label }}"
                                        class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-2 transition hover:border-violet-500">

                                        <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                            @if ($torneo->image_url)
                                                <img src="{{ $torneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[10px]">🏆</span>
                                            @endif
                                        </span>

                                        <span class="max-w-[150px] truncate text-[10px] font-black {{ $fila['existe'] ? 'text-slate-300' : 'text-slate-500' }}">
                                            {{ $torneo->name }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($manuales->isNotEmpty())
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/30 p-3">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Fuera del calendario
                        </p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                            {{ $manuales->count() }}
                            {{ $manuales->count() === 1 ? 'torneo se lanza a mano' : 'torneos se lanzan a mano' }}:
                            no tienen recurrencia, así que nunca se anuncian en una temporada concreta.
                        </p>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($manuales as $torneo)
                                <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                    class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-2 transition hover:border-slate-600">
                                    <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                        @if ($torneo->image_url)
                                            <img src="{{ $torneo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-[10px]">🏆</span>
                                        @endif
                                    </span>
                                    <span class="max-w-[150px] truncate text-[10px] font-black text-slate-500">
                                        {{ $torneo->name }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </section>


        @if ($seasons->isEmpty())

            <section x-show="vista !== 'calendar'" x-cloak
                class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

                <span class="inline-flex text-slate-700"><x-omni-icon name="calendario" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">
                    {{ $hayFiltros ? 'Ninguna temporada encaja con lo que has filtrado' : 'Este universo no tiene temporadas' }}
                </p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Prueba a quitar algún filtro.
                    @else
                        Las temporadas son los tramos de tiempo del mundo. Los torneos se reparten entre
                        ellas y las competiciones se juegan dentro de una.
                    @endif
                </p>

                @if ($puedeEditar && ! $hayFiltros)
                    <button type="button" @click="abrirLote = true"
                        class="mt-3 rounded-xl bg-cyan-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-cyan-400">
                        Crear varias de golpe
                    </button>
                @endif
            </section>

        @else

            {{-- ---------- LÍNEA DE TIEMPO ---------- --}}

            <section x-show="vista === 'timeline'" x-cloak class="relative pl-6">

                <span class="absolute inset-y-2 left-[13px] w-px bg-slate-800"></span>

                <div class="space-y-2.5">
                    @foreach ($seasons as $temporada)
                        @php [$clase, $tono, $etiqueta] = $tonoEstado[$temporada->status] ?? $tonoEstado['PLANNED']; @endphp

                        <div class="relative">
                            <span class="absolute -left-[17px] top-4 h-3 w-3 rounded-full border-2 border-slate-950"
                                style="background-color: {{ $tono }}"></span>

                            <article class="flex flex-wrap items-center gap-3 rounded-2xl border bg-slate-900/50 p-3"
                                style="border-color: {{ $temporada->status === 'ACTIVE' ? $tono : '#1e293b' }}">

                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border font-mono text-[13px] font-black"
                                    style="border-color: {{ $tono }}55; color: {{ $tono }}">
                                    {{ $temporada->number }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
                                            class="truncate text-[13px] font-black text-white transition hover:underline">
                                            {{ $temporada->name }}
                                        </a>
                                        <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $clase }}">
                                            {{ $etiqueta }}
                                        </span>
                                    </div>
                                    <p class="truncate text-[10px] text-slate-500">{{ $temporada->period_label }}</p>
                                </div>

                                <span class="shrink-0 text-right">
                                    <span class="block font-mono text-[13px] font-black {{ $temporada->competitions_count > 0 ? 'text-amber-300' : 'text-slate-700' }}">
                                        {{ $temporada->competitions_count }}
                                    </span>
                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                        competiciones
                                    </span>
                                </span>

                                @include('universes.seasons.partials.acciones', [
                                    'universe' => $universe,
                                    'temporada' => $temporada,
                                    'puedeEditar' => $puedeEditar,
                                ])
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>


            {{-- ---------- CUADRÍCULA ---------- --}}

            <section x-show="vista === 'grid'" x-cloak class="grid gap-2.5" :class="columnas">
                @foreach ($seasons as $temporada)
                    @php
                        [$clase, $tono, $etiqueta] = $tonoEstado[$temporada->status] ?? $tonoEstado['PLANNED'];
                        $tocan = $torneos->filter(fn($t) => $t->occursInSeason($temporada->number));
                    @endphp

                    <article class="overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
                        style="border-color: {{ $temporada->status === 'ACTIVE' ? $tono : $tono . '33' }}">

                        <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
                            class="relative block aspect-[16/10] overflow-hidden"
                            style="background: radial-gradient(120% 120% at 50% 0%, {{ $tono }}22, #020617 70%)">

                            <span class="flex h-full w-full items-center justify-center font-mono text-5xl font-black"
                                style="color: {{ $tono }}66">{{ $temporada->number }}</span>

                            <span class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $clase }}">
                                {{ $etiqueta }}
                            </span>
                        </a>

                        <div class="p-2.5">
                            <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
                                class="block truncate text-[12px] font-black text-white">{{ $temporada->name }}</a>

                            <p class="truncate text-[9px] text-slate-600">{{ $temporada->period_label }}</p>

                            <div class="mt-1.5 flex items-center gap-1.5">
                                <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] {{ $temporada->competitions_count > 0 ? 'text-amber-300' : 'text-slate-700' }}"
                                    title="Competiciones jugadas en esta temporada">
                                    {{ $temporada->competitions_count }} jug.
                                </span>

                                @if ($tocan->isNotEmpty())
                                    <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-violet-300"
                                        title="{{ $tocan->pluck('name')->implode(', ') }}">
                                        {{ $tocan->count() }} tocan
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">
                            @include('universes.seasons.partials.acciones', [
                                'universe' => $universe,
                                'temporada' => $temporada,
                                'puedeEditar' => $puedeEditar,
                            ])
                        </div>
                    </article>
                @endforeach
            </section>


            {{-- ---------- GALERÍA ---------- --}}

            <section x-show="vista === 'gallery'" x-cloak class="grid gap-2" :class="columnas">
                @foreach ($seasons as $temporada)
                    @php [$clase, $tono, $etiqueta] = $tonoEstado[$temporada->status] ?? $tonoEstado['PLANNED']; @endphp

                    <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
                        title="{{ $temporada->name }} · {{ $etiqueta }}"
                        class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                        style="border-color: {{ $temporada->status === 'ACTIVE' ? $tono : $tono . '33' }}">

                        <span class="flex aspect-square items-center justify-center font-mono text-3xl font-black"
                            style="color: {{ $tono }}88; background: radial-gradient(120% 120% at 50% 0%, {{ $tono }}22, transparent 70%)">
                            {{ $temporada->number }}
                        </span>

                        <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                            {{ $temporada->name }}
                        </span>
                        <span class="block truncate px-1.5 pb-1 text-center text-[9px]" style="color: {{ $tono }}">
                            {{ $etiqueta }}
                        </span>
                    </a>
                @endforeach
            </section>


            {{-- ---------- LISTA ---------- --}}

            <section x-show="vista === 'list'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                @foreach ($seasons as $temporada)
                    @php [$clase, $tono, $etiqueta] = $tonoEstado[$temporada->status] ?? $tonoEstado['PLANNED']; @endphp

                    <div class="flex items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border font-mono text-[12px] font-black"
                            style="border-color: {{ $tono }}55; color: {{ $tono }}">
                            {{ $temporada->number }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
                                    class="truncate text-[12px] font-black text-white">{{ $temporada->name }}</a>
                                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $clase }}">
                                    {{ $etiqueta }}
                                </span>
                            </div>
                            <p class="truncate text-[10px] text-slate-500">{{ $temporada->period_label }}</p>
                        </div>

                        <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[10px] {{ $temporada->competitions_count > 0 ? 'text-amber-300' : 'text-slate-700' }}"
                            title="Competiciones · terminadas">
                            {{ $temporada->completadas_count }}/{{ $temporada->competitions_count }}
                        </span>

                        @include('universes.seasons.partials.acciones', [
                            'universe' => $universe,
                            'temporada' => $temporada,
                            'puedeEditar' => $puedeEditar,
                        ])
                    </div>
                @endforeach
            </section>


            {{-- ---------- TABLA ---------- --}}

            <section x-show="vista === 'table'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Nº</th>
                                <th class="px-3 py-2.5">Temporada</th>
                                <th class="px-3 py-2.5">Empieza</th>
                                <th class="px-3 py-2.5">Termina</th>
                                <th class="px-3 py-2.5 text-right">Competiciones</th>
                                <th class="px-3 py-2.5 text-right">Terminadas</th>
                                <th class="px-3 py-2.5 text-right">Toca</th>
                                <th class="px-3 py-2.5">Estado</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($seasons as $temporada)
                                @php
                                    [$clase, $tono, $etiqueta] = $tonoEstado[$temporada->status] ?? $tonoEstado['PLANNED'];
                                    $tocan = $torneos->filter(fn($t) => $t->occursInSeason($temporada->number));
                                @endphp

                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2 font-mono text-[12px] font-black" style="color: {{ $tono }}">
                                        {{ $temporada->number }}
                                    </td>

                                    <td class="px-3 py-2">
                                        <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
                                            class="truncate text-[12px] font-black text-white transition hover:underline">
                                            {{ $temporada->name }}
                                        </a>
                                    </td>

                                    <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                        {{ $temporada->starts_at?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                        {{ $temporada->ends_at?->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $temporada->competitions_count > 0 ? 'text-slate-300' : 'text-slate-700' }}">
                                        {{ $temporada->competitions_count }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $temporada->completadas_count > 0 ? 'text-cyan-300' : 'text-slate-700' }}">
                                        {{ $temporada->completadas_count }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $tocan->isNotEmpty() ? 'text-violet-300' : 'text-slate-700' }}"
                                        title="{{ $tocan->pluck('name')->implode(', ') ?: 'No toca ningún torneo' }}">
                                        {{ $tocan->count() }}
                                    </td>

                                    <td class="px-3 py-2">
                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $clase }}">
                                            {{ $etiqueta }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>


            <div x-show="vista !== 'calendar'" x-cloak>{{ $seasons->links() }}</div>

        @endif

    </div>


    <script>
        function temporadasDelUniverso(config) {

            return {

                /*
                 * Sin torneos el calendario no tiene nada que enseñar, y
                 * abrir en él escondía las temporadas justo cuando el aviso
                 * de arriba pide activar una.
                 */
                vista: config.hayTorneos ? 'calendar' : 'grid',
                tamano: 6,
                abrirLote: false,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.seasons.view') ?? '{}');
                        if (['calendar', 'timeline', 'grid', 'gallery', 'list', 'table'].includes(g.vista)
                            && ! (g.vista === 'calendar' && ! config.hayTorneos)) {
                            this.vista = g.vista;
                        }
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.seasons.view',
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

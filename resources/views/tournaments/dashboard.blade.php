@php
    /*
     * El taller de torneos.
     *
     * Un panel que solo cuenta cuántas cosas hay no ayuda a decidir qué hacer
     * a continuación. Este responde a cuatro preguntas, en este orden:
     *
     *   qué falta        lo que está a medio terminar y no se puede jugar
     *   qué está vivo    las competiciones reales montadas sobre tus plantillas
     *   qué tocaste      por dónde ibas la última vez
     *   de qué va todo   el reparto por motor y por tipo, para ver los huecos
     *
     * Las cifras van arriba porque se leen de un vistazo, pero no son lo
     * importante: lo importante es el bloque de «qué falta», que es el único
     * que dice en qué trabajar.
     */

    /*
     * Las clases van literales, nunca compuestas: Tailwind lee este archivo
     * y una clase armada con 'border-' . $color no existiría en el CSS.
     */
    $tonos = [
        'amber' => ['borde' => 'border-amber-500/40', 'texto' => 'text-amber-300', 'fondo' => 'bg-amber-500/10', 'barra' => 'bg-amber-400'],
        'violet' => ['borde' => 'border-violet-500/40', 'texto' => 'text-violet-300', 'fondo' => 'bg-violet-500/10', 'barra' => 'bg-violet-400'],
        'cyan' => ['borde' => 'border-cyan-500/40', 'texto' => 'text-cyan-300', 'fondo' => 'bg-cyan-500/10', 'barra' => 'bg-cyan-400'],
        'emerald' => ['borde' => 'border-emerald-500/40', 'texto' => 'text-emerald-300', 'fondo' => 'bg-emerald-500/10', 'barra' => 'bg-emerald-400'],
        'rose' => ['borde' => 'border-rose-500/40', 'texto' => 'text-rose-300', 'fondo' => 'bg-rose-500/10', 'barra' => 'bg-rose-400'],
        'sky' => ['borde' => 'border-sky-500/40', 'texto' => 'text-sky-300', 'fondo' => 'bg-sky-500/10', 'barra' => 'bg-sky-400'],
        'slate' => ['borde' => 'border-slate-700', 'texto' => 'text-slate-300', 'fondo' => 'bg-slate-800/60', 'barra' => 'bg-slate-500'],
    ];

    $gravedad = [
        'error' => ['punto' => 'bg-rose-400', 'texto' => 'text-rose-300', 'borde' => 'border-rose-500/30', 'fondo' => 'bg-rose-500/5'],
        'warning' => ['punto' => 'bg-amber-400', 'texto' => 'text-amber-300', 'borde' => 'border-amber-500/30', 'fondo' => 'bg-amber-500/5'],
        'info' => ['punto' => 'bg-sky-400', 'texto' => 'text-sky-300', 'borde' => 'border-sky-500/25', 'fondo' => 'bg-sky-500/5'],
    ];

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'DRAFT' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
        'RUNNING' => 'bg-emerald-500/15 text-emerald-300',
        'COMPLETED' => 'bg-slate-800 text-slate-400',
    ];

    /* Cuántas cosas hay que arreglar, y de qué gravedad la peor */
    $pendientes = collect($pending);

    $peorGravedad = $pendientes->contains('severity', 'error')
        ? 'error'
        : ($pendientes->contains('severity', 'warning')
            ? 'warning'
            : 'info');

    $cuantasFaltan = (int) $pendientes->sum('count');

    $sinTerminar = $statistics['tournaments'] + $statistics['phases'] === 0;
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">Taller de torneos</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- LA PORTADA --}}
        {{-- ===================================================== --}}

        <section
            class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-amber-950/40">

            {{-- Un poco de luz al fondo, para que no sea un rectángulo plano --}}
            <span class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"></span>
            <span class="pointer-events-none absolute -bottom-32 left-1/3 h-72 w-72 rounded-full bg-violet-500/10 blur-3xl"></span>

            <div class="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-center">

                <div class="min-w-0">

                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">
                        OmniMerge · Torneos
                    </p>

                    <h1 class="mt-2 text-3xl font-black leading-tight tracking-tight text-white sm:text-4xl">
                        Construye el lenguaje<br class="hidden sm:block">
                        de tus competiciones.
                    </h1>

                    <p class="mt-3 max-w-xl text-[13px] leading-relaxed text-slate-400">
                        Una <strong class="font-black text-slate-200">fase</strong> define qué ocurre
                        dentro de una etapa. Un <strong class="font-black text-slate-200">torneo</strong>
                        las encadena en un recorrido: por dónde entra la gente, qué atraviesa y en qué
                        finales acaba. Después, cada universo lo juega tantas veces como quiera.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">

                        @can('create', App\Models\TournamentTemplate::class)
                            <a href="{{ route('tournaments.templates.create') }}"
                                class="flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-slate-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                                <x-omni-icon name="trofeo" size="h-4 w-4" />
                                Nuevo torneo
                            </a>
                        @endcan

                        @can('create', App\Models\PhaseTemplate::class)
                            <a href="{{ route('tournaments.phase-templates.create') }}"
                                class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-amber-500/60 hover:text-amber-300">
                                <x-omni-icon name="grafo" size="h-4 w-4" />
                                Nueva fase
                            </a>
                        @endcan

                        <a href="{{ route('tournaments.lab.index') }}"
                            class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-violet-500/60 hover:text-violet-300">
                            <x-omni-icon name="matraz" size="h-4 w-4" />
                            Laboratorio
                        </a>

                    </div>

                </div>


                {{-- El estado del taller, en una frase --}}

                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">

                    @if ($sinTerminar)
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Empezar</p>

                        <p class="mt-2 text-[13px] leading-relaxed text-slate-300">
                            El taller está vacío. Lo habitual es empezar por una
                            <strong class="text-amber-300">fase</strong> —una etapa suelta— y después
                            encadenarla en un torneo.
                        </p>
                    @elseif ($cuantasFaltan > 0)
                        <p class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wider {{ $gravedad[$peorGravedad]['texto'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $gravedad[$peorGravedad]['punto'] }}"></span>
                            Hay cosas a medias
                        </p>

                        <p class="mt-2 text-[13px] leading-relaxed text-slate-300">
                            <strong class="text-white">{{ $cuantasFaltan }}</strong>
                            {{ $cuantasFaltan === 1 ? 'pieza está' : 'piezas están' }} sin terminar.
                            Están todas listadas abajo, con el enlace para ir a arreglarlas.
                        </p>
                    @else
                        <p class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wider text-emerald-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            Todo en orden
                        </p>

                        <p class="mt-2 text-[13px] leading-relaxed text-slate-300">
                            Ninguna plantilla se ha quedado a medias. Todo lo que hay se puede jugar.
                        </p>
                    @endif

                    @if ($statistics['running'] > 0)
                        <p class="mt-3 flex items-center gap-2 border-t border-slate-800 pt-3 text-[11px] text-slate-400">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                            </span>

                            <strong class="font-black text-emerald-300">{{ $statistics['running'] }}</strong>
                            {{ $statistics['running'] === 1 ? 'competición en curso' : 'competiciones en curso' }}
                            con tus plantillas
                        </p>
                    @endif

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- LAS CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8">

            @foreach ([['Torneos', $statistics['tournaments'], 'text-white', 'trofeo', route('tournaments.templates.index')], ['Activos', $statistics['active_tournaments'], 'text-emerald-300', 'cuadricula', route('tournaments.templates.index', ['status' => 'ACTIVE'])], ['Fases', $statistics['phases'], 'text-amber-300', 'grafo', route('tournaments.phase-templates.index')], ['Fases activas', $statistics['active_phases'], 'text-emerald-300', 'capas', route('tournaments.phase-templates.index', ['status' => 'ACTIVE'])], ['Salidas', $statistics['phase_exits'], 'text-violet-300', 'flecha-derecha', route('tournaments.phase-templates.index', ['sort' => 'exits_desc'])], ['Entradas', $statistics['phase_gates'], 'text-cyan-300', 'flecha-izquierda', route('tournaments.phase-templates.index')], ['Públicas', $statistics['public'], 'text-sky-300', 'globo', route('tournaments.templates.index', ['visibility' => 'PUBLIC'])], ['Competiciones', $statistics['competitions'], 'text-rose-300', 'espadas', route('universes.dashboard')]] as [$etiqueta, $valor, $color, $icono, $enlace])
                <a href="{{ $enlace }}"
                    class="group rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2.5 transition hover:border-slate-700 hover:bg-slate-900">

                    <span class="flex items-center justify-between">
                        <span class="font-mono text-xl font-black {{ $color }}">{{ $valor }}</span>

                        <span class="text-slate-700 transition group-hover:text-slate-500">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </span>
                    </span>

                    <span class="mt-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </a>
            @endforeach

        </section>


        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px] xl:items-start">

            {{-- ============================================================= --}}
            {{-- COLUMNA PRINCIPAL --}}
            {{-- ============================================================= --}}

            <div class="space-y-4">

                {{-- ===================================================== --}}
                {{-- QUÉ FALTA POR TERMINAR --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg {{ $cuantasFaltan > 0 ? $gravedad[$peorGravedad]['fondo'] . ' ' . $gravedad[$peorGravedad]['texto'] : 'bg-emerald-500/15 text-emerald-300' }}">
                            <x-omni-icon name="controles" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">Qué falta por terminar</h2>
                            <p class="text-[10px] text-slate-500">
                                Lo que impide jugar, y lo que se ha quedado suelto.
                            </p>
                        </div>

                        @if ($cuantasFaltan > 0)
                            <span
                                class="rounded-lg border {{ $gravedad[$peorGravedad]['borde'] }} px-2 py-1 font-mono text-[11px] font-black {{ $gravedad[$peorGravedad]['texto'] }}">
                                {{ $cuantasFaltan }}
                            </span>
                        @endif
                    </header>

                    @if ($pendientes->isEmpty())

                        <div class="px-5 py-10 text-center">
                            <span class="inline-flex text-emerald-400/60">
                                <x-omni-icon name="medalla" size="h-9 w-9" />
                            </span>

                            <p class="mt-2 text-sm font-black text-white">Nada pendiente</p>

                            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                                Todos los torneos tienen entrada, fases y finales, y ninguna fase se
                                ha quedado sin salidas. Se puede jugar todo lo que hay.
                            </p>
                        </div>

                    @else

                        <ul class="divide-y divide-slate-800/70">
                            @foreach ($pendientes as $linea)
                                @php $g = $gravedad[$linea['severity']]; @endphp

                                <li>
                                    <a href="{{ $linea['url'] }}"
                                        class="group flex items-center gap-3 px-5 py-3 transition hover:bg-slate-900">

                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $g['punto'] }}"></span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block text-[12px] font-black text-slate-200">
                                                {{ $linea['title'] }}
                                            </span>

                                            <span class="block text-[10px] leading-4 text-slate-500">
                                                {{ $linea['detail'] }}
                                            </span>
                                        </span>

                                        <span
                                            class="shrink-0 rounded-lg border {{ $g['borde'] }} {{ $g['fondo'] }} px-2 py-1 font-mono text-[12px] font-black {{ $g['texto'] }}">
                                            {{ $linea['count'] }}
                                        </span>

                                        <span
                                            class="hidden shrink-0 text-[10px] font-black text-slate-600 transition group-hover:text-amber-300 sm:block">
                                            {{ $linea['action'] }} →
                                        </span>

                                    </a>
                                </li>
                            @endforeach
                        </ul>

                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- QUÉ ESTÁ VIVO --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                            <x-omni-icon name="espadas" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">Tus plantillas, en juego</h2>
                            <p class="text-[10px] text-slate-500">
                                Competiciones reales montadas sobre lo que has diseñado.
                            </p>
                        </div>

                        <a href="{{ route('universes.dashboard') }}"
                            class="shrink-0 text-[10px] font-black text-slate-500 transition hover:text-rose-300">
                            Universos →
                        </a>
                    </header>

                    @if ($live['competitions']->isEmpty())

                        <div class="px-5 py-10 text-center">
                            <span class="inline-flex text-slate-700">
                                <x-omni-icon name="orbita" size="h-9 w-9" />
                            </span>

                            <p class="mt-2 text-sm font-black text-white">Todavía no se ha jugado nada</p>

                            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                                Una plantilla se juega desde un universo: allí se elige a quién entra,
                                qué se reparte y cuándo empieza. Aquí solo se diseña.
                            </p>

                            <a href="{{ route('universes.dashboard') }}"
                                class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-rose-500/60 hover:text-rose-300">
                                Ir a Universos
                            </a>
                        </div>

                    @else

                        <ul class="divide-y divide-slate-800/70">
                            @foreach ($live['competitions'] as $competicion)
                                <li>
                                    <a href="{{ route('universes.competitions.show', [$competicion->universe_id, $competicion]) }}"
                                        class="group flex items-center gap-3 px-5 py-3 transition hover:bg-slate-900">

                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($competicion->image)
                                                <img src="{{ asset('storage/' . $competicion->image) }}" alt=""
                                                    loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-sm opacity-50">
                                                    {{ $competicion->tournamentTemplate?->display_icon ?? '🏆' }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[12px] font-black text-slate-200">
                                                {{ $competicion->name }}
                                            </span>

                                            <span class="block truncate text-[10px] text-slate-500">
                                                {{ $competicion->universe?->name ?? 'Universo' }}
                                                ·
                                                {{ $competicion->tournamentTemplate?->name ?? 'Plantilla retirada' }}
                                            </span>
                                        </span>

                                        @if ($competicion->participant_count)
                                            <span class="hidden shrink-0 font-mono text-[11px] text-slate-500 sm:block">
                                                {{ $competicion->participant_count }}
                                            </span>
                                        @endif

                                        <span
                                            class="shrink-0 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$competicion->status] ?? 'bg-slate-800 text-slate-500' }}">
                                            @if ($competicion->status === 'RUNNING')
                                                <span class="mr-1 inline-block h-1 w-1 animate-pulse rounded-full bg-emerald-400"></span>
                                            @endif
                                            {{ $competicion->status }}
                                        </span>

                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <p class="border-t border-slate-800 px-5 py-2.5 text-[10px] text-slate-600">
                            {{ $live['universe_tournaments'] }}
                            {{ $live['universe_tournaments'] === 1 ? 'torneo de universo usa' : 'torneos de universo usan' }}
                            tus plantillas · {{ $statistics['competitions'] }} competiciones montadas en total
                        </p>

                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- POR DÓNDE IBAS --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/15 text-sky-300">
                            <x-omni-icon name="historial" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">Por dónde ibas</h2>
                            <p class="text-[10px] text-slate-500">
                                Lo último que tocaste, torneos y fases mezclados.
                            </p>
                        </div>
                    </header>

                    @if ($recent->isEmpty())

                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-black text-white">Nada todavía</p>
                            <p class="mt-1 text-[11px] text-slate-500">
                                En cuanto crees algo aparecerá aquí para poder continuar.
                            </p>
                        </div>

                    @else

                        <div class="grid gap-2 p-3 sm:grid-cols-2">
                            @foreach ($recent as $pieza)
                                @php $t = $tonos[$pieza['accent']] ?? $tonos['slate']; @endphp

                                <a href="{{ $pieza['url'] }}"
                                    class="group flex items-center gap-3 rounded-xl border {{ $t['borde'] }} bg-slate-950/60 p-2.5 transition hover:bg-slate-900">

                                    <span
                                        class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                        @if ($pieza['image'])
                                            <img src="{{ $pieza['image'] }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition group-hover:scale-110">
                                        @else
                                            <span class="text-base opacity-50">{{ $pieza['icon'] }}</span>
                                        @endif
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center gap-1.5">
                                            <span
                                                class="rounded px-1 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $t['fondo'] }} {{ $t['texto'] }}">
                                                {{ $pieza['kind'] }}
                                            </span>

                                            <span class="font-mono text-[9px] text-slate-600">{{ $pieza['code'] }}</span>
                                        </span>

                                        <span class="mt-0.5 block truncate text-[12px] font-black text-slate-200">
                                            {{ $pieza['name'] }}
                                        </span>

                                        <span class="block truncate text-[10px] text-slate-500">
                                            {{ $pieza['detail'] }}
                                            ·
                                            {{ $pieza['updated_at']?->locale('es')->diffForHumans() }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- CÓMO SE MONTA UN TORNEO --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                            <x-omni-icon name="capas" size="h-4 w-4" />
                        </span>

                        <div>
                            <h2 class="text-sm font-black text-white">Cómo se monta un torneo</h2>
                            <p class="text-[10px] text-slate-500">
                                Cinco piezas, cada una con su sitio. De izquierda a derecha.
                            </p>
                        </div>
                    </header>

                    <div class="grid gap-2 p-4 sm:grid-cols-3 lg:grid-cols-5">

                        @foreach ([['grafo', 'La fase', 'Qué ocurre dentro de una etapa: cuadro, liga, grupos o suizo.', 'amber', route('tournaments.phase-templates.index')], ['flecha-derecha', 'Sus salidas', 'Quién sale de esa etapa y con qué criterio: los dos primeros, los eliminados…', 'violet', route('tournaments.phase-templates.index', ['sort' => 'exits_desc'])], ['trofeo', 'El torneo', 'Encadena fases en un recorrido con entradas y finales.', 'cyan', route('tournaments.templates.index')], ['matraz', 'El laboratorio', 'Ejecuta el recorrido con participantes de mentira para ver si funciona.', 'emerald', route('tournaments.lab.index')], ['orbita', 'El universo', 'Lo juega de verdad: elige quién entra, qué se reparte y cuándo empieza.', 'rose', route('universes.dashboard')]] as $indice => [$icono, $titulo, $texto, $color, $enlace])
                            @php $t = $tonos[$color]; @endphp

                            <a href="{{ $enlace }}"
                                class="group relative rounded-xl border {{ $t['borde'] }} bg-slate-950/60 p-3 transition hover:bg-slate-900">

                                <span class="flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg {{ $t['fondo'] }} {{ $t['texto'] }}">
                                        <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                                    </span>

                                    <span class="font-mono text-[9px] font-black text-slate-700">
                                        0{{ $indice + 1 }}
                                    </span>
                                </span>

                                <span class="mt-2 block text-[12px] font-black text-slate-200">{{ $titulo }}</span>

                                <span class="mt-1 block text-[10px] leading-4 text-slate-500">{{ $texto }}</span>
                            </a>
                        @endforeach

                    </div>

                </section>

            </div>


            {{-- ============================================================= --}}
            {{-- COLUMNA LATERAL --}}
            {{-- ============================================================= --}}

            <aside class="space-y-4">

                {{-- ===================================================== --}}
                {{-- DE QUÉ ESTÁN HECHAS TUS FASES --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        De qué están hechas tus fases
                    </p>

                    @if (collect($engines)->sum('count') === 0)
                        <p class="mt-2 text-[11px] leading-4 text-slate-600">
                            Todavía no hay fases. Cada una usa un motor: cuadro, liga, grupos o suizo.
                        </p>
                    @else
                        <div class="mt-3 space-y-2">
                            @foreach ($engines as $motor)
                                @php $t = $tonos[$motor['accent']]; @endphp

                                <a href="{{ $motor['url'] }}" class="group block">

                                    <span class="flex items-baseline justify-between gap-2">
                                        <span class="flex items-center gap-1.5">
                                            <span class="text-[11px]">{{ $motor['icon'] }}</span>
                                            <span
                                                class="text-[11px] font-bold text-slate-400 transition group-hover:text-slate-200">
                                                {{ $motor['label'] }}
                                            </span>
                                        </span>

                                        <span class="font-mono text-[11px] font-black {{ $t['texto'] }}">
                                            {{ $motor['count'] }}
                                        </span>
                                    </span>

                                    <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-slate-950">
                                        <span class="block h-full rounded-full {{ $t['barra'] }} transition-all"
                                            style="width: {{ max(3, $motor['share']) }}%"></span>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        <p class="mt-3 border-t border-slate-800 pt-2.5 text-[10px] leading-4 text-slate-600">
                            Un taller con un solo motor es un taller con un hueco: cada formato
                            resuelve un problema distinto.
                        </p>
                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- Y DE QUÉ CLASE SON TUS TORNEOS --}}
                {{-- ===================================================== --}}

                @if (!empty($categories))
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Y de qué clase son tus torneos
                        </p>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($categories as $tipo)
                                @php $t = $tonos[$tipo['accent']]; @endphp

                                <a href="{{ $tipo['url'] }}"
                                    class="flex items-baseline gap-1.5 rounded-lg border {{ $t['borde'] }} {{ $t['fondo'] }} px-2 py-1 transition hover:bg-slate-900">
                                    <span class="font-mono text-[12px] font-black {{ $t['texto'] }}">
                                        {{ $tipo['count'] }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400">{{ $tipo['label'] }}</span>
                                </a>
                            @endforeach
                        </div>

                    </section>
                @endif


                {{-- ===================================================== --}}
                {{-- LAS FASES QUE MÁS SE REPITEN --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Las fases que más se repiten
                    </p>

                    @if ($mostUsed->isEmpty())
                        <p class="mt-2 text-[11px] leading-4 text-slate-600">
                            Ninguna fase se usa todavía en un torneo. Una fase reutilizada en varios
                            recorridos es lo que ahorra volver a montarla.
                        </p>
                    @else
                        <p class="mt-1 text-[10px] leading-4 text-slate-600">
                            Si tocas una de estas, tocas todos los torneos que la usan.
                        </p>

                        <ul class="mt-2.5 space-y-1.5">
                            @foreach ($mostUsed as $fase)
                                @php $t = $tonos[$fase->accent] ?? $tonos['slate']; @endphp

                                <li>
                                    <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
                                        class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/60 px-2 py-1.5 transition hover:border-slate-700 hover:bg-slate-900">

                                        <span class="text-[13px]">{{ $fase->display_icon }}</span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-slate-200">
                                                {{ $fase->name }}
                                            </span>
                                            <span class="block truncate text-[9px] {{ $t['texto'] }}">
                                                {{ $fase->type_label }}
                                            </span>
                                        </span>

                                        <span
                                            class="shrink-0 rounded-md bg-amber-500/15 px-1.5 py-0.5 font-mono text-[10px] font-black text-amber-300">
                                            ×{{ $fase->tournament_phase_nodes_count }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- ATAJOS --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Ir a</p>

                    <div class="mt-2.5 space-y-1">
                        @foreach ([['trofeo', 'Biblioteca de torneos', route('tournaments.templates.index')], ['grafo', 'Biblioteca de fases', route('tournaments.phase-templates.index')], ['matraz', 'Laboratorio', route('tournaments.lab.index')], ['orbita', 'Universos', route('universes.dashboard')], ['libro', 'Biblioteca de entidades', route('dashboard')]] as [$icono, $texto, $enlace])
                            <a href="{{ $enlace }}"
                                class="flex items-center gap-2.5 rounded-lg px-2 py-2 text-[11px] font-bold text-slate-400 transition hover:bg-slate-950 hover:text-white">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                                <span class="flex-1">{{ $texto }}</span>
                                <x-omni-icon name="chevron-derecha" size="h-3 w-3" />
                            </a>
                        @endforeach
                    </div>

                </section>

            </aside>

        </div>

    </div>

</x-tournament-layout>

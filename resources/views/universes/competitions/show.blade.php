@php
    /*
     * La ficha de una competición.
     *
     * Lo que había: una pantalla en claro, con emojis sueltos, que ponía
     * la tabla de competidores tal cual —sin poder buscar en ella, sin
     * poder ordenarla, sin poder mirarla de otra forma— y que calculaba la
     * clasificación final para no enseñarla en ningún sitio.
     *
     * Lo que uno viene a saber al abrir una competición:
     *
     *   · en qué estado está y si le toca hacer algo → cabecera y aviso
     *   · quién ganó                                 → el podio
     *   · quién compitió y cómo le fue               → competidores, que
     *                                                  ahora se buscan, se
     *                                                  ordenan y se miran
     *                                                  de cinco maneras
     *   · cómo se desarrolló                         → fases
     *   · qué ha ido pasando                         → historial filtrable
     *
     * El panel del motor queda plegado a propósito: sus piezas se comparten
     * con el Competition Lab, que todavía está en claro, así que abiertas
     * dejarían una isla blanca en mitad de la pantalla. Se pliega con
     * x-show, nunca con x-if, para que nada se desmonte.
     */

    $torneo = $competition->universeTournament;

    /* Su cara es la suya; si no tiene, la del torneo del que sale */
    $cara = $competition->image_url ?: $torneo?->image_url;

    $tonoEstado = [
        'DRAFT' => ['#fbbf24', 'bg-amber-500/15 text-amber-300'],
        'RUNNING' => ['#34d399', 'bg-emerald-500/15 text-emerald-300'],
        'PAUSED' => ['#fb7185', 'bg-rose-500/15 text-rose-300'],
        'COMPLETED' => ['#22d3ee', 'bg-cyan-500/15 text-cyan-300'],
        'CANCELLED' => ['#475569', 'bg-slate-800 text-slate-500'],
    ];

    [$tono, $claseEstado] = $tonoEstado[$competition->status] ?? ['#a78bfa', 'bg-violet-500/15 text-violet-300'];

    /* Lo que de verdad pide atención: parada y esperando a alguien */
    $teEspera = in_array($competition->runtime_status, ['AWAITING_DECISION', 'BLOCKED'], true);

    $campeon = $history['champion'] ?? null;

    /* El podio sale de la clasificación final, que ya se calculaba */
    $podio = $campeon ? $finalStandings->take(3) : collect();

    /*
     * Los competidores viajan a Alpine como datos, no como HTML: así se
     * pueden buscar, ordenar y repartir en varias vistas sin volver al
     * servidor ni repetir la lista cinco veces en el DOM.
     */
    $competidores = $participants
        ->map(fn($p) => [
            'id' => (int) $p->id,
            'seed' => (int) $p->seed,
            'nombre' => $p->name,
            'imagen' => $p->face_url ?? $p->universeEntity?->image_url,
            'tipo' => $p->entity_type_name,
            'version' => $p->entity_version_name,
            'estado' => $p->status,
            'estadoTexto' => $p->status_label,
            'jugados' => (int) $p->matches,
            'ganados' => (int) $p->wins,
            'empatados' => (int) $p->draws,
            'perdidos' => (int) $p->losses,
            'puntos' => (float) $p->points,
            'puesto' => $p->placement ? (int) $p->placement : null,
            'desenlace' => $p->outcome,
            'destino' => $p->final_location_name,
            'sigueEnElMundo' => (bool) $p->universe_entity_id,
            'ficha' => $p->universe_entity_id
                ? route('universes.entities.show', [$universe, $p->universe_entity_id])
                : null,
            'atributos' => collect($p->attribute_snapshot ?? [])
                ->map(fn($a) => [
                    'nombre' => $a['name'] ?? '',
                    'valor' => (string) ($a['display'] ?? ''),
                    'destacado' => (bool) ($a['featured'] ?? false),
                ])
                ->values()
                ->all(),
        ])
        ->values();

    $niveles = $events->pluck('level')->filter()->unique()->values();
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">{{ $competition->name }}</x-slot>

    <div class="space-y-4">

        <a href="{{ route('universes.competitions.index', $universe) }}"
            class="inline-flex items-center gap-1.5 text-[11px] font-black text-slate-500 transition hover:text-violet-300">
            <x-omni-icon name="flecha-izquierda" size="h-3.5 w-3.5" />
            Competiciones
        </a>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-[12px] font-bold text-rose-200">
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- CABECERA: QUÉ COMPETICIÓN ES ESTA --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $teEspera ? '#fb7185' : $tono . '55' }}">

            <div class="relative">

                {{-- Su cara, de fondo --}}
                <div class="absolute inset-0">
                    @if ($cara)
                        <img src="{{ $cara }}" alt="" class="h-full w-full object-cover opacity-25">
                    @endif
                    <span class="absolute inset-0"
                        style="background: linear-gradient(105deg, #020617f2 35%, {{ $tono }}22 100%)"></span>
                </div>

                <div class="relative flex flex-col gap-4 p-4 xl:flex-row xl:items-end">

                    <div class="flex min-w-0 flex-1 items-start gap-3">

                        <span class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                            style="border-color: {{ $tono }}55">
                            @if ($cara)
                                <img src="{{ $cara }}" alt="{{ $competition->name }}" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center" style="color: {{ $tono }}">
                                    <x-omni-icon name="trofeo" size="h-7 w-7" />
                                </span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">

                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                                {{ $competition->isDraft() ? 'Edición preparada' : ($competition->isClosed() ? 'Edición jugada' : 'Edición en juego') }}
                            </p>

                            <h1 class="mt-0.5 text-xl font-black tracking-tight text-white">
                                {{ $competition->name }}
                            </h1>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5">

                                <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $claseEstado }}">
                                    {{ $competition->status_label }}
                                </span>

                                <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-500">
                                    {{ $competition->code }}
                                </span>

                                @if ($competition->season)
                                    <a href="{{ route('universes.seasons.show', [$universe, $competition->season]) }}"
                                        class="flex items-center gap-1 rounded border border-violet-500/40 px-1.5 py-0.5 text-[9px] font-black text-violet-300 transition hover:bg-violet-500/10">
                                        <x-omni-icon name="calendario" size="h-3 w-3" />
                                        <span class="font-mono">T{{ $competition->season->number }}</span>
                                        {{ $competition->season->name }}
                                    </a>
                                @endif

                                @if (!empty($game['name']))
                                    <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500"
                                        title="Con este juego se resuelve cada batalla">
                                        {{ $game['name'] }}
                                    </span>
                                @endif

                                @if ($competition->started_at)
                                    <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-600">
                                        {{ $competition->started_at->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>

                            @if ($torneo)
                                <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950/70 py-0.5 pl-0.5 pr-2 transition hover:border-slate-600">

                                    <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                        @if ($torneo->image_url)
                                            <img src="{{ $torneo->image_url }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-slate-700">
                                                <x-omni-icon name="trofeo" size="h-3 w-3" />
                                            </span>
                                        @endif
                                    </span>

                                    <span class="min-w-0">
                                        <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">del torneo</span>
                                        <span class="block truncate text-[10px] font-black text-slate-300">{{ $torneo->name }}</span>
                                    </span>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Qué se puede hacer con ella --}}
                    <div class="flex shrink-0 flex-wrap items-center gap-1.5">

                        <a href="{{ route('universes.competitions.play', [$universe, $competition]) }}"
                            class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-[11px] font-black transition"
                            style="background-color: {{ $teEspera ? '#f43f5e' : $tono }}; color: #020617">
                            <x-omni-icon :name="$competition->isClosed() ? 'ojo' : 'reproducir'" size="h-3.5 w-3.5" />
                            {{ $competition->isClosed() ? 'Ver el torneo' : ($competition->isDraft() ? 'Empezar a jugar' : 'Seguir jugando') }}
                        </a>

                        @can('update', $competition)
                            @if ($competition->status === 'RUNNING')
                                <form method="POST" action="{{ route('universes.competitions.pause', [$universe, $competition]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Pausar"
                                        class="flex items-center rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-2 text-slate-400 transition hover:border-amber-500/50 hover:text-amber-300">
                                        <x-omni-icon name="pausa" size="h-3.5 w-3.5" />
                                    </button>
                                </form>
                            @endif

                            @if ($competition->status === 'PAUSED')
                                <form method="POST" action="{{ route('universes.competitions.resume', [$universe, $competition]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="flex items-center gap-1 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-2.5 py-2 text-[11px] font-black text-emerald-300 transition hover:bg-emerald-500/20">
                                        <x-omni-icon name="reproducir" size="h-3.5 w-3.5" />
                                        Reanudar
                                    </button>
                                </form>
                            @endif

                            @if ($competition->isDraft())
                                <a href="{{ route('universes.competitions.edit', [$universe, $competition]) }}"
                                    title="Editar la configuración"
                                    class="flex items-center rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-2 text-slate-400 transition hover:border-violet-500/50 hover:text-violet-300">
                                    <x-omni-icon name="lapiz" size="h-3.5 w-3.5" />
                                </a>
                            @endif

                            <a href="{{ route('universes.competitions.create', $universe) }}?universe_tournament_id={{ $competition->universe_tournament_id }}&copy={{ $competition->id }}"
                                title="Copiar en una edición nueva"
                                class="flex items-center rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-2 text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                                <x-omni-icon name="copiar" size="h-3.5 w-3.5" />
                            </a>

                            @unless ($competition->isClosed())
                                <form method="POST" action="{{ route('universes.competitions.cancel', [$universe, $competition]) }}"
                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="x"
                                    data-confirm-title="Cancelar la competición"
                                    data-confirm-message="Se conservará su historial, pero no podrá continuar."
                                    data-confirm-subject="{{ $competition->name }}"
                                    data-confirm-detail="Lo ya jugado se queda como está. Lo que falte no se jugará nunca."
                                    data-confirm-action="Sí, cancelarla">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Cancelar"
                                        class="flex items-center rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-2 text-slate-500 transition hover:border-rose-500/50 hover:text-rose-300">
                                        <x-omni-icon name="detener" size="h-3.5 w-3.5" />
                                    </button>
                                </form>
                            @endunless
                        @endcan
                    </div>
                </div>
            </div>

            <p class="border-t border-slate-800/80 px-4 py-2 text-[10px] leading-relaxed text-slate-500">
                @if ($competition->isDraft())
                    Preparada y sin empezar. Todavía puedes cambiar el juego, cómo se pelea en cada fase
                    y qué se lleva quien gane: la forma y los competidores ya están congelados.
                @elseif ($competition->isClosed())
                    Cerrada. Se conserva en modo lectura: puedes recorrer competidores, fases e historial,
                    pero ya no admite nuevas acciones.
                @else
                    Los resultados se guardan en cuanto se registran. Puedes cerrar el navegador y volver
                    otro día: la competición continúa donde la dejaste.
                @endif
            </p>
        </section>


        @if ($teEspera)
            <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-rose-500/40 bg-rose-500/5 px-4 py-3">

                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                    <x-omni-icon name="aviso" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-black text-white">Se ha parado y no avanza sin ti</p>
                    <p class="text-[10px] leading-relaxed text-rose-200/70">
                        {{ $competition->runtime_status_label }} — hace falta que decidas algo para que siga.
                    </p>
                </div>

                <a href="{{ route('universes.competitions.play', [$universe, $competition]) }}"
                    class="shrink-0 rounded-xl bg-rose-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-rose-400">
                    Atender →
                </a>
            </section>
        @endif


        <div x-data="competitionLab({
            initialState: @js($payload['state']),
            initialToken: null,
            persistent: true,
            revision: @js($payload['revision']),
            actionUrl: @js(route('universes.competitions.action', [$universe, $competition])),
            storageKey: null,
        })" class="space-y-4">


            {{-- ===================================================== --}}
            {{-- CIFRAS --}}
            {{-- ===================================================== --}}

            <section class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                @foreach ([['Competidores', $history['participants'], 'usuario', '#a78bfa'], ['Fases', $history['phases'], 'capas', '#22d3ee'], ['Encuentros', $history['matches'], 'espadas', '#fbbf24'], ['Jugados', $history['matches_played'], 'check', '#34d399'], ['Duración', $history['duration'] ?? '—', 'historial', '#fb7185']] as [$etiqueta, $valor, $icono, $color])
                    <article class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="block truncate font-mono text-xl font-black" style="color: {{ $color }}">
                                    {{ $valor }}
                                </span>
                                <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    {{ $etiqueta }}
                                </span>
                            </div>
                            <span class="shrink-0" style="color: {{ $color }}66">
                                <x-omni-icon :name="$icono" size="h-5 w-5" />
                            </span>
                        </div>
                    </article>
                @endforeach
            </section>


            {{-- ===================================================== --}}
            {{-- PODIO --}}
            {{-- ===================================================== --}}

            {{--
                La clasificación final ya se calculaba y no se enseñaba en
                ninguna parte. Es lo primero que uno quiere ver de una
                competición terminada.
            --}}

            @if ($podio->isNotEmpty())
                @php
                    $metales = [
                        1 => ['#fbbf24', 'Campeón'],
                        2 => ['#cbd5e1', 'Segundo'],
                        3 => ['#f59e0b', 'Tercero'],
                    ];
                @endphp

                <section class="overflow-hidden rounded-2xl border border-amber-500/30 bg-amber-500/5">

                    <div class="flex items-center gap-2.5 border-b border-amber-500/20 px-4 py-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                            <x-omni-icon name="trofeo" size="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">El podio</h2>
                            <p class="text-[10px] text-amber-200/70">Cómo acabó la clasificación final.</p>
                        </div>
                    </div>

                    <div class="grid gap-2 p-3 lg:grid-cols-3">
                        @foreach ($podio as $indice => $puesto)
                            @php
                                [$metal, $titulo] = $metales[$indice + 1] ?? ['#64748b', 'Puesto ' . ($indice + 1)];
                            @endphp

                            <article class="rounded-xl border bg-slate-950 p-3" style="border-color: {{ $metal }}55">

                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider"
                                        style="color: {{ $metal }}">
                                        <x-omni-icon :name="$indice === 0 ? 'trofeo' : 'medalla'" size="h-3.5 w-3.5" />
                                        {{ $titulo }}
                                    </span>

                                    <span class="font-mono text-[10px] font-black text-slate-600">
                                        {{ $puesto->points }} pts
                                    </span>
                                </div>

                                @include('universes.competitions.partials.participant-chip', [
                                    'name' => $puesto->name,
                                    'imageUrl' => $puesto->face_url ?? $puesto->universeEntity?->image_url,
                                    'typeName' => $puesto->entity_type_name,
                                    'versionName' => $puesto->entity_version_name,
                                    'attributes' => $puesto->attribute_snapshot ?? [],
                                    'seed' => $puesto->seed,
                                    'size' => 'md',
                                    'maxAttributes' => 3,
                                ])

                                <div class="mt-2 flex items-center gap-1.5 border-t border-slate-800 pt-2">
                                    <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-400">
                                        {{ $puesto->wins }}G · {{ $puesto->draws }}E · {{ $puesto->losses }}P
                                    </span>

                                    @if ($puesto->universe_entity_id)
                                        <a href="{{ route('universes.entities.show', [$universe, $puesto->universe_entity_id]) }}"
                                            class="ml-auto text-[10px] font-black text-slate-500 transition hover:text-violet-300">
                                            Su ficha →
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif


            {{-- ===================================================== --}}
            {{-- ARRANQUE --}}
            {{-- ===================================================== --}}

            @if ($competition->isDraft())
                @can('update', $competition)
                    <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-violet-500/30 bg-violet-500/5 px-4 py-3">

                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                            <x-omni-icon name="chispa" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-black text-white">Todo listo para empezar</p>
                            <p class="text-[10px] leading-relaxed text-slate-500">
                                {{ $competition->participant_count }} competidores esperando. Al comenzar, el
                                Tournament Graph los repartirá por las fases según la configuración congelada.
                            </p>
                        </div>

                        <button type="button" @click="execute('START_TOURNAMENT')" :disabled="loading"
                            class="flex shrink-0 items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400 disabled:opacity-50">
                            <x-omni-icon name="reproducir" size="h-3.5 w-3.5" />
                            <span x-show="!loading">Comenzar competición</span>
                            <span x-show="loading" x-cloak>Comenzando…</span>
                        </button>
                    </section>
                @endcan
            @endif


            <div x-show="error" x-cloak
                class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-[12px] font-bold text-rose-200">
                <span x-text="error"></span>
            </div>


            {{-- ===================================================== --}}
            {{-- JUGAR A MANO + PANEL DEL MOTOR --}}
            {{-- ===================================================== --}}

            @unless ($competition->isDraft())

                {{-- El simulador es la forma de jugar, así que va a la vista --}}
                @include('universes.competitions.partials.simulator')

                <section x-data="{ abierto: false }"
                    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <button type="button" @click="abierto = !abierto"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left transition hover:bg-slate-950/50">

                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-400">
                            <x-omni-icon name="engranaje" size="h-4 w-4" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-[13px] font-black text-white">Panel del motor</span>
                            <span class="block text-[10px] text-slate-500">
                                Recorrido automático, decisiones pendientes e inspector de participantes.
                                Estado: <span class="font-mono text-slate-400"
                                    x-text="state?.graph_runtime?.status ?? state?.status ?? '—'"></span>
                            </span>
                        </span>

                        <span class="shrink-0 text-slate-600 transition" :class="abierto && 'rotate-90'">
                            <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                        </span>
                    </button>

                    <div x-show="abierto" x-cloak class="border-t border-slate-800 bg-white p-4">
                        @include('tournaments.lab.partials.automatic-runtime')

                        <div class="mt-6">
                            @include('tournaments.lab.partials.manual-decision')
                        </div>

                        <div class="mt-6">
                            @include('tournaments.lab.partials.participants-inspector')
                        </div>
                    </div>
                </section>
            @endunless


            {{-- ===================================================== --}}
            {{-- CÓMO SE DESARROLLÓ --}}
            {{-- ===================================================== --}}

            {{-- Antes de empezar no se ha desarrollado nada: la fase salia vacia diciendo «no llego a generar encuentros» --}}
            @if ($phaseBlocks->isNotEmpty() && ! $competition->isDraft())
                <section x-data="{ fase: 0, total: {{ $phaseBlocks->count() }} }"
                    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-4 py-2.5">

                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                            <x-omni-icon name="capas" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">Cómo se desarrolló</h2>
                            <p class="text-[10px] text-slate-500">
                                Cada fase con la forma que le toca: un cuadro no se lee como una liguilla.
                            </p>
                        </div>

                        @if ($phaseBlocks->count() > 1)
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" @click="fase = Math.max(0, fase - 1)" :disabled="fase === 0"
                                    class="rounded-lg border border-slate-800 px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                                    <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                                </button>
                                <span class="font-mono text-[10px] font-black text-slate-600">
                                    <span x-text="fase + 1"></span>/<span x-text="total"></span>
                                </span>
                                <button type="button" @click="fase = Math.min(total - 1, fase + 1)"
                                    :disabled="fase === total - 1"
                                    class="rounded-lg border border-slate-800 px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                                    <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                                </button>
                            </div>
                        @endif
                    </div>

                    @if ($phaseBlocks->count() > 1)
                        <div class="flex flex-wrap gap-1.5 border-b border-slate-800 px-3 py-2">
                            @foreach ($phaseBlocks as $indice => $bloque)
                                <button type="button" @click="fase = {{ $indice }}"
                                    :class="fase === {{ $indice }} ? 'bg-cyan-500 text-slate-950' : 'border border-slate-800 text-slate-500 hover:text-slate-200'"
                                    class="rounded-lg px-2 py-1 text-[10px] font-black transition">
                                    {{ $bloque['phase']->node_name }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="p-3">
                        @foreach ($phaseBlocks as $indice => $bloque)
                            <div x-show="fase === {{ $indice }}" @if ($indice > 0) x-cloak @endif>
                                @include('universes.competitions.partials.history.phase', ['block' => $bloque])
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif


            {{-- ===================================================== --}}
            {{-- COMPETIDORES --}}
            {{-- ===================================================== --}}

            <section x-data="competidoresDeLaFicha(@js($competidores))"
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-4 py-2.5">

                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <x-omni-icon name="usuario" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Competidores</h2>
                        <p class="text-[10px] text-slate-500">
                            Congelados al empezar: el nombre, la versión y los atributos son los que tenían
                            ese día, no los de ahora.
                        </p>
                    </div>

                    <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[11px] font-black text-slate-400">
                        <span x-text="filtrados.length"></span>/{{ $competidores->count() }}
                    </span>
                </div>


                {{-- FILTROS Y FORMA DE MIRAR --}}

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/50 p-2">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar competidor</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" x-model="busqueda" placeholder="Buscar por nombre, tipo o versión…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <select x-model="estado"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Cualquier estado</option>
                        <template x-for="valor in estados" :key="valor">
                            <option :value="valor" x-text="etiquetaEstado(valor)"></option>
                        </template>
                    </select>

                    <select x-model="orden"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="seed">Por seed</option>
                        <option value="puesto">Por puesto final</option>
                        <option value="puntos">Con más puntos</option>
                        <option value="ganados">Con más victorias</option>
                        <option value="nombre">Nombre (A–Z)</option>
                    </select>

                    <button type="button" @click="soloVivos = !soloVivos"
                        :class="soloVivos ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-300' : 'border-slate-800 bg-slate-950 text-slate-500'"
                        class="rounded-xl border px-2.5 py-2 text-[10px] font-black transition"
                        title="Ocultar a quien ya no está en el Universo">
                        Solo los que siguen
                    </button>

                    <button type="button" x-show="hayFiltros" x-cloak @click="limpiar()"
                        class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                        Quitar filtros
                    </button>

                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ([['grid', 'cuadricula', 'Cuadrícula: la ficha de cada uno'], ['list', 'menu', 'Lista: una línea cada uno'], ['table', 'controles', 'Tabla: para comparar números'], ['podio', 'medalla', 'Podio: por cómo acabaron'], ['estado', 'capas', 'Por estado: agrupados']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="vista === '{{ $modo }}'"
                                :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>
                </div>


                <div class="p-3">

                    <template x-if="filtrados.length === 0">
                        <p class="rounded-xl border border-dashed border-slate-800 py-10 text-center text-[11px] text-slate-600">
                            Ningún competidor encaja con lo que has filtrado.
                        </p>
                    </template>


                    {{-- ---------- CUADRÍCULA ---------- --}}

                    <div x-show="vista === 'grid'" class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        <template x-for="c in filtrados" :key="c.id">
                            <article class="rounded-xl border border-slate-800 bg-slate-950 p-2.5 transition hover:border-slate-700">

                                <div class="flex items-start gap-2.5">

                                    <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                        <template x-if="c.imagen">
                                            <img :src="c.imagen" :alt="c.nombre" loading="lazy" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!c.imagen">
                                            <span class="flex h-full w-full items-center justify-center font-mono text-[11px] text-slate-700"
                                                x-text="'#' + c.seed"></span>
                                        </template>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="shrink-0 rounded bg-slate-900 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-500"
                                                x-text="'#' + c.seed"></span>
                                            <span class="truncate text-[12px] font-black text-white" x-text="c.nombre"></span>
                                        </div>

                                        <p class="mt-0.5 truncate text-[10px] text-slate-600">
                                            <span x-text="c.tipo"></span>
                                            <template x-if="c.version">
                                                <span class="text-violet-400"> · <span x-text="c.version"></span></span>
                                            </template>
                                        </p>

                                        <div class="mt-1 flex flex-wrap items-center gap-1">
                                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase"
                                                :class="claseEstado(c.estado)" x-text="c.estadoTexto"></span>

                                            <template x-if="c.puesto">
                                                <span class="rounded border border-amber-500/40 px-1.5 py-0.5 font-mono text-[9px] font-black text-amber-300"
                                                    x-text="c.puesto + 'º'"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Sus atributos congelados --}}
                                <template x-if="c.atributos.length">
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <template x-for="a in c.atributos.slice(0, 4)" :key="a.nombre">
                                            <span class="rounded px-1.5 py-0.5 text-[9px] font-bold"
                                                :class="a.destacado ? 'bg-violet-500/15 text-violet-300' : 'bg-slate-900 text-slate-500'">
                                                <span x-text="a.nombre"></span>
                                                <span class="font-black" x-text="a.valor"></span>
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                <div class="mt-2 flex items-center gap-1.5 border-t border-slate-800 pt-2">
                                    <span class="font-mono text-[10px] text-slate-500">
                                        <span x-text="c.ganados"></span>G ·
                                        <span x-text="c.empatados"></span>E ·
                                        <span x-text="c.perdidos"></span>P
                                    </span>

                                    <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[10px] font-black text-slate-300"
                                        x-text="c.puntos + ' pts'"></span>

                                    <template x-if="c.ficha">
                                        <a :href="c.ficha"
                                            class="ml-auto text-[10px] font-black text-slate-600 transition hover:text-violet-300">
                                            Su ficha →
                                        </a>
                                    </template>
                                </div>

                                <template x-if="c.destino">
                                    <p class="mt-1.5 truncate rounded bg-slate-900 px-2 py-1 text-[9px] text-slate-500">
                                        Acabó en <span class="font-black text-slate-400" x-text="c.destino"></span>
                                    </p>
                                </template>
                            </article>
                        </template>
                    </div>


                    {{-- ---------- LISTA ---------- --}}

                    <div x-show="vista === 'list'" x-cloak class="divide-y divide-slate-800/70">
                        <template x-for="c in filtrados" :key="c.id">
                            <div class="flex flex-wrap items-center gap-2.5 py-2">

                                <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    <template x-if="c.imagen">
                                        <img :src="c.imagen" :alt="c.nombre" loading="lazy" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!c.imagen">
                                        <span class="flex h-full w-full items-center justify-center font-mono text-[10px] text-slate-700"
                                            x-text="c.seed"></span>
                                    </template>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[12px] font-black text-white" x-text="c.nombre"></p>
                                    <p class="truncate text-[10px] text-slate-600">
                                        <span x-text="c.tipo"></span>
                                        <template x-if="c.destino">
                                            <span> · acabó en <span class="text-slate-400" x-text="c.destino"></span></span>
                                        </template>
                                    </p>
                                </div>

                                <span class="shrink-0 font-mono text-[10px] text-slate-500">
                                    <span x-text="c.ganados"></span>G·<span x-text="c.empatados"></span>E·<span
                                        x-text="c.perdidos"></span>P
                                </span>

                                <span class="shrink-0 rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[10px] font-black text-slate-300"
                                    x-text="c.puntos"></span>

                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-black uppercase"
                                    :class="claseEstado(c.estado)" x-text="c.estadoTexto"></span>

                                <template x-if="c.ficha">
                                    <a :href="c.ficha"
                                        class="shrink-0 text-[10px] font-black text-slate-600 transition hover:text-violet-300">→</a>
                                </template>
                            </div>
                        </template>
                    </div>


                    {{-- ---------- TABLA ---------- --}}

                    <div x-show="vista === 'table'" x-cloak class="overflow-x-auto">
                        <table class="w-full min-w-[760px]">
                            <thead class="border-b border-slate-800 text-left">
                                <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    <th class="px-2 py-2">Seed</th>
                                    <th class="px-2 py-2">Competidor</th>
                                    <th class="px-2 py-2">Estado</th>
                                    <th class="px-2 py-2 text-center">PJ</th>
                                    <th class="px-2 py-2 text-center">G</th>
                                    <th class="px-2 py-2 text-center">E</th>
                                    <th class="px-2 py-2 text-center">P</th>
                                    <th class="px-2 py-2 text-center">Pts</th>
                                    <th class="px-2 py-2">Acabó en</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-800/70">
                                <template x-for="c in filtrados" :key="c.id">
                                    <tr class="transition hover:bg-slate-950/50">
                                        <td class="px-2 py-1.5 font-mono text-[10px] text-slate-600" x-text="c.seed"></td>

                                        <td class="px-2 py-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="h-7 w-7 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-950">
                                                    <template x-if="c.imagen">
                                                        <img :src="c.imagen" :alt="c.nombre" loading="lazy"
                                                            class="h-full w-full object-cover">
                                                    </template>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-[11px] font-black text-white"
                                                        x-text="c.nombre"></span>
                                                    <span class="block truncate text-[9px] text-slate-600"
                                                        x-text="c.version || c.tipo"></span>
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-2 py-1.5">
                                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase"
                                                :class="claseEstado(c.estado)" x-text="c.estadoTexto"></span>
                                        </td>

                                        <td class="px-2 py-1.5 text-center font-mono text-[11px] text-slate-400" x-text="c.jugados"></td>
                                        <td class="px-2 py-1.5 text-center font-mono text-[11px] text-emerald-300" x-text="c.ganados"></td>
                                        <td class="px-2 py-1.5 text-center font-mono text-[11px] text-slate-500" x-text="c.empatados"></td>
                                        <td class="px-2 py-1.5 text-center font-mono text-[11px] text-rose-300" x-text="c.perdidos"></td>
                                        <td class="px-2 py-1.5 text-center font-mono text-[11px] font-black text-white" x-text="c.puntos"></td>
                                        <td class="px-2 py-1.5 text-[10px] text-slate-500" x-text="c.destino || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>


                    {{-- ---------- PODIO ---------- --}}

                    <div x-show="vista === 'podio'" x-cloak class="space-y-1.5">
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Ordenados por cómo acabaron. Quien no tiene puesto final es que la competición
                            todavía no ha decidido el suyo.
                        </p>

                        <template x-for="c in porPuesto" :key="c.id">
                            <div class="flex flex-wrap items-center gap-2.5 rounded-xl border p-2"
                                :class="c.puesto && c.puesto <= 3 ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-800 bg-slate-950'">

                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg font-mono text-[12px] font-black"
                                    :class="c.puesto && c.puesto <= 3 ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-900 text-slate-600'"
                                    x-text="c.puesto ? c.puesto + 'º' : '—'"></span>

                                <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    <template x-if="c.imagen">
                                        <img :src="c.imagen" :alt="c.nombre" loading="lazy" class="h-full w-full object-cover">
                                    </template>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[12px] font-black text-white" x-text="c.nombre"></p>
                                    <p class="truncate text-[10px] text-slate-600"
                                        x-text="c.desenlace || c.destino || c.estadoTexto"></p>
                                </div>

                                <span class="shrink-0 rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[10px] font-black text-slate-300"
                                    x-text="c.puntos + ' pts'"></span>
                            </div>
                        </template>
                    </div>


                    {{-- ---------- POR ESTADO ---------- --}}

                    <div x-show="vista === 'estado'" x-cloak class="space-y-3">
                        <template x-for="grupo in agrupados" :key="grupo.clave">
                            <section class="overflow-hidden rounded-xl border border-slate-800">

                                <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-950 px-3 py-1.5">
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase"
                                        :class="claseEstado(grupo.clave)" x-text="grupo.etiqueta"></span>
                                    <span class="ml-auto font-mono text-[10px] font-black text-slate-600"
                                        x-text="grupo.gente.length"></span>
                                </div>

                                <div class="divide-y divide-slate-800/70 px-3">
                                    <template x-for="c in grupo.gente" :key="c.id">
                                        <div class="flex items-center gap-2.5 py-1.5">
                                            <span class="h-7 w-7 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-950">
                                                <template x-if="c.imagen">
                                                    <img :src="c.imagen" :alt="c.nombre" loading="lazy"
                                                        class="h-full w-full object-cover">
                                                </template>
                                            </span>
                                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-white"
                                                x-text="c.nombre"></span>
                                            <span class="shrink-0 font-mono text-[10px] text-slate-600"
                                                x-text="c.puntos + ' pts'"></span>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </template>
                    </div>
                </div>
            </section>


            {{-- ===================================================== --}}
            {{-- HISTORIAL --}}
            {{-- ===================================================== --}}

            <section x-data="{ nivel: '', busqueda: '' }"
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-4 py-2.5">

                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-400">
                        <x-omni-icon name="historial" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Historial</h2>
                        <p class="text-[10px] text-slate-500">
                            Todo lo que ha ocurrido aquí, guardado en base de datos. Lo más reciente primero.
                        </p>
                    </div>

                    @if ($niveles->isNotEmpty())
                        <div class="flex shrink-0 flex-wrap items-center gap-1">
                            <button type="button" @click="nivel = ''"
                                :class="nivel === '' ? 'bg-slate-700 text-white' : 'border border-slate-800 text-slate-500'"
                                class="rounded-lg px-2 py-1 text-[10px] font-black transition">Todo</button>

                            @foreach ($niveles as $unNivel)
                                <button type="button" @click="nivel = '{{ $unNivel }}'"
                                    :class="nivel === '{{ $unNivel }}' ? '{{ match ($unNivel) {
                                        'SUCCESS' => 'bg-emerald-500 text-slate-950',
                                        'WARNING' => 'bg-amber-500 text-slate-950',
                                        'ERROR' => 'bg-rose-500 text-white',
                                        default => 'bg-slate-700 text-white',
                                    } }}' : 'border border-slate-800 text-slate-500'"
                                    class="rounded-lg px-2 py-1 text-[10px] font-black transition">
                                    {{ $unNivel }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($events->isNotEmpty())
                    <div class="border-b border-slate-800 bg-slate-950/50 p-2">
                        <label class="relative block">
                            <span class="sr-only">Buscar en el historial</span>
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>
                            <input type="search" x-model="busqueda" placeholder="Buscar en el historial…"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                        </label>
                    </div>
                @endif

                <div class="space-y-1 p-3">
                    @forelse ($events as $event)
                        <div x-show="(nivel === '' || nivel === @js($event->level)) && (busqueda === '' || @js(mb_strtolower(($event->type ?? '') . ' ' . ($event->message ?? ''))).includes(busqueda.toLowerCase()))"
                            class="flex items-start gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2">

                            <span
                                class="mt-0.5 shrink-0 rounded px-1.5 py-0.5 text-[9px] font-black uppercase
                                {{ match ($event->level) {
                                    'SUCCESS' => 'bg-emerald-500/15 text-emerald-300',
                                    'WARNING' => 'bg-amber-500/15 text-amber-300',
                                    'ERROR' => 'bg-rose-500/15 text-rose-300',
                                    default => 'bg-slate-800 text-slate-500',
                                } }}">
                                {{ $event->level }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[11px] font-black text-slate-300">{{ $event->type }}</p>
                                <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">{{ $event->message }}</p>
                            </div>

                            @if ($event->created_at)
                                <span class="shrink-0 font-mono text-[9px] text-slate-700">
                                    {{ $event->created_at->format('d/m H:i') }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-800 py-10 text-center text-[11px] text-slate-600">
                            Todavía no ha pasado nada aquí.
                        </p>
                    @endforelse
                </div>
            </section>


            {{-- ===================================================== --}}
            {{-- ELIMINAR --}}
            {{-- ===================================================== --}}

            @if ($competition->isDraft())
                @can('delete', $competition)
                    <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/5 px-4 py-3">

                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                            <x-omni-icon name="papelera" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-black text-white">Eliminar esta edición</p>
                            <p class="text-[10px] text-slate-500">
                                Todavía no ha empezado, así que puede borrarse. Una vez iniciada solo podrá
                                cancelarse.
                            </p>
                        </div>

                        <form method="POST"
                            action="{{ route('universes.competitions.destroy', [$universe, $competition]) }}"
                            data-omni-confirm data-confirm-variant="danger" data-confirm-icon="x"
                            data-confirm-title="Eliminar la edición"
                            data-confirm-message="Todavía no ha empezado, así que no se pierde nada jugado."
                            data-confirm-subject="{{ $competition->name }}"
                            data-confirm-detail="Se van con ella sus participantes y su reparto por puertas."
                            data-confirm-action="Sí, eliminarla">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="shrink-0 rounded-xl bg-rose-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-rose-400">
                                Eliminar
                            </button>
                        </form>
                    </section>
                @endcan
            @endif

        </div>
    </div>


    <script>
        function competidoresDeLaFicha(datos) {

            return {

                todos: datos,

                vista: 'grid',
                busqueda: '',
                estado: '',
                orden: 'seed',
                soloVivos: false,

                init() {
                    /* La forma de mirar se recuerda, como en la lista */
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.competition.participants') ?? '{}');
                        if (['grid', 'list', 'table', 'podio', 'estado'].includes(g.vista)) this.vista = g.vista;
                        if (typeof g.orden === 'string') this.orden = g.orden;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('orden', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem(
                            'omnimerge.competition.participants',
                            JSON.stringify({ vista: this.vista, orden: this.orden })
                        );
                    } catch (e) {}
                },

                get estados() {
                    return [...new Set(this.todos.map(c => c.estado))].filter(Boolean);
                },

                etiquetaEstado(valor) {
                    const uno = this.todos.find(c => c.estado === valor);

                    return uno ? uno.estadoTexto : valor;
                },

                /* Los estados que el Runtime usa de verdad */
                claseEstado(valor) {
                    return {
                        WAITING: 'bg-slate-800 text-slate-500',
                        ACTIVE: 'bg-emerald-500/15 text-emerald-300',
                        COMPETING: 'bg-emerald-500/15 text-emerald-300',
                        FINISHED: 'bg-cyan-500/15 text-cyan-300',
                        ELIMINATED: 'bg-rose-500/15 text-rose-300',
                        STRANDED: 'bg-amber-500/15 text-amber-300',
                    }[valor] ?? 'bg-slate-800 text-slate-500';
                },

                get hayFiltros() {
                    return this.busqueda !== '' || this.estado !== '' || this.soloVivos;
                },

                limpiar() {
                    this.busqueda = '';
                    this.estado = '';
                    this.soloVivos = false;
                },

                get filtrados() {
                    const texto = this.busqueda.trim().toLowerCase();

                    const lista = this.todos.filter(c => {

                        if (this.estado !== '' && c.estado !== this.estado) {
                            return false;
                        }

                        if (this.soloVivos && !c.sigueEnElMundo) {
                            return false;
                        }

                        if (texto !== '') {
                            const donde = [c.nombre, c.tipo, c.version, c.destino]
                                .filter(Boolean)
                                .join(' ')
                                .toLowerCase();

                            if (!donde.includes(texto)) {
                                return false;
                            }
                        }

                        return true;
                    });

                    const criterios = {
                        seed: (a, b) => a.seed - b.seed,
                        puntos: (a, b) => b.puntos - a.puntos || a.seed - b.seed,
                        ganados: (a, b) => b.ganados - a.ganados || b.puntos - a.puntos,
                        nombre: (a, b) => a.nombre.localeCompare(b.nombre),

                        /* Quien todavía no tiene puesto decidido, al final */
                        puesto: (a, b) =>
                            (a.puesto ?? Infinity) - (b.puesto ?? Infinity) || a.seed - b.seed,
                    };

                    return lista.sort(criterios[this.orden] ?? criterios.seed);
                },

                get porPuesto() {
                    return [...this.filtrados].sort(
                        (a, b) => (a.puesto ?? Infinity) - (b.puesto ?? Infinity) || b.puntos - a.puntos
                    );
                },

                get agrupados() {
                    const mapa = new Map();

                    for (const c of this.filtrados) {
                        if (!mapa.has(c.estado)) {
                            mapa.set(c.estado, {
                                clave: c.estado,
                                etiqueta: c.estadoTexto,
                                gente: [],
                            });
                        }

                        mapa.get(c.estado).gente.push(c);
                    }

                    return [...mapa.values()];
                },
            };
        }
    </script>

</x-universe-layout>

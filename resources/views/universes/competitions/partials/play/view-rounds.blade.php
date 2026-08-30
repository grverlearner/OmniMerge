@php
    /*
     * Todos contra todos, con tres formas de mirarlo.
     *
     *   JORNADAS       las batallas mandan: se ve el calendario y se juega.
     *   MIXTA          batallas del tamaño que elijas + la tabla al lado.
     *   CLASIFICACIÓN  la tabla manda: retratos grandes, batallas pequeñas.
     *
     * Son la misma información con distinta jerarquía. Mientras se juega
     * interesa el calendario; cuando la liga avanza, interesa la tabla; y
     * la mixta sirve para seguir ambas cosas a la vez.
     */

    $rounds = $block['rounds']->sortKeys();

    $nodeId = (string) $block['phase']->node_id;

    $points = ($pointsByPhase ?? collect())->get($nodeId) ?? collect();

    /*
     * Las cifras que enseña la tabla son las que USA la fase para ordenar.
     *
     * Antes venian de PhasePointsService, que suma game_encounters — solo
     * los enfrentamientos que pasaron por el motor de juego—. En una fase
     * de 45 batallas con 18 registradas, esa suma es parcial: alguien
     * aparecia con «—» y otro con una diferencia que no era la suya.
     *
     * El resultado era una tabla que se contradecia sola: ordenada por el
     * puesto real de la fase —puntos, diferencia, anotados— pero enseñando
     * unos numeros con los que ese orden no cuadraba, y de ahi la sensacion
     * de que la etiqueta «Clasificado» estaba puesta en la fila equivocada.
     *
     * La clasificacion de la fase ya trae score_for / score_against /
     * score_difference, calculados sobre TODAS las batallas. Son los que
     * deciden, asi que son los que se enseñan.
     */
    $showPoints = (bool) ($tracksPoints ?? false);
    $puntosDe = fn($row) => [
        'for' => (int) $row->score_for,
        'against' => (int) $row->score_against,
        'difference' => (int) $row->score_difference,
    ];


    /*
     * El orden lo decide la FASE, no esta pantalla.
     *
     * Aquí se reordenaba por puntos y, en caso de empate, por la diferencia
     * de los puntos ANOTADOS —las columnas A favor / En contra—. Y esos
     * números son otra cosa: PhasePointsService los suma de game_encounters
     * para enseñar el rendimiento bruto al lado de la tabla, y su propia
     * documentación dice que no deciden nada.
     *
     * Quien decide es el motor de la fase, con los desempates que tenga
     * configurados, y deja su veredicto en `position`. Al ordenar de otra
     * manera salían dos verdades: la tabla ponía a uno por encima del corte
     * y la etiqueta —que sí lee al motor— decía «Eliminado», mientras que
     * abajo alguien aparecía «Clasificado». Ninguna de las dos estaba mal;
     * estaban ordenadas por criterios distintos.
     *
     * Con `position` mandando, el orden, la línea de corte y las etiquetas
     * salen todos de la misma fuente y no pueden contradecirse.
     */
    $conPuestos = $block['standings']->isNotEmpty()
        && $block['standings']->every(fn($row) => $row->position !== null);

    /*
     * Solo si el motor todavía no ha puesto puestos —una fase recién
     * abierta— se ordena aquí, y entonces da igual porque nadie está
     * clasificado ni eliminado.
     */

    $standings = $conPuestos
        ? $block['standings']->sortBy(fn($row) => (int) $row->position)->values()
        : $block['standings']
            ->sortBy([
                fn($a, $b) => (int) $b->points <=> (int) $a->points,
                fn($a, $b) => (int) $b->wins <=> (int) $a->wins,
            ])
            ->values();

    /*
     * Quien esta pasando y quien esta quedando fuera.
     *
     * Si la fase ya resolvio, mandan los estados reales (ADVANCED /
     * ELIMINATED). Si sigue en juego, se dibuja el CORTE previsto que sale
     * de las puertas de salida: es una prevision, no un resultado, y se
     * dice asi en pantalla.
     */
    $cutInfo = ($qualification ?? collect())->get($nodeId) ?? ['cut' => null, 'label' => null];

    $cut = $cutInfo['cut'] ?? null;

    /* Un corte mayor que el numero de participantes no separa a nadie */
    $cutApplies = $cut !== null && $cut < $standings->count();

    $phaseResolved = $standings->contains(fn($row) => $row->status === 'ADVANCED');

    $rowState = function ($row, int $index) use ($cutApplies, $cut, $phaseResolved) {

        if ($phaseResolved) {
            return $row->status === 'ADVANCED' ? 'in' : 'out';
        }

        if (! $cutApplies) {
            return 'open';
        }

        return $index < $cut ? 'in' : 'out';
    };
@endphp

<div x-data="{
        /*
         * Se recuerda dónde estabas.
         *
         * Volver de una batalla recarga la página —el cuadro y las tablas
         * cambian con cada resultado—, así que sin esto siempre aparecías
         * en Jornadas y en la jornada 1, aunque estuvieras en la 2 y en
         * modo Mixta. La clave incluye la fase: dos fases de la misma
         * competición no comparten preferencia.
         */
        storeKey: 'omnimerge.arena.{{ $competition->id }}.{{ $block['phase']->id }}',

        get modeKey() { return this.storeKey + '.mode'; },
        get roundKey() { return this.storeKey + '.round'; },

        mode: localStorage.getItem('omnimerge.arena.{{ $competition->id }}.{{ $block['phase']->id }}.mode')
            || 'rounds',

        setMode(value) {
            this.mode = value;
            localStorage.setItem(this.modeKey, value);
        },

        /*
         * Cuantas batallas caben por fila en el modo mixto.
         *
         * El tamano de cada tarjeta NO se toca directamente: la tarjeta
         * ocupa el ancho de su celda, asi que mas columnas significa mas
         * pequenas. Un solo numero controla el tamano sin duplicar
         * variantes de la tarjeta.
         */
        columns: parseInt(localStorage.getItem('omnimerge.arena.columns') || '3', 10),

        setColumns(value) {
            this.columns = value;
            localStorage.setItem('omnimerge.arena.columns', value);
        },

        get columnClass() {
            return {
                1: 'grid-cols-1',
                2: 'grid-cols-2',
                3: 'grid-cols-3',
                4: 'grid-cols-4',
                5: 'grid-cols-5',
                6: 'grid-cols-6',
            }[this.columns] ?? 'grid-cols-3';
        },

        /*
         * Tamaño del retrato en la clasificación.
         *
         * Las clases van escritas enteras y no construidas: Tailwind solo
         * genera lo que encuentra literalmente en la plantilla, así que
         * 'h-' + n nunca existiría en el CSS final.
         */
        portrait: parseInt(localStorage.getItem('omnimerge.arena.portrait') || '2', 10),

        setPortrait(value) {
            this.portrait = value;
            localStorage.setItem('omnimerge.arena.portrait', value);
        },

        get portraitClass() {
            return {
                1: 'h-10 w-10',
                2: 'h-14 w-14',
                3: 'h-20 w-20',
                4: 'h-28 w-28',
            }[this.portrait] ?? 'h-14 w-14';
        },

        /* El retrato de la columna estrecha del modo mixto */
        get portraitCompactClass() {
            return {
                1: 'h-8 w-8',
                2: 'h-10 w-10',
                3: 'h-14 w-14',
                4: 'h-20 w-20',
            }[this.portrait] ?? 'h-10 w-10';
        },

        get portraitLabel() {
            return {
                1: 'Pequeño',
                2: 'Mediano',
                3: 'Grande',
                4: 'Enorme',
            }[this.portrait] ?? 'Mediano';
        },
    }">

    {{-- SELECTOR DE VISTA --}}

    <div class="flex items-center justify-between gap-3 border-b border-slate-800 px-5 py-2.5">

        <div class="flex items-center gap-1 rounded-xl bg-slate-950 p-1">

            <button type="button" @click="setMode('rounds')"
                :class="mode === 'rounds' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                ⚔ Jornadas
            </button>

            <button type="button" @click="setMode('mixed')"
                :class="mode === 'mixed' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                ⊞ Mixta
            </button>

            <button type="button" @click="setMode('table')"
                :class="mode === 'table' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                🏅 Clasificación
            </button>

        </div>


        {{-- Tamano de las tarjetas, solo donde importa --}}

        <div x-show="mode === 'mixed'" x-cloak class="flex items-center gap-2">

            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                Tamaño
            </span>

            <div class="flex items-center gap-0.5 rounded-lg bg-slate-950 p-0.5">
                <template x-for="n in [1, 2, 3, 4, 5, 6]" :key="n">
                    <button type="button" @click="setColumns(n)"
                        :class="columns === n
                            ? 'bg-slate-700 text-white'
                            : 'text-slate-600 hover:text-slate-400'"
                        class="rounded px-2 py-1 font-mono text-[10px] font-black transition"
                        x-text="n"></button>
                </template>
            </div>

            <span class="text-[9px] text-slate-600">
                <span x-text="columns"></span> por fila
            </span>

        </div>

        {{-- Tamaño del retrato, donde hay clasificación --}}

        <div x-show="mode === 'table' || mode === 'mixed'" x-cloak
            class="flex items-center gap-2">

            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                Retrato
            </span>

            <div class="flex items-center gap-0.5 rounded-lg bg-slate-950 p-0.5">
                <template x-for="n in [1, 2, 3, 4]" :key="n">
                    <button type="button" @click="setPortrait(n)"
                        :class="portrait === n
                            ? 'bg-slate-700 text-white'
                            : 'text-slate-600 hover:text-slate-400'"
                        class="rounded px-2 py-1 text-[10px] font-black transition">
                        <span x-show="n === 1">S</span>
                        <span x-show="n === 2">M</span>
                        <span x-show="n === 3">L</span>
                        <span x-show="n === 4">XL</span>
                    </button>
                </template>
            </div>

            <span class="hidden text-[9px] text-slate-600 sm:inline" x-text="portraitLabel"></span>

        </div>

        @if ($cutApplies)
            <p class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Pasan {{ $cut }}
                @if ($cutInfo['label'])
                    <span class="text-slate-600">· {{ $cutInfo['label'] }}</span>
                @endif
            </p>
        @endif

        @if ($showPoints)
            <p class="text-[10px] font-bold text-slate-500">
                {{ $pointsLabel ?? 'Puntos' }} contabilizados
            </p>
        @endif

    </div>


    {{-- ============================================ --}}
    {{-- MODO JORNADAS · las batallas mandan --}}
    {{-- ============================================ --}}

    <div x-show="mode === 'rounds'" class="grid gap-5 p-5 xl:grid-cols-[1fr_340px]">

        <div x-data="{
                round: parseInt(
                    localStorage.getItem('omnimerge.arena.{{ $competition->id }}.{{ $block['phase']->id }}.round')
                        || '{{ $rounds->keys()->first() ?? 0 }}',
                    10
                ),

                setRound(value) {
                    this.round = value;
                    localStorage.setItem(
                        'omnimerge.arena.{{ $competition->id }}.{{ $block['phase']->id }}.round',
                        value
                    );
                },
            }">

            @if ($rounds->count() > 1)
                <div class="mb-4 flex flex-wrap gap-1.5">
                    @foreach ($rounds as $number => $matches)
                        <button type="button" @click="setRound({{ $number }})"
                            :class="round === {{ $number }}
                                ? 'bg-violet-500 text-white'
                                : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                            class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                            J{{ $number }}
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach ($rounds as $number => $matches)
                <div x-show="round === {{ $number }}" x-cloak>

                    <p class="mb-3 text-[10px] font-black uppercase tracking-wider text-slate-400">
                        Jornada {{ $number }}
                    </p>

                    <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($matches as $match)
                            @include('universes.competitions.partials.play.match-chip', ['match' => $match])
                        @endforeach
                    </div>

                </div>
            @endforeach

        </div>


        {{-- Clasificación compacta, al lado --}}

        <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">

            <p class="mb-3 text-[10px] font-black uppercase tracking-wider text-slate-400">
                Clasificación
            </p>

            <div class="space-y-1">

                @foreach ($standings as $index => $row)

                    @php
                        $rowPoints = $puntosDe($row);
                        $state = $rowState($row, $index);
                    @endphp

                    {{-- Linea de corte: separa a los que pasan de los que no --}}
                    @if ($cutApplies && $index === $cut)
                        <div class="flex items-center gap-2 py-1">
                            <span class="h-px flex-1 bg-rose-500/40"></span>
                            <span class="text-[8px] font-black uppercase tracking-wider text-rose-500">
                                corte
                            </span>
                            <span class="h-px flex-1 bg-rose-500/40"></span>
                        </div>
                    @endif

                    <div @class([
                        'flex items-center gap-2.5 rounded-lg border-l-2 px-2 py-1.5',
                        'border-emerald-400 bg-emerald-500/10' => $state === 'in',
                        'border-rose-500/50 bg-rose-500/5' => $state === 'out',
                        'border-transparent' => $state === 'open',
                    ])>

                        <span class="w-5 shrink-0 text-center font-mono text-[11px] font-black text-slate-500">
                            {{ $index + 1 }}
                        </span>

                        <div class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-800">
                            @if ($row->universeEntity?->image_url)
                                <img src="{{ $row->universeEntity->image_url }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>

                        <span class="min-w-0 flex-1 truncate text-[11px] font-semibold text-slate-300">
                            {{ $row->participant_name }}
                        </span>

                        @if ($rowPoints)
                            <span class="shrink-0 font-mono text-[10px] {{ $rowPoints['difference'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $rowPoints['difference'] >= 0 ? '+' : '' }}{{ $rowPoints['difference'] }}
                            </span>
                        @endif

                        <span class="w-7 shrink-0 text-right font-mono text-xs font-black text-violet-300">
                            {{ $row->points }}
                        </span>

                    </div>
                @endforeach

            </div>

        </div>

    </div>


    {{-- ============================================ --}}
    {{-- MODO MIXTO · batallas ajustables + tabla completa --}}
    {{-- ============================================ --}}
    {{--
        Las batallas a la izquierda, con el tamaño que elijas; la
        clasificación entera a la derecha, en una columna estrecha.

        La tabla lleva las mismas cifras que el modo clasificación, pero
        apilada en vez de en columnas: en 300 px no cabe una tabla ancha,
        y recortar datos sería peor que reorganizarlos.
    --}}

    <div x-show="mode === 'mixed'" x-cloak class="grid gap-5 p-5 xl:grid-cols-[1fr_300px]">

        {{-- BATALLAS --}}

        <div x-data="{
                round: localStorage.getItem('omnimerge.arena.{{ $competition->id }}.{{ $block['phase']->id }}.mixedRound')
                    || 'all',

                setRound(value) {
                    this.round = value;
                    localStorage.setItem(
                        'omnimerge.arena.{{ $competition->id }}.{{ $block['phase']->id }}.mixedRound',
                        value
                    );
                },
            }">

            <div class="mb-3 flex flex-wrap items-center gap-1.5">

                <button type="button" @click="setRound('all')"
                    :class="round === 'all'
                        ? 'bg-violet-500 text-white'
                        : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                    class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                    Todas
                </button>

                @foreach ($rounds as $number => $matches)
                    <button type="button" @click="setRound('{{ $number }}')"
                        :class="String(round) === '{{ $number }}'
                            ? 'bg-violet-500 text-white'
                            : 'bg-slate-800 text-slate-400 hover:text-slate-200'"
                        class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                        J{{ $number }}
                    </button>
                @endforeach

            </div>

            @foreach ($rounds as $number => $matches)
                <div x-show="round === 'all' || String(round) === '{{ $number }}'" class="mb-4">

                    <p x-show="round === 'all'"
                        class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Jornada {{ $number }}
                    </p>

                    <div class="grid gap-2.5" :class="columnClass">
                        @foreach ($matches as $match)
                            @include('universes.competitions.partials.play.match-chip', ['match' => $match])
                        @endforeach
                    </div>

                </div>
            @endforeach

        </div>


        {{-- CLASIFICACIÓN COMPLETA, EN COLUMNA --}}

        <div class="rounded-2xl border border-slate-800 bg-slate-950/50">

            <div class="flex items-center justify-between gap-2 border-b border-slate-800 px-4 py-2.5">

                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Clasificación
                </p>

                @if ($showPoints)
                    <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-black text-slate-500">
                        con {{ mb_strtolower($pointsLabel ?? 'puntos') }}
                    </span>
                @endif

            </div>

            <div class="divide-y divide-slate-800/60">

                @foreach ($standings as $index => $row)

                    @php
                        $rowPoints = $puntosDe($row);
                        $place = $index + 1;
                        $state = $rowState($row, $index);
                    @endphp

                    @if ($cutApplies && $index === $cut)
                        <div class="flex items-center gap-2 bg-slate-950 px-3 py-1">
                            <span class="h-px flex-1 bg-rose-500/40"></span>
                            <span class="text-[8px] font-black uppercase tracking-wider text-rose-500">
                                corte
                            </span>
                            <span class="h-px flex-1 bg-rose-500/40"></span>
                        </div>
                    @endif

                    <div @class([
                        'border-l-2 px-3 py-2.5',
                        'border-emerald-400 bg-emerald-500/5' => $state === 'in',
                        'border-rose-500/50 bg-rose-500/[0.03]' => $state === 'out',
                        'border-transparent' => $state === 'open',
                    ])>

                        {{-- Identidad --}}
                        <div class="flex items-center gap-2">

                            <span @class([
                                'flex h-5 w-5 shrink-0 items-center justify-center rounded font-mono text-[10px] font-black',
                                'bg-amber-400 text-slate-950' => $place === 1,
                                'bg-slate-400 text-slate-950' => $place === 2,
                                'bg-orange-600 text-white' => $place === 3,
                                'text-slate-600' => $place > 3,
                            ])>
                                {{ $place }}
                            </span>

                            <div class="shrink-0 overflow-hidden rounded-lg bg-slate-800 transition-all"
                                :class="portraitCompactClass">
                                @if ($row->universeEntity?->image_url)
                                    <img src="{{ $row->universeEntity->image_url }}" alt=""
                                        class="h-full w-full object-cover">
                                @endif
                            </div>

                            <p class="min-w-0 flex-1 truncate text-[11px] font-black text-white">
                                {{ $row->participant_name }}
                            </p>

                            <span class="shrink-0 font-mono text-sm font-black text-violet-300">
                                {{ $row->points }}
                            </span>

                        </div>


                        {{-- Detalle, apilado para caber en poco ancho --}}
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 pl-7 text-[9px]">

                            <span class="text-slate-600">
                                <span class="font-mono text-slate-400">{{ $row->matches }}</span> PJ
                            </span>

                            <span class="text-emerald-500">
                                <span class="font-mono font-black">{{ $row->wins }}</span>V
                            </span>

                            <span class="text-slate-500">
                                <span class="font-mono font-black">{{ $row->draws }}</span>E
                            </span>

                            <span class="text-rose-500">
                                <span class="font-mono font-black">{{ $row->losses }}</span>D
                            </span>

                            @if ($rowPoints)
                                <span class="ml-auto font-mono text-slate-500"
                                    title="A favor / en contra">
                                    {{ $rowPoints['for'] }}<span class="text-slate-700">:</span>{{ $rowPoints['against'] }}
                                </span>

                                <span class="font-mono font-black {{ $rowPoints['difference'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $rowPoints['difference'] >= 0 ? '+' : '' }}{{ $rowPoints['difference'] }}
                                </span>
                            @endif

                            @if ($state === 'in')
                                <span class="font-black text-emerald-400">▲</span>
                            @elseif ($state === 'out')
                                <span class="font-black text-rose-400/70">▼</span>
                            @endif

                        </div>

                    </div>
                @endforeach

            </div>

        </div>

    </div>


    {{-- ============================================ --}}
    {{-- MODO CLASIFICACIÓN · la tabla manda --}}
    {{-- ============================================ --}}

    <div x-show="mode === 'table'" x-cloak class="p-5">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[640px]">

                <thead>
                    <tr class="border-b border-slate-800 text-[9px] font-black uppercase tracking-wider text-slate-500">
                        <th class="px-2 py-2 text-left">#</th>
                        <th class="px-2 py-2 text-left">Competidor</th>
                        <th class="px-2 py-2 text-center">PJ</th>
                        <th class="px-2 py-2 text-center">PG</th>
                        <th class="px-2 py-2 text-center">PE</th>
                        <th class="px-2 py-2 text-center">PP</th>

                        @if ($showPoints)
                            <th class="px-2 py-2 text-center text-emerald-500">A favor</th>
                            <th class="px-2 py-2 text-center text-rose-500">En contra</th>
                            <th class="px-2 py-2 text-center">Dif.</th>
                        @endif

                        <th class="px-2 py-2 text-center text-violet-400">PTS</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($standings as $index => $row)

                        @php
                            $rowPoints = $puntosDe($row);
                            $place = $index + 1;
                            $state = $rowState($row, $index);
                        @endphp

                        {{-- Linea de corte, como fila propia --}}
                        @if ($cutApplies && $index === $cut)
                            <tr>
                                <td colspan="{{ $showPoints ? 10 : 7 }}" class="px-2 py-1">
                                    <div class="flex items-center gap-2">
                                        <span class="h-px flex-1 bg-rose-500/40"></span>
                                        <span class="rounded-full bg-rose-500/15 px-3 py-0.5 text-[9px] font-black uppercase tracking-wider text-rose-400">
                                            Corte de clasificación · pasan {{ $cut }}
                                        </span>
                                        <span class="h-px flex-1 bg-rose-500/40"></span>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        <tr @class([
                            'border-b border-slate-800/60 border-l-4 transition hover:bg-slate-900/40',
                            'border-l-emerald-400 bg-emerald-500/5' => $state === 'in',
                            'border-l-rose-500/60 bg-rose-500/[0.03]' => $state === 'out',
                            'border-l-transparent' => $state === 'open',
                        ])>

                            <td class="px-2 py-3">
                                <span @class([
                                    'flex h-7 w-7 items-center justify-center rounded-lg font-mono text-xs font-black',
                                    'bg-amber-400 text-slate-950' => $place === 1,
                                    'bg-slate-400 text-slate-950' => $place === 2,
                                    'bg-orange-600 text-white' => $place === 3,
                                    'text-slate-500' => $place > 3,
                                ])>
                                    {{ $place }}
                                </span>
                            </td>

                            {{-- Retrato grande: es lo que el usuario quería ver --}}
                            <td class="px-2 py-3">
                                <div class="flex items-center gap-3">

                                    <div class="shrink-0 overflow-hidden rounded-xl bg-slate-800 ring-1 ring-slate-700 transition-all"
                                        :class="portraitClass">
                                        @if ($row->universeEntity?->image_url)
                                            <img src="{{ $row->universeEntity->image_url }}"
                                                alt="{{ $row->participant_name }}"
                                                class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-lg opacity-30">✦</div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-white">
                                            {{ $row->participant_name }}
                                        </p>

                                        @if ($state === 'in')
                                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-400">
                                                {{ $phaseResolved ? '▲ Clasificado' : '▲ Pasando' }}
                                            </span>
                                        @elseif ($state === 'out')
                                            <span class="text-[9px] font-black uppercase tracking-wider text-rose-400/80">
                                                {{ $phaseResolved ? '▼ Eliminado' : '▼ Fuera' }}
                                            </span>
                                        @endif
                                    </div>

                                </div>
                            </td>

                            <td class="px-2 py-3 text-center font-mono text-xs text-slate-400">{{ $row->matches }}</td>
                            <td class="px-2 py-3 text-center font-mono text-xs font-black text-emerald-400">{{ $row->wins }}</td>
                            <td class="px-2 py-3 text-center font-mono text-xs text-slate-500">{{ $row->draws }}</td>
                            <td class="px-2 py-3 text-center font-mono text-xs text-rose-400">{{ $row->losses }}</td>

                            @if ($showPoints)
                                <td class="px-2 py-3 text-center font-mono text-xs text-emerald-300">
                                    {{ $rowPoints['for'] ?? '—' }}
                                </td>

                                <td class="px-2 py-3 text-center font-mono text-xs text-rose-300">
                                    {{ $rowPoints['against'] ?? '—' }}
                                </td>

                                <td class="px-2 py-3 text-center">
                                    @if ($rowPoints)
                                        <span class="font-mono text-xs font-black {{ $rowPoints['difference'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                            {{ $rowPoints['difference'] >= 0 ? '+' : '' }}{{ $rowPoints['difference'] }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-600">—</span>
                                    @endif
                                </td>
                            @endif

                            <td class="px-2 py-3 text-center">
                                <span class="font-mono text-lg font-black text-violet-300">{{ $row->points }}</span>
                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>


        @if ($showPoints)
            <p class="mt-3 text-[10px] leading-relaxed text-slate-500">
                El orden y el corte los decide <strong class="font-black text-slate-400">la
                fase</strong>, con los desempates que tenga configurados.
                <strong class="font-black text-slate-400">A favor</strong> y
                <strong class="font-black text-slate-400">en contra</strong> se enseñan al lado
                como lo que son —lo que cada uno anotó y encajó— y no deciden el puesto: por eso
                pueden no acompañar al orden.
            </p>
        @endif


        {{-- Batallas en pequeño, debajo --}}

        <div class="mt-6">

            <p class="mb-3 text-[10px] font-black uppercase tracking-wider text-slate-500">
                Batallas · pulsa para jugar
            </p>

            <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($block['matches'] as $match)
                    @include('universes.competitions.partials.play.match-chip', ['match' => $match])
                @endforeach
            </div>

        </div>

    </div>

</div>

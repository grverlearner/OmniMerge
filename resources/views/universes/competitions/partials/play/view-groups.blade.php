@php
    /*
     * FASE DE GRUPOS
     *
     * Un grupo es una liga pequeña con dos lecturas que no se sustituyen:
     * la TABLA dice cómo va, las JORNADAS dicen qué toca jugar. Antes solo
     * se veía la tabla, así que la fase parecía un resumen de algo que
     * ocurría en otro sitio: no había forma de disputar una batalla.
     *
     * Tres modos, porque son tres preguntas distintas:
     *
     *   Grupos        cada grupo entero — su tabla y sus batallas
     *   Jornadas      una jornada, los cuatro grupos a la vez
     *   Clasificación solo las tablas, para comparar de un vistazo
     *
     * El ancho de los recuadros se ajusta arriba, igual que en la liga.
     */

    $groups = $block['groups']->sortKeys();

    $matchesByGroup = $block['matches']->groupBy('group_label');

    /* Jornadas reales, compartidas por todos los grupos */
    $rounds = $block['matches']
        ->pluck('round_number')
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $matchesByRound = $block['matches']->groupBy('round_number');

    $nodeId = (string) $block['phase']->node_id;

    /*
     * Puntos reales de la fase, si el juego los registra. La clasificación
     * del motor solo sabe de victorias; esto añade lo que cada uno hizo y
     * encajó de verdad.
     */
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

    /*
     * Cuántos pasan de cada grupo. En una fase de grupos el corte es por
     * grupo, no por fase: cada tabla lleva su propia línea.
     *
     * Mientras la fase se juega es una PREVISIÓN sobre la tabla actual; en
     * cuanto resuelve mandan los estados reales que reparte el grafo.
     */
    $cutInfo = ($qualification ?? collect())->get($nodeId) ?? [];

    $groupCut = $cutInfo['group_cut'] ?? null;

    $phaseResolved = $block['standings']->contains(
        fn($row) => $row->status === 'ADVANCED'
    );

    $rowState = function ($row, int $index, int $total) use ($groupCut, $phaseResolved) {

        if ($phaseResolved) {
            return $row->status === 'ADVANCED' ? 'in' : 'out';
        }

        if ($groupCut === null || $groupCut >= $total) {
            return 'open';
        }

        return $index < $groupCut ? 'in' : 'out';
    };

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
    $order = function ($rows) {

        $conPuestos = $rows->isNotEmpty()
            && $rows->every(fn($row) => $row->position !== null);

    /*
     * Solo si el motor todavía no ha puesto puestos —una fase recién
     * abierta— se ordena aquí, y entonces da igual porque nadie está
     * clasificado ni eliminado.
     */

        return $conPuestos
            ? $rows->sortBy(fn($row) => (int) $row->position)->values()
            : $rows->sortBy([
                fn($a, $b) => (int) $b->points <=> (int) $a->points,
                fn($a, $b) => (int) $b->wins <=> (int) $a->wins,
            ])->values();
    };

    /* La jornada por la que se entra: la primera sin terminar */
    $firstOpen = $rounds
        ->first(fn($number) => $matchesByRound
            ->get($number, collect())
            ->contains(fn($match) => $match->status !== 'COMPLETED'))
        ?? $rounds->first()
        ?? 1;

    $key = 'omnimerge.arena.' . $competition->id . '.' . $nodeId;
@endphp

<div x-data="{
        mode: localStorage.getItem('{{ $key }}.gsmode') ?? 'groups',
        columns: Number(localStorage.getItem('{{ $key }}.gscols') ?? 2),
        round: Number(localStorage.getItem('{{ $key }}.gsround') ?? {{ $firstOpen }}),
        rounds: {{ Illuminate\Support\Js::from($rounds) }},

        setMode(value) {
            this.mode = value;
            localStorage.setItem('{{ $key }}.gsmode', value);
        },

        setColumns(value) {
            this.columns = value;
            localStorage.setItem('{{ $key }}.gscols', value);
        },

        setRound(value) {
            if (! this.rounds.includes(value)) return;
            this.round = value;
            localStorage.setItem('{{ $key }}.gsround', value);
        },

        get roundIndex() {
            return this.rounds.indexOf(this.round);
        },

        /*
         * El ancho se enlaza, no se duplica: repetir el grid una vez por
         * tamaño metía cuatro copias de las 48 batallas en el DOM para
         * enseñar una.
         *
         * Las clases se escriben enteras aquí, y aquí es un sitio que
         * Tailwind lee. Compuestas ('grid-cols-' + n) o detrás de una
         * variable de PHP no llegarían nunca al CSS.
         */
        get gridClass() {
            return {
                1: 'grid-cols-1',
                2: 'grid-cols-1 lg:grid-cols-2',
                3: 'grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3',
                4: 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4',
            }[this.columns] ?? 'grid-cols-1 lg:grid-cols-2';
        },
    }">

    {{-- ============================================ --}}
    {{-- CONTROLES --}}
    {{-- ============================================ --}}

    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 bg-slate-950/40 px-5 py-2.5">

        {{-- Modo --}}
        <div class="flex items-center gap-1 rounded-xl bg-slate-900 p-1">

            <button type="button" @click="setMode('groups')"
                :class="mode === 'groups' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                Grupos
            </button>

            <button type="button" @click="setMode('rounds')"
                :class="mode === 'rounds' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                Jornadas
            </button>

            <button type="button" @click="setMode('table')"
                :class="mode === 'table' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="rounded-lg px-3 py-1.5 text-[11px] font-black transition">
                Clasificación
            </button>

        </div>


        {{-- Jornada, solo cuando se está mirando una --}}
        <div x-show="mode === 'rounds'" x-cloak class="flex items-center gap-1.5">

            <button type="button" @click="setRound(rounds[roundIndex - 1])"
                :disabled="roundIndex <= 0"
                class="rounded-lg border border-slate-700 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-500 hover:text-white disabled:opacity-25">
                ←
            </button>

            <span class="min-w-[92px] rounded-lg bg-slate-900 px-3 py-1.5 text-center text-[11px] font-black text-violet-300">
                Jornada <span x-text="round"></span>
            </span>

            <button type="button" @click="setRound(rounds[roundIndex + 1])"
                :disabled="roundIndex >= rounds.length - 1"
                class="rounded-lg border border-slate-700 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-500 hover:text-white disabled:opacity-25">
                →
            </button>

        </div>


        {{-- Ancho de los recuadros --}}
        <div class="ml-auto flex items-center gap-1.5">

            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                Tamaño
            </span>

            @foreach ([1, 2, 3, 4] as $option)
                <button type="button" @click="setColumns({{ $option }})"
                    :class="columns === {{ $option }} ? 'bg-slate-700 text-white' : 'bg-slate-900 text-slate-600 hover:text-slate-400'"
                    class="h-6 w-6 rounded-md text-[10px] font-black transition">
                    {{ $option }}
                </button>
            @endforeach

        </div>

    </div>


    {{-- ============================================ --}}
    {{-- MODO GRUPOS · cada grupo con su tabla y sus batallas --}}
    {{-- ============================================ --}}

    <div x-show="mode === 'groups'" x-cloak class="p-5">

        <div class="grid gap-5" :class="gridClass">

                @foreach ($groups as $groupLabel => $rows)

                    @php
                        $ordered = $order($rows);
                        $groupMatches = $matchesByGroup->get($groupLabel, collect());
                        $groupDone = $groupMatches->where('status', 'COMPLETED')->count();
                    @endphp

                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/50">

                        <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">

                            <p class="text-[11px] font-black uppercase tracking-wider text-violet-300">
                                {{ $groupLabel ?: 'Grupo único' }}
                            </p>

                            <span class="ml-auto font-mono text-[10px] font-black text-slate-500">
                                {{ $groupDone }}/{{ $groupMatches->count() }}
                            </span>

                        </div>

                        @include('universes.competitions.partials.play.group-table', [
                            'ordered' => $ordered,
                            'points' => $points,
                            'showPoints' => $showPoints,
                            'rowState' => $rowState,
                            'groupCut' => $groupCut,
                            'phaseResolved' => $phaseResolved,
                        ])

                        {{-- BATALLAS, POR JORNADA --}}

                        @if ($groupMatches->isNotEmpty())

                            <div class="space-y-3 border-t border-slate-800 p-3">

                                @foreach ($groupMatches->groupBy('round_number') as $number => $roundMatches)

                                    <div>
                                        <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                                            Jornada {{ $number }}
                                        </p>

                                        <div class="grid gap-2 grid-cols-2">
                                            @foreach ($roundMatches as $match)
                                                @include('universes.competitions.partials.play.match-chip', ['match' => $match])
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        @endif

                    </div>
                @endforeach

        </div>

    </div>


    {{-- ============================================ --}}
    {{-- MODO JORNADAS · una jornada, todos los grupos --}}
    {{-- ============================================ --}}

    <div x-show="mode === 'rounds'" x-cloak class="p-5">

        @foreach ($rounds as $number)

            <div x-show="round === {{ $number }}" x-cloak>

                @php
                    $roundMatches = $matchesByRound->get($number, collect());
                    $byGroup = $roundMatches->groupBy('group_label');
                    $roundDone = $roundMatches->where('status', 'COMPLETED')->count();
                @endphp

                <div class="mb-4 flex items-center gap-3">

                    <h4 class="text-sm font-black text-white">
                        Jornada {{ $number }}
                    </h4>

                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 font-mono text-[10px] font-black text-slate-400">
                        {{ $roundDone }}/{{ $roundMatches->count() }}
                    </span>

                    @if ($roundDone < $roundMatches->count() && ! $readonly)
                        <span class="text-[10px] font-bold text-violet-400/70">
                            Pulsa una batalla para disputarla
                        </span>
                    @endif

                </div>

                <div class="grid gap-4" :class="gridClass">

                        @foreach ($byGroup as $groupLabel => $groupMatches)

                            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/50">

                                <div class="border-b border-slate-800 px-4 py-2">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-violet-300">
                                        {{ $groupLabel ?: 'Grupo único' }}
                                    </p>
                                </div>

                                <div class="grid gap-2 p-3 grid-cols-2">
                                    @foreach ($groupMatches as $match)
                                        @include('universes.competitions.partials.play.match-chip', ['match' => $match])
                                    @endforeach
                                </div>

                            </div>
                        @endforeach

                </div>

            </div>
        @endforeach

    </div>


    {{-- ============================================ --}}
    {{-- MODO CLASIFICACIÓN · solo las tablas --}}
    {{-- ============================================ --}}

    <div x-show="mode === 'table'" x-cloak class="p-5">

        <div class="grid gap-4" :class="gridClass">

                @foreach ($groups as $groupLabel => $rows)

                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/50">

                        <div class="border-b border-slate-800 px-4 py-2">
                            <p class="text-[10px] font-black uppercase tracking-wider text-violet-300">
                                {{ $groupLabel ?: 'Grupo único' }}
                            </p>
                        </div>

                        @include('universes.competitions.partials.play.group-table', [
                            'ordered' => $order($rows),
                            'points' => $points,
                            'showPoints' => $showPoints,
                            'rowState' => $rowState,
                            'groupCut' => $groupCut,
                            'phaseResolved' => $phaseResolved,
                        ])

                    </div>
                @endforeach

        </div>

    </div>

</div>

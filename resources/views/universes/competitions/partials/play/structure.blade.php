@php
    /*
     * ETAPA 2 · La estructura de cada fase.
     *
     * Los datos vienen del historial, que desde la Fase 8 funciona igual
     * con una competición en curso: no hay lógica paralela.
     */

    $totalMatches = $phaseBlocks->sum(fn($block) => $block['matches']->count());

    $needsOpening = $phaseBlocks->isEmpty() || $totalMatches === 0;

    /*
     * Una ventana por fase.
     *
     * Apiladas en un solo scroll, un torneo de tres fases obligaba a
     * bajar por la liga entera para llegar a los grupos, y la fase que
     * de verdad se está jugando quedaba enterrada. Cada fase es un
     * momento distinto del torneo y merece su propia pantalla.
     *
     * Aquí se resume, una vez, lo que la barra necesita de cada fase: no
     * se recalcula dentro del bucle de secciones.
     *
     * Las clases van completas y literales, nunca compuestas: Tailwind
     * lee el archivo compilado y una clase que se arma con 'bg-' . $x
     * simplemente no existiría en el CSS.
     */
    $tones = [

        'DONE' => [
            'badge' => 'Completada',
            'active' => 'border-emerald-400 bg-emerald-500/10 shadow-lg shadow-emerald-950/40',
            'chip' => 'bg-emerald-500/15 text-emerald-300',
            'bar' => 'bg-emerald-400',
        ],

        'LIVE' => [
            'badge' => 'En juego',
            'active' => 'border-violet-400 bg-violet-500/10 shadow-lg shadow-violet-950/40',
            'chip' => 'bg-violet-500/15 text-violet-300',
            'bar' => 'bg-violet-400',
        ],

        'OPEN' => [
            'badge' => 'Por jugar',
            'active' => 'border-sky-400 bg-sky-500/10 shadow-lg shadow-sky-950/40',
            'chip' => 'bg-sky-500/15 text-sky-300',
            'bar' => 'bg-sky-400',
        ],

        'LOCKED' => [
            'badge' => 'Sin abrir',
            'active' => 'border-slate-500 bg-slate-800/60',
            'chip' => 'bg-slate-700/60 text-slate-400',
            'bar' => 'bg-slate-600',
        ],
    ];

    /*
     * La forma del recorrido. Sin ella todo esto era una fila, y un torneo
     * con dos fases a la vez no es una fila.
     */
    $forma = $phaseGraph ?? [];

    $phaseTabs = $phaseBlocks
        ->values()
        ->map(function ($block, $index) use ($tones, $forma) {

            $phase = $block['phase'];

            $done = $block['matches']->where('status', 'COMPLETED')->count();
            $total = $block['matches']->count();

            $state = match (true) {
                $total > 0 && $done === $total => 'DONE',
                $done > 0 => 'LIVE',
                $total > 0 => 'OPEN',
                default => 'LOCKED',
            };

            $nodo = $forma[$phase->node_id] ?? [
                'level' => 1,
                'depends_on' => [],
                'feeds' => [],
                'parallel_with' => [],
                'waits_for_all' => false,
            ];

            return [
                'index' => $index,
                'block' => $block,
                'phase' => $phase,
                'node_id' => (int) $phase->node_id,
                'level' => (int) $nodo['level'],
                'depends_on' => $nodo['depends_on'],
                'feeds' => $nodo['feeds'],
                'parallel_with' => $nodo['parallel_with'],
                'waits_for_all' => (bool) $nodo['waits_for_all'],
                'done' => $done,
                'total' => $total,
                'progress' => $total > 0 ? (int) round($done * 100 / $total) : 0,
                'state' => $state,
                'tone' => $tones[$state],

                'icon' => match ($phase->phase_type) {
                    'SINGLE_ELIMINATION' => '🏆',
                    'ROUND_ROBIN' => '🔄',
                    'GROUP_STAGE' => '▦',
                    'SWISS' => '⇄',
                    default => '◆',
                },

                'engine' => match ($phase->phase_type) {
                    'SINGLE_ELIMINATION' => 'Eliminación directa',
                    'ROUND_ROBIN' => 'Todos contra todos',
                    'GROUP_STAGE' => 'Fase de grupos',
                    'SWISS' => 'Sistema suizo',
                    default => $phase->phase_type,
                },
            ];
        });

    /*
     * Al entrar se abre la fase que se está jugando, no la primera. Si
     * ya están todas terminadas, la última: es donde acabó el torneo.
     */
    $defaultTab =
        $phaseTabs->firstWhere('state', 'LIVE')['index']
        ?? $phaseTabs->firstWhere('state', 'OPEN')['index']
        ?? ($phaseTabs->isNotEmpty() ? $phaseTabs->last()['index'] : 0);

    $tabStorageKey = 'omnimerge.arena.' . $competition->id . '.phase';

    /*
     * Las pestañas, agrupadas por nivel. Las de un mismo nivel se juegan a
     * la vez: entre ellas se salta libremente, y ninguna espera a la otra
     * salvo que una fase posterior las junte.
     */
    $porNivel = $phaseTabs->groupBy('level')->sortKeys();

    $hayParalelas = $phaseTabs->contains(fn($t) => count($t['parallel_with']) > 0);

    /* De nodo a pestaña, para poder nombrar dependencias */
    $porNodo = $phaseTabs->keyBy('node_id');

    /*
     * Una fase está lista cuando todas las que la alimentan terminaron.
     * Con varias entradas eso es lo que decide si se puede abrir o no, y
     * es justo lo que la pantalla no sabía decir.
     */
    $pendientesDe = function (array $tab) use ($porNodo) {

        return collect($tab['depends_on'])
            ->map(fn($id) => $porNodo[$id] ?? null)
            ->filter()
            ->filter(fn($otro) => $otro['state'] !== 'DONE')
            ->values();
    };
@endphp

<div class="p-5">

    {{-- ============================================ --}}
    {{-- EL MOTOR ESTÁ ESPERANDO UNA DECISIÓN --}}
    {{-- ============================================ --}}

    {{--
        Va ANTES que todo lo demás, y sustituye a «abrir la fase».

        Una fase configurada a mano —grupos manuales, orden manual, BYEs
        elegidos— no arranca sola: el motor la deja parada y espera. Eso
        siempre funcionó, pero esta pantalla no lo enseñaba, ofrecía «abrir
        la fase», y abrir exige un recorrido en marcha. De ahí el «el
        Tournament Graph Runtime no está en ejecución»: cierto, y sin
        ninguna pista de qué hacer.
    --}}

    @if (! $readonly && ! empty($pendingDecisions ?? []))

        @foreach ($pendingDecisions as $pendiente)
            @include('universes.competitions.partials.play.decision', ['pendiente' => $pendiente])
        @endforeach

    @elseif ($needsOpening)

        <div class="flex min-h-[70vh] items-center justify-center">

            <div class="max-w-lg text-center">

                <div class="text-6xl opacity-25">
                    {{ $competition->isDraft() ? '🔒' : '◆' }}
                </div>

                <h3 class="mt-6 text-2xl font-black text-white">
                    {{ $competition->isDraft()
                        ? 'La competición no ha comenzado'
                        : 'La fase todavía no está repartida' }}
                </h3>

                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-400">
                    {{ $competition->isDraft()
                        ? 'Los competidores ya están inscritos. Al comenzar se repartirán en la primera fase y aparecerán los enfrentamientos.'
                        : 'El recorrido está iniciado pero los participantes aún no han entrado en la fase. Ábrela para ver los enfrentamientos.' }}
                </p>

                @unless ($readonly)
                    <button type="button"
                        @click="{{ $competition->isDraft() ? 'startAndOpen()' : 'openArena()' }}"
                        :disabled="loading"
                        class="mt-8 rounded-2xl bg-violet-500 px-10 py-4 text-sm font-black text-white shadow-xl shadow-violet-900/40 transition hover:bg-violet-400 disabled:opacity-50">
                        <span x-show="!loading">
                            {{ $competition->isDraft() ? 'Comenzar y abrir la fase →' : 'Abrir la fase →' }}
                        </span>
                        <span x-show="loading" x-cloak>Repartiendo competidores…</span>
                    </button>
                @endunless

            </div>

        </div>
    @else

        {{-- ============================================ --}}
        {{-- FASES --}}
        {{-- ============================================ --}}

        <div x-data="{
                phase: Number(localStorage.getItem('{{ $tabStorageKey }}') ?? {{ $defaultTab }}),
                go(index) {
                    this.phase = index;
                    localStorage.setItem('{{ $tabStorageKey }}', index);
                },
            }"
            class="space-y-5">

            {{-- ============================================ --}}
            {{-- RECORRIDO: UNA PESTAÑA POR FASE --}}
            {{-- ============================================ --}}

            @if ($phaseTabs->count() > 1)

                <nav class="-mx-1 overflow-x-auto px-1 pb-1">

                    {{--
                        Las fases, agrupadas por nivel.

                        Antes iban en una fila con flechas entre todas, lo que
                        decía algo falso en cuanto dos fases arrancan a la vez:
                        que una va después de la otra. Ahora las que se juegan
                        simultáneamente van juntas bajo su nivel, y la flecha
                        solo separa niveles —que es donde sí hay un «después»—.
                    --}}

                    <div class="flex min-w-max items-stretch gap-2">

                        @foreach ($porNivel as $nivel => $delNivel)

                            @if (! $loop->first)
                                <div class="flex items-center px-1 text-lg text-slate-700">→</div>
                            @endif

                            <div @class([
                                'rounded-2xl' => $hayParalelas,
                                'border border-dashed border-slate-800 bg-slate-950/40 p-1.5' => $hayParalelas && $delNivel->count() > 1,
                            ])>

                                @if ($hayParalelas && $delNivel->count() > 1)
                                    <p class="px-1.5 pb-1 text-[8px] font-black uppercase tracking-[0.2em] text-amber-400">
                                        ⇉ {{ $delNivel->count() }} fases a la vez
                                    </p>
                                @endif

                                <div class="flex items-stretch gap-2">

                        @foreach ($delNivel as $tab)

                            <button type="button" @click="go({{ $tab['index'] }})"
                                :class="phase === {{ $tab['index'] }}
                                    ? '{{ $tab['tone']['active'] }}'
                                    : 'border-slate-800 bg-slate-900/40 hover:border-slate-600'"
                                class="min-w-[195px] rounded-2xl border px-4 py-3 text-left transition">

                                <div class="flex items-center gap-2">

                                    <span class="text-base">{{ $tab['icon'] }}</span>

                                    <span class="rounded-full px-2 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tab['tone']['chip'] }}">
                                        {{ $tab['tone']['badge'] }}
                                    </span>

                                    <span class="ml-auto font-mono text-[10px] font-black text-slate-500">
                                        {{ $tab['done'] }}/{{ $tab['total'] }}
                                    </span>

                                </div>

                                <p class="mt-2 truncate text-xs font-black text-white">
                                    {{ $tab['phase']->node_name }}
                                </p>

                                <p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    {{ $tab['engine'] }}
                                </p>

                                <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-800">
                                    <div class="h-full rounded-full transition-all {{ $tab['tone']['bar'] }}"
                                        style="width: {{ $tab['progress'] }}%"></div>
                                </div>

                            </button>
                        @endforeach

                                </div>
                            </div>
                        @endforeach

                    </div>

                </nav>
            @endif


            @foreach ($phaseTabs as $tab)

                @php
                    $block = $tab['block'];
                    $phase = $tab['phase'];
                    $engineIcon = $tab['icon'];
                    $engineLabel = $tab['engine'];
                    $done = $tab['done'];
                    $total = $tab['total'];
                    $progress = $tab['progress'];
                @endphp

                <section x-show="phase === {{ $tab['index'] }}" x-cloak
                    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

                    {{-- CABECERA --}}

                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 bg-slate-900/60 px-5 py-3">

                        <span class="text-xl">{{ $engineIcon }}</span>

                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-black text-white">{{ $phase->node_name }}</h3>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                {{ $engineLabel }}
                            </p>
                        </div>

                        {{-- Progreso --}}
                        <div class="flex items-center gap-3">

                            <div class="h-1.5 w-28 overflow-hidden rounded-full bg-slate-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-emerald-400 transition-all"
                                    style="width: {{ $progress }}%"></div>
                            </div>

                            <span class="font-mono text-[11px] font-black text-slate-400">
                                {{ $done }}/{{ $total }}
                            </span>

                        </div>

                    </div>


                    {{-- ============================================ --}}
                    {{-- DE DÓNDE SALIÓ EL REPARTO EN GRUPOS --}}
                    {{-- ============================================ --}}

                    {{--
                        Cuando el recorrido ya decidió los grupos —cada puerta
                        de entrada llena el suyo—, la fase no pregunta nada.
                        Callarlo del todo dejaría sin explicar por qué unos
                        acabaron en el Grupo A y otros en el B.
                    --}}

                    @php $fuente = ($groupSources ?? [])[$tab['node_id']] ?? null; @endphp

                    @if ($fuente)
                        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-emerald-500/5 px-5 py-2.5">

                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400">
                                ⇥ repartido por el recorrido
                            </span>

                            @foreach ($fuente as $fila)
                                <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[10px] text-slate-300">
                                    <strong class="font-black text-slate-100">{{ $fila['group_name'] }}</strong>
                                    ← {{ $fila['port_name'] }}
                                    <span class="font-mono text-slate-600">· {{ $fila['count'] }}</span>
                                </span>
                            @endforeach

                            <span class="text-[10px] text-slate-600">
                                · no hizo falta repartir a mano
                            </span>
                        </div>
                    @endif


                    {{-- ============================================ --}}
                    {{-- CÓMO VA ESTA FASE --}}
                    {{-- ============================================ --}}

                    {{--
                        Cada fase tiene su propio orden, y no es ninguno de los
                        dos de la tira de arriba: un cuadro de dieciséis y una
                        liga de diez van cada uno por su cuenta.

                        De dónde salen las filas depende del motor, y por eso se
                        decide aquí y no dentro del trozo que las pinta:

                          fase de grupos   la lista ÚNICA que calcula el motor;
                                           las posiciones de sus tablas se
                                           repiten por grupo (1,1,1,2,2,2…) y
                                           como ranking de la fase no dicen nada
                          las demás        sus propias posiciones, que ya son
                                           el orden de la fase
                    --}}

                    @php
                        $general = ($overallStandings ?? [])[$tab['node_id']] ?? null;

                        if ($phase->phase_type === 'GROUP_STAGE') {

                            /*
                             * Sin lista única no se enseña nada.
                             *
                             * Las posiciones de una fase de grupos son de cada
                             * grupo: 1, 1, 1, 1, 2, 2, 2, 2… Pintadas en fila
                             * parecen un ranking y no lo son, y cuatro primeros
                             * seguidos confunden más que la ausencia. La lista
                             * de verdad aparece en cuanto el motor recalcule.
                             */
                            $filasFase = $general
                                ? collect($general['rows'])
                                    ->map(fn($fila) => [
                                        'position' => $fila['position'],
                                        'name' => $fila['name'],
                                        'image_url' => $fila['image_url'],
                                        'points' => $fila['points'],

                                        /* «B1»: su grupo y su puesto dentro de él */
                                        'origin' => mb_substr(
                                            str_replace('Grupo ', '', $fila['group_name']), 0, 1
                                        ) . $fila['group_position'],
                                    ])
                                    ->all()
                                : [];

                            $notaFase = $general['label'] ?? null;

                        } else {

                            $filasFase = $block['standings']
                                ->sortBy(fn($fila) => $fila->position ?? PHP_INT_MAX)
                                ->map(fn($fila) => [
                                    'position' => $fila->position,
                                    'name' => $fila->participant_name,
                                    'image_url' => $fila->universeEntity?->image_url,
                                    'points' => $fila->points,
                                    'origin' => $fila->group_label
                                        ? mb_substr(str_replace('Grupo ', '', $fila->group_label), 0, 1)
                                        : null,
                                ])
                                ->values()
                                ->all();

                            $notaFase = $tab['engine'];
                        }
                    @endphp

                    @include('universes.competitions.partials.play.phase-ranking', [
                        'rows' => $filasFase,
                        'note' => $notaFase,
                    ])


                    {{-- CUERPO SEGÚN EL MOTOR --}}

                    @if ($block['view'] === 'bracket')
                        @include('universes.competitions.partials.play.view-bracket', ['block' => $block])
                    @elseif ($block['view'] === 'groups')
                        @include('universes.competitions.partials.play.view-groups', ['block' => $block])
                    @else
                        @include('universes.competitions.partials.play.view-rounds', ['block' => $block])
                    @endif


                    {{-- ============================================ --}}
                    {{-- QUÉ TOCA HACER AHORA EN ESTA FASE --}}
                    {{-- ============================================ --}}

                    @php
                        /*
                         * A dónde va esta fase, según el RECORRIDO, no según
                         * el orden de la lista.
                         *
                         * Antes era `$phaseTabs[$index + 1]`: la siguiente de
                         * la fila. Con dos fases en paralelo eso señalaba a la
                         * fase hermana —que no va después, va al lado— y
                         * ofrecía abrirla como si esta la alimentara.
                         */
                        $siguientes =
                            collect($tab['feeds'])
                            ->map(fn($id) => $porNodo[$id] ?? null)
                            ->filter()
                            ->values();

                        /* Las hermanas: se juegan a la vez que esta */
                        $hermanas =
                            collect($tab['parallel_with'])
                            ->map(fn($id) => $porNodo[$id] ?? null)
                            ->filter()
                            ->values();

                        /*
                         * Una fase posterior solo se abre cuando TODAS las que
                         * la alimentan terminaron. Aquí se mira una por una, y
                         * se guarda qué falta para poder decirlo por su nombre
                         * en vez de un «todavía no».
                         */
                        $abribles = $siguientes->filter(
                            fn($n) => $n['total'] === 0 && $pendientesDe($n)->isEmpty()
                        )->values();

                        $bloqueadas = $siguientes->filter(
                            fn($n) => $n['total'] === 0 && $pendientesDe($n)->isNotEmpty()
                        )->values();

                        $next = $abribles->first();

                        $canOpenNext = $tab['state'] === 'DONE' && $next !== null;
                    @endphp

                    {{--
                        Lo que se juega a la vez que esta fase.

                        Va arriba del pie y siempre visible: saber que hay otra
                        fase abierta en paralelo es lo que permite ir a ella sin
                        creer que hay que terminar esta primero.
                    --}}

                    @if ($hermanas->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-2 border-t border-slate-800 bg-slate-950/40 px-5 py-2.5">

                            <span class="text-[10px] font-black uppercase tracking-wider text-amber-400">
                                ⇉ a la vez que esta
                            </span>

                            @foreach ($hermanas as $hermana)
                                <button type="button" @click="go({{ $hermana['index'] }})"
                                    class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 transition hover:border-amber-400">

                                    <span class="text-[11px]">{{ $hermana['icon'] }}</span>

                                    <span class="max-w-[160px] truncate text-[10px] font-black text-slate-200">
                                        {{ $hermana['phase']->node_name }}
                                    </span>

                                    <span class="rounded px-1 py-0.5 text-[8px] font-black uppercase {{ $hermana['tone']['chip'] }}">
                                        {{ $hermana['tone']['badge'] }}
                                    </span>

                                    <span class="font-mono text-[9px] text-slate-500">
                                        {{ $hermana['done'] }}/{{ $hermana['total'] }}
                                    </span>
                                </button>
                            @endforeach

                            <span class="text-[10px] text-slate-600">
                                · puedes saltar entre ellas cuando quieras
                            </span>
                        </div>
                    @endif

                    <div class="border-t border-slate-800 px-5 py-3.5">

                        @if ($canOpenNext)

                            <div class="flex flex-wrap items-center gap-3">

                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-emerald-300">
                                        ✓ {{ $tab['phase']->node_name }} terminada
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        Reparte a los clasificados en
                                        <strong class="font-black text-slate-300">{{ $next['phase']->node_name }}</strong>
                                        para poder jugarla.
                                    </p>
                                </div>

                                @unless ($readonly)
                                    <button type="button"
                                        @click="go({{ $next['index'] }}); openArena()"
                                        :disabled="loading"
                                        class="shrink-0 rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-black text-slate-950 shadow-lg shadow-emerald-950/40 transition hover:bg-emerald-400 disabled:opacity-50">
                                        <span x-show="!loading">Abrir {{ $next['phase']->node_name }} →</span>
                                        <span x-show="loading" x-cloak>Repartiendo…</span>
                                    </button>
                                @endunless

                            </div>

                        @elseif ($tab['state'] === 'DONE' && $bloqueadas->isNotEmpty())

                            {{--
                                Terminada, pero lo que viene después no puede
                                empezar todavía porque lo alimentan varias
                                fases y alguna sigue en juego.

                                Se dice CUÁL falta, por su nombre y con un
                                botón para ir a ella. Un «todavía no» a secas
                                deja al usuario buscando qué hacer, que es
                                exactamente el callejón del que venimos.
                            --}}

                            @foreach ($bloqueadas as $bloqueada)
                                @php $faltan = $pendientesDe($bloqueada); @endphp

                                <div class="flex flex-wrap items-center gap-3 {{ $loop->first ? '' : 'mt-3 border-t border-slate-800/60 pt-3' }}">

                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-black text-emerald-300">
                                            ✓ {{ $tab['phase']->node_name }} terminada
                                        </p>

                                        <p class="mt-0.5 text-[11px] leading-relaxed text-slate-400">
                                            <strong class="font-black text-slate-200">{{ $bloqueada['phase']->node_name }}</strong>
                                            se alimenta de
                                            {{ count($bloqueada['depends_on']) }} fases
                                            @if ($bloqueada['waits_for_all'])
                                                y espera a que <span class="font-bold">todas</span> terminen.
                                            @else
                                                y todavía faltan participantes por llegar.
                                            @endif
                                            Falta
                                            {{ $faltan->count() === 1 ? 'que termine' : 'que terminen' }}:
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach ($faltan as $pendiente)
                                            <button type="button"
                                                @click="go({{ $pendiente['index'] }}); $el.closest('.arena-scroll')?.scrollTo({ top: 0, behavior: 'smooth' })"
                                                class="flex items-center gap-1.5 rounded-lg border border-amber-500/50 bg-amber-500/10 px-2.5 py-1.5 transition hover:bg-amber-500/20">

                                                <span class="text-[11px]">{{ $pendiente['icon'] }}</span>

                                                <span class="max-w-[150px] truncate text-[10px] font-black text-amber-200">
                                                    {{ $pendiente['phase']->node_name }}
                                                </span>

                                                <span class="font-mono text-[9px] text-amber-400/70">
                                                    {{ $pendiente['done'] }}/{{ $pendiente['total'] }}
                                                </span>

                                                <span class="text-[10px] text-amber-300">→</span>
                                            </button>
                                        @endforeach
                                    </div>

                                </div>
                            @endforeach

                        @elseif ($tab['state'] === 'DONE' && $siguientes->firstWhere('total', '>', 0))

                            @php $yaAbierta = $siguientes->first(fn($n) => $n['total'] > 0); @endphp

                            <div class="flex flex-wrap items-center gap-3">

                                <p class="min-w-0 flex-1 text-xs font-black text-emerald-300">
                                    ✓ {{ $tab['phase']->node_name }} terminada
                                </p>

                                <button type="button"
                                    @click="go({{ $yaAbierta['index'] }}); $el.closest('.arena-scroll')?.scrollTo({ top: 0, behavior: 'smooth' })"
                                    class="shrink-0 rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-400 hover:text-violet-300">
                                    Ir a {{ $yaAbierta['phase']->node_name }} →
                                </button>

                            </div>

                        @elseif ($tab['state'] === 'DONE')

                            {{--
                                La fase final terminó pero la competición
                                todavía no está cerrada.

                                Pasa cuando se juega la última batalla y se
                                vuelve aquí sin dejar que el motor termine su
                                recorrido: falta enrutar al campeón, así que
                                no hay ni resultado ni premios. Antes esto
                                solo decía "mira la etapa 4" y allí no había
                                nada; ahora se puede cerrar desde aquí.
                            --}}
                            {{--
                                «Se jugó todo» solo si de verdad se jugó todo.

                                Sin esta comprobación, en un recorrido con
                                fases en paralelo la primera que terminaba ya
                                ofrecía cerrar la competición y repartir los
                                premios, con la fase hermana a medias.
                            --}}
                            @php $faltanFases = $phaseTabs->filter(fn($o) => $o['state'] !== 'DONE')->values(); @endphp

                            @if ($faltanFases->isNotEmpty())

                                <div class="flex flex-wrap items-center gap-3">

                                    <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
                                        ✓ <span class="font-black text-emerald-300">{{ $tab['phase']->node_name }}</span>
                                        terminada. El recorrido sigue en
                                        {{ $faltanFases->count() === 1 ? 'otra fase' : 'otras ' . $faltanFases->count() . ' fases' }}.
                                    </p>

                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach ($faltanFases as $otra)
                                            <button type="button"
                                                @click="go({{ $otra['index'] }}); $el.closest('.arena-scroll')?.scrollTo({ top: 0, behavior: 'smooth' })"
                                                class="flex items-center gap-1.5 rounded-lg border border-slate-700 px-2.5 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-violet-400 hover:text-violet-300">
                                                <span>{{ $otra['icon'] }}</span>
                                                <span class="max-w-[150px] truncate">{{ $otra['phase']->node_name }}</span>
                                                <span>→</span>
                                            </button>
                                        @endforeach
                                    </div>

                                </div>

                            @elseif (! $competition->isClosed() && ! $readonly)

                                <div class="flex flex-wrap items-center gap-3">

                                    <p class="min-w-0 flex-1 text-[11px] text-slate-400">
                                        Se jugó todo. Falta cerrar la competición para
                                        proclamar al campeón y repartir los premios.
                                    </p>

                                    <button type="button" @click="openArena()" :disabled="loading"
                                        class="shrink-0 rounded-xl bg-amber-500 px-5 py-2 text-[11px] font-black text-slate-950 transition hover:bg-amber-400 disabled:opacity-40">
                                        🏆 Cerrar y repartir premios
                                    </button>

                                </div>

                            @else

                                <p class="text-xs font-black text-amber-300">
                                    🏆 Competición terminada · el resultado está en la etapa 4
                                    y los premios en la 5
                                </p>

                            @endif

                        @elseif ($tab['total'] > 0)

                            <div class="flex flex-wrap items-center gap-3">

                                <p class="min-w-0 flex-1 text-[11px] text-slate-500">
                                    Quedan
                                    <strong class="font-black text-violet-300">{{ $tab['total'] - $tab['done'] }}</strong>
                                    batallas. Pulsa cualquiera para disputarla con el motor.
                                </p>

                                @unless ($readonly)
                                    <button type="button"
                                        @click="window.OmniConfirm.request({
                                            variant: 'danger',
                                            icon: '⚡',
                                            title: 'Resolver todo sin jugar',
                                            message: 'El motor decidirá TODAS las batallas que faltan, no solo las de esta fase.',
                                            detail: 'No se puede deshacer: los resultados quedan como si se hubieran jugado.',
                                            actionLabel: 'Sí, resolver todo',
                                        }).then(ok => ok && execute('RUN_TOURNAMENT').then(() => window.location.reload()))"
                                        :disabled="loading"
                                        class="shrink-0 rounded-xl border border-slate-800 px-4 py-2 text-[11px] font-black text-slate-500 transition hover:border-slate-600 hover:text-slate-300 disabled:opacity-50">
                                        Resolver todo sin jugar
                                    </button>
                                @endunless

                            </div>

                        @else

                            <p class="text-[11px] text-slate-500">
                                Esta fase todavía no tiene competidores. Se abrirá cuando
                                termine la anterior.
                            </p>

                        @endif

                    </div>

                </section>
            @endforeach

        </div>


    @endif

</div>

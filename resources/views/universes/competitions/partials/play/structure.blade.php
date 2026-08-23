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

    $phaseTabs = $phaseBlocks
        ->values()
        ->map(function ($block, $index) use ($tones) {

            $phase = $block['phase'];

            $done = $block['matches']->where('status', 'COMPLETED')->count();
            $total = $block['matches']->count();

            $state = match (true) {
                $total > 0 && $done === $total => 'DONE',
                $done > 0 => 'LIVE',
                $total > 0 => 'OPEN',
                default => 'LOCKED',
            };

            return [
                'index' => $index,
                'block' => $block,
                'phase' => $phase,
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
@endphp

<div class="p-5">

    {{-- ============================================ --}}
    {{-- LA FASE TODAVÍA NO ESTÁ ABIERTA --}}
    {{-- ============================================ --}}

    @if ($needsOpening)

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

                    <div class="flex min-w-max items-stretch gap-2">

                        @foreach ($phaseTabs as $tab)

                            {{-- El torneo avanza de una fase a la siguiente --}}
                            @if (! $loop->first)
                                <div class="flex items-center px-0.5 text-lg text-slate-700">→</div>
                            @endif

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
                        $next = $phaseTabs[$tab['index'] + 1] ?? null;

                        /*
                         * "Abrir siguiente fase" estaba al final de la
                         * pantalla, suelto y siempre visible, así que no
                         * se sabía a qué fase se refería ni cuándo tocaba
                         * pulsarlo. Aquí vive dentro de la fase que lo
                         * habilita, y solo aparece cuando esa fase ha
                         * terminado: pulsarlo es OFICIALIZAR el paso.
                         */
                        $canOpenNext =
                            $tab['state'] === 'DONE'
                            && $next !== null
                            && $next['total'] === 0;
                    @endphp

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

                        @elseif ($tab['state'] === 'DONE' && $next !== null)

                            <div class="flex flex-wrap items-center gap-3">

                                <p class="min-w-0 flex-1 text-xs font-black text-emerald-300">
                                    ✓ {{ $tab['phase']->node_name }} terminada
                                </p>

                                <button type="button"
                                    @click="go({{ $next['index'] }}); $el.closest('.arena-scroll')?.scrollTo({ top: 0, behavior: 'smooth' })"
                                    class="shrink-0 rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-400 hover:text-violet-300">
                                    Ir a {{ $next['phase']->node_name }} →
                                </button>

                            </div>

                        @elseif ($tab['state'] === 'DONE')

                            <p class="text-xs font-black text-amber-300">
                                🏆 Fase final terminada · mira el resultado en la etapa 4
                            </p>

                        @elseif ($tab['total'] > 0)

                            <div class="flex flex-wrap items-center gap-3">

                                <p class="min-w-0 flex-1 text-[11px] text-slate-500">
                                    Quedan
                                    <strong class="font-black text-violet-300">{{ $tab['total'] - $tab['done'] }}</strong>
                                    batallas. Pulsa cualquiera para disputarla con el motor.
                                </p>

                                @unless ($readonly)
                                    <button type="button"
                                        @click="if (confirm('Se resolverán automáticamente TODAS las batallas que faltan en la competición, no solo las de esta fase. ¿Continuar?')) { execute('RUN_TOURNAMENT').then(() => window.location.reload()) }"
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

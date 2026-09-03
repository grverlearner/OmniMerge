@php
    /*
     * La arena.
     *
     * Aquí se JUEGA la competición: cada etapa ocupa la ventana entera y
     * el usuario decide qué batalla disputar. El Runtime sigue siendo la
     * única fuente de verdad — esta pantalla solo lo pone en escena.
     */

    $champion = $standings->firstWhere('outcome', 'CHAMPION');

    $stageLabels = [
        1 => ['n' => '01', 'label' => 'Participantes'],
        2 => ['n' => '02', 'label' => 'Estructura'],
        3 => ['n' => '03', 'label' => 'Batalla'],
        4 => ['n' => '04', 'label' => 'Resultado'],
        5 => ['n' => '05', 'label' => 'Premios'],
    ];

    $initialStage = $competition->isDraft() ? 1 : 2;
@endphp

<x-arena-layout :title="$competition->name">

    <div x-data="competitionArena({
            actionUrl: @js(route('universes.competitions.action', [$universe, $competition])),
            persistent: true,
            revision: @js((int) ($payload['revision'] ?? 0)),
            initialState: @js($payload['state'] ?? []),
            initialToken: null,
            storageKey: null,

            initialStage: {{ $initialStage }},
            readonly: {{ $readonly ? 'true' : 'false' }},
            battles: @js($battles),

            {{-- __BATTLE__ se sustituye por la clave al abrir --}}
            battleUrl: @js(route('universes.competitions.battles.show', [$universe, $competition, '__BATTLE__'])),
        })"
        class="flex h-screen flex-col overflow-hidden bg-slate-950">


        {{-- ============================================ --}}
        {{-- BARRA SUPERIOR --}}
        {{-- ============================================ --}}

        <header class="flex shrink-0 items-center gap-4 border-b border-slate-800/80 bg-slate-900/50 px-5 py-3 backdrop-blur">

            <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
                class="shrink-0 rounded-xl border border-slate-800 px-3 py-2 text-xs font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
                ←
            </a>

            <div class="min-w-0 flex-1">

                <div class="flex flex-wrap items-center gap-2">

                    <h1 class="truncate text-base font-black text-white">{{ $competition->name }}</h1>

                    <span class="shrink-0 rounded-full bg-violet-500/20 px-2.5 py-0.5 text-[10px] font-black text-violet-300">
                        {{ $definition['icon'] ?? '🎲' }} {{ $definition['name'] }}
                    </span>

                    @if ($readonly)
                        <span class="shrink-0 rounded-full bg-slate-800 px-2.5 py-0.5 text-[10px] font-black text-slate-400">
                            {{ $competition->status === 'PAUSED' ? 'Pausada' : 'Terminada · solo lectura' }}
                        </span>
                    @endif

                </div>

                <p class="truncate text-[11px] text-slate-500">
                    {{ $universe->name }} · {{ $participants->count() }} competidores
                    @if ($competition->season)
                        · Temporada {{ $competition->season->number }}
                    @endif
                </p>

            </div>


            {{-- ETAPAS --}}

            <nav class="hidden shrink-0 items-center gap-1 rounded-2xl bg-slate-900 p-1 md:flex">

                @foreach ($stageLabels as $number => $meta)
                    <button type="button" @click="stage = {{ $number }}"
                        :class="stage === {{ $number }}
                            ? 'bg-violet-500 text-white shadow-lg shadow-violet-900/40'
                            : 'text-slate-500 hover:text-slate-300'"
                        class="flex items-center gap-2 rounded-xl px-3 py-2 transition">
                        <span class="font-mono text-[9px] opacity-60">{{ $meta['n'] }}</span>
                        <span class="text-[11px] font-black">{{ $meta['label'] }}</span>
                    </button>
                @endforeach

            </nav>

            <template x-if="loading">
                <span class="shrink-0 text-[11px] font-black text-violet-400">···</span>
            </template>

        </header>


        {{-- ERROR --}}

        <template x-if="error">
            <div class="shrink-0 border-b border-rose-500/30 bg-rose-500/10 px-5 py-2.5">
                <p class="text-xs font-bold text-rose-300" x-text="error"></p>
            </div>
        </template>


        {{-- ============================================ --}}
        {{-- RANKING --}}
        {{-- ============================================ --}}

        {{--
            Fuera del lienzo, así que se ve en las cinco etapas: mientras
            juegas, mirando la estructura o repartiendo premios. Es un
            vistazo permanente, no una pantalla más.
        --}}

        @include('universes.competitions.partials.play.ranking-strip')


        {{-- ============================================ --}}
        {{-- LIENZO --}}
        {{-- ============================================ --}}

        <main class="arena-scroll min-h-0 flex-1 overflow-y-auto">

            <div x-show="stage === 1" x-cloak class="h-full">
                @include('universes.competitions.partials.play.participants')
            </div>

            <div x-show="stage === 2" x-cloak class="h-full">
                @include('universes.competitions.partials.play.structure')
            </div>

            <div x-show="stage === 3" x-cloak class="h-full">
                @include('universes.competitions.partials.play.battle')
            </div>

            <div x-show="stage === 5" x-cloak class="h-full">
                @include('universes.competitions.partials.play.awards')
            </div>

            <div x-show="stage === 4" x-cloak class="h-full">
                @include('universes.competitions.partials.play.result')
            </div>

        </main>

    </div>

</x-arena-layout>

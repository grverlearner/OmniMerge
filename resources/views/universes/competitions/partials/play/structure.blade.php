@php
    /*
     * ETAPA 2 · La estructura de cada fase.
     *
     * Los datos vienen del historial, que desde la Fase 8 funciona igual
     * con una competición en curso: no hay lógica paralela.
     */

    $totalMatches = $phaseBlocks->sum(fn($block) => $block['matches']->count());

    $needsOpening = $phaseBlocks->isEmpty() || $totalMatches === 0;
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

        <div class="space-y-5">

            @foreach ($phaseBlocks as $block)

                @php
                    $phase = $block['phase'];

                    $engineIcon = match ($phase->phase_type) {
                        'SINGLE_ELIMINATION' => '🏆',
                        'ROUND_ROBIN' => '🔄',
                        'GROUP_STAGE' => '▦',
                        default => '◆',
                    };

                    $engineLabel = match ($phase->phase_type) {
                        'SINGLE_ELIMINATION' => 'Eliminación directa',
                        'ROUND_ROBIN' => 'Todos contra todos',
                        'GROUP_STAGE' => 'Fase de grupos',
                        default => $phase->phase_type,
                    };

                    $done = $block['matches']->where('status', 'COMPLETED')->count();
                    $total = $block['matches']->count();
                    $progress = $total > 0 ? round($done * 100 / $total) : 0;
                @endphp

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

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

                </section>
            @endforeach

        </div>


        {{-- ACCIONES DE FASE --}}

        @unless ($readonly)
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">

                <p class="w-full text-center text-[11px] text-slate-600">
                    Pulsa cualquier batalla marcada <span class="font-black text-violet-400">▶ Jugar</span>
                    para disputarla con el motor.
                </p>

                <button type="button" @click="openArena()" :disabled="loading"
                    class="rounded-xl border border-slate-700 px-5 py-2.5 text-xs font-black text-slate-300 transition hover:border-slate-500 hover:text-white disabled:opacity-50">
                    Abrir siguiente fase
                </button>

                <button type="button"
                    @click="if (confirm('Se resolverán automáticamente todas las batallas que faltan. ¿Continuar?')) { execute('RUN_TOURNAMENT').then(() => window.location.reload()) }"
                    :disabled="loading"
                    class="rounded-xl border border-slate-800 px-5 py-2.5 text-xs font-black text-slate-500 transition hover:border-slate-700 hover:text-slate-300 disabled:opacity-50">
                    Resolver todo sin jugar
                </button>

            </div>
        @endunless
    @endif

</div>

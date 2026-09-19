@php
    /*
     * Una fase del historial, con la representación que le corresponde
     * a su motor. Un bracket y una liguilla no se ven igual porque no
     * son lo mismo.
     *
     * $block  ['phase','matches','standings','view','rounds','groups']
     */

    $phase = $block['phase'];

    $engineLabel = match ($phase->phase_type) {
        'SINGLE_ELIMINATION' => 'Eliminación directa',
        'ROUND_ROBIN' => 'Todos contra todos',
        'GROUP_STAGE' => 'Fase de grupos',
        'SWISS' => 'Suizo',
        default => $phase->phase_type,
    };

    /* Del juego de iconos, nunca un carácter suelto */
    $engineIcon = match ($phase->phase_type) {
        'SINGLE_ELIMINATION' => 'grafo',
        'ROUND_ROBIN' => 'orbita',
        'GROUP_STAGE' => 'cuadricula',
        'SWISS' => 'barajar',
        default => 'capas',
    };

    $advanced = $block['standings']->where('status', 'ADVANCED')->count();
@endphp


<section class="rounded-2xl border border-slate-800 bg-slate-950 p-4">

    <div class="flex flex-wrap items-start justify-between gap-3">

        <div class="flex min-w-0 items-center gap-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                <x-omni-icon :name="$engineIcon" size="h-4 w-4" />
            </span>

            <div class="min-w-0">
                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-cyan-300/70">
                    {{ $engineLabel }}
                </p>

                <h3 class="truncate text-[15px] font-black text-white">
                    {{ $phase->node_name }}
                </h3>
            </div>
        </div>


        <div class="flex shrink-0 flex-wrap gap-1.5">

            <span class="rounded-lg border border-slate-800 px-2 py-1 font-mono text-[10px] font-black text-slate-400">
                {{ $block['matches']->count() }} encuentros
            </span>

            <span class="rounded-lg border border-slate-800 px-2 py-1 font-mono text-[10px] font-black text-slate-400">
                {{ $phase->participant_count }} competidores
            </span>

        </div>

    </div>


    <div class="mt-4">

        @switch($block['view'])

            @case('bracket')
                @include('universes.competitions.partials.history.bracket', [
                    'rounds' => $block['rounds'],
                ])
            @break


            @case('groups')
                @include('universes.competitions.partials.history.groups', [
                    'groups' => $block['groups'],
                    'matches' => $block['matches'],
                ])
            @break


            @case('table')
                @if ($block['standings']->isNotEmpty())
                    @include('universes.competitions.partials.history.standings-table', [
                        'standings' => $block['standings'],
                    ])
                @endif


                @if ($block['matches']->isNotEmpty())
                    <div class="mt-4">

                        <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Jornadas
                        </p>

                        <div class="grid gap-2 lg:grid-cols-2">
                            @foreach ($block['matches'] as $match)
                                @include('universes.competitions.partials.history.match-card', [
                                    'match' => $match,
                                ])
                            @endforeach
                        </div>

                    </div>
                @endif
            @break


            @default
                <div class="grid gap-2 lg:grid-cols-2">
                    @foreach ($block['matches'] as $match)
                        @include('universes.competitions.partials.history.match-card', [
                            'match' => $match,
                        ])
                    @endforeach
                </div>
        @endswitch

    </div>


    {{-- Traspaso hacia la siguiente fase --}}

    @if ($advanced > 0)
        <div class="mt-4 flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/5 px-3 py-2">

            <span class="shrink-0 text-emerald-300">
                <x-omni-icon name="flecha-derecha" size="h-4 w-4" />
            </span>

            <p class="text-[11px] font-bold text-emerald-200">
                <span class="font-black">{{ $advanced }}</span>
                {{ $advanced === 1 ? 'competidor salió' : 'competidores salieron' }}
                de esta fase hacia la siguiente
            </p>

        </div>
    @endif

</section>

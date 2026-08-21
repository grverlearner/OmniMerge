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
        default => $phase->phase_type,
    };

    $engineIcon = match ($phase->phase_type) {
        'SINGLE_ELIMINATION' => '🏆',
        'ROUND_ROBIN' => '🔄',
        'GROUP_STAGE' => '▦',
        default => '◆',
    };

    $advanced = $block['standings']->where('status', 'ADVANCED')->count();
@endphp


<section
    class="
        rounded-3xl
        border
        border-slate-200
        bg-white
        p-6
    ">

    <div
        class="
            flex
            flex-wrap
            items-start
            justify-between
            gap-4
        ">

        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                {{ $engineIcon }} {{ $engineLabel }}
            </p>

            <h3 class="mt-1.5 text-xl font-black text-slate-900">
                {{ $phase->node_name }}
            </h3>
        </div>


        <div class="flex flex-wrap gap-2">

            <span class="rounded-xl bg-slate-50 px-3 py-2 text-[10px] font-black text-slate-600">
                {{ $block['matches']->count() }} encuentros
            </span>

            <span class="rounded-xl bg-slate-50 px-3 py-2 text-[10px] font-black text-slate-600">
                {{ $phase->participant_count }} competidores
            </span>

        </div>

    </div>


    <div class="mt-5">

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
                    <div class="mt-5">

                        <p class="mb-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
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
        <div
            class="
                mt-5
                flex
                items-center
                gap-3
                rounded-2xl
                border
                border-violet-200
                bg-violet-50/60
                px-4
                py-3
            ">

            <span class="text-lg">↓</span>

            <p class="text-xs font-bold text-violet-900">
                <span class="font-black">{{ $advanced }}</span>
                {{ $advanced === 1 ? 'competidor salió' : 'competidores salieron' }}
                de esta fase hacia la siguiente
            </p>

        </div>
    @endif

</section>

@php
    /*
     * Eliminación directa: el cuadro COMPLETO desde el primer momento.
     *
     * El motor genera los encuentros ronda a ronda, así que las rondas
     * futuras todavía no existen como filas. Aquí se deducen: si la
     * primera ronda tiene N encuentros, la siguiente tiene ceil(N/2), y
     * el ganador del encuentro i pasa al ceil(i/2) de la ronda siguiente.
     *
     * Esa deducción es SOLO para pintar. No se inventa ningún resultado:
     * los huecos futuros se muestran vacíos, diciendo de dónde saldrá su
     * ocupante.
     */

    $rounds = $block['rounds']->sortKeys();

    $roundNumbers = $rounds->keys()->values();

    $firstRoundMatches = $roundNumbers->isNotEmpty()
        ? $rounds->get($roundNumbers->first())->values()
        : collect();

    /* Forma esperada del cuadro */
    $columns = [];
    $slots = max(1, $firstRoundMatches->count());
    $index = 0;

    while (true) {
        $columns[] = [
            'round_number' => $roundNumbers->get($index),
            'slots' => $slots,
        ];

        if ($slots <= 1) {
            break;
        }

        $slots = (int) ceil($slots / 2);
        $index++;

        /* Cortafuegos: un cuadro nunca tiene tantas rondas */
        if ($index > 12) {
            break;
        }
    }

    $roundName = function (int $slots, int $position) {
        return match ($slots) {
            1 => 'Final',
            2 => 'Semifinales',
            4 => 'Cuartos de final',
            8 => 'Octavos de final',
            16 => 'Dieciseisavos',
            default => 'Ronda ' . $position,
        };
    };
@endphp

<div class="arena-scroll overflow-x-auto p-5">

    <div class="flex min-w-max items-stretch gap-4">

        @foreach ($columns as $columnIndex => $column)

            @php
                $matches = $column['round_number'] !== null
                    ? $rounds->get($column['round_number'])->values()
                    : collect();

                $played = $matches->where('status', 'COMPLETED')->count();

                $name = $roundName($column['slots'], $columnIndex + 1);

                $nextName = isset($columns[$columnIndex + 1])
                    ? $roundName($columns[$columnIndex + 1]['slots'], $columnIndex + 2)
                    : null;
            @endphp

            <div class="w-52 shrink-0">

                {{-- CABECERA DE COLUMNA --}}

                <div class="mb-3 flex items-center justify-between gap-2">

                    <p @class([
                        'truncate text-[10px] font-black uppercase tracking-wider',
                        'text-white' => $matches->isNotEmpty() && $played < $matches->count(),
                        'text-slate-500' => $matches->isEmpty() || $played === $matches->count(),
                    ])>
                        {{ $name }}
                    </p>

                    @if ($matches->isNotEmpty())
                        <span class="shrink-0 rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-500">
                            {{ $played }}/{{ $matches->count() }}
                        </span>
                    @else
                        <span class="shrink-0 rounded bg-slate-900 px-1.5 py-0.5 text-[9px] font-black text-slate-700">
                            —
                        </span>
                    @endif

                </div>


                {{-- HUECOS --}}

                <div class="space-y-3">

                    @for ($slot = 1; $slot <= $column['slots']; $slot++)

                        @php
                            $match = $matches->get($slot - 1);

                            /* A dónde va el ganador de este hueco */
                            $destination = $nextName
                                ? $nextName . ' · ' . (int) ceil($slot / 2)
                                : null;

                            /* De dónde vendrá su ocupante */
                            $feeders = $columnIndex > 0
                                ? [$slot * 2 - 1, $slot * 2]
                                : null;

                            $previousName = $columnIndex > 0
                                ? $roundName($columns[$columnIndex - 1]['slots'], $columnIndex)
                                : null;

                            /*
                             * Quien ya se ha clasificado a este hueco. El
                             * motor no crea la ronda siguiente hasta que
                             * termina la actual, pero el ganador de un
                             * enfrentamiento ya jugado SI se conoce, y
                             * dejarlo invisible hace pensar que se perdio.
                             */
                            $qualified = [];

                            if ($feeders && $columnIndex > 0) {

                                $previousRound = $columns[$columnIndex - 1]['round_number'];

                                $previousMatches = $previousRound !== null
                                    ? $rounds->get($previousRound)->values()
                                    : collect();

                                foreach ($feeders as $feeder) {

                                    $feederMatch = $previousMatches->get($feeder - 1);

                                    if (! $feederMatch || $feederMatch->status !== 'COMPLETED') {
                                        $qualified[] = null;
                                        continue;
                                    }

                                    $winnerIsA = $feederMatch->winner_key === $feederMatch->participant_a_key;

                                    $qualified[] = [
                                        'name' => $winnerIsA
                                            ? $feederMatch->participant_a_name
                                            : $feederMatch->participant_b_name,
                                        'entity' => $winnerIsA
                                            ? $feederMatch->participantAEntity
                                            : $feederMatch->participantBEntity,
                                        'from' => $feeder,
                                    ];
                                }
                            }

                            $anyQualified = collect($qualified)->filter()->isNotEmpty();
                        @endphp

                        @if ($match)
                            @include('universes.competitions.partials.play.match-chip', [
                                'match' => $match,
                                'destination' => $destination,
                            ])
                        @else

                            {{-- HUECO TODAVÍA SIN OCUPANTES --}}

                            <div @class([
                                'rounded-xl border border-dashed p-2',
                                'border-violet-500/40 bg-violet-950/20' => $anyQualified,
                                'border-slate-800 bg-slate-900/20' => !$anyQualified,
                            ])>

                                {{-- Quien ya esta dentro y quien falta --}}

                                <div class="flex gap-1.5">

                                    @foreach ($qualified ?: [null, null] as $slotIndex => $who)

                                        <div class="relative w-1/2">

                                            @if ($who)
                                                <div class="relative aspect-square overflow-hidden rounded-lg bg-slate-800 ring-1 ring-violet-500/40">

                                                    @if ($who['entity']?->image_url)
                                                        <img src="{{ $who['entity']->image_url }}"
                                                            alt="{{ $who['name'] }}" class="h-full w-full object-cover">
                                                    @else
                                                        <div class="flex h-full w-full items-center justify-center text-sm opacity-30">✦</div>
                                                    @endif

                                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-1 pb-0.5 pt-2">
                                                        <p class="truncate text-[9px] font-black leading-tight text-white">
                                                            {{ $who['name'] }}
                                                        </p>
                                                    </div>

                                                    <span class="absolute left-0.5 top-0.5 text-[9px]">✔</span>

                                                </div>
                                            @else
                                                <div class="flex aspect-square items-center justify-center rounded-lg border border-dashed border-slate-800">
                                                    <span class="text-[9px] font-black text-slate-700">
                                                        {{ $feeders[$slotIndex] ?? '?' }}
                                                    </span>
                                                </div>
                                            @endif

                                        </div>
                                    @endforeach

                                </div>

                                <p class="mt-1.5 text-[9px] font-bold leading-tight text-slate-600">
                                    @if ($anyQualified)
                                        <span class="text-violet-400">Clasificado</span> · falta el otro lado
                                    @elseif ($feeders)
                                        Ganadores de {{ $previousName }} · {{ $feeders[0] }} y {{ $feeders[1] }}
                                    @else
                                        Sin asignar
                                    @endif
                                </p>

                                @if ($destination)
                                    <p class="mt-1.5 truncate text-[9px] font-black text-slate-700">
                                        → {{ $destination }}
                                    </p>
                                @endif

                            </div>
                        @endif

                    @endfor

                </div>

            </div>


            {{-- SEPARADOR ENTRE RONDAS --}}

            @if (!$loop->last)
                <div class="flex w-4 shrink-0 items-center justify-center">
                    <div class="h-full w-px bg-gradient-to-b from-transparent via-slate-800 to-transparent"></div>
                </div>
            @endif
        @endforeach

    </div>

</div>

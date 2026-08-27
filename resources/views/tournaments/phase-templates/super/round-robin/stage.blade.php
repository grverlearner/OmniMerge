@php
    /*
     * Escenario central — cómo funciona la fase, de un vistazo.
     *
     *   ENTRADAS  →  PARTICIPANTES  →  SALIDAS
     *
     * Los participantes son caras prestadas de tus universos y tu
     * biblioteca. No son inscritos, no se guardan y no tienen nada que ver
     * con un torneo real: están para poder mirar la estructura y
     * entenderla en vez de leer una lista de "Seed 1, Seed 2".
     *
     * La columna de la izquierda es la puerta por la que entró cada uno; la
     * de la derecha, la salida que reclama ese puesto final. El color es el
     * mismo que en el panel derecho, y por eso se puede seguir una puerta
     * con la vista de un lado a otro de la pantalla.
     */
@endphp

<div class="p-3">

    {{-- ============ RESUMEN DE LA ESTRUCTURA ============ --}}

    <div class="mb-3 grid grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-6">

        @foreach ([
            ['key' => 'participants', 'label' => 'Compiten'],
            ['key' => 'total_rounds', 'label' => 'Jornadas'],
            ['key' => 'series_per_round', 'label' => 'Por jornada'],
            ['key' => 'total_series', 'label' => 'Enfrentamientos'],
        ] as $tile)
            <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    {{ $tile['label'] }}
                </p>
                <p class="font-mono text-lg font-black text-slate-100"
                    x-text="structure.{{ $tile['key'] }} ?? '—'"></p>
            </div>
        @endforeach

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Vueltas</p>
            <p class="font-mono text-lg font-black text-slate-100" x-text="cycles"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Descansos</p>
            <p class="font-mono text-lg font-black"
                :class="structure.is_odd ? 'text-amber-300' : 'text-slate-600'"
                x-text="structure.total_rest_assignments ?? 0"></p>
        </div>

    </div>


    {{-- ============ LA TABLA ============ --}}

    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/30">

        <div class="flex items-center justify-between gap-2 border-b border-slate-800 px-3 py-1.5">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Estructura
            </p>

            <div class="flex items-center gap-2">

                <template x-if="hasResults">
                    <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
                        <span x-text="playedCount"></span>/<span x-text="totalPlayable"></span> simulados
                    </span>
                </template>

                <button type="button" x-show="hasResults" x-cloak @click="clearResults()"
                    class="text-[9px] font-black text-slate-500 transition hover:text-rose-400">
                    limpiar
                </button>

                <p class="text-[9px] text-slate-600">
                    Caras prestadas · no son inscritos
                </p>

            </div>

        </div>

        <div class="arena-scroll max-h-[46vh] overflow-auto">

            <table class="w-full min-w-[560px] border-collapse">

                <thead class="sticky top-0 z-10 bg-slate-900">
                    <tr class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        <th class="px-2 py-1.5 text-left">Ent.</th>
                        <th class="px-1 py-1.5 text-left">Pos</th>
                        <th class="px-2 py-1.5 text-left">Competidor</th>

                        @foreach ($payload['catalog']['standings_columns'] as $key => $label)
                            <th class="px-1 py-1.5 text-center
                                @if ($key === 'POINTS') text-violet-400
                                @elseif ($key === 'SCORE_FOR') text-emerald-500
                                @elseif ($key === 'SCORE_AGAINST') text-rose-500
                                @endif"
                                title="{{ $key }}">{{ $label }}</th>
                        @endforeach

                        <th class="px-2 py-1.5 text-right">Sal.</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="(row, position) in classified" :key="row.seed">
                        <tr class="border-t border-slate-800/60 transition hover:bg-slate-800/30">

                            {{-- PUERTA DE ENTRADA --}}

                            <td class="px-2 py-1">
                                <template x-if="gateOfSeed(row.seed)">
                                    <span class="flex items-center gap-1">
                                        <span class="h-4 w-1 rounded-full"
                                            :class="gateOfSeed(row.seed).color.dot"></span>
                                        <span class="font-mono text-[9px] font-black"
                                            :class="gateOfSeed(row.seed).color.text"
                                            x-text="'#' + gateOfSeed(row.seed).number"></span>
                                    </span>
                                </template>

                                <template x-if="!gateOfSeed(row.seed)">
                                    <span class="font-mono text-[9px] text-slate-700">—</span>
                                </template>
                            </td>

                            {{-- POSICIÓN --}}

                            <td class="px-1 py-1">
                                <span class="flex items-center gap-1">
                                    <span class="flex h-5 w-5 items-center justify-center rounded font-mono text-[10px] font-black"
                                        :class="position === 0 ? 'bg-amber-400 text-slate-950'
                                            : position === 1 ? 'bg-slate-400 text-slate-950'
                                            : position === 2 ? 'bg-orange-600 text-white'
                                            : 'text-slate-500'"
                                        x-text="position + 1"></span>

                                    {{-- De qué puesto de la parrilla salió, si ya no coincide --}}
                                    <span x-show="hasResults && row.seed !== position + 1" x-cloak
                                        class="font-mono text-[8px] text-slate-600"
                                        :title="'Salió del puesto ' + row.seed"
                                        x-text="'(' + row.seed + ')'"></span>
                                </span>
                            </td>

                            {{-- COMPETIDOR --}}

                            <td class="px-2 py-1">
                                <div class="flex items-center gap-1.5">

                                    <div class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-800 ring-1"
                                        :class="gateOfSeed(row.seed) ? gateOfSeed(row.seed).color.ring : 'ring-slate-700'">
                                        <template x-if="atSeed(row.seed)?.image_url">
                                            <img :src="atSeed(row.seed).image_url" alt=""
                                                class="h-full w-full object-cover">
                                        </template>
                                    </div>

                                    <span class="truncate text-[11px] font-bold text-slate-200"
                                        x-text="atSeed(row.seed)?.short ?? '—'"
                                        :title="atSeed(row.seed)?.name"></span>

                                    {{--
                                        Reordenar a mano solo tiene sentido sobre la
                                        parrilla, así que se esconde en cuanto la tabla
                                        deja de ir en ese orden.
                                    --}}
                                    <span x-show="showsManual && !hasResults && !readonly" x-cloak
                                        class="ml-auto flex shrink-0 gap-0.5">
                                        <button type="button" @click="move(row.seed - 1, -1)"
                                            :disabled="row.seed === 1"
                                            class="flex h-4 w-4 items-center justify-center rounded text-[9px] text-slate-500 transition hover:bg-slate-700 hover:text-slate-100 disabled:opacity-20">▲</button>

                                        <button type="button" @click="move(row.seed - 1, 1)"
                                            :disabled="row.seed === castSize"
                                            class="flex h-4 w-4 items-center justify-center rounded text-[9px] text-slate-500 transition hover:bg-slate-700 hover:text-slate-100 disabled:opacity-20">▼</button>
                                    </span>

                                </div>
                            </td>

                            {{-- ESTADÍSTICAS --}}

                            @foreach ($payload['catalog']['standings_columns'] as $key => $label)
                                <td class="px-1 py-1 text-center font-mono text-[10px]
                                    @if ($key === 'POINTS') font-black text-violet-300
                                    @elseif ($key === 'SCORE_DIFFERENCE') font-black
                                    @else text-slate-400 @endif"
                                    @if ($key === 'SCORE_DIFFERENCE')
                                        :class="row.SCORE_DIFFERENCE > 0 ? 'text-emerald-400'
                                            : row.SCORE_DIFFERENCE < 0 ? 'text-rose-400' : 'text-slate-500'"
                                    @endif
                                    :class="!hasResults && '{{ $key }}' !== 'POINTS' ? 'text-slate-700' : ''">
                                    <span x-text="'{{ $key }}' === 'SCORE_DIFFERENCE' && row.SCORE_DIFFERENCE > 0
                                        ? '+' + row.SCORE_DIFFERENCE
                                        : row['{{ $key }}']"></span>
                                </td>
                            @endforeach

                            {{-- PUERTA DE SALIDA --}}

                            <td class="px-2 py-1 text-right">
                                <template x-if="exitOfPosition(position + 1)">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="font-mono text-[9px] font-black"
                                            :class="exitOfPosition(position + 1).color.text"
                                            x-text="exitOfPosition(position + 1).name"></span>
                                        <span class="h-4 w-1 rounded-full"
                                            :class="exitOfPosition(position + 1).color.dot"></span>
                                    </span>
                                </template>

                                <template x-if="!exitOfPosition(position + 1)">
                                    <span class="font-mono text-[9px] text-slate-700">—</span>
                                </template>
                            </td>

                        </tr>
                    </template>
                </tbody>

            </table>

        </div>

        <p class="border-t border-slate-800 px-3 py-1.5 text-[9px] leading-relaxed text-slate-600">
            <span x-show="!hasResults">
                Los números están a cero porque no se ha jugado nada. Simula abajo
                para ver cómo se llena la tabla y a quién se lleva cada salida.
            </span>

            <span x-show="hasResults" x-cloak>
                Resultados inventados, solo para mirar: no se guardan y se borran al
                cambiar la parrilla. Ordenado por
                <strong class="text-slate-500">{{ implode(' · ', $payload['catalog']['tiebreak_chain']) }}</strong>.
            </span>
        </p>

    </div>


    {{-- ============ FUENTE DE RANKING ============ --}}

    <div x-show="showsRanking" x-cloak
        class="mt-3 overflow-hidden rounded-xl border border-slate-800 bg-slate-900/30">

        <div class="border-b border-slate-800 px-3 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Con qué clasificación se siembra
            </p>
        </div>

        <div class="p-3">

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($payload['catalog']['ranking_sources'] as $key => $source)
                    <button type="button" @click="rankingSource = @js($key)"
                        class="rounded-lg border p-2 text-left transition"
                        :class="rankingSource === @js($key)
                            ? 'border-amber-500 bg-amber-500/10'
                            : 'border-slate-700 hover:border-slate-600'">

                        <span class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full"
                                :class="rankingSource === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>
                            <span class="text-[10px] font-black"
                                :class="rankingSource === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                                {{ $source['label'] }}
                            </span>
                        </span>

                        <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                            {{ $source['hint'] }}
                        </span>
                    </button>
                @endforeach
            </div>

            {{-- La siembra que produce esa fuente --}}

            <div class="arena-scroll mt-2 flex gap-1 overflow-x-auto pb-1">
                <template x-for="seed in castSize" :key="'rk' + seed">
                    <div class="flex w-12 shrink-0 flex-col items-center gap-0.5 rounded-md bg-slate-900 px-1 py-1">
                        <span class="font-mono text-[9px] font-black text-amber-400" x-text="'#' + seed"></span>

                        <div class="h-7 w-7 overflow-hidden rounded bg-slate-800">
                            <template x-if="atSeed(seed)?.image_url">
                                <img :src="atSeed(seed).image_url" alt="" class="h-full w-full object-cover">
                            </template>
                        </div>

                        <span class="w-full truncate text-center text-[8px] text-slate-500"
                            x-text="atSeed(seed)?.short"></span>
                    </div>
                </template>
            </div>

            <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
                Esta siembra es una demostración. Una fase vive en tu biblioteca y
                no pertenece a ningún universo ni torneo, así que la clasificación
                real no existe hasta que un torneo la use: lo que se guarda es
                <strong class="text-slate-500">cuál de las dos fuentes</strong> leer entonces.
            </p>

        </div>

    </div>


    {{-- ============ AVISO DE MODO ============ --}}

    <div x-show="showsGateOrder" x-cloak
        class="mt-3 rounded-xl border border-sky-500/30 bg-sky-500/5 px-3 py-2">
        <p class="text-[10px] leading-relaxed text-sky-200/80">
            <strong class="font-black">El orden lo mandan las puertas.</strong>
            Cambia una puerta o su capacidad en el panel derecho y la tabla se
            reordena sola.
        </p>
    </div>

    <div x-show="orderMode === 'RANDOM'" x-cloak
        class="mt-3 rounded-xl border border-slate-700 bg-slate-900/40 px-3 py-2">
        <p class="text-[10px] leading-relaxed text-slate-400">
            El sorteo de verdad lo hace el motor al arrancar la fase, con los
            participantes reales. Lo de aquí es una tirada de muestra para ver
            cómo queda.
        </p>
    </div>

    <div x-show="showsManual" x-cloak
        class="mt-3 rounded-xl border border-slate-700 bg-slate-900/40 px-3 py-2">
        <p class="text-[10px] leading-relaxed text-slate-400">
            Al arrancar, la fase se detendrá y te pedirá colocar a los
            participantes reales. Mueve estos con las flechas para ver cómo
            cambian los emparejamientos.
        </p>
    </div>

</div>

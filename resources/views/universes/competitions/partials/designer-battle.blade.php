@php
    /*
     * 04 · LA BATALLA — cómo se pelea, y cómo se decide quién ganó.
     *
     * Cuatro preguntas distintas que suelen confundirse:
     *
     *   cuántos compiten a la vez   2 es un duelo, 4 una batalla campal
     *   cuántos juegos dura         al mejor de N, o N fijos
     *   qué decide el ganador       el marcador, o las anotaciones
     *   si puede quedar en tablas   casi nunca, pero en una liga sí
     *
     * La tercera es la que más cuesta explicar con palabras, así que se
     * dibuja: el mismo enfrentamiento leído de las dos maneras, con el
     * ganador cambiando según cuál se elija.
     */
@endphp

<section x-show="isOpen('battle')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-amber-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">04</span>
        <span class="text-[11px]">⚔</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-amber-300">La batalla</h2>
        <span class="ml-auto text-[10px] text-slate-600">Cómo se pelea en esta edición</span>
    </div>

    <div class="space-y-3 p-4">

        {{-- ============ CUÁNTOS COMPITEN A LA VEZ ============ --}}

        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Cuántos compiten en cada batalla
            </p>

            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">

                <button type="button" @click="battleParticipants = ''"
                    class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition"
                    :class="!battleParticipants
                        ? 'border-amber-400/60 bg-amber-500/10 text-amber-300'
                        : 'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-700'">
                    lo que diga cada fase
                </button>

                @foreach ([2, 3, 4, 6, 8] as $n)
                    <button type="button" @click="battleParticipants = {{ $n }}"
                        class="w-11 rounded-lg border py-1.5 text-center font-mono text-[12px] font-black transition"
                        :class="Number(battleParticipants) === {{ $n }}
                            ? 'border-amber-400/60 bg-amber-500/10 text-amber-300'
                            : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'">
                        {{ $n }}
                    </button>
                @endforeach

                <input type="number" min="2" max="64" x-model="battleParticipants"
                    placeholder="otro"
                    class="w-16 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center font-mono text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            </div>

            <p class="mt-1.5 text-[10px] leading-relaxed text-slate-500">
                <span x-show="!battleParticipants">
                    Cada fase usará lo que su propia forma dicte: un cuadro cruza de
                    dos en dos, unos grupos pueden juntar a cuatro.
                </span>
                <span x-show="Number(battleParticipants) === 2">Duelos: uno contra uno.</span>
                <span x-show="Number(battleParticipants) > 2">
                    Batalla campal: <span x-text="battleParticipants"></span> compitiendo a la vez,
                    y gana uno.
                </span>
            </p>

            <template x-if="game && battleParticipants && (Number(battleParticipants) < game.min_participants
                || (game.max_participants && Number(battleParticipants) > game.max_participants))">
                <p class="mt-1.5 rounded-lg bg-rose-500/10 px-2 py-1 text-[10px] text-rose-300">
                    <span x-text="game.name"></span> admite
                    <span x-text="game.min_participants + (game.max_participants ? '–' + game.max_participants : ' o más')"></span>
                    por batalla: con <span x-text="battleParticipants"></span> no puede resolver.
                </p>
            </template>

            <input type="hidden" name="battle_participants" :value="battleParticipants">
        </div>


        {{-- ============ CUÁNTOS JUEGOS DURA ============ --}}

        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Cuántos juegos dura un enfrentamiento
            </p>

            <div class="mt-1.5 grid gap-2 sm:grid-cols-2">

                @foreach ([
                    'BEST_OF' => ['Al mejor de N', 'Se juega hasta que alguien gana la mitad más uno. Un 3-0 acaba antes.'],
                    'FIXED_GAMES' => ['N juegos fijos', 'Se juegan todos, pase lo que pase. Lo decide el conjunto.'],
                ] as $value => [$label, $help])
                    <button type="button" @click="seriesFormat = '{{ $value }}'"
                        class="rounded-lg border p-2.5 text-left transition"
                        :class="seriesFormat === '{{ $value }}'
                            ? 'border-amber-400/60 bg-amber-500/10'
                            : 'border-slate-800 bg-slate-950 hover:border-slate-700'">

                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full border-2 transition"
                                :class="seriesFormat === '{{ $value }}'
                                    ? 'border-amber-400 bg-amber-400'
                                    : 'border-slate-600'"></span>

                            <span class="text-[11px] font-black"
                                :class="seriesFormat === '{{ $value }}' ? 'text-amber-300' : 'text-slate-300'">
                                {{ $label }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-[9px] leading-relaxed text-slate-500">{{ $help }}</p>
                    </button>
                @endforeach
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-1.5" x-show="seriesFormat === 'BEST_OF'">
                @foreach ([1, 3, 5, 7] as $n)
                    <button type="button" @click="bestOf = {{ $n }}"
                        class="w-11 rounded-lg border py-1.5 text-center font-mono text-[12px] font-black transition"
                        :class="bestOf === {{ $n }}
                            ? 'border-amber-400/60 bg-amber-500/10 text-amber-300'
                            : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'">
                        {{ $n }}
                    </button>
                @endforeach

                <input type="number" min="1" max="15" step="2" x-model.number="bestOf" @change="fixBestOf()"
                    class="w-16 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center font-mono text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">

                <p class="text-[10px] text-slate-500" x-show="!bestOfIsEven">
                    Gana el primero en llegar a
                    <span class="font-mono font-black text-amber-300" x-text="Math.floor(bestOf / 2) + 1"></span>.
                </p>

                <p class="text-[10px] text-rose-300" x-show="bestOfIsEven">
                    Al mejor de un par no se puede decidir: se empata a la mitad.
                    <button type="button" @click="fixBestOf()" class="underline">subir a <span x-text="bestOf + 1"></span></button>
                </p>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-1.5" x-show="seriesFormat === 'FIXED_GAMES'" x-cloak>
                @foreach ([1, 2, 3, 4] as $n)
                    <button type="button" @click="fixedGames = {{ $n }}"
                        class="w-11 rounded-lg border py-1.5 text-center font-mono text-[12px] font-black transition"
                        :class="fixedGames === {{ $n }}
                            ? 'border-amber-400/60 bg-amber-500/10 text-amber-300'
                            : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'">
                        {{ $n }}
                    </button>
                @endforeach

                <input type="number" min="1" max="15" x-model.number="fixedGames"
                    class="w-16 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center font-mono text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">

                <p class="text-[10px] text-slate-500">
                    Se juegan los <span class="font-mono font-black text-amber-300" x-text="fixedGames"></span>,
                    y si quedan igualados decide el acumulado.
                </p>
            </div>

            <input type="hidden" name="series_format" :value="seriesFormat">
            <input type="hidden" name="best_of" :value="bestOf">
            <input type="hidden" name="fixed_games" :value="fixedGames">

            <x-input-error :messages="$errors->get('series_format')" class="mt-1" />
            <x-input-error :messages="$errors->get('best_of')" class="mt-1" />
            <x-input-error :messages="$errors->get('fixed_games')" class="mt-1" />
        </div>


        {{-- ============ QUÉ DECIDE AL GANADOR ============ --}}

        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Qué decide quién gana el enfrentamiento
            </p>

            <p class="mt-0.5 text-[10px] leading-relaxed text-slate-600">
                No es lo mismo ganar más juegos que sumar más. Un competidor puede
                ganar 2 de 3 por poco y perder el tercero por mucho.
            </p>

            <div class="mt-2 grid gap-2 lg:grid-cols-2">

                @foreach ([
                    'SERIES_THEN_POINTS' => ['El marcador', 'Manda quién ganó más juegos. Solo si empatan deciden las anotaciones.'],
                    'POINTS_ONLY' => ['Las anotaciones', 'Solo cuenta lo acumulado. Da igual cuántos juegos ganó cada uno.'],
                ] as $value => [$label, $help])
                    <button type="button" @click="decisionMode = '{{ $value }}'"
                        class="rounded-lg border p-2.5 text-left transition"
                        :class="decisionMode === '{{ $value }}'
                            ? 'border-amber-400/60 bg-amber-500/10'
                            : 'border-slate-800 bg-slate-950 hover:border-slate-700'">

                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full border-2 transition"
                                :class="decisionMode === '{{ $value }}'
                                    ? 'border-amber-400 bg-amber-400'
                                    : 'border-slate-600'"></span>

                            <span class="text-[11px] font-black"
                                :class="decisionMode === '{{ $value }}' ? 'text-amber-300' : 'text-slate-300'">
                                {{ $label }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-[9px] leading-relaxed text-slate-500">{{ $help }}</p>
                    </button>
                @endforeach
            </div>


            {{--
                El mismo enfrentamiento, leído de las dos maneras.

                Es el caso que hace visible la diferencia: A gana dos juegos
                ajustados y pierde el tercero por goleada. Con el marcador
                gana A; con las anotaciones gana B. Explicarlo con palabras
                no convence a nadie; verlo, sí.
            --}}

            <div class="mt-2.5 rounded-xl border border-slate-800 bg-slate-900/60 p-3">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    El mismo enfrentamiento, con cada criterio
                </p>

                @php
                    $juegos = [[7, 5], [6, 4], [2, 20]];
                    $marcA = 2; $marcB = 1;
                    $puntA = 15; $puntB = 29;
                @endphp

                <div class="mt-2 space-y-1">
                    @foreach ($juegos as $i => [$a, $b])
                        <div class="flex items-center gap-2">
                            <span class="w-12 shrink-0 font-mono text-[9px] text-slate-600">juego {{ $i + 1 }}</span>

                            <span class="w-8 shrink-0 text-right font-mono text-[12px] font-black {{ $a > $b ? 'text-emerald-300' : 'text-slate-600' }}">{{ $a }}</span>

                            <span class="flex h-3 flex-1 overflow-hidden rounded-full bg-slate-950">
                                <span class="{{ $a > $b ? 'bg-emerald-500/60' : 'bg-slate-700' }}"
                                    style="width: {{ round($a / ($a + $b) * 100) }}%"></span>
                                <span class="{{ $b > $a ? 'bg-sky-500/60' : 'bg-slate-700' }}"
                                    style="width: {{ round($b / ($a + $b) * 100) }}%"></span>
                            </span>

                            <span class="w-8 shrink-0 font-mono text-[12px] font-black {{ $b > $a ? 'text-sky-300' : 'text-slate-600' }}">{{ $b }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 grid gap-1.5 sm:grid-cols-2">

                    <div class="rounded-lg border p-2 transition"
                        :class="decisionMode === 'SERIES_THEN_POINTS'
                            ? 'border-amber-400/60 bg-amber-500/10'
                            : 'border-slate-800 bg-slate-950/60 opacity-50'">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Marcador</p>
                        <p class="font-mono text-[15px] font-black text-slate-100">{{ $marcA }} – {{ $marcB }}</p>
                        <p class="text-[10px] text-emerald-300">Gana <span class="font-black">A</span>, por juegos ganados.</p>
                    </div>

                    <div class="rounded-lg border p-2 transition"
                        :class="decisionMode === 'POINTS_ONLY'
                            ? 'border-amber-400/60 bg-amber-500/10'
                            : 'border-slate-800 bg-slate-950/60 opacity-50'">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Anotaciones</p>
                        <p class="font-mono text-[15px] font-black text-slate-100">{{ $puntA }} – {{ $puntB }}</p>
                        <p class="text-[10px] text-sky-300">Gana <span class="font-black">B</span>, por acumulado.</p>
                    </div>
                </div>

                <p class="mt-1.5 text-[9px] leading-relaxed text-slate-600">
                    El mismo enfrentamiento tiene dos ganadores distintos según qué se
                    mire. Por eso se elige aquí y no se deja al azar.
                </p>
            </div>

            <input type="hidden" name="decision_mode" :value="decisionMode">
        </div>


        {{-- ============ EMPATES ============ --}}

        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">

            <label class="flex cursor-pointer items-start gap-2">
                {{-- El 0 primero: sin marcar, un checkbox no envia nada --}}
                <input type="hidden" name="allow_draws" value="0">
                <input type="checkbox" name="allow_draws" value="1" x-model="allowDraws"
                    class="mt-0.5 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">

                <span>
                    <span class="text-[11px] font-black text-slate-300">
                        Un enfrentamiento puede quedar en empate
                    </span>

                    <span class="mt-0.5 block text-[10px] leading-relaxed text-slate-500">
                        En una liga o unos grupos un empate es un resultado real —un punto
                        para cada uno—. En un cuadro no: la ronda siguiente necesita a
                        alguien a quien colocar, así que <span class="font-bold text-slate-400">las
                        eliminatorias siempre exigen ganador</span> aunque esto esté marcado.
                    </span>
                </span>
            </label>
        </div>


        {{-- ============ UNO PARA TODO, O UNO POR FASE ============ --}}

        @if ($inherited['allow_phase_battle'])
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Dónde se decide
                </p>

                <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                    @foreach ([
                        'COMPETITION' => ['Toda la edición igual', 'Se pelea igual en todas las fases. Es lo normal.'],
                        'PHASE' => ['Cada fase la suya', '«Todo al mejor de 3, menos la final, que es al 5». Se ajusta en el bloque 05.'],
                    ] as $value => [$label, $help])
                        <button type="button" @click="battleScope = '{{ $value }}'"
                            class="rounded-lg border p-2.5 text-left transition"
                            :class="battleScope === '{{ $value }}'
                                ? 'border-amber-400/60 bg-amber-500/10'
                                : 'border-slate-800 bg-slate-950 hover:border-slate-700'">

                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full border-2 transition"
                                    :class="battleScope === '{{ $value }}'
                                        ? 'border-amber-400 bg-amber-400'
                                        : 'border-slate-600'"></span>

                                <span class="text-[11px] font-black"
                                    :class="battleScope === '{{ $value }}' ? 'text-amber-300' : 'text-slate-300'">
                                    {{ $label }}
                                </span>
                            </div>

                            <p class="mt-0.5 text-[9px] leading-relaxed text-slate-500">{{ $help }}</p>
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            <p class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-[10px] leading-relaxed text-slate-600">
                Se pelea igual en todas las fases de esta edición. Para poder hacer
                excepciones —«menos la final»— hay que habilitarlo en el torneo.
            </p>
        @endif

        <input type="hidden" name="battle_scope" :value="battleScope">

    </div>
</section>

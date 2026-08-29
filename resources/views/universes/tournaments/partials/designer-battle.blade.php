@php
    /*
     * 03 · LA BATALLA — cómo se pelea y cómo se decide quién gana.
     *
     * Tres preguntas encadenadas:
     *
     *   1. cuántos caben en una batalla
     *   2. cuántos enfrentamientos tiene
     *   3. QUIÉN GANA cuando ya se han jugado
     *
     * La tercera es la que no era obvia, y es la razón de que este bloque
     * lleve un ejemplo dibujado en vez de una frase.
     *
     * Una batalla produce DOS cuentas distintas:
     *
     *   el marcador     cuántos enfrentamientos ganó cada uno
     *   las anotaciones cuántos puntos sumó en total dentro de ellos
     *
     * Y no siempre coinciden. Se puede ganar el marcador perdiendo las
     * anotaciones: ganar dos apretados y perder uno por goleada. Cuál de
     * las dos manda es una decisión de diseño del torneo, no un detalle.
     *
     * El ejemplo está elegido para que los dos modos den ganadores
     * DISTINTOS: con uno donde coinciden no se ve qué se está eligiendo.
     */
@endphp

<section x-show="isOpen('battle')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-amber-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">03</span>
        <span class="text-[11px]">⚔</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-amber-300">La batalla</h2>
        <span class="ml-auto text-[10px] text-slate-600">Cómo se pelea y quién gana</span>
    </div>

    <div class="space-y-4 p-4">

        {{-- ==================== CUÁNTOS CABEN ==================== --}}

        <div>
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Cuántos compiten en una batalla
            </p>

            <div class="mt-1.5 flex flex-wrap gap-1.5">

                {{--
                    «Según la fase» no es un valor: es renunciar a decidirlo
                    aquí. Hace falta cuando el torneo tiene grupos de cuatro
                    y una final a dos.
                --}}
                <button type="button" @click="battleParticipants = ''"
                    class="rounded-xl border px-3 py-2 text-left transition"
                    :class="battleParticipants === ''
                        ? 'border-amber-400/60 bg-amber-500/10'
                        : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">
                    <span class="block text-[11px] font-black"
                        :class="battleParticipants === '' ? 'text-amber-300' : 'text-slate-300'">
                        Varía
                    </span>
                    <span class="block text-[9px] text-slate-600">lo decide cada fase</span>
                </button>

                <template x-for="n in participantChoices" :key="'p' + n">
                    <button type="button" @click="battleParticipants = n"
                        class="w-[86px] rounded-xl border px-3 py-2 text-left transition"
                        :class="battleParticipants === n
                            ? 'border-amber-400/60 bg-amber-500/10'
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                        <span class="flex items-baseline gap-1">
                            <span class="font-mono text-lg font-black"
                                :class="battleParticipants === n ? 'text-amber-300' : 'text-slate-300'"
                                x-text="n"></span>

                            {{-- Las siluetas dicen de un vistazo qué clase de batalla es --}}
                            <span class="flex gap-0.5">
                                <template x-for="i in n" :key="'s' + i">
                                    <span class="h-2 w-2 rounded-full"
                                        :class="battleParticipants === n ? 'bg-amber-400/70' : 'bg-slate-700'"></span>
                                </template>
                            </span>
                        </span>

                        <span class="block text-[9px] text-slate-600"
                            x-text="n === 2 ? 'un duelo' : 'campal'"></span>
                    </button>
                </template>

            </div>

            <input type="hidden" name="battle_participants" :value="battleParticipants">

            <p class="mt-1.5 text-[9px] leading-relaxed text-slate-600">
                Lo que ofrece esta lista sale del juego elegido: cada uno declara
                cuántos admite, y ofrecer más sería ofrecer una batalla que no se
                puede jugar.
            </p>

            <x-input-error :messages="$errors->get('battle_participants')" class="mt-1" />
        </div>


        {{-- ==================== CUÁNTOS ENFRENTAMIENTOS ==================== --}}

        <div class="border-t border-slate-800 pt-4">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Cuántos enfrentamientos tiene una batalla
            </p>

            <div class="mt-1.5 grid gap-2 sm:grid-cols-2">

                @foreach ([
                    'BEST_OF' => ['Al mejor de N', 'Se para en cuanto está decidido. Al mejor de 3, quien gane los dos primeros no juega el tercero.'],
                    'FIXED_GAMES' => ['Enfrentamientos fijos', 'Se juegan todos, decida o no decida. Es lo que hace que las anotaciones importen.'],
                ] as $value => [$label, $help])
                    <button type="button" @click="seriesFormat = '{{ $value }}'"
                        class="rounded-xl border p-3 text-left transition"
                        :class="seriesFormat === '{{ $value }}'
                            ? 'border-amber-400/60 bg-amber-500/10'
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full border-2 transition"
                                :class="seriesFormat === '{{ $value }}' ? 'border-amber-400 bg-amber-400' : 'border-slate-600'"></span>
                            <span class="text-[12px] font-black"
                                :class="seriesFormat === '{{ $value }}' ? 'text-amber-300' : 'text-slate-300'">{{ $label }}</span>
                        </div>

                        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">{{ $help }}</p>
                    </button>
                @endforeach

            </div>

            <input type="hidden" name="series_format" :value="seriesFormat">

            <div class="mt-2 flex flex-wrap items-center gap-1.5">

                <template x-if="seriesFormat === 'BEST_OF'">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Al mejor de</span>

                        @foreach ([1, 3, 5, 7, 9] as $n)
                            <button type="button" @click="bestOf = {{ $n }}"
                                class="h-9 w-9 rounded-lg border font-mono text-[13px] font-black transition"
                                :class="bestOf === {{ $n }}
                                    ? 'border-amber-400/60 bg-amber-500/15 text-amber-300'
                                    : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700'">{{ $n }}</button>
                        @endforeach

                        <span class="text-[9px] text-slate-600">
                            siempre impar — al mejor de un par se empata a la mitad
                        </span>
                    </div>
                </template>

                <template x-if="seriesFormat === 'FIXED_GAMES'">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuántos juegos</span>

                        @foreach ([1, 2, 3, 4, 5, 6] as $n)
                            <button type="button" @click="fixedGames = {{ $n }}"
                                class="h-9 w-9 rounded-lg border font-mono text-[13px] font-black transition"
                                :class="fixedGames === {{ $n }}
                                    ? 'border-amber-400/60 bg-amber-500/15 text-amber-300'
                                    : 'border-slate-800 bg-slate-950/50 text-slate-500 hover:border-slate-700'">{{ $n }}</button>
                        @endforeach

                        <span class="text-[9px] text-slate-600">se juegan todos</span>
                    </div>
                </template>

            </div>

            <input type="hidden" name="best_of" :value="bestOf">
            <input type="hidden" name="fixed_games" :value="fixedGames">

            <x-input-error :messages="$errors->get('series_format')" class="mt-1" />
            <x-input-error :messages="$errors->get('best_of')" class="mt-1" />
            <x-input-error :messages="$errors->get('fixed_games')" class="mt-1" />
        </div>


        {{-- ==================== QUIÉN GANA ==================== --}}

        <div class="border-t border-slate-800 pt-4">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Y entonces, ¿quién gana la batalla?
            </p>

            <p class="mt-1 max-w-2xl text-[10px] leading-relaxed text-slate-500">
                Una batalla produce <strong class="text-slate-300">dos cuentas</strong>, y no
                siempre coinciden. El <strong class="text-sky-300">marcador</strong> cuenta
                cuántos enfrentamientos ganó cada uno. Las
                <strong class="text-violet-300">anotaciones</strong> suman los puntos que hizo
                dentro de ellos. Se puede ganar el marcador perdiendo las anotaciones: ganar
                dos apretados y perder uno por goleada.
            </p>


            {{-- ============ EL EJEMPLO ============ --}}

            {{--
                Los números están elegidos para que los dos modos den
                ganadores DISTINTOS. Con un ejemplo donde coinciden, esta
                sección no explicaría nada.
            --}}

            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/60">

                <p class="border-b border-slate-800 px-3 py-1.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Un ejemplo — al mejor de 3
                </p>

                <div class="grid gap-3 p-3 lg:grid-cols-[1fr_auto]">

                    {{-- Los enfrentamientos --}}

                    <div class="space-y-1">
                        <template x-for="g in example.games" :key="'g' + g.n">
                            <div class="flex items-center gap-2 rounded-lg bg-slate-900/60 px-2.5 py-1.5">
                                <span class="w-16 shrink-0 font-mono text-[9px] text-slate-600"
                                    x-text="'juego ' + g.n"></span>

                                <span class="font-mono text-[13px] font-black"
                                    :class="g.a > g.b ? 'text-emerald-300' : 'text-slate-500'"
                                    x-text="g.a"></span>

                                <span class="text-[10px] text-slate-700">–</span>

                                <span class="font-mono text-[13px] font-black"
                                    :class="g.b > g.a ? 'text-emerald-300' : 'text-slate-500'"
                                    x-text="g.b"></span>

                                <span class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black"
                                    :class="g.a > g.b ? 'bg-sky-500/15 text-sky-300' : 'bg-rose-500/15 text-rose-300'"
                                    x-text="'gana ' + (g.a > g.b ? 'A' : 'B')"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Las dos cuentas, enfrentadas --}}

                    <div class="grid gap-2 sm:grid-cols-2 lg:w-[300px]">

                        <div class="rounded-xl border p-2.5 transition"
                            :class="decisionMode === 'SERIES_THEN_POINTS'
                                ? 'border-sky-400/60 bg-sky-500/10'
                                : 'border-slate-800 bg-slate-950/60 opacity-50'">

                            <p class="text-[9px] font-black uppercase tracking-wider text-sky-300">Marcador</p>

                            <p class="mt-1 font-mono text-xl font-black text-slate-100">
                                <span x-text="example.wins.a"></span>–<span x-text="example.wins.b"></span>
                            </p>

                            <p class="text-[9px] text-slate-500">enfrentamientos ganados</p>

                            <p class="mt-1 text-[10px] font-black"
                                :class="example.bySeries === 'A' ? 'text-sky-300' : 'text-rose-300'"
                                x-text="example.bySeries ? 'gana ' + example.bySeries : 'empate'"></p>
                        </div>

                        <div class="rounded-xl border p-2.5 transition"
                            :class="decisionMode === 'POINTS_ONLY'
                                ? 'border-violet-400/60 bg-violet-500/10'
                                : 'border-slate-800 bg-slate-950/60 opacity-50'">

                            <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">Anotaciones</p>

                            <p class="mt-1 font-mono text-xl font-black text-slate-100">
                                <span x-text="example.points.a"></span>–<span x-text="example.points.b"></span>
                            </p>

                            <p class="text-[9px] text-slate-500">puntos acumulados</p>

                            <p class="mt-1 text-[10px] font-black"
                                :class="example.byPoints === 'A' ? 'text-sky-300' : 'text-rose-300'"
                                x-text="example.byPoints ? 'gana ' + example.byPoints : 'empate'"></p>
                        </div>

                    </div>

                </div>

                <p class="border-t border-slate-800 bg-slate-900/60 px-3 py-2 text-[11px] font-black">
                    <span class="text-slate-500">Con lo que has elegido, esta batalla la gana</span>
                    <span class="ml-1 rounded px-2 py-0.5 text-slate-950"
                        :class="exampleWinner === 'A' ? 'bg-sky-400' : 'bg-rose-400'"
                        x-text="exampleWinner ?? '—'"></span>
                </p>

            </div>


            {{-- ============ LOS DOS MODOS ============ --}}

            <div class="mt-3 grid gap-2 sm:grid-cols-2">

                {{--
                    Las clases van enteras y literales, no armadas con una
                    variable: Tailwind lee el codigo fuente para decidir que
                    compila, y una clase construida en tiempo de render
                    nunca llega al CSS.
                --}}
                @foreach ([
                    'SERIES_THEN_POINTS' => [
                        'label' => 'Manda el marcador',
                        'help' => 'Gana quien se lleve más enfrentamientos. Solo si empatan deciden las anotaciones.',
                        'on' => 'border-sky-400/60 bg-sky-500/10',
                        'dot' => 'border-sky-400 bg-sky-400',
                        'text' => 'text-sky-300',
                    ],
                    'POINTS_ONLY' => [
                        'label' => 'Mandan las anotaciones',
                        'help' => 'Solo cuenta el total de puntos. Da igual cuántos enfrentamientos ganó cada uno.',
                        'on' => 'border-violet-400/60 bg-violet-500/10',
                        'dot' => 'border-violet-400 bg-violet-400',
                        'text' => 'text-violet-300',
                    ],
                ] as $value => $mode)
                    <button type="button" @click="decisionMode = '{{ $value }}'"
                        class="rounded-xl border p-3 text-left transition"
                        :class="decisionMode === '{{ $value }}'
                            ? '{{ $mode['on'] }}'
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full border-2 transition"
                                :class="decisionMode === '{{ $value }}' ? '{{ $mode['dot'] }}' : 'border-slate-600'"></span>
                            <span class="text-[12px] font-black"
                                :class="decisionMode === '{{ $value }}' ? '{{ $mode['text'] }}' : 'text-slate-300'">{{ $mode['label'] }}</span>
                        </div>

                        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">{{ $mode['help'] }}</p>
                    </button>
                @endforeach

            </div>

            <input type="hidden" name="decision_mode" :value="decisionMode">

            {{-- Un juego sin puntos no puede decidirse por anotaciones --}}
            <template x-if="decisionMode === 'POINTS_ONLY' && game && !game.tracks_points">
                <p class="mt-2 rounded-xl bg-amber-500/10 px-3 py-2 text-[10px] font-bold leading-relaxed text-amber-300">
                    <span x-text="game.name"></span> no lleva anotaciones, así que con este
                    modo no habría con qué decidir. Elige «manda el marcador» o un juego que
                    sí las lleve.
                </p>
            </template>

            <x-input-error :messages="$errors->get('decision_mode')" class="mt-1" />


            {{-- ============ EMPATES ============ --}}

            <label class="mt-3 flex cursor-pointer items-start gap-2 rounded-xl border p-3 transition"
                :class="allowDraws ? 'border-amber-400/50 bg-amber-500/5' : 'border-slate-800 bg-slate-950/50'">

                <input type="hidden" name="allow_draws" value="0">
                <input type="checkbox" name="allow_draws" value="1" x-model="allowDraws"
                    class="mt-0.5 h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">

                <span>
                    <span class="block text-[11px] font-black text-slate-200">
                        Una batalla puede quedar en empate
                    </span>
                    <span class="mt-0.5 block text-[10px] leading-relaxed text-slate-500">
                        Casi siempre no: un cuadro necesita que alguien avance, y una fase que
                        acaba empatada no sabe a quién mandar a la siguiente. Tiene sentido en
                        ligas, donde un empate reparte puntos.
                    </span>
                </span>
            </label>

            {{-- ============ ¿PUEDE CAMBIAR POR FASE? ============ --}}

            {{--
                «Todo al mejor de 3, menos la final, que es al 5» es la
                excepción más común que hay, y hasta ahora no se podía decir.
                El permiso se da aquí; la excepción concreta la escribe cada
                edición, porque depende de qué fases tenga la que se juegue.
            --}}

            <label class="mt-3 flex cursor-pointer items-start gap-2 rounded-xl border p-3 transition"
                :class="allowPhaseBattle ? 'border-amber-400/50 bg-amber-500/5' : 'border-slate-800 bg-slate-950/50'">

                <input type="hidden" name="allow_phase_battle" value="0">
                <input type="checkbox" name="allow_phase_battle" value="1" x-model="allowPhaseBattle"
                    class="mt-0.5 h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">

                <span>
                    <span class="block text-[11px] font-black text-slate-200">
                        Cada edición puede pelear distinto en cada fase
                    </span>
                    <span class="mt-0.5 block text-[10px] leading-relaxed text-slate-500">
                        Cuántos juegos dura un enfrentamiento, cuántos compiten a la vez, qué
                        decide al ganador y si cabe empate: con esto marcado, una edición
                        puede hacer excepciones fase por fase.
                    </span>
                </span>
            </label>

        </div>

    </div>

</section>

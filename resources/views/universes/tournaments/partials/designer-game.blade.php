@php
    /*
     * 02 · EL JUEGO — con qué se resuelve cada enfrentamiento.
     *
     * Dos formas de decidirlo, y la diferencia importa:
     *
     *   SINGLE  la Copa se juega SIEMPRE al mismo juego. Es su identidad.
     *   VARIED  cada edición elige el suyo, y lo de aquí es la sugerencia.
     *
     * El juego elegido se presenta entero —sus reglas, cómo gana, qué mide
     * de cada competidor, cuántos caben— porque elegir un juego por su
     * nombre es elegir a ciegas.
     */
@endphp

<section x-show="isOpen('game')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-emerald-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-emerald-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">02</span>
        <span class="text-[11px]">🎲</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-emerald-300">El juego</h2>
        <span class="ml-auto text-[10px] text-slate-600">Con qué se resuelve cada enfrentamiento</span>
    </div>

    <div class="p-4">

        {{-- ============ UNO FIJO, O UNO POR EDICIÓN ============ --}}

        <div class="grid gap-2 sm:grid-cols-2">

            @foreach ([
                'SINGLE' => ['Siempre el mismo', 'La Copa se juega a este juego y a ningún otro. Es parte de lo que es.'],
                'VARIED' => ['Uno por edición', 'Cada competición elige el suyo. Lo de aquí abajo es solo la sugerencia.'],
            ] as $value => [$label, $help])
                <button type="button" @click="gameMode = '{{ $value }}'"
                    class="rounded-xl border p-3 text-left transition"
                    :class="gameMode === '{{ $value }}'
                        ? 'border-emerald-400/60 bg-emerald-500/10'
                        : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                    <div class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full border-2 transition"
                            :class="gameMode === '{{ $value }}'
                                ? 'border-emerald-400 bg-emerald-400'
                                : 'border-slate-600'"></span>

                        <span class="text-[12px] font-black"
                            :class="gameMode === '{{ $value }}' ? 'text-emerald-300' : 'text-slate-300'">
                            {{ $label }}
                        </span>
                    </div>

                    <p class="mt-1 text-[10px] leading-relaxed text-slate-500">{{ $help }}</p>
                </button>
            @endforeach

        </div>

        <input type="hidden" name="game_mode" :value="gameMode">
        <input type="hidden" name="game_key" :value="gameKey">

        <x-input-error :messages="$errors->get('game_mode')" class="mt-2" />
        <x-input-error :messages="$errors->get('game_key')" class="mt-2" />


        {{-- ============ ELEGIR EL JUEGO ============ --}}

        <p class="mt-4 text-[9px] font-black uppercase tracking-wider text-slate-500"
            x-text="gameMode === 'VARIED' ? 'Juego sugerido' : 'Qué juego'"></p>

        <div class="mt-1.5 grid gap-1.5 sm:grid-cols-2">

            <template x-for="g in games" :key="g.key">
                <button type="button" @click="pickGame(g.key)"
                    class="rounded-xl border p-2.5 text-left transition"
                    :class="gameKey === g.key
                        ? 'border-emerald-400/60 bg-emerald-500/10'
                        : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                    <div class="flex items-center gap-2">
                        <span class="text-lg" x-text="g.icon"></span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[12px] font-black text-slate-100" x-text="g.name"></span>
                            <span class="block truncate text-[9px] text-slate-500" x-text="g.type_label"></span>
                        </span>

                        <span class="shrink-0 rounded-full px-1.5 py-0.5 font-mono text-[8px] font-black"
                            :class="gameKey === g.key ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-500'"
                            x-text="g.minimum_participants + (g.maximum_participants ? '–' + g.maximum_participants : '+')"></span>
                    </div>

                    <p class="mt-1 line-clamp-2 text-[10px] leading-relaxed text-slate-500" x-text="g.tagline"></p>
                </button>
            </template>

        </div>


        {{-- ============ CÓMO ES ESE JUEGO ============ --}}

        {{--
            Elegir un juego por su nombre es elegir a ciegas. Aquí se abre
            entero: qué mide de cada competidor, cómo se gana, qué pasa si
            hay empate, y sus reglas escritas.
        --}}

        <template x-if="game">
            <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-950/60 p-3">

                <div class="flex items-start gap-3">
                    <span class="text-2xl" x-text="game.icon"></span>

                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-black text-slate-100" x-text="game.name"></p>
                        <p class="mt-0.5 text-[11px] leading-relaxed text-slate-400" x-text="game.description"></p>
                    </div>
                </div>

                <div class="mt-3 grid gap-2 lg:grid-cols-2">

                    {{-- Cómo se gana --}}

                    <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-emerald-300">Cómo se gana</p>
                        <p class="mt-1 text-[10px] leading-relaxed text-slate-300" x-text="game.win_condition"></p>

                        <p class="mt-2 text-[9px] font-black uppercase tracking-wider text-slate-500">Si empatan</p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-slate-400" x-text="game.tiebreak"></p>
                    </div>

                    {{-- Qué mide de cada competidor --}}

                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Qué mide de cada competidor
                        </p>

                        <div class="mt-1 space-y-1">
                            <template x-for="stat in (game.stats ?? [])" :key="stat.key">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400"></span>
                                    <span class="text-[10px] font-bold text-slate-300" x-text="stat.label"></span>
                                    <span class="min-w-0 flex-1 truncate text-[9px] text-slate-600" x-text="stat.help"></span>
                                </div>
                            </template>
                        </div>

                        {{--
                            Las "anotaciones" del bloque siguiente salen de
                            aquí: si un juego no lleva puntos, decidir por
                            anotaciones no puede funcionar.
                        --}}
                        <p class="mt-2 flex items-center gap-1.5 text-[9px]">
                            <span class="font-black uppercase tracking-wider text-slate-500">Anotaciones</span>
                            <span x-show="game.tracks_points" class="rounded bg-emerald-500/15 px-1.5 py-0.5 font-black text-emerald-300"
                                x-text="game.points_label"></span>
                            <span x-show="!game.tracks_points" class="rounded bg-slate-800 px-1.5 py-0.5 font-black text-slate-500">
                                este juego no lleva
                            </span>
                        </p>
                    </div>

                </div>

                {{-- Sus reglas --}}

                <div class="mt-2 rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Reglas</p>

                    <ol class="mt-1 space-y-0.5">
                        <template x-for="(rule, i) in (game.rules ?? [])" :key="i">
                            <li class="flex gap-1.5">
                                <span class="w-3 shrink-0 text-right font-mono text-[9px] text-slate-600" x-text="i + 1"></span>
                                <span class="text-[10px] leading-relaxed text-slate-400" x-text="rule"></span>
                            </li>
                        </template>
                    </ol>
                </div>

            </div>
        </template>

        {{-- ============ ¿PUEDE CAMBIAR DENTRO DE UNA EDICIÓN? ============ --}}

        {{--
            Otra pregunta, y se confunde con la de arriba.

            «Uno por edición» dice si el juego puede cambiar ENTRE ediciones.
            Esto dice si puede cambiar DENTRO de una —los grupos a un juego y
            la final a otro—.

            Son independientes: un torneo puede ser siempre al mismo juego y
            aun así querer que la final se juegue a otra cosa.
        --}}

        <label class="mt-3 flex cursor-pointer items-start gap-2 rounded-xl border p-3 transition"
            :class="allowPhaseGame ? 'border-emerald-400/50 bg-emerald-500/5' : 'border-slate-800 bg-slate-950/50'">

            <input type="hidden" name="allow_phase_game" value="0">
            <input type="checkbox" name="allow_phase_game" value="1" x-model="allowPhaseGame"
                class="mt-0.5 h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500">

            <span>
                <span class="block text-[11px] font-black text-slate-200">
                    Cada edición puede usar un juego distinto en cada fase
                </span>
                <span class="mt-0.5 block text-[10px] leading-relaxed text-slate-500">
                    Sin esto, todas las fases de una edición juegan al mismo juego. Con
                    esto, la edición decide si lo baja a las fases o no —el permiso lo das
                    tú, la decisión la toma ella—.
                </span>
            </span>
        </label>

    </div>

</section>

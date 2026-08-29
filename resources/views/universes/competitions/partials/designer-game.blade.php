@php
    /*
     * 03 · EL JUEGO — con qué se resuelve cada enfrentamiento.
     *
     * Lo primero que hay que saber aquí es si SE PUEDE elegir. Un torneo de
     * juego único impone el suyo: una edición que lo cambiase ya no sería
     * la misma competición. Cuando no se puede, la pantalla lo dice y
     * enseña cuál es, en vez de ofrecer una lista que no hace nada.
     *
     * Lo segundo es dónde se decide: uno para toda la edición, o uno por
     * fase. Esa segunda puerta también la abre el torneo.
     */
@endphp

<section x-show="isOpen('game')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-emerald-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-emerald-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">03</span>
        <span class="text-[11px]">🎲</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-emerald-300">El juego</h2>
        <span class="ml-auto text-[10px] text-slate-600">Con qué se resuelve cada enfrentamiento</span>
    </div>

    <div class="space-y-3 p-4">

        {{-- ============ QUÉ PERMITE EL TORNEO ============ --}}

        @if ($inherited['game_mode'] !== 'VARIED')
            <p class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-[10px] leading-relaxed text-slate-500">
                «{{ $universeTournament->name }}» es de <span class="font-bold text-slate-300">juego único</span>:
                se juega siempre al mismo, y eso es parte de lo que es. Esta edición
                lo hereda y no puede cambiarlo. Si quieres que cada edición elija el
                suyo, hazlo del torneo — allí se decide.
            </p>
        @endif


        {{-- ============ CUÁL ============ --}}

        <div>
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                @if ($inherited['game_mode'] === 'VARIED')
                    Con qué se juega esta edición
                @else
                    El juego del torneo
                @endif
            </p>

            <div class="mt-1.5 grid gap-2 sm:grid-cols-2">

                <template x-for="g in games" :key="'g' + g.key">
                    <button type="button"
                        @click="g.allowed && (gameKey = g.key)"
                        :disabled="!g.allowed"
                        class="rounded-xl border p-3 text-left transition"
                        :class="[
                            gameKey === g.key
                                ? 'border-emerald-400/60 bg-emerald-500/10'
                                : 'border-slate-800 bg-slate-950/50',
                            g.allowed ? 'hover:border-slate-600' : 'cursor-not-allowed opacity-35',
                        ]">

                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full border-2 transition"
                                :class="gameKey === g.key ? 'border-emerald-400 bg-emerald-400' : 'border-slate-600'"></span>

                            <span class="text-[13px]" x-text="g.icon ?? '🎲'"></span>

                            <span class="text-[12px] font-black"
                                :class="gameKey === g.key ? 'text-emerald-300' : 'text-slate-300'"
                                x-text="g.name"></span>

                            <template x-if="!g.allowed">
                                <span class="ml-auto text-[8px] text-slate-600">no lo permite el torneo</span>
                            </template>
                        </div>

                        <p class="mt-1 text-[10px] leading-relaxed text-slate-500" x-text="g.tagline"></p>

                        {{--
                            Qué mide de cada competidor. Importa aquí porque
                            los premios de más abajo solo pueden tocar estas
                            estadísticas: premiar una que el juego no lleva
                            sería prometer algo que nadie puede cobrar.
                        --}}
                        <div class="mt-1.5 flex flex-wrap gap-1" x-show="g.stats.length">
                            <template x-for="st in g.stats" :key="'gs' + g.key + '-' + st.key">
                                <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[8px] text-slate-500"
                                    x-text="st.label"></span>
                            </template>
                        </div>

                        <p class="mt-1 font-mono text-[8px] text-slate-600"
                            x-text="'admite ' + g.min_participants + (g.max_participants ? '–' + g.max_participants : '+') + ' por batalla'
                                + (g.allows_draws ? ' · puede empatar' : ' · nunca empata')"></p>
                    </button>
                </template>

            </div>

            <input type="hidden" name="game_key" :value="gameKey">
            <x-input-error :messages="$errors->get('game_key')" class="mt-1" />
        </div>


        {{-- ============ UNO PARA TODO, O UNO POR FASE ============ --}}

        @if ($inherited['allow_phase_game'])
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Dónde se decide
                </p>

                <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                    @foreach ([
                        'COMPETITION' => ['Toda la edición igual', 'Las fases juegan todas al mismo juego. Es lo normal.'],
                        'PHASE' => ['Cada fase el suyo', 'Los grupos a un juego y la final a otro. Se elige fase por fase, en el bloque 05.'],
                    ] as $value => [$label, $help])
                        <button type="button" @click="gameScope = '{{ $value }}'"
                            class="rounded-lg border p-2.5 text-left transition"
                            :class="gameScope === '{{ $value }}'
                                ? 'border-emerald-400/60 bg-emerald-500/10'
                                : 'border-slate-800 bg-slate-950 hover:border-slate-700'">

                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full border-2 transition"
                                    :class="gameScope === '{{ $value }}'
                                        ? 'border-emerald-400 bg-emerald-400'
                                        : 'border-slate-600'"></span>

                                <span class="text-[11px] font-black"
                                    :class="gameScope === '{{ $value }}' ? 'text-emerald-300' : 'text-slate-300'">
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
                Todas las fases de esta edición juegan al mismo juego. Para poder
                cambiarlo fase por fase, hay que habilitarlo en el torneo.
            </p>
        @endif

        <input type="hidden" name="game_scope" :value="gameScope">

    </div>
</section>

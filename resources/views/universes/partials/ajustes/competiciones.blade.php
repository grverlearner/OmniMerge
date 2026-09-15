{{--
    TORNEOS NUEVOS — con qué nace un torneo de este mundo.

    Es el punto de partida del diseñador al crear un torneo: su formato de
    batalla, cómo se decide, cada cuánto se juega y cómo arranca su sala de
    participantes. Los torneos que ya existen conservan lo suyo.
--}}

<section id="competiciones" data-seccion class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3"
        style="background: linear-gradient(120deg, #fb923c1a, transparent 60%)">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-500/15 text-orange-300">
            <x-omni-icon name="trofeo" size="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-black text-white">Torneos nuevos</h2>
            <p class="text-[10px] text-slate-500">Con qué nace un torneo al crearlo en este universo. Los que ya existen no cambian.</p>
        </div>
        <a href="{{ route('universes.tournaments.create', $universe) }}" target="_blank" rel="noopener"
            class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-orange-500 hover:text-orange-300">Probar a crear uno</a>
        <button type="button" @click="restablecer(['default_series_format', 'default_best_of', 'default_fixed_games', 'default_decision_mode', 'default_allow_draws', 'default_recurrence', 'default_face_mode', 'default_door_strategy'])"
            class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="deshacer" size="h-3 w-3" />
            Restablecer
        </button>
    </header>

    @foreach (['default_series_format', 'default_best_of', 'default_fixed_games', 'default_decision_mode', 'default_recurrence', 'default_face_mode', 'default_door_strategy'] as $campo)
        <input type="hidden" name="settings[{{ $campo }}]" :value="s.{{ $campo }}">
    @endforeach
    <input type="hidden" name="settings[default_allow_draws]" :value="s.default_allow_draws ? 1 : 0">

    <div class="grid gap-4 p-4 lg:grid-cols-2">

        {{-- ============ LA BATALLA ============ --}}

        <div class="space-y-3 rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
            <p class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-orange-300">
                <x-omni-icon name="espadas" size="h-3.5 w-3.5" /> La batalla
            </p>

            <div class="grid grid-cols-2 gap-1.5">
                @foreach (['BEST_OF' => ['Al mejor de', 'Gana quien antes llegue a la mayoría'], 'FIXED_GAMES' => ['Juegos fijos', 'Se juegan todos, pase lo que pase']] as $valor => [$texto, $ayuda])
                    <button type="button" @click="s.default_series_format = '{{ $valor }}'"
                        class="rounded-xl border px-2.5 py-2 text-left transition"
                        :class="s.default_series_format === '{{ $valor }}' ? 'border-orange-400 bg-orange-500/15' : 'border-slate-800 hover:border-slate-600'">
                        <span class="block text-[12px] font-black text-slate-100">{{ $texto }}</span>
                        <span class="block text-[9px] leading-3 text-slate-500">{{ $ayuda }}</span>
                    </button>
                @endforeach
            </div>

            <div x-show="s.default_series_format === 'BEST_OF'" class="flex flex-wrap items-center gap-1">
                <span class="mr-1 text-[10px] text-slate-500">Al mejor de</span>
                @foreach ([1, 3, 5, 7, 9] as $n)
                    <button type="button" @click="s.default_best_of = {{ $n }}"
                        class="h-9 w-9 rounded-xl border font-mono text-[14px] font-black transition"
                        :class="s.default_best_of == {{ $n }} ? 'border-orange-400 bg-orange-500 text-slate-950' : 'border-slate-700 text-slate-400 hover:text-white'">{{ $n }}</button>
                @endforeach
            </div>

            <label x-show="s.default_series_format === 'FIXED_GAMES'" class="flex items-center gap-2">
                <span class="text-[10px] text-slate-500">Juegos por enfrentamiento</span>
                <input type="number" min="1" max="20" x-model.number="s.default_fixed_games"
                    class="w-20 rounded-lg border-slate-700 bg-slate-900 py-1 text-center font-mono text-[14px] font-black text-white focus:border-orange-500 focus:ring-orange-500">
            </label>

            <div>
                <p class="text-[10px] text-slate-500">Cómo se decide quién gana</p>
                <div class="mt-1 grid gap-1.5">
                    @foreach ($decisiones as $valor => $texto)
                        <button type="button" @click="s.default_decision_mode = '{{ $valor }}'"
                            class="flex items-center gap-2 rounded-xl border px-2.5 py-2 text-left transition"
                            :class="s.default_decision_mode === '{{ $valor }}' ? 'border-orange-400 bg-orange-500/10' : 'border-slate-800 hover:border-slate-600'">
                            <span class="h-2.5 w-2.5 rounded-full border-2" :class="s.default_decision_mode === '{{ $valor }}' ? 'border-orange-400 bg-orange-400' : 'border-slate-600'"></span>
                            <span class="text-[11px] font-bold text-slate-200">{{ $texto }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <button type="button" @click="s.default_allow_draws = ! s.default_allow_draws"
                class="flex w-full items-center gap-2 rounded-xl border border-slate-800 px-2.5 py-2 text-left transition hover:border-slate-600">
                <span class="min-w-0 flex-1">
                    <span class="block text-[12px] font-black text-slate-100">Permitir empates</span>
                    <span class="block text-[9px] text-slate-500">Si no, un enfrentamiento siempre tiene ganador</span>
                </span>
                <span class="relative h-5 w-9 shrink-0 rounded-full transition" :class="s.default_allow_draws ? 'bg-orange-500' : 'bg-slate-700'">
                    <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white transition-all" :class="s.default_allow_draws ? 'left-[18px]' : 'left-0.5'"></span>
                </span>
            </button>
        </div>


        {{-- ============ CUÁNDO Y QUIÉN ============ --}}

        <div class="space-y-3 rounded-2xl border border-slate-800 bg-slate-950/50 p-3">
            <p class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-orange-300">
                <x-omni-icon name="calendario" size="h-3.5 w-3.5" /> Cuándo se juega
            </p>

            <div class="grid grid-cols-2 gap-1.5">
                @foreach (['EVERY_SEASON' => ['Cada temporada', 'Una edición por temporada'], 'MANUAL' => ['Cuando se convoque', 'Sin calendario fijo']] as $valor => [$texto, $ayuda])
                    <button type="button" @click="s.default_recurrence = '{{ $valor }}'"
                        class="rounded-xl border px-2.5 py-2 text-left transition"
                        :class="s.default_recurrence === '{{ $valor }}' ? 'border-orange-400 bg-orange-500/15' : 'border-slate-800 hover:border-slate-600'">
                        <span class="block text-[12px] font-black text-slate-100"
                            x-text="'{{ $valor }}' === 'EVERY_SEASON' ? 'Cada ' + (s.label_season || 'temporada').toLowerCase() : '{{ $texto }}'"></span>
                        <span class="block text-[9px] leading-3 text-slate-500">{{ $ayuda }}</span>
                    </button>
                @endforeach
            </div>

            <p class="flex items-center gap-1.5 pt-2 text-[11px] font-black uppercase tracking-wider text-rose-300">
                <x-omni-icon name="usuario" size="h-3.5 w-3.5" /> Su sala de participantes
            </p>

            <div>
                <p class="text-[10px] text-slate-500">Con qué cara sale cada uno</p>
                <div class="mt-1 grid grid-cols-2 gap-1.5">
                    @foreach (['AUTO' => ['La que toca', 'La versión que pide la regla'], 'BASE' => ['La de siempre', 'Su versión base']] as $valor => [$texto, $ayuda])
                        <button type="button" @click="s.default_face_mode = '{{ $valor }}'"
                            class="rounded-xl border px-2.5 py-2 text-left transition"
                            :class="s.default_face_mode === '{{ $valor }}' ? 'border-rose-400 bg-rose-500/15' : 'border-slate-800 hover:border-slate-600'">
                            <span class="block text-[12px] font-black text-slate-100">{{ $texto }}</span>
                            <span class="block text-[9px] leading-3 text-slate-500">{{ $ayuda }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-[10px] text-slate-500">Cómo se reparte entre las puertas</p>
                <div class="mt-1 grid grid-cols-3 gap-1.5">
                    @foreach (['BALANCED' => 'Equilibrado', 'IN_ORDER' => 'En orden', 'RANDOM' => 'Al azar'] as $valor => $texto)
                        <button type="button" @click="s.default_door_strategy = '{{ $valor }}'"
                            class="rounded-xl border px-2 py-2 text-center text-[11px] font-black transition"
                            :class="s.default_door_strategy === '{{ $valor }}' ? 'border-rose-400 bg-rose-500/15 text-rose-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'">{{ $texto }}</button>
                    @endforeach
                </div>
            </div>

            <p class="rounded-xl border border-slate-800 px-3 py-2 text-[10px] leading-4 text-slate-500">
                Un torneo nuevo nacerá
                <span class="font-black text-slate-300"
                    x-text="(s.default_series_format === 'BEST_OF' ? 'al mejor de ' + s.default_best_of : s.default_fixed_games + ' juegos fijos')
                        + (s.default_allow_draws ? ', con empates' : ', sin empates')
                        + ', ' + (s.default_recurrence === 'EVERY_SEASON' ? 'cada ' + (s.label_season || 'temporada').toLowerCase() : 'cuando se convoque')"></span>.
                Todo se puede cambiar en su diseñador.
            </p>
        </div>
    </div>
</section>

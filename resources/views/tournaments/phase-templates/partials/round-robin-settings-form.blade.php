<form method="POST" action="{{ route('tournaments.round-robin.update', $phaseTemplate) }}" class="space-y-6">

    @csrf
    @method('PUT')

    {{-- ========================================================= --}}
    {{-- CALENDARIO --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-600">
            01 · Calendario
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Estructura del Round Robin
        </h3>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Cada ciclo hace que todos los participantes se enfrenten entre sí una vez.
        </p>

        <div class="mt-6 grid gap-5 md:grid-cols-2">

            <div>
                <label class="text-xs font-black uppercase text-slate-500">
                    Ciclos
                </label>

                <select name="cycles"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                    @for ($cycle = 1; $cycle <= 10; $cycle++)
                        <option value="{{ $cycle }}" @selected((int) old('cycles', $settings->cycles) === $cycle)>

                            {{ $cycle }}

                            @if ($cycle === 1)
                                · Single Round Robin
                            @elseif ($cycle === 2)
                                · Double Round Robin
                            @elseif ($cycle === 3)
                                · Triple Round Robin
                            @endif

                        </option>
                    @endfor

                </select>

                <p class="mt-2 text-xs leading-5 text-slate-400">
                    Dos ciclos hacen que cada pareja se enfrente dos veces.
                </p>
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">
                    Orden inicial
                </label>

                <select name="initial_order_mode"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                    @foreach ([
        'INPUT_ORDER' => 'Orden de entrada',
        'RANDOM' => 'Aleatorio',
        'RANKING' => 'Ranking',
        'MANUAL' => 'Manual',
    ] as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('initial_order_mode', $settings->initial_order_mode) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

                <p class="mt-2 text-xs leading-5 text-slate-400">
                    Todos se enfrentarán igualmente; este orden influye principalmente en la generación del calendario.
                </p>
            </div>

        </div>

        <input type="hidden" name="schedule_mode" value="BALANCED">

        <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">

            <p class="text-xs font-black text-cyan-900">
                Calendario equilibrado
            </p>

            <p class="mt-2 text-xs leading-5 text-cyan-700">
                La rotación Circle Method genera jornadas sin repetir enfrentamientos dentro de
                un mismo ciclo.
            </p>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- RESULTADOS --}}
    {{-- ========================================================= --}}

    <section x-data="{ allowDraws: @js((bool) old('allow_draws', $settings->allow_draws)) }" class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-600">
            02 · Resultados
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Empates y series
        </h3>

        <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">

            <input type="checkbox" name="allow_draws" value="1" x-model="allowDraws" @checked(old('allow_draws', $settings->allow_draws))
                class="mt-0.5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">

            <span>
                <span class="block text-sm font-black text-slate-800">
                    Permitir empates
                </span>

                <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Si está desactivado, el futuro Result Provider deberá producir necesariamente un ganador.
                </span>
            </span>

        </label>

        <div class="mt-5"
            x-data="{ formato: '{{ old('series_format', $settings->series_format ?: 'BEST_OF') }}' }">

            <label class="text-xs font-black uppercase text-slate-500">
                Cuantos enfrentamientos por batalla
            </label>

            <div class="mt-2 grid grid-cols-2 gap-2">

                <button type="button" @click="formato = 'BEST_OF'"
                    :class="formato === 'BEST_OF'
                        ? 'border-cyan-400 bg-cyan-50 text-cyan-800'
                        : 'border-slate-200 bg-white text-slate-500'"
                    class="rounded-xl border-2 p-3 text-left transition">
                    <span class="block text-xs font-black">Al mejor de (BO)</span>
                    <span class="mt-0.5 block text-[10px] leading-tight opacity-70">
                        Termina en cuanto alguien saca ventaja suficiente
                    </span>
                </button>

                <button type="button" @click="formato = 'FIXED_GAMES'"
                    :class="formato === 'FIXED_GAMES'
                        ? 'border-sky-400 bg-sky-50 text-sky-800'
                        : 'border-slate-200 bg-white text-slate-500'"
                    class="rounded-xl border-2 p-3 text-left transition">
                    <span class="block text-xs font-black">Cantidad fija</span>
                    <span class="mt-0.5 block text-[10px] leading-tight opacity-70">
                        Se juegan todos y decide el acumulado
                    </span>
                </button>

            </div>

            <input type="hidden" name="series_format" :value="formato">


            {{-- BEST OF --}}

            <div x-show="formato === 'BEST_OF'" class="mt-3">

                <select name="default_best_of"
                    class="w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                    @foreach ([1, 3, 5, 7, 9] as $value)
                        <option value="{{ $value }}"
                            @selected((int) old('default_best_of', $settings->default_best_of) === $value)>
                            BO{{ $value }} · gana quien llegue a {{ intdiv($value, 2) + 1 }}
                        </option>
                    @endforeach

                </select>

                <p class="mt-2 text-[11px] leading-5 text-emerald-700">
                    Cada resultado enviado es un enfrentamiento. La serie se cierra en cuanto
                    alguien alcanza la mayoria necesaria, asi que puede acabar antes de jugarlos todos.
                </p>

            </div>


            {{-- CANTIDAD FIJA --}}

            <div x-show="formato === 'FIXED_GAMES'" x-cloak class="mt-3">

                <input type="number" name="fixed_games" min="1" max="20"
                    value="{{ old('fixed_games', $settings->fixed_games ?: 2) }}"
                    class="w-full rounded-xl border-slate-300 focus:border-sky-400 focus:ring-sky-400">

                <p class="mt-2 text-[11px] leading-5 text-sky-700">
                    Se juegan SIEMPRE todos y gana quien sume mas. No es lo mismo que un BO:
                    dos enfrentamientos fijos pueden acabar empatados, y entonces se juega un
                    desempate solo si la fase necesita un ganador.
                </p>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- SCORING --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-600">
            03 · Scoring
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Sistema de puntuación
        </h3>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            OmniMerge no obliga a utilizar 3-1-0. Puedes definir puntuaciones positivas, negativas o decimales.
        </p>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">

                <label class="text-[10px] font-black uppercase tracking-wider text-emerald-700">
                    Victoria
                </label>

                <input type="number" name="win_points" step="0.01"
                    value="{{ old('win_points', $settings->win_points) }}"
                    class="mt-3 w-full rounded-xl border-emerald-200 bg-white focus:border-emerald-400 focus:ring-emerald-400">
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">

                <label class="text-[10px] font-black uppercase tracking-wider text-amber-700">
                    Empate
                </label>

                <input type="number" name="draw_points" step="0.01"
                    value="{{ old('draw_points', $settings->draw_points) }}"
                    class="mt-3 w-full rounded-xl border-amber-200 bg-white focus:border-amber-400 focus:ring-amber-400">
            </div>

            <div class="rounded-2xl border border-red-200 bg-red-50 p-4">

                <label class="text-[10px] font-black uppercase tracking-wider text-red-700">
                    Derrota
                </label>

                <input type="number" name="loss_points" step="0.01"
                    value="{{ old('loss_points', $settings->loss_points) }}"
                    class="mt-3 w-full rounded-xl border-red-200 bg-white focus:border-red-400 focus:ring-red-400">
            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- EMPATE EN CORTE --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
            04 · Clasificación
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Empate en una frontera de salida
        </h3>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Define qué deberá ocurrir cuando un empate afecta directamente una salida como TOP_N.
        </p>

        <label class="mt-5 block text-xs font-black uppercase text-slate-500">
            Política para resolver el corte
        </label>
        <p class="mt-1 text-xs leading-5 text-slate-400">
            Solo entra en juego cuando TOP_N o una salida equivalente deja un empate exactamente en la frontera.
        </p>

        <select name="cutoff_tie_policy"
            class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

            @foreach ($cutoffPolicies as $value => $definition)
                <option value="{{ $value }}" @selected(old('cutoff_tie_policy', $settings->cutoff_tie_policy) === $value)>
                    {{ $definition['label'] }}
                </option>
            @endforeach

        </select>

        @foreach ($cutoffPolicies as $value => $definition)
            @if ($settings->cutoff_tie_policy === $value)
                <p class="mt-3 text-xs leading-5 text-violet-700">
                    {{ $definition['description'] }}
                </p>
            @endif
        @endforeach

    </section>


    <button type="submit"
        class="w-full rounded-xl bg-cyan-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-cyan-600/20 transition hover:bg-cyan-700">
        Guardar configuración Round Robin
    </button>

</form>

<form method="POST" action="{{ route('tournaments.swiss.update', $phaseTemplate) }}" x-data="{
    completion: @js(old('completion_mode', $settings->completion_mode)),
    byePolicy: @js(old('bye_policy', $settings->bye_policy)),
    acceleration: @js(old('acceleration_mode', $settings->acceleration_mode))
}"
    class="space-y-6">

    @csrf
    @method('PUT')


    {{-- ========================================================= --}}
    {{-- FORMAT --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
            01 · Format
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            ¿Cuándo termina el Swiss?
        </h3>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Puedes utilizar un Swiss tradicional de rondas fijas o uno
            progresivo donde una cantidad de victorias clasifica y una cantidad
            de derrotas elimina.
        </p>


        <div class="mt-6">

            <label class="text-xs font-black uppercase text-slate-500">
                Modo de finalización
            </label>

            <select name="completion_mode" x-model="completion"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

                @foreach ($completionModes as $value => $definition)
                    <option value="{{ $value }}">
                        {{ $definition['label'] }}
                    </option>
                @endforeach

            </select>

        </div>


        {{-- FIXED ROUNDS --}}

        <div x-show="completion === 'FIXED_ROUNDS'" x-transition class="mt-5">

            <label class="text-xs font-black uppercase text-slate-500">
                Número de rondas
            </label>

            <input type="number" name="fixed_rounds" min="1" max="100"
                value="{{ old('fixed_rounds', $settings->fixed_rounds) }}" :disabled="completion !== 'FIXED_ROUNDS'"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

        </div>


        {{-- RECORD THRESHOLDS --}}

        <div x-show="completion === 'RECORD_THRESHOLDS'" x-transition class="mt-5 grid gap-4 sm:grid-cols-3">

            <div>

                <label class="text-xs font-black uppercase text-emerald-600">
                    Victorias para clasificar
                </label>

                <input type="number" name="qualification_wins" min="1" max="100"
                    value="{{ old('qualification_wins', $settings->qualification_wins) }}"
                    :disabled="completion !== 'RECORD_THRESHOLDS'"
                    class="mt-2 w-full rounded-xl border-emerald-200 focus:border-emerald-400 focus:ring-emerald-400">

            </div>

            <div>

                <label class="text-xs font-black uppercase text-red-600">
                    Derrotas para eliminar
                </label>

                <input type="number" name="elimination_losses" min="1" max="100"
                    value="{{ old('elimination_losses', $settings->elimination_losses) }}"
                    :disabled="completion !== 'RECORD_THRESHOLDS'"
                    class="mt-2 w-full rounded-xl border-red-200 focus:border-red-400 focus:ring-red-400">

            </div>

            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Máximo de rondas
                </label>

                <input type="number" name="max_rounds" min="1" max="100"
                    value="{{ old('max_rounds', $settings->max_rounds) }}"
                    :disabled="completion !== 'RECORD_THRESHOLDS'"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- PAIRING --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-600">
            02 · Pairing Engine
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            ¿Cómo se eligen los rivales?
        </h3>


        <div class="mt-6 grid gap-5 sm:grid-cols-2">

            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Algoritmo
                </label>

                <select name="pairing_algorithm"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                    @foreach ($pairingAlgorithms as $value => $definition)
                        <option value="{{ $value }}" @selected(old('pairing_algorithm', $settings->pairing_algorithm) === $value)>

                            {{ $definition['label'] }}

                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Base de emparejamiento
                </label>

                <select name="pairing_basis"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                    @foreach ($pairingBases as $value => $definition)
                        <option value="{{ $value }}" @selected(old('pairing_basis', $settings->pairing_basis) === $value)>

                            {{ $definition['label'] }}

                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Primera ronda
                </label>

                <select name="first_round_mode"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                    @foreach ($firstRoundModes as $value => $definition)
                        <option value="{{ $value }}" @selected(old('first_round_mode', $settings->first_round_mode) === $value)>

                            {{ $definition['label'] }}

                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Rematches
                </label>

                <select name="rematch_policy"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

                    @foreach ($rematchPolicies as $value => $label)
                        <option value="{{ $value }}" @selected(old('rematch_policy', $settings->rematch_policy) === $value)>

                            {{ $label }}

                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Floater
                </label>

                <select name="floater_policy" class="mt-2 w-full rounded-xl border-slate-300">

                    @foreach ($floaterPolicies as $value => $label)
                        <option value="{{ $value }}" @selected(old('floater_policy', $settings->floater_policy) === $value)>

                            {{ $label }}

                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Side / orientación
                </label>

                <select name="side_balance_policy" class="mt-2 w-full rounded-xl border-slate-300">

                    @foreach ($sidePolicies as $value => $label)
                        <option value="{{ $value }}" @selected(old('side_balance_policy', $settings->side_balance_policy) === $value)>

                            {{ $label }}

                        </option>
                    @endforeach

                </select>

            </div>

        </div>


        <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">

            <p class="text-xs font-black text-cyan-900">
                Rondas dinámicas
            </p>

            <p class="mt-1 text-xs leading-5 text-cyan-700">
                La primera ronda puede previsualizarse por completo.
                Desde la segunda ronda, los enfrentamientos dependen de
                los resultados reales de las rondas anteriores.
            </p>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- SCORING --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-600">
            03 · Scoring & Match
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Resultado y puntuación
        </h3>


        <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">

            <input type="checkbox" name="allow_draws" value="1" @checked(old('allow_draws', $settings->allow_draws))
                class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">

            <span>

                <span class="block text-sm font-black text-slate-900">
                    Permitir empates
                </span>

                <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Útil para ajedrez y cualquier competición que permita
                    un resultado sin ganador.
                </span>

            </span>

        </label>


        <div class="mt-5 grid gap-3 sm:grid-cols-3">

            <div class="rounded-2xl bg-emerald-50 p-4">

                <label class="text-[10px] font-black uppercase text-emerald-700">
                    Victoria
                </label>

                <input type="number" name="win_points" step="0.01"
                    value="{{ old('win_points', $settings->win_points) }}"
                    class="mt-2 w-full rounded-xl border-emerald-200 bg-white">

            </div>


            <div class="rounded-2xl bg-amber-50 p-4">

                <label class="text-[10px] font-black uppercase text-amber-700">
                    Empate
                </label>

                <input type="number" name="draw_points" step="0.01"
                    value="{{ old('draw_points', $settings->draw_points) }}"
                    class="mt-2 w-full rounded-xl border-amber-200 bg-white">

            </div>


            <div class="rounded-2xl bg-red-50 p-4">

                <label class="text-[10px] font-black uppercase text-red-700">
                    Derrota
                </label>

                <input type="number" name="loss_points" step="0.01"
                    value="{{ old('loss_points', $settings->loss_points) }}"
                    class="mt-2 w-full rounded-xl border-red-200 bg-white">

            </div>

        </div>


        @include('tournaments.phase-templates.partials.battle-format-moved')

    </section>


    {{-- ========================================================= --}}
    {{-- BYE --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
            04 · BYE
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Participante sin rival
        </h3>


        <div class="mt-5">

            <label class="text-xs font-black uppercase text-slate-500">
                Política de BYE
            </label>

            <select name="bye_policy" x-model="byePolicy" class="mt-2 w-full rounded-xl border-slate-300">

                @foreach ($byePolicies as $value => $label)
                    <option value="{{ $value }}">
                        {{ $label }}
                    </option>
                @endforeach

            </select>

        </div>


        <div x-show="byePolicy !== 'DISABLED'" x-transition class="mt-5 grid gap-4 sm:grid-cols-2">

            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Puntos del BYE
                </label>

                <input type="number" name="bye_points" step="0.01"
                    value="{{ old('bye_points', $settings->bye_points) }}" :disabled="byePolicy === 'DISABLED'"
                    class="mt-2 w-full rounded-xl border-slate-300">

            </div>

            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Máximo por participante
                </label>

                <input type="number" name="max_byes_per_participant" min="0" max="20"
                    value="{{ old('max_byes_per_participant', $settings->max_byes_per_participant) }}"
                    :disabled="byePolicy === 'DISABLED'" class="mt-2 w-full rounded-xl border-slate-300">

            </div>

        </div>


        {{-- Siempre enviamos valores válidos si BYE está desactivado. --}}

        <input type="hidden" name="bye_points" value="0" :disabled="byePolicy !== 'DISABLED'">

        <input type="hidden" name="max_byes_per_participant" value="0" :disabled="byePolicy !== 'DISABLED'">

    </section>


    {{-- ========================================================= --}}
    {{-- ACCELERATION --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-fuchsia-600">
            05 · Advanced Pairing
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Pairing Score y aceleración
        </h3>


        <div class="mt-5">

            <label class="text-xs font-black uppercase text-slate-500">
                Score inicial
            </label>

            <select name="initial_pairing_score_mode" class="mt-2 w-full rounded-xl border-slate-300">

                <option value="ZERO" @selected(old('initial_pairing_score_mode', $settings->initial_pairing_score_mode) === 'ZERO')>
                    Todos comienzan en 0
                </option>

                <option value="EXTERNAL_SCORE" @selected(old('initial_pairing_score_mode', $settings->initial_pairing_score_mode) === 'EXTERNAL_SCORE')>
                    Score suministrado por la ejecución
                </option>

            </select>

        </div>


        <div class="mt-5">

            <label class="text-xs font-black uppercase text-slate-500">
                Aceleración
            </label>

            <select name="acceleration_mode" x-model="acceleration" class="mt-2 w-full rounded-xl border-slate-300">

                @foreach ($accelerationModes as $value => $definition)
                    <option value="{{ $value }}">
                        {{ $definition['label'] }}
                    </option>
                @endforeach

            </select>

        </div>


        <div x-show="acceleration === 'GENERIC_VIRTUAL_POINTS'" x-transition class="mt-5 grid gap-4 sm:grid-cols-3">

            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Rondas
                </label>

                <input type="number" name="acceleration_rounds" min="1" max="100"
                    value="{{ old('acceleration_rounds', $settings->acceleration_rounds) }}"
                    :disabled="acceleration !== 'GENERIC_VIRTUAL_POINTS'"
                    class="mt-2 w-full rounded-xl border-slate-300">

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Seeds beneficiados
                </label>

                <input type="number" name="acceleration_seed_count" min="1" max="512"
                    value="{{ old('acceleration_seed_count', $settings->acceleration_seed_count) }}"
                    :disabled="acceleration !== 'GENERIC_VIRTUAL_POINTS'"
                    class="mt-2 w-full rounded-xl border-slate-300">

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Puntos virtuales
                </label>

                <input type="number" name="acceleration_virtual_points" step="0.01"
                    value="{{ old('acceleration_virtual_points', $settings->acceleration_virtual_points) }}"
                    :disabled="acceleration !== 'GENERIC_VIRTUAL_POINTS'"
                    class="mt-2 w-full rounded-xl border-slate-300">

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- RESOLUTION --}}
    {{-- ========================================================= --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-rose-600">
            06 · Resolution
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Empates y participantes sin resolver
        </h3>


        <div class="mt-5 grid gap-5 sm:grid-cols-2">

            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Empate en cutoff
                </label>

                <select name="cutoff_tie_policy" class="mt-2 w-full rounded-xl border-slate-300">

                    @foreach ($cutoffPolicies as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('cutoff_tie_policy', $settings->cutoff_tie_policy) === $value)>

                            {{ $label }}

                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label class="text-xs font-black uppercase text-slate-500">
                    Fallback
                </label>

                <select name="fallback_policy" class="mt-2 w-full rounded-xl border-slate-300">

                    @foreach ($fallbackPolicies as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('fallback_policy', $settings->fallback_policy) === $value)>

                            {{ $label }}

                        </option>
                    @endforeach

                </select>

            </div>

        </div>

    </section>


    <button type="submit"
        class="w-full rounded-xl bg-violet-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700">

        Guardar configuración Swiss

    </button>

</form>

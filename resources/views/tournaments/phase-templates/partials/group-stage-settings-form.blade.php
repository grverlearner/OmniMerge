<form method="POST" action="{{ route('tournaments.group-stage.update', $phaseTemplate) }}" x-data="{
    groupMode: @js(old('group_count_mode', $settings->group_count_mode)),
    distribution: @js(old('distribution_mode', $settings->distribution_mode)),
    remainder: @js(old('remainder_policy', $settings->remainder_policy))
}"
    class="space-y-6">

    @csrf
    @method('PUT')

    {{-- ESTRUCTURA --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-600">01 · Structure</p>
        <h3 class="mt-2 text-xl font-black text-slate-900">¿Cómo se construyen los grupos?</h3>
        <p class="mt-2 text-sm leading-6 text-slate-500">Puedes fijar una cantidad, indicar un tamaño objetivo o diseñar
            grupos completamente personalizados.</p>

        <div class="mt-6">
            <label class="text-xs font-black uppercase text-slate-500">Modo</label>

            <select name="group_count_mode" x-model="groupMode"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-indigo-400 focus:ring-indigo-400">
                @foreach ($groupCountModes as $value => $definition)
                    <option value="{{ $value }}">{{ $definition['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div x-show="groupMode === 'FIXED_GROUP_COUNT'" x-transition class="mt-5">
            <label class="text-xs font-black uppercase text-slate-500">Cantidad de grupos</label>

            <input type="number" name="group_count" min="2" max="256"
                value="{{ old('group_count', $settings->group_count) }}"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-indigo-400 focus:ring-indigo-400">
        </div>

        <div x-show="groupMode === 'TARGET_GROUP_SIZE'" x-transition class="mt-5">
            <label class="text-xs font-black uppercase text-slate-500">Tamaño objetivo</label>

            <input type="number" name="target_group_size" min="2" max="256"
                value="{{ old('target_group_size', $settings->target_group_size) }}"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-indigo-400 focus:ring-indigo-400">
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs font-black uppercase text-slate-500">Mínimo por grupo</label>

                <input type="number" name="min_group_size" min="2" max="256"
                    value="{{ old('min_group_size', $settings->min_group_size) }}"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-indigo-400 focus:ring-indigo-400">
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Máximo por grupo</label>

                <input type="number" name="max_group_size" min="2" max="512"
                    value="{{ old('max_group_size', $settings->max_group_size) }}"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-indigo-400 focus:ring-indigo-400">
            </div>
        </div>

        <div x-show="groupMode !== 'CUSTOM_GROUPS'" x-transition class="mt-5">

            <label class="text-xs font-black uppercase text-slate-500">
                Participantes sobrantes
            </label>

            <select name="remainder_policy" x-model="remainder" :disabled="groupMode === 'CUSTOM_GROUPS'"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-indigo-400 focus:ring-indigo-400">

                @foreach ($remainderPolicies as $value => $definition)
                    <option value="{{ $value }}">
                        {{ $definition['label'] }}
                    </option>
                @endforeach

            </select>

            <p class="mt-2 text-xs leading-5 text-slate-400">
                Define cómo se reparten los participantes cuando la cantidad total
                no puede dividirse exactamente entre todos los grupos.
            </p>

        </div>


        <div x-show="groupMode === 'CUSTOM_GROUPS'" x-transition class="mt-5">

            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">

                <p class="text-xs font-black text-indigo-900">
                    Capacidades administradas por grupo
                </p>

                <p class="mt-1 text-xs leading-5 text-indigo-700">
                    En el modo Grupos personalizados, cada grupo define su propia capacidad.
                    Por eso la política de sobrantes cambia automáticamente a Manual.
                </p>

            </div>

        </div>


        <input type="hidden" name="remainder_policy" value="MANUAL" :disabled="groupMode !== 'CUSTOM_GROUPS'">

    </section>


    {{-- DISTRIBUCIÓN --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">02 · Distribution</p>
        <h3 class="mt-2 text-xl font-black text-slate-900">¿Cómo llegan los participantes a cada grupo?</h3>

        <div class="mt-6">
            <select name="distribution_mode" x-model="distribution"
                class="w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">
                @foreach ($distributionModes as $value => $definition)
                    <option value="{{ $value }}">{{ $definition['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div x-show="distribution === 'POT_DRAW'" x-transition class="mt-5">
            <label class="text-xs font-black uppercase text-slate-500">Cantidad de Pots</label>

            <input type="number" name="pot_count" min="1" max="256"
                value="{{ old('pot_count', $settings->pot_count) }}" placeholder="Automático"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

            <p class="mt-2 text-xs leading-5 text-slate-400">Vacío = OmniMerge utilizará como referencia el grupo de
                mayor tamaño.</p>
        </div>

        <div class="mt-5 rounded-2xl border border-violet-100 bg-violet-50 p-4">
            <p class="text-xs font-black text-violet-900">Snake Seeded</p>
            <p class="mt-1 text-xs leading-5 text-violet-700">Ejemplo con 4 grupos: 1→A, 2→B, 3→C, 4→D, después 5→D,
                6→C, 7→B, 8→A.</p>
        </div>

    </section>


    {{-- MOTOR INTERNO --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-600">03 · Calendario interno</p>
        <h3 class="mt-2 text-xl font-black text-slate-900">Round Robin dentro de cada grupo</h3>
        <p class="mt-2 text-sm leading-6 text-slate-500">Cada grupo utiliza su propio calendario Round Robin y su
            propia clasificación.</p>

        <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
            <p class="text-xs font-black text-cyan-900">↻ Todos contra todos</p>
            <p class="mt-1 text-xs text-cyan-700">Configuración aplicada independientemente a cada grupo.</p>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs font-black uppercase text-slate-500">Ciclos</label>

                <select name="internal_cycles"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">
                    @for ($cycle = 1; $cycle <= 10; $cycle++)
                        <option value="{{ $cycle }}" @selected((int) old('internal_cycles', $settings->internal_cycles) === $cycle)>
                            {{ $cycle }}
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">Best Of</label>

                <select name="internal_best_of"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">
                    @foreach ([1, 3, 5, 7, 9] as $bestOf)
                        <option value="{{ $bestOf }}" @selected((int) old('internal_best_of', $settings->internal_best_of) === $bestOf)>
                            BO{{ $bestOf }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
            <input type="checkbox" name="internal_allow_draws" value="1" @checked(old('internal_allow_draws', $settings->internal_allow_draws))
                class="mt-0.5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">

            <span>
                <span class="block text-sm font-black text-slate-900">Permitir empates</span>
                <span class="mt-1 block text-xs leading-5 text-slate-500">Si se desactiva, el Runtime deberá producir
                    un ganador para cada serie.</span>
            </span>
        </label>

        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-emerald-50 p-4">
                <label class="text-[10px] font-black uppercase text-emerald-700">Victoria</label>

                <input type="number" name="internal_win_points" step="0.01"
                    value="{{ old('internal_win_points', $settings->internal_win_points) }}"
                    class="mt-2 w-full rounded-xl border-emerald-200 bg-white">
            </div>

            <div class="rounded-2xl bg-amber-50 p-4">
                <label class="text-[10px] font-black uppercase text-amber-700">Empate</label>

                <input type="number" name="internal_draw_points" step="0.01"
                    value="{{ old('internal_draw_points', $settings->internal_draw_points) }}"
                    class="mt-2 w-full rounded-xl border-amber-200 bg-white">
            </div>

            <div class="rounded-2xl bg-red-50 p-4">
                <label class="text-[10px] font-black uppercase text-red-700">Derrota</label>

                <input type="number" name="internal_loss_points" step="0.01"
                    value="{{ old('internal_loss_points', $settings->internal_loss_points) }}"
                    class="mt-2 w-full rounded-xl border-red-200 bg-white">
            </div>
        </div>

    </section>


    {{-- COMPARACIÓN ENTRE GRUPOS --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-fuchsia-600">04 · Cross Group Ranking</p>
        <h3 class="mt-2 text-xl font-black text-slate-900">Comparación entre grupos</h3>

        <div class="mt-5">
            <label class="text-xs font-black uppercase text-slate-500">Normalización por defecto</label>

            <select name="cross_group_normalization"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-fuchsia-400 focus:ring-fuchsia-400">
                <option value="RAW" @selected(old('cross_group_normalization', $settings->cross_group_normalization) === 'RAW')>
                    Valores totales
                </option>

                <option value="PER_MATCH" @selected(old('cross_group_normalization', $settings->cross_group_normalization) === 'PER_MATCH')>
                    Por partido
                </option>
            </select>

            <p class="mt-2 text-xs leading-5 text-slate-400">PER_MATCH resulta especialmente útil cuando los grupos no
                tienen el mismo tamaño.</p>
        </div>

        <div class="mt-5">
            <label class="text-xs font-black uppercase text-slate-500">Empate en el último cupo</label>

            <select name="cutoff_tie_policy"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-fuchsia-400 focus:ring-fuchsia-400">
                @foreach ($cutoffPolicies as $value => $label)
                    <option value="{{ $value }}" @selected(old('cutoff_tie_policy', $settings->cutoff_tie_policy) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

    </section>

    <button type="submit"
        class="w-full rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
        Guardar configuración Group Stage
    </button>

</form>

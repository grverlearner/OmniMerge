<form method="POST" action="{{ route('tournaments.single-elimination.update', $phaseTemplate) }}" class="space-y-6">

    @csrf
    @method('PUT')

    {{-- FINALIZACIÓN --}}

    <section x-data="{
        completionMode: @js(old('completion_mode', $settings->completion_mode))
    }" class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
            01 · Finalización
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            ¿Cuándo termina esta Fase?
        </h3>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            Eliminación directa puede producir un campeón o detenerse
            cuando quede una determinada cantidad de clasificados.
        </p>

        <div class="mt-6">

            <label class="text-xs font-black uppercase text-slate-500">
                Modo
            </label>

            <select name="completion_mode" x-model="completionMode"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                <option value="WINNER">
                    Hasta obtener un ganador
                </option>

                <option value="SURVIVORS">
                    Hasta que queden N supervivientes
                </option>

            </select>

        </div>

        <div x-show="completionMode === 'SURVIVORS'" x-transition class="mt-5">

            <label class="text-xs font-black uppercase text-slate-500">
                Supervivientes objetivo
            </label>

            <select name="target_survivors"
                class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                @foreach ([1, 2, 4, 8, 16, 32, 64, 128, 256] as $value)
                    <option value="{{ $value }}" @selected((int) old('target_survivors', $settings->target_survivors) === $value)>
                        {{ $value }}
                    </option>
                @endforeach

            </select>

            <x-input-error :messages="$errors->get('target_survivors')" class="mt-2" />

        </div>

    </section>

    {{-- SEEDING --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
            02 · Distribución
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Seeding y Pairing
        </h3>

        <div class="mt-6 grid gap-5 md:grid-cols-2">

            <div>
                <label class="text-xs font-black uppercase text-slate-500">
                    Seeding
                </label>

                <select name="seeding_mode"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    @foreach ([
        'INPUT_ORDER' => 'Orden de entrada',
        'RANDOM' => 'Aleatorio',
        'RANKING' => 'Ranking',
        'MANUAL' => 'Manual',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('seeding_mode', $settings->seeding_mode) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

                <p class="mt-2 text-xs leading-5 text-slate-400">
                    Determina cómo se obtiene el orden inicial
                    de los participantes.
                </p>
            </div>

            <div>
                <label class="text-xs font-black uppercase text-slate-500">
                    Pairing
                </label>

                <select name="pairing_mode"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    @foreach ([
        'STANDARD_SEEDED' => 'Seeded estándar',
        'SEQUENTIAL' => 'Secuencial',
        'RANDOM' => 'Aleatorio',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('pairing_mode', $settings->pairing_mode) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

                <p class="mt-2 text-xs leading-5 text-slate-400">
                    Determina cómo se enfrentan los seeds
                    dentro del bracket.
                </p>
            </div>

        </div>

    </section>

    {{-- BYE --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
            03 · BYE
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Avances automáticos
        </h3>

        @if ($phaseTemplate->allow_byes)

            <div class="mt-6">

                <label class="text-xs font-black uppercase text-slate-500">
                    Asignar BYEs a
                </label>

                <select name="bye_assignment"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                    @foreach ([
        'TOP_SEEDS' => 'Mejores seeds',
        'RANDOM' => 'Aleatoriamente',
        'MANUAL' => 'Manual',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('bye_assignment', $settings->bye_assignment) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

            </div>
        @else
            <input type="hidden" name="bye_assignment" value="{{ $settings->bye_assignment }}">

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">

                <p class="text-sm font-black text-slate-700">
                    BYEs desactivados
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    El contrato general de esta Fase no permite BYEs.
                    Los previews deberán utilizar cantidades compatibles
                    con un bracket completo.
                </p>

            </div>

        @endif

    </section>

    {{-- BEST OF --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">
            04 · Series
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-900">
            Best Of por defecto
        </h3>

        <div class="mt-6">

            <select name="default_best_of"
                class="w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">

                @foreach ([1, 3, 5, 7, 9] as $value)
                    <option value="{{ $value }}" @selected((int) old('default_best_of', $settings->default_best_of) === $value)>

                        Best of {{ $value }}
                        ·
                        {{ intdiv($value, 2) + 1 }}
                        {{ intdiv($value, 2) + 1 === 1 ? 'victoria' : 'victorias' }}

                    </option>
                @endforeach

            </select>

        </div>

        <div class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">

            <p class="text-xs font-black text-indigo-900">
                ¿Qué significa Best of?
            </p>

            <p class="mt-2 text-xs leading-5 text-indigo-700">
                BO1 requiere 1 victoria. BO3 requiere 2.
                BO5 requiere 3. BO7 requiere 4.
                La serie termina cuando uno de los competidores
                alcanza la mayoría necesaria.
            </p>

        </div>

    </section>

    {{-- RESEED --}}

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <label class="flex cursor-pointer items-start gap-3">

            <input type="checkbox" name="reseed_each_round" value="1" @checked(old('reseed_each_round', $settings->reseed_each_round))
                class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-500">

            <span>
                <span class="block text-sm font-black text-slate-900">
                    Reseed después de cada ronda
                </span>

                <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Los supervivientes podrán volver a ordenarse
                    antes de construir la siguiente ronda.
                    La ejecución real llegará con Competition Lab.
                </span>
            </span>

        </label>

    </section>

    <button type="submit"
        class="w-full rounded-xl bg-amber-500 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600">
        Guardar configuración
    </button>

</form>

<form x-ref="settingsForm" method="POST" action="{{ route('tournaments.single-elimination.update', $phaseTemplate) }}"
    class="space-y-4" @input="markSettingsDirty()" @change="markSettingsDirty()" @submit="submitting = true">
    @csrf
    @method('PUT')
    @include('tournaments.phase-templates.partials.single-elimination-advanced-settings-form')

    {{-- FINALIZACIÓN --}}

    <section id="single-elimination-completion"
        class="scroll-mt-32 overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm">
        <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left"
            @click="toggleSection('completion')">
            <span class="flex min-w-0 items-center gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-sm font-black text-amber-700">
                    01
                </span>

                <span class="min-w-0">
                    <span class="block font-black text-slate-900">
                        Finalización
                    </span>

                    <span class="mt-1 block truncate text-xs font-bold text-amber-700"
                        x-text="completionLabel()"></span>
                </span>
            </span>

            <span class="text-lg font-black text-slate-400 transition" :class="sections.completion ? 'rotate-180' : ''">
                ⌄
            </span>
        </button>

        <div x-show="sections.completion" x-transition class="border-t border-amber-100 bg-amber-50/30 p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Modo
                    </label>

                    <select name="completion_mode" x-model="draft.completionMode"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-amber-400 focus:ring-amber-400">
                        <option value="WINNER">
                            Hasta obtener un ganador
                        </option>

                        <option value="SURVIVORS">
                            Hasta que queden N supervivientes
                        </option>
                    </select>
                </div>

                <div x-show="draft.completionMode === 'SURVIVORS'" x-transition>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Supervivientes objetivo
                    </label>

                    <input type="number" name="target_survivors" min="1" max="256" step="1"
                        x-model.number="draft.targetSurvivors"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-amber-400 focus:ring-amber-400">

                    <p class="mt-2 text-[11px] leading-5 text-slate-500"
                        x-text="draft.configurationMode === 'BASIC'
                        ? 'En modo básico usa 1, 2, 4, 8, 16...'
                        : 'En modo avanzado puede ser cualquier entero alcanzable por las reglas K → Q.'">
                    </p>
                    <x-input-error :messages="$errors->get('target_survivors')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-amber-100 bg-white px-4 py-3">
                <p class="text-xs leading-5 text-slate-500">
                    La fase debe eliminar al menos un participante.
                    El objetivo siempre debe ser menor que la entrada.
                </p>
            </div>
        </div>
    </section>

    {{-- DISTRIBUCIÓN --}}

    <section id="single-elimination-distribution"
        class="scroll-mt-32 overflow-hidden rounded-3xl border border-indigo-200 bg-white shadow-sm">
        <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left"
            @click="toggleSection('distribution')">
            <span class="flex min-w-0 items-center gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-sm font-black text-indigo-700">
                    02
                </span>

                <span class="min-w-0">
                    <span class="block font-black text-slate-900">
                        Distribución
                    </span>

                    <span class="mt-1 block truncate text-xs font-bold text-indigo-700">
                        <span x-text="seedingLabel()"></span>
                        ·
                        <span x-text="pairingLabel()"></span>
                    </span>
                </span>
            </span>

            <span class="text-lg font-black text-slate-400 transition"
                :class="sections.distribution ? 'rotate-180' : ''">
                ⌄
            </span>
        </button>

        <div x-show="sections.distribution" x-transition class="border-t border-indigo-100 bg-indigo-50/30 p-5">
            <div x-cloak x-show="draft.configurationMode === 'ADVANCED'"
                class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 font-black text-amber-700">!</span>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-black text-amber-900">
                                Sin efecto en modo Avanzado
                            </p>

                            <span
                                class="rounded-full bg-slate-900 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-white">
                                Próximamente
                            </span>
                        </div>

                        <p class="mt-1 text-[10px] leading-5 text-amber-800">
                            El Structure Graph todavía no aplica Seeding ni Pairing al generar o ejecutar la
                            estructura interna: el orden de entrada de los participantes se respeta tal cual llega.
                            Estos valores solo tienen efecto real en modo Básico.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Seeding
                    </label>

                    <p class="mt-1 text-[11px] leading-4 text-slate-400">
                        Define de dónde se obtiene el orden inicial.
                    </p>

                    <select name="seeding_mode" x-model="draft.seedingMode"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-400 focus:ring-indigo-400">
                        @foreach ([
        'INPUT_ORDER' => 'Orden de entrada',
        'RANDOM' => 'Aleatorio',
        'RANKING' => 'Ranking',
        'MANUAL' => 'Manual',
    ] as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('seeding_mode', $settings->seeding_mode) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Pairing
                    </label>

                    <p class="mt-1 text-[11px] leading-4 text-slate-400">
                        Define cómo se enfrentan los puestos del bracket.
                    </p>

                    <select name="pairing_mode" x-model="draft.pairingMode"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-400 focus:ring-indigo-400">
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
                </div>
            </div>
        </div>
    </section>

    {{-- BYES --}}

    <section id="single-elimination-byes"
        class="scroll-mt-32 overflow-hidden rounded-3xl border border-cyan-200 bg-white shadow-sm">
        <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left"
            @click="toggleSection('byes')">
            <span class="flex min-w-0 items-center gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-sm font-black text-cyan-700">
                    03
                </span>

                <span class="min-w-0">
                    <span class="block font-black text-slate-900">
                        BYEs
                    </span>

                    <span class="mt-1 block truncate text-xs font-bold text-cyan-700">
                        @if ($phaseTemplate->allow_byes)
                            Permitidos ·
                            <span x-text="byeLabel()"></span>
                        @else
                            Desactivados en el contrato
                        @endif
                    </span>
                </span>
            </span>

            <span class="text-lg font-black text-slate-400 transition" :class="sections.byes ? 'rotate-180' : ''">
                ⌄
            </span>
        </button>

        <div x-show="sections.byes" x-transition class="border-t border-cyan-100 bg-cyan-50/30 p-5">
            <div x-cloak x-show="draft.configurationMode === 'ADVANCED'"
                class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 font-black text-amber-700">!</span>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-black text-amber-900">
                                Sin efecto en modo Avanzado
                            </p>

                            <span
                                class="rounded-full bg-slate-900 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-white">
                                Próximamente
                            </span>
                        </div>

                        <p class="mt-1 text-[10px] leading-5 text-amber-800">
                            El Structure Graph asigna los BYEs a quienes queden sin encuentro disponible según el
                            orden de entrada, sin aplicar esta política. Solo tiene efecto real en modo Básico.
                        </p>
                    </div>
                </div>
            </div>

            @if ($phaseTemplate->allow_byes)
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Asignar BYEs a
                </label>

                <select name="bye_assignment" x-model="draft.byeAssignment"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-cyan-400 focus:ring-cyan-400">
                    @foreach ([
        'TOP_SEEDS' => 'Mejores seeds',
        'RANDOM' => 'Aleatoriamente',
        'MANUAL' => 'Manual',
    ] as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('bye_assignment', $settings->bye_assignment) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="bye_assignment" value="{{ $settings->bye_assignment }}">

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-black text-slate-700">
                        BYEs desactivados
                    </p>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Solo serán válidas cantidades que completen
                        un bracket sin posiciones libres.
                    </p>

                    <a href="{{ route('tournaments.phase-templates.edit', $phaseTemplate) }}"
                        class="mt-3 inline-flex text-xs font-black text-cyan-700 hover:text-cyan-800">
                        Editar contrato de la fase →
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- SERIES --}}

    <section id="single-elimination-series"
        class="scroll-mt-32 overflow-hidden rounded-3xl border border-violet-200 bg-white shadow-sm">
        <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left"
            @click="toggleSection('series')">
            <span class="flex min-w-0 items-center gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-sm font-black text-violet-700">
                    04
                </span>

                <span class="min-w-0">
                    <span class="block font-black text-slate-900">
                        Series
                    </span>

                    <span class="mt-1 block truncate text-xs font-bold text-violet-700" x-text="seriesLabel()"></span>
                </span>
            </span>

            <span class="text-lg font-black text-slate-400 transition" :class="sections.series ? 'rotate-180' : ''">
                ⌄
            </span>
        </button>

        <div x-show="sections.series" x-transition class="border-t border-violet-100 bg-violet-50/30 p-5">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Formato predeterminado
            </p>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="cursor-pointer rounded-2xl border p-4 transition"
                    :class="draft.seriesFormat === 'BEST_OF' ?
                        'border-violet-400 bg-violet-100' :
                        'border-slate-200 bg-white'">
                    <span class="flex items-start gap-3">
                        <input type="radio" name="series_format" value="BEST_OF" x-model="draft.seriesFormat"
                            class="mt-0.5 border-slate-300 text-violet-600 focus:ring-violet-500">

                        <span>
                            <span class="block text-sm font-black text-slate-900">
                                Best of
                            </span>

                            <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                                Termina cuando alguien alcanza la mayoría necesaria.
                            </span>
                        </span>
                    </span>
                </label>

                <label class="cursor-pointer rounded-2xl border p-4 transition"
                    :class="draft.seriesFormat === 'FIXED_GAMES' ?
                        'border-cyan-400 bg-cyan-50' :
                        'border-slate-200 bg-white'">
                    <span class="flex items-start gap-3">
                        <input type="radio" name="series_format" value="FIXED_GAMES" x-model="draft.seriesFormat"
                            class="mt-0.5 border-slate-300 text-cyan-600 focus:ring-cyan-500">

                        <span>
                            <span class="block text-sm font-black text-slate-900">
                                Cantidad fija
                            </span>

                            <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                                Se disputan obligatoriamente todos los enfrentamientos.
                            </span>
                        </span>
                    </span>
                </label>
            </div>

            <div x-show="draft.seriesFormat === 'BEST_OF'" x-transition class="mt-4">
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Best of
                </label>

                <select name="default_best_of" x-model.number="draft.defaultBestOf"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-violet-400 focus:ring-violet-400">
                    @foreach ([1, 3, 5, 7, 9] as $value)
                        <option value="{{ $value }}"
                            @selected((int) old('default_best_of', $settings->default_best_of) === $value)>
                            BO{{ $value }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-2 text-[11px] leading-5 text-emerald-700">
                    BO3+ registra juegos individuales y cierra la serie cuando alguien alcanza la mayoría necesaria.
                </p>

                <x-input-error :messages="$errors->get('default_best_of')" class="mt-2" />
            </div>

            <div x-show="draft.seriesFormat === 'FIXED_GAMES'" x-transition class="mt-4">
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Enfrentamientos obligatorios
                </label>

                <input type="number" name="fixed_games" min="1" max="99"
                    x-model.number="draft.fixedGames"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-cyan-400 focus:ring-cyan-400">

                <p class="mt-2 text-[11px] leading-5 text-slate-500">
                    La serie no termina antes. El resultado se determina
                    después de disputar la cantidad completa.
                </p>

                <x-input-error :messages="$errors->get('fixed_games')" class="mt-2" />
            </div>

            <x-input-error :messages="$errors->get('series_format')" class="mt-3" />
        </div>
    </section>

    {{-- RESEED --}}

    <section id="single-elimination-reseed"
        class="scroll-mt-32 overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-sm">
        <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left"
            @click="toggleSection('reseed')">
            <span class="flex min-w-0 items-center gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-black text-emerald-700">
                    05
                </span>

                <span class="min-w-0">
                    <span class="block font-black text-slate-900">
                        Reordenamiento entre rondas
                    </span>

                    <span class="mt-1 block truncate text-xs font-bold"
                        :class="draft.reseedEachRound ?
                            'text-emerald-700' :
                            'text-slate-400'"
                        x-text="draft.reseedEachRound
                        ? 'Reseed activado'
                        : 'Reseed desactivado'"></span>
                </span>
            </span>

            <span class="text-lg font-black text-slate-400 transition" :class="sections.reseed ? 'rotate-180' : ''">
                ⌄
            </span>
        </button>

        <div x-show="sections.reseed" x-transition class="border-t border-emerald-100 bg-emerald-50/30 p-5">
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-emerald-100 bg-white p-4">
                <input type="checkbox" name="reseed_each_round" value="1" x-model="draft.reseedEachRound"
                    @checked(old('reseed_each_round', $settings->reseed_each_round))
                    class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">

                <span class="min-w-0">
                    <span class="block text-sm font-black text-slate-900">
                        Reordenar supervivientes después de cada ronda
                    </span>

                    <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                        Los participantes que avancen volverán a ordenarse
                        antes de crear los siguientes enfrentamientos.
                    </span>
                </span>
            </label>
        </div>
    </section>

    <noscript>
        <button type="submit" class="w-full rounded-xl bg-amber-500 px-6 py-3.5 text-sm font-black text-white">
            Guardar configuración
        </button>
    </noscript>
</form>

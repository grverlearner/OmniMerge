<form x-ref="settingsForm" method="POST" action="{{ route('tournaments.single-elimination.update', $phaseTemplate) }}"
    class="space-y-4" @input="markSettingsDirty()" @change="markSettingsDirty()" @submit="submitting = true">
    @csrf
    @method('PUT')

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

                    <select name="target_survivors" x-model.number="draft.targetSurvivors"
                        class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-amber-400 focus:ring-amber-400">
                        @foreach ([1, 2, 4, 8, 16, 32, 64, 128, 256] as $value)
                            <option value="{{ $value }}" @selected((int) old('target_survivors', $settings->target_survivors) === $value)>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>

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
                            <option value="{{ $value }}" @selected(old('seeding_mode', $settings->seeding_mode) === $value)>
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
                        <option value="{{ $value }}" @selected(old('bye_assignment', $settings->bye_assignment) === $value)>
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

                    <span class="mt-1 block truncate text-xs font-bold text-violet-700">
                        Best of
                        <span x-text="draft.defaultBestOf"></span>
                    </span>
                </span>
            </span>

            <span class="text-lg font-black text-slate-400 transition" :class="sections.series ? 'rotate-180' : ''">
                ⌄
            </span>
        </button>

        <div x-show="sections.series" x-transition class="border-t border-violet-100 bg-violet-50/30 p-5">
            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                Best of predeterminado
            </label>

            <select name="default_best_of" x-model.number="draft.defaultBestOf"
                class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-violet-400 focus:ring-violet-400">
                @foreach ([1, 3, 5, 7, 9] as $value)
                    <option value="{{ $value }}" @selected((int) old('default_best_of', $settings->default_best_of) === $value)>
                        BO{{ $value }}
                        ·
                        {{ intdiv($value, 2) + 1 }}
                        {{ intdiv($value, 2) + 1 === 1 ? 'victoria' : 'victorias' }}
                    </option>
                @endforeach
            </select>

            <div class="mt-3 grid grid-cols-5 gap-2">
                @foreach ([[1, 1], [3, 2], [5, 3], [7, 4], [9, 5]] as [$bestOf, $wins])
                    <div class="rounded-xl border px-2 py-2 text-center transition"
                        :class="draft.defaultBestOf === {{ $bestOf }} ?
                            'border-violet-300 bg-violet-100' :
                            'border-slate-200 bg-white'">
                        <p class="text-xs font-black text-violet-700">
                            BO{{ $bestOf }}
                        </p>

                        <p class="mt-0.5 text-[9px] font-bold text-slate-400">
                            {{ $wins }}V
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- RESEED --}}

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

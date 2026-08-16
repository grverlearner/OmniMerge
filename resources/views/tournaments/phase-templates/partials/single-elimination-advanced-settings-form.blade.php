{{-- MODO Y FUNDAMENTOS AVANZADOS --}}

<section id="single-elimination-mode"
    class="scroll-mt-32 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left"
        @click="toggleSection('mode')">
        <span class="flex min-w-0 items-center gap-3">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">
                00
            </span>

            <span class="min-w-0">
                <span class="block font-black text-slate-900">
                    Nivel de configuración
                </span>

                <span class="mt-1 block truncate text-xs font-bold text-slate-500" x-text="configurationModeLabel()">
                </span>
            </span>
        </span>

        <span class="text-lg font-black text-slate-400 transition" :class="sections.mode ? 'rotate-180' : ''">
            ⌄
        </span>
    </button>

    <div x-show="sections.mode" x-transition class="border-t border-slate-100 bg-slate-50/60 p-5">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="cursor-pointer rounded-2xl border p-4 transition"
                :class="draft.configurationMode === 'BASIC' ?
                    'border-amber-400 bg-amber-50' :
                    'border-slate-200 bg-white'">
                <span class="flex items-start gap-3">
                    <input type="radio" name="configuration_mode" value="BASIC" x-model="draft.configurationMode"
                        class="mt-0.5 border-slate-300 text-amber-600 focus:ring-amber-500">

                    <span>
                        <span class="block text-sm font-black text-slate-900">
                            Básico
                        </span>

                        <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                            Conserva el bracket clásico: duelos 2 → 1 y objetivos potencia de 2.
                        </span>
                    </span>
                </span>
            </label>

            <label class="cursor-pointer rounded-2xl border p-4 transition"
                :class="draft.configurationMode === 'ADVANCED' ?
                    'border-fuchsia-400 bg-fuchsia-50' :
                    'border-slate-200 bg-white'">
                <span class="flex items-start gap-3">
                    <input type="radio" name="configuration_mode" value="ADVANCED" x-model="draft.configurationMode"
                        class="mt-0.5 border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">

                    <span>
                        <span class="block text-sm font-black text-slate-900">
                            Avanzado
                        </span>

                        <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                            Habilita formatos K → Q, perfiles múltiples y políticas de sobrantes.
                        </span>
                    </span>
                </span>
            </label>
        </div>

        <x-input-error :messages="$errors->get('configuration_mode')" class="mt-3" />
    </div>
</section>

<section id="single-elimination-advanced" x-cloak x-show="draft.configurationMode === 'ADVANCED'" x-transition
    class="scroll-mt-32 overflow-hidden rounded-3xl border border-fuchsia-200 bg-white shadow-sm">
    <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left"
        @click="toggleSection('advanced')">
        <span class="flex min-w-0 items-center gap-3">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-fuchsia-100 text-sm font-black text-fuchsia-700">
                A
            </span>

            <span class="min-w-0">
                <span class="block font-black text-slate-900">
                    Formato competitivo avanzado
                </span>

                <span class="mt-1 block truncate text-xs font-bold text-fuchsia-700">
                    <span x-text="formatLabel()"></span>
                    ·
                    <span x-text="encounterProfileLabel()"></span>
                </span>
            </span>
        </span>

        <span class="text-lg font-black text-slate-400 transition" :class="sections.advanced ? 'rotate-180' : ''">
            ⌄
        </span>
    </button>

    <div x-show="sections.advanced" x-transition class="border-t border-fuchsia-100 bg-fuchsia-50/30 p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Participantes por encuentro (K)
                </label>

                <input type="number" name="entrants_per_match" min="2" max="64"
                    x-model.number="draft.entrantsPerMatch"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">

                <x-input-error :messages="$errors->get('entrants_per_match')" class="mt-2" />
            </div>

            <div>
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Clasificados por encuentro (Q)
                </label>

                <input type="number" name="qualifiers_per_match" min="1" max="63"
                    x-model.number="draft.qualifiersPerMatch"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">

                <x-input-error :messages="$errors->get('qualifiers_per_match')" class="mt-2" />
            </div>

            <div>
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Perfil de encuentro
                </label>

                <select name="encounter_profile" x-model="draft.encounterProfile"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">
                    <option value="DUEL">
                        Duelo
                    </option>

                    <option value="MULTI_COMPETITOR">
                        Multicompetidor
                    </option>

                    <option value="CUSTOM">
                        Personalizado · requiere estructura
                    </option>
                </select>

                <x-input-error :messages="$errors->get('encounter_profile')" class="mt-2" />
            </div>

            <div>
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Política de sobrantes
                </label>

                <select name="remainder_policy" x-model="draft.remainderPolicy"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-fuchsia-400 focus:ring-fuchsia-400">
                    @if ($phaseTemplate->allow_byes)
                        <option value="BYE">
                            Avance libre (BYE)
                        </option>
                    @endif

                    <option value="PRELIMINARY">
                        Ronda preliminar
                    </option>

                    <option value="BALANCED">
                        Distribución balanceada
                    </option>

                    <option value="INCOMPLETE_MATCH">
                        Encuentro incompleto
                    </option>

                    <option value="MANUAL">
                        Resolución manual · requiere estructura
                    </option>

                    <option value="REJECT">
                        Rechazar cantidad incompatible
                    </option>
                </select>

                <x-input-error :messages="$errors->get('remainder_policy')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Forma de entrada
                </label>

                <select name="input_mode" x-model="draft.inputMode"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    <option value="POOL">
                        Bolsa común
                    </option>

                    <option value="PER_SEED">
                        Una entrada por seed
                    </option>

                    <option value="GROUPED">
                        Entradas agrupadas
                    </option>

                    <option value="HYBRID">
                        Híbrida
                    </option>

                    <option value="CUSTOM">
                        Personalizada · requiere configurar puertas
                    </option>
                </select>

                <x-input-error :messages="$errors->get('input_mode')" class="mt-2" />
            </div>

            <div>
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Enrutamiento interno
                </label>

                <select name="routing_mode" x-model="draft.routingMode"
                    class="mt-2 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-400 focus:ring-indigo-400">
                    <option value="AUTOMATIC">
                        Automático
                    </option>

                    <option value="POSITIONAL">
                        Por posición
                    </option>

                    <option value="MANUAL">
                        Manual · requiere constructor
                    </option>

                    <option value="CUSTOM">
                        Personalizado · requiere constructor
                    </option>
                </select>

                <x-input-error :messages="$errors->get('routing_mode')" class="mt-2" />
            </div>
        </div>

        <div x-cloak
            x-show="draft.encounterProfile === 'CUSTOM' || draft.remainderPolicy === 'MANUAL' || draft.inputMode === 'CUSTOM' || draft.routingMode === 'MANUAL' || draft.routingMode === 'CUSTOM'"
            class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 font-black text-amber-700">!</span>
                <div>
                    <p class="text-xs font-black text-amber-900">Requiere completar la estructura</p>
                    <p class="mt-1 text-[10px] leading-5 text-amber-800">
                        La opción se guardará, pero la fase no estará lista para ejecutarse hasta que construyas
                        sus etapas, encuentros, conexiones y puertas en la sección Estructura.
                    </p>
                    <a href="{{ route('tournaments.single-elimination.structure.show', [
                        'phaseTemplate' => $phaseTemplate,
                        'workspace' => 'CUSTOM',
                    ]) }}"
                        class="mt-3 inline-flex text-[10px] font-black text-amber-900 underline decoration-amber-400 underline-offset-4">
                        Abrir constructor personalizado →
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-fuchsia-100 bg-white p-4 text-xs leading-5 text-slate-500">
            La configuración automática puede generar la estructura desde estas reglas.
            Si seleccionas entrada o enrutamiento manual, deberás completar etapas,
            encuentros, conexiones y slots desde la sección Estructura antes de probarla.
        </div>
    </div>
</section>

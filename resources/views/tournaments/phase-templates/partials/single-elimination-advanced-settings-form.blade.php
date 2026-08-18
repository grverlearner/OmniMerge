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
                        @change="
                            if ($event.target.checked) {
                                const form = $event.target.form;
                                form.elements.namedItem('input_mode').value = 'POOL';
                                form.elements.namedItem('routing_mode').value = 'AUTOMATIC';
                                form.elements.namedItem('entrants_per_match').value = '2';
                                form.elements.namedItem('qualifiers_per_match').value = '1';
                                form.elements.namedItem('encounter_profile').value = 'DUEL';
                                form.elements.namedItem('remainder_policy').value = @js($phaseTemplate->allow_byes ? 'BYE' : 'REJECT');
                            }
                        "
                        class="mt-0.5 border-slate-300 text-amber-600 focus:ring-amber-500">

                    <span>
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="block text-sm font-black text-slate-900">
                                Básico
                            </span>

                            <span
                                class="rounded-full bg-emerald-100 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-emerald-700">
                                Stable V1 · Disponible
                            </span>
                        </span>

                        <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                            Contrato estable: POOL · Automático · Duelo 2 → 1 · sobrantes por BYE o Rechazar.
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
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="block text-sm font-black text-slate-900">
                                Avanzado
                            </span>

                            <span
                                class="rounded-full bg-fuchsia-100 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-fuchsia-700">
                                Structure Graph
                            </span>
                        </span>

                        <span class="mt-1 block text-[11px] leading-5 text-slate-500">
                            Habilita K → Q, multicompetidor y políticas avanzadas. Las capacidades manuales o personalizadas no ejecutables se marcan como Próximamente.
                        </span>
                    </span>
                </span>
            </label>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-3">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                <p class="text-[8px] font-black uppercase tracking-wider text-emerald-700">BASIC Stable V1</p>
                <p class="mt-1 text-[10px] font-bold text-emerald-900">Disponible y ejecutable</p>
            </div>

            <div class="rounded-xl border border-fuchsia-200 bg-fuchsia-50 px-3 py-2">
                <p class="text-[8px] font-black uppercase tracking-wider text-fuchsia-700">ADVANCED</p>
                <p class="mt-1 text-[10px] font-bold text-fuchsia-900">Disponible con Structure Graph</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">Manual / Custom</p>
                <p class="mt-1 text-[10px] font-bold text-slate-700">Próximamente cuando no exista runtime</p>
            </div>
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

                    <option value="CUSTOM"
                        @disabled(old('encounter_profile', $settings->encounter_profile) !== 'CUSTOM')
                        :disabled="draft.encounterProfile !== 'CUSTOM'">
                        Personalizado · Próximamente · no ejecutable
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

                    <option value="MANUAL"
                        @disabled(old('remainder_policy', $settings->remainder_policy) !== 'MANUAL')
                        :disabled="draft.remainderPolicy !== 'MANUAL'">
                        Resolución manual · Próximamente · no ejecutable
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

                    <option value="CUSTOM"
                        @disabled(old('input_mode', $settings->input_mode) !== 'CUSTOM')
                        :disabled="draft.inputMode !== 'CUSTOM'">
                        Personalizada · Próximamente · no ejecutable
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

                    <option value="MANUAL"
                        @disabled(old('routing_mode', $settings->routing_mode) !== 'MANUAL')
                        :disabled="draft.routingMode !== 'MANUAL'">
                        Manual · Próximamente · no ejecutable
                    </option>

                    <option value="CUSTOM"
                        @disabled(old('routing_mode', $settings->routing_mode) !== 'CUSTOM')
                        :disabled="draft.routingMode !== 'CUSTOM'">
                        Personalizado · Próximamente · no ejecutable
                    </option>
                </select>

                <x-input-error :messages="$errors->get('routing_mode')" class="mt-2" />
            </div>
        </div>

        <div x-cloak
            x-show="draft.encounterProfile === 'CUSTOM' || draft.remainderPolicy === 'MANUAL' || draft.inputMode === 'CUSTOM' || draft.routingMode === 'MANUAL' || draft.routingMode === 'CUSTOM'"
            class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 font-black text-amber-700">!</span>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-black text-amber-900">
                            Capacidad no ejecutable en Stable V1
                        </p>

                        <span
                            class="rounded-full bg-slate-900 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-white">
                            Próximamente
                        </span>
                    </div>

                    <p class="mt-1 text-[10px] leading-5 text-amber-800">
                        Este valor solo permanece disponible cuando ya existe en una configuración heredada,
                        para que puedas visualizarlo y corregirlo. Construir la estructura no basta para volverlo
                        ejecutable: selecciona una alternativa disponible antes de ejecutar la fase.
                    </p>
                </div>
            </div>
        </div>

        <div x-cloak
            x-show="draft.inputMode === 'GROUPED' || draft.inputMode === 'HYBRID'"
            class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
            <p class="text-xs font-black text-indigo-900">
                Requiere revisión en Structure Graph
            </p>

            <p class="mt-1 text-[10px] leading-5 text-indigo-700">
                Las entradas agrupadas o híbridas generan una puerta estructural provisional.
                Antes de ejecutar debes revisar cómo se alimentan sus slots dentro de Estructura.
            </p>
        </div>

        <div class="mt-4 rounded-2xl border border-fuchsia-100 bg-white p-4 text-xs leading-5 text-slate-500">
            El modo avanzado puede generar una base estructural desde estas reglas.
            Los avisos del Preview indican si la definición actual es completa, requiere revisión
            o depende de una capacidad que todavía no tiene runtime estable.
        </div>
    </div>
</section>

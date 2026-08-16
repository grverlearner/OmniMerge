<section x-cloak x-show="view === 'compact'" x-transition.opacity role="tabpanel" class="mt-6">
    <div class="rounded-[32px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                    Lectura rápida
                </p>

                <h2 class="mt-1 text-2xl font-black text-slate-900">
                    Resumen estructural
                </h2>
            </div>

            <p class="max-w-md text-xs leading-5 text-slate-400">
                Selecciona cualquier bloque para abrir el inspector y rastrear sus rutas.
            </p>
        </div>

        <div class="mt-6 space-y-3">
            {{-- Puertas de entrada --}}
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="gate in payload.input_gates" :key="gate.key">
                    <button type="button" @click="select(gate.key)" :data-structure-key="gate.key"
                        class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md"
                        :class="isDimmed(gate) ? 'opacity-30' : ''">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider text-fuchsia-500">
                                    Puerta de entrada
                                </p>

                                <p class="mt-1 font-black text-fuchsia-950" x-text="gate.name"></p>
                            </div>

                            <span
                                class="rounded-lg bg-white px-2.5 py-1 text-[9px] font-black text-fuchsia-700 shadow-sm"
                                x-text="gate.contract"></span>
                        </div>

                        <p class="mt-3 text-[11px] leading-5 text-fuchsia-700">
                            <span x-text="gate.type_label"></span>

                            ·

                            <span x-text="gate.routes.length"></span>
                            rutas
                        </p>
                    </button>
                </template>
            </div>

            <div class="flex justify-center text-lg font-black text-violet-400" aria-hidden="true">
                ↓
            </div>

            {{-- Rondas --}}
            <template x-for="round in visibleRounds()" :key="round.key">
                <div>
                    <button type="button" @click="select(round.key)" :data-structure-key="round.key"
                        class="w-full rounded-3xl border p-5 text-left transition hover:-translate-y-0.5 hover:shadow-lg"
                        :class="{
                            'border-red-300 bg-red-50': round.issue_level === 'ERROR',
                            'border-amber-300 bg-amber-50': round.issue_level === 'WARNING',
                            'border-cyan-200 bg-cyan-50': round.issue_level === 'RECOMMENDATION',
                            'border-violet-200 bg-gradient-to-r from-violet-50 to-indigo-50': round
                                .issue_level === 'NONE',
                            'opacity-30': isDimmed(round)
                        }">
                        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-lg bg-white px-2.5 py-1 font-mono text-[9px] font-bold text-slate-400 shadow-sm"
                                        x-text="round.code"></span>

                                    <span x-show="round.generation_source === 'MANUAL'"
                                        class="rounded-full bg-violet-100 px-2.5 py-1 text-[9px] font-black text-violet-700">
                                        Manual
                                    </span>

                                    <span x-show="round.issue_count > 0"
                                        class="rounded-full bg-white px-2.5 py-1 text-[9px] font-black text-red-600 shadow-sm">
                                        <span x-text="round.issue_count"></span>
                                        problemas
                                    </span>
                                </div>

                                <p class="mt-2 text-lg font-black text-slate-900" x-text="round.name"></p>

                                <p class="mt-1 text-xs text-slate-500">
                                    <span x-text="round.participants_expected"></span>
                                    entran

                                    ·

                                    <span x-text="round.qualifiers_expected"></span>
                                    avanzan

                                    ·

                                    <span x-text="round.encounter_count"></span>
                                    encuentros
                                </p>
                            </div>

                            <div class="grid grid-cols-3 gap-2 sm:min-w-[320px]">
                                <div class="rounded-xl bg-white p-3 text-center shadow-sm">
                                    <p class="text-[8px] font-black uppercase text-slate-400">
                                        Encuentros
                                    </p>

                                    <p class="mt-1 font-black text-slate-900" x-text="round.encounter_count"></p>
                                </div>

                                <div class="rounded-xl bg-white p-3 text-center shadow-sm">
                                    <p class="text-[8px] font-black uppercase text-cyan-500">
                                        BYEs
                                    </p>

                                    <p class="mt-1 font-black text-cyan-700" x-text="round.byes"></p>
                                </div>

                                <div class="rounded-xl bg-white p-3 text-center shadow-sm">
                                    <p class="text-[8px] font-black uppercase text-indigo-500">
                                        Rutas
                                    </p>

                                    <p class="mt-1 font-black text-indigo-700" x-text="round.route_keys.length"></p>
                                </div>
                            </div>
                        </div>
                    </button>

                    <div class="flex justify-center py-3 text-lg font-black text-violet-400" aria-hidden="true">
                        ↓
                    </div>
                </div>
            </template>

            {{-- Puertas de salida --}}
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="exit in payload.exits" :key="exit.key">
                    <button type="button" @click="select(exit.key)" :data-structure-key="exit.key"
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md"
                        :class="isDimmed(exit) ? 'opacity-30' : ''">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider text-emerald-500">
                                    Puerta de salida
                                </p>

                                <p class="mt-1 font-black text-emerald-950" x-text="exit.name"></p>
                            </div>

                            <span
                                class="rounded-lg bg-white px-2.5 py-1 text-[9px] font-black text-emerald-700 shadow-sm"
                                x-text="exit.contract"></span>
                        </div>

                        <p class="mt-3 text-[11px] leading-5 text-emerald-700">
                            <span x-text="exit.selector"></span>

                            ·

                            <span x-text="exit.routes.length"></span>
                            rutas
                        </p>
                    </button>
                </template>
            </div>
        </div>
    </div>
</section>

<section x-cloak x-show="view === 'blocks'" x-transition.opacity role="tabpanel" class="mt-6 space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                Explorador de encuentros
            </p>

            <h2 class="mt-1 text-2xl font-black text-slate-900">
                Estructura detallada
            </h2>
        </div>

        <div class="flex gap-2">
            <button type="button" @click="expandAllRounds()"
                class="min-h-10 rounded-xl border border-slate-200 bg-white px-4 py-2 text-[10px] font-black text-slate-600 hover:border-violet-300">
                Expandir rondas
            </button>

            <button type="button" @click="collapseAllRounds()"
                class="min-h-10 rounded-xl border border-slate-200 bg-white px-4 py-2 text-[10px] font-black text-slate-600 hover:border-violet-300">
                Plegar rondas
            </button>
        </div>
    </div>

    {{-- Puertas de entrada --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="gate in payload.input_gates" :key="gate.key">
            <article :data-structure-key="gate.key"
                class="rounded-3xl border border-fuchsia-200 bg-white p-5 shadow-sm transition"
                :class="{
                    'ring-2 ring-fuchsia-400 ring-offset-2': selectedKey === gate.key,
                    'opacity-30': isDimmed(gate)
                }">
                <button type="button" @click="select(gate.key)" class="w-full text-left">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-fuchsia-500">
                                Entrada
                            </p>

                            <p class="mt-1 font-black text-slate-900" x-text="gate.name"></p>

                            <p class="mt-1 font-mono text-[9px] font-bold text-slate-400" x-text="gate.code"></p>
                        </div>

                        <span class="rounded-full bg-fuchsia-100 px-2.5 py-1 text-[9px] font-black text-fuchsia-700"
                            x-text="gate.contract"></span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[8px] font-black uppercase text-slate-400">
                                Tipo
                            </p>

                            <p class="mt-1 text-xs font-black text-slate-800" x-text="gate.type_label"></p>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-[8px] font-black uppercase text-slate-400">
                                Distribución
                            </p>

                            <p class="mt-1 text-xs font-black text-slate-800" x-text="gate.distribution"></p>
                        </div>
                    </div>
                </button>

                <div class="mt-4 space-y-1.5 border-t border-fuchsia-100 pt-4">
                    <template x-for="route in gate.routes.slice(0, 5)" :key="route.key">
                        <button type="button" @click="select(route.key)"
                            class="block w-full truncate rounded-lg bg-indigo-50 px-3 py-2 text-left text-[9px] font-bold text-indigo-700 hover:bg-indigo-100">
                            →

                            <span x-text="route.target_label"></span>
                        </button>
                    </template>

                    <p x-show="gate.routes.length > 5" class="px-3 text-[9px] font-bold text-slate-400">
                        +

                        <span x-text="gate.routes.length - 5"></span>

                        rutas adicionales
                    </p>
                </div>
            </article>
        </template>
    </div>

    <div class="flex justify-center text-xl font-black text-violet-400" aria-hidden="true">
        ↓
    </div>

    {{-- Rondas --}}
    <template x-for="round in visibleRounds()" :key="round.key">
        <article :data-structure-key="round.key"
            class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm"
            :class="{
                'ring-2 ring-violet-400 ring-offset-2': selectedKey === round.key,
                'opacity-40': isDimmed(round)
            }">
            <div
                class="flex flex-col justify-between gap-4 bg-gradient-to-r from-slate-950 via-violet-950 to-indigo-950 p-5 text-white sm:flex-row sm:items-center">
                <button type="button" @click="select(round.key)" class="min-w-0 text-left">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-lg bg-white/10 px-2.5 py-1 font-mono text-[9px] font-bold text-violet-200"
                            x-text="round.code"></span>

                        <span class="rounded-full bg-violet-400/15 px-2.5 py-1 text-[9px] font-black text-violet-200"
                            x-text="round.type_label"></span>

                        <span x-show="round.issue_count > 0"
                            class="rounded-full bg-red-400/15 px-2.5 py-1 text-[9px] font-black text-red-200">
                            <span x-text="round.issue_count"></span>
                            problemas
                        </span>
                    </div>

                    <h3 class="mt-2 truncate text-xl font-black" x-text="round.name"></h3>

                    <p class="mt-1 text-[11px] text-slate-300">
                        <span x-text="round.participants_expected"></span>
                        participantes

                        ·

                        <span x-text="round.qualifiers_expected"></span>
                        clasifican

                        ·

                        <span x-text="round.encounter_count"></span>
                        encuentros
                    </p>
                </button>

                <div class="flex flex-wrap items-center gap-2">
                    <span x-show="round.byes > 0"
                        class="rounded-xl bg-cyan-400/15 px-3 py-2 text-[10px] font-black text-cyan-200">
                        <span x-text="round.byes"></span>
                        BYEs
                    </span>

                    <button type="button" @click="toggleRound(round.key)"
                        class="min-h-10 rounded-xl bg-white px-4 py-2 text-[10px] font-black text-slate-900">
                        <span
                            x-text="
                                isRoundExpanded(round.key)
                                    ? 'Plegar'
                                    : 'Ver encuentros'
                            "></span>
                    </button>
                </div>
            </div>

            {{-- Encuentros --}}
            <div x-show="isRoundExpanded(round.key)" class="grid gap-4 p-4 sm:p-5 lg:grid-cols-2 2xl:grid-cols-3">
                <template x-for="encounter in round.visible_encounters" :key="encounter.key">
                    <article :data-structure-key="encounter.key"
                        class="rounded-3xl border bg-white shadow-sm transition"
                        :class="{
                            'border-red-300 ring-2 ring-red-100': encounter.issue_level === 'ERROR',
                            'border-amber-300': encounter.issue_level === 'WARNING',
                            'border-cyan-200': encounter.issue_level === 'RECOMMENDATION',
                            'border-slate-200': encounter.issue_level === 'NONE',
                            'ring-2 ring-violet-500 ring-offset-2': selectedKey === encounter.key,
                            'opacity-25': isDimmed(encounter),
                            'p-3': density === 'dense',
                            'p-4': density === 'comfortable'
                        }">
                        <button type="button" @click="select(encounter.key)" class="w-full text-left">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-[9px] font-bold text-slate-400"
                                            x-text="encounter.code"></span>

                                        <span x-show="encounter.generation_source === 'MANUAL'"
                                            class="rounded-full bg-violet-100 px-2 py-1 text-[8px] font-black text-violet-700">
                                            Manual
                                        </span>

                                        <span x-show="encounter.locked"
                                            class="rounded-full bg-slate-100 px-2 py-1 text-[8px] font-black text-slate-600">
                                            Protegido
                                        </span>
                                    </div>

                                    <h4 class="mt-1 truncate font-black text-slate-900" x-text="encounter.name"></h4>
                                </div>

                                <span
                                    class="shrink-0 rounded-xl bg-violet-100 px-3 py-2 text-xs font-black text-violet-700"
                                    x-text="encounter.format"></span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2 text-[9px] font-black">
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-slate-600"
                                    x-text="encounter.profile"></span>

                                <span class="rounded-lg bg-amber-100 px-2.5 py-1.5 text-amber-700"
                                    x-text="encounter.series"></span>

                                <span x-show="encounter.issue_count > 0"
                                    class="rounded-lg bg-red-100 px-2.5 py-1.5 text-red-700">
                                    <span x-text="encounter.issue_count"></span>
                                    problemas
                                </span>
                            </div>
                        </button>

                        <div class="mt-4 grid gap-3" :class="density === 'dense' ? 'grid-cols-2' : ''">
                            {{-- Slots de entrada --}}
                            <div class="rounded-2xl border border-fuchsia-100 bg-fuchsia-50/60 p-3">
                                <p class="text-[8px] font-black uppercase tracking-wider text-fuchsia-600">
                                    Slots de entrada
                                </p>

                                <div class="mt-2 space-y-2">
                                    <template x-for="slot in encounter.slots" :key="slot.key">
                                        <button type="button" @click="select(slot.key)"
                                            :data-structure-key="slot.key"
                                            class="block w-full rounded-xl border bg-white p-2.5 text-left transition hover:border-fuchsia-300"
                                            :class="{
                                                'border-red-300': slot.issue_level === 'ERROR',
                                                'border-amber-300': slot.issue_level === 'WARNING',
                                                'border-slate-200': !['ERROR', 'WARNING'].includes(slot.issue_level),
                                                'ring-2 ring-fuchsia-400': selectedKey === slot.key
                                            }">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[10px] font-black text-slate-800"
                                                    x-text="slot.name"></span>

                                                <span class="text-[8px] font-black text-slate-400"
                                                    x-text="slot.type_label"></span>
                                            </div>

                                            <p class="mt-1 truncate text-[9px] font-bold text-fuchsia-700"
                                                x-text="
                                                    slot.routes.length
                                                        ? '← ' + slot.routes[0].source_label
                                                        : 'Sin fuente'
                                                ">
                                            </p>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Resultados y destinos --}}
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
                                <p class="text-[8px] font-black uppercase tracking-wider text-emerald-600">
                                    Resultados y destinos
                                </p>

                                <div class="mt-2 space-y-2">
                                    <template x-for="result in encounter.results" :key="result.key">
                                        <button type="button" @click="select(result.key)"
                                            :data-structure-key="result.key"
                                            class="block w-full rounded-xl border bg-white p-2.5 text-left transition hover:border-emerald-300"
                                            :class="{
                                                'border-red-300': result.issue_level === 'ERROR',
                                                'border-amber-300': result.issue_level === 'WARNING',
                                                'border-slate-200': !['ERROR', 'WARNING'].includes(result.issue_level),
                                                'ring-2 ring-emerald-400': selectedKey === result.key
                                            }">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[10px] font-black text-slate-800"
                                                    x-text="result.name"></span>

                                                <span class="text-[8px] font-black text-slate-400"
                                                    x-text="result.quantity_label"></span>
                                            </div>

                                            <p class="mt-1 truncate text-[9px] font-bold text-emerald-700"
                                                x-text="
                                                    result.routes.length
                                                        ? '→ ' + result.routes[0].target_label
                                                        : 'Sin destino'
                                                ">
                                            </p>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>

                <div x-show="round.visible_encounters.length === 0"
                    class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center text-xs font-bold text-slate-400">
                    Ningún encuentro de esta ronda coincide con los filtros.
                </div>
            </div>
        </article>
    </template>

    <div class="flex justify-center text-xl font-black text-violet-400" aria-hidden="true">
        ↓
    </div>

    {{-- Puertas de salida --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="exit in payload.exits" :key="exit.key">
            <article :data-structure-key="exit.key"
                class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm transition"
                :class="{
                    'ring-2 ring-emerald-400 ring-offset-2': selectedKey === exit.key,
                    'opacity-30': isDimmed(exit)
                }">
                <button type="button" @click="select(exit.key)" class="w-full text-left">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-emerald-500">
                                Salida
                            </p>

                            <p class="mt-1 font-black text-slate-900" x-text="exit.name"></p>

                            <p class="mt-1 font-mono text-[9px] font-bold text-slate-400" x-text="exit.code"></p>
                        </div>

                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[9px] font-black text-emerald-700"
                            x-text="exit.contract"></span>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-slate-500" x-text="exit.summary"></p>
                </button>

                <div class="mt-4 space-y-1.5 border-t border-emerald-100 pt-4">
                    <template x-for="route in exit.routes.slice(0, 5)" :key="route.key">
                        <button type="button" @click="select(route.key)"
                            class="block w-full truncate rounded-lg bg-indigo-50 px-3 py-2 text-left text-[9px] font-bold text-indigo-700 hover:bg-indigo-100">
                            ←

                            <span x-text="route.source_label"></span>
                        </button>
                    </template>
                </div>
            </article>
        </template>
    </div>

    {{-- Estado sin coincidencias --}}
    <div x-show="visibleRounds().length === 0"
        class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
        <p class="font-black text-slate-800">
            No existen coincidencias
        </p>

        <p class="mt-1 text-xs text-slate-400">
            Limpia los filtros para volver a mostrar la estructura.
        </p>

        <button type="button" @click="clearFilters()"
            class="mt-4 rounded-xl bg-violet-600 px-4 py-2 text-xs font-black text-white">
            Limpiar filtros
        </button>
    </div>
</section>

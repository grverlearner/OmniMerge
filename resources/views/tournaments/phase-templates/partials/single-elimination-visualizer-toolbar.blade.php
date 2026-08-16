<section
    class="sticky top-3 z-20 mt-6 rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-xl shadow-slate-900/5 backdrop-blur">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Vista de la estructura">
            <button type="button" role="tab" :aria-selected="view === 'compact'" @click="setView('compact')"
                class="min-h-10 rounded-xl px-4 py-2 text-xs font-black transition"
                :class="view === 'compact'
                    ?
                    'bg-violet-600 text-white shadow-lg shadow-violet-500/20' :
                    'border border-slate-200 bg-white text-slate-600 hover:border-violet-300'">
                Resumen
            </button>

            <button type="button" role="tab" :aria-selected="view === 'blocks'" @click="setView('blocks')"
                class="min-h-10 rounded-xl px-4 py-2 text-xs font-black transition"
                :class="view === 'blocks'
                    ?
                    'bg-violet-600 text-white shadow-lg shadow-violet-500/20' :
                    'border border-slate-200 bg-white text-slate-600 hover:border-violet-300'">
                Bloques
            </button>

            <button type="button" role="tab" :aria-selected="view === 'table'" @click="setView('table')"
                class="min-h-10 rounded-xl px-4 py-2 text-xs font-black transition"
                :class="view === 'table'
                    ?
                    'bg-violet-600 text-white shadow-lg shadow-violet-500/20' :
                    'border border-slate-200 bg-white text-slate-600 hover:border-violet-300'">
                Tabla
            </button>
        </div>

        <div class="flex flex-1 flex-col gap-2 lg:flex-row xl:max-w-4xl">
            <label class="relative flex-1">
                <span class="sr-only">
                    Buscar en la estructura
                </span>

                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    ⌕
                </span>

                <input type="search" x-model.debounce.250ms="query"
                    placeholder="Buscar encuentro, código, origen o destino…"
                    class="min-h-11 w-full rounded-xl border-slate-200 bg-slate-50 pl-9 text-xs font-bold text-slate-700 focus:border-violet-400 focus:bg-white focus:ring-violet-400">
            </label>

            <select x-model="roundFilter" aria-label="Filtrar por ronda"
                class="min-h-11 rounded-xl border-slate-200 bg-white text-xs font-black text-slate-600 focus:border-violet-400 focus:ring-violet-400">
                <option value="ALL">
                    Todas las rondas
                </option>

                <template x-for="round in payload.rounds" :key="round.key">
                    <option :value="round.key" x-text="round.name"></option>
                </template>
            </select>

            <select x-model="severityFilter" aria-label="Filtrar por diagnóstico"
                class="min-h-11 rounded-xl border-slate-200 bg-white text-xs font-black text-slate-600 focus:border-violet-400 focus:ring-violet-400">
                <option value="ALL">
                    Todo diagnóstico
                </option>

                <option value="ERROR">
                    Con errores
                </option>

                <option value="WARNING">
                    Con advertencias
                </option>

                <option value="RECOMMENDATION">
                    Con recomendaciones
                </option>

                <option value="NONE">
                    Sin problemas
                </option>
            </select>

            <select x-model="generationFilter" aria-label="Filtrar por origen de generación"
                class="min-h-11 rounded-xl border-slate-200 bg-white text-xs font-black text-slate-600 focus:border-violet-400 focus:ring-violet-400">
                <option value="ALL">
                    Auto y manual
                </option>

                <option value="GENERATED">
                    Automáticos
                </option>

                <option value="MANUAL">
                    Manuales
                </option>
            </select>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
        <div class="flex flex-wrap items-center gap-2 text-[10px] font-black">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-fuchsia-50 px-3 py-1.5 text-fuchsia-700">
                <span class="h-2 w-2 rounded-full bg-fuchsia-500"></span>
                Entrada
            </span>

            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-indigo-700">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                Ruta
            </span>

            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Salida
            </span>

            <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1.5 text-violet-700">
                ◇ Manual
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" x-show="! noActiveFilters()" @click="clearFilters()"
                class="min-h-9 rounded-xl border border-slate-200 px-3 py-2 text-[10px] font-black text-slate-500 hover:bg-slate-50">
                Limpiar filtros
            </button>

            <div class="flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                <button type="button" @click="setDensity('comfortable')"
                    class="min-h-8 rounded-lg px-3 text-[9px] font-black"
                    :class="density === 'comfortable'
                        ?
                        'bg-white text-violet-700 shadow-sm' :
                        'text-slate-400'">
                    Cómoda
                </button>

                <button type="button" @click="setDensity('dense')"
                    class="min-h-8 rounded-lg px-3 text-[9px] font-black"
                    :class="density === 'dense'
                        ?
                        'bg-white text-violet-700 shadow-sm' :
                        'text-slate-400'">
                    Densa
                </button>
            </div>

            <button type="button" @click="problemsOpen = true"
                class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-slate-950 px-4 py-2 text-[10px] font-black text-white">
                Diagnóstico

                <span class="rounded-full bg-white/15 px-2 py-0.5" x-text="payload.issues.length"></span>
            </button>
        </div>
    </div>
    <div x-cloak x-show="selected"
        class="mt-3 flex flex-col gap-3 rounded-2xl border border-violet-200 bg-violet-50 p-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-violet-600 px-2.5 py-1 text-[8px] font-black uppercase text-white">
                    Trazado activo
                </span>

                <span class="truncate text-xs font-black text-slate-900" x-text="selected?.name"></span>

                <span class="rounded-full bg-white px-2.5 py-1 text-[9px] font-black text-violet-700"
                    x-text="traceModeLabel()"></span>
            </div>

            <div class="mt-2 flex flex-wrap gap-3 text-[9px] font-black">
                <span class="text-sky-700">
                    ● Origen
                </span>

                <span class="text-emerald-700">
                    ● Destino
                </span>

                <span class="text-amber-700">
                    ● Cruce
                </span>

                <span class="text-slate-500">
                    <span x-text="traceNodeCount()"></span>
                    nodos ·
                    <span x-text="traceConnections().length"></span>
                    conexiones
                </span>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="button" @click="openInspector()"
                class="rounded-xl bg-violet-600 px-4 py-2.5 text-[10px] font-black text-white">
                Abrir inspector
            </button>

            <button type="button" @click="clearSelection()"
                class="rounded-xl border border-violet-200 bg-white px-4 py-2.5 text-[10px] font-black text-violet-700">
                Limpiar trazado
            </button>
        </div>
    </div>
</section>

<template x-teleport="body">
    <div>
        {{-- Fondo del inspector --}}
        <div x-cloak x-show="inspectorOpen" x-transition.opacity @click="closeInspector()"
            class="fixed inset-0 z-40 bg-slate-950/55 backdrop-blur-sm"></div>

        {{-- Inspector lateral derecho --}}
        <aside x-cloak x-show="inspectorOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full" @keydown.escape.window="closeInspector()" role="dialog"
            aria-modal="true" aria-labelledby="structure-inspector-title"
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col bg-white shadow-2xl">
            <template x-if="selected">
                <div class="flex min-h-0 flex-1 flex-col">
                    <header class="shrink-0 border-b border-slate-200 bg-slate-950 p-5 text-white sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full bg-violet-400/15 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-violet-200"
                                        x-text="selected.kind_label"></span>

                                    <span
                                        class="rounded-full bg-white/10 px-2.5 py-1 font-mono text-[9px] font-bold text-slate-300"
                                        x-text="selected.code"></span>

                                    <span x-show="selected.generation_source === 'MANUAL'"
                                        class="rounded-full bg-fuchsia-400/15 px-2.5 py-1 text-[9px] font-black text-fuchsia-200">
                                        Manual
                                    </span>

                                    <span x-show="selected.locked"
                                        class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[9px] font-black text-cyan-200">
                                        Protegido
                                    </span>
                                </div>

                                <h2 id="structure-inspector-title" class="mt-3 truncate text-2xl font-black"
                                    x-text="selected.name"></h2>

                                <p class="mt-2 text-xs leading-5 text-slate-300"
                                    x-text="selected.description || 'Sin descripción adicional.'"></p>
                            </div>

                            <button type="button" @click="closeInspector()" aria-label="Cerrar inspector"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lg font-black text-white hover:bg-white/20">
                                ×
                            </button>
                        </div>

                        {{-- Modos de seguimiento --}}
                        <div class="mt-5 grid grid-cols-4 gap-1 rounded-2xl bg-white/5 p-1">
                            @foreach ([['DIRECT', 'Directa'], ['UPSTREAM', 'Origen'], ['DOWNSTREAM', 'Destino'], ['FULL', 'Completa']] as [$mode, $label])
                                <button type="button" @click="setTraceMode('{{ $mode }}')"
                                    class="min-h-9 rounded-xl px-2 text-[9px] font-black transition"
                                    :class="traceMode === '{{ $mode }}'
                                        ?
                                        'bg-violet-400 text-slate-950' :
                                        'text-slate-300 hover:bg-white/10'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <div class="mt-3 flex items-start justify-between gap-3 rounded-2xl bg-white/5 p-3">
                            <div>
                                <p class="text-[9px] font-black uppercase text-violet-200" x-text="traceModeLabel()">
                                </p>

                                <p class="mt-1 text-[10px] leading-4 text-slate-300" x-text="traceModeDescription()">
                                </p>
                            </div>

                            <button type="button" @click="clearSelection()"
                                class="rounded-xl border border-white/10 px-3 py-2 text-[9px] font-black text-white">
                                Limpiar
                            </button>
                        </div>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">
                        {{-- Propiedades --}}
                        <section>
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
                                    Propiedades
                                </h3>

                                <span class="rounded-full px-2.5 py-1 text-[9px] font-black"
                                    :class="selected.status === 'ACTIVE' ?
                                        'bg-emerald-100 text-emerald-700' :
                                        'bg-slate-100 text-slate-600'"
                                    x-text="statusLabel(selected.status)"></span>
                            </div>

                            <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                                <template x-for="detail in selected.details || []" :key="detail.label">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                        <dt class="text-[8px] font-black uppercase tracking-wider text-slate-400"
                                            x-text="detail.label"></dt>

                                        <dd class="mt-1 break-words text-xs font-black text-slate-800"
                                            x-text="detail.value || 'No definido'"></dd>
                                    </div>
                                </template>
                            </dl>
                        </section>

                        {{-- Recorrido visible --}}
                        <section class="mt-6">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-500">
                                    Recorrido visible
                                </h3>

                                <span
                                    class="rounded-full bg-indigo-100 px-2.5 py-1 text-[9px] font-black text-indigo-700"
                                    x-text="(selected.route_keys || []).length"></span>
                            </div>

                            <div class="mt-3 space-y-2">
                                <template x-for="route in traceConnections()" :key="route.key">
                                    <button type="button" @click="select(route.key, false)"
                                        class="block w-full rounded-2xl border border-indigo-100 bg-indigo-50 p-3 text-left">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-mono text-[8px] font-bold text-indigo-400"
                                                x-text="route.code"></p>

                                            <span class="rounded-full px-2 py-1 text-[8px] font-black"
                                                :class="traceUpstreamKeys.includes(route.key) && traceDownstreamKeys.includes(
                                                        route.key) ?
                                                    'bg-amber-100 text-amber-700' :
                                                    traceUpstreamKeys.includes(route.key) ?
                                                    'bg-sky-100 text-sky-700' :
                                                    traceDownstreamKeys.includes(route.key) ?
                                                    'bg-emerald-100 text-emerald-700' :
                                                    'bg-violet-100 text-violet-700'"
                                                x-text="traceUpstreamKeys.includes(route.key) && traceDownstreamKeys.includes(route.key)
                        ? 'Cruce'
                        : traceUpstreamKeys.includes(route.key)
                            ? 'Origen'
                            : traceDownstreamKeys.includes(route.key)
                                ? 'Destino'
                                : 'Directa'"></span>
                                        </div>

                                        <p class="mt-1 truncate text-[10px] font-black text-slate-700">
                                            <span x-text="route.source_label"></span>
                                            <span class="px-1 text-indigo-500">→</span>
                                            <span x-text="route.target_label"></span>
                                        </p>
                                    </button>
                                </template>

                                <p x-show="traceConnections().length === 0"
                                    class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-[10px] font-bold text-slate-400">
                                    Este modo no encontró conexiones registradas.
                                    Puede existir solamente el paso interno slot → resultado.
                                </p>
                            </div>
                        </section>

                        {{-- Diagnóstico del elemento --}}
                        <section class="mt-6">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-red-500">
                                    Diagnóstico del elemento
                                </h3>

                                <span class="rounded-full px-2.5 py-1 text-[9px] font-black"
                                    :class="selected.issue_count ?
                                        'bg-red-100 text-red-700' :
                                        'bg-emerald-100 text-emerald-700'"
                                    x-text="selected.issue_count || 0"></span>
                            </div>

                            <div class="mt-3 space-y-2">
                                <template x-for="issue in selected.issues || []" :key="issue.code">
                                    <div class="rounded-2xl border p-3"
                                        :class="{
                                            'border-red-200 bg-red-50': issue.severity === 'ERROR',
                                            'border-amber-200 bg-amber-50': issue.severity === 'WARNING',
                                            'border-cyan-200 bg-cyan-50': issue.severity === 'RECOMMENDATION'
                                        }">
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-xs font-black shadow-sm"
                                                x-text="
                                                    issue.severity === 'ERROR'
                                                        ? '!'
                                                        : issue.severity === 'WARNING'
                                                            ? '△'
                                                            : 'i'
                                                "></span>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-[10px] font-black text-slate-900"
                                                        x-text="issue.title || severityLabel(issue.severity)"></p>

                                                    <p class="font-mono text-[8px] font-bold text-slate-400"
                                                        x-text="issue.code"></p>
                                                </div>

                                                <p class="mt-1 text-[11px] font-bold leading-5 text-slate-700"
                                                    x-text="issue.message"></p>

                                                <div class="mt-3 space-y-2">
                                                    <div x-show="issue.element"
                                                        class="rounded-xl bg-white/70 px-3 py-2">
                                                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-400">
                                                            Elemento
                                                        </p>
                                                        <p class="mt-1 text-[10px] font-bold text-slate-700"
                                                            x-text="issue.element"></p>
                                                    </div>

                                                    <div x-show="issue.impact"
                                                        class="rounded-xl bg-white/70 px-3 py-2">
                                                        <p class="text-[8px] font-black uppercase tracking-wider text-slate-400">
                                                            Impacto
                                                        </p>
                                                        <p class="mt-1 text-[10px] leading-4 text-slate-600"
                                                            x-text="issue.impact"></p>
                                                    </div>

                                                    <div x-show="issue.action"
                                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                                        <p class="text-[8px] font-black uppercase tracking-wider text-violet-500">
                                                            Qué hacer
                                                        </p>
                                                        <p class="mt-1 text-[10px] font-bold leading-4 text-slate-700"
                                                            x-text="issue.action"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="!selected.issue_count"
                                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-[11px] font-bold text-emerald-700">
                                    ✓ No existen problemas asociados directamente con este elemento.
                                </div>
                            </div>
                        </section>

                        {{-- Edición controlada --}}
                        <section x-show="selectedEditable()" class="mt-6 border-t border-slate-200 pt-6">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">
                                    Edición controlada
                                </p>

                                <p class="mt-1 text-[11px] leading-5 text-slate-500">
                                    Estos cambios marcan el elemento como manual, incrementan la versión y vuelven a
                                    validar la estructura.
                                </p>
                            </div>

                            <form method="POST" :action="elementUpdateUrl()" class="mt-4 space-y-4">
                                @csrf
                                @method('PUT')

                                <template x-if="selectedHasName()">
                                    <label class="block">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                            Nombre
                                        </span>

                                        <input type="text" name="name" x-model="selected.name" maxlength="120"
                                            required
                                            class="mt-2 w-full rounded-xl border-slate-300 text-sm font-bold focus:border-violet-400 focus:ring-violet-400">
                                    </label>
                                </template>

                                <template x-if="selectedHasLabel()">
                                    <label class="block">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                            Etiqueta
                                        </span>

                                        <input type="text" name="label" x-model="selected.name" maxlength="160"
                                            class="mt-2 w-full rounded-xl border-slate-300 text-sm font-bold focus:border-violet-400 focus:ring-violet-400">
                                    </label>
                                </template>

                                <template x-if="selectedHasDescription()">
                                    <label class="block">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                            Descripción
                                        </span>

                                        <textarea name="description" x-model="selected.description" rows="3" maxlength="2000"
                                            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400"></textarea>
                                    </label>
                                </template>

                                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-3 text-[10px] leading-5 text-sky-800">
                                    Los campos de posición, orden y capacidad describen este elemento dentro del grafo.
                                    Cuando aparezca un campo “ID”, debe apuntar a un elemento existente de la misma estructura.
                                </div>

                                <template x-if="selected.kind === 'ROUND'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="text-[9px] font-black uppercase text-slate-500">Número de etapa</span>
                                            <input type="number" name="stage_number" min="1" max="1000" x-model.number="selected.stage_number"
                                                class="mt-2 w-full rounded-xl border-slate-300 text-sm">
                                        </label>
                                        <label class="block">
                                            <span class="text-[9px] font-black uppercase text-slate-500">Rama</span>
                                            <select name="branch_code" x-model="selected.branch_code" class="mt-2 w-full rounded-xl border-slate-300 text-sm">
                                                <option value="MAIN">Principal</option><option value="SECONDARY">Secundaria</option>
                                                <option value="REPECHAGE">Repechaje</option><option value="CUSTOM">Personalizada</option>
                                            </select>
                                        </label>
                                        <label class="block">
                                            <span class="text-[9px] font-black uppercase text-slate-500">Tipo de ronda</span>
                                            <select name="round_type" x-model="selected.round_type" class="mt-2 w-full rounded-xl border-slate-300 text-sm">
                                                <option value="PRELIMINARY">Preliminar</option><option value="MAIN">Principal</option>
                                                <option value="REPECHAGE">Repechaje</option><option value="PLACEMENT">Posicionamiento</option>
                                                <option value="CUSTOM">Personalizada</option>
                                            </select>
                                        </label>
                                        <label class="block">
                                            <span class="text-[9px] font-black uppercase text-slate-500">Orden</span>
                                            <input type="number" name="sort_order" min="0" x-model.number="selected.sort_order"
                                                class="mt-2 w-full rounded-xl border-slate-300 text-sm">
                                        </label>
                                        <label class="block"><span class="text-[9px] font-black uppercase text-slate-500">Participantes esperados</span>
                                            <input type="number" name="participants_expected" min="1" max="512" x-model.number="selected.participants_expected" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label class="block"><span class="text-[9px] font-black uppercase text-slate-500">Clasificados esperados</span>
                                            <input type="number" name="qualifiers_expected" min="1" max="512" x-model.number="selected.qualifiers_expected" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                    </div>
                                </template>

                                <template x-if="selected.kind === 'ENCOUNTER'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Posición</span><input type="number" name="position" min="1" x-model.number="selected.position" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Orden</span><input type="number" name="sort_order" min="0" x-model.number="selected.sort_order" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Participantes K</span><input type="number" name="entrants_count" min="2" max="64" x-model.number="selected.entrants_count" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Clasifican Q</span><input type="number" name="qualifiers_count" min="1" max="63" x-model.number="selected.qualifiers_count" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Mínimo para iniciar</span><input type="number" name="min_entrants_to_start" min="1" max="64" x-model.number="selected.min_entrants_to_start" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Perfil</span><select name="encounter_profile" x-model="selected.encounter_profile" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="DUEL">Duelo</option><option value="MULTI_COMPETITOR">Multicompetidor</option><option value="CUSTOM">Personalizado</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Activación</span><select name="activation_policy" x-model="selected.activation_policy" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="ALL_SLOTS_FILLED">Todos los slots</option><option value="MINIMUM_REACHED">Mínimo alcanzado</option><option value="MANUAL">Manual</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Serie</span><select name="series_format" x-model="selected.series_format" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="NONE">Sin serie</option><option value="BEST_OF">Best of</option><option value="FIXED_GAMES">Juegos fijos</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Best of</span><select name="best_of" x-model.number="selected.best_of" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="1">BO1</option><option value="3">BO3</option><option value="5">BO5</option><option value="7">BO7</option><option value="9">BO9</option><option value="11">BO11</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Juegos fijos</span><input type="number" name="fixed_games" min="1" max="99" x-model.number="selected.fixed_games" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label class="sm:col-span-2 flex items-center gap-3 rounded-xl border border-slate-200 p-3"><input type="hidden" name="allows_incomplete" value="0"><input type="checkbox" name="allows_incomplete" value="1" x-model="selected.allows_incomplete" class="rounded border-slate-300 text-violet-600"><span class="text-xs font-black text-slate-700">Permitir encuentro incompleto</span></label>
                                    </div>
                                </template>

                                <template x-if="selected.kind === 'INPUT_GATE'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Tipo</span><select name="input_type" x-model="selected.input_type" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="POOL">Pool</option><option value="PER_SEED">Por seed</option><option value="GROUPED">Agrupada</option><option value="HYBRID">Híbrida</option><option value="CUSTOM">Personalizada</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Merge</span><select name="merge_policy" x-model="selected.merge_policy" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="APPEND">Append</option><option value="WAIT_ALL">Wait all</option><option value="FIRST_AVAILABLE">Primera disponible</option><option value="PRIORITY">Prioridad</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Distribución</span><select name="distribution_mode" x-model="selected.distribution_mode" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="INPUT_ORDER">Orden entrada</option><option value="RANKING">Ranking</option><option value="RANDOM">Aleatoria</option><option value="BALANCED">Balanceada</option><option value="EXTREMES">Extremos</option><option value="MANUAL">Manual</option><option value="CUSTOM">Personalizada</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Si está vacía</span><input name="empty_behavior" maxlength="80" x-model="selected.empty_behavior" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Mínimo</span><input type="number" name="min_participants" min="0" max="512" x-model.number="selected.min_participants" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Máximo</span><input type="number" name="max_participants" min="0" max="512" x-model.number="selected.max_participants" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Exactos</span><input type="number" name="exact_participants" min="0" max="512" x-model.number="selected.exact_participants" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Prioridad</span><input type="number" name="priority" min="0" x-model.number="selected.priority" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label class="flex items-center gap-3"><input type="hidden" name="is_required" value="0"><input type="checkbox" name="is_required" value="1" x-model="selected.required"><span class="text-xs font-bold">Obligatoria</span></label>
                                        <label class="flex items-center gap-3"><input type="hidden" name="accepts_batch" value="0"><input type="checkbox" name="accepts_batch" value="1" x-model="selected.accepts_batch"><span class="text-xs font-bold">Aceptar lote</span></label>
                                        <label class="flex items-center gap-3"><input type="hidden" name="accepts_multiple_connections" value="0"><input type="checkbox" name="accepts_multiple_connections" value="1" x-model="selected.accepts_multiple_connections"><span class="text-xs font-bold">Múltiples conexiones</span></label>
                                    </div>
                                </template>

                                <template x-if="selected.kind === 'SLOT'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Posición</span><input type="number" name="position" min="1" x-model.number="selected.position" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Capacidad</span><input type="number" name="capacity" min="1" max="64" x-model.number="selected.capacity" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Tipo</span><select name="slot_type" x-model="selected.slot_type" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="PARTICIPANT">Participante</option><option value="BYE">BYE</option><option value="OPTIONAL">Opcional</option><option value="MANUAL">Manual</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Fuente</span><select name="source_policy" x-model="selected.source_policy_value" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="SINGLE">Única</option><option value="FIRST_AVAILABLE">Primera</option><option value="PRIORITY">Prioridad</option><option value="CONDITIONAL">Condicional</option><option value="MANUAL">Manual</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Vacío</span><input name="empty_behavior" maxlength="80" x-model="selected.empty_behavior" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Regla asignación</span><input name="assignment_rule" maxlength="120" x-model="selected.assignment_rule" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label class="flex items-center gap-3"><input type="hidden" name="is_required" value="0"><input type="checkbox" name="is_required" value="1" x-model="selected.required"><span class="text-xs font-bold">Requerido</span></label>
                                    </div>
                                </template>

                                <template x-if="selected.kind === 'RESULT'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Tipo</span><select name="result_type" x-model="selected.result_type" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="WINNER">Ganador</option><option value="LOSER">Perdedor</option><option value="POSITION">Posición</option><option value="TOP_N">Top N</option><option value="QUALIFIED">Clasificados</option><option value="ELIMINATED">Eliminados</option><option value="SURVIVOR">Supervivientes</option><option value="MANUAL">Manual</option><option value="CUSTOM">Custom</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Cantidad</span><input type="number" name="quantity" min="1" max="64" x-model.number="selected.quantity" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Desde posición</span><input type="number" name="position_from" min="1" max="64" x-model.number="selected.position_from" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Hasta posición</span><input type="number" name="position_to" min="1" max="64" x-model.number="selected.position_to" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Estado producido</span><input name="participant_status" maxlength="80" x-model="selected.participant_status" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Prioridad</span><input type="number" name="priority" min="0" x-model.number="selected.priority" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label class="flex items-center gap-3"><input type="hidden" name="is_splittable" value="0"><input type="checkbox" name="is_splittable" value="1" x-model="selected.splittable"><span class="text-xs font-bold">Divisible</span></label>
                                    </div>
                                </template>

                                <template x-if="selected.kind === 'CONNECTION'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Origen</span><select name="source_type" x-model="selected.source_type" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="INPUT_GATE">Puerta</option><option value="RESULT">Resultado</option></select></label>
                                        <label x-show="selected.source_type === 'INPUT_GATE'"><span class="text-[9px] font-black uppercase text-slate-500">ID puerta origen</span><input type="number" name="source_input_gate_id" min="1" x-model.number="selected.source_input_gate_id" :disabled="selected.source_type !== 'INPUT_GATE'" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label x-show="selected.source_type === 'RESULT'"><span class="text-[9px] font-black uppercase text-slate-500">ID resultado origen</span><input type="number" name="source_result_id" min="1" x-model.number="selected.source_result_id" :disabled="selected.source_type !== 'RESULT'" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Destino</span><select name="target_type" x-model="selected.target_type" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="SLOT">Slot</option><option value="PHASE_EXIT">Salida</option></select></label>
                                        <label x-show="selected.target_type === 'SLOT'"><span class="text-[9px] font-black uppercase text-slate-500">ID slot destino</span><input type="number" name="target_slot_id" min="1" x-model.number="selected.target_slot_id" :disabled="selected.target_type !== 'SLOT'" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label x-show="selected.target_type === 'PHASE_EXIT'"><span class="text-[9px] font-black uppercase text-slate-500">ID salida destino</span><input type="number" name="target_phase_exit_id" min="1" x-model.number="selected.target_phase_exit_id" :disabled="selected.target_type !== 'PHASE_EXIT'" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Asignación</span><select name="allocation_mode" x-model="selected.allocation_mode" class="mt-2 w-full rounded-xl border-slate-300 text-sm"><option value="ALL">Todo</option><option value="TAKE_N">Tomar N</option><option value="POSITION">Posición</option><option value="REMAINDER">Restante</option><option value="CONDITIONAL">Condicional</option></select></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Valor</span><input type="number" step="0.01" name="allocation_value" min="0" x-model.number="selected.allocation_value" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Prioridad</span><input type="number" name="priority" min="0" x-model.number="selected.priority" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                        <label><span class="text-[9px] font-black uppercase text-slate-500">Condición</span><input name="condition_type" maxlength="80" x-model="selected.condition_type" class="mt-2 w-full rounded-xl border-slate-300 text-sm"></label>
                                    </div>
                                </template>

                                <label class="block">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                        Estado
                                    </span>

                                    <select name="status" x-model="selected.status" required
                                        class="mt-2 w-full rounded-xl border-slate-300 text-sm font-bold focus:border-violet-400 focus:ring-violet-400">
                                        <option value="ACTIVE">
                                            Activo
                                        </option>

                                        <option value="INACTIVE">
                                            Inactivo
                                        </option>
                                    </select>
                                </label>

                                <template x-if="selectedIsLockable()">
                                    <label
                                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50 p-4">
                                        <input type="hidden" name="is_locked" value="0">

                                        <input type="checkbox" name="is_locked" value="1"
                                            x-model="selected.locked"
                                            class="mt-0.5 rounded border-violet-300 text-violet-600 focus:ring-violet-500">

                                        <span>
                                            <span class="block text-xs font-black text-violet-900">
                                                Proteger personalización
                                            </span>

                                            <span class="mt-1 block text-[10px] leading-4 text-violet-700">
                                                Una regeneración requerirá confirmación explícita para reemplazarla.
                                            </span>
                                        </span>
                                    </label>
                                </template>

                                <button type="submit"
                                    class="w-full rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white shadow-lg shadow-violet-500/20 transition hover:bg-violet-700">
                                    Guardar elemento
                                </button>
                            </form>
                        </section>
                    </div>
                </div>
            </template>
        </aside>

        {{-- Fondo del diagnóstico general --}}
        <div x-cloak x-show="problemsOpen" x-transition.opacity @click="problemsOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/55 backdrop-blur-sm"></div>

        {{-- Panel lateral izquierdo de problemas --}}
        <aside x-cloak x-show="problemsOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full" @keydown.escape.window="problemsOpen = false" role="dialog"
            aria-modal="true" aria-labelledby="structure-problems-title"
            class="fixed inset-y-0 left-0 z-50 flex w-full max-w-lg flex-col bg-white shadow-2xl">
            <header class="shrink-0 bg-slate-950 p-5 text-white sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-red-300">
                            Diagnóstico integrado
                        </p>

                        <h2 id="structure-problems-title" class="mt-2 text-2xl font-black">
                            Problemas del grafo
                        </h2>

                        <p class="mt-1 text-xs text-slate-300">
                            Selecciona un problema para localizar su elemento.
                        </p>
                    </div>

                    <button type="button" @click="problemsOpen = false" aria-label="Cerrar diagnóstico"
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-lg font-black hover:bg-white/20">
                        ×
                    </button>
                </div>
            </header>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-5 sm:p-6">
                <template x-for="issue in payload.issues"
                    :key="issue.severity + ':' + issue.code + ':' + issue.entity_key">
                    <button type="button" @click="goToIssue(issue)"
                        class="block w-full rounded-2xl border p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md"
                        :class="{
                            'border-red-200 bg-red-50': issue.severity === 'ERROR',
                            'border-amber-200 bg-amber-50': issue.severity === 'WARNING',
                            'border-cyan-200 bg-cyan-50': issue.severity === 'RECOMMENDATION'
                        }">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-sm font-black shadow-sm"
                                x-text="
                                    issue.severity === 'ERROR'
                                        ? '!'
                                        : issue.severity === 'WARNING'
                                            ? '△'
                                            : 'i'
                                "></span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[9px] font-black uppercase tracking-wider"
                                        x-text="severityLabel(issue.severity)"></span>

                                    <span class="font-mono text-[8px] font-bold text-slate-400"
                                        x-text="issue.code"></span>
                                </div>

                                <p class="mt-1 text-xs font-black leading-5 text-slate-800"
                                    x-text="issue.title || issue.message"></p>

                                <p class="mt-1 text-[10px] leading-4 text-slate-600"
                                    x-text="issue.message"></p>

                                <p class="mt-2 text-[9px] font-black text-slate-500"
                                    x-text="issue.element || issue.entity_label"></p>

                                <div x-show="issue.action"
                                    class="mt-3 rounded-xl border border-white/80 bg-white/70 px-3 py-2">
                                    <p class="text-[8px] font-black uppercase tracking-wider text-violet-500">
                                        Siguiente acción
                                    </p>
                                    <p class="mt-1 text-[9px] font-bold leading-4 text-slate-700"
                                        x-text="issue.action"></p>
                                </div>
                            </div>
                        </div>
                    </button>
                </template>

                <div x-show="payload.issues.length === 0 && payload.structure.status === 'VALID'"
                    class="rounded-3xl border border-emerald-200 bg-emerald-50 p-8 text-center">
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-lg font-black text-emerald-700">
                        ✓
                    </span>

                    <p class="mt-3 font-black text-emerald-900">
                        Sin problemas
                    </p>

                    <p class="mt-1 text-xs text-emerald-700">
                        El snapshot validado no tiene errores, advertencias ni recomendaciones.
                    </p>
                </div>

                <div x-show="payload.issues.length === 0 && payload.structure.status !== 'VALID'"
                    class="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center">
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200 text-lg font-black text-slate-600">
                        ◇
                    </span>

                    <p class="mt-3 font-black text-slate-900"
                        x-text="payload.structure.status_label"></p>

                    <p class="mt-1 text-xs leading-5 text-slate-500"
                        x-text="payload.structure.status === 'NOT_GENERATED'
                            ? 'No existe estructura todavía. Genera el grafo antes de buscar problemas.'
                            : 'No hay issues listados, pero el estado actual todavía no habilita el runtime.'"></p>
                </div>
            </div>
        </aside>
    </div>
</template>

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

                        {{-- Rutas relacionadas --}}
                        <section class="mt-6">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-500">
                                    Rutas relacionadas
                                </h3>

                                <span
                                    class="rounded-full bg-indigo-100 px-2.5 py-1 text-[9px] font-black text-indigo-700"
                                    x-text="(selected.route_keys || []).length"></span>
                            </div>

                            <div class="mt-3 space-y-2">
                                <template x-for="routeKey in selected.route_keys || []" :key="routeKey">
                                    <button type="button" x-show="connectionIndex[routeKey]"
                                        @click="select(routeKey, false)"
                                        class="block w-full rounded-2xl border border-indigo-100 bg-indigo-50 p-3 text-left transition hover:border-indigo-300">
                                        <p class="font-mono text-[8px] font-bold text-indigo-400"
                                            x-text="connectionIndex[routeKey]?.code"></p>

                                        <p class="mt-1 truncate text-[10px] font-black text-slate-700">
                                            <span x-text="connectionIndex[routeKey]?.source_label"></span>

                                            <span class="px-1 text-indigo-500">
                                                →
                                            </span>

                                            <span x-text="connectionIndex[routeKey]?.target_label"></span>
                                        </p>
                                    </button>
                                </template>

                                <p x-show="!(selected.route_keys || []).length"
                                    class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-[10px] font-bold text-slate-400">
                                    Este elemento no tiene rutas relacionadas.
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

                                            <div>
                                                <p class="font-mono text-[8px] font-bold text-slate-400"
                                                    x-text="issue.code"></p>

                                                <p class="mt-1 text-[11px] font-bold leading-5 text-slate-700"
                                                    x-text="issue.message"></p>
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

                                <p class="mt-1 text-xs font-bold leading-5 text-slate-700" x-text="issue.message"></p>

                                <p class="mt-2 truncate text-[9px] font-black text-slate-500"
                                    x-text="issue.entity_label"></p>
                            </div>
                        </div>
                    </button>
                </template>

                <div x-show="payload.issues.length === 0"
                    class="rounded-3xl border border-emerald-200 bg-emerald-50 p-8 text-center">
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-lg font-black text-emerald-700">
                        ✓
                    </span>

                    <p class="mt-3 font-black text-emerald-900">
                        Sin problemas
                    </p>

                    <p class="mt-1 text-xs text-emerald-700">
                        El grafo no tiene errores, advertencias ni recomendaciones.
                    </p>
                </div>
            </div>
        </aside>
    </div>
</template>

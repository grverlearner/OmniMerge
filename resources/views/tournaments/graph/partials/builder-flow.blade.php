<div x-ref="flowSurface" @scroll.debounce.30ms="calculateLines()"
    class="relative min-h-[620px] overflow-auto bg-slate-50/70">
    <div class="relative min-w-max p-8">
        <svg class="pointer-events-none absolute inset-0 z-10 h-full w-full overflow-visible" aria-hidden="true">
            <defs>
                <marker id="flow-arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                    <path d="M0,0 L8,4 L0,8 Z" fill="#94a3b8"></path>
                </marker>
            </defs>

            <template x-for="line in lines" :key="line.id">
                <path :d="line.path" fill="none" :stroke="lineColor(line.connection)"
                    :stroke-width="lineWidth(line.connection)" stroke-linecap="round" marker-end="url(#flow-arrow)"
                    class="pointer-events-auto cursor-pointer transition"
                    @click.stop="select(
                        'CONNECTION',
                        connectionById(line.connection.id)
                    )">
                </path>
            </template>
        </svg>

        <div class="relative z-20 flex items-start gap-20">
            {{-- INICIOS --}}
            <section class="w-64 shrink-0">
                <div class="mb-4 flex items-center gap-2">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-black text-emerald-700">
                        0
                    </span>

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-emerald-600">
                            Orígenes
                        </p>

                        <p class="text-xs font-bold text-slate-500">
                            Participantes iniciales
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <template x-for="start in payload.starts" :key="start.id">
                        <button type="button" :id="`flow-start-${start.id}`" @click="select('START', start)"
                            class="relative block w-full rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="isSelected('START', start.id) ?
                                'border-emerald-500 ring-4 ring-emerald-100' :
                                'border-emerald-200'">
                            <span
                                class="absolute -right-2 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full border-4 border-white bg-emerald-500"></span>

                            <div class="flex items-start justify-between gap-3">
                                <span
                                    class="rounded-lg bg-emerald-50 px-2 py-1 text-[9px] font-black uppercase text-emerald-700">
                                    Inicio
                                </span>

                                <span class="text-[9px] font-black text-slate-400" x-text="start.code"></span>
                            </div>

                            <p class="mt-3 font-black text-slate-900" x-text="start.name"></p>

                            <p class="mt-1 text-[11px] font-semibold text-slate-500" x-text="start.source_type_label">
                            </p>

                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-[10px] font-bold text-slate-400">
                                    Participantes
                                </span>

                                <span class="text-xs font-black text-emerald-700"
                                    x-text="start.expected_participants ?? 'Flexible'"></span>
                            </div>
                        </button>
                    </template>

                    <template x-if="payload.starts.length === 0">
                        <button type="button" @click="$dispatch('open-add-start')"
                            class="w-full rounded-2xl border-2 border-dashed border-emerald-200 bg-emerald-50/50 p-5 text-center text-xs font-black text-emerald-700">
                            ＋ Crear primer inicio
                        </button>
                    </template>
                </div>
            </section>

            {{-- NIVELES DE FASES --}}
            @foreach ($flowAnalysis['levels'] as $level)
                <section class="w-80 shrink-0">
                    <div class="mb-4 flex items-center gap-2">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-xs font-black text-amber-700">
                            {{ $level['level'] }}
                        </span>

                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-amber-600">
                                Etapa {{ $level['level'] }}
                            </p>

                            <p class="text-xs font-bold text-slate-500">
                                {{ $level['label'] }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        @foreach ($tournamentTemplate->graphNodes->whereIn('id', $level['node_ids']) as $node)
                            <article @click="select('NODE', nodeById({{ $node->id }}))"
                                class="relative cursor-pointer overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                :class="isSelected('NODE', {{ $node->id }}) ?
                                    'border-amber-500 ring-4 ring-amber-100' :
                                    'border-slate-200'">
                                <div class="border-b border-slate-100 bg-gradient-to-r from-amber-50 to-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <span
                                            class="rounded-lg bg-amber-100 px-2 py-1 text-[9px] font-black uppercase text-amber-700">
                                            {{ $node->phaseTemplate->type_label }}
                                        </span>

                                        <span class="text-[9px] font-black text-slate-400">
                                            {{ $node->code }}
                                        </span>
                                    </div>

                                    <h3 class="mt-3 font-black text-slate-900">
                                        {{ $node->name }}
                                    </h3>

                                    <p class="mt-1 line-clamp-2 text-[11px] leading-5 text-slate-500">
                                        {{ $node->phaseTemplate->name }}
                                    </p>

                                    <div
                                        class="mt-3 rounded-xl bg-white/80 px-3 py-2 text-[10px] font-bold text-slate-600">
                                        {{ $node->phaseTemplate->participant_contract_label }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 divide-x divide-slate-100">
                                    <div class="p-3">
                                        <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-emerald-600">
                                            Entradas
                                        </p>

                                        <div class="space-y-2">
                                            @forelse ($node->entryPorts as $entry)
                                                <div id="flow-entry-{{ $node->id }}-{{ $entry->id }}"
                                                    class="relative rounded-lg border border-emerald-100 bg-emerald-50 px-2 py-2">
                                                    <span
                                                        class="absolute -left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full border-[3px] border-white bg-emerald-500"></span>

                                                    <p class="truncate text-[9px] font-black text-emerald-800">
                                                        {{ $entry->name }}
                                                    </p>

                                                    <p class="mt-0.5 text-[8px] font-bold text-emerald-600">
                                                        {{ $entry->contract_label }}
                                                    </p>
                                                </div>
                                            @empty
                                                <p class="text-[9px] font-bold text-red-500">
                                                    Sin entradas
                                                </p>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="p-3">
                                        <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-violet-600">
                                            Salidas
                                        </p>

                                        <div class="space-y-2">
                                            @forelse ($node->phaseTemplate->exits as $exit)
                                                <div id="flow-exit-{{ $node->id }}-{{ $exit->id }}"
                                                    class="relative rounded-lg border border-violet-100 bg-violet-50 px-2 py-2">
                                                    <span
                                                        class="absolute -right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full border-[3px] border-white bg-violet-500"></span>

                                                    <p class="truncate text-[9px] font-black text-violet-800">
                                                        {{ $exit->name }}
                                                    </p>

                                                    <p class="mt-0.5 truncate text-[8px] font-bold text-violet-600">
                                                        {{ $exit->selector_label }}
                                                    </p>
                                                </div>
                                            @empty
                                                <p class="text-[9px] font-bold text-amber-600">
                                                    Sin salidas
                                                </p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach

            {{-- NODOS SIN RUTA --}}
            @if (count($flowAnalysis['unreachable_node_ids']) > 0)
                <section class="w-80 shrink-0">
                    <div class="mb-4 flex items-center gap-2">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-100 text-xs font-black text-red-700">
                            !
                        </span>

                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-red-600">
                                Sin conexión
                            </p>

                            <p class="text-xs font-bold text-slate-500">
                                No alcanzables desde un inicio
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach ($tournamentTemplate->graphNodes->whereIn('id', $flowAnalysis['unreachable_node_ids']) as $node)
                            <button type="button" @click="select('NODE', nodeById({{ $node->id }}))"
                                class="block w-full rounded-2xl border border-red-200 bg-white p-4 text-left shadow-sm">
                                <span
                                    class="rounded-lg bg-red-50 px-2 py-1 text-[9px] font-black uppercase text-red-700">
                                    Fase aislada
                                </span>

                                <p class="mt-3 font-black text-slate-900">
                                    {{ $node->name }}
                                </p>

                                <p class="mt-1 text-[10px] text-slate-500">
                                    {{ $node->phaseTemplate->name }}
                                </p>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- TERMINALES --}}
            <section class="w-64 shrink-0">
                <div class="mb-4 flex items-center gap-2">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100 text-xs font-black text-rose-700">
                        ◆
                    </span>

                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-rose-600">
                            Destinos
                        </p>

                        <p class="text-xs font-bold text-slate-500">
                            Finales de las rutas
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <template x-for="terminal in payload.terminals" :key="terminal.id">
                        <button type="button" :id="`flow-terminal-${terminal.id}`"
                            @click="select('TERMINAL', terminal)"
                            class="relative block w-full rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="isSelected('TERMINAL', terminal.id) ?
                                'border-rose-500 ring-4 ring-rose-100' :
                                'border-rose-200'">
                            <span
                                class="absolute -left-2 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full border-4 border-white bg-rose-500"></span>

                            <div class="flex items-start justify

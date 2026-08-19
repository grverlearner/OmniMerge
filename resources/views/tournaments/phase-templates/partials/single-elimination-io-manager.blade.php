<section x-data="{
    gateTab: window.location.hash === '#output-gates' ? 'output' : 'input',
    selectTab(tab) {
        this.gateTab = tab;
        history.replaceState(null, '', tab === 'output' ? '#output-gates' : '#input-gates');
    }
}" class="mt-8 overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
    <header class="bg-gradient-to-br from-slate-950 via-fuchsia-950 to-indigo-950 p-6 text-white sm:p-8">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-fuchsia-300">
            Contrato de participantes
        </p>

        <h2 class="mt-2 text-3xl font-black">
            Puertas de entrada y salida
        </h2>

        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">
            Configura qué recibe la fase, cómo se distribuye hacia los slots
            y qué conjuntos de participantes produce al terminar.
        </p>

        <div class="mt-5 inline-flex rounded-2xl border border-white/10 bg-white/5 p-1">
            <button type="button" @click="selectTab('input')"
                :class="gateTab === 'input' ? 'bg-fuchsia-400 text-slate-950' : 'text-white hover:bg-white/10'"
                class="rounded-xl px-4 py-3 text-[10px] font-black transition">
                Entradas · {{ $inputGates->count() }}
            </button>

            <button type="button" @click="selectTab('output')"
                :class="gateTab === 'output' ? 'bg-emerald-400 text-slate-950' : 'text-white hover:bg-white/10'"
                class="rounded-xl px-4 py-3 text-[10px] font-black transition">
                Salidas · {{ $exits->count() }}
            </button>
        </div>
    </header>

    {{-- Entradas --}}
    <div id="input-gates" x-cloak x-show="gateTab === 'input'" x-transition
        class="scroll-mt-28 p-5 sm:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-fuchsia-600">
                    Entradas hacia la estructura
                </p>

                <h3 class="mt-1 text-2xl font-black text-slate-900">
                    Puertas de entrada
                </h3>
            </div>

            <div class="flex flex-wrap gap-2 text-[9px] font-black">
                <span class="rounded-full bg-fuchsia-100 px-3 py-2 text-fuchsia-700">
                    {{ $inputGates->count() }} definiciones
                </span>

                <span class="rounded-full bg-indigo-100 px-3 py-2 text-indigo-700">
                    {{ $inputGates->sum(fn($gate) => $gate->outgoingConnections->count()) }}
                    rutas internas
                </span>

                <span class="rounded-full bg-slate-100 px-3 py-2 text-slate-600">
                    {{ $inputGates->sum(fn($gate) => $gate->contextualEntryPorts->count()) }}
                    usos contextuales
                </span>
            </div>
        </div>

        <div class="mt-6 grid gap-6 2xl:grid-cols-[minmax(0,1fr)_440px]">
            <div class="space-y-4">
                @forelse ($inputGates as $phaseInputGate)
                    <article x-data="{ editing: false }"
                        class="overflow-hidden rounded-3xl border border-fuchsia-200 bg-white shadow-sm">
                        <div class="p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-fuchsia-100 font-black text-fuchsia-700">
                                    IN
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-black text-slate-900">
                                            {{ $phaseInputGate->name }}
                                        </h4>

                                        <span
                                            class="rounded-full bg-fuchsia-50 px-2.5 py-1 text-[9px] font-black text-fuchsia-700">
                                            {{ $phaseInputGate->type_label }}
                                        </span>

                                        <span
                                            class="rounded-full bg-indigo-50 px-2.5 py-1 text-[9px] font-black text-indigo-700">
                                            {{ $phaseInputGate->contract_label }}
                                        </span>

                                        @if ($phaseInputGate->generation_source === 'MANUAL')
                                            <span
                                                class="rounded-full bg-violet-100 px-2.5 py-1 text-[9px] font-black text-violet-700">
                                                Manual
                                            </span>
                                        @endif

                                        @if ($phaseInputGate->is_locked)
                                            <span
                                                class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-black text-slate-600">
                                                Protegida
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-xs leading-5 text-slate-500">
                                        {{ $phaseInputGate->description ?: 'Sin descripción adicional.' }}
                                    </p>

                                    <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                                        <div class="rounded-2xl border border-fuchsia-100 bg-fuchsia-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-fuchsia-500">
                                                Tipo
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-fuchsia-900">
                                                {{ $phaseInputGate->type_label }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-indigo-500">
                                                Capacidad
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-indigo-900">
                                                {{ $phaseInputGate->contract_label }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-sky-500">
                                                Distribución
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-sky-900">
                                                {{ $phaseInputGate->distribution_label }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">
                                                Estado
                                            </p>
                                            <p class="mt-1 text-[11px] font-black {{ $phaseInputGate->status === 'ACTIVE' ? 'text-emerald-700' : 'text-slate-600' }}">
                                                {{ $phaseInputGate->status_label }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-amber-600">
                                                Cobertura
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-amber-900">
                                                {{ $phaseInputGate->coverage_label }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2 text-[9px] font-bold">
                                        <span class="rounded-lg bg-slate-50 px-2.5 py-1.5 font-mono text-slate-500">
                                            {{ $phaseInputGate->code }}
                                        </span>

                                        <span class="rounded-lg bg-indigo-50 px-2.5 py-1.5 text-indigo-700">
                                            {{ $phaseInputGate->outgoingConnections->count() }}
                                            slots
                                        </span>

                                        <span class="rounded-lg bg-fuchsia-50 px-2.5 py-1.5 text-fuchsia-700">
                                            {{ $phaseInputGate->contextualEntryPorts->count() }}
                                            puertos contextuales
                                        </span>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <button type="button" @click="editing = !editing"
                                        class="rounded-xl border border-fuchsia-200 px-3 py-2 text-[10px] font-black text-fuchsia-700">
                                        <span x-text="editing ? 'Cerrar' : 'Editar y mapear'"></span>
                                    </button>

                                    <form method="POST"
                                        action="{{ route('tournaments.single-elimination.input-gates.duplicate', [$phaseTemplate, $phaseInputGate]) }}">
                                        @csrf

                                        <button type="submit"
                                            class="rounded-xl border border-slate-200 px-3 py-2 text-[10px] font-black text-slate-600">
                                            Duplicar
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('tournaments.single-elimination.input-gates.destroy', [$phaseTemplate, $phaseInputGate]) }}"
                                        data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                        data-confirm-title="Eliminar puerta de entrada"
                                        data-confirm-message="También se eliminarán sus rutas internas."
                                        data-confirm-subject="{{ $phaseInputGate->name }}"
                                        data-confirm-action="Eliminar puerta">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="rounded-xl bg-red-50 px-3 py-2 text-[10px] font-black text-red-600">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-4 border-t border-fuchsia-100 pt-4">
                                <p class="text-[9px] font-black uppercase text-fuchsia-500">
                                    Destinos internos
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse ($phaseInputGate->outgoingConnections->sortBy('allocation_value') as $connection)
                                        <span
                                            class="rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-2 text-[9px] font-bold text-indigo-700">
                                            P{{ (int) $connection->allocation_value }}
                                            →
                                            {{ $connection->target_label }}
                                        </span>
                                    @empty
                                        <span
                                            class="rounded-xl border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-[9px] font-bold text-amber-700">
                                            Sin slots asignados
                                        </span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div x-cloak x-show="editing" x-transition
                            class="border-t border-fuchsia-100 bg-slate-50/70 p-5">
                            @include(
                                'tournaments.phase-templates.partials.single-elimination-input-gate-form',
                                [
                                    'phaseInputGate' => $phaseInputGate,
                                ]
                            )
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-fuchsia-300 bg-fuchsia-50 p-8 text-center">
                        <p class="font-black text-fuchsia-900">
                            No existen puertas de entrada
                        </p>
                    </div>
                @endforelse
            </div>

            <aside class="h-fit 2xl:sticky 2xl:top-28">
                <div class="overflow-hidden rounded-3xl border border-fuchsia-200 bg-white">
                    <div class="bg-fuchsia-950 p-5 text-white">
                        <p class="text-[9px] font-black uppercase text-fuchsia-300">
                            Nueva definición
                        </p>

                        <h4 class="mt-1 text-lg font-black">
                            Crear puerta de entrada
                        </h4>
                    </div>

                    <div class="p-5">
                        @include(
                            'tournaments.phase-templates.partials.single-elimination-input-gate-form',
                            [
                                'phaseInputGate' => null,
                            ]
                        )
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- Salidas --}}
    <div id="output-gates" x-cloak x-show="gateTab === 'output'" x-transition
        class="scroll-mt-28 p-5 sm:p-8">
        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">
                Clasificados que abandonan la fase
            </p>

            <h3 class="mt-1 text-2xl font-black text-slate-900">
                Puertas de salida
            </h3>

            <p class="mt-2 text-xs leading-5 text-slate-500">
                Aquí defines quién abandona la fase. El destino siguiente
                se conecta después desde el grafo del torneo.
            </p>
        </div>

        <div class="mt-6 grid gap-6 2xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-4">
                @forelse ($exits as $phaseExit)
                    <article x-data="{ editing: false }"
                        class="overflow-hidden rounded-3xl border border-emerald-200 bg-white">
                        <div class="p-5">
                            <div class="flex flex-col gap-4 lg:flex-row">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 font-black text-emerald-700">
                                    OUT
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-black text-slate-900">
                                            {{ $phaseExit->name }}
                                        </h4>

                                        <span
                                            class="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-black text-emerald-700">
                                            {{ $phaseExit->selector_label }}
                                        </span>

                                        <span
                                            class="rounded-full bg-indigo-50 px-2.5 py-1 text-[9px] font-black text-indigo-700">
                                            {{ $phaseExit->contract_label }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-xs font-bold text-slate-600">
                                        {{ $phaseExit->selection_summary }}
                                    </p>

                                    <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-emerald-600">
                                                Selector
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-emerald-900">
                                                {{ $phaseExit->selector_label }}
                                            </p>
                                            <p class="mt-1 text-[9px] leading-4 text-emerald-700">
                                                {{ $phaseExit->selection_summary }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-sky-600">
                                                Timing
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-sky-900">
                                                {{ $phaseExit->timing_label }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-indigo-600">
                                                Contrato
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-indigo-900">
                                                {{ $phaseExit->contract_label }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border border-violet-100 bg-violet-50/60 p-3">
                                            <p class="text-[8px] font-black uppercase tracking-wider text-violet-600">
                                                Modo de resolución
                                            </p>
                                            <p class="mt-1 text-[11px] font-black text-violet-900">
                                                {{ $phaseExit->resolution_mode_label }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse ($phaseExit->incomingInternalConnections as $connection)
                                            <span
                                                class="rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-2 text-[9px] font-bold text-indigo-700">
                                                {{ $connection->source_label }} →
                                            </span>
                                        @empty
                                            <span
                                                class="rounded-xl border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-[9px] font-bold text-amber-700">
                                                Sin resultados internos conectados
                                            </span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-start gap-2">
                                    <button type="button" @click="editing = !editing"
                                        class="inline-flex h-10 w-24 items-center justify-center rounded-xl border border-emerald-200 bg-white text-[10px] font-black text-emerald-700 transition hover:bg-emerald-50">
                                        <span x-text="editing ? 'Cerrar' : 'Editar'"></span>
                                    </button>

                                    <form method="POST"
                                        action="{{ route('tournaments.phase-exits.destroy', [$phaseTemplate, $phaseExit]) }}"
                                        class="shrink-0" data-omni-confirm data-confirm-variant="danger"
                                        data-confirm-icon="×" data-confirm-title="Eliminar puerta de salida"
                                        data-confirm-message="La salida solo se eliminará si no tiene rutas internas ni conexiones activas en el Tournament Graph."
                                        data-confirm-subject="{{ $phaseExit->name }}"
                                        data-confirm-action="Eliminar salida">
                                        @csrf
                                        @method('DELETE')

                                        <input type="hidden" name="return_to" value="structure_io">

                                        <button type="submit"
                                            class="inline-flex h-10 w-24 items-center justify-center rounded-xl bg-red-50 text-[10px] font-black text-red-600 transition hover:bg-red-100">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div x-cloak x-show="editing" class="border-t border-emerald-100 bg-slate-50 p-5">
                            @include('tournaments.phase-templates.partials.exit-form', [
                                'phaseTemplate' => $phaseTemplate,
                                'phaseExit' => $phaseExit,
                                'returnTo' => 'structure_io',
                            ])
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-emerald-300 bg-emerald-50 p-8 text-center">
                        <p class="font-black text-emerald-900">
                            No existen puertas de salida
                        </p>
                    </div>
                @endforelse
            </div>

            <aside class="h-fit 2xl:sticky 2xl:top-28">
                <div class="overflow-hidden rounded-3xl border border-emerald-200 bg-white">
                    <div class="bg-emerald-950 p-5 text-white">
                        <p class="text-[9px] font-black uppercase text-emerald-300">
                            Nuevo contrato
                        </p>

                        <h4 class="mt-1 text-lg font-black">
                            Crear puerta de salida
                        </h4>
                    </div>

                    <div class="p-5">
                        @include('tournaments.phase-templates.partials.exit-form', [
                            'phaseTemplate' => $phaseTemplate,
                            'phaseExit' => null,
                            'returnTo' => 'structure_io',
                        ])
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

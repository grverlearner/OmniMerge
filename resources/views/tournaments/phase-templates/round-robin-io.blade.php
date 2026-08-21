<x-tournament-layout>
    <x-slot name="header">
        Entrada y salida · {{ $phaseTemplate->name }}
    </x-slot>

    <div class="pb-16">
        @include('tournaments.phase-templates.partials.workspace-navigation', [
            'current' => 'io',
        ])

        {{-- HERO --}}

        <section
            class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-cyan-950 to-emerald-950 p-7 text-white shadow-xl sm:p-8">

            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl">
            </div>

            <div class="relative">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-cyan-300">
                    ⇄ Puertas de salida
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">
                    {{ $phaseTemplate->name }}
                </h1>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                    Round Robin no elimina participantes durante la fase — todos juegan
                    todas las jornadas. Estas puertas deciden quién sale con qué destino
                    cuando la fase termina, según la clasificación final (puntos +
                    desempates). Los selectores más útiles aquí son
                    <strong class="text-white">Mejores N</strong>,
                    <strong class="text-white">Últimos N</strong>,
                    <strong class="text-white">Posición específica</strong> y
                    <strong class="text-white">Rango de posiciones</strong>.
                </p>
            </div>
        </section>

        {{-- ENTRADA DE LA FASE --}}

        <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                        ⇢ Entrada de la fase
                    </p>
                    <h3 class="mt-2 font-black text-slate-900">
                        Cómo llegan los participantes
                    </h3>
                    <p class="mt-2 max-w-2xl text-xs leading-6 text-slate-500">
                        Round Robin no tiene puertas de entrada configurables por separado
                        como Single Elimination (no reparte a posiciones concretas de un
                        bracket) — recibe un único conjunto de participantes y los enfrenta
                        a todos entre sí. Ese conjunto respeta el contrato de participantes
                        de esta Fase:
                        <strong class="text-slate-800">
                            {{ $phaseTemplate->participant_contract_label }}
                        </strong>.
                    </p>
                    <p class="mt-3 max-w-2xl text-xs leading-6 text-slate-500">
                        Cuando coloques esta Fase como Node dentro de un Tournament Graph, se
                        genera automáticamente una puerta de entrada única ("Entrada
                        principal") que acumula a todos los participantes que le lleguen
                        desde cualquier Start o Phase Exit conectado. Puedes editar su
                        política de unión o su contrato de capacidad desde el editor del
                        grafo (pestaña "Camino" de la plantilla de torneo, panel del Node).
                    </p>
                </div>

                <div class="shrink-0 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                        Contrato
                    </p>
                    <p class="mt-1 text-sm font-black text-slate-800">
                        {{ $phaseTemplate->participant_contract_label }}
                    </p>
                </div>
            </div>
        </section>

        {{-- STATS --}}

        <section class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ([
        ['Salidas', $exits->count(), 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        ['Activas', $exits->where('status', 'ACTIVE')->count(), 'border-cyan-200 bg-cyan-50 text-cyan-700'],
        ['Inactivas', $exits->where('status', 'INACTIVE')->count(), 'border-slate-200 bg-slate-50 text-slate-500'],
    ] as [$label, $value, $classes])
                <div class="rounded-2xl border p-4 {{ $classes }}">
                    <p class="text-[9px] font-black uppercase tracking-wider">
                        {{ $label }}
                    </p>
                    <p class="mt-1 text-2xl font-black">
                        {{ $value }}
                    </p>
                </div>
            @endforeach
        </section>

        @if (session('success'))
            <div
                class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="font-black text-red-900">
                    No se pudo guardar la puerta de salida
                </p>
                <div class="mt-2 space-y-1">
                    @foreach ($errors->all() as $error)
                        <p class="text-xs leading-5 text-red-700">
                            • {{ $error }}
                        </p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- LISTA + FORMULARIO --}}

        <section class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">

            <div class="space-y-3">
                @forelse ($exits as $exit)
                    <article x-data="{ editing: false }"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 font-black text-emerald-700">
                                OUT
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-black text-slate-900">
                                        {{ $exit->name }}
                                    </h4>

                                    <span
                                        class="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-black text-emerald-700">
                                        {{ $exit->selector_label }}
                                    </span>

                                    @if ($exit->status !== 'ACTIVE')
                                        <span
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-black text-slate-500">
                                            Inactiva
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 text-xs font-bold text-slate-600">
                                    {{ $exit->selection_summary }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2 text-[9px] font-black">
                                    <span class="rounded-lg bg-sky-50 px-2.5 py-1.5 text-sky-700">
                                        {{ $exit->timing_label }}
                                    </span>
                                    <span class="rounded-lg bg-indigo-50 px-2.5 py-1.5 text-indigo-700">
                                        Prioridad {{ $exit->priority }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <button type="button" @click="editing = !editing"
                                    class="rounded-xl border border-emerald-200 px-3 py-2 text-[10px] font-black text-emerald-700">
                                    <span x-text="editing ? 'Cerrar' : 'Editar'"></span>
                                </button>

                                <form method="POST"
                                    action="{{ route('tournaments.phase-exits.destroy', [$phaseTemplate, $exit]) }}"
                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                    data-confirm-title="Eliminar puerta de salida"
                                    data-confirm-message="La salida solo se eliminará si no tiene conexiones activas en el Tournament Graph."
                                    data-confirm-subject="{{ $exit->name }}" data-confirm-action="Eliminar salida">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="round_robin_io">

                                    <button type="submit"
                                        class="rounded-xl bg-red-50 px-3 py-2 text-[10px] font-black text-red-600">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div x-cloak x-show="editing" class="border-t border-emerald-100 bg-slate-50 p-5">
                            @include('tournaments.phase-templates.partials.exit-form', [
                                'phaseTemplate' => $phaseTemplate,
                                'phaseExit' => $exit,
                                'returnTo' => 'round_robin_io',
                            ])
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-emerald-300 bg-emerald-50 p-8 text-center">
                        <p class="font-black text-emerald-900">
                            No existen puertas de salida
                        </p>
                        <p class="mt-2 text-sm leading-6 text-emerald-700">
                            Sin ninguna configurada, al completar la fase todos los
                            participantes quedan como supervivientes genéricos, sin
                            enrutarse a ningún destino específico del Tournament Graph.
                        </p>
                    </div>
                @endforelse
            </div>

            <aside class="h-fit rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5 xl:sticky xl:top-28">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-600">
                    Nueva puerta
                </p>
                <h3 class="mt-2 font-black text-slate-900">
                    Agregar salida
                </h3>

                @include('tournaments.phase-templates.partials.exit-form', [
                    'phaseTemplate' => $phaseTemplate,
                    'phaseExit' => null,
                    'returnTo' => 'round_robin_io',
                ])
            </aside>
        </section>
    </div>
</x-tournament-layout>

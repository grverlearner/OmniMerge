<x-tournament-layout>
    <x-slot name="header">
        Simulador · {{ $phaseTemplate->name }}
    </x-slot>

    <div x-data="singleEliminationSimulator({
        initializeUrl: @js(route('tournaments.single-elimination.simulator.initialize', $phaseTemplate)),
        actionUrl: @js(route('tournaments.single-elimination.simulator.action', $phaseTemplate)),
        storageKey: @js('omnimerge:se-simulator:' . auth()->id() . ':' . $phaseTemplate->id),
        minParticipants: @js((int) $phaseTemplate->min_participants),
        maxParticipants: @js($phaseTemplate->max_participants !== null ? (int) $phaseTemplate->max_participants : null),
        exactParticipants: @js($phaseTemplate->exact_participants !== null ? (int) $phaseTemplate->exact_participants : null),
    })" x-init="init()" class="pb-16">

        @include('tournaments.phase-templates.partials.workspace-navigation', [
            'current' => 'simulator',
        ])

        {{-- HERO --}}

        <section
            class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-violet-950 to-fuchsia-950 p-6 text-white shadow-xl sm:p-8">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-violet-400/15 blur-3xl">
            </div>

            <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">
                        ▶ Simulador de Single Elimination
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">
                        Prueba esta fase sin crear un torneo
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-violet-100/80">
                        Genera participantes ficticios y ejecuta la configuración real de
                        {{ $settings->configuration_mode_label }} —
                        seeding, pairing, BYE, series y puertas de salida — exactamente como se
                        ejecutaría en un torneo real. Nada de esto se guarda: es completamente
                        temporal y aislado.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black">
                        Estado:
                        <span x-text="statusLabel(runtime()?.status ?? 'SIN GENERAR')"></span>
                    </span>
                </div>
            </div>
        </section>

        <div x-show="error" x-cloak class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
            <p class="text-xs font-black text-red-800">No fue posible completar la acción</p>
            <p class="mt-1 text-xs leading-6 text-red-700" x-text="error"></p>
        </div>

        {{-- PASO 1-2: CONSTRUCTOR DE PARTICIPANTES --}}

        <section x-show="!state" x-cloak class="mt-6">
            @include('tournaments.phase-templates.partials.simulator.participants-builder')
        </section>

        {{-- PASO 3-7: BRACKET Y EJECUCIÓN --}}

        <section x-show="state" x-cloak class="mt-6 space-y-5">
            @include('tournaments.phase-templates.partials.simulator.manual-decision')

            @include('tournaments.phase-templates.partials.simulator.bracket-viewer')

            @include('tournaments.phase-templates.partials.simulator.exits-panel')

            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" @click="resetSimulation()" :disabled="loading"
                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-black text-amber-700 disabled:opacity-40">
                    ↺ Reiniciar (mismos participantes)
                </button>

                <button type="button" @click="newSimulation()"
                    class="rounded-xl border border-red-200 bg-white px-4 py-3 text-xs font-black text-red-600">
                    Nueva simulación
                </button>
            </div>
        </section>
    </div>
</x-tournament-layout>

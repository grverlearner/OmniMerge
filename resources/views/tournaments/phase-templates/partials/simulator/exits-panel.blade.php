{{-- Puertas de salida resueltas al completar la fase --}}

<section x-show="isCompleted()" x-cloak class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">

    <p class="text-[9px] font-black uppercase tracking-wider text-emerald-700">
        Puertas de salida
    </p>

    <h3 class="mt-1 text-xl font-black text-slate-950">
        Quién sale por cada puerta
    </h3>

    <p class="mt-2 max-w-2xl text-xs leading-6 text-slate-600">
        Resuelto con las mismas Phase Exits configuradas en la pestaña
        "Entrada y salida" de esta fase. En un torneo real, cada puerta enrutaría
        estos participantes hacia la siguiente fase del Tournament Graph.
    </p>

    <div x-show="!exitOutcomes().length" class="mt-4 rounded-2xl border border-dashed border-emerald-300 bg-white p-4 text-xs text-slate-500">
        Esta fase no tiene puertas de salida configuradas todavía.
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <template x-for="outcome in exitOutcomes()" :key="outcome.exit_id">
            <div class="rounded-2xl border border-emerald-200 bg-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-black text-slate-900" x-text="outcome.exit_name"></p>
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-[8px] font-black text-emerald-700"
                        x-text="outcome.selector_type"></span>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <template x-for="participantId in outcome.participant_ids" :key="participantId">
                        <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-700"
                            x-text="participantName(participantId)"></span>
                    </template>

                    <span x-show="!outcome.participant_ids.length" class="text-[10px] font-bold text-slate-400">
                        Sin participantes
                    </span>
                </div>
            </div>
        </template>
    </div>

    <div x-show="unassignedAfterExits().length" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-xs font-black text-amber-900">Sin salida asignada</p>
        <div class="mt-2 flex flex-wrap gap-1.5">
            <template x-for="participantId in unassignedAfterExits()" :key="participantId">
                <span class="rounded-lg bg-white px-2 py-1 text-[10px] font-bold text-amber-800"
                    x-text="participantName(participantId)"></span>
            </template>
        </div>
    </div>
</section>

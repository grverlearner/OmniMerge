{{-- Constructor rápido de participantes ficticios --}}

<section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
        Paso 1 · Participantes ficticios
    </p>

    <h2 class="mt-2 text-xl font-black text-slate-950">
        ¿Cuántos participantes quieres probar?
    </h2>

    <p class="mt-2 max-w-2xl text-xs leading-6 text-slate-500">
        Se generan participantes de prueba (nunca Entidades reales). Puedes editar el
        nombre y el seed de cada uno antes de generar la simulación.
        @if ($phaseTemplate->exact_participants !== null)
            Esta fase exige exactamente {{ $phaseTemplate->exact_participants }} participantes.
        @else
            Esta fase admite entre {{ $phaseTemplate->min_participants }}
            @if ($phaseTemplate->max_participants !== null)
                y {{ $phaseTemplate->max_participants }}
            @else
                y sin límite superior
            @endif
            participantes.
        @endif
    </p>

    <div class="mt-5 flex flex-wrap gap-2">
        @foreach ([2, 4, 8, 16, 32] as $count)
            <button type="button" @click="quickFill({{ $count }})"
                class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2 text-xs font-black text-violet-700 hover:bg-violet-100">
                {{ $count }}
            </button>
        @endforeach

        <button type="button" @click="addParticipant()"
            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-black text-slate-600 hover:bg-slate-100">
            + Agregar uno
        </button>
    </div>

    <div class="mt-5 max-h-[420px] space-y-2 overflow-y-auto">
        <template x-for="(participant, index) in builderParticipants" :key="index">
            <div class="grid grid-cols-[32px_minmax(0,1fr)_90px_36px] items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-[10px] font-black text-slate-500"
                    x-text="index + 1"></span>

                <input type="text" x-model="participant.name" maxlength="60"
                    class="rounded-lg border-slate-200 bg-white text-xs font-bold">

                <input type="number" x-model.number="participant.seed" min="1"
                    class="rounded-lg border-slate-200 bg-white text-center text-xs font-bold" title="Seed">

                <button type="button" @click="removeParticipant(index)"
                    :disabled="builderParticipants.length <= 2"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-30">
                    ×
                </button>
            </div>
        </template>
    </div>

    <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
        <p class="text-xs font-bold text-slate-500">
            <span x-text="builderParticipants.length"></span> participantes listos
        </p>

        <button type="button" @click="generateSimulation()" :disabled="!canGenerate()"
            class="rounded-xl bg-violet-600 px-6 py-3 text-xs font-black text-white shadow-lg shadow-violet-500/20 disabled:cursor-not-allowed disabled:opacity-40">
            <span x-show="!loading">▶ Generar simulación</span>
            <span x-show="loading" x-cloak>Generando…</span>
        </button>
    </div>
</section>

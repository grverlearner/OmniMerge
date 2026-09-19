{{--
    ¿SE PUEDE JUGAR?

    Dos avisos distintos:

      en el acto   lo que ya se ve: a una fase de entrada le llegan menos
                   (o mas) de los que pide
      al ensayar   lo que solo se sabe jugando: una fase de mas adelante
                   que se queda corta. Se juega en memoria y no se guarda.
--}}

<div class="space-y-1.5">

    <template x-if="phaseProblems.length">
        <div class="rounded-2xl border border-rose-500/40 bg-rose-500/5 p-2.5">
            <p class="flex items-center gap-1.5 text-[12px] font-black text-rose-200">
                <x-omni-icon name="aviso" size="h-4 w-4" />
                <span x-text="phaseProblems.length === 1 ? 'Una fase no podrá arrancar' : phaseProblems.length + ' fases no podrán arrancar'"></span>
            </p>
            <ul class="mt-1 space-y-0.5">
                <template x-for="n in phaseProblems" :key="'pp' + n.node_id">
                    <li class="text-[11px] leading-relaxed text-rose-200/80" x-text="'· ' + phaseProblemText(n)"></li>
                </template>
            </ul>
            <p class="mt-1 text-[10px] text-slate-500">
                Ajusta quién entra o el reparto. Tal como está, la edición no se podrá crear.
            </p>
        </div>
    </template>

    <div class="flex flex-wrap items-center gap-2 rounded-2xl border px-2.5 py-2"
        :class="rehearsalCurrent && rehearsal.ok === true ? 'border-emerald-500/40 bg-emerald-500/5'
            : (rehearsalCurrent && rehearsal.ok === false ? 'border-rose-500/40 bg-rose-500/5' : 'border-slate-800 bg-slate-900/60')">

        <button type="button" @click="rehearse()" :disabled="rehearsal.loading"
            class="flex shrink-0 items-center gap-1.5 rounded-xl border border-violet-500/40 bg-violet-500/10 px-2.5 py-1.5 text-[11px] font-black text-violet-200 transition hover:bg-violet-500/20 disabled:opacity-50">
            <x-omni-icon name="reproducir" size="h-3.5 w-3.5" />
            <span x-text="rehearsal.loading ? 'Ensayando…' : 'Comprobar si se puede jugar'"></span>
        </button>

        <p class="min-w-0 flex-1 text-[11px] leading-relaxed"
            :class="rehearsalCurrent && rehearsal.ok === true ? 'text-emerald-200' : (rehearsalCurrent && rehearsal.ok === false ? 'text-rose-200' : 'text-slate-500')">
            <span x-show="! rehearsalCurrent || rehearsal.ok === null">
                Juega el torneo entero en memoria con este reparto, sin guardar nada, y dice si puede terminar.
            </span>
            <span x-show="rehearsalCurrent && rehearsal.ok === true" x-cloak>
                Se puede jugar de principio a fin con este reparto.
            </span>
            <span x-show="rehearsalCurrent && rehearsal.ok === false" x-cloak x-text="rehearsal.problem"></span>
        </p>
    </div>
</div>

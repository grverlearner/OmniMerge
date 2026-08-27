@php
    /*
     * El diagnóstico del grafo, abajo y siempre a la vista.
     *
     * Un torneo puede estar mal de formas que no se ven mirando el dibujo:
     * una fase que espera 16 y recibe 8, una salida que no lleva a ningún
     * sitio, un final al que no llega nadie. Todo eso lo calculan los
     * servicios de validación que ya existían —estructural y de flujo— y
     * aquí solo se enseña.
     *
     * Se pliega, y recuerda si lo dejaste plegado: cualquier formulario de
     * esta pantalla recarga la página, y sin recordarlo volvería a abrirse
     * en cada guardado.
     */
@endphp

<section class="shrink-0 border-t border-slate-800 bg-slate-900/60"
    x-data="{
        open: true,

        init() {
            try {
                const saved = localStorage.getItem('omnimerge.torneo.diagnostico');

                if (saved !== null) this.open = saved === '1';
            } catch (e) {
                /* Sin almacenamiento: se queda abierto */
            }
        },

        toggle() {
            this.open = !this.open;

            try {
                localStorage.setItem('omnimerge.torneo.diagnostico', this.open ? '1' : '0');
            } catch (e) {
                /* Se pliega igual, solo que no se recuerda */
            }
        },
    }">

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-1.5">

        <button type="button" @click="toggle()"
            class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-[0.16em] text-slate-400 transition hover:text-slate-100">
            <span x-text="open ? '▾' : '▸'"></span>
            Diagnóstico
        </button>

        <span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider"
            :class="isValid ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300'"
            x-text="isValid ? 'el torneo se puede jugar' : 'hay que arreglar algo'"></span>

        <template x-if="(stats.errors ?? 0) > 0">
            <span class="rounded bg-rose-500/15 px-1.5 py-0.5 text-[9px] font-black text-rose-300">
                <span x-text="stats.errors"></span> errores
            </span>
        </template>

        <template x-if="(stats.warnings ?? 0) > 0">
            <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
                <span x-text="stats.warnings"></span> avisos
            </span>
        </template>

        <form method="POST" class="ml-auto"
            action="{{ route('tournaments.graph.validate', $tournamentTemplate) }}">
            @csrf
            <button class="rounded-md border border-slate-700 px-2 py-1 text-[9px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-300">
                ✓ Revisar de nuevo
            </button>
        </form>

        <form method="POST"
            action="{{ route('tournaments.graph.auto-layout', $tournamentTemplate) }}">
            @csrf
            <button class="rounded-md border border-slate-700 px-2 py-1 text-[9px] font-black text-slate-400 transition hover:border-sky-500 hover:text-sky-300"
                title="Recolocar las piezas por nivel">
                ⌗ Ordenar
            </button>
        </form>

    </div>


    <div x-show="open" x-cloak
        class="arena-scroll max-h-[22vh] overflow-y-auto border-t border-slate-800 px-3 py-2">

        <div class="grid gap-1.5 lg:grid-cols-2">

            <template x-for="(problem, i) in validation.errors" :key="'ve' + i">
                <p class="flex items-start gap-1.5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-2 py-1">
                    <span class="shrink-0 text-[10px] text-rose-400">✕</span>
                    <span class="text-[9px] leading-relaxed text-rose-200" x-text="problem.message"></span>
                </p>
            </template>

            <template x-for="(problem, i) in validation.warnings" :key="'vw' + i">
                <p class="flex items-start gap-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-2 py-1">
                    <span class="shrink-0 text-[10px] text-amber-400">!</span>
                    <span class="text-[9px] leading-relaxed text-amber-200" x-text="problem.message"></span>
                </p>
            </template>

            <template x-for="(note, i) in (validation.information ?? [])" :key="'vi' + i">
                <p class="flex items-start gap-1.5 rounded-lg border border-slate-800 bg-slate-950/50 px-2 py-1">
                    <span class="shrink-0 text-[10px] text-slate-500">·</span>
                    <span class="text-[9px] leading-relaxed text-slate-400" x-text="note.message"></span>
                </p>
            </template>

            <template x-if="validation.errors.length === 0
                && validation.warnings.length === 0
                && (validation.information ?? []).length === 0">
                <p class="py-3 text-center text-[10px] text-slate-600 lg:col-span-2">
                    Nada que señalar: el recorrido está completo y cuadra.
                </p>
            </template>

        </div>

    </div>

</section>

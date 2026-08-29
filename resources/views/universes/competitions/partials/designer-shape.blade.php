@php
    /*
     * 02 · LA FORMA — con qué plantilla se juega esta edición.
     *
     * Es la decisión más grande de la pantalla, y hasta ahora se tomaba
     * leyendo un desplegable con el nombre. Un nombre no dice si hay
     * grupos, ni cuántas rondas, ni por dónde entra la gente: dos
     * plantillas llamadas «Copa 2024» y «Copa 2025» pueden no parecerse.
     *
     * Así que aquí se ve la estructura ANTES de elegir: las puertas de
     * entrada a la izquierda, las fases repartidas en las columnas en las
     * que de verdad se juegan —dos fases paralelas caen en la misma—, y
     * las salidas a la derecha.
     *
     * Un torneo es una marca; su plantilla es la forma con la que SUELE
     * jugarse, no una condena. La cuarta edición puede necesitar una fase
     * previa que la primera no tenía porque ahora se apunta el triple de
     * gente, y por eso se puede elegir otra.
     */
@endphp

<section x-show="isOpen('shape')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-sky-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-sky-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">02</span>
        <span class="text-[11px]">⑂</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-sky-300">La forma</h2>
        <span class="ml-auto text-[10px] text-slate-600">Con qué recorrido se juega esta edición</span>
    </div>

    <div class="p-4">

        @if ($competition)
            <p class="mb-3 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-[10px] leading-relaxed text-slate-500">
                La forma ya no se cambia: al crear esta edición se dibujó su recorrido
                con esta plantilla y se repartió a los competidores por él. Cambiarla
                ahora dejaría un cuadro hecho para otra gente. Para jugar con otra
                forma, crea una edición nueva copiando esta.
            </p>
        @endif


        {{-- ============ LA ELEGIDA ============ --}}

        <template x-if="template">
            <div class="rounded-2xl border border-sky-500/30 bg-sky-500/5 p-3">

                <div class="flex flex-wrap items-center gap-2">

                    <span class="text-[13px] font-black text-sky-200" x-text="template.name"></span>

                    <template x-if="template.is_default">
                        <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[9px] font-black text-sky-300">
                            la del torneo
                        </span>
                    </template>

                    <span class="ml-auto flex flex-wrap items-center gap-1.5 font-mono text-[9px] text-slate-500">
                        <span x-text="template.counts.starts + ' entradas'"></span>
                        <span class="text-slate-700">·</span>
                        <span x-text="template.counts.phases + ' fases'"></span>
                        <span class="text-slate-700">·</span>
                        <span x-text="template.counts.terminals + ' salidas'"></span>
                    </span>

                    @if (! $competition)
                        <button type="button" @click="templateOpen = !templateOpen"
                            class="rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-sky-500 hover:text-sky-300"
                            x-text="templateOpen ? 'cerrar' : 'cambiar de forma'"></button>
                    @endif
                </div>

                <p class="mt-1 text-[10px] leading-relaxed text-slate-500"
                    x-show="template.description" x-text="template.description"></p>


                {{-- El recorrido --}}

                @include('universes.competitions.partials.structure', ['fuente' => 'template'])

            </div>
        </template>

        <template x-if="!template">
            <p class="rounded-xl border border-dashed border-rose-500/40 bg-rose-500/5 px-3 py-4 text-center text-[11px] leading-relaxed text-rose-300">
                Este torneo no tiene ninguna plantilla disponible. Sin una forma no
                hay recorrido que jugar: crea una en la Biblioteca de Torneos.
            </p>
        </template>


        {{-- ============ LAS OTRAS ============ --}}

        @if (! $competition)
            <div x-show="templateOpen" x-cloak class="mt-3 space-y-2">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Las demás formas que puedes usar
                </p>

                <template x-for="tpl in templates" :key="'tpl' + tpl.id">
                    <button type="button" @click="pickTemplate(tpl.id)"
                        class="block w-full rounded-xl border p-3 text-left transition"
                        :class="Number(tpl.id) === Number(templateId)
                            ? 'border-sky-400/60 bg-sky-500/10'
                            : 'border-slate-800 bg-slate-950/50 hover:border-slate-600'">

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full border-2 transition"
                                :class="Number(tpl.id) === Number(templateId)
                                    ? 'border-sky-400 bg-sky-400'
                                    : 'border-slate-600'"></span>

                            <span class="text-[12px] font-black text-slate-200" x-text="tpl.name"></span>

                            <template x-if="tpl.is_default">
                                <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[9px] font-black text-sky-300">
                                    la del torneo
                                </span>
                            </template>

                            <span class="ml-auto font-mono text-[9px] text-slate-600">
                                <span x-text="tpl.counts.starts"></span>→<span x-text="tpl.counts.phases"></span>→<span x-text="tpl.counts.terminals"></span>
                            </span>
                        </div>

                        {{--
                            La forma de cada candidata, no solo su nombre.
                            Elegir por el nombre es elegir a ciegas.
                        --}}
                        <div class="mt-2 flex flex-wrap gap-1">
                            <template x-for="ph in tpl.phases" :key="'p' + tpl.id + '-' + ph.id">
                                <span class="rounded border border-slate-800 bg-slate-950 px-1.5 py-0.5">
                                    <span class="font-mono text-[8px] text-slate-600" x-text="'L' + ph.level"></span>
                                    <span class="text-[9px] text-slate-400" x-text="ph.shape"></span>
                                </span>
                            </template>

                            <template x-if="tpl.phases.length === 0">
                                <span class="text-[9px] text-rose-400">sin ninguna fase todavía</span>
                            </template>
                        </div>
                    </button>
                </template>

            </div>
        @endif

        <input type="hidden" name="tournament_template_id" :value="templateId">

        @if ($source)
            <input type="hidden" name="copied_from_instance_id" value="{{ $source->id }}">
        @endif

        <x-input-error :messages="$errors->get('tournament_template_id')" class="mt-2" />

    </div>
</section>

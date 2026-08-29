@php
    /*
     * EL RECORRIDO, DIBUJADO
     *
     * Las puertas de entrada a la izquierda, las fases repartidas en las
     * columnas en las que de verdad se juegan —dos fases paralelas caen en
     * la misma— y las salidas a la derecha.
     *
     * Las columnas salen de las conexiones, no del orden en que se
     * crearon: una lista mentiría sobre lo que pasa a la vez.
     *
     * Espera $fuente: el nombre de la expresión Alpine que tiene el brief
     * —normalmente `template`—, para poder dibujar tanto la elegida como
     * cualquier candidata sin duplicar esto.
     */

    $t = $fuente ?? 'template';
@endphp

<div class="mt-3 overflow-x-auto">
    <div class="flex min-w-max items-stretch gap-2">

        {{-- ============ POR DÓNDE SE ENTRA ============ --}}

        <div class="flex w-40 shrink-0 flex-col gap-1.5">

            <p class="text-[8px] font-black uppercase tracking-wider text-emerald-400">
                Entra
            </p>

            <template x-for="st in {{ $t }}.starts" :key="'st' + st.id">
                <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/5 px-2 py-1.5">
                    <p class="truncate text-[10px] font-black text-emerald-200" x-text="st.name"></p>
                    <p class="font-mono text-[9px] text-slate-500"
                        x-text="st.capacity ? st.capacity + ' plazas' : 'sin límite'"></p>
                </div>
            </template>

            <template x-if="{{ $t }}.starts.length === 0">
                <p class="rounded-lg border border-dashed border-rose-500/40 px-2 py-2 text-[9px] leading-relaxed text-rose-400">
                    Sin puertas de entrada: nadie puede empezar.
                </p>
            </template>
        </div>


        {{-- ============ LAS FASES, POR COLUMNAS ============ --}}

        <template x-for="(nivel, i) in {{ $t === 'template' ? 'columns' : 'columnsOf(' . $t . ')' }}" :key="'lvl' + i">
            <div class="flex w-44 shrink-0 flex-col gap-1.5">

                <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">
                    <span x-text="'Paso ' + (i + 1)"></span>
                    <span class="text-slate-700" x-show="nivel.length > 1"
                        x-text="'· ' + nivel.length + ' a la vez'"></span>
                </p>

                <template x-for="ph in nivel" :key="'ph' + ph.id">
                    <div class="rounded-lg border border-slate-700 bg-slate-950/70 px-2 py-1.5">

                        <p class="truncate text-[10px] font-black text-slate-200" x-text="ph.name"></p>

                        <p class="truncate text-[9px] text-cyan-300" x-text="ph.shape"></p>

                        {{--
                            El esquema en pequeño: una columna por ronda o
                            por grupo, con su altura. Es lo que hace que un
                            cuadro de 8 y uno de 32 no se parezcan.
                        --}}
                        <template x-if="ph.outline?.columns?.length">
                            <div class="mt-1 flex items-end gap-0.5">
                                <template x-for="(col, c) in ph.outline.columns" :key="'c' + ph.id + '-' + c">
                                    <span class="w-1.5 rounded-sm bg-cyan-500/40"
                                        :style="'height:' + Math.max(3, Math.min(22, col * 3)) + 'px'"
                                        :title="col"></span>
                                </template>
                            </div>
                        </template>

                        <p class="mt-0.5 font-mono text-[8px] text-slate-600"
                            x-show="ph.entries.length > 1"
                            x-text="ph.entries.length + ' entradas'"></p>
                    </div>
                </template>
            </div>
        </template>


        {{-- ============ DÓNDE SE ACABA ============ --}}

        <div class="flex w-40 shrink-0 flex-col gap-1.5">

            <p class="text-[8px] font-black uppercase tracking-wider text-violet-400">
                Sale
            </p>

            <template x-for="tm in {{ $t }}.terminals" :key="'tm' + tm.id">
                <div class="rounded-lg border border-violet-500/30 bg-violet-500/5 px-2 py-1.5">
                    <p class="truncate text-[10px] font-black text-violet-200" x-text="tm.name"></p>
                    <p class="font-mono text-[9px] text-slate-500"
                        x-text="tm.capacity ? tm.capacity + ' plazas' : tm.type"></p>
                </div>
            </template>

            <template x-if="{{ $t }}.terminals.length === 0">
                <p class="rounded-lg border border-dashed border-amber-500/40 px-2 py-2 text-[9px] leading-relaxed text-amber-400">
                    Sin salidas: nadie llega a ningún sitio.
                </p>
            </template>
        </div>

    </div>
</div>

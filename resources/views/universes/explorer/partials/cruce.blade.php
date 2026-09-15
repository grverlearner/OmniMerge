@php
    /*
     * Dos criterios a la vez.
     *
     * Una rejilla donde las filas son un criterio y las columnas otro, y cada
     * celda son los que cumplen las dos cosas. Es la pregunta que no se puede
     * hacer con un solo cuadro: «de la Hoja, ¿cuáles han ganado algo?».
     *
     * Las celdas vacias se quedan vacias y se ven: un hueco en la rejilla es
     * informacion -ahi no hay nadie- y taparlo seria mentir.
     */
@endphp

<div x-show="modo === 'cruce'" x-cloak>

    <template x-if="! cruzarCon">
        <div class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">
            <span class="inline-flex text-slate-700"><x-omni-icon name="cuadricula" size="h-9 w-9" /></span>
            <p class="mt-2 text-[13px] font-black text-white">Falta el segundo criterio</p>
            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                El cruce necesita dos: uno para las filas y otro para las columnas.
            </p>
            <button type="button" @click="panel = true"
                class="mt-3 rounded-xl border border-violet-500/40 bg-violet-500/10 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                Elegir el segundo
            </button>
        </div>
    </template>


    <template x-if="cruzarCon">
        <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

            <table class="w-full border-separate border-spacing-0">

                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 border-b border-r border-slate-800 bg-slate-950 p-2 text-left align-bottom">
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">Filas</span>
                            <span class="block text-[11px] font-black text-slate-300" x-text="criterioActual.etiqueta"></span>
                            <span class="mt-1 block text-[8px] font-black uppercase tracking-wider text-slate-600">Columnas</span>
                            <span class="block text-[11px] font-black text-slate-300" x-text="criterioCruce?.etiqueta"></span>
                        </th>

                        <template x-for="col in cruce.columnas" :key="col.valor">
                            <th class="border-b border-slate-800 bg-slate-950 p-2 align-bottom">
                                <span class="mx-auto mb-1 block h-1 w-8 rounded-full"
                                    :style="`background: ${col.color}`"></span>
                                <span class="block max-w-[120px] truncate text-[10px] font-black"
                                    :style="`color: ${col.color}`" x-text="col.valor"
                                    :title="col.valor"></span>
                                <span class="block font-mono text-[9px] text-slate-600" x-text="col.total"></span>
                            </th>
                        </template>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="fila in cruce.filas" :key="fila.valor">
                        <tr>
                            <th class="sticky left-0 z-10 border-b border-r border-slate-800 bg-slate-950 p-2 text-left">
                                <span class="flex items-center gap-1.5">
                                    <span class="h-6 w-1 shrink-0 rounded-full" :style="`background: ${fila.color}`"></span>
                                    <span class="min-w-0">
                                        <span class="block max-w-[150px] truncate text-[11px] font-black"
                                            :style="`color: ${fila.color}`" x-text="fila.valor" :title="fila.valor"></span>
                                        <span class="block font-mono text-[9px] text-slate-600" x-text="fila.total"></span>
                                    </span>
                                </span>
                            </th>

                            <template x-for="col in cruce.columnas" :key="col.valor">
                                <td class="border-b border-l border-slate-800/60 p-1.5 align-top"
                                    :style="celda(fila.valor, col.valor).length > 0
                                        ? `background: linear-gradient(135deg, ${fila.color}14, ${col.color}14)`
                                        : ''">

                                    <template x-if="celda(fila.valor, col.valor).length === 0">
                                        <span class="block py-2 text-center font-mono text-[10px] text-slate-800">·</span>
                                    </template>

                                    <template x-if="celda(fila.valor, col.valor).length > 0">
                                        <div class="flex flex-wrap gap-1">
                                            @include('universes.explorer.partials.caras', [
                                                'lista' => 'celda(fila.valor, col.valor)',
                                                'etiqueta' => 'nunca',
                                            ])
                                        </div>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>
</div>

@php
    /*
     * Todo el universo de una vez.
     *
     * Sin cuadros, sin cabeceras: solo las caras, ordenadas por grupo, de modo
     * que los colores se agrupen en bandas. Es la unica forma de ver de un
     * vistazo si el mundo esta repartido o si hay un bloque que se lo come.
     */
@endphp

<div x-show="modo === 'todo'" x-cloak class="space-y-2">

    {{-- ---------- LA LEYENDA, QUE TAMBIEN ES EL FOCO ---------- --}}

    <div class="flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-800 bg-slate-900/40 p-2">

        <template x-for="grupo in grupos" :key="grupo.valor">
            <button type="button" @click="foco = (foco === grupo.valor ? null : grupo.valor)"
                class="flex items-center gap-1.5 rounded-lg border px-2 py-1 transition"
                :style="foco === grupo.valor
                    ? `border-color: ${grupo.color}; background: ${grupo.color}22`
                    : 'border-color: #1e293b'">

                <span class="h-2.5 w-2.5 rounded-sm" :style="`background: ${grupo.color}`"></span>
                <span class="max-w-[140px] truncate text-[10px] font-black text-slate-300" x-text="grupo.valor"></span>
                <span class="font-mono text-[9px] text-slate-600" x-text="grupo.miembros.length"></span>
            </button>
        </template>

        <template x-if="sinDato.length > 0">
            <span class="flex items-center gap-1.5 rounded-lg border border-dashed border-slate-800 px-2 py-1">
                <span class="h-2.5 w-2.5 rounded-sm bg-slate-700"></span>
                <span class="text-[10px] font-black text-slate-500">Sin dato</span>
                <span class="font-mono text-[9px] text-slate-600" x-text="sinDato.length"></span>
            </span>
        </template>
    </div>


    {{-- ---------- TODAS LAS CARAS ---------- --}}

    <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-3">

        <div class="grid gap-1.5"
            :style="`grid-template-columns: repeat(auto-fill, ${lado}px)`">

            <template x-for="e in panorama" :key="e.id + '@' + e.__grupo">

                <button type="button" @click="elegida = e"
                    class="group relative block transition"
                    :class="apagada(e) || (foco && ! grupoDe(e).includes(foco)) ? 'opacity-15 grayscale' : ''"
                    :title="`${e.nombre} · ${grupoDe(e).join(', ') || 'Sin dato'}`">

                    <span class="block rounded-xl p-[2px]" :style="`background: ${aro(e)}`">
                        <span class="block overflow-hidden rounded-[10px] bg-slate-950"
                            :style="`width: ${lado}px; height: ${lado}px`">

                            <template x-if="e.img">
                                <img :src="e.img" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-110">
                            </template>

                            <template x-if="! e.img">
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                            </template>
                        </span>
                    </span>

                    <template x-if="e.titulos > 0">
                        <span class="absolute -right-1 -top-1 rounded-full bg-amber-400 px-1 font-mono text-[9px] font-black text-slate-950"
                            x-text="e.titulos"></span>
                    </template>
                </button>
            </template>
        </div>
    </div>
</div>

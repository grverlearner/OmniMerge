{{--
    Una cara en la sala. Va dentro de un x-for con la variable `c`.

    La imagen es la de la versión con la que saldría en ESTE torneo, no la
    de siempre. Arriba a la izquierda, su puerta; arriba a la derecha, si
    se decidió a mano; abajo, el nombre de la versión cuando la hay.
--}}

<button type="button" @click="cardClick(c)"
    class="group relative block w-full overflow-hidden rounded-xl border bg-slate-950 text-left transition hover:-translate-y-0.5"
    :style="isIn(c.id)
        ? `border-color: ${starts.length > 1 && calc.doorOf[c.id] ? doorColor(calc.doorOf[c.id]) + '99' : '#10b98155'}`
        : 'border-color: #1e293b'"
    :title="c.name + ' · ' + reasonText(c.id)">

    <span class="relative block aspect-square overflow-hidden bg-slate-900">
        <template x-if="face(c).image_url">
            <img :src="face(c).image_url" alt="" loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                :class="isIn(c.id) ? '' : 'opacity-30 grayscale'">
        </template>

        <template x-if="! face(c).image_url">
            <span class="flex h-full w-full items-center justify-center font-mono text-[15px] font-black text-slate-600"
                x-text="initials(c.name)"></span>
        </template>

        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/75 to-transparent px-1.5 pb-1 pt-6">
            <span class="block truncate text-[10px] font-black leading-tight text-white" x-text="c.name"></span>
            <span x-show="face(c).version_id" class="block truncate text-[9px] font-bold leading-tight"
                :style="`color: ${fromTone(face(c).from)}`" x-text="face(c).name"></span>
        </span>

        <span x-show="isIn(c.id) && starts.length > 1 && calc.doorOf[c.id]"
            class="absolute left-1 top-1 rounded-md px-1 font-mono text-[9px] font-black text-slate-950"
            :style="`background-color: ${doorColor(calc.doorOf[c.id])}`"
            x-text="doorShort(calc.doorOf[c.id])"></span>

        <span x-show="isIn(c.id) && starts.length && ! calc.doorOf[c.id]"
            class="absolute left-1 top-1 rounded-md bg-amber-400 px-1 text-[8px] font-black uppercase text-slate-950"
            title="Cumple, pero no cabe en ninguna puerta">sin puerta</span>

        <span x-show="handOf(c.id)"
            class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full text-slate-950"
            :class="handOf(c.id) === 'IN' ? 'bg-emerald-400' : 'bg-rose-400'"
            :title="handOf(c.id) === 'IN' ? 'Metido a mano' : 'Sacado a mano'">
            <template x-if="handOf(c.id) === 'IN'"><x-omni-icon name="check" size="h-3 w-3" /></template>
            <template x-if="handOf(c.id) === 'OUT'"><x-omni-icon name="cerrar" size="h-3 w-3" /></template>
        </span>

        <span x-show="isIn(c.id) && face(c).image_missing"
            class="absolute right-1 top-6 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-slate-950"
            title="Sale sin imagen">
            <x-omni-icon name="aviso" size="h-2.5 w-2.5" />
        </span>
    </span>
</button>

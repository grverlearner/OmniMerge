@php
    /*
     * Moverse entre fases, dentro de la estructura.
     *
     * Antes esto era un desplegable en la esquina de arriba. Funcionaba,
     * pero obligaba a subir a una esquina para algo que se hace todo el
     * rato, y sobre todo no decía DÓNDE estabas: un desplegable con cinco
     * nombres no enseña que la que miras es la tercera de cinco.
     *
     * Aquí cada fase es una ficha, con su color de nivel y su silueta. La
     * que se mira va marcada, y las flechas saltan a la de al lado.
     *
     * El orden es el de la lista de fases, no el del grafo: el recorrido
     * puede tener bifurcaciones y entonces «la siguiente» no existe como
     * una sola cosa. Para seguir el camino de verdad están las fichas de
     * antes y después, que sí salen del grafo.
     */
@endphp

<div class="mb-2 flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 p-1.5">

    <button type="button" @click="step(-1)" :disabled="nodes.length < 2"
        class="shrink-0 rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100 disabled:opacity-30"
        title="La fase anterior">◀</button>

    <div class="arena-scroll flex min-w-0 flex-1 gap-1 overflow-x-auto">

        <template x-for="node in nodes" :key="'nav' + node.key">
            <button type="button" @click="goTo(node.key)"
                class="flex shrink-0 items-center gap-1.5 rounded-lg border px-2 py-1 transition"
                :class="node.key === focus
                    ? colorOf(node.key).border + ' ' + colorOf(node.key).soft + ' ring-1 ' + colorOf(node.key).ring
                    : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                <span class="h-4 w-1 shrink-0 rounded-full" :class="colorOf(node.key).dot"></span>

                <span class="flex flex-col items-start">
                    <span class="max-w-[130px] truncate text-[10px] font-black"
                        :class="node.key === focus ? 'text-slate-100' : 'text-slate-400'"
                        x-text="node.name"></span>

                    <span class="max-w-[130px] truncate text-[8px]"
                        :class="colorOf(node.key).text"
                        x-text="node.phase_type_label"></span>
                </span>

                {{-- Lo que está suelto, sin tener que entrar a mirarlo --}}

                <template x-if="unconnectedExits(node).length || emptyEntries(node).length">
                    <span class="shrink-0 text-[9px] text-amber-400"
                        :title="'Tiene cabos sueltos'">▲</span>
                </template>
            </button>
        </template>

        <template x-if="nodes.length === 0">
            <p class="px-2 py-1 text-[10px] text-slate-600">Todavía no hay fases.</p>
        </template>

    </div>

    <span class="shrink-0 font-mono text-[9px] text-slate-600"
        x-show="nodes.length > 1"
        x-text="(focusIndex + 1) + '/' + nodes.length"></span>

    <button type="button" @click="step(1)" :disabled="nodes.length < 2"
        class="shrink-0 rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100 disabled:opacity-30"
        title="La fase siguiente">▶</button>

</div>

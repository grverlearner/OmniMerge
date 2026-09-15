@php
    /*
     * El cajon de mandos.
     *
     * Vive fuera de la pantalla y se abre cuando hace falta, porque lo que
     * tiene que ocupar el sitio es el mapa. Dentro esta todo: por que criterio
     * se reparte el mundo, con cual se cruza, en que orden y de que tamaño.
     *
     * Cada criterio enseña su cobertura ANTES de elegirlo. Elegir «aldea» sin
     * saber que solo le consta a cuatro de veintidos es elegir a ciegas.
     */
@endphp

{{-- El velo --}}
<div x-show="panel" x-cloak x-transition.opacity @click="panel = false"
    class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm"></div>

<aside x-show="panel" x-cloak
    x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full"
    x-transition:leave="transition duration-150" x-transition:leave-end="translate-x-full"
    class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md flex-col border-l border-slate-800 bg-slate-950">

    <header class="flex items-center gap-2 border-b border-slate-800 px-4 py-3">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
            <x-omni-icon name="controles" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Cómo se reparte el mundo</h2>
            <p class="text-[10px] text-slate-500">Cambiar de criterio vuelve a repartir a todos.</p>
        </div>

        <button type="button" @click="panel = false"
            class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:text-white">
            <x-omni-icon name="cerrar" size="h-4 w-4" />
        </button>
    </header>


    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3">

        {{-- ---------- CRITERIO PRINCIPAL ---------- --}}

        <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Separar por</p>

        <div class="mt-2 space-y-1.5">
            <template x-for="c in criterios" :key="c.clave">

                <button type="button" @click="criterio = c.clave; foco = null"
                    class="block w-full rounded-xl border p-2.5 text-left transition"
                    :class="criterio === c.clave
                        ? 'border-violet-500 bg-violet-500/10'
                        : 'border-slate-800 bg-slate-900/40 hover:border-slate-700'">

                    <span class="flex items-center gap-2">
                        <span class="min-w-0 flex-1 truncate text-[12px] font-black"
                            :class="criterio === c.clave ? 'text-violet-200' : 'text-slate-200'"
                            x-text="c.etiqueta"></span>

                        <span class="shrink-0 rounded px-1.5 py-0.5 font-mono text-[9px] font-black"
                            :class="c.familia === 'atributo'
                                ? 'bg-slate-800 text-slate-400'
                                : 'bg-slate-900 text-slate-600'"
                            x-text="c.familia === 'atributo' ? 'atributo' : 'propio'"></span>
                    </span>

                    <span class="mt-1 block text-[10px] leading-3 text-slate-500" x-text="c.ayuda"></span>

                    {{-- La cobertura, dibujada: cuanto del mundo reparte --}}
                    <span class="mt-1.5 flex items-center gap-2">
                        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-950">
                            <span class="block h-full rounded-full transition-all"
                                :class="c.cobertura === total ? 'bg-emerald-500' : 'bg-amber-500'"
                                :style="`width: ${total ? Math.round(c.cobertura / total * 100) : 0}%`"></span>
                        </span>

                        <span class="shrink-0 font-mono text-[9px]"
                            :class="c.cobertura === total ? 'text-emerald-400' : 'text-amber-400'">
                            <span x-text="c.cobertura"></span>/<span x-text="total"></span>
                        </span>

                        {{--
                            Un criterio con un solo valor no reparte nada: forma
                            un cuadro con todo el mundo dentro. Se deja en la
                            lista porque saberlo tambien es informacion, pero se
                            dice, para que nadie lo elija esperando un mapa.
                        --}}
                        <span class="shrink-0 font-mono text-[9px]"
                            :class="c.valores > 1 ? 'text-slate-600' : 'text-amber-500'"
                            :title="c.valores > 1 ? '' : 'Todos tienen el mismo valor: el mapa saldría de una sola pieza'">
                            <span x-text="c.valores > 1 ? c.valores + ' cuadros' : 'no reparte'"></span>
                        </span>

                        <template x-if="c.compartidos > 0">
                            <span class="shrink-0 rounded bg-violet-500/15 px-1 font-mono text-[9px] font-black text-violet-300"
                                :title="`${c.compartidos} en más de un cuadro`">
                                ↔<span x-text="c.compartidos"></span>
                            </span>
                        </template>
                    </span>
                </button>
            </template>
        </div>


        {{-- ---------- CRUZAR CON ---------- --}}

        <p class="mt-4 text-[9px] font-black uppercase tracking-wider text-slate-600">
            Cruzar con <span class="text-slate-700">(para el modo cruce)</span>
        </p>

        <div class="mt-2 flex flex-wrap gap-1.5">

            <button type="button" @click="cruzarCon = ''"
                class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                :class="cruzarCon === '' ? 'border-violet-500 bg-violet-500/10 text-violet-200' : 'border-slate-800 text-slate-500'">
                Ninguno
            </button>

            <template x-for="c in criterios" :key="'x' + c.clave">
                <button type="button" @click="cruzarCon = c.clave; modo = 'cruce'"
                    x-show="c.clave !== criterio"
                    class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                    :class="cruzarCon === c.clave
                        ? 'border-violet-500 bg-violet-500/10 text-violet-200'
                        : 'border-slate-800 text-slate-400 hover:border-slate-700'"
                    x-text="c.etiqueta"></button>
            </template>
        </div>


        {{-- ---------- ORDEN Y TAMAÑO ---------- --}}

        <p class="mt-4 text-[9px] font-black uppercase tracking-wider text-slate-600">Orden de los cuadros</p>

        <div class="mt-2 flex flex-wrap gap-1.5">
            <template x-for="[valor, texto] in [['poblacion','Del más grande al más pequeño'],['nombre','Por nombre'],['titulos','Por títulos ganados']]"
                :key="valor">
                <button type="button" @click="orden = valor"
                    class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                    :class="orden === valor ? 'border-violet-500 bg-violet-500/10 text-violet-200' : 'border-slate-800 text-slate-400'"
                    x-text="texto"></button>
            </template>
        </div>


        <p class="mt-4 text-[9px] font-black uppercase tracking-wider text-slate-600">Tamaño de las caras</p>

        <div class="mt-2 flex items-center gap-3">
            <input type="range" min="28" max="96" step="4" x-model.number="lado"
                class="h-1.5 flex-1 cursor-pointer appearance-none rounded-full bg-slate-800 accent-violet-500">
            <span class="w-12 shrink-0 text-right font-mono text-[11px] font-black text-slate-400"
                x-text="lado + 'px'"></span>
        </div>


        {{-- ---------- LO QUE SE ENSEÑA ---------- --}}

        <p class="mt-4 text-[9px] font-black uppercase tracking-wider text-slate-600">Qué se enseña</p>

        <div class="mt-2 space-y-1.5">

            <label class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/40 px-2.5 py-2">
                <input type="checkbox" x-model="verSinDato"
                    class="rounded border-slate-700 bg-slate-950 text-violet-500 focus:ring-violet-500">
                <span class="min-w-0 flex-1">
                    <span class="block text-[11px] font-black text-slate-200">El cuadro «Sin dato»</span>
                    <span class="block text-[9px] leading-3 text-slate-600">
                        A quién no le consta el criterio. Ocultarlo no los borra.
                    </span>
                </span>
            </label>

            <label class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/40 px-2.5 py-2">
                <input type="checkbox" x-model="soloCoincidencias"
                    class="rounded border-slate-700 bg-slate-950 text-violet-500 focus:ring-violet-500">
                <span class="min-w-0 flex-1">
                    <span class="block text-[11px] font-black text-slate-200">Al buscar, esconder el resto</span>
                    <span class="block text-[9px] leading-3 text-slate-600">
                        Por defecto se apagan pero se quedan, para no perder la forma del mapa.
                    </span>
                </span>
            </label>
        </div>
    </div>


    <footer class="border-t border-slate-800 px-4 py-2.5">
        <button type="button" @click="panel = false"
            class="w-full rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
            Ver el mapa
        </button>
    </footer>
</aside>

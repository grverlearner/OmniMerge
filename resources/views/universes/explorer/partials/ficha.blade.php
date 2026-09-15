@php
    /*
     * La ficha de una cara, sin salir del mapa.
     *
     * Pinchar una entidad no te lleva a otra pantalla: se abre aqui, con su
     * imagen grande y sus atributos. Y cada atributo es un boton que vuelve a
     * repartir el mundo por el -desde Kakashi se salta a «reparte a todos por
     * aldea»-, que es la forma natural de explorar: tirando del hilo de lo que
     * acabas de ver.
     */
@endphp

<div x-show="elegida" x-cloak x-transition.opacity @click="elegida = null"
    class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm"></div>

<aside x-show="elegida" x-cloak
    x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full"
    x-transition:leave="transition duration-150" x-transition:leave-end="translate-x-full"
    class="fixed right-0 top-0 z-50 flex h-full w-full max-w-sm flex-col border-l border-slate-800 bg-slate-950">

    <template x-if="elegida">
        <div class="flex h-full min-h-0 flex-col">

            <div class="relative h-56 shrink-0 overflow-hidden bg-slate-900">

                <template x-if="elegida.img">
                    <img :src="elegida.img" alt="" class="h-full w-full object-cover">
                </template>

                <template x-if="! elegida.img">
                    <span class="flex h-full w-full items-center justify-center text-6xl text-slate-800">◍</span>
                </template>

                <span class="absolute inset-x-0 bottom-0 h-24"
                    style="background: linear-gradient(to top, #020617, transparent)"></span>

                <button type="button" @click="elegida = null"
                    class="absolute right-2 top-2 rounded-lg bg-slate-950/80 p-1.5 text-slate-400 transition hover:text-white">
                    <x-omni-icon name="cerrar" size="h-4 w-4" />
                </button>

                <div class="absolute inset-x-0 bottom-0 p-3">
                    <p class="text-[15px] font-black leading-tight text-white" x-text="elegida.nombre"></p>
                    <p class="text-[10px] font-bold text-slate-400">
                        <span x-text="elegida.tipo || 'Sin tipo'"></span> ·
                        <span x-text="elegida.estado"></span>
                    </p>
                </div>
            </div>


            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-3">

                {{-- Lo que ha hecho compitiendo --}}
                <div class="grid grid-cols-3 gap-1.5">
                    <template x-for="[etiqueta, valor, tono] in [
                        ['Competiciones', elegida.jugadas, '#a78bfa'],
                        ['Títulos', elegida.titulos, '#fbbf24'],
                        ['Trofeos', elegida.trofeos, '#34d399'],
                    ]" :key="etiqueta">
                        <span class="rounded-xl border border-slate-800 bg-slate-900/50 px-2 py-1.5 text-center">
                            <span class="block font-mono text-[17px] font-black"
                                :style="`color: ${valor > 0 ? tono : '#475569'}`" x-text="valor"></span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600"
                                x-text="etiqueta"></span>
                        </span>
                    </template>
                </div>


                {{-- Dónde cae con el criterio de ahora --}}
                <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-2.5">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Ahora mismo está en
                    </p>

                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <template x-if="grupoDe(elegida).length === 0">
                            <span class="text-[11px] text-slate-500">
                                En «Sin dato»: no le consta
                                «<span x-text="criterioActual.etiqueta"></span>».
                            </span>
                        </template>

                        <template x-for="v in grupoDe(elegida)" :key="v">
                            <button type="button" @click="foco = v; elegida = null"
                                class="rounded-lg border px-2 py-1 text-[11px] font-black transition hover:brightness-125"
                                :style="`border-color: ${color(v)}66; color: ${color(v)}; background: ${color(v)}18`"
                                x-text="v"></button>
                        </template>
                    </div>
                </div>


                {{-- Sus atributos: cada uno reparte el mundo --}}
                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Sus atributos
                    </p>

                    <template x-if="Object.keys(elegida.attrs).length === 0">
                        <p class="mt-1 text-[10px] leading-4 text-slate-600">
                            No se le copió ningún atributo al importarla. Los atributos viajan
                            desde la Biblioteca en el momento de importar, y después no se
                            sincronizan.
                        </p>
                    </template>

                    <div class="mt-1.5 space-y-1.5">
                        <template x-for="[nombre, valores] in Object.entries(elegida.attrs)" :key="nombre">
                            <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-2">

                                <button type="button" @click="criterio = nombre; foco = null; elegida = null"
                                    class="flex w-full items-center gap-1.5 text-left transition hover:text-violet-300"
                                    :title="`Repartir todo el universo por ${nombre}`">

                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-300"
                                        x-text="nombre"></span>

                                    <span class="shrink-0 text-[9px] font-black uppercase tracking-wider text-violet-400">
                                        repartir por esto
                                    </span>
                                </button>

                                <div class="mt-1 flex flex-wrap gap-1">
                                    <template x-for="v in valores" :key="v">
                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold"
                                            :style="`color: ${color(v)}; background: ${color(v)}18`"
                                            x-text="v"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>


            <footer class="shrink-0 border-t border-slate-800 p-3">
                <a :href="elegida.url"
                    class="block w-full rounded-xl bg-violet-500 px-4 py-2.5 text-center text-[12px] font-black text-white transition hover:bg-violet-400">
                    Ver su ficha completa
                </a>
            </footer>
        </div>
    </template>
</aside>

@php
    /*
     * El modo de entrada: un cuadro por cada valor del criterio.
     *
     * El tamaño del cuadro no es decorativo: un grupo que se lleva la mitad
     * del mundo ocupa la mitad del mapa. Asi la forma del reparto se ve antes
     * de leer un solo numero.
     *
     * Dentro de cada cuadro, primero los representativos -los que mas han
     * hecho compitiendo- en grande, y detras todos los demas.
     */
@endphp

<div x-show="modo === 'cuadros'" x-cloak class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-6">

    <template x-for="grupo in grupos" :key="grupo.valor">

        <section class="overflow-hidden rounded-2xl border bg-slate-900/40 transition"
            :class="foco && foco !== grupo.valor ? 'opacity-30' : ''"
            :style="`border-color: ${grupo.color}55; grid-column: span ${grupo.ancho} / span ${grupo.ancho}`">

            {{-- ---------- CABECERA DEL CUADRO ---------- --}}

            <header class="relative overflow-hidden px-3 py-2"
                :style="`background: linear-gradient(120deg, ${grupo.color}22, transparent 70%)`">

                <div class="flex items-center gap-2">

                    <span class="h-7 w-1.5 shrink-0 rounded-full"
                        :style="`background: ${grupo.color}`"></span>

                    <button type="button" @click="foco = (foco === grupo.valor ? null : grupo.valor)"
                        class="min-w-0 flex-1 text-left"
                        :title="foco === grupo.valor ? 'Quitar el foco' : 'Mirar solo este cuadro'">

                        <span class="block truncate text-[13px] font-black leading-tight"
                            :style="`color: ${grupo.color}`"
                            x-text="grupo.valor"></span>

                        <span class="block font-mono text-[9px] text-slate-500">
                            <span x-text="grupo.miembros.length"></span> ·
                            <span x-text="grupo.porcentaje + '%'"></span>
                            <template x-if="grupo.compartidos > 0">
                                <span class="text-slate-600">
                                    · <span x-text="grupo.compartidos"></span> compartid<span
                                        x-text="grupo.compartidos === 1 ? 'a' : 'as'"></span>
                                </span>
                            </template>
                        </span>
                    </button>

                    {{-- La barra de tamaño del grupo, comparada con el mayor --}}
                    <span class="hidden h-1.5 w-16 shrink-0 overflow-hidden rounded-full bg-slate-950 sm:block">
                        <span class="block h-full rounded-full"
                            :style="`width: ${grupo.relativo}%; background: ${grupo.color}`"></span>
                    </span>
                </div>
            </header>


            {{-- ---------- LOS REPRESENTATIVOS ---------- --}}

            <template x-if="grupo.representativos.length > 0">
                <div class="flex items-center gap-2 border-y border-slate-800/70 bg-slate-950/40 px-3 py-2">

                    <span class="shrink-0 text-[8px] font-black uppercase leading-3 tracking-wider text-slate-600">
                        Los que<br>más han<br>hecho
                    </span>

                    <div class="flex flex-wrap gap-1.5">
                        @include('universes.explorer.partials.caras', [
                            'lista' => 'grupo.representativos',
                            'etiqueta' => 'nunca',
                        ])
                    </div>
                </div>
            </template>


            {{-- ---------- TODOS ---------- --}}

            <div class="grid gap-1.5 p-3"
                :style="`grid-template-columns: repeat(auto-fill, ${lado}px)`">

                @include('universes.explorer.partials.caras', ['lista' => 'grupo.resto'])
            </div>
        </section>
    </template>


    {{-- ---------- SIN DATO ---------- --}}

    <template x-if="verSinDato && sinDato.length > 0">

        <section class="overflow-hidden rounded-2xl border border-dashed border-slate-800 bg-slate-950/40"
            :class="foco ? 'opacity-30' : ''"
            :style="`grid-column: span ${anchoDe(sinDato.length)} / span ${anchoDe(sinDato.length)}`">

            <header class="px-3 py-2">
                <span class="block text-[13px] font-black text-slate-500">Sin dato</span>

                {{--
                    Honestidad: no es que no pertenezcan a ningun sitio, es que
                    a esta copia no le consta el dato. Y el porque cambia: un
                    atributo pudo no viajar al importar; el tipo, simplemente,
                    nunca se puso.
                --}}
                <span class="block text-[9px] leading-3 text-slate-600">
                    <span x-text="sinDato.length"></span> sin
                    «<span x-text="criterioActual.etiqueta"></span>».
                    <span x-show="criterioActual.familia === 'atributo'">
                        El atributo no viajó al importarlas.
                    </span>
                    <span x-show="criterioActual.familia !== 'atributo'">
                        Nunca se les puso.
                    </span>
                </span>
            </header>

            <div class="grid gap-1.5 px-3 pb-3"
                :style="`grid-template-columns: repeat(auto-fill, ${lado}px)`">

                @include('universes.explorer.partials.caras', ['lista' => 'sinDato'])
            </div>
        </section>
    </template>
</div>

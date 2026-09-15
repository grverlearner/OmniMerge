@php
    /*
     * Quien esta en dos sitios a la vez.
     *
     * Un atributo puede traer varios valores -dos aldeas, dos animes- y eso
     * es justo lo que un cuadro por valor no puede enseñar: si sale en los
     * dos cuadros, parecen dos entidades distintas.
     *
     * Aqui cada valor es un nodo y cada puente es la gente que comparten. El
     * grosor del puente es cuantos, y el color va de un valor al otro, para
     * que se vea de donde a donde va sin leer nada.
     */
@endphp

<div x-show="modo === 'compartidos'" x-cloak class="space-y-2">

    {{-- ---------- CUANDO NADIE COMPARTE ---------- --}}

    <template x-if="puentes.length === 0">
        <div class="rounded-2xl border border-dashed border-slate-800 py-12 text-center">

            <span class="inline-flex text-slate-700"><x-omni-icon name="grafo" size="h-9 w-9" /></span>

            <p class="mt-2 text-[13px] font-black text-white">
                Con «<span x-text="criterioActual.etiqueta"></span>» nadie está en dos sitios
            </p>

            <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                Cada entidad cae en un solo cuadro, así que no hay nada que cruzar.
                Esto pasa siempre con los criterios de sí o no, y con los atributos
                que solo admiten un valor.
            </p>

            {{-- En vez de dejarlo en un no: los criterios que si reparten doble --}}
            <template x-if="criteriosQueComparten.length > 0">
                <div class="mt-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Estos sí tienen gente en varios cuadros
                    </p>

                    <div class="mt-2 flex flex-wrap justify-center gap-1.5">
                        <template x-for="c in criteriosQueComparten" :key="c.clave">
                            <button type="button" @click="criterio = c.clave; foco = null"
                                class="rounded-xl border border-violet-500/40 bg-violet-500/10 px-2.5 py-1.5 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                <span x-text="c.etiqueta"></span>
                                <span class="font-mono text-[9px] opacity-70" x-text="'· ' + c.compartidos"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>


    {{-- ---------- EL DIAGRAMA ---------- --}}

    <template x-if="puentes.length > 0">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-3">

            <div class="mb-1 flex flex-wrap items-baseline gap-x-2">
                <h2 class="text-[13px] font-black text-white">Los puentes del mundo</h2>
                <p class="text-[10px] text-slate-500">
                    <span x-text="compartidas.length"></span>
                    entidad<span x-text="compartidas.length === 1 ? '' : 'es'"></span>
                    en más de un cuadro de «<span x-text="criterioActual.etiqueta"></span>».
                    El grosor es cuántas comparte cada par.
                </p>
            </div>

            {{--
                El dibujo se arma como marcado y se inyecta.

                Dentro de un <svg> los hijos estan en el espacio de nombres de
                SVG, asi que un <template> ahi NO es un template de HTML y
                `x-for` no tiene nada que clonar: la seccion salia vacia. Al
                asignar el <svg> entero al innerHTML de un <div>, el navegador
                usa el parser de HTML, que si sabe cambiar de espacio de
                nombres al entrar en <svg>.
            --}}
            <div class="overflow-x-auto" x-html="diagramaSvg"></div>

        </section>
    </template>


    {{-- ---------- CADA PUENTE, CON SUS CARAS ---------- --}}

    <template x-if="puentes.length > 0">
        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">

            <template x-for="p in puentes" :key="p.a + '||' + p.b">

                <article class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">

                    <header class="flex items-center gap-2 px-3 py-2"
                        :style="`background: linear-gradient(90deg, ${p.colorA}22, ${p.colorB}22)`">

                        <span class="truncate text-[11px] font-black" :style="`color: ${p.colorA}`"
                            x-text="p.a"></span>

                        <span class="shrink-0 text-slate-600">
                            <x-omni-icon name="flecha-derecha" size="h-3 w-3" />
                        </span>

                        <span class="truncate text-[11px] font-black" :style="`color: ${p.colorB}`"
                            x-text="p.b"></span>

                        <span class="ml-auto shrink-0 font-mono text-[11px] font-black text-slate-400"
                            x-text="p.quienes.length"></span>
                    </header>

                    <div class="flex flex-wrap gap-1.5 p-3">
                        @include('universes.explorer.partials.caras', ['lista' => 'p.quienes'])
                    </div>
                </article>
            </template>
        </div>
    </template>
</div>

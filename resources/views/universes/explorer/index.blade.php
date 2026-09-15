@php
    /*
     * El mapa del Universo.
     *
     * Esta pantalla no lista entidades: las reparte. Cada entidad es una cara,
     * y las caras se agrupan en cuadros segun el criterio que se elija. Elegir
     * «aldea» forma los cuadros de las aldeas; elegir «¿tiene título?» los
     * parte en dos. Todo el mundo se recoloca delante de los ojos.
     *
     * Por eso el reparto se hace en el navegador con el censo que manda el
     * controlador: una sola consulta, y despues cambiar de criterio es
     * instantaneo. Pedir al servidor un reparto nuevo cada vez mataria la
     * sensacion que hace util esta pantalla.
     *
     * Cuatro formas de mirar el mismo reparto:
     *
     *   cuadros      un cuadro por valor, del tamaño de su poblacion
     *   cruce        dos criterios a la vez, en rejilla
     *   compartidos  quien esta en dos cuadros y con quien los comparte
     *   todo         el universo entero sin cuadros, teñido por grupo
     *
     * Ver docs/md/69-Universos-Explorar.md
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark" :bleed="true">

    <x-slot name="header">Explorar</x-slot>


    @if ($totalEntidades === 0)

        <section class="rounded-2xl border border-dashed border-slate-800 py-20 text-center">

            <span class="inline-flex text-slate-700"><x-omni-icon name="globo" size="h-10 w-10" /></span>

            <h2 class="mt-3 text-[15px] font-black text-white">Este mundo todavía está vacío</h2>

            <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                El mapa dibuja a los habitantes del universo. Trae entidades desde tu
                Biblioteca y aparecerán aquí, repartidas por lo que quieras.
            </p>

            @can('update', $universe)
                <a href="{{ route('universes.entities.create', $universe) }}"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
                    <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                    Traer desde la Biblioteca
                </a>
            @endcan
        </section>

    @else

        <div x-data="mapaDelUniverso(@js([
            'censo' => $censo,
            'criterios' => $criterios,
            'porDefecto' => $porDefecto,
            'modoPorDefecto' => $universe->ajustes()->get('explorer_default_mode'),
            'total' => $totalEntidades,
        ]))" class="space-y-2">


            {{-- ===================================================== --}}
            {{-- LA BARRA: LO MINIMO PARA QUE EL MAPA SEA EL PROTAGONISTA --}}
            {{-- ===================================================== --}}

            <header class="sticky top-2 z-30 rounded-2xl border border-slate-800 bg-slate-900/95 px-2.5 py-2 backdrop-blur">

                <div class="flex flex-wrap items-center gap-2">

                    {{-- Qué se está mirando --}}
                    <button type="button" @click="panel = true"
                        class="group flex min-w-0 items-center gap-2 rounded-xl px-1 py-0.5 text-left transition hover:bg-slate-800/60"
                        title="Cambiar el criterio">

                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                            <x-omni-icon name="globo" size="h-4 w-4" />
                        </span>

                        <span class="min-w-0">
                            <span class="block text-[9px] font-black uppercase leading-3 tracking-[0.18em] text-slate-600">
                                {{ $universe->name }} · repartido por
                            </span>

                            <span class="flex items-center gap-1.5">
                                <span class="truncate text-[14px] font-black leading-tight text-white"
                                    x-text="criterioActual.etiqueta"></span>
                                <span class="shrink-0 text-slate-600 transition group-hover:text-violet-400">
                                    <x-omni-icon name="chevron-derecha" size="h-3 w-3" />
                                </span>
                            </span>
                        </span>
                    </button>

                    {{-- Cómo ha quedado el reparto --}}
                    <span class="hidden shrink-0 items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-1.5 font-mono text-[10px] sm:flex">
                        <span class="font-black text-violet-300" x-text="visibles.length"></span>
                        <span class="text-slate-600">en</span>
                        <span class="font-black text-slate-300" x-text="grupos.length"></span>
                        <span class="text-slate-600">cuadros</span>

                        <template x-if="sinDato.length > 0">
                            <span class="text-amber-400/80" :title="`${sinDato.length} sin este dato`">
                                · <span x-text="sinDato.length"></span> sin dato
                            </span>
                        </template>

                        <template x-if="compartidas.length > 0">
                            <button type="button" @click="modo = 'compartidos'"
                                class="text-violet-400 underline transition hover:text-violet-200"
                                :title="`${compartidas.length} están en más de un cuadro`">
                                · ↔<span x-text="compartidas.length"></span>
                            </button>
                        </template>
                    </span>


                    <span class="flex-1"></span>


                    {{-- Buscar: apaga, no borra --}}
                    <label class="relative w-full min-w-[150px] sm:w-52">
                        <span class="sr-only">Buscar en el mapa</span>
                        <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" x-model="busqueda" placeholder="Buscar en el mapa…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 py-1.5 pl-8 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>


                    {{-- Forma de mirar --}}
                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ([['cuadros', 'capas', 'Cuadros: uno por cada valor'], ['cruce', 'cuadricula', 'Cruce: dos criterios a la vez'], ['compartidos', 'grafo', 'Compartidos: quién está en dos sitios'], ['todo', 'orbita', 'Todo junto: el universo entero teñido']] as [$m, $icono, $ayuda])
                            <button type="button" @click="modo = '{{ $m }}'" title="{{ $ayuda }}"
                                :aria-pressed="modo === '{{ $m }}'"
                                :class="modo === '{{ $m }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>


                    {{-- Tamaño de las caras --}}
                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        <button type="button" @click="lado = Math.max(28, lado - 8)" :disabled="lado <= 28"
                            title="Caras más pequeñas"
                            class="rounded-lg px-1.5 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                        </button>
                        <span class="w-7 text-center font-mono text-[9px] font-black text-slate-500" x-text="lado"></span>
                        <button type="button" @click="lado = Math.min(96, lado + 8)" :disabled="lado >= 96"
                            title="Caras más grandes"
                            class="rounded-lg px-1.5 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                        </button>
                    </span>


                    {{-- Criterios y pantalla completa --}}
                    <button type="button" @click="panel = true"
                        class="flex shrink-0 items-center gap-1.5 rounded-xl border border-violet-500/40 bg-violet-500/10 px-2.5 py-1.5 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                        <x-omni-icon name="controles" size="h-3.5 w-3.5" />
                        <span class="hidden lg:inline">Criterios</span>
                    </button>

                    <button type="button" @click="pantallaCompleta()"
                        class="shrink-0 rounded-xl border border-slate-800 bg-slate-950 px-2 py-1.5 text-slate-500 transition hover:border-violet-500 hover:text-violet-300"
                        :title="aPantallaCompleta ? 'Salir de pantalla completa' : 'Ver el mapa a pantalla completa'">
                        <x-omni-icon name="panel" size="h-4 w-4" />
                    </button>
                </div>


                {{-- El foco: mirar un solo cuadro sin perder el resto de vista --}}
                <template x-if="foco">
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 border-t border-slate-800 pt-1.5">
                        <span class="text-[10px] text-slate-500">Mirando solo</span>
                        <span class="rounded-lg px-2 py-0.5 text-[11px] font-black"
                            :style="`color: ${color(foco)}; background: ${color(foco)}1f`" x-text="foco"></span>
                        <button type="button" @click="foco = null"
                            class="text-[10px] font-black text-slate-500 underline transition hover:text-slate-200">
                            volver al mapa entero
                        </button>
                    </div>
                </template>

                {{-- Un mapa de caras sin caras no es un mapa: se dice --}}
                @if ($sinImagen > 0)
                    <p class="mt-1.5 border-t border-slate-800 pt-1.5 text-[10px] text-slate-600">
                        {{ $sinImagen }} de {{ $totalEntidades }}
                        {{ $sinImagen === 1 ? 'entidad no tiene imagen' : 'entidades no tienen imagen' }},
                        así que {{ $sinImagen === 1 ? 'sale' : 'salen' }} como un hueco.
                        @can('update', $universe)
                            <a href="{{ route('universes.entities.index', $universe) }}"
                                class="font-black text-slate-500 underline transition hover:text-slate-300">
                                Ponerles imagen
                            </a>
                        @endcan
                    </p>
                @endif
            </header>


            {{-- ===================================================== --}}
            {{-- EL LIENZO --}}
            {{-- ===================================================== --}}

            <div x-ref="lienzo" class="space-y-2 bg-slate-950"
                :class="aPantallaCompleta ? 'overflow-y-auto p-3' : ''">

                @include('universes.explorer.partials.cuadros')
                @include('universes.explorer.partials.cruce')
                @include('universes.explorer.partials.compartidos')
                @include('universes.explorer.partials.todo')

                {{-- Cuando la búsqueda deja el mapa vacío --}}
                <template x-if="visibles.length === 0">
                    <div class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">
                        <span class="inline-flex text-slate-700"><x-omni-icon name="brujula" size="h-9 w-9" /></span>
                        <p class="mt-2 text-[13px] font-black text-white">
                            Nadie se llama así en este universo
                        </p>
                        <button type="button" @click="busqueda = ''"
                            class="mt-3 rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Borrar la búsqueda
                        </button>
                    </div>
                </template>
            </div>


            @include('universes.explorer.partials.criterios')
            @include('universes.explorer.partials.ficha')
        </div>


        <script>
            function mapaDelUniverso(datos) {

                return {

                    censo: datos.censo,
                    criterios: datos.criterios,
                    total: datos.total,

                    criterio: datos.porDefecto,
                    cruzarCon: '',
                    modo: datos.modoPorDefecto ?? 'cuadros',
                    orden: 'poblacion',
                    lado: 56,

                    busqueda: '',
                    soloCoincidencias: false,
                    verSinDato: true,

                    panel: false,
                    foco: null,
                    elegida: null,
                    aPantallaCompleta: false,


                    init() {

                        try {
                            const g = JSON.parse(localStorage.getItem('omnimerge.mapa') ?? '{}');

                            /* Si la configuración del universo cambió, manda ella y no lo último que se miró */
                            const mismaBase = g.base === (datos.porDefecto + '|' + (datos.modoPorDefecto ?? 'cuadros'));

                            if (mismaBase && this.criterios.some((c) => c.clave === g.criterio)) this.criterio = g.criterio;
                            if (mismaBase && ['cuadros', 'cruce', 'compartidos', 'todo'].includes(g.modo)) this.modo = g.modo;
                            if (['poblacion', 'nombre', 'titulos'].includes(g.orden)) this.orden = g.orden;
                            if (g.lado >= 28 && g.lado <= 96) this.lado = g.lado;
                            if (typeof g.verSinDato === 'boolean') this.verSinDato = g.verSinDato;
                        } catch (e) {
                            /* sin memoria, valores de fábrica */
                        }

                        ['criterio', 'modo', 'orden', 'lado', 'verSinDato'].forEach((campo) => {
                            this.$watch(campo, () => this.guardar());
                        });

                        document.addEventListener('fullscreenchange', () => {
                            this.aPantallaCompleta = document.fullscreenElement === this.$refs.lienzo;
                        });
                    },

                    guardar() {
                        localStorage.setItem('omnimerge.mapa', JSON.stringify({
                            base: datos.porDefecto + '|' + (datos.modoPorDefecto ?? 'cuadros'),
                            criterio: this.criterio,
                            modo: this.modo,
                            orden: this.orden,
                            lado: this.lado,
                            verSinDato: this.verSinDato,
                        }));
                    },


                    /*
                    |----------------------------------------------------------
                    | El criterio: en qué cuadros cae cada entidad
                    |----------------------------------------------------------
                    */

                    get criterioActual() {
                        return this.criterios.find((c) => c.clave === this.criterio) ?? this.criterios[0];
                    },

                    get criterioCruce() {
                        return this.criterios.find((c) => c.clave === this.cruzarCon) ?? null;
                    },

                    /*
                     * Siempre una lista. Vacía significa «no le consta el dato»,
                     * que es distinto de «no pertenece a nada»: por eso el cuadro
                     * se llama «Sin dato» y no «Otros».
                     */
                    valoresDe(entidad, clave) {

                        const criterio = this.criterios.find((c) => c.clave === clave);

                        if (! criterio || ! entidad) return [];

                        if (criterio.familia === 'atributo') {
                            return entidad.attrs[clave] ?? [];
                        }

                        switch (clave) {
                            case 'TIPO': return entidad.tipo ? [entidad.tipo] : [];
                            case 'ESTADO': return [entidad.estado];
                            case 'COMPITE': return [entidad.jugadas > 0 ? 'Ha competido' : 'Todavía no ha competido'];
                            case 'TITULO': return [entidad.titulos > 0 ? 'Con título' : 'Sin título'];
                            case 'TROFEO': return [entidad.trofeos > 0 ? 'Con trofeo' : 'Sin trofeo'];
                        }

                        return [];
                    },

                    grupoDe(entidad) {
                        return this.valoresDe(entidad, this.criterio);
                    },

                    cuantosCuadros(entidad) {
                        return this.grupoDe(entidad).length;
                    },


                    /*
                    |----------------------------------------------------------
                    | El color
                    |----------------------------------------------------------
                    |
                    | Sale del propio nombre del valor, así que «Hoja» es del
                    | mismo color en los cuatro modos y en todas las visitas sin
                    | guardar nada. La ausencia no recibe color: un «Sin título»
                    | pintado de fucsia parecería un logro.
                    */

                    color(valor) {

                        if (! valor) return '#475569';

                        if (/^(sin |todav[ií]a no|a[uú]n no)/i.test(valor)) return '#64748b';

                        let h = 0;

                        for (let i = 0; i < valor.length; i++) {
                            h = (h * 33 + valor.charCodeAt(i)) % 360;
                        }

                        return `hsl(${h} 72% 62%)`;
                    },

                    /*
                     * El aro de la cara. Un color si está en un cuadro; todos sus
                     * colores repartidos en círculo si está en varios. Es la forma
                     * de ver la doble pertenencia sin escribirla.
                     */
                    aro(entidad) {

                        const colores = this.grupoDe(entidad).map((v) => this.color(v));

                        if (colores.length === 0) return '#1e293b';
                        if (colores.length === 1) return colores[0];

                        const paso = 100 / colores.length;

                        const trozos = colores
                            .map((c, i) => `${c} ${i * paso}% ${(i + 1) * paso}%`)
                            .join(', ');

                        return `conic-gradient(from 45deg, ${trozos})`;
                    },


                    /*
                    |----------------------------------------------------------
                    | Buscar sin romper el mapa
                    |----------------------------------------------------------
                    |
                    | Por defecto los que no coinciden se apagan pero siguen ahí:
                    | quitarlos cambiaría el tamaño de los cuadros y la forma del
                    | reparto, que es justo lo que se está mirando.
                    */

                    coincide(entidad) {

                        const q = this.busqueda.trim().toLowerCase();

                        if (q === '') return true;

                        if (entidad.nombre.toLowerCase().includes(q)) return true;
                        if ((entidad.tipo ?? '').toLowerCase().includes(q)) return true;

                        return Object.values(entidad.attrs)
                            .flat()
                            .some((v) => String(v).toLowerCase().includes(q));
                    },

                    apagada(entidad) {
                        return ! this.coincide(entidad);
                    },

                    get visibles() {

                        if (this.busqueda.trim() === '' || ! this.soloCoincidencias) {
                            return this.censo;
                        }

                        return this.censo.filter((e) => this.coincide(e));
                    },


                    /*
                    |----------------------------------------------------------
                    | El reparto
                    |----------------------------------------------------------
                    */

                    ordenarMiembros(lista) {

                        return [...lista].sort(
                            (a, b) =>
                                b.titulos - a.titulos
                                || b.trofeos - a.trofeos
                                || b.jugadas - a.jugadas
                                || a.nombre.localeCompare(b.nombre)
                        );
                    },

                    get grupos() {

                        const caja = new Map();

                        for (const entidad of this.visibles) {
                            for (const valor of this.grupoDe(entidad)) {

                                if (! caja.has(valor)) caja.set(valor, []);

                                caja.get(valor).push(entidad);
                            }
                        }

                        let lista = [...caja.entries()].map(([valor, miembros]) => {

                            const ordenados = this.ordenarMiembros(miembros);

                            /*
                             * «Los que más han hecho» solo tiene sentido si alguien
                             * ha hecho algo. En un cuadro donde nadie ha competido,
                             * destacar a tres sería inventarse una jerarquía.
                             */
                            const hayJerarquia = ordenados.length >= 5 && ordenados[0].jugadas > 0;

                            return {
                                valor,
                                color: this.color(valor),
                                miembros: ordenados,
                                representativos: hayJerarquia ? ordenados.slice(0, 3) : [],
                                resto: hayJerarquia ? ordenados.slice(3) : ordenados,
                                titulos: ordenados.reduce((s, e) => s + e.titulos, 0),
                                compartidos: ordenados.filter((e) => this.grupoDe(e).length > 1).length,
                            };
                        });

                        if (this.orden === 'nombre') {
                            lista.sort((a, b) => a.valor.localeCompare(b.valor));
                        } else if (this.orden === 'titulos') {
                            lista.sort((a, b) => b.titulos - a.titulos || b.miembros.length - a.miembros.length);
                        } else {
                            lista.sort((a, b) => b.miembros.length - a.miembros.length || a.valor.localeCompare(b.valor));
                        }

                        const mayor = Math.max(1, ...lista.map((g) => g.miembros.length));
                        const cuantos = Math.max(1, this.visibles.length);

                        return lista.map((g) => ({
                            ...g,
                            porcentaje: Math.round((g.miembros.length / cuantos) * 100),
                            relativo: Math.round((g.miembros.length / mayor) * 100),
                            ancho: this.anchoDe(g.miembros.length),
                        }));
                    },

                    /*
                     * Un cuadro ocupa lo que pesa. Así el reparto se ve antes de
                     * leer un número: si uno se lleva media pantalla, se lleva
                     * medio universo.
                     */
                    anchoDe(cuantos) {

                        const cuota = cuantos / Math.max(1, this.visibles.length);

                        if (cuota >= 0.45) return 6;
                        if (cuota >= 0.25) return 4;
                        if (cuota >= 0.12) return 3;
                        if (cuota >= 0.05) return 2;

                        return 1;
                    },

                    get sinDato() {
                        return this.ordenarMiembros(
                            this.visibles.filter((e) => this.grupoDe(e).length === 0)
                        );
                    },


                    /*
                    |----------------------------------------------------------
                    | El cruce
                    |----------------------------------------------------------
                    */

                    get cruce() {

                        const filas = this.grupos.map((g) => ({
                            valor: g.valor,
                            color: g.color,
                            total: g.miembros.length,
                        }));

                        const cuenta = new Map();

                        for (const entidad of this.visibles) {
                            for (const valor of this.valoresDe(entidad, this.cruzarCon)) {
                                cuenta.set(valor, (cuenta.get(valor) ?? 0) + 1);
                            }
                        }

                        const columnas = [...cuenta.entries()]
                            .sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]))
                            .map(([valor, total]) => ({ valor, color: this.color(valor), total }));

                        return { filas, columnas };
                    },

                    celda(fila, columna) {

                        return this.ordenarMiembros(
                            this.visibles.filter(
                                (e) =>
                                    this.grupoDe(e).includes(fila)
                                    && this.valoresDe(e, this.cruzarCon).includes(columna)
                            )
                        );
                    },


                    /*
                    |----------------------------------------------------------
                    | Los compartidos
                    |----------------------------------------------------------
                    */

                    get compartidas() {
                        return this.visibles.filter((e) => this.grupoDe(e).length > 1);
                    },

                    get puentes() {

                        const caja = new Map();

                        for (const entidad of this.compartidas) {

                            const suyos = [...this.grupoDe(entidad)].sort();

                            for (let i = 0; i < suyos.length; i++) {
                                for (let j = i + 1; j < suyos.length; j++) {

                                    const llave = suyos[i] + '||' + suyos[j];

                                    if (! caja.has(llave)) {
                                        caja.set(llave, {
                                            a: suyos[i],
                                            b: suyos[j],
                                            colorA: this.color(suyos[i]),
                                            colorB: this.color(suyos[j]),
                                            quienes: [],
                                        });
                                    }

                                    caja.get(llave).quienes.push(entidad);
                                }
                            }
                        }

                        return [...caja.values()].sort((a, b) => b.quienes.length - a.quienes.length);
                    },

                    /* Cuando el criterio de ahora no comparte nada, cuáles sí */
                    get criteriosQueComparten() {
                        return this.criterios.filter(
                            (c) => c.compartidos > 0 && c.clave !== this.criterio
                        );
                    },

                    /*
                     * El diagrama de puentes. Los valores en círculo, y entre los
                     * que comparten gente una curva que pasa por el centro, con el
                     * grosor de cuántos comparten y el color yendo de uno al otro.
                     */
                    get diagrama() {

                        const puentes = this.puentes;

                        const nodos = this.grupos.filter(
                            (g) => puentes.some((p) => p.a === g.valor || p.b === g.valor)
                        );

                        const ancho = 640;
                        const alto = 380;
                        const cx = ancho / 2;
                        const cy = alto / 2 - 8;
                        const radio = Math.min(cx, cy) - 62;

                        const sitio = new Map();

                        nodos.forEach((g, i) => {
                            const angulo = (i / Math.max(1, nodos.length)) * Math.PI * 2 - Math.PI / 2;
                            sitio.set(g.valor, {
                                x: cx + Math.cos(angulo) * radio,
                                y: cy + Math.sin(angulo) * radio,
                            });
                        });

                        const mayor = Math.max(1, ...puentes.map((p) => p.quienes.length));

                        return {
                            ancho,
                            alto,

                            nodos: nodos.map((g) => {
                                const p = sitio.get(g.valor);
                                return {
                                    valor: g.valor,
                                    x: p.x,
                                    y: p.y,
                                    r: 16 + Math.min(22, g.miembros.length * 2),
                                    color: g.color,
                                    total: g.miembros.length,
                                    corto: g.valor.length > 16 ? g.valor.slice(0, 15) + '…' : g.valor,
                                };
                            }),

                            puentes: puentes
                                .filter((p) => sitio.has(p.a) && sitio.has(p.b))
                                .map((p) => {
                                    const A = sitio.get(p.a);
                                    const B = sitio.get(p.b);
                                    return {
                                        ...p,
                                        x1: A.x, y1: A.y, x2: B.x, y2: B.y,
                                        d: `M ${A.x} ${A.y} Q ${cx} ${cy} ${B.x} ${B.y}`,
                                        grosor: 2 + (p.quienes.length / mayor) * 14,
                                        cuantos: p.quienes.length,
                                    };
                                }),
                        };
                    },


                    /*
                     * El dibujo, ya como marcado.
                     *
                     * Se arma a mano en vez de con x-for porque dentro de un
                     * <svg> un <template> no es un template de HTML y Alpine no
                     * puede clonarlo. Los nombres van escapados: salen de datos
                     * del usuario y aqui se concatenan en marcado.
                     */
                    escapar(texto) {
                        return String(texto).replace(
                            /[&<>\"']/g,
                            (c) => ({
                                '&': '&amp;',
                                '<': '&lt;',
                                '>': '&gt;',
                                '\"': '&quot;',
                                "'": '&#39;',
                            })[c]
                        );
                    },

                    get diagramaSvg() {

                        const d = this.diagrama;

                        if (d.nodos.length === 0) return '';

                        const gradientes = d.puentes
                            .map(
                                (p, i) =>
                                    `<linearGradient id="puente${i}" gradientUnits="userSpaceOnUse"`
                                    + ` x1="${p.x1}" y1="${p.y1}" x2="${p.x2}" y2="${p.y2}">`
                                    + `<stop offset="0%" stop-color="${p.colorA}"/>`
                                    + `<stop offset="100%" stop-color="${p.colorB}"/>`
                                    + `</linearGradient>`
                            )
                            .join('');

                        const puentes = d.puentes
                            .map(
                                (p, i) =>
                                    `<path d="${p.d}" fill="none" stroke="url(#puente${i})"`
                                    + ` stroke-width="${p.grosor}" stroke-linecap="round" opacity="0.65">`
                                    + `<title>${this.escapar(p.a)} ↔ ${this.escapar(p.b)}: ${p.cuantos}</title>`
                                    + `</path>`
                            )
                            .join('');

                        const nodos = d.nodos
                            .map(
                                (n) =>
                                    `<g>`
                                    + `<circle cx="${n.x}" cy="${n.y}" r="${n.r}" fill="${n.color}" opacity="0.2"/>`
                                    + `<circle cx="${n.x}" cy="${n.y}" r="${n.r}" fill="none" stroke="${n.color}" stroke-width="2"/>`
                                    + `<text x="${n.x}" y="${n.y + 5}" text-anchor="middle" font-size="13"`
                                    + ` font-weight="700" fill="${n.color}">${n.total}</text>`
                                    + `<text x="${n.x}" y="${n.y + n.r + 15}" text-anchor="middle" font-size="11"`
                                    + ` font-weight="700" fill="#cbd5e1">${this.escapar(n.corto)}</text>`
                                    + `</g>`
                            )
                            .join('');

                        return `<svg viewBox="0 0 ${d.ancho} ${d.alto}" class="mx-auto block h-auto w-full max-w-3xl">`
                            + `<defs>${gradientes}</defs>${puentes}${nodos}</svg>`;
                    },


                    /*
                    |----------------------------------------------------------
                    | Todo junto
                    |----------------------------------------------------------
                    |
                    | Las caras seguidas, ordenadas por grupo, para que los colores
                    | formen bandas. Quien está en dos cuadros sale dos veces, y
                    | debe salir: está en los dos sitios.
                    */

                    get panorama() {

                        const salida = [];

                        for (const grupo of this.grupos) {
                            for (const entidad of grupo.miembros) {
                                salida.push({ ...entidad, __grupo: grupo.valor });
                            }
                        }

                        if (this.verSinDato) {
                            for (const entidad of this.sinDato) {
                                salida.push({ ...entidad, __grupo: '—' });
                            }
                        }

                        return salida;
                    },


                    pantallaCompleta() {

                        if (document.fullscreenElement) {
                            document.exitFullscreen?.();
                            return;
                        }

                        this.$refs.lienzo.requestFullscreen?.();
                    },
                };
            }
        </script>
    @endif

</x-universe-layout>

@php
    /*
     * LA CONFIGURACIÓN DE UN UNIVERSO
     *
     * Todo lo que se puede decidir de un mundo que ya existe, en una sola
     * pantalla y con efecto real: cada sección dice dónde se nota.
     *
     *   identidad       nombre, descripción, estado, portada y lema
     *   apariencia      color, icono y encuadre de la portada
     *   vocabulario     cómo se llaman aquí sus cosas
     *   menú e inicio   qué secciones se ven y a dónde lleva entrar
     *   resumen         qué bloques enseña y en qué orden
     *   clasificación   puntos, mínimo para aparecer y desempates
     *   torneos nuevos  con qué nace un torneo de este mundo
     *   explorar        cómo se abre el mapa
     *   zona de peligro archivar y borrar
     *
     * A la derecha, cómo queda: el sidebar, la tarjeta de «Mis universos» y
     * el Resumen, cambiando mientras se configura.
     *
     * Un solo formulario para todo menos archivar y borrar, que tienen el suyo.
     * Ver App\Support\Universes\UniverseSettings y docs/md/81.
     */

    $secciones = [
        'identidad' => ['Identidad', 'globo', '#a78bfa'],
        'apariencia' => ['Apariencia', 'chispa', '#f472b6'],
        'vocabulario' => ['Vocabulario', 'libro', '#38bdf8'],
        'menu' => ['Menú e inicio', 'panel', '#2dd4bf'],
        'resumen' => ['Resumen', 'cuadricula', '#34d399'],
        'clasificacion' => ['Clasificación', 'barras', '#fbbf24'],
        'competiciones' => ['Torneos nuevos', 'trofeo', '#fb923c'],
        'explorar' => ['Explorar', 'brujula', '#818cf8'],
        'peligro' => ['Zona de peligro', 'aviso', '#f43f5e'],
    ];
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Configuración</x-slot>

    <div x-data="ajustesDelUniverso(@js([
        'nombre' => old('name', $universe->name),
        'descripcion' => old('description', $universe->description ?? ''),
        'estado' => old('status', $universe->status),
        'portada' => $universe->image_url,
        'ajustes' => $ajustes,
        'defaults' => $porDefecto,
        'nav' => \App\Support\Universes\UniverseSettings::NAV,
        'homes' => \App\Support\Universes\UniverseSettings::HOMES,
        'bloques' => \App\Support\Universes\UniverseSettings::BLOCKS,
        'desempates' => \App\Support\Universes\UniverseSettings::TIEBREAKS,
        'codigo' => $universe->code,
        'temporada' => $temporadaActiva?->number,
        'cuentas' => $cuentas,
    ]))" class="space-y-3">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="relative overflow-hidden rounded-2xl border bg-slate-900/60 px-4 py-4"
            :style="`border-color: ${s.accent}55`">
            <span class="pointer-events-none absolute -right-20 -top-24 h-60 w-60 rounded-full blur-3xl" :style="`background-color: ${s.accent}22`"></span>

            <div class="relative flex flex-wrap items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-2"
                    :style="`border-color: ${s.accent}; background-color: ${s.accent}1a; color: ${s.accent}`">
                    <template x-if="portada"><img :src="portada" alt="" class="h-full w-full object-cover" :style="`object-position: ${s.cover_position}`"></template>
                    <template x-if="! portada">
                        <span>
                            @foreach (\App\Support\Universes\UniverseSettings::ICONS as $icono)
                                <span x-show="s.icon === '{{ $icono }}'"><x-omni-icon :name="$icono" size="h-7 w-7" /></span>
                            @endforeach
                        </span>
                    </template>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em]" :style="`color: ${s.accent}`">Configuración del universo</p>
                    <h1 class="truncate text-2xl font-black text-white" x-text="nombre || 'Sin nombre'"></h1>
                    <p class="truncate text-[11px] text-slate-500" x-text="s.tagline || 'Todo lo que decidas aquí se nota en el sidebar, el Resumen, «Mis universos», la clasificación, los torneos nuevos y el mapa.'"></p>
                </div>

                <a href="{{ route('universes.show', $universe) }}"
                    class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">
                    <x-omni-icon name="flecha-izquierda" size="h-3.5 w-3.5" />
                    Volver al universo
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-[12px] font-bold text-rose-200">
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <div class="grid items-start gap-3 lg:grid-cols-[210px_minmax(0,1fr)] 2xl:grid-cols-[210px_minmax(0,1fr)_320px]">

            {{-- ===================================================== --}}
            {{-- EL ÍNDICE --}}
            {{-- ===================================================== --}}

            <nav class="space-y-1 rounded-2xl border border-slate-800 bg-slate-900/50 p-2 lg:sticky lg:top-24">
                @foreach ($secciones as $clave => [$texto, $icono, $tono])
                    <button type="button" @click="ir('{{ $clave }}')"
                        class="flex w-full items-center gap-2 rounded-xl px-2.5 py-2 text-left text-[12px] font-black transition"
                        :class="seccion === '{{ $clave }}' ? 'text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                        :style="seccion === '{{ $clave }}' ? 'background-color: {{ $tono }}22; box-shadow: inset 3px 0 0 {{ $tono }}' : ''">
                        <span style="color: {{ $tono }}"><x-omni-icon :name="$icono" size="h-4 w-4" /></span>
                        {{ $texto }}
                    </button>
                @endforeach

                <div class="mt-2 border-t border-slate-800 px-2.5 pt-2 text-[10px] leading-4">
                    <p x-show="sucio" x-cloak class="flex items-center gap-1.5 font-black text-amber-300">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"></span>
                        Hay cambios sin guardar
                    </p>
                    <p x-show="! sucio" class="text-slate-600">Todo guardado.</p>
                </div>
            </nav>


            {{-- ===================================================== --}}
            {{-- LAS SECCIONES --}}
            {{-- ===================================================== --}}

            <div class="min-w-0 space-y-3">

                <form method="POST" action="{{ route('universes.update', $universe) }}" enctype="multipart/form-data"
                    @submit="enviando = true" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_section" :value="seccion">

                    @include('universes.partials.ajustes.identidad')
                    @include('universes.partials.ajustes.apariencia')
                    @include('universes.partials.ajustes.vocabulario')
                    @include('universes.partials.ajustes.menu')
                    @include('universes.partials.ajustes.resumen')
                    @include('universes.partials.ajustes.clasificacion')
                    @include('universes.partials.ajustes.competiciones')
                    @include('universes.partials.ajustes.explorar')

                    {{-- Las listas viajan siempre, aunque vayan vacías: vacía también es una decisión --}}
                    @foreach (['hidden_nav', 'summary_order', 'summary_hidden', 'ranking_tiebreaks'] as $lista)
                        <input type="hidden" name="settings[{{ $lista }}][]" value="">
                        <template x-for="valor in s.{{ $lista }}" :key="'{{ $lista }}' + valor">
                            <input type="hidden" name="settings[{{ $lista }}][]" :value="valor">
                        </template>
                    @endforeach

                    {{-- Guardar --}}
                    <div class="sticky bottom-2 z-20 flex flex-wrap items-center gap-2 rounded-2xl border bg-slate-950/95 px-4 py-3 shadow-2xl shadow-black backdrop-blur"
                        :style="`border-color: ${sucio ? '#fbbf2466' : '#1e293b'}`">
                        <p class="mr-auto text-[11px] leading-4" :class="sucio ? 'text-amber-200' : 'text-slate-500'"
                            x-text="sucio ? 'Tienes cambios sin guardar. Se aplican en todo el universo al guardar.' : 'Nada pendiente. Cambia algo y lo verás a la derecha antes de guardarlo.'"></p>

                        <button type="button" x-show="sucio" x-cloak @click="descartar()"
                            class="flex items-center gap-1.5 rounded-xl border border-slate-700 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-slate-500 hover:text-white">
                            <x-omni-icon name="deshacer" size="h-3.5 w-3.5" />
                            Descartar
                        </button>

                        <button type="submit"
                            class="flex items-center gap-1.5 rounded-xl px-5 py-2 text-[12px] font-black text-slate-950 transition hover:brightness-110"
                            :style="`background-color: ${s.accent}`">
                            <x-omni-icon name="guardar" size="h-4 w-4" />
                            Guardar la configuración
                        </button>
                    </div>
                </form>

                @include('universes.partials.ajustes.peligro')
            </div>


            {{-- ===================================================== --}}
            {{-- CÓMO QUEDA --}}
            {{-- ===================================================== --}}

            <aside class="hidden space-y-3 2xl:sticky 2xl:top-24 2xl:block">
                @include('universes.partials.ajustes.vista-previa')
            </aside>
        </div>
    </div>


    <script>
        function ajustesDelUniverso(c) {
            const copia = (x) => JSON.parse(JSON.stringify(x));

            return {
                /* El contrato de universes.partials.universe-form */
                nombre: c.nombre,
                descripcion: c.descripcion,
                estado: c.estado,
                portada: c.portada,
                quitarPortada: false,

                cargarPortada(evento) {
                    const fichero = evento.target.files[0];
                    if (! fichero) return;
                    this.portada = URL.createObjectURL(fichero);
                    this.quitarPortada = false;
                },

                limpiarPortada() {
                    this.portada = null;
                    this.quitarPortada = true;
                },

                /* La configuración */
                s: copia(c.ajustes),
                def: c.defaults,
                nav: c.nav,
                homes: c.homes,
                bloques: c.bloques,
                desempates: c.desempates,
                codigo: c.codigo,
                temporada: c.temporada,
                cuentas: c.cuentas,

                seccion: 'identidad',
                inicial: '',
                enviando: false,

                init() {
                    this.inicial = this.huella;

                    const hash = window.location.hash.replace('#', '');
                    if (hash && document.getElementById(hash)) this.$nextTick(() => this.ir(hash));

                    const observador = new IntersectionObserver((entradas) => {
                        entradas.forEach((e) => { if (e.isIntersecting) this.seccion = e.target.id; });
                    }, { rootMargin: '-35% 0px -60% 0px' });

                    document.querySelectorAll('[data-seccion]').forEach((el) => observador.observe(el));

                    /* Salir con cambios: el modal de OmniMerge (ver OmniUnsaved en app.js) */
                    window.OmniUnsaved?.watch(() => this.sucio && ! this.enviando);
                },

                get huella() {
                    return JSON.stringify([this.s, this.nombre, this.descripcion, this.estado, this.quitarPortada, this.portada]);
                },

                get sucio() {
                    return this.huella !== this.inicial;
                },

                descartar() {
                    const [s, nombre, descripcion, estado, quitar, portada] = JSON.parse(this.inicial);
                    this.s = s;
                    this.nombre = nombre;
                    this.descripcion = descripcion;
                    this.estado = estado;
                    this.quitarPortada = quitar;
                    this.portada = portada;
                },

                ir(id) {
                    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    this.seccion = id;
                    history.replaceState(null, '', '#' + id);
                },

                restablecer(claves) {
                    claves.forEach((k) => { this.s[k] = copia(this.def[k]); });
                },

                alternar(lista, valor) {
                    const i = this.s[lista].indexOf(valor);
                    if (i >= 0) this.s[lista].splice(i, 1);
                    else this.s[lista].push(valor);
                },

                mover(lista, i, paso) {
                    const j = i + paso;
                    if (j < 0 || j >= this.s[lista].length) return;
                    const copiaLista = [...this.s[lista]];
                    [copiaLista[i], copiaLista[j]] = [copiaLista[j], copiaLista[i]];
                    this.s[lista] = copiaLista;
                },

                /* Cómo se llama cada sección con el vocabulario de este mundo */
                navLabel(clave) {
                    return {
                        entities: this.s.label_entities,
                        seasons: this.s.label_seasons,
                        tournaments: this.s.label_tournaments,
                        competitions: this.s.label_competitions,
                    }[clave] || this.nav[clave][1];
                },

                navVisible(clave) {
                    return ! this.s.hidden_nav.includes(clave);
                },

                get homeValido() {
                    return this.s.home === 'show' || this.navVisible(this.s.home);
                },

                bloquesDe(zona) {
                    return this.s.summary_order.filter((b) => this.bloques[b]?.[1] === zona && ! this.s.summary_hidden.includes(b));
                },

                /* El ejemplo de la clasificación: un campeón con 4-1-1 en 2 competiciones */
                get puntosEjemplo() {
                    const p = (k) => Number(this.s[k]) || 0;
                    return p('points_champion') + 4 * p('points_win') + p('points_draw') + p('points_loss') + 2 * p('points_participation');
                },

                get desempatesInactivos() {
                    return Object.keys(this.desempates).filter((d) => ! this.s.ranking_tiebreaks.includes(d));
                },
            };
        }
    </script>

</x-universe-layout>

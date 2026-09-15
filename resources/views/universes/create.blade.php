@php
    /*
     * Crear un universo.
     *
     * Antes eran cuatro campos -nombre, descripcion, portada, estado- y despues
     * hacian falta cuatro pantallas mas para que el mundo funcionase de verdad:
     * crear su primera temporada, decidir que premia, elegir con que se juega y
     * traerle gente. Todo eso ya existia, suelto, y el Resumen del universo
     * recien creado se llenaba de avisos de cosas a medias.
     *
     * Aqui se decide todo junto, y todo sigue siendo opcional: un universo se
     * puede crear con su nombre y nada mas.
     *
     * A la derecha, lo que se va a crear tal y como se vera en la estanteria:
     * la forma mas honesta de enseñar el resultado es la cosa, no una
     * descripcion de la cosa.
     *
     * Ver docs/md/72-Universos-Crear.md
     */
@endphp

<x-universe-layout surface="dark">

    <x-slot name="header">Crear un universo</x-slot>

    <form method="POST" action="{{ route('universes.store') }}" enctype="multipart/form-data"
        x-data="crearUniverso(@js([
            'biblioteca' => $biblioteca,
            'puntos' => $puntosDefecto,
            'juegos' => $juegos->pluck('key'),
        ]))" class="space-y-3">

        @csrf


        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    OmniMerge · Universos
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Un mundo nuevo
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] leading-relaxed text-slate-500">
                    Un universo tiene su propia gente, su propio calendario y su propia
                    clasificación. Puedes dejarlo todo montado ahora o crear solo el nombre y
                    seguir después: <strong class="text-slate-400">nada de lo de abajo es
                    obligatorio</strong>.
                </p>
            </div>

            <a href="{{ route('universes.index') }}"
                class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-slate-200">
                <x-omni-icon name="flecha-izquierda" size="h-3.5 w-3.5" />
                Volver a mis mundos
            </a>
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


        {{-- ===================================================== --}}
        {{-- ARQUETIPOS: TRES MUNDOS YA PENSADOS --}}
        {{-- ===================================================== --}}

        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                ¿Prefieres empezar de algo ya pensado?
            </p>

            <div class="mt-1.5 flex flex-wrap gap-1.5">
                @foreach ([['liga', '#34d399', 'Una liga larga', 'Seis temporadas encadenadas, la primera en marcha, y premia la constancia.'], ['copa', '#fbbf24', 'Una copa corta', 'Una sola temporada activa y el título por encima de todo.'], ['pruebas', '#60a5fa', 'Un mundo de pruebas', 'Borrador, sin calendario, para trastear sin ensuciar nada.']] as [$clave, $tono, $texto, $ayuda])
                    <button type="button" @click="aplicarPlantilla('{{ $clave }}')"
                        class="group flex-1 rounded-xl border p-2 text-left transition"
                        :style="plantilla === '{{ $clave }}'
                            ? 'border-color: {{ $tono }}; background-color: {{ $tono }}14'
                            : 'border-color: #1e293b'">

                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $tono }}"></span>
                            <span class="text-[12px] font-black"
                                :class="plantilla === '{{ $clave }}' ? 'text-white' : 'text-slate-300'">
                                {{ $texto }}
                            </span>
                        </span>

                        <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                    </button>
                @endforeach
            </div>

            <p class="mt-1.5 text-[9px] leading-3 text-slate-600">
                Solo rellenan los campos de abajo. Puedes cambiar cualquiera después de elegir.
            </p>
        </section>


        {{-- ===================================================== --}}
        {{-- EL FORMULARIO Y SU RESUMEN --}}
        {{-- ===================================================== --}}

        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_320px]">

            <div class="min-w-0 space-y-3">

                @include('universes.partials.universe-form')

                @include('universes.partials.crear.tiempo')

                @include('universes.partials.crear.puntos')

                @include('universes.partials.crear.juego')

                @include('universes.partials.crear.habitantes')
            </div>

            <div class="min-w-0">
                @include('universes.partials.crear.resumen')
            </div>
        </div>
    </form>


    <script>
        function crearUniverso(datos) {

            return {

                /* ---------- Identidad ---------- */

                nombre: @js(old('name', '')),
                descripcion: @js(old('description', '')),
                estado: @js(old('status', 'DRAFT')),
                portada: null,
                quitarPortada: false,

                /* ---------- Lo que se monta de una vez ---------- */

                temporadas: {
                    cuantas: Number(@js(old('seasons_count', 0))),
                    patron: @js(old('seasons_pattern', 'Temporada {n}')),
                    desde: @js(old('seasons_starts_at', '')),
                    duracion: Number(@js(old('seasons_duration', 1))),
                    unidad: @js(old('seasons_duration_unit', 'months')),
                    primera: @js(old('seasons_first_status', 'PLANNED')),
                },

                puntos: {
                    campeon: Number(@js(old('points_champion', $puntosDefecto['points_champion']))),
                    victoria: Number(@js(old('points_win', $puntosDefecto['points_win']))),
                    empate: Number(@js(old('points_draw', $puntosDefecto['points_draw']))),
                    derrota: Number(@js(old('points_loss', $puntosDefecto['points_loss']))),
                    participar: Number(@js(old('points_participation', $puntosDefecto['points_participation']))),
                },

                arquetipo: 'equilibrado',

                juego: @js(old('game_key', $juegos->first()['key'] ?? '')),

                biblioteca: datos.biblioteca,
                elegidas: @js(array_map('intval', old('entity_ids', []))),
                buscarHabitante: '',
                tipoHabitante: '',

                plantilla: '',


                /* ---------- La portada ---------- */

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


                /* ---------- Los tres mundos ya pensados ---------- */

                aplicarPlantilla(cual) {

                    this.plantilla = cual;

                    if (cual === 'liga') {
                        this.estado = 'ACTIVE';
                        this.temporadas.cuantas = 6;
                        this.temporadas.patron = 'Temporada {n}';
                        this.temporadas.duracion = 1;
                        this.temporadas.unidad = 'months';
                        this.temporadas.primera = 'ACTIVE';
                        this.ponerArquetipo('constancia', [15, 10, 4, 1, 2]);
                    }

                    if (cual === 'copa') {
                        this.estado = 'ACTIVE';
                        this.temporadas.cuantas = 1;
                        this.temporadas.patron = 'Edición {n}';
                        this.temporadas.duracion = 0;
                        this.temporadas.primera = 'ACTIVE';
                        this.ponerArquetipo('titulos', [100, 3, 1, 0, 1]);
                    }

                    if (cual === 'pruebas') {
                        this.estado = 'DRAFT';
                        this.temporadas.cuantas = 0;
                        this.ponerArquetipo('equilibrado', [10, 3, 1, 0, 1]);
                    }
                },

                ponerArquetipo(cual, valores) {

                    this.arquetipo = cual;

                    [
                        this.puntos.campeon,
                        this.puntos.victoria,
                        this.puntos.empate,
                        this.puntos.derrota,
                        this.puntos.participar,
                    ] = valores;
                },


                /* ---------- Las temporadas, tal y como quedarán ---------- */

                /*
                 * Refleja lo que hace UniverseSeasonService::createMany: el
                 * patrón con {n}, las fechas encadenadas y el estado de la
                 * primera. Si esas reglas cambian allí, esta vista previa
                 * mentiría, así que van juntas a propósito.
                 */
                get vistaPreviaTemporadas() {

                    const salida = [];
                    const cuantas = Math.max(0, Math.min(24, this.temporadas.cuantas || 0));

                    /*
                     * Se arma la fecha por partes y no con `new Date('2026-03-01')`:
                     * esa forma la interpreta como medianoche UTC y al escribirla
                     * en hora local retrocede un día, así que la vista previa
                     * decía «28 feb» para un 1 de marzo. El servidor usa el día
                     * que se escribió, y la previa tiene que decir lo mismo.
                     */
                    const partes = (this.temporadas.desde || '').split('-').map(Number);

                    let cursor = partes.length === 3 && ! partes.some(isNaN)
                        ? new Date(partes[0], partes[1] - 1, partes[2])
                        : null;

                    const duracion = Math.max(0, this.temporadas.duracion || 0);

                    for (let i = 0; i < Math.min(cuantas, 6); i++) {

                        const numero = i + 1;

                        let fechas = 'Sin fechas';
                        let fin = null;
                        let siguiente = null;

                        if (cursor) {

                            siguiente = new Date(cursor);

                            if (duracion > 0) {
                                if (this.temporadas.unidad === 'days') siguiente.setDate(siguiente.getDate() + duracion);
                                if (this.temporadas.unidad === 'weeks') siguiente.setDate(siguiente.getDate() + duracion * 7);
                                if (this.temporadas.unidad === 'months') siguiente.setMonth(siguiente.getMonth() + duracion);
                                if (this.temporadas.unidad === 'years') siguiente.setFullYear(siguiente.getFullYear() + duracion);
                            }

                            /*
                             * Termina el día ANTES de que empiece la siguiente,
                             * igual que UniverseSeasonService::createMany: dos
                             * temporadas no pueden compartir el mismo día. Sin
                             * este ajuste la previa decía un día de más.
                             */
                            fin = new Date(siguiente);
                            fin.setDate(fin.getDate() - 1);

                            const corto = (d) => d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });

                            fechas = duracion > 0
                                ? `${corto(cursor)} → ${corto(fin)}`
                                : `desde ${corto(cursor)}`;
                        }

                        salida.push({
                            numero,
                            nombre: (this.temporadas.patron || 'Temporada {n}').replaceAll('{n}', numero),
                            fechas,
                            activa: numero === 1 && this.temporadas.primera === 'ACTIVE',
                        });

                        if (cursor && duracion > 0) cursor = siguiente;
                    }

                    return salida;
                },


                /* ---------- El simulador de puntos ---------- */

                get ejemplosDePuntos() {

                    const perfiles = [
                        { nombre: 'El que gana la final', t: 1, g: 4, e: 0, p: 1, comp: 1 },
                        { nombre: 'El regular', t: 0, g: 9, e: 3, p: 4, comp: 4 },
                        { nombre: 'El que participa', t: 0, g: 1, e: 1, p: 6, comp: 4 },
                    ];

                    const cuentas = perfiles.map((perfil) => ({
                        ...perfil,
                        total: perfil.t * this.puntos.campeon
                            + perfil.g * this.puntos.victoria
                            + perfil.e * this.puntos.empate
                            + perfil.p * this.puntos.derrota
                            + perfil.comp * this.puntos.participar,
                    }));

                    const techo = Math.max(1, ...cuentas.map((c) => c.total));

                    return cuentas.map((c) => ({
                        ...c,
                        ancho: Math.max(Math.round((c.total / techo) * 100), 2),
                        lider: c.total === techo,
                    }));
                },


                /* ---------- Los habitantes ---------- */

                get habitantesVisibles() {

                    const q = this.buscarHabitante.trim().toLowerCase();

                    return this.biblioteca.filter((e) => {

                        if (this.tipoHabitante && e.tipo !== this.tipoHabitante) return false;

                        if (q === '') return true;

                        return e.nombre.toLowerCase().includes(q)
                            || (e.tipo ?? '').toLowerCase().includes(q);
                    });
                },

                alternarHabitante(id) {

                    const donde = this.elegidas.indexOf(id);

                    if (donde === -1) {
                        this.elegidas.push(id);
                    } else {
                        this.elegidas.splice(donde, 1);
                    }
                },

                elegirVisibles() {

                    for (const e of this.habitantesVisibles) {
                        if (! this.elegidas.includes(e.id)) this.elegidas.push(e.id);
                    }
                },

                get carasElegidas() {
                    return this.biblioteca
                        .filter((e) => this.elegidas.includes(e.id) && e.img)
                        .slice(0, 12);
                },


                /* ---------- El resumen ---------- */

                get tonoEstado() {
                    return {
                        ACTIVE: '#34d399',
                        DRAFT: '#60a5fa',
                        ARCHIVED: '#64748b',
                    }[this.estado] ?? '#94a3b8';
                },

                get textoEstado() {
                    return {
                        ACTIVE: 'En marcha',
                        DRAFT: 'Borrador',
                        ARCHIVED: 'Archivado',
                    }[this.estado] ?? this.estado;
                },

                get cifrasPrevias() {
                    return [
                        { etiqueta: 'Gente', valor: this.elegidas.length, tono: '#a78bfa' },
                        { etiqueta: 'Temporadas', valor: this.temporadas.cuantas || 0, tono: '#60a5fa' },
                        { etiqueta: 'Torneos', valor: 0, tono: '#22d3ee' },
                        { etiqueta: 'Jugadas', valor: 0, tono: '#34d399' },
                    ];
                },

                get loQueSeHara() {

                    const cuantas = this.temporadas.cuantas || 0;
                    const gente = this.elegidas.length;

                    return [
                        {
                            texto: this.nombre.trim() === ''
                                ? 'Ponerle nombre al mundo'
                                : `Crear «${this.nombre.trim()}»`,
                            nota: this.textoEstado,
                            tono: this.tonoEstado,
                            hecho: this.nombre.trim() !== '',
                        },
                        {
                            texto: cuantas > 0
                                ? `Crear ${cuantas} ${cuantas === 1 ? 'temporada' : 'temporadas'}`
                                : 'Sin calendario',
                            nota: cuantas > 0
                                ? (this.temporadas.primera === 'ACTIVE' ? 'la primera arranca ya' : 'todas planificadas')
                                : 'los torneos que se repiten no sabrán cuándo les toca',
                            tono: '#60a5fa',
                            hecho: cuantas > 0,
                        },
                        {
                            texto: 'Guardar qué premia este mundo',
                            nota: `título ${this.puntos.campeon} · victoria ${this.puntos.victoria} · participar ${this.puntos.participar}`,
                            tono: '#a78bfa',
                            hecho: true,
                        },
                        {
                            texto: this.juego ? 'Dejar puesto el juego por defecto' : 'Sin juego por defecto',
                            nota: this.juego || 'se usará el de fábrica',
                            tono: '#34d399',
                            hecho: !! this.juego,
                        },
                        {
                            texto: gente > 0
                                ? `Traer ${gente} ${gente === 1 ? 'competidor' : 'competidores'}`
                                : 'Sin habitantes',
                            nota: gente > 0
                                ? 'copias independientes de tu Biblioteca'
                                : 'un mundo vacío no puede jugar nada todavía',
                            tono: '#fbbf24',
                            hecho: gente > 0,
                        },
                    ];
                },
            };
        }
    </script>

</x-universe-layout>

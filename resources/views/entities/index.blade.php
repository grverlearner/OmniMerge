@php
    /*
     * La biblioteca de entidades.
     *
     * Una entidad no se reconoce por su nombre: se reconoce por su CARA y por
     * lo que lleva encima —de qué tipo es, qué características tiene, en qué
     * colecciones está, cuántas versiones se le han hecho—. Por eso la imagen
     * está siempre, aunque sea pequeña, y por eso hay un modo que abre todo
     * eso dentro de la propia ficha sin salir de la lista.
     *
     * Cinco maneras de mirar:
     *
     *   galería      solo la cara y el nombre, en grande y sin nada alrededor
     *   cuadrícula   la cara y lo esencial, para abarcar muchas
     *   a fondo      además: características con su valor, colecciones, versiones
     *   lista        una línea por entidad
     *   tabla        para comparar cifras
     *
     * El filtrado y la ordenación los hace el SERVIDOR —ya existían y son
     * potentes, incluido el filtro por una característica y su valor— porque
     * la lista está paginada: filtrar en el cliente ordenaría una página, no
     * la biblioteca. Lo que vive en el cliente es la forma de mirar.
     */

    $estados = [
        '' => 'Cualquier estado',
        'ACTIVE' => 'Activa',
        'DRAFT' => 'Borrador',
        'ARCHIVED' => 'Archivada',
    ];

    $visibilidades = [
        '' => 'Cualquier visibilidad',
        'PRIVATE' => 'Privada',
        'PUBLIC' => 'Pública',
        'UNLISTED' => 'No listada',
    ];

    $conImagen = [
        '' => 'Con o sin imagen',
        'yes' => 'Solo con imagen',
        'no' => 'Solo sin imagen',
    ];

    $conAtributos = [
        '' => 'Con o sin características',
        'yes' => 'Solo con características',
        'no' => 'Solo sin características',
    ];

    $ordenes = [
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguas',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'code_asc' => 'Código ↑',
        'code_desc' => 'Código ↓',
        'attributes_desc' => 'Más características',
        'attributes_asc' => 'Menos características',
        'collections_desc' => 'Más colecciones',
        'collections_asc' => 'Menos colecciones',
        'views_desc' => 'Más vistas',
        'clones_desc' => 'Más copiadas',
    ];

    $filtrando =
        $search !== '' ||
        $status ||
        $visibility ||
        $type ||
        $image ||
        $attributesState ||
        $collectionId ||
        $filterAttributeId;

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'DRAFT' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    /* La opción elegida, para poder nombrarla en el aviso de filtro activo */
    $atributoElegido = $filterAttributes->firstWhere('id', $filterAttributeId);

    $opcionElegida = $filterOptionId && $atributoElegido
        ? $atributoElegido->options->firstWhere('id', $filterOptionId)
        : null;
@endphp

<x-app-layout title="Entidades" surface="dark">

    <x-slot name="header">Entidades</x-slot>

    <div x-data="{
        view: 'grid',
        size: 4,

        /* El filtro por característica necesita saber qué opciones tiene */
        filterAttribute: @js((string) $filterAttributeId),
        options: @js($filterAttributes->mapWithKeys(fn($a) => [(string) $a->id => $a->options->map(fn($o) => ['id' => $o->id, 'name' => $o->name])])),

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.entities.view') ?? '{}');
                if (['gallery', 'grid', 'detail', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4, 5, 6].includes(g.size)) this.size = g.size;
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.entities.view',
                    JSON.stringify({ view: this.view, size: this.size }));
            } catch (e) {}
        },

        get currentOptions() {
            return this.options[this.filterAttribute] ?? [];
        },

        get columns() {
            /*
             * La galería cabe más apretada que la cuadrícula: como no lleva
             * texto debajo, una cara pequeña se sigue reconociendo.
             */
            if (this.view === 'gallery') {
                return {
                    2: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                    3: 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-5',
                    4: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                    5: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-8',
                    6: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-10',
                }[this.size];
            }

            if (this.view === 'detail') {
                return { 2: 'lg:grid-cols-1', 3: 'lg:grid-cols-2', 4: 'lg:grid-cols-2 xl:grid-cols-3', 5: 'lg:grid-cols-3 xl:grid-cols-4', 6: 'lg:grid-cols-4 xl:grid-cols-5' }[this.size];
            }

            return {
                2: 'sm:grid-cols-2',
                3: 'sm:grid-cols-2 lg:grid-cols-3',
                4: 'sm:grid-cols-3 lg:grid-cols-4',
                5: 'sm:grid-cols-3 lg:grid-cols-5',
                6: 'sm:grid-cols-4 lg:grid-cols-6',
            }[this.size];
        },
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header
            class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40">

            <span class="pointer-events-none absolute -right-24 -top-28 h-72 w-72 rounded-full bg-indigo-500/10 blur-3xl"></span>
            <span class="pointer-events-none absolute -bottom-32 left-1/4 h-64 w-64 rounded-full bg-violet-500/10 blur-3xl"></span>

            <div class="relative px-5 py-5">

                <div class="flex flex-wrap items-end gap-4">

                    <div class="min-w-0 flex-1">

                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-400">
                            Biblioteca · Creaciones
                        </p>

                        <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                            Entidades
                        </h1>

                        <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                            Todo lo que has creado. Cada entidad tiene su cara, su tipo, sus
                            características y las colecciones donde vive; desde aquí se puede
                            recorrer todo eso sin abrirlas una a una.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @can('create', App\Models\Entity::class)
                            <a href="{{ route('entities.bulk.create') }}"
                                class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-indigo-500/60 hover:text-indigo-300">
                                <x-omni-icon name="capas" size="h-4 w-4" />
                                Crear varias
                            </a>

                            <a href="{{ route('entities.bulk-edit.index') }}"
                                class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-violet-500/60 hover:text-violet-300">
                                <x-omni-icon name="controles" size="h-4 w-4" />
                                Editar en lote
                            </a>

                            <a href="{{ route('entities.create') }}"
                                class="flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-indigo-950/40 transition hover:bg-indigo-400">
                                <x-omni-icon name="mas" size="h-4 w-4" />
                                Nueva entidad
                            </a>
                        @endcan
                    </div>

                </div>


                {{-- ============ LAS CIFRAS ============ --}}

                <div class="mt-4 flex flex-wrap gap-2">

                    @foreach ([['Entidades', $stats['total'], 'text-white', []], ['Públicas', $stats['public'], 'text-sky-300', ['visibility' => 'PUBLIC']], ['Con características', $stats['with_attributes'], 'text-violet-300', ['attributes_state' => 'yes']], ['Archivadas', $stats['archived'], 'text-slate-400', ['status' => 'ARCHIVED']], ['Sin tipo', $stats['untyped'], 'text-amber-300', ['type' => 'none']]] as [$etiqueta, $valor, $color, $parametros])
                        <a href="{{ route('entities.index', $parametros) }}"
                            class="group flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 transition hover:border-slate-700">
                            <span class="font-mono text-lg font-black {{ $color }}">{{ $valor }}</span>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 transition group-hover:text-slate-400">
                                {{ $etiqueta }}
                            </span>
                        </a>
                    @endforeach

                </div>

            </div>

        </header>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('entities.index') }}" class="space-y-2 px-4 py-3">

                {{-- Primera fila: buscar y lo que más se usa --}}
                <div class="flex flex-wrap items-center gap-2">

                    <label class="relative min-w-[200px] flex-1">
                        <span class="sr-only">Buscar entidad</span>

                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>

                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre, código o descripción..."
                            class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">
                    </label>

                    {{-- Tipo: lleva su icono en la etiqueta --}}
                    <select name="type" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Cualquier tipo</option>
                        <option value="none" @selected($type === 'none')>Sin tipo</option>

                        @foreach ($entityTypes as $tipoEntidad)
                            <option value="{{ $tipoEntidad->id }}" @selected((string) $type === (string) $tipoEntidad->id)>
                                {{ $tipoEntidad->icon }} {{ $tipoEntidad->name }} ({{ $tipoEntidad->entities_count }})
                            </option>
                        @endforeach
                    </select>

                    <select name="collection" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Cualquier colección</option>

                        @foreach ($collections as $coleccion)
                            <option value="{{ $coleccion->id }}" @selected((int) $collectionId === $coleccion->id)>
                                {{ $coleccion->name }} ({{ $coleccion->entities_count }})
                            </option>
                        @endforeach
                    </select>

                    @foreach ([['status', $estados, $status], ['visibility', $visibilidades, $visibility], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                        <select name="{{ $campo }}" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($opciones as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endforeach

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                        Buscar
                    </button>


                    {{-- ============ CÓMO MIRAR ============ --}}

                    <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                        @foreach ([['gallery', 'galeria', 'Galería: solo la cara y el nombre'], ['grid', 'cuadricula', 'Cuadrícula: la cara y lo esencial'], ['detail', 'capas', 'A fondo: características, colecciones y versiones'], ['list', 'controles', 'Lista: una línea por entidad'], ['table', 'grafo', 'Tabla: para comparar cifras']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="view === '{{ $modo }}'"
                                :class="view === '{{ $modo }}' ?
                                    'bg-indigo-500 text-white' :
                                    'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

                    <span x-show="view !== 'list' && view !== 'table'" x-cloak
                        class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                        <button type="button" @click="size = Math.max(2, size - 1)" :disabled="size === 2"
                            title="Más grandes"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                        </button>

                        <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="size"></span>

                        <button type="button" @click="size = Math.min(6, size + 1)" :disabled="size === 6"
                            title="Más pequeñas"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                        </button>
                    </span>

                </div>


                {{-- Segunda fila: los filtros finos --}}
                <div class="flex flex-wrap items-center gap-2 border-t border-slate-800/70 pt-2">

                    @foreach ([['image', $conImagen, $image], ['attributes_state', $conAtributos, $attributesState]] as [$campo, $opciones, $actual])
                        <select name="{{ $campo }}" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-900 py-1.5 text-[10px] font-bold text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($opciones as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endforeach

                    {{--
                        El filtro por característica.

                        Es el más potente de todos —«enséñame las que tienen
                        continente = Sudamérica»— y estaba escondido. Las
                        opciones del segundo desplegable dependen del primero,
                        y por eso viven en el cliente: ir al servidor para
                        rellenar un desplegable sería una recarga por elegir.
                    --}}
                    <span class="flex items-center gap-1.5 rounded-xl border border-violet-500/25 bg-violet-500/5 px-2 py-1">
                        <span class="text-[9px] font-black uppercase tracking-wider text-violet-300">Tiene</span>

                        <select name="filter_attribute" x-model="filterAttribute" onchange="this.form.submit()"
                            class="rounded-lg border-slate-800 bg-slate-900 py-1 text-[10px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">cualquier característica</option>

                            @foreach ($filterAttributes as $atributo)
                                <option value="{{ $atributo->id }}">{{ $atributo->name }}</option>
                            @endforeach
                        </select>

                        <span class="text-[9px] font-black text-violet-400" x-show="filterAttribute">=</span>

                        <select name="filter_option" x-show="filterAttribute" x-cloak onchange="this.form.submit()"
                            class="rounded-lg border-slate-800 bg-slate-900 py-1 text-[10px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">cualquier valor</option>

                            <template x-for="opcion in currentOptions" :key="opcion.id">
                                <option :value="opcion.id" :selected="opcion.id === {{ (int) $filterOptionId }}"
                                    x-text="opcion.name"></option>
                            </template>
                        </select>
                    </span>

                    <select name="per_page" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-1.5 text-[10px] font-bold text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ([12, 24, 48, 96] as $cuantas)
                            <option value="{{ $cuantas }}" @selected($perPage === $cuantas)>{{ $cuantas }} por página</option>
                        @endforeach
                    </select>

                    @if ($filtrando)
                        <a href="{{ route('entities.index') }}"
                            class="rounded-xl border border-rose-500/30 px-3 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500/10">
                            Quitar filtros
                        </a>
                    @endif

                    <span class="ml-auto font-mono text-[10px] text-slate-600">
                        {{ $entities->total() }}
                        {{ $entities->total() === 1 ? 'entidad' : 'entidades' }}
                        @if ($filtrando)
                            <span class="text-violet-400">filtradas</span>
                        @endif
                    </span>

                </div>

            </form>

        </div>


        {{-- Si hay un filtro por característica puesto, se dice en palabras --}}
        @if ($atributoElegido)
            <p class="flex flex-wrap items-center gap-2 rounded-xl border border-violet-500/25 bg-violet-500/5 px-4 py-2.5 text-[11px]">
                <span class="font-black text-violet-300">Filtrando por característica:</span>
                <span class="text-slate-300">
                    {{ $atributoElegido->name }}
                    @if ($opcionElegida)
                        = <strong class="text-white">{{ $opcionElegida->name }}</strong>
                    @else
                        <span class="text-slate-500">(cualquier valor)</span>
                    @endif
                </span>
            </p>
        @endif


        {{-- ===================================================== --}}
        {{-- LAS ENTIDADES --}}
        {{-- ===================================================== --}}

        @if ($entities->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700">
                    <x-omni-icon name="chispa" size="h-10 w-10" />
                </span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ninguna entidad encaja' : 'Todavía no has creado nada' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba a quitar algún filtro: puede que lo que buscas esté archivado, sin tipo o sin esa característica.'
                        : 'Una entidad es cualquier cosa que quieras hacer competir: un personaje, un país, un equipo. Se le ponen características y se organiza en colecciones.' }}
                </p>

                @if ($filtrando)
                    <a href="{{ route('entities.index') }}"
                        class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                        Quitar los filtros
                    </a>
                @else
                    @can('create', App\Models\Entity::class)
                        <a href="{{ route('entities.create') }}"
                            class="mt-4 inline-block rounded-xl bg-indigo-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-indigo-400">
                            + Crear la primera
                        </a>
                    @endcan
                @endif
            </div>

        @else

            {{-- ============ GALERÍA ============ --}}

            {{--
                Una pared de caras. Sin cifras, sin botones y sin separación
                entre fichas: aquí no se trabaja, se mira.
            --}}

            <div x-show="view === 'gallery'" x-cloak class="grid gap-2" :class="columns">
                @foreach ($entities as $entity)
                    @include('entities.partials.library-poster', ['entidad' => $entity])
                @endforeach
            </div>


            {{-- ============ CUADRÍCULA Y A FONDO ============ --}}

            <div x-show="view === 'grid' || view === 'detail'" class="grid gap-3" :class="columns">
                @foreach ($entities as $entity)
                    @include('entities.partials.library-card', [
                        'entidad' => $entity,
                        'estadoTono' => $estadoTono,
                    ])
                @endforeach
            </div>


            {{-- ============ LISTA ============ --}}

            <div x-show="view === 'list'" x-cloak class="space-y-2">
                @foreach ($entities as $entity)
                    @include('entities.partials.library-row', [
                        'entidad' => $entity,
                        'estadoTono' => $estadoTono,
                    ])
                @endforeach
            </div>


            {{-- ============ TABLA ============ --}}

            <div x-show="view === 'table'" x-cloak
                class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full min-w-[900px]">

                    <thead class="border-b border-slate-800 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-2.5">Entidad</th>
                            <th class="px-3 py-2.5">Tipo</th>
                            <th class="px-3 py-2.5">Estado</th>
                            <th class="px-3 py-2.5">Visibilidad</th>
                            <th class="px-3 py-2.5 text-right">Características</th>
                            <th class="px-3 py-2.5 text-right">Colecciones</th>
                            <th class="px-3 py-2.5 text-right">Versiones</th>
                            <th class="px-3 py-2.5 text-right">Vistas</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($entities as $entity)
                            <tr class="transition hover:bg-slate-900/60">

                                <td class="px-3 py-2">
                                    <a href="{{ route('entities.show', $entity) }}" class="flex items-center gap-2">
                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($entity->base_display_image_url)
                                                <img src="{{ $entity->base_display_image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-600">
                                                    {{ $entity->entityType?->icon ?: mb_strtoupper(mb_substr($entity->name, 0, 1)) }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block truncate text-[12px] font-black text-white">{{ $entity->name }}</span>
                                            <span class="block font-mono text-[9px] text-slate-600">{{ $entity->code }}</span>
                                        </span>
                                    </a>
                                </td>

                                <td class="px-3 py-2">
                                    @if ($entity->entityType)
                                        <span class="flex items-center gap-1.5 text-[11px] font-bold text-slate-300">
                                            <span class="h-2 w-2 rounded-full"
                                                style="background-color: {{ $entity->entityType->color ?: '#64748b' }}"></span>
                                            {{ $entity->entityType->name }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-700">Sin tipo</span>
                                    @endif
                                </td>

                                <td class="px-3 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$entity->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $entity->status_label }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-[11px] text-slate-400">{{ $entity->visibility_label }}</td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] text-violet-300">
                                    {{ $entity->entity_attributes_count }}</td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] text-cyan-300">
                                    {{ $entity->collections_count }}</td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] text-amber-300">
                                    {{ $entity->entity_versions_count }}</td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400">
                                    {{ $entity->views_count }}</td>

                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('entities.show', $entity) }}"
                                        class="text-[10px] font-black text-slate-400 transition hover:text-indigo-300">
                                        Ver →
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>


            <div class="mt-6">
                {{ $entities->links() }}
            </div>

        @endif

    </div>

</x-app-layout>

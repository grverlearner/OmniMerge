@php
    /*
     * El índice de atributos.
     *
     * Un atributo de tipo catálogo **es** sus valores —«Clanes» no significa
     * nada sin Uzumaki, Uchiha y los otros cincuenta— y la pantalla no cargaba
     * ninguno: se distinguían por el nombre y por un número.
     *
     * Cinco maneras de mirar, y la que importa es nueva:
     *
     *   galería    la cara del atributo y su nombre
     *   valores    las CARAS de sus opciones ← lo que distingue un catálogo
     *   cuadrícula la ficha con sus etiquetas y sus interruptores
     *   lista      una línea por atributo
     *   tabla      para comparar tipo, uso y ajustes de un vistazo
     *
     * Y dos cifras que señalan trabajo pendiente y no estaban: los catálogos
     * **sin valores** —que no sirven para nada— y los atributos **que no usa
     * ninguna entidad**. Las dos son enlaces que filtran.
     */

    $tonoTipo = [
        'OPTION' => ['Catálogo', 'border-violet-500/30 bg-violet-500/10 text-violet-300', '#8b5cf6'],
        'BOOLEAN' => ['Sí / No', 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300', '#10b981'],
        'TEXT' => ['Texto corto', 'border-slate-700 bg-slate-800/60 text-slate-300', '#64748b'],
        'LONG_TEXT' => ['Texto largo', 'border-slate-700 bg-slate-800/60 text-slate-300', '#64748b'],
        'INTEGER' => ['Número entero', 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300', '#06b6d4'],
        'DECIMAL' => ['Número decimal', 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300', '#06b6d4'],
        'DATE' => ['Fecha', 'border-amber-500/30 bg-amber-500/10 text-amber-300', '#f59e0b'],
        'COLOR' => ['Color', 'border-fuchsia-500/30 bg-fuchsia-500/10 text-fuchsia-300', '#d946ef'],
    ];

    $tonoEstado = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $estadoEtiqueta = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
        'ARCHIVED' => 'Archivado',
    ];

    $ordenes = [
        'manual' => 'Orden manual',
        'newest' => 'Los más nuevos',
        'oldest' => 'Los más antiguos',
        'name_asc' => 'Nombre (A–Z)',
        'name_desc' => 'Nombre (Z–A)',
        'catalog_desc' => 'Más valores',
        'catalog_asc' => 'Menos valores',
        'usage_desc' => 'Más usados',
        'usage_asc' => 'Menos usados',
        'code_asc' => 'Código (A–Z)',
        'code_desc' => 'Código (Z–A)',
    ];

    $filtrando =
        $search !== '' ||
        $dataType ||
        $status ||
        $scope ||
        $multiple ||
        $usage ||
        $filling ||
        $featured ||
        $groupId;
@endphp

<x-app-layout title="Atributos" surface="dark">

    <x-slot name="header">Atributos</x-slot>

    <div x-data="{
        view: 'values',
        size: 4,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.attributes.view') ?? '{}');
                if (['gallery', 'values', 'grid', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4, 5].includes(g.size)) this.size = g.size;
            } catch (e) {}

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.attributes.view',
                    JSON.stringify({ view: this.view, size: this.size }));
            } catch (e) {}
        },

        get columns() {
            return {
                2: 'grid-cols-1 sm:grid-cols-2',
                3: 'grid-cols-2 sm:grid-cols-3',
                4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                5: 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-5',
            }[this.size];
        },
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('entities.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Entidades
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Atributos</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Los datos que describen a tus entidades: fuerza, clan, edad. Se crean una vez y valen
                    para toda la biblioteca.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('attributes.structure.index') }}"
                    class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-cyan-500 hover:text-cyan-300">
                    <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                    Estructura
                </a>

                @can('create', App\Models\Attribute::class)
                    <a href="{{ route('attributes.create') }}"
                        class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                        Nuevo atributo
                    </a>
                @endcan
            </div>
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid gap-2 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,340px)]">

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ([['Atributos', $stats['total'], 'text-white', []], ['Catálogos', $stats['catalog'], 'text-violet-300', ['data_type' => 'OPTION']], ['En uso', $stats['used'], 'text-emerald-300', ['usage' => 'used']], ['Sin usar', $stats['unused'], 'text-slate-400', ['usage' => 'unused']], ['Catálogos vacíos', $stats['empty_catalogs'], 'text-rose-300', ['filling' => 'empty']], ['Destacados', $stats['featured'], 'text-amber-300', ['featured' => 'yes']]] as [$etiqueta, $valor, $tono, $parametros])
                    <a href="{{ route('attributes.index', $parametros) }}"
                        class="group flex items-baseline gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-2.5 py-1.5 transition hover:border-slate-700">
                        <span class="font-mono text-base font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 transition group-hover:text-slate-400">{{ $etiqueta }}</span>
                    </a>
                @endforeach
            </div>


            {{-- De qué está hecha la biblioteca --}}
            @if ($reparto->isNotEmpty())
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Por tipo</p>

                    <div class="mt-1.5 flex h-2 overflow-hidden rounded-full bg-slate-950">
                        @foreach ($reparto as $tipo => $cuantos)
                            <span class="h-full transition"
                                style="width: {{ $stats['total'] > 0 ? ($cuantos / $stats['total']) * 100 : 0 }}%; background-color: {{ $tonoTipo[$tipo][2] ?? '#64748b' }}"
                                title="{{ $tonoTipo[$tipo][0] ?? $tipo }}: {{ $cuantos }}"></span>
                        @endforeach
                    </div>

                    <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5">
                        @foreach ($reparto as $tipo => $cuantos)
                            <a href="{{ route('attributes.index', ['data_type' => $tipo]) }}"
                                class="flex items-center gap-1 text-[9px] font-bold text-slate-500 transition hover:text-slate-200">
                                <span class="h-2 w-2 rounded-sm"
                                    style="background-color: {{ $tonoTipo[$tipo][2] ?? '#64748b' }}"></span>
                                {{ $tonoTipo[$tipo][0] ?? $tipo }}
                                <span class="font-mono text-slate-400">{{ $cuantos }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>


        {{-- ===================================================== --}}
        {{-- CATÁLOGOS VACÍOS --}}
        {{-- ===================================================== --}}

        {{--
            La única cifra de esta pantalla que señala algo roto: un catálogo
            sin valores no se puede asignar a nada, así que no hace nada.
        --}}

        @if ($stats['empty_catalogs'] > 0 && $filling !== 'empty')
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-rose-500/25 bg-rose-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                    <x-omni-icon name="controles" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-rose-200/80">
                    <strong class="text-rose-200">{{ $stats['empty_catalogs'] }}</strong>
                    {{ $stats['empty_catalogs'] === 1 ? 'catálogo no tiene ningún valor' : 'catálogos no tienen ningún valor' }}.
                    Un catálogo vacío no se puede asignar a nada: sale en las listas y no hace nada.
                </p>

                <a href="{{ route('attributes.index', ['filling' => 'empty']) }}"
                    class="shrink-0 rounded-xl border border-rose-500/30 px-3 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                    Verlos
                </a>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('attributes.index') }}"
                class="flex flex-wrap items-center gap-2 px-4 py-3">

                <label class="relative min-w-[170px] flex-1">
                    <span class="sr-only">Buscar atributo</span>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Buscar por nombre, código o descripción…"
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                </label>

                @php
                    $tipos = ['' => 'Cualquier tipo'];
                    foreach ($tonoTipo as $clave => $datos) {
                        $tipos[$clave] = $datos[0];
                    }
                @endphp

                @foreach ([['data_type', $tipos, $dataType], ['status', ['' => 'Cualquier estado', 'ACTIVE' => 'Activos', 'INACTIVE' => 'Inactivos', 'ARCHIVED' => 'Archivados'], $status], ['scope', ['' => 'Cualquier visibilidad', 'PUBLIC' => 'Públicos', 'PRIVATE' => 'Privados'], $scope], ['usage', ['' => 'Usados o no', 'used' => 'Solo los usados', 'unused' => 'Solo los que nadie usa'], $usage], ['filling', ['' => 'Con o sin valores', 'filled' => 'Solo con valores', 'empty' => 'Catálogos vacíos'], $filling], ['multiple', ['' => 'Selección: da igual', 'yes' => 'Admiten varios', 'no' => 'Uno solo'], $multiple], ['featured', ['' => 'Destacados: da igual', 'yes' => 'Solo destacados'], $featured], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                    <select name="{{ $campo }}" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach ($opciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                @endforeach

                @if ($groups->isNotEmpty())
                    <select name="group" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Cualquier grupo</option>
                        @foreach ($groups as $grupo)
                            <option value="{{ $grupo->id }}" @selected($groupId === $grupo->id)>{{ $grupo->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="per_page" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                    @foreach ([12, 24, 48] as $cuantos)
                        <option value="{{ $cuantos }}" @selected($perPage === $cuantos)>{{ $cuantos }} por página</option>
                    @endforeach
                </select>

                <button type="submit"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Buscar
                </button>

                @if ($filtrando)
                    <a href="{{ route('attributes.index') }}"
                        class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar filtros
                    </a>
                @endif

                <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['gallery', 'galeria', 'Galería: la cara y el nombre'], ['values', 'capas', 'Valores: las caras de cada catálogo'], ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'], ['list', 'menu', 'Lista: una línea por atributo'], ['table', 'controles', 'Tabla: para comparar ajustes']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="view === '{{ $modo }}'"
                            :class="view === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="['gallery', 'values', 'grid'].includes(view)" x-cloak
                    class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    <button type="button" @click="size = Math.max(2, size - 1)" :disabled="size === 2" title="Más grandes"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>
                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="size"></span>
                    <button type="button" @click="size = Math.min(5, size + 1)" :disabled="size === 5" title="Más pequeños"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>
            </form>
        </div>


        {{-- ===================================================== --}}
        {{-- LOS ATRIBUTOS --}}
        {{-- ===================================================== --}}

        @if ($attributes->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="controles" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ningún atributo encaja' : 'Todavía no has creado ninguno' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba a quitar algún filtro: puede que el que buscas esté archivado o sea de otro tipo.'
                        : 'Un atributo es un dato que describe a tus entidades. «Clan» es un catálogo con valores; «Fuerza» un número; «Vivo» un sí o no. Se crean una vez y valen para toda la biblioteca.' }}
                </p>

                @if ($filtrando)
                    <a href="{{ route('attributes.index') }}"
                        class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Quitar los filtros
                    </a>
                @else
                    @can('create', App\Models\Attribute::class)
                        <a href="{{ route('attributes.create') }}"
                            class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            + Crear el primero
                        </a>
                    @endcan
                @endif
            </div>

        @else

            {{-- ============ GALERÍA ============ --}}

            <div x-show="view === 'gallery'" x-cloak class="grid gap-2.5" :class="columns">
                @foreach ($attributes as $atributo)
                    @php $acento = $atributo->color ?: ($tonoTipo[$atributo->data_type][2] ?? '#8b5cf6'); @endphp

                    <a href="{{ route('attributes.show', $atributo) }}"
                        class="group relative block overflow-hidden rounded-2xl border bg-slate-950 transition duration-300 hover:-translate-y-1"
                        style="border-color: {{ $acento }}40">

                        <span class="block aspect-[4/3] overflow-hidden">
                            @if ($atributo->image_url)
                                <img src="{{ $atributo->image_url }}" alt="{{ $atributo->name }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-4xl"
                                    style="color: {{ $acento }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                                    {{ $atributo->icon ?: $atributo->data_type_icon }}
                                </span>
                            @endif
                        </span>

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-2.5 pb-2 pt-8">
                            <span class="block truncate text-[12px] font-black text-white">{{ $atributo->name }}</span>
                            <span class="block text-[9px] font-black" style="color: {{ $acento }}">
                                {{ $atributo->data_type_label }}
                                @if ($atributo->data_type === 'OPTION')
                                    · {{ $atributo->options_count }} valores
                                @endif
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>


            {{-- ============ VALORES ============ --}}

            {{--
                La vista que faltaba. «Clanes» no significa nada sin ver que
                dentro están Uzumaki y Uchiha; hasta ahora había que entrar.
            --}}

            <div x-show="view === 'values'" class="grid gap-3" :class="columns">
                @foreach ($attributes as $atributo)
                    @php
                        $acento = $atributo->color ?: ($tonoTipo[$atributo->data_type][2] ?? '#8b5cf6');
                        $valores = $atributo->options->take(8);
                    @endphp

                    <article class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
                        style="border-color: {{ $acento }}40">

                        <div class="flex items-center gap-2.5 border-b border-slate-800 p-2.5">
                            <a href="{{ route('attributes.show', $atributo) }}"
                                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($atributo->image_url)
                                    <img src="{{ $atributo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-base"
                                        style="color: {{ $acento }}">{{ $atributo->icon ?: $atributo->data_type_icon }}</span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('attributes.show', $atributo) }}"
                                    class="block truncate text-[12px] font-black text-white">{{ $atributo->name }}</a>
                                <p class="truncate text-[9px]" style="color: {{ $acento }}">
                                    {{ $atributo->data_type_label }}
                                    <span class="text-slate-600">
                                        · {{ $atributo->entity_attributes_count }}
                                        {{ $atributo->entity_attributes_count === 1 ? 'entidad' : 'entidades' }}
                                    </span>
                                </p>
                            </div>

                            @if ($atributo->is_featured)
                                <span class="shrink-0 text-amber-400" title="Destacado">★</span>
                            @endif
                        </div>

                        @if ($atributo->data_type !== 'OPTION')
                            <div class="flex flex-1 items-center justify-center p-5 text-center">
                                <p class="text-[10px] leading-relaxed text-slate-600">
                                    No es un catálogo: su valor se escribe a mano.
                                    @if ($atributo->unit)
                                        <span class="block text-slate-500">Se mide en {{ $atributo->unit }}.</span>
                                    @endif
                                </p>
                            </div>
                        @elseif ($valores->isEmpty())
                            <div class="flex flex-1 items-center justify-center p-5">
                                <p class="text-center text-[10px] leading-relaxed text-rose-300/80">
                                    Catálogo vacío: no se puede asignar a nada.
                                    @can('update', $atributo)
                                        <a href="{{ route('attributes.show', $atributo) }}"
                                            class="mt-1 block font-black underline">Añadirle valores →</a>
                                    @endcan
                                </p>
                            </div>
                        @else
                            <div class="grid flex-1 grid-cols-4 gap-px bg-slate-800/60">
                                @foreach ($valores as $valor)
                                    <span class="group/v relative block aspect-square overflow-hidden bg-slate-950"
                                        title="{{ $valor->name }}">
                                        @if ($valor->image_url)
                                            <img src="{{ $valor->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover/v:scale-110">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-700">◇</span>
                                        @endif

                                        <span class="absolute inset-x-0 bottom-0 truncate bg-slate-950/85 px-1 py-0.5 text-[8px] font-black text-slate-300 opacity-0 transition group-hover/v:opacity-100">
                                            {{ $valor->name }}
                                        </span>
                                    </span>
                                @endforeach

                                @if ($atributo->options_count > 8)
                                    <a href="{{ route('attributes.show', $atributo) }}"
                                        class="flex aspect-square items-center justify-center bg-slate-950 text-[11px] font-black transition hover:bg-slate-900"
                                        style="color: {{ $acento }}">
                                        +{{ $atributo->options_count - 8 }}
                                    </a>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">
                            <a href="{{ route('attributes.show', $atributo) }}"
                                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">Ver</a>

                            @can('update', $atributo)
                                <a href="{{ route('attributes.edit', $atributo) }}"
                                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">✎ Editar</a>
                            @endcan

                            <span class="ml-auto rounded-lg border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoTipo[$atributo->data_type][1] ?? 'border-slate-700 text-slate-400' }}">
                                {{ $atributo->data_type === 'OPTION' ? $atributo->options_count . ' valores' : $atributo->data_type_label }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>


            {{-- ============ CUADRÍCULA ============ --}}

            <div x-show="view === 'grid'" x-cloak class="grid gap-3" :class="columns">
                @foreach ($attributes as $atributo)
                    @include('attributes.partials.library-card', [
                        'atributo' => $atributo,
                        'tonoTipo' => $tonoTipo,
                        'tonoEstado' => $tonoEstado,
                        'estadoEtiqueta' => $estadoEtiqueta,
                    ])
                @endforeach
            </div>


            {{-- ============ LISTA ============ --}}

            <div x-show="view === 'list'" x-cloak class="space-y-1.5">
                @foreach ($attributes as $atributo)
                    @php $acento = $atributo->color ?: ($tonoTipo[$atributo->data_type][2] ?? '#8b5cf6'); @endphp

                    <article class="flex items-center gap-3 rounded-xl border bg-slate-900/50 p-2 transition hover:bg-slate-900"
                        style="border-color: {{ $acento }}30">

                        <a href="{{ route('attributes.show', $atributo) }}"
                            class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($atributo->image_url)
                                <img src="{{ $atributo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-base"
                                    style="color: {{ $acento }}">{{ $atributo->icon ?: $atributo->data_type_icon }}</span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('attributes.show', $atributo) }}"
                                    class="truncate text-[12px] font-black text-white">{{ $atributo->name }}</a>

                                @if ($atributo->is_featured)
                                    <span class="text-[10px] text-amber-400" title="Destacado">★</span>
                                @endif

                                <span class="rounded border px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoTipo[$atributo->data_type][1] ?? 'border-slate-700 text-slate-400' }}">
                                    {{ $atributo->data_type_label }}
                                </span>

                                @if ($atributo->status !== 'ACTIVE')
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoEstado[$atributo->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $estadoEtiqueta[$atributo->status] ?? $atributo->status }}
                                    </span>
                                @endif
                            </div>

                            <p class="truncate text-[10px] text-slate-500">
                                {{ $atributo->description ?: 'Sin descripción.' }}
                            </p>
                        </div>

                        {{-- Los valores, en pequeño --}}
                        @if ($atributo->data_type === 'OPTION' && $atributo->options->isNotEmpty())
                            <span class="hidden shrink-0 -space-x-2 sm:flex">
                                @foreach ($atributo->options->take(4) as $valor)
                                    <span class="h-7 w-7 overflow-hidden rounded-lg border-2 border-slate-900 bg-slate-950"
                                        title="{{ $valor->name }}">
                                        @if ($valor->image_url)
                                            <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                @endforeach
                            </span>
                        @endif

                        <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black"
                            style="border-color: {{ $acento }}40; color: {{ $atributo->entity_attributes_count > 0 ? $acento : '#475569' }}"
                            title="Entidades que lo usan">
                            {{ $atributo->entity_attributes_count }}
                        </span>

                        @can('update', $atributo)
                            <a href="{{ route('attributes.edit', $atributo) }}"
                                class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>
                        @endcan
                    </article>
                @endforeach
            </div>


            {{-- ============ TABLA ============ --}}

            <div x-show="view === 'table'" x-cloak
                class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full min-w-[900px]">
                    <thead class="border-b border-slate-800 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-2.5">Atributo</th>
                            <th class="px-3 py-2.5">Tipo</th>
                            <th class="px-3 py-2.5 text-right">Valores</th>
                            <th class="px-3 py-2.5 text-right">Lo usan</th>
                            <th class="px-3 py-2.5 text-center">Varios</th>
                            <th class="px-3 py-2.5 text-center">Filtrable</th>
                            <th class="px-3 py-2.5 text-center">Comparable</th>
                            <th class="px-3 py-2.5">Visibilidad</th>
                            <th class="px-3 py-2.5">Estado</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($attributes as $atributo)
                            @php $acento = $atributo->color ?: ($tonoTipo[$atributo->data_type][2] ?? '#8b5cf6'); @endphp

                            <tr class="transition hover:bg-slate-900/60">
                                <td class="px-3 py-2">
                                    <a href="{{ route('attributes.show', $atributo) }}" class="flex items-center gap-2">
                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($atributo->image_url)
                                                <img src="{{ $atributo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[11px]"
                                                    style="color: {{ $acento }}">{{ $atributo->icon ?: $atributo->data_type_icon }}</span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-[12px] font-black text-white">{{ $atributo->name }}</span>
                                            <span class="block font-mono text-[9px] text-slate-600">{{ $atributo->code }}</span>
                                        </span>
                                    </a>
                                </td>

                                <td class="px-3 py-2">
                                    <span class="rounded border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoTipo[$atributo->data_type][1] ?? 'border-slate-700 text-slate-400' }}">
                                        {{ $atributo->data_type_label }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px]">
                                    @if ($atributo->data_type === 'OPTION')
                                        <span class="{{ $atributo->options_count > 0 ? '' : 'text-rose-300' }}"
                                            style="{{ $atributo->options_count > 0 ? 'color: ' . $acento : '' }}">
                                            {{ $atributo->options_count }}
                                        </span>
                                    @else
                                        <span class="text-slate-700">—</span>
                                    @endif
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $atributo->entity_attributes_count > 0 ? 'text-emerald-300' : 'text-slate-700' }}">
                                    {{ $atributo->entity_attributes_count }}
                                </td>

                                @foreach ([$atributo->allows_multiple, $atributo->is_filterable, $atributo->is_comparable] as $bandera)
                                    <td class="px-3 py-2 text-center">
                                        <span class="{{ $bandera ? 'text-emerald-400' : 'text-slate-800' }}">
                                            {{ $bandera ? '✓' : '·' }}
                                        </span>
                                    </td>
                                @endforeach

                                <td class="px-3 py-2 text-[11px] text-slate-400">{{ $atributo->scope_label }}</td>

                                <td class="px-3 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$atributo->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $estadoEtiqueta[$atributo->status] ?? $atributo->status }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('attributes.show', $atributo) }}"
                                        class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">Ver →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


            <div class="mt-6">{{ $attributes->links() }}</div>

        @endif

    </div>

</x-app-layout>

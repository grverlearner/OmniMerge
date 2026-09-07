@php
    /*
     * El índice de colecciones.
     *
     * Una colección es lo único de la biblioteca que **no se define por sus
     * datos sino por lo que agrupa**, y la pantalla no enseñaba nada de dentro:
     * cuatro modos de vista que se diferenciaban en el tamaño de la misma
     * tarjeta, y un número de entidades como único indicio.
     *
     * Ahora hay cinco maneras de mirar, y la que importa es nueva:
     *
     *   galería     la portada y el nombre, para reconocerlas de un vistazo
     *   contenido   las CARAS de lo que hay dentro ← lo que las distingue
     *   cuadrícula  la ficha con sus etiquetas y sus acciones
     *   lista       una línea por colección, para repasar muchas
     *   tabla       para comparar tamaño, visibilidad y uso
     *
     * El color de cada colección es un dato suyo, no una decisión de diseño,
     * así que viaja en `style` —una clase de Tailwind compuesta no existiría en
     * el CSS— y tiñe su borde, su cifra y su icono.
     */

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $estadoEtiqueta = [
        'ACTIVE' => 'Activa',
        'INACTIVE' => 'Inactiva',
        'ARCHIVED' => 'Archivada',
    ];

    $ordenes = [
        'newest' => 'Las más nuevas',
        'oldest' => 'Las más antiguas',
        'name_asc' => 'Nombre (A–Z)',
        'name_desc' => 'Nombre (Z–A)',
        'entities_desc' => 'Las más llenas',
        'entities_asc' => 'Las más vacías',
        'views_desc' => 'Las más vistas',
        'clones_desc' => 'Las más copiadas',
        'code_asc' => 'Código (A–Z)',
        'code_desc' => 'Código (Z–A)',
    ];

    $filtrando =
        $search !== '' ||
        $status ||
        $visibility ||
        $image ||
        $content ||
        $origin ||
        $sharing;
@endphp

<x-app-layout title="Colecciones" surface="dark">

    <x-slot name="header">Colecciones</x-slot>

    <div x-data="{
        view: 'content',
        size: 4,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.collections.view') ?? '{}');
                if (['gallery', 'content', 'grid', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4, 5].includes(g.size)) this.size = g.size;
            } catch (e) {}

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.collections.view',
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

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Colecciones</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Agrupaciones que tú decides: una franquicia, un equipo, una lista de favoritas.
                    Una entidad puede estar en varias a la vez.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ([['Colecciones', $stats['total'], 'text-white', []], ['Públicas', $stats['public'], 'text-emerald-300', ['visibility' => 'PUBLIC']], ['Con contenido', $stats['with_entities'], 'text-violet-300', ['content' => 'yes']], ['Vacías', $stats['empty'], 'text-amber-300', ['content' => 'no']], ['Copiables', $stats['shareable'], 'text-cyan-300', ['sharing' => 'yes']]] as [$etiqueta, $valor, $tono, $parametros])
                    <a href="{{ route('collections.index', $parametros) }}"
                        class="group flex items-baseline gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-2.5 py-1.5 transition hover:border-slate-700">
                        <span class="font-mono text-base font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 transition group-hover:text-slate-400">{{ $etiqueta }}</span>
                    </a>
                @endforeach
            </div>
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

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
        {{-- QUÉ QUEDA FUERA --}}
        {{-- ===================================================== --}}

        {{--
            La única cifra de esta pantalla sobre la que se puede actuar: las
            entidades que no están en ninguna colección. Sin esto, «tengo 6
            colecciones» no dice si la biblioteca está ordenada o no.
        --}}

        @if ($stats['uncovered'] > 0 || $stats['covered'] > 0)
            @php
                $totalEntidades = $stats['covered'] + $stats['uncovered'];
                $porcentaje = $totalEntidades > 0 ? (int) round(($stats['covered'] / $totalEntidades) * 100) : 0;
            @endphp

            <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/50 px-4 py-3">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
                    <x-omni-icon name="barras" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[12px] font-black text-white">
                        {{ $stats['covered'] }} de {{ $totalEntidades }} entidades están en alguna colección
                    </p>

                    <div class="mt-1 flex items-center gap-2.5">
                        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-950">
                            <span class="block h-full rounded-full {{ $porcentaje >= 90 ? 'bg-emerald-500' : ($porcentaje >= 50 ? 'bg-indigo-500' : 'bg-amber-500') }}"
                                style="width: {{ max($porcentaje, 2) }}%"></span>
                        </span>
                        <span class="shrink-0 font-mono text-[10px] font-black text-slate-500">{{ $porcentaje }}%</span>
                    </div>

                    <p class="mt-1 text-[10px] text-slate-500">
                        @if ($stats['uncovered'] === 0)
                            <span class="font-black text-emerald-300">Ninguna se queda fuera.</span>
                        @else
                            <strong class="text-amber-300">{{ $stats['uncovered'] }}</strong>
                            {{ $stats['uncovered'] === 1 ? 'entidad no está' : 'entidades no están' }} en ninguna.
                            No es un error —una entidad no tiene por qué pertenecer a nada—, pero conviene saberlo.
                        @endif
                    </p>
                </div>

                @can('create', App\Models\Collection::class)
                    <a href="{{ route('collections.create') }}"
                        class="shrink-0 rounded-xl bg-violet-500/15 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                        + Nueva colección
                    </a>
                @endcan
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('collections.index') }}"
                class="flex flex-wrap items-center gap-2 px-4 py-3">

                <label class="relative min-w-[170px] flex-1">
                    <span class="sr-only">Buscar colección</span>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Buscar por nombre, código o descripción…"
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                </label>

                @foreach ([['visibility', ['' => 'Cualquier visibilidad', 'PUBLIC' => 'Públicas', 'UNLISTED' => 'No listadas', 'PRIVATE' => 'Privadas'], $visibility], ['status', ['' => 'Cualquier estado', 'ACTIVE' => 'Activas', 'INACTIVE' => 'Inactivas', 'ARCHIVED' => 'Archivadas'], $status], ['content', ['' => 'Llenas o vacías', 'yes' => 'Solo con contenido', 'no' => 'Solo las vacías'], $content], ['image', ['' => 'Portada: da igual', 'yes' => 'Con portada', 'no' => 'Sin portada'], $image], ['origin', ['' => 'Propias o clonadas', 'own' => 'Solo propias', 'cloned' => 'Solo clonadas'], $origin], ['sharing', ['' => 'Copiables: da igual', 'yes' => 'Solo copiables', 'no' => 'Solo cerradas'], $sharing], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                    <select name="{{ $campo }}" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach ($opciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                @endforeach

                <select name="per_page" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                    @foreach ([12, 24, 48, 96] as $cuantas)
                        <option value="{{ $cuantas }}" @selected($perPage === $cuantas)>{{ $cuantas }} por página</option>
                    @endforeach
                </select>

                <button type="submit"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Buscar
                </button>

                @if ($filtrando)
                    <a href="{{ route('collections.index') }}"
                        class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar filtros
                    </a>
                @endif

                @can('create', App\Models\Collection::class)
                    <a href="{{ route('collections.create') }}" title="Crear una colección"
                        class="flex items-center gap-1.5 rounded-xl bg-violet-500/15 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                        <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                        Nueva
                    </a>
                @endcan

                <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['gallery', 'galeria', 'Galería: solo la portada y el nombre'], ['content', 'capas', 'Contenido: las caras de lo que hay dentro'], ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'], ['list', 'menu', 'Lista: una línea por colección'], ['table', 'controles', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="view === '{{ $modo }}'"
                            :class="view === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="['gallery', 'content', 'grid'].includes(view)" x-cloak
                    class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    <button type="button" @click="size = Math.max(2, size - 1)" :disabled="size === 2" title="Más grandes"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>
                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="size"></span>
                    <button type="button" @click="size = Math.min(5, size + 1)" :disabled="size === 5" title="Más pequeñas"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>
            </form>
        </div>


        {{-- ===================================================== --}}
        {{-- LAS COLECCIONES --}}
        {{-- ===================================================== --}}

        @if ($collections->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="capas" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ninguna colección encaja' : 'Todavía no has agrupado nada' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba a quitar algún filtro: puede que la que buscas esté archivada o sea privada.'
                        : 'Una colección junta entidades por el motivo que tú quieras —una franquicia, un equipo, tus favoritas— sin duplicarlas ni moverlas de sitio. Una entidad puede estar en varias a la vez.' }}
                </p>

                @if ($filtrando)
                    <a href="{{ route('collections.index') }}"
                        class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Quitar los filtros
                    </a>
                @else
                    @can('create', App\Models\Collection::class)
                        <a href="{{ route('collections.create') }}"
                            class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            + Crear la primera
                        </a>
                    @endcan
                @endif
            </div>

        @else

            {{-- ============ GALERÍA ============ --}}

            <div x-show="view === 'gallery'" x-cloak class="grid gap-2.5" :class="columns">
                @foreach ($collections as $coleccion)
                    @php $acento = $coleccion->color ?: '#8b5cf6'; @endphp

                    <a href="{{ route('collections.show', $coleccion) }}"
                        class="group relative block overflow-hidden rounded-2xl border bg-slate-950 transition duration-300 hover:-translate-y-1"
                        style="border-color: {{ $acento }}40">

                        <span class="block aspect-[4/3] overflow-hidden">
                            @if ($coleccion->image_url)
                                <img src="{{ $coleccion->image_url }}" alt="{{ $coleccion->name }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-4xl"
                                    style="color: {{ $acento }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                                    {{ $coleccion->icon ?: '◫' }}
                                </span>
                            @endif
                        </span>

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-2.5 pb-2 pt-8">
                            <span class="block truncate text-[12px] font-black text-white">{{ $coleccion->name }}</span>
                            <span class="block text-[9px] font-black" style="color: {{ $acento }}">
                                {{ $coleccion->entities_count }}
                                {{ $coleccion->entities_count === 1 ? 'entidad' : 'entidades' }}
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>


            {{-- ============ CONTENIDO ============ --}}

            {{--
                La vista que faltaba. Una colección se reconoce por lo que tiene
                dentro, y hasta ahora había que abrirla para saberlo.
            --}}

            <div x-show="view === 'content'" class="grid gap-3" :class="columns">
                @foreach ($collections as $coleccion)
                    @php
                        $acento = $coleccion->color ?: '#8b5cf6';
                        $dentro = $coleccion->entities->take(9);
                    @endphp

                    <article class="group relative flex flex-col rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
                        style="border-color: {{ $acento }}40">

                        <div class="flex items-center gap-2.5 border-b border-slate-800 p-2.5">
                            <a href="{{ route('collections.show', $coleccion) }}"
                                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($coleccion->image_url)
                                    <img src="{{ $coleccion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-base"
                                        style="color: {{ $acento }}">{{ $coleccion->icon ?: '◫' }}</span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('collections.show', $coleccion) }}"
                                    class="block truncate text-[12px] font-black text-white">{{ $coleccion->name }}</a>
                                <p class="truncate text-[9px]" style="color: {{ $acento }}">
                                    {{ $coleccion->entities_count }}
                                    {{ $coleccion->entities_count === 1 ? 'entidad' : 'entidades' }}
                                    <span class="text-slate-600">· {{ $coleccion->visibility_label }}</span>
                                </p>
                            </div>
                        </div>

                        @if ($dentro->isEmpty())
                            <div class="flex flex-1 items-center justify-center p-5">
                                <p class="text-center text-[10px] leading-relaxed text-slate-600">
                                    Vacía. Añádele entidades desde
                                    <a href="{{ route('collections.edit', $coleccion) }}"
                                        class="font-black text-slate-400 underline">editarla</a>.
                                </p>
                            </div>
                        @else
                            <div class="grid flex-1 grid-cols-3 gap-px bg-slate-800/60">
                                @foreach ($dentro as $miembro)
                                    <a href="{{ route('entities.show', $miembro) }}" title="{{ $miembro->name }}"
                                        class="group/m relative block aspect-square overflow-hidden bg-slate-950">
                                        @if ($miembro->image_url)
                                            <img src="{{ $miembro->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover/m:scale-110">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                        @endif

                                        <span class="absolute inset-x-0 bottom-0 truncate bg-slate-950/85 px-1 py-0.5 text-[8px] font-black text-slate-300 opacity-0 transition group-hover/m:opacity-100">
                                            {{ $miembro->name }}
                                        </span>
                                    </a>
                                @endforeach

                                @if ($coleccion->entities_count > 9)
                                    <a href="{{ route('collections.show', $coleccion) }}"
                                        class="flex aspect-square items-center justify-center bg-slate-950 text-[11px] font-black transition hover:bg-slate-900"
                                        style="color: {{ $acento }}">
                                        +{{ $coleccion->entities_count - 9 }}
                                    </a>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">
                            <a href="{{ route('collections.show', $coleccion) }}"
                                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">Ver</a>

                            @can('update', $coleccion)
                                <a href="{{ route('collections.edit', $coleccion) }}"
                                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">✎</a>

                                @include('collections.partials.quick-visibility', ['coleccion' => $coleccion])
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>


            {{-- ============ CUADRÍCULA ============ --}}

            <div x-show="view === 'grid'" x-cloak class="grid gap-3" :class="columns">
                @foreach ($collections as $coleccion)
                    @include('collections.partials.library-card', [
                        'coleccion' => $coleccion,
                        'estadoTono' => $estadoTono,
                        'estadoEtiqueta' => $estadoEtiqueta,
                    ])
                @endforeach
            </div>


            {{-- ============ LISTA ============ --}}

            <div x-show="view === 'list'" x-cloak class="space-y-1.5">
                @foreach ($collections as $coleccion)
                    @php $acento = $coleccion->color ?: '#8b5cf6'; @endphp

                    <article class="flex items-center gap-3 rounded-xl border bg-slate-900/50 p-2 transition hover:bg-slate-900"
                        style="border-color: {{ $acento }}30">

                        <a href="{{ route('collections.show', $coleccion) }}"
                            class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($coleccion->image_url)
                                <img src="{{ $coleccion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-base"
                                    style="color: {{ $acento }}">{{ $coleccion->icon ?: '◫' }}</span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('collections.show', $coleccion) }}"
                                    class="truncate text-[12px] font-black text-white">{{ $coleccion->name }}</a>

                                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                                    style="color: {{ $acento }}; background-color: {{ $acento }}22">
                                    {{ $coleccion->visibility_label }}
                                </span>

                                @if ($coleccion->status !== 'ACTIVE')
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$coleccion->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $estadoEtiqueta[$coleccion->status] ?? $coleccion->status }}
                                    </span>
                                @endif
                            </div>

                            <p class="truncate text-[10px] text-slate-500">
                                {{ $coleccion->description ?: 'Sin descripción.' }}
                            </p>
                        </div>

                        {{-- Las caras de dentro, en pequeño --}}
                        <span class="hidden shrink-0 -space-x-2 sm:flex">
                            @foreach ($coleccion->entities->take(4) as $miembro)
                                <span class="h-7 w-7 overflow-hidden rounded-lg border-2 border-slate-900 bg-slate-950"
                                    title="{{ $miembro->name }}">
                                    @if ($miembro->image_url)
                                        <img src="{{ $miembro->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @endif
                                </span>
                            @endforeach
                        </span>

                        <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black"
                            style="border-color: {{ $acento }}40; color: {{ $coleccion->entities_count > 0 ? $acento : '#475569' }}">
                            {{ $coleccion->entities_count }}
                        </span>

                        @can('update', $coleccion)
                            <a href="{{ route('collections.edit', $coleccion) }}"
                                class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>
                        @endcan
                    </article>
                @endforeach
            </div>


            {{-- ============ TABLA ============ --}}

            <div x-show="view === 'table'" x-cloak
                class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full min-w-[820px]">
                    <thead class="border-b border-slate-800 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-2.5">Colección</th>
                            <th class="px-3 py-2.5">Visibilidad</th>
                            <th class="px-3 py-2.5">Estado</th>
                            <th class="px-3 py-2.5 text-right">Entidades</th>
                            <th class="px-3 py-2.5 text-right">Vistas</th>
                            <th class="px-3 py-2.5 text-right">Copias</th>
                            <th class="px-3 py-2.5 text-center">Copiable</th>
                            <th class="px-3 py-2.5">Origen</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($collections as $coleccion)
                            @php $acento = $coleccion->color ?: '#8b5cf6'; @endphp

                            <tr class="transition hover:bg-slate-900/60">
                                <td class="px-3 py-2">
                                    <a href="{{ route('collections.show', $coleccion) }}" class="flex items-center gap-2">
                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($coleccion->image_url)
                                                <img src="{{ $coleccion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[11px]"
                                                    style="color: {{ $acento }}">{{ $coleccion->icon ?: '◫' }}</span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-[12px] font-black text-white">{{ $coleccion->name }}</span>
                                            <span class="block font-mono text-[9px] text-slate-600">{{ $coleccion->code }}</span>
                                        </span>
                                    </a>
                                </td>

                                <td class="px-3 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                        style="color: {{ $acento }}; background-color: {{ $acento }}22">
                                        {{ $coleccion->visibility_label }}
                                    </span>
                                </td>

                                <td class="px-3 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $estadoTono[$coleccion->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $estadoEtiqueta[$coleccion->status] ?? $coleccion->status }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px]"
                                    style="color: {{ $coleccion->entities_count > 0 ? $acento : '#475569' }}">
                                    {{ $coleccion->entities_count }}
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $coleccion->views_count > 0 ? 'text-slate-300' : 'text-slate-700' }}">
                                    {{ $coleccion->views_count }}
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $coleccion->clones_count > 0 ? 'text-cyan-300' : 'text-slate-700' }}">
                                    {{ $coleccion->clones_count }}
                                </td>

                                <td class="px-3 py-2 text-center">
                                    <span class="{{ $coleccion->allow_cloning ? 'text-emerald-400' : 'text-slate-800' }}">
                                        {{ $coleccion->allow_cloning ? '✓' : '·' }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-[11px] text-slate-500">
                                    {{ $coleccion->source_collection_id
                                        ? 'Clonada de ' . ($coleccion->sourceCollection?->name ?? '—')
                                        : 'Propia' }}
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('collections.show', $coleccion) }}"
                                        class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">Ver →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


            <div class="mt-6">{{ $collections->links() }}</div>

        @endif

    </div>

</x-app-layout>

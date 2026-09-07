@php
    /*
     * Imágenes: las caras de cada versión, todas juntas.
     *
     * Aquí no se navega, se mira y se corrige. Antes cada versión ocupaba una
     * franja entera con sus fotos a 160px y hacía falta rodar la página para
     * ver seis; y para cambiar el tipo de una imagen o hacerla portada había
     * que salir a la ficha de la versión, corregir, y volver.
     *
     * Ahora las tres acciones —hacer portada, editar el tipo y el pie,
     * borrar— viven encima de la propia imagen, en un único panel compartido
     * que cambia de destino según la que se pulse. Los tres endpoints ya
     * existían y devuelven back(), así que se vuelve exactamente aquí.
     *
     * La portada no es una fila de la galería: es el campo `image` de la
     * versión. Por eso no se puede editar desde aquí y su ficha lleva otro
     * botón.
     */

    $tiposDeMedio = [
        '' => 'Cualquier tipo de imagen',
        'PORTRAIT' => 'Retrato',
        'FULL_BODY' => 'Cuerpo completo',
        'COMBAT' => 'Combate',
        'OUTFIT' => 'Apariencia',
        'REFERENCE' => 'Referencia',
        'ALTERNATIVE' => 'Alternativa',
        'OTHER' => 'Otra',
    ];

    $tiposEditables = array_diff_key($tiposDeMedio, ['' => '']);

    $tonoDeMedio = [
        'PORTRAIT' => 'border-fuchsia-500/30 bg-fuchsia-500/10 text-fuchsia-300',
        'FULL_BODY' => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300',
        'COMBAT' => 'border-rose-500/30 bg-rose-500/10 text-rose-300',
        'OUTFIT' => 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300',
        'REFERENCE' => 'border-slate-700 bg-slate-800/60 text-slate-300',
        'ALTERNATIVE' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'OTHER' => 'border-slate-700 bg-slate-800/60 text-slate-400',
    ];

    $estados = [
        '' => 'Todas las versiones',
        'GALLERY' => 'Solo las que tienen galería',
        'COVER_ONLY' => 'Solo portada, sin galería',
        'EMPTY' => 'Sin ninguna imagen',
    ];

    $ordenes = [
        'images' => 'Más imágenes primero',
        'empty' => 'Las más vacías primero',
        'entity' => 'Por entidad (A–Z)',
        'name' => 'Por nombre (A–Z)',
        'recent' => 'Editadas hace poco',
    ];

    $filtrando = $search !== '' || $versionId || $typeId || $mediaType !== '' || $estado !== '';
@endphp

<x-app-layout title="Imágenes de las versiones" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <div x-data="{
        view: 'gallery',
        size: 5,

        editor: null,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.versionmedia.view') ?? '{}');
                if (['gallery', 'grid', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4, 5, 6].includes(g.size)) this.size = g.size;
            } catch (e) {}

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.versionmedia.view',
                    JSON.stringify({ view: this.view, size: this.size }));
            } catch (e) {}
        },

        abrir(datos) { this.editor = datos; },
        cerrar() { this.editor = null; },

        get columns() {
            return {
                2: 'grid-cols-1 sm:grid-cols-2',
                3: 'grid-cols-2 sm:grid-cols-3',
                4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                5: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-5',
                6: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
            }[this.size];
        },
    }" @keydown.escape.window="cerrar()" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('versions.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Definiciones
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Imágenes</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Todas las caras de tus versiones juntas. Pulsa una para hacerla portada,
                    cambiarle el tipo o borrarla.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ([['Versiones', $stats['total'], 'text-white', []], ['Imágenes', $stats['imagenes'], 'text-fuchsia-300', []], ['Con galería', $stats['con_galeria'], 'text-indigo-300', ['state' => 'GALLERY']], ['Sin nada', $stats['sin_nada'], 'text-amber-300', ['state' => 'EMPTY']]] as [$etiqueta, $valor, $tono, $parametros])
                    <a href="{{ route('versions.media', $parametros) }}"
                        class="group flex items-baseline gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-2.5 py-1.5 transition hover:border-slate-700">
                        <span class="font-mono text-base font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 transition group-hover:text-slate-400">{{ $etiqueta }}</span>
                    </a>
                @endforeach
            </div>

        </header>


        @include('versions.partials.workspace-navigation')


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('versions.media') }}"
                class="flex flex-wrap items-center gap-2 px-4 py-3">

                <label class="relative min-w-[170px] flex-1">
                    <span class="sr-only">Buscar</span>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Buscar por entidad, definición o versión..."
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                </label>

                <select name="version" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                    <option value="">Cualquier definición</option>
                    @foreach ($versions as $definicion)
                        <option value="{{ $definicion->id }}" @selected($versionId === $definicion->id)>{{ $definicion->name }}</option>
                    @endforeach
                </select>

                <select name="type" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                    <option value="">Cualquier tipo de entidad</option>
                    @foreach ($entityTypes as $tipo)
                        <option value="{{ $tipo->id }}" @selected($typeId === $tipo->id)>{{ $tipo->name }}</option>
                    @endforeach
                </select>

                @foreach ([['media_type', $tiposDeMedio, $mediaType], ['state', $estados, $estado], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                    <select name="{{ $campo }}" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                        @foreach ($opciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                @endforeach

                <button type="submit"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-fuchsia-500 hover:text-fuchsia-300">
                    Buscar
                </button>

                @if ($filtrando)
                    <a href="{{ route('versions.media') }}"
                        class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar filtros
                    </a>
                @endif

                <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['gallery', 'galeria', 'Galería: cada imagen suelta'], ['grid', 'cuadricula', 'Cuadrícula: una ficha por versión'], ['list', 'capas', 'Lista: la tira de cada versión'], ['table', 'controles', 'Tabla: para localizar huecos']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="view === '{{ $modo }}'"
                            :class="view === '{{ $modo }}' ? 'bg-fuchsia-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="['gallery', 'grid'].includes(view)" x-cloak
                    class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    <button type="button" @click="size = Math.max(2, size - 1)" :disabled="size === 2" title="Más grandes"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>
                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="size"></span>
                    <button type="button" @click="size = Math.min(6, size + 1)" :disabled="size === 6" title="Más pequeñas"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>

            </form>

        </div>


        @if ($entityVersions->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="galeria" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ninguna versión encaja' : 'Todavía no hay imágenes' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba a quitar el tipo de imagen: es el filtro que más recorta, porque solo deja las versiones que tienen alguna de ese tipo.'
                        : 'Cada versión aplicada puede llevar su portada y una galería propia. Aquí aparecerán todas juntas en cuanto subas la primera.' }}
                </p>

                @if ($filtrando)
                    <a href="{{ route('versions.media') }}"
                        class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-fuchsia-500 hover:text-fuchsia-300">
                        Quitar los filtros
                    </a>
                @endif
            </div>

        @else

            {{-- ===================================================== --}}
            {{-- GALERÍA: cada imagen suelta --}}
            {{-- ===================================================== --}}

            <div x-show="view === 'gallery'" class="grid gap-2" :class="columns">

                @foreach ($entityVersions as $item)

                    @if (!$mediaType && $item->image_url)
                        {{-- La portada. No es una fila de galería, así que solo se mira. --}}
                        <figure class="group relative overflow-hidden rounded-xl border border-amber-500/30 bg-slate-950">
                            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy"
                                class="aspect-square w-full object-cover transition duration-500 group-hover:scale-105">

                            <span class="absolute left-1.5 top-1.5 rounded-lg bg-amber-400 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-950">
                                ★ Portada
                            </span>

                            <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-2 pb-1.5 pt-6">
                                <span class="block truncate text-[10px] font-black text-white">{{ $item->name }}</span>
                                <span class="block truncate text-[9px] text-slate-400">{{ $item->entity->name }}</span>
                            </figcaption>

                            @can('update', $item)
                                <a href="{{ route('entity-versions.edit', [$item->entity, $item]) }}"
                                    title="Cambiar la portada desde la ficha de la versión"
                                    class="absolute right-1.5 top-1.5 rounded-lg bg-slate-950/80 px-1.5 py-1 text-[10px] font-black text-slate-300 opacity-0 transition hover:text-amber-300 group-hover:opacity-100">
                                    ✎
                                </a>
                            @endcan
                        </figure>
                    @endif

                    @foreach ($item->images as $imagen)
                        <figure class="group relative overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:border-fuchsia-500/40">

                            @if ($imagen->image_url)
                                <img src="{{ $imagen->image_url }}" alt="{{ $imagen->alt_text ?? $item->name }}" loading="lazy"
                                    class="aspect-square w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="flex aspect-square w-full items-center justify-center text-2xl text-slate-800">◈</span>
                            @endif

                            <span class="absolute left-1.5 top-1.5 rounded-lg border px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoDeMedio[$imagen->media_type] ?? $tonoDeMedio['OTHER'] }}">
                                {{ $imagen->media_type_label }}
                            </span>

                            <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-2 pb-1.5 pt-6">
                                <span class="block truncate text-[10px] font-black text-white">{{ $item->name }}</span>
                                <span class="block truncate text-[9px] text-slate-400">
                                    {{ $imagen->caption ?: $item->entity->name }}
                                </span>
                            </figcaption>

                            @can('update', $item)
                                <button type="button"
                                    @click="abrir({
                                        titulo: @js($item->name),
                                        entidad: @js($item->entity->name),
                                        url: @js($imagen->image_url),
                                        caption: @js($imagen->caption),
                                        alt: @js($imagen->alt_text),
                                        tipo: @js($imagen->media_type),
                                        accion: @js(route('entity-versions.images.update', [$item->entity, $item, $imagen])),
                                        portada: @js(route('entity-versions.images.primary', [$item->entity, $item, $imagen])),
                                        borrar: @js(route('entity-versions.images.destroy', [$item->entity, $item, $imagen])),
                                    })"
                                    class="absolute right-1.5 top-1.5 rounded-lg bg-slate-950/85 px-2 py-1 text-[10px] font-black text-slate-200 opacity-0 transition hover:bg-fuchsia-500 group-hover:opacity-100">
                                    ✎
                                </button>
                            @endcan
                        </figure>
                    @endforeach

                @endforeach

            </div>


            {{-- ===================================================== --}}
            {{-- CUADRÍCULA: una ficha por versión --}}
            {{-- ===================================================== --}}

            <div x-show="view === 'grid'" x-cloak class="grid gap-3" :class="columns">

                @foreach ($entityVersions as $item)
                    <article class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                        <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                            class="group relative block aspect-square overflow-hidden bg-slate-950">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-3xl text-slate-800">◈</span>
                            @endif

                            <span class="absolute right-1.5 top-1.5 rounded-lg bg-slate-950/85 px-1.5 py-0.5 font-mono text-[10px] font-black {{ $item->images_count > 0 ? 'text-fuchsia-300' : 'text-slate-600' }}">
                                {{ $item->images_count }}
                            </span>
                        </a>

                        <div class="p-2">
                            <p class="truncate text-[11px] font-black text-white">{{ $item->name }}</p>
                            <p class="truncate text-[9px] text-slate-500">{{ $item->entity->name }}</p>

                            @if ($item->images->isNotEmpty())
                                <div class="mt-1.5 flex gap-1 overflow-x-auto">
                                    @foreach ($item->images->take(6) as $imagen)
                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded border border-slate-800">
                                            @if ($imagen->image_url)
                                                <img src="{{ $imagen->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-1.5 rounded-lg border border-dashed border-slate-800 py-1 text-center text-[9px] text-slate-600">
                                    Sin galería
                                </p>
                            @endif
                        </div>

                    </article>
                @endforeach

            </div>


            {{-- ===================================================== --}}
            {{-- LISTA: la tira de cada versión --}}
            {{-- ===================================================== --}}

            <div x-show="view === 'list'" x-cloak class="space-y-2">

                @foreach ($entityVersions as $item)
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-2.5">

                        <div class="flex items-center gap-2.5">

                            <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                                    class="block truncate text-[12px] font-black text-white transition hover:text-fuchsia-300">
                                    {{ $item->name }}
                                </a>
                                <p class="truncate text-[10px] text-slate-500">
                                    {{ $item->entity->name }}
                                    <span class="text-slate-700">·</span>
                                    <span class="text-violet-400">{{ $item->version->name }}</span>
                                </p>
                            </div>

                            <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $item->images_count > 0 ? 'border-fuchsia-500/25 text-fuchsia-300' : 'border-slate-800 text-slate-700' }}">
                                {{ $item->images_count }}
                            </span>

                            @can('update', $item)
                                <a href="{{ route('entity-versions.edit', [$item->entity, $item]) }}"
                                    class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>
                            @endcan
                        </div>

                        @if ($item->images->isNotEmpty())
                            <div class="mt-2 flex gap-1.5 overflow-x-auto pb-1">
                                @foreach ($item->images as $imagen)
                                    <button type="button"
                                        @can('update', $item)
                                            @click="abrir({
                                                titulo: @js($item->name),
                                                entidad: @js($item->entity->name),
                                                url: @js($imagen->image_url),
                                                caption: @js($imagen->caption),
                                                alt: @js($imagen->alt_text),
                                                tipo: @js($imagen->media_type),
                                                accion: @js(route('entity-versions.images.update', [$item->entity, $item, $imagen])),
                                                portada: @js(route('entity-versions.images.primary', [$item->entity, $item, $imagen])),
                                                borrar: @js(route('entity-versions.images.destroy', [$item->entity, $item, $imagen])),
                                            })"
                                        @endcan
                                        title="{{ $imagen->media_type_label }}"
                                        class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-slate-800 transition hover:border-fuchsia-500">
                                        @if ($imagen->image_url)
                                            <img src="{{ $imagen->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        @endif
                                        <span class="absolute inset-0 flex items-center justify-center bg-slate-950/70 text-[10px] font-black text-white opacity-0 transition group-hover:opacity-100">✎</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                    </section>
                @endforeach

            </div>


            {{-- ===================================================== --}}
            {{-- TABLA: para localizar huecos --}}
            {{-- ===================================================== --}}

            <div x-show="view === 'table'" x-cloak
                class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full min-w-[760px]">
                    <thead class="border-b border-slate-800 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-2.5">Versión</th>
                            <th class="px-3 py-2.5">Entidad</th>
                            <th class="px-3 py-2.5">Definición</th>
                            <th class="px-3 py-2.5 text-center">Portada</th>
                            <th class="px-3 py-2.5 text-right">Galería</th>
                            <th class="px-3 py-2.5">Tipos presentes</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($entityVersions as $item)
                            <tr class="transition hover:bg-slate-900/60">
                                <td class="px-3 py-2">
                                    <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}" class="flex items-center gap-2">
                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($item->image_url)
                                                <img src="{{ $item->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-700">◈</span>
                                            @endif
                                        </span>
                                        <span class="truncate text-[12px] font-black text-white">{{ $item->name }}</span>
                                    </a>
                                </td>

                                <td class="px-3 py-2 text-[11px] text-slate-400">{{ $item->entity->name }}</td>
                                <td class="px-3 py-2 text-[11px] text-violet-300">{{ $item->version->name }}</td>

                                <td class="px-3 py-2 text-center">
                                    @if ($item->image_url)
                                        <span class="text-amber-400" title="Tiene portada">★</span>
                                    @else
                                        <span class="text-rose-500/60" title="Sin portada">✕</span>
                                    @endif
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $item->images_count > 0 ? 'text-fuchsia-300' : 'text-slate-700' }}">
                                    {{ $item->images_count }}
                                </td>

                                <td class="px-3 py-2">
                                    <span class="flex flex-wrap gap-1">
                                        @forelse ($item->images->pluck('media_type')->unique() as $tipo)
                                            <span class="rounded border px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoDeMedio[$tipo] ?? $tonoDeMedio['OTHER'] }}">
                                                {{ $tiposDeMedio[$tipo] ?? 'Otra' }}
                                            </span>
                                        @empty
                                            <span class="text-[10px] text-slate-700">—</span>
                                        @endforelse
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-right">
                                    @can('update', $item)
                                        <a href="{{ route('entity-versions.edit', [$item->entity, $item]) }}"
                                            class="text-[10px] font-black text-slate-400 transition hover:text-amber-300">Editar →</a>
                                    @else
                                        <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                                            class="text-[10px] font-black text-slate-400 transition hover:text-fuchsia-300">Ver →</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>


            <div class="mt-6">{{ $entityVersions->links() }}</div>

        @endif


        {{-- ===================================================== --}}
        {{-- EL PANEL DE CORRECCIÓN --}}
        {{-- ===================================================== --}}

        {{--
            Uno solo para todas las imágenes: pintar un formulario por cada
            miniatura sería multiplicar por veinte el mismo marcado. El panel
            cambia de destino según la que se haya pulsado.
        --}}

        <div x-show="editor" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
            @click.self="cerrar()">

            <template x-if="editor">
                <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">

                    <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
                            <x-omni-icon name="galeria" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-black text-white" x-text="editor.titulo"></p>
                            <p class="truncate text-[10px] text-slate-500" x-text="editor.entidad"></p>
                        </div>

                        <button type="button" @click="cerrar()"
                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-white">
                            <x-omni-icon name="cerrar" size="h-4 w-4" />
                        </button>
                    </div>

                    <div class="grid gap-4 p-4 sm:grid-cols-[minmax(0,240px)_minmax(0,1fr)]">

                        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                            <img :src="editor.url" alt="" class="aspect-square w-full object-cover">
                        </div>

                        <div class="space-y-3">

                            <form method="POST" :action="editor.accion" class="space-y-2.5">
                                @csrf
                                @method('PATCH')

                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">
                                        Qué muestra
                                    </span>
                                    <select name="media_type" x-model="editor.tipo"
                                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                                        @foreach ($tiposEditables as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">
                                        Pie de foto
                                    </span>
                                    <input type="text" name="caption" x-model="editor.caption" maxlength="200"
                                        placeholder="«Tras el combate contra Pain»"
                                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">
                                        Texto alternativo
                                    </span>
                                    <input type="text" name="alt_text" x-model="editor.alt" maxlength="200"
                                        placeholder="Para quien no puede ver la imagen"
                                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                                </label>

                                <button type="submit"
                                    class="w-full rounded-xl bg-fuchsia-500 py-2.5 text-[11px] font-black text-white transition hover:bg-fuchsia-400">
                                    Guardar los cambios
                                </button>
                            </form>

                            <div class="flex gap-2 border-t border-slate-800 pt-3">

                                <form method="POST" :action="editor.portada" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                        title="Pasa a ser la cara de esta versión; la portada de ahora vuelve a la galería"
                                        class="w-full rounded-xl border border-amber-500/30 bg-amber-500/10 py-2 text-[11px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-amber-950">
                                        ★ Hacerla portada
                                    </button>
                                </form>

                                <form method="POST" :action="editor.borrar" class="flex-1"
                                    onsubmit="return confirm('Se borra la imagen para siempre. ¿Seguro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full rounded-xl border border-rose-500/30 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                                        Borrarla
                                    </button>
                                </form>

                            </div>

                            <p class="text-[10px] leading-relaxed text-slate-600">
                                El tipo no es decorativo: es lo que permite filtrar arriba por
                                «combate» o «apariencia» cuando la galería crece.
                            </p>

                        </div>

                    </div>

                </div>
            </template>

        </div>

    </div>

</x-app-layout>

@php
    /*
     * Aplicadas: cada molde ya puesto sobre una entidad concreta.
     *
     * Una fila de esta pantalla es siempre la unión de dos cosas —una entidad
     * y una definición—, así que se puede mirar desde los dos lados:
     *
     *   galería      solo la cara y el nombre
     *   cuadrícula   la ficha con sus etiquetas
     *   lista        una línea por versión, para repasar muchas
     *   tabla        para comparar cambios, imágenes y estado
     *   por entidad  agrupadas: qué versiones tiene cada una  ← la vista especial
     *
     * La última es la que responde la pregunta que de verdad se hace aquí:
     * «¿a esta entidad le falta alguna?». Por eso lleva su propio botón de
     * añadir dentro de cada grupo.
     */

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $ordenes = [
        'default' => 'Base activa primero',
        'entity' => 'Por entidad (A–Z)',
        'name' => 'Por nombre (A–Z)',
        'changes' => 'Más cambios propios',
        'images' => 'Más imágenes',
        'recent' => 'Editadas hace poco',
    ];

    $filtrando =
        $search !== '' ||
        $versionId ||
        $typeId ||
        $entityId ||
        $status !== '' ||
        $default !== '' ||
        $overrides !== '' ||
        $media !== '';

    $porEntidad = $entityVersions->getCollection()->groupBy('entity_id');
@endphp

<x-app-layout title="Versiones aplicadas" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <div x-data="{
        view: 'grid',
        size: 4,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.applied.view') ?? '{}');
                if (['gallery', 'grid', 'list', 'table', 'byentity'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4, 5, 6].includes(g.size)) this.size = g.size;
            } catch (e) {}

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.applied.view',
                    JSON.stringify({ view: this.view, size: this.size }));
            } catch (e) {}
        },

        get columns() {
            return {
                2: 'grid-cols-1 sm:grid-cols-2',
                3: 'grid-cols-2 sm:grid-cols-3',
                4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                5: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-5',
                6: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-6',
            }[this.size];
        },

        get grande() { return this.size <= 3; },
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('versions.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Definiciones
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Versiones aplicadas
                </h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Un molde puesto sobre alguien concreto: «Naruto — Shippuden», con su propia
                    cara y sus propios cambios.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ([['Aplicadas', $stats['total'], 'text-white', []], ['Entidades', $stats['entities'], 'text-indigo-300', []], ['Base activa', $stats['default'], 'text-amber-300', ['default' => 'YES']], ['Con cambios', $stats['with_overrides'], 'text-violet-300', ['overrides' => 'YES']], ['Con imágenes', $stats['with_media'], 'text-fuchsia-300', ['media' => 'YES']]] as [$etiqueta, $valor, $tono, $parametros])
                    <a href="{{ route('versions.entities.index', $parametros) }}"
                        class="group flex items-baseline gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-2.5 py-1.5 transition hover:border-slate-700">
                        <span class="font-mono text-base font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 transition group-hover:text-slate-400">{{ $etiqueta }}</span>
                    </a>
                @endforeach
            </div>

        </header>


        @include('versions.partials.workspace-navigation')


        {{-- ===================================================== --}}
        {{-- ELEGIR ENTIDAD, CON SU CARA --}}
        {{-- ===================================================== --}}

        {{--
            Elegir entre cien nombres en un desplegable no es elegir. Aquí las
            entidades se reconocen por la cara, que es como se recuerdan.
        --}}

        <section x-data="{ abierto: {{ $entityId ? 'true' : 'false' }} }"
            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-950">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
                    <x-omni-icon name="usuario" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1">
                    @if ($selectedEntity)
                        <span class="block truncate text-[12px] font-black text-white">
                            Viendo solo las de {{ $selectedEntity->name }}
                        </span>
                    @else
                        <span class="block text-[12px] font-black text-white">Filtrar por entidad</span>
                    @endif

                    <span class="block text-[10px] text-slate-500">
                        {{ $entitiesWithVersions->count() }}
                        {{ $entitiesWithVersions->count() === 1 ? 'entidad tiene' : 'entidades tienen' }}
                        alguna versión aplicada.
                    </span>
                </span>

                @if ($entityId)
                    <a href="{{ route('versions.entities.index', array_diff_key(request()->query(), ['entity' => ''])) }}"
                        class="shrink-0 rounded-lg border border-rose-500/30 px-2.5 py-1 text-[10px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar
                    </a>
                @endif

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800">

                <div class="flex gap-2 overflow-x-auto px-4 py-3">

                    @forelse ($entitiesWithVersions as $entidad)
                        @php
                            $activa = $entityId === $entidad->id;
                            $destino = $activa
                                ? array_diff_key(request()->query(), ['entity' => '', 'page' => ''])
                                : array_merge(array_diff_key(request()->query(), ['page' => '']), ['entity' => $entidad->id]);
                        @endphp

                        <a href="{{ route('versions.entities.index', $destino) }}" title="{{ $entidad->name }}"
                            class="group flex w-24 shrink-0 flex-col overflow-hidden rounded-xl border transition hover:-translate-y-0.5 {{ $activa ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-700' }}">

                            <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-lg text-slate-700">◍</span>
                                @endif

                                <span class="absolute right-1 top-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-indigo-300">
                                    {{ $entidad->entity_versions_count }}
                                </span>
                            </span>

                            <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black {{ $activa ? 'text-indigo-200' : 'text-slate-400' }}">
                                {{ $entidad->name }}
                            </span>
                        </a>
                    @empty
                        <p class="px-2 py-4 text-[11px] text-slate-600">
                            Ninguna entidad tiene versiones todavía.
                        </p>
                    @endforelse

                </div>

                @if ($entitiesWithout > 0)
                    <p class="border-t border-slate-800 px-4 py-2 text-[10px] text-slate-500">
                        Hay <strong class="text-amber-300">{{ $entitiesWithout }}</strong>
                        {{ $entitiesWithout === 1 ? 'entidad activa sin ninguna versión' : 'entidades activas sin ninguna versión' }}.
                        <a href="{{ route('versions.coverage') }}"
                            class="font-black text-slate-300 underline transition hover:text-violet-300">Ver a cuáles les falta →</a>
                    </p>
                @endif

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('versions.entities.index') }}"
                class="flex flex-wrap items-center gap-2 px-4 py-3">

                @if ($entityId)
                    <input type="hidden" name="entity" value="{{ $entityId }}">
                @endif

                <label class="relative min-w-[170px] flex-1">
                    <span class="sr-only">Buscar</span>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Buscar por entidad, definición o nombre de la versión..."
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                </label>

                <select name="version" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Cualquier definición</option>
                    @foreach ($versions as $definicion)
                        <option value="{{ $definicion->id }}" @selected($versionId === $definicion->id)>
                            {{ $definicion->name }} ({{ $definicion->entity_versions_count }})
                        </option>
                    @endforeach
                </select>

                <select name="type" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Cualquier tipo</option>
                    @foreach ($entityTypes as $tipo)
                        <option value="{{ $tipo->id }}" @selected($typeId === $tipo->id)>{{ $tipo->name }}</option>
                    @endforeach
                </select>

                @foreach ([['default', ['' => 'Base activa: da igual', 'YES' => 'Solo la base activa ★', 'NO' => 'Solo las secundarias'], $default], ['overrides', ['' => 'Cambios: da igual', 'YES' => 'Con cambios propios', 'NO' => 'Solo heredadas'], $overrides], ['media', ['' => 'Imágenes: da igual', 'YES' => 'Con galería', 'NO' => 'Sin galería'], $media], ['status', ['' => 'Cualquier estado', 'ACTIVE' => 'Activas', 'INACTIVE' => 'Inactivas', 'ARCHIVED' => 'Archivadas'], $status]] as [$campo, $opciones, $actual])
                    <select name="{{ $campo }}" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach ($opciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                @endforeach

                <select name="sort" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                    @foreach ($ordenes as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>

                <button type="submit"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Buscar
                </button>

                @if ($filtrando)
                    <a href="{{ route('versions.entities.index') }}"
                        class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar filtros
                    </a>
                @endif

                <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['gallery', 'galeria', 'Galería: solo la cara'], ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'], ['list', 'capas', 'Lista: una línea por versión'], ['table', 'controles', 'Tabla: para comparar'], ['byentity', 'usuario', 'Por entidad: agrupadas por quién las lleva']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="view === '{{ $modo }}'"
                            :class="view === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
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


        {{-- ===================================================== --}}
        {{-- LAS VERSIONES --}}
        {{-- ===================================================== --}}

        @if ($entityVersions->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="chispa" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ninguna versión encaja' : 'Todavía no hay ninguna versión aplicada' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba a quitar algún filtro: puede que la que buscas esté archivada o pertenezca a otro tipo.'
                        : 'Una definición existe suelta hasta que alguien la aplica. Empieza por la cobertura: ahí se ve a qué entidades les falta cada molde.' }}
                </p>

                <a href="{{ $filtrando ? route('versions.entities.index') : route('versions.coverage') }}"
                    class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    {{ $filtrando ? 'Quitar los filtros' : 'Ver la cobertura →' }}
                </a>
            </div>

        @else

            {{-- ============ GALERÍA ============ --}}

            <div x-show="view === 'gallery'" x-cloak class="grid gap-2.5" :class="columns">
                @foreach ($entityVersions as $item)
                    <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                        class="group relative block overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 transition duration-300 hover:-translate-y-1 hover:border-violet-500/50">

                        <span class="block aspect-[3/4] overflow-hidden">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-3xl text-slate-800">◈</span>
                            @endif
                        </span>

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-2.5 pb-2 pt-8">
                            <span class="block truncate text-[11px] font-black text-white">{{ $item->name }}</span>
                            <span class="block truncate text-[9px] text-violet-300">{{ $item->version->name }}</span>
                        </span>

                        @if ($item->is_default)
                            <span class="absolute right-2 top-2 rounded-lg bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-amber-950">★</span>
                        @endif
                    </a>
                @endforeach
            </div>


            {{-- ============ CUADRÍCULA ============ --}}

            <div x-show="view === 'grid'" class="grid gap-3" :class="columns">
                @foreach ($entityVersions as $item)
                    <article
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition duration-300 hover:-translate-y-0.5 hover:border-violet-500/40">

                        <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                            class="relative block aspect-square overflow-hidden bg-slate-950">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-3xl text-slate-800">◈</span>
                            @endif

                            <span class="absolute left-2 top-2 rounded-lg border border-violet-500/30 bg-slate-950/85 px-1.5 py-0.5 text-[9px] font-black text-violet-300">
                                {{ $item->version->name }}
                            </span>

                            @if ($item->is_default)
                                <span class="absolute right-2 top-2 rounded-lg bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-amber-950"
                                    title="Base activa: la cara que enseña el resto de la aplicación">★</span>
                            @endif
                        </a>

                        <div class="flex-1 p-2.5">
                            <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                                class="block truncate text-[12px] font-black text-white transition hover:text-violet-300">
                                {{ $item->name }}
                            </a>

                            <a href="{{ route('entities.show', $item->entity) }}"
                                class="mt-0.5 flex items-center gap-1 text-[10px] text-slate-500 transition hover:text-indigo-300">
                                <x-omni-icon name="usuario" size="h-3 w-3" />
                                <span class="truncate">{{ $item->entity->name }}</span>
                            </a>

                            <div x-show="grande" x-cloak class="mt-2 flex flex-wrap gap-1 text-[9px]">
                                <span class="rounded-lg border px-1.5 py-0.5 font-bold {{ $item->version_attributes_count > 0 ? 'border-violet-500/25 bg-violet-500/5 text-violet-300' : 'border-slate-800 text-slate-600' }}">
                                    {{ $item->version_attributes_count }} cambios
                                </span>

                                <span class="rounded-lg border px-1.5 py-0.5 font-bold {{ $item->images_count > 0 ? 'border-fuchsia-500/25 bg-fuchsia-500/5 text-fuchsia-300' : 'border-slate-800 text-slate-600' }}">
                                    {{ $item->images_count }} imágenes
                                </span>

                                @if ($item->status !== 'ACTIVE')
                                    <span class="rounded-lg px-1.5 py-0.5 font-black uppercase tracking-wider {{ $estadoTono[$item->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $item->status_label }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div x-show="grande" x-cloak class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">
                            <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">Ver</a>

                            @can('update', $item)
                                <a href="{{ route('entity-versions.edit', [$item->entity, $item]) }}"
                                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">✎ Editar</a>

                                <a href="{{ route('entity-versions.attributes.edit', [$item->entity, $item]) }}"
                                    class="ml-auto rounded-lg bg-violet-500/15 px-2 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                    Características
                                </a>
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>


            {{-- ============ LISTA ============ --}}

            <div x-show="view === 'list'" x-cloak class="space-y-1.5">
                @foreach ($entityVersions as $item)
                    <article
                        class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/50 p-2 transition hover:border-violet-500/40 hover:bg-slate-900">

                        <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                            class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                                    class="truncate text-[12px] font-black text-white transition hover:text-violet-300">{{ $item->name }}</a>

                                @if ($item->is_default)
                                    <span class="rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★ BASE</span>
                                @endif

                                @if ($item->status !== 'ACTIVE')
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$item->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $item->status_label }}
                                    </span>
                                @endif
                            </div>

                            <p class="truncate text-[10px] text-slate-500">
                                {{ $item->entity->name }}
                                <span class="text-slate-700">·</span>
                                <span class="text-violet-400">{{ $item->version->name }}</span>
                                @if ($item->entity->entityType)
                                    <span class="text-slate-700">·</span> {{ $item->entity->entityType->name }}
                                @endif
                            </p>
                        </div>

                        <div class="hidden shrink-0 items-center gap-1.5 sm:flex">
                            <span class="rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $item->version_attributes_count > 0 ? 'border-violet-500/25 text-violet-300' : 'border-slate-800 text-slate-700' }}"
                                title="Características propias">{{ $item->version_attributes_count }}</span>

                            <span class="rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $item->images_count > 0 ? 'border-fuchsia-500/25 text-fuchsia-300' : 'border-slate-800 text-slate-700' }}"
                                title="Imágenes de galería">{{ $item->images_count }}</span>
                        </div>

                        @can('update', $item)
                            <a href="{{ route('entity-versions.edit', [$item->entity, $item]) }}"
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
                            <th class="px-3 py-2.5">Versión</th>
                            <th class="px-3 py-2.5">Entidad</th>
                            <th class="px-3 py-2.5">Definición</th>
                            <th class="px-3 py-2.5">Tipo</th>
                            <th class="px-3 py-2.5 text-center">Base</th>
                            <th class="px-3 py-2.5 text-right">Cambios</th>
                            <th class="px-3 py-2.5 text-right">Imágenes</th>
                            <th class="px-3 py-2.5">Estado</th>
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
                                <td class="px-3 py-2 text-[11px] text-slate-500">{{ $item->entity->entityType?->name ?? '—' }}</td>

                                <td class="px-3 py-2 text-center">
                                    @if ($item->is_default)
                                        <span class="text-amber-400">★</span>
                                    @else
                                        <span class="text-slate-800">·</span>
                                    @endif
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $item->version_attributes_count > 0 ? 'text-violet-300' : 'text-slate-700' }}">
                                    {{ $item->version_attributes_count }}
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $item->images_count > 0 ? 'text-fuchsia-300' : 'text-slate-700' }}">
                                    {{ $item->images_count }}
                                </td>

                                <td class="px-3 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$item->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                                        class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">Ver →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>


            {{-- ============ POR ENTIDAD (la vista especial) ============ --}}

            {{--
                La pregunta real de esta pantalla no es «qué versiones hay»,
                es «qué versiones tiene ESTA». Agrupadas, se ve de un vistazo
                quién va sobrada y quién solo tiene una.
            --}}

            <div x-show="view === 'byentity'" x-cloak class="space-y-3">

                @foreach ($porEntidad as $grupo)
                    @php $entidad = $grupo->first()->entity; @endphp

                    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                        <div class="flex items-center gap-3 border-b border-slate-800 px-3 py-2.5">

                            <a href="{{ route('entities.show', $entidad) }}"
                                class="h-10 w-10 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('entities.show', $entidad) }}"
                                    class="block truncate text-[13px] font-black text-white transition hover:text-indigo-300">
                                    {{ $entidad->name }}
                                </a>
                                <p class="text-[10px] text-slate-500">
                                    {{ $entidad->entityType?->name ?? 'Sin tipo' }}
                                    <span class="text-slate-700">·</span>
                                    {{ $grupo->count() }} {{ $grupo->count() === 1 ? 'versión' : 'versiones' }} en esta página
                                </p>
                            </div>

                            @can('update', $entidad)
                                <a href="{{ route('entity-versions.create', $entidad) }}"
                                    class="shrink-0 rounded-lg bg-violet-500/15 px-2.5 py-1.5 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                    + Añadir versión
                                </a>
                            @endcan

                            <a href="{{ route('entity-versions.index', $entidad) }}"
                                class="shrink-0 rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300">
                                Todas →
                            </a>
                        </div>

                        <div class="flex gap-2 overflow-x-auto p-3">
                            @foreach ($grupo as $item)
                                <a href="{{ route('entity-versions.show', [$entidad, $item]) }}" title="{{ $item->name }}"
                                    class="group flex w-28 shrink-0 flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-violet-500/50">

                                    <span class="relative block aspect-[4/5] overflow-hidden">
                                        @if ($item->image_url)
                                            <img src="{{ $item->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◈</span>
                                        @endif

                                        @if ($item->is_default)
                                            <span class="absolute right-1 top-1 rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★</span>
                                        @endif
                                    </span>

                                    <span class="block p-1.5">
                                        <span class="block truncate text-[10px] font-black text-white">{{ $item->name }}</span>
                                        <span class="block truncate text-[9px] text-violet-300">{{ $item->version->name }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                    </section>
                @endforeach

                <p class="px-1 text-[10px] text-slate-600">
                    Los grupos se arman con lo que hay en esta página. Para verlos enteros,
                    ordena <strong class="text-slate-400">por entidad</strong> o filtra por una.
                </p>

            </div>


            <div class="mt-6">{{ $entityVersions->links() }}</div>

        @endif

    </div>

</x-app-layout>

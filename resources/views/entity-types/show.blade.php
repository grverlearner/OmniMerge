@php
    /*
     * La ficha de un tipo de entidad.
     *
     * Un tipo no es un nombre con un color: es todo lo que lo lleva puesto.
     * Por eso esta pantalla enseña la colección entera —filtrable, ordenable
     * y en cuatro modos de vista— y responde además a las dos preguntas que
     * uno se hace al abrirlo: qué características suelen tener sus entidades,
     * y en qué colecciones acaban.
     *
     * El color del tipo es un dato del usuario, así que se usa en `style` y
     * como variable CSS, no como clase de Tailwind: una clase compuesta con
     * 'border-' . $color no existiría en el CSS.
     */

    $color = $entityType->color ?: '#6366f1';

    $estados = [
        '' => 'Cualquier estado',
        'ACTIVE' => 'Activa',
        'INACTIVE' => 'Inactiva',
        'ARCHIVED' => 'Archivada',
    ];

    $ordenes = [
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguas',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'attributes_desc' => 'Más características',
    ];

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $filtrando = $search !== '' || $status !== '';
@endphp

<x-app-layout :title="$entityType->name" surface="dark">

    <x-slot name="header">{{ $entityType->name }}</x-slot>

    <div x-data="{
        view: 'gallery',
        size: 4,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.entity-type.view') ?? '{}');
                if (['gallery', 'grid', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4, 5, 6].includes(g.size)) this.size = g.size;
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.entity-type.view',
                    JSON.stringify({ view: this.view, size: this.size }));
            } catch (e) {}
        },

        get columns() {
            if (this.view === 'gallery') {
                return {
                    2: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                    3: 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-5',
                    4: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                    5: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-8',
                    6: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-10',
                }[this.size];
            }

            return {
                2: 'sm:grid-cols-2',
                3: 'sm:grid-cols-2 lg:grid-cols-3',
                4: 'sm:grid-cols-3 lg:grid-cols-4',
                5: 'sm:grid-cols-3 lg:grid-cols-5',
                6: 'sm:grid-cols-4 lg:grid-cols-6',
            }[this.size];
        },
    }" style="--tipo: {{ $color }}" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- LA PORTADA --}}
        {{-- ===================================================== --}}

        <header class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $color }}44">

            <div class="relative">

                {{-- El color del tipo, de fondo --}}
                <span class="pointer-events-none absolute inset-0"
                    style="background: radial-gradient(70% 120% at 15% 0%, {{ $color }}26, transparent 65%)"></span>

                <div class="relative flex flex-wrap items-start gap-5 p-5">

                    {{-- Su cara --}}
                    <span class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border bg-slate-950"
                        style="border-color: {{ $color }}55">
                        @if ($entityType->image_url)
                            <img src="{{ $entityType->image_url }}" alt="{{ $entityType->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-4xl"
                                style="color: {{ $color }}">{{ $entityType->icon ?: '◇' }}</span>
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">

                        <a href="{{ route('entity-types.index') }}"
                            class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                            ← Tipos de entidad
                        </a>

                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <h1 class="text-3xl font-black tracking-tight text-white">{{ $entityType->name }}</h1>

                            <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$entityType->status] ?? 'bg-slate-800 text-slate-500' }}">
                                {{ $entityType->status }}
                            </span>

                            <span class="font-mono text-[10px] text-slate-600">{{ $entityType->code }}</span>

                            <span class="flex items-center gap-1.5 rounded-lg border px-2 py-1"
                                style="border-color: {{ $color }}44">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $color }}"></span>
                                <span class="font-mono text-[9px] text-slate-500">{{ $color }}</span>
                            </span>
                        </div>

                        <p class="mt-2 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                            {{ $entityType->description ?: 'Sin descripción. Un tipo es una etiqueta para organizarte: no limita qué características puede tener una entidad.' }}
                        </p>

                        {{-- Sus cifras --}}
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ([['Entidades', $stats['total'], 'text-white'], ['Con imagen', $stats['with_image'], 'text-cyan-300'], ['Activas', $stats['active'], 'text-emerald-300'], ['Públicas', $stats['public'], 'text-sky-300']] as [$etiqueta, $valor, $tono])
                                <span class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                                    <span class="font-mono text-lg font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">
                                        {{ $valor }}
                                    </span>
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        {{ $etiqueta }}
                                    </span>
                                </span>
                            @endforeach
                        </div>

                        {{-- Qué se puede hacer --}}
                        <div class="mt-4 flex flex-wrap gap-2">

                            @can('create', App\Models\Entity::class)
                                <a href="{{ route('entities.create', ['type' => $entityType->id]) }}"
                                    class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black text-slate-950 transition hover:opacity-90"
                                    style="background-color: {{ $color }}">
                                    <x-omni-icon name="mas" size="h-4 w-4" />
                                    {{-- Sin concordar con el nombre del tipo: «Nueva Personaje» chirría --}}
                                    Nueva entidad de este tipo
                                </a>
                            @endcan

                            <a href="{{ route('entities.index', ['type' => $entityType->id]) }}"
                                class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-indigo-500/60 hover:text-indigo-300">
                                <x-omni-icon name="cuadricula" size="h-4 w-4" />
                                Verlas en la biblioteca
                            </a>

                            @can('update', $entityType)
                                <a href="{{ route('entity-types.edit', $entityType) }}"
                                    class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-amber-500/60 hover:text-amber-300">
                                    <x-omni-icon name="controles" size="h-4 w-4" />
                                    Editar el tipo
                                </a>
                            @endcan

                            @can('create', App\Models\EntityType::class)
                                <a href="{{ route('entity-types.create') }}"
                                    class="flex items-center gap-2 rounded-xl border border-dashed border-slate-700 px-4 py-2.5 text-xs font-black text-slate-400 transition hover:border-emerald-500/60 hover:text-emerald-300">
                                    <x-omni-icon name="mas" size="h-4 w-4" />
                                    Otro tipo
                                </a>
                            @endcan

                        </div>

                    </div>

                </div>

            </div>

        </header>


        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">

            {{-- ============================================================= --}}
            {{-- SUS ENTIDADES --}}
            {{-- ============================================================= --}}

            <div class="space-y-4">

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background-color: {{ $color }}26; color: {{ $color }}">
                            <x-omni-icon name="chispa" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">Lo que es de este tipo</h2>
                            <p class="text-[10px] text-slate-500">
                                {{ $entities->total() }}
                                {{ $entities->total() === 1 ? 'entidad' : 'entidades' }}
                                @if ($filtrando)
                                    tras los filtros
                                @endif
                            </p>
                        </div>

                        {{-- Cuatro maneras de mirarlas --}}
                        <span class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                            @foreach ([['gallery', 'galeria', 'Galería: solo la cara y el nombre'], ['grid', 'cuadricula', 'Cuadrícula: con sus cifras'], ['list', 'controles', 'Lista: una línea por entidad'], ['table', 'grafo', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
                                <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                                    :aria-pressed="view === '{{ $modo }}'"
                                    :style="view === '{{ $modo }}' ? 'background-color: {{ $color }}; color: #0f172a' : ''"
                                    :class="view === '{{ $modo }}' ? '' : 'text-slate-500 hover:text-slate-200'"
                                    class="rounded-lg px-2 py-1.5 transition">
                                    <x-omni-icon :name="$icono" size="h-4 w-4" />
                                </button>
                            @endforeach
                        </span>

                        <span x-show="view !== 'list' && view !== 'table'" x-cloak
                            class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                            <button type="button" @click="size = Math.max(2, size - 1)" :disabled="size === 2"
                                title="Más grandes"
                                class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                                <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                            </button>

                            <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500"
                                x-text="size"></span>

                            <button type="button" @click="size = Math.min(6, size + 1)" :disabled="size === 6"
                                title="Más pequeñas"
                                class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                                <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                            </button>
                        </span>
                    </header>


                    {{-- ============ BUSCAR Y ORDENAR ============ --}}

                    <form method="GET" action="{{ route('entity-types.show', $entityType) }}"
                        class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-2.5">

                        <label class="relative min-w-[180px] flex-1">
                            <span class="sr-only">Buscar</span>

                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>

                            <input type="search" name="search" value="{{ $search }}"
                                placeholder="Buscar entre las de este tipo..."
                                class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">
                        </label>

                        @foreach ([['status', $estados, $status], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                            <select name="{{ $campo }}" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($opciones as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>
                                        {{ $etiqueta }}
                                    </option>
                                @endforeach
                            </select>
                        @endforeach

                        <button type="submit"
                            class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                            Buscar
                        </button>

                        @if ($filtrando)
                            <a href="{{ route('entity-types.show', $entityType) }}"
                                class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                                Quitar filtros
                            </a>
                        @endif
                    </form>


                    {{-- ============ LAS ENTIDADES ============ --}}

                    @if ($entities->isEmpty())

                        <div class="px-5 py-14 text-center">
                            <span class="inline-flex" style="color: {{ $color }}44">
                                <x-omni-icon name="chispa" size="h-10 w-10" />
                            </span>

                            <p class="mt-2 text-sm font-black text-white">
                                {{ $filtrando ? 'Ninguna encaja con eso' : 'Todavía no hay nada de este tipo' }}
                            </p>

                            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                                {{ $filtrando
                                    ? 'Prueba a quitar el filtro de estado o a buscar otra cosa.'
                                    : 'Un tipo vacío no molesta, pero tampoco sirve de nada hasta que algo lo lleva puesto.' }}
                            </p>

                            @can('create', App\Models\Entity::class)
                                @unless ($filtrando)
                                    <a href="{{ route('entities.create', ['type' => $entityType->id]) }}"
                                        class="mt-4 inline-block rounded-xl px-4 py-2 text-[11px] font-black text-slate-950"
                                        style="background-color: {{ $color }}">
                                        + Crear la primera
                                    </a>
                                @endunless
                            @endcan
                        </div>

                    @else

                        {{-- GALERÍA --}}
                        <div x-show="view === 'gallery'" class="grid gap-2 p-4" :class="columns">
                            @foreach ($entities as $entity)
                                @include('entities.partials.library-poster', ['entidad' => $entity])
                            @endforeach
                        </div>

                        {{-- CUADRÍCULA --}}
                        <div x-show="view === 'grid'" x-cloak class="grid gap-3 p-4" :class="columns">
                            @foreach ($entities as $entity)
                                @include('entities.partials.library-card', [
                                    'entidad' => $entity,
                                    'estadoTono' => $estadoTono,
                                ])
                            @endforeach
                        </div>

                        {{-- LISTA --}}
                        <div x-show="view === 'list'" x-cloak class="space-y-2 p-4">
                            @foreach ($entities as $entity)
                                @include('entities.partials.library-row', [
                                    'entidad' => $entity,
                                    'estadoTono' => $estadoTono,
                                ])
                            @endforeach
                        </div>

                        {{-- TABLA --}}
                        <div x-show="view === 'table'" x-cloak class="overflow-x-auto">
                            <table class="w-full min-w-[720px]">

                                <thead class="border-b border-slate-800 bg-slate-950/40 text-left">
                                    <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        <th class="px-3 py-2.5">Entidad</th>
                                        <th class="px-3 py-2.5">Estado</th>
                                        <th class="px-3 py-2.5">Visibilidad</th>
                                        <th class="px-3 py-2.5 text-right">Rasgos</th>
                                        <th class="px-3 py-2.5 text-right">Colecciones</th>
                                        <th class="px-3 py-2.5 text-right">Versiones</th>
                                        <th class="px-3 py-2.5"></th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-800/70">
                                    @foreach ($entities as $entity)
                                        <tr class="transition hover:bg-slate-900/60">
                                            <td class="px-3 py-2">
                                                <a href="{{ route('entities.show', $entity) }}"
                                                    class="flex items-center gap-2">
                                                    <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                        @if ($entity->base_display_image_url)
                                                            <img src="{{ $entity->base_display_image_url }}" alt=""
                                                                loading="lazy" class="h-full w-full object-cover">
                                                        @else
                                                            <span class="flex h-full w-full items-center justify-center text-[11px]"
                                                                style="color: {{ $color }}">{{ $entityType->icon ?: '◇' }}</span>
                                                        @endif
                                                    </span>

                                                    <span class="min-w-0">
                                                        <span class="block truncate text-[12px] font-black text-white">
                                                            {{ $entity->name }}
                                                        </span>
                                                        <span class="block font-mono text-[9px] text-slate-600">
                                                            {{ $entity->code }}
                                                        </span>
                                                    </span>
                                                </a>
                                            </td>

                                            <td class="px-3 py-2">
                                                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$entity->status] ?? 'bg-slate-800 text-slate-500' }}">
                                                    {{ $entity->status_label }}
                                                </span>
                                            </td>

                                            <td class="px-3 py-2 text-[11px] text-slate-400">
                                                {{ $entity->visibility_label }}</td>

                                            <td class="px-3 py-2 text-right font-mono text-[11px] text-violet-300">
                                                {{ $entity->entity_attributes_count }}</td>

                                            <td class="px-3 py-2 text-right font-mono text-[11px] text-cyan-300">
                                                {{ $entity->collections_count }}</td>

                                            <td class="px-3 py-2 text-right font-mono text-[11px] text-amber-300">
                                                {{ $entity->entity_versions_count }}</td>

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

                        <div class="border-t border-slate-800 px-4 py-3">
                            {{ $entities->links() }}
                        </div>

                    @endif

                </section>

            </div>


            {{-- ============================================================= --}}
            {{-- LO QUE DESCRIBE AL TIPO --}}
            {{-- ============================================================= --}}

            <aside class="space-y-4">

                {{-- Qué características lleva lo de este tipo --}}
                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Qué suelen llevar
                    </p>

                    @if ($rasgos->isEmpty())
                        <p class="mt-2 text-[11px] leading-4 text-slate-600">
                            Ninguna de sus entidades tiene características todavía.
                        </p>
                    @else
                        <p class="mt-1 text-[10px] leading-4 text-slate-600">
                            Si casi todas llevan una característica, esa describe al tipo tanto
                            como su nombre.
                        </p>

                        <div class="mt-2.5 space-y-2">
                            @foreach ($rasgos as $rasgo)
                                @php $colorRasgo = $rasgo['attribute']->color ?: '#64748b'; @endphp

                                <a href="{{ route('attributes.show', $rasgo['attribute']) }}" class="group block">
                                    <span class="flex items-baseline justify-between gap-2">
                                        <span class="flex min-w-0 items-center gap-1.5">
                                            <span class="text-[11px]"
                                                style="color: {{ $colorRasgo }}">{{ $rasgo['attribute']->icon ?: '◆' }}</span>
                                            <span class="truncate text-[11px] font-bold text-slate-400 transition group-hover:text-slate-200">
                                                {{ $rasgo['attribute']->name }}
                                            </span>
                                        </span>

                                        <span class="shrink-0 font-mono text-[11px] font-black"
                                            style="color: {{ $colorRasgo }}">{{ $rasgo['share'] }}%</span>
                                    </span>

                                    <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-slate-950">
                                        <span class="block h-full rounded-full transition-all"
                                            style="width: {{ max(3, $rasgo['share']) }}%; background-color: {{ $colorRasgo }}"></span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                </section>


                {{-- Dónde acaban --}}
                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Dónde acaban
                    </p>

                    @if ($colecciones->isEmpty())
                        <p class="mt-2 text-[11px] leading-4 text-slate-600">
                            Ninguna de sus entidades está en una colección todavía.
                        </p>
                    @else
                        <ul class="mt-2.5 space-y-1.5">
                            @foreach ($colecciones as $coleccion)
                                <li>
                                    <a href="{{ route('collections.show', $coleccion) }}"
                                        class="flex items-center gap-2.5 rounded-xl border bg-slate-950/60 p-2 transition hover:bg-slate-900"
                                        style="border-color: {{ $coleccion->color ?: '#1e293b' }}66">

                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                            @if ($coleccion->image_url)
                                                <img src="{{ $coleccion->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-sm"
                                                    style="color: {{ $coleccion->color ?: '#64748b' }}">
                                                    {{ $coleccion->icon ?: '◈' }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200">
                                            {{ $coleccion->name }}
                                        </span>

                                        <span class="shrink-0 font-mono text-[10px] text-slate-500">
                                            {{ $coleccion->typed_count }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </section>


                {{-- Los demás tipos, para saltar sin volver al índice --}}
                @if ($hermanos->isNotEmpty())
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                        <p class="flex items-center gap-2 text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Tus otros tipos
                            <span class="h-px flex-1 bg-slate-800"></span>
                            <a href="{{ route('entity-types.index') }}"
                                class="text-slate-600 transition hover:text-indigo-300">todos →</a>
                        </p>

                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            @foreach ($hermanos as $hermano)
                                @php $colorHermano = $hermano->color ?: '#64748b'; @endphp

                                <a href="{{ route('entity-types.show', $hermano) }}"
                                    class="flex items-center gap-1.5 rounded-lg border bg-slate-950 px-2 py-1.5 transition hover:bg-slate-900"
                                    style="border-color: {{ $colorHermano }}44">

                                    @if ($hermano->image_url)
                                        <img src="{{ $hermano->image_url }}" alt="" loading="lazy"
                                            class="h-4 w-4 rounded object-cover">
                                    @else
                                        <span class="text-[11px]"
                                            style="color: {{ $colorHermano }}">{{ $hermano->icon ?: '◇' }}</span>
                                    @endif

                                    <span class="text-[10px] font-bold text-slate-300">{{ $hermano->name }}</span>
                                    <span class="font-mono text-[9px] text-slate-600">{{ $hermano->entities_count }}</span>
                                </a>
                            @endforeach
                        </div>

                        @can('create', App\Models\EntityType::class)
                            <a href="{{ route('entity-types.create') }}"
                                class="mt-2.5 flex items-center justify-center gap-2 rounded-xl border border-dashed border-slate-700 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-emerald-500/60 hover:text-emerald-300">
                                <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                                Crear otro tipo
                            </a>
                        @endcan

                    </section>
                @endif


                {{-- Lo que no se deshace --}}
                @can('delete', $entityType)
                    <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-rose-300">
                            Lo que no se deshace
                        </p>

                        <p class="mt-2 text-[11px] leading-4 text-slate-500">
                            @if ($stats['total'] > 0)
                                Lo llevan puesto <strong class="text-slate-300">{{ $stats['total'] }}</strong>
                                {{ $stats['total'] === 1 ? 'entidad' : 'entidades' }}. Al borrarlo se quedan
                                sin tipo; no se borran con él.
                            @else
                                No lo lleva ninguna entidad, así que borrarlo no arrastra nada.
                            @endif
                        </p>

                        <form method="POST" action="{{ route('entity-types.destroy', $entityType) }}" class="mt-3"
                            data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                            data-confirm-title="Borrar este tipo"
                            data-confirm-subject="{{ $entityType->name }}"
                            data-confirm-message="Las entidades que lo llevan se quedarán sin tipo."
                            data-confirm-detail="Esto no se puede deshacer."
                            data-confirm-action="Borrarlo">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="w-full rounded-xl border border-rose-500/40 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/15">
                                Borrar este tipo
                            </button>
                        </form>

                    </section>
                @endcan

            </aside>

        </div>

    </div>

</x-app-layout>

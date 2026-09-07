@php
    /*
     * Todas las versiones de una entidad.
     *
     * Aquí se responde una pregunta muy concreta: ¿en cuántos estados tengo a
     * esta entidad, y cuál es la que enseña? Antes había dos maneras de
     * mirarlo —línea de tiempo y cuadrícula— y la primera ocupaba una pantalla
     * entera por versión.
     *
     * Ahora seis, porque son seis preguntas distintas:
     *
     *   galería    cuántas caras tiene, de un vistazo
     *   cuadrícula la ficha de cada una con sus acciones
     *   lista      para repasar muchas seguidas
     *   tabla      para comparar cambios, imágenes y estado
     *   árbol      cuál hereda de cuál   ← lo que ninguna otra enseña
     *   recorrido  en qué orden se leen, con la base marcada
     *
     * Y las dos acciones que nadie entendía —comparar y cambiar base— van
     * explicadas donde están, no en otra pantalla.
     */

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $activeBaseEntityVersion = $entity->baseVersionSetting?->entityVersion;

    $versiones = $entity->entityVersions;

    $raices = $versiones->whereNull('parent_entity_version_id');

    $cifras = [
        ['Versiones', $versiones->count(), 'text-violet-300'],
        ['Con cambios', $versiones->where('version_attributes_count', '>', 0)->count(), 'text-indigo-300'],
        ['Con galería', $versiones->where('images_count', '>', 0)->count(), 'text-fuchsia-300'],
        ['Moldes distintos', $versiones->pluck('version_id')->unique()->count(), 'text-cyan-300'],
        ['Inactivas', $versiones->where('status', '!=', 'ACTIVE')->count(), 'text-amber-300'],
    ];
@endphp

<x-app-layout :title="'Versiones de ' . $entity->name" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <div x-data="{
        view: 'grid',
        size: 4,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.entityVersions.view') ?? '{}');
                if (['gallery', 'grid', 'list', 'table', 'tree', 'timeline'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4, 5, 6].includes(g.size)) this.size = g.size;
            } catch (e) {}

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.entityVersions.view',
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
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('entities.show', $entity) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($entity->image_url)
                    <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('versions.entities.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Aplicadas
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    Versiones de {{ $entity->name }}
                </h1>

                <p class="text-[10px] text-slate-500">
                    {{ $entity->entityType?->name ?? 'Sin tipo' }}
                    <span class="text-slate-700">·</span>
                    {{ $versiones->count() }} {{ $versiones->count() === 1 ? 'estado guardado' : 'estados guardados' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @can('update', $entity)
                    <a href="{{ route('entity-versions.create', $entity) }}"
                        class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        + Nueva versión
                    </a>
                @endcan

                <a href="{{ route('entities.show', $entity) }}"
                    class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                    Ver la entidad
                </a>
            </div>
        </header>


        @include('versions.partials.workspace-navigation')


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
        {{-- LAS CIFRAS --}}
        {{-- ===================================================== --}}

        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
            @foreach ($cifras as [$etiqueta, $valor, $tono])
                <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                    <p class="font-mono text-xl font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</p>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                </div>
            @endforeach
        </div>


        {{-- ===================================================== --}}
        {{-- LA BASE ACTIVA --}}
        {{-- ===================================================== --}}

        @include('entities.partials.base-version-manager')


        @if ($versiones->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="capas" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $entity->name }} solo existe en un estado
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    Una versión sirve para tener a la misma entidad en dos momentos sin duplicarla:
                    «{{ $entity->name }} niño» y «{{ $entity->name }} adulto» siguen siendo la misma persona,
                    con la misma historia y los mismos torneos.
                </p>

                @can('update', $entity)
                    <a href="{{ route('entity-versions.create', $entity) }}"
                        class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        Crear la primera
                    </a>
                @endcan
            </div>

        @else

            {{-- ===================================================== --}}
            {{-- COMPARAR --}}
            {{-- ===================================================== --}}

            {{--
                «Comparar» era un enlace suelto. Lo que hace no se adivina, y es
                justo lo que más falta hace cuando ya tienes cuatro versiones:
                poner sus características una al lado de la otra y ver en qué se
                diferencian de verdad.
            --}}

            @if ($versiones->count() > 1)
                <section x-data="{ abierto: false }"
                    class="overflow-hidden rounded-2xl border border-cyan-500/25 bg-slate-900/50">

                    <div class="flex flex-wrap items-center gap-3 p-4">

                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                            <x-omni-icon name="controles" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">Comparar sus versiones</h2>
                            <p class="text-[10px] leading-relaxed text-slate-500">
                                Pone las características de varias versiones en columnas, una al lado de otra,
                                y marca en qué se diferencian.
                            </p>
                        </div>

                        <button type="button" @click="abierto = !abierto"
                            class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-cyan-500 hover:text-cyan-300">
                            <span x-text="abierto ? 'Ocultar' : '¿Cómo se ve?'"></span>
                        </button>

                        <a href="{{ route('entity-versions.compare', $entity) }}"
                            class="shrink-0 rounded-xl bg-cyan-500/15 px-3 py-2 text-[11px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-slate-950">
                            Comparar →
                        </a>
                    </div>

                    <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800 bg-slate-950/40 p-4">
                        <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)]">

                            <svg viewBox="0 0 250 110" class="h-auto w-full text-cyan-400" fill="none"
                                stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">

                                <rect x="6" y="6" width="72" height="98" rx="5" />
                                <rect x="88" y="6" width="72" height="98" rx="5" />
                                <rect x="170" y="6" width="72" height="98" rx="5" opacity=".5" />

                                <circle cx="42" cy="26" r="10" opacity=".7" />
                                <circle cx="124" cy="26" r="10" opacity=".7" />
                                <circle cx="206" cy="26" r="10" opacity=".35" />

                                <path d="M16 48h52M98 48h52M180 48h52" opacity=".25" />

                                <path d="M16 62h34M98 62h34M180 62h34" opacity=".25" />

                                <path d="M16 76h44" opacity=".9" />
                                <path d="M98 76h26" opacity=".9" />
                                <path d="M180 76h38" opacity=".35" />
                                <rect x="12" y="70" width="54" height="12" rx="3" opacity=".55" />
                                <rect x="94" y="70" width="54" height="12" rx="3" opacity=".55" />

                                <path d="M16 92h40M98 92h40M180 92h40" opacity=".25" />
                            </svg>

                            <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                                <p>
                                    Cada columna es una versión, con su cara arriba. Cada fila es una
                                    característica: fuerza, edad, clan…
                                </p>
                                <p>
                                    Las filas <strong class="text-cyan-300">resaltadas</strong> son las que
                                    cambian de una versión a otra; las demás son iguales en todas y por eso se
                                    quedan apagadas. Así se ve de un vistazo qué distingue de verdad a
                                    «{{ $entity->name }} niño» de «{{ $entity->name }} adulto», en vez de
                                    tener que abrir las dos y recordar.
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    La primera columna es siempre la <strong class="text-slate-300">entidad
                                    original</strong>, para saber de qué se parte.
                                </p>
                            </div>

                        </div>
                    </div>

                </section>
            @endif


            {{-- ===================================================== --}}
            {{-- FORMA DE MIRAR --}}
            {{-- ===================================================== --}}

            <div class="sticky top-20 z-20 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

                <p class="min-w-0 flex-1 text-[11px] font-black text-slate-400">
                    Sus {{ $versiones->count() }} versiones
                </p>

                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['gallery', 'galeria', 'Galería: solo las caras'], ['grid', 'cuadricula', 'Cuadrícula: la ficha de cada una'], ['list', 'capas', 'Lista: una línea por versión'], ['table', 'controles', 'Tabla: para comparar de un vistazo'], ['tree', 'grafo', 'Árbol: cuál hereda de cuál'], ['timeline', 'historial', 'Recorrido: en qué orden se leen']] as [$modo, $icono, $ayuda])
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
            </div>


            {{-- ============ GALERÍA ============ --}}

            <div x-show="view === 'gallery'" x-cloak class="grid gap-2.5" :class="columns">
                @foreach ($versiones as $item)
                    <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                        class="group relative block overflow-hidden rounded-2xl border bg-slate-950 transition duration-300 hover:-translate-y-1 {{ $item->baseSetting ? 'border-amber-500/40' : 'border-slate-800 hover:border-violet-500/50' }}">

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
                            <span class="block truncate text-[9px] text-violet-300">{{ $item->version?->name }}</span>
                        </span>

                        @if ($item->baseSetting)
                            <span class="absolute right-2 top-2 rounded-lg bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-amber-950">★</span>
                        @endif
                    </a>
                @endforeach
            </div>


            {{-- ============ CUADRÍCULA ============ --}}

            <div x-show="view === 'grid'" class="grid gap-3" :class="columns">
                @foreach ($versiones as $item)
                    <article class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5 {{ $item->baseSetting ? 'border-amber-500/40' : 'border-slate-800 hover:border-violet-500/40' }}">

                        <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                            class="relative block aspect-square overflow-hidden bg-slate-950">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-3xl text-slate-800">◈</span>
                            @endif

                            <span class="absolute left-2 top-2 rounded-lg border border-violet-500/30 bg-slate-950/85 px-1.5 py-0.5 text-[9px] font-black text-violet-300">
                                {{ $item->version?->name }}
                            </span>

                            @if ($item->baseSetting)
                                <span class="absolute right-2 top-2 rounded-lg bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-amber-950"
                                    title="Base activa: la cara que enseña la aplicación">★</span>
                            @endif
                        </a>

                        <div class="flex-1 p-2.5">
                            <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                                class="block truncate text-[12px] font-black text-white transition hover:text-violet-300">
                                {{ $item->name }}
                            </a>

                            @if ($item->parent_entity_version_id)
                                <p class="truncate text-[9px] text-slate-600">
                                    hereda de {{ $versiones->firstWhere('id', $item->parent_entity_version_id)?->name ?? '—' }}
                                </p>
                            @endif

                            <div class="mt-2 flex flex-wrap gap-1 text-[9px]">
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

                        <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">
                            <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">Ver</a>

                            @can('update', $item)
                                <a href="{{ route('entity-versions.edit', [$entity, $item]) }}"
                                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">✎ Editar</a>

                                <a href="{{ route('entity-versions.attributes.edit', [$entity, $item]) }}"
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
                @foreach ($versiones as $item)
                    <article class="flex items-center gap-3 rounded-xl border bg-slate-900/50 p-2 transition hover:bg-slate-900 {{ $item->baseSetting ? 'border-amber-500/40' : 'border-slate-800 hover:border-violet-500/40' }}">

                        <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                            class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                                    class="truncate text-[12px] font-black text-white transition hover:text-violet-300">{{ $item->name }}</a>

                                @if ($item->baseSetting)
                                    <span class="rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★ BASE</span>
                                @endif

                                @if ($item->status !== 'ACTIVE')
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$item->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $item->status_label }}
                                    </span>
                                @endif
                            </div>

                            <p class="truncate text-[10px] text-slate-500">
                                <span class="text-violet-400">{{ $item->version?->name }}</span>
                                @if ($item->parent_entity_version_id)
                                    <span class="text-slate-700">·</span>
                                    hereda de {{ $versiones->firstWhere('id', $item->parent_entity_version_id)?->name ?? '—' }}
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
                            <a href="{{ route('entity-versions.edit', [$entity, $item]) }}"
                                class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>
                        @endcan
                    </article>
                @endforeach
            </div>


            {{-- ============ TABLA ============ --}}

            <div x-show="view === 'table'" x-cloak
                class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full min-w-[760px]">
                    <thead class="border-b border-slate-800 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-2.5">Versión</th>
                            <th class="px-3 py-2.5">Molde</th>
                            <th class="px-3 py-2.5">Hereda de</th>
                            <th class="px-3 py-2.5 text-center">Base</th>
                            <th class="px-3 py-2.5 text-right">Cambios</th>
                            <th class="px-3 py-2.5 text-right">Imágenes</th>
                            <th class="px-3 py-2.5">Estado</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($versiones as $item)
                            <tr class="transition hover:bg-slate-900/60">
                                <td class="px-3 py-2">
                                    <a href="{{ route('entity-versions.show', [$entity, $item]) }}" class="flex items-center gap-2">
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

                                <td class="px-3 py-2 text-[11px] text-violet-300">{{ $item->version?->name }}</td>

                                <td class="px-3 py-2 text-[11px] text-slate-500">
                                    {{ $item->parent_entity_version_id
                                        ? ($versiones->firstWhere('id', $item->parent_entity_version_id)?->name ?? '—')
                                        : 'La entidad original' }}
                                </td>

                                <td class="px-3 py-2 text-center">
                                    @if ($item->baseSetting)
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
                                    <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                                        class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">Ver →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


            {{-- ============ ÁRBOL ============ --}}

            <div x-show="view === 'tree'" x-cloak
                class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                <p class="mb-3 text-[11px] leading-relaxed text-slate-500">
                    Una versión puede <strong class="text-slate-300">heredar de otra</strong> en vez de la
                    entidad original: «Modo Sabio» puede partir de «Shippuden» y quedarse solo con lo que
                    cambia. Eso es lo único que enseña esta vista.
                </p>

                <div class="space-y-1.5">
                    @foreach ($raices as $raiz)
                        @include('entity-versions.partials.version-branch', [
                            'nodo' => $raiz,
                            'nivel' => 0,
                            'todas' => $versiones,
                            'entity' => $entity,
                            'estadoTono' => $estadoTono,
                        ])
                    @endforeach
                </div>

                @if ($raices->count() === $versiones->count())
                    <p class="mt-3 border-t border-slate-800 pt-2.5 text-[10px] text-slate-600">
                        Ninguna hereda de otra todavía: las {{ $versiones->count() }} parten directamente de
                        {{ $entity->name }}.
                    </p>
                @endif
            </div>


            {{-- ============ RECORRIDO ============ --}}

            <div x-show="view === 'timeline'" x-cloak
                class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                <div class="relative space-y-3 pl-8">

                    <span class="absolute bottom-3 left-3 top-3 w-px bg-slate-800"></span>

                    {{-- El punto de partida --}}
                    <div class="relative">
                        <span class="absolute -left-[26px] top-3 h-3 w-3 rounded-full border-2 border-slate-700 bg-slate-950"></span>

                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">
                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($entity->image_url)
                                    <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-[12px] font-black text-slate-300">{{ $entity->name }}</span>
                                <span class="block text-[9px] text-slate-600">La entidad original, de la que parte todo</span>
                            </span>
                        </div>
                    </div>

                    @foreach ($versiones as $item)
                        <div class="relative">
                            <span class="absolute -left-[26px] top-3 h-3 w-3 rounded-full border-2 {{ $item->baseSetting ? 'border-amber-400 bg-amber-400' : 'border-violet-500 bg-slate-950' }}"></span>

                            <div class="flex flex-wrap items-center gap-2.5 rounded-xl border p-2.5 transition hover:bg-slate-900 {{ $item->baseSetting ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-800 bg-slate-950/60' }}">

                                <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                                    class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    @if ($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                                            class="truncate text-[13px] font-black text-white transition hover:text-violet-300">{{ $item->name }}</a>

                                        @if ($item->baseSetting)
                                            <span class="rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★ BASE ACTIVA</span>
                                        @endif
                                    </div>

                                    <p class="truncate text-[10px] text-violet-400">{{ $item->version?->name }}</p>

                                    @if ($item->description)
                                        <p class="mt-0.5 line-clamp-2 text-[10px] leading-4 text-slate-500">{{ $item->description }}</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center gap-1.5">
                                    <span class="rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $item->version_attributes_count > 0 ? 'border-violet-500/25 text-violet-300' : 'border-slate-800 text-slate-700' }}">
                                        {{ $item->version_attributes_count }} ✎
                                    </span>

                                    <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                                        class="rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                                        Ver →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

        @endif

    </div>

</x-app-layout>

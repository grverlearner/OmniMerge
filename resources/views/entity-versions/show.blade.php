@php
    /*
     * La ficha de una versión de entidad.
     *
     * Es el punto donde se juntan las dos mitades del taller, y la pantalla no
     * lo enseñaba: una versión no existe sola, sale de una ENTIDAD más un
     * MOLDE. Por eso lo primero que hay ahora es esa suma, con las tres caras
     * —la de la entidad, la del molde y la que resulta—, y no una imagen
     * suelta con su nombre debajo.
     *
     * Las cuatro secciones se mantienen porque responden cuatro preguntas
     * distintas, pero cada una dice cuántas cosas tiene antes de abrirla:
     *
     *   Resumen        de dónde sale, qué es y qué se puede hacer con ella
     *   Características qué valores tiene y cuáles son suyos de verdad
     *   Multimedia     su portada y su galería
     *   Jerarquía      de quién hereda y quién hereda de ella
     *
     * La distinción que más falta hacía: una característica **heredada** viene
     * de la entidad y cambia cuando cambie la entidad; una **propia** solo
     * existe aquí. Se ve de un vistazo, con su origen y su cara.
     */

    $tonoEstado = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $esBase = (bool) $entityVersion->baseSetting;

    $propias = $caracteristicas->where('source', 'VERSION');
    $heredadas = $caracteristicas->where('source', '!=', 'VERSION');

    $presentacion = $entity->presentation;

    $esPresentacion = $presentacion?->entity_version_id === $entityVersion->id;
@endphp

<x-app-layout :title="$entityVersion->name" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <div x-data="{
        tab: 'summary',

        init() {
            try {
                const g = localStorage.getItem('omnimerge.entityVersion.tab');
                if (['summary', 'attributes', 'media', 'hierarchy'].includes(g)) this.tab = g;
            } catch (e) {}

            this.$watch('tab', (v) => {
                try { localStorage.setItem('omnimerge.entityVersion.tab', v); } catch (e) {}
            });
        },

        soloPropias: false,
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('entity-versions.index', $entity) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($entityVersion->image_url)
                    <img src="{{ $entityVersion->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('entity-versions.index', $entity) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Versiones de {{ $entity->name }}
                </a>

                <div class="mt-0.5 flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-xl font-black tracking-tight text-white">
                        {{ $entityVersion->name }}
                    </h1>

                    @if ($esBase)
                        <span class="rounded bg-amber-400 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-950">
                            ★ Base activa
                        </span>
                    @endif

                    @if ($entityVersion->status !== 'ACTIVE')
                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$entityVersion->status] ?? 'bg-slate-800 text-slate-500' }}">
                            {{ $entityVersion->status_label }}
                        </span>
                    @endif
                </div>

                <p class="font-mono text-[10px] text-slate-600">{{ $entityVersion->code }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @can('update', $entityVersion)
                    <a href="{{ route('entity-versions.edit', [$entity, $entityVersion]) }}"
                        class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                        ✎ Editar
                    </a>

                    <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
                        class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        Características
                    </a>
                @endcan
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


        {{-- Recién creada: decir qué falta, no solo felicitar --}}
        @if (session('entity_version_just_created'))
            <div class="rounded-2xl border border-violet-500/30 bg-violet-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-violet-200">Versión creada.</p>
                <p class="mt-0.5 text-[11px] leading-relaxed text-violet-200/70">
                    De momento hereda todo de {{ $entity->name }}. Lo siguiente suele ser decir
                    <strong>qué cambia</strong> en ella —desde
                    <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
                        class="font-black underline">Características</a>— y subirle sus imágenes.
                </p>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- DE DÓNDE SALE --}}
        {{-- ===================================================== --}}

        {{--
            La suma que da sentido a todo: una entidad + un molde = esta
            versión. Las tres caras juntas, porque cada una lleva a un sitio
            distinto y porque así se entiende sin leer nada.
        --}}

        <section class="grid gap-4 lg:grid-cols-[minmax(0,300px)_minmax(0,1fr)]">

            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                <div class="relative aspect-square overflow-hidden bg-slate-950">
                    @if ($entityVersion->image_url)
                        <img src="{{ $entityVersion->image_url }}" alt="{{ $entityVersion->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-5xl text-slate-800">◈</span>
                    @endif

                    @if ($esBase)
                        <span class="absolute right-2 top-2 rounded-lg bg-amber-400 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-950">
                            ★ Base activa
                        </span>
                    @endif
                </div>

                <p class="border-t border-slate-800 px-3 py-2 text-center text-[10px] text-slate-600">
                    La cara de esta versión
                </p>
            </div>


            <div class="space-y-3">

                {{-- La suma --}}
                <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900/50 p-3">

                    <a href="{{ route('entities.show', $entity) }}"
                        class="group flex min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-indigo-500/50">
                        <span class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                            @if ($entity->image_url)
                                <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">La entidad</span>
                            <span class="block truncate text-[12px] font-black text-white transition group-hover:text-indigo-300">{{ $entity->name }}</span>
                            <span class="block truncate text-[9px] text-slate-600">{{ $entity->entityType?->name ?? 'Sin tipo' }}</span>
                        </span>
                    </a>

                    <span class="shrink-0 text-lg font-black text-slate-700">+</span>

                    <a href="{{ route('versions.show', $entityVersion->version) }}"
                        class="group flex min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-violet-500/50">
                        <span class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                            @if ($entityVersion->version?->image_url)
                                <img src="{{ $entityVersion->version->image_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-violet-400">◈</span>
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">El molde</span>
                            <span class="block truncate text-[12px] font-black text-white transition group-hover:text-violet-300">{{ $entityVersion->version?->name }}</span>
                            <span class="block truncate text-[9px] text-slate-600">
                                {{ $entityVersion->version?->kind_label }} · {{ $entityVersion->version?->scope_label }}
                            </span>
                        </span>
                    </a>

                    <span class="shrink-0 text-lg font-black text-slate-700">=</span>

                    <span class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-violet-500/30 bg-violet-500/5 p-2">
                        <span class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                            @if ($entityVersion->image_url)
                                <img src="{{ $entityVersion->image_url }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[9px] font-black uppercase tracking-wider text-violet-400/70">Esta versión</span>
                            <span class="block truncate text-[12px] font-black text-white">{{ $entityVersion->name }}</span>
                        </span>
                    </span>
                </div>


                {{-- Cifras --}}
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([['Características', $caracteristicas->count(), 'text-white'], ['Propias', $propias->count(), 'text-violet-300'], ['Imágenes', $entityVersion->images->count(), 'text-fuchsia-300'], ['Versiones hijas', $entityVersion->children->count(), 'text-cyan-300']] as [$etiqueta, $valor, $tono])
                        <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                            <p class="font-mono text-xl font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</p>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                        </div>
                    @endforeach
                </div>


                {{-- Descripción --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">Qué cambia en ella</h2>
                    <p class="mt-1 text-[12px] leading-relaxed {{ $entityVersion->description ? 'text-slate-300' : 'text-slate-600' }}">
                        {{ $entityVersion->description ?: 'Sin descripción. Una línea diciendo qué la distingue ahorra abrir la ficha para acordarse.' }}
                    </p>
                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- LAS PESTAÑAS --}}
        {{-- ===================================================== --}}

        <nav class="sticky top-20 z-20 flex items-center gap-1 overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/95 p-1.5 backdrop-blur">
            @foreach ([['summary', 'Resumen', 'panel', null], ['attributes', 'Características', 'controles', $caracteristicas->count()], ['media', 'Multimedia', 'galeria', $entityVersion->images->count()], ['hierarchy', 'Jerarquía', 'grafo', $entityVersion->children->count()]] as [$clave, $etiqueta, $icono, $cuantos])
                <button type="button" @click="tab = '{{ $clave }}'"
                    :aria-current="tab === '{{ $clave }}' ? 'page' : null"
                    :class="tab === '{{ $clave }}'
                        ? 'bg-violet-500/15 text-violet-200 ring-1 ring-inset ring-violet-500/40'
                        : 'text-slate-500 hover:bg-slate-900 hover:text-slate-200'"
                    class="flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-[12px] font-black transition">
                    <x-omni-icon :name="$icono" size="h-4 w-4" />
                    {{ $etiqueta }}

                    @if (!is_null($cuantos))
                        <span class="rounded px-1 font-mono text-[10px] {{ $cuantos > 0 ? 'bg-slate-800 text-slate-300' : 'text-slate-700' }}">
                            {{ $cuantos }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>


        {{-- ===================================================== --}}
        {{-- RESUMEN --}}
        {{-- ===================================================== --}}

        <div x-show="tab === 'summary'" class="space-y-4">

            {{-- Qué se puede hacer con ella --}}
            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="border-b border-slate-800 px-4 py-3">
                    <h2 class="text-[13px] font-black text-white">Qué se puede hacer con ella</h2>
                    <p class="text-[10px] text-slate-500">
                        Cada botón dice qué pasa antes de pulsarlo.
                    </p>
                </div>

                <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- Base activa --}}
                    @can('update', $entity)
                        <div class="rounded-xl border p-3 {{ $esBase ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-800 bg-slate-950' }}">
                            <div class="flex items-center gap-1.5">
                                <span class="text-amber-400">★</span>
                                <span class="text-[11px] font-black text-white">Base activa</span>
                            </div>

                            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                                @if ($esBase)
                                    Ahora mismo es la cara que la aplicación enseña de {{ $entity->name }}.
                                    Al quitarla se vuelve a la entidad original; no se borra nada.
                                @else
                                    Ponla como la cara que la aplicación enseñará de {{ $entity->name }}.
                                    La que lo sea ahora deja de serlo; ninguna se borra.
                                @endif
                            </p>

                            @if ($esBase)
                                <form method="POST" action="{{ route('entities.base-version.destroy', $entity) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full rounded-lg border border-amber-500/30 py-1.5 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-amber-950">
                                        Volver a la entidad original
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('entities.base-version.update', $entity) }}" class="mt-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="entity_version_id" value="{{ $entityVersion->id }}">
                                    <button type="submit"
                                        class="w-full rounded-lg bg-amber-500/15 py-1.5 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-amber-950">
                                        Hacerla la base activa
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endcan

                    {{-- Comparar --}}
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-cyan-400"><x-omni-icon name="controles" size="h-3.5 w-3.5" /></span>
                            <span class="text-[11px] font-black text-white">Compararla con otras</span>
                        </div>

                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            Pone sus características al lado de las de otras versiones y marca en qué se
                            diferencian de verdad.
                        </p>

                        <a href="{{ route('entity-versions.compare', ['entity' => $entity, 'versions' => [$entityVersion->id]]) }}"
                            class="mt-2 block rounded-lg bg-cyan-500/15 py-1.5 text-center text-[10px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-slate-950">
                            Comparar →
                        </a>
                    </div>

                    {{-- Una versión que cuelgue de esta --}}
                    @can('update', $entity)
                        <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-violet-400"><x-omni-icon name="capas" size="h-3.5 w-3.5" /></span>
                                <span class="text-[11px] font-black text-white">Una versión encima de esta</span>
                            </div>

                            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                                Crea otra versión que herede de <strong class="text-slate-300">esta</strong> en
                                vez de la entidad: solo guardará lo que cambie respecto a ella.
                            </p>

                            <a href="{{ route('entity-versions.create', ['entity' => $entity, 'parent_entity_version_id' => $entityVersion->id]) }}"
                                class="mt-2 block rounded-lg bg-violet-500/15 py-1.5 text-center text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                + Crear una encima
                            </a>
                        </div>
                    @endcan

                    {{-- Presentación pública --}}
                    @can('update', $entity)
                        <div class="rounded-xl border p-3 {{ $esPresentacion ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-slate-800 bg-slate-950' }}">
                            <div class="flex items-center gap-1.5">
                                <span class="text-emerald-400"><x-omni-icon name="globo" size="h-3.5 w-3.5" /></span>
                                <span class="text-[11px] font-black text-white">Presentación pública</span>
                            </div>

                            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                                @if ($esPresentacion)
                                    Es la que se enseña en tu página pública.
                                @else
                                    Distinta de la base activa: esta es la cara que ven <strong class="text-slate-300">los
                                    demás</strong> en tu página pública, no la que usas tú por dentro.
                                @endif
                            </p>

                            @unless ($esPresentacion)
                                <form method="POST" action="{{ route('entities.presentation.update', $entity) }}" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="mode" value="VERSION">
                                    <input type="hidden" name="entity_version_id" value="{{ $entityVersion->id }}">
                                    <input type="hidden" name="use_version_name" value="1">
                                    <input type="hidden" name="use_version_description" value="1">

                                    <button type="submit"
                                        class="w-full rounded-lg bg-emerald-500/15 py-1.5 text-[10px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-emerald-950">
                                        Enseñar esta en público
                                    </button>
                                </form>
                            @endunless
                        </div>
                    @endcan

                    {{-- Aplicar el molde a más entidades --}}
                    @can('update', $entityVersion->version)
                        <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-indigo-400"><x-omni-icon name="orbita" size="h-3.5 w-3.5" /></span>
                                <span class="text-[11px] font-black text-white">Este molde, a más entidades</span>
                            </div>

                            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                                No toca esta versión: aplica «{{ $entityVersion->version?->name }}» a otras
                                entidades de tu biblioteca.
                            </p>

                            <a href="{{ route('versions.entities.bulk.create', $entityVersion->version) }}"
                                class="mt-2 block rounded-lg border border-slate-800 py-1.5 text-center text-[10px] font-black text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300">
                                Aplicar en lote →
                            </a>
                        </div>
                    @endcan

                    {{-- Herencia --}}
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-400"><x-omni-icon name="chispa" size="h-3.5 w-3.5" /></span>
                            <span class="text-[11px] font-black text-white">Cómo hereda</span>
                        </div>

                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            @if ($entityVersion->inherit_base_attributes)
                                Parte de las características de
                                <strong class="text-slate-300">{{ $entityVersion->parent?->name ?? $entity->name }}</strong>
                                y solo guarda lo que cambia: {{ $propias->count() }} de {{ $caracteristicas->count() }}.
                            @else
                                <strong class="text-amber-300">No hereda.</strong> Todo lo que tiene es suyo;
                                si cambias la entidad, esta versión no se entera.
                            @endif
                        </p>

                        @can('update', $entityVersion)
                            <a href="{{ route('entity-versions.edit', [$entity, $entityVersion]) }}"
                                class="mt-2 block rounded-lg border border-slate-800 py-1.5 text-center text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                                Cambiarlo →
                            </a>
                        @endcan
                    </div>

                </div>
            </section>


            {{-- Un adelanto de las características --}}
            @if ($caracteristicas->isNotEmpty())
                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">Lo más destacado</h2>
                            <p class="text-[10px] text-slate-500">
                                Las primeras {{ min(8, $caracteristicas->count()) }} de sus
                                {{ $caracteristicas->count() }} características.
                            </p>
                        </div>

                        <button type="button" @click="tab = 'attributes'"
                            class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                            Verlas todas →
                        </button>
                    </div>

                    <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($caracteristicas->take(8) as $rasgo)
                            @include('entity-versions.partials.trait-card', ['rasgo' => $rasgo])
                        @endforeach
                    </div>
                </section>
            @endif

        </div>


        {{-- ===================================================== --}}
        {{-- CARACTERÍSTICAS --}}
        {{-- ===================================================== --}}

        <div x-show="tab === 'attributes'" x-cloak class="space-y-4">

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">

                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <x-omni-icon name="controles" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Sus características</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            <strong class="text-violet-300">{{ $propias->count() }} propias</strong> de esta
                            versión y <strong class="text-slate-400">{{ $heredadas->count() }} heredadas</strong>
                            de {{ $entityVersion->parent?->name ?? $entity->name }}. Las heredadas cambian
                            solas si cambia el original; las propias, no.
                        </p>
                    </div>

                    @if ($propias->isNotEmpty() && $heredadas->isNotEmpty())
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                            <input type="checkbox" x-model="soloPropias"
                                class="rounded border-slate-700 bg-slate-900 text-violet-500">
                            <span class="text-[11px] font-black text-slate-300">Solo las propias</span>
                        </label>
                    @endif

                    @can('update', $entityVersion)
                        <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
                            class="shrink-0 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            Editarlas
                        </a>
                    @endcan
                </div>

                @if ($caracteristicas->isEmpty())

                    <div class="p-8 text-center">
                        <span class="inline-flex text-slate-700"><x-omni-icon name="controles" size="h-9 w-9" /></span>

                        <p class="mt-2 text-[13px] font-black text-white">Sin ninguna característica</p>

                        <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                            @if ($entityVersion->inherit_base_attributes)
                                Hereda de {{ $entity->name }}, pero la entidad tampoco tiene ninguna asignada
                                todavía.
                            @else
                                Esta versión no hereda nada y no se le ha dado ningún valor propio.
                            @endif
                        </p>

                        @can('update', $entityVersion)
                            <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
                                class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                                Darle valores
                            </a>
                        @endcan
                    </div>

                @else

                    <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($caracteristicas as $rasgo)
                            <div x-show="! soloPropias || {{ $rasgo['source'] === 'VERSION' ? 'true' : 'false' }}">
                                @include('entity-versions.partials.trait-card', ['rasgo' => $rasgo])
                            </div>
                        @endforeach
                    </div>

                @endif

            </section>

        </div>


        {{-- ===================================================== --}}
        {{-- MULTIMEDIA --}}
        {{-- ===================================================== --}}

        <div x-show="tab === 'media'" x-cloak>
            @include('entity-versions.partials.media-manager')
        </div>


        {{-- ===================================================== --}}
        {{-- JERARQUÍA --}}
        {{-- ===================================================== --}}

        <div x-show="tab === 'hierarchy'" x-cloak class="space-y-4">

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="border-b border-slate-800 px-4 py-3">
                    <h2 class="text-[13px] font-black text-white">De quién hereda</h2>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Una versión parte de la entidad original, o de otra versión suya. Lo que herede es lo
                        que <strong class="text-slate-300">no</strong> hace falta volver a escribir.
                    </p>
                </div>

                <div class="p-4">
                    <div class="relative space-y-2 pl-7">

                        <span class="absolute bottom-3 left-2.5 top-3 w-px bg-slate-800"></span>

                        {{-- El origen --}}
                        <div class="relative">
                            <span class="absolute -left-[22px] top-4 h-2.5 w-2.5 rounded-full border-2 border-slate-700 bg-slate-950"></span>

                            @if ($entityVersion->parent)
                                <a href="{{ route('entity-versions.show', [$entity, $entityVersion->parent]) }}"
                                    class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2.5 transition hover:border-violet-500/50">
                                    <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                        @if ($entityVersion->parent->image_url)
                                            <img src="{{ $entityVersion->parent->image_url }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Hereda de</span>
                                        <span class="block truncate text-[12px] font-black text-white">{{ $entityVersion->parent->name }}</span>
                                        <span class="block truncate text-[9px] text-violet-400">{{ $entityVersion->parent->version?->name }}</span>
                                    </span>
                                </a>
                            @else
                                <a href="{{ route('entities.show', $entity) }}"
                                    class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2.5 transition hover:border-indigo-500/50">
                                    <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                        @if ($entity->image_url)
                                            <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Hereda de</span>
                                        <span class="block truncate text-[12px] font-black text-white">{{ $entity->name }}</span>
                                        <span class="block text-[9px] text-slate-600">La entidad original</span>
                                    </span>
                                </a>
                            @endif
                        </div>

                        {{-- Esta --}}
                        <div class="relative">
                            <span class="absolute -left-[22px] top-4 h-2.5 w-2.5 rounded-full border-2 border-violet-500 bg-violet-500"></span>

                            <div class="flex items-center gap-2.5 rounded-xl border border-violet-500/40 bg-violet-500/5 p-2.5">
                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                    @if ($entityVersion->image_url)
                                        <img src="{{ $entityVersion->image_url }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[9px] font-black uppercase tracking-wider text-violet-400/70">Estás aquí</span>
                                    <span class="block truncate text-[12px] font-black text-white">{{ $entityVersion->name }}</span>
                                    <span class="block truncate text-[9px] text-slate-500">
                                        {{ $propias->count() }} características propias
                                    </span>
                                </span>
                            </div>
                        </div>

                        {{-- Las que cuelgan --}}
                        @forelse ($entityVersion->children as $hija)
                            <div class="relative pl-5">
                                <span class="absolute -left-[22px] top-4 h-2.5 w-2.5 rounded-full border-2 border-slate-700 bg-slate-950"></span>
                                <span class="absolute left-0 top-4 h-px w-4 bg-slate-800"></span>

                                <a href="{{ route('entity-versions.show', [$entity, $hija]) }}"
                                    class="flex items-center gap-2.5 rounded-xl border p-2.5 transition hover:border-violet-500/50 {{ $hija->baseSetting ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-800 bg-slate-950' }}">
                                    <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                        @if ($hija->image_url)
                                            <img src="{{ $hija->image_url }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Hereda de esta</span>
                                        <span class="block truncate text-[12px] font-black text-white">{{ $hija->name }}</span>
                                        <span class="block truncate text-[9px] text-violet-400">{{ $hija->version?->name }}</span>
                                    </span>

                                    @if ($hija->baseSetting)
                                        <span class="shrink-0 rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★</span>
                                    @endif
                                </a>
                            </div>
                        @empty
                            <div class="relative pl-5">
                                <span class="absolute left-0 top-4 h-px w-4 bg-slate-800"></span>

                                <p class="rounded-xl border border-dashed border-slate-800 p-3 text-[11px] leading-relaxed text-slate-600">
                                    Ninguna versión hereda de esta todavía.
                                    @can('update', $entity)
                                        <a href="{{ route('entity-versions.create', ['entity' => $entity, 'parent_entity_version_id' => $entityVersion->id]) }}"
                                            class="font-black text-violet-400 underline transition hover:text-violet-300">Crear una encima →</a>
                                    @endcan
                                </p>
                            </div>
                        @endforelse

                    </div>
                </div>
            </section>


            {{-- El molde, y su propia jerarquía --}}
            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="border-b border-slate-800 px-4 py-3">
                    <h2 class="text-[13px] font-black text-white">El molde, por su lado</h2>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Los moldes tienen su propia jerarquía, independiente de la de las versiones. Esta es
                        la de «{{ $entityVersion->version?->name }}».
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 p-4">

                    @if ($entityVersion->version?->parent)
                        <a href="{{ route('versions.show', $entityVersion->version->parent) }}"
                            class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-violet-500/50">
                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800">
                                @if ($entityVersion->version->parent->image_url)
                                    <img src="{{ $entityVersion->version->parent->image_url }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </span>
                            <span class="text-[11px] font-black text-slate-300">{{ $entityVersion->version->parent->name }}</span>
                        </a>

                        <span class="text-slate-700">→</span>
                    @endif

                    <a href="{{ route('versions.show', $entityVersion->version) }}"
                        class="flex items-center gap-2 rounded-xl border border-violet-500/40 bg-violet-500/10 p-2">
                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800">
                            @if ($entityVersion->version?->image_url)
                                <img src="{{ $entityVersion->version->image_url }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </span>
                        <span class="text-[11px] font-black text-violet-200">{{ $entityVersion->version?->name }}</span>
                    </a>

                    @foreach ($entityVersion->version?->children ?? [] as $hijaMolde)
                        @if ($loop->first)
                            <span class="text-slate-700">→</span>
                        @endif

                        <a href="{{ route('versions.show', $hijaMolde) }}"
                            class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-violet-500/50">
                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800">
                                @if ($hijaMolde->image_url)
                                    <img src="{{ $hijaMolde->image_url }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </span>
                            <span class="text-[11px] font-black text-slate-300">{{ $hijaMolde->name }}</span>
                        </a>
                    @endforeach

                    @if (! $entityVersion->version?->parent && ($entityVersion->version?->children ?? collect())->isEmpty())
                        <p class="text-[11px] text-slate-600">
                            Este molde no cuelga de ninguno ni tiene moldes hijos.
                        </p>
                    @endif
                </div>
            </section>

        </div>


        {{-- ===================================================== --}}
        {{-- ZONA DE PELIGRO --}}
        {{-- ===================================================== --}}

        @can('update', $entityVersion)
            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 px-4 py-3">
                <p class="text-[10px] leading-relaxed text-slate-600">
                    Esta versión no se puede borrar desde aquí. Si te equivocaste de molde, crea la correcta:
                    el molde de una versión no se cambia porque sería otra versión distinta. Para sacarla de
                    circulación sin perderla, ponla <strong class="text-slate-400">Inactiva</strong> desde
                    <a href="{{ route('entity-versions.edit', [$entity, $entityVersion]) }}"
                        class="font-black text-slate-400 underline transition hover:text-white">Editar</a>.
                </p>
            </section>
        @endcan

    </div>

</x-app-layout>

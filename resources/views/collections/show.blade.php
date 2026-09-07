@php
    /*
     * La ficha de una colección.
     *
     * Era la pantalla más pobre del módulo: la portada, el nombre, y una
     * rejilla plana con las entidades de dentro. Una colección **es** lo que
     * agrupa, así que casi todo lo interesante estaba sin contar.
     *
     * Lo que se añade, y todo sale de datos que ya existían:
     *
     *   · DE QUÉ ESTÁ HECHA — el reparto por tipo, con barra y caras. Veinte
     *     personajes y tres aldeas no es lo mismo que veintitrés personajes.
     *   · QUÉ TIENEN EN COMÚN — los valores de catálogo que comparten TODAS.
     *     Es la firma de la colección: si las doce son de Konoha, eso es lo
     *     que la define.
     *   · QUIÉN MÁS PODRÍA ENTRAR — las entidades de fuera que cumplen esa
     *     firma entera, con un botón para meterlas de un clic. Es el pago de
     *     lo anterior: la firma no es decoración, sirve para algo.
     *
     * Y las entidades de dentro se pueden mirar de cinco maneras, quitarse una
     * a una sin abrir el formulario de edición, y filtrarse.
     */

    $acento = $collection->color ?: '#8b5cf6';

    $miembros = $collection->entities;

    $miembrosParaAlpine = $miembros
        ->map(
            fn($entidad) => [
                'id' => (string) $entidad->id,
                'name' => $entidad->name,
                'type_id' => (string) ($entidad->entity_type_id ?? ''),
                'type' => $entidad->entityType?->name,
                'image_url' => $entidad->image_url,
                'url' => route('entities.show', $entidad),
                'quitar' => route('collections.entities.detach', [$collection, $entidad]),
                'otras' => $entidad->collections
                    ->where('id', '!=', $collection->id)
                    ->map(fn($c) => ['name' => $c->name, 'color' => $c->color ?: '#8b5cf6'])
                    ->values(),
            ],
        )
        ->values();

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
@endphp

<x-app-layout :title="$collection->name" surface="dark">

    <x-slot name="header">Colecciones</x-slot>

    <div x-data="fichaDeColeccion(@js($miembrosParaAlpine))" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <span class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                style="border-color: {{ $acento }}55">
                @if ($collection->image_url)
                    <img src="{{ $collection->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-lg"
                        style="color: {{ $acento }}">{{ $collection->icon ?: '◫' }}</span>
                @endif
            </span>

            <div class="min-w-0 flex-1">
                <a href="{{ route('collections.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Colecciones
                </a>

                <div class="mt-0.5 flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-xl font-black tracking-tight text-white">{{ $collection->name }}</h1>

                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                        style="color: {{ $acento }}; background-color: {{ $acento }}22">
                        {{ $collection->visibility_label }}
                    </span>

                    @if ($collection->status !== 'ACTIVE')
                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $estadoTono[$collection->status] ?? 'bg-slate-800 text-slate-500' }}">
                            {{ $estadoEtiqueta[$collection->status] ?? $collection->status }}
                        </span>
                    @endif
                </div>

                <p class="font-mono text-[10px] text-slate-600">{{ $collection->code }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @can('update', $collection)
                    <a href="{{ route('collections.edit', $collection) }}"
                        class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                        ✎ Editar
                    </a>

                    @include('collections.partials.quick-visibility', ['coleccion' => $collection])
                @endcan
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
        {{-- QUÉ ES --}}
        {{-- ===================================================== --}}

        <section class="grid gap-4 lg:grid-cols-[minmax(0,300px)_minmax(0,1fr)]">

            <div class="overflow-hidden rounded-2xl border bg-slate-900/50"
                style="border-color: {{ $acento }}40">

                <div class="relative aspect-[4/3] overflow-hidden bg-slate-950">
                    @if ($collection->image_url)
                        <img src="{{ $collection->image_url }}" alt="{{ $collection->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-6xl"
                            style="color: {{ $acento }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                            {{ $collection->icon ?: '◫' }}
                        </span>
                    @endif
                </div>

                <div class="space-y-2 p-3">
                    @if ($collection->allow_cloning)
                        <p class="flex items-center gap-1.5 text-[10px] font-bold text-emerald-300">
                            <x-omni-icon name="orbita" size="h-3.5 w-3.5" />
                            Otros pueden copiarla
                        </p>
                    @else
                        <p class="flex items-center gap-1.5 text-[10px] font-bold text-slate-600">
                            <x-omni-icon name="orbita" size="h-3.5 w-3.5" />
                            Nadie puede copiarla
                        </p>
                    @endif

                    @if ($collection->sourceCollection)
                        <p class="text-[10px] text-slate-500">
                            Clonada de
                            <strong class="text-slate-300">{{ $collection->sourceCollection->name }}</strong>.
                        </p>
                    @endif

                    <div class="grid grid-cols-2 gap-1.5 border-t border-slate-800 pt-2 text-center">
                        <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5">
                            <span class="block font-mono text-sm font-black {{ $collection->views_count > 0 ? 'text-slate-300' : 'text-slate-700' }}">
                                {{ $collection->views_count }}
                            </span>
                            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Vistas</span>
                        </span>

                        <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5">
                            <span class="block font-mono text-sm font-black {{ $collection->clones_count > 0 ? 'text-cyan-300' : 'text-slate-700' }}">
                                {{ $collection->clones_count }}
                            </span>
                            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Copias</span>
                        </span>
                    </div>
                </div>
            </div>


            <div class="space-y-3">

                {{-- Cifras --}}
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([['Entidades', $cifras['entidades'], $acento], ['Tipos distintos', $cifras['tipos'], null], ['Con imagen', $cifras['con_imagen'], null], ['También en otras', $cifras['compartidas'], null]] as [$etiqueta, $valor, $tono])
                        <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                            <p class="font-mono text-xl font-black {{ $tono ? '' : ($valor > 0 ? 'text-slate-200' : 'text-slate-700') }}"
                                @if ($tono) style="color: {{ $valor > 0 ? $tono : '#475569' }}" @endif>
                                {{ $valor }}
                            </p>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                        </div>
                    @endforeach
                </div>


                {{-- Descripción --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">Qué agrupa</h2>
                    <p class="mt-1 text-[12px] leading-relaxed {{ $collection->description ? 'text-slate-300' : 'text-slate-600' }}">
                        {{ $collection->description ?: 'Sin descripción. Una línea diciendo qué tienen en común las de dentro se agradece dentro de un año.' }}
                    </p>
                </div>


                {{-- De qué está hecha --}}
                @if ($composicion->isNotEmpty())
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">

                        <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                            De qué está hecha
                        </h2>

                        <div class="mt-2 space-y-2">
                            @foreach ($composicion as $bloque)
                                <div class="flex items-center gap-2.5">

                                    <span class="flex -space-x-1.5">
                                        @foreach ($bloque['muestra'] as $muestra)
                                            <span class="h-6 w-6 shrink-0 overflow-hidden rounded border-2 border-slate-900 bg-slate-950"
                                                title="{{ $muestra->name }}">
                                                @if ($muestra->image_url)
                                                    <img src="{{ $muestra->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @endif
                                            </span>
                                        @endforeach
                                    </span>

                                    <span class="w-28 shrink-0 truncate text-[11px] font-black text-slate-300">
                                        {{ $bloque['nombre'] }}
                                    </span>

                                    <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-950">
                                        <span class="block h-full rounded-full"
                                            style="width: {{ max($bloque['porcentaje'], 3) }}%; background-color: {{ $acento }}"></span>
                                    </span>

                                    <span class="shrink-0 font-mono text-[10px] font-black text-slate-500">
                                        {{ $bloque['cuantas'] }} · {{ $bloque['porcentaje'] }}%
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- QUÉ TIENEN EN COMÚN --}}
        {{-- ===================================================== --}}

        @if ($firma->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-emerald-500/25 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                        <x-omni-icon name="grafo" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Lo que tienen todas en común</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Valores de catálogo que comparten las
                            <strong class="text-slate-400">{{ $cifras['entidades'] }}</strong>. Es la firma de
                            esta colección: lo que de verdad la define, más allá de su nombre.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ($firma as $rasgo)
                        <div class="overflow-hidden rounded-xl border border-emerald-500/30 bg-slate-950">
                            <div class="aspect-[4/3] overflow-hidden bg-slate-900">
                                @if ($rasgo->image_url)
                                    <img src="{{ $rasgo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl text-slate-700">◇</span>
                                @endif
                            </div>

                            <div class="p-1.5">
                                <p class="truncate text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    {{ $rasgo->attribute?->name }}
                                </p>
                                <p class="truncate text-[11px] font-black text-white">{{ $rasgo->name }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- QUIÉN MÁS PODRÍA ENTRAR --}}
        {{-- ===================================================== --}}

        {{--
            El pago de la sección anterior: si las doce comparten «Aldea =
            Konoha», estas otras también la tienen y no están dentro. La firma
            deja de ser un dato curioso y pasa a hacer algo.
        --}}

        @if ($sugeridas->isNotEmpty())
            @can('update', $collection)
                <form method="POST" action="{{ route('collections.entities.attach', $collection) }}"
                    x-data="{ elegidas: [] }"
                    class="overflow-hidden rounded-2xl border border-cyan-500/25 bg-slate-900/50">
                    @csrf

                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                            <x-omni-icon name="chispa" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">Quién más podría entrar</h2>
                            <p class="text-[10px] leading-relaxed text-slate-500">
                                {{ $sugeridas->count() }}
                                {{ $sugeridas->count() === 1 ? 'entidad cumple' : 'entidades cumplen' }}
                                <strong class="text-emerald-300">toda la firma</strong> de esta colección y
                                no {{ $sugeridas->count() === 1 ? 'está' : 'están' }} dentro. Es una
                                sugerencia, no una regla: decides tú.
                            </p>
                        </div>

                        <button type="button" @click="elegidas = @js($sugeridas->pluck('id'))"
                            class="shrink-0 rounded-lg border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-cyan-500 hover:text-cyan-300">
                            Todas
                        </button>

                        <button type="submit" x-show="elegidas.length > 0" x-cloak
                            class="shrink-0 rounded-xl bg-cyan-500 px-3 py-2 text-[11px] font-black text-slate-950 transition hover:bg-cyan-400">
                            Añadir <span x-text="elegidas.length"></span>
                        </button>
                    </div>

                    <template x-for="id in elegidas" :key="'a' + id">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>

                    <div class="grid grid-cols-3 gap-2 p-4 sm:grid-cols-4 lg:grid-cols-6">
                        @foreach ($sugeridas as $candidata)
                            <label class="group cursor-pointer overflow-hidden rounded-xl border bg-slate-950 transition"
                                :class="elegidas.includes({{ $candidata->id }})
                                    ? 'border-cyan-500 ring-1 ring-cyan-500'
                                    : 'border-slate-800 hover:border-slate-600'">

                                <input type="checkbox" class="sr-only"
                                    :checked="elegidas.includes({{ $candidata->id }})"
                                    @change="elegidas.includes({{ $candidata->id }})
                                        ? elegidas.splice(elegidas.indexOf({{ $candidata->id }}), 1)
                                        : elegidas.push({{ $candidata->id }})">

                                <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                    @if ($candidata->image_url)
                                        <img src="{{ $candidata->image_url }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                    @endif

                                    <span x-show="elegidas.includes({{ $candidata->id }})" x-cloak
                                        class="absolute inset-0 flex items-center justify-center bg-cyan-500/30 text-xl font-black text-white">✓</span>
                                </span>

                                <span class="block px-1.5 py-1">
                                    <span class="block truncate text-[10px] font-black text-white">{{ $candidata->name }}</span>
                                    <span class="block truncate text-[9px] text-slate-600">{{ $candidata->entityType?->name ?? 'Sin tipo' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </form>
            @endcan
        @endif


        {{-- ===================================================== --}}
        {{-- LO QUE HAY DENTRO --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    style="background-color: {{ $acento }}22; color: {{ $acento }}">
                    <x-omni-icon name="capas" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Lo que hay dentro</h2>
                    <p class="text-[10px] text-slate-500">
                        Meter una entidad aquí no la saca de ninguna otra colección ni la mueve de sitio.
                    </p>
                </div>

                @can('update', $collection)
                    <a href="{{ route('collections.edit', $collection) }}"
                        class="shrink-0 rounded-xl px-3 py-2 text-[11px] font-black transition"
                        style="background-color: {{ $acento }}22; color: {{ $acento }}">
                        + Añadir entidades
                    </a>
                @endcan
            </div>


            @if ($miembros->isEmpty())

                <div class="p-10 text-center">
                    <span class="inline-flex text-slate-700"><x-omni-icon name="capas" size="h-9 w-9" /></span>

                    <p class="mt-2 text-[13px] font-black text-white">Está vacía</p>

                    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                        Una colección vacía no molesta a nadie, pero tampoco hace nada. Añádele las entidades
                        que quieras agrupar: pueden estar en otras colecciones a la vez.
                    </p>

                    @can('update', $collection)
                        <a href="{{ route('collections.edit', $collection) }}"
                            class="mt-4 inline-block rounded-xl px-4 py-2 text-[11px] font-black"
                            style="background-color: {{ $acento }}; color: #020617">
                            Añadirle entidades
                        </a>
                    @endcan
                </div>

            @else

                {{-- ---------- FILTROS Y FORMA DE MIRAR ---------- --}}

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/40 px-4 py-3">

                    <label class="relative min-w-[160px] flex-1">
                        <span class="sr-only">Buscar dentro</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" x-model="buscar" placeholder="Buscar dentro de la colección…"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    @if ($tiposDeLosMiembros->count() > 1)
                        <select x-model="tipo"
                            class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="ALL">Cualquier tipo</option>
                            @foreach ($tiposDeLosMiembros as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <select x-model="orden"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="collection">Orden de la colección</option>
                        <option value="name">Nombre (A–Z)</option>
                        <option value="name_desc">Nombre (Z–A)</option>
                        <option value="type">Por tipo</option>
                    </select>

                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                        @foreach ([['gallery', 'galeria', 'Galería: solo las caras'], ['grid', 'cuadricula', 'Cuadrícula: con sus etiquetas'], ['list', 'menu', 'Lista: una línea por entidad'], ['table', 'controles', 'Tabla: para comparar'], ['bytype', 'capas', 'Por tipo: agrupadas']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="vista === '{{ $modo }}'"
                                :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

                    <span x-show="['gallery', 'grid'].includes(vista)" x-cloak
                        class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                        <button type="button" @click="tamano = Math.max(3, tamano - 1)" :disabled="tamano === 3"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                        </button>
                        <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="tamano"></span>
                        <button type="button" @click="tamano = Math.min(8, tamano + 1)" :disabled="tamano === 8"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                        </button>
                    </span>
                </div>


                {{-- ---------- GALERÍA ---------- --}}

                <div x-show="vista === 'gallery'" x-cloak class="grid gap-2 p-4" :class="columnas">
                    <template x-for="e in visibles" :key="'g' + e.id">
                        <a :href="e.url" :title="e.name"
                            class="group relative block overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-slate-600">

                            <span class="block aspect-[3/4] overflow-hidden">
                                <template x-if="e.image_url">
                                    <img :src="e.image_url" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                </template>
                                <template x-if="! e.image_url">
                                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                </template>
                            </span>

                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-2 pb-1.5 pt-6">
                                <span class="block truncate text-[10px] font-black text-white" x-text="e.name"></span>
                            </span>
                        </a>
                    </template>
                </div>


                {{-- ---------- CUADRÍCULA ---------- --}}

                <div x-show="vista === 'grid'" class="grid gap-2.5 p-4" :class="columnas">
                    <template x-for="e in visibles" :key="'c' + e.id">
                        <article class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:border-slate-600">

                            <a :href="e.url" class="relative block aspect-square overflow-hidden bg-slate-900">
                                <template x-if="e.image_url">
                                    <img :src="e.image_url" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                </template>
                                <template x-if="! e.image_url">
                                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                </template>
                            </a>

                            <div class="p-1.5">
                                <a :href="e.url" class="block truncate text-[10px] font-black text-white" x-text="e.name"></a>
                                <p class="truncate text-[9px] text-slate-600" x-text="e.type ?? 'Sin tipo'"></p>

                                <div class="mt-1 flex flex-wrap gap-0.5">
                                    <template x-for="c in e.otras.slice(0, 2)" :key="'o' + c.name">
                                        <span class="truncate rounded px-1 text-[8px] font-black"
                                            :style="'color: ' + c.color + '; background-color: ' + c.color + '22'"
                                            x-text="c.name"></span>
                                    </template>
                                </div>

                                @can('update', $collection)
                                    <form method="POST" :action="e.quitar" class="mt-1"
                                        onsubmit="return confirm('Se saca de esta colección. La entidad no se borra. ¿Seguro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-full rounded-lg border border-slate-800 py-1 text-[9px] font-black text-slate-500 transition hover:border-rose-500 hover:text-rose-300">
                                            Sacar de aquí
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    </template>
                </div>


                {{-- ---------- LISTA ---------- --}}

                <div x-show="vista === 'list'" x-cloak class="divide-y divide-slate-800/70">
                    <template x-for="e in visibles" :key="'l' + e.id">
                        <div class="flex items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50">

                            <a :href="e.url" class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                <template x-if="e.image_url">
                                    <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="! e.image_url">
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                </template>
                            </a>

                            <div class="min-w-0 flex-1">
                                <a :href="e.url" class="block truncate text-[12px] font-black text-white" x-text="e.name"></a>
                                <p class="truncate text-[10px] text-slate-500" x-text="e.type ?? 'Sin tipo'"></p>
                            </div>

                            <span class="hidden shrink-0 flex-wrap justify-end gap-1 sm:flex">
                                <template x-for="c in e.otras.slice(0, 3)" :key="'ol' + c.name">
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black"
                                        :style="'color: ' + c.color + '; background-color: ' + c.color + '22'"
                                        x-text="c.name"></span>
                                </template>
                            </span>

                            @can('update', $collection)
                                <form method="POST" :action="e.quitar" class="shrink-0"
                                    onsubmit="return confirm('Se saca de esta colección. La entidad no se borra. ¿Seguro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Sacarla de esta colección"
                                        class="rounded-lg px-2 py-1 text-[11px] font-black text-slate-600 transition hover:text-rose-300">✕</button>
                                </form>
                            @endcan
                        </div>
                    </template>
                </div>


                {{-- ---------- TABLA ---------- --}}

                <div x-show="vista === 'table'" x-cloak class="overflow-x-auto">
                    <table class="w-full min-w-[620px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Entidad</th>
                                <th class="px-3 py-2.5">Tipo</th>
                                <th class="px-3 py-2.5">También en</th>
                                <th class="px-3 py-2.5"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            <template x-for="e in visibles" :key="'t' + e.id">
                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2">
                                        <a :href="e.url" class="flex items-center gap-2">
                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                <template x-if="e.image_url">
                                                    <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                                </template>
                                            </span>
                                            <span class="truncate text-[12px] font-black text-white" x-text="e.name"></span>
                                        </a>
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-500" x-text="e.type ?? '—'"></td>

                                    <td class="px-3 py-2">
                                        <span class="flex flex-wrap gap-1">
                                            <template x-for="c in e.otras" :key="'ot' + c.name">
                                                <span class="rounded px-1.5 py-0.5 text-[9px] font-black"
                                                    :style="'color: ' + c.color + '; background-color: ' + c.color + '22'"
                                                    x-text="c.name"></span>
                                            </template>

                                            <template x-if="e.otras.length === 0">
                                                <span class="text-[10px] text-slate-700">solo en esta</span>
                                            </template>
                                        </span>
                                    </td>

                                    <td class="px-3 py-2 text-right">
                                        @can('update', $collection)
                                            <form method="POST" :action="e.quitar"
                                                onsubmit="return confirm('Se saca de esta colección. La entidad no se borra. ¿Seguro?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-[10px] font-black text-slate-600 transition hover:text-rose-300">Sacar</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>


                {{-- ---------- POR TIPO ---------- --}}

                <div x-show="vista === 'bytype'" x-cloak class="space-y-3 p-4">
                    <template x-for="grupo in porTipo" :key="'gt' + grupo.nombre">
                        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950">

                            <div class="flex items-center gap-2 border-b border-slate-800 px-3 py-2">
                                <span class="text-[11px] font-black text-white" x-text="grupo.nombre"></span>
                                <span class="rounded px-1.5 py-0.5 font-mono text-[10px] font-black"
                                    style="color: {{ $acento }}; background-color: {{ $acento }}22"
                                    x-text="grupo.items.length"></span>
                            </div>

                            <div class="flex gap-2 overflow-x-auto p-2.5">
                                <template x-for="e in grupo.items" :key="'gi' + e.id">
                                    <a :href="e.url" :title="e.name"
                                        class="group flex w-24 shrink-0 flex-col overflow-hidden rounded-lg border border-slate-800 bg-slate-900 transition hover:border-slate-600">
                                        <span class="block aspect-square overflow-hidden bg-slate-950">
                                            <template x-if="e.image_url">
                                                <img :src="e.image_url" alt="" loading="lazy"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                            </template>
                                            <template x-if="! e.image_url">
                                                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                            </template>
                                        </span>
                                        <span class="block truncate px-1 py-1 text-center text-[9px] font-black text-slate-300"
                                            x-text="e.name"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>


                <p x-show="visibles.length === 0" x-cloak class="p-8 text-center text-[11px] text-slate-600">
                    Ninguna de las de dentro encaja con lo que has filtrado.
                </p>

                <p class="border-t border-slate-800 px-4 py-2 text-[10px] text-slate-600">
                    <span x-text="visibles.length"></span> de {{ $miembros->count() }} entidades.
                    Sacar una de aquí no la borra: sigue en tu biblioteca y en las demás colecciones.
                </p>

            @endif

        </section>

    </div>


    <script>
        /*
         * La ficha de una colección: solo la parte de mirar lo que hay dentro.
         *
         * El orden «de la colección» es el que trae el servidor —el pivote
         * `sort_order`—, así que no se recalcula: se deja la lista tal cual.
         */
        function fichaDeColeccion(miembros) {

            return {

                miembros: miembros ?? [],

                buscar: '',
                tipo: 'ALL',
                orden: 'collection',

                vista: 'grid',
                tamano: 5,


                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.collectionShow.view') ?? '{}');
                        if (['gallery', 'grid', 'list', 'table', 'bytype'].includes(g.vista)) this.vista = g.vista;
                        if (g.tamano >= 3 && g.tamano <= 8) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.collectionShow.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
                    } catch (e) {}
                },


                get columnas() {
                    return {
                        3: 'grid-cols-2 sm:grid-cols-3',
                        4: 'grid-cols-2 sm:grid-cols-4',
                        5: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-5',
                        6: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-6',
                        7: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-7',
                        8: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                    }[this.tamano];
                },


                get visibles() {
                    const q = this.buscar.trim().toLowerCase();

                    let lista = this.miembros.filter((e) => {

                        if (q && ! e.name.toLowerCase().includes(q)) return false;

                        if (this.tipo !== 'ALL' && String(e.type_id) !== String(this.tipo)) return false;

                        return true;
                    });

                    if (this.orden === 'name') {
                        lista = [...lista].sort((a, b) => a.name.localeCompare(b.name));
                    } else if (this.orden === 'name_desc') {
                        lista = [...lista].sort((a, b) => b.name.localeCompare(a.name));
                    } else if (this.orden === 'type') {
                        lista = [...lista].sort((a, b) =>
                            (a.type ?? '').localeCompare(b.type ?? '') || a.name.localeCompare(b.name));
                    }

                    return lista;
                },


                get porTipo() {
                    const mapa = {};

                    this.visibles.forEach((e) => {
                        const clave = e.type ?? 'Sin tipo';

                        if (! mapa[clave]) mapa[clave] = [];

                        mapa[clave].push(e);
                    });

                    return Object.entries(mapa)
                        .map(([nombre, items]) => ({ nombre, items }))
                        .sort((a, b) => b.items.length - a.items.length);
                },
            };
        }
    </script>

</x-app-layout>

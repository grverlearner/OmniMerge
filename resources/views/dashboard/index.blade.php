@php
    /*
     * El panel.
     *
     * Es lo primero que se ve al entrar, así que su trabajo no es enseñar todo
     * lo que hay sino contestar tres cosas en este orden:
     *
     *   1. ¿de qué va mi biblioteca?   → las caras, no un número
     *   2. ¿qué estaba haciendo?       → lo último tocado, con un clic para seguir
     *   3. ¿qué le falta?              → lo que está a medias, dicho y enlazado
     *
     * Y a mano, siempre: el buscador —que ya existía en el servidor y no estaba
     * enchufado a nada— y los atajos a lo que se hace todos los días.
     */

    $atajos = [
        ['Nueva entidad', 'chispa', route('entities.create'), '#a78bfa', 'Un personaje, un país, lo que sea'],
        ['Nueva colección', 'capas', route('collections.create'), '#22d3ee', 'Agrupar entidades que van juntas'],
        ['Nuevo atributo', 'controles', route('attributes.create'), '#34d399', 'Un dato con el que describirlas'],
        ['Nuevo valor', 'cuadricula', route('attribute-options.create'), '#fbbf24', 'Una opción para un catálogo'],
        ['Taller de versiones', 'orbita', route('versions.index'), '#f472b6', 'Variantes de una misma entidad'],
        ['Comunidad', 'globo', route('community.index'), '#60a5fa', 'Copiar lo que otros han hecho'],
    ];

    /* Solo lo que de verdad está pendiente; lo demás no es un aviso. */
    $pendientes = $healthItems->where('count', '>', 0)->values();
    $resueltos = $healthItems->where('count', 0)->count();

    $bloques = [
        ['entities', 'Entidades', $statistics['entities'], '#a78bfa', route('entities.index')],
        ['collections', 'Colecciones', $statistics['collections'], '#22d3ee', route('collections.index')],
        ['attributes', 'Atributos', $statistics['attributes'], '#34d399', route('attributes.index')],
        ['catalogs', 'Valores', $statistics['catalog_options'], '#fbbf24', route('attribute-options.index')],
        ['types', 'Tipos', $statistics['entity_types'], '#f472b6', route('entity-types.index')],
        ['groups', 'Grupos', $statistics['attribute_groups'], '#60a5fa', route('attribute-groups.index')],
    ];

    $vacia = $statistics['resources_total'] === 0;
@endphp

<x-app-layout title="Panel" surface="dark">

    <x-slot name="header">Panel</x-slot>

    <div x-data="panelDeBiblioteca({
        rutaBusqueda: @js(route('dashboard.search')),
    })" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- DE QUÉ VA MI BIBLIOTECA --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            @if ($mosaico->isNotEmpty())
                <div class="relative">
                    <div class="grid grid-cols-6 gap-px bg-slate-800 sm:grid-cols-12 lg:grid-cols-[repeat(24,minmax(0,1fr))]">
                        @foreach ($mosaico as $pieza)
                            <a href="{{ route('entities.show', $pieza) }}" title="{{ $pieza->name }}"
                                class="group block aspect-square overflow-hidden bg-slate-950">
                                <img src="{{ $pieza->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover opacity-50 transition duration-500 group-hover:scale-110 group-hover:opacity-100">
                            </a>
                        @endforeach
                    </div>

                    <span class="pointer-events-none absolute inset-x-0 bottom-0 h-14 bg-gradient-to-t from-slate-900 to-transparent"></span>
                </div>
            @endif

            <div class="flex flex-wrap items-end gap-4 p-4 {{ $mosaico->isNotEmpty() ? 'relative -mt-6' : '' }}">

                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">Tu biblioteca</p>

                    <h1 class="mt-0.5 text-2xl font-black tracking-tight text-white">
                        Hola, {{ $user->name }}
                    </h1>

                    <p class="mt-0.5 text-[11px] text-slate-500">
                        @if ($vacia)
                            Todavía está vacía. Lo de abajo son los cuatro sitios por donde se empieza.
                        @else
                            <strong class="text-slate-300">{{ $statistics['resources_total'] }}</strong>
                            cosas creadas entre entidades, tipos, atributos, valores, grupos y colecciones.
                        @endif
                    </p>
                </div>


                {{-- El buscador, que ya existía en el servidor y no estaba enchufado --}}
                <div class="relative w-full sm:w-80" @click.outside="cerrarBusqueda()">

                    <label class="relative block">
                        <span class="sr-only">Buscar en la biblioteca</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-4 w-4" />
                        </span>

                        <input type="search" x-model="consulta" @input.debounce.250ms="buscar()"
                            @keydown.escape="cerrarBusqueda()"
                            @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)"
                            @keydown.enter.prevent="abrirElegido()"
                            x-ref="buscador"
                            placeholder="Buscar entidades, atributos, valores…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 pr-12 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">

                        <span class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-600 sm:block">
                            /
                        </span>
                    </label>

                    <div x-show="abierto" x-cloak
                        class="absolute inset-x-0 top-full z-30 mt-1 max-h-80 overflow-y-auto rounded-xl border border-slate-800 bg-slate-950 shadow-2xl">

                        <template x-if="cargando">
                            <p class="px-3 py-4 text-center text-[11px] text-slate-600">Buscando…</p>
                        </template>

                        <template x-if="! cargando && resultados.length === 0">
                            <p class="px-3 py-4 text-center text-[11px] text-slate-600">
                                Nada con ese nombre en tu biblioteca.
                            </p>
                        </template>

                        <template x-for="(resultado, indice) in resultados" :key="resultado.kind + resultado.id">
                            <a :href="resultado.url"
                                :class="indice === elegido ? 'bg-violet-500/15' : ''"
                                @mouseenter="elegido = indice"
                                class="flex items-center gap-2 border-b border-slate-800/70 px-2 py-1.5 transition last:border-0">

                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                    <template x-if="resultado.image_url">
                                        <img :src="resultado.image_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="! resultado.image_url">
                                        <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-600"
                                            x-text="resultado.icon"></span>
                                    </template>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[11px] font-black text-white" x-text="resultado.title"></span>
                                    <span class="block truncate text-[9px] text-slate-500" x-text="resultado.subtitle"></span>
                                </span>

                                <span class="shrink-0 rounded border border-slate-800 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-500"
                                    x-text="resultado.kind_label"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>


            {{-- Las cifras, cada una a su sitio --}}
            <div class="grid grid-cols-2 gap-px border-t border-slate-800 bg-slate-800 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($bloques as [$clave, $etiqueta, $cuantos, $tono, $ruta])
                    <a href="{{ $ruta }}" class="bg-slate-900/50 px-3 py-2.5 transition hover:bg-slate-950">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $cuantos > 0 ? $tono : '#475569' }}">{{ $cuantos }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </a>
                @endforeach
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- ATAJOS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($atajos as [$etiqueta, $icono, $ruta, $tono, $ayuda])
                <a href="{{ $ruta }}" title="{{ $ayuda }}"
                    class="group flex flex-col gap-1.5 rounded-2xl border bg-slate-900/50 p-3 transition duration-300 hover:-translate-y-0.5"
                    style="border-color: {{ $tono }}33">

                    <span class="flex h-8 w-8 items-center justify-center rounded-xl transition group-hover:scale-110"
                        style="background-color: {{ $tono }}22; color: {{ $tono }}">
                        <x-omni-icon :name="$icono" size="h-4 w-4" />
                    </span>

                    <span class="block text-[11px] font-black leading-tight text-white">{{ $etiqueta }}</span>
                    <span class="block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                </a>
            @endforeach
        </section>


        @if ($vacia)

            {{-- ================================================= --}}
            {{-- POR DÓNDE SE EMPIEZA --}}
            {{-- ================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5 p-5">

                <h2 class="text-[14px] font-black text-white">Por dónde se empieza</h2>

                <p class="mt-1 max-w-2xl text-[11px] leading-relaxed text-slate-400">
                    OmniMerge se monta de dentro afuera. No hace falta seguir el orden, pero este es el que
                    menos vueltas da.
                </p>

                <div class="mt-4 grid gap-3 lg:grid-cols-4">
                    @foreach ([['1', 'Un tipo', 'Personaje, país, arma… la categoría de lo que vas a crear.', route('entity-types.create'), '#f472b6'], ['2', 'Unos atributos', 'Los datos con los que describirlas: clan, edad, elemento.', route('attributes.create'), '#34d399'], ['3', 'Tus entidades', 'Ya con su tipo y sus atributos rellenados.', route('entities.create'), '#a78bfa'], ['4', 'Colecciones', 'Para agrupar las que van juntas.', route('collections.create'), '#22d3ee']] as [$paso, $titulo, $texto, $ruta, $tono])
                        <a href="{{ $ruta }}"
                            class="group rounded-2xl border bg-slate-950 p-3 transition hover:-translate-y-0.5"
                            style="border-color: {{ $tono }}33">

                            <span class="flex h-7 w-7 items-center justify-center rounded-lg font-mono text-[12px] font-black"
                                style="background-color: {{ $tono }}22; color: {{ $tono }}">{{ $paso }}</span>

                            <p class="mt-2 text-[12px] font-black text-white">{{ $titulo }}</p>
                            <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">{{ $texto }}</p>
                        </a>
                    @endforeach
                </div>

                <p class="mt-4 border-t border-violet-500/20 pt-3 text-[10px] text-slate-500">
                    ¿Prefieres no empezar de cero? En la
                    <a href="{{ route('community.index') }}" class="font-black text-violet-300 underline">comunidad</a>
                    puedes copiar entidades, atributos y catálogos enteros de otros a tu biblioteca.
                </p>
            </section>

        @else

            {{-- ================================================= --}}
            {{-- QUÉ ESTABA HACIENDO --}}
            {{-- ================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">

                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <x-omni-icon name="historial" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Sigue donde lo dejaste</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Lo último que has tocado, de cualquier clase, ordenado por cuándo lo tocaste.
                        </p>
                    </div>

                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ([['gallery', 'galeria', 'Galería: solo las caras'], ['grid', 'cuadricula', 'Cuadrícula: con su tipo'], ['list', 'menu', 'Lista: una línea cada uno'], ['table', 'controles', 'Tabla: con sus fechas']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="vista === '{{ $modo }}'"
                                :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

                    <span x-show="['gallery', 'grid'].includes(vista)" x-cloak
                        class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        <button type="button" @click="tamano = Math.max(4, tamano - 1)" :disabled="tamano === 4"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                        </button>
                        <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="tamano"></span>
                        <button type="button" @click="tamano = Math.min(9, tamano + 1)" :disabled="tamano === 9"
                            class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                            <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                        </button>
                    </span>
                </div>

                @if ($workspaceItems->isEmpty())
                    <p class="px-4 py-8 text-center text-[11px] text-slate-600">
                        Todavía no has tocado nada.
                    </p>
                @else

                    {{-- GALERÍA --}}
                    <div x-show="vista === 'gallery'" x-cloak class="grid gap-2 p-3" :class="columnas">
                        @foreach ($workspaceItems as $item)
                            <a href="{{ $item['url'] }}" title="{{ $item['name'] }}"
                                class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                                style="border-color: {{ $item['color'] }}33">
                                <span class="block aspect-square overflow-hidden bg-slate-900">
                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-xl"
                                            style="color: {{ $item['color'] }}66">{{ $item['icon'] }}</span>
                                    @endif
                                </span>
                                <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                                    {{ $item['name'] }}
                                </span>
                                <span class="block truncate px-1.5 pb-1 text-center text-[9px] text-slate-600">
                                    {{ $item['type'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>

                    {{-- CUADRÍCULA --}}
                    <div x-show="vista === 'grid'" class="grid gap-2.5 p-3" :class="columnas">
                        @foreach ($workspaceItems as $item)
                            <article class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                                style="border-color: {{ $item['color'] }}33">

                                <a href="{{ $item['url'] }}" class="block aspect-square overflow-hidden bg-slate-900">
                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-2xl"
                                            style="color: {{ $item['color'] }}66">{{ $item['icon'] }}</span>
                                    @endif
                                </a>

                                <div class="p-1.5">
                                    <a href="{{ $item['url'] }}"
                                        class="block truncate text-[11px] font-black text-white">{{ $item['name'] }}</a>
                                    <p class="truncate text-[9px]" style="color: {{ $item['color'] }}">{{ $item['type'] }}</p>
                                    <p class="truncate text-[9px] text-slate-600">{{ $item['subtitle'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- LISTA --}}
                    <div x-show="vista === 'list'" x-cloak class="divide-y divide-slate-800/70">
                        @foreach ($workspaceItems as $item)
                            <a href="{{ $item['url'] }}"
                                class="flex items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50">

                                <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                                    style="border-color: {{ $item['color'] }}40">
                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center"
                                            style="color: {{ $item['color'] }}">{{ $item['icon'] }}</span>
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[12px] font-black text-white">{{ $item['name'] }}</span>
                                    <span class="block truncate text-[10px] text-slate-500">{{ $item['subtitle'] }}</span>
                                </span>

                                <span class="shrink-0 rounded-lg border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                    style="border-color: {{ $item['color'] }}40; color: {{ $item['color'] }}">
                                    {{ $item['type'] }}
                                </span>

                                <span class="hidden shrink-0 font-mono text-[9px] text-slate-600 sm:block">
                                    {{ $item['updated_at']?->diffForHumans(null, true) }}
                                </span>
                            </a>
                        @endforeach
                    </div>

                    {{-- TABLA --}}
                    <div x-show="vista === 'table'" x-cloak class="overflow-x-auto">
                        <table class="w-full min-w-[600px]">
                            <thead class="border-b border-slate-800 text-left">
                                <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    <th class="px-4 py-2.5">Qué</th>
                                    <th class="px-3 py-2.5">Clase</th>
                                    <th class="px-3 py-2.5">Código</th>
                                    <th class="px-3 py-2.5">Creado</th>
                                    <th class="px-3 py-2.5">Tocado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/70">
                                @foreach ($workspaceItems as $item)
                                    <tr class="transition hover:bg-slate-950/50">
                                        <td class="px-4 py-2">
                                            <a href="{{ $item['url'] }}" class="flex items-center gap-2">
                                                <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                                                    style="border-color: {{ $item['color'] }}40">
                                                    @if ($item['image_url'])
                                                        <img src="{{ $item['image_url'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                    @else
                                                        <span class="flex h-full w-full items-center justify-center text-[10px]"
                                                            style="color: {{ $item['color'] }}">{{ $item['icon'] }}</span>
                                                    @endif
                                                </span>
                                                <span class="truncate text-[12px] font-black text-white">{{ $item['name'] }}</span>
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-[11px]" style="color: {{ $item['color'] }}">{{ $item['type'] }}</td>
                                        <td class="px-3 py-2 font-mono text-[10px] text-slate-600">{{ $item['code'] }}</td>
                                        <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                            {{ $item['created_at']?->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                            {{ $item['updated_at']?->diffForHumans(null, true) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>


            {{-- ================================================= --}}
            {{-- LO QUE FALTA, Y DE QUÉ ESTÁ HECHA --}}
            {{-- ================================================= --}}

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">

                <div class="space-y-4">

                    {{-- Lo que está a medias --}}
                    <section class="overflow-hidden rounded-2xl border {{ $pendientes->isEmpty() ? 'border-emerald-500/25 bg-emerald-500/5' : 'border-amber-500/25 bg-amber-500/5' }}">

                        <div class="flex flex-wrap items-center gap-3 border-b {{ $pendientes->isEmpty() ? 'border-emerald-500/20' : 'border-amber-500/20' }} px-4 py-2.5">

                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $pendientes->isEmpty() ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }}">
                                <x-omni-icon name="matraz" size="h-4 w-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <h2 class="text-[13px] font-black text-white">Lo que está a medias</h2>
                                <p class="text-[10px] leading-relaxed {{ $pendientes->isEmpty() ? 'text-emerald-200/60' : 'text-amber-200/60' }}">
                                    @if ($pendientes->isEmpty())
                                        Nada pendiente: las {{ $healthItems->count() }} comprobaciones salen limpias.
                                    @else
                                        {{ $pendientes->count() }} de {{ $healthItems->count() }} comprobaciones
                                        encuentran algo. No es un error —una biblioteca viva siempre tiene
                                        cabos sueltos— pero aquí están, y cada uno lleva a su sitio.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($pendientes->isNotEmpty())
                            <div class="grid gap-2 p-3 sm:grid-cols-2">
                                @foreach ($pendientes as $aviso)
                                    <a href="{{ $aviso['url'] }}"
                                        class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2.5 transition hover:border-amber-500/50">

                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 font-mono text-[13px] font-black text-amber-300">
                                            {{ $aviso['count'] }}
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-white">{{ $aviso['label'] }}</span>
                                            <span class="block truncate text-[9px] leading-3 text-slate-500">{{ $aviso['description'] }}</span>
                                        </span>

                                        <span class="shrink-0 text-slate-700">›</span>
                                    </a>
                                @endforeach
                            </div>

                            @if ($resueltos > 0)
                                <p class="border-t border-amber-500/20 px-4 py-2 text-[10px] text-slate-500">
                                    Las otras {{ $resueltos }} comprobaciones salen limpias.
                                </p>
                            @endif
                        @endif
                    </section>


                    {{-- Lo que más te han copiado --}}
                    @if ($loMasCopiado->isNotEmpty())
                        <section class="overflow-hidden rounded-2xl border border-fuchsia-500/25 bg-fuchsia-500/5">

                            <div class="flex flex-wrap items-center gap-3 border-b border-fuchsia-500/20 px-4 py-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
                                    <x-omni-icon name="medalla" size="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-[13px] font-black text-white">Lo que más te han copiado</h2>
                                    <p class="text-[10px] leading-relaxed text-fuchsia-200/60">
                                        De lo que has publicado, esto es lo que se ha llevado la gente.
                                    </p>
                                </div>

                                <a href="{{ route('profiles.show', $user->username) }}"
                                    class="shrink-0 rounded-xl border border-fuchsia-500/30 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:text-white">
                                    Tu perfil →
                                </a>
                            </div>

                            <div class="grid grid-cols-3 gap-2 p-3 sm:grid-cols-6">
                                @foreach ($loMasCopiado as $pieza)
                                    <a href="{{ route('entities.show', $pieza) }}" title="{{ $pieza->name }}"
                                        class="group relative overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5">
                                        <span class="block aspect-square overflow-hidden bg-slate-900">
                                            @if ($pieza->image_url)
                                                <img src="{{ $pieza->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-xl text-slate-800">◍</span>
                                            @endif
                                        </span>

                                        <span class="absolute right-1 top-1 rounded bg-fuchsia-500 px-1 font-mono text-[9px] font-black text-fuchsia-950">
                                            ↺{{ $pieza->clones_count }}
                                        </span>

                                        <span class="block truncate px-1 py-1 text-center text-[9px] font-black text-slate-400">
                                            {{ $pieza->name }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif


                    {{-- Lo último de cada clase --}}
                    <section x-data="{ clase: 'entities' }"
                        class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                        <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-800 px-3 py-2">
                            <h2 class="mr-auto text-[13px] font-black text-white">Lo último de cada clase</h2>

                            @foreach ([['entities', 'Entidades', '#a78bfa'], ['attributes', 'Atributos', '#34d399'], ['options', 'Valores', '#fbbf24'], ['collections', 'Colecciones', '#22d3ee'], ['types', 'Tipos', '#f472b6'], ['groups', 'Grupos', '#60a5fa']] as [$clave, $etiqueta, $tono])
                                <button type="button" @click="clase = '{{ $clave }}'"
                                    :class="clase === '{{ $clave }}' ? 'text-white' : 'text-slate-500 hover:text-slate-300'"
                                    :style="clase === '{{ $clave }}' ? 'border-color: {{ $tono }}; background-color: {{ $tono }}22' : ''"
                                    class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black transition">
                                    {{ $etiqueta }}
                                </button>
                            @endforeach
                        </div>

                        @foreach ([['entities', $recentEntities, '#a78bfa', 'entities.show', '✦'], ['attributes', $recentAttributes, '#34d399', 'attributes.show', '☷'], ['options', $recentOptions, '#fbbf24', 'attribute-options.show', '◇'], ['collections', $recentCollections, '#22d3ee', 'collections.show', '❒'], ['types', $recentTypes, '#f472b6', 'entity-types.show', '◈'], ['groups', $recentGroups, '#60a5fa', 'attribute-groups.show', '▥']] as [$clave, $lista, $tono, $ruta, $respaldo])

                            <div x-show="clase === '{{ $clave }}'" @if ($clave !== 'entities') x-cloak @endif>
                                @if ($lista->isEmpty())
                                    <p class="px-4 py-8 text-center text-[11px] text-slate-600">
                                        Todavía no has creado ninguno.
                                    </p>
                                @else
                                    <div class="grid grid-cols-3 gap-2 p-3 sm:grid-cols-5 lg:grid-cols-6">
                                        @foreach ($lista as $cosa)
                                            <a href="{{ route($ruta, $cosa) }}" title="{{ $cosa->name }}"
                                                class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5">
                                                <span class="block aspect-square overflow-hidden bg-slate-900">
                                                    @if ($cosa->image_url ?? null)
                                                        <img src="{{ $cosa->image_url }}" alt="" loading="lazy"
                                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                                    @else
                                                        <span class="flex h-full w-full items-center justify-center text-xl"
                                                            style="color: {{ $tono }}55">{{ $cosa->icon ?: $respaldo }}</span>
                                                    @endif
                                                </span>
                                                <span class="block truncate px-1 py-1 text-center text-[9px] font-black text-slate-400">
                                                    {{ $cosa->name }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                </div>


                {{-- ---------- LA COLUMNA DE AL LADO ---------- --}}

                <aside class="space-y-3">

                    {{-- Hasta dónde llega --}}
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                        <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Hasta dónde llega
                        </h2>

                        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                            Si tu biblioteca vive sola o está conectada con la comunidad.
                        </p>

                        <div class="mt-2.5 grid grid-cols-3 gap-2">
                            @foreach ([['Públicas', $alcance['publicas'], '#60a5fa', 'Entidades que los demás pueden ver'], ['Te copiaron', $alcance['copiado'], '#e879f9', 'Veces que alguien se llevó algo tuyo'], ['Te trajiste', $alcance['traido'], '#fbbf24', 'Cosas que copiaste de otros']] as [$etiqueta, $numero, $tono, $ayuda])
                                <div class="rounded-xl border border-slate-800 bg-slate-950 px-2 py-1.5" title="{{ $ayuda }}">
                                    <span class="block font-mono text-[15px] font-black"
                                        style="color: {{ $numero > 0 ? $tono : '#475569' }}">{{ $numero }}</span>
                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if ($alcance['publicas'] === 0)
                            <p class="mt-2 border-t border-slate-800 pt-2 text-[10px] leading-4 text-slate-500">
                                No has publicado nada todavía. Nada se comparte solo: se marca como público
                                en la ficha de cada entidad.
                            </p>
                        @endif

                        <a href="{{ route('community.creators.show', $user->username) }}"
                            class="mt-2 block rounded-xl border border-slate-800 px-3 py-1.5 text-center text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                            Cómo te ven los demás →
                        </a>
                    </section>


                    {{-- De qué está hecha --}}
                    @if ($typeDistribution->isNotEmpty())
                        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                            <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                                De qué está hecha
                            </h2>

                            <div class="mt-2 space-y-1.5">
                                @foreach ($typeDistribution as $fila)
                                    <a href="{{ $fila['url'] }}" class="block">
                                        <div class="flex items-center gap-1.5 text-[10px]">
                                            <span style="color: {{ $fila['color'] }}">{{ $fila['icon'] }}</span>
                                            <span class="min-w-0 flex-1 truncate font-bold text-slate-300">{{ $fila['name'] }}</span>
                                            <span class="font-mono font-black" style="color: {{ $fila['color'] }}">{{ $fila['count'] }}</span>
                                        </div>
                                        <div class="mt-0.5 h-1.5 overflow-hidden rounded-full bg-slate-950">
                                            <div class="h-full rounded-full"
                                                style="width: {{ max((int) round(($fila['count'] / $distributionMax) * 100), 3) }}%; background-color: {{ $fila['color'] }}"></div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif


                    {{-- Los catálogos más grandes --}}
                    @if ($topCatalogs->isNotEmpty())
                        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                            <div class="border-b border-slate-800 px-3.5 py-2">
                                <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                                    Tus catálogos
                                </h2>
                            </div>

                            <div class="divide-y divide-slate-800/70">
                                @foreach ($topCatalogs as $catalogo)
                                    @php $tono = $catalogo->color ?: '#6366f1'; @endphp

                                    <a href="{{ route('attributes.show', $catalogo) }}"
                                        class="flex items-center gap-2 px-3 py-1.5 transition hover:bg-slate-950/50">

                                        @include('attributes.partials.cara', [
                                            'cosa' => $catalogo,
                                            'tamano' => 'h-7 w-7',
                                            'respaldo' => '◫',
                                            'tono' => $tono,
                                        ])

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-slate-300">{{ $catalogo->name }}</span>
                                            <span class="block h-1 overflow-hidden rounded-full bg-slate-950">
                                                <span class="block h-full rounded-full"
                                                    style="width: {{ max((int) round(($catalogo->active_options_count / $catalogMax) * 100), 3) }}%; background-color: {{ $tono }}"></span>
                                            </span>
                                        </span>

                                        <span class="shrink-0 font-mono text-[10px] font-black"
                                            style="color: {{ $catalogo->active_options_count > 0 ? $tono : '#f43f5e' }}">
                                            {{ $catalogo->active_options_count }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif


                    {{-- Los otros paneles --}}
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                        <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Los otros paneles
                        </h2>

                        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                            Este panel es solo la biblioteca. Los torneos y los universos tienen el suyo.
                        </p>

                        <div class="mt-2 space-y-1.5">
                            @foreach ([['Torneos', 'trofeo', route('tournaments.dashboard'), '#fbbf24'], ['Universos', 'orbita', route('universes.dashboard'), '#22d3ee'], ['Comunidad', 'globo', route('community.index'), '#a78bfa']] as [$etiqueta, $icono, $ruta, $tono])
                                <a href="{{ $ruta }}"
                                    class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-2 transition hover:-translate-y-0.5"
                                    style="border-color: {{ $tono }}22">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg"
                                        style="background-color: {{ $tono }}22; color: {{ $tono }}">
                                        <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-300">{{ $etiqueta }}</span>
                                    <span class="shrink-0 text-slate-700">›</span>
                                </a>
                            @endforeach
                        </div>
                    </section>

                </aside>
            </div>

        @endif

    </div>


    <script>
        function panelDeBiblioteca(config) {

            return {

                vista: 'grid',
                tamano: 6,

                /* El buscador */
                consulta: '',
                resultados: [],
                abierto: false,
                cargando: false,
                elegido: 0,
                peticion: 0,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.dashboard.view') ?? '{}');
                        if (['gallery', 'grid', 'list', 'table'].includes(g.vista)) this.vista = g.vista;
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());

                    /*
                     * La barra «/» enfoca el buscador, como en cualquier sitio
                     * donde uno busca a menudo. Se ignora si ya se está
                     * escribiendo en otro campo.
                     */
                    window.addEventListener('keydown', (evento) => {
                        if (evento.key !== '/' || evento.metaKey || evento.ctrlKey) return;

                        const activo = document.activeElement;
                        const escribiendo = activo && (
                            activo.tagName === 'INPUT'
                            || activo.tagName === 'TEXTAREA'
                            || activo.isContentEditable
                        );

                        if (escribiendo) return;

                        evento.preventDefault();
                        this.$refs.buscador?.focus();
                    });
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.dashboard.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
                    } catch (e) {}
                },

                get columnas() {
                    return {
                        4: 'grid-cols-2 sm:grid-cols-4',
                        5: 'grid-cols-2 sm:grid-cols-5',
                        6: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                        7: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                        8: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                        9: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-9',
                    }[this.tamano];
                },

                async buscar() {
                    const texto = this.consulta.trim();

                    if (texto.length < 2) {
                        this.resultados = [];
                        this.abierto = false;
                        return;
                    }

                    /*
                     * Cada búsqueda lleva su número. Si vuelve una respuesta de
                     * una búsqueda anterior a la última tecleada, se descarta:
                     * de lo contrario la lista parpadea con resultados viejos.
                     */
                    const mia = ++this.peticion;

                    this.abierto = true;
                    this.cargando = true;

                    try {
                        const respuesta = await fetch(
                            config.rutaBusqueda + '?q=' + encodeURIComponent(texto),
                            { headers: { 'Accept': 'application/json' } }
                        );

                        const datos = await respuesta.json();

                        if (mia !== this.peticion) return;

                        this.resultados = datos.results ?? [];
                        this.elegido = 0;
                    } catch (e) {
                        if (mia === this.peticion) this.resultados = [];
                    } finally {
                        if (mia === this.peticion) this.cargando = false;
                    }
                },

                cerrarBusqueda() {
                    this.abierto = false;
                },

                mover(paso) {
                    if (this.resultados.length === 0) return;

                    this.elegido =
                        (this.elegido + paso + this.resultados.length) % this.resultados.length;
                },

                abrirElegido() {
                    const elegido = this.resultados[this.elegido];

                    if (elegido) window.location.href = elegido.url;
                },
            };
        }
    </script>

</x-app-layout>

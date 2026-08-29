@php
    /*
     * EL PANEL DE COMPETIDORES
     *
     * Cinco formas de mirar lo mismo, porque no siempre se busca lo mismo:
     *
     *   Cuadrícula  solo la cara, muchos de golpe
     *   Galería     la cara y sus atributos
     *   Lista       una línea con su récord y su catálogo
     *   Tabla       columnas comparables: victorias, títulos, trofeos
     *   Ficha       todo lo que hay de cada uno, incluidas sus versiones
     *
     * Y filtros de verdad: por atributo y valor de catálogo, no solo por
     * nombre. Buscar «sharingan» tiene que encontrar a quien lo lleva.
     *
     * Todo se recorta en la propia pantalla. El servidor manda la lista
     * entera y ya viene filtrada si el enlace traía filtros, así que
     * compartir una vista filtrada funciona aunque el JavaScript tarde.
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Competidores</x-slot>

    <div x-data="entityBrowser({
            entities: @js($entities),
            catalog: @js($catalog),
            types: @js($types),
            filters: @js($filters),
        })">


        {{-- ============ LA CABECERA ============ --}}

        <div class="mb-3 flex flex-wrap items-center gap-2">
            <div>
                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-emerald-300">
                    {{ $universe->name }}
                </p>
                <h1 class="text-lg font-black text-slate-100">
                    Competidores
                    <span class="font-mono text-[13px] text-slate-600" x-text="shown.length"></span>
                    <span class="font-mono text-[11px] text-slate-700">/ {{ $counts['total'] }}</span>
                </h1>
            </div>

            <a href="{{ route('universes.entities.create', $universe) }}"
                class="ml-auto rounded-lg bg-emerald-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-emerald-400">
                + traer de la Biblioteca
            </a>
        </div>


        {{-- ============ LO QUE HAY, DE UN VISTAZO ============ --}}

        {{--
            Cada cifra es también un filtro. Un número que solo se mira es
            un número desperdiciado: si dice que 2 tienen trofeos, pulsarlo
            debería enseñar esos 2.
        --}}

        <div class="mb-3 grid grid-cols-3 gap-1.5 sm:grid-cols-6">
            @foreach ([
                ['', '', 'Todos', $counts['total'], 'text-slate-300'],
                ['status', 'ACTIVE', 'Activos', $counts['active'], 'text-emerald-300'],
                ['status', 'RETIRED', 'Retirados', $counts['retired'], 'text-slate-400'],
                ['only', 'TROPHIES', 'Con trofeos', $counts['with_trophies'], 'text-amber-300'],
                ['only', 'VERSIONS', 'Con versiones', $counts['with_versions'], 'text-violet-300'],
                ['only', 'NEVER', 'Sin jugar', $counts['never_played'], 'text-rose-300'],
            ] as [$campo, $valor, $label, $cifra, $tono])
                <button type="button"
                    @click="{{ $campo === '' ? 'clearFilters()' : ($campo . " = " . $campo . " === '" . $valor . "' ? '' : '" . $valor . "'") }}"
                    class="rounded-xl border px-2 py-1.5 text-left transition"
                    :class="{{ $campo === '' ? 'activeFilters === 0' : ($campo . " === '" . $valor . "'") }}
                        ? 'border-slate-600 bg-slate-800/60'
                        : 'border-slate-800 bg-slate-900/50 hover:border-slate-700'">

                    <span class="block font-mono text-[16px] font-black leading-none {{ $tono }}">{{ $cifra }}</span>
                    <span class="block truncate text-[9px] uppercase tracking-wider text-slate-600">{{ $label }}</span>
                </button>
            @endforeach
        </div>


        {{-- ============ LA BARRA ============ --}}

        <div class="mb-3 rounded-2xl border border-slate-800 bg-slate-900/50 p-2">

            <div class="flex flex-wrap items-center gap-1.5">

                <input type="search" x-model="search"
                    placeholder="buscar por nombre, código, tipo, atributo o valor…"
                    class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-3 py-1.5 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">

                {{-- Ordenar --}}

                <select x-model="sort"
                    class="rounded-lg border-slate-700 bg-slate-950 px-2 py-1.5 text-[11px] text-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach ($sorts as $clave => $label)
                        <option value="{{ $clave }}">{{ $label }}</option>
                    @endforeach
                </select>

                {{-- Cómo se mira --}}

                <div class="flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                    @foreach ([
                        'GRID' => ['▦', 'Cuadrícula: solo la cara'],
                        'GALLERY' => ['▤', 'Galería: la cara y sus atributos'],
                        'LIST' => ['☰', 'Lista: una línea con todo'],
                        'TABLE' => ['⊞', 'Tabla: columnas comparables'],
                        'CARD' => ['❑', 'Ficha: todo, versiones incluidas'],
                    ] as $modo => [$icono, $ayuda])
                        <button type="button" @click="setView('{{ $modo }}')" title="{{ $ayuda }}"
                            class="rounded-md px-2 py-1 text-[11px] transition"
                            :class="view === '{{ $modo }}'
                                ? 'bg-emerald-500 text-slate-950'
                                : 'text-slate-500 hover:text-slate-200'">{{ $icono }}</button>
                    @endforeach
                </div>

                {{-- De qué tamaño --}}

                <div class="flex items-center gap-0.5 rounded-lg border border-slate-800 bg-slate-950"
                    x-show="view !== 'TABLE' && view !== 'LIST'">

                    <button type="button" @click="setSize(size + 1)" title="Más pequeños"
                        class="px-2 py-1 text-[13px] leading-none text-slate-500 transition hover:text-slate-100">−</button>

                    <span class="font-mono text-[9px] text-slate-600" x-text="size"></span>

                    <button type="button" @click="setSize(size - 1)" title="Más grandes"
                        class="px-2 py-1 text-[13px] leading-none text-slate-500 transition hover:text-slate-100">+</button>
                </div>

                <button type="button" @click="openFilters = !openFilters"
                    class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition"
                    :class="activeFilters
                        ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-300'
                        : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-600'">
                    filtros
                    <span x-show="activeFilters" class="ml-0.5 font-mono" x-text="activeFilters"></span>
                </button>
            </div>


            {{-- Lo que hay puesto, para poder quitarlo de uno en uno --}}

            <div class="mt-1.5 flex flex-wrap items-center gap-1" x-show="chips.length" x-cloak>
                <template x-for="(chip, i) in chips" :key="'chip' + i">
                    <button type="button" @click="chip.clear()"
                        class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2 py-0.5 text-[9px] font-bold text-emerald-200 transition hover:border-rose-500/50 hover:text-rose-200">
                        <span x-text="chip.label"></span>
                        <span class="ml-0.5 opacity-60">×</span>
                    </button>
                </template>

                <button type="button" @click="clearFilters()"
                    class="px-1.5 text-[9px] font-black text-slate-500 transition hover:text-slate-200">
                    limpiar todo
                </button>
            </div>


            {{-- ============ LOS FILTROS ============ --}}

            <div x-show="openFilters" x-cloak class="mt-2 border-t border-slate-800 pt-2">

                <div class="grid gap-2 lg:grid-cols-[200px_1fr]">

                    <div class="space-y-1.5">

                        @if (count($types))
                            <label class="block">
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Tipo</span>
                                <select x-model="type"
                                    class="mt-0.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">todos</option>
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        <label class="block">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Solo</span>
                            <select x-model="only"
                                class="mt-0.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">sin restringir</option>
                                <option value="TROPHIES">con trofeos</option>
                                <option value="TITLES">campeones</option>
                                <option value="VERSIONS">con más de una versión</option>
                                <option value="PLAYED">han competido</option>
                                <option value="NEVER">nunca han competido</option>
                                <option value="LIBRARY">vienen de la Biblioteca</option>
                            </select>
                        </label>
                    </div>


                    {{--
                        El catálogo. Cada atributo dice cuántos lo llevan, y
                        cada valor cuántos lo tienen: elegir a ciegas y
                        descubrir después que el filtro deja cero es peor
                        que verlo antes.
                    --}}

                    <div class="space-y-1.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Por atributo y catálogo
                        </p>

                        @if (empty($catalog))
                            <p class="rounded-lg border border-dashed border-slate-700 px-3 py-3 text-center text-[10px] text-slate-600">
                                Ninguno de estos competidores tiene atributos todavía.
                            </p>
                        @else
                            <div class="space-y-1">
                                @foreach ($catalog as $a)
                                    <div class="rounded-lg border border-slate-800 bg-slate-950/60">

                                        <div class="flex items-center gap-2 px-2 py-1.5">

                                            <button type="button" @click="toggleHas('{{ $a['key'] }}')"
                                                class="rounded px-1.5 py-0.5 text-[10px] font-black transition"
                                                :class="isHas('{{ $a['key'] }}')
                                                    ? 'bg-emerald-500/20 text-emerald-300'
                                                    : 'text-slate-400 hover:text-slate-100'"
                                                title="Los que tengan este atributo, con el valor que sea">
                                                {{ $a['label'] }}
                                            </button>

                                            <span class="font-mono text-[9px] text-slate-600">{{ $a['entities'] }}</span>

                                            <button type="button"
                                                @click="openAttribute = openAttribute === '{{ $a['key'] }}' ? null : '{{ $a['key'] }}'"
                                                class="ml-auto text-[10px] text-slate-600 transition hover:text-slate-300"
                                                x-text="openAttribute === '{{ $a['key'] }}' ? '−' : '+ ' + {{ count($a['values']) }}"></button>
                                        </div>

                                        <div x-show="openAttribute === '{{ $a['key'] }}'" x-cloak
                                            class="flex flex-wrap gap-1 border-t border-slate-800 p-1.5">
                                            @foreach ($a['values'] as $v)
                                                <button type="button"
                                                    @click="toggleValue('{{ $a['key'] }}', @js($v['key']))"
                                                    class="rounded-full border px-2 py-0.5 text-[9px] font-bold transition"
                                                    :class="isValueOn('{{ $a['key'] }}', @js($v['key']))
                                                        ? 'border-emerald-400/60 bg-emerald-500/20 text-emerald-200'
                                                        : 'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'">
                                                    {{ $v['label'] }}
                                                    <span class="ml-0.5 font-mono opacity-60">{{ $v['entities'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        {{-- ============ LOS COMPETIDORES ============ --}}

        <template x-if="shown.length === 0">
            <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-[11px] leading-relaxed text-slate-600">
                Ningún competidor cumple estos filtros.
                <button type="button" @click="clearFilters()" class="ml-1 underline">quitarlos</button>
            </p>
        </template>

        @include('universes.entities.partials.browser-table')
        @include('universes.entities.partials.browser-cards')

    </div>

</x-universe-layout>

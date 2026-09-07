@php
    /*
     * Las definiciones de versión.
     *
     * Esta pantalla tenía un problema de vocabulario antes que de diseño: se
     * llamaba «Versiones» y enseñaba cosas que no son la versión de nadie.
     *
     * Una DEFINICIÓN —«Shippuden», «Niño», «Forma final»— es un molde. No
     * pertenece a ninguna entidad: existe suelta, y muchas entidades la
     * aplican. Cada aplicación concreta es una VERSIÓN DE ENTIDAD, y esas
     * viven en la pestaña de al lado.
     *
     * Por eso lo primero que hay aquí es esa explicación dibujada: sin ella,
     * la mitad de la pantalla no significa nada.
     *
     * Tres maneras de mirar:
     *
     *   mosaico   la ficha con su cara y en cuántas entidades se usa
     *   árbol     la jerarquía: qué definición cuelga de cuál
     *   tabla     para comparar clase, ámbito, activación y uso
     */

    $clases = [
        '' => 'Cualquier clase',
        'ERA' => 'Era',
        'AGE' => 'Edad',
        'FORM' => 'Forma',
        'TRANSFORMATION' => 'Transformación',
        'OUTFIT' => 'Apariencia',
        'TIMELINE' => 'Línea temporal',
    ];

    $ambitos = [
        '' => 'Cualquier ámbito',
        'SHARED' => 'Compartida',
        'EXCLUSIVE' => 'Exclusiva',
    ];

    $activaciones = [
        '' => 'Cualquier activación',
        'AUTO' => 'Automática',
        'MANUAL' => 'Manual',
        'BOTH' => 'Automática y manual',
    ];

    $estados = [
        '' => 'Cualquier estado',
        'ACTIVE' => 'Activa',
        'INACTIVE' => 'Inactiva',
        'ARCHIVED' => 'Archivada',
    ];

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $filtrando = $search !== '' || $kind !== '' || $scope !== '' || $status !== '' || $activation !== '';
@endphp

<x-app-layout title="Definiciones de versión" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <div x-data="{
        view: 'mosaic',
        size: 3,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.versions.view') ?? '{}');
                if (['mosaic', 'tree', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4].includes(g.size)) this.size = g.size;
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.versions.view',
                    JSON.stringify({ view: this.view, size: this.size }));
            } catch (e) {}
        },

        get columns() {
            return {
                2: 'sm:grid-cols-1 lg:grid-cols-2',
                3: 'sm:grid-cols-2 lg:grid-cols-3',
                4: 'sm:grid-cols-2 lg:grid-cols-4',
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

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Definiciones de versión
                </h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Los moldes que tus entidades pueden aplicar: «Shippuden», «Niño», «Forma final».
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ([['Definiciones', $stats['total'], 'text-white', []], ['Activas', $stats['active'], 'text-emerald-300', ['status' => 'ACTIVE']], ['Compartidas', $stats['shared'], 'text-violet-300', ['scope' => 'SHARED']], ['Exclusivas', $stats['exclusive'], 'text-sky-300', ['scope' => 'EXCLUSIVE']], ['Automáticas', $stats['automatic'], 'text-cyan-300', ['activation' => 'AUTO']]] as [$etiqueta, $valor, $tono, $parametros])
                    <a href="{{ route('versions.index', $parametros) }}"
                        class="group flex items-baseline gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-2.5 py-1.5 transition hover:border-slate-700">
                        <span class="font-mono text-base font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 transition group-hover:text-slate-400">{{ $etiqueta }}</span>
                    </a>
                @endforeach
            </div>

        </header>


        {{-- ===================================================== --}}
        {{-- EL TALLER --}}
        {{-- ===================================================== --}}

        @include('versions.partials.workspace-navigation')


        {{-- ===================================================== --}}
        {{-- QUÉ ES ESTO, DIBUJADO --}}
        {{-- ===================================================== --}}

        {{--
            El concepto que hace falta entender antes de tocar nada. Un
            esquema de cuatro trazos lo explica en un vistazo; el párrafo
            equivalente se lee en diez segundos y se olvida en cinco.
        --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-5 py-3 text-left transition hover:bg-violet-500/5">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="grafo" size="h-4 w-4" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Un molde, muchas versiones
                    <span class="font-bold text-slate-500">— la diferencia, en un dibujo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-5">

                <div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">

                    {{-- El esquema --}}
                    <svg viewBox="0 0 240 130" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- El molde --}}
                        <rect x="6" y="48" width="62" height="34" rx="5" />
                        <path d="M16 60h42M16 70h28" opacity=".5" />
                        <text x="37" y="42" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="9" font-weight="700">Definición</text>

                        {{-- Las flechas --}}
                        <path d="M70 65h22M92 65 84 59M92 65l-8 6" opacity=".8" />
                        <path d="M76 62 96 26M96 26l-9 1M96 26l1 9" opacity=".55" />
                        <path d="M76 68 96 104M96 104l-9-1M96 104l1-9" opacity=".55" />

                        {{-- Las entidades con su versión --}}
                        <rect x="104" y="12" width="60" height="28" rx="5" opacity=".9" />
                        <circle cx="118" cy="26" r="6" opacity=".7" />
                        <path d="M132 22h24M132 30h16" opacity=".45" />

                        <rect x="104" y="51" width="60" height="28" rx="5" opacity=".9" />
                        <circle cx="118" cy="65" r="6" opacity=".7" />
                        <path d="M132 61h24M132 69h16" opacity=".45" />

                        <rect x="104" y="90" width="60" height="28" rx="5" opacity=".9" />
                        <circle cx="118" cy="104" r="6" opacity=".7" />
                        <path d="M132 100h24M132 108h16" opacity=".45" />

                        <text x="196" y="20" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700" opacity=".85">Naruto</text>
                        <text x="196" y="59" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700" opacity=".85">Sasuke</text>
                        <text x="196" y="98" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700" opacity=".85">Sakura</text>
                    </svg>

                    <div class="space-y-2.5 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            <strong class="text-violet-300">La definición</strong> es el molde:
                            «Shippuden». Existe una sola vez, no pertenece a nadie y se gestiona
                            desde esta pantalla.
                        </p>

                        <p>
                            <strong class="text-white">La versión de una entidad</strong> es el
                            molde ya puesto sobre alguien: «Naruto — Shippuden», con su propia
                            imagen, su nombre y sus características. Esas viven en
                            <a href="{{ route('versions.entities.index') }}"
                                class="font-black text-violet-300 underline transition hover:text-violet-200">Aplicadas</a>.
                        </p>

                        <p>
                            Una definición <strong class="text-slate-200">compartida</strong> la
                            puede aplicar cualquier entidad; una <strong
                                class="text-slate-200">exclusiva</strong> se reserva para una. Y su
                            <strong class="text-slate-200">activación</strong> decide si se elige
                            sola —según el catálogo activo— o a mano.
                        </p>

                        <p class="border-t border-slate-800 pt-2.5 text-[10px] text-slate-600">
                            De cada entidad, una de sus versiones puede marcarse como
                            <strong class="text-slate-400">Base activa</strong>: esa es la cara que
                            el resto de la aplicación enseña.
                        </p>
                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('versions.index') }}"
                class="flex flex-wrap items-center gap-2 px-4 py-3">

                <label class="relative min-w-[180px] flex-1">
                    <span class="sr-only">Buscar definición</span>

                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>

                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Buscar por nombre, código o descripción..."
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                </label>

                @foreach ([['kind', $clases, $kind], ['scope', $ambitos, $scope], ['activation', $activaciones, $activation], ['status', $estados, $status]] as [$campo, $opciones, $actual])
                    <select name="{{ $campo }}" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach ($opciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                @endforeach

                <button type="submit"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Buscar
                </button>

                @can('create', App\Models\Version::class)
                    <a href="{{ route('versions.create') }}" title="Crear una definición nueva"
                        class="flex items-center gap-1.5 rounded-xl bg-violet-500/15 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                        <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                        Nueva
                    </a>
                @endcan

                @if ($filtrando)
                    <a href="{{ route('versions.index') }}"
                        class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar filtros
                    </a>
                @endif

                <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['mosaic', 'cuadricula', 'Mosaico: la ficha de cada molde'], ['tree', 'grafo', 'Árbol: qué definición cuelga de cuál'], ['table', 'controles', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="view === '{{ $modo }}'"
                            :class="view === '{{ $modo }}' ? 'bg-violet-500 text-white' :
                                'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="view === 'mosaic'" x-cloak
                    class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    <button type="button" @click="size = Math.max(2, size - 1)" :disabled="size === 2"
                        title="Más grandes"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>

                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="size"></span>

                    <button type="button" @click="size = Math.min(4, size + 1)" :disabled="size === 4"
                        title="Más pequeñas"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>

            </form>

        </div>


        {{-- ===================================================== --}}
        {{-- LAS DEFINICIONES --}}
        {{-- ===================================================== --}}

        @if ($versions->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700">
                    <x-omni-icon name="capas" size="h-10 w-10" />
                </span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ninguna definición encaja' : 'Todavía no hay moldes' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba a quitar algún filtro: puede que la que buscas esté archivada o sea de otra clase.'
                        : 'Una definición sirve para tener a la misma entidad en dos estados sin duplicarla: «Naruto niño» y «Naruto Hokage» son la misma persona.' }}
                </p>

                @if ($filtrando)
                    <a href="{{ route('versions.index') }}"
                        class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Quitar los filtros
                    </a>
                @else
                    @can('create', App\Models\Version::class)
                        <a href="{{ route('versions.create') }}"
                            class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            + Crear la primera
                        </a>
                    @endcan
                @endif
            </div>

        @else

            {{-- ============ MOSAICO ============ --}}

            <div x-show="view === 'mosaic'" class="grid gap-3" :class="columns">
                @foreach ($versions as $definicion)
                    @include('versions.partials.library-card', [
                        'definicion' => $definicion,
                        'estadoTono' => $estadoTono,
                    ])
                @endforeach

                {{--
                    Crear va al final, no arriba: primero se mira lo que ya hay
                    —para no repetir un molde que existía— y solo después se
                    añade. Arriba competía con el título por la atención.
                --}}

                @can('create', App\Models\Version::class)
                    <a href="{{ route('versions.create') }}"
                        class="group flex min-h-[220px] flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-800 p-5 text-center transition hover:border-violet-500/60 hover:bg-violet-500/5">

                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 text-slate-600 transition group-hover:bg-violet-500/15 group-hover:text-violet-300">
                            <x-omni-icon name="mas" size="h-5 w-5" />
                        </span>

                        <span class="block text-[12px] font-black text-slate-300 transition group-hover:text-white">
                            Nueva definición
                        </span>

                        <span class="block max-w-[200px] text-[10px] leading-relaxed text-slate-600">
                            Un molde nuevo que luego podrás aplicar a todas las entidades que encajen.
                        </span>
                    </a>
                @endcan
            </div>


            {{-- ============ ÁRBOL ============ --}}

            {{--
                Una definición puede colgar de otra —«Shippuden» dentro de
                «Naruto», por ejemplo—. En mosaico eso no se ve; aquí sí.
            --}}

            <div x-show="view === 'tree'" x-cloak
                class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                @if ($treeVersions->isEmpty())
                    <p class="py-8 text-center text-[11px] text-slate-600">
                        Ninguna definición cuelga de otra todavía.
                    </p>
                @else
                    <div class="space-y-1.5">
                        @foreach ($treeVersions as $raiz)
                            @include('versions.partials.tree-branch', [
                                'nodo' => $raiz,
                                'nivel' => 0,
                                'estadoTono' => $estadoTono,
                            ])
                        @endforeach
                    </div>
                @endif

            </div>


            {{-- ============ TABLA ============ --}}

            <div x-show="view === 'table'" x-cloak
                class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full min-w-[860px]">

                    <thead class="border-b border-slate-800 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-2.5">Definición</th>
                            <th class="px-3 py-2.5">Clase</th>
                            <th class="px-3 py-2.5">Ámbito</th>
                            <th class="px-3 py-2.5">Activación</th>
                            <th class="px-3 py-2.5">Estado</th>
                            <th class="px-3 py-2.5 text-right">Aplicada en</th>
                            <th class="px-3 py-2.5 text-right">Hijas</th>
                            <th class="px-3 py-2.5 text-right">Catálogos</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($versions as $definicion)
                            <tr class="transition hover:bg-slate-900/60">

                                <td class="px-3 py-2">
                                    <a href="{{ route('versions.show', $definicion) }}"
                                        class="flex items-center gap-2">
                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($definicion->image_url)
                                                <img src="{{ $definicion->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[11px] text-violet-400">◈</span>
                                            @endif
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block truncate text-[12px] font-black text-white">
                                                {{ $definicion->name }}
                                            </span>
                                            <span class="block font-mono text-[9px] text-slate-600">
                                                {{ $definicion->code }}
                                            </span>
                                        </span>
                                    </a>
                                </td>

                                <td class="px-3 py-2 text-[11px] text-slate-400">{{ $definicion->kind_label }}</td>
                                <td class="px-3 py-2 text-[11px] text-slate-400">{{ $definicion->scope_label }}</td>
                                <td class="px-3 py-2 text-[11px] text-slate-400">{{ $definicion->activation_label }}</td>

                                <td class="px-3 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$definicion->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $definicion->status_label }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $definicion->entity_versions_count > 0 ? 'text-violet-300' : 'text-slate-700' }}">
                                    {{ $definicion->entity_versions_count }}
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-500">
                                    {{ $definicion->children_count }}</td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] text-cyan-300">
                                    {{ $definicion->catalog_links_count }}</td>

                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('versions.show', $definicion) }}"
                                        class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">
                                        Ver →
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>


            <div class="mt-6">
                {{ $versions->links() }}
            </div>

        @endif

    </div>

</x-app-layout>

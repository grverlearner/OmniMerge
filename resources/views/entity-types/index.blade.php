@php
    /*
     * La biblioteca de tipos de entidad.
     *
     * Un tipo no se reconoce por su nombre: se reconoce por lo que lleva
     * puesto. Por eso cada ficha enseña las caras de sus últimas entidades —si
     * un tipo llamado «Personaje» está lleno de banderas, se ve aquí sin
     * abrirlo—, y por eso un tipo vacío se marca: existe, pero todavía no
     * sirve de nada.
     *
     * Tres maneras de mirar:
     *
     *   mosaico   la ficha grande con sus caras dentro
     *   lista     una línea por tipo, con las caras en fila
     *   tabla     para comparar cifras
     *
     * El color de cada tipo es un dato del usuario y por eso va en `style` y
     * en variables CSS: una clase compuesta con 'border-' . $color no
     * existiría en el CSS.
     */

    $estados = [
        '' => 'Cualquier estado',
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
        'ARCHIVED' => 'Archivado',
    ];

    $ordenes = [
        'manual' => 'Mi orden',
        'entities_desc' => 'Más usados',
        'entities_asc' => 'Menos usados',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'code_asc' => 'Código ↑',
        'code_desc' => 'Código ↓',
    ];

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $filtrando = $search !== '' || $status;

    /* Cuántas entidades hay repartidas entre los tipos de esta página */
    $totalEntidades = $entityTypes->sum('entities_count');
@endphp

<x-app-layout title="Tipos de entidad" surface="dark">

    <x-slot name="header">Tipos de entidad</x-slot>

    <div x-data="{
        view: 'mosaic',
        size: 3,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.entity-types.view') ?? '{}');
                if (['mosaic', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4].includes(g.size)) this.size = g.size;
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.entity-types.view',
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

        <header
            class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40">

            <span class="pointer-events-none absolute -right-24 -top-28 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl"></span>
            <span class="pointer-events-none absolute -bottom-32 left-1/4 h-56 w-56 rounded-full bg-violet-500/10 blur-3xl"></span>

            <div class="relative px-5 py-5">

                <div class="flex flex-wrap items-end gap-4">

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('entities.index') }}"
                            class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                            ← Entidades
                        </a>

                        <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                            Tipos de entidad
                        </h1>

                        <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                            «Personaje», «País», «Equipo». Son etiquetas para organizarte: sirven
                            para filtrar y para reconocer de un vistazo, y no limitan qué
                            características puede tener una entidad.
                        </p>
                    </div>

                    @can('create', App\Models\EntityType::class)
                        <a href="{{ route('entity-types.create') }}"
                            class="flex shrink-0 items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-indigo-950/40 transition hover:bg-indigo-400">
                            <x-omni-icon name="mas" size="h-4 w-4" />
                            Nuevo tipo
                        </a>
                    @endcan
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ([['Tipos', $stats['total'], 'text-white', []], ['Activos', $stats['active'], 'text-emerald-300', ['status' => 'ACTIVE']], ['Inactivos', $stats['inactive'], 'text-amber-300', ['status' => 'INACTIVE']], ['Archivados', $stats['archived'], 'text-slate-400', ['status' => 'ARCHIVED']]] as [$etiqueta, $valor, $tono, $parametros])
                        <a href="{{ route('entity-types.index', $parametros) }}"
                            class="group flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 transition hover:border-slate-700">
                            <span class="font-mono text-lg font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">
                                {{ $valor }}
                            </span>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600 transition group-hover:text-slate-400">
                                {{ $etiqueta }}
                            </span>
                        </a>
                    @endforeach

                    <span class="flex items-baseline gap-2 rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-3 py-2">
                        <span class="font-mono text-lg font-black text-indigo-300">{{ $totalEntidades }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Entidades repartidas
                        </span>
                    </span>
                </div>

            </div>

        </header>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('entity-types.index') }}"
                class="flex flex-wrap items-center gap-2 px-4 py-3">

                <label class="relative min-w-[200px] flex-1">
                    <span class="sr-only">Buscar tipo</span>

                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>

                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Buscar por nombre, código o descripción..."
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">
                </label>

                @foreach ([['status', $estados, $status], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                    <select name="{{ $campo }}" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($opciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                @endforeach

                <select name="per_page" onchange="this.form.submit()"
                    class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ([12, 24, 48] as $cuantos)
                        <option value="{{ $cuantos }}" @selected($perPage === $cuantos)>{{ $cuantos }} por página</option>
                    @endforeach
                </select>

                <button type="submit"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                    Buscar
                </button>

                @if ($filtrando)
                    <a href="{{ route('entity-types.index') }}"
                        class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar filtros
                    </a>
                @endif

                <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                    @foreach ([['mosaic', 'cuadricula', 'Mosaico: la ficha con sus caras'], ['list', 'controles', 'Lista: una línea por tipo'], ['table', 'grafo', 'Tabla: para comparar cifras']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="view === '{{ $modo }}'"
                            :class="view === '{{ $modo }}' ? 'bg-indigo-500 text-white' :
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
        {{-- LOS TIPOS --}}
        {{-- ===================================================== --}}

        @if ($entityTypes->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700">
                    <x-omni-icon name="galeria" size="h-10 w-10" />
                </span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ningún tipo encaja' : 'Todavía no tienes tipos' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba a quitar el filtro de estado o a buscar otra cosa.'
                        : 'Sin tipos las entidades siguen funcionando, pero no hay forma de agruparlas ni de reconocerlas de un vistazo.' }}
                </p>

                @if ($filtrando)
                    <a href="{{ route('entity-types.index') }}"
                        class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                        Quitar los filtros
                    </a>
                @else
                    @can('create', App\Models\EntityType::class)
                        <a href="{{ route('entity-types.create') }}"
                            class="mt-4 inline-block rounded-xl bg-indigo-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-indigo-400">
                            + Crear el primero
                        </a>
                    @endcan
                @endif
            </div>

        @else

            {{-- ============ MOSAICO ============ --}}

            <div x-show="view === 'mosaic'" class="grid gap-3" :class="columns">
                @foreach ($entityTypes as $tipo)
                    @include('entity-types.partials.library-card', [
                        'tipo' => $tipo,
                        'estadoTono' => $estadoTono,
                    ])
                @endforeach
            </div>


            {{-- ============ LISTA ============ --}}

            <div x-show="view === 'list'" x-cloak class="space-y-2">
                @foreach ($entityTypes as $tipo)
                    @include('entity-types.partials.library-row', [
                        'tipo' => $tipo,
                        'estadoTono' => $estadoTono,
                    ])
                @endforeach
            </div>


            {{-- ============ TABLA ============ --}}

            <div x-show="view === 'table'" x-cloak
                class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                <table class="w-full min-w-[760px]">

                    <thead class="border-b border-slate-800 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-2.5">Tipo</th>
                            <th class="px-3 py-2.5">Color</th>
                            <th class="px-3 py-2.5">Estado</th>
                            <th class="px-3 py-2.5 text-right">Entidades</th>
                            <th class="px-3 py-2.5 text-right">Orden</th>
                            <th class="px-3 py-2.5">Creado</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        @foreach ($entityTypes as $tipo)
                            @php $color = $tipo->color ?: '#6366f1'; @endphp

                            <tr class="transition hover:bg-slate-900/60">

                                <td class="px-3 py-2">
                                    <a href="{{ route('entity-types.show', $tipo) }}" class="flex items-center gap-2">
                                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($tipo->image_url)
                                                <img src="{{ $tipo->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[13px]"
                                                    style="color: {{ $color }}">{{ $tipo->icon ?: '◇' }}</span>
                                            @endif
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block truncate text-[12px] font-black text-white">
                                                {{ $tipo->name }}
                                            </span>
                                            <span class="block font-mono text-[9px] text-slate-600">{{ $tipo->code }}</span>
                                        </span>
                                    </a>
                                </td>

                                <td class="px-3 py-2">
                                    <span class="flex items-center gap-1.5">
                                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $color }}"></span>
                                        <span class="font-mono text-[10px] text-slate-500">{{ $color }}</span>
                                    </span>
                                </td>

                                <td class="px-3 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$tipo->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $tipo->status }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] {{ $tipo->entities_count > 0 ? 'text-indigo-300' : 'text-slate-700' }}">
                                    {{ $tipo->entities_count }}
                                </td>

                                <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-500">
                                    {{ $tipo->sort_order }}
                                </td>

                                <td class="px-3 py-2 text-[10px] text-slate-500">
                                    {{ $tipo->created_at?->locale('es')->isoFormat('D MMM YYYY') }}
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('entity-types.show', $tipo) }}"
                                        class="text-[10px] font-black text-slate-400 transition hover:text-indigo-300">
                                        Ver →
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>


            <div class="mt-6">
                {{ $entityTypes->links() }}
            </div>

        @endif

    </div>

</x-app-layout>

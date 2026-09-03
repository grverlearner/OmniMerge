@php
    /*
     * La biblioteca de plantillas de torneo.
     *
     * Una plantilla de torneo no se entiende por su nombre: se entiende por
     * su RECORRIDO —por dónde entra la gente, qué fases atraviesa y en qué
     * finales acaba—. Antes la lista enseñaba una tarjeta con la foto y poco
     * más, así que para saber qué era cada cosa había que abrirla.
     *
     * Aquí hay cuatro maneras de mirarla:
     *
     *   cuadrícula   la cara y las cifras, para abarcar muchas
     *   detalle      el recorrido entero: entradas → fases → finales
     *   lista        una línea por plantilla
     *   tabla        para comparar cifras
     *
     * El filtrado y la ordenación los hace el SERVIDOR porque la lista está
     * paginada: filtrar en el cliente ordenaría una página, no la
     * biblioteca. Lo que vive en el cliente es la forma de mirar, que no
     * cambia los datos, y se recuerda entre visitas.
     */

    use App\Models\TournamentTemplate;

    $estados = [
        '' => 'Cualquier estado',
        'DRAFT' => 'Borrador',
        'ACTIVE' => 'Activa',
        'ARCHIVED' => 'Archivada',
    ];

    $visibilidades = [
        '' => 'Cualquier visibilidad',
        'PRIVATE' => 'Privada',
        'PUBLIC' => 'Pública',
        'UNLISTED' => 'No listada',
    ];

    $categorias = ['' => 'Cualquier tipo'] + TournamentTemplate::CATEGORIES;

    $usos = [
        '' => 'Usadas o no',
        'used' => 'Ya en uso',
        'unused' => 'Sin usar todavía',
    ];

    $ordenes = [
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguas',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'phases_desc' => 'Más fases',
        'phases_asc' => 'Menos fases',
        'participants_desc' => 'Más participantes',
        'used_desc' => 'Más usadas',
    ];

    $filtrando = $search !== '' || $status !== '' || $visibility !== '' || $category !== '' || $use !== '';

    /*
     * Las clases van literales, nunca compuestas: Tailwind lee este archivo
     * y una clase armada con 'border-' . $color no existiría en el CSS.
     */
    $tonos = [
        'amber' => ['borde' => 'border-amber-500/40', 'texto' => 'text-amber-300', 'fondo' => 'bg-amber-500/10'],
        'violet' => ['borde' => 'border-violet-500/40', 'texto' => 'text-violet-300', 'fondo' => 'bg-violet-500/10'],
        'cyan' => ['borde' => 'border-cyan-500/40', 'texto' => 'text-cyan-300', 'fondo' => 'bg-cyan-500/10'],
        'emerald' => ['borde' => 'border-emerald-500/40', 'texto' => 'text-emerald-300', 'fondo' => 'bg-emerald-500/10'],
        'rose' => ['borde' => 'border-rose-500/40', 'texto' => 'text-rose-300', 'fondo' => 'bg-rose-500/10'],
        'sky' => ['borde' => 'border-sky-500/40', 'texto' => 'text-sky-300', 'fondo' => 'bg-sky-500/10'],
        'slate' => ['borde' => 'border-slate-700', 'texto' => 'text-slate-300', 'fondo' => 'bg-slate-800/60'],
    ];

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'DRAFT' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    /* Los finales tienen sentidos muy distintos y conviene que se vean distintos */
    $finalTono = [
        'CHAMPION' => 'border-amber-500/30 bg-amber-500/5 text-amber-300',
        'QUALIFIED' => 'border-emerald-500/30 bg-emerald-500/5 text-emerald-300',
        'PLACEMENT' => 'border-sky-500/30 bg-sky-500/5 text-sky-300',
        'ELIMINATED' => 'border-slate-700 bg-slate-900 text-slate-500',
    ];
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">Mis plantillas</x-slot>

    <div x-data="{
        view: 'grid',
        size: 3,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.tournaments.view') ?? '{}');
                if (['grid', 'detail', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4].includes(g.size)) this.size = g.size;
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.tournaments.view',
                    JSON.stringify({ view: this.view, size: this.size }));
            } catch (e) {}
        },

        get columns() {
            if (this.view === 'detail') {
                return { 2: 'lg:grid-cols-1', 3: 'lg:grid-cols-2', 4: 'lg:grid-cols-3' }[this.size];
            }

            return {
                2: 'sm:grid-cols-1 lg:grid-cols-2',
                3: 'sm:grid-cols-2 lg:grid-cols-3',
                4: 'sm:grid-cols-2 lg:grid-cols-4',
            }[this.size];
        },
    }">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="px-5 py-5">

                <div class="flex flex-wrap items-end gap-4">

                    <div class="min-w-0 flex-1">

                        <a href="{{ route('tournaments.dashboard') }}"
                            class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-amber-400">
                            ← Torneos
                        </a>

                        <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                            Mis plantillas
                        </h1>

                        <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-500">
                            Una plantilla describe un recorrido completo: por dónde entra la gente,
                            qué fases atraviesa y en qué finales acaba. Define cómo funciona el
                            torneo, no quién lo juega.
                        </p>
                    </div>

                    @can('create', App\Models\TournamentTemplate::class)
                        <a href="{{ route('tournaments.templates.create') }}"
                            class="shrink-0 rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-slate-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                            + Nueva plantilla
                        </a>
                    @endcan

                </div>


                {{-- ============ LAS CIFRAS ============ --}}

                <div class="mt-4 flex flex-wrap gap-2">

                    @foreach ([['Plantillas', $stats['total'], 'text-white'], ['Activas', $stats['active'], 'text-emerald-300'], ['Borradores', $stats['draft'], 'text-amber-300'], ['Públicas', $stats['public'], 'text-sky-300'], ['En uso', $stats['in_use'], 'text-violet-300']] as [$etiqueta, $valor, $color])
                        <span
                            class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                            <span class="font-mono text-lg font-black {{ $color }}">{{ $valor }}</span>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                {{ $etiqueta }}
                            </span>
                        </span>
                    @endforeach

                </div>

            </div>

        </header>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 mt-4 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <div class="px-4 py-3">

                <form method="GET" action="{{ route('tournaments.templates.index') }}"
                    class="flex flex-wrap items-center gap-2">

                    {{-- Buscar --}}
                    <label class="relative min-w-[200px] flex-1">
                        <span class="sr-only">Buscar plantilla</span>

                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>

                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre, código o descripción..."
                            class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-amber-500 focus:ring-amber-500">
                    </label>

                    {{-- Los cinco filtros. Se envían al cambiar: un filtro que
                         hay que confirmar con un botón se usa la mitad. --}}
                    @foreach ([['category', $categorias, $category], ['status', $estados, $status], ['visibility', $visibilidades, $visibility], ['use', $usos, $use], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                        <select name="{{ $campo }}" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-amber-500 focus:ring-amber-500">
                            @foreach ($opciones as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($actual === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endforeach

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                        Buscar
                    </button>

                    @if ($filtrando)
                        <a href="{{ route('tournaments.templates.index') }}"
                            class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                            Quitar filtros
                        </a>
                    @endif


                    {{-- ============ CÓMO MIRAR ============ --}}

                    <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                        @foreach ([['grid', 'cuadricula', 'Cuadrícula: la cara y las cifras'], ['detail', 'grafo', 'Detalle: el recorrido completo'], ['list', 'controles', 'Lista: una línea por plantilla'], ['table', 'capas', 'Tabla: para comparar cifras']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="view === '{{ $modo }}'"
                                :class="view === '{{ $modo }}' ?
                                    'bg-amber-500 text-slate-950' :
                                    'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

                    {{-- Cuántas caben a lo ancho --}}
                    <span x-show="view === 'grid' || view === 'detail'" x-cloak
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

        </div>


        {{-- ===================================================== --}}
        {{-- LAS PLANTILLAS --}}
        {{-- ===================================================== --}}

        <div class="py-5">

            @if ($templates->isEmpty())

                <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <span class="inline-flex text-slate-700">
                        <x-omni-icon name="trofeo" size="h-10 w-10" />
                    </span>

                    <h2 class="mt-3 text-lg font-black text-white">
                        {{ $filtrando ? 'Ninguna plantilla encaja' : 'Todavía no hay plantillas' }}
                    </h2>

                    <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                        {{ $filtrando
                            ? 'Prueba a quitar algún filtro: puede que lo que buscas esté archivado o sea de otro tipo.'
                            : 'Una plantilla encadena fases para formar un recorrido completo, desde quién entra hasta quién se lleva qué.' }}
                    </p>

                    @if ($filtrando)
                        <a href="{{ route('tournaments.templates.index') }}"
                            class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                            Quitar los filtros
                        </a>
                    @else
                        @can('create', App\Models\TournamentTemplate::class)
                            <a href="{{ route('tournaments.templates.create') }}"
                                class="mt-4 inline-block rounded-xl bg-amber-500 px-4 py-2 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                                + Crear la primera
                            </a>
                        @endcan
                    @endif
                </div>

            @else

                {{-- ============ CUADRÍCULA Y DETALLE ============ --}}

                <div x-show="view === 'grid' || view === 'detail'" class="grid gap-4" :class="columns">
                    @foreach ($templates as $template)
                        @include('tournaments.templates.partials.library-card', [
                            'plantilla' => $template,
                            'tonos' => $tonos,
                            'estadoTono' => $estadoTono,
                            'finalTono' => $finalTono,
                        ])
                    @endforeach
                </div>


                {{-- ============ LISTA ============ --}}

                <div x-show="view === 'list'" x-cloak class="space-y-2">
                    @foreach ($templates as $template)
                        @include('tournaments.templates.partials.library-row', [
                            'plantilla' => $template,
                            'tonos' => $tonos,
                            'estadoTono' => $estadoTono,
                        ])
                    @endforeach
                </div>


                {{-- ============ TABLA ============ --}}

                <div x-show="view === 'table'" x-cloak
                    class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                    <table class="w-full min-w-[980px]">

                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-3 py-2.5">Plantilla</th>
                                <th class="px-3 py-2.5">Tipo</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-3 py-2.5">Visibilidad</th>
                                <th class="px-3 py-2.5 text-right">Participantes</th>
                                <th class="px-3 py-2.5 text-right">Entradas</th>
                                <th class="px-3 py-2.5 text-right">Fases</th>
                                <th class="px-3 py-2.5 text-right">Enlaces</th>
                                <th class="px-3 py-2.5 text-right">Finales</th>
                                <th class="px-3 py-2.5 text-right">En uso</th>
                                <th class="px-3 py-2.5"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($templates as $template)
                                <tr class="transition hover:bg-slate-900/60">

                                    <td class="px-3 py-2.5">
                                        <a href="{{ route('tournaments.templates.show', $template) }}"
                                            class="flex items-center gap-2">
                                            <span
                                                class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-800 bg-slate-950 text-[11px]">
                                                @if ($template->image_url)
                                                    <img src="{{ $template->image_url }}" alt="" loading="lazy"
                                                        class="h-full w-full object-cover">
                                                @else
                                                    {{ $template->display_icon }}
                                                @endif
                                            </span>

                                            <span class="min-w-0">
                                                <span
                                                    class="block truncate text-[12px] font-black text-white">{{ $template->name }}</span>
                                                <span
                                                    class="block font-mono text-[9px] text-slate-600">{{ $template->code }}</span>
                                            </span>
                                        </a>
                                    </td>

                                    <td class="px-3 py-2.5 text-[11px] font-bold {{ $tonos[$template->accent]['texto'] }}">
                                        {{ $template->category_label ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$template->status] ?? 'bg-slate-800 text-slate-500' }}">
                                            {{ $template->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-2.5 text-[11px] text-slate-400">
                                        {{ $template->visibility_label }}
                                    </td>

                                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-slate-300">
                                        {{ $template->min_participants }}@if ($template->max_participants)–{{ $template->max_participants }}@else+@endif
                                    </td>

                                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-cyan-300">
                                        {{ $template->graph_starts_count }}</td>

                                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-amber-300">
                                        {{ $template->graph_nodes_count }}</td>

                                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-slate-400">
                                        {{ $template->graph_connections_count }}</td>

                                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-violet-300">
                                        {{ $template->graph_terminals_count }}</td>

                                    <td class="px-3 py-2.5 text-right font-mono text-[11px] {{ $template->universe_tournaments_count > 0 ? 'text-emerald-300' : 'text-slate-700' }}">
                                        {{ $template->universe_tournaments_count }}</td>

                                    <td class="px-3 py-2.5 text-right">
                                        <a href="{{ route('tournaments.templates.show', $template) }}"
                                            class="text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                                            Ver →
                                        </a>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                    </table>

                </div>


                <div class="mt-6">
                    {{ $templates->links() }}
                </div>

            @endif

        </div>

    </div>

</x-tournament-layout>

@php
    /*
     * La biblioteca de Fases.
     *
     * Una fase no se reconoce por su nombre: se reconoce por su forma —cuánta
     * gente admite, por dónde entra, por dónde sale—. Antes había que abrir
     * cada una para saberlo, así que la lista servía para poco más que
     * localizar algo cuyo nombre ya recordabas.
     *
     * Aquí cada ficha cuenta esa forma, y hay cuatro maneras de mirarla:
     *
     *   cuadrícula   la cara y lo esencial, para abarcar muchas
     *   estructura   entradas → motor → salidas, con sus nombres
     *   lista        una línea por fase, con todo en horizontal
     *   tabla        para comparar cifras
     *
     * El filtrado y la ordenación los hace el SERVIDOR —ya existían— porque
     * la lista está paginada: filtrar en el cliente ordenaría una página, no
     * la biblioteca. Lo que sí vive en el cliente es la forma de mirar, que
     * no cambia los datos, y se recuerda entre visitas.
     */

    $tipos = [
        '' => 'Todos los motores',
        'SINGLE_ELIMINATION' => 'Eliminación directa',
        'ROUND_ROBIN' => 'Todos contra todos',
        'GROUP_STAGE' => 'Fase de grupos',
        'SWISS' => 'Sistema suizo',
    ];

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

    $ordenes = [
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguas',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'exits_desc' => 'Más salidas',
    ];

    /* Un filtro puesto es un filtro que hay que poder quitar */
    $filtrando = $search !== '' || $type !== '' || $status !== '' || $visibility !== '';

    /*
     * Las clases van literales, nunca compuestas: Tailwind lee el archivo y
     * una clase armada con 'border-' . $x no existiría en el CSS.
     */
    $tonos = [
        'amber' => ['borde' => 'border-amber-500/40', 'texto' => 'text-amber-300', 'fondo' => 'bg-amber-500/10', 'punto' => 'bg-amber-400'],
        'violet' => ['borde' => 'border-violet-500/40', 'texto' => 'text-violet-300', 'fondo' => 'bg-violet-500/10', 'punto' => 'bg-violet-400'],
        'cyan' => ['borde' => 'border-cyan-500/40', 'texto' => 'text-cyan-300', 'fondo' => 'bg-cyan-500/10', 'punto' => 'bg-cyan-400'],
        'emerald' => ['borde' => 'border-emerald-500/40', 'texto' => 'text-emerald-300', 'fondo' => 'bg-emerald-500/10', 'punto' => 'bg-emerald-400'],
        'rose' => ['borde' => 'border-rose-500/40', 'texto' => 'text-rose-300', 'fondo' => 'bg-rose-500/10', 'punto' => 'bg-rose-400'],
        'sky' => ['borde' => 'border-sky-500/40', 'texto' => 'text-sky-300', 'fondo' => 'bg-sky-500/10', 'punto' => 'bg-sky-400'],
        'slate' => ['borde' => 'border-slate-700', 'texto' => 'text-slate-300', 'fondo' => 'bg-slate-800/60', 'punto' => 'bg-slate-500'],
    ];

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'DRAFT' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">Biblioteca de Fases</x-slot>


    <div x-data="{
            view: 'grid',
            size: 3,

            init() {
                try {
                    const g = JSON.parse(localStorage.getItem('omnimerge.phases.view') ?? '{}');
                    if (['grid', 'structure', 'list', 'table'].includes(g.view)) this.view = g.view;
                    if ([2, 3, 4, 5].includes(g.size)) this.size = g.size;
                } catch (e) { /* modo privado, sin memoria */ }

                this.$watch('view', () => this.remember());
                this.$watch('size', () => this.remember());
            },

            remember() {
                try {
                    localStorage.setItem('omnimerge.phases.view',
                        JSON.stringify({ view: this.view, size: this.size }));
                } catch (e) {}
            },

            get columns() {
                return {
                    2: 'sm:grid-cols-1 lg:grid-cols-2',
                    3: 'sm:grid-cols-2 lg:grid-cols-3',
                    4: 'sm:grid-cols-2 lg:grid-cols-4',
                    5: 'sm:grid-cols-3 lg:grid-cols-5',
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
                            Biblioteca de Fases
                        </h1>

                        <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-500">
                            Cada fase es una pieza reutilizable: define cuánta gente admite, por
                            dónde entra y por dónde sale. Los torneos se montan encajándolas.
                        </p>
                    </div>

                    @can('create', App\Models\PhaseTemplate::class)
                        <a href="{{ route('tournaments.phase-templates.create') }}"
                            class="shrink-0 rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-slate-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                            + Nueva fase
                        </a>
                    @endcan

                </div>


                {{-- ============ LAS CIFRAS ============ --}}

                <div class="mt-4 flex flex-wrap gap-2">

                    @foreach ([
                        ['Fases', $stats['total'], 'text-white', null],
                        ['Activas', $stats['active'], 'text-emerald-300', 'ACTIVE'],
                        ['Públicas', $stats['public'], 'text-sky-300', null],
                        ['Con salidas', $stats['with_exits'], 'text-violet-300', null],
                    ] as [$etiqueta, $valor, $color, $filtro])
                        <span class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
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

                <form method="GET" action="{{ route('tournaments.phase-templates.index') }}"
                    class="flex flex-wrap items-center gap-2">

                    {{--
                        Los selectores envían al cambiar: un filtro que exige
                        buscar el botón de aplicar se usa la mitad de veces.
                        La búsqueda no, porque enviaría en cada tecla.
                    --}}

                    <label class="relative min-w-[190px] flex-1">
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre, código o descripción…"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 py-2 pl-8 pr-3 text-[12px] text-slate-100 placeholder:text-slate-600 focus:border-amber-500 focus:ring-amber-500">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-600">⌕</span>
                    </label>

                    @foreach ([
                        ['type', $tipos, $type],
                        ['status', $estados, $status],
                        ['visibility', $visibilidades, $visibility],
                        ['sort', $ordenes, $sort],
                    ] as [$campo, $opciones, $actual])
                        <select name="{{ $campo }}" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-bold text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                            @foreach ($opciones as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($actual === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endforeach

                    <button type="submit"
                        class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                        Buscar
                    </button>

                    @if ($filtrando)
                        <a href="{{ route('tournaments.phase-templates.index') }}"
                            class="rounded-xl border border-rose-500/40 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                            × Quitar filtros
                        </a>
                    @endif


                    {{-- ============ CÓMO SE MIRA ============ --}}

                    <div class="ml-auto flex items-center gap-2">

                        <div class="flex rounded-xl border border-slate-800 bg-slate-900 p-0.5">
                            @foreach ([
                                'grid' => ['▦', 'Cuadrícula: la cara y lo esencial'],
                                'structure' => ['⇥', 'Estructura: entradas, motor y salidas'],
                                'list' => ['☰', 'Lista: una línea por fase'],
                                'table' => ['▤', 'Tabla: para comparar cifras'],
                            ] as $modo => [$icono, $ayuda])
                                <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                                    class="rounded-lg px-2.5 py-1.5 text-[12px] transition"
                                    :class="view === '{{ $modo }}'
                                        ? 'bg-amber-500 text-slate-950'
                                        : 'text-slate-500 hover:text-slate-200'">{{ $icono }}</button>
                            @endforeach
                        </div>

                        {{-- El tamaño solo significa algo cuando hay rejilla --}}
                        <div class="flex items-center gap-0.5 rounded-xl border border-slate-800 bg-slate-900 px-1"
                            x-show="view === 'grid' || view === 'structure'">

                            <button type="button" @click="size = Math.max(2, size - 1)" :disabled="size <= 2"
                                title="Más grandes"
                                class="px-2 py-1.5 text-[13px] leading-none text-slate-500 transition hover:text-slate-100 disabled:opacity-25">−</button>

                            <span class="w-3 text-center font-mono text-[10px] text-slate-600" x-text="size"></span>

                            <button type="button" @click="size = Math.min(5, size + 1)" :disabled="size >= 5"
                                title="Más pequeñas"
                                class="px-2 py-1.5 text-[13px] leading-none text-slate-500 transition hover:text-slate-100 disabled:opacity-25">+</button>
                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- LAS FASES --}}
        {{-- ===================================================== --}}

        <div class="py-5">

            @if ($phaseTemplates->isEmpty())

                <div class="flex min-h-[50vh] items-center justify-center">
                    <div class="max-w-md text-center">
                        <div class="text-6xl opacity-20">◇</div>

                        <h2 class="mt-5 text-xl font-black text-white">
                            {{ $filtrando ? 'Nada coincide con eso' : 'Todavía no hay fases' }}
                        </h2>

                        <p class="mx-auto mt-2 max-w-sm text-[12px] leading-relaxed text-slate-500">
                            {{ $filtrando
                                ? 'Prueba a quitar algún filtro: la biblioteca tiene ' . $stats['total'] . ' fases en total.'
                                : 'Una fase es la pieza con la que se montan los torneos: define cuánta gente admite y por dónde entra y sale.' }}
                        </p>

                        @if ($filtrando)
                            <a href="{{ route('tournaments.phase-templates.index') }}"
                                class="mt-5 inline-block rounded-xl border border-slate-700 px-5 py-2.5 text-xs font-black text-slate-300 transition hover:border-amber-400 hover:text-amber-300">
                                Quitar los filtros
                            </a>
                        @else
                            @can('create', App\Models\PhaseTemplate::class)
                                <a href="{{ route('tournaments.phase-templates.create') }}"
                                    class="mt-5 inline-block rounded-xl bg-amber-500 px-6 py-3 text-xs font-black text-slate-950">
                                    Crear la primera
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>

            @else

                {{-- ============ CUADRÍCULA Y ESTRUCTURA ============ --}}

                <div x-show="view === 'grid' || view === 'structure'"
                    class="grid grid-cols-1 gap-3" :class="columns">

                    @foreach ($phaseTemplates as $fase)
                        @include('tournaments.phase-templates.partials.library-card', [
                            'fase' => $fase,
                            'tonos' => $tonos,
                            'estadoTono' => $estadoTono,
                        ])
                    @endforeach

                </div>


                {{-- ============ LISTA ============ --}}

                <div x-show="view === 'list'" x-cloak class="space-y-2">

                    @foreach ($phaseTemplates as $fase)
                        @include('tournaments.phase-templates.partials.library-row', [
                            'fase' => $fase,
                            'tonos' => $tonos,
                            'estadoTono' => $estadoTono,
                        ])
                    @endforeach

                </div>


                {{-- ============ TABLA ============ --}}

                <div x-show="view === 'table'" x-cloak
                    class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/40">

                    <table class="w-full min-w-[900px]">

                        <thead>
                            <tr class="border-b border-slate-800 text-[9px] font-black uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3 text-left">Fase</th>
                                <th class="px-3 py-3 text-left">Motor</th>
                                <th class="px-3 py-3 text-left">Admite</th>
                                <th class="px-3 py-3 text-center">Entradas</th>
                                <th class="px-3 py-3 text-center">Salidas</th>
                                <th class="px-3 py-3 text-center">En uso</th>
                                <th class="px-3 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-right">Creada</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($phaseTemplates as $fase)
                                @php $tono = $tonos[$fase->accent]; @endphp

                                <tr class="border-b border-slate-800/60 transition hover:bg-slate-900/60">

                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('tournaments.phase-templates.show', $fase) }}"
                                            class="flex items-center gap-2.5">

                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg border {{ $tono['borde'] }} {{ $tono['fondo'] }}">
                                                @if ($fase->image_url)
                                                    <img src="{{ $fase->image_url }}" alt="" class="h-full w-full object-cover">
                                                @else
                                                    <span class="text-[13px]">{{ $fase->display_icon }}</span>
                                                @endif
                                            </span>

                                            <span class="min-w-0">
                                                <span class="block truncate text-[12px] font-black text-slate-100">{{ $fase->name }}</span>
                                                <span class="block font-mono text-[9px] text-slate-600">{{ $fase->code }}</span>
                                            </span>
                                        </a>
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <span class="text-[11px] font-bold {{ $tono['texto'] }}">{{ $fase->type_label }}</span>
                                    </td>

                                    <td class="px-3 py-2.5 text-[11px] text-slate-400">
                                        {{ $fase->participant_mode_label }}
                                    </td>

                                    <td class="px-3 py-2.5 text-center font-mono text-[12px] font-black text-cyan-300">
                                        {{ $fase->input_gates_count }}
                                    </td>

                                    <td class="px-3 py-2.5 text-center font-mono text-[12px] font-black text-violet-300">
                                        {{ $fase->exits_count }}
                                    </td>

                                    <td class="px-3 py-2.5 text-center">
                                        <span @class([
                                            'font-mono text-[12px] font-black',
                                            'text-amber-300' => $fase->tournament_phase_nodes_count > 0,
                                            'text-slate-700' => $fase->tournament_phase_nodes_count === 0,
                                        ])>{{ $fase->tournament_phase_nodes_count ?: '—' }}</span>
                                    </td>

                                    <td class="px-3 py-2.5 text-center">
                                        <span class="rounded px-2 py-0.5 text-[9px] font-black uppercase {{ $estadoTono[$fase->status] ?? 'bg-slate-800 text-slate-500' }}">
                                            {{ $fase->status }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-2.5 text-right font-mono text-[10px] text-slate-600">
                                        {{ $fase->created_at->format('d/m/Y') }}
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                    </table>

                </div>


                <div class="mt-5">
                    {{ $phaseTemplates->links() }}
                </div>

            @endif

        </div>

    </div>

</x-tournament-layout>

@php
    /*
     * La comunidad del taller.
     *
     * Es una comunidad PROPIA, no la de la Biblioteca de entidades. Aquí solo
     * hay dos cosas —plantillas de torneo y plantillas de fase— y una sola
     * acción que importa: llevarse una a tu espacio para trabajarla.
     *
     * Los dos tipos se mezclan a propósito. Quien busca «una fase de grupos de
     * 16» y quien busca «una copa entera» está haciendo lo mismo: buscar una
     * pieza que le ahorre montarla. Separarlas en dos pantallas obligaría a
     * mirar dos veces.
     *
     * Cuatro maneras de mirar, las mismas que en las bibliotecas propias, con
     * un modo «detalle» que enseña el recorrido o las puertas antes de
     * llevarse nada: copiar algo sin saber qué hace por dentro es copiar un
     * problema.
     */

    use App\Models\TournamentTemplate;

    $tipos = [
        'all' => 'Torneos y fases',
        'tournaments' => 'Solo torneos',
        'phases' => 'Solo fases',
    ];

    $motores = [
        '' => 'Cualquier motor',
        'SINGLE_ELIMINATION' => 'Eliminación directa',
        'ROUND_ROBIN' => 'Todos contra todos',
        'GROUP_STAGE' => 'Fase de grupos',
        'SWISS' => 'Sistema suizo',
    ];

    $categorias = ['' => 'Cualquier tipo'] + TournamentTemplate::CATEGORIES;

    $ordenes = [
        'recent' => 'Recién publicadas',
        'popular' => 'Más copiadas',
        'views' => 'Más vistas',
        'complex' => 'Más completas',
        'name' => 'Nombre A → Z',
    ];

    $filtrando = $q !== '' || $kind !== 'all' || $engine !== '' || $category !== '' || $creator !== null;

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

    $finalTono = [
        'CHAMPION' => 'border-amber-500/30 bg-amber-500/5 text-amber-300',
        'QUALIFIED' => 'border-emerald-500/30 bg-emerald-500/5 text-emerald-300',
        'PLACEMENT' => 'border-sky-500/30 bg-sky-500/5 text-sky-300',
        'ELIMINATED' => 'border-slate-700 bg-slate-900 text-slate-500',
    ];

    $hayTorneos = $tournaments && $tournaments->count() > 0;
    $hayFases = $phases && $phases->count() > 0;
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">Comunidad del taller</x-slot>

    <div x-data="{
        view: 'grid',
        size: 3,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.community.view') ?? '{}');
                if (['grid', 'detail', 'list', 'table'].includes(g.view)) this.view = g.view;
                if ([2, 3, 4].includes(g.size)) this.size = g.size;
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('view', () => this.remember());
            this.$watch('size', () => this.remember());
        },

        remember() {
            try {
                localStorage.setItem('omnimerge.community.view',
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
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header
            class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-violet-950/40">

            <span class="pointer-events-none absolute -right-24 -top-28 h-72 w-72 rounded-full bg-violet-500/10 blur-3xl"></span>
            <span class="pointer-events-none absolute -bottom-32 left-1/4 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></span>

            <div class="relative flex flex-wrap items-end gap-5 px-5 py-5">

                <div class="min-w-0 flex-1">

                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-400">
                        Torneos · Comunidad
                    </p>

                    <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                        Lo que ha montado la gente
                    </h1>

                    <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                        Torneos completos y fases sueltas que otros han publicado. Ábrelos para ver
                        cómo están hechos por dentro y, si te sirven, llévatelos: la copia entra en
                        tu espacio como borrador privado y a partir de ahí es tuya.
                    </p>

                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ([['Torneos', $stats['tournaments'], 'text-amber-300'], ['Fases', $stats['phases'], 'text-cyan-300'], ['Se pueden copiar', $stats['clonable'], 'text-emerald-300'], ['Creadores', $stats['creators'], 'text-violet-300']] as [$etiqueta, $valor, $color])
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

            {{-- Si se está mirando a una persona, se dice --}}
            @if ($creator)
                <div class="relative flex flex-wrap items-center gap-3 border-t border-slate-800 bg-slate-950/40 px-5 py-3">
                    <x-user-avatar :user="$creator" size="sm" />

                    <span class="min-w-0 flex-1">
                        <span class="block text-[12px] font-black text-white">{{ $creator->name }}</span>
                        <span class="block text-[10px] text-slate-500">
                            Viendo solo lo que ha publicado {{ '@' . $creator->username }}
                        </span>
                    </span>

                    <a href="{{ route('tournaments.community.creator', $creator) }}"
                        class="rounded-lg border border-slate-700 px-3 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Su perfil →
                    </a>

                    <a href="{{ route('tournaments.community.index') }}"
                        class="rounded-lg border border-rose-500/30 px-3 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Ver de todos
                    </a>
                </div>
            @endif

        </header>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <div class="px-4 py-3">

                <form method="GET" action="{{ route('tournaments.community.index') }}"
                    class="flex flex-wrap items-center gap-2">

                    @if ($creator)
                        <input type="hidden" name="creator" value="{{ $creator->id }}">
                    @endif

                    <label class="relative min-w-[200px] flex-1">
                        <span class="sr-only">Buscar en la comunidad</span>

                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>

                        <input type="search" name="q" value="{{ $q }}"
                            placeholder="Buscar por nombre, código o descripción..."
                            class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    @foreach ([['kind', $tipos, $kind], ['engine', $motores, $engine], ['category', $categorias, $category], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                        <select name="{{ $campo }}" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach ($opciones as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($actual === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endforeach

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Buscar
                    </button>

                    @if ($filtrando)
                        <a href="{{ route('tournaments.community.index') }}"
                            class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                            Quitar filtros
                        </a>
                    @endif


                    <span class="ml-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                        @foreach ([['grid', 'cuadricula', 'Cuadrícula: la cara y lo esencial'], ['detail', 'grafo', 'Detalle: qué hace por dentro'], ['list', 'controles', 'Lista: una línea por pieza'], ['table', 'capas', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
                            <button type="button" @click="view = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="view === '{{ $modo }}'"
                                :class="view === '{{ $modo }}' ?
                                    'bg-violet-500 text-white' :
                                    'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

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


        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_300px] xl:items-start">

            <div class="space-y-5">

                @if (!$hayTorneos && !$hayFases)

                    <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                        <span class="inline-flex text-slate-700">
                            <x-omni-icon name="globo" size="h-10 w-10" />
                        </span>

                        <h2 class="mt-3 text-lg font-black text-white">
                            {{ $filtrando ? 'Nada encaja con eso' : 'Todavía no hay nada publicado' }}
                        </h2>

                        <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                            {{ $filtrando
                                ? 'Prueba a quitar algún filtro, o busca por otro motor.'
                                : 'Una plantilla aparece aquí cuando está activa, es pública y tiene fecha de publicación. Empieza tú.' }}
                        </p>

                        <a href="{{ $filtrando ? route('tournaments.community.index') : route('tournaments.creator.show') }}"
                            class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            {{ $filtrando ? 'Quitar los filtros' : 'Ir a mi panel de creador' }}
                        </a>
                    </div>

                @endif


                {{-- ===================================================== --}}
                {{-- LOS TORNEOS --}}
                {{-- ===================================================== --}}

                @if ($hayTorneos)
                    <section>

                        <div class="mb-2.5 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                                <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                            </span>

                            <h2 class="text-sm font-black text-white">Torneos completos</h2>

                            <span class="font-mono text-[11px] text-slate-600">
                                {{ $tournaments instanceof \Illuminate\Contracts\Pagination\Paginator ? $tournaments->total() : $tournaments->count() }}
                            </span>

                            @if ($kind === 'all' && $stats['tournaments'] > $tournaments->count())
                                <a href="{{ request()->fullUrlWithQuery(['kind' => 'tournaments']) }}"
                                    class="ml-auto text-[10px] font-black text-slate-500 transition hover:text-amber-300">
                                    Ver los {{ $stats['tournaments'] }} →
                                </a>
                            @endif
                        </div>

                        @include('tournaments.community.partials.collection', [
                            'items' => $tournaments,
                            'kind' => 'tournament',
                        ])

                        @if ($tournaments instanceof \Illuminate\Contracts\Pagination\Paginator)
                            <div class="mt-5">{{ $tournaments->links() }}</div>
                        @endif

                    </section>
                @endif


                {{-- ===================================================== --}}
                {{-- LAS FASES --}}
                {{-- ===================================================== --}}

                @if ($hayFases)
                    <section>

                        <div class="mb-2.5 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                                <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                            </span>

                            <h2 class="text-sm font-black text-white">Fases sueltas</h2>

                            <span class="font-mono text-[11px] text-slate-600">
                                {{ $phases instanceof \Illuminate\Contracts\Pagination\Paginator ? $phases->total() : $phases->count() }}
                            </span>

                            @if ($kind === 'all' && $stats['phases'] > $phases->count())
                                <a href="{{ request()->fullUrlWithQuery(['kind' => 'phases']) }}"
                                    class="ml-auto text-[10px] font-black text-slate-500 transition hover:text-cyan-300">
                                    Ver las {{ $stats['phases'] }} →
                                </a>
                            @endif
                        </div>

                        @include('tournaments.community.partials.collection', [
                            'items' => $phases,
                            'kind' => 'phase',
                        ])

                        @if ($phases instanceof \Illuminate\Contracts\Pagination\Paginator)
                            <div class="mt-5">{{ $phases->links() }}</div>
                        @endif

                    </section>
                @endif

            </div>


            {{-- ============================================================= --}}
            {{-- COLUMNA LATERAL --}}
            {{-- ============================================================= --}}

            <aside class="space-y-4">

                {{-- Quién publica --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Quién publica</p>

                    @if ($topCreators->isEmpty())
                        <p class="mt-2 text-[11px] leading-4 text-slate-600">
                            Nadie ha publicado todavía con el perfil visible.
                        </p>
                    @else
                        <ul class="mt-2.5 space-y-1">
                            @foreach ($topCreators as $persona)
                                <li>
                                    <a href="{{ route('tournaments.community.creator', $persona) }}"
                                        class="flex items-center gap-2.5 rounded-lg px-2 py-2 transition hover:bg-slate-950">

                                        <x-user-avatar :user="$persona" size="sm" />

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-slate-200">
                                                {{ $persona->name }}
                                            </span>
                                            <span class="block truncate text-[9px] text-slate-500">
                                                {{ $persona->public_tournaments_count }} torneos ·
                                                {{ $persona->public_phases_count }} fases
                                            </span>
                                        </span>

                                        <span class="shrink-0 text-slate-700">
                                            <x-omni-icon name="chevron-derecha" size="h-3 w-3" />
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </section>


                {{-- Cómo funciona esto --}}

                <section class="rounded-2xl border border-violet-500/30 bg-violet-500/5 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                        Qué pasa cuando te llevas una
                    </p>

                    <ol class="mt-2.5 space-y-2 text-[11px] leading-4 text-slate-400">
                        <li class="flex gap-2">
                            <span class="font-mono text-[10px] font-black text-violet-400">01</span>
                            <span>Se copia entera a tu espacio, con su estructura, sus puertas y sus salidas.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-mono text-[10px] font-black text-violet-400">02</span>
                            <span>Entra como <strong class="text-slate-200">borrador privado</strong>: no se
                                publica sola ni toca nada de lo que ya tienes.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-mono text-[10px] font-black text-violet-400">03</span>
                            <span>A partir de ahí es tuya: cámbiala sin que el original se entere.</span>
                        </li>
                    </ol>

                </section>


                {{-- Publicar lo tuyo --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Lo tuyo</p>

                    <p class="mt-2 text-[11px] leading-4 text-slate-500">
                        Para que una plantilla aparezca aquí tiene que estar activa, ser pública y
                        permitir la copia. El panel de creador dice cuáles se quedan a un paso.
                    </p>

                    <a href="{{ route('tournaments.creator.show') }}"
                        class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-violet-500 px-4 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400">
                        <x-omni-icon name="usuario" size="h-3.5 w-3.5" />
                        Mi panel de creador
                    </a>

                </section>


                {{-- Y el resto de OmniMerge --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Otras comunidades
                    </p>

                    <p class="mt-2 text-[11px] leading-4 text-slate-600">
                        Aquí solo hay torneos y fases. Las entidades, catálogos y colecciones tienen
                        la suya.
                    </p>

                    <a href="{{ route('community.index') }}"
                        class="mt-2.5 flex items-center gap-2.5 rounded-lg px-2 py-2 text-[11px] font-bold text-slate-400 transition hover:bg-slate-950 hover:text-white">
                        <x-omni-icon name="libro" size="h-4 w-4" />
                        <span class="flex-1">Comunidad de la Biblioteca</span>
                        <x-omni-icon name="chevron-derecha" size="h-3 w-3" />
                    </a>

                </section>

            </aside>

        </div>

    </div>

</x-tournament-layout>

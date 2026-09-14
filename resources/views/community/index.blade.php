@php
    /*
     * La comunidad de la biblioteca.
     *
     * Aquí se ve lo que otros han hecho público —entidades, colecciones,
     * atributos y valores de catálogo— y se copia a la biblioteca propia.
     *
     * Lo que faltaba y ahora se dice en todas partes:
     *
     *   · De quién es cada cosa, y de quién la copió esa persona. El dato
     *     («source_*_id») se guardaba desde el principio y no se enseñaba en
     *     ningún sitio, así que una cadena de tres copias parecía tres
     *     creaciones originales.
     *   · Si tú ya la copiaste. Sin eso, la comunidad invita a duplicar lo
     *     mismo una y otra vez.
     *   · Si su autor deja copiarla, dicho antes de pulsar y no después.
     */

    $pestanas = [
        'all' => ['Todo', 'panel', null],
        'entities' => ['Entidades', 'chispa', $statistics['entities']],
        'collections' => ['Colecciones', 'capas', $statistics['collections']],
        'attributes' => ['Atributos', 'controles', $statistics['attributes']],
        'catalogs' => ['Catálogos', 'cuadricula', $statistics['catalogs']],
        'creators' => ['Creadores', 'usuario', $statistics['creators']],
    ];

    /* Los filtros que hay que arrastrar al cambiar de pestaña o de página. */
    $comunes = array_filter([
        'search' => $search,
        'sort' => $sort,
        'creator' => $creator,
        'image' => $image,
        'cloning' => $cloning,
        'period' => $period,
        'per_page' => $perPage !== 24 ? $perPage : null,
    ]);

    $hayFiltros = $search || $creator || $image || $cloning || $period
        || $entityTypeId || $dataType || $multiple || $catalogState
        || $attributeId || $hierarchy || $usage || $collectionSize;

    /* Los modos de mirar de cada pestaña, con su forma especial al final. */
    $modos = [
        'entities' => [
            ['gallery', 'galeria', 'Galería: solo las caras'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada una'],
            ['table', 'controles', 'Tabla: para comparar y ver de quién viene'],
            ['special', 'usuario', 'Por creador: agrupadas por quien las hizo'],
        ],
        'collections' => [
            ['gallery', 'galeria', 'Galería: solo las portadas'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada una'],
            ['table', 'controles', 'Tabla: para comparar'],
            ['special', 'capas', 'Contenido: las caras de lo que hay dentro'],
        ],
        'attributes' => [
            ['gallery', 'galeria', 'Galería: solo las caras'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada uno'],
            ['table', 'controles', 'Tabla: para comparar'],
            ['special', 'capas', 'Valores: qué trae dentro cada catálogo'],
        ],
        'catalogs' => [
            ['gallery', 'galeria', 'Galería: solo las caras'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada uno'],
            ['table', 'controles', 'Tabla: para comparar'],
            ['special', 'grafo', 'Por catálogo: agrupados por a cuál pertenecen'],
        ],
    ];

    $ordenes = [
        'popular' => 'Lo más copiado',
        'newest' => 'Lo más nuevo',
        'oldest' => 'Lo más antiguo',
        'name_asc' => 'Nombre (A–Z)',
        'name_desc' => 'Nombre (Z–A)',
    ];
@endphp

<x-app-layout title="Comunidad" surface="dark">

    <x-slot name="header">Comunidad</x-slot>

    <div x-data="exploradorDeComunidad({ pestana: @js($tab) })" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">Biblioteca compartida</p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Comunidad</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Lo que otros han hecho público: entidades, colecciones, atributos y valores de catálogo.
                    Todo se puede copiar a tu biblioteca, y la copia recuerda de quién viene.
                </p>
            </div>

            <a href="{{ route('entities.index') }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                Mi biblioteca →
            </a>
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-[12px] font-bold text-amber-200">
                {{ session('warning') }}
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
        {{-- CÓMO FUNCIONA --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="globo" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Cómo funciona copiar
                    <span class="font-bold text-slate-500">— y por qué queda anotado de quién viene</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                <div class="grid gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">

                    <svg viewBox="0 0 300 140" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- La biblioteca de otro --}}
                        <rect x="6" y="40" width="76" height="56" rx="6" />
                        <circle cx="26" cy="58" r="7" opacity=".8" />
                        <path d="M40 55h32M40 63h20" opacity=".4" />
                        <rect x="16" y="74" width="14" height="14" rx="3" opacity=".7" />
                        <rect x="34" y="74" width="14" height="14" rx="3" opacity=".7" />
                        <rect x="52" y="74" width="14" height="14" rx="3" opacity=".7" />
                        <text x="44" y="34" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">Lo de otra persona</text>
                        <text x="44" y="108" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">público en la comunidad</text>

                        {{-- La copia --}}
                        <path d="M88 68h30M118 68l-7-5M118 68l-7 5" opacity=".8" />
                        <text x="103" y="60" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700">copiar</text>

                        {{-- Tu biblioteca --}}
                        <rect x="124" y="40" width="76" height="56" rx="6" stroke-width="2" />
                        <circle cx="144" cy="58" r="7" opacity=".8" />
                        <path d="M158 55h32M158 63h20" opacity=".4" />
                        <rect x="134" y="74" width="14" height="14" rx="3" opacity=".7" />
                        <rect x="152" y="74" width="14" height="14" rx="3" opacity=".7" />
                        <rect x="170" y="74" width="14" height="14" rx="3" opacity=".7" />
                        <text x="162" y="34" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">La tuya</text>
                        <text x="162" y="108" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">tuya para editar</text>

                        {{-- La atribución --}}
                        <path d="M206 68h30M236 68l-7-5M236 68l-7 5" opacity=".5" stroke-dasharray="4 3" />
                        <rect x="242" y="52" width="52" height="32" rx="5" stroke-dasharray="4 3" opacity=".8" />
                        <circle cx="254" cy="64" r="4" opacity=".7" />
                        <path d="M262 62h24M250 74h36" opacity=".35" />
                        <text x="268" y="46" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700">inspirado en…</text>

                        <path d="M6 122h288" opacity=".15" />
                        <text x="150" y="134" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".5">la copia es tuya, pero recuerda de dónde salió</text>
                    </svg>

                    <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            Al copiar algo se crea una <strong class="text-white">copia tuya</strong> en tu
                            biblioteca: la puedes editar, renombrar y borrar sin que le pase nada al
                            original.
                        </p>

                        <p>
                            La copia guarda de dónde salió, así que en tu ficha y en la comunidad aparece
                            <strong class="text-amber-300">«inspirado en @alguien»</strong>. Si esa persona a
                            su vez lo había copiado, la cadena se ve entera.
                        </p>

                        <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-400">
                            <strong class="text-slate-200">Copiar arrastra lo que hace falta.</strong> Una
                            colección se lleva sus entidades, un atributo de catálogo se lleva sus valores.
                            Por eso las fichas de aquí enseñan lo que traen dentro antes de que pulses.
                        </p>

                        <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            Lo que ya copiaste sale marcado con <span class="font-black text-emerald-400">✓
                                Ya lo tienes</span>, y el botón lleva a tu copia en vez de hacer otra.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- QUÉ HAY --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach (['entities' => ['Entidades', '#a78bfa'], 'collections' => ['Colecciones', '#22d3ee'], 'attributes' => ['Atributos', '#34d399'], 'catalogs' => ['Catálogos', '#fbbf24'], 'creators' => ['Creadores', '#fb7185']] as $clave => [$etiqueta, $tono])
                <a href="{{ route('community.index', array_merge($comunes, ['tab' => $clave])) }}"
                    class="rounded-xl border bg-slate-900/50 px-3 py-2 transition hover:-translate-y-0.5"
                    style="border-color: {{ $tab === $clave ? $tono : '#1e293b' }}">
                    <span class="block font-mono text-xl font-black"
                        style="color: {{ $statistics[$clave] > 0 ? $tono : '#475569' }}">
                        {{ $statistics[$clave] }}
                    </span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                </a>
            @endforeach
        </section>


        {{-- ===================================================== --}}
        {{-- PESTAÑAS --}}
        {{-- ===================================================== --}}

        <nav class="flex flex-wrap items-center gap-1.5">
            @foreach ($pestanas as $clave => [$etiqueta, $icono, $cuantos])
                <a href="{{ route('community.index', array_merge($comunes, ['tab' => $clave])) }}"
                    class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-[11px] font-black transition {{ $tab === $clave ? 'border-violet-500 bg-violet-500/15 text-white' : 'border-slate-800 bg-slate-900/50 text-slate-400 hover:border-slate-700 hover:text-slate-200' }}">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                    {{ $etiqueta }}
                    @if ($cuantos !== null)
                        <span class="font-mono text-[10px] {{ $tab === $clave ? 'text-violet-300' : 'text-slate-600' }}">
                            {{ $cuantos }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('community.index') }}"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar en la comunidad…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    @if ($tab !== 'creators')
                        <select name="creator" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Cualquier creador</option>
                            @foreach ($publicCreators as $unCreador)
                                <option value="{{ $unCreador->username }}" @selected($creator === $unCreador->username)>
                                    {{ '@' . $unCreador->username }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach ($ordenes as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    @if ($tab !== 'creators')
                        <select name="image" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['' => 'Con o sin imagen', 'yes' => 'Solo con imagen', 'no' => 'Sin imagen'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($image === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        <select name="period" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['' => 'De cualquier fecha', 'week' => 'De esta semana', 'month' => 'De este mes', 'year' => 'De este año'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($period === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Los que solo tienen sentido en una pestaña --}}

                    @if ($tab === 'entities')
                        <select name="entity_type" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Cualquier tipo</option>
                            @foreach ($entityTypes as $tipo)
                                <option value="{{ $tipo->id }}" @selected($entityTypeId === $tipo->id)>{{ $tipo->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($tab === 'collections')
                        <select name="collection_size" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['' => 'De cualquier tamaño', 'empty' => 'Vacías', 'small' => 'Pequeñas', 'medium' => 'Medianas', 'large' => 'Grandes'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($collectionSize === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($tab === 'attributes')
                        <select name="data_type" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['' => 'Cualquier tipo', 'OPTION' => 'Catálogo', 'TEXT' => 'Texto', 'LONG_TEXT' => 'Texto largo', 'INTEGER' => 'Número entero', 'DECIMAL' => 'Número decimal', 'BOOLEAN' => 'Sí o no', 'DATE' => 'Fecha', 'COLOR' => 'Color'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($dataType === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        <select name="catalog_state" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['' => 'Llenos o vacíos', 'filled' => 'Con valores', 'empty' => 'Catálogo vacío'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($catalogState === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($tab === 'catalogs')
                        <select name="attribute" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Cualquier catálogo</option>
                            @foreach ($publicAttributes as $unAtributo)
                                <option value="{{ $unAtributo->id }}" @selected($attributeId === $unAtributo->id)>
                                    {{ $unAtributo->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="hierarchy" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['' => 'Toda la jerarquía', 'root' => 'Sin padre', 'child' => 'Cuelgan de otro', 'has_children' => 'Tienen hijos'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($hierarchy === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endif

                    <select name="per_page" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach ([12, 24, 48, 96] as $cuantos)
                            <option value="{{ $cuantos }}" @selected($perPage === $cuantos)>{{ $cuantos }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Buscar
                    </button>

                    @if ($hayFiltros)
                        <a href="{{ route('community.index', ['tab' => $tab]) }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>


                @if (isset($modos[$tab]))
                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ($modos[$tab] as [$modo, $icono, $ayuda])
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
                @endif
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- COPIAR VARIAS A LA VEZ --}}
        {{-- ===================================================== --}}

        @auth
            @if ($tab === 'entities')
                <form method="POST" action="{{ route('community.entities.clone-many') }}"
                    x-show="seleccionadas.length > 0" x-cloak x-collapse
                    class="flex flex-wrap items-center gap-3 rounded-2xl border border-violet-500/40 bg-violet-500/10 px-4 py-2.5">
                    @csrf

                    <template x-for="id in seleccionadas" :key="'sel' + id">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>

                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500 font-mono text-[11px] font-black text-white"
                        x-text="seleccionadas.length"></span>

                    <p class="min-w-0 flex-1 text-[11px] font-bold text-violet-100">
                        <span x-text="seleccionadas.length === 1 ? 'entidad seleccionada' : 'entidades seleccionadas'"></span>.
                        <span class="font-normal text-violet-200/60">
                            Se copian a tu biblioteca anotando de quién viene cada una. Las que ya tengas se
                            saltan.
                        </span>
                    </p>

                    <div class="flex shrink-0 items-center gap-1.5">
                        <button type="submit"
                            class="rounded-xl bg-violet-500 px-3 py-1.5 text-[11px] font-black text-white transition hover:bg-violet-400">
                            Copiarlas todas
                        </button>

                        <button type="button" @click="seleccionadas = []"
                            class="rounded-xl px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:text-white">
                            Quitar selección
                        </button>
                    </div>
                </form>

                <p x-show="seleccionadas.length === 0" x-cloak
                    class="px-1 text-[10px] text-slate-600">
                    En la vista de lista puedes marcar varias entidades y copiarlas de una vez.
                </p>
            @endif
        @endauth


        {{-- ===================================================== --}}
        {{-- RESULTADOS --}}
        {{-- ===================================================== --}}

        @if ($tab === 'all')

            @include('community.partials.resumen-todo')

        @elseif ($tab === 'creators')

            @if ($creators->isEmpty())
                @include('community.partials.vacio', ['que' => 'creadores'])
            @else
                <p class="px-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                    {{ $creators->total() }} {{ $creators->total() === 1 ? 'creador' : 'creadores' }}
                </p>

                <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($creators as $unCreador)
                        @include('community.partials.tarjeta-creador', ['creador' => $unCreador])
                    @endforeach
                </div>

                <div>{{ $creators->links() }}</div>
            @endif

        @else

            @php
                $listado = match ($tab) {
                    'entities' => $entities,
                    'collections' => $collections,
                    'attributes' => $attributes,
                    'catalogs' => $catalogs,
                };

                $partial = match ($tab) {
                    'entities' => 'community.partials.resultados-entidades',
                    'collections' => 'community.partials.resultados-colecciones',
                    'attributes' => 'community.partials.resultados-atributos',
                    'catalogs' => 'community.partials.resultados-catalogos',
                };
            @endphp

            @if ($listado->isEmpty())
                @include('community.partials.vacio', ['que' => $pestanas[$tab][0]])
            @else
                <div class="flex flex-wrap items-center gap-3 px-1">
                    <p class="min-w-0 flex-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                        {{ $listado->total() }} {{ mb_strtolower($pestanas[$tab][0]) }}
                        @if ($creator)
                            · de {{ '@' . $creator }}
                        @endif
                    </p>
                </div>

                @include($partial, ['items' => $listado])

                <div>{{ $listado->links() }}</div>
            @endif

        @endif

    </div>


    <script>
        function exploradorDeComunidad(config) {

            return {

                pestana: config.pestana ?? 'all',

                vista: 'grid',
                tamano: 6,

                seleccionadas: [],

                init() {
                    /*
                     * La forma de mirar se recuerda por pestaña: no tiene
                     * sentido que elegir «tabla» en catálogos deje las entidades
                     * en tabla también.
                     */
                    try {
                        const guardado = JSON.parse(
                            localStorage.getItem('omnimerge.community.view') ?? '{}'
                        );

                        const mio = guardado[this.pestana] ?? {};

                        if (['gallery', 'grid', 'list', 'table', 'special'].includes(mio.vista)) {
                            this.vista = mio.vista;
                        }

                        if (mio.tamano >= 4 && mio.tamano <= 9) {
                            this.tamano = mio.tamano;
                        }
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        const guardado = JSON.parse(
                            localStorage.getItem('omnimerge.community.view') ?? '{}'
                        );

                        guardado[this.pestana] = {
                            vista: this.vista,
                            tamano: this.tamano,
                        };

                        localStorage.setItem(
                            'omnimerge.community.view',
                            JSON.stringify(guardado)
                        );
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

                /*
                 * Las fichas de colección y atributo son apaisadas y no caben en
                 * nueve columnas, así que su rejilla va dos pasos por debajo.
                 */
                get columnasAnchas() {
                    return {
                        4: 'sm:grid-cols-2',
                        5: 'sm:grid-cols-2 lg:grid-cols-3',
                        6: 'sm:grid-cols-2 lg:grid-cols-3',
                        7: 'sm:grid-cols-3 lg:grid-cols-4',
                        8: 'sm:grid-cols-3 lg:grid-cols-4',
                        9: 'sm:grid-cols-4 lg:grid-cols-5',
                    }[this.tamano];
                },
            };
        }
    </script>

</x-app-layout>

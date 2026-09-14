@php
    /*
     * La biblioteca de valores de catálogo.
     *
     * Lo que había: una rejilla clara con cuatro formas de mirar y un montón de
     * filtros. Correcto, y con dos agujeros de fondo:
     *
     *   · Enseñaba quinientos valores en fila sin decir nunca a qué catálogo
     *     pertenece cada montón ni cuál está a medio hacer.
     *   · Un valor sin imagen es invisible en todas las pantallas que eligen por
     *     la cara —el constructor de reglas, las dependencias de catálogo, el
     *     selector de una entidad— y aquí no se distinguía de los demás.
     *
     * Ahora hay una vista de **catálogos** que resume cada uno con las caras de
     * los suyos, una de **jerarquía** que enseña cuál cuelga de cuál, y los que
     * no tienen imagen se marcan en rojo y se pueden filtrar de una.
     *
     * Y se pueden archivar o reactivar sin abrir la ficha, de uno en uno o en
     * lote: entrar y salir de un formulario para tocar un desplegable es el
     * trabajo que nadie hace, y por eso los catálogos acumulan valores muertos.
     */

    $tonoEstado = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $estadoEtiqueta = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
        'ARCHIVED' => 'Archivado',
    ];

    /* Los de esta página, repartidos por catálogo, para los modos agrupados. */
    $agrupados = $options->getCollection()->groupBy('attribute_id');

    $hayFiltros = $search || $attributeId || $status || $image || $hierarchy || $usage;

    $sinCara = $stats['sin_imagen'] ?? 0;
@endphp

<x-app-layout title="Catálogos" surface="dark">

    <x-slot name="header">Atributos</x-slot>

    <div x-data="bibliotecaDeValores({
        rutaRapida: @js(route('attribute-options.quick', ['attributeOption' => '__ID__'])),
    })" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('attributes.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Atributos
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Catálogos</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Los valores que se pueden elegir: los clanes, las aldeas, los elementos. Cada uno
                    pertenece a un atributo y cada uno tiene su cara.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('attributes.structure.index') }}"
                    class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-cyan-500 hover:text-cyan-300">
                    <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                    Estructura
                </a>

                @can('create', App\Models\AttributeOption::class)
                    <a href="{{ route('attribute-options.create', $attributeId ? ['attribute' => $attributeId] : []) }}"
                        class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                        Nuevo valor
                    </a>
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
        {{-- QUÉ ES ESTO --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="capas" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Qué es un valor de catálogo
                    <span class="font-bold text-slate-500">— y por qué su imagen no es un adorno</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">

                    <svg viewBox="0 0 260 126" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- El catálogo --}}
                        <rect x="6" y="40" width="62" height="42" rx="5" stroke-dasharray="5 4" />
                        <path d="M16 52h42M16 62h42M16 72h26" opacity=".45" />
                        <text x="37" y="34" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">El catálogo</text>
                        <text x="37" y="94" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">«Clan»</text>

                        {{-- Sus valores, con cara --}}
                        <path d="M74 61h18M92 61l-6-4M92 61l-6 4" opacity=".7" />
                        <rect x="98" y="34" width="46" height="16" rx="3" opacity=".9" />
                        <rect x="98" y="53" width="46" height="16" rx="3" opacity=".9" />
                        <rect x="98" y="72" width="46" height="16" rx="3" opacity=".35" stroke-dasharray="3 3" />
                        <circle cx="108" cy="42" r="4" opacity=".8" />
                        <circle cx="108" cy="61" r="4" opacity=".8" />
                        <circle cx="108" cy="80" r="4" opacity=".3" />
                        <text x="121" y="26" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">Sus valores</text>
                        <text x="121" y="100" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">el de puntos, sin imagen</text>

                        {{-- La entidad que se queda con uno --}}
                        <path d="M150 61h18M168 61l-6-4M168 61l-6 4" opacity=".7" />
                        <rect x="174" y="34" width="80" height="54" rx="6" />
                        <circle cx="194" cy="52" r="9" opacity=".7" />
                        <path d="M210 46h34" opacity=".4" />
                        <circle cx="190" cy="72" r="4" opacity=".8" />
                        <path d="M199 72h26" opacity=".5" />
                        <text x="214" y="26" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">La entidad</text>
                        <text x="214" y="100" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">Naruto · Clan = Uzumaki</text>

                        <path d="M6 112h248" opacity=".15" />
                        <text x="130" y="122" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".5">un valor sin cara no se puede reconocer al elegirlo</text>
                    </svg>

                    <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            Un <strong class="text-white">valor de catálogo</strong> es una de las opciones
                            que se podrán elegir al rellenar un atributo: Uzumaki dentro de «Clan», Konoha
                            dentro de «Aldea». Pertenece a un atributo y solo a uno.
                        </p>

                        <p>
                            Pueden <strong class="text-violet-300">colgar unos de otros</strong> —una aldea
                            dentro de un país—, y esa jerarquía se ve en la vista del mismo nombre. No la
                            tienen los atributos: la tienen sus valores.
                        </p>

                        <p class="rounded-xl border border-rose-500/25 bg-rose-500/5 px-3 py-2 text-[10px] text-rose-200/80">
                            <strong class="text-rose-200">La imagen no es decoración.</strong> Todas las
                            pantallas donde se elige un valor —el constructor de reglas, las dependencias
                            entre catálogos, el formulario de una entidad— lo enseñan por su cara. Un valor
                            sin imagen aparece como un cuadro vacío y no hay forma de reconocerlo.
                        </p>

                        <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            Archivar un valor no lo borra: deja de poder elegirse, pero las entidades que ya
                            lo llevaban lo conservan.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">

            @foreach ([['Valores', $stats['total'], null, '#a78bfa'], ['Catálogos', $stats['catalogs'], null, '#22d3ee'], ['Activos', $stats['active'], ['status' => 'ACTIVE'], '#34d399'], ['Se usan', $stats['used'], ['usage' => 'used'], '#fbbf24'], ['Con padre', $stats['hierarchical'], ['hierarchy' => 'child'], '#818cf8'], ['Sin imagen', $sinCara, ['image' => 'no'], '#fb7185']] as [$etiqueta, $valor, $filtro, $tono])

                @if ($filtro)
                    <a href="{{ route('attribute-options.index', $filtro) }}"
                        class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 transition hover:border-slate-700">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </a>
                @else
                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </div>
                @endif
            @endforeach
        </section>


        {{-- El aviso que de verdad hay que atender --}}
        @if ($sinCara > 0 && $image !== 'no')
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-rose-500/25 bg-rose-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                    <x-omni-icon name="galeria" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-rose-200/80">
                    <strong class="text-rose-200">{{ $sinCara }}</strong>
                    {{ $sinCara === 1 ? 'valor no tiene imagen' : 'valores no tienen imagen' }}.
                    En las pantallas donde se elige por la cara
                    {{ $sinCara === 1 ? 'aparecerá' : 'aparecerán' }} como un cuadro vacío.
                </p>

                <a href="{{ route('attribute-options.index', ['image' => 'no']) }}"
                    class="shrink-0 rounded-xl border border-rose-500/40 px-2.5 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                    Verlos →
                </a>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('attribute-options.index') }}"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar valor</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre, código o descripción…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <select name="attribute" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Todos los catálogos</option>
                        @foreach ($attributes as $catalogo)
                            <option value="{{ $catalogo->id }}" @selected($attributeId === $catalogo->id)>
                                {{ $catalogo->name }} ({{ $catalogo->options_count }})
                            </option>
                        @endforeach
                    </select>

                    <select name="status" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Cualquier estado', 'ACTIVE' => 'Activos', 'INACTIVE' => 'Inactivos', 'ARCHIVED' => 'Archivados'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $status === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="image" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Con o sin imagen', 'yes' => 'Con imagen', 'no' => 'Sin imagen'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $image === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="hierarchy" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Toda la jerarquía', 'root' => 'Sin padre', 'child' => 'Cuelgan de otro', 'has_children' => 'Tienen hijos'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $hierarchy === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="usage" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Se usen o no', 'used' => 'Los que se usan', 'unused' => 'Los que no usa nadie'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $usage === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['manual' => 'Orden del catálogo', 'name_asc' => 'Nombre (A–Z)', 'name_desc' => 'Nombre (Z–A)', 'usage_desc' => 'Más usados', 'usage_asc' => 'Menos usados', 'children_desc' => 'Con más hijos', 'children_asc' => 'Con menos hijos', 'code_asc' => 'Código (asc.)', 'code_desc' => 'Código (desc.)', 'newest' => 'Los más nuevos', 'oldest' => 'Los más antiguos'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

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
                        <a href="{{ route('attribute-options.index') }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>


                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['catalogs', 'capas', 'Catálogos: uno por catálogo, con las caras de los suyos'], ['gallery', 'galeria', 'Galería: solo las caras'], ['grid', 'cuadricula', 'Cuadrícula: con su catálogo y su uso'], ['list', 'menu', 'Lista: una línea por valor'], ['table', 'controles', 'Tabla: para comparar'], ['tree', 'grafo', 'Jerarquía: cuál cuelga de cuál']] as [$modo, $icono, $ayuda])
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

                <button type="button" x-show="['gallery', 'grid', 'list'].includes(vista)" x-cloak
                    @click="agrupar = !agrupar"
                    :class="agrupar ? 'border-cyan-500 text-cyan-300' : 'border-slate-800 text-slate-500'"
                    class="rounded-xl border bg-slate-950 px-2.5 py-2 text-[10px] font-black transition"
                    title="Separar los valores por el catálogo al que pertenecen">
                    Agrupar
                </button>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- LO SELECCIONADO --}}
        {{-- ===================================================== --}}

        {{--
            Cambiar el estado de varios valores a la vez. Los ids se emiten una
            sola vez desde la selección: si cada modo de vista llevara los suyos,
            el mismo id viajaría seis veces.
        --}}

        <form method="POST" action="{{ route('attribute-options.bulk') }}"
            x-show="seleccionadas.length > 0" x-cloak x-collapse
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-violet-500/40 bg-violet-500/10 px-4 py-2.5">
            @csrf

            <template x-for="id in seleccionadas" :key="'sel' + id">
                <input type="hidden" name="ids[]" :value="id">
            </template>

            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500 font-mono text-[11px] font-black text-white"
                x-text="seleccionadas.length"></span>

            <p class="min-w-0 flex-1 text-[11px] font-bold text-violet-100">
                <span x-text="seleccionadas.length === 1 ? 'valor seleccionado' : 'valores seleccionados'"></span>.
                <span class="font-normal text-violet-200/60">
                    Archivar no borra nada: deja de poder elegirse y las entidades que ya lo llevaban lo
                    conservan.
                </span>
            </p>

            {{--
                Cada botón lleva su propio `name`/`value`: el navegador manda el
                del que se pulsa. Así el estado no depende de que una escritura
                de Alpine llegue al DOM antes del envío.

                Las clases van escritas enteras a propósito: Tailwind lee el
                código fuente, y una clase compuesta —«border-rose-500» armada a
                trozos— no existiría en el CSS generado.
            --}}

            <div class="flex shrink-0 flex-wrap items-center gap-1.5">

                <button type="submit" name="status" value="ACTIVE"
                    class="rounded-xl border border-emerald-500/40 px-3 py-1.5 text-[10px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-white">
                    Activar
                </button>

                <button type="submit" name="status" value="INACTIVE"
                    class="rounded-xl border border-amber-500/40 px-3 py-1.5 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-white">
                    Desactivar
                </button>

                <button type="submit" name="status" value="ARCHIVED"
                    class="rounded-xl border border-rose-500/40 px-3 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                    Archivar
                </button>

                <button type="button" @click="seleccionadas = []"
                    class="rounded-xl px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:text-white">
                    Quitar selección
                </button>
            </div>
        </form>


        {{--
            El formulario que ejecuta un cambio suelto desde una ficha. Uno solo
            para toda la página: uno por valor serían cientos. La acción y el
            estado se escriben en el DOM a mano justo antes de enviar, sin pasar
            por una vinculación reactiva que podría no haber llegado todavía.
        --}}

        <form method="POST" x-ref="formRapido" action="" class="hidden">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="">
        </form>


        {{-- ===================================================== --}}
        {{-- CATÁLOGOS --}}
        {{-- ===================================================== --}}

        {{--
            La vista que faltaba. Quinientos valores en fila no dicen de qué está
            hecha la biblioteca; un catálogo por tarjeta, con las caras de los
            suyos y lo que le falta, sí.

            Se monta con TODOS los valores, no con los de esta página: un resumen
            calculado sobre una página es un resumen equivocado.
        --}}

        <section x-show="vista === 'catalogs'" x-cloak class="space-y-3">

            <p class="text-[10px] leading-relaxed text-slate-500">
                Un catálogo por tarjeta, con las caras de los suyos. Las cifras son de la biblioteca
                entera, no de esta página, y los filtros de arriba no se les aplican.
            </p>

            @if ($resumenCatalogos->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-800 py-10 text-center text-[11px] text-slate-600">
                    Todavía no hay ningún catálogo. Un atributo de tipo catálogo es el que puede tener
                    valores.
                </p>
            @else
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($resumenCatalogos as $resumen)
                        @php
                            $cat = $resumen['atributo'];
                            $tono = $cat->color ?: '#6366f1';
                            $porcentaje = $resumen['total'] > 0
                                ? (int) round(($resumen['con_imagen'] / $resumen['total']) * 100)
                                : 0;
                        @endphp

                        <article class="overflow-hidden rounded-2xl border bg-slate-900/50 transition hover:-translate-y-0.5"
                            style="border-color: {{ $resumen['total'] === 0 ? '#f43f5e40' : $tono . '40' }}">

                            <div class="flex items-center gap-2.5 border-b border-slate-800 p-3">

                                @include('attributes.partials.cara', [
                                    'cosa' => $cat,
                                    'tamano' => 'h-10 w-10',
                                    'respaldo' => '◫',
                                    'tono' => $tono,
                                ])

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('attributes.show', $cat) }}"
                                        class="block truncate text-[13px] font-black text-white transition hover:underline">
                                        {{ $cat->name }}
                                    </a>
                                    <p class="truncate font-mono text-[9px] text-slate-600">{{ $cat->code }}</p>
                                </div>

                                <span class="shrink-0 rounded-xl border px-2.5 py-1 font-mono text-[13px] font-black"
                                    style="border-color: {{ $tono }}40; color: {{ $resumen['total'] > 0 ? $tono : '#f43f5e' }}">
                                    {{ $resumen['total'] }}
                                </span>
                            </div>

                            @if ($resumen['caras']->isNotEmpty())
                                <div class="flex gap-1 p-2">
                                    @foreach ($resumen['caras'] as $cara)
                                        <a href="{{ route('attribute-options.show', $cara->id) }}"
                                            title="{{ $cara->name }}"
                                            class="block aspect-square min-w-0 flex-1 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            <img src="{{ $cara->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @elseif ($resumen['total'] > 0)
                                <p class="px-3 py-3 text-center text-[10px] text-rose-300/70">
                                    Ninguno de sus valores tiene imagen.
                                </p>
                            @else
                                <p class="px-3 py-3 text-center text-[10px] text-slate-600">
                                    Catálogo vacío: no se puede asignar a ninguna entidad.
                                </p>
                            @endif

                            @if ($resumen['total'] > 0)
                                <div class="px-3 pb-2">
                                    <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        <span>Con imagen</span>
                                        <span style="color: {{ $porcentaje === 100 ? '#34d399' : ($porcentaje >= 50 ? '#fbbf24' : '#fb7185') }}">
                                            {{ $resumen['con_imagen'] }}/{{ $resumen['total'] }}
                                        </span>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-950">
                                        <div class="h-full rounded-full transition-all"
                                            style="width: {{ max($porcentaje, 2) }}%; background-color: {{ $porcentaje === 100 ? '#34d399' : ($porcentaje >= 50 ? '#fbbf24' : '#fb7185') }}"></div>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-3 gap-px border-t border-slate-800 bg-slate-800">
                                @foreach ([['Se usan', $resumen['usados']], ['Con padre', $resumen['con_padre']], ['Activos', $resumen['activos']]] as [$etiqueta, $numero])
                                    <div class="bg-slate-900/50 px-2 py-1.5 text-center">
                                        <span class="block font-mono text-[13px] font-black {{ $numero > 0 ? 'text-slate-200' : 'text-slate-700' }}">
                                            {{ $numero }}
                                        </span>
                                        <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                            {{ $etiqueta }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">
                                <a href="{{ route('attribute-options.index', ['attribute' => $cat->id]) }}"
                                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
                                    Ver sus valores
                                </a>

                                <a href="{{ route('attribute-options.create', ['attribute' => $cat->id]) }}"
                                    class="ml-auto rounded-lg px-2 py-1 text-[10px] font-black transition"
                                    style="color: {{ $tono }}">
                                    + Añadir
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>


        {{-- ===================================================== --}}
        {{-- JERARQUÍA --}}
        {{-- ===================================================== --}}

        <section x-show="vista === 'tree'" x-cloak class="space-y-3">

            @if ($stats['hierarchical'] === 0)
                <p class="rounded-2xl border border-dashed border-slate-800 py-10 text-center text-[11px] leading-relaxed text-slate-600">
                    Ningún valor cuelga de otro todavía. La jerarquía se monta al crear o editar un valor,
                    eligiendo de cuál cuelga.
                </p>
            @else
                <p class="text-[10px] leading-relaxed text-slate-500">
                    El árbol se monta con la biblioteca entera, no con esta página: un árbol partido por la
                    paginación no es un árbol. Los filtros de arriba no se le aplican.
                </p>

                <div class="grid gap-3 lg:grid-cols-2">
                    @foreach ($attributes as $catalogo)
                        @php
                            $suyos = $valoresPorCatalogo->get($catalogo->id, collect());
                            $raices = $suyos->whereNull('parent_option_id');
                            $tono = $catalogo->color ?: '#6366f1';
                        @endphp

                        @continue($suyos->isEmpty())

                        <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
                            style="border-color: {{ $tono }}40">

                            <div class="flex items-center gap-2.5 border-b border-slate-800 px-3 py-2">
                                @include('attributes.partials.cara', [
                                    'cosa' => $catalogo,
                                    'tamano' => 'h-8 w-8',
                                    'respaldo' => '◫',
                                    'tono' => $tono,
                                ])

                                <a href="{{ route('attributes.show', $catalogo) }}"
                                    class="min-w-0 flex-1 truncate text-[12px] font-black text-white transition hover:underline">
                                    {{ $catalogo->name }}
                                </a>

                                <span class="shrink-0 font-mono text-[10px] font-black" style="color: {{ $tono }}">
                                    {{ $suyos->count() }}
                                </span>
                            </div>

                            <div class="space-y-1.5 p-3">
                                @foreach ($raices as $raiz)
                                    @include('attribute-options.partials.branch', [
                                        'nodo' => $raiz,
                                        'nivel' => 0,
                                        'todos' => $suyos,
                                        'acento' => $tono,
                                        'usos' => $usoPorValor,
                                    ])
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </section>


        {{-- ===================================================== --}}
        {{-- LO QUE HAY EN ESTA PÁGINA --}}
        {{-- ===================================================== --}}

        @if ($options->isEmpty())

            <section x-show="!['catalogs', 'tree'].includes(vista)" x-cloak
                class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

                <span class="inline-flex text-slate-700"><x-omni-icon name="capas" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">
                    {{ $hayFiltros ? 'Ningún valor encaja con lo que has filtrado' : 'Todavía no hay valores' }}
                </p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Prueba a quitar algún filtro.
                    @else
                        Un valor de catálogo es una de las opciones que se podrán elegir al rellenar un
                        atributo: los clanes de «Clan», las aldeas de «Aldea».
                    @endif
                </p>

                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @if ($hayFiltros)
                        <a href="{{ route('attribute-options.index') }}"
                            class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Quitar filtros
                        </a>
                    @endif

                    @can('create', App\Models\AttributeOption::class)
                        <a href="{{ route('attribute-options.create') }}"
                            class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            Crear el primero
                        </a>
                    @endcan
                </div>
            </section>

        @else

            {{-- Cuántos hay, y el seleccionar todo --}}
            <div x-show="!['catalogs', 'tree'].includes(vista)" x-cloak
                class="flex flex-wrap items-center gap-3 px-1">

                <p class="min-w-0 flex-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                    {{ $options->total() }} {{ $options->total() === 1 ? 'valor' : 'valores' }}
                    @if ($selectedAttribute)
                        · en {{ $selectedAttribute->name }}
                    @endif
                </p>

                <button type="button" @click="alternarTodos()"
                    class="rounded-lg border border-slate-800 px-2.5 py-1 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300"
                    x-text="seleccionadas.length === idsDeLaPagina.length ? 'Quitar selección' : 'Seleccionar los ' + idsDeLaPagina.length + ' de esta página'">
                </button>
            </div>


            {{-- ---------- GALERÍA ---------- --}}

            <section x-show="vista === 'gallery'" x-cloak class="space-y-4">

                <div x-show="! agrupar" class="grid gap-2" :class="columnas">
                    @foreach ($options as $valor)
                        <a href="{{ route('attribute-options.show', $valor) }}" title="{{ $valor->name }}"
                            class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                            style="border-color: {{ $valor->image_url ? '#1e293b' : '#f43f5e40' }}">
                            <span class="block aspect-square overflow-hidden bg-slate-900">
                                @if ($valor->image_url)
                                    <img src="{{ $valor->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl text-rose-500/40">
                                        {{ $valor->icon ?: '◇' }}
                                    </span>
                                @endif
                            </span>
                            <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300">
                                {{ $valor->name }}
                            </span>
                        </a>
                    @endforeach
                </div>

                <div x-show="agrupar" x-cloak class="space-y-4">
                    @foreach ($agrupados as $idCatalogo => $delCatalogo)
                        @php
                            $cat = $delCatalogo->first()->attribute;
                            $tono = $cat?->color ?: '#6366f1';
                        @endphp

                        <div>
                            <div class="mb-2 flex items-center gap-2">
                                @include('attributes.partials.cara', [
                                    'cosa' => $cat,
                                    'tamano' => 'h-7 w-7',
                                    'respaldo' => '◫',
                                    'tono' => $tono,
                                ])
                                <h3 class="text-[12px] font-black text-white">{{ $cat?->name ?? 'Sin catálogo' }}</h3>
                                <span class="font-mono text-[10px] font-black" style="color: {{ $tono }}">
                                    {{ $delCatalogo->count() }}
                                </span>
                                <span class="h-px min-w-0 flex-1" style="background-color: {{ $tono }}30"></span>
                            </div>

                            <div class="grid gap-2" :class="columnas">
                                @foreach ($delCatalogo as $valor)
                                    <a href="{{ route('attribute-options.show', $valor) }}" title="{{ $valor->name }}"
                                        class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                                        style="border-color: {{ $valor->image_url ? '#1e293b' : '#f43f5e40' }}">
                                        <span class="block aspect-square overflow-hidden bg-slate-900">
                                            @if ($valor->image_url)
                                                <img src="{{ $valor->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-xl text-rose-500/40">
                                                    {{ $valor->icon ?: '◇' }}
                                                </span>
                                            @endif
                                        </span>
                                        <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300">
                                            {{ $valor->name }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>


            {{-- ---------- CUADRÍCULA ---------- --}}

            <section x-show="vista === 'grid'" class="space-y-4">

                <div x-show="! agrupar" class="grid gap-2.5" :class="columnas">
                    @foreach ($options as $valor)
                        @include('attribute-options.partials.library-card', [
                            'valor' => $valor,
                            'tonoEstado' => $tonoEstado,
                            'estadoEtiqueta' => $estadoEtiqueta,
                        ])
                    @endforeach
                </div>

                <div x-show="agrupar" x-cloak class="space-y-4">
                    @foreach ($agrupados as $idCatalogo => $delCatalogo)
                        @php
                            $cat = $delCatalogo->first()->attribute;
                            $tono = $cat?->color ?: '#6366f1';
                        @endphp

                        <div>
                            <div class="mb-2 flex items-center gap-2">
                                @include('attributes.partials.cara', [
                                    'cosa' => $cat,
                                    'tamano' => 'h-7 w-7',
                                    'respaldo' => '◫',
                                    'tono' => $tono,
                                ])
                                <h3 class="text-[12px] font-black text-white">{{ $cat?->name ?? 'Sin catálogo' }}</h3>
                                <span class="font-mono text-[10px] font-black" style="color: {{ $tono }}">
                                    {{ $delCatalogo->count() }}
                                </span>
                                <span class="h-px min-w-0 flex-1" style="background-color: {{ $tono }}30"></span>
                            </div>

                            <div class="grid gap-2.5" :class="columnas">
                                @foreach ($delCatalogo as $valor)
                                    @include('attribute-options.partials.library-card', [
                                        'valor' => $valor,
                                        'tonoEstado' => $tonoEstado,
                                        'estadoEtiqueta' => $estadoEtiqueta,
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>


            {{-- ---------- LISTA ---------- --}}

            {{--
                Se pinta dos veces sobre la misma pieza: seguida —respetando el
                orden que se haya elegido arriba— y separada por catálogo. Si la
                lista sin agrupar se sacara de los grupos, el orden elegido se
                perdería sin que nadie lo dijera.
            --}}

            <section x-show="vista === 'list'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div x-show="! agrupar">
                    @foreach ($options as $valor)
                        @include('attribute-options.partials.list-row', [
                            'valor' => $valor,
                            'tonoEstado' => $tonoEstado,
                            'estadoEtiqueta' => $estadoEtiqueta,
                        ])
                    @endforeach
                </div>

                <div x-show="agrupar" x-cloak>
                    @foreach ($agrupados as $idCatalogo => $delCatalogo)
                        @php
                            $cat = $delCatalogo->first()->attribute;
                            $tono = $cat?->color ?: '#6366f1';
                        @endphp

                        <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-1.5">
                            @include('attributes.partials.cara', [
                                'cosa' => $cat,
                                'tamano' => 'h-6 w-6',
                                'respaldo' => '▫',
                                'tono' => $tono,
                            ])
                            <span class="text-[11px] font-black text-white">{{ $cat?->name ?? 'Sin catálogo' }}</span>
                            <span class="font-mono text-[10px] font-black" style="color: {{ $tono }}">{{ $delCatalogo->count() }}</span>
                        </div>

                        @foreach ($delCatalogo as $valor)
                            @include('attribute-options.partials.list-row', [
                                'valor' => $valor,
                                'tonoEstado' => $tonoEstado,
                                'estadoEtiqueta' => $estadoEtiqueta,
                            ])
                        @endforeach
                    @endforeach
                </div>
            </section>


            {{-- ---------- TABLA ---------- --}}

            <section x-show="vista === 'table'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Valor</th>
                                <th class="px-3 py-2.5">Catálogo</th>
                                <th class="px-3 py-2.5">Cuelga de</th>
                                <th class="px-3 py-2.5 text-right">Hijos</th>
                                <th class="px-3 py-2.5 text-right">Usos</th>
                                <th class="px-3 py-2.5">Imagen</th>
                                <th class="px-3 py-2.5">Estado</th>
                                <th class="px-3 py-2.5"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($options as $valor)
                                @php $tonoValor = $valor->color ?: ($valor->attribute?->color ?: '#6366f1'); @endphp

                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2">
                                        <a href="{{ route('attribute-options.show', $valor) }}" class="flex items-center gap-2">
                                            @include('attributes.partials.cara', [
                                                'cosa' => $valor,
                                                'tamano' => 'h-8 w-8',
                                                'respaldo' => '◇',
                                                'tono' => $tonoValor,
                                            ])
                                            <span class="min-w-0">
                                                <span class="block truncate text-[12px] font-black text-white">{{ $valor->name }}</span>
                                                <span class="block font-mono text-[9px] text-slate-600">{{ $valor->code }}</span>
                                            </span>
                                        </a>
                                    </td>

                                    <td class="px-3 py-2 text-[11px]" style="color: {{ $tonoValor }}">
                                        {{ $valor->attribute?->name ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-500">{{ $valor->parent?->name ?? '—' }}</td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $valor->children_count > 0 ? 'text-slate-300' : 'text-slate-700' }}">
                                        {{ $valor->children_count }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px]"
                                        style="color: {{ $valor->values_count > 0 ? $tonoValor : '#475569' }}">
                                        {{ $valor->values_count }}
                                    </td>

                                    <td class="px-3 py-2">
                                        @if ($valor->image_url)
                                            <span class="text-[11px] text-emerald-400">sí</span>
                                        @else
                                            <span class="text-[11px] font-black text-rose-400">no</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2">
                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$valor->status] ?? 'bg-slate-800 text-slate-500' }}">
                                            {{ $estadoEtiqueta[$valor->status] ?? $valor->status }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-2 text-right">
                                        <a href="{{ route('attribute-options.show', $valor) }}"
                                            class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">Ver →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>


            {{-- ---------- PAGINACIÓN ---------- --}}

            <div x-show="!['catalogs', 'tree'].includes(vista)" x-cloak>
                {{ $options->links() }}
            </div>

        @endif

    </div>


    <script>
        function bibliotecaDeValores(config) {

            return {

                vista: 'grid',
                tamano: 6,
                agrupar: false,

                seleccionadas: [],

                idsDeLaPagina: @js($options->pluck('id')->map(fn($id) => (int) $id)->values()),

                init() {
                    try {
                        const guardado = JSON.parse(
                            localStorage.getItem('omnimerge.catalogValues.view') ?? '{}'
                        );

                        if (['catalogs', 'gallery', 'grid', 'list', 'table', 'tree'].includes(guardado.vista)) {
                            this.vista = guardado.vista;
                        }

                        if (guardado.tamano >= 4 && guardado.tamano <= 9) {
                            this.tamano = guardado.tamano;
                        }

                        this.agrupar = guardado.agrupar === true;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                    this.$watch('agrupar', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem(
                            'omnimerge.catalogValues.view',
                            JSON.stringify({
                                vista: this.vista,
                                tamano: this.tamano,
                                agrupar: this.agrupar,
                            })
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

                alternarTodos() {
                    this.seleccionadas =
                        this.seleccionadas.length === this.idsDeLaPagina.length
                            ? []
                            : [...this.idsDeLaPagina];
                },

                cambiarEstado(id, estado) {
                    const formulario = this.$refs.formRapido;

                    formulario.action = config.rutaRapida.replace('__ID__', id);
                    formulario.querySelector('[name="status"]').value = estado;

                    formulario.submit();
                },
            };
        }
    </script>

</x-app-layout>

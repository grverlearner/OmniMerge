@php
    /*
     * La ficha de un atributo.
     *
     * Lo que tenía: la cabecera, unas cifras, sus interruptores y el catálogo
     * de valores con su explorador. Todo correcto, y todo mudo sobre las dos
     * preguntas que de verdad se hacen al abrir un atributo:
     *
     *   ¿esto lo usa alguien?      → «17 entidades» sin enseñar ninguna
     *   ¿qué valores se usan?      → una lista alfabética de cincuenta clanes
     *
     * Ahora las dos se contestan con caras: quién lo usa, y el ranking de los
     * valores que sostienen el catálogo frente a los que no ha tocado nadie.
     *
     * Y el explorador gana una vista de **jerarquía**: los valores de un
     * catálogo pueden colgar unos de otros —una aldea dentro de un país— y eso
     * no se veía en ninguna de las tres vistas que había.
     */

    $acento = $attribute->color ?: '#6366f1';

    $esCatalogo = $attribute->usesCatalog();

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

    $interruptores = [
        ['Obligatorio', $attribute->is_required, 'No se puede guardar la entidad sin valor.'],
        ['Filtrable', $attribute->is_filterable, 'Aparece como filtro en los listados.'],
        ['Comparable', $attribute->is_comparable, 'Se puede usar al comparar entidades.'],
        ['Buscable', $attribute->is_searchable, 'Su valor entra en la caja de búsqueda.'],
        ['Visible', $attribute->is_visible, 'Se enseña en la ficha de la entidad.'],
        ['Destacado', $attribute->is_featured, 'Sale antes y con más peso visual.'],
        ['Admite varios', $attribute->allows_multiple, 'Se pueden elegir varios valores a la vez.'],
        ['Copiable', $attribute->allow_cloning, 'Otros pueden llevárselo a su biblioteca.'],
    ];

    /* El árbol de valores, montado desde lo que ya viene cargado. */
    $raices = $parentOptions->whereNull('parent_option_id');

    $usoMaximo = $topValores->max('values_count') ?: 1;
@endphp

<x-app-layout :title="$attribute->name" surface="dark">

    <x-slot name="header">Atributos</x-slot>

    <div x-data="{
        vista: 'grid',
        tamano: 6,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.attributeShow.view') ?? '{}');
                if (['gallery', 'grid', 'list', 'table', 'tree'].includes(g.vista)) this.vista = g.vista;
                if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
            } catch (e) {}

            this.$watch('vista', () => this.recordar());
            this.$watch('tamano', () => this.recordar());
        },

        recordar() {
            try {
                localStorage.setItem('omnimerge.attributeShow.view',
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
    }" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <span class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                style="border-color: {{ $acento }}55">
                @if ($attribute->image_url)
                    <img src="{{ $attribute->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-lg"
                        style="color: {{ $acento }}">{{ $attribute->icon ?: $attribute->data_type_icon }}</span>
                @endif
            </span>

            <div class="min-w-0 flex-1">
                <a href="{{ route('attributes.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Atributos
                </a>

                <div class="mt-0.5 flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-xl font-black tracking-tight text-white">{{ $attribute->name }}</h1>

                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                        style="color: {{ $acento }}; background-color: {{ $acento }}22">
                        {{ $attribute->data_type_label }}
                    </span>

                    @if ($attribute->is_featured)
                        <span class="text-amber-400" title="Destacado">★</span>
                    @endif

                    @if ($attribute->status !== 'ACTIVE')
                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$attribute->status] ?? 'bg-slate-800 text-slate-500' }}">
                            {{ $estadoEtiqueta[$attribute->status] ?? $attribute->status }}
                        </span>
                    @endif
                </div>

                <p class="font-mono text-[10px] text-slate-600">{{ $attribute->code }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @can('update', $attribute)
                    <a href="{{ route('attributes.edit', $attribute) }}"
                        class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                        ✎ Editar
                    </a>
                @endcan

                @if ($esCatalogo)
                    <a href="#catalog"
                        class="rounded-xl px-3 py-2 text-[11px] font-black transition"
                        style="background-color: {{ $acento }}22; color: {{ $acento }}">
                        + Añadir valores
                    </a>
                @endif
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

            <div class="space-y-3">

                <div class="overflow-hidden rounded-2xl border bg-slate-900/50"
                    style="border-color: {{ $acento }}40">

                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-950">
                        @if ($attribute->image_url)
                            <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-6xl"
                                style="color: {{ $acento }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                                {{ $attribute->icon ?: $attribute->data_type_icon }}
                            </span>
                        @endif
                    </div>

                    <div class="p-3">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">Así se rellena</p>

                        <div class="mt-1.5 rounded-xl border border-slate-800 bg-slate-950 p-2.5">
                            <p class="mb-1.5 text-[11px] font-black text-slate-300">{{ $attribute->name }}</p>

                            @if ($esCatalogo && $attribute->allows_multiple)
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($parentOptions->take(3) as $muestra)
                                        <span class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-900 py-0.5 pl-0.5 pr-1.5">
                                            <span class="h-4 w-4 overflow-hidden rounded-sm border border-slate-800">
                                                @if ($muestra->image_url)
                                                    <img src="{{ $muestra->image_url }}" alt="" class="h-full w-full object-cover">
                                                @endif
                                            </span>
                                            <span class="truncate text-[9px] text-slate-400">{{ $muestra->name }}</span>
                                        </span>
                                    @endforeach

                                    @if ($parentOptions->isEmpty())
                                        <span class="text-[10px] text-rose-300">Sin valores todavía</span>
                                    @endif
                                </div>
                            @elseif ($esCatalogo)
                                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5">
                                    <span class="text-[10px] text-slate-500">
                                        {{ $parentOptions->first()?->name ?? 'Elige un valor…' }}
                                    </span>
                                    <span class="text-[10px] text-slate-600">▾</span>
                                </div>
                            @elseif ($attribute->data_type === 'BOOLEAN')
                                <span class="flex h-5 w-9 items-center rounded-full p-0.5"
                                    style="background-color: {{ $acento }}">
                                    <span class="ml-auto h-4 w-4 rounded-full bg-slate-50"></span>
                                </span>
                            @elseif (in_array($attribute->data_type, ['INTEGER', 'DECIMAL'], true))
                                <div class="flex items-center gap-2">
                                    <span class="flex-1 rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 font-mono text-[11px]"
                                        style="color: {{ $acento }}">
                                        {{ $attribute->data_type === 'DECIMAL' ? '1,80' : '42' }}
                                    </span>
                                    @if ($attribute->unit)
                                        <span class="rounded-lg border border-slate-800 px-2 py-1.5 text-[10px] font-black text-slate-400">
                                            {{ $attribute->unit }}
                                        </span>
                                    @endif
                                </div>
                            @elseif ($attribute->data_type === 'DATE')
                                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5">
                                    <span class="font-mono text-[10px] text-slate-400">10 / 10 / 1993</span>
                                    <span class="text-[10px]" style="color: {{ $acento }}">◫</span>
                                </div>
                            @elseif ($attribute->data_type === 'COLOR')
                                <div class="flex items-center gap-2">
                                    <span class="h-7 w-7 rounded-lg border border-slate-800" style="background-color: {{ $acento }}"></span>
                                    <span class="rounded-lg border border-slate-800 bg-slate-900 px-2 py-1.5 font-mono text-[10px] text-slate-400">
                                        {{ $acento }}
                                    </span>
                                </div>
                            @else
                                <div class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5">
                                    <span class="text-[10px] text-slate-600">
                                        {{ $attribute->placeholder ?: 'Escribe aquí…' }}
                                    </span>
                                </div>
                            @endif

                            @if ($attribute->help_text)
                                <p class="mt-1.5 text-[9px] leading-3 text-slate-600">{{ $attribute->help_text }}</p>
                            @endif
                        </div>
                    </div>
                </div>

            </div>


            <div class="space-y-3">

                {{-- Cifras --}}
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([['Lo usan', $attribute->entity_attributes_count, true], [$esCatalogo ? 'Valores' : 'Unidad', $esCatalogo ? $attribute->options_count : ($attribute->unit ?: '—'), true], ['Sin usar', $esCatalogo ? $valoresSinUsar : '—', false], ['Con padre', $esCatalogo ? $valoresConPadre : '—', false]] as [$etiqueta, $valor, $conAcento])
                        <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                            <p class="truncate font-mono text-xl font-black {{ $conAcento ? '' : 'text-slate-400' }}"
                                @if ($conAcento) style="color: {{ is_numeric($valor) && $valor == 0 ? '#475569' : $acento }}" @endif>
                                {{ $valor }}
                            </p>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                        </div>
                    @endforeach
                </div>


                {{-- Descripción --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">Qué describe</h2>
                    <p class="mt-1 text-[12px] leading-relaxed {{ $attribute->description ? 'text-slate-300' : 'text-slate-600' }}">
                        {{ $attribute->description ?: 'Sin descripción. Una línea diciendo qué mide se agradece dentro de un año.' }}
                    </p>
                </div>


                {{-- Interruptores --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">

                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Cómo se comporta
                    </h2>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($interruptores as [$etiqueta, $puesto, $ayuda])
                            <span title="{{ $ayuda }}"
                                class="flex items-center gap-1.5 rounded-lg border px-2 py-1 text-[10px] font-bold {{ $puesto ? 'border-slate-700 bg-slate-800/60 text-slate-200' : 'border-slate-800/60 text-slate-700' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $puesto ? 'bg-emerald-400' : 'bg-slate-700' }}"></span>
                                {{ $etiqueta }}
                            </span>
                        @endforeach
                    </div>

                    @if ($attribute->min_numeric_value !== null || $attribute->max_numeric_value !== null || $attribute->min_length || $attribute->max_length)
                        <p class="mt-2 border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            <strong class="text-slate-400">Límites:</strong>
                            @if ($attribute->min_numeric_value !== null || $attribute->max_numeric_value !== null)
                                de {{ $attribute->min_numeric_value ?? '−∞' }} a {{ $attribute->max_numeric_value ?? '∞' }}
                                {{ $attribute->unit }}
                            @else
                                entre {{ $attribute->min_length ?? 0 }} y {{ $attribute->max_length ?? '∞' }} caracteres
                            @endif
                        </p>
                    @endif

                    @if ($attribute->groups->isNotEmpty())
                        <div class="mt-2 flex flex-wrap items-center gap-1.5 border-t border-slate-800 pt-2">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Grupos</span>
                            @foreach ($attribute->groups as $grupo)
                                <a href="{{ route('attributes.index', ['group' => $grupo->id]) }}"
                                    class="rounded-lg border border-slate-800 px-2 py-0.5 text-[10px] font-bold text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                                    {{ $grupo->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- QUIÉN LO USA --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
                    <x-omni-icon name="usuario" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Quién lo usa</h2>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        @if ($attribute->entity_attributes_count === 0)
                            Ninguna entidad lo tiene asignado todavía. Crear el atributo no se lo pone a
                            nadie: eso se hace desde cada entidad, o en lote.
                        @else
                            <strong class="text-indigo-300">{{ $attribute->entity_attributes_count }}</strong>
                            {{ $attribute->entity_attributes_count === 1 ? 'entidad lo tiene' : 'entidades lo tienen' }}
                            asignado.
                        @endif
                    </p>
                </div>

                @if ($attribute->entity_attributes_count > 0)
                    <a href="{{ route('entities.index') }}"
                        class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300">
                        Ver entidades →
                    </a>
                @endif
            </div>

            @if ($entidadesQueLoUsan->isNotEmpty())
                <div class="grid grid-cols-3 gap-2 p-4 sm:grid-cols-6 lg:grid-cols-9">
                    @foreach ($entidadesQueLoUsan as $entidad)
                        <a href="{{ route('entities.show', $entidad) }}" title="{{ $entidad->name }}"
                            class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:border-indigo-500/40">
                            <span class="block aspect-square overflow-hidden bg-slate-900">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl text-slate-800">◍</span>
                                @endif
                            </span>
                            <span class="block truncate px-1.5 py-1 text-center text-[9px] font-black text-slate-400">
                                {{ $entidad->name }}
                            </span>
                        </a>
                    @endforeach
                </div>

                @if ($attribute->entity_attributes_count > $entidadesQueLoUsan->count())
                    <p class="border-t border-slate-800 px-4 py-2 text-[10px] text-slate-600">
                        Y {{ $attribute->entity_attributes_count - $entidadesQueLoUsan->count() }} más.
                    </p>
                @endif
            @endif
        </section>


        {{-- ===================================================== --}}
        {{-- LOS VALORES QUE SE USAN --}}
        {{-- ===================================================== --}}

        @if ($esCatalogo && $topValores->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        style="background-color: {{ $acento }}22; color: {{ $acento }}">
                        <x-omni-icon name="barras" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Los valores que se usan de verdad</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            En un catálogo de {{ $attribute->options_count }}, esto es lo que distingue los
                            que sostienen la biblioteca de los que no ha tocado nadie.
                            @if ($valoresSinUsar > 0)
                                <strong class="text-amber-300">{{ $valoresSinUsar }}</strong>
                                {{ $valoresSinUsar === 1 ? 'no lo usa nadie' : 'no los usa nadie' }}.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="space-y-1.5 p-4">
                    @foreach ($topValores as $valor)
                        <a href="{{ route('attribute-options.show', $valor) }}"
                            class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-slate-700">

                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                @if ($valor->image_url)
                                    <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◇</span>
                                @endif
                            </span>

                            <span class="w-32 shrink-0 truncate text-[11px] font-black text-white">{{ $valor->name }}</span>

                            <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-900">
                                <span class="block h-full rounded-full"
                                    style="width: {{ max((int) round(($valor->values_count / $usoMaximo) * 100), 3) }}%; background-color: {{ $acento }}"></span>
                            </span>

                            <span class="shrink-0 font-mono text-[11px] font-black" style="color: {{ $acento }}">
                                {{ $valor->values_count }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- EL CATÁLOGO --}}
        {{-- ===================================================== --}}

        @if ($esCatalogo)
            <section id="catalog" class="overflow-hidden rounded-2xl border bg-slate-900/50"
                style="border-color: {{ $acento }}40">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        style="background-color: {{ $acento }}22; color: {{ $acento }}">
                        <x-omni-icon name="capas" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Sus valores</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Lo que se podrá elegir al rellenar este atributo. Pueden colgar unos de otros:
                            una aldea dentro de un país.
                        </p>
                    </div>

                    <span class="shrink-0 rounded-xl border px-3 py-1.5 font-mono text-[11px] font-black"
                        style="border-color: {{ $acento }}40; color: {{ $attribute->options_count > 0 ? $acento : '#f43f5e' }}">
                        {{ $attribute->options_count }}
                    </span>
                </div>


                {{-- ---------- AÑADIR UN VALOR ---------- --}}

                @can('update', $attribute)
                    <form method="POST" action="{{ route('attributes.options.store', $attribute) }}"
                        enctype="multipart/form-data"
                        x-data="{
                            abierto: {{ $errors->any() ? 'true' : 'false' }},
                            nombre: @js(old('name', '')),
                            imagen: null,
                            verImagen(e) {
                                const f = e.target.files?.[0];
                                if (! f) return;
                                const r = new FileReader();
                                r.onload = (ev) => { this.imagen = ev.target.result; };
                                r.readAsDataURL(f);
                            },
                        }"
                        class="border-b border-slate-800 bg-slate-950/40">
                        @csrf

                        <input type="hidden" name="context" value="attribute_show">
                        <input type="hidden" name="status" value="ACTIVE">

                        <button type="button" @click="abierto = !abierto"
                            class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-950/60">

                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                                style="background-color: {{ $acento }}22; color: {{ $acento }}">
                                <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-[12px] font-black text-white">Añadir un valor</span>
                                <span class="block text-[10px] text-slate-500">
                                    Con su imagen, y colgando de otro si hace falta.
                                </span>
                            </span>

                            <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                                <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                            </span>
                        </button>

                        <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800 p-4">

                            <div class="grid gap-3 lg:grid-cols-[minmax(0,180px)_minmax(0,1fr)]">

                                {{-- Su cara --}}
                                <div>
                                    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                                        <div class="relative aspect-square overflow-hidden bg-slate-900">
                                            <template x-if="imagen">
                                                <img :src="imagen" alt="" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="! imagen">
                                                <span class="flex h-full w-full items-center justify-center text-3xl text-slate-800">◇</span>
                                            </template>
                                        </div>
                                    </div>

                                    <label class="mt-2 block cursor-pointer rounded-xl border border-dashed border-slate-700 p-2 text-center transition hover:border-violet-500">
                                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                                            @change="verImagen($event)" class="sr-only">
                                        <span class="text-[10px] font-black text-slate-300">Elegir imagen</span>
                                    </label>
                                </div>

                                <div class="space-y-3">

                                    <label class="block">
                                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                            Cómo se llama
                                        </span>
                                        <input type="text" name="name" x-model="nombre" required maxlength="150"
                                            placeholder="«Uzumaki», «Konoha», «Fuego»…"
                                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-sm font-bold text-white placeholder:font-normal placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                                    </label>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                                ¿Cuelga de otro valor?
                                            </span>
                                            <select name="parent_option_id"
                                                class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                                                <option value="">De ninguno</option>
                                                @foreach ($parentOptions as $posible)
                                                    <option value="{{ $posible->id }}" @selected(old('parent_option_id') == $posible->id)>
                                                        {{ $posible->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="mt-1 block text-[10px] leading-4 text-slate-600">
                                                Así se monta la jerarquía: Konoha dentro de País del Fuego.
                                            </span>
                                        </label>

                                        <div class="grid grid-cols-2 gap-2">
                                            <label class="block">
                                                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">Símbolo</span>
                                                <input type="text" name="icon" value="{{ old('icon') }}" placeholder="◆" maxlength="10"
                                                    class="w-full rounded-xl border-slate-800 bg-slate-900 text-center text-sm text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                                            </label>

                                            <label class="block">
                                                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">Color</span>
                                                <input type="color" name="color" value="{{ old('color', $acento) }}"
                                                    class="h-[38px] w-full cursor-pointer rounded-xl border border-slate-800 bg-slate-900 p-1">
                                            </label>
                                        </div>
                                    </div>

                                    <label class="block">
                                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                            Descripción
                                        </span>
                                        <textarea name="description" rows="2" maxlength="2000"
                                            placeholder="Opcional."
                                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">{{ old('description') }}</textarea>
                                    </label>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <button type="submit" :disabled="! nombre"
                                            class="rounded-xl px-4 py-2 text-[11px] font-black text-slate-950 transition disabled:cursor-not-allowed disabled:opacity-40"
                                            style="background-color: {{ $acento }}">
                                            Añadir el valor
                                        </button>

                                        <a href="{{ route('attribute-options.create', ['attribute' => $attribute->id]) }}"
                                            class="text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                                            ¿Necesitas más campos? Ficha completa →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                @endcan


                @if ($attribute->options_count === 0)

                    <div class="p-10 text-center">
                        <span class="inline-flex text-rose-500/50"><x-omni-icon name="capas" size="h-9 w-9" /></span>

                        <p class="mt-2 text-[13px] font-black text-white">Este catálogo está vacío</p>

                        <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                            Sin valores no se puede asignar a ninguna entidad: sale en las listas y no hace
                            nada. Añádele el primero aquí arriba.
                        </p>
                    </div>

                @else

                    {{-- ---------- FILTROS Y FORMA DE MIRAR ---------- --}}

                    <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/40 px-4 py-3">

                        <form method="GET" action="{{ route('attributes.show', $attribute) }}#catalog"
                            class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                            <label class="relative min-w-[150px] flex-1">
                                <span class="sr-only">Buscar valor</span>
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                    <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                                </span>
                                <input type="search" name="catalog_search" value="{{ $catalogSearch }}"
                                    placeholder="Buscar valor…"
                                    class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                            </label>

                            <select name="catalog_status" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                                @foreach (['' => 'Cualquier estado', 'ACTIVE' => 'Activos', 'INACTIVE' => 'Inactivos', 'ARCHIVED' => 'Archivados'] as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected((string) $catalogStatus === (string) $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>

                            <select name="catalog_sort" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                                @foreach (['manual' => 'Orden manual', 'name_asc' => 'Nombre (A–Z)', 'name_desc' => 'Nombre (Z–A)', 'usage_desc' => 'Más usados', 'usage_asc' => 'Menos usados', 'newest' => 'Los más nuevos', 'oldest' => 'Los más antiguos'] as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($catalogSort === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>

                            <select name="catalog_per_page" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                                @foreach ([12, 24, 48] as $cuantos)
                                    <option value="{{ $cuantos }}" @selected($catalogPerPage === $cuantos)>{{ $cuantos }}</option>
                                @endforeach
                            </select>

                            <button type="submit"
                                class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                                Buscar
                            </button>
                        </form>

                        <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                            @foreach ([['gallery', 'galeria', 'Galería: solo las caras'], ['grid', 'cuadricula', 'Cuadrícula: con su uso'], ['list', 'menu', 'Lista: una línea por valor'], ['table', 'controles', 'Tabla: para comparar'], ['tree', 'grafo', 'Jerarquía: cuál cuelga de cuál']] as [$modo, $icono, $ayuda])
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


                    @if ($catalogOptions->isEmpty())

                        <p class="p-10 text-center text-[11px] text-slate-600">
                            Ningún valor encaja con lo que has filtrado.
                        </p>

                    @else

                        {{-- GALERÍA --}}
                        <div x-show="vista === 'gallery'" x-cloak class="grid gap-2 p-4" :class="columnas">
                            @foreach ($catalogOptions as $option)
                                <a href="{{ route('attribute-options.show', $option) }}" title="{{ $option->name }}"
                                    class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-slate-600">
                                    <span class="block aspect-square overflow-hidden bg-slate-900">
                                        @if ($option->image_url)
                                            <img src="{{ $option->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-xl text-slate-700">
                                                {{ $option->icon ?: '◇' }}
                                            </span>
                                        @endif
                                    </span>
                                    <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300">
                                        {{ $option->name }}
                                    </span>
                                </a>
                            @endforeach
                        </div>


                        {{-- CUADRÍCULA --}}
                        <div x-show="vista === 'grid'" class="grid gap-2 p-4" :class="columnas">
                            @foreach ($catalogOptions as $option)
                                @php $colorValor = $option->color ?: $acento; @endphp

                                <article class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                                    style="border-color: {{ $option->values_count > 0 ? $colorValor . '40' : '#1e293b' }}">

                                    <a href="{{ route('attribute-options.show', $option) }}"
                                        class="relative block aspect-square overflow-hidden bg-slate-900">
                                        @if ($option->image_url)
                                            <img src="{{ $option->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-2xl"
                                                style="color: {{ $colorValor }}66">{{ $option->icon ?: '◇' }}</span>
                                        @endif

                                        @if ($option->children_count > 0)
                                            <span class="absolute left-1 top-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-slate-300"
                                                title="{{ $option->children_count }} valores cuelgan de este">
                                                ⌄{{ $option->children_count }}
                                            </span>
                                        @endif

                                        @if ($option->status !== 'ACTIVE')
                                            <span class="absolute right-1 top-1 rounded px-1 text-[8px] font-black uppercase {{ $tonoEstado[$option->status] ?? 'bg-slate-800 text-slate-500' }}">
                                                {{ $estadoEtiqueta[$option->status] ?? $option->status }}
                                            </span>
                                        @endif
                                    </a>

                                    <div class="p-1.5">
                                        <a href="{{ route('attribute-options.show', $option) }}"
                                            class="block truncate text-[10px] font-black text-white">{{ $option->name }}</a>

                                        @if ($option->parent)
                                            <p class="truncate text-[9px] text-slate-600">en {{ $option->parent->name }}</p>
                                        @endif

                                        <p class="mt-0.5 font-mono text-[9px] {{ $option->values_count > 0 ? '' : 'text-slate-700' }}"
                                            style="{{ $option->values_count > 0 ? 'color: ' . $colorValor : '' }}"
                                            title="Entidades que tienen este valor">
                                            {{ $option->values_count }}
                                            {{ $option->values_count === 1 ? 'uso' : 'usos' }}
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>


                        {{-- LISTA --}}
                        <div x-show="vista === 'list'" x-cloak class="divide-y divide-slate-800/70">
                            @foreach ($catalogOptions as $option)
                                @php $colorValor = $option->color ?: $acento; @endphp

                                <div class="flex items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50">

                                    <a href="{{ route('attribute-options.show', $option) }}"
                                        class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                        @if ($option->image_url)
                                            <img src="{{ $option->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center"
                                                style="color: {{ $colorValor }}">{{ $option->icon ?: '◇' }}</span>
                                        @endif
                                    </a>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <a href="{{ route('attribute-options.show', $option) }}"
                                                class="truncate text-[12px] font-black text-white">{{ $option->name }}</a>

                                            @if ($option->status !== 'ACTIVE')
                                                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoEstado[$option->status] ?? 'bg-slate-800 text-slate-500' }}">
                                                    {{ $estadoEtiqueta[$option->status] ?? $option->status }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="truncate text-[10px] text-slate-500">
                                            @if ($option->parent)
                                                dentro de <span class="text-slate-400">{{ $option->parent->name }}</span>
                                            @elseif ($option->children_count > 0)
                                                {{ $option->children_count }} valores cuelgan de este
                                            @else
                                                {{ $option->description ?: 'Sin descripción.' }}
                                            @endif
                                        </p>
                                    </div>

                                    <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black"
                                        style="border-color: {{ $colorValor }}40; color: {{ $option->values_count > 0 ? $colorValor : '#475569' }}">
                                        {{ $option->values_count }}
                                    </span>

                                    @can('update', $attribute)
                                        <a href="{{ route('attribute-options.edit', $option) }}"
                                            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>
                                    @endcan
                                </div>
                            @endforeach
                        </div>


                        {{-- TABLA --}}
                        <div x-show="vista === 'table'" x-cloak class="overflow-x-auto">
                            <table class="w-full min-w-[620px]">
                                <thead class="border-b border-slate-800 text-left">
                                    <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        <th class="px-4 py-2.5">Valor</th>
                                        <th class="px-3 py-2.5">Cuelga de</th>
                                        <th class="px-3 py-2.5 text-right">Hijos</th>
                                        <th class="px-3 py-2.5 text-right">Usos</th>
                                        <th class="px-3 py-2.5">Estado</th>
                                        <th class="px-3 py-2.5"></th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-800/70">
                                    @foreach ($catalogOptions as $option)
                                        @php $colorValor = $option->color ?: $acento; @endphp

                                        <tr class="transition hover:bg-slate-950/50">
                                            <td class="px-4 py-2">
                                                <a href="{{ route('attribute-options.show', $option) }}" class="flex items-center gap-2">
                                                    <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                        @if ($option->image_url)
                                                            <img src="{{ $option->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                        @else
                                                            <span class="flex h-full w-full items-center justify-center text-[11px]"
                                                                style="color: {{ $colorValor }}">{{ $option->icon ?: '◇' }}</span>
                                                        @endif
                                                    </span>
                                                    <span class="truncate text-[12px] font-black text-white">{{ $option->name }}</span>
                                                </a>
                                            </td>

                                            <td class="px-3 py-2 text-[11px] text-slate-500">{{ $option->parent?->name ?? '—' }}</td>

                                            <td class="px-3 py-2 text-right font-mono text-[11px] {{ $option->children_count > 0 ? 'text-slate-300' : 'text-slate-700' }}">
                                                {{ $option->children_count }}
                                            </td>

                                            <td class="px-3 py-2 text-right font-mono text-[11px]"
                                                style="color: {{ $option->values_count > 0 ? $colorValor : '#475569' }}">
                                                {{ $option->values_count }}
                                            </td>

                                            <td class="px-3 py-2">
                                                <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$option->status] ?? 'bg-slate-800 text-slate-500' }}">
                                                    {{ $estadoEtiqueta[$option->status] ?? $option->status }}
                                                </span>
                                            </td>

                                            <td class="px-3 py-2 text-right">
                                                <a href="{{ route('attribute-options.show', $option) }}"
                                                    class="text-[10px] font-black text-slate-400 transition hover:text-violet-300">Ver →</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>


                        {{-- JERARQUÍA --}}

                        {{--
                            La vista que faltaba. Un valor puede colgar de otro y
                            eso no se veía en ninguna de las tres anteriores. Se
                            monta con TODOS los valores activos, no con la página
                            actual: un árbol partido por la paginación no es un
                            árbol.
                        --}}

                        <div x-show="vista === 'tree'" x-cloak class="p-4">

                            @if ($valoresConPadre === 0)
                                <p class="rounded-xl border border-dashed border-slate-800 py-8 text-center text-[11px] leading-relaxed text-slate-600">
                                    Ningún valor cuelga de otro todavía. La jerarquía se monta al añadir un
                                    valor, eligiendo de cuál cuelga.
                                </p>
                            @else
                                <p class="mb-3 text-[10px] leading-relaxed text-slate-500">
                                    Se enseñan los <strong class="text-slate-400">{{ $parentOptions->count() }}</strong>
                                    valores activos, no solo los de esta página: un árbol partido por la
                                    paginación no es un árbol.
                                </p>

                                <div class="space-y-1.5">
                                    @foreach ($raices as $raiz)
                                        @include('attributes.partials.option-branch', [
                                            'nodo' => $raiz,
                                            'nivel' => 0,
                                            'todos' => $parentOptions,
                                            'acento' => $acento,
                                        ])
                                    @endforeach
                                </div>
                            @endif
                        </div>


                        <div class="border-t border-slate-800 px-4 py-3">
                            {{ $catalogOptions->links() }}
                        </div>

                    @endif

                @endif

            </section>

        @else

            {{-- ===================================================== --}}
            {{-- NO ES CATÁLOGO --}}
            {{-- ===================================================== --}}

            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="controles" size="h-8 w-8" /></span>

                <p class="mt-2 text-[13px] font-black text-white">Este atributo no tiene catálogo</p>

                <p class="mx-auto mt-1 max-w-lg text-[11px] leading-relaxed text-slate-500">
                    Es de tipo <strong class="text-slate-300">{{ $attribute->data_type_label }}</strong>: su
                    valor se escribe a mano en cada entidad, no se elige de una lista. Solo los de tipo
                    <strong class="text-violet-300">catálogo</strong> tienen valores que gestionar.
                </p>
            </section>

        @endif


        {{-- ===================================================== --}}
        {{-- ZONA DE PELIGRO --}}
        {{-- ===================================================== --}}

        @can('delete', $attribute)
            <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                <div class="flex flex-wrap items-center gap-3">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[12px] font-black text-rose-200">Eliminar este atributo</h2>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-rose-200/60">
                            @if ($attribute->entity_attributes_count > 0 || $attribute->options_count > 0)
                                Se lleva por delante sus {{ $attribute->options_count }}
                                {{ $attribute->options_count === 1 ? 'valor' : 'valores' }} y el dato en las
                                {{ $attribute->entity_attributes_count }}
                                {{ $attribute->entity_attributes_count === 1 ? 'entidad que lo usa' : 'entidades que lo usan' }}.
                            @else
                                No lo usa nadie y no tiene valores, así que borrarlo no rompe nada.
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('attributes.destroy', $attribute) }}"
                        data-omni-confirm data-confirm-variant="danger" data-confirm-title="Eliminar el atributo" data-confirm-message="Se borran también todos sus valores." data-confirm-subject="{{ $attribute->name }}" data-confirm-detail="No se puede deshacer." data-confirm-action="Sí, eliminarlo">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="rounded-xl border border-rose-500/40 px-4 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                            Eliminar
                        </button>
                    </form>

                </div>
            </section>
        @endcan

    </div>

</x-app-layout>

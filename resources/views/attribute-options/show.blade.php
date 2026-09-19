@php
    /*
     * La ficha de un valor de catálogo.
     *
     * Lo que tenía: migas, un héroe grande, cifras, la tarjeta de su catálogo,
     * su apariencia, su jerarquía… y al final una sección que prometía que
     * «podrá ser referenciado desde Universos y Torneos». Es decir: todo menos
     * lo único que un valor de catálogo existe para contestar.
     *
     *   ¿quién lo lleva?     → «17 usos» como número, sin enseñar ni una cara
     *   ¿es de los que se usan? → nada lo situaba entre sus hermanos
     *   ¿puedo borrarlo?     → nada decía si hay una regla montada encima
     *
     * Ahora las tres se contestan, y la promesa de futuro se ha ido: una ficha
     * no es sitio para un folleto.
     */

    $catalogo = $attributeOption->attribute;

    $acento = $attributeOption->color ?: ($catalogo?->color ?: '#6366f1');

    $tonoCatalogo = $catalogo?->color ?: '#6366f1';

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

    $enUso = $attributeOption->values_count;

    $hermanosSinEl = $hermanos->where('id', '!=', $attributeOption->id);

    $usoMaximo = $hermanos->max('values_count') ?: 1;

    /* Para saltar al anterior y al siguiente sin volver al índice. */
    $indice = $hermanos->search(fn($h) => $h->id === $attributeOption->id);
    $anterior = $indice !== false && $indice > 0 ? $hermanos[$indice - 1] : null;
    $siguiente = $indice !== false && $indice < $hermanos->count() - 1 ? $hermanos[$indice + 1] : null;

    $ataduras = $dependencias->count() + $enReglas->count();
@endphp

<x-app-layout :title="$attributeOption->name" surface="dark">

    <x-slot name="header">Catálogos</x-slot>

    <div x-data="{
        vista: 'gallery',
        tamano: 6,

        init() {
            try {
                const g = JSON.parse(localStorage.getItem('omnimerge.optionShow.view') ?? '{}');
                if (['gallery', 'grid', 'list', 'ranking'].includes(g.vista)) this.vista = g.vista;
                if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
            } catch (e) {}

            this.$watch('vista', () => this.recordar());
            this.$watch('tamano', () => this.recordar());
        },

        recordar() {
            try {
                localStorage.setItem('omnimerge.optionShow.view',
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
        {{-- DÓNDE ESTOY --}}
        {{-- ===================================================== --}}

        <nav class="flex flex-wrap items-center gap-1.5 text-[10px] font-black uppercase tracking-[0.15em] text-slate-600">

            <a href="{{ route('attribute-options.index') }}" class="transition hover:text-violet-400">Catálogos</a>

            <span class="text-slate-800">›</span>

            <a href="{{ route('attributes.show', $catalogo) }}"
                class="flex items-center gap-1.5 transition hover:underline"
                style="color: {{ $tonoCatalogo }}">
                @include('attributes.partials.cara', [
                    'cosa' => $catalogo,
                    'tamano' => 'h-4 w-4',
                    'respaldo' => '◫',
                    'tono' => $tonoCatalogo,
                ])
                {{ $catalogo?->name }}
            </a>

            @foreach ($ancestors as $ancestro)
                <span class="text-slate-800">›</span>
                <a href="{{ route('attribute-options.show', $ancestro) }}"
                    class="flex items-center gap-1.5 transition hover:text-slate-300">
                    @include('attributes.partials.cara', [
                        'cosa' => $ancestro,
                        'tamano' => 'h-4 w-4',
                        'respaldo' => '◇',
                        'tono' => $ancestro->color ?: $tonoCatalogo,
                    ])
                    {{ $ancestro->name }}
                </a>
            @endforeach

            <span class="text-slate-800">›</span>
            <span class="text-slate-400">{{ $attributeOption->name }}</span>
        </nav>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- QUIÉN ES --}}
        {{-- ===================================================== --}}

        <section class="grid gap-4 lg:grid-cols-[minmax(0,280px)_minmax(0,1fr)]">

            <div class="space-y-3">

                <div class="overflow-hidden rounded-2xl border bg-slate-900/50"
                    style="border-color: {{ $attributeOption->image_url ? $acento . '55' : '#f43f5e55' }}">

                    <div class="relative aspect-square overflow-hidden bg-slate-950">
                        @if ($attributeOption->image_url)
                            <img src="{{ $attributeOption->image_url }}" alt="{{ $attributeOption->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-6xl"
                                style="color: {{ $acento }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $acento }}22, transparent 70%)">
                                {{ $attributeOption->icon ?: '◇' }}
                            </span>

                            <span class="absolute inset-x-0 bottom-0 bg-rose-500/85 py-1 text-center text-[10px] font-black text-white">
                                sin imagen · no se le reconoce al elegirlo
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Saltar entre hermanos --}}
                @if ($anterior || $siguiente)
                    <div class="flex items-center gap-1.5">
                        @if ($anterior)
                            <a href="{{ route('attribute-options.show', $anterior) }}"
                                title="{{ $anterior->name }}"
                                class="flex min-w-0 flex-1 items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 p-1.5 transition hover:border-slate-700">
                                <span class="shrink-0 text-slate-600">‹</span>
                                @include('attributes.partials.cara', [
                                    'cosa' => $anterior,
                                    'tamano' => 'h-7 w-7',
                                    'respaldo' => '◇',
                                    'tono' => $anterior->color ?: $tonoCatalogo,
                                ])
                                <span class="min-w-0 flex-1 truncate text-[10px] font-black text-slate-400">
                                    {{ $anterior->name }}
                                </span>
                            </a>
                        @endif

                        @if ($siguiente)
                            <a href="{{ route('attribute-options.show', $siguiente) }}"
                                title="{{ $siguiente->name }}"
                                class="flex min-w-0 flex-1 items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 p-1.5 transition hover:border-slate-700">
                                <span class="min-w-0 flex-1 truncate text-right text-[10px] font-black text-slate-400">
                                    {{ $siguiente->name }}
                                </span>
                                @include('attributes.partials.cara', [
                                    'cosa' => $siguiente,
                                    'tamano' => 'h-7 w-7',
                                    'respaldo' => '◇',
                                    'tono' => $siguiente->color ?: $tonoCatalogo,
                                ])
                                <span class="shrink-0 text-slate-600">›</span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>


            <div class="space-y-3">

                <header class="flex flex-wrap items-start gap-3">

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-2xl font-black tracking-tight text-white">
                                {{ $attributeOption->name }}
                            </h1>

                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$attributeOption->status] ?? 'bg-slate-800 text-slate-500' }}">
                                {{ $estadoEtiqueta[$attributeOption->status] ?? $attributeOption->status }}
                            </span>
                        </div>

                        <p class="mt-0.5 flex flex-wrap items-center gap-1.5 font-mono text-[10px] text-slate-600">
                            {{ $attributeOption->code }}
                            <span class="text-slate-800">·</span>
                            <a href="{{ route('attributes.show', $catalogo) }}"
                                class="font-sans font-bold transition hover:underline"
                                style="color: {{ $tonoCatalogo }}">{{ $catalogo?->name }}</a>
                            @if ($posicion)
                                <span class="text-slate-800">·</span>
                                <span class="font-sans">nº {{ $posicion }} de {{ $hermanos->count() }} por uso</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                        @can('update', $attributeOption)
                            <a href="{{ route('attribute-options.edit', $attributeOption) }}"
                                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                                ✎ Editar
                            </a>

                            <a href="{{ route('attribute-options.create', ['attribute' => $catalogo?->id, 'parent' => $attributeOption->id]) }}"
                                class="rounded-xl px-3 py-2 text-[11px] font-black transition"
                                style="background-color: {{ $acento }}22; color: {{ $acento }}"
                                title="Crear un valor que cuelgue de este">
                                + Uno dentro
                            </a>
                        @endcan
                    </div>
                </header>


                {{-- Cifras --}}
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">

                    <a href="#quien" class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 transition hover:border-slate-700">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $enUso > 0 ? $acento : '#475569' }}">{{ $enUso }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">
                            {{ $enUso === 1 ? 'entidad lo lleva' : 'entidades lo llevan' }}
                        </span>
                    </a>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2"
                        title="Veces que aparece en una versión de entidad, que es otro sitio donde puede estar puesto">
                        <span class="block font-mono text-xl font-black {{ $usoEnVersiones > 0 ? 'text-cyan-300' : 'text-slate-600' }}">
                            {{ $usoEnVersiones }}
                        </span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">en versiones</span>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                        <span class="block font-mono text-xl font-black {{ $attributeOption->children_count > 0 ? 'text-slate-200' : 'text-slate-600' }}">
                            {{ $attributeOption->children_count }}
                        </span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">cuelgan de él</span>
                    </div>

                    <div class="rounded-xl border px-3 py-2 {{ $ataduras > 0 ? 'border-amber-500/30 bg-amber-500/5' : 'border-slate-800 bg-slate-900/50' }}"
                        title="Reglas y dependencias entre catálogos que lo nombran">
                        <span class="block font-mono text-xl font-black {{ $ataduras > 0 ? 'text-amber-300' : 'text-slate-600' }}">
                            {{ $ataduras }}
                        </span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">lo nombran</span>
                    </div>
                </div>


                {{-- Qué es --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">Qué representa</h2>
                    <p class="mt-1 text-[12px] leading-relaxed {{ $attributeOption->description ? 'text-slate-300' : 'text-slate-600' }}">
                        {{ $attributeOption->description ?: 'Sin descripción. Una línea diciendo qué es se agradece dentro de un año.' }}
                    </p>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5 border-t border-slate-800 pt-2.5">

                        <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 px-2 py-1">
                            <span class="h-3 w-3 rounded" style="background-color: {{ $acento }}"></span>
                            <span class="font-mono text-[10px] text-slate-400">{{ $attributeOption->color ?: '—' }}</span>
                        </span>

                        <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 px-2 py-1">
                            <span class="text-[11px]" style="color: {{ $acento }}">{{ $attributeOption->icon ?: '◇' }}</span>
                            <span class="text-[10px] text-slate-500">símbolo</span>
                        </span>

                        @if ($attributeOption->numeric_value !== null)
                            <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 px-2 py-1">
                                <span class="font-mono text-[11px] font-black" style="color: {{ $acento }}">
                                    {{ rtrim(rtrim((string) $attributeOption->numeric_value, '0'), '.') }}
                                </span>
                                <span class="text-[10px] text-slate-500">valor numérico</span>
                            </span>
                        @endif

                        @if ($attributeOption->parent)
                            <a href="{{ route('attribute-options.show', $attributeOption->parent) }}"
                                class="flex items-center gap-1.5 rounded-lg border border-slate-800 py-0.5 pl-0.5 pr-2 transition hover:border-cyan-500">
                                @include('attributes.partials.cara', [
                                    'cosa' => $attributeOption->parent,
                                    'tamano' => 'h-5 w-5',
                                    'respaldo' => '◇',
                                    'tono' => $attributeOption->parent->color ?: $tonoCatalogo,
                                ])
                                <span class="text-[10px] text-slate-400">dentro de {{ $attributeOption->parent->name }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- QUIÉN LO LLEVA --}}
        {{-- ===================================================== --}}

        <section id="quien" class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $enUso > 0 ? $acento . '40' : '#1e293b' }}">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    style="background-color: {{ $acento }}22; color: {{ $acento }}">
                    <x-omni-icon name="usuario" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Quién lo lleva</h2>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        @if ($totalEntidades === 0 && $usoEnVersiones === 0)
                            Ninguna entidad lo tiene puesto todavía. Crearlo no se lo pone a nadie: eso se
                            hace desde la ficha de cada entidad.
                        @else
                            <strong style="color: {{ $acento }}">{{ $totalEntidades }}</strong>
                            {{ $totalEntidades === 1 ? 'entidad lo lleva' : 'entidades lo llevan' }}
                            @if ($usoEnVersiones > 0)
                                , y aparece <strong class="text-cyan-300">{{ $usoEnVersiones }}</strong>
                                {{ $usoEnVersiones === 1 ? 'vez' : 'veces' }} en versiones de entidad
                            @endif
                            .
                        @endif
                    </p>
                </div>

                @if ($totalEntidades > 0)
                    <a href="{{ route('entities.index') }}"
                        class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                        Ver entidades →
                    </a>
                @endif
            </div>

            @if ($entidades->isNotEmpty())
                <div class="grid grid-cols-3 gap-2 p-4 sm:grid-cols-6 lg:grid-cols-8">
                    @foreach ($entidades as $entidad)
                        <a href="{{ route('entities.show', $entidad) }}" title="{{ $entidad->name }}"
                            class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5"
                            style="border-color: {{ $acento }}22">
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

                @if ($totalEntidades > $entidades->count())
                    <p class="border-t border-slate-800 px-4 py-2 text-[10px] text-slate-600">
                        Y {{ $totalEntidades - $entidades->count() }} más.
                    </p>
                @endif
            @endif
        </section>


        {{-- ===================================================== --}}
        {{-- QUÉ SE APOYA EN ÉL --}}
        {{-- ===================================================== --}}

        {{--
            Lo que no se veía en ninguna parte: antes de borrar un valor conviene
            saber si hay una regla montada encima. La sección solo aparece cuando
            hay algo que decir.
        --}}

        @if ($ataduras > 0)
            <section class="overflow-hidden rounded-2xl border border-amber-500/25 bg-amber-500/5">

                <div class="flex items-center gap-3 border-b border-amber-500/20 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                        <x-omni-icon name="grafo" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Qué se apoya en él</h2>
                        <p class="text-[10px] leading-relaxed text-amber-200/70">
                            Hay {{ $ataduras }} {{ $ataduras === 1 ? 'cosa' : 'cosas' }} montadas encima de
                            este valor. Borrarlo se las lleva por delante; archivarlo, no.
                        </p>
                    </div>

                    <a href="{{ route('attributes.structure.index') }}"
                        class="shrink-0 rounded-xl border border-amber-500/40 px-2.5 py-2 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-white">
                        Estructura →
                    </a>
                </div>

                <div class="space-y-1.5 p-4">

                    @foreach ($dependencias as $dependencia)
                        @php
                            $esOrigen = $dependencia->source_option_id === $attributeOption->id;
                            $otro = $esOrigen ? $dependencia->targetOption : $dependencia->sourceOption;
                            $permite = $dependencia->relationship_type === 'ALLOWS';
                        @endphp

                        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2">

                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $permite ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300' }}">
                                {{ $permite ? 'permite' : 'bloquea' }}
                            </span>

                            <span class="text-[11px] text-slate-400">
                                {{ $esOrigen ? 'Cuando está puesto, ' . ($permite ? 'deja elegir' : 'impide elegir') : ($permite ? 'Se puede elegir cuando está' : 'No se puede elegir cuando está') }}
                            </span>

                            @if ($otro)
                                <a href="{{ route('attribute-options.show', $otro) }}"
                                    class="flex min-w-0 items-center gap-1.5 rounded-lg border border-slate-800 py-0.5 pl-0.5 pr-2 transition hover:border-slate-600">
                                    @include('attributes.partials.cara', [
                                        'cosa' => $otro,
                                        'tamano' => 'h-6 w-6',
                                        'respaldo' => '◇',
                                        'tono' => $otro->color ?: '#6366f1',
                                    ])
                                    <span class="min-w-0">
                                        <span class="block truncate text-[11px] font-black text-white">{{ $otro->name }}</span>
                                        <span class="block truncate text-[9px] text-slate-600">{{ $otro->attribute?->name }}</span>
                                    </span>
                                </a>
                            @else
                                <span class="text-[11px] text-slate-600">un valor que ya no existe</span>
                            @endif
                        </div>
                    @endforeach

                    @foreach ($enReglas as $condicion)
                        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2">

                            <span class="rounded bg-violet-500/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-violet-300">
                                regla
                            </span>

                            <span class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
                                Cuando
                                <strong class="text-slate-200">{{ $condicion->sourceAttribute?->name }}</strong>
                                {{ ['EQUALS' => 'es', 'NOT_EQUALS' => 'no es', 'EXISTS' => 'tiene valor', 'NOT_EXISTS' => 'no tiene valor'][$condicion->operator] ?? $condicion->operator }}
                                <strong style="color: {{ $acento }}">{{ $attributeOption->name }}</strong>,
                                {{ ['SHOW' => 'se muestra', 'HIDE' => 'se oculta', 'REQUIRE' => 'se exige'][$condicion->rule?->action] ?? '—' }}
                                <strong class="text-slate-200">{{ $condicion->rule?->targetAttribute?->name }}</strong>.
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- LO QUE CUELGA DE ÉL --}}
        {{-- ===================================================== --}}

        @if ($attributeOption->children->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                        <x-omni-icon name="grafo" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Lo que cuelga de él</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            {{ $attributeOption->children_count }}
                            {{ $attributeOption->children_count === 1 ? 'valor está dentro de este' : 'valores están dentro de este' }}.
                            Si lo mueves o lo borras, se {{ $attributeOption->children_count === 1 ? 'va' : 'van' }} con él.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 p-4 sm:grid-cols-5 lg:grid-cols-7">
                    @foreach ($attributeOption->children as $hijo)
                        @php $tonoHijo = $hijo->color ?: $acento; @endphp

                        <a href="{{ route('attribute-options.show', $hijo) }}" title="{{ $hijo->name }}"
                            class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                            style="border-color: {{ $tonoHijo }}33">
                            <span class="block aspect-square overflow-hidden bg-slate-900">
                                @if ($hijo->image_url)
                                    <img src="{{ $hijo->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl"
                                        style="color: {{ $tonoHijo }}66">{{ $hijo->icon ?: '◇' }}</span>
                                @endif
                            </span>
                            <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                                {{ $hijo->name }}
                            </span>
                            <span class="block pb-1 text-center font-mono text-[9px]"
                                style="color: {{ $hijo->values_count > 0 ? $tonoHijo : '#475569' }}">
                                {{ $hijo->values_count }} {{ $hijo->values_count === 1 ? 'uso' : 'usos' }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- SUS HERMANOS --}}
        {{-- ===================================================== --}}

        @if ($hermanosSinEl->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
                style="border-color: {{ $tonoCatalogo }}40">

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3">

                    @include('attributes.partials.cara', [
                        'cosa' => $catalogo,
                        'tamano' => 'h-8 w-8',
                        'respaldo' => '◫',
                        'tono' => $tonoCatalogo,
                    ])

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">
                            Los otros de {{ $catalogo?->name }}
                        </h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Entre los que se elige cuando alguien rellena este atributo. Salta a cualquiera
                            sin volver al índice.
                        </p>
                    </div>

                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ([['gallery', 'galeria', 'Galería: solo las caras'], ['grid', 'cuadricula', 'Cuadrícula: con su uso'], ['list', 'menu', 'Lista: una línea cada uno'], ['ranking', 'barras', 'Ranking: quién sostiene el catálogo']] as [$modo, $icono, $ayuda])
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


                {{-- GALERÍA --}}
                <div x-show="vista === 'gallery'" class="grid gap-2 p-4" :class="columnas">
                    @foreach ($hermanosSinEl as $hermano)
                        @php $tonoH = $hermano->color ?: $tonoCatalogo; @endphp

                        <a href="{{ route('attribute-options.show', $hermano) }}" title="{{ $hermano->name }}"
                            class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                            style="border-color: {{ $hermano->image_url ? '#1e293b' : '#f43f5e40' }}">
                            <span class="block aspect-square overflow-hidden bg-slate-900">
                                @if ($hermano->image_url)
                                    <img src="{{ $hermano->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl"
                                        style="color: {{ $tonoH }}66">{{ $hermano->icon ?: '◇' }}</span>
                                @endif
                            </span>
                            <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300">
                                {{ $hermano->name }}
                            </span>
                        </a>
                    @endforeach
                </div>


                {{-- CUADRÍCULA --}}
                <div x-show="vista === 'grid'" x-cloak class="grid gap-2 p-4" :class="columnas">
                    @foreach ($hermanosSinEl as $hermano)
                        @php $tonoH = $hermano->color ?: $tonoCatalogo; @endphp

                        <article class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                            style="border-color: {{ $hermano->values_count > 0 ? $tonoH . '40' : '#1e293b' }}">

                            <a href="{{ route('attribute-options.show', $hermano) }}"
                                class="relative block aspect-square overflow-hidden bg-slate-900">
                                @if ($hermano->image_url)
                                    <img src="{{ $hermano->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-2xl"
                                        style="color: {{ $tonoH }}66">{{ $hermano->icon ?: '◇' }}</span>
                                @endif

                                @if ($hermano->status !== 'ACTIVE')
                                    <span class="absolute right-1 top-1 rounded px-1 text-[8px] font-black uppercase {{ $tonoEstado[$hermano->status] ?? 'bg-slate-800 text-slate-500' }}">
                                        {{ $estadoEtiqueta[$hermano->status] ?? $hermano->status }}
                                    </span>
                                @endif
                            </a>

                            <div class="p-1.5">
                                <a href="{{ route('attribute-options.show', $hermano) }}"
                                    class="block truncate text-[10px] font-black text-white">{{ $hermano->name }}</a>
                                <p class="font-mono text-[9px] {{ $hermano->values_count > 0 ? '' : 'text-slate-700' }}"
                                    style="{{ $hermano->values_count > 0 ? 'color: ' . $tonoH : '' }}">
                                    {{ $hermano->values_count }} {{ $hermano->values_count === 1 ? 'uso' : 'usos' }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>


                {{-- LISTA --}}
                <div x-show="vista === 'list'" x-cloak class="divide-y divide-slate-800/70">
                    @foreach ($hermanosSinEl as $hermano)
                        @php $tonoH = $hermano->color ?: $tonoCatalogo; @endphp

                        <a href="{{ route('attribute-options.show', $hermano) }}"
                            class="flex items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50">

                            @include('attributes.partials.cara', [
                                'cosa' => $hermano,
                                'tamano' => 'h-9 w-9',
                                'respaldo' => '◇',
                                'tono' => $tonoH,
                            ])

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12px] font-black text-white">{{ $hermano->name }}</span>
                                <span class="block truncate font-mono text-[9px] text-slate-600">{{ $hermano->code }}</span>
                            </span>

                            @if ($hermano->status !== 'ACTIVE')
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $tonoEstado[$hermano->status] ?? 'bg-slate-800 text-slate-500' }}">
                                    {{ $estadoEtiqueta[$hermano->status] ?? $hermano->status }}
                                </span>
                            @endif

                            <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black"
                                style="border-color: {{ $tonoH }}40; color: {{ $hermano->values_count > 0 ? $tonoH : '#475569' }}">
                                {{ $hermano->values_count }}
                            </span>
                        </a>
                    @endforeach
                </div>


                {{-- RANKING --}}

                {{--
                    La forma de mirar que contesta «¿es este de los que sostienen
                    el catálogo?». El propio valor se marca en su sitio, en vez
                    de esconderse entre los demás.
                --}}

                <div x-show="vista === 'ranking'" x-cloak class="space-y-1.5 p-4">
                    @foreach ($hermanos->sortByDesc('values_count')->values() as $posicionEnLista => $hermano)
                        @php
                            $tonoH = $hermano->color ?: $tonoCatalogo;
                            $esEl = $hermano->id === $attributeOption->id;
                            $ancho = max((int) round(($hermano->values_count / $usoMaximo) * 100), 2);
                        @endphp

                        <div class="flex items-center gap-2.5 rounded-xl border p-2 {{ $esEl ? 'bg-slate-950' : 'border-slate-800 bg-slate-950' }}"
                            style="{{ $esEl ? 'border-color: ' . $acento : '' }}">

                            <span class="w-6 shrink-0 text-center font-mono text-[10px] font-black {{ $esEl ? '' : 'text-slate-700' }}"
                                style="{{ $esEl ? 'color: ' . $acento : '' }}">
                                {{ $posicionEnLista + 1 }}
                            </span>

                            @if ($esEl)
                                @include('attributes.partials.cara', [
                                    'cosa' => $hermano,
                                    'tamano' => 'h-8 w-8',
                                    'respaldo' => '◇',
                                    'tono' => $tonoH,
                                ])
                            @else
                                <a href="{{ route('attribute-options.show', $hermano) }}" class="shrink-0">
                                    @include('attributes.partials.cara', [
                                        'cosa' => $hermano,
                                        'tamano' => 'h-8 w-8',
                                        'respaldo' => '◇',
                                        'tono' => $tonoH,
                                    ])
                                </a>
                            @endif

                            <span class="w-28 shrink-0 truncate text-[11px] font-black {{ $esEl ? 'text-white' : 'text-slate-400' }}">
                                @if ($esEl)
                                    {{ $hermano->name }}
                                @else
                                    <a href="{{ route('attribute-options.show', $hermano) }}"
                                        class="transition hover:text-white">{{ $hermano->name }}</a>
                                @endif
                            </span>

                            <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-900">
                                <span class="block h-full rounded-full"
                                    style="width: {{ $ancho }}%; background-color: {{ $hermano->values_count > 0 ? $tonoH : '#334155' }}"></span>
                            </span>

                            <span class="w-8 shrink-0 text-right font-mono text-[11px] font-black"
                                style="color: {{ $hermano->values_count > 0 ? $tonoH : '#475569' }}">
                                {{ $hermano->values_count }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- ZONA DE PELIGRO --}}
        {{-- ===================================================== --}}

        @can('delete', $attributeOption)
            <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                <div class="flex flex-wrap items-center gap-3">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[12px] font-black text-rose-200">Eliminar este valor</h2>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-rose-200/60">
                            @if ($enUso > 0 || $attributeOption->children_count > 0 || $ataduras > 0)
                                @if ($enUso > 0)
                                    Lo llevan {{ $enUso }} {{ $enUso === 1 ? 'entidad' : 'entidades' }}.
                                @endif
                                @if ($attributeOption->children_count > 0)
                                    {{ $attributeOption->children_count }}
                                    {{ $attributeOption->children_count === 1 ? 'valor cuelga' : 'valores cuelgan' }} de él.
                                @endif
                                @if ($ataduras > 0)
                                    Y hay {{ $ataduras }} {{ $ataduras === 1 ? 'regla o dependencia' : 'reglas o dependencias' }}
                                    apoyadas encima.
                                @endif
                                Si solo quieres que deje de poder elegirse,
                                <strong class="text-rose-200">archívalo</strong>: eso no borra nada y las
                                entidades que ya lo llevaban lo conservan.
                            @else
                                No lo lleva nadie, no cuelga nadie de él y no hay ninguna regla encima, así
                                que borrarlo no rompe nada.
                            @endif
                        </p>
                    </div>

                    @can('update', $attributeOption)
                        @if ($attributeOption->status === 'ACTIVE')
                            <form method="POST" action="{{ route('attribute-options.quick', $attributeOption) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="ARCHIVED">

                                <button type="submit"
                                    class="rounded-xl border border-amber-500/40 px-4 py-2 text-[11px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-white">
                                    Archivar
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('attribute-options.quick', $attributeOption) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="ACTIVE">

                                <button type="submit"
                                    class="rounded-xl border border-emerald-500/40 px-4 py-2 text-[11px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-white">
                                    Reactivar
                                </button>
                            </form>
                        @endif
                    @endcan

                    <form method="POST" action="{{ route('attribute-options.destroy', $attributeOption) }}"
                        data-omni-confirm data-confirm-variant="danger" data-confirm-title="Eliminar la opción" data-confirm-message="Se elimina del catálogo y deja de poder elegirse." data-confirm-subject="{{ $attributeOption->name }}" data-confirm-detail="No se puede deshacer." data-confirm-action="Sí, eliminarla">
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

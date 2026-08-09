<x-app-layout>

    <x-slot name="header">
        Comunidad
    </x-slot>


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <div x-data="communityExplorer({
        searchUrl: @js(route('community.search'))
    })" x-init="init()"
        @keydown.escape.window="
            searchOpen = false;
            cloneOpen = false;
        ">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <section
            class="
                relative
                overflow-visible
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
                sm:p-7
            ">

            <div class="absolute right-0 top-0 -z-0 h-40 w-40 rounded-full bg-violet-100 blur-3xl"></div>

            <div class="relative">

                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">

                    <div>

                        <span
                            class="rounded-full bg-violet-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-violet-700">
                            🌐 Comunidad OmniMerge
                        </span>


                        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">
                            Descubre. Copia. Evoluciona.
                        </h2>


                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            Explora recursos públicos creados por otros usuarios
                            y reutilízalos como copias independientes dentro de tu Biblioteca.
                        </p>

                    </div>


                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">

                        @foreach ([['✦', $statistics['entities'], 'Entidades', 'entities'], ['▤', $statistics['collections'], 'Colecciones', 'collections'], ['☷', $statistics['attributes'], 'Atributos', 'attributes'], ['◆', $statistics['catalogs'], 'Catálogos', 'catalogs'], ['◎', $statistics['creators'], 'Creadores', 'creators']] as [$icon, $value, $label, $target])
                            <a href="{{ route('community.index', ['tab' => $target]) }}"
                                class="
                                    rounded-xl
                                    bg-slate-50
                                    px-3
                                    py-2.5
                                    text-center
                                    transition
                                    hover:bg-indigo-50
                                ">
                                <p class="text-sm">
                                    {{ $icon }}
                                </p>

                                <p class="mt-1 text-sm font-black text-slate-800">
                                    {{ number_format($value) }}
                                </p>

                                <p class="mt-0.5 hidden text-[8px] font-bold uppercase text-slate-400 sm:block">
                                    {{ $label }}
                                </p>
                            </a>
                        @endforeach

                    </div>

                </div>


                {{-- BUSCADOR GLOBAL --}}
                <div class="relative mt-6 max-w-4xl"
                    @click.outside="
                        searchOpen = false
                    ">

                    <div
                        class="
                            flex
                            items-center
                            gap-3
                            rounded-2xl
                            border
                            border-slate-200
                            bg-slate-50
                            px-4
                            focus-within:border-violet-300
                            focus-within:bg-white
                            focus-within:ring-4
                            focus-within:ring-violet-50
                        ">

                        <span class="text-xl text-slate-400">
                            ⌕
                        </span>


                        <input type="search" x-model="searchQuery" @input.debounce.300ms="searchCommunity()"
                            @focus="
                                if (searchQuery.length >= 2) {
                                    searchOpen = true
                                }
                            "
                            placeholder="Busca cualquier entidad, creador, tipo, atributo o elemento de Catálogo..."
                            class="
                                w-full
                                min-w-0
                                border-0
                                bg-transparent
                                py-4
                                text-sm
                                text-slate-900
                                placeholder:text-slate-400
                                focus:ring-0
                            ">


                        <span x-show="searching" x-cloak class="text-xs font-bold text-violet-500">
                            Buscando...
                        </span>

                    </div>


                    <div x-show="searchOpen" x-cloak x-transition
                        class="
                            absolute
                            inset-x-0
                            top-[calc(100%+8px)]
                            z-50
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            shadow-2xl
                        ">

                        <div class="max-h-[430px] overflow-y-auto p-2">

                            <template x-for="(result, index) in searchResults"
                                :key="`${result.type}-${result.url}-${index}`">

                                <a :href="result.url"
                                    class="flex items-center gap-3 rounded-xl p-3 hover:bg-violet-50">

                                    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-xl bg-slate-100">

                                        <template x-if="result.image">

                                            <img :src="result.image" class="h-full w-full object-cover">

                                        </template>


                                        <template x-if="!result.image">

                                            <div class="flex h-full items-center justify-center font-black text-violet-400"
                                                x-text="result.icon"></div>

                                        </template>

                                    </div>


                                    <div class="min-w-0 flex-1">

                                        <div class="flex items-center gap-2">

                                            <p class="truncate text-sm font-black text-slate-800" x-text="result.title">
                                            </p>

                                            <span
                                                class="rounded-full bg-violet-50 px-2 py-0.5 text-[8px] font-black uppercase text-violet-600"
                                                x-text="result.type"></span>

                                        </div>


                                        <p class="mt-1 truncate text-[10px] text-slate-400" x-text="result.subtitle">
                                        </p>

                                    </div>


                                    <span class="text-xs text-slate-300">
                                        →
                                    </span>

                                </a>

                            </template>


                            <div x-show="
                                    ! searching
                                    &&
                                    searchQuery.length >= 2
                                    &&
                                    searchResults.length === 0
                                "
                                x-cloak class="p-8 text-center text-sm text-slate-400">
                                No encontramos resultados.
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- TABS --}}
        {{-- ===================================================== --}}

        <div class="mt-6 overflow-x-auto">

            <nav class="flex min-w-max gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">

                @foreach ([
        'all' => ['⌘', 'Todo'],
        'entities' => ['✦', 'Entidades'],
        'collections' => ['▤', 'Colecciones'],
        'attributes' => ['☷', 'Atributos'],
        'catalogs' => ['◆', 'Catálogos'],
        'creators' => ['◎', 'Creadores'],
    ] as $value => [$icon, $label])
                    <a href="{{ route('community.index', ['tab' => $value]) }}"
                        class="
                            {{ $tab === $value
                                ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20'
                                : 'text-slate-600 hover:bg-slate-100' }}

                            flex
                            items-center
                            gap-2
                            rounded-xl
                            px-4
                            py-2.5
                            text-sm
                            font-bold
                            transition
                        ">
                        <span>
                            {{ $icon }}
                        </span>

                        {{ $label }}
                    </a>
                @endforeach

            </nav>

        </div>


        {{-- FILTROS --}}
        @include('community.partials.filters')


        {{-- ===================================================== --}}
        {{-- CONTROLES DE VISTA --}}
        {{-- ===================================================== --}}

        @if ($tab !== 'all')

            <div
                class="
                    mt-5
                    flex
                    flex-col
                    gap-3
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-3
                    shadow-sm
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                ">

                <div class="flex flex-wrap gap-2">

                    @foreach ([
        'gallery' => '▦ Galería',
        'grid' => '▦ Cuadrícula',
        'masonry' => '▥ Mosaico',
        'list' => '☰ Lista',
        'table' => '≡ Tabla',
    ] as $value => $label)
                        <button type="button"
                            @click="
                                setView(
                                    '{{ $value }}'
                                )
                            "
                            :class="view === '{{ $value }}'
                                ?
                                'bg-violet-600 text-white' :
                                'bg-slate-100 text-slate-500'"
                            class="rounded-lg px-3 py-2 text-xs font-bold">
                            {{ $label }}
                        </button>
                    @endforeach

                </div>


                <div x-show="
                        view === 'grid'
                    " class="flex gap-2">

                    @foreach ([
        'compact' => 'Compacto',
        'medium' => 'Mediano',
        'large' => 'Grande',
    ] as $value => $label)
                        <button type="button"
                            @click="
                                setDensity(
                                    '{{ $value }}'
                                )
                            "
                            :class="density === '{{ $value }}'
                                ?
                                'bg-slate-900 text-white' :
                                'bg-slate-100 text-slate-500'"
                            class="rounded-lg px-3 py-2 text-xs font-bold">
                            {{ $label }}
                        </button>
                    @endforeach

                </div>

            </div>

        @endif


        {{-- ===================================================== --}}
        {{-- TODO --}}
        {{-- ===================================================== --}}

        @if ($tab === 'all')

            <div class="mt-8 space-y-12">

                @foreach ([
        'entities' => ['✦', 'Entidades', 'entity'],
        'collections' => ['▤', 'Colecciones', 'collection'],
        'attributes' => ['☷', 'Atributos', 'attribute'],
        'catalogs' => ['◆', 'Catálogos', 'catalog'],
        'creators' => ['◎', 'Creadores', 'creator'],
    ] as $key => [$icon, $label, $itemType])
                    @php

                        $sectionItems = $allResults[$key];

                    @endphp


                    @if ($sectionItems->isNotEmpty())
                        <section>

                            <div class="mb-4 flex items-end justify-between">

                                <div>

                                    <p class="text-[10px] font-black uppercase tracking-wider text-violet-500">
                                        {{ $icon }} Comunidad
                                    </p>

                                    <h3 class="mt-1 text-xl font-black text-slate-900">
                                        {{ $label }}
                                    </h3>

                                </div>


                                <a href="{{ route('community.index', [
                                    'tab' => $key,
                                    'search' => $search,
                                ]) }}"
                                    class="text-xs font-black text-violet-600">
                                    Ver todos →
                                </a>

                            </div>


                            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                                @foreach ($sectionItems as $item)
                                    @include('community.partials.item-card', [
                                        'item' => $item,
                                        'itemType' => $itemType,
                                    ])
                                @endforeach

                            </div>

                        </section>
                    @endif
                @endforeach

            </div>
        @else
            {{-- ================================================= --}}
            {{-- RESULTADOS DE UNA PESTAÑA --}}
            {{-- ================================================= --}}

            @php

                $paginator = match ($tab) {
                    'entities' => $entities,

                    'collections' => $collections,

                    'attributes' => $attributes,

                    'catalogs' => $catalogs,

                    'creators' => $creators,

                    default => null,
                };

                $itemType = match ($tab) {
                    'entities' => 'entity',

                    'collections' => 'collection',

                    'attributes' => 'attribute',

                    'catalogs' => 'catalog',

                    'creators' => 'creator',

                    default => 'entity',
                };

                $currentCollection = $paginator ? $paginator->getCollection() : collect();

                $groupedResults = null;

                if ($groupBy && $tab !== 'creators') {
                    $groupedResults = match (true) {
                        $tab === 'entities' && $groupBy === 'type' => $currentCollection->groupBy(
                            fn($item) => $item->entityType?->name ?? 'Sin tipo',
                        ),

                        $tab === 'attributes' && $groupBy === 'data_type' => $currentCollection->groupBy(
                            fn($item) => $item->data_type_label,
                        ),

                        $tab === 'catalogs' && $groupBy === 'attribute' => $currentCollection->groupBy(
                            fn($item) => $item->attribute?->name ?? 'Sin Catálogo',
                        ),

                        $groupBy === 'creator' && $tab === 'catalogs' => $currentCollection->groupBy(
                            fn($item) => '@' . $item->user->username,
                        ),

                        $groupBy === 'creator' => $currentCollection->groupBy(
                            fn($item) => '@' . $item->creator->username,
                        ),

                        default => null,
                    };
                }

            @endphp


            @if (!$paginator || $paginator->isEmpty())

                <div class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white py-20 text-center">

                    <div class="text-5xl">
                        ⌕
                    </div>

                    <p class="mt-4 font-black text-slate-700">
                        No encontramos resultados
                    </p>

                    <p class="mt-2 text-sm text-slate-500">
                        Prueba otro término o elimina algunos filtros.
                    </p>

                </div>
            @elseif ($groupedResults)
                <div class="mt-8 space-y-12">

                    @foreach ($groupedResults as $groupLabel => $groupItems)
                        <section>

                            <div class="mb-4 flex items-center gap-3">

                                <h3 class="text-xl font-black text-slate-900">
                                    {{ $groupLabel }}
                                </h3>

                                <span
                                    class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-black text-violet-700">
                                    {{ $groupItems->count() }}
                                    en esta página
                                </span>

                            </div>


                            @include('community.partials.results-view', [
                                'items' => $groupItems,
                                'itemType' => $itemType,
                            ])

                        </section>
                    @endforeach

                </div>
            @else
                <div class="mt-6">

                    @include('community.partials.results-view', [
                        'items' => $currentCollection,
                        'itemType' => $itemType,
                    ])

                </div>

            @endif


            @if ($paginator)
                <div class="mt-8">
                    {{ $paginator->links() }}
                </div>
            @endif

        @endif


        {{-- ===================================================== --}}
        {{-- MODAL COPIAR --}}
        {{-- ===================================================== --}}

        <div x-show="cloneOpen" x-cloak
            class="
                fixed
                inset-0
                z-[100]
                flex
                items-center
                justify-center
                bg-slate-950/60
                p-4
                backdrop-blur-sm
            ">

            <div @click.outside="
                    cloneOpen = false
                "
                class="
                    w-full
                    max-w-lg
                    rounded-3xl
                    bg-white
                    p-6
                    shadow-2xl
                ">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <p class="text-[10px] font-black uppercase tracking-wider text-violet-500">
                            Copiar a Biblioteca
                        </p>

                        <h3 class="mt-2 text-xl font-black text-slate-900" x-text="cloneTitle"></h3>

                    </div>


                    <button type="button"
                        @click="
                            cloneOpen = false
                        "
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                        ×
                    </button>

                </div>


                <div class="mt-5 rounded-2xl bg-slate-50 p-5">

                    <p class="text-sm font-bold text-slate-700">
                        ¿Qué ocurrirá?
                    </p>


                    <ul class="mt-3 space-y-2 text-sm text-slate-500">

                        <li>
                            ✓ Se creará una copia independiente.
                        </li>

                        <li>
                            ✓ La copia será privada.
                        </li>

                        <li>
                            ✓ Podrás modificarla sin afectar al original.
                        </li>

                        <li x-show="cloneType === 'entity'">
                            ✓ Se copiarán sus atributos y valores.
                        </li>

                        <li x-show="cloneType === 'collection'">
                            ✓ Se copiarán también sus entidades.
                        </li>

                        <li x-show="cloneType === 'attribute'">
                            ✓ Se copiará su Catálogo y jerarquías.
                        </li>

                        <li x-show="cloneType === 'catalog'">
                            ✓ Si todavía no tienes el Catálogo padre, OmniMerge lo copiará para conservar el contexto.
                        </li>

                    </ul>

                </div>


                <form method="POST" :action="cloneAction" class="mt-5">

                    @csrf

                    <div class="flex justify-end gap-3">

                        <button type="button"
                            @click="
                                cloneOpen = false
                            "
                            class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600">
                            Cancelar
                        </button>


                        <button type="submit"
                            class="rounded-xl bg-violet-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-violet-600/20">
                            ⧉ Copiar
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <script>
        function communityExplorer(
            config
        ) {

            return {

                view: localStorage.getItem(
                        'omnimerge.community.view'
                    ) ||
                    'grid',

                density: localStorage.getItem(
                        'omnimerge.community.density'
                    ) ||
                    'medium',

                searchQuery: @js($search),

                searchResults: [],

                searchOpen: false,

                searching: false,

                cloneOpen: false,

                cloneAction: '',

                cloneTitle: '',

                cloneType: '',

                cloneSubtitle: '',


                init() {

                    const allowedViews = [
                        'gallery',
                        'grid',
                        'masonry',
                        'list',
                        'table'
                    ];


                    if (
                        !allowedViews.includes(
                            this.view
                        )
                    ) {
                        this.view =
                            'grid';
                    }
                },


                setView(
                    value
                ) {

                    this.view =
                        value;

                    localStorage.setItem(
                        'omnimerge.community.view',
                        value
                    );
                },


                setDensity(
                    value
                ) {

                    this.density =
                        value;

                    localStorage.setItem(
                        'omnimerge.community.density',
                        value
                    );
                },


                openClone(
                    action,
                    title,
                    type,
                    subtitle
                ) {

                    this.cloneAction =
                        action;

                    this.cloneTitle =
                        title;

                    this.cloneType =
                        type;

                    this.cloneSubtitle =
                        subtitle;

                    this.cloneOpen =
                        true;
                },


                async searchCommunity() {

                    const query =
                        this
                        .searchQuery
                        .trim();


                    if (
                        query.length < 2
                    ) {

                        this.searchResults = [];

                        this.searchOpen =
                            false;

                        return;
                    }


                    this.searchOpen =
                        true;

                    this.searching =
                        true;


                    try {

                        const url =
                            new URL(
                                config.searchUrl,
                                window.location.origin
                            );


                        url.searchParams.set(
                            'q',
                            query
                        );


                        const response =
                            await fetch(
                                url, {
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                }
                            );


                        const data =
                            await response.json();


                        if (
                            query !==
                            this
                            .searchQuery
                            .trim()
                        ) {
                            return;
                        }


                        this.searchResults =
                            Array.isArray(
                                data.results
                            ) ?
                            data.results :
                            [];

                    } catch (
                        error
                    ) {

                        this.searchResults = [];

                    } finally {

                        this.searching =
                            false;
                    }
                }
            };
        }
    </script>

</x-app-layout>

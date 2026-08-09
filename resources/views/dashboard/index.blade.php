<x-app-layout>

    <x-slot name="header">
        Biblioteca
    </x-slot>


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    @php

        /*
        |--------------------------------------------------------------------------
        | Tarjetas principales
        |--------------------------------------------------------------------------
        */

        $metricCards = [
            [
                'label' => 'Entidades',

                'value' => $statistics['entities'],

                'description' =>
                    $statistics['active_entities'] . ' activas · ' . $statistics['public_entities'] . ' públicas',

                'icon' => '✦',

                'url' => route('entities.index'),

                'classes' => 'bg-indigo-50 text-indigo-700',
            ],

            [
                'label' => 'Atributos',

                'value' => $statistics['attributes'],

                'description' => 'Características reutilizables',

                'icon' => '☷',

                'url' => route('attributes.index'),

                'classes' => 'bg-violet-50 text-violet-700',
            ],

            [
                'label' => 'Catálogo',

                'value' => $statistics['catalog_options'],

                'description' => $statistics['catalog_attributes'] . ' atributos con Catálogo',

                'icon' => '◆',

                'url' => route('attribute-options.index'),

                'classes' => 'bg-fuchsia-50 text-fuchsia-700',
            ],

            [
                'label' => 'Colecciones',

                'value' => $statistics['collections'],

                'description' => 'Organización de entidades',

                'icon' => '▤',

                'url' => route('collections.index'),

                'classes' => 'bg-cyan-50 text-cyan-700',
            ],

            [
                'label' => 'Tipos',

                'value' => $statistics['entity_types'],

                'description' => 'Clasificación de entidades',

                'icon' => '◇',

                'url' => route('entity-types.index'),

                'classes' => 'bg-amber-50 text-amber-700',
            ],

            [
                'label' => 'Grupos',

                'value' => $statistics['attribute_groups'],

                'description' => 'Organización de atributos',

                'icon' => '▥',

                'url' => route('attribute-groups.index'),

                'classes' => 'bg-emerald-50 text-emerald-700',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Acciones rápidas
        |--------------------------------------------------------------------------
        */

        $quickActions = [
            [
                'label' => 'Nueva entidad',

                'description' => 'Crea una nueva pieza de tu Biblioteca.',

                'icon' => '✦',

                'url' => route('entities.create'),
            ],

            [
                'label' => 'Nuevo atributo',

                'description' => 'Define una característica reutilizable.',

                'icon' => '☷',

                'url' => route('attributes.create'),
            ],

            [
                'label' => 'Nuevo Catálogo',

                'description' => 'Añade un elemento seleccionable.',

                'icon' => '◆',

                'url' => route('attribute-options.create'),
            ],

            [
                'label' => 'Nueva colección',

                'description' => 'Organiza varias entidades.',

                'icon' => '▤',

                'url' => route('collections.create'),
            ],

            [
                'label' => 'Nuevo tipo',

                'description' => 'Clasifica futuras entidades.',

                'icon' => '◇',

                'url' => route('entity-types.create'),
            ],

            [
                'label' => 'Nuevo grupo',

                'description' => 'Organiza visualmente tus atributos.',

                'icon' => '▥',

                'url' => route('attribute-groups.create'),
            ],
        ];

    @endphp


    <div x-data="dashboardWorkspace({
        searchUrl: @js(route('dashboard.search'))
    })" x-init="init()"
        @keydown.escape.window="
            createOpen = false;
            customizeOpen = false;
            searchOpen = false;
        ">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
                sm:p-6
            ">

            <div
                class="
                    flex
                    flex-col
                    gap-5
                    xl:flex-row
                    xl:items-start
                    xl:justify-between
                ">

                {{-- TÍTULO --}}
                <div>

                    <div
                        class="
                            flex
                            flex-wrap
                            items-center
                            gap-2
                        ">

                        <span
                            class="
                                rounded-full
                                bg-indigo-50
                                px-3
                                py-1
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-indigo-700
                            ">
                            Biblioteca
                        </span>


                        <span
                            class="
                                rounded-full
                                bg-slate-100
                                px-3
                                py-1
                                text-[10px]
                                font-bold
                                text-slate-500
                            ">
                            {{ number_format($statistics['resources_total']) }}
                            recursos
                        </span>

                    </div>


                    <h2
                        class="
                            mt-3
                            text-2xl
                            font-black
                            tracking-tight
                            text-slate-900
                            sm:text-3xl
                        ">
                        Hola,
                        {{ explode(' ', $user->name)[0] }}
                        👋
                    </h2>


                    <p
                        class="
                            mt-2
                            max-w-2xl
                            text-sm
                            leading-6
                            text-slate-500
                        ">
                        Continúa construyendo, organizando y
                        explorando las piezas de tu Biblioteca.
                    </p>

                </div>


                {{-- CONTROLES --}}
                <div
                    class="
                        flex
                        flex-wrap
                        gap-2
                    ">

                    {{-- MODO --}}
                    <div
                        class="
                            flex
                            rounded-xl
                            bg-slate-100
                            p-1
                        ">

                        @foreach ([
        'summary' => 'Resumen',

        'compact' => 'Compacto',

        'visual' => 'Visual',
    ] as $value => $label)
                            <button type="button"
                                @click="
                                    setView(
                                        '{{ $value }}'
                                    )
                                "
                                :class="view === '{{ $value }}'
                                
                                    ?
                                    'bg-white text-indigo-700 shadow-sm'
                                
                                    :
                                    'text-slate-500'"
                                class="
                                    rounded-lg
                                    px-3
                                    py-2
                                    text-xs
                                    font-bold
                                    transition
                                ">
                                {{ $label }}
                            </button>
                        @endforeach

                    </div>


                    {{-- PERSONALIZAR --}}
                    <button type="button"
                        @click="
                            customizeOpen = true
                        "
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-4
                            py-2.5
                            text-xs
                            font-bold
                            text-slate-600
                            hover:bg-slate-50
                        ">
                        ⚙ Personalizar
                    </button>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- BUSCADOR + CREAR --}}
            {{-- ================================================= --}}

            <div
                class="
                    mt-6
                    flex
                    flex-col
                    gap-3
                    lg:flex-row
                ">

                {{-- BUSCADOR --}}
                <div class="
                        relative
                        flex-1
                    "
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
                            transition
                            focus-within:border-indigo-300
                            focus-within:bg-white
                            focus-within:ring-4
                            focus-within:ring-indigo-50
                        ">

                        <span
                            class="
                                text-lg
                                text-slate-400
                            ">
                            ⌕
                        </span>


                        <input type="search"
                            x-model="
                                searchQuery
                            "
                            @input.debounce.300ms="
                                searchLibrary()
                            "
                            @focus="
                                if (
                                    searchQuery.length >= 2
                                ) {
                                    searchOpen = true;
                                }
                            "
                            placeholder="Buscar entidades, atributos, Catálogos, grupos..."
                            class="
                                w-full
                                border-0
                                bg-transparent
                                px-0
                                py-3.5
                                text-sm
                                text-slate-900
                                placeholder:text-slate-400
                                focus:ring-0
                            ">


                        <span x-show="
                                searching
                            " x-cloak
                            class="
                                text-xs
                                font-bold
                                text-indigo-500
                            ">
                            Buscando...
                        </span>

                    </div>


                    {{-- RESULTADOS --}}
                    <div x-show="
                            searchOpen
                        " x-cloak x-transition
                        class="
                            absolute
                            inset-x-0
                            top-[calc(100%+8px)]
                            z-40
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            shadow-2xl
                        ">

                        <div
                            class="
                                border-b
                                border-slate-100
                                px-4
                                py-3
                            ">

                            <p
                                class="
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-slate-400
                                ">
                                Resultados de tu Biblioteca
                            </p>

                        </div>


                        <div
                            class="
                                max-h-[430px]
                                overflow-y-auto
                                p-2
                            ">

                            <template
                                x-for="
                                    result
                                    in searchResults
                                "
                                :key="`${result.kind}-${result.id}`">

                                <a :href="result.url"
                                    class="
                                        flex
                                        items-center
                                        gap-3
                                        rounded-xl
                                        p-3
                                        transition
                                        hover:bg-indigo-50
                                    ">

                                    <div
                                        class="
                                            h-11
                                            w-11
                                            shrink-0
                                            overflow-hidden
                                            rounded-xl
                                            bg-slate-100
                                        ">

                                        <template
                                            x-if="
                                                result.image_url
                                            ">

                                            <img :src="result.image_url"
                                                :alt="result.title"
                                                class="
                                                    h-full
                                                    w-full
                                                    object-cover
                                                ">

                                        </template>


                                        <template
                                            x-if="
                                                ! result.image_url
                                            ">

                                            <div class="
                                                    flex
                                                    h-full
                                                    items-center
                                                    justify-center
                                                    font-black
                                                    text-indigo-400
                                                "
                                                x-text="
                                                    result.icon
                                                    || '◆'
                                                ">
                                            </div>

                                        </template>

                                    </div>


                                    <div
                                        class="
                                            min-w-0
                                            flex-1
                                        ">

                                        <div
                                            class="
                                                flex
                                                items-center
                                                gap-2
                                            ">

                                            <p class="
                                                    truncate
                                                    text-sm
                                                    font-black
                                                    text-slate-800
                                                "
                                                x-text="
                                                    result.title
                                                ">
                                            </p>


                                            <span
                                                class="
                                                    rounded-full
                                                    bg-indigo-50
                                                    px-2
                                                    py-0.5
                                                    text-[8px]
                                                    font-black
                                                    uppercase
                                                    text-indigo-600
                                                "
                                                x-text="
                                                    result.kind_label
                                                "></span>

                                        </div>


                                        <p
                                            class="
                                                mt-1
                                                truncate
                                                text-[10px]
                                                text-slate-400
                                            ">
                                            <span
                                                x-text="
                                                    result.code
                                                "
                                                class="
                                                    font-mono
                                                "></span>

                                            <span>
                                                ·
                                            </span>

                                            <span
                                                x-text="
                                                    result.subtitle
                                                "></span>
                                        </p>

                                    </div>


                                    <span
                                        class="
                                            text-xs
                                            text-slate-300
                                        ">
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
                                x-cloak
                                class="
                                    p-8
                                    text-center
                                ">

                                <p
                                    class="
                                        text-sm
                                        font-bold
                                        text-slate-500
                                    ">
                                    No encontramos resultados.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- + CREAR --}}
                {{-- ================================================= --}}

                <div class="
                        relative
                    "
                    @click.outside="
                        createOpen = false
                    ">

                    <button type="button"
                        @click="
                            createOpen = ! createOpen
                        "
                        class="
                            flex
                            w-full
                            items-center
                            justify-center
                            gap-2
                            rounded-2xl
                            bg-indigo-600
                            px-6
                            py-3.5
                            text-sm
                            font-black
                            text-white
                            shadow-lg
                            shadow-indigo-600/20
                            hover:bg-indigo-700
                            lg:w-auto
                        ">
                        <span class="text-lg">
                            +
                        </span>

                        Crear
                    </button>


                    <div x-show="
                            createOpen
                        " x-cloak x-transition
                        class="
                            absolute
                            right-0
                            top-[calc(100%+8px)]
                            z-40
                            w-full
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            p-2
                            shadow-2xl
                            lg:w-[360px]
                        ">

                        <div
                            class="
                                px-3
                                pb-2
                                pt-2
                            ">

                            <p
                                class="
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-slate-400
                                ">
                                Crear en Biblioteca
                            </p>

                        </div>


                        @foreach ($quickActions as $action)
                            <a href="{{ $action['url'] }}"
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-xl
                                    p-3
                                    transition
                                    hover:bg-indigo-50
                                ">

                                <div
                                    class="
                                        flex
                                        h-10
                                        w-10
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-indigo-50
                                        font-black
                                        text-indigo-600
                                    ">
                                    {{ $action['icon'] }}
                                </div>


                                <div>

                                    <p
                                        class="
                                            text-sm
                                            font-black
                                            text-slate-800
                                        ">
                                        {{ $action['label'] }}
                                    </p>


                                    <p
                                        class="
                                            mt-0.5
                                            text-[10px]
                                            text-slate-400
                                        ">
                                        {{ $action['description'] }}
                                    </p>

                                </div>

                            </a>
                        @endforeach

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- MÉTRICAS --}}
        {{-- ===================================================== --}}

        <section x-show="
                view !== 'compact'
            "
            class="
                mt-6
                grid
                grid-cols-2
                gap-3
                md:grid-cols-3
                xl:grid-cols-6
            ">

            @foreach ($metricCards as $card)
                <a href="{{ $card['url'] }}"
                    class="
                        group
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        p-4
                        shadow-sm
                        transition
                        hover:-translate-y-0.5
                        hover:border-indigo-200
                        hover:shadow-md
                    ">

                    <div
                        class="
                            flex
                            items-start
                            justify-between
                            gap-3
                        ">

                        <div>

                            <p
                                class="
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-slate-400
                                ">
                                {{ $card['label'] }}
                            </p>


                            <p
                                class="
                                    mt-2
                                    text-2xl
                                    font-black
                                    text-slate-900
                                ">
                                {{ number_format($card['value']) }}
                            </p>

                        </div>


                        <div
                            class="
                                flex
                                h-10
                                w-10
                                items-center
                                justify-center
                                rounded-xl
                                text-lg
                                font-black
                                {{ $card['classes'] }}
                            ">
                            {{ $card['icon'] }}
                        </div>

                    </div>


                    <p
                        class="
                            mt-3
                            line-clamp-1
                            text-[10px]
                            text-slate-400
                        ">
                        {{ $card['description'] }}
                    </p>


                    <p
                        class="
                            mt-3
                            text-[10px]
                            font-black
                            text-indigo-600
                            opacity-0
                            transition
                            group-hover:opacity-100
                        ">
                        Abrir →
                    </p>

                </a>
            @endforeach

        </section>


        {{-- ===================================================== --}}
        {{-- MODO RESUMEN --}}
        {{-- ===================================================== --}}

        <div x-show="
                view === 'summary'
            "
            class="
                mt-6
                space-y-6
            ">

            {{-- ================================================= --}}
            {{-- CONTINUAR + ACCIONES --}}
            {{-- ================================================= --}}

            <section
                class="
                    grid
                    gap-6
                    xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.7fr)]
                ">

                {{-- CONTINUAR --}}
                <article x-show="
                        sections.continue
                    "
                    class="
                        overflow-hidden
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        shadow-sm
                    ">

                    <div
                        class="
                            flex
                            items-end
                            justify-between
                            border-b
                            border-slate-100
                            px-5
                            py-4
                        ">

                        <div>

                            <p
                                class="
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-indigo-500
                                ">
                                Workspace
                            </p>


                            <h3
                                class="
                                    mt-1
                                    text-lg
                                    font-black
                                    text-slate-900
                                ">
                                Continuar trabajando
                            </h3>

                        </div>


                        <span
                            class="
                                text-[10px]
                                text-slate-400
                            ">
                            Modificados recientemente
                        </span>

                    </div>


                    <div
                        class="
                            grid
                            gap-2
                            p-3
                            md:grid-cols-2
                        ">

                        @forelse ($workspaceItems->take(6)
                            as $item)
                            <a href="{{ $item['url'] }}"
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-2xl
                                    p-3
                                    transition
                                    hover:bg-indigo-50
                                ">

                                <div
                                    class="
                                        h-12
                                        w-12
                                        shrink-0
                                        overflow-hidden
                                        rounded-xl
                                        bg-slate-100
                                    ">

                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}"
                                            class="
                                                h-full
                                                w-full
                                                object-cover
                                            ">
                                    @else
                                        <div class="
                                                flex
                                                h-full
                                                items-center
                                                justify-center
                                                font-black
                                            "
                                            style="
                                                background-color:
                                                    {{ $item['color'] }}15;

                                                color:
                                                    {{ $item['color'] }};
                                            ">
                                            {{ $item['icon'] }}
                                        </div>
                                    @endif

                                </div>


                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                    ">

                                    <div
                                        class="
                                            flex
                                            items-center
                                            gap-2
                                        ">

                                        <p
                                            class="
                                                truncate
                                                text-sm
                                                font-black
                                                text-slate-800
                                            ">
                                            {{ $item['name'] }}
                                        </p>


                                        <span
                                            class="
                                                rounded-full
                                                bg-slate-100
                                                px-2
                                                py-0.5
                                                text-[8px]
                                                font-bold
                                                text-slate-500
                                            ">
                                            {{ $item['type'] }}
                                        </span>

                                    </div>


                                    <p
                                        class="
                                            mt-1
                                            truncate
                                            text-[10px]
                                            text-slate-400
                                        ">
                                        {{ $item['subtitle'] }}

                                        ·

                                        {{ $item['updated_at']->diffForHumans() }}
                                    </p>

                                </div>


                                <span
                                    class="
                                        text-xs
                                        text-slate-300
                                    ">
                                    →
                                </span>

                            </a>

                        @empty

                            <div
                                class="
                                    col-span-2
                                    py-12
                                    text-center
                                    text-sm
                                    text-slate-400
                                ">
                                Empieza creando tu primer recurso.
                            </div>
                        @endforelse

                    </div>

                </article>


                {{-- ACCIONES --}}
                <article x-show="
                        sections.quick
                    "
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                        shadow-sm
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-indigo-500
                        ">
                        Atajos
                    </p>


                    <h3
                        class="
                            mt-1
                            text-lg
                            font-black
                            text-slate-900
                        ">
                        Acciones rápidas
                    </h3>


                    <div
                        class="
                            mt-4
                            grid
                            grid-cols-2
                            gap-2
                        ">

                        @foreach ($quickActions as $action)
                            <a href="{{ $action['url'] }}"
                                class="
                                    rounded-2xl
                                    border
                                    border-slate-100
                                    bg-slate-50
                                    p-3
                                    transition
                                    hover:border-indigo-200
                                    hover:bg-indigo-50
                                ">

                                <p
                                    class="
                                        text-lg
                                        text-indigo-500
                                    ">
                                    {{ $action['icon'] }}
                                </p>


                                <p
                                    class="
                                        mt-2
                                        text-xs
                                        font-black
                                        text-slate-700
                                    ">
                                    {{ $action['label'] }}
                                </p>

                            </a>
                        @endforeach

                    </div>


                    <a href="{{ route('community.index') }}"
                        class="
                            mt-4
                            flex
                            items-center
                            justify-between
                            rounded-2xl
                            border
                            border-indigo-100
                            bg-indigo-50
                            p-4
                        ">

                        <div>

                            <p
                                class="
                                    text-xs
                                    font-black
                                    text-indigo-800
                                ">
                                🌐 Comunidad
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[10px]
                                    text-indigo-500
                                ">
                                Descubre contenido reutilizable.
                            </p>

                        </div>


                        <span class="
                                text-indigo-400
                            ">
                            →
                        </span>

                    </a>

                </article>

            </section>


            {{-- ================================================= --}}
            {{-- ACTIVIDAD + SALUD --}}
            {{-- ================================================= --}}

            <section
                class="
                    grid
                    gap-6
                    xl:grid-cols-2
                ">

                {{-- ACTIVIDAD --}}
                <article x-show="
                        sections.activity
                    "
                    class="
                        overflow-hidden
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        shadow-sm
                    ">

                    <div
                        class="
                            border-b
                            border-slate-100
                            px-5
                            py-4
                        ">

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-indigo-500
                            ">
                            Historial reciente
                        </p>


                        <h3
                            class="
                                mt-1
                                text-lg
                                font-black
                                text-slate-900
                            ">
                            Actividad
                        </h3>

                    </div>


                    <div
                        class="
                            divide-y
                            divide-slate-100
                        ">

                        @forelse ($activityItems
                            as $item)
                            <a href="{{ $item['url'] }}"
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    px-5
                                    py-3.5
                                    hover:bg-slate-50
                                ">

                                <div class="
                                        flex
                                        h-9
                                        w-9
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-xl
                                    "
                                    style="
                                        background-color:
                                            {{ $item['color'] }}15;

                                        color:
                                            {{ $item['color'] }};
                                    ">
                                    {{ $item['icon'] }}
                                </div>


                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                    ">

                                    <p
                                        class="
                                            truncate
                                            text-sm
                                            font-bold
                                            text-slate-700
                                        ">
                                        {{ $item['name'] }}
                                    </p>


                                    <p
                                        class="
                                            mt-0.5
                                            text-[10px]
                                            text-slate-400
                                        ">
                                        {{ $item['action'] }}

                                        ·

                                        {{ $item['type'] }}

                                        ·

                                        {{ $item['updated_at']->diffForHumans() }}
                                    </p>

                                </div>

                            </a>

                        @empty

                            <div
                                class="
                                    p-10
                                    text-center
                                    text-sm
                                    text-slate-400
                                ">
                                Sin actividad todavía.
                            </div>
                        @endforelse

                    </div>

                </article>


                {{-- SALUD --}}
                <article x-show="
                        sections.health
                    "
                    class="
                        overflow-hidden
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        shadow-sm
                    ">

                    <div
                        class="
                            border-b
                            border-slate-100
                            px-5
                            py-4
                        ">

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-emerald-500
                            ">
                            Organización
                        </p>


                        <h3
                            class="
                                mt-1
                                text-lg
                                font-black
                                text-slate-900
                            ">
                            Estado de tu Biblioteca
                        </h3>

                    </div>


                    <div
                        class="
                            grid
                            gap-2
                            p-3
                            sm:grid-cols-2
                        ">

                        @foreach ($healthItems as $health)
                            <a href="{{ $health['url'] }}"
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-2xl
                                    p-3
                                    transition
                                    hover:bg-slate-50
                                ">

                                <div
                                    class="
                                        flex
                                        h-10
                                        w-10
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-xl

                                        {{ $health['count'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}
                                    ">
                                    {{ $health['count'] > 0 ? $health['icon'] : '✓' }}
                                </div>


                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                    ">

                                    <p
                                        class="
                                            text-xs
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $health['label'] }}
                                    </p>


                                    <p
                                        class="
                                            mt-0.5
                                            text-[10px]
                                            text-slate-400
                                        ">
                                        @if ($health['count'] > 0)
                                            <strong
                                                class="
                                                    text-amber-600
                                                ">
                                                {{ $health['count'] }}
                                            </strong>

                                            por revisar
                                        @else
                                            Todo organizado
                                        @endif
                                    </p>

                                </div>

                            </a>
                        @endforeach

                    </div>

                </article>

            </section>


            {{-- ================================================= --}}
            {{-- ENTIDADES RECIENTES --}}
            {{-- ================================================= --}}

            <section x-show="
                    sections.entities
                ">

                <div
                    class="
                        flex
                        items-end
                        justify-between
                        gap-4
                    ">

                    <div>

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-indigo-500
                            ">
                            Creaciones
                        </p>


                        <h3
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Entidades recientes
                        </h3>

                    </div>


                    <a href="{{ route('entities.index') }}"
                        class="
                            text-xs
                            font-black
                            text-indigo-600
                        ">
                        Ver todas →
                    </a>

                </div>


                <div
                    class="
                        mt-4
                        grid
                        grid-cols-2
                        gap-3
                        sm:grid-cols-3
                        md:grid-cols-4
                        xl:grid-cols-5
                    ">

                    @forelse ($recentEntities->take(10)
                        as $entity)
                        <a href="{{ route('entities.show', $entity) }}"
                            class="
                                group
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-sm
                                transition
                                hover:-translate-y-0.5
                                hover:border-indigo-200
                                hover:shadow-md
                            ">

                            <div
                                class="
                                    aspect-square
                                    bg-slate-100
                                ">

                                @if ($entity->image_url)
                                    <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                            transition
                                            duration-300
                                            group-hover:scale-105
                                        ">
                                @else
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            bg-gradient-to-br
                                            from-indigo-50
                                            to-violet-100
                                            text-4xl
                                            font-black
                                            text-indigo-300
                                        ">
                                        {{ $entity->entityType?->icon ?: '✦' }}
                                    </div>
                                @endif

                            </div>


                            <div class="p-3">

                                <p
                                    class="
                                        truncate
                                        text-sm
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $entity->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        truncate
                                        text-[10px]
                                        text-slate-400
                                    ">
                                    {{ $entity->entityType?->name ?? 'Sin tipo' }}
                                </p>


                                <div
                                    class="
                                        mt-3
                                        flex
                                        gap-3
                                        text-[9px]
                                        font-bold
                                        text-slate-400
                                    ">
                                    <span>
                                        {{ $entity->entity_attributes_count }}
                                        atributos
                                    </span>

                                    <span>
                                        {{ $entity->collections_count }}
                                        colecciones
                                    </span>
                                </div>

                            </div>

                        </a>

                    @empty

                        <div
                            class="
                                col-span-full
                                rounded-2xl
                                border
                                border-dashed
                                border-slate-300
                                bg-white
                                py-12
                                text-center
                                text-sm
                                text-slate-400
                            ">
                            Todavía no tienes entidades.
                        </div>
                    @endforelse

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- ATRIBUTOS + CATÁLOGOS --}}
            {{-- ================================================= --}}

            <section
                class="
                    grid
                    gap-6
                    xl:grid-cols-2
                ">

                {{-- ATRIBUTOS --}}
                <article x-show="
                        sections.attributes
                    "
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                        shadow-sm
                    ">

                    <div
                        class="
                            flex
                            items-end
                            justify-between
                        ">

                        <div>

                            <p
                                class="
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-violet-500
                                ">
                                Características
                            </p>


                            <h3
                                class="
                                    mt-1
                                    text-lg
                                    font-black
                                    text-slate-900
                                ">
                                Atributos recientes
                            </h3>

                        </div>


                        <a href="{{ route('attributes.index') }}"
                            class="
                                text-xs
                                font-black
                                text-indigo-600
                            ">
                            Ver todos
                        </a>

                    </div>


                    <div
                        class="
                            mt-4
                            grid
                            gap-2
                            sm:grid-cols-2
                        ">

                        @foreach ($recentAttributes->take(6) as $attribute)
                            <a href="{{ route('attributes.show', $attribute) }}"
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-2xl
                                    border
                                    border-slate-100
                                    p-3
                                    hover:border-violet-200
                                    hover:bg-violet-50
                                ">

                                <div
                                    class="
                                        h-12
                                        w-12
                                        shrink-0
                                        overflow-hidden
                                        rounded-xl
                                        bg-slate-100
                                    ">

                                    @if ($attribute->image_url)
                                        <img src="{{ $attribute->image_url }}"
                                            class="
                                                h-full
                                                w-full
                                                object-cover
                                            ">
                                    @else
                                        <div
                                            class="
                                                flex
                                                h-full
                                                items-center
                                                justify-center
                                                font-black
                                                text-violet-500
                                            ">
                                            {{ $attribute->icon ?: $attribute->data_type_icon }}
                                        </div>
                                    @endif

                                </div>


                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                    ">

                                    <p
                                        class="
                                            truncate
                                            text-xs
                                            font-black
                                            text-slate-800
                                        ">
                                        {{ $attribute->name }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            truncate
                                            text-[9px]
                                            text-slate-400
                                        ">
                                        {{ $attribute->data_type_label }}

                                        @if ($attribute->data_type === 'OPTION')
                                            ·
                                            {{ $attribute->options_count }}
                                            elementos
                                        @endif
                                    </p>

                                </div>

                            </a>
                        @endforeach

                    </div>

                </article>


                {{-- CATÁLOGO --}}
                <article x-show="
                        sections.catalogs
                    "
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                        shadow-sm
                    ">

                    <div
                        class="
                            flex
                            items-end
                            justify-between
                        ">

                        <div>

                            <p
                                class="
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-fuchsia-500
                                ">
                                Catálogo
                            </p>


                            <h3
                                class="
                                    mt-1
                                    text-lg
                                    font-black
                                    text-slate-900
                                ">
                                Elementos recientes
                            </h3>

                        </div>


                        <a href="{{ route('attribute-options.index') }}"
                            class="
                                text-xs
                                font-black
                                text-indigo-600
                            ">
                            Ver todos
                        </a>

                    </div>


                    <div
                        class="
                            mt-4
                            grid
                            grid-cols-3
                            gap-2
                            sm:grid-cols-5
                        ">

                        @forelse ($recentOptions->take(10)
                            as $option)
                            <a href="{{ route('attribute-options.show', $option) }}"
                                class="
                                    group
                                    min-w-0
                                ">

                                <div
                                    class="
                                        aspect-square
                                        overflow-hidden
                                        rounded-xl
                                        bg-slate-100
                                    ">

                                    @if ($option->image_url)
                                        <img src="{{ $option->image_url }}"
                                            class="
                                                h-full
                                                w-full
                                                object-cover
                                                transition
                                                group-hover:scale-105
                                            ">
                                    @else
                                        <div
                                            class="
                                                flex
                                                h-full
                                                items-center
                                                justify-center
                                                text-2xl
                                                font-black
                                                text-fuchsia-400
                                            ">
                                            {{ $option->icon ?: '◆' }}
                                        </div>
                                    @endif

                                </div>


                                <p
                                    class="
                                        mt-2
                                        truncate
                                        text-center
                                        text-[10px]
                                        font-black
                                        text-slate-700
                                    ">
                                    {{ $option->name }}
                                </p>

                            </a>

                        @empty

                            <p
                                class="
                                    col-span-full
                                    py-8
                                    text-center
                                    text-sm
                                    text-slate-400
                                ">
                                Sin elementos de Catálogo.
                            </p>
                        @endforelse

                    </div>

                </article>

            </section>


            {{-- ================================================= --}}
            {{-- COLECCIONES --}}
            {{-- ================================================= --}}

            <section x-show="
                    sections.collections
                ">

                <div
                    class="
                        flex
                        items-end
                        justify-between
                    ">

                    <div>

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-cyan-500
                            ">
                            Organización
                        </p>


                        <h3
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Colecciones recientes
                        </h3>

                    </div>


                    <a href="{{ route('collections.index') }}"
                        class="
                            text-xs
                            font-black
                            text-indigo-600
                        ">
                        Ver todas →
                    </a>

                </div>


                <div
                    class="
                        mt-4
                        grid
                        gap-4
                        sm:grid-cols-2
                        lg:grid-cols-3
                    ">

                    @foreach ($recentCollections as $collection)
                        <a href="{{ route('collections.show', $collection) }}"
                            class="
                                group
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-sm
                                hover:border-cyan-200
                            ">

                            <div
                                class="
                                    aspect-[16/7]
                                    overflow-hidden
                                    bg-slate-100
                                ">

                                @if ($collection->image_url)
                                    <img src="{{ $collection->image_url }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                            transition
                                            group-hover:scale-105
                                        ">
                                @else
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            text-4xl
                                            text-cyan-300
                                        ">
                                        {{ $collection->icon ?: '▤' }}
                                    </div>
                                @endif

                            </div>


                            <div class="p-4">

                                <p
                                    class="
                                        truncate
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $collection->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-[10px]
                                        text-slate-400
                                    ">
                                    {{ $collection->entities_count }}
                                    entidades
                                </p>

                            </div>

                        </a>
                    @endforeach

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- INSIGHTS --}}
            {{-- ================================================= --}}

            <section x-show="
                    sections.insights
                "
                class="
                    grid
                    gap-6
                    xl:grid-cols-2
                ">

                {{-- TIPOS --}}
                <article
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                        shadow-sm
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-indigo-500
                        ">
                        Distribución
                    </p>


                    <h3
                        class="
                            mt-1
                            text-lg
                            font-black
                            text-slate-900
                        ">
                        Entidades por tipo
                    </h3>


                    <div
                        class="
                            mt-5
                            space-y-4
                        ">

                        @forelse ($typeDistribution
                            as $item)
                            <a href="{{ $item['url'] }}"
                                class="
                                    block
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        justify-between
                                        gap-4
                                    ">

                                    <div
                                        class="
                                            flex
                                            min-w-0
                                            items-center
                                            gap-2
                                        ">

                                        <span
                                            class="
                                                flex
                                                h-7
                                                w-7
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                bg-slate-100
                                                text-xs
                                            ">
                                            {{ $item['icon'] }}
                                        </span>


                                        <span
                                            class="
                                                truncate
                                                text-xs
                                                font-bold
                                                text-slate-600
                                            ">
                                            {{ $item['name'] }}
                                        </span>

                                    </div>


                                    <span
                                        class="
                                            text-xs
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $item['count'] }}
                                    </span>

                                </div>


                                <div
                                    class="
                                        mt-2
                                        h-2.5
                                        overflow-hidden
                                        rounded-full
                                        bg-slate-100
                                    ">

                                    <div class="
                                            h-full
                                            rounded-full
                                        "
                                        style="
                                            width:
                                                {{ $item['percentage'] }}%;

                                            background-color:
                                                {{ $item['color'] }};
                                        ">
                                    </div>

                                </div>

                            </a>

                        @empty

                            <p
                                class="
                                    py-8
                                    text-center
                                    text-sm
                                    text-slate-400
                                ">
                                Todavía no hay datos.
                            </p>
                        @endforelse

                    </div>

                </article>


                {{-- CATÁLOGOS GRANDES --}}
                <article
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                        shadow-sm
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-fuchsia-500
                        ">
                        Estructura
                    </p>


                    <h3
                        class="
                            mt-1
                            text-lg
                            font-black
                            text-slate-900
                        ">
                        Catálogos principales
                    </h3>


                    <div
                        class="
                            mt-5
                            space-y-4
                        ">

                        @forelse (
                            $topCatalogs
                            as $catalog
                        )

                            @php

                                $catalogPercentage = round(($catalog->active_options_count / $catalogMax) * 100, 1);
                            @endphp


                            <a href="{{ route('attributes.show', $catalog) }}"
                                class="
                                    block
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        justify-between
                                        gap-4
                                    ">

                                    <div
                                        class="
                                            flex
                                            min-w-0
                                            items-center
                                            gap-2
                                        ">

                                        <div
                                            class="
                                                flex
                                                h-7
                                                w-7
                                                items-center
                                                justify-center
                                                overflow-hidden
                                                rounded-lg
                                                bg-violet-50
                                                text-xs
                                                text-violet-500
                                            ">

                                            @if ($catalog->image_url)
                                                <img src="{{ $catalog->image_url }}"
                                                    class="
                                                        h-full
                                                        w-full
                                                        object-cover
                                                    ">
                                            @else
                                                {{ $catalog->icon ?: '◆' }}
                                            @endif

                                        </div>


                                        <span
                                            class="
                                                truncate
                                                text-xs
                                                font-bold
                                                text-slate-600
                                            ">
                                            {{ $catalog->name }}
                                        </span>

                                    </div>


                                    <span
                                        class="
                                            text-xs
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $catalog->active_options_count }}
                                    </span>

                                </div>


                                <div
                                    class="
                                        mt-2
                                        h-2.5
                                        overflow-hidden
                                        rounded-full
                                        bg-slate-100
                                    ">

                                    <div class="
                                            h-full
                                            rounded-full
                                            bg-violet-500
                                        "
                                        style="
                                            width:
                                                {{ $catalogPercentage }}%;
                                        ">
                                    </div>

                                </div>

                            </a>

                        @empty

                            <p
                                class="
                                    py-8
                                    text-center
                                    text-sm
                                    text-slate-400
                                ">
                                Todavía no existen Catálogos.
                            </p>
                        @endforelse

                    </div>

                </article>

            </section>

        </div>


        {{-- ===================================================== --}}
        {{-- MODO COMPACTO --}}
        {{-- ===================================================== --}}

        <div x-show="
                view === 'compact'
            " x-cloak
            class="
                mt-5
                space-y-4
            ">

            {{-- STATS COMPACTAS --}}
            <section
                class="
                    flex
                    flex-wrap
                    gap-2
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-3
                    shadow-sm
                ">

                @foreach ($metricCards as $card)
                    <a href="{{ $card['url'] }}"
                        class="
                            flex
                            flex-1
                            items-center
                            justify-between
                            gap-3
                            rounded-xl
                            bg-slate-50
                            px-3
                            py-2.5
                            hover:bg-indigo-50
                        ">

                        <span
                            class="
                                whitespace-nowrap
                                text-xs
                                font-bold
                                text-slate-500
                            ">
                            {{ $card['icon'] }}
                            {{ $card['label'] }}
                        </span>


                        <strong
                            class="
                                text-sm
                                text-slate-800
                            ">
                            {{ number_format($card['value']) }}
                        </strong>

                    </a>
                @endforeach

            </section>


            <section
                class="
                    grid
                    gap-4
                    xl:grid-cols-[minmax(0,1.3fr)_minmax(300px,0.7fr)]
                ">

                {{-- CONTINUAR COMPACTO --}}
                <article x-show="
                        sections.continue
                    "
                    class="
                        overflow-hidden
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                    ">

                    <div
                        class="
                            border-b
                            border-slate-100
                            px-4
                            py-3
                        ">
                        <h3
                            class="
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            Continuar trabajando
                        </h3>
                    </div>


                    <div
                        class="
                            divide-y
                            divide-slate-100
                        ">

                        @foreach ($workspaceItems->take(8) as $item)
                            <a href="{{ $item['url'] }}"
                                class="
                                    grid
                                    gap-2
                                    px-4
                                    py-3
                                    hover:bg-slate-50
                                    sm:grid-cols-[minmax(0,1fr)_130px_120px]
                                    sm:items-center
                                ">

                                <div
                                    class="
                                        flex
                                        min-w-0
                                        items-center
                                        gap-2
                                    ">

                                    <span
                                        style="
                                            color:
                                                {{ $item['color'] }};
                                        ">
                                        {{ $item['icon'] }}
                                    </span>


                                    <span
                                        class="
                                            truncate
                                            text-xs
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $item['name'] }}
                                    </span>

                                </div>


                                <span
                                    class="
                                        text-[10px]
                                        font-bold
                                        text-slate-400
                                    ">
                                    {{ $item['type'] }}
                                </span>


                                <span
                                    class="
                                        text-[10px]
                                        text-slate-400
                                        sm:text-right
                                    ">
                                    {{ $item['updated_at']->diffForHumans() }}
                                </span>

                            </a>
                        @endforeach

                    </div>

                </article>


                {{-- REVISAR --}}
                <article x-show="
                        sections.health
                    "
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        p-4
                    ">

                    <h3
                        class="
                            text-sm
                            font-black
                            text-slate-800
                        ">
                        Por revisar
                    </h3>


                    <div
                        class="
                            mt-3
                            space-y-1
                        ">

                        @foreach ($healthItems as $health)
                            <a href="{{ $health['url'] }}"
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    rounded-lg
                                    px-2
                                    py-2
                                    hover:bg-slate-50
                                ">

                                <span
                                    class="
                                        text-[11px]
                                        text-slate-500
                                    ">
                                    {{ $health['label'] }}
                                </span>


                                <strong
                                    class="
                                        text-xs

                                        {{ $health['count'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}
                                    ">
                                    {{ $health['count'] }}
                                </strong>

                            </a>
                        @endforeach

                    </div>

                </article>

            </section>


            {{-- ACCIONES COMPACTAS --}}
            <section x-show="
                    sections.quick
                "
                class="
                    flex
                    flex-wrap
                    gap-2
                ">

                @foreach (array_slice($quickActions, 0, 4) as $action)
                    <a href="{{ $action['url'] }}"
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-slate-600
                            hover:border-indigo-200
                            hover:text-indigo-700
                        ">
                        {{ $action['icon'] }}
                        {{ $action['label'] }}
                    </a>
                @endforeach

            </section>

        </div>


        {{-- ===================================================== --}}
        {{-- MODO VISUAL --}}
        {{-- ===================================================== --}}

        <div x-show="
                view === 'visual'
            " x-cloak
            class="
                mt-6
                space-y-8
            ">

            {{-- ENTIDADES VISUALES --}}
            <section x-show="
                    sections.entities
                ">

                <div
                    class="
                        flex
                        items-end
                        justify-between
                    ">

                    <div>

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-indigo-500
                            ">
                            Tu mundo visual
                        </p>


                        <h3
                            class="
                                mt-1
                                text-2xl
                                font-black
                                text-slate-900
                            ">
                            Entidades recientes
                        </h3>

                    </div>


                    <a href="{{ route('entities.index') }}"
                        class="
                            text-xs
                            font-black
                            text-indigo-600
                        ">
                        Explorar →
                    </a>

                </div>


                <div
                    class="
                        mt-4
                        grid
                        grid-cols-3
                        gap-3
                        sm:grid-cols-4
                        lg:grid-cols-5
                        xl:grid-cols-8
                    ">

                    @foreach ($recentEntities->take(8) as $entity)
                        <a href="{{ route('entities.show', $entity) }}"
                            class="
                                group
                                min-w-0
                            ">

                            <div
                                class="
                                    aspect-square
                                    overflow-hidden
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-slate-100
                                ">

                                @if ($entity->image_url)
                                    <img src="{{ $entity->image_url }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                            transition
                                            duration-300
                                            group-hover:scale-105
                                        ">
                                @else
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            text-4xl
                                            text-indigo-300
                                        ">
                                        ✦
                                    </div>
                                @endif

                            </div>


                            <p
                                class="
                                    mt-2
                                    truncate
                                    text-center
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                {{ $entity->name }}
                            </p>

                        </a>
                    @endforeach

                </div>

            </section>


            {{-- CATÁLOGO VISUAL --}}
            <section x-show="
                    sections.catalogs
                ">

                <div
                    class="
                        flex
                        items-end
                        justify-between
                    ">

                    <div>

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-fuchsia-500
                            ">
                            Catálogo
                        </p>


                        <h3
                            class="
                                mt-1
                                text-2xl
                                font-black
                                text-slate-900
                            ">
                            Elementos recientes
                        </h3>

                    </div>


                    <a href="{{ route('attribute-options.index') }}"
                        class="
                            text-xs
                            font-black
                            text-indigo-600
                        ">
                        Ver Catálogo →
                    </a>

                </div>


                <div
                    class="
                        mt-4
                        grid
                        grid-cols-3
                        gap-3
                        sm:grid-cols-5
                        md:grid-cols-6
                        xl:grid-cols-10
                    ">

                    @foreach ($recentOptions->take(10) as $option)
                        <a href="{{ route('attribute-options.show', $option) }}"
                            class="
                                group
                                min-w-0
                            ">

                            <div
                                class="
                                    aspect-square
                                    overflow-hidden
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-white
                                ">

                                @if ($option->image_url)
                                    <img src="{{ $option->image_url }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                            transition
                                            group-hover:scale-105
                                        ">
                                @else
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            text-3xl
                                            text-fuchsia-300
                                        ">
                                        {{ $option->icon ?: '◆' }}
                                    </div>
                                @endif

                            </div>


                            <p
                                class="
                                    mt-2
                                    truncate
                                    text-center
                                    text-[10px]
                                    font-black
                                    text-slate-700
                                ">
                                {{ $option->name }}
                            </p>

                        </a>
                    @endforeach

                </div>

            </section>


            {{-- COLECCIONES VISUALES --}}
            <section x-show="
                    sections.collections
                ">

                <h3
                    class="
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Colecciones
                </h3>


                <div
                    class="
                        mt-4
                        grid
                        gap-4
                        sm:grid-cols-2
                        lg:grid-cols-3
                    ">

                    @foreach ($recentCollections->take(6) as $collection)
                        <a href="{{ route('collections.show', $collection) }}"
                            class="
                                group
                                relative
                                overflow-hidden
                                rounded-3xl
                                border
                                border-slate-200
                                bg-slate-900
                            ">

                            <div
                                class="
                                    aspect-[16/8]
                                ">

                                @if ($collection->image_url)
                                    <img src="{{ $collection->image_url }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                            opacity-75
                                            transition
                                            group-hover:scale-105
                                        ">
                                @else
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            bg-slate-800
                                            text-5xl
                                            text-slate-500
                                        ">
                                        ▤
                                    </div>
                                @endif

                            </div>


                            <div
                                class="
                                    absolute
                                    inset-x-0
                                    bottom-0
                                    bg-gradient-to-t
                                    from-black/90
                                    to-transparent
                                    px-5
                                    pb-4
                                    pt-10
                                    text-white
                                ">

                                <p
                                    class="
                                        font-black
                                    ">
                                    {{ $collection->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-[10px]
                                        text-white/60
                                    ">
                                    {{ $collection->entities_count }}
                                    entidades
                                </p>

                            </div>

                        </a>
                    @endforeach

                </div>

            </section>


            {{-- ATRIBUTOS VISUALES --}}
            <section x-show="
                    sections.attributes
                ">

                <h3
                    class="
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Atributos
                </h3>


                <div
                    class="
                        mt-4
                        grid
                        gap-3
                        sm:grid-cols-2
                        lg:grid-cols-4
                    ">

                    @foreach ($recentAttributes->take(8) as $attribute)
                        <a href="{{ route('attributes.show', $attribute) }}"
                            class="
                                flex
                                items-center
                                gap-3
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                p-3
                                hover:border-violet-200
                            ">

                            <div
                                class="
                                    h-14
                                    w-14
                                    shrink-0
                                    overflow-hidden
                                    rounded-xl
                                    bg-violet-50
                                ">

                                @if ($attribute->image_url)
                                    <img src="{{ $attribute->image_url }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        ">
                                @else
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            text-violet-500
                                        ">
                                        {{ $attribute->data_type_icon }}
                                    </div>
                                @endif

                            </div>


                            <div class="min-w-0">

                                <p
                                    class="
                                        truncate
                                        text-xs
                                        font-black
                                        text-slate-700
                                    ">
                                    {{ $attribute->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-[9px]
                                        text-slate-400
                                    ">
                                    {{ $attribute->data_type_label }}
                                </p>

                            </div>

                        </a>
                    @endforeach

                </div>

            </section>


            {{-- CONTINUAR / SALUD VISUAL --}}
            <section
                class="
                    grid
                    gap-6
                    xl:grid-cols-2
                ">

                <article x-show="
                        sections.continue
                    "
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                    ">

                    <h3
                        class="
                            text-lg
                            font-black
                            text-slate-900
                        ">
                        Continuar
                    </h3>


                    <div
                        class="
                            mt-4
                            grid
                            grid-cols-4
                            gap-3
                        ">

                        @foreach ($workspaceItems->take(4) as $item)
                            <a href="{{ $item['url'] }}"
                                class="
                                    min-w-0
                                ">

                                <div
                                    class="
                                        aspect-square
                                        overflow-hidden
                                        rounded-xl
                                        bg-slate-100
                                    ">

                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}"
                                            class="
                                                h-full
                                                w-full
                                                object-cover
                                            ">
                                    @else
                                        <div class="
                                                flex
                                                h-full
                                                items-center
                                                justify-center
                                                text-2xl
                                            "
                                            style="
                                                color:
                                                    {{ $item['color'] }};
                                            ">
                                            {{ $item['icon'] }}
                                        </div>
                                    @endif

                                </div>


                                <p
                                    class="
                                        mt-2
                                        truncate
                                        text-center
                                        text-[10px]
                                        font-bold
                                        text-slate-600
                                    ">
                                    {{ $item['name'] }}
                                </p>

                            </a>
                        @endforeach

                    </div>

                </article>


                <article x-show="
                        sections.health
                    "
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                    ">

                    <h3
                        class="
                            text-lg
                            font-black
                            text-slate-900
                        ">
                        Estado de Biblioteca
                    </h3>


                    <div
                        class="
                            mt-4
                            flex
                            flex-wrap
                            gap-2
                        ">

                        @foreach ($healthItems as $health)
                            <a href="{{ $health['url'] }}"
                                class="
                                    rounded-full
                                    border
                                    px-3
                                    py-2
                                    text-[10px]
                                    font-bold

                                    {{ $health['count'] > 0
                                        ? 'border-amber-200 bg-amber-50 text-amber-700'
                                        : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}
                                ">
                                {{ $health['count'] > 0 ? $health['count'] : '✓' }}

                                {{ $health['label'] }}
                            </a>
                        @endforeach

                    </div>

                </article>

            </section>

        </div>


        {{-- ===================================================== --}}
        {{-- MODAL PERSONALIZACIÓN --}}
        {{-- ===================================================== --}}

        <div x-show="
                customizeOpen
            " x-cloak
            class="
                fixed
                inset-0
                z-[100]
                flex
                items-center
                justify-center
                bg-slate-950/50
                p-4
                backdrop-blur-sm
            ">

            <div @click.outside="
                    customizeOpen = false
                "
                class="
                    w-full
                    max-w-lg
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    shadow-2xl
                ">

                <div
                    class="
                        flex
                        items-start
                        justify-between
                        gap-4
                    ">

                    <div>

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-indigo-500
                            ">
                            Preferencias
                        </p>


                        <h3
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Personalizar Dashboard
                        </h3>


                        <p
                            class="
                                mt-2
                                text-sm
                                leading-6
                                text-slate-500
                            ">
                            Decide qué información deseas
                            conservar visible.
                        </p>

                    </div>


                    <button type="button"
                        @click="
                            customizeOpen = false
                        "
                        class="
                            flex
                            h-9
                            w-9
                            items-center
                            justify-center
                            rounded-xl
                            bg-slate-100
                            text-slate-500
                        ">
                        ×
                    </button>

                </div>


                <div class="
                        mt-6
                        space-y-2
                    ">

                    <template
                        x-for="
                            option
                            in sectionOptions
                        "
                        :key="option.key">

                        <label
                            class="
                                flex
                                cursor-pointer
                                items-center
                                justify-between
                                gap-4
                                rounded-xl
                                border
                                border-slate-100
                                px-4
                                py-3
                                hover:bg-slate-50
                            ">

                            <div>

                                <p class="
                                        text-sm
                                        font-bold
                                        text-slate-700
                                    "
                                    x-text="
                                        option.label
                                    ">
                                </p>


                                <p class="
                                        mt-0.5
                                        text-[10px]
                                        text-slate-400
                                    "
                                    x-text="
                                        option.description
                                    ">
                                </p>

                            </div>


                            <input type="checkbox"
                                x-model="
                                    sections[
                                        option.key
                                    ]
                                "
                                @change="
                                    saveSections()
                                "
                                class="
                                    rounded
                                    border-slate-300
                                    text-indigo-600
                                    focus:ring-indigo-500
                                ">

                        </label>

                    </template>

                </div>


                <div
                    class="
                        mt-6
                        flex
                        justify-between
                        gap-3
                        border-t
                        border-slate-100
                        pt-5
                    ">

                    <button type="button"
                        @click="
                            resetPreferences()
                        "
                        class="
                            text-xs
                            font-bold
                            text-slate-500
                            hover:text-red-600
                        ">
                        Restaurar
                    </button>


                    <button type="button"
                        @click="
                            customizeOpen = false
                        "
                        class="
                            rounded-xl
                            bg-indigo-600
                            px-5
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Listo
                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ========================================================= --}}

    <script>
        function dashboardWorkspace(
            config
        ) {

            const defaultSections = {

                continue: true,

                quick: true,

                activity: true,

                health: true,

                entities: true,

                attributes: true,

                catalogs: true,

                collections: true,

                insights: true
            };


            return {

                /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                */

                view: 'summary',

                createOpen: false,

                customizeOpen: false,

                searchOpen: false,

                searching: false,

                searchQuery: '',

                searchResults: [],

                sections: {
                    ...defaultSections
                },


                sectionOptions: [

                    {
                        key: 'continue',

                        label: 'Continuar trabajando',

                        description: 'Recursos modificados recientemente.'
                    },

                    {
                        key: 'quick',

                        label: 'Acciones rápidas',

                        description: 'Atajos para crear recursos.'
                    },

                    {
                        key: 'activity',

                        label: 'Actividad reciente',

                        description: 'Creaciones y modificaciones recientes.'
                    },

                    {
                        key: 'health',

                        label: 'Estado de Biblioteca',

                        description: 'Elementos pendientes por organizar.'
                    },

                    {
                        key: 'entities',

                        label: 'Entidades recientes',

                        description: 'Tus últimas creaciones.'
                    },

                    {
                        key: 'attributes',

                        label: 'Atributos recientes',

                        description: 'Características creadas recientemente.'
                    },

                    {
                        key: 'catalogs',

                        label: 'Catálogo reciente',

                        description: 'Últimos elementos añadidos.'
                    },

                    {
                        key: 'collections',

                        label: 'Colecciones',

                        description: 'Tus agrupaciones recientes.'
                    },

                    {
                        key: 'insights',

                        label: 'Distribuciones',

                        description: 'Tipos y Catálogos principales.'
                    }
                ],


                /*
                |--------------------------------------------------------------------------
                | Inicialización
                |--------------------------------------------------------------------------
                */

                init() {

                    this.loadView();

                    this.loadSections();
                },


                /*
                |--------------------------------------------------------------------------
                | Vista
                |--------------------------------------------------------------------------
                */

                setView(
                    value
                ) {

                    const allowed = [
                        'summary',
                        'compact',
                        'visual'
                    ];


                    if (
                        !allowed.includes(
                            value
                        )
                    ) {
                        value =
                            'summary';
                    }


                    this.view =
                        value;


                    localStorage.setItem(
                        'omnimerge.dashboard.view',
                        value
                    );
                },


                loadView() {

                    const saved =
                        localStorage.getItem(
                            'omnimerge.dashboard.view'
                        );


                    if (
                        [
                            'summary',
                            'compact',
                            'visual'
                        ].includes(
                            saved
                        )
                    ) {

                        this.view =
                            saved;
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | Secciones
                |--------------------------------------------------------------------------
                */

                loadSections() {

                    try {

                        const saved =
                            JSON.parse(
                                localStorage.getItem(
                                    'omnimerge.dashboard.sections'
                                )
                            );


                        if (
                            saved &&
                            typeof saved ===
                            'object'
                        ) {

                            this.sections = {

                                ...defaultSections,

                                ...saved
                            };
                        }

                    } catch (
                        error
                    ) {

                        this.sections = {
                            ...defaultSections
                        };
                    }
                },


                saveSections() {

                    localStorage.setItem(

                        'omnimerge.dashboard.sections',

                        JSON.stringify(
                            this.sections
                        )
                    );
                },


                resetPreferences() {

                    this.sections = {
                        ...defaultSections
                    };


                    this.view =
                        'summary';


                    localStorage.removeItem(
                        'omnimerge.dashboard.sections'
                    );


                    localStorage.removeItem(
                        'omnimerge.dashboard.view'
                    );
                },


                /*
                |--------------------------------------------------------------------------
                | Búsqueda
                |--------------------------------------------------------------------------
                */

                async searchLibrary() {

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

                        this.searching =
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
                                url.toString(), {
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                }
                            );


                        if (
                            !response.ok
                        ) {

                            throw new Error(
                                'Search request failed.'
                            );
                        }


                        const data =
                            await response.json();


                        /*
                         * Evita que una respuesta anterior
                         * reemplace una búsqueda nueva.
                         */

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

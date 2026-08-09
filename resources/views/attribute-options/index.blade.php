<x-app-layout>

    <x-slot name="header">
        Catálogos
    </x-slot>


    @php

        $groupedOptions = $options->getCollection()->groupBy('attribute_id');

    @endphp


    <div x-data="{
    
        view: localStorage.getItem(
                'omnimerge.catalogs.view'
            ) ||
            'grid',
    
        density: localStorage.getItem(
                'omnimerge.catalogs.density'
            ) ||
            'compact',
    
        grouped: localStorage.getItem(
                'omnimerge.catalogs.grouped'
            ) ===
            'true',
    
    
        setView(value) {
    
            this.view = value;
    
            localStorage.setItem(
                'omnimerge.catalogs.view',
                value
            );
        },
    
    
        setDensity(value) {
    
            this.density = value;
    
            localStorage.setItem(
                'omnimerge.catalogs.density',
                value
            );
        },
    
    
        toggleGrouped() {
    
            this.grouped = !this.grouped;
    
            localStorage.setItem(
                'omnimerge.catalogs.grouped',
                this.grouped
            );
        }
    }">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <div
            class="
                flex
                flex-col
                justify-between
                gap-5
                sm:flex-row
                sm:items-start
            ">

            <div>

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.16em]
                        text-violet-600
                    ">
                    Biblioteca · Recursos reutilizables
                </p>


                <h2
                    class="
                        mt-2
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    Catálogos
                </h2>


                <p
                    class="
                        mt-2
                        max-w-3xl
                        text-slate-500
                    ">
                    Explora y administra todos los elementos
                    reutilizables que forman parte de tus
                    Catálogos de OmniMerge.
                </p>

            </div>


            <a href="{{ route('attribute-options.create') }}"
                class="
                    rounded-xl
                    bg-violet-600
                    px-5
                    py-3
                    text-center
                    text-sm
                    font-black
                    text-white
                    shadow-lg
                    shadow-violet-600/20
                    hover:bg-violet-700
                ">
                + Nuevo elemento
            </a>

        </div>


        {{-- ===================================================== --}}
        {{-- ESTADÍSTICAS --}}
        {{-- ===================================================== --}}

        <div
            class="
                mt-7
                grid
                grid-cols-2
                gap-3
                md:grid-cols-3
                xl:grid-cols-6
            ">

            @foreach ([
        [
            'label' => 'Elementos',
            'value' => $stats['total'],
            'classes' => 'border-slate-200 bg-white text-slate-900',
        ],

        [
            'label' => 'Catálogos',
            'value' => $stats['catalogs'],
            'classes' => 'border-violet-100 bg-violet-50 text-violet-700',
        ],

        [
            'label' => 'Activos',
            'value' => $stats['active'],
            'classes' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        ],

        [
            'label' => 'En uso',
            'value' => $stats['used'],
            'classes' => 'border-indigo-100 bg-indigo-50 text-indigo-700',
        ],

        [
            'label' => 'Jerárquicos',
            'value' => $stats['hierarchical'],
            'classes' => 'border-cyan-100 bg-cyan-50 text-cyan-700',
        ],

        [
            'label' => 'Archivados',
            'value' => $stats['archived'],
            'classes' => 'border-slate-200 bg-slate-50 text-slate-600',
        ],
    ] as $stat)
                <article
                    class="
                        rounded-2xl
                        border
                        p-4
                        {{ $stat['classes'] }}
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            opacity-60
                        ">
                        {{ $stat['label'] }}
                    </p>


                    <p
                        class="
                            mt-2
                            text-2xl
                            font-black
                        ">
                        {{ $stat['value'] }}
                    </p>

                </article>
            @endforeach

        </div>


        {{-- ===================================================== --}}
        {{-- SELECTOR VISUAL DE CATÁLOGO --}}
        {{-- ===================================================== --}}

        @if ($attributes->isNotEmpty())

            <section class="mt-6">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                    ">

                    <p
                        class="
                            text-xs
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-400
                        ">
                        Filtrar rápidamente por Catálogo
                    </p>


                    @if ($selectedAttribute)
                        <a href="{{ route('attribute-options.index') }}"
                            class="
                                text-xs
                                font-bold
                                text-violet-600
                            ">
                            Ver todos
                        </a>
                    @endif

                </div>


                <div
                    class="
                        mt-3
                        flex
                        gap-3
                        overflow-x-auto
                        pb-2
                    ">

                    @foreach ($attributes as $attribute)
                        <a href="{{ route(
                            'attribute-options.index',
                            array_merge(request()->except(['page', 'attribute']), [
                                'attribute' => $attribute->id,
                            ]),
                        ) }}"
                            class="
                                flex
                                min-w-[190px]
                                items-center
                                gap-3
                                rounded-2xl
                                border
                                p-3
                                transition
                                {{ $attributeId == $attribute->id
                                    ? 'border-violet-400 bg-violet-50 ring-2 ring-violet-100'
                                    : 'border-slate-200 bg-white hover:border-violet-200' }}
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
                                            text-lg
                                        ">
                                        {{ $attribute->icon ?: '◆' }}
                                    </div>
                                @endif

                            </div>


                            <div class="min-w-0">

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
                                        text-[10px]
                                        text-slate-400
                                    ">
                                    {{ $attribute->options_count }}
                                    elementos
                                </p>

                            </div>

                        </a>
                    @endforeach

                </div>

            </section>

        @endif
        {{-- ===================================================== --}}
        {{-- FILTROS RESPONSIVE --}}
        {{-- ===================================================== --}}

        <form method="GET" action="{{ route('attribute-options.index') }}"
            class="
        mt-5
        min-w-0
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-4
        shadow-sm
    ">

            <div
                class="
            grid
            min-w-0
            grid-cols-1
            gap-3
            sm:grid-cols-2
            xl:grid-cols-4
        ">

                {{-- SEARCH --}}
                <input name="search" value="{{ $search }}" placeholder="Nombre, código o descripción..."
                    class="
                w-full
                min-w-0
                rounded-xl
                border-slate-300
                bg-white
                py-2.5
                text-sm
                text-slate-900
                placeholder:text-slate-400
                focus:border-violet-500
                focus:ring-violet-500
                sm:col-span-2
            ">


                {{-- CATÁLOGO --}}
                <select name="attribute"
                    class="
                w-full
                min-w-0
                rounded-xl
                border-slate-300
                bg-white
                py-2.5
                text-sm
                text-slate-900
            ">
                    <option value="">
                        Todos los Catálogos
                    </option>

                    @foreach ($attributes as $attribute)
                        <option value="{{ $attribute->id }}" @selected($attributeId == $attribute->id)>
                            {{ $attribute->name }}
                        </option>
                    @endforeach
                </select>


                {{-- ESTADO --}}
                <select name="status"
                    class="
                w-full
                min-w-0
                rounded-xl
                border-slate-300
                bg-white
                py-2.5
                text-sm
                text-slate-900
            ">
                    <option value="">
                        Todo estado
                    </option>

                    <option value="ACTIVE" @selected($status === 'ACTIVE')>
                        Activo
                    </option>

                    <option value="INACTIVE" @selected($status === 'INACTIVE')>
                        Inactivo
                    </option>

                    <option value="ARCHIVED" @selected($status === 'ARCHIVED')>
                        Archivado
                    </option>
                </select>


                {{-- IMAGEN --}}
                <select name="image"
                    class="
                w-full
                min-w-0
                rounded-xl
                border-slate-300
                bg-white
                py-2.5
                text-sm
                text-slate-900
            ">
                    <option value="">
                        Cualquier imagen
                    </option>

                    <option value="yes" @selected($image === 'yes')>
                        Con imagen
                    </option>

                    <option value="no" @selected($image === 'no')>
                        Sin imagen
                    </option>
                </select>


                {{-- JERARQUÍA --}}
                <select name="hierarchy"
                    class="
                w-full
                min-w-0
                rounded-xl
                border-slate-300
                bg-white
                py-2.5
                text-sm
                text-slate-900
            ">
                    <option value="">
                        Toda jerarquía
                    </option>

                    <option value="root" @selected($hierarchy === 'root')>
                        Nivel principal
                    </option>

                    <option value="child" @selected($hierarchy === 'child')>
                        Tiene superior
                    </option>

                    <option value="has_children" @selected($hierarchy === 'has_children')>
                        Tiene subelementos
                    </option>
                </select>


                {{-- USO --}}
                <select name="usage"
                    class="
                w-full
                min-w-0
                rounded-xl
                border-slate-300
                bg-white
                py-2.5
                text-sm
                text-slate-900
            ">
                    <option value="">
                        Cualquier uso
                    </option>

                    <option value="used" @selected($usage === 'used')>
                        En uso
                    </option>

                    <option value="unused" @selected($usage === 'unused')>
                        Sin utilizar
                    </option>
                </select>


                {{-- ORDEN --}}
                <select name="sort"
                    class="
                w-full
                min-w-0
                rounded-xl
                border-slate-300
                bg-white
                py-2.5
                text-sm
                text-slate-900
            ">
                    @foreach ([
        'manual' => 'Orden personalizado',
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'code_asc' => 'Código ascendente',
        'code_desc' => 'Código descendente',
        'usage_desc' => 'Más utilizados',
        'usage_asc' => 'Menos utilizados',
        'children_desc' => 'Más subelementos',
        'children_asc' => 'Menos subelementos',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

            </div>


            {{-- ACCIONES --}}
            <div
                class="
            mt-3
            flex
            flex-col
            gap-3
            border-t
            border-slate-100
            pt-3
            sm:flex-row
            sm:items-center
            sm:justify-between
        ">

                <div>

                    @if ($search || $attributeId || $status || $image || $hierarchy || $usage || $sort !== 'manual' || $perPage !== 24)
                        <a href="{{ route('attribute-options.index') }}"
                            class="
                        text-xs
                        font-bold
                        text-slate-500
                        hover:text-violet-600
                    ">
                            × Limpiar filtros
                        </a>
                    @endif

                </div>


                <div class="flex w-full gap-2 sm:w-auto">

                    <select name="per_page"
                        class="
                    min-w-0
                    flex-1
                    rounded-xl
                    border-slate-300
                    bg-white
                    py-2.5
                    text-sm
                    text-slate-900
                    sm:w-32
                ">
                        @foreach ([12, 24, 48, 96] as $number)
                            <option value="{{ $number }}" @selected($perPage === $number)>
                                {{ $number }}/pág.
                            </option>
                        @endforeach
                    </select>


                    <button type="submit"
                        class="
                    shrink-0
                    rounded-xl
                    bg-slate-900
                    px-5
                    py-2.5
                    text-sm
                    font-black
                    text-white
                ">
                        Aplicar
                    </button>

                </div>

            </div>

        </form>


        {{-- ===================================================== --}}
        {{-- OPCIONES DE VISTA --}}
        {{-- ===================================================== --}}

        <div
            class="
                mt-5
                flex
                flex-col
                justify-between
                gap-4
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-3
                shadow-sm
                lg:flex-row
                lg:items-center
            ">

            <div
                class="
                    flex
                    flex-wrap
                    items-center
                    gap-2
                ">

                <span
                    class="
                        mr-1
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Vista
                </span>


                @foreach ([
        'gallery' => '▦ Galería',
        'grid' => '▦ Cuadrícula',
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
                            'bg-violet-600 text-white'
                        
                            :
                            'bg-slate-100 text-slate-500'"
                        class="
                            rounded-lg
                            px-3
                            py-2
                            text-xs
                            font-bold
                        ">
                        {{ $label }}
                    </button>
                @endforeach

            </div>


            <div
                class="
                    flex
                    flex-wrap
                    items-center
                    gap-2
                ">

                <div x-show="
                        view === 'grid'
                    "
                    class="
                        flex
                        flex-wrap
                        gap-2
                    ">

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
                                'bg-slate-900 text-white'
                            
                                :
                                'bg-slate-100 text-slate-500'"
                            class="
                                rounded-lg
                                px-3
                                py-2
                                text-xs
                                font-bold
                            ">
                            {{ $label }}
                        </button>
                    @endforeach

                </div>


                <button type="button" x-show="
                        view !== 'table'
                    "
                    @click="
                        toggleGrouped()
                    "
                    :class="grouped
                    
                        ?
                        'bg-violet-100 text-violet-700'
                    
                        :
                        'bg-slate-100 text-slate-500'"
                    class="
                        rounded-lg
                        px-3
                        py-2
                        text-xs
                        font-bold
                    ">
                    ◫ Agrupar por Catálogo
                </button>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- EMPTY --}}
        {{-- ===================================================== --}}

        @if ($options->isEmpty())

            <div
                class="
                    mt-6
                    rounded-3xl
                    border
                    border-dashed
                    border-slate-300
                    bg-white
                    py-20
                    text-center
                ">

                <div class="text-4xl">
                    ◆
                </div>


                <h3
                    class="
                        mt-4
                        font-black
                        text-slate-800
                    ">
                    No se encontraron elementos
                </h3>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Crea Naruto, Fuego, Perú,
                    Legendario u otro elemento reutilizable.
                </p>


                <a href="{{ route('attribute-options.create') }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-violet-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear elemento
                </a>

            </div>
        @else
            {{-- ===================================================== --}}
            {{-- GALERÍA NORMAL --}}
            {{-- ===================================================== --}}

            <div x-cloak x-show="
        view === 'gallery'
        &&
        ! grouped
    "
                class="
        mt-6
        grid
        grid-cols-3
        gap-3
        sm:grid-cols-4
        md:grid-cols-5
        lg:grid-cols-7
        xl:grid-cols-8
        2xl:grid-cols-10
    ">

                @foreach ($options as $option)
                    @include('attribute-options.partials.index-gallery-card', [
                        'option' => $option,
                    ])
                @endforeach

            </div>


            {{-- ===================================================== --}}
            {{-- GALERÍA AGRUPADA POR CATÁLOGO --}}
            {{-- ===================================================== --}}

            <div x-cloak x-show="
        view === 'gallery'
        &&
        grouped
    "
                class="
        mt-8
        space-y-10
    ">

                @foreach ($groupedOptions as $group)
                    @php

                        $groupAttribute = $group->first()->attribute;

                    @endphp


                    <section>

                        <div
                            class="
                    flex
                    min-w-0
                    items-center
                    gap-3
                ">

                            <div
                                class="
                        h-10
                        w-10
                        shrink-0
                        overflow-hidden
                        rounded-xl
                        bg-slate-100
                    ">

                                @if ($groupAttribute->image_url)
                                    <img src="{{ $groupAttribute->image_url }}" alt="{{ $groupAttribute->name }}"
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
                            ">
                                        {{ $groupAttribute->icon ?: '◆' }}
                                    </div>
                                @endif

                            </div>


                            <div class="min-w-0">

                                <h3
                                    class="
                            truncate
                            font-black
                            text-slate-900
                        ">
                                    {{ $groupAttribute->name }}
                                </h3>


                                <p
                                    class="
                            mt-0.5
                            text-xs
                            text-slate-400
                        ">
                                    {{ $group->count() }}
                                    en esta página
                                </p>

                            </div>

                        </div>


                        <div
                            class="
                    mt-4
                    grid
                    grid-cols-3
                    gap-3
                    sm:grid-cols-4
                    md:grid-cols-5
                    lg:grid-cols-7
                    xl:grid-cols-8
                    2xl:grid-cols-10
                ">

                            @foreach ($group as $option)
                                @include('attribute-options.partials.index-gallery-card', [
                                    'option' => $option,
                                ])
                            @endforeach

                        </div>

                    </section>
                @endforeach

            </div>
            {{-- ================================================= --}}
            {{-- GRID NORMAL --}}
            {{-- ================================================= --}}

            <div x-show="
                    view === 'grid'
                    &&
                    ! grouped
                "
                class="
                    mt-6
                    grid
                    gap-4
                "
                :class="{
                
                    'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5': density === 'compact',
                
                    'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': density === 'medium',
                
                    'sm:grid-cols-2 xl:grid-cols-3': density === 'large'
                }">

                @foreach ($options as $option)
                    @include('attribute-options.partials.index-card', [
                        'option' => $option,
                    ])
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- GRID AGRUPADO --}}
            {{-- ================================================= --}}

            <div x-cloak
                x-show="
                    view === 'grid'
                    &&
                    grouped
                "
                class="
                    mt-8
                    space-y-10
                ">

                @foreach ($groupedOptions as $group)
                    @php
                        $groupAttribute = $group->first()->attribute;
                    @endphp


                    <section>

                        <div
                            class="
                                flex
                                items-center
                                gap-3
                            ">

                            <div
                                class="
                                    h-10
                                    w-10
                                    overflow-hidden
                                    rounded-xl
                                    bg-slate-100
                                ">

                                @if ($groupAttribute->image_url)
                                    <img src="{{ $groupAttribute->image_url }}"
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
                                        ">
                                        {{ $groupAttribute->icon ?: '◆' }}
                                    </div>
                                @endif

                            </div>


                            <div>

                                <h3
                                    class="
                                        font-black
                                        text-slate-900
                                    ">
                                    {{ $groupAttribute->name }}
                                </h3>


                                <p
                                    class="
                                        text-xs
                                        text-slate-400
                                    ">
                                    {{ $group->count() }}
                                    en esta página
                                </p>

                            </div>

                        </div>


                        <div class="
                                mt-4
                                grid
                                gap-4
                            "
                            :class="{
                            
                                'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5': density === 'compact',
                            
                                'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': density === 'medium',
                            
                                'sm:grid-cols-2 xl:grid-cols-3': density === 'large'
                            }">

                            @foreach ($group as $option)
                                @include('attribute-options.partials.index-card', [
                                    'option' => $option,
                                ])
                            @endforeach

                        </div>

                    </section>
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- LIST NORMAL --}}
            {{-- ================================================= --}}

            <div x-cloak
                x-show="
                    view === 'list'
                    &&
                    ! grouped
                "
                class="
                    mt-6
                    space-y-3
                ">

                @foreach ($options as $option)
                    @include('attribute-options.partials.index-list-item', [
                        'option' => $option,
                    ])
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- LIST AGRUPADA --}}
            {{-- ================================================= --}}

            <div x-cloak
                x-show="
                    view === 'list'
                    &&
                    grouped
                "
                class="
                    mt-8
                    space-y-10
                ">

                @foreach ($groupedOptions as $group)
                    @php
                        $groupAttribute = $group->first()->attribute;
                    @endphp


                    <section>

                        <h3
                            class="
                                text-lg
                                font-black
                                text-slate-900
                            ">
                            {{ $groupAttribute->name }}
                        </h3>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-400
                            ">
                            {{ $groupAttribute->code }}
                        </p>


                        <div
                            class="
                                mt-4
                                space-y-3
                            ">

                            @foreach ($group as $option)
                                @include('attribute-options.partials.index-list-item', [
                                    'option' => $option,
                                ])
                            @endforeach

                        </div>

                    </section>
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- TABLE --}}
            {{-- ================================================= --}}

            <div x-cloak x-show="
                    view === 'table'
                "
                class="
                    mt-6
                    overflow-hidden
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    shadow-sm
                ">

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr>

                                @foreach (['Elemento', 'Código', 'Catálogo', 'Superior', 'Hijos', 'Usos', 'Estado', ''] as $heading)
                                    <th
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-3
                                            text-left
                                            text-[10px]
                                            font-black
                                            uppercase
                                            tracking-wider
                                            text-slate-400
                                        ">
                                        {{ $heading }}
                                    </th>
                                @endforeach

                            </tr>

                        </thead>


                        <tbody
                            class="
                                divide-y
                                divide-slate-100
                            ">

                            @foreach ($options as $option)
                                <tr class="hover:bg-slate-50">

                                    {{-- ELEMENT --}}
                                    <td
                                        class="
                                            px-5
                                            py-4
                                        ">

                                        <div
                                            class="
                                                flex
                                                items-center
                                                gap-3
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

                                                @if ($option->image_url)
                                                    <img src="{{ $option->image_url }}"
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
                                                        ">
                                                        {{ $option->icon ?: '◆' }}
                                                    </div>
                                                @endif

                                            </div>


                                            <a href="{{ route('attribute-options.show', $option) }}"
                                                class="
                                                    font-bold
                                                    text-slate-900
                                                    hover:text-violet-700
                                                ">
                                                {{ $option->name }}
                                            </a>

                                        </div>

                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-mono
                                            text-xs
                                            font-black
                                            text-slate-600
                                        ">
                                        {{ $option->code }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            text-sm
                                            font-bold
                                            text-slate-700
                                        ">
                                        {{ $option->attribute->name }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            text-sm
                                            text-slate-500
                                        ">
                                        {{ $option->parent?->name ?? '—' }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $option->children_count }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $option->values_count }}
                                    </td>


                                    <td class="px-5 py-4">

                                        <x-status-badge :status="$option->status" />

                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                        ">

                                        <a href="{{ route('attribute-options.show', $option) }}"
                                            class="
                                                text-xs
                                                font-black
                                                text-violet-600
                                            ">
                                            Abrir
                                        </a>

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @endif


        {{-- ===================================================== --}}
        {{-- PAGINATION --}}
        {{-- ===================================================== --}}

        <div class="mt-8">

            {{ $options->links() }}

        </div>

    </div>

</x-app-layout>

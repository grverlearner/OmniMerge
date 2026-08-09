<x-app-layout>

    <x-slot name="header">
        Atributos
    </x-slot>
    @include('attributes.partials.section-navigation')

    <div x-data="{
    
        view: localStorage.getItem(
                'omnimerge.attributes.view'
            ) ||
            'grid',
    
        density: localStorage.getItem(
                'omnimerge.attributes.density'
            ) ||
            'compact',
    
    
        setView(value) {
    
            this.view = value;
    
            localStorage.setItem(
                'omnimerge.attributes.view',
                value
            );
        },
    
    
        setDensity(value) {
    
            this.density = value;
    
            localStorage.setItem(
                'omnimerge.attributes.density',
                value
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
                        text-indigo-600
                    ">
                    Biblioteca · Características
                </p>


                <h2
                    class="
                        mt-2
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    Atributos
                </h2>


                <p
                    class="
                        mt-2
                        max-w-2xl
                        text-slate-500
                    ">
                    Construye características reutilizables,
                    Catálogos y reglas que posteriormente
                    podrán utilizarse en todo OmniMerge.
                </p>

            </div>


            <a href="{{ route('attributes.create') }}"
                class="
                    rounded-xl
                    bg-indigo-600
                    px-5
                    py-3
                    text-center
                    text-sm
                    font-black
                    text-white
                    shadow-lg
                    shadow-indigo-600/20
                    hover:bg-indigo-700
                ">
                + Nuevo atributo
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
                lg:grid-cols-5
            ">

            @foreach ([
        [
            'label' => 'Total',
            'value' => $stats['total'],
            'class' => 'border-slate-200 bg-white text-slate-900',
        ],

        [
            'label' => 'Catálogo',
            'value' => $stats['catalog'],
            'class' => 'border-indigo-100 bg-indigo-50 text-indigo-700',
        ],

        [
            'label' => 'Sí / No',
            'value' => $stats['boolean'],
            'class' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        ],

        [
            'label' => 'Públicos',
            'value' => $stats['public'],
            'class' => 'border-cyan-100 bg-cyan-50 text-cyan-700',
        ],

        [
            'label' => 'En uso',
            'value' => $stats['used'],
            'class' => 'border-violet-100 bg-violet-50 text-violet-700',
        ],
    ] as $stat)
                <article
                    class="
                        rounded-2xl
                        border
                        p-4
                        {{ $stat['class'] }}
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
        {{-- FILTROS RESPONSIVE --}}
        {{-- ===================================================== --}}

        <form method="GET" action="{{ route('attributes.index') }}"
            class="
        mt-6
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

                {{-- BÚSQUEDA --}}
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
                focus:border-indigo-500
                focus:ring-indigo-500
                sm:col-span-2
            ">


                {{-- TIPO --}}
                <select name="data_type"
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
                        Todos los tipos
                    </option>

                    @foreach ([
        'OPTION' => 'Catálogo',
        'BOOLEAN' => 'Sí / No',
        'TEXT' => 'Texto corto',
        'LONG_TEXT' => 'Texto largo',
        'INTEGER' => 'Número entero',
        'DECIMAL' => 'Número decimal',
        'DATE' => 'Fecha',
        'COLOR' => 'Color',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($dataType === $value)>
                            {{ $label }}
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
                        Todos los estados
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


                {{-- VISIBILIDAD --}}
                <select name="scope"
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
                        Toda visibilidad
                    </option>

                    <option value="PUBLIC" @selected($scope === 'PUBLIC')>
                        Público
                    </option>

                    <option value="PRIVATE" @selected($scope === 'PRIVATE')>
                        Privado
                    </option>

                    <option value="UNLISTED" @selected($scope === 'UNLISTED')>
                        No listado
                    </option>
                </select>


                {{-- GRUPO --}}
                <select name="group"
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
                        Todos los grupos
                    </option>

                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected($groupId == $group->id)>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>


                {{-- SELECCIÓN --}}
                <select name="multiple"
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
                        Cualquier selección
                    </option>

                    <option value="yes" @selected($multiple === 'yes')>
                        Solo múltiples
                    </option>

                    <option value="no" @selected($multiple === 'no')>
                        Solo únicos
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
        'catalog_desc' => 'Catálogo más grande',
        'catalog_asc' => 'Catálogo más pequeño',
        'usage_desc' => 'Más utilizado',
        'usage_asc' => 'Menos utilizado',
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

                    @if ($search || $dataType || $status || $scope || $multiple || $groupId || $sort !== 'manual' || $perPage !== 24)
                        <a href="{{ route('attributes.index') }}"
                            class="
                        text-xs
                        font-bold
                        text-slate-500
                        hover:text-indigo-600
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
                        @foreach ([12, 24, 48] as $number)
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
                    hover:bg-slate-800
                ">
                        Aplicar
                    </button>

                </div>

            </div>

        </form>


        {{-- ===================================================== --}}
        {{-- CONTROLES DE VISTA --}}
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
                md:flex-row
                md:items-center
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
                            'bg-indigo-600 text-white'
                        
                            :
                            'bg-slate-100 text-slate-500 hover:text-slate-900'"
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


            <div x-show="
                    view === 'grid'
                "
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
                    Tamaño
                </span>


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
                            'bg-slate-100 text-slate-500 hover:text-slate-900'"
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

        </div>


        {{-- ===================================================== --}}
        {{-- VACÍO --}}
        {{-- ===================================================== --}}

        @if ($attributes->isEmpty())

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
                    ☷
                </div>


                <h3
                    class="
                        mt-4
                        font-black
                        text-slate-800
                    ">
                    No se encontraron atributos
                </h3>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Crea atributos como Anime,
                    Elemento, Poder, País o Puede volar.
                </p>


                <a href="{{ route('attributes.create') }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-indigo-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear atributo
                </a>

            </div>
        @else
            {{-- ===================================================== --}}
            {{-- GALERÍA --}}
            {{-- ===================================================== --}}

            <div x-cloak x-show="
        view === 'gallery'
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

                @foreach ($attributes as $attribute)
                    @include('attributes.partials.index-gallery-card', [
                        'attribute' => $attribute,
                    ])
                @endforeach

            </div>
            {{-- ================================================= --}}
            {{-- GRID --}}
            {{-- ================================================= --}}

            <div x-show="view === 'grid'"
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

                @foreach ($attributes as $attribute)
                    <article
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
                            hover:shadow-lg
                        ">

                        <a href="{{ route('attributes.show', $attribute) }}"
                            class="
                                block
                                overflow-hidden
                                bg-slate-100
                            "
                            :class="{
                                'h-28': density === 'compact',
                                'h-36': density === 'medium',
                                'h-48': density === 'large'
                            }">

                            @if ($attribute->image_url)
                                <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
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
                                        text-4xl
                                        font-black
                                    "
                                    style="
                                        background-color:
                                            {{ $attribute->color ?? '#6366F1' }}20;

                                        color:
                                            {{ $attribute->color ?? '#6366F1' }};
                                    ">
                                    {{ $attribute->icon ?: $attribute->data_type_icon }}
                                </div>
                            @endif

                        </a>


                        <div class="p-4"
                            :class="{
                                'p-4': density === 'compact',
                                'p-5': density === 'medium',
                                'p-6': density === 'large'
                            }">

                            <div
                                class="
                                    flex
                                    items-start
                                    justify-between
                                    gap-3
                                ">

                                <div class="min-w-0">

                                    <p
                                        class="
                                            font-mono
                                            text-[10px]
                                            font-black
                                            uppercase
                                            tracking-wider
                                            text-slate-400
                                        ">
                                        {{ $attribute->code }}
                                    </p>


                                    <a href="{{ route('attributes.show', $attribute) }}"
                                        class="
                                            mt-1
                                            block
                                            truncate
                                            font-black
                                            text-slate-900
                                            hover:text-indigo-700
                                        ">
                                        {{ $attribute->name }}
                                    </a>

                                </div>


                                <x-status-badge :status="$attribute->status" />

                            </div>


                            <div
                                class="
                                    mt-3
                                    flex
                                    flex-wrap
                                    gap-1.5
                                ">

                                <span
                                    class="
                                        rounded-full
                                        bg-indigo-50
                                        px-2
                                        py-1
                                        text-[9px]
                                        font-black
                                        text-indigo-700
                                    ">
                                    {{ $attribute->data_type_label }}
                                </span>


                                @if ($attribute->allows_multiple)
                                    <span
                                        class="
                                            rounded-full
                                            bg-violet-50
                                            px-2
                                            py-1
                                            text-[9px]
                                            font-black
                                            text-violet-700
                                        ">
                                        Múltiple
                                    </span>
                                @endif


                                <span
                                    class="
                                        rounded-full
                                        bg-slate-100
                                        px-2
                                        py-1
                                        text-[9px]
                                        font-bold
                                        text-slate-600
                                    ">
                                    {{ $attribute->scope_label }}
                                </span>

                            </div>


                            <p x-show="
                                    density !== 'compact'
                                "
                                class="
                                    mt-3
                                    line-clamp-2
                                    text-sm
                                    leading-6
                                    text-slate-500
                                ">
                                {{ $attribute->description ?: 'Sin descripción.' }}
                            </p>


                            <div
                                class="
                                    mt-4
                                    grid
                                    grid-cols-2
                                    gap-2
                                    border-t
                                    border-slate-100
                                    pt-3
                                ">

                                <div>

                                    <p
                                        class="
                                            text-[9px]
                                            font-bold
                                            uppercase
                                            text-slate-400
                                        ">
                                        Catálogo
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $attribute->options_count }}
                                    </p>

                                </div>


                                <div>

                                    <p
                                        class="
                                            text-[9px]
                                            font-bold
                                            uppercase
                                            text-slate-400
                                        ">
                                        Usos
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $attribute->entity_attributes_count }}
                                    </p>

                                </div>

                            </div>

                        </div>

                    </article>
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- LISTA --}}
            {{-- ================================================= --}}

            <div x-cloak x-show="view === 'list'"
                class="
                    mt-6
                    space-y-3
                ">

                @foreach ($attributes as $attribute)
                    <article
                        class="
                            flex
                            flex-col
                            gap-4
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            p-4
                            shadow-sm
                            md:flex-row
                            md:items-center
                        ">

                        <div
                            class="
                                h-20
                                w-full
                                shrink-0
                                overflow-hidden
                                rounded-xl
                                bg-slate-100
                                md:w-20
                            ">

                            @if ($attribute->image_url)
                                <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
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
                                        font-black
                                    "
                                    style="
                                        background-color:
                                            {{ $attribute->color ?? '#6366F1' }}20;

                                        color:
                                            {{ $attribute->color ?? '#6366F1' }};
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

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-2
                                ">

                                <a href="{{ route('attributes.show', $attribute) }}"
                                    class="
                                        font-black
                                        text-slate-900
                                        hover:text-indigo-700
                                    ">
                                    {{ $attribute->name }}
                                </a>


                                <x-status-badge :status="$attribute->status" />

                            </div>


                            <p
                                class="
                                    mt-1
                                    font-mono
                                    text-[10px]
                                    font-black
                                    text-slate-400
                                ">
                                {{ $attribute->code }}
                                ·
                                {{ $attribute->slug }}
                            </p>


                            <p
                                class="
                                    mt-2
                                    line-clamp-1
                                    text-sm
                                    text-slate-500
                                ">
                                {{ $attribute->description ?: 'Sin descripción.' }}
                            </p>

                        </div>


                        <div
                            class="
                                flex
                                flex-wrap
                                items-center
                                gap-5
                                md:shrink-0
                            ">

                            <div>

                                <p
                                    class="
                                        text-[9px]
                                        font-bold
                                        uppercase
                                        text-slate-400
                                    ">
                                    Tipo
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-bold
                                        text-slate-700
                                    ">
                                    {{ $attribute->data_type_label }}
                                </p>

                            </div>


                            <div>

                                <p
                                    class="
                                        text-[9px]
                                        font-bold
                                        uppercase
                                        text-slate-400
                                    ">
                                    Catálogo
                                </p>

                                <p
                                    class="
                                        mt-1
                                        font-black
                                        text-slate-700
                                    ">
                                    {{ $attribute->options_count }}
                                </p>

                            </div>


                            <div>

                                <p
                                    class="
                                        text-[9px]
                                        font-bold
                                        uppercase
                                        text-slate-400
                                    ">
                                    Usos
                                </p>

                                <p
                                    class="
                                        mt-1
                                        font-black
                                        text-slate-700
                                    ">
                                    {{ $attribute->entity_attributes_count }}
                                </p>

                            </div>


                            <a href="{{ route('attributes.show', $attribute) }}"
                                class="
                                    rounded-xl
                                    bg-indigo-50
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-indigo-700
                                ">
                                Abrir →
                            </a>

                        </div>

                    </article>
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- TABLA --}}
            {{-- ================================================= --}}

            <div x-cloak x-show="view === 'table'"
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

                    <table
                        class="
                            min-w-full
                            divide-y
                            divide-slate-200
                        ">

                        <thead class="bg-slate-50">

                            <tr>

                                @foreach (['Atributo', 'Código', 'Tipo', 'Catálogo', 'Usos', 'Visibilidad', 'Estado', ''] as $heading)
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

                            @foreach ($attributes as $attribute)
                                <tr class="hover:bg-slate-50">

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

                                                @if ($attribute->image_url)
                                                    <img src="{{ $attribute->image_url }}"
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
                                                            color:
                                                                {{ $attribute->color ?? '#6366F1' }};
                                                        ">
                                                        {{ $attribute->icon ?: $attribute->data_type_icon }}
                                                    </div>
                                                @endif

                                            </div>


                                            <div
                                                class="
                                                    min-w-[150px]
                                                ">

                                                <a href="{{ route('attributes.show', $attribute) }}"
                                                    class="
                                                        font-bold
                                                        text-slate-900
                                                        hover:text-indigo-700
                                                    ">
                                                    {{ $attribute->name }}
                                                </a>


                                                <p
                                                    class="
                                                        mt-1
                                                        max-w-[220px]
                                                        truncate
                                                        text-xs
                                                        text-slate-400
                                                    ">
                                                    {{ $attribute->slug }}
                                                </p>

                                            </div>

                                        </div>

                                    </td>


                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            font-mono
                                            text-xs
                                            font-black
                                            text-slate-600
                                        ">
                                        {{ $attribute->code }}
                                    </td>


                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            text-sm
                                            font-bold
                                            text-slate-700
                                        ">
                                        {{ $attribute->data_type_label }}

                                        @if ($attribute->allows_multiple)
                                            · Múltiple
                                        @endif
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $attribute->options_count }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $attribute->entity_attributes_count }}
                                    </td>


                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            text-sm
                                            text-slate-600
                                        ">
                                        {{ $attribute->scope_label }}
                                    </td>


                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                        ">
                                        <x-status-badge :status="$attribute->status" />
                                    </td>


                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                        ">

                                        <a href="{{ route('attributes.show', $attribute) }}"
                                            class="
                                                text-xs
                                                font-black
                                                text-indigo-600
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


        {{-- PAGINACIÓN --}}
        <div class="mt-8">

            {{ $attributes->links() }}

        </div>

    </div>

</x-app-layout>

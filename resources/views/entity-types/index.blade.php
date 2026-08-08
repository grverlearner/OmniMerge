<x-app-layout>

    <x-slot name="header">
        Entidades
    </x-slot>


    @include('entities.partials.section-navigation')


    <div x-data="{
    
        view: localStorage.getItem(
                'omnimerge.entityTypes.view'
            ) ||
            'grid',
    
        density: localStorage.getItem(
                'omnimerge.entityTypes.density'
            ) ||
            'compact',
    
    
        setView(value) {
    
            this.view = value;
    
            localStorage.setItem(
                'omnimerge.entityTypes.view',
                value
            );
        },
    
    
        setDensity(value) {
    
            this.density = value;
    
            localStorage.setItem(
                'omnimerge.entityTypes.density',
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
                    Entidades · Configuración
                </p>


                <h2
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Tipos de entidad
                </h2>


                <p
                    class="
                        mt-2
                        max-w-2xl
                        text-slate-500
                    ">
                    Organiza tus entidades mediante categorías
                    reutilizables y personalizadas.
                </p>

            </div>


            <div
                class="
                    flex
                    flex-wrap
                    gap-3
                ">

                <a href="{{ route('entities.index') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-300
                        bg-white
                        px-5
                        py-3
                        text-sm
                        font-bold
                        text-slate-700
                        hover:bg-slate-50
                    ">
                    ← Mis entidades
                </a>


                <a href="{{ route('entity-types.create') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-indigo-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                        shadow-lg
                        shadow-indigo-600/20
                        transition
                        hover:bg-indigo-700
                    ">
                    + Nuevo tipo
                </a>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- RESUMEN --}}
        {{-- ===================================================== --}}

        <div
            class="
                mt-7
                grid
                gap-3
                grid-cols-2
                lg:grid-cols-4
            ">

            {{-- TOTAL --}}
            <div
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                    shadow-sm
                ">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Total
                </p>

                <p
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    {{ $stats['total'] }}
                </p>

            </div>


            {{-- ACTIVOS --}}
            <div
                class="
                    rounded-2xl
                    border
                    border-emerald-100
                    bg-emerald-50
                    p-4
                ">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wider
                        text-emerald-500
                    ">
                    Activos
                </p>

                <p
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-emerald-700
                    ">
                    {{ $stats['active'] }}
                </p>

            </div>


            {{-- INACTIVOS --}}
            <div
                class="
                    rounded-2xl
                    border
                    border-amber-100
                    bg-amber-50
                    p-4
                ">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wider
                        text-amber-500
                    ">
                    Inactivos
                </p>

                <p
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-amber-700
                    ">
                    {{ $stats['inactive'] }}
                </p>

            </div>


            {{-- ARCHIVADOS --}}
            <div
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-slate-50
                    p-4
                ">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Archivados
                </p>

                <p
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-700
                    ">
                    {{ $stats['archived'] }}
                </p>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- FILTROS --}}
        {{-- ===================================================== --}}

        <form method="GET"
            class="
                mt-6
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
                    gap-3
                    lg:grid-cols-[minmax(240px,1fr)_170px_210px_130px_auto]
                ">

                {{-- BUSCAR --}}
                <input name="search" value="{{ $search }}"
                    placeholder="Buscar por nombre, código o descripción..."
                    class="
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-slate-900
                        placeholder:text-slate-400
                        focus:border-indigo-500
                        focus:text-slate-900
                        focus:ring-indigo-500
                    ">


                {{-- ESTADO --}}
                <select name="status"
                    class="
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-slate-900
                        focus:border-indigo-500
                        focus:ring-indigo-500
                    ">

                    <option value="">
                        Todos los estados
                    </option>

                    <option value="ACTIVE" @selected($status === 'ACTIVE')>
                        Activos
                    </option>

                    <option value="INACTIVE" @selected($status === 'INACTIVE')>
                        Inactivos
                    </option>

                    <option value="ARCHIVED" @selected($status === 'ARCHIVED')>
                        Archivados
                    </option>

                </select>


                {{-- ORDEN --}}
                <select name="sort"
                    class="
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-slate-900
                        focus:border-indigo-500
                        focus:ring-indigo-500
                    ">

                    @foreach ([
        'manual' => 'Orden personalizado',

        'newest' => 'Más recientes',

        'oldest' => 'Más antiguos',

        'name_asc' => 'Nombre A → Z',

        'name_desc' => 'Nombre Z → A',

        'code_asc' => 'Código ascendente',

        'code_desc' => 'Código descendente',

        'entities_desc' => 'Más entidades',

        'entities_asc' => 'Menos entidades',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>


                {{-- CANTIDAD --}}
                <select name="per_page"
                    class="
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-slate-900
                        focus:border-indigo-500
                        focus:ring-indigo-500
                    ">

                    @foreach ([12, 24, 48] as $number)
                        <option value="{{ $number }}" @selected($perPage === $number)>
                            {{ $number }} / pág.
                        </option>
                    @endforeach

                </select>


                {{-- BUSCAR --}}
                <button type="submit"
                    class="
                        rounded-xl
                        bg-slate-900
                        px-5
                        py-3
                        text-sm
                        font-bold
                        text-white
                        hover:bg-slate-800
                    ">
                    Aplicar
                </button>

            </div>


            @if ($search || $status || $sort !== 'manual' || $perPage !== 24)
                <div class="mt-3">

                    <a href="{{ route('entity-types.index') }}"
                        class="
                            text-xs
                            font-bold
                            text-slate-500
                            hover:text-indigo-600
                        ">
                        × Limpiar filtros
                    </a>

                </div>
            @endif

        </form>


        {{-- ===================================================== --}}
        {{-- OPCIONES DE VISUALIZACIÓN --}}
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

            {{-- VISTA --}}
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
                        font-bold
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Vista
                </span>


                <button type="button" @click="
                        setView('grid')
                    "
                    :class="view === 'grid'
                    
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
                        transition
                    ">
                    ▦ Cuadrícula
                </button>


                <button type="button" @click="
                        setView('list')
                    "
                    :class="view === 'list'
                    
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
                        transition
                    ">
                    ☰ Lista
                </button>


                <button type="button" @click="
                        setView('table')
                    "
                    :class="view === 'table'
                    
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
                        transition
                    ">
                    ≡ Tabla
                </button>

            </div>


            {{-- DENSIDAD --}}
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
                        font-bold
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
                        :class="density
                        === '{{ $value }}'
                        
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
                            transition
                        ">
                        {{ $label }}
                    </button>
                @endforeach

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- VACÍO --}}
        {{-- ===================================================== --}}

        @if ($entityTypes->isEmpty())

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

                <div
                    class="
                        mx-auto
                        flex
                        h-16
                        w-16
                        items-center
                        justify-center
                        rounded-2xl
                        bg-indigo-50
                        text-3xl
                        text-indigo-400
                    ">
                    ◇
                </div>


                <h3
                    class="
                        mt-5
                        text-lg
                        font-black
                        text-slate-800
                    ">
                    No se encontraron tipos
                </h3>


                <p
                    class="
                        mx-auto
                        mt-2
                        max-w-sm
                        text-sm
                        text-slate-500
                    ">
                    Crea una categoría como Personaje,
                    País, Animal, Objeto o cualquier
                    clasificación que necesites.
                </p>


                <a href="{{ route('entity-types.create') }}"
                    class="
                        mt-6
                        inline-flex
                        rounded-xl
                        bg-indigo-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear primer tipo
                </a>

            </div>
        @else
            {{-- ================================================= --}}
            {{-- CUADRÍCULA --}}
            {{-- ================================================= --}}

            <div x-show="
                    view === 'grid'
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

                @foreach ($entityTypes as $type)
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

                        {{-- IMAGEN --}}
                        <a href="{{ route('entity-types.show', $type) }}"
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

                            @if ($type->image_url)
                                <img src="{{ $type->image_url }}" alt="{{ $type->name }}"
                                    class="
                                        h-full
                                        w-full
                                        object-cover
                                        transition
                                        duration-300
                                        group-hover:scale-[1.02]
                                    ">
                            @else
                                <div class="
                                        flex
                                        h-full
                                        w-full
                                        items-center
                                        justify-center
                                        text-4xl
                                        font-black
                                    "
                                    style="
                                        background-color:
                                            {{ $type->color ?? '#6366F1' }}20;

                                        color:
                                            {{ $type->color ?? '#6366F1' }};
                                    ">
                                    {{ $type->icon ?: '◇' }}
                                </div>
                            @endif

                        </a>


                        {{-- CONTENIDO --}}
                        <div class="
                                p-4
                            "
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
                                            truncate
                                            font-mono
                                            text-[10px]
                                            font-black
                                            uppercase
                                            tracking-wider
                                            text-slate-400
                                        ">
                                        {{ $type->code }}
                                    </p>


                                    <a href="{{ route('entity-types.show', $type) }}"
                                        class="
                                            mt-1
                                            block
                                            truncate
                                            font-black
                                            text-slate-900
                                            hover:text-indigo-700
                                        "
                                        :class="{
                                            'text-base': density
                                            === 'compact',
                                        
                                            'text-lg': density
                                            !== 'compact'
                                        }">
                                        {{ $type->name }}
                                    </a>

                                </div>


                                <x-status-badge :status="$type->status" />

                            </div>


                            <p x-show="
                                    density
                                    !== 'compact'
                                "
                                class="
                                    mt-3
                                    line-clamp-2
                                    text-sm
                                    leading-6
                                    text-slate-500
                                ">
                                {{ $type->description ?: 'Sin descripción.' }}
                            </p>


                            <div
                                class="
                                    mt-4
                                    flex
                                    items-center
                                    justify-between
                                    border-t
                                    border-slate-100
                                    pt-3
                                ">

                                <span
                                    class="
                                        text-xs
                                        font-semibold
                                        text-slate-500
                                    ">
                                    {{ $type->entities_count }}

                                    {{ $type->entities_count === 1 ? 'entidad' : 'entidades' }}
                                </span>


                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2
                                    ">

                                    <a href="{{ route('entities.create', [
                                        'type' => $type->id,
                                    ]) }}"
                                        title="Crear entidad de este tipo"
                                        class="
                                            rounded-lg
                                            bg-slate-100
                                            px-2.5
                                            py-1.5
                                            text-xs
                                            font-bold
                                            text-slate-600
                                            hover:bg-indigo-50
                                            hover:text-indigo-700
                                        ">
                                        +
                                    </a>


                                    <a href="{{ route('entity-types.edit', $type) }}"
                                        title="Editar"
                                        class="
                                            rounded-lg
                                            bg-slate-100
                                            px-2.5
                                            py-1.5
                                            text-xs
                                            font-bold
                                            text-slate-600
                                            hover:bg-slate-200
                                            hover:text-slate-900
                                        ">
                                        Editar
                                    </a>

                                </div>

                            </div>

                        </div>

                    </article>
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- LISTA --}}
            {{-- ================================================= --}}

            <div x-cloak x-show="
                    view === 'list'
                "
                class="
                    mt-6
                    space-y-3
                ">

                @foreach ($entityTypes as $type)
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
                            transition
                            hover:border-indigo-200
                            hover:shadow-md
                            md:flex-row
                            md:items-center
                        ">

                        {{-- IMAGEN --}}
                        <a href="{{ route('entity-types.show', $type) }}"
                            class="
                                h-20
                                w-full
                                shrink-0
                                overflow-hidden
                                rounded-xl
                                bg-slate-100
                                md:w-20
                            ">

                            @if ($type->image_url)
                                <img src="{{ $type->image_url }}" alt="{{ $type->name }}"
                                    class="
                                        h-full
                                        w-full
                                        object-cover
                                    ">
                            @else
                                <div class="
                                        flex
                                        h-full
                                        w-full
                                        items-center
                                        justify-center
                                        text-2xl
                                    "
                                    style="
                                        background-color:
                                            {{ $type->color ?? '#6366F1' }}20;

                                        color:
                                            {{ $type->color ?? '#6366F1' }};
                                    ">
                                    {{ $type->icon ?: '◇' }}
                                </div>
                            @endif

                        </a>


                        {{-- INFORMACIÓN --}}
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

                                <a href="{{ route('entity-types.show', $type) }}"
                                    class="
                                        font-black
                                        text-slate-900
                                        hover:text-indigo-700
                                    ">
                                    {{ $type->name }}
                                </a>


                                <x-status-badge :status="$type->status" />

                            </div>


                            <p
                                class="
                                    mt-1
                                    font-mono
                                    text-[10px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-slate-400
                                ">
                                {{ $type->code }}
                                ·
                                Nº {{ $type->sequence_number }}
                            </p>


                            <p
                                class="
                                    mt-2
                                    line-clamp-1
                                    text-sm
                                    text-slate-500
                                ">
                                {{ $type->description ?: 'Sin descripción.' }}
                            </p>

                        </div>


                        {{-- DATOS --}}
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
                                        text-[10px]
                                        font-bold
                                        uppercase
                                        tracking-wider
                                        text-slate-400
                                    ">
                                    Entidades
                                </p>

                                <p
                                    class="
                                        mt-1
                                        font-black
                                        text-slate-700
                                    ">
                                    {{ $type->entities_count }}
                                </p>

                            </div>


                            <div>

                                <p
                                    class="
                                        text-[10px]
                                        font-bold
                                        uppercase
                                        tracking-wider
                                        text-slate-400
                                    ">
                                    Creado
                                </p>

                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-bold
                                        text-slate-700
                                    ">
                                    {{ $type->created_at->format('d/m/Y') }}
                                </p>

                            </div>


                            <a href="{{ route('entity-types.show', $type) }}"
                                class="
                                    rounded-xl
                                    bg-indigo-50
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-indigo-700
                                    hover:bg-indigo-100
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

                <div class="
                        overflow-x-auto
                    ">

                    <table
                        class="
                            min-w-full
                            divide-y
                            divide-slate-200
                        ">

                        <thead class="
                                bg-slate-50
                            ">

                            <tr>

                                @foreach (['Tipo', 'Código', 'N.º', 'Entidades', 'Estado', 'Creado', ''] as $heading)
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

                            @foreach ($entityTypes as $type)
                                <tr
                                    class="
                                        transition
                                        hover:bg-slate-50
                                    ">

                                    {{-- TIPO --}}
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

                                                @if ($type->image_url)
                                                    <img src="{{ $type->image_url }}" alt="{{ $type->name }}"
                                                        class="
                                                            h-full
                                                            w-full
                                                            object-cover
                                                        ">
                                                @else
                                                    <div class="
                                                            flex
                                                            h-full
                                                            w-full
                                                            items-center
                                                            justify-center
                                                            text-lg
                                                        "
                                                        style="
                                                            background-color:
                                                                {{ $type->color ?? '#6366F1' }}20;

                                                            color:
                                                                {{ $type->color ?? '#6366F1' }};
                                                        ">
                                                        {{ $type->icon ?: '◇' }}
                                                    </div>
                                                @endif

                                            </div>


                                            <div
                                                class="
                                                    min-w-[160px]
                                                ">

                                                <a href="{{ route('entity-types.show', $type) }}"
                                                    class="
                                                        font-bold
                                                        text-slate-900
                                                        hover:text-indigo-700
                                                    ">
                                                    {{ $type->name }}
                                                </a>


                                                <p
                                                    class="
                                                        mt-1
                                                        max-w-[220px]
                                                        truncate
                                                        text-xs
                                                        text-slate-400
                                                    ">
                                                    {{ $type->description ?: 'Sin descripción.' }}
                                                </p>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- CÓDIGO --}}
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
                                        {{ $type->code }}
                                    </td>


                                    {{-- SECUENCIA --}}
                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            text-sm
                                            font-bold
                                            text-slate-600
                                        ">
                                        #{{ $type->sequence_number }}
                                    </td>


                                    {{-- ENTIDADES --}}
                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            text-sm
                                            font-bold
                                            text-slate-700
                                        ">
                                        {{ $type->entities_count }}
                                    </td>


                                    {{-- ESTADO --}}
                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                        ">
                                        <x-status-badge :status="$type->status" />
                                    </td>


                                    {{-- FECHA --}}
                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            text-sm
                                            text-slate-500
                                        ">
                                        {{ $type->created_at->format('d/m/Y') }}
                                    </td>


                                    {{-- ACCIONES --}}
                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            text-right
                                        ">

                                        <div
                                            class="
                                                flex
                                                items-center
                                                justify-end
                                                gap-2
                                            ">

                                            <a href="{{ route('entity-types.show', $type) }}"
                                                class="
                                                    text-xs
                                                    font-black
                                                    text-indigo-600
                                                    hover:text-indigo-800
                                                ">
                                                Abrir
                                            </a>


                                            <a href="{{ route('entity-types.edit', $type) }}"
                                                class="
                                                    text-xs
                                                    font-bold
                                                    text-slate-500
                                                    hover:text-slate-900
                                                ">
                                                Editar
                                            </a>

                                        </div>

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @endif


        {{-- ===================================================== --}}
        {{-- PAGINACIÓN --}}
        {{-- ===================================================== --}}

        <div class="mt-8">

            {{ $entityTypes->links() }}

        </div>

    </div>

</x-app-layout>

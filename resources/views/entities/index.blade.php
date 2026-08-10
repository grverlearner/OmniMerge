<x-app-layout>

    <x-slot name="header">
        Entidades
    </x-slot>


    @include('entities.partials.section-navigation')


    <div x-data="{
    
        view: localStorage.getItem(
                'omnimerge.entities.view'
            ) ||
            'grid',
    
        density: localStorage.getItem(
                'omnimerge.entities.density'
            ) ||
            'compact',
    
    
        setView(value) {
    
            this.view =
                value;
    
            localStorage.setItem(
                'omnimerge.entities.view',
                value
            );
        },
    
    
        setDensity(value) {
    
            this.density =
                value;
    
            localStorage.setItem(
                'omnimerge.entities.density',
                value
            );
        }
    }">

        {{-- ===================================================== --}}
        {{-- HEADER --}}
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
                    Biblioteca · Creaciones
                </p>


                <h2
                    class="
                        mt-2
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    Entidades
                </h2>


                <p
                    class="
                        mt-2
                        max-w-3xl
                        text-slate-500
                    ">
                    Explora y administra todas las piezas
                    que posteriormente podrán utilizarse
                    en Universos, Torneos y simulaciones.
                </p>

            </div>

            <div
                class="
        flex
        flex-col
        gap-2
        sm:flex-row
        sm:flex-wrap
        sm:justify-end
    ">

                <a href="{{ route('entities.bulk-edit.index') }}"
                    class="
                        flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        border
                        border-violet-200
                        bg-violet-50
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-violet-700
                        transition
                        hover:border-violet-300
                        hover:bg-violet-100
                    ">
                    <span>
                        ⚙
                    </span>

                    Edición masiva
                </a>


                <a href="{{ route('entities.bulk.create') }}"
                    class="
                        flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        border
                        border-indigo-200
                        bg-indigo-50
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-indigo-700
                        transition
                        hover:border-indigo-300
                        hover:bg-indigo-100
                    ">
                    <span>
                        ✦
                    </span>

                    Creación masiva
                </a>


                <a href="{{ route('entities.create') }}"
                    class="
                        flex
                        items-center
                        justify-center
                        gap-2
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
                    <span>
                        +
                    </span>

                    Nueva entidad
                </a>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- STATS --}}
        {{-- ===================================================== --}}

        <div
            class="
                mt-7
                grid
                grid-cols-2
                gap-3
                md:grid-cols-3
                xl:grid-cols-5
            ">

            @foreach ([['Total', $stats['total'], 'border-slate-200 bg-white text-slate-900'], ['Públicas', $stats['public'], 'border-cyan-100 bg-cyan-50 text-cyan-700'], ['Con características', $stats['with_attributes'], 'border-indigo-100 bg-indigo-50 text-indigo-700'], ['Archivadas', $stats['archived'], 'border-slate-200 bg-slate-50 text-slate-600'], ['Sin tipo', $stats['untyped'], 'border-amber-100 bg-amber-50 text-amber-700']] as [$label, $value, $classes])
                <article
                    class="
                        rounded-2xl
                        border
                        p-4
                        {{ $classes }}
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            opacity-60
                        ">
                        {{ $label }}
                    </p>


                    <p
                        class="
                            mt-2
                            text-2xl
                            font-black
                        ">
                        {{ $value }}
                    </p>

                </article>
            @endforeach

        </div>


        {{-- ===================================================== --}}
        {{-- FILTROS --}}
        {{-- ===================================================== --}}

        {{-- ===================================================== --}}
        {{-- FILTROS RESPONSIVE --}}
        {{-- ===================================================== --}}

        <form method="GET" action="{{ route('entities.index') }}"
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

            {{-- ========================================================= --}}
            {{-- FILTROS PRINCIPALES --}}
            {{-- ========================================================= --}}

            <div
                class="
            grid
            min-w-0
            grid-cols-1
            gap-3
            sm:grid-cols-2
            xl:grid-cols-4
        ">

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


                <select name="type"
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

                    <option value="none" @selected($type === 'none')>
                        Sin tipo
                    </option>

                    @foreach ($entityTypes as $entityType)
                        <option value="{{ $entityType->id }}" @selected((string) $type === (string) $entityType->id)>
                            {{ $entityType->name }}
                        </option>
                    @endforeach
                </select>


                <select name="visibility"
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

                    <option value="PUBLIC" @selected($visibility === 'PUBLIC')>
                        Público
                    </option>

                    <option value="PRIVATE" @selected($visibility === 'PRIVATE')>
                        Privado
                    </option>

                    <option value="UNLISTED" @selected($visibility === 'UNLISTED')>
                        No listado
                    </option>
                </select>


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


                <select name="collection"
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
                        Todas las colecciones
                    </option>

                    @foreach ($collections as $collection)
                        <option value="{{ $collection->id }}" @selected($collectionId == $collection->id)>
                            {{ $collection->name }}
                        </option>
                    @endforeach
                </select>


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


                <select name="attributes_state"
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
                        Cualquier característica
                    </option>

                    <option value="yes" @selected($attributesState === 'yes')>
                        Con características
                    </option>

                    <option value="no" @selected($attributesState === 'no')>
                        Sin características
                    </option>
                </select>


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
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguas',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'code_asc' => 'Código ascendente',
        'code_desc' => 'Código descendente',
        'attributes_desc' => 'Más características',
        'attributes_asc' => 'Menos características',
        'collections_desc' => 'Más colecciones',
        'collections_asc' => 'Menos colecciones',
        'views_desc' => 'Más vistas',
        'clones_desc' => 'Más clonadas',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

            </div>


            {{-- ========================================================= --}}
            {{-- FILTRO POR ATRIBUTO --}}
            {{-- ========================================================= --}}

            <div
                class="
            mt-3
            grid
            min-w-0
            grid-cols-1
            gap-3
            border-t
            border-slate-100
            pt-3
            md:grid-cols-2
        ">

                <select name="filter_attribute"
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
                        Filtrar por atributo...
                    </option>

                    @foreach ($filterAttributes as $attribute)
                        <option value="{{ $attribute->id }}" @selected($filterAttributeId == $attribute->id)>
                            {{ $attribute->name }}
                        </option>
                    @endforeach
                </select>


                <select name="filter_option"
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
                        Cualquier elemento del Catálogo
                    </option>

                    @foreach ($filterAttributes as $attribute)
                        <optgroup label="{{ $attribute->name }}">

                            @foreach ($attribute->options as $option)
                                <option value="{{ $option->id }}" @selected($filterOptionId == $option->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach

                        </optgroup>
                    @endforeach
                </select>

            </div>


            {{-- ========================================================= --}}
            {{-- ACCIONES --}}
            {{-- ========================================================= --}}

            <div
                class="
            mt-3
            flex
            flex-col
            gap-3
            sm:flex-row
            sm:items-center
            sm:justify-between
        ">

                <div class="min-w-0">

                    @if (count(request()->query()) > 0)
                        <a href="{{ route('entities.index') }}"
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


                <div
                    class="
                        flex
                        w-full
                        min-w-0
                        gap-2
                        sm:w-auto
                    ">

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
                            sm:flex-none
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
                            hover:bg-slate-800
                        ">
                        Aplicar
                    </button>

                </div>

            </div>

        </form>


        {{-- ===================================================== --}}
        {{-- VISTAS --}}
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
                    gap-2
                ">

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

        </div>


        @if ($entities->isEmpty())

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

                <div class="text-5xl">
                    ✦
                </div>


                <h3
                    class="
                        mt-4
                        font-black
                        text-slate-800
                    ">
                    No se encontraron entidades
                </h3>


                <a href="{{ route('entities.create') }}"
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
                    Crear entidad
                </a>

            </div>
        @else
            {{-- ================================================= --}}
            {{-- GALERÍA --}}
            {{-- ================================================= --}}

            <div x-show="
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

                @foreach ($entities as $entity)
                    @include('entities.partials.index-gallery-card', compact('entity'))
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- GRID --}}
            {{-- ================================================= --}}

            <div x-cloak x-show="
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

                @foreach ($entities as $entity)
                    @include('entities.partials.index-card', compact('entity'))
                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- LIST --}}
            {{-- ================================================= --}}

            <div x-cloak x-show="
                    view === 'list'
                "
                class="
                    mt-6
                    space-y-3
                ">

                @foreach ($entities as $entity)
                    @include('entities.partials.index-list-item', compact('entity'))
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
                ">

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr>

                                @foreach (['Entidad', 'Código', 'Tipo', 'Características', 'Colecciones', 'Visibilidad', 'Estado', ''] as $heading)
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

                            @foreach ($entities as $entity)
                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4">

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

                                                @if ($entity->image_url)
                                                    <img src="{{ $entity->image_url }}"
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
                                                            text-indigo-300
                                                        ">
                                                        ✦
                                                    </div>
                                                @endif

                                            </div>


                                            <a href="{{ route('entities.show', $entity) }}"
                                                class="
                                                    font-bold
                                                    text-slate-900
                                                    hover:text-indigo-700
                                                ">
                                                {{ $entity->name }}
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
                                        {{ $entity->code }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            text-sm
                                            text-slate-600
                                        ">
                                        {{ $entity->entityType?->name ?? '—' }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $entity->entity_attributes_count }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $entity->collections_count }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            text-sm
                                            text-slate-600
                                        ">
                                        {{ $entity->visibility_label }}
                                    </td>


                                    <td class="px-5 py-4">

                                        <x-status-badge :status="$entity->status" />

                                    </td>


                                    <td class="px-5 py-4">

                                        <a href="{{ route('entities.show', $entity) }}"
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


        <div class="mt-8">

            {{ $entities->links() }}

        </div>

    </div>

</x-app-layout>

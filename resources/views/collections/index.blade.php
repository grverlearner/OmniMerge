<x-app-layout>

    <x-slot name="header">
        Colecciones
    </x-slot>


    @include('entities.partials.section-navigation')


    <div x-data="{
    
        view: localStorage.getItem(
                'omnimerge.collections.view'
            ) ||
            'grid',
    
        density: localStorage.getItem(
                'omnimerge.collections.density'
            ) ||
            'compact',
    
    
        setView(value) {
    
            this.view =
                value;
    
            localStorage.setItem(
                'omnimerge.collections.view',
                value
            );
        },
    
    
        setDensity(value) {
    
            this.density =
                value;
    
            localStorage.setItem(
                'omnimerge.collections.density',
                value
            );
        }
    }">

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
                        tracking-wider
                        text-indigo-600
                    ">
                    Entidades · Organización
                </p>


                <h2
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    Colecciones
                </h2>


                <p
                    class="
                        mt-2
                        max-w-2xl
                        text-slate-500
                    ">
                    Agrupa entidades sin modificar su identidad
                    ni limitar su uso en otros contextos.
                </p>

            </div>


            <a href="{{ route('collections.create') }}"
                class="
                    rounded-xl
                    bg-indigo-600
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                ">
                + Nueva colección
            </a>

        </div>


        {{-- STATS --}}
        <div
            class="
                mt-7
                grid
                grid-cols-2
                gap-3
                lg:grid-cols-4
            ">

            @foreach ([['Total', $stats['total']], ['Públicas', $stats['public']], ['Activas', $stats['active']], ['Con entidades', $stats['with_entities']]] as [$label, $value])
                <article
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        p-4
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            text-slate-400
                        ">
                        {{ $label }}
                    </p>

                    <p
                        class="
                            mt-2
                            text-2xl
                            font-black
                            text-slate-900
                        ">
                        {{ $value }}
                    </p>

                </article>
            @endforeach

        </div>

        {{-- ===================================================== --}}
        {{-- FILTROS RESPONSIVE --}}
        {{-- ===================================================== --}}

        <form method="GET"
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

                <input name="search" value="{{ $search }}" placeholder="Buscar colección..."
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
        'entities_desc' => 'Más entidades',
        'entities_asc' => 'Menos entidades',
        'views_desc' => 'Más vistas',
        'clones_desc' => 'Más clonadas',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

            </div>


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

                @if ($search || $visibility || $status || $image || $sort !== 'newest' || $perPage !== 24)
                    <a href="{{ route('collections.index') }}"
                        class="
                    text-xs
                    font-bold
                    text-slate-500
                    hover:text-indigo-600
                ">
                        × Limpiar filtros
                    </a>
                @else
                    <span></span>
                @endif


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


        {{-- VIEWS --}}
        <div
            class="
                mt-5
                flex
                flex-col
                justify-between
                gap-3
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-3
                md:flex-row
            ">

            <div class="flex flex-wrap gap-2">

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


        {{-- GALLERY --}}
        <div x-show="view === 'gallery'"
            class="
                mt-6
                grid
                grid-cols-2
                gap-3
                sm:grid-cols-3
                md:grid-cols-4
                lg:grid-cols-6
                xl:grid-cols-8
            ">

            @foreach ($collections as $collection)
                <a href="{{ route('collections.show', $collection) }}"
                    class="
                        group
                        overflow-hidden
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                    ">

                    <div class="aspect-square bg-slate-100">

                        @if ($collection->image_url)
                            <img src="{{ $collection->image_url }}"
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
                                    text-4xl
                                ">
                                {{ $collection->icon ?: '▤' }}
                            </div>
                        @endif

                    </div>


                    <div class="p-3 text-center">

                        <p
                            class="
                                truncate
                                text-xs
                                font-black
                                text-slate-800
                            ">
                            {{ $collection->name }}
                        </p>

                    </div>

                </a>
            @endforeach

        </div>


        {{-- GRID --}}
        <div x-cloak x-show="view === 'grid'" class="mt-6 grid gap-4"
            :class="{
                'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': density === 'compact',
            
                'sm:grid-cols-2 lg:grid-cols-3': density === 'medium',
            
                'sm:grid-cols-2': density === 'large'
            }">

            @foreach ($collections as $collection)
                <article
                    class="
                        overflow-hidden
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                    ">

                    <a href="{{ route('collections.show', $collection) }}" class="block bg-slate-100"
                        :class="{
                            'h-32': density === 'compact',
                            'h-44': density === 'medium',
                            'h-60': density === 'large'
                        }">

                        @if ($collection->image_url)
                            <img src="{{ $collection->image_url }}"
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
                                    text-5xl
                                "
                                style="
                                    background-color:
                                        {{ $collection->color ?? '#6366F1' }}20;
                                ">
                                {{ $collection->icon ?: '▤' }}
                            </div>
                        @endif

                    </a>


                    <div class="p-4">

                        <p
                            class="
                                font-mono
                                text-[9px]
                                text-slate-400
                            ">
                            {{ $collection->code }}
                        </p>


                        <a href="{{ route('collections.show', $collection) }}"
                            class="
                                mt-1
                                block
                                font-black
                                text-slate-900
                            ">
                            {{ $collection->name }}
                        </a>


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

                            <span class="text-xs text-slate-500">
                                {{ $collection->entities_count }}
                                entidades
                            </span>


                            <x-status-badge :status="$collection->status" />

                        </div>

                    </div>

                </article>
            @endforeach

        </div>


        {{-- LIST --}}
        <div x-cloak x-show="view === 'list'" class="
                mt-6
                space-y-3
            ">

            @foreach ($collections as $collection)
                <article
                    class="
                        flex
                        items-center
                        gap-4
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        p-4
                    ">

                    <div
                        class="
                            h-16
                            w-16
                            shrink-0
                            overflow-hidden
                            rounded-xl
                            bg-slate-100
                        ">

                        @if ($collection->image_url)
                            <img src="{{ $collection->image_url }}"
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
                                ▤
                            </div>
                        @endif

                    </div>


                    <div class="min-w-0 flex-1">

                        <p
                            class="
                                font-black
                                text-slate-900
                            ">
                            {{ $collection->name }}
                        </p>

                        <p
                            class="
                                mt-1
                                font-mono
                                text-[10px]
                                text-slate-400
                            ">
                            {{ $collection->code }}
                        </p>

                    </div>


                    <span
                        class="
                            text-xs
                            font-bold
                            text-slate-500
                        ">
                        {{ $collection->entities_count }}
                        entidades
                    </span>


                    <a href="{{ route('collections.show', $collection) }}"
                        class="
                            text-xs
                            font-black
                            text-indigo-600
                        ">
                        Abrir →
                    </a>

                </article>
            @endforeach

        </div>


        {{-- TABLE --}}
        <div x-cloak x-show="view === 'table'"
            class="
                mt-6
                overflow-x-auto
                rounded-2xl
                border
                border-slate-200
                bg-white
            ">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr>

                        @foreach (['Colección', 'Código', 'Entidades', 'Visibilidad', 'Estado', ''] as $heading)
                            <th
                                class="
                                    px-5
                                    py-3
                                    text-left
                                    text-[10px]
                                    font-black
                                    uppercase
                                    text-slate-400
                                ">
                                {{ $heading }}
                            </th>
                        @endforeach

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @foreach ($collections as $collection)
                        <tr>

                            <td class="px-5 py-4 font-bold">
                                {{ $collection->name }}
                            </td>

                            <td
                                class="
                                    px-5
                                    py-4
                                    font-mono
                                    text-xs
                                ">
                                {{ $collection->code }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $collection->entities_count }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $collection->visibility_label }}
                            </td>

                            <td class="px-5 py-4">

                                <x-status-badge :status="$collection->status" />

                            </td>

                            <td class="px-5 py-4">

                                <a href="{{ route('collections.show', $collection) }}"
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


        <div class="mt-8">
            {{ $collections->links() }}
        </div>

    </div>

</x-app-layout>

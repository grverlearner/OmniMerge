<x-app-layout>

    <x-slot name="header">
        Grupos de atributos
    </x-slot>


    @include('attributes.partials.section-navigation')


    <div x-data="{
    
        view: localStorage.getItem(
                'omnimerge.attributeGroups.view'
            ) ||
            'grid',
    
        density: localStorage.getItem(
                'omnimerge.attributeGroups.density'
            ) ||
            'compact',
    
    
        setView(value) {
    
            this.view =
                value;
    
            localStorage.setItem(
                'omnimerge.attributeGroups.view',
                value
            );
        },
    
    
        setDensity(value) {
    
            this.density =
                value;
    
            localStorage.setItem(
                'omnimerge.attributeGroups.density',
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
                    Atributos · Organización
                </p>


                <h2
                    class="
                        mt-2
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    Grupos
                </h2>


                <p
                    class="
                        mt-2
                        max-w-3xl
                        text-slate-500
                    ">
                    Organiza tus atributos para presentar
                    las características de las entidades
                    de una forma más clara y coherente.
                </p>

            </div>


            <a href="{{ route('attribute-groups.create') }}"
                class="
                    rounded-xl
                    bg-indigo-600
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                    shadow-lg
                    shadow-indigo-600/20
                    hover:bg-indigo-700
                ">
                + Nuevo grupo
            </a>

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
                lg:grid-cols-4
            ">

            @foreach ([['Grupos', $stats['total'], 'border-slate-200 bg-white text-slate-900'], ['Activos', $stats['active'], 'border-emerald-100 bg-emerald-50 text-emerald-700'], ['Relaciones', $stats['relations'], 'border-indigo-100 bg-indigo-50 text-indigo-700'], ['Vacíos', $stats['empty'], 'border-amber-100 bg-amber-50 text-amber-700']] as [$label, $value, $classes])
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
        {{-- FILTROS RESPONSIVE --}}
        {{-- ===================================================== --}}

        <form method="GET" action="{{ route('attribute-groups.index') }}"
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

                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Nombre, código o descripción..."
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


                <select name="layout"
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
                        Toda presentación
                    </option>

                    @foreach ([
        'LIST' => 'Lista',
        'GRID' => 'Cuadrícula',
        'CARDS' => 'Tarjetas',
        'TABLE' => 'Tabla',
        'COMPACT' => 'Compacto',
    ] as $value => $labelText)
                        <option value="{{ $value }}" @selected($layout === $value)>
                            {{ $labelText }}
                        </option>
                    @endforeach
                </select>


                <select name="content"
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
                        Cualquier contenido
                    </option>

                    <option value="with" @selected($content === 'with')>
                        Con atributos
                    </option>

                    <option value="empty" @selected($content === 'empty')>
                        Vacíos
                    </option>
                </select>


                <select name="collapsible"
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
                        Cualquier comportamiento
                    </option>

                    <option value="yes" @selected($collapsible === 'yes')>
                        Contraíbles
                    </option>

                    <option value="no" @selected($collapsible === 'no')>
                        Siempre visibles
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
        'manual' => 'Orden personalizado',
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'code_asc' => 'Código ascendente',
        'code_desc' => 'Código descendente',
        'attributes_desc' => 'Más atributos',
        'attributes_asc' => 'Menos atributos',
    ] as $value => $labelText)
                        <option value="{{ $value }}" @selected($sort === $value)>
                            {{ $labelText }}
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

                <a href="{{ route('attribute-groups.index') }}"
                    class="
                text-xs
                font-bold
                text-slate-500
                hover:text-indigo-600
            ">
                    × Limpiar filtros
                </a>


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
                ">
                        Aplicar
                    </button>

                </div>

            </div>

        </form>
        {{-- ===================================================== --}}
        {{-- MODOS DE VISTA --}}
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
    ] as $value => $labelText)
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
                        {{ $labelText }}
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
    ] as $value => $labelText)
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
                        {{ $labelText }}
                    </button>
                @endforeach

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- EMPTY --}}
        {{-- ===================================================== --}}

        @if ($groups->isEmpty())

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
                    ▥
                </div>


                <h3
                    class="
                        mt-4
                        font-black
                        text-slate-800
                    ">
                    No se encontraron grupos
                </h3>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Crea grupos como Información general,
                    Apariencia, Combate o Historia.
                </p>


                <a href="{{ route('attribute-groups.create') }}"
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
                    Crear grupo
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

                @foreach ($groups as $group)
                    @include('attribute-groups.partials.index-gallery-card', [
                        'group' => $group,
                    ])
                @endforeach

            </div>
            {{-- ================================================= --}}
            {{-- GRID --}}
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
                
                    'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': density === 'compact',
                
                    'sm:grid-cols-2 lg:grid-cols-3': density === 'medium',
                
                    'sm:grid-cols-2 xl:grid-cols-3': density === 'large'
                }">

                @foreach ($groups as $group)
                    @include('attribute-groups.partials.index-card', [
                        'group' => $group,
                    ])
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

                @foreach ($groups as $group)
                    @include('attribute-groups.partials.index-list-item', [
                        'group' => $group,
                    ])
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

                                @foreach (['Grupo', 'Código', 'Atributos', 'Presentación', 'Comportamiento', 'Estado', 'Actualizado', ''] as $heading)
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

                            @foreach ($groups as $group)
                                <tr
                                    class="
                                        hover:bg-slate-50
                                    ">

                                    <td class="px-5 py-4">

                                        <div
                                            class="
                                                flex
                                                items-center
                                                gap-3
                                            ">

                                            <div class="
                                                    flex
                                                    h-10
                                                    w-10
                                                    items-center
                                                    justify-center
                                                    rounded-xl
                                                    font-black
                                                "
                                                style="
                                                    background-color:
                                                        {{ $group->color ?? '#6366F1' }}20;

                                                    color:
                                                        {{ $group->color ?? '#6366F1' }};
                                                ">
                                                {{ $group->icon ?: '▥' }}
                                            </div>


                                            <a href="{{ route('attribute-groups.show', $group) }}"
                                                class="
                                                    font-bold
                                                    text-slate-900
                                                    hover:text-indigo-700
                                                ">
                                                {{ $group->name }}
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
                                        {{ $group->code }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $group->attributes_count }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            text-sm
                                            font-bold
                                            text-slate-600
                                        ">
                                        {{ $group->layout_label }}
                                    </td>


                                    <td
                                        class="
                                            px-5
                                            py-4
                                            text-sm
                                            text-slate-500
                                        ">
                                        {{ $group->collapsible
                                            ? ($group->default_expanded
                                                ? 'Contraíble · abierto'
                                                : 'Contraíble · cerrado')
                                            : 'Siempre visible' }}
                                    </td>


                                    <td class="px-5 py-4">

                                        <x-status-badge :status="$group->status" />

                                    </td>


                                    <td
                                        class="
                                            whitespace-nowrap
                                            px-5
                                            py-4
                                            text-xs
                                            text-slate-500
                                        ">
                                        {{ $group->updated_at->format('d/m/Y') }}
                                    </td>


                                    <td class="px-5 py-4">

                                        <a href="{{ route('attribute-groups.show', $group) }}"
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


        {{-- PAGINATION --}}
        <div class="mt-8">

            {{ $groups->links() }}

        </div>

    </div>

</x-app-layout>

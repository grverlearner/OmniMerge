@php

    $typeLabels = [
        'OPTION' => 'Catálogo',
        'BOOLEAN' => 'Sí / No',
        'TEXT' => 'Texto corto',
        'LONG_TEXT' => 'Texto largo',
        'INTEGER' => 'Número entero',
        'DECIMAL' => 'Número decimal',
        'DATE' => 'Fecha',
        'COLOR' => 'Color',
    ];

@endphp


<x-app-layout>

    <x-slot name="header">
        Atributos
    </x-slot>
    @include('attributes.partials.section-navigation')


    <div x-data="{
    
        catalogView: localStorage.getItem(
                'omnimerge.catalog.view'
            ) ||
            'grid',
    
        catalogDensity: localStorage.getItem(
                'omnimerge.catalog.density'
            ) ||
            'compact',
    
        quickAdvanced: false,
    
    
        setCatalogView(value) {
    
            this.catalogView =
                value;
    
            localStorage.setItem(
                'omnimerge.catalog.view',
                value
            );
        },
    
    
        setCatalogDensity(value) {
    
            this.catalogDensity =
                value;
    
            localStorage.setItem(
                'omnimerge.catalog.density',
                value
            );
        }
    }">

        {{-- ===================================================== --}}
        {{-- VOLVER --}}
        {{-- ===================================================== --}}

        <div class="mb-5">

            <a href="{{ route('attributes.index') }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Atributos
            </a>

        </div>


        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}

        <section
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
                    grid
                    lg:grid-cols-[360px_minmax(0,1fr)]
                ">

                {{-- IMAGEN --}}
                <div
                    class="
                        min-h-[300px]
                        bg-slate-100
                    ">

                    @if ($attribute->image_url)
                        <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                            class="
                                h-full
                                min-h-[300px]
                                w-full
                                object-cover
                            ">
                    @else
                        <div class="
                                flex
                                h-full
                                min-h-[300px]
                                items-center
                                justify-center
                                text-7xl
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


                {{-- INFO --}}
                <div
                    class="
                        flex
                        flex-col
                        justify-between
                        p-6
                        sm:p-8
                    ">

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
                                    font-mono
                                    text-[10px]
                                    font-black
                                    text-indigo-700
                                ">
                                {{ $attribute->code }}
                            </span>


                            <span
                                class="
                                    rounded-full
                                    bg-violet-50
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    text-violet-700
                                ">
                                {{ $attribute->data_type_label }}
                            </span>


                            @if ($attribute->allows_multiple)
                                <span
                                    class="
                                        rounded-full
                                        bg-fuchsia-50
                                        px-3
                                        py-1
                                        text-[10px]
                                        font-black
                                        text-fuchsia-700
                                    ">
                                    Múltiple
                                </span>
                            @endif


                            <span
                                class="
                                    rounded-full
                                    bg-cyan-50
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    text-cyan-700
                                ">
                                {{ $attribute->scope_label }}
                            </span>


                            <x-status-badge :status="$attribute->status" />

                        </div>


                        <h1
                            class="
                                mt-5
                                text-3xl
                                font-black
                                tracking-tight
                                text-slate-900
                                sm:text-4xl
                            ">
                            {{ $attribute->name }}
                        </h1>


                        <p
                            class="
                                mt-2
                                font-mono
                                text-xs
                                font-bold
                                text-slate-400
                            ">
                            {{ $attribute->slug }}
                        </p>


                        <p
                            class="
                                mt-5
                                max-w-3xl
                                leading-7
                                text-slate-600
                            ">
                            {{ $attribute->description ?: 'Este atributo todavía no tiene una descripción.' }}
                        </p>


                        @if ($attribute->help_text)
                            <div
                                class="
                                    mt-5
                                    rounded-2xl
                                    border
                                    border-indigo-100
                                    bg-indigo-50
                                    p-4
                                ">

                                <p
                                    class="
                                        text-xs
                                        font-black
                                        uppercase
                                        tracking-wider
                                        text-indigo-500
                                    ">
                                    Texto de ayuda
                                </p>


                                <p
                                    class="
                                        mt-2
                                        text-sm
                                        leading-6
                                        text-indigo-800
                                    ">
                                    {{ $attribute->help_text }}
                                </p>

                            </div>
                        @endif

                    </div>


                    <div
                        class="
                            mt-8
                            flex
                            flex-wrap
                            gap-3
                        ">

                        <a href="{{ route('attributes.edit', $attribute) }}"
                            class="
                                rounded-xl
                                bg-indigo-600
                                px-5
                                py-3
                                text-sm
                                font-black
                                text-white
                                hover:bg-indigo-700
                            ">
                            Editar atributo
                        </a>


                        @if ($attribute->options_count === 0 && $attribute->entity_attributes_count === 0)
                            <form method="POST"
                                action="{{ route('attributes.destroy', $attribute) }}"
                                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                data-confirm-title="Eliminar Atributo"
                                data-confirm-message="
        Este Atributo será eliminado
        de tu Biblioteca.
    "
                                data-confirm-subject="{{ $attribute->name }}"
                                data-confirm-detail="
        Esta acción solamente está disponible
        cuando el Atributo no tiene Catálogo
        ni valores asignados.
    "
                                data-confirm-action="Eliminar Atributo"
                                data-confirm-image="{{ $attribute->image_url ?? '' }}">

                                @csrf
                                @method('DELETE')


                                <button type="submit"
                                    class="
                                        rounded-xl
                                        border
                                        border-red-200
                                        px-5
                                        py-3
                                        text-sm
                                        font-bold
                                        text-red-600
                                        hover:bg-red-50
                                    ">
                                    Eliminar
                                </button>

                            </form>
                        @else
                            <span title="Archiva el atributo si ya contiene datos."
                                class="
                                    cursor-not-allowed
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-slate-50
                                    px-5
                                    py-3
                                    text-sm
                                    font-bold
                                    text-slate-300
                                ">
                                Eliminar
                            </span>
                        @endif

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ESTADÍSTICAS --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-6
                grid
                gap-4
                sm:grid-cols-2
                xl:grid-cols-4
            ">

            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Catálogo
                </p>

                <p
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $attribute->options_count }}
                </p>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Entidades que lo usan
                </p>

                <p
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $attribute->entity_attributes_count }}
                </p>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Selección
                </p>

                <p
                    class="
                        mt-2
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    {{ $attribute->selection_mode_label }}
                </p>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Creado
                </p>

                <p
                    class="
                        mt-2
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    {{ $attribute->created_at->format('d/m/Y') }}
                </p>

            </article>

        </section>


        {{-- ===================================================== --}}
        {{-- CONFIGURACIÓN --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-8
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
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
                    Configuración
                </p>


                <h2
                    class="
                        mt-2
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Cómo funciona este atributo
                </h2>

            </div>


            <div
                class="
                    mt-6
                    grid
                    gap-4
                    sm:grid-cols-2
                    lg:grid-cols-4
                ">

                @foreach ([['Tipo', $attribute->data_type_label], ['Presentación', $attribute->display_style_label], ['Origen', $attribute->usesCatalog() ? 'Catálogo' : 'Valor directo'], ['Visibilidad', $attribute->scope_label]] as [$label, $value])
                    <div
                        class="
                            rounded-2xl
                            bg-slate-50
                            p-4
                        ">

                        <p
                            class="
                                text-xs
                                text-slate-500
                            ">
                            {{ $label }}
                        </p>


                        <p
                            class="
                                mt-2
                                font-black
                                text-slate-900
                            ">
                            {{ $value }}
                        </p>

                    </div>
                @endforeach

            </div>


            {{-- FLAGS --}}
            <div
                class="
                    mt-5
                    flex
                    flex-wrap
                    gap-2
                ">

                @foreach ([
        'allows_multiple' => 'Múltiple',
        'is_required' => 'Obligatorio',
        'is_filterable' => 'Filtrable',
        'is_comparable' => 'Comparable',
        'is_searchable' => 'Buscable',
        'is_visible' => 'Visible',
        'is_featured' => 'Destacado',
        'allow_cloning' => 'Clonable',
    ] as $field => $label)
                    @if ($attribute->{$field})
                        <span
                            class="
                                rounded-full
                                bg-slate-100
                                px-3
                                py-1.5
                                text-[10px]
                                font-black
                                text-slate-600
                            ">
                            ✓ {{ $label }}
                        </span>
                    @endif
                @endforeach

            </div>


            {{-- GRUPOS --}}
            @if ($attribute->groups->isNotEmpty())

                <div
                    class="
                        mt-6
                        border-t
                        border-slate-100
                        pt-5
                    ">

                    <p
                        class="
                            text-xs
                            font-black
                            uppercase
                            text-slate-400
                        ">
                        Grupos
                    </p>


                    <div
                        class="
                            mt-3
                            flex
                            flex-wrap
                            gap-2
                        ">

                        @foreach ($attribute->groups as $group)
                            <span
                                class="
                                    rounded-full
                                    bg-indigo-50
                                    px-3
                                    py-1.5
                                    text-xs
                                    font-bold
                                    text-indigo-700
                                ">
                                {{ $group->name }}
                            </span>
                        @endforeach

                    </div>

                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- CATÁLOGO --}}
        {{-- ===================================================== --}}

        @if ($attribute->usesCatalog())

            <section id="catalog" class="
                    mt-10
                ">

                {{-- CABECERA --}}
                <div
                    class="
                        flex
                        flex-col
                        justify-between
                        gap-4
                        sm:flex-row
                        sm:items-end
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
                            Núcleo seleccionable
                        </p>


                        <h2
                            class="
                                mt-2
                                text-3xl
                                font-black
                                tracking-tight
                                text-slate-900
                            ">
                            Catálogo
                        </h2>


                        <p
                            class="
                                mt-2
                                max-w-2xl
                                text-sm
                                leading-6
                                text-slate-500
                            ">
                            Elementos reutilizables que podrán
                            seleccionarse al utilizar el atributo
                            {{ $attribute->name }}.
                        </p>

                    </div>


                    <a href="{{ route('attribute-options.create', [
                        'attribute' => $attribute->id,
                    ]) }}"
                        class="
                            rounded-xl
                            border
                            border-violet-200
                            bg-violet-50
                            px-4
                            py-2.5
                            text-sm
                            font-black
                            text-violet-700
                            hover:bg-violet-100
                        ">
                        Abrir creador completo →
                    </a>

                </div>


                @if (session('success'))
                    <div
                        class="
                            mt-5
                            rounded-2xl
                            border
                            border-emerald-200
                            bg-emerald-50
                            px-5
                            py-4
                            text-sm
                            font-bold
                            text-emerald-700
                        ">
                        ✓ {{ session('success') }}
                    </div>
                @endif


                {{-- ================================================= --}}
                {{-- CREACIÓN RÁPIDA + EXPLORADOR --}}
                {{-- ================================================= --}}

                <div
                    class="
                        mt-6
                        grid
                        items-start
                        gap-6
                        xl:grid-cols-[360px_minmax(0,1fr)]
                    ">

                    {{-- ============================================= --}}
                    {{-- CREACIÓN RÁPIDA --}}
                    {{-- ============================================= --}}

                    <article
                        class="
                            rounded-3xl
                            border
                            border-slate-200
                            bg-white
                            p-6
                            shadow-sm
                            xl:sticky
                            xl:top-24
                        ">

                        <p
                            class="
                                text-xs
                                font-black
                                uppercase
                                tracking-wider
                                text-violet-600
                            ">
                            Nuevo elemento
                        </p>


                        <h3
                            class="
                                mt-2
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Agregar al Catálogo
                        </h3>


                        <p
                            class="
                                mt-2
                                text-sm
                                leading-6
                                text-slate-500
                            ">
                            Crea rápidamente un elemento.
                            Al guardarlo permanecerás en esta página.
                        </p>


                        <form method="POST" action="{{ route('attributes.options.store', $attribute) }}"
                            enctype="multipart/form-data"
                            class="
                                mt-6
                                space-y-5
                            ">

                            @csrf


                            <input type="hidden" name="context" value="attribute_show">


                            <input type="hidden" name="status" value="ACTIVE">


                            {{-- NOMBRE --}}
                            <div>

                                <label
                                    class="
                                        mb-2
                                        block
                                        text-sm
                                        font-bold
                                        text-slate-700
                                    ">
                                    Nombre *
                                </label>


                                <input name="name" value="{{ old('name') }}" required
                                    placeholder="Ejemplo: Naruto"
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-slate-900
                                        placeholder:text-slate-400
                                    ">


                                @error('name')
                                    <p
                                        class="
                                            mt-2
                                            text-xs
                                            font-bold
                                            text-red-600
                                        ">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- IMAGEN --}}
                            <div>

                                <label
                                    class="
                                        mb-2
                                        block
                                        text-sm
                                        font-bold
                                        text-slate-700
                                    ">
                                    Imagen
                                </label>


                                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                                    class="
                                        block
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-300
                                        bg-white
                                        text-xs
                                        text-slate-700

                                        file:mr-3
                                        file:border-0
                                        file:bg-violet-50
                                        file:px-3
                                        file:py-2.5
                                        file:font-bold
                                        file:text-violet-700
                                    ">

                            </div>


                            {{-- SUPERIOR --}}
                            <div>

                                <label
                                    class="
                                        mb-2
                                        block
                                        text-sm
                                        font-bold
                                        text-slate-700
                                    ">
                                    Elemento superior
                                </label>


                                <select name="parent_option_id"
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-slate-900
                                    ">

                                    <option value="">
                                        Ninguno
                                    </option>


                                    @foreach ($parentOptions as $parent)
                                        <option value="{{ $parent->id }}" @selected(old('parent_option_id') == $parent->id)>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- AVANZADO --}}
                            <button type="button"
                                @click="
                                    quickAdvanced =
                                        ! quickAdvanced
                                "
                                class="
                                    text-xs
                                    font-bold
                                    text-violet-600
                                ">
                                <span
                                    x-text="
                                        quickAdvanced
                                            ? '− Menos opciones'
                                            : '+ Más opciones'
                                    "></span>
                            </button>


                            <div x-cloak x-show="quickAdvanced"
                                class="
                                    space-y-4
                                ">

                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block
                                            text-xs
                                            font-bold
                                            text-slate-600
                                        ">
                                        Descripción
                                    </label>


                                    <textarea name="description" rows="3"
                                        class="
                                            w-full
                                            rounded-xl
                                            border-slate-300
                                            bg-white
                                            text-slate-900
                                        ">{{ old('description') }}</textarea>

                                </div>


                                <div
                                    class="
                                        grid
                                        grid-cols-2
                                        gap-3
                                    ">

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-bold
                                                text-slate-600
                                            ">
                                            Icono
                                        </label>


                                        <input name="icon" value="{{ old('icon') }}" placeholder="◆"
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-slate-900
                                            ">

                                    </div>


                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-bold
                                                text-slate-600
                                            ">
                                            Color
                                        </label>


                                        <input type="color" name="color" value="{{ old('color', '#6366F1') }}"
                                            class="
                                                h-11
                                                w-full
                                                rounded-xl
                                                border
                                                border-slate-300
                                                bg-white
                                                p-1
                                            ">

                                    </div>

                                </div>

                            </div>


                            <button type="submit"
                                class="
                                    w-full
                                    rounded-xl
                                    bg-violet-600
                                    px-5
                                    py-3
                                    text-sm
                                    font-black
                                    text-white
                                    hover:bg-violet-700
                                ">
                                + Agregar al Catálogo
                            </button>

                        </form>

                    </article>


                    {{-- ============================================= --}}
                    {{-- EXPLORADOR --}}
                    {{-- ============================================= --}}

                    <div>

                        {{-- FILTROS --}}
                        <form method="GET" action="{{ route('attributes.show', $attribute) }}#catalog"
                            class="
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
                                    md:grid-cols-2
                                    xl:grid-cols-[1fr_150px_190px_120px_auto]
                                ">

                                <input name="catalog_search" value="{{ $catalogSearch }}"
                                    placeholder="Buscar dentro del Catálogo..."
                                    class="
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-slate-900
                                        placeholder:text-slate-400
                                    ">


                                <select name="catalog_status"
                                    class="
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-slate-900
                                    ">

                                    <option value="">
                                        Todo estado
                                    </option>

                                    <option value="ACTIVE" @selected($catalogStatus === 'ACTIVE')>
                                        Activos
                                    </option>

                                    <option value="INACTIVE" @selected($catalogStatus === 'INACTIVE')>
                                        Inactivos
                                    </option>

                                </select>


                                <select name="catalog_sort"
                                    class="
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-slate-900
                                    ">

                                    @foreach ([
        'manual' => 'Orden personalizado',
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'usage_desc' => 'Más utilizado',
        'usage_asc' => 'Menos utilizado',
    ] as $value => $label)
                                        <option value="{{ $value }}" @selected($catalogSort === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach

                                </select>


                                <select name="catalog_per_page"
                                    class="
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-slate-900
                                    ">

                                    @foreach ([12, 24, 48] as $number)
                                        <option value="{{ $number }}" @selected($catalogPerPage === $number)>
                                            {{ $number }}/pág.
                                        </option>
                                    @endforeach

                                </select>


                                <button
                                    class="
                                        rounded-xl
                                        bg-slate-900
                                        px-4
                                        py-3
                                        text-xs
                                        font-black
                                        text-white
                                    ">
                                    Aplicar
                                </button>

                            </div>

                        </form>


                        {{-- VISTAS --}}
                        <div
                            class="
                                mt-4
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
                                md:items-center
                            ">

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    gap-2
                                ">

                                @foreach ([
        'grid' => '▦ Cuadrícula',
        'list' => '☰ Lista',
        'table' => '≡ Tabla',
    ] as $value => $label)
                                    <button type="button"
                                        @click="
                                            setCatalogView(
                                                '{{ $value }}'
                                            )
                                        "
                                        :class="catalogView === '{{ $value }}'
                                        
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


                            <div x-show="
                                    catalogView === 'grid'
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
                                            setCatalogDensity(
                                                '{{ $value }}'
                                            )
                                        "
                                        :class="catalogDensity === '{{ $value }}'
                                        
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


                        {{-- VACÍO --}}
                        @if ($catalogOptions->isEmpty())

                            <div
                                class="
                                    mt-4
                                    rounded-3xl
                                    border
                                    border-dashed
                                    border-slate-300
                                    bg-white
                                    py-16
                                    text-center
                                ">

                                <div class="text-4xl">
                                    ◆
                                </div>


                                <p
                                    class="
                                        mt-4
                                        font-black
                                        text-slate-700
                                    ">
                                    El Catálogo está vacío
                                </p>


                                <p
                                    class="
                                        mt-2
                                        text-sm
                                        text-slate-500
                                    ">
                                    Agrega el primer elemento
                                    desde el formulario lateral.
                                </p>

                            </div>
                        @else
                            {{-- GRID --}}
                            <div x-show="
                                    catalogView === 'grid'
                                "
                                class="
                                    mt-4
                                    grid
                                    gap-4
                                "
                                :class="{
                                
                                    'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': catalogDensity === 'compact',
                                
                                    'sm:grid-cols-2 lg:grid-cols-3': catalogDensity === 'medium',
                                
                                    'sm:grid-cols-2': catalogDensity === 'large'
                                }">

                                @foreach ($catalogOptions as $option)
                                    <article
                                        class="
                                            group
                                            overflow-hidden
                                            rounded-2xl
                                            border
                                            border-slate-200
                                            bg-white
                                            shadow-sm
                                            hover:border-violet-200
                                        ">

                                        <a href="{{ route('attribute-options.show', $option) }}"
                                            class="
                                                block
                                                overflow-hidden
                                                bg-slate-100
                                            "
                                            :class="{
                                                'h-28': catalogDensity === 'compact',
                                            
                                                'h-36': catalogDensity === 'medium',
                                            
                                                'h-48': catalogDensity === 'large'
                                            }">

                                            @if ($option->image_url)
                                                <img src="{{ $option->image_url }}" alt="{{ $option->name }}"
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
                                                    "
                                                    style="
                                                        background-color:
                                                            {{ $option->color ?? '#6366F1' }}20;

                                                        color:
                                                            {{ $option->color ?? '#6366F1' }};
                                                    ">
                                                    {{ $option->icon ?: '◆' }}
                                                </div>
                                            @endif

                                        </a>


                                        <div class="p-4">

                                            <div
                                                class="
                                                    flex
                                                    items-start
                                                    justify-between
                                                    gap-2
                                                ">

                                                <div class="min-w-0">

                                                    <a href="{{ route('attribute-options.show', $option) }}"
                                                        class="
                                                            block
                                                            truncate
                                                            font-black
                                                            text-slate-900
                                                            hover:text-violet-700
                                                        ">
                                                        {{ $option->name }}
                                                    </a>


                                                    <p
                                                        class="
                                                            mt-1
                                                            truncate
                                                            font-mono
                                                            text-[10px]
                                                            font-bold
                                                            text-slate-400
                                                        ">
                                                        {{ $option->code }}
                                                    </p>

                                                </div>


                                                <x-status-badge :status="$option->status" />

                                            </div>


                                            @if ($option->parent)
                                                <p
                                                    class="
                                                        mt-2
                                                        truncate
                                                        text-xs
                                                        text-slate-500
                                                    ">
                                                    Depende de
                                                    <strong>
                                                        {{ $option->parent->name }}
                                                    </strong>
                                                </p>
                                            @endif


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
                                                        text-[10px]
                                                        font-bold
                                                        text-slate-400
                                                    ">
                                                    {{ $option->values_count }}
                                                    usos
                                                </span>


                                                <div
                                                    class="
                                                        flex
                                                        gap-2
                                                    ">

                                                    <a href="{{ route('attribute-options.edit', $option) }}"
                                                        class="
                                                            text-xs
                                                            font-bold
                                                            text-slate-500
                                                            hover:text-slate-900
                                                        ">
                                                        Editar
                                                    </a>


                                                    <a href="{{ route('attribute-options.show', $option) }}"
                                                        class="
                                                            text-xs
                                                            font-black
                                                            text-violet-600
                                                        ">
                                                        Abrir →
                                                    </a>

                                                </div>

                                            </div>

                                        </div>

                                    </article>
                                @endforeach

                            </div>


                            {{-- LISTA --}}
                            <div x-cloak
                                x-show="
                                    catalogView === 'list'
                                "
                                class="
                                    mt-4
                                    space-y-3
                                ">

                                @foreach ($catalogOptions as $option)
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
                                            md:flex-row
                                            md:items-center
                                        ">

                                        <div
                                            class="
                                                h-16
                                                w-full
                                                shrink-0
                                                overflow-hidden
                                                rounded-xl
                                                bg-slate-100
                                                md:w-16
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
                                                        text-2xl
                                                    ">
                                                    {{ $option->icon ?: '◆' }}
                                                </div>
                                            @endif

                                        </div>


                                        <div
                                            class="
                                                min-w-0
                                                flex-1
                                            ">

                                            <a href="{{ route('attribute-options.show', $option) }}"
                                                class="
                                                    font-black
                                                    text-slate-900
                                                    hover:text-violet-700
                                                ">
                                                {{ $option->name }}
                                            </a>


                                            <p
                                                class="
                                                    mt-1
                                                    font-mono
                                                    text-[10px]
                                                    font-bold
                                                    text-slate-400
                                                ">
                                                {{ $option->code }}

                                                @if ($option->parent)
                                                    ·
                                                    {{ $option->parent->name }}
                                                @endif
                                            </p>

                                        </div>


                                        <span
                                            class="
                                                text-xs
                                                font-bold
                                                text-slate-500
                                            ">
                                            {{ $option->values_count }}
                                            usos
                                        </span>


                                        <a href="{{ route('attribute-options.show', $option) }}"
                                            class="
                                                text-xs
                                                font-black
                                                text-violet-600
                                            ">
                                            Abrir →
                                        </a>

                                    </article>
                                @endforeach

                            </div>


                            {{-- TABLA --}}
                            <div x-cloak
                                x-show="
                                    catalogView === 'table'
                                "
                                class="
                                    mt-4
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

                                                @foreach (['Elemento', 'Código', 'Superior', 'Usos', 'Estado', ''] as $heading)
                                                    <th
                                                        class="
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

                                            @foreach ($catalogOptions as $option)
                                                <tr>

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
                                                                    h-10
                                                                    w-10
                                                                    shrink-0
                                                                    overflow-hidden
                                                                    rounded-lg
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
                                                            font-bold
                                                            text-slate-500
                                                        ">
                                                        {{ $option->code }}
                                                    </td>


                                                    <td
                                                        class="
                                                            px-5
                                                            py-4
                                                            text-sm
                                                            text-slate-600
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
                                                        {{ $option->values_count }}
                                                    </td>


                                                    <td
                                                        class="
                                                            px-5
                                                            py-4
                                                        ">
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


                            <div class="mt-6">

                                {{ $catalogOptions->links() }}

                            </div>

                        @endif

                    </div>

                </div>

            </section>
        @else
            {{-- ================================================= --}}
            {{-- SIN CATÁLOGO --}}
            {{-- ================================================= --}}

            <section
                class="
                    mt-8
                    rounded-3xl
                    border
                    border-blue-200
                    bg-blue-50
                    p-6
                ">

                <p
                    class="
                        font-black
                        text-blue-900
                    ">
                    Este atributo no utiliza Catálogo
                </p>


                <p
                    class="
                        mt-2
                        text-sm
                        leading-6
                        text-blue-700
                    ">
                    Su valor se almacena directamente como
                    {{ strtolower($attribute->data_type_label) }}.
                    No necesita elementos seleccionables.
                </p>

            </section>

        @endif

    </div>

</x-app-layout>

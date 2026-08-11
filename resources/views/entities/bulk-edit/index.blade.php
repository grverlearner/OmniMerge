<x-app-layout>

    <x-slot name="header">
        Gestión masiva de Entidades
    </x-slot>


    @include('entities.partials.section-navigation')


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <div x-data="bulkEditManager({
    
        entities: @js($entityPayload),
    
        attributes: @js($attributePayload),
    
        entityTypes: @js($typePayload),
    
        collections: @js($collectionPayload),
    
        initialRules: @js($attributeFilters),
    
        matchedCount: @js($matchedCount)
    })" x-init="init()">

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
                    bg-gradient-to-br
                    from-slate-950
                    via-indigo-950
                    to-violet-950
                    p-6
                    text-white
                    sm:p-8
                ">

                <div
                    class="
                        flex
                        flex-col
                        gap-6
                        xl:flex-row
                        xl:items-start
                        xl:justify-between
                    ">

                    <div>

                        <span
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-full
                                bg-white/10
                                px-3
                                py-1.5
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-indigo-100
                            ">
                            ⚙ Gestión masiva
                        </span>


                        <h1
                            class="
                                mt-4
                                text-3xl
                                font-black
                                tracking-tight
                                sm:text-4xl
                            ">
                            Edita tu Biblioteca a gran escala
                        </h1>


                        <p
                            class="
                                mt-3
                                max-w-3xl
                                text-sm
                                leading-6
                                text-slate-300
                            ">
                            Encuentra, selecciona, agrupa y transforma
                            Entidades sin tener que abrirlas una por una.
                        </p>

                    </div>


                    <a href="{{ route('entities.index') }}"
                        class="
                            shrink-0
                            rounded-xl
                            bg-white/10
                            px-4
                            py-2.5
                            text-sm
                            font-bold
                            text-white
                            backdrop-blur
                            transition
                            hover:bg-white/20
                        ">
                        ← Volver a Entidades
                    </a>

                </div>


                {{-- STATS --}}
                <div
                    class="
                        mt-7
                        grid
                        grid-cols-2
                        gap-2
                        md:grid-cols-5
                    ">

                    @foreach ([['Biblioteca', $stats['total']], ['Activas', $stats['active']], ['Públicas', $stats['public']], ['Con atributos', $stats['with_attributes']], ['Sin imagen', $stats['without_image']]] as [$label, $number])
                        <div
                            class="
                                rounded-2xl
                                bg-white/10
                                p-4
                                backdrop-blur
                            ">

                            <p
                                class="
                                    text-[9px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-white/50
                                ">
                                {{ $label }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-2xl
                                    font-black
                                ">
                                {{ number_format($number) }}
                            </p>

                        </div>
                    @endforeach

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- SUCCESS / ERROR --}}
        {{-- ===================================================== --}}

        @if (session('success'))
            <div
                class="
                    mt-5
                    rounded-2xl
                    border
                    border-emerald-200
                    bg-emerald-50
                    p-4
                    text-sm
                    font-bold
                    text-emerald-700
                ">
                ✓ {{ session('success') }}
            </div>
        @endif


        @if ($errors->any())

            <div
                class="
                    mt-5
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    p-5
                ">

                <p
                    class="
                        font-black
                        text-red-800
                    ">
                    No se pudo realizar la operación.
                </p>


                <ul
                    class="
                        mt-3
                        list-disc
                        space-y-1
                        pl-5
                        text-sm
                        text-red-700
                    ">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ===================================================== --}}
        {{-- NAVEGACIÓN DEL WORKSPACE --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-6
                overflow-x-auto
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-2
                shadow-sm
            ">

            <div
                class="
                    flex
                    min-w-max
                    gap-2
                ">

                <template x-for="
                        tab
                        in tabs
                    "
                    :key="tab.id">

                    <button type="button"
                        @click="
                            activeTab =
                                tab.id
                        "
                        class="
                            flex
                            items-center
                            gap-2
                            rounded-xl
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            transition
                        "
                        :class="activeTab === tab.id
                        
                            ?
                            'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20'
                        
                            :
                            'text-slate-500 hover:bg-slate-100'">

                        <span x-text="
                                tab.icon
                            "></span>


                        <span x-text="
                                tab.label
                            "></span>

                    </button>

                </template>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- FILTROS --}}
        {{-- ===================================================== --}}

        <section x-show="
                activeTab === 'selection'
            "
            class="
                mt-6
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
                    gap-4
                    lg:flex-row
                    lg:items-start
                    lg:justify-between
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
                        Paso 1
                    </p>


                    <h2
                        class="
                            mt-1
                            text-xl
                            font-black
                            text-slate-900
                        ">
                        Encontrar Entidades
                    </h2>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-slate-500
                        ">
                        Primero limita el conjunto sobre el que quieres trabajar.
                    </p>

                </div>


                <div
                    class="
                        rounded-xl
                        bg-indigo-50
                        px-4
                        py-3
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            text-indigo-500
                        ">
                        Coincidencias
                    </p>


                    <p
                        class="
                            mt-1
                            text-xl
                            font-black
                            text-indigo-800
                        ">
                        {{ number_format($matchedCount) }}
                    </p>

                </div>

            </div>


            <form method="GET" action="{{ route('entities.bulk-edit.index') }}"
                class="
                    mt-6
                    space-y-4
                ">

                {{-- FILTROS GENERALES --}}
                <div
                    class="
                        grid
                        min-w-0
                        grid-cols-1
                        gap-3
                        sm:grid-cols-2
                        lg:grid-cols-3
                        xl:grid-cols-4
                    ">

                    <div
                        class="
                            min-w-0
                            sm:col-span-2
                        ">

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Buscar
                        </label>


                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Nombre, código o descripción..."
                            class="
                                w-full
                                min-w-0
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Tipo
                        </label>


                        <select name="type"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Todos
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

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Colección
                        </label>


                        <select name="collection"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Cualquiera
                            </option>


                            @foreach ($collections as $collection)
                                <option value="{{ $collection->id }}" @selected($collectionId === $collection->id)>
                                    {{ $collection->name }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Estado
                        </label>


                        <select name="status"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Todos
                            </option>

                            @foreach ([
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
        'ARCHIVED' => 'Archivado',
    ] as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Visibilidad
                        </label>


                        <select name="visibility"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Todas
                            </option>

                            @foreach ([
        'PUBLIC' => 'Público',
        'PRIVATE' => 'Privado',
        'UNLISTED' => 'No listado',
    ] as $value => $label)
                                <option value="{{ $value }}" @selected($visibility === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Imagen
                        </label>


                        <select name="image"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Cualquiera
                            </option>

                            <option value="yes" @selected($image === 'yes')>
                                Con imagen
                            </option>

                            <option value="no" @selected($image === 'no')>
                                Sin imagen
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Características
                        </label>


                        <select name="attributes_state"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Cualquiera
                            </option>

                            <option value="yes" @selected($attributesState === 'yes')>
                                Con Atributos
                            </option>

                            <option value="no" @selected($attributesState === 'no')>
                                Sin Atributos
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Orden
                        </label>


                        <select name="sort"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="name_asc" @selected($sort === 'name_asc')>
                                Nombre A → Z
                            </option>

                            <option value="name_desc" @selected($sort === 'name_desc')>
                                Nombre Z → A
                            </option>

                            <option value="newest" @selected($sort === 'newest')>
                                Más recientes
                            </option>

                            <option value="oldest" @selected($sort === 'oldest')>
                                Más antiguas
                            </option>

                            <option value="code_asc" @selected($sort === 'code_asc')>
                                Código ↑
                            </option>

                            <option value="code_desc" @selected($sort === 'code_desc')>
                                Código ↓
                            </option>

                            <option value="attributes_desc" @selected($sort === 'attributes_desc')>
                                Más características
                            </option>

                        </select>

                    </div>

                </div>


                {{-- REGLAS DE ATRIBUTOS --}}
                <div
                    class="
                        rounded-2xl
                        border
                        border-violet-100
                        bg-violet-50/40
                        p-4
                    ">

                    <div
                        class="
                            flex
                            flex-col
                            gap-3
                            sm:flex-row
                            sm:items-center
                            sm:justify-between
                        ">

                        <div>

                            <p
                                class="
                                    text-xs
                                    font-black
                                    text-violet-900
                                ">
                                Reglas por características
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[10px]
                                    text-violet-500
                                ">
                                Puedes combinar hasta 3 reglas usando AND / OR.
                            </p>

                        </div>


                        <button type="button"
                            @click="
                                addFilterRule()
                            "
                            x-show="
                                filterRules.length < 3
                            "
                            class="
                                rounded-xl
                                bg-violet-600
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-white
                            ">
                            + Regla
                        </button>

                    </div>


                    <div
                        class="
                            mt-4
                            space-y-3
                        ">

                        <template
                            x-for="
                                (rule, index)
                                in filterRules
                            "
                            :key="rule.key">

                            <div
                                class="
                                    grid
                                    gap-2
                                    rounded-xl
                                    bg-white
                                    p-3
                                    md:grid-cols-[90px_minmax(180px,1fr)_160px_minmax(180px,1fr)_auto]
                                    md:items-end
                                ">

                                <div>

                                    <label
                                        class="
                                            mb-1
                                            block
                                            text-[8px]
                                            font-black
                                            uppercase
                                            text-slate-400
                                        ">
                                        Lógica
                                    </label>


                                    <select
                                        x-model="
                                            rule.logic
                                        "
                                        :name="`attribute_filters[${index}][logic]`"
                                        class="
                                            w-full
                                            rounded-lg
                                            border-slate-300
                                            text-xs
                                        ">

                                        <option value="AND">
                                            AND
                                        </option>

                                        <option value="OR">
                                            OR
                                        </option>

                                    </select>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-1
                                            block
                                            text-[8px]
                                            font-black
                                            uppercase
                                            text-slate-400
                                        ">
                                        Atributo
                                    </label>


                                    <select
                                        x-model="
                                            rule.attribute_id
                                        "
                                        @change="
                                            normalizeFilterRule(
                                                rule
                                            )
                                        "
                                        :name="`attribute_filters[${index}][attribute_id]`"
                                        class="
                                            w-full
                                            rounded-lg
                                            border-slate-300
                                            text-xs
                                        ">

                                        <option value="">
                                            Seleccionar...
                                        </option>


                                        <template
                                            x-for="
                                                attribute
                                                in attributes
                                            "
                                            :key="attribute.id">

                                            <option :value="attribute.id"
                                                x-text="
                                                    attribute.name
                                                ">
                                            </option>

                                        </template>

                                    </select>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-1
                                            block
                                            text-[8px]
                                            font-black
                                            uppercase
                                            text-slate-400
                                        ">
                                        Operador
                                    </label>


                                    <select
                                        x-model="
                                            rule.operator
                                        "
                                        :name="`attribute_filters[${index}][operator]`"
                                        class="
                                            w-full
                                            rounded-lg
                                            border-slate-300
                                            text-xs
                                        ">

                                        <option value="eq">
                                            Igual
                                        </option>

                                        <option value="contains">
                                            Contiene
                                        </option>

                                        <option value="gt">
                                            Mayor que
                                        </option>

                                        <option value="gte">
                                            Mayor o igual
                                        </option>

                                        <option value="lt">
                                            Menor que
                                        </option>

                                        <option value="lte">
                                            Menor o igual
                                        </option>

                                        <option value="between">
                                            Entre
                                        </option>

                                        <option value="present">
                                            Tiene Atributo
                                        </option>

                                        <option value="missing">
                                            No tiene Atributo
                                        </option>

                                    </select>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-1
                                            block
                                            text-[8px]
                                            font-black
                                            uppercase
                                            text-slate-400
                                        ">
                                        Valor
                                    </label>


                                    {{-- OPTION --}}
                                    <template
                                        x-if="
                                            filterAttribute(
                                                rule
                                            )
                                            &&
                                            filterAttribute(
                                                rule
                                            ).data_type === 'OPTION'
                                            &&
                                            ! [
                                                'present',
                                                'missing'
                                            ].includes(
                                                rule.operator
                                            )
                                        ">

                                        <select
                                            x-model="
                                                rule.value
                                            "
                                            :name="`attribute_filters[${index}][value]`"
                                            class="
                                                w-full
                                                rounded-lg
                                                border-slate-300
                                                text-xs
                                            ">

                                            <option value="">
                                                Elegir...
                                            </option>


                                            <template
                                                x-for="
                                                    option
                                                    in filterAttribute(
                                                        rule
                                                    ).options
                                                "
                                                :key="option.id">

                                                <option :value="option.id"
                                                    x-text="
                                                        option.name
                                                    ">
                                                </option>

                                            </template>

                                        </select>

                                    </template>


                                    {{-- GENERAL --}}
                                    <template
                                        x-if="
                                            ! filterAttribute(
                                                rule
                                            )
                                            ||
                                            filterAttribute(
                                                rule
                                            ).data_type !== 'OPTION'
                                        ">

                                        <div
                                            class="
                                                flex
                                                gap-2
                                            ">

                                            <input type="text"
                                                x-model="
                                                    rule.value
                                                "
                                                :name="`attribute_filters[${index}][value]`"
                                                :disabled="[
                                                    'present',
                                                    'missing'
                                                ].includes(
                                                    rule.operator
                                                )"
                                                class="
                                                    w-full
                                                    min-w-0
                                                    rounded-lg
                                                    border-slate-300
                                                    text-xs
                                                ">


                                            <input
                                                x-show="
                                                    rule.operator === 'between'
                                                "
                                                type="text"
                                                x-model="
                                                    rule.value2
                                                "
                                                :name="`attribute_filters[${index}][value2]`" placeholder="Hasta"
                                                class="
                                                    w-full
                                                    min-w-0
                                                    rounded-lg
                                                    border-slate-300
                                                    text-xs
                                                ">

                                        </div>

                                    </template>

                                </div>


                                <button type="button"
                                    @click="
                                        removeFilterRule(
                                            index
                                        )
                                    "
                                    class="
                                        rounded-lg
                                        bg-red-50
                                        px-3
                                        py-2
                                        text-xs
                                        font-black
                                        text-red-500
                                    ">
                                    ×
                                </button>

                            </div>

                        </template>

                    </div>

                </div>


                <div
                    class="
                        flex
                        flex-col
                        gap-2
                        border-t
                        border-slate-100
                        pt-4
                        sm:flex-row
                        sm:justify-between
                    ">

                    <a href="{{ route('entities.bulk-edit.index') }}"
                        class="
                            rounded-xl
                            px-4
                            py-2.5
                            text-center
                            text-xs
                            font-bold
                            text-slate-500
                            hover:bg-slate-100
                        ">
                        × Limpiar
                    </a>


                    <button type="submit"
                        class="
                            rounded-xl
                            bg-slate-900
                            px-5
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Aplicar filtros
                    </button>

                </div>

            </form>

        </section>


        {{-- ===================================================== --}}
        {{-- SELECCIÓN --}}
        {{-- ===================================================== --}}

        <section x-show="
                activeTab === 'selection'
            "
            class="
                mt-6
                rounded-3xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">

            <div
                class="
                    flex
                    flex-col
                    gap-4
                    border-b
                    border-slate-100
                    p-5
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                ">

                <div>

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            text-indigo-500
                        ">
                        Paso 2
                    </p>


                    <h2
                        class="
                            mt-1
                            text-lg
                            font-black
                            text-slate-900
                        ">
                        Seleccionar
                    </h2>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-400
                        ">
                        <strong>
                            {{ $entities->count() }}
                        </strong>

                        cargadas en este Workspace.

                        @if ($matchedCount > 500)
                            Hay más resultados; reduce los filtros
                            para trabajar con los restantes.
                        @endif
                    </p>

                </div>


                <div
                    class="
                        flex
                        flex-wrap
                        gap-2
                    ">

                    <button type="button" @click="
                            selectAll()
                        "
                        class="
                            rounded-xl
                            bg-indigo-50
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-indigo-700
                        ">
                        Seleccionar todas
                    </button>


                    <button type="button"
                        @click="
                            clearSelection()
                        "
                        class="
                            rounded-xl
                            bg-slate-100
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-slate-500
                        ">
                        Limpiar selección
                    </button>

                </div>

            </div>


            {{-- AGRUPACIÓN --}}
            <div
                class="
                    border-b
                    border-slate-100
                    bg-slate-50/60
                    p-4
                ">

                <div
                    class="
                        grid
                        gap-3
                        sm:grid-cols-2
                        lg:max-w-3xl
                    ">

                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Jerarquía nivel 1
                        </label>


                        <select x-model="
                                groupLevel1
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-xs
                            ">

                            <option value="">
                                Sin agrupar
                            </option>

                            <option value="type">
                                Tipo
                            </option>

                            <option value="status">
                                Estado
                            </option>

                            <option value="visibility">
                                Visibilidad
                            </option>

                            <option value="collection">
                                Primera Colección
                            </option>

                            <option value="image">
                                Tiene imagen
                            </option>


                            <template
                                x-for="
                                    attribute
                                    in attributes
                                "
                                :key="`group-1-${attribute.id}`">

                                <option :value="`attribute:${attribute.id}`"
                                    x-text="
                                        `Atributo · ${attribute.name}`
                                    ">
                                </option>

                            </template>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Jerarquía nivel 2
                        </label>


                        <select x-model="
                                groupLevel2
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-xs
                            ">

                            <option value="">
                                Sin segundo nivel
                            </option>

                            <option value="type">
                                Tipo
                            </option>

                            <option value="status">
                                Estado
                            </option>

                            <option value="visibility">
                                Visibilidad
                            </option>

                            <option value="collection">
                                Primera Colección
                            </option>

                            <option value="image">
                                Tiene imagen
                            </option>


                            <template
                                x-for="
                                    attribute
                                    in attributes
                                "
                                :key="`group-2-${attribute.id}`">

                                <option :value="`attribute:${attribute.id}`"
                                    x-text="
                                        `Atributo · ${attribute.name}`
                                    ">
                                </option>

                            </template>

                        </select>

                    </div>

                </div>

            </div>


            {{-- SIN AGRUPAR --}}
            <div x-show="
                    ! groupLevel1
                "
                class="
                    divide-y
                    divide-slate-100
                ">

                <template
                    x-for="
                        entity
                        in entities
                    "
                    :key="entity.id">

                    <div class="
                            flex
                            min-w-0
                            items-center
                            gap-4
                            p-4
                            transition
                        "
                        :class="isSelected(
                                entity.id
                            )
                        
                            ?
                            'bg-indigo-50/60'
                        
                            :
                            'hover:bg-slate-50'">

                        <input type="checkbox"
                            :checked="isSelected(
                                entity.id
                            )"
                            @change="
                                toggleSelection(
                                    entity.id
                                )
                            "
                            class="
                                shrink-0
                                rounded
                                border-slate-300
                                text-indigo-600
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

                            <template
                                x-if="
                                    entity.image_url
                                ">

                                <img :src="entity.image_url"
                                    class="
                                        h-full
                                        w-full
                                        object-cover
                                    ">

                            </template>


                            <template
                                x-if="
                                    ! entity.image_url
                                ">

                                <div
                                    class="
                                        flex
                                        h-full
                                        items-center
                                        justify-center
                                        text-lg
                                        text-indigo-300
                                    ">
                                    ✦
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
                                    flex-wrap
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
                                        entity.name
                                    ">
                                </p>


                                <span
                                    class="
                                        rounded-full
                                        bg-slate-100
                                        px-2
                                        py-0.5
                                        font-mono
                                        text-[8px]
                                        font-bold
                                        text-slate-400
                                    "
                                    x-text="
                                        entity.code
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
                                        entity.entity_type_name
                                    "></span>

                                ·

                                <span
                                    x-text="
                                        entity.entity_attributes_count
                                    "></span>

                                características ·

                                <span
                                    x-text="
                                        entity.collections_count
                                    "></span>

                                colecciones
                            </p>

                        </div>


                        <span
                            class="
                                hidden
                                rounded-full
                                bg-slate-100
                                px-2.5
                                py-1
                                text-[9px]
                                font-black
                                text-slate-500
                                md:inline-flex
                            "
                            x-text="
                                entity.status_label
                            "></span>

                    </div>

                </template>

            </div>


            {{-- AGRUPACIÓN JERÁRQUICA --}}
            <div x-show="
                    groupLevel1
                " x-cloak
                class="
                    space-y-5
                    p-5
                ">

                <template
                    x-for="
                        ([groupName, groupEntities])
                        in Object.entries(
                            groupedLevel1
                        )
                    "
                    :key="groupName">

                    <section
                        class="
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                        ">

                        <header
                            class="
                                flex
                                items-center
                                justify-between
                                bg-slate-50
                                px-4
                                py-3
                            ">

                            <div>

                                <p class="
                                        text-xs
                                        font-black
                                        uppercase
                                        tracking-wider
                                        text-slate-700
                                    "
                                    x-text="
                                        groupName
                                    ">
                                </p>


                                <p
                                    class="
                                        mt-0.5
                                        text-[9px]
                                        text-slate-400
                                    ">
                                    <span
                                        x-text="
                                            groupEntities.length
                                        "></span>

                                    Entidades
                                </p>

                            </div>


                            <button type="button"
                                @click="
                                    selectEntities(
                                        groupEntities
                                    )
                                "
                                class="
                                    text-[10px]
                                    font-black
                                    text-indigo-600
                                ">
                                Seleccionar grupo
                            </button>

                        </header>


                        {{-- NIVEL 2 --}}
                        <div x-show="
                                groupLevel2
                            "
                            class="
                                space-y-3
                                p-3
                            ">

                            <template
                                x-for="
                                    ([secondName, secondEntities])
                                    in Object.entries(
                                        groupSecond(
                                            groupEntities
                                        )
                                    )
                                "
                                :key="`${groupName}-${secondName}`">

                                <div
                                    class="
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-100
                                    ">

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            bg-indigo-50/50
                                            px-3
                                            py-2
                                        ">

                                        <span
                                            class="
                                                text-[10px]
                                                font-black
                                                text-indigo-700
                                            "
                                            x-text="
                                                secondName
                                            "></span>


                                        <span
                                            class="
                                                text-[8px]
                                                text-indigo-400
                                            "
                                            x-text="
                                                secondEntities.length
                                            "></span>

                                    </div>


                                    <div
                                        class="
                                            grid
                                            gap-2
                                            p-3
                                            sm:grid-cols-2
                                            xl:grid-cols-3
                                        ">

                                        <template
                                            x-for="
                                                entity
                                                in secondEntities
                                            "
                                            :key="entity.id">

                                            <button type="button"
                                                @click="
                                                    toggleSelection(
                                                        entity.id
                                                    )
                                                "
                                                class="
                                                    flex
                                                    min-w-0
                                                    items-center
                                                    gap-3
                                                    rounded-xl
                                                    border
                                                    p-2.5
                                                    text-left
                                                "
                                                :class="isSelected(
                                                        entity.id
                                                    )
                                                
                                                    ?
                                                    'border-indigo-300 bg-indigo-50'
                                                
                                                    :
                                                    'border-slate-100 bg-white'">

                                                <div
                                                    class="
                                                        h-9
                                                        w-9
                                                        shrink-0
                                                        overflow-hidden
                                                        rounded-lg
                                                        bg-slate-100
                                                    ">

                                                    <template
                                                        x-if="
                                                            entity.image_url
                                                        ">

                                                        <img :src="entity.image_url"
                                                            class="
                                                                h-full
                                                                w-full
                                                                object-cover
                                                            ">

                                                    </template>

                                                </div>


                                                <div
                                                    class="
                                                        min-w-0
                                                    ">

                                                    <p class="
                                                            truncate
                                                            text-xs
                                                            font-black
                                                            text-slate-700
                                                        "
                                                        x-text="
                                                            entity.name
                                                        ">
                                                    </p>


                                                    <p class="
                                                            mt-0.5
                                                            truncate
                                                            font-mono
                                                            text-[8px]
                                                            text-slate-400
                                                        "
                                                        x-text="
                                                            entity.code
                                                        ">
                                                    </p>

                                                </div>

                                            </button>

                                        </template>

                                    </div>

                                </div>

                            </template>

                        </div>


                        {{-- SOLO NIVEL 1 --}}
                        <div x-show="
                                ! groupLevel2
                            "
                            class="
                                grid
                                gap-2
                                p-3
                                sm:grid-cols-2
                                lg:grid-cols-3
                                xl:grid-cols-4
                            ">

                            <template
                                x-for="
                                    entity
                                    in groupEntities
                                "
                                :key="entity.id">

                                <button type="button"
                                    @click="
                                        toggleSelection(
                                            entity.id
                                        )
                                    "
                                    class="
                                        min-w-0
                                        rounded-xl
                                        border
                                        p-3
                                        text-left
                                    "
                                    :class="isSelected(
                                            entity.id
                                        )
                                    
                                        ?
                                        'border-indigo-300 bg-indigo-50'
                                    
                                        :
                                        'border-slate-100'">

                                    <p class="
                                            truncate
                                            text-xs
                                            font-black
                                            text-slate-700
                                        "
                                        x-text="
                                            entity.name
                                        ">
                                    </p>


                                    <p class="
                                            mt-1
                                            truncate
                                            font-mono
                                            text-[8px]
                                            text-slate-400
                                        "
                                        x-text="
                                            entity.code
                                        ">
                                    </p>

                                </button>

                            </template>

                        </div>

                    </section>

                </template>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- SELECCIÓN ACTUAL --}}
        {{-- ===================================================== --}}

        <div x-show="
                selectedCount > 0
            " x-cloak
            class="
                sticky
                top-4
                z-40
                mt-5
                flex
                flex-col
                gap-3
                rounded-2xl
                border
                border-indigo-200
                bg-indigo-950/95
                p-4
                text-white
                shadow-2xl
                backdrop-blur
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">

            <div>

                <p class="
                        text-sm
                        font-black
                    ">
                    <span x-text="
                            selectedCount
                        "></span>

                    Entidades seleccionadas
                </p>


                <p
                    class="
                        mt-0.5
                        text-[10px]
                        text-indigo-200
                    ">
                    Ahora elige qué quieres modificar.
                </p>

            </div>


            <div
                class="
                    flex
                    flex-wrap
                    gap-2
                ">

                <button type="button"
                    @click="
                        activeTab =
                            'matrix'
                    "
                    class="
                        rounded-lg
                        bg-white/10
                        px-3
                        py-2
                        text-xs
                        font-bold
                    ">
                    ▦ Matriz
                </button>


                <button type="button"
                    @click="
                        activeTab =
                            'attributes'
                    "
                    class="
                        rounded-lg
                        bg-white/10
                        px-3
                        py-2
                        text-xs
                        font-bold
                    ">
                    ◆ Características
                </button>


                <button type="button"
                    @click="
                        activeTab =
                            'collections'
                    "
                    class="
                        rounded-lg
                        bg-white/10
                        px-3
                        py-2
                        text-xs
                        font-bold
                    ">
                    ▤ Colecciones
                </button>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- SIN SELECCIÓN --}}
        {{-- ===================================================== --}}

        <div x-show="
                activeTab !== 'selection'
                &&
                selectedCount === 0
            "
            x-cloak
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

            <div class="
                    text-5xl
                ">
                ☑
            </div>


            <p
                class="
                    mt-4
                    font-black
                    text-slate-700
                ">
                Primero selecciona Entidades
            </p>


            <button type="button"
                @click="
                    activeTab =
                        'selection'
                "
                class="
                    mt-4
                    rounded-xl
                    bg-indigo-600
                    px-5
                    py-2.5
                    text-sm
                    font-black
                    text-white
                ">
                Ir a selección
            </button>

        </div>


        {{-- ===================================================== --}}
        {{-- EDICIÓN RÁPIDA --}}
        {{-- ===================================================== --}}

        <section
            x-show="
                activeTab === 'quick'
                &&
                selectedCount > 0
            "
            x-cloak class="
                mt-6
                space-y-5
            ">

            <div
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    shadow-sm
                ">

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        text-indigo-500
                    ">
                    Edición rápida
                </p>


                <h2
                    class="
                        mt-1
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Propiedades generales
                </h2>


                <div
                    class="
                        mt-6
                        grid
                        gap-5
                        lg:grid-cols-2
                    ">

                    {{-- TIPO --}}
                    <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                        class="
                            rounded-2xl
                            border
                            border-slate-200
                            p-5
                        ">

                        @csrf

                        <input type="hidden" name="operation" value="set_property">

                        <input type="hidden" name="property" value="entity_type_id">


                        <template
                            x-for="
                                id
                                in selectedIds
                            "
                            :key="`type-${id}`">
                            <input type="hidden" name="entity_ids[]" :value="id">
                        </template>


                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Tipo de Entidad
                        </label>


                        <select name="property_value"
                            class="
                                mt-3
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Sin Tipo
                            </option>


                            @foreach ($entityTypes as $entityType)
                                <option value="{{ $entityType->id }}">
                                    {{ $entityType->name }}
                                </option>
                            @endforeach

                        </select>


                        <button type="submit"
                            class="
                                mt-3
                                w-full
                                rounded-xl
                                bg-indigo-600
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                            ">
                            Aplicar a seleccionadas
                        </button>

                    </form>


                    {{-- DESCRIPCIÓN --}}
                    <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                        class="
                            rounded-2xl
                            border
                            border-slate-200
                            p-5
                        ">

                        @csrf

                        <input type="hidden" name="operation" value="set_property">

                        <input type="hidden" name="property" value="description">


                        <template
                            x-for="
                                id
                                in selectedIds
                            "
                            :key="`desc-${id}`">
                            <input type="hidden" name="entity_ids[]" :value="id">
                        </template>


                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Establecer descripción común
                        </label>


                        <textarea name="property_value" rows="4" placeholder="Vacío = eliminar descripción"
                            class="
                                mt-3
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            "></textarea>


                        <button type="submit"
                            class="
                                mt-3
                                w-full
                                rounded-xl
                                bg-slate-900
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                            ">
                            Aplicar descripción
                        </button>

                    </form>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- MATRIZ --}}
        {{-- ===================================================== --}}

        <section
            x-show="
                activeTab === 'matrix'
                &&
                selectedCount > 0
            "
            x-cloak
            class="
                mt-6
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
                    p-5
                ">

                <div
                    class="
                        flex
                        flex-col
                        gap-4
                        xl:flex-row
                        xl:items-start
                        xl:justify-between
                    ">

                    <div>

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                text-indigo-500
                            ">
                            Editor matricial
                        </p>


                        <h2
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Entidades × Características
                        </h2>


                        <p
                            class="
                                mt-2
                                text-xs
                                text-slate-400
                            ">
                            Edita directamente los datos y luego guarda
                            todo el conjunto.
                        </p>

                    </div>


                    <div
                        class="
                            w-full
                            xl:max-w-md
                        ">

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Columnas de Atributos
                        </label>


                        <select multiple
                            x-model="
                                matrixAttributeIds
                            "
                            class="
                                min-h-28
                                w-full
                                rounded-xl
                                border-slate-300
                                text-xs
                            ">

                            <template
                                x-for="
                                    attribute
                                    in attributes
                                "
                                :key="`matrix-option-${attribute.id}`">

                                <option :value="attribute.id"
                                    x-text="
                                        `${attribute.name} · ${attribute.data_type_label}`
                                    ">
                                </option>

                            </template>

                        </select>

                    </div>

                </div>

            </div>


            <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                @submit="
                    matrixSubmitting =
                        true
                ">

                @csrf


                <input type="hidden" name="operation" value="matrix_update">


                <input type="hidden" name="matrix_payload"
                    :value="JSON.stringify(
                        matrixPayload()
                    )">


                <template
                    x-for="
                        id
                        in selectedIds
                    "
                    :key="`matrix-id-${id}`">
                    <input type="hidden" name="entity_ids[]" :value="id">
                </template>


                <div class="
                        overflow-x-auto
                    ">

                    <table
                        class="
                            min-w-max
                            w-full
                        ">

                        <thead class="
                                bg-slate-50
                            ">

                            <tr>

                                <th
                                    class="
                                        sticky
                                        left-0
                                        z-20
                                        min-w-56
                                        bg-slate-50
                                        px-3
                                        py-3
                                        text-left
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Entidad
                                </th>


                                <th
                                    class="
                                        min-w-48
                                        px-3
                                        py-3
                                        text-left
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Tipo
                                </th>


                                <th
                                    class="
                                        min-w-36
                                        px-3
                                        py-3
                                        text-left
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Estado
                                </th>


                                <th
                                    class="
                                        min-w-36
                                        px-3
                                        py-3
                                        text-left
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Visibilidad
                                </th>


                                <template
                                    x-for="
                                        attribute
                                        in matrixAttributes
                                    "
                                    :key="`matrix-head-${attribute.id}`">

                                    <th
                                        class="
                                            min-w-56
                                            px-3
                                            py-3
                                            text-left
                                            text-[9px]
                                            font-black
                                            uppercase
                                            text-slate-400
                                        ">
                                        <span
                                            x-text="
                                                attribute.name
                                            "></span>

                                        <span
                                            class="
                                                ml-1
                                                font-normal
                                                normal-case
                                                text-slate-300
                                            "
                                            x-text="
                                                attribute.data_type_label
                                            "></span>
                                    </th>

                                </template>

                            </tr>

                        </thead>


                        <tbody
                            class="
                                divide-y
                                divide-slate-100
                            ">

                            <template
                                x-for="
                                    entity
                                    in selectedEntities
                                "
                                :key="`matrix-row-${entity.id}`">

                                <tr
                                    class="
                                        align-top
                                        hover:bg-slate-50/60
                                    ">

                                    <td
                                        class="
                                            sticky
                                            left-0
                                            z-10
                                            bg-white
                                            px-3
                                            py-3
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

                                                <template
                                                    x-if="
                                                        entity.image_url
                                                    ">

                                                    <img :src="entity.image_url"
                                                        class="
                                                            h-full
                                                            w-full
                                                            object-cover
                                                        ">

                                                </template>

                                            </div>


                                            <div
                                                class="
                                                    min-w-0
                                                    flex-1
                                                ">

                                                <input type="text"
                                                    x-model="
                                                        entity.edit.name
                                                    "
                                                    class="
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        text-xs
                                                        font-bold
                                                    ">


                                                <p class="
                                                        mt-1
                                                        font-mono
                                                        text-[8px]
                                                        text-slate-400
                                                    "
                                                    x-text="
                                                        entity.code
                                                    ">
                                                </p>

                                            </div>

                                        </div>

                                    </td>


                                    <td
                                        class="
                                            px-3
                                            py-3
                                        ">

                                        <select
                                            x-model="
                                                entity.edit.entity_type_id
                                            "
                                            class="
                                                w-full
                                                rounded-lg
                                                border-slate-300
                                                text-xs
                                            ">

                                            <option value="">
                                                Sin tipo
                                            </option>


                                            <template
                                                x-for="
                                                    type
                                                    in entityTypes
                                                "
                                                :key="type.id">

                                                <option :value="type.id"
                                                    x-text="
                                                        type.name
                                                    ">
                                                </option>

                                            </template>

                                        </select>

                                    </td>


                                    <td
                                        class="
                                            px-3
                                            py-3
                                        ">

                                        <select
                                            x-model="
                                                entity.edit.status
                                            "
                                            class="
                                                w-full
                                                rounded-lg
                                                border-slate-300
                                                text-xs
                                            ">

                                            <option value="ACTIVE">
                                                Activo
                                            </option>

                                            <option value="INACTIVE">
                                                Inactivo
                                            </option>

                                            <option value="ARCHIVED">
                                                Archivado
                                            </option>

                                        </select>

                                    </td>


                                    <td
                                        class="
                                            px-3
                                            py-3
                                        ">

                                        <select
                                            x-model="
                                                entity.edit.visibility
                                            "
                                            class="
                                                w-full
                                                rounded-lg
                                                border-slate-300
                                                text-xs
                                            ">

                                            <option value="PUBLIC">
                                                Público
                                            </option>

                                            <option value="PRIVATE">
                                                Privado
                                            </option>

                                            <option value="UNLISTED">
                                                No listado
                                            </option>

                                        </select>

                                    </td>


                                    <template
                                        x-for="
                                            attribute
                                            in matrixAttributes
                                        "
                                        :key="`matrix-cell-${entity.id}-${attribute.id}`">

                                        <td
                                            class="
                                                px-3
                                                py-3
                                            ">

                                            {{-- OPTION SIMPLE --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'OPTION'
                                                    &&
                                                    ! attribute.allows_multiple
                                                ">

                                                <select
                                                    x-model="
                                                        entity.edit.attribute_values[
                                                            attribute.id
                                                        ]
                                                    "
                                                    class="
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                                    <option value="">
                                                        Sin valor
                                                    </option>


                                                    <template
                                                        x-for="
                                                            option
                                                            in attribute.options
                                                        "
                                                        :key="option.id">

                                                        <option :value="option.id"
                                                            x-text="
                                                                option.name
                                                            ">
                                                        </option>

                                                    </template>

                                                </select>

                                            </template>


                                            {{-- OPTION MULTI --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'OPTION'
                                                    &&
                                                    attribute.allows_multiple
                                                ">

                                                <select multiple
                                                    x-model="
                                                        entity.edit.attribute_values[
                                                            attribute.id
                                                        ]
                                                    "
                                                    class="
                                                        min-h-24
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                                    <template
                                                        x-for="
                                                            option
                                                            in attribute.options
                                                        "
                                                        :key="option.id">

                                                        <option :value="option.id"
                                                            x-text="
                                                                option.name
                                                            ">
                                                        </option>

                                                    </template>

                                                </select>

                                            </template>


                                            {{-- BOOLEAN --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'BOOLEAN'
                                                ">

                                                <select
                                                    x-model="
                                                        entity.edit.attribute_values[
                                                            attribute.id
                                                        ]
                                                    "
                                                    class="
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                                    <option value="">
                                                        Sin valor
                                                    </option>

                                                    <option value="1">
                                                        Sí
                                                    </option>

                                                    <option value="0">
                                                        No
                                                    </option>

                                                </select>

                                            </template>


                                            {{-- NUMBERS --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'INTEGER'
                                                    ||
                                                    attribute.data_type === 'DECIMAL'
                                                ">

                                                <input type="number"
                                                    :step="attribute.data_type === 'INTEGER' ?
                                                        '1' :
                                                        'any'"
                                                    x-model="
                                                        entity.edit.attribute_values[
                                                            attribute.id
                                                        ]
                                                    "
                                                    class="
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                            </template>


                                            {{-- DATE --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'DATE'
                                                ">

                                                <input type="date"
                                                    x-model="
                                                        entity.edit.attribute_values[
                                                            attribute.id
                                                        ]
                                                    "
                                                    class="
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                            </template>


                                            {{-- COLOR --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'COLOR'
                                                ">

                                                <input type="text"
                                                    x-model="
                                                        entity.edit.attribute_values[
                                                            attribute.id
                                                        ]
                                                    "
                                                    placeholder="#6366F1"
                                                    class="
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        font-mono
                                                        text-xs
                                                    ">

                                            </template>


                                            {{-- TEXT --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'TEXT'
                                                    ||
                                                    attribute.data_type === 'LONG_TEXT'
                                                ">

                                                <input type="text"
                                                    x-model="
                                                        entity.edit.attribute_values[
                                                            attribute.id
                                                        ]
                                                    "
                                                    class="
                                                        w-full
                                                        rounded-lg
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                            </template>

                                        </td>

                                    </template>

                                </tr>

                            </template>

                        </tbody>

                    </table>

                </div>


                <div
                    class="
                        flex
                        justify-end
                        border-t
                        border-slate-100
                        p-4
                    ">

                    <button type="submit"
                        class="
                            rounded-xl
                            bg-indigo-600
                            px-6
                            py-3
                            text-sm
                            font-black
                            text-white
                            shadow-lg
                            shadow-indigo-600/20
                        ">
                        Guardar matriz
                    </button>

                </div>

            </form>

        </section>


        {{-- ===================================================== --}}
        {{-- CARACTERÍSTICAS --}}
        {{-- ===================================================== --}}

        <section
            x-show="
                activeTab === 'attributes'
                &&
                selectedCount > 0
            "
            x-cloak
            class="
                mt-6
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    text-violet-500
                ">
                Características
            </p>


            <h2
                class="
                    mt-1
                    text-xl
                    font-black
                    text-slate-900
                ">
                Atributos y Catálogos
            </h2>


            <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                class="
                    mt-6
                ">

                @csrf


                <template
                    x-for="
                        id
                        in selectedIds
                    "
                    :key="`attribute-op-${id}`">
                    <input type="hidden" name="entity_ids[]" :value="id">
                </template>


                <input type="hidden" name="operation" :value="attributeOperation">


                <input type="hidden" name="attribute_id" :value="selectedAttributeId">


                <input type="hidden" name="attribute_value_json"
                    :value="JSON.stringify(
                        attributeValue
                    )">


                <div
                    class="
                        grid
                        gap-4
                        lg:grid-cols-3
                    ">

                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Acción
                        </label>


                        <select
                            x-model="
                                attributeOperation
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="set_attribute">
                                Establecer / reemplazar valor
                            </option>

                            <option value="append_attribute">
                                Añadir valor sin quitar existentes
                            </option>

                            <option value="remove_attribute_value">
                                Quitar valor específico
                            </option>

                            <option value="clear_attribute_value">
                                Limpiar valor
                            </option>

                            <option value="remove_attribute">
                                Eliminar Atributo completo
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Atributo
                        </label>


                        <select
                            x-model="
                                selectedAttributeId
                            "
                            @change="
                                resetAttributeValue()
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Seleccionar...
                            </option>


                            <template
                                x-for="
                                    attribute
                                    in attributes
                                "
                                :key="attribute.id">

                                <option :value="attribute.id"
                                    x-text="
                                        `${attribute.name} · ${attribute.data_type_label}`
                                    ">
                                </option>

                            </template>

                        </select>

                    </div>


                    <div
                        x-show="
                            ! [
                                'clear_attribute_value',
                                'remove_attribute'
                            ].includes(
                                attributeOperation
                            )
                        ">

                        <label
                            class="
                                mb-1.5
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Valor
                        </label>


                        {{-- OPTION SIMPLE --}}
                        <template
                            x-if="
                                currentAttribute
                                &&
                                currentAttribute.data_type === 'OPTION'
                                &&
                                ! currentAttribute.allows_multiple
                            ">

                            <select
                                x-model="
                                    attributeValue
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                ">

                                <option value="">
                                    Sin valor
                                </option>


                                <template
                                    x-for="
                                        option
                                        in currentAttribute.options
                                    "
                                    :key="option.id">

                                    <option :value="option.id"
                                        x-text="
                                            option.name
                                        ">
                                    </option>

                                </template>

                            </select>

                        </template>


                        {{-- OPTION MULTI --}}
                        <template
                            x-if="
                                currentAttribute
                                &&
                                currentAttribute.data_type === 'OPTION'
                                &&
                                currentAttribute.allows_multiple
                            ">

                            <select multiple
                                x-model="
                                    attributeValue
                                "
                                class="
                                    min-h-32
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                ">

                                <template
                                    x-for="
                                        option
                                        in currentAttribute.options
                                    "
                                    :key="option.id">

                                    <option :value="option.id"
                                        x-text="
                                            option.name
                                        ">
                                    </option>

                                </template>

                            </select>

                        </template>


                        {{-- BOOLEAN --}}
                        <template
                            x-if="
                                currentAttribute
                                &&
                                currentAttribute.data_type === 'BOOLEAN'
                            ">

                            <select
                                x-model="
                                    attributeValue
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                ">

                                <option value="">
                                    Sin valor
                                </option>

                                <option value="1">
                                    Sí
                                </option>

                                <option value="0">
                                    No
                                </option>

                            </select>

                        </template>


                        {{-- NUMBER --}}
                        <template
                            x-if="
                                currentAttribute
                                &&
                                (
                                    currentAttribute.data_type === 'INTEGER'
                                    ||
                                    currentAttribute.data_type === 'DECIMAL'
                                )
                            ">

                            <input type="number"
                                :step="currentAttribute.data_type === 'INTEGER' ?
                                    '1' :
                                    'any'"
                                x-model="
                                    attributeValue
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                ">

                        </template>


                        {{-- DATE --}}
                        <template
                            x-if="
                                currentAttribute
                                &&
                                currentAttribute.data_type === 'DATE'
                            ">

                            <input type="date"
                                x-model="
                                    attributeValue
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                ">

                        </template>


                        {{-- COLOR --}}
                        <template
                            x-if="
                                currentAttribute
                                &&
                                currentAttribute.data_type === 'COLOR'
                            ">

                            <input type="text"
                                x-model="
                                    attributeValue
                                "
                                placeholder="#6366F1"
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    font-mono
                                    text-sm
                                ">

                        </template>


                        {{-- TEXT --}}
                        <template
                            x-if="
                                currentAttribute
                                &&
                                (
                                    currentAttribute.data_type === 'TEXT'
                                    ||
                                    currentAttribute.data_type === 'LONG_TEXT'
                                )
                            ">

                            <input type="text"
                                x-model="
                                    attributeValue
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-sm
                                ">

                        </template>

                    </div>

                </div>


                <div
                    class="
                        mt-5
                        rounded-2xl
                        bg-slate-50
                        p-4
                        text-xs
                        leading-6
                        text-slate-500
                    ">

                    <template
                        x-if="
                            attributeOperation === 'set_attribute'
                        ">
                        <p>
                            <strong>Establecer:</strong>
                            reemplaza el valor del Atributo en las Entidades seleccionadas.
                        </p>
                    </template>


                    <template
                        x-if="
                            attributeOperation === 'append_attribute'
                        ">
                        <p>
                            <strong>Añadir:</strong>
                            en Atributos multivalor conserva lo existente y añade el nuevo valor.
                        </p>
                    </template>


                    <template
                        x-if="
                            attributeOperation === 'remove_attribute'
                        ">
                        <p class="text-red-600">
                            <strong>Eliminar Atributo:</strong>
                            elimina la asignación y sus valores de las Entidades seleccionadas.
                        </p>
                    </template>

                </div>


                <button type="submit"
                    class="
                        mt-5
                        rounded-xl
                        bg-violet-600
                        px-6
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Aplicar a

                    <span x-text="
                            selectedCount
                        "></span>

                    Entidades
                </button>

            </form>

        </section>


        {{-- ===================================================== --}}
        {{-- COLECCIONES --}}
        {{-- ===================================================== --}}

        <section
            x-show="
                activeTab === 'collections'
                &&
                selectedCount > 0
            "
            x-cloak
            class="
                mt-6
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    text-cyan-500
                ">
                Organización
            </p>


            <h2
                class="
                    mt-1
                    text-xl
                    font-black
                    text-slate-900
                ">
                Colecciones
            </h2>


            <div
                class="
                    mt-6
                    grid
                    gap-5
                    lg:grid-cols-3
                ">

                {{-- ADD --}}
                <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                    class="
                        rounded-2xl
                        border
                        border-emerald-200
                        bg-emerald-50/40
                        p-5
                    ">

                    @csrf

                    <input type="hidden" name="operation" value="add_collection">


                    <template
                        x-for="
                            id
                            in selectedIds
                        "
                        :key="`add-col-${id}`">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>


                    <p
                        class="
                            font-black
                            text-emerald-800
                        ">
                        + Añadir
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-emerald-600
                        ">
                        Conserva las Colecciones existentes.
                    </p>


                    <select name="collection_id"
                        class="
                            mt-4
                            w-full
                            rounded-xl
                            border-emerald-200
                            bg-white
                            text-sm
                        ">

                        <option value="">
                            Seleccionar...
                        </option>


                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}">
                                {{ $collection->name }}
                            </option>
                        @endforeach

                    </select>


                    <button type="submit"
                        class="
                            mt-3
                            w-full
                            rounded-xl
                            bg-emerald-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Añadir Colección
                    </button>

                </form>


                {{-- REMOVE --}}
                <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                    class="
                        rounded-2xl
                        border
                        border-amber-200
                        bg-amber-50/40
                        p-5
                    ">

                    @csrf

                    <input type="hidden" name="operation" value="remove_collection">


                    <template
                        x-for="
                            id
                            in selectedIds
                        "
                        :key="`remove-col-${id}`">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>


                    <p
                        class="
                            font-black
                            text-amber-800
                        ">
                        − Quitar
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-amber-600
                        ">
                        Solo elimina la relación con esa Colección.
                    </p>


                    <select name="collection_id"
                        class="
                            mt-4
                            w-full
                            rounded-xl
                            border-amber-200
                            bg-white
                            text-sm
                        ">

                        <option value="">
                            Seleccionar...
                        </option>


                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}">
                                {{ $collection->name }}
                            </option>
                        @endforeach

                    </select>


                    <button type="submit"
                        class="
                            mt-3
                            w-full
                            rounded-xl
                            bg-amber-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Quitar Colección
                    </button>

                </form>


                {{-- REPLACE --}}
                <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                    class="
                        rounded-2xl
                        border
                        border-indigo-200
                        bg-indigo-50/40
                        p-5
                    ">

                    @csrf

                    <input type="hidden" name="operation" value="set_collections">


                    <template
                        x-for="
                            id
                            in selectedIds
                        "
                        :key="`set-col-${id}`">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>


                    <p
                        class="
                            font-black
                            text-indigo-800
                        ">
                        ⇄ Reemplazar
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-indigo-600
                        ">
                        Las Entidades quedarán exactamente en estas Colecciones.
                    </p>


                    <select name="collection_ids[]" multiple
                        class="
                            mt-4
                            min-h-32
                            w-full
                            rounded-xl
                            border-indigo-200
                            bg-white
                            text-sm
                        ">

                        @foreach ($collections as $collection)
                            <option value="{{ $collection->id }}">
                                {{ $collection->name }}
                            </option>
                        @endforeach

                    </select>


                    <button type="submit"
                        class="
                            mt-3
                            w-full
                            rounded-xl
                            bg-indigo-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Establecer Colecciones
                    </button>

                </form>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ESTRUCTURA --}}
        {{-- ===================================================== --}}

        <section
            x-show="
                activeTab === 'structure'
                &&
                selectedCount > 0
            "
            x-cloak class="
                mt-6
                space-y-5
            ">

            {{-- PRESENTACIÓN --}}
            <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    shadow-sm
                ">

                @csrf

                <input type="hidden" name="operation" value="attribute_presentation">


                <template
                    x-for="
                        id
                        in selectedIds
                    "
                    :key="`presentation-${id}`">
                    <input type="hidden" name="entity_ids[]" :value="id">
                </template>


                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        text-fuchsia-500
                    ">
                    Presentación
                </p>


                <h2
                    class="
                        mt-1
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Cómo se muestra una característica
                </h2>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Estos cambios afectan únicamente las asignaciones
                    de las Entidades seleccionadas, no la definición
                    global del Atributo.
                </p>


                <div
                    class="
                        mt-6
                        grid
                        gap-4
                        md:grid-cols-2
                        xl:grid-cols-3
                    ">

                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Atributo
                        </label>


                        <select name="attribute_id"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Seleccionar...
                            </option>


                            @foreach ($attributes as $attribute)
                                <option value="{{ $attribute->id }}">
                                    {{ $attribute->name }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Etiqueta personalizada
                        </label>


                        <input type="text" name="custom_label" placeholder="Ej. Poder"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Visible
                        </label>


                        <select name="presentation_visibility"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                No modificar
                            </option>

                            <option value="1">
                                Visible
                            </option>

                            <option value="0">
                                Oculto
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Destacado
                        </label>


                        <select name="presentation_featured"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                No modificar
                            </option>

                            <option value="1">
                                Destacado
                            </option>

                            <option value="0">
                                Normal
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Posición
                        </label>


                        <input type="number" name="presentation_sort_order" min="0" step="10"
                            placeholder="No modificar"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Nota
                        </label>


                        <input type="text" name="notes" placeholder="Nota opcional..."
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                    </div>

                </div>


                <button type="submit"
                    class="
                        mt-5
                        rounded-xl
                        bg-fuchsia-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Aplicar presentación
                </button>

            </form>


            {{-- ORDEN --}}
            <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    shadow-sm
                ">

                @csrf

                <input type="hidden" name="operation" value="reorder_attributes">


                <template
                    x-for="
                        id
                        in selectedIds
                    "
                    :key="`order-entity-${id}`">
                    <input type="hidden" name="entity_ids[]" :value="id">
                </template>


                <template
                    x-for="
                        id
                        in orderAttributeIds
                    "
                    :key="`order-attribute-${id}`">
                    <input type="hidden" name="attribute_order[]" :value="id">
                </template>


                <div
                    class="
                        flex
                        flex-col
                        gap-3
                        sm:flex-row
                        sm:items-start
                        sm:justify-between
                    ">

                    <div>

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                text-indigo-500
                            ">
                            Orden
                        </p>


                        <h2
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Ordenar características
                        </h2>

                    </div>


                    <button type="button"
                        @click="
                            loadOrderAttributes()
                        "
                        class="
                            rounded-xl
                            bg-indigo-50
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-indigo-700
                        ">
                        Cargar Atributos utilizados
                    </button>

                </div>


                <div x-show="
                        orderAttributeIds.length > 0
                    "
                    class="
                        mt-5
                        max-w-2xl
                        space-y-2
                    ">

                    <template
                        x-for="
                            (attributeId, index)
                            in orderAttributeIds
                        "
                        :key="attributeId">

                        <div
                            class="
                                flex
                                items-center
                                gap-3
                                rounded-xl
                                border
                                border-slate-200
                                bg-slate-50
                                p-3
                            ">

                            <span
                                class="
                                    w-8
                                    text-center
                                    font-mono
                                    text-[9px]
                                    text-slate-400
                                "
                                x-text="
                                    index + 1
                                "></span>


                            <div
                                class="
                                    min-w-0
                                    flex-1
                                ">

                                <p class="
                                        truncate
                                        text-xs
                                        font-black
                                        text-slate-700
                                    "
                                    x-text="
                                        attributeName(
                                            attributeId
                                        )
                                    ">
                                </p>

                            </div>


                            <button type="button"
                                @click="
                                    moveOrderUp(
                                        index
                                    )
                                "
                                class="
                                    rounded-lg
                                    bg-white
                                    px-2.5
                                    py-1.5
                                    text-xs
                                    font-black
                                    text-slate-500
                                ">
                                ↑
                            </button>


                            <button type="button"
                                @click="
                                    moveOrderDown(
                                        index
                                    )
                                "
                                class="
                                    rounded-lg
                                    bg-white
                                    px-2.5
                                    py-1.5
                                    text-xs
                                    font-black
                                    text-slate-500
                                ">
                                ↓
                            </button>

                        </div>

                    </template>

                </div>


                <button x-show="
                        orderAttributeIds.length > 0
                    "
                    type="submit"
                    class="
                        mt-5
                        rounded-xl
                        bg-indigo-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Aplicar orden
                </button>

            </form>

        </section>


        {{-- ===================================================== --}}
        {{-- PUBLICACIÓN --}}
        {{-- ===================================================== --}}

        <section
            x-show="
                activeTab === 'publication'
                &&
                selectedCount > 0
            "
            x-cloak
            class="
                mt-6
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    text-cyan-500
                ">
                Publicación
            </p>


            <h2
                class="
                    mt-1
                    text-xl
                    font-black
                    text-slate-900
                ">
                Estado, visibilidad y Comunidad
            </h2>


            <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                class="
                    mt-6
                ">

                @csrf

                <input type="hidden" name="operation" value="set_publication">


                <template
                    x-for="
                        id
                        in selectedIds
                    "
                    :key="`publication-${id}`">
                    <input type="hidden" name="entity_ids[]" :value="id">
                </template>


                <div
                    class="
                        grid
                        gap-4
                        md:grid-cols-3
                    ">

                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Estado
                        </label>


                        <select name="publication_status"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                No modificar
                            </option>

                            <option value="ACTIVE">
                                Activo
                            </option>

                            <option value="INACTIVE">
                                Inactivo
                            </option>

                            <option value="ARCHIVED">
                                Archivado
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Visibilidad
                        </label>


                        <select name="publication_visibility"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                No modificar
                            </option>

                            <option value="PUBLIC">
                                Público
                            </option>

                            <option value="PRIVATE">
                                Privado
                            </option>

                            <option value="UNLISTED">
                                No listado
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                mb-1
                                block
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Permitir copiar
                        </label>


                        <select name="publication_allow_cloning"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                No modificar
                            </option>

                            <option value="1">
                                Sí
                            </option>

                            <option value="0">
                                No
                            </option>

                        </select>

                    </div>

                </div>


                <div
                    class="
                        mt-5
                        rounded-2xl
                        bg-cyan-50
                        p-4
                        text-xs
                        leading-6
                        text-cyan-700
                    ">
                    Una Entidad aparece publicada cuando está
                    <strong>Activa + Pública</strong>.
                    El backend actualizará automáticamente
                    <code>published_at</code>.
                </div>


                <button type="submit"
                    class="
                        mt-5
                        rounded-xl
                        bg-cyan-600
                        px-6
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Aplicar publicación
                </button>

            </form>

        </section>


        {{-- ===================================================== --}}
        {{-- PELIGRO --}}
        {{-- ===================================================== --}}

        <section
            x-show="
                activeTab === 'danger'
                &&
                selectedCount > 0
            "
            x-cloak
            class="
                mt-6
                rounded-3xl
                border
                border-red-200
                bg-white
                p-6
                shadow-sm
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    text-red-500
                ">
                Zona peligrosa
            </p>


            <h2
                class="
                    mt-1
                    text-xl
                    font-black
                    text-slate-900
                ">
                Archivar o eliminar
            </h2>


            <div
                class="
                    mt-6
                    grid
                    gap-5
                    lg:grid-cols-2
                ">

                {{-- ARCHIVE --}}
                <form method="POST" action="{{ route('entities.bulk-edit.update') }}" data-omni-confirm
                    data-confirm-variant="warning" data-confirm-icon="!" data-confirm-title="Archivar Entidades"
                    data-confirm-message="
        Las Entidades seleccionadas pasarán
        al estado Archivado.
    "
                    :data-confirm-subject="`${selectedCount} ${
                                                    selectedCount === 1
                                                        ? 'Entidad seleccionada'
                                                        : 'Entidades seleccionadas'
                                                }`"
                    data-confirm-detail="
        Las Entidades no serán eliminadas.
        Podrás conservar sus datos y administrarlas posteriormente.
    "
                    data-confirm-action="Archivar seleccionadas"
                    class="
        rounded-2xl
        border
        border-amber-200
        bg-amber-50
        p-5
    ">

                    @csrf

                    <input type="hidden" name="operation" value="archive">


                    <template
                        x-for="
                            id
                            in selectedIds
                        "
                        :key="`archive-${id}`">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>


                    <p
                        class="
                            font-black
                            text-amber-800
                        ">
                        Archivar
                    </p>


                    <p
                        class="
                            mt-2
                            text-sm
                            leading-6
                            text-amber-700
                        ">
                        Recomendado si quieres conservar las Entidades
                        pero retirarlas del uso normal.
                    </p>


                    <button type="submit"
                        class="
                            mt-4
                            rounded-xl
                            bg-amber-600
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-white
                        ">
                        Archivar seleccionadas
                    </button>

                </form>


                {{-- DELETE --}}
                <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                    data-confirm-title="Eliminar Entidades"
                    data-confirm-message="
        Vas a eliminar todas las Entidades
        actualmente seleccionadas.
    "
                    :data-confirm-subject="`${selectedCount} ${
                                selectedCount === 1
                                    ? 'Entidad seleccionada'
                                    : 'Entidades seleccionadas'
                            }`"
                    data-confirm-detail="
        OmniMerge utilizará eliminación lógica.
        Esta operación afectará a todas las Entidades seleccionadas.
    "
                    data-confirm-action="Sí, eliminar seleccionadas"
                    class="
                        rounded-2xl
                        border
                        border-red-200
                        bg-red-50
                        p-5
                    ">

                    @csrf

                    <input type="hidden" name="operation" value="delete">


                    <template
                        x-for="
                            id
                            in selectedIds
                        "
                        :key="`delete-${id}`">
                        <input type="hidden" name="entity_ids[]" :value="id">
                    </template>


                    <p
                        class="
                            font-black
                            text-red-800
                        ">
                        Eliminar
                    </p>


                    <p
                        class="
                            mt-2
                            text-sm
                            leading-6
                            text-red-700
                        ">
                        Las Entidades utilizan eliminación lógica.
                        La imagen no se destruye.
                    </p>


                    <button type="submit"
                        class="
                            mt-4
                            rounded-xl
                            bg-red-600
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-white
                        ">
                        Eliminar seleccionadas
                    </button>

                </form>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- JAVASCRIPT / ALPINE --}}
        {{-- ===================================================== --}}

        <script>
            function bulkEditManager(
                config
            ) {

                return {

                    /*
                    |--------------------------------------------------------------------------
                    | Navegación
                    |--------------------------------------------------------------------------
                    */

                    activeTab: 'selection',

                    tabs: [

                        {
                            id: 'selection',

                            icon: '☑',

                            label: 'Selección'
                        },

                        {
                            id: 'quick',

                            icon: '⚡',

                            label: 'Rápida'
                        },

                        {
                            id: 'matrix',

                            icon: '▦',

                            label: 'Matriz'
                        },

                        {
                            id: 'attributes',

                            icon: '◆',

                            label: 'Características'
                        },

                        {
                            id: 'collections',

                            icon: '▤',

                            label: 'Colecciones'
                        },

                        {
                            id: 'structure',

                            icon: '☷',

                            label: 'Estructura'
                        },

                        {
                            id: 'publication',

                            icon: '◉',

                            label: 'Publicación'
                        },

                        {
                            id: 'danger',

                            icon: '⚠',

                            label: 'Peligro'
                        },
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Recursos
                    |--------------------------------------------------------------------------
                    */

                    entities: config.entities ?? [],

                    attributes: config.attributes ?? [],

                    entityTypes: config.entityTypes ?? [],

                    collections: config.collections ?? [],


                    /*
                    |--------------------------------------------------------------------------
                    | Selección
                    |--------------------------------------------------------------------------
                    */

                    selectedIds: [],


                    /*
                    |--------------------------------------------------------------------------
                    | Agrupación
                    |--------------------------------------------------------------------------
                    */

                    groupLevel1: localStorage.getItem(
                            'omnimerge.bulk-edit.group1'
                        ) ??
                        '',

                    groupLevel2: localStorage.getItem(
                            'omnimerge.bulk-edit.group2'
                        ) ??
                        '',


                    /*
                    |--------------------------------------------------------------------------
                    | Filter rules
                    |--------------------------------------------------------------------------
                    */

                    filterRules: [],


                    /*
                    |--------------------------------------------------------------------------
                    | Matrix
                    |--------------------------------------------------------------------------
                    */

                    matrixAttributeIds: [],

                    matrixSubmitting: false,


                    /*
                    |--------------------------------------------------------------------------
                    | Attribute operation
                    |--------------------------------------------------------------------------
                    */

                    selectedAttributeId: '',

                    attributeOperation: 'set_attribute',

                    attributeValue: '',


                    /*
                    |--------------------------------------------------------------------------
                    | Structure
                    |--------------------------------------------------------------------------
                    */

                    orderAttributeIds: [],


                    /*
                    |--------------------------------------------------------------------------
                    | INIT
                    |--------------------------------------------------------------------------
                    */

                    init() {

                        /*
                        |--------------------------------------------------------------------------
                        | Preparar Entidades editables
                        |--------------------------------------------------------------------------
                        */

                        this.entities =
                            this.entities
                            .map(
                                entity => {

                                    entity.edit = {

                                        name: entity.name,

                                        description: entity.description,

                                        entity_type_id: String(
                                            entity.entity_type_id ??
                                            ''
                                        ),

                                        status: entity.status,

                                        visibility: entity.visibility,

                                        allow_cloning:
                                            !!entity.allow_cloning,

                                        attribute_values: JSON.parse(
                                            JSON.stringify(
                                                entity.attribute_values ?? {}
                                            )
                                        ),
                                    };


                                    /*
                                     * Asegurar valor para todos
                                     * los Atributos.
                                     */
                                    this.attributes
                                        .forEach(
                                            attribute => {

                                                const id =
                                                    String(
                                                        attribute.id
                                                    );


                                                if (
                                                    entity
                                                    .edit
                                                    .attribute_values[
                                                        id
                                                    ] ===
                                                    undefined
                                                ) {

                                                    entity
                                                        .edit
                                                        .attribute_values[
                                                            id
                                                        ] =
                                                        attribute
                                                        .allows_multiple

                                                        ?
                                                        []

                                                        :
                                                        '';
                                                }
                                            }
                                        );


                                    return entity;
                                }
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Reglas cargadas desde GET
                        |--------------------------------------------------------------------------
                        */

                        const oldRules =
                            config.initialRules ?? [];


                        if (
                            oldRules.length >
                            0
                        ) {

                            this.filterRules =
                                oldRules.map(
                                    (
                                        rule,
                                        index
                                    ) => ({

                                        key: `rule-${Date.now()}-${index}`,

                                        logic: rule.logic ??
                                            'AND',

                                        attribute_id: rule.attribute_id ?
                                            String(
                                                rule.attribute_id
                                            ) : '',

                                        operator: rule.operator ??
                                            'eq',

                                        value: rule.value ??
                                            '',

                                        value2: rule.value2 ??
                                            '',
                                    })
                                );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Persistir agrupación
                        |--------------------------------------------------------------------------
                        */

                        this.$watch(
                            'groupLevel1',
                            value => {

                                localStorage.setItem(
                                    'omnimerge.bulk-edit.group1',
                                    value
                                );
                            }
                        );


                        this.$watch(
                            'groupLevel2',
                            value => {

                                localStorage.setItem(
                                    'omnimerge.bulk-edit.group2',
                                    value
                                );
                            }
                        );
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | GETTERS
                    |--------------------------------------------------------------------------
                    */

                    get selectedCount() {

                        return this
                            .selectedIds
                            .length;
                    },


                    get selectedEntities() {

                        return this.entities
                            .filter(
                                entity =>
                                this.isSelected(
                                    entity.id
                                )
                            );
                    },


                    get currentAttribute() {

                        if (
                            !this
                            .selectedAttributeId
                        ) {
                            return null;
                        }


                        return this.attributes
                            .find(
                                attribute =>
                                String(
                                    attribute.id
                                ) ===
                                String(
                                    this.selectedAttributeId
                                )
                            ) ??
                            null;
                    },


                    get matrixAttributes() {

                        return this.attributes
                            .filter(
                                attribute =>
                                this
                                .matrixAttributeIds
                                .includes(
                                    String(
                                        attribute.id
                                    )
                                )
                            );
                    },


                    get groupedLevel1() {

                        return this.groupEntities(
                            this.entities,
                            this.groupLevel1
                        );
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | Selection
                    |--------------------------------------------------------------------------
                    */

                    isSelected(
                        id
                    ) {

                        return this
                            .selectedIds
                            .includes(
                                String(
                                    id
                                )
                            );
                    },


                    toggleSelection(
                        id
                    ) {

                        id =
                            String(
                                id
                            );


                        if (
                            this.isSelected(
                                id
                            )
                        ) {

                            this.selectedIds =
                                this.selectedIds
                                .filter(
                                    value =>
                                    value !== id
                                );


                            return;
                        }


                        this.selectedIds
                            .push(
                                id
                            );
                    },


                    selectAll() {

                        this.selectedIds =
                            this.entities
                            .map(
                                entity =>
                                String(
                                    entity.id
                                )
                            );
                    },


                    clearSelection() {

                        this.selectedIds = [];
                    },


                    selectEntities(
                        entities
                    ) {

                        const ids =
                            entities.map(
                                entity =>
                                String(
                                    entity.id
                                )
                            );


                        this.selectedIds = [
                            ...new Set([
                                ...this.selectedIds,
                                ...ids
                            ])
                        ];
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | GROUPING
                    |--------------------------------------------------------------------------
                    */

                    groupLabel(
                        entity,
                        key
                    ) {

                        if (!key) {

                            return 'Todas';
                        }


                        if (
                            key === 'type'
                        ) {

                            return entity
                                .entity_type_name ??
                                'Sin tipo';
                        }


                        if (
                            key === 'status'
                        ) {

                            return entity
                                .status_label ??
                                entity.status;
                        }


                        if (
                            key === 'visibility'
                        ) {

                            return entity
                                .visibility_label ??
                                entity.visibility;
                        }


                        if (
                            key === 'image'
                        ) {

                            return entity.image_url ?
                                'Con imagen' :
                                'Sin imagen';
                        }


                        if (
                            key === 'collection'
                        ) {

                            return entity
                                .collections[
                                    0
                                ]
                                ?.name ??
                                'Sin Colección';
                        }


                        if (
                            key.startsWith(
                                'attribute:'
                            )
                        ) {

                            const id =
                                key.split(
                                    ':'
                                )[
                                    1
                                ];


                            return entity
                                .attribute_displays[
                                    id
                                ] ||
                                'Sin valor';
                        }


                        return 'Otros';
                    },


                    groupEntities(
                        entities,
                        key
                    ) {

                        const groups = {};


                        entities.forEach(
                            entity => {

                                const label =
                                    this.groupLabel(
                                        entity,
                                        key
                                    );


                                if (
                                    !groups[
                                        label
                                    ]
                                ) {

                                    groups[
                                        label
                                    ] = [];
                                }


                                groups[
                                        label
                                    ]
                                    .push(
                                        entity
                                    );
                            }
                        );


                        return groups;
                    },


                    groupSecond(
                        entities
                    ) {

                        return this.groupEntities(
                            entities,
                            this.groupLevel2
                        );
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | FILTER RULES
                    |--------------------------------------------------------------------------
                    */

                    addFilterRule() {

                        if (
                            this.filterRules.length >=
                            3
                        ) {
                            return;
                        }


                        this.filterRules.push({

                            key: `rule-${Date.now()}-${Math.random()}`,

                            logic: 'AND',

                            attribute_id: '',

                            operator: 'eq',

                            value: '',

                            value2: '',
                        });
                    },


                    removeFilterRule(
                        index
                    ) {

                        this.filterRules.splice(
                            index,
                            1
                        );
                    },


                    filterAttribute(
                        rule
                    ) {

                        return this.attributes
                            .find(
                                attribute =>
                                String(
                                    attribute.id
                                ) ===
                                String(
                                    rule.attribute_id
                                )
                            ) ??
                            null;
                    },


                    normalizeFilterRule(
                        rule
                    ) {

                        rule.value =
                            '';

                        rule.value2 =
                            '';


                        const attribute =
                            this.filterAttribute(
                                rule
                            );


                        if (!attribute) {
                            return;
                        }


                        if (
                            attribute.data_type ===
                            'OPTION'
                        ) {

                            rule.operator =
                                'eq';
                        }
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | ATTRIBUTE OP
                    |--------------------------------------------------------------------------
                    */

                    resetAttributeValue() {

                        if (
                            this.currentAttribute &&
                            this
                            .currentAttribute
                            .allows_multiple
                        ) {

                            this.attributeValue = [];

                        } else {

                            this.attributeValue =
                                '';
                        }
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | ORDER
                    |--------------------------------------------------------------------------
                    */

                    loadOrderAttributes() {

                        const used =
                            new Set();


                        this.selectedEntities
                            .forEach(
                                entity => {

                                    (
                                        entity.attribute_ids ?? []
                                    )
                                    .forEach(
                                        id =>
                                        used.add(
                                            String(
                                                id
                                            )
                                        )
                                    );
                                }
                            );


                        this.orderAttributeIds =
                            this.attributes
                            .filter(
                                attribute =>
                                used.has(
                                    String(
                                        attribute.id
                                    )
                                )
                            )
                            .sort(
                                (
                                    a,
                                    b
                                ) =>
                                Number(
                                    a.sort_order ??
                                    0
                                ) -
                                Number(
                                    b.sort_order ??
                                    0
                                )
                            )
                            .map(
                                attribute =>
                                String(
                                    attribute.id
                                )
                            );
                    },


                    attributeName(
                        id
                    ) {

                        return this.attributes
                            .find(
                                attribute =>
                                String(
                                    attribute.id
                                ) ===
                                String(
                                    id
                                )
                            )
                            ?.name ??
                            'Atributo';
                    },


                    moveOrderUp(
                        index
                    ) {

                        if (
                            index <= 0
                        ) {
                            return;
                        }


                        const copy = [
                            ...this.orderAttributeIds
                        ];


                        [
                            copy[
                                index - 1
                            ],
                            copy[
                                index
                            ]
                        ] = [
                            copy[
                                index
                            ],
                            copy[
                                index - 1
                            ]
                        ];


                        this.orderAttributeIds =
                            copy;
                    },


                    moveOrderDown(
                        index
                    ) {

                        if (
                            index >=
                            this.orderAttributeIds.length -
                            1
                        ) {
                            return;
                        }


                        const copy = [
                            ...this.orderAttributeIds
                        ];


                        [
                            copy[
                                index + 1
                            ],
                            copy[
                                index
                            ]
                        ] = [
                            copy[
                                index
                            ],
                            copy[
                                index + 1
                            ]
                        ];


                        this.orderAttributeIds =
                            copy;
                    },


                    /*
                    |--------------------------------------------------------------------------
                    | MATRIX PAYLOAD
                    |--------------------------------------------------------------------------
                    */

                    matrixPayload() {

                        const result = {};


                        this.selectedEntities
                            .forEach(
                                entity => {

                                    const attributes = {};


                                    this.matrixAttributeIds
                                        .forEach(
                                            attributeId => {

                                                attributes[
                                                        attributeId
                                                    ] =
                                                    entity
                                                    .edit
                                                    .attribute_values[
                                                        attributeId
                                                    ];
                                            }
                                        );


                                    result[
                                        entity.id
                                    ] = {

                                        properties: {

                                            name: entity
                                                .edit
                                                .name,

                                            description: entity
                                                .edit
                                                .description,

                                            entity_type_id: entity
                                                .edit
                                                .entity_type_id,

                                            status: entity
                                                .edit
                                                .status,

                                            visibility: entity
                                                .edit
                                                .visibility,

                                            allow_cloning: entity
                                                .edit
                                                .allow_cloning,
                                        },

                                        attributes: attributes,
                                    };
                                }
                            );


                        return result;
                    }
                };
            }
        </script>

    </div>

</x-app-layout>

<x-app-layout>

    <x-slot name="header">
        Creación masiva
    </x-slot>


    @include('entities.partials.section-navigation')


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    @php

        /*
        |--------------------------------------------------------------------------
        | Atributos para Alpine
        |--------------------------------------------------------------------------
        */

        $attributePayload = $attributes
            ->map(
                fn($attribute) => [
                    'id' => (string) $attribute->id,

                    'code' => $attribute->code,

                    'name' => $attribute->name,

                    'data_type' => $attribute->data_type,

                    'data_type_label' => $attribute->data_type_label,

                    'icon' => $attribute->icon ?: $attribute->data_type_icon,

                    'color' => $attribute->color ?: '#6366F1',

                    'image_url' => $attribute->image_url,

                    'allows_multiple' => (bool) $attribute->allows_multiple,

                    'is_required' => (bool) $attribute->is_required,

                    'min_numeric_value' => $attribute->min_numeric_value,

                    'max_numeric_value' => $attribute->max_numeric_value,

                    'groups' => $attribute->groups->pluck('name')->values()->all(),

                    'options' => $attribute->options
                        ->map(
                            fn($option) => [
                                'id' => (string) $option->id,

                                'code' => $option->code,

                                'name' => $option->name,

                                'image_url' => $option->image_url,

                                'icon' => $option->icon ?: '◆',

                                'color' => $option->color ?: '#7C3AED',
                            ],
                        )
                        ->values()
                        ->all(),
                ],
            )
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Tipos
        |--------------------------------------------------------------------------
        */

        $typePayload = $entityTypes
            ->map(
                fn($type) => [
                    'id' => (string) $type->id,

                    'name' => $type->name,

                    'code' => $type->code,

                    'image_url' => $type->image_url,

                    'icon' => $type->icon ?: '◇',

                    'color' => $type->color ?: '#6366F1',
                ],
            )
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Colecciones
        |--------------------------------------------------------------------------
        */

        $collectionPayload = $collections
            ->map(
                fn($collection) => [
                    'id' => (string) $collection->id,

                    'name' => $collection->name,

                    'image_url' => $collection->image_url,

                    'icon' => $collection->icon ?: '▤',

                    'entities_count' => $collection->entities_count,
                ],
            )
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | OLD
        |--------------------------------------------------------------------------
        */

        $oldPayload = [
            'batch_name' => old('batch_name', ''),

            'entity_type_id' => old('entity_type_id', ''),

            'status' => old('status', 'ACTIVE'),

            'visibility' => old('visibility', 'PUBLIC'),

            'allow_cloning' => old('allow_cloning', true),

            'duplicate_strategy' => old('duplicate_strategy', 'create'),

            'collection_ids' => old('collection_ids', []),

            'selected_attribute_ids' => old('selected_attribute_ids', []),

            'common_attribute_ids' => old('common_attribute_ids', []),

            'common_attributes' => old('common_attributes', []),

            'rows' => old('rows'),
        ];
    @endphp


    <div x-data="bulkEntityBuilder({
    
        attributes: @js($attributePayload),
    
        entityTypes: @js($typePayload),
    
        collections: @js($collectionPayload),
    
        template: @js($templatePayload),
    
        old: @js($oldPayload),
    
        existingNames: @js($existingEntityNames),
    
        createUrl: @js(route('entities.bulk.create'))
    })" x-init="init()">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
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
                    from-indigo-600
                    via-violet-600
                    to-fuchsia-600
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
                                rounded-full
                                bg-white/15
                                px-3
                                py-1
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                backdrop-blur
                            ">
                            ✦ Gestión rápida
                        </span>


                        <h1
                            class="
                                mt-4
                                text-3xl
                                font-black
                                tracking-tight
                                sm:text-4xl
                            ">
                            Creación masiva
                        </h1>


                        <p
                            class="
                                mt-3
                                max-w-3xl
                                text-sm
                                leading-6
                                text-white/75
                            ">
                            Configura una vez, crea muchas.
                            Comparte Tipo, Colecciones y características,
                            mientras modificas los valores que cambian
                            directamente desde una tabla.
                        </p>

                    </div>


                    <a href="{{ route('entities.index') }}"
                        class="
                            shrink-0
                            rounded-xl
                            bg-white/15
                            px-4
                            py-2.5
                            text-sm
                            font-bold
                            backdrop-blur
                            transition
                            hover:bg-white/25
                        ">
                        ← Volver a Entidades
                    </a>

                </div>


                {{-- RESUMEN --}}
                <div
                    class="
                        mt-7
                        grid
                        grid-cols-2
                        gap-2
                        md:grid-cols-4
                    ">

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
                                text-white/60
                            ">
                            Listas
                        </p>

                        <p class="
                                mt-1
                                text-2xl
                                font-black
                            "
                            x-text="
                                readyCount
                            "></p>
                    </div>


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
                                text-white/60
                            ">
                            Atributos
                        </p>

                        <p class="
                                mt-1
                                text-2xl
                                font-black
                            "
                            x-text="
                                selectedAttributeIds.length
                            ">
                        </p>
                    </div>


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
                                text-white/60
                            ">
                            Sin imagen
                        </p>

                        <p class="
                                mt-1
                                text-2xl
                                font-black
                            "
                            x-text="
                                rowsWithoutImage
                            "></p>
                    </div>


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
                                text-white/60
                            ">
                            Seleccionadas
                        </p>

                        <p class="
                                mt-1
                                text-2xl
                                font-black
                            "
                            x-text="
                                selectedRowsCount
                            ">
                        </p>
                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ERRORES --}}
        {{-- ===================================================== --}}

        @if ($errors->any())

            <section
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
                    No se pudo crear el lote.
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

            </section>

        @endif


        {{-- ===================================================== --}}
        {{-- FORM --}}
        {{-- ===================================================== --}}

        <form method="POST" action="{{ route('entities.bulk.store') }}" enctype="multipart/form-data"
            class="
                mt-6
                space-y-6
            "
            @submit="
                prepareSubmit(
                    $event
                )
            ">

            @csrf


            {{-- ================================================= --}}
            {{-- CONFIGURACIÓN GENERAL --}}
            {{-- ================================================= --}}

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
                            Configuración compartida
                        </h2>

                        <p
                            class="
                                mt-2
                                text-sm
                                text-slate-500
                            ">
                            Estos valores se utilizarán como base
                            para todas las Entidades del lote.
                        </p>

                    </div>


                    {{-- PLANTILLA --}}
                    <div
                        class="
                            w-full
                            lg:max-w-sm
                        ">

                        <label
                            class="
                                mb-1.5
                                block
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-400
                            ">
                            Partir de una Entidad
                        </label>


                        <select
                            @change="
                                loadTemplate(
                                    $event.target.value
                                )
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Sin plantilla
                            </option>


                            @foreach ($templateEntities as $templateEntity)
                                <option value="{{ $templateEntity->id }}" @selected($templateEntityId === $templateEntity->id)>
                                    {{ $templateEntity->name }}

                                    @if ($templateEntity->entityType)
                                        ·
                                        {{ $templateEntity->entityType->name }}
                                    @endif
                                </option>
                            @endforeach

                        </select>

                    </div>

                </div>


                <div
                    class="
                        mt-6
                        grid
                        gap-4
                        sm:grid-cols-2
                        xl:grid-cols-4
                    ">

                    {{-- NOMBRE LOTE --}}
                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-xs
                                font-bold
                                text-slate-600
                            ">
                            Nombre del lote
                        </label>


                        <input type="text" name="batch_name"
                            x-model="
                                batchName
                            "
                            placeholder="Ej. Personajes Naruto"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                    </div>


                    {{-- TIPO --}}
                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-xs
                                font-bold
                                text-slate-600
                            ">
                            Tipo común
                        </label>


                        <select name="entity_type_id"
                            x-model="
                                entityTypeId
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                            <option value="">
                                Sin tipo común
                            </option>


                            <template
                                x-for="
                                    type
                                    in entityTypes
                                "
                                :key="type.id">

                                <option
                                    :value="type.id"
                                    x-text="
                                        type.name
                                    ">
                                </option>

                            </template>

                        </select>

                    </div>


                    {{-- VISIBILIDAD --}}
                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-xs
                                font-bold
                                text-slate-600
                            ">
                            Visibilidad
                        </label>


                        <select name="visibility"
                            x-model="
                                visibility
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
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

                    </div>


                    {{-- ESTADO --}}
                    <div>

                        <label
                            class="
                                mb-1.5
                                block
                                text-xs
                                font-bold
                                text-slate-600
                            ">
                            Estado
                        </label>


                        <select name="status"
                            x-model="
                                status
                            "
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
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

                    </div>

                </div>


                <div
                    class="
                        mt-5
                        grid
                        gap-4
                        lg:grid-cols-2
                    ">

                    {{-- DUPLICADOS --}}
                    <div
                        class="
                            rounded-2xl
                            bg-slate-50
                            p-4
                        ">

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Si ya existe una Entidad con ese nombre
                        </label>


                        <select name="duplicate_strategy"
                            x-model="
                                duplicateStrategy
                            "
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-sm
                            ">

                            <option value="create">
                                Crear igualmente
                            </option>

                            <option value="skip">
                                Omitir la fila
                            </option>

                        </select>

                    </div>


                    {{-- CLONACIÓN --}}
                    <label
                        class="
                            flex
                            cursor-pointer
                            items-start
                            gap-3
                            rounded-2xl
                            bg-slate-50
                            p-4
                        ">

                        <input type="hidden" name="allow_cloning" value="0">


                        <input type="checkbox" name="allow_cloning" value="1"
                            x-model="
                                allowCloning
                            "
                            class="
                                mt-1
                                rounded
                                border-slate-300
                                text-indigo-600
                            ">


                        <span>

                            <span
                                class="
                                    block
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                Permitir copiar
                            </span>

                            <span
                                class="
                                    mt-1
                                    block
                                    text-[10px]
                                    leading-5
                                    text-slate-400
                                ">
                                Cuando sean públicas,
                                otros usuarios podrán copiarlas
                                desde Comunidad.
                            </span>

                        </span>

                    </label>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- COLECCIONES --}}
            {{-- ================================================= --}}

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


                <h2
                    class="
                        mt-1
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    Colecciones comunes
                </h2>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Todas las Entidades del lote
                    serán añadidas a las Colecciones seleccionadas.
                </p>


                @if ($collections->isEmpty())
                    <p
                        class="
                            mt-4
                            rounded-xl
                            bg-slate-50
                            p-4
                            text-sm
                            text-slate-400
                        ">
                        Todavía no tienes Colecciones.
                    </p>
                @else
                    <div
                        class="
                            mt-5
                            grid
                            gap-3
                            sm:grid-cols-2
                            lg:grid-cols-3
                            xl:grid-cols-4
                        ">

                        <template
                            x-for="
                                collection
                                in collections
                            "
                            :key="collection.id">

                            <label
                                class="
                                    flex
                                    cursor-pointer
                                    min-w-0
                                    items-center
                                    gap-3
                                    rounded-2xl
                                    border
                                    p-3
                                    transition
                                "
                                :class="collectionIds.includes(
                                        collection.id
                                    )
                                
                                    ?
                                    'border-cyan-300 bg-cyan-50'
                                
                                    :
                                    'border-slate-200 bg-white hover:border-slate-300'">

                                <input type="checkbox" name="collection_ids[]"
                                    :value="collection.id"
                                    x-model="
                                        collectionIds
                                    "
                                    class="
                                        rounded
                                        border-slate-300
                                        text-cyan-600
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

                                    <template
                                        x-if="
                                            collection.image_url
                                        ">

                                        <img :src="collection.image_url"
                                            class="
                                                h-full
                                                w-full
                                                object-cover
                                            ">

                                    </template>


                                    <template
                                        x-if="
                                            ! collection.image_url
                                        ">

                                        <div class="
                                                flex
                                                h-full
                                                items-center
                                                justify-center
                                                text-cyan-400
                                            "
                                            x-text="
                                                collection.icon
                                            ">
                                        </div>

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
                                            collection.name
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
                                                collection.entities_count
                                            "></span>

                                        entidades actuales
                                    </p>

                                </div>

                            </label>

                        </template>

                    </div>
                @endif

            </section>


            {{-- ================================================= --}}
            {{-- ATRIBUTOS --}}
            {{-- ================================================= --}}

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
                        gap-4
                        lg:flex-row
                        lg:items-end
                        lg:justify-between
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
                            Paso 2
                        </p>


                        <h2
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Características del lote
                        </h2>


                        <p
                            class="
                                mt-2
                                text-sm
                                text-slate-500
                            ">
                            Decide qué Atributos utilizarán estas Entidades
                            y cuáles tendrán un mismo valor para todas.
                        </p>

                    </div>


                    <div
                        class="
                            w-full
                            lg:max-w-sm
                        ">

                        <input type="search"
                            x-model="
                                attributeSearch
                            "
                            placeholder="Buscar Atributo..."
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">

                    </div>

                </div>


                {{-- TODOS LOS ATRIBUTOS --}}
                <div
                    class="
                        mt-5
                        grid
                        gap-2
                        sm:grid-cols-2
                        md:grid-cols-3
                        xl:grid-cols-4
                    ">

                    <template
                        x-for="
                            attribute
                            in filteredAttributes
                        "
                        :key="attribute.id">

                        <button type="button"
                            @click="
                                toggleAttribute(
                                    attribute
                                )
                            "
                            class="
                                flex
                                min-w-0
                                items-center
                                gap-3
                                rounded-2xl
                                border
                                p-3
                                text-left
                                transition
                            "
                            :class="isAttributeSelected(
                                    attribute.id
                                )
                            
                                ?
                                'border-violet-300 bg-violet-50 ring-2 ring-violet-100'
                            
                                :
                                'border-slate-200 bg-white hover:border-violet-200'">

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
                                        attribute.image_url
                                    ">

                                    <img :src="attribute.image_url"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        ">

                                </template>


                                <template
                                    x-if="
                                        ! attribute.image_url
                                    ">

                                    <div class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            font-black
                                        "
                                        :style="`
                                                                                        color:
                                                                                            ${attribute.color};
                                        
                                                                                        background-color:
                                                                                            ${attribute.color}15;
                                                                                    `"
                                        x-text="
                                            attribute.icon
                                        ">
                                    </div>

                                </template>

                            </div>


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
                                        attribute.name
                                    ">
                                </p>


                                <p class="
                                        mt-1
                                        truncate
                                        text-[9px]
                                        text-slate-400
                                    "
                                    x-text="
                                        attribute.data_type_label
                                    ">
                                </p>

                            </div>


                            <span
                                x-show="
                                    isAttributeSelected(
                                        attribute.id
                                    )
                                "
                                class="
                                    text-sm
                                    font-black
                                    text-violet-600
                                ">
                                ✓
                            </span>

                        </button>

                    </template>

                </div>


                {{-- HIDDEN IDS --}}
                <template
                    x-for="
                        id
                        in selectedAttributeIds
                    "
                    :key="`selected-${id}`">

                    <input type="hidden" name="selected_attribute_ids[]"
                        :value="id">

                </template>


                <template
                    x-for="
                        id
                        in commonAttributeIds
                    "
                    :key="`common-${id}`">

                    <input type="hidden" name="common_attribute_ids[]"
                        :value="id">

                </template>


                {{-- ATRIBUTOS SELECCIONADOS --}}
                <div x-show="
                        selectedAttributes.length > 0
                    "
                    class="
                        mt-6
                        border-t
                        border-slate-100
                        pt-6
                    ">

                    <div
                        class="
                            flex
                            items-center
                            justify-between
                            gap-4
                        ">

                        <h3
                            class="
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            Seleccionados
                        </h3>


                        <span
                            class="
                                text-[10px]
                                text-slate-400
                            ">
                            Común = mismo valor para todo el lote
                        </span>

                    </div>


                    <div
                        class="
                            mt-3
                            grid
                            gap-3
                            md:grid-cols-2
                            xl:grid-cols-3
                        ">

                        <template
                            x-for="
                                attribute
                                in selectedAttributes
                            "
                            :key="`selected-card-${attribute.id}`">

                            <article
                                class="
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    p-4
                                ">

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-3
                                    ">

                                    <div class="
                                            flex
                                            h-9
                                            w-9
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-xl
                                            bg-violet-50
                                            font-black
                                            text-violet-600
                                        "
                                        x-text="
                                            attribute.icon
                                        ">
                                    </div>


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
                                                attribute.name
                                            ">
                                        </p>


                                        <p class="
                                                mt-0.5
                                                text-[9px]
                                                text-slate-400
                                            "
                                            x-text="
                                                attribute.data_type_label
                                            ">
                                        </p>

                                    </div>


                                    <button type="button"
                                        @click="
                                            removeAttribute(
                                                attribute.id
                                            )
                                        "
                                        class="
                                            text-sm
                                            font-black
                                            text-slate-300
                                            hover:text-red-500
                                        ">
                                        ×
                                    </button>

                                </div>


                                <div
                                    class="
                                        mt-4
                                        grid
                                        grid-cols-2
                                        gap-2
                                    ">

                                    <button type="button"
                                        @click="
                                            setAttributeMode(
                                                attribute.id,
                                                'row'
                                            )
                                        "
                                        class="
                                            rounded-lg
                                            px-3
                                            py-2
                                            text-[10px]
                                            font-black
                                        "
                                        :class="!isCommon(
                                                attribute.id
                                            )
                                        
                                            ?
                                            'bg-slate-900 text-white'
                                        
                                            :
                                            'bg-slate-100 text-slate-500'">
                                        Por Entidad
                                    </button>


                                    <button type="button"
                                        @click="
                                            setAttributeMode(
                                                attribute.id,
                                                'common'
                                            )
                                        "
                                        class="
                                            rounded-lg
                                            px-3
                                            py-2
                                            text-[10px]
                                            font-black
                                        "
                                        :class="isCommon(
                                                attribute.id
                                            )
                                        
                                            ?
                                            'bg-violet-600 text-white'
                                        
                                            :
                                            'bg-violet-50 text-violet-600'">
                                        Común
                                    </button>

                                </div>

                            </article>

                        </template>

                    </div>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- VALORES COMUNES --}}
            {{-- ================================================= --}}

            <section x-show="
                    commonAttributes.length > 0
                " x-cloak
                class="
                    rounded-3xl
                    border
                    border-violet-200
                    bg-violet-50/40
                    p-5
                    shadow-sm
                    sm:p-6
                ">

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-500
                    ">
                    Valores compartidos
                </p>


                <h2
                    class="
                        mt-1
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    Se aplicarán a todas las Entidades
                </h2>


                <div
                    class="
                        mt-5
                        grid
                        gap-4
                        md:grid-cols-2
                        xl:grid-cols-3
                    ">

                    <template
                        x-for="
                            attribute
                            in commonAttributes
                        "
                        :key="`common-input-${attribute.id}`">

                        <div
                            class="
                                rounded-2xl
                                border
                                border-violet-100
                                bg-white
                                p-4
                            ">

                            <div
                                class="
                                    mb-3
                                    flex
                                    items-center
                                    gap-2
                                ">

                                <span
                                    class="
                                        flex
                                        h-7
                                        w-7
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-violet-50
                                        text-xs
                                        text-violet-600
                                    "
                                    x-text="
                                        attribute.icon
                                    "></span>


                                <span
                                    class="
                                        text-xs
                                        font-black
                                        text-slate-700
                                    "
                                    x-text="
                                        attribute.name
                                    "></span>


                                <span
                                    x-show="
                                        attribute.is_required
                                    "
                                    class="
                                        text-red-500
                                    ">
                                    *
                                </span>

                            </div>


                            {{-- OPTION SIMPLE --}}
                            <template
                                x-if="
                                    attribute.data_type === 'OPTION'
                                    &&
                                    ! attribute.allows_multiple
                                ">

                                <select
                                    x-model="
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}]`"
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
                                            in attribute.options
                                        "
                                        :key="option.id">

                                        <option
                                            :value="option.id"
                                            x-text="
                                                option.name
                                            ">
                                        </option>

                                    </template>

                                </select>

                            </template>


                            {{-- OPTION MÚLTIPLE --}}
                            <template
                                x-if="
                                    attribute.data_type === 'OPTION'
                                    &&
                                    attribute.allows_multiple
                                ">

                                <select multiple
                                    x-model="
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}][]`"
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
                                            in attribute.options
                                        "
                                        :key="option.id">

                                        <option
                                            :value="option.id"
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
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}]`"
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
                                    attribute.data_type === 'INTEGER'
                                    ||
                                    attribute.data_type === 'DECIMAL'
                                ">

                                <input type="number"
                                    :step="attribute.data_type === 'INTEGER' ?
                                        '1' :
                                        'any'"
                                    :min="attribute.min_numeric_value ??
                                        null"
                                    :max="attribute.max_numeric_value ??
                                        null"
                                    x-model="
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}]`"
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
                                    attribute.data_type === 'DATE'
                                ">

                                <input type="date"
                                    x-model="
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}]`"
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
                                    attribute.data_type === 'COLOR'
                                ">

                                <input type="text" placeholder="#6366F1"
                                    x-model="
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}]`"
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        font-mono
                                        text-sm
                                    ">

                            </template>


                            {{-- LONG TEXT --}}
                            <template
                                x-if="
                                    attribute.data_type === 'LONG_TEXT'
                                ">

                                <textarea rows="3"
                                    x-model="
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}]`"
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-sm
                                    "></textarea>

                            </template>


                            {{-- TEXT --}}
                            <template
                                x-if="
                                    attribute.data_type === 'TEXT'
                                ">

                                <input type="text"
                                    x-model="
                                        commonValues[
                                            attribute.id
                                        ]
                                    "
                                    :name="`common_attributes[${attribute.id}]`"
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-sm
                                    ">

                            </template>

                        </div>

                    </template>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- HERRAMIENTAS --}}
            {{-- ================================================= --}}

            <section
                class="
                    grid
                    gap-4
                    lg:grid-cols-3
                ">

                {{-- FILAS --}}
                <article
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
                            text-[10px]
                            font-black
                            uppercase
                            text-indigo-500
                        ">
                        Filas
                    </p>


                    <div
                        class="
                            mt-3
                            flex
                            flex-wrap
                            gap-2
                        ">

                        <button type="button"
                            @click="
                                addRows(
                                    1
                                )
                            "
                            class="
                                rounded-xl
                                bg-indigo-50
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-indigo-700
                            ">
                            + 1 fila
                        </button>


                        <button type="button"
                            @click="
                                addRows(
                                    5
                                )
                            "
                            class="
                                rounded-xl
                                bg-indigo-50
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-indigo-700
                            ">
                            + 5
                        </button>


                        <button type="button"
                            @click="
                                addRows(
                                    10
                                )
                            "
                            class="
                                rounded-xl
                                bg-indigo-50
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-indigo-700
                            ">
                            + 10
                        </button>

                    </div>

                </article>


                {{-- IMPORTACIÓN --}}
                <article
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
                            text-[10px]
                            font-black
                            uppercase
                            text-emerald-500
                        ">
                        Importar datos
                    </p>


                    <div
                        class="
                            mt-3
                            flex
                            flex-wrap
                            gap-2
                        ">

                        <button type="button"
                            @click="
                                pasteOpen = true
                            "
                            class="
                                rounded-xl
                                bg-emerald-50
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-emerald-700
                            ">
                            ⧉ Pegar Excel / Sheets
                        </button>


                        <label
                            class="
                                cursor-pointer
                                rounded-xl
                                bg-emerald-50
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-emerald-700
                            ">
                            CSV

                            <input type="file" accept=".csv,text/csv" class="hidden"
                                @change="
                                    importCsv(
                                        $event
                                    )
                                ">
                        </label>

                    </div>

                </article>


                {{-- IMÁGENES --}}
                <article
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
                            text-[10px]
                            font-black
                            uppercase
                            text-fuchsia-500
                        ">
                        Imágenes en masa
                    </p>


                    <label
                        class="
                            mt-3
                            block
                            cursor-pointer
                            rounded-xl
                            border
                            border-dashed
                            border-fuchsia-200
                            bg-fuchsia-50
                            px-3
                            py-2
                            text-center
                            text-xs
                            font-black
                            text-fuchsia-700
                        ">
                        Seleccionar imágenes

                        <input type="file" name="bulk_images[]" multiple
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="hidden"
                            @change="
                                matchBulkImages(
                                    $event
                                )
                            ">
                    </label>


                    <p
                        class="
                            mt-2
                            text-[9px]
                            leading-4
                            text-slate-400
                        ">
                        El nombre del archivo se relaciona automáticamente
                        con el nombre de la Entidad.
                    </p>

                </article>

            </section>


            {{-- ================================================= --}}
            {{-- ACCIONES MASIVAS --}}
            {{-- ================================================= --}}

            <section x-show="
                    selectedRowsCount > 0
                " x-cloak
                class="
                    rounded-2xl
                    border
                    border-indigo-200
                    bg-indigo-50
                    p-4
                ">

                <div
                    class="
                        flex
                        flex-col
                        gap-4
                        xl:flex-row
                        xl:items-end
                    ">

                    <div class="
                            shrink-0
                        ">

                        <p
                            class="
                                text-sm
                                font-black
                                text-indigo-900
                            ">
                            <span
                                x-text="
                                    selectedRowsCount
                                "></span>

                            seleccionadas
                        </p>


                        <p
                            class="
                                mt-1
                                text-[10px]
                                text-indigo-500
                            ">
                            Aplica cambios solo a estas filas.
                        </p>

                    </div>


                    <div x-show="
                            individualAttributes.length > 0
                        "
                        class="
                            grid
                            min-w-0
                            flex-1
                            gap-3
                            md:grid-cols-[220px_minmax(0,1fr)_auto]
                            md:items-end
                        ">

                        <div>

                            <label
                                class="
                                    mb-1
                                    block
                                    text-[9px]
                                    font-black
                                    uppercase
                                    text-indigo-500
                                ">
                                Atributo
                            </label>


                            <select
                                x-model="
                                    bulkAttributeId
                                "
                                @change="
                                    resetBulkValue()
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border-indigo-200
                                    bg-white
                                    text-sm
                                ">

                                <option value="">
                                    Seleccionar...
                                </option>


                                <template
                                    x-for="
                                        attribute
                                        in individualAttributes
                                    "
                                    :key="attribute.id">

                                    <option
                                        :value="attribute.id"
                                        x-text="
                                            attribute.name
                                        ">
                                    </option>

                                </template>

                            </select>

                        </div>


                        {{-- VALOR BULK --}}
                        <div x-show="
                                bulkAttribute
                            ">

                            <label
                                class="
                                    mb-1
                                    block
                                    text-[9px]
                                    font-black
                                    uppercase
                                    text-indigo-500
                                ">
                                Nuevo valor
                            </label>


                            <template
                                x-if="
                                    bulkAttribute
                                    &&
                                    bulkAttribute.data_type === 'OPTION'
                                    &&
                                    ! bulkAttribute.allows_multiple
                                ">

                                <select
                                    x-model="
                                        bulkValue
                                    "
                                    class="
                                        w-full
                                        rounded-xl
                                        border-indigo-200
                                        bg-white
                                        text-sm
                                    ">

                                    <option value="">
                                        Sin valor
                                    </option>


                                    <template
                                        x-for="
                                            option
                                            in bulkAttribute.options
                                        "
                                        :key="option.id">

                                        <option
                                            :value="option.id"
                                            x-text="
                                                option.name
                                            ">
                                        </option>

                                    </template>

                                </select>

                            </template>


                            <template
                                x-if="
                                    bulkAttribute
                                    &&
                                    bulkAttribute.data_type === 'OPTION'
                                    &&
                                    bulkAttribute.allows_multiple
                                ">

                                <select multiple
                                    x-model="
                                        bulkValue
                                    "
                                    class="
                                        min-h-24
                                        w-full
                                        rounded-xl
                                        border-indigo-200
                                        bg-white
                                        text-sm
                                    ">

                                    <template
                                        x-for="
                                            option
                                            in bulkAttribute.options
                                        "
                                        :key="option.id">

                                        <option
                                            :value="option.id"
                                            x-text="
                                                option.name
                                            ">
                                        </option>

                                    </template>

                                </select>

                            </template>


                            <template
                                x-if="
                                    bulkAttribute
                                    &&
                                    bulkAttribute.data_type === 'BOOLEAN'
                                ">

                                <select
                                    x-model="
                                        bulkValue
                                    "
                                    class="
                                        w-full
                                        rounded-xl
                                        border-indigo-200
                                        bg-white
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


                            <template
                                x-if="
                                    bulkAttribute
                                    &&
                                    (
                                        bulkAttribute.data_type === 'INTEGER'
                                        ||
                                        bulkAttribute.data_type === 'DECIMAL'
                                    )
                                ">

                                <input type="number"
                                    :step="bulkAttribute.data_type === 'INTEGER' ?
                                        '1' :
                                        'any'"
                                    x-model="
                                        bulkValue
                                    "
                                    class="
                                        w-full
                                        rounded-xl
                                        border-indigo-200
                                        bg-white
                                        text-sm
                                    ">

                            </template>


                            <template
                                x-if="
                                    bulkAttribute
                                    &&
                                    bulkAttribute.data_type === 'DATE'
                                ">

                                <input type="date"
                                    x-model="
                                        bulkValue
                                    "
                                    class="
                                        w-full
                                        rounded-xl
                                        border-indigo-200
                                        bg-white
                                        text-sm
                                    ">

                            </template>


                            <template
                                x-if="
                                    bulkAttribute
                                    &&
                                    bulkAttribute.data_type === 'COLOR'
                                ">

                                <input type="text" placeholder="#6366F1"
                                    x-model="
                                        bulkValue
                                    "
                                    class="
                                        w-full
                                        rounded-xl
                                        border-indigo-200
                                        bg-white
                                        font-mono
                                        text-sm
                                    ">

                            </template>


                            <template
                                x-if="
                                    bulkAttribute
                                    &&
                                    (
                                        bulkAttribute.data_type === 'TEXT'
                                        ||
                                        bulkAttribute.data_type === 'LONG_TEXT'
                                    )
                                ">

                                <input type="text"
                                    x-model="
                                        bulkValue
                                    "
                                    class="
                                        w-full
                                        rounded-xl
                                        border-indigo-200
                                        bg-white
                                        text-sm
                                    ">

                            </template>

                        </div>


                        <button type="button"
                            @click="
                                applyBulkValue()
                            "
                            class="
                                rounded-xl
                                bg-indigo-600
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                            ">
                            Aplicar valor
                        </button>

                    </div>


                    <div
                        class="
                            flex
                            shrink-0
                            flex-wrap
                            gap-2
                        ">

                        <button type="button"
                            @click="
                                duplicateSelected()
                            "
                            class="
                                rounded-xl
                                bg-white
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-indigo-700
                            ">
                            ⧉ Duplicar
                        </button>


                        <button type="button"
                            @click="
                                removeSelected()
                            "
                            class="
                                rounded-xl
                                bg-red-50
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-red-600
                            ">
                            Eliminar
                        </button>

                    </div>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- TABLA --}}
            {{-- ================================================= --}}

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
                                tracking-wider
                                text-indigo-500
                            ">
                            Paso 3
                        </p>


                        <h2
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Entidades
                        </h2>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-400
                            ">
                            Las filas sin nombre serán ignoradas.
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
                                toggleSelectAll()
                            "
                            class="
                                rounded-xl
                                bg-slate-100
                                px-3
                                py-2
                                text-xs
                                font-bold
                                text-slate-600
                            ">
                            Seleccionar todas
                        </button>


                        <button type="button"
                            @click="
                                saveDraft()
                            "
                            class="
                                rounded-xl
                                bg-slate-100
                                px-3
                                py-2
                                text-xs
                                font-bold
                                text-slate-600
                            ">
                            Guardar borrador
                        </button>


                        <button type="button"
                            @click="
                                clearDraft()
                            "
                            class="
                                rounded-xl
                                bg-slate-100
                                px-3
                                py-2
                                text-xs
                                font-bold
                                text-slate-400
                            ">
                            Borrar borrador
                        </button>

                    </div>

                </div>


                <div class="
                        overflow-x-auto
                    ">

                    <table
                        class="
                            min-w-max
                            w-full
                            border-collapse
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
                                        w-12
                                        bg-slate-50
                                        px-3
                                        py-3
                                    ">
                                    ✓
                                </th>


                                <th
                                    class="
                                        min-w-24
                                        px-3
                                        py-3
                                        text-left
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Imagen
                                </th>


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
                                    Nombre
                                </th>


                                <th
                                    class="
                                        min-w-72
                                        px-3
                                        py-3
                                        text-left
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Descripción
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
                                    Tipo específico
                                </th>


                                <template
                                    x-for="
                                        attribute
                                        in individualAttributes
                                    "
                                    :key="`header-${attribute.id}`">

                                    <th
                                        class="
                                            min-w-56
                                            px-3
                                            py-3
                                            text-left
                                            align-bottom
                                        ">

                                        <div
                                            class="
                                                flex
                                                items-center
                                                justify-between
                                                gap-2
                                            ">

                                            <div>

                                                <p class="
                                                        text-[9px]
                                                        font-black
                                                        uppercase
                                                        text-slate-500
                                                    "
                                                    x-text="
                                                        attribute.name
                                                    ">
                                                </p>


                                                <p class="
                                                        mt-1
                                                        text-[8px]
                                                        font-normal
                                                        text-slate-400
                                                    "
                                                    x-text="
                                                        attribute.data_type_label
                                                    ">
                                                </p>

                                            </div>


                                            <button type="button" title="Copiar primer valor hacia abajo"
                                                @click="
                                                    copyDown(
                                                        attribute.id
                                                    )
                                                "
                                                class="
                                                    rounded-lg
                                                    bg-white
                                                    px-2
                                                    py-1
                                                    text-[9px]
                                                    font-black
                                                    text-indigo-500
                                                ">
                                                ↓
                                            </button>

                                        </div>

                                    </th>

                                </template>


                                <th
                                    class="
                                        w-14
                                        px-3
                                        py-3
                                    ">
                                </th>

                            </tr>

                        </thead>


                        <tbody
                            class="
                                divide-y
                                divide-slate-100
                            ">

                            <template
                                x-for="
                                    (row, rowIndex)
                                    in rows
                                "
                                :key="row.key">

                                <tr class="
                                        align-top
                                        transition
                                    "
                                    :class="{
                                    
                                        'bg-indigo-50/60': row.selected,
                                    
                                        'bg-amber-50/60':
                                            !row.selected &&
                                            isExistingName(
                                                row.name
                                            )
                                    }">

                                    {{-- CHECK --}}
                                    <td
                                        class="
                                            sticky
                                            left-0
                                            z-10
                                            bg-inherit
                                            px-3
                                            py-3
                                            text-center
                                        ">

                                        <input type="checkbox"
                                            x-model="
                                                row.selected
                                            "
                                            class="
                                                rounded
                                                border-slate-300
                                                text-indigo-600
                                            ">

                                    </td>


                                    {{-- IMAGE --}}
                                    <td
                                        class="
                                            px-3
                                            py-3
                                        ">

                                        <div
                                            class="
                                                h-16
                                                w-16
                                                overflow-hidden
                                                rounded-xl
                                                bg-slate-100
                                            ">

                                            <template
                                                x-if="
                                                    row.imagePreview
                                                    ||
                                                    row.bulkImagePreview
                                                ">

                                                <img :src="row.imagePreview ||
                                                    row.bulkImagePreview"
                                                    class="
                                                        h-full
                                                        w-full
                                                        object-cover
                                                    ">

                                            </template>


                                            <template
                                                x-if="
                                                    ! row.imagePreview
                                                    &&
                                                    ! row.bulkImagePreview
                                                ">

                                                <div
                                                    class="
                                                        flex
                                                        h-full
                                                        items-center
                                                        justify-center
                                                        text-xl
                                                        text-slate-300
                                                    ">
                                                    ✦
                                                </div>

                                            </template>

                                        </div>


                                        <label
                                            class="
                                                mt-2
                                                block
                                                cursor-pointer
                                                text-center
                                                text-[9px]
                                                font-bold
                                                text-indigo-600
                                            ">
                                            Seleccionar

                                            <input type="file"
                                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                :name="`images[${row.key}]`"
                                                class="hidden"
                                                @change="
                                                    previewIndividualImage(
                                                        $event,
                                                        row
                                                    )
                                                ">
                                        </label>


                                        <p x-show="
                                                row.bulkImageName
                                                &&
                                                ! row.imagePreview
                                            "
                                            class="
                                                mt-1
                                                max-w-16
                                                truncate
                                                text-center
                                                text-[8px]
                                                text-emerald-500
                                            "
                                            x-text="
                                                row.bulkImageName
                                            ">
                                        </p>

                                    </td>


                                    {{-- NAME --}}
                                    <td
                                        class="
                                            px-3
                                            py-3
                                        ">

                                        <input type="text"
                                            x-model="
                                                row.name
                                            "
                                            :name="`rows[${row.key}][name]`"
                                            placeholder="Nombre..."
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-sm
                                                font-bold
                                                text-slate-800
                                            ">


                                        <p x-show="
                                                isExistingName(
                                                    row.name
                                                )
                                            "
                                            x-cloak
                                            class="
                                                mt-1
                                                text-[9px]
                                                font-bold
                                                text-amber-600
                                            ">
                                            ⚠ Ya existe en tu Biblioteca
                                        </p>

                                    </td>


                                    {{-- DESCRIPTION --}}
                                    <td
                                        class="
                                            px-3
                                            py-3
                                        ">

                                        <textarea rows="3"
                                            x-model="
                                                row.description
                                            "
                                            :name="`rows[${row.key}][description]`"
                                            placeholder="Descripción..."
                                            class="
                                                w-full
                                                resize-y
                                                rounded-xl
                                                border-slate-300
                                                text-xs
                                            "></textarea>

                                    </td>


                                    {{-- TYPE OVERRIDE --}}
                                    <td
                                        class="
                                            px-3
                                            py-3
                                        ">

                                        <select
                                            x-model="
                                                row.entity_type_id
                                            "
                                            :name="`rows[${row.key}][entity_type_id]`"
                                            class="
                                                w-full
                                                rounded-xl
                                                border-slate-300
                                                text-xs
                                            ">

                                            <option value="">
                                                Usar tipo común
                                            </option>


                                            <template
                                                x-for="
                                                    type
                                                    in entityTypes
                                                "
                                                :key="type.id">

                                                <option
                                                    :value="type.id"
                                                    x-text="
                                                        type.name
                                                    ">
                                                </option>

                                            </template>

                                        </select>

                                    </td>


                                    {{-- ATRIBUTOS INDIVIDUALES --}}
                                    <template
                                        x-for="
                                            attribute
                                            in individualAttributes
                                        "
                                        :key="`cell-${row.key}-${attribute.id}`">

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
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}]`"
                                                    class="
                                                        w-full
                                                        rounded-xl
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

                                                        <option
                                                            :value="option.id"
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
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}][]`"
                                                    class="
                                                        min-h-24
                                                        w-full
                                                        rounded-xl
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                                    <template
                                                        x-for="
                                                            option
                                                            in attribute.options
                                                        "
                                                        :key="option.id">

                                                        <option
                                                            :value="option.id"
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
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}]`"
                                                    class="
                                                        w-full
                                                        rounded-xl
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
                                                    :min="attribute.min_numeric_value ??
                                                        null"
                                                    :max="attribute.max_numeric_value ??
                                                        null"
                                                    x-model="
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}]`"
                                                    class="
                                                        w-full
                                                        rounded-xl
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
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}]`"
                                                    class="
                                                        w-full
                                                        rounded-xl
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                            </template>


                                            {{-- COLOR --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'COLOR'
                                                ">

                                                <input type="text" placeholder="#6366F1"
                                                    x-model="
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}]`"
                                                    class="
                                                        w-full
                                                        rounded-xl
                                                        border-slate-300
                                                        font-mono
                                                        text-xs
                                                    ">

                                            </template>


                                            {{-- LONG TEXT --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'LONG_TEXT'
                                                ">

                                                <textarea rows="3"
                                                    x-model="
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}]`"
                                                    class="
                                                        w-full
                                                        rounded-xl
                                                        border-slate-300
                                                        text-xs
                                                    "></textarea>

                                            </template>


                                            {{-- TEXT --}}
                                            <template
                                                x-if="
                                                    attribute.data_type === 'TEXT'
                                                ">

                                                <input type="text"
                                                    x-model="
                                                        row.attributes[
                                                            attribute.id
                                                        ]
                                                    "
                                                    :name="`rows[${row.key}][attributes][${attribute.id}]`"
                                                    class="
                                                        w-full
                                                        rounded-xl
                                                        border-slate-300
                                                        text-xs
                                                    ">

                                            </template>

                                        </td>

                                    </template>


                                    {{-- DELETE --}}
                                    <td
                                        class="
                                            px-3
                                            py-3
                                            text-center
                                        ">

                                        <button type="button"
                                            @click="
                                                removeRow(
                                                    rowIndex
                                                )
                                            "
                                            class="
                                                flex
                                                h-9
                                                w-9
                                                items-center
                                                justify-center
                                                rounded-xl
                                                bg-red-50
                                                text-red-400
                                                transition
                                                hover:bg-red-100
                                                hover:text-red-600
                                            "
                                            title="Eliminar fila">
                                            ×
                                        </button>

                                    </td>

                                </tr>

                            </template>

                        </tbody>

                    </table>

                </div>


                <div
                    class="
                        flex
                        flex-col
                        gap-3
                        border-t
                        border-slate-100
                        p-4
                        sm:flex-row
                        sm:items-center
                        sm:justify-between
                    ">

                    <p
                        class="
                            text-xs
                            text-slate-400
                        ">
                        <span
                            class="
                                font-black
                                text-slate-600
                            "
                            x-text="
                                rows.length
                            "></span>

                        filas en el editor · máximo 200.
                    </p>


                    <button type="button"
                        @click="
                            addRows(
                                5
                            )
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
                        + Añadir 5 filas
                    </button>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- RESUMEN FINAL --}}
            {{-- ================================================= --}}

            <section
                class="
                    sticky
                    bottom-4
                    z-30
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white/95
                    p-4
                    shadow-2xl
                    backdrop-blur
                    sm:p-5
                ">

                <div
                    class="
                        flex
                        flex-col
                        gap-4
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
                                tracking-wider
                                text-indigo-500
                            ">
                            Revisión
                        </p>


                        <div
                            class="
                                mt-2
                                flex
                                flex-wrap
                                gap-x-5
                                gap-y-2
                                text-sm
                            ">

                            <span
                                class="
                                    font-black
                                    text-emerald-600
                                ">
                                <span
                                    x-text="
                                        readyCount
                                    "></span>

                                listas
                            </span>


                            <span
                                class="
                                    text-slate-500
                                ">
                                <span
                                    x-text="
                                        rowsWithoutImage
                                    "></span>

                                sin imagen
                            </span>


                            <span
                                class="
                                    text-amber-600
                                ">
                                <span
                                    x-text="
                                        existingNameCount
                                    "></span>

                                nombres existentes
                            </span>


                            <span
                                x-show="
                                    importWarningCount > 0
                                "
                                class="
                                    text-amber-600
                                ">
                                <span
                                    x-text="
                                        importWarningCount
                                    "></span>

                                advertencias de importación
                            </span>

                        </div>

                    </div>


                    <div
                        class="
                            flex
                            flex-col
                            gap-2
                            sm:flex-row
                        ">

                        <button type="button"
                            @click="
                                saveDraft()
                            "
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                px-5
                                py-3
                                text-sm
                                font-bold
                                text-slate-600
                            ">
                            Guardar borrador
                        </button>


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
                                transition
                                hover:bg-indigo-700
                            ">
                            Crear

                            <span
                                x-text="
                                    readyCount
                                "></span>

                            Entidades
                        </button>

                    </div>

                </div>

            </section>

        </form>


        {{-- ===================================================== --}}
        {{-- MODAL PEGAR EXCEL --}}
        {{-- ===================================================== --}}

        <div x-show="
                pasteOpen
            " x-cloak
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
                    pasteOpen = false
                "
                class="
                    w-full
                    max-w-3xl
                    rounded-3xl
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
                                text-emerald-500
                            ">
                            Importación rápida
                        </p>


                        <h3
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Pegar desde Excel o Google Sheets
                        </h3>


                        <p
                            class="
                                mt-2
                                text-sm
                                leading-6
                                text-slate-500
                            ">
                            La primera fila puede contener encabezados.
                            Usa Nombre, Descripción y nombres/códigos
                            de tus Atributos.
                        </p>

                    </div>


                    <button type="button"
                        @click="
                            pasteOpen = false
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


                <div
                    class="
                        mt-5
                        rounded-2xl
                        bg-slate-50
                        p-4
                        font-mono
                        text-xs
                        leading-6
                        text-slate-500
                    ">
                    Nombre&nbsp;&nbsp;&nbsp;&nbsp;Descripción&nbsp;&nbsp;&nbsp;&nbsp;Anime&nbsp;&nbsp;&nbsp;&nbsp;Elemento&nbsp;&nbsp;&nbsp;&nbsp;Poder<br>
                    Naruto&nbsp;&nbsp;&nbsp;&nbsp;Ninja de
                    Konoha&nbsp;&nbsp;&nbsp;&nbsp;Naruto&nbsp;&nbsp;&nbsp;&nbsp;Viento&nbsp;&nbsp;&nbsp;&nbsp;95<br>
                    Sasuke&nbsp;&nbsp;&nbsp;&nbsp;Uchiha&nbsp;&nbsp;&nbsp;&nbsp;Naruto&nbsp;&nbsp;&nbsp;&nbsp;Rayo&nbsp;&nbsp;&nbsp;&nbsp;94
                </div>


                <textarea x-model="
                        pasteText
                    " rows="12" placeholder="Pega aquí..."
                    class="
                        mt-5
                        w-full
                        rounded-2xl
                        border-slate-300
                        font-mono
                        text-sm
                    "></textarea>


                <div
                    class="
                        mt-5
                        flex
                        justify-end
                        gap-3
                    ">

                    <button type="button"
                        @click="
                            pasteOpen = false
                        "
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            px-5
                            py-3
                            text-sm
                            font-bold
                            text-slate-600
                        ">
                        Cancelar
                    </button>


                    <button type="button"
                        @click="
                            importPasted()
                        "
                        class="
                            rounded-xl
                            bg-emerald-600
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-white
                        ">
                        Importar filas
                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ALPINE --}}
    {{-- ========================================================= --}}

    <script>
        function bulkEntityBuilder(
            config
        ) {

            const draftKey =
                'omnimerge.entities.bulk.draft';


            return {

                /*
                |--------------------------------------------------------------------------
                | Recursos
                |--------------------------------------------------------------------------
                */

                attributes: config.attributes ??
                    [],

                entityTypes: config.entityTypes ??
                    [],

                collections: config.collections ??
                    [],


                /*
                |--------------------------------------------------------------------------
                | Configuración
                |--------------------------------------------------------------------------
                */

                batchName: '',

                entityTypeId: '',

                status: 'ACTIVE',

                visibility: 'PUBLIC',

                allowCloning: true,

                duplicateStrategy: 'create',

                collectionIds: [],


                /*
                |--------------------------------------------------------------------------
                | Características
                |--------------------------------------------------------------------------
                */

                selectedAttributeIds: [],

                commonAttributeIds: [],

                commonValues: {},

                attributeSearch: '',


                /*
                |--------------------------------------------------------------------------
                | Filas
                |--------------------------------------------------------------------------
                */

                rows: [],


                /*
                |--------------------------------------------------------------------------
                | Bulk
                |--------------------------------------------------------------------------
                */

                bulkAttributeId: '',

                bulkValue: '',


                /*
                |--------------------------------------------------------------------------
                | Import
                |--------------------------------------------------------------------------
                */

                pasteOpen: false,

                pasteText: '',


                /*
                |--------------------------------------------------------------------------
                | Existentes
                |--------------------------------------------------------------------------
                */

                existingNames: new Set(
                    (
                        config.existingNames ??
                        []
                    )
                    .map(
                        value =>
                        thisNormalize(
                            value
                        )
                    )
                ),


                /*
                |--------------------------------------------------------------------------
                | INIT
                |--------------------------------------------------------------------------
                */

                init() {

                    /*
                    |--------------------------------------------------------------------------
                    | OLD de Laravel tiene prioridad
                    |--------------------------------------------------------------------------
                    */

                    if (
                        config.old &&
                        config.old.rows
                    ) {

                        this.restoreOld(
                            config.old
                        );

                        this.registerAutoDraft();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Plantilla
                    |--------------------------------------------------------------------------
                    */

                    if (
                        config.template
                    ) {

                        this.applyTemplate(
                            config.template
                        );

                        this.registerAutoDraft();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Borrador local
                    |--------------------------------------------------------------------------
                    */

                    const draft =
                        this.readDraft();


                    if (draft) {

                        this.restoreDraft(
                            draft
                        );

                        this.registerAutoDraft();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Nuevo lote
                    |--------------------------------------------------------------------------
                    */

                    this.addRows(
                        5
                    );


                    this.registerAutoDraft();
                },


                /*
                |--------------------------------------------------------------------------
                | GETTERS
                |--------------------------------------------------------------------------
                */

                get selectedAttributes() {

                    return this.attributes
                        .filter(
                            attribute =>
                            this
                            .selectedAttributeIds
                            .includes(
                                String(
                                    attribute.id
                                )
                            )
                        );
                },


                get commonAttributes() {

                    return this
                        .selectedAttributes
                        .filter(
                            attribute =>
                            this
                            .commonAttributeIds
                            .includes(
                                String(
                                    attribute.id
                                )
                            )
                        );
                },


                get individualAttributes() {

                    return this
                        .selectedAttributes
                        .filter(
                            attribute =>
                            !this
                            .commonAttributeIds
                            .includes(
                                String(
                                    attribute.id
                                )
                            )
                        );
                },


                get filteredAttributes() {

                    const search =
                        this
                        .attributeSearch
                        .trim()
                        .toLowerCase();


                    if (!search) {
                        return this.attributes;
                    }


                    return this.attributes
                        .filter(
                            attribute => {

                                const text =
                                    `
                                        ${attribute.name}
                                        ${attribute.code}
                                        ${attribute.data_type_label}
                                        ${(attribute.groups ?? []).join(' ')}
                                    `
                                    .toLowerCase();


                                return text.includes(
                                    search
                                );
                            }
                        );
                },


                get selectedRowsCount() {

                    return this.rows
                        .filter(
                            row =>
                            row.selected
                        )
                        .length;
                },


                get readyCount() {

                    return this.rows
                        .filter(
                            row =>
                            row
                            .name
                            .trim() !==
                            ''
                        )
                        .length;
                },


                get rowsWithoutImage() {

                    return this.rows
                        .filter(
                            row =>
                            row
                            .name
                            .trim() !==
                            '' &&
                            !row.imagePreview &&
                            !row.bulkImagePreview
                        )
                        .length;
                },


                get existingNameCount() {

                    return this.rows
                        .filter(
                            row =>
                            this.isExistingName(
                                row.name
                            )
                        )
                        .length;
                },


                get importWarningCount() {

                    return this.rows
                        .reduce(
                            (
                                total,
                                row
                            ) =>
                            total +
                            (
                                row.importWarnings
                                ?.length ??
                                0
                            ),

                            0
                        );
                },


                get bulkAttribute() {

                    if (
                        !this.bulkAttributeId
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
                                this.bulkAttributeId
                            )
                        ) ??
                        null;
                },


                /*
                |--------------------------------------------------------------------------
                | ROW
                |--------------------------------------------------------------------------
                */

                uid() {

                    return 'r_' +
                        Date.now()
                        .toString(36) +
                        '_' +
                        Math.random()
                        .toString(36)
                        .slice(
                            2,
                            9
                        );
                },


                newRow(
                    seed = {}
                ) {

                    const attributes =
                        JSON.parse(
                            JSON.stringify(
                                seed.attributes ??
                                {}
                            )
                        );


                    const row = {

                        key: seed.key ??
                            this.uid(),

                        selected: false,

                        name: seed.name ??
                            '',

                        description: seed.description ??
                            '',

                        entity_type_id: String(
                            seed.entity_type_id ??
                            ''
                        ),

                        attributes: attributes,

                        imagePreview: null,

                        bulkImagePreview: null,

                        bulkImageName: null,

                        importWarnings: seed.importWarnings ??
                            [],
                    };


                    this.ensureRowValues(
                        row
                    );


                    return row;
                },


                addRows(
                    amount
                ) {

                    const available =
                        Math.max(
                            0,
                            200 -
                            this.rows.length
                        );


                    const count =
                        Math.min(
                            amount,
                            available
                        );


                    for (
                        let index = 0; index < count; index++
                    ) {

                        this.rows.push(
                            this.newRow()
                        );
                    }
                },


                removeRow(
                    index
                ) {

                    this.rows.splice(
                        index,
                        1
                    );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            1
                        );
                    }
                },


                toggleSelectAll() {

                    const shouldSelect =
                        this.rows.some(
                            row =>
                            !row.selected &&
                            row
                            .name
                            .trim() !==
                            ''
                        );


                    this.rows
                        .forEach(
                            row => {

                                if (
                                    row
                                    .name
                                    .trim() !==
                                    ''
                                ) {

                                    row.selected =
                                        shouldSelect;
                                }
                            }
                        );
                },


                duplicateSelected() {

                    const selected =
                        this.rows
                        .filter(
                            row =>
                            row.selected
                        );


                    if (
                        selected.length === 0
                    ) {
                        return;
                    }


                    const remaining =
                        200 -
                        this.rows.length;


                    selected
                        .slice(
                            0,
                            remaining
                        )
                        .forEach(
                            row => {

                                const clone =
                                    this.newRow({

                                        name: row.name ?
                                            row.name +
                                            ' copia' :
                                            '',

                                        description: row.description,

                                        entity_type_id: row.entity_type_id,

                                        attributes: row.attributes,
                                    });


                                this.rows.push(
                                    clone
                                );
                            }
                        );


                    selected
                        .forEach(
                            row =>
                            row.selected =
                            false
                        );
                },


                removeSelected() {

                    this.rows =
                        this.rows
                        .filter(
                            row =>
                            !row.selected
                        );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            1
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | ATTRIBUTES
                |--------------------------------------------------------------------------
                */

                isAttributeSelected(
                    id
                ) {

                    return this
                        .selectedAttributeIds
                        .includes(
                            String(
                                id
                            )
                        );
                },


                isCommon(
                    id
                ) {

                    return this
                        .commonAttributeIds
                        .includes(
                            String(
                                id
                            )
                        );
                },


                toggleAttribute(
                    attribute
                ) {

                    const id =
                        String(
                            attribute.id
                        );


                    if (
                        this.isAttributeSelected(
                            id
                        )
                    ) {

                        this.removeAttribute(
                            id
                        );

                    } else {

                        this.selectedAttributeIds
                            .push(
                                id
                            );


                        this.rows
                            .forEach(
                                row =>
                                this.ensureRowAttribute(
                                    row,
                                    attribute
                                )
                            );
                    }
                },


                removeAttribute(
                    id
                ) {

                    id =
                        String(
                            id
                        );


                    this.selectedAttributeIds =
                        this
                        .selectedAttributeIds
                        .filter(
                            value =>
                            value !==
                            id
                        );


                    this.commonAttributeIds =
                        this
                        .commonAttributeIds
                        .filter(
                            value =>
                            value !==
                            id
                        );


                    delete this.commonValues[
                        id
                    ];


                    this.rows
                        .forEach(
                            row => {

                                delete row.attributes[
                                    id
                                ];
                            }
                        );
                },


                setAttributeMode(
                    id,
                    mode
                ) {

                    id =
                        String(
                            id
                        );


                    const attribute =
                        this.attributes
                        .find(
                            item =>
                            String(
                                item.id
                            ) ===
                            id
                        );


                    if (!attribute) {
                        return;
                    }


                    if (
                        mode === 'common'
                    ) {

                        if (
                            !this
                            .commonAttributeIds
                            .includes(
                                id
                            )
                        ) {

                            this.commonAttributeIds
                                .push(
                                    id
                                );
                        }


                        this.ensureCommonValue(
                            attribute
                        );

                        return;
                    }


                    this.commonAttributeIds =
                        this
                        .commonAttributeIds
                        .filter(
                            value =>
                            value !== id
                        );


                    this.rows
                        .forEach(
                            row =>
                            this.ensureRowAttribute(
                                row,
                                attribute
                            )
                        );
                },


                ensureCommonValue(
                    attribute
                ) {

                    const id =
                        String(
                            attribute.id
                        );


                    if (
                        this.commonValues[
                            id
                        ] !==
                        undefined
                    ) {
                        return;
                    }


                    this.commonValues[
                            id
                        ] =
                        attribute.allows_multiple ?
                        [] :
                        '';
                },


                ensureRowAttribute(
                    row,
                    attribute
                ) {

                    const id =
                        String(
                            attribute.id
                        );


                    if (
                        row.attributes[
                            id
                        ] !==
                        undefined
                    ) {
                        return;
                    }


                    row.attributes[
                            id
                        ] =
                        attribute.allows_multiple ?
                        [] :
                        '';
                },


                ensureRowValues(
                    row
                ) {

                    this.selectedAttributes
                        .forEach(
                            attribute =>
                            this.ensureRowAttribute(
                                row,
                                attribute
                            )
                        );
                },


                /*
                |--------------------------------------------------------------------------
                | BULK VALUE
                |--------------------------------------------------------------------------
                */

                resetBulkValue() {

                    if (
                        this.bulkAttribute &&
                        this.bulkAttribute
                        .allows_multiple
                    ) {

                        this.bulkValue = [];

                    } else {

                        this.bulkValue =
                            '';
                    }
                },


                applyBulkValue() {

                    const attribute =
                        this.bulkAttribute;


                    if (!attribute) {

                        alert(
                            'Selecciona un atributo.'
                        );

                        return;
                    }


                    if (
                        this.selectedRowsCount ===
                        0
                    ) {

                        alert(
                            'Selecciona al menos una fila.'
                        );

                        return;
                    }


                    this.rows
                        .filter(
                            row =>
                            row.selected
                        )
                        .forEach(
                            row => {

                                row.attributes[
                                        String(
                                            attribute.id
                                        )
                                    ] =
                                    JSON.parse(
                                        JSON.stringify(
                                            this.bulkValue
                                        )
                                    );
                            }
                        );
                },


                copyDown(
                    attributeId
                ) {

                    attributeId =
                        String(
                            attributeId
                        );


                    const source =
                        this.rows
                        .find(
                            row =>
                            this.hasValue(
                                row.attributes[
                                    attributeId
                                ]
                            )
                        );


                    if (!source) {

                        alert(
                            'No existe ningún valor para copiar.'
                        );

                        return;
                    }


                    const value =
                        JSON.parse(
                            JSON.stringify(
                                source.attributes[
                                    attributeId
                                ]
                            )
                        );


                    this.rows
                        .forEach(
                            row => {

                                row.attributes[
                                        attributeId
                                    ] =
                                    JSON.parse(
                                        JSON.stringify(
                                            value
                                        )
                                    );
                            }
                        );
                },


                hasValue(
                    value
                ) {

                    if (
                        Array.isArray(
                            value
                        )
                    ) {

                        return value.length > 0;
                    }


                    return value !== null &&
                        value !== undefined &&
                        String(
                            value
                        ) !== '';
                },


                /*
                |--------------------------------------------------------------------------
                | IMAGES
                |--------------------------------------------------------------------------
                */

                previewIndividualImage(
                    event,
                    row
                ) {

                    const file =
                        event.target.files[
                            0
                        ];


                    if (!file) {
                        return;
                    }


                    row.imagePreview =
                        URL.createObjectURL(
                            file
                        );
                },


                matchBulkImages(
                    event
                ) {

                    const files =
                        Array.from(
                            event.target.files ??
                            []
                        );


                    /*
                     * Limpiar previews masivos anteriores.
                     */
                    this.rows
                        .forEach(
                            row => {

                                row.bulkImagePreview =
                                    null;

                                row.bulkImageName =
                                    null;
                            }
                        );


                    files
                        .forEach(
                            file => {

                                const fileBase =
                                    file.name
                                    .replace(
                                        /\.[^/.]+$/,
                                        ''
                                    );


                                const normalized =
                                    this.normalize(
                                        fileBase
                                    );


                                const row =
                                    this.rows
                                    .find(
                                        candidate =>

                                        !candidate
                                        .imagePreview

                                        &&

                                        !candidate
                                        .bulkImagePreview

                                        &&

                                        this.normalize(
                                            candidate.name
                                        ) ===
                                        normalized
                                    );


                                if (!row) {
                                    return;
                                }


                                row.bulkImagePreview =
                                    URL.createObjectURL(
                                        file
                                    );


                                row.bulkImageName =
                                    file.name;
                            }
                        );
                },


                /*
                |--------------------------------------------------------------------------
                | TEMPLATE
                |--------------------------------------------------------------------------
                */

                loadTemplate(
                    id
                ) {

                    const url =
                        new URL(
                            config.createUrl,
                            window.location.origin
                        );


                    if (id) {

                        url.searchParams.set(
                            'template_entity',
                            id
                        );
                    }


                    window.location.href =
                        url.toString();
                },


                applyTemplate(
                    template
                ) {

                    this.entityTypeId =
                        String(
                            template.entity_type_id ??
                            ''
                        );


                    this.collectionIds =
                        (
                            template.collection_ids ??
                            []
                        )
                        .map(
                            String
                        );


                    this.selectedAttributeIds =
                        (
                            template.selected_attribute_ids ??
                            []
                        )
                        .map(
                            String
                        );


                    this.commonAttributeIds = [];


                    this.commonValues = {};


                    /*
                     * Cinco filas listas para trabajar,
                     * todas con la estructura y valores
                     * de la Entidad base.
                     */

                    for (
                        let index = 0; index < 5; index++
                    ) {

                        this.rows.push(
                            this.newRow({

                                attributes: template.values ??
                                    {},
                            })
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | DUPLICATES
                |--------------------------------------------------------------------------
                */

                isExistingName(
                    name
                ) {

                    const normalized =
                        this.normalize(
                            name
                        );


                    if (!normalized) {
                        return false;
                    }


                    return this
                        .existingNames
                        .has(
                            normalized
                        );
                },


                /*
                |--------------------------------------------------------------------------
                | CSV / EXCEL
                |--------------------------------------------------------------------------
                */

                async importCsv(
                    event
                ) {

                    const file =
                        event.target.files[
                            0
                        ];


                    if (!file) {
                        return;
                    }


                    const text =
                        await file.text();


                    this.pasteText =
                        text;


                    this.importPasted();


                    event.target.value =
                        '';
                },


                importPasted() {

                    const text =
                        this
                        .pasteText
                        .trim();


                    if (!text) {
                        return;
                    }


                    const delimiter =
                        text.includes(
                            '\t'
                        ) ?
                        '\t' :
                        ',';


                    const lines =
                        text
                        .split(
                            /\r?\n/
                        )
                        .filter(
                            line =>
                            line.trim() !==
                            ''
                        );


                    if (
                        lines.length === 0
                    ) {
                        return;
                    }


                    let matrix =
                        lines
                        .map(
                            line =>
                            this.parseCsvLine(
                                line,
                                delimiter
                            )
                        );


                    let headers =
                        matrix[
                            0
                        ]
                        .map(
                            value =>
                            this.normalize(
                                value
                            )
                        );


                    const nameHeaders = [
                        'name',
                        'nombre'
                    ];


                    let hasHeader =
                        headers
                        .some(
                            header =>
                            nameHeaders
                            .includes(
                                header
                            )
                        );


                    let dataRows;


                    if (hasHeader) {

                        dataRows =
                            matrix.slice(
                                1
                            );

                    } else {

                        headers = [
                            'nombre',
                            'descripcion'
                        ];


                        dataRows =
                            matrix;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Mapear encabezados a Atributos
                    |--------------------------------------------------------------------------
                    */

                    const mappedAttributes = {};


                    headers
                        .forEach(
                            (
                                header,
                                index
                            ) => {

                                if (
                                    [
                                        'name',
                                        'nombre',
                                        'description',
                                        'descripcion'
                                    ]
                                    .includes(
                                        header
                                    )
                                ) {
                                    return;
                                }


                                const attribute =
                                    this.attributes
                                    .find(
                                        item =>

                                        this.normalize(
                                            item.name
                                        ) ===
                                        header

                                        ||

                                        this.normalize(
                                            item.code
                                        ) ===
                                        header
                                    );


                                if (!attribute) {
                                    return;
                                }


                                mappedAttributes[
                                        index
                                    ] =
                                    attribute;


                                const id =
                                    String(
                                        attribute.id
                                    );


                                if (
                                    !this
                                    .selectedAttributeIds
                                    .includes(
                                        id
                                    )
                                ) {

                                    this.selectedAttributeIds
                                        .push(
                                            id
                                        );
                                }
                            }
                        );


                    const imported = [];


                    dataRows
                        .slice(
                            0,
                            200
                        )
                        .forEach(
                            values => {

                                const row =
                                    this.newRow();


                                headers
                                    .forEach(
                                        (
                                            header,
                                            index
                                        ) => {

                                            const raw =
                                                values[
                                                    index
                                                ] ??
                                                '';


                                            if (
                                                [
                                                    'name',
                                                    'nombre'
                                                ]
                                                .includes(
                                                    header
                                                )
                                            ) {

                                                row.name =
                                                    raw.trim();

                                                return;
                                            }


                                            if (
                                                [
                                                    'description',
                                                    'descripcion'
                                                ]
                                                .includes(
                                                    header
                                                )
                                            ) {

                                                row.description =
                                                    raw.trim();

                                                return;
                                            }


                                            const attribute =
                                                mappedAttributes[
                                                    index
                                                ];


                                            if (!attribute) {
                                                return;
                                            }


                                            row.attributes[
                                                    String(
                                                        attribute.id
                                                    )
                                                ] =
                                                this.parseAttributeCell(
                                                    attribute,
                                                    raw,
                                                    row
                                                );
                                        }
                                    );


                                imported.push(
                                    row
                                );
                            }
                        );


                    /*
                     * Si solo había filas vacías,
                     * reemplazarlas.
                     */
                    const existingUseful =
                        this.rows.some(
                            row =>
                            row
                            .name
                            .trim() !==
                            ''
                        );


                    if (!existingUseful) {

                        this.rows =
                            imported;

                    } else {

                        const available =
                            Math.max(
                                0,
                                200 -
                                this.rows.length
                            );


                        this.rows.push(
                            ...imported.slice(
                                0,
                                available
                            )
                        );
                    }


                    this.rows
                        .forEach(
                            row =>
                            this.ensureRowValues(
                                row
                            )
                        );


                    this.pasteOpen =
                        false;


                    this.pasteText =
                        '';
                },


                parseAttributeCell(
                    attribute,
                    raw,
                    row
                ) {

                    raw =
                        String(
                            raw ??
                            ''
                        )
                        .trim();


                    if (!raw) {

                        return attribute
                            .allows_multiple ?
                            [] :
                            '';
                    }


                    if (
                        attribute.data_type ===
                        'OPTION'
                    ) {

                        const rawValues =
                            attribute.allows_multiple

                            ?
                            raw
                            .split(
                                /[|;]/
                            )
                            .map(
                                value =>
                                value.trim()
                            )
                            .filter(
                                Boolean
                            )

                            :
                            [
                                raw
                            ];


                        const ids = [];


                        rawValues
                            .forEach(
                                rawValue => {

                                    const normalized =
                                        this.normalize(
                                            rawValue
                                        );


                                    const option =
                                        attribute.options
                                        .find(
                                            item =>

                                            this.normalize(
                                                item.name
                                            ) ===
                                            normalized

                                            ||

                                            this.normalize(
                                                item.code
                                            ) ===
                                            normalized
                                        );


                                    if (option) {

                                        ids.push(
                                            String(
                                                option.id
                                            )
                                        );

                                    } else {

                                        row.importWarnings
                                            .push(
                                                `${attribute.name}: "${rawValue}" no existe en el Catálogo.`
                                            );
                                    }
                                }
                            );


                        return attribute
                            .allows_multiple ?
                            ids :
                            (
                                ids[
                                    0
                                ] ??
                                ''
                            );
                    }


                    if (
                        attribute.data_type ===
                        'BOOLEAN'
                    ) {

                        const normalized =
                            this.normalize(
                                raw
                            );


                        if (
                            [
                                'si',
                                'yes',
                                'true',
                                '1'
                            ]
                            .includes(
                                normalized
                            )
                        ) {

                            return '1';
                        }


                        if (
                            [
                                'no',
                                'false',
                                '0'
                            ]
                            .includes(
                                normalized
                            )
                        ) {

                            return '0';
                        }


                        row.importWarnings
                            .push(
                                `${attribute.name}: "${raw}" no es Sí/No.`
                            );


                        return '';
                    }


                    return raw;
                },


                parseCsvLine(
                    line,
                    delimiter
                ) {

                    const result = [];

                    let current = '';

                    let quoted =
                        false;


                    for (
                        let index = 0; index < line.length; index++
                    ) {

                        const char =
                            line[
                                index
                            ];


                        if (
                            char === '"'
                        ) {

                            if (
                                quoted &&
                                line[
                                    index + 1
                                ] === '"'
                            ) {

                                current +=
                                    '"';

                                index++;

                            } else {

                                quoted = !quoted;
                            }


                            continue;
                        }


                        if (
                            char === delimiter &&
                            !quoted
                        ) {

                            result.push(
                                current
                            );

                            current =
                                '';

                            continue;
                        }


                        current +=
                            char;
                    }


                    result.push(
                        current
                    );


                    return result;
                },


                /*
                |--------------------------------------------------------------------------
                | DRAFT
                |--------------------------------------------------------------------------
                */

                registerAutoDraft() {

                    window.addEventListener(
                        'beforeunload',
                        () => {

                            this.saveDraft(
                                false
                            );
                        }
                    );
                },


                draftData() {

                    return {

                        batchName: this.batchName,

                        entityTypeId: this.entityTypeId,

                        status: this.status,

                        visibility: this.visibility,

                        allowCloning: this.allowCloning,

                        duplicateStrategy: this.duplicateStrategy,

                        collectionIds: this.collectionIds,

                        selectedAttributeIds: this.selectedAttributeIds,

                        commonAttributeIds: this.commonAttributeIds,

                        commonValues: this.commonValues,

                        rows: this.rows
                            .map(
                                row => ({

                                    key: row.key,

                                    name: row.name,

                                    description: row.description,

                                    entity_type_id: row.entity_type_id,

                                    attributes: row.attributes,

                                    importWarnings: row.importWarnings,
                                })
                            ),
                    };
                },


                saveDraft(
                    notify = true
                ) {

                    localStorage.setItem(
                        draftKey,

                        JSON.stringify(
                            this.draftData()
                        )
                    );


                    if (notify) {

                        alert(
                            'Borrador guardado en este navegador.'
                        );
                    }
                },


                readDraft() {

                    try {

                        const raw =
                            localStorage.getItem(
                                draftKey
                            );


                        return raw ?
                            JSON.parse(
                                raw
                            ) :
                            null;

                    } catch (
                        error
                    ) {

                        return null;
                    }
                },


                restoreDraft(
                    draft
                ) {

                    this.batchName =
                        draft.batchName ??
                        '';

                    this.entityTypeId =
                        String(
                            draft.entityTypeId ??
                            ''
                        );

                    this.status =
                        draft.status ??
                        'ACTIVE';

                    this.visibility =
                        draft.visibility ??
                        'PUBLIC';

                    this.allowCloning =
                        draft.allowCloning ??
                        true;

                    this.duplicateStrategy =
                        draft.duplicateStrategy ??
                        'create';

                    this.collectionIds =
                        (
                            draft.collectionIds ??
                            []
                        )
                        .map(
                            String
                        );

                    this.selectedAttributeIds =
                        (
                            draft.selectedAttributeIds ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonAttributeIds =
                        (
                            draft.commonAttributeIds ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonValues =
                        draft.commonValues ??
                        {};


                    this.rows =
                        (
                            draft.rows ??
                            []
                        )
                        .map(
                            row =>
                            this.newRow(
                                row
                            )
                        );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            5
                        );
                    }
                },


                clearDraft() {

                    localStorage.removeItem(
                        draftKey
                    );


                    alert(
                        'Borrador eliminado.'
                    );
                },


                /*
                |--------------------------------------------------------------------------
                | OLD
                |--------------------------------------------------------------------------
                */

                restoreOld(
                    old
                ) {

                    this.batchName =
                        old.batch_name ??
                        '';

                    this.entityTypeId =
                        String(
                            old.entity_type_id ??
                            ''
                        );

                    this.status =
                        old.status ??
                        'ACTIVE';

                    this.visibility =
                        old.visibility ??
                        'PUBLIC';

                    this.allowCloning = !!old.allow_cloning;

                    this.duplicateStrategy =
                        old.duplicate_strategy ??
                        'create';

                    this.collectionIds =
                        (
                            old.collection_ids ??
                            []
                        )
                        .map(
                            String
                        );

                    this.selectedAttributeIds =
                        (
                            old.selected_attribute_ids ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonAttributeIds =
                        (
                            old.common_attribute_ids ??
                            []
                        )
                        .map(
                            String
                        );

                    this.commonValues =
                        old.common_attributes ??
                        {};


                    this.rows =
                        Object
                        .entries(
                            old.rows ??
                            {}
                        )
                        .map(
                            (
                                [
                                    key,
                                    row
                                ]
                            ) =>
                            this.newRow({

                                key: key,

                                name: row.name ??
                                    '',

                                description: row.description ??
                                    '',

                                entity_type_id: row.entity_type_id ??
                                    '',

                                attributes: row.attributes ??
                                    {},
                            })
                        );


                    if (
                        this.rows.length === 0
                    ) {

                        this.addRows(
                            5
                        );
                    }
                },


                /*
                |--------------------------------------------------------------------------
                | SUBMIT
                |--------------------------------------------------------------------------
                */

                prepareSubmit(
                    event
                ) {

                    if (
                        this.readyCount === 0
                    ) {

                        event.preventDefault();


                        alert(
                            'Debes ingresar al menos una Entidad con nombre.'
                        );


                        return;
                    }


                    /*
                     * Guardar estado por si el backend
                     * devuelve un error.
                     */
                    this.saveDraft(
                        false
                    );
                },


                /*
                |--------------------------------------------------------------------------
                | NORMALIZE
                |--------------------------------------------------------------------------
                */

                normalize(
                    value
                ) {

                    return thisNormalize(
                        value
                    );
                }
            };


            /*
            |--------------------------------------------------------------------------
            | Helper fuera del objeto
            |--------------------------------------------------------------------------
            */

            function thisNormalize(
                value
            ) {

                return String(
                        value ??
                        ''
                    )
                    .normalize(
                        'NFD'
                    )
                    .replace(
                        /[\u0300-\u036f]/g,
                        ''
                    )
                    .toLowerCase()
                    .replace(
                        /[^a-z0-9]+/g,
                        '-'
                    )
                    .replace(
                        /^-+|-+$/g,
                        ''
                    );
            }
        }
    </script>

</x-app-layout>

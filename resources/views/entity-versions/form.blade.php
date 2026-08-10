<x-app-layout>

    <x-slot name="header">
        Versiones de {{ $entity->name }}
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    @php

        $editing = $entityVersion !== null;

        $initialMode = old('definition_mode', $creationDefaults['definition_mode'] ?? 'EXISTING');

        $initialImageSource = old('image_source', $entity->image ? 'ENTITY' : 'UPLOAD');

        $initialSelectedVersion = old('version_id', $entityVersion?->version_id ?? '');

        $initialManualParent = old(
            'parent_entity_version_id',
            $entityVersion?->parent_entity_version_id ?? ($creationDefaults['parent_entity_version_id'] ?? ''),
        );
    @endphp


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <form method="POST" enctype="multipart/form-data"
        action="{{ $editing
            ? route('entity-versions.update', [$entity, $entityVersion])
            : route('entity-versions.store', $entity) }}"
        x-data="entityVersionBuilder({
        
            editing: @js($editing),
        
            entityName: @js($entity->name),
        
            entityImageUrl: @js($entity->image_url),
        
            versions: @js($versionPayload),
        
            entityVersions: @js($entityVersionPayload),
        
            catalogs: @js($catalogPayload),
        
            initialMode: @js($initialMode),
        
            initialVersionId: @js((string) $initialSelectedVersion),
        
            initialImageSource: @js($initialImageSource),
        
            initialImageUrl: @js($entityVersion?->image_url ?? $entity->image_url),
        
            initialParent: @js((string) $initialManualParent),
        
            initialNewParent: @js((string) old('new_version_parent_id', $creationDefaults['new_version_parent_id'] ?? '')),
        })" class="
            space-y-6
        ">

        @csrf


        @if ($editing)
            @method('PUT')
        @endif


        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}

        <section
            class="
                overflow-hidden
                rounded-3xl
                bg-gradient-to-br
                from-indigo-950
                via-violet-950
                to-fuchsia-950
                p-6
                text-white
                shadow-xl
                sm:p-8
            ">

            <div
                class="
                    flex
                    flex-col
                    gap-5
                    lg:flex-row
                    lg:items-start
                    lg:justify-between
                ">

                <div>

                    <p
                        class="
                            font-mono
                            text-[10px]
                            font-black
                            uppercase
                            tracking-widest
                            text-violet-300
                        ">
                        {{ $previewCode }}
                    </p>


                    <h1
                        class="
                            mt-2
                            text-3xl
                            font-black
                            tracking-tight
                            sm:text-4xl
                        ">
                        {{ $editing ? 'Editar versión' : 'Crear una versión' }}
                    </h1>


                    <p
                        class="
                            mt-2
                            text-xl
                            font-black
                            text-violet-200
                        ">
                        {{ $entity->name }}
                    </p>


                    <p
                        class="
                            mt-4
                            max-w-3xl
                            text-sm
                            leading-6
                            text-white/65
                        ">
                        {{ $editing
                            ? 'Modifica la representación concreta de esta Entidad.'
                            : 'Puedes reutilizar una definición existente o crear una nueva sin abandonar esta pantalla.' }}
                    </p>

                </div>


                <a href="{{ route('entity-versions.index', $entity) }}"
                    class="
                        rounded-xl
                        bg-white/10
                        px-4
                        py-2.5
                        text-center
                        text-xs
                        font-black
                        text-white
                        backdrop-blur
                        transition
                        hover:bg-white/20
                    ">
                    ← Volver
                </a>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ERRORES --}}
        {{-- ===================================================== --}}

        @if ($errors->any())

            <section
                class="
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    p-5
                ">

                <p
                    class="
                        text-sm
                        font-black
                        text-red-700
                    ">
                    Hay información que debes revisar:
                </p>


                <ul
                    class="
                        mt-3
                        list-disc
                        space-y-1
                        pl-5
                        text-xs
                        text-red-600
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
        {{-- PASO 1 — DEFINICIÓN --}}
        {{-- ===================================================== --}}

        <section
            class="
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
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-500
                    ">
                    Paso 1
                </p>


                <h2
                    class="
                        mt-1
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    ¿Qué representa esta versión?
                </h2>


                <p
                    class="
                        mt-2
                        max-w-3xl
                        text-sm
                        leading-6
                        text-slate-500
                    ">
                    Una definición permite reutilizar el mismo
                    contexto en muchas Entidades.
                </p>

            </div>


            @if (!$editing)

                <input type="hidden" name="definition_mode" :value="mode">


                <div
                    class="
                        mt-6
                        grid
                        gap-3
                        md:grid-cols-3
                    ">

                    {{-- EXISTENTE --}}
                    <button type="button" @click="mode = 'EXISTING'"
                        class="
                            rounded-2xl
                            border
                            p-5
                            text-left
                            transition
                        "
                        :class="mode === 'EXISTING'
                            ?
                            'border-indigo-400 bg-indigo-50 ring-2 ring-indigo-100' :
                            'border-slate-200 hover:border-indigo-200'">

                        <div
                            class="
                                flex
                                h-10
                                w-10
                                items-center
                                justify-center
                                rounded-xl
                                bg-indigo-100
                                text-lg
                                text-indigo-700
                            ">
                            ◈
                        </div>

                        <p
                            class="
                                mt-4
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            Usar existente
                        </p>

                        <p
                            class="
                                mt-1
                                text-[10px]
                                leading-5
                                text-slate-500
                            ">
                            Shippuden, Boruto, Clásico...
                            ya creadas anteriormente.
                        </p>

                    </button>


                    {{-- SHARED --}}
                    <button type="button" @click="mode = 'NEW_SHARED'"
                        class="
                            rounded-2xl
                            border
                            p-5
                            text-left
                            transition
                        "
                        :class="mode === 'NEW_SHARED'
                            ?
                            'border-violet-400 bg-violet-50 ring-2 ring-violet-100' :
                            'border-slate-200 hover:border-violet-200'">

                        <div
                            class="
                                flex
                                h-10
                                w-10
                                items-center
                                justify-center
                                rounded-xl
                                bg-violet-100
                                text-lg
                                text-violet-700
                            ">
                            ✦
                        </div>

                        <p
                            class="
                                mt-4
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            Nueva compartida
                        </p>

                        <p
                            class="
                                mt-1
                                text-[10px]
                                leading-5
                                text-slate-500
                            ">
                            Crea aquí una definición que luego
                            podrán utilizar otras Entidades.
                        </p>

                    </button>


                    {{-- EXCLUSIVE --}}
                    <button type="button" @click="mode = 'NEW_EXCLUSIVE'"
                        class="
                            rounded-2xl
                            border
                            p-5
                            text-left
                            transition
                        "
                        :class="mode === 'NEW_EXCLUSIVE'
                            ?
                            'border-fuchsia-400 bg-fuchsia-50 ring-2 ring-fuchsia-100' :
                            'border-slate-200 hover:border-fuchsia-200'">

                        <div
                            class="
                                flex
                                h-10
                                w-10
                                items-center
                                justify-center
                                rounded-xl
                                bg-fuchsia-100
                                text-lg
                                text-fuchsia-700
                            ">
                            ◆
                        </div>

                        <p
                            class="
                                mt-4
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            Nueva exclusiva
                        </p>

                        <p
                            class="
                                mt-1
                                text-[10px]
                                leading-5
                                text-slate-500
                            ">
                            Para transformaciones únicas,
                            como Modo Baryon.
                        </p>

                    </button>

                </div>


                {{-- ================================================= --}}
                {{-- EXISTENTE --}}
                {{-- ================================================= --}}

                <div x-show="
                        mode === 'EXISTING'
                    " x-cloak
                    class="
                        mt-6
                        rounded-2xl
                        border
                        border-indigo-100
                        bg-indigo-50/30
                        p-5
                    ">

                    <div
                        class="
                            flex
                            flex-col
                            gap-3
                            md:flex-row
                            md:items-center
                            md:justify-between
                        ">

                        <div>

                            <p
                                class="
                                    text-xs
                                    font-black
                                    text-indigo-800
                                ">
                                Selecciona una definición
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-[10px]
                                    text-indigo-500
                                ">
                                Busca por nombre, código o tipo.
                            </p>

                        </div>


                        <input type="search" x-model="versionSearch" placeholder="Buscar Shippuden..."
                            class="
                                w-full
                                rounded-xl
                                border-indigo-200
                                text-sm
                                md:w-72
                            ">

                    </div>


                    <input type="hidden" name="version_id" :value="selectedVersionId">


                    <div
                        class="
                            mt-4
                            grid
                            max-h-[460px]
                            gap-3
                            overflow-y-auto
                            pr-1
                            sm:grid-cols-2
                            xl:grid-cols-3
                        ">

                        <template
                            x-for="
                                item in filteredVersions()
                            "
                            :key="item.id">

                            <button type="button"
                                @click="
                                    selectedVersionId = item.id
                                "
                                class="
                                    flex
                                    gap-3
                                    rounded-2xl
                                    border
                                    bg-white
                                    p-3
                                    text-left
                                    transition
                                "
                                :class="String(selectedVersionId) === String(item.id) ?
                                    'border-indigo-500 ring-2 ring-indigo-100' :
                                    'border-slate-200 hover:border-indigo-200'">

                                <div
                                    class="
                                        h-16
                                        w-16
                                        shrink-0
                                        overflow-hidden
                                        rounded-xl
                                        bg-slate-100
                                    ">

                                    <img x-show="item.image_url" :src="item.image_url"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
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
                                            text-slate-800
                                        "
                                        x-text="item.name"></p>


                                    <p class="
                                            mt-1
                                            font-mono
                                            text-[8px]
                                            text-slate-400
                                        "
                                        x-text="item.code"></p>


                                    <div
                                        class="
                                            mt-2
                                            flex
                                            flex-wrap
                                            gap-1
                                        ">

                                        <span
                                            class="
                                                rounded-full
                                                bg-indigo-50
                                                px-2
                                                py-1
                                                text-[7px]
                                                font-black
                                                text-indigo-600
                                            "
                                            x-text="item.kind"></span>


                                        <span
                                            class="
                                                rounded-full
                                                bg-slate-100
                                                px-2
                                                py-1
                                                text-[7px]
                                                font-black
                                                text-slate-500
                                            "
                                            x-text="`${item.usage_count} Entidades`"></span>

                                    </div>

                                </div>

                            </button>

                        </template>

                    </div>


                    <div x-show="
                            filteredVersions().length === 0
                        "
                        class="
                            mt-4
                            rounded-xl
                            border
                            border-dashed
                            border-indigo-200
                            p-5
                            text-center
                        ">

                        <p
                            class="
                                text-xs
                                font-black
                                text-indigo-700
                            ">
                            No encontramos esa definición.
                        </p>


                        <button type="button"
                            @click="
                                mode = 'NEW_SHARED';
                                newVersionName = versionSearch;
                            "
                            class="
                                mt-2
                                text-xs
                                font-black
                                text-violet-600
                                underline
                            ">
                            + Crear
                            <span
                                x-text="
                                    versionSearch || 'una nueva Versión'
                                "></span>
                        </button>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- NUEVA DEFINICIÓN --}}
                {{-- ================================================= --}}

                <div x-show="
                        mode !== 'EXISTING'
                    " x-cloak
                    class="
                        mt-6
                        space-y-5
                        rounded-2xl
                        border
                        border-violet-100
                        bg-violet-50/30
                        p-5
                    ">

                    <div
                        class="
                            grid
                            gap-4
                            md:grid-cols-2
                        ">

                        <div>
                            <label
                                class="
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                Nombre general *
                            </label>

                            <input type="text" name="new_version_name" x-model="newVersionName"
                                value="{{ old('new_version_name') }}" placeholder="Ej. Shippuden"
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                ">
                        </div>


                        <div>
                            <label
                                class="
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                Tipo
                            </label>

                            <select name="new_version_kind"
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                ">

                                @foreach ([
        'ERA' => 'Era',
        'AGE' => 'Edad',
        'FORM' => 'Forma',
        'TRANSFORMATION' => 'Transformación',
        'OUTFIT' => 'Apariencia',
        'TIMELINE' => 'Línea temporal',
        'OTHER' => 'Otra',
    ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('new_version_kind', 'OTHER') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach

                            </select>
                        </div>


                        <div>
                            <label
                                class="
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                Versión padre
                            </label>

                            <select name="new_version_parent_id" x-model="newVersionParentId"
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                ">

                                <option value="">
                                    Ninguna
                                </option>


                                @foreach ($versions as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>


                        <div>
                            <label
                                class="
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                Activación
                            </label>

                            <select name="new_version_activation_mode"
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                ">

                                <option value="BOTH">
                                    Automática y manual
                                </option>

                                <option value="AUTO">
                                    Automática
                                </option>

                                <option value="MANUAL">
                                    Manual
                                </option>

                            </select>
                        </div>


                        <div class="md:col-span-2">

                            <label
                                class="
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                Descripción general
                            </label>

                            <textarea name="new_version_description" rows="3"
                                placeholder="Qué representa esta Versión para todas las Entidades..."
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                ">{{ old('new_version_description') }}</textarea>

                        </div>

                    </div>


                    {{-- IMAGEN GENERAL --}}
                    <div
                        class="
                            rounded-2xl
                            border
                            border-violet-100
                            bg-white
                            p-4
                        ">

                        <p
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Portada general
                        </p>


                        <p
                            class="
                                mt-1
                                text-[10px]
                                text-slate-400
                            ">
                            No necesitas subir dos veces la misma imagen.
                        </p>


                        <div
                            class="
                                mt-3
                                flex
                                flex-wrap
                                gap-4
                            ">

                            <label
                                class="
                                    flex
                                    cursor-pointer
                                    items-center
                                    gap-2
                                    text-xs
                                    font-bold
                                    text-slate-600
                                ">

                                <input type="radio" name="definition_image_mode" value="SAME"
                                    x-model="definitionImageMode">

                                Usar la misma imagen

                            </label>


                            <label
                                class="
                                    flex
                                    cursor-pointer
                                    items-center
                                    gap-2
                                    text-xs
                                    font-bold
                                    text-slate-600
                                ">

                                <input type="radio" name="definition_image_mode" value="UPLOAD"
                                    x-model="definitionImageMode">

                                Usar otra portada

                            </label>

                        </div>


                        <div x-show="
                                definitionImageMode === 'UPLOAD'
                            "
                            x-cloak class="mt-4">

                            <input type="file" name="new_version_image" accept="image/jpeg,image/png,image/webp"
                                class="
                                    w-full
                                    rounded-xl
                                    border
                                    border-slate-200
                                    p-3
                                    text-xs
                                ">

                        </div>

                    </div>


                    {{-- CONTEXTO --}}
                    <div x-data="{
                        attributeId: @js((string) old('new_catalog_attribute_id', ''))
                    }"
                        class="
                            rounded-2xl
                            border
                            border-cyan-100
                            bg-cyan-50/50
                            p-4
                        ">

                        <p
                            class="
                                text-xs
                                font-black
                                text-cyan-800
                            ">
                            Contexto automático
                            <span
                                class="
                                    font-normal
                                    text-cyan-500
                                ">
                                — opcional
                            </span>
                        </p>


                        <p
                            class="
                                mt-1
                                text-[10px]
                                leading-5
                                text-cyan-600
                            ">
                            Ejemplo:
                            Anime → Naruto Shippuden
                            puede activar automáticamente
                            la definición Shippuden.
                        </p>


                        <div
                            class="
                                mt-4
                                grid
                                gap-3
                                md:grid-cols-3
                            ">

                            <select name="new_catalog_attribute_id" x-model="attributeId"
                                class="
                                    w-full
                                    rounded-xl
                                    border-cyan-200
                                    text-sm
                                ">

                                <option value="">
                                    Sin contexto
                                </option>


                                <template
                                    x-for="
                                        catalog in catalogs
                                    "
                                    :key="catalog.id">

                                    <option :value="catalog.id" x-text="catalog.name"></option>

                                </template>

                            </select>


                            <select name="new_catalog_attribute_option_id"
                                class="
                                    w-full
                                    rounded-xl
                                    border-cyan-200
                                    text-sm
                                ">

                                <option value="">
                                    Elemento...
                                </option>


                                <template
                                    x-for="
                                        option in optionsFor(
                                            attributeId
                                        )
                                    "
                                    :key="option.id">

                                    <option :value="option.id" x-text="option.name"></option>

                                </template>

                            </select>


                            <select name="new_relation_type"
                                class="
                                    w-full
                                    rounded-xl
                                    border-cyan-200
                                    text-sm
                                ">

                                <option value="ACTIVATES">
                                    Activa esta Versión
                                </option>

                                <option value="CONTEXT">
                                    Contexto
                                </option>

                                <option value="RELATED">
                                    Relacionada
                                </option>

                            </select>

                        </div>

                    </div>

                </div>
            @else
                {{-- ================================================= --}}
                {{-- EDICIÓN: DEFINICIÓN EXISTENTE --}}
                {{-- ================================================= --}}

                <div
                    class="
                        mt-6
                        grid
                        gap-4
                        md:grid-cols-2
                    ">

                    <div class="md:col-span-2">

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Definición *
                        </label>


                        <select name="version_id" required
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">

                            @foreach ($versions as $item)
                                <option value="{{ $item->id }}" @selected((string) old('version_id', $entityVersion->version_id) === (string) $item->id)>
                                    {{ $item->name }}
                                    ·
                                    {{ $item->kind_label }}
                                    ·
                                    {{ $item->scope_label }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- PASO 2 — REPRESENTACIÓN --}}
        {{-- ===================================================== --}}

        <section
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
                    tracking-wider
                    text-indigo-500
                ">
                Paso 2
            </p>


            <h2
                class="
                    mt-1
                    text-2xl
                    font-black
                    text-slate-900
                ">
                ¿Cómo se ve {{ $entity->name }}?
            </h2>


            <div
                class="
                    mt-6
                    grid
                    gap-6
                    lg:grid-cols-[310px_minmax(0,1fr)]
                ">

                {{-- IMAGE BUILDER --}}
                <div>

                    <div
                        class="
                            relative
                            aspect-square
                            overflow-hidden
                            rounded-3xl
                            border
                            border-slate-200
                            bg-slate-100
                        ">

                        <img x-show="imagePreview" :src="imagePreview"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">


                        <div x-show="! imagePreview"
                            class="
                                absolute
                                inset-0
                                flex
                                items-center
                                justify-center
                                text-6xl
                                text-slate-300
                            ">
                            ◈
                        </div>

                    </div>


                    @if (!$editing)

                        <input type="hidden" name="image_source" :value="imageSource">


                        <div
                            class="
                                mt-3
                                grid
                                gap-2
                            ">

                            <button type="button"
                                @click="
                                    imageSource = 'UPLOAD';
                                    refreshImagePreview();
                                "
                                class="
                                    rounded-xl
                                    border
                                    px-3
                                    py-2.5
                                    text-left
                                    text-xs
                                    font-black
                                "
                                :class="imageSource === 'UPLOAD'
                                    ?
                                    'border-violet-400 bg-violet-50 text-violet-700' :
                                    'border-slate-200 text-slate-500'">
                                ↑ Subir una imagen nueva
                            </button>


                            @if ($entity->image)
                                <button type="button"
                                    @click="
                                        imageSource = 'ENTITY';
                                        refreshImagePreview();
                                    "
                                    class="
                                        rounded-xl
                                        border
                                        px-3
                                        py-2.5
                                        text-left
                                        text-xs
                                        font-black
                                    "
                                    :class="imageSource === 'ENTITY'
                                        ?
                                        'border-indigo-400 bg-indigo-50 text-indigo-700' :
                                        'border-slate-200 text-slate-500'">
                                    ✦ Reutilizar imagen de {{ $entity->name }}
                                </button>
                            @endif


                            @if ($parentEntityVersions->isNotEmpty())
                                <button type="button"
                                    @click="
                                        imageSource = 'VERSION';
                                        refreshImagePreview();
                                    "
                                    class="
                                        rounded-xl
                                        border
                                        px-3
                                        py-2.5
                                        text-left
                                        text-xs
                                        font-black
                                    "
                                    :class="imageSource === 'VERSION'
                                        ?
                                        'border-fuchsia-400 bg-fuchsia-50 text-fuchsia-700' :
                                        'border-slate-200 text-slate-500'">
                                    ◈ Reutilizar otra Versión
                                </button>
                            @endif

                        </div>


                        <div x-show="
                                imageSource === 'UPLOAD'
                            "
                            class="mt-3">

                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                                @change="
                                    previewUploadedImage(
                                        $event
                                    )
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border
                                    border-slate-200
                                    p-3
                                    text-xs
                                ">

                        </div>


                        <div x-show="
                                imageSource === 'VERSION'
                            "
                            x-cloak class="mt-3">

                            <select name="source_entity_version_id" x-model="sourceEntityVersionId"
                                @change="
                                    refreshImagePreview()
                                "
                                class="
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                    text-xs
                                ">

                                <option value="">
                                    Seleccionar...
                                </option>


                                <template
                                    x-for="
                                        item in entityVersions
                                    "
                                    :key="item.id">

                                    <option :value="item.id" x-text="item.name"></option>

                                </template>

                            </select>

                        </div>
                    @else
                        <label
                            class="
                                mt-3
                                block
                                rounded-xl
                                border
                                border-slate-200
                                p-3
                                text-xs
                                font-black
                                text-slate-600
                            ">

                            Cambiar imagen

                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                                @change="
                                    previewUploadedImage(
                                        $event
                                    )
                                "
                                class="
                                    mt-2
                                    block
                                    w-full
                                    text-[10px]
                                    font-normal
                                ">

                        </label>

                    @endif

                </div>


                {{-- DATA --}}
                <div class="min-w-0">

                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Nombre
                            {{ $editing ? '*' : '— automático si lo dejas vacío' }}
                        </label>


                        <input type="text" name="name" value="{{ old('name', $entityVersion?->name) }}"
                            {{ $editing ? 'required' : '' }}
                            :placeholder="suggestedEntityVersionName()"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">


                        <p x-show="
                                ! @js($editing)
                            "
                            class="
                                mt-1
                                text-[9px]
                                text-slate-400
                            ">
                            Sugerencia:
                            <strong
                                x-text="
                                    suggestedEntityVersionName()
                                "></strong>
                        </p>

                    </div>


                    <div class="mt-4">

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Descripción específica
                        </label>


                        <textarea name="description" rows="5" placeholder="Qué cambia específicamente en esta Entidad..."
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">{{ old('description', $entityVersion?->description) }}</textarea>

                    </div>


                    {{-- HERENCIA --}}
                    <div
                        class="
                            mt-5
                            grid
                            gap-3
                            md:grid-cols-2
                        ">

                        <label
                            class="
                                flex
                                cursor-pointer
                                gap-3
                                rounded-2xl
                                border
                                border-indigo-100
                                bg-indigo-50
                                p-4
                            ">

                            <input type="hidden" name="inherit_base_attributes" value="0">

                            <input type="checkbox" name="inherit_base_attributes" value="1"
                                @checked(old('inherit_base_attributes', $entityVersion?->inherit_base_attributes ?? true))
                                class="
                                    mt-1
                                    rounded
                                    border-indigo-300
                                    text-indigo-600
                                ">

                            <span>

                                <strong
                                    class="
                                        block
                                        text-xs
                                        text-indigo-800
                                    ">
                                    Heredar características
                                </strong>

                                <small
                                    class="
                                        mt-1
                                        block
                                        text-[9px]
                                        leading-4
                                        text-indigo-500
                                    ">
                                    Solo guardarás aquello que cambia.
                                </small>

                            </span>

                        </label>


                        <label
                            class="
                                flex
                                cursor-pointer
                                gap-3
                                rounded-2xl
                                border
                                border-amber-100
                                bg-amber-50
                                p-4
                            ">

                            <input type="hidden" name="is_default" value="0">

                            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $entityVersion?->is_default ?? false))
                                class="
                                    mt-1
                                    rounded
                                    border-amber-300
                                    text-amber-600
                                ">

                            <span>

                                <strong
                                    class="
                                        block
                                        text-xs
                                        text-amber-800
                                    ">
                                    Predeterminada
                                </strong>

                                <small
                                    class="
                                        mt-1
                                        block
                                        text-[9px]
                                        leading-4
                                        text-amber-600
                                    ">
                                    Se utilizará cuando no haya otro contexto.
                                </small>

                            </span>

                        </label>

                    </div>


                    {{-- PADRE AUTOMÁTICO --}}
                    @if (!$editing)
                        <div
                            class="
                                mt-4
                                rounded-2xl
                                border
                                border-emerald-100
                                bg-emerald-50/60
                                p-4
                            ">

                            <div
                                class="
                                    flex
                                    items-start
                                    gap-3
                                ">

                                <input type="hidden" name="auto_parent" value="0">

                                <input type="checkbox" name="auto_parent" value="1" x-model="autoParent"
                                    class="
                                        mt-1
                                        rounded
                                        border-emerald-300
                                        text-emerald-600
                                    ">


                                <div>

                                    <p
                                        class="
                                            text-xs
                                            font-black
                                            text-emerald-800
                                        ">
                                        Detectar padre automáticamente
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            text-[10px]
                                            leading-5
                                            text-emerald-600
                                        ">
                                        OmniMerge buscará la representación
                                        de la definición padre dentro de
                                        {{ $entity->name }}.
                                    </p>


                                    <p x-show="
                                            suggestedParentName()
                                        "
                                        class="
                                            mt-2
                                            text-[10px]
                                            font-black
                                            text-emerald-700
                                        ">
                                        Sugerido:
                                        <span
                                            x-text="
                                                suggestedParentName()
                                            "></span>
                                    </p>

                                </div>

                            </div>

                        </div>
                    @endif

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- CONFIGURACIÓN AVANZADA --}}
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

            <button type="button"
                @click="
                    advancedOpen =
                        ! advancedOpen
                "
                class="
                    flex
                    w-full
                    items-center
                    justify-between
                    gap-4
                    p-5
                    text-left
                ">

                <div>

                    <p
                        class="
                            text-sm
                            font-black
                            text-slate-800
                        ">
                        Configuración avanzada
                    </p>

                    <p
                        class="
                            mt-1
                            text-[10px]
                            text-slate-400
                        ">
                        Jerarquía manual, prioridad,
                        orden y estado.
                    </p>

                </div>


                <span
                    class="
                        text-xl
                        text-slate-400
                    "
                    x-text="
                        advancedOpen
                            ? '−'
                            : '+'
                    "></span>

            </button>


            <div x-show="advancedOpen" x-cloak
                class="
                    border-t
                    border-slate-100
                    p-5
                ">

                <div
                    class="
                        grid
                        gap-4
                        md:grid-cols-2
                        xl:grid-cols-4
                    ">

                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Padre concreto
                        </label>


                        <select x-model="manualParentId"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">

                            <option value="">
                                Ninguno
                            </option>


                            @foreach ($parentEntityVersions as $parent)
                                <option value="{{ $parent->id }}">
                                    {{ $parent->name }}
                                </option>
                            @endforeach

                        </select>


                        <input type="hidden" name="parent_entity_version_id"
                            :value="autoParent && !editing ?
                                suggestedParentId() :
                                manualParentId">

                    </div>


                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Prioridad
                        </label>

                        <input type="number" name="priority"
                            value="{{ old('priority', $entityVersion?->priority ?? 0) }}"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">

                    </div>


                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Orden
                        </label>

                        <input type="number" name="sort_order" min="0"
                            value="{{ old('sort_order', $entityVersion?->sort_order ?? 0) }}"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">

                    </div>


                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Estado
                        </label>

                        <select name="status"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">

                            @foreach ([
        'ACTIVE' => 'Activa',
        'INACTIVE' => 'Inactiva',
        'ARCHIVED' => 'Archivada',
    ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $entityVersion?->status ?? 'ACTIVE') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- SUBMIT --}}
        {{-- ===================================================== --}}

        <div
            class="
                sticky
                bottom-4
                z-40
                flex
                flex-col
                gap-3
                rounded-2xl
                border
                border-slate-200
                bg-white/95
                p-4
                shadow-2xl
                backdrop-blur
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">

            <div>

                <p class="
                        text-xs
                        font-black
                        text-slate-700
                    "
                    x-text="
                        suggestedEntityVersionName()
                    "></p>


                <p
                    class="
                        mt-1
                        text-[9px]
                        text-slate-400
                    ">
                    La definición y la representación
                    se guardarán en una sola operación.
                </p>

            </div>


            <div class="flex gap-2">

                <a href="{{ route('entity-versions.index', $entity) }}"
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        px-5
                        py-3
                        text-sm
                        font-bold
                        text-slate-500
                    ">
                    Cancelar
                </a>


                <button type="submit"
                    class="
                        rounded-xl
                        bg-violet-600
                        px-6
                        py-3
                        text-sm
                        font-black
                        text-white
                        shadow-lg
                        shadow-violet-600/20
                        transition
                        hover:bg-violet-700
                    ">
                    {{ $editing ? 'Guardar cambios' : 'Crear Versión' }}
                </button>

            </div>

        </div>

    </form>


    <script>
        function entityVersionBuilder(
            config
        ) {

            return {

                editing:
                    !!config.editing,

                entityName: config.entityName,

                entityImageUrl: config.entityImageUrl,

                versions: config.versions ??
                    [],

                entityVersions: config.entityVersions ??
                    [],

                catalogs: config.catalogs ??
                    [],


                /*
                |--------------------------------------------------------------------------
                | Definición
                |--------------------------------------------------------------------------
                */

                mode: config.editing ?
                    'EXISTING' :
                    (
                        config.initialMode ||
                        'EXISTING'
                    ),

                selectedVersionId: String(
                    config.initialVersionId ||
                    ''
                ),

                versionSearch: '',

                newVersionName: @js(old('new_version_name', '')),

                newVersionParentId: String(
                    config.initialNewParent ||
                    ''
                ),

                definitionImageMode: @js(old('definition_image_mode', 'SAME')),


                /*
                |--------------------------------------------------------------------------
                | Imagen
                |--------------------------------------------------------------------------
                */

                imageSource: config.initialImageSource ||
                    'UPLOAD',

                imagePreview: config.initialImageUrl ||
                    null,

                uploadedPreview: null,

                sourceEntityVersionId: @js((string) old('source_entity_version_id', '')),


                /*
                |--------------------------------------------------------------------------
                | Jerarquía
                |--------------------------------------------------------------------------
                */

                autoParent: @js(old('auto_parent', true)),

                manualParentId: String(
                    config.initialParent ||
                    ''
                ),


                advancedOpen: @js($errors->has('parent_entity_version_id') || $errors->has('priority') || $errors->has('sort_order') || $errors->has('status')),


                /*
                |--------------------------------------------------------------------------
                | Buscar definiciones
                |--------------------------------------------------------------------------
                */

                filteredVersions() {

                    const query =
                        this.versionSearch
                        .trim()
                        .toLowerCase();


                    if (!query) {

                        return this.versions;
                    }


                    return this.versions.filter(
                        item => {

                            const text = [
                                    item.name,
                                    item.code,
                                    item.kind,
                                    item.scope,
                                    item.parent_name,
                                ]
                                .filter(Boolean)
                                .join(' ')
                                .toLowerCase();


                            return text.includes(
                                query
                            );
                        }
                    );
                },


                selectedDefinition() {

                    return this.versions.find(
                            item =>
                            String(item.id) ===
                            String(
                                this.selectedVersionId
                            )
                        ) ||
                        null;
                },


                definitionName() {

                    if (
                        this.mode === 'EXISTING'
                    ) {

                        return this
                            .selectedDefinition()
                            ?.name ||
                            'Versión';
                    }


                    return this.newVersionName
                        ?.trim() ||
                        'Nueva Versión';
                },


                /*
                |--------------------------------------------------------------------------
                | Nombre automático
                |--------------------------------------------------------------------------
                */

                suggestedEntityVersionName() {

                    return `${this.entityName} — ${this.definitionName()}`;
                },


                /*
                |--------------------------------------------------------------------------
                | Padre definición
                |--------------------------------------------------------------------------
                */

                definitionParentVersionId() {

                    if (
                        this.mode === 'EXISTING'
                    ) {

                        return String(
                            this
                            .selectedDefinition()
                            ?.parent_version_id ||
                            ''
                        );
                    }


                    return String(
                        this.newVersionParentId ||
                        ''
                    );
                },


                suggestedParentId() {

                    const parentVersionId =
                        this.definitionParentVersionId();


                    if (!parentVersionId) {

                        return '';
                    }


                    const parent =
                        this.entityVersions.find(
                            item =>
                            String(
                                item.version_id
                            ) ===
                            String(
                                parentVersionId
                            )
                        );


                    return parent ?
                        String(parent.id) :
                        '';
                },


                suggestedParentName() {

                    const id =
                        this.suggestedParentId();


                    if (!id) {
                        return '';
                    }


                    return this.entityVersions.find(
                            item =>
                            String(item.id) ===
                            String(id)
                        )
                        ?.name ||
                        '';
                },


                /*
                |--------------------------------------------------------------------------
                | Imagen
                |--------------------------------------------------------------------------
                */

                previewUploadedImage(
                    event
                ) {

                    const file =
                        event.target.files
                        ?.[
                            0
                        ];


                    if (!file) {
                        return;
                    }


                    if (
                        this.uploadedPreview &&
                        this.uploadedPreview
                        .startsWith(
                            'blob:'
                        )
                    ) {

                        URL.revokeObjectURL(
                            this.uploadedPreview
                        );
                    }


                    this.uploadedPreview =
                        URL.createObjectURL(
                            file
                        );


                    this.imageSource =
                        'UPLOAD';


                    this.imagePreview =
                        this.uploadedPreview;
                },


                refreshImagePreview() {

                    if (
                        this.imageSource ===
                        'UPLOAD'
                    ) {

                        this.imagePreview =
                            this.uploadedPreview;

                        return;
                    }


                    if (
                        this.imageSource ===
                        'ENTITY'
                    ) {

                        this.imagePreview =
                            this.entityImageUrl;

                        return;
                    }


                    const selected =
                        this.entityVersions.find(
                            item =>
                            String(item.id) ===
                            String(
                                this.sourceEntityVersionId
                            )
                        );


                    this.imagePreview =
                        selected
                        ?.image_url ||
                        null;
                },


                /*
                |--------------------------------------------------------------------------
                | Catálogos
                |--------------------------------------------------------------------------
                */

                optionsFor(
                    attributeId
                ) {

                    return this.catalogs.find(
                            item =>
                            String(item.id) ===
                            String(attributeId)
                        )
                        ?.options ||
                        [];
                },
            };
        }
    </script>

</x-app-layout>

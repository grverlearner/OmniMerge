<x-app-layout>

    <x-slot name="header">
        Versiones
    </x-slot>


    @include('entities.partials.section-navigation')


    @php

        $editing = $version !== null;

        $initialLinks = old('catalog_links', $linkPayload);

    @endphp


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <div x-data="versionForm({
    
        catalogs: @js($catalogPayload),
    
        initialLinks: @js($initialLinks),
    
        initialImageUrl: @js($version?->image_url)
    })">

        <form method="POST" enctype="multipart/form-data"
            action="{{ $editing ? route('versions.update', $version) : route('versions.store') }}"
            class="
                space-y-6
            ">

            @csrf

            @if ($editing)
                @method('PUT')
            @endif


            {{-- HERO --}}
            <section
                class="
                    rounded-3xl
                    bg-gradient-to-br
                    from-violet-950
                    via-indigo-950
                    to-slate-950
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
                            ">
                            {{ $editing ? 'Editar Versión' : 'Nueva Versión' }}
                        </h1>

                        <p
                            class="
                                mt-3
                                max-w-2xl
                                text-sm
                                leading-6
                                text-slate-300
                            ">
                            Define un contexto reutilizable:
                            era, forma, transformación,
                            apariencia o línea temporal.
                        </p>
                    </div>


                    <a href="{{ $editing ? route('versions.show', $version) : route('versions.index') }}"
                        class="
                            rounded-xl
                            bg-white/10
                            px-4
                            py-2.5
                            text-sm
                            font-bold
                        ">
                        ← Volver
                    </a>

                </div>

            </section>


            @if ($errors->any())

                <div
                    class="
                        rounded-2xl
                        border
                        border-red-200
                        bg-red-50
                        p-5
                    ">
                    <ul
                        class="
                            list-disc
                            space-y-1
                            pl-5
                            text-sm
                            text-red-700
                        ">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            @endif


            {{-- IDENTIDAD --}}
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
                        text-violet-500
                    ">
                    1 · Identidad
                </p>


                <div
                    class="
                        mt-5
                        grid
                        gap-5
                        lg:grid-cols-[280px_minmax(0,1fr)]
                    ">

                    {{-- IMAGE --}}
                    <div>

                        {{-- PREVIEW --}}
                        <div
                            class="
            relative
            aspect-square
            overflow-hidden
            rounded-3xl
            border
            border-slate-200
            bg-gradient-to-br
            from-violet-50
            via-indigo-50
            to-slate-100
        ">

                            {{-- IMAGEN --}}
                            <template x-if="
                imagePreview
            ">

                                <img :src="imagePreview" alt="Vista previa"
                                    class="
                    h-full
                    w-full
                    object-cover
                ">

                            </template>


                            {{-- VACÍO --}}
                            <div x-show="
                ! imagePreview
            "
                                class="
                absolute
                inset-0
                flex
                flex-col
                items-center
                justify-center
                gap-3
                p-6
                text-center
            ">

                                <div
                                    class="
                    flex
                    h-20
                    w-20
                    items-center
                    justify-center
                    rounded-3xl
                    bg-violet-100
                    text-5xl
                    text-violet-300
                ">
                                    ◈
                                </div>


                                <div>

                                    <p
                                        class="
                        text-sm
                        font-black
                        text-slate-600
                    ">
                                        Imagen de la Versión
                                    </p>


                                    <p
                                        class="
                        mt-1
                        text-[10px]
                        leading-4
                        text-slate-400
                    ">
                                        La previsualización aparecerá aquí
                                        cuando selecciones un archivo.
                                    </p>

                                </div>

                            </div>


                            {{-- CAMBIAR --}}
                            <label
                                class="
                absolute
                bottom-3
                left-3
                right-3
                cursor-pointer
                rounded-xl
                bg-slate-950/80
                px-4
                py-3
                text-center
                text-xs
                font-black
                text-white
                shadow-lg
                backdrop-blur
                transition
                hover:bg-slate-950
            ">

                                <span
                                    x-text="
                    imagePreview
                        ? 'Cambiar imagen'
                        : 'Seleccionar imagen'
                "></span>


                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                                    {{ $editing ? '' : 'required' }}
                                    @change="
                    previewImage(
                        $event
                    )
                "
                                    class="
                    hidden
                ">

                            </label>

                        </div>


                        {{-- NOMBRE ARCHIVO --}}
                        <div x-show="
            selectedImageName
        " x-cloak
                            class="
            mt-3
            flex
            items-center
            gap-3
            rounded-xl
            border
            border-emerald-200
            bg-emerald-50
            p-3
        ">

                            <div
                                class="
                flex
                h-8
                w-8
                shrink-0
                items-center
                justify-center
                rounded-lg
                bg-emerald-100
                text-emerald-600
            ">
                                ✓
                            </div>


                            <div class="
                min-w-0
                flex-1
            ">

                                <p
                                    class="
                    text-[9px]
                    font-black
                    uppercase
                    text-emerald-600
                ">
                                    Archivo seleccionado
                                </p>


                                <p class="
                    mt-0.5
                    truncate
                    text-xs
                    font-bold
                    text-emerald-800
                "
                                    x-text="
                    selectedImageName
                "></p>

                            </div>

                        </div>


                        <div
                            class="
            mt-3
            rounded-xl
            bg-slate-50
            p-3
        ">

                            <p
                                class="
                text-[9px]
                leading-5
                text-slate-500
            ">
                                <strong>
                                    Formatos:
                                </strong>

                                JPG, JPEG, PNG o WEBP.

                                <br>

                                <strong>
                                    Tamaño máximo:
                                </strong>

                                2 MB.
                            </p>


                            <p
                                class="
                mt-2
                text-[9px]
                leading-5
                text-slate-400
            ">
                                Esta es la imagen representativa general de la Versión.
                                Cada Entidad asociada tendrá posteriormente
                                su propia imagen específica.
                            </p>

                        </div>

                    </div>


                    <div
                        class="
                            grid
                            content-start
                            gap-4
                        ">

                        <div>
                            <label
                                class="
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                Nombre *
                            </label>

                            <input type="text" name="name" value="{{ old('name', $version?->name) }}" required
                                placeholder="Ej. Shippuden"
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
                                Descripción
                            </label>

                            <textarea name="description" rows="6"
                                class="
                                    mt-2
                                    w-full
                                    rounded-xl
                                    border-slate-300
                                "
                                placeholder="Describe qué representa esta Versión...">{{ old('description', $version?->description) }}</textarea>
                        </div>

                    </div>

                </div>

            </section>


            {{-- CLASIFICACIÓN --}}
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
                        text-indigo-500
                    ">
                    2 · Clasificación y jerarquía
                </p>


                <div
                    class="
                        mt-5
                        grid
                        gap-4
                        md:grid-cols-2
                        xl:grid-cols-3
                    ">

                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Tipo
                        </label>

                        <select name="version_kind"
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
                                <option value="{{ $value }}" @selected(old('version_kind', $version?->version_kind ?? 'OTHER') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Ámbito
                        </label>

                        <select name="scope"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                            <option value="SHARED" @selected(old('scope', $version?->scope ?? 'SHARED') === 'SHARED')>
                                Compartida
                            </option>

                            <option value="EXCLUSIVE" @selected(old('scope', $version?->scope ?? 'SHARED') === 'EXCLUSIVE')>
                                Exclusiva
                            </option>
                        </select>
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Activación
                        </label>

                        <select name="activation_mode"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                            @foreach ([
        'AUTO' => 'Automática',
        'MANUAL' => 'Manual',
        'BOTH' => 'Automática y manual',
    ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('activation_mode', $version?->activation_mode ?? 'BOTH') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Versión padre
                        </label>

                        <select name="parent_version_id"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                            <option value="">
                                Ninguna
                            </option>

                            @foreach ($parentVersions as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_version_id', $version?->parent_version_id) === (string) $parent->id)>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Prioridad
                        </label>

                        <input type="number" name="priority" value="{{ old('priority', $version?->priority ?? 0) }}"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Orden
                        </label>

                        <input type="number" name="sort_order" min="0"
                            value="{{ old('sort_order', $version?->sort_order ?? 0) }}"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
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
                                <option value="{{ $value }}" @selected(old('status', $version?->status ?? 'ACTIVE') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </section>


            {{-- CATÁLOGOS --}}
            <section
                class="
                    rounded-3xl
                    border
                    border-violet-200
                    bg-violet-50/30
                    p-6
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
                                text-[10px]
                                font-black
                                uppercase
                                text-violet-500
                            ">
                            3 · Contexto
                        </p>

                        <h2
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-slate-900
                            ">
                            Relación con Catálogos
                        </h2>

                        <p
                            class="
                                mt-2
                                text-xs
                                text-slate-500
                            ">
                            Es opcional. Estas relaciones podrán ser
                            utilizadas por el futuro VersionResolver.
                        </p>
                    </div>


                    <button type="button" @click="addLink()"
                        class="
                            rounded-xl
                            bg-violet-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        + Relación
                    </button>

                </div>


                <div class="
                        mt-5
                        space-y-3
                    ">

                    <template
                        x-for="
                            (link, index)
                            in links
                        "
                        :key="link.key">

                        <article
                            class="
                                grid
                                gap-3
                                rounded-2xl
                                border
                                border-violet-100
                                bg-white
                                p-4
                                lg:grid-cols-2
                                xl:grid-cols-4
                            ">

                            <div>
                                <label
                                    class="
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Atributo
                                </label>

                                <select x-model="link.attribute_id"
                                    @change="
                                        link.attribute_option_id = ''
                                    "
                                    :name="`catalog_links[${index}][attribute_id]`"
                                    class="
                                        mt-1
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
                                            catalog
                                            in catalogs
                                        "
                                        :key="catalog.id">
                                        <option :value="catalog.id" x-text="catalog.name"></option>
                                    </template>
                                </select>
                            </div>


                            <div>
                                <label
                                    class="
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Elemento
                                </label>

                                <select
                                    x-model="
                                        link.attribute_option_id
                                    "
                                    :name="`catalog_links[${index}][attribute_option_id]`"
                                    class="
                                        mt-1
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
                                            option
                                            in optionsFor(
                                                link.attribute_id
                                            )
                                        "
                                        :key="option.id">
                                        <option :value="option.id" x-text="option.name"></option>
                                    </template>
                                </select>
                            </div>


                            <div>
                                <label
                                    class="
                                        text-[9px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Relación
                                </label>

                                <select
                                    x-model="
                                        link.relation_type
                                    "
                                    :name="`catalog_links[${index}][relation_type]`"
                                    class="
                                        mt-1
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-xs
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


                            <div
                                class="
                                    flex
                                    items-end
                                    gap-2
                                ">

                                <div class="flex-1">
                                    <label
                                        class="
                                            text-[9px]
                                            font-black
                                            uppercase
                                            text-slate-400
                                        ">
                                        Grupo
                                    </label>

                                    <input type="number" min="1"
                                        x-model="
                                            link.condition_group
                                        "
                                        :name="`catalog_links[${index}][condition_group]`"
                                        class="
                                            mt-1
                                            w-full
                                            rounded-xl
                                            border-slate-300
                                            text-xs
                                        ">
                                </div>


                                <div class="flex-1">
                                    <label
                                        class="
                                            text-[9px]
                                            font-black
                                            uppercase
                                            text-slate-400
                                        ">
                                        Lógica
                                    </label>

                                    <select
                                        x-model="
                                            link.logical_operator
                                        "
                                        :name="`catalog_links[${index}][logical_operator]`"
                                        class="
                                            mt-1
                                            w-full
                                            rounded-xl
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


                                <input type="hidden" :name="`catalog_links[${index}][priority]`" value="0">


                                <input type="hidden" :name="`catalog_links[${index}][is_required]`" value="0">


                                <button type="button"
                                    @click="
                                        links.splice(
                                            index,
                                            1
                                        )
                                    "
                                    class="
                                        rounded-xl
                                        bg-red-50
                                        px-3
                                        py-2.5
                                        font-black
                                        text-red-500
                                    ">
                                    ×
                                </button>

                            </div>

                        </article>

                    </template>


                    <div x-show="links.length === 0"
                        class="
                            rounded-2xl
                            border
                            border-dashed
                            border-violet-200
                            p-8
                            text-center
                            text-xs
                            text-violet-400
                        ">
                        Esta Versión no está vinculada a ningún Catálogo.
                        Esto es completamente válido.
                    </div>

                </div>

            </section>


            <div
                class="
                    flex
                    justify-end
                    gap-3
                ">
                <a href="{{ $editing ? route('versions.show', $version) : route('versions.index') }}"
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
                </a>

                <button
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
                    ">
                    {{ $editing ? 'Guardar cambios' : 'Crear Versión' }}
                </button>
            </div>

        </form>

    </div>


    <script>
        function versionForm(
            config
        ) {
            return {
                imagePreview: config.initialImageUrl ?? null,

                selectedImageName: '',

                catalogs: config.catalogs ?? [],

                links: (
                        config.initialLinks ?? []
                    )
                    .map(
                        (link, index) => ({

                            key: `link-${Date.now()}-${index}`,

                            attribute_id: String(
                                link.attribute_id ??
                                ''
                            ),

                            attribute_option_id: String(
                                link.attribute_option_id ??
                                ''
                            ),

                            relation_type: link.relation_type ??
                                'ACTIVATES',

                            condition_group: link.condition_group ??
                                1,

                            logical_operator: link.logical_operator ??
                                'AND',

                            priority: link.priority ??
                                0,

                            is_required:
                                !!link.is_required,
                        })
                    ),

                /*
    |--------------------------------------------------------------------------
    | Preview de imagen
    |--------------------------------------------------------------------------
    */

                previewImage(
                    event
                ) {

                    const input =
                        event.target;


                    if (
                        !input.files ||
                        !input.files.length
                    ) {

                        this.selectedImageName =
                            '';

                        return;
                    }


                    const file =
                        input.files[
                            0
                        ];


                    /*
                    |--------------------------------------------------------------------------
                    | Validación básica en navegador
                    |--------------------------------------------------------------------------
                    */

                    const validTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ];


                    if (
                        !validTypes.includes(
                            file.type
                        )
                    ) {

                        alert(
                            'Selecciona una imagen JPG, PNG o WEBP.'
                        );


                        input.value =
                            '';


                        this.selectedImageName =
                            '';


                        return;
                    }


                    /*
                     * 2 MB
                     */
                    const maxSize =
                        2 *
                        1024 *
                        1024;


                    if (
                        file.size >
                        maxSize
                    ) {

                        alert(
                            'La imagen no puede superar los 2 MB.'
                        );


                        input.value =
                            '';


                        this.selectedImageName =
                            '';


                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Revocar Blob anterior
                    |--------------------------------------------------------------------------
                    */

                    if (
                        this.imagePreview &&
                        this.imagePreview.startsWith(
                            'blob:'
                        )
                    ) {

                        URL.revokeObjectURL(
                            this.imagePreview
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Mostrar preview
                    |--------------------------------------------------------------------------
                    */

                    this.imagePreview =
                        URL.createObjectURL(
                            file
                        );


                    this.selectedImageName =
                        file.name;
                },


                addLink() {
                    this.links.push({
                        key: `link-${Date.now()}-${Math.random()}`,

                        attribute_id: '',

                        attribute_option_id: '',

                        relation_type: 'ACTIVATES',

                        condition_group: 1,

                        logical_operator: 'AND',

                        priority: 0,

                        is_required: false,
                    });
                },


                optionsFor(
                    attributeId
                ) {
                    return this.catalogs
                        .find(
                            catalog =>
                            String(
                                catalog.id
                            ) ===
                            String(
                                attributeId
                            )
                        )
                        ?.options ?? [];
                }
            };
        }
    </script>

</x-app-layout>

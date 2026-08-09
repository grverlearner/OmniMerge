@php

    $editing = isset($entity) && $entity->exists;

    $currentName = old('name', $entity->name ?? '');

    $currentType = (string) old('entity_type_id', $entity->entity_type_id ?? request('type', ''));

    $selectedCollections = old(
        'collection_ids',
        $editing ? $entity->collections->pluck('id')->map(fn($id) => (string) $id)->all() : [],
    );

    $selectedCollections = array_map('strval', $selectedCollections);
@endphp


<div x-data="{

    name: @js($currentName),

    selectedType: @js($currentType),

    typeSearch: '',

    collectionSearch: '',

    imagePreview: @js($editing ? $entity->image_url : null),

    removeImage: false,


    previewImage(event) {

        const file =
            event.target.files[0];


        if (!file) {
            return;
        }


        this.removeImage =
            false;


        const reader =
            new FileReader();


        reader.onload =
            event => {

                this.imagePreview =
                    event.target.result;

            };


        reader.readAsDataURL(
            file
        );
    },


    clearImage() {

        this.imagePreview =
            null;

        this.removeImage =
            true;


        if (
            this.$refs.imageInput
        ) {

            this.$refs.imageInput.value =
                '';
        }
    },


    slugify(value) {

        return value
            .toString()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            )
            .toLowerCase()
            .trim()
            .replace(
                /[^a-z0-9]+/g,
                '-'
            )
            .replace(
                /^-+|-+$/g,
                ''
            );
    }
}" class="
        grid
        gap-8
        xl:grid-cols-[minmax(0,1fr)_320px]
    ">

    <div class="space-y-10">

        {{-- ===================================================== --}}
        {{-- 1. IDENTIDAD --}}
        {{-- ===================================================== --}}

        <section>

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-600
                ">
                1 · Identidad
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Información principal
            </h3>


            <div
                class="
                    mt-6
                    grid
                    gap-5
                    lg:grid-cols-2
                ">

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


                    <input name="name" type="text" x-model="name" value="{{ $currentName }}" required
                        placeholder="Ejemplo: Naruto Uzumaki"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:ring-indigo-500
                        ">

                </div>


                {{-- CODE --}}
                <div>

                    <label
                        class="
                            mb-2
                            block
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Código OmniMerge
                    </label>


                    <div
                        class="
                            flex
                            h-[42px]
                            items-center
                            rounded-xl
                            border
                            border-slate-200
                            bg-slate-100
                            px-4
                        ">

                        <span
                            class="
                                font-mono
                                text-sm
                                font-black
                                text-slate-700
                            ">
                            {{ $editing ? $entity->code : $previewCode }}
                        </span>


                        <span
                            class="
                                ml-auto
                                rounded-full
                                bg-slate-200
                                px-2
                                py-1
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-500
                            ">
                            Automático
                        </span>

                    </div>

                </div>


                {{-- SLUG --}}
                <div class="lg:col-span-2">

                    <label
                        class="
                            mb-2
                            block
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Identificador URL
                    </label>


                    <div
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            bg-slate-50
                            px-4
                            py-3
                        ">

                        @if ($editing)
                            <span
                                class="
                                    font-mono
                                    text-sm
                                    font-bold
                                    text-slate-700
                                ">
                                {{ $entity->slug }}
                            </span>
                        @else
                            <span
                                x-text="
                                    slugify(name)
                                    || 'se-generara-al-guardar'
                                "
                                class="
                                    font-mono
                                    text-sm
                                    font-bold
                                    text-slate-700
                                "></span>
                        @endif

                    </div>


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        ">
                        Código y URL se mantienen estables después
                        de crear la entidad.
                    </p>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 2. TIPO --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-600
                ">
                2 · Clasificación
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Tipo de entidad
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Opcional. Sirve para clasificar la creación
                como personaje, país, objeto, criatura, etc.
            </p>


            <input type="hidden" name="entity_type_id" :value="selectedType">


            <input type="text" x-model="typeSearch" placeholder="Buscar tipo..."
                class="
                    mt-5
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-white
                    text-slate-900
                    placeholder:text-slate-400
                ">


            <div
                class="
                    mt-4
                    grid
                    max-h-[430px]
                    gap-3
                    overflow-y-auto
                    pr-1
                    sm:grid-cols-2
                    lg:grid-cols-3
                ">

                {{-- SIN TIPO --}}
                <button type="button"
                    x-show="
                        ! typeSearch
                        ||
                        'sin tipo'
                            .includes(
                                typeSearch.toLowerCase()
                            )
                    "
                    @click="
                        selectedType = ''
                    "
                    :class="selectedType === ''
                    
                        ?
                        'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                    
                        :
                        'border-slate-200 bg-white'"
                    class="
                        flex
                        items-center
                        gap-3
                        rounded-2xl
                        border
                        p-3
                        text-left
                    ">

                    <div
                        class="
                            flex
                            h-14
                            w-14
                            items-center
                            justify-center
                            rounded-xl
                            bg-slate-100
                            text-xl
                            font-black
                            text-slate-400
                        ">
                        ?
                    </div>


                    <div>

                        <p
                            class="
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            Sin tipo
                        </p>

                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-400
                            ">
                            Clasificar después
                        </p>

                    </div>

                </button>


                @foreach ($entityTypes as $type)
                    <button type="button"
                        x-show="
                            ! typeSearch
                            ||
                            @js(mb_strtolower($type->name . ' ' . $type->code)).includes(
                                typeSearch.toLowerCase()
                            )
                        "
                        @click="
                            selectedType =
                                '{{ $type->id }}'
                        "
                        :class="selectedType === '{{ $type->id }}'
                        
                            ?
                            'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                        
                            :
                            'border-slate-200 bg-white'"
                        class="
                            flex
                            items-center
                            gap-3
                            rounded-2xl
                            border
                            p-3
                            text-left
                            transition
                            hover:border-indigo-300
                        ">

                        <div
                            class="
                                h-14
                                w-14
                                shrink-0
                                overflow-hidden
                                rounded-xl
                                bg-slate-100
                            ">

                            @if ($type->image_url)
                                <img src="{{ $type->image_url }}"
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
                                        text-xl
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

                        </div>


                        <div class="min-w-0">

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-black
                                    text-slate-800
                                ">
                                {{ $type->name }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    font-mono
                                    text-[9px]
                                    text-slate-400
                                ">
                                {{ $type->code }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[10px]
                                    text-slate-500
                                ">
                                {{ $type->entities_count }}
                                entidades
                            </p>

                        </div>

                    </button>
                @endforeach

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 3. IMAGEN --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-600
                ">
                3 · Representación
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Imagen de la entidad
            </h3>


            <div
                class="
                    mt-5
                    flex
                    flex-col
                    gap-5
                    rounded-2xl
                    border
                    border-slate-200
                    bg-slate-50
                    p-5
                    sm:flex-row
                    sm:items-center
                ">

                <div
                    class="
                        h-32
                        w-32
                        shrink-0
                        overflow-hidden
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                    ">

                    <template x-if="imagePreview">

                        <img :src="imagePreview"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">

                    </template>


                    <template x-if="! imagePreview">

                        <div
                            class="
                                flex
                                h-full
                                items-center
                                justify-center
                                bg-indigo-50
                                text-4xl
                                font-black
                                text-indigo-300
                            ">
                            ✦
                        </div>

                    </template>

                </div>


                <div class="flex-1">

                    <input x-ref="imageInput" type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                        @change="
                            previewImage(
                                $event
                            )
                        "
                        class="
                            block
                            w-full
                            rounded-xl
                            border
                            border-slate-300
                            bg-white
                            text-sm
                            text-slate-700

                            file:mr-4
                            file:border-0
                            file:bg-indigo-50
                            file:px-4
                            file:py-3
                            file:font-bold
                            file:text-indigo-700
                        ">


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        ">
                        JPG, PNG o WEBP. Máximo 4 MB.
                    </p>


                    @if ($editing)
                        <input type="hidden" name="remove_image"
                            :value="removeImage
                                ?
                                1 :
                                0">


                        <button type="button" x-show="imagePreview"
                            @click="
                                clearImage()
                            "
                            class="
                                mt-3
                                text-xs
                                font-bold
                                text-red-600
                            ">
                            Quitar imagen
                        </button>
                    @endif

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- DESCRIPCIÓN --}}
        {{-- ===================================================== --}}

        <section>

            <label
                class="
                    mb-2
                    block
                    text-sm
                    font-bold
                    text-slate-700
                ">
                Descripción
            </label>


            <textarea name="description" rows="5" placeholder="Describe quién o qué es esta entidad..."
                class="
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-white
                    text-slate-900
                    placeholder:text-slate-400
                ">{{ old('description', $entity->description ?? '') }}</textarea>

        </section>


        {{-- ===================================================== --}}
        {{-- 4. CARACTERÍSTICAS --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

            @include('entities.partials.characteristics-builder')

        </section>


        {{-- ===================================================== --}}
        {{-- 5. COLECCIONES --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-600
                ">
                5 · Organización
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Colecciones
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Una entidad puede formar parte de varias
                colecciones simultáneamente.
            </p>


            @if ($collections->isNotEmpty())

                <input type="text" x-model="
                        collectionSearch
                    "
                    placeholder="Buscar colección..."
                    class="
                        mt-5
                        w-full
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-slate-900
                        placeholder:text-slate-400
                    ">


                <div
                    class="
                        mt-4
                        grid
                        max-h-[430px]
                        gap-3
                        overflow-y-auto
                        pr-1
                        sm:grid-cols-2
                        lg:grid-cols-3
                    ">

                    @foreach ($collections as $collection)
                        <label
                            x-show="
                                ! collectionSearch
                                ||
                                @js(mb_strtolower($collection->name . ' ' . $collection->code)).includes(
                                    collectionSearch.toLowerCase()
                                )
                            "
                            class="
                                relative
                                cursor-pointer
                                overflow-hidden
                                rounded-2xl
                                border-2
                                border-slate-200
                                bg-white
                                transition
                                hover:border-indigo-300
                                has-[:checked]:border-indigo-500
                                has-[:checked]:bg-indigo-50
                            ">

                            <input type="checkbox" name="collection_ids[]" value="{{ $collection->id }}"
                                @checked(in_array((string) $collection->id, $selectedCollections, true))
                                class="
                                    absolute
                                    right-3
                                    top-3
                                    z-10
                                    rounded
                                    border-slate-300
                                    text-indigo-600
                                ">


                            <div
                                class="
                                    aspect-[16/7]
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
                                    <div class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            text-3xl
                                        "
                                        style="
                                            background-color:
                                                {{ $collection->color ?? '#6366F1' }}20;

                                            color:
                                                {{ $collection->color ?? '#6366F1' }};
                                        ">
                                        {{ $collection->icon ?: '▤' }}
                                    </div>
                                @endif

                            </div>


                            <div class="p-4">

                                <p
                                    class="
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $collection->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-xs
                                        text-slate-400
                                    ">
                                    {{ $collection->entities_count }}
                                    entidades
                                </p>

                            </div>

                        </label>
                    @endforeach

                </div>
            @else
                <div
                    class="
                        mt-5
                        rounded-xl
                        border
                        border-dashed
                        border-slate-300
                        bg-slate-50
                        p-5
                        text-sm
                        text-slate-500
                    ">
                    Todavía no tienes colecciones.
                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- 6. PUBLICACIÓN --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-600
                ">
                6 · Publicación
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Visibilidad
            </h3>


            <div
                class="
                    mt-5
                    grid
                    gap-3
                    md:grid-cols-3
                ">

                @foreach ([
        'PUBLIC' => ['🌐', 'Público', 'Puede aparecer en Comunidad.'],

        'PRIVATE' => ['🔒', 'Privado', 'Solo tú puedes verlo.'],

        'UNLISTED' => ['🔗', 'No listado', 'Accesible sin aparecer en búsquedas.'],
    ] as $value => [$symbol, $label, $description])
                    <label
                        class="
                            cursor-pointer
                            rounded-2xl
                            border
                            border-slate-200
                            p-4
                            has-[:checked]:border-indigo-500
                            has-[:checked]:bg-indigo-50
                        ">

                        <input type="radio" name="visibility" value="{{ $value }}"
                            @checked(old('visibility', $entity->visibility ?? 'PUBLIC') === $value)
                            class="
                                border-slate-300
                                text-indigo-600
                            ">


                        <p
                            class="
                                mt-3
                                font-black
                                text-slate-800
                            ">
                            {{ $symbol }}
                            {{ $label }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                leading-5
                                text-slate-500
                            ">
                            {{ $description }}
                        </p>

                    </label>
                @endforeach

            </div>


            <input type="hidden" name="allow_cloning" value="0">


            <label
                class="
                    mt-4
                    flex
                    cursor-pointer
                    items-start
                    gap-3
                    rounded-2xl
                    border
                    border-slate-200
                    p-4
                ">

                <input type="checkbox" name="allow_cloning" value="1" @checked(old('allow_cloning', $entity->allow_cloning ?? true))
                    class="
                        mt-1
                        rounded
                        border-slate-300
                        text-indigo-600
                    ">


                <div>

                    <p
                        class="
                            text-sm
                            font-black
                            text-slate-800
                        ">
                        Permitir clonación
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            leading-5
                            text-slate-500
                        ">
                        Si la entidad es pública, otros usuarios
                        podrán crear una copia independiente.
                    </p>

                </div>

            </label>

        </section>


        {{-- ===================================================== --}}
        {{-- 7. ESTADO --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

            <label
                class="
                    mb-2
                    block
                    text-sm
                    font-bold
                    text-slate-700
                ">
                Estado
            </label>


            <select name="status"
                class="
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-white
                    text-slate-900
                ">

                <option value="ACTIVE" @selected(old('status', $entity->status ?? 'ACTIVE') === 'ACTIVE')>
                    Activo
                </option>


                <option value="INACTIVE" @selected(old('status', $entity->status ?? 'ACTIVE') === 'INACTIVE')>
                    Inactivo
                </option>


                <option value="ARCHIVED" @selected(old('status', $entity->status ?? 'ACTIVE') === 'ARCHIVED')>
                    Archivado
                </option>

            </select>

        </section>


        {{-- ===================================================== --}}
        {{-- BOTONES --}}
        {{-- ===================================================== --}}

        <div
            class="
                flex
                flex-wrap
                justify-end
                gap-3
                border-t
                border-slate-200
                pt-7
            ">

            <a href="{{ $editing ? route('entities.show', $entity) : route('entities.index') }}"
                class="
                    rounded-xl
                    border
                    border-slate-300
                    px-5
                    py-3
                    text-sm
                    font-bold
                    text-slate-700
                ">
                Cancelar
            </a>


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
                    hover:bg-indigo-700
                ">
                {{ $editing ? 'Guardar cambios' : 'Crear entidad' }}
            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- PREVIEW LATERAL --}}
    {{-- ========================================================= --}}

    <aside class="
            xl:sticky
            xl:top-24
            xl:self-start
        ">

        <div
            class="
                overflow-hidden
                rounded-3xl
                border
                border-slate-200
                bg-white
                shadow-sm
            ">

            <div class="
                    aspect-[4/5]
                    bg-slate-100
                ">

                <template x-if="imagePreview">

                    <img :src="imagePreview"
                        class="
                            h-full
                            w-full
                            object-cover
                        ">

                </template>


                <template x-if="! imagePreview">

                    <div
                        class="
                            flex
                            h-full
                            items-center
                            justify-center
                            bg-gradient-to-br
                            from-indigo-100
                            to-violet-100
                            text-7xl
                            font-black
                            text-indigo-300
                        ">
                        ✦
                    </div>

                </template>

            </div>


            <div class="p-5">

                <p
                    class="
                        font-mono
                        text-[10px]
                        font-black
                        text-slate-400
                    ">
                    {{ $editing ? $entity->code : $previewCode }}
                </p>


                <h4 x-text="
                        name
                        || 'Nueva entidad'
                    "
                    class="
                        mt-2
                        text-xl
                        font-black
                        text-slate-900
                    ">
                </h4>


                <p
                    class="
                        mt-3
                        text-xs
                        leading-6
                        text-slate-500
                    ">
                    Esta será una pieza reutilizable
                    de tu Biblioteca y podrá participar
                    posteriormente en Universos y Torneos.
                </p>

            </div>

        </div>

    </aside>

</div>

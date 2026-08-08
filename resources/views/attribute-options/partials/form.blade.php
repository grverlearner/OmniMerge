@php

    $editing = isset($attributeOption) && $attributeOption->exists;

    $catalog = $editing ? $attributeOption->attribute : $selectedAttribute;

    $currentName = old('name', $attributeOption->name ?? '');

    $currentIcon = old('icon', $attributeOption->icon ?? '◆');

    $currentColor = old('color', $attributeOption->color ?? '#6366F1');

    $currentParentId = old('parent_option_id', $attributeOption->parent_option_id ?? ($selectedParentId ?? null));

@endphp


<div x-data="{

    name: @js($currentName),

    icon: @js($currentIcon),

    color: @js($currentColor),

    imagePreview: @js($editing ? $attributeOption->image_url : null),

    removeImage: false,

    advanced: false,

    parentId: @js($currentParentId ? (string) $currentParentId : ''),


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
            (event) => {

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
    }
}" class="
        grid
        gap-8
        xl:grid-cols-[minmax(0,1fr)_320px]
    ">

    {{-- ========================================================= --}}
    {{-- FORMULARIO --}}
    {{-- ========================================================= --}}

    <div class="space-y-9">


        {{-- ===================================================== --}}
        {{-- IDENTIDAD --}}
        {{-- ===================================================== --}}

        <section>

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-violet-600
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
                Información del elemento
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Cada elemento posee identidad propia dentro
                de OmniMerge, pero conserva el contexto
                proporcionado por su Catálogo.
            </p>


            <div
                class="
                    mt-6
                    grid
                    gap-6
                    lg:grid-cols-2
                ">

                {{-- NOMBRE --}}
                <div>

                    <label for="name"
                        class="
                            mb-2
                            block
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Nombre *
                    </label>


                    <input id="name" name="name" type="text" x-model="name" value="{{ $currentName }}"
                        required placeholder="Ejemplo: Naruto"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-violet-500
                            focus:ring-violet-500
                        ">


                    @error('name')
                        <p
                            class="
                                mt-2
                                text-sm
                                font-semibold
                                text-red-600
                            ">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- CÓDIGO --}}
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
                                tracking-wider
                                text-slate-700
                            ">
                            {{ $editing ? $attributeOption->code : $previewCode }}
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
                                tracking-wider
                                text-slate-500
                            ">
                            Permanente
                        </span>

                    </div>


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        ">
                        Se genera automáticamente y nunca
                        cambia aunque renombres el elemento.
                    </p>

                </div>

            </div>


            {{-- CATÁLOGO PROPIETARIO --}}
            <div
                class="
                    mt-6
                    rounded-2xl
                    border
                    border-violet-100
                    bg-violet-50
                    p-4
                ">

                <div
                    class="
                        flex
                        items-center
                        gap-4
                    ">

                    <div
                        class="
                            h-14
                            w-14
                            shrink-0
                            overflow-hidden
                            rounded-xl
                            bg-white
                        ">

                        @if ($catalog->image_url)
                            <img src="{{ $catalog->image_url }}" alt="{{ $catalog->name }}"
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
                                        {{ $catalog->color ?? '#6366F1' }}20;

                                    color:
                                        {{ $catalog->color ?? '#6366F1' }};
                                ">
                                {{ $catalog->icon ?: '◆' }}
                            </div>
                        @endif

                    </div>


                    <div class="min-w-0">

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-violet-500
                            ">
                            Catálogo propietario
                        </p>


                        <p
                            class="
                                mt-1
                                truncate
                                font-black
                                text-violet-950
                            ">
                            {{ $catalog->name }}
                        </p>


                        <p
                            class="
                                mt-1
                                font-mono
                                text-[10px]
                                font-bold
                                text-violet-500
                            ">
                            {{ $catalog->code }}
                        </p>

                    </div>


                    @if ($editing)
                        <span
                            class="
                                ml-auto
                                rounded-full
                                bg-white
                                px-3
                                py-1.5
                                text-[9px]
                                font-black
                                uppercase
                                tracking-wider
                                text-violet-600
                            ">
                            Bloqueado
                        </span>
                    @endif

                </div>


                <p
                    class="
                        mt-3
                        text-xs
                        leading-5
                        text-violet-700
                    ">
                    El Catálogo no puede cambiarse después
                    de crear el elemento. Así se conserva
                    su significado y sus relaciones.
                </p>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- REPRESENTACIÓN --}}
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
                    text-violet-600
                ">
                2 · Representación
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Imagen e identidad visual
            </h3>


            <div
                class="
                    mt-6
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
                        h-28
                        w-28
                        shrink-0
                        overflow-hidden
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                    ">

                    <template x-if="imagePreview">

                        <img :src="imagePreview" alt=""
                            class="
                                h-full
                                w-full
                                object-cover
                            ">

                    </template>


                    <template x-if="! imagePreview">

                        <div class="
                                flex
                                h-full
                                w-full
                                items-center
                                justify-center
                                text-4xl
                                font-black
                            "
                            :style="`
                                                            background-color:
                                                                ${color}20;
                            
                                                            color:
                                                                ${color};
                                                        `">
                            <span
                                x-text="
                                    icon
                                    || '◆'
                                "></span>
                        </div>

                    </template>

                </div>


                <div class="flex-1">

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Imagen
                    </label>


                    <input x-ref="imageInput" name="image" type="file" accept=".jpg,.jpeg,.png,.webp"
                        @change="
                            previewImage(
                                $event
                            )
                        "
                        class="
                            mt-2
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
                            file:bg-violet-50
                            file:px-4
                            file:py-3
                            file:font-bold
                            file:text-violet-700
                        ">


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        ">
                        JPG, PNG o WEBP.
                        Máximo 4 MB.
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
                            Quitar imagen actual
                        </button>
                    @endif

                </div>

            </div>


            <div
                class="
                    mt-5
                    grid
                    gap-5
                    sm:grid-cols-2
                ">

                <div>

                    <label
                        class="
                            mb-2
                            block
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Icono alternativo
                    </label>


                    <input name="icon" x-model="icon" value="{{ $currentIcon }}" placeholder="🍥"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                        ">

                </div>


                <div>

                    <label
                        class="
                            mb-2
                            block
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Color
                    </label>


                    <div class="flex gap-3">

                        <input type="color" name="color" x-model="color" value="{{ $currentColor }}"
                            class="
                                h-11
                                w-16
                                rounded-xl
                                border
                                border-slate-300
                                bg-white
                                p-1
                            ">


                        <input type="text" x-model="color" readonly
                            class="
                                flex-1
                                rounded-xl
                                border-slate-300
                                bg-slate-50
                                font-mono
                                text-sm
                                uppercase
                                text-slate-900
                            ">

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- DESCRIPCIÓN --}}
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
                Descripción
            </label>


            <textarea name="description" rows="5" placeholder="Explica qué representa este elemento..."
                class="
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-white
                    text-slate-900
                    placeholder:text-slate-400
                ">{{ old('description', $attributeOption->description ?? '') }}</textarea>

        </section>


        {{-- ===================================================== --}}
        {{-- JERARQUÍA --}}
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
                    text-violet-600
                ">
                3 · Jerarquía
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Elemento superior
            </h3>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Úsalo solamente cuando este elemento dependa
                jerárquicamente de otro elemento del mismo
                Catálogo.
            </p>


            <div
                class="
                    mt-5
                    rounded-2xl
                    border
                    border-slate-200
                    bg-slate-50
                    p-5
                ">

                <select name="parent_option_id" x-model="parentId"
                    class="
                        w-full
                        rounded-xl
                        border-slate-300
                        bg-white
                        text-slate-900
                    ">

                    <option value="">
                        Ninguno · Nivel principal
                    </option>


                    @foreach ($parentOptions as $parentOption)
                        <option value="{{ $parentOption->id }}" @selected($currentParentId == $parentOption->id)>
                            {{ $parentOption->name }}
                            ·
                            {{ $parentOption->code }}
                        </option>
                    @endforeach

                </select>


                @error('parent_option_id')
                    <p
                        class="
                            mt-2
                            text-sm
                            font-semibold
                            text-red-600
                        ">
                        {{ $message }}
                    </p>
                @enderror


                <div
                    class="
                        mt-4
                        rounded-xl
                        border
                        border-blue-100
                        bg-blue-50
                        p-4
                    ">

                    <p
                        class="
                            text-xs
                            font-black
                            text-blue-800
                        ">
                        Ejemplo
                    </p>


                    <div
                        class="
                            mt-3
                            space-y-1
                            font-mono
                            text-xs
                            text-blue-700
                        ">

                        <p>
                            Perú
                        </p>

                        <p class="pl-4">
                            └── Tacna
                        </p>

                        <p class="pl-8">
                            └── Pocollay
                        </p>

                    </div>


                    <p
                        class="
                            mt-3
                            text-xs
                            leading-5
                            text-blue-700
                        ">
                        La jerarquía es opcional.
                        Para Anime → Naruto, One Piece y Bleach
                        normalmente todos serían elementos principales.
                    </p>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- AVANZADO --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

            <button type="button"
                @click="
                    advanced =
                        ! advanced
                "
                class="
                    flex
                    w-full
                    items-center
                    justify-between
                    rounded-2xl
                    border
                    border-slate-200
                    bg-slate-50
                    px-5
                    py-4
                    text-left
                ">

                <div>

                    <p
                        class="
                            text-sm
                            font-black
                            text-slate-800
                        ">
                        ⚙ Configuración avanzada
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-500
                        ">
                        Valor de referencia y estado.
                    </p>

                </div>


                <span
                    x-text="
                        advanced
                            ? '−'
                            : '+'
                    "
                    class="
                        text-slate-400
                    "></span>

            </button>


            <div x-cloak x-show="advanced" x-transition
                class="
                    mt-5
                    space-y-6
                    rounded-2xl
                    border
                    border-slate-200
                    p-5
                ">

                {{-- NUMERIC VALUE --}}
                <div>

                    <label
                        class="
                            mb-2
                            block
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        Valor numérico de referencia
                    </label>


                    <input type="number" step="any" name="numeric_value"
                        value="{{ old('numeric_value', $attributeOption->numeric_value ?? '') }}"
                        placeholder="Ejemplo: 4"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                        ">


                    <p
                        class="
                            mt-2
                            text-xs
                            leading-5
                            text-slate-500
                        ">
                        Opcional. Permite asociar una equivalencia
                        numérica para comparaciones, reglas,
                        rankings o sistemas futuros.
                    </p>

                </div>


                {{-- STATUS --}}
                <div>

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

                        <option value="ACTIVE" @selected(old('status', $attributeOption->status ?? 'ACTIVE') === 'ACTIVE')>
                            Activo
                        </option>


                        <option value="INACTIVE" @selected(old('status', $attributeOption->status ?? 'ACTIVE') === 'INACTIVE')>
                            Inactivo
                        </option>


                        <option value="ARCHIVED" @selected(old('status', $attributeOption->status ?? 'ACTIVE') === 'ARCHIVED')>
                            Archivado
                        </option>

                    </select>


                    <div
                        class="
                            mt-3
                            space-y-1
                            text-xs
                            text-slate-500
                        ">

                        <p>
                            <strong class="text-emerald-600">
                                Activo:
                            </strong>
                            disponible normalmente.
                        </p>


                        <p>
                            <strong class="text-amber-600">
                                Inactivo:
                            </strong>
                            temporalmente deshabilitado.
                        </p>


                        <p>
                            <strong class="text-slate-600">
                                Archivado:
                            </strong>
                            conservado para historial.
                        </p>

                    </div>

                </div>

            </div>

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

            <a href="{{ $editing ? route('attribute-options.show', $attributeOption) : route('attribute-options.index') }}"
                class="
                    rounded-xl
                    border
                    border-slate-300
                    px-5
                    py-3
                    text-sm
                    font-bold
                    text-slate-700
                    hover:bg-slate-50
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
                    hover:bg-violet-700
                ">
                {{ $editing ? 'Guardar cambios' : 'Crear elemento' }}
            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- PREVIEW --}}
    {{-- ========================================================= --}}

    <aside class="
            xl:sticky
            xl:top-24
            xl:self-start
        ">

        <div
            class="
                rounded-3xl
                border
                border-slate-200
                bg-slate-50
                p-5
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Vista previa
            </p>


            <div
                class="
                    mt-4
                    overflow-hidden
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                ">

                <div class="
                        h-44
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

                        <div class="
                                flex
                                h-full
                                items-center
                                justify-center
                                text-5xl
                                font-black
                            "
                            :style="`
                                                            background-color:
                                                                ${color}20;
                            
                                                            color:
                                                                ${color};
                                                        `">
                            <span
                                x-text="
                                    icon
                                    || '◆'
                                "></span>
                        </div>

                    </template>

                </div>


                <div class="p-5">

                    <p
                        class="
                            font-mono
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-400
                        ">
                        {{ $editing ? $attributeOption->code : $previewCode }}
                    </p>


                    <h4 x-text="
                            name
                            || 'Nuevo elemento'
                        "
                        class="
                            mt-2
                            text-lg
                            font-black
                            text-slate-900
                        ">
                    </h4>


                    <div
                        class="
                            mt-3
                            flex
                            items-center
                            gap-2
                        ">

                        <div
                            class="
                                h-7
                                w-7
                                overflow-hidden
                                rounded-lg
                                bg-slate-100
                            ">

                            @if ($catalog->image_url)
                                <img src="{{ $catalog->image_url }}"
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
                                        text-xs
                                    ">
                                    {{ $catalog->icon ?: '◆' }}
                                </div>
                            @endif

                        </div>


                        <span
                            class="
                                text-xs
                                font-bold
                                text-slate-500
                            ">
                            {{ $catalog->name }}
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <div
            class="
                mt-4
                rounded-2xl
                border
                border-violet-100
                bg-violet-50
                p-5
            ">

            <p
                class="
                    text-sm
                    font-black
                    text-violet-900
                ">
                ◆ Elemento de Catálogo
            </p>


            <p
                class="
                    mt-2
                    text-xs
                    leading-6
                    text-violet-700
                ">
                Esta pieza podrá ser utilizada por entidades
                y, posteriormente, referenciada desde
                Universos, Torneos, filtros y otras reglas
                de OmniMerge.
            </p>

        </div>

    </aside>

</div>

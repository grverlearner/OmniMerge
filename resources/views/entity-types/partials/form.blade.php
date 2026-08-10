@php

    $editing = isset($entityType) && $entityType->exists;

    $initialName = old('name', $entityType->name ?? '');

    $initialIcon = old('icon', $entityType->icon ?? '◇');

    $initialColor = old('color', $entityType->color ?? '#6366F1');

@endphp


<div x-data="{

    name: @js($initialName),

    icon: @js($initialIcon),

    color: @js($initialColor),

    imagePreview: @js($editing ? $entityType->image_url : null),

    removeImage: false,


    previewImage(event) {

        const file =
            event.target.files[0];


        if (!file) {
            return;
        }


        this.removeImage = false;


        const reader =
            new FileReader();


        reader.onload = (event) => {

            this.imagePreview =
                event.target.result;

        };


        reader.readAsDataURL(file);
    },


    clearImage() {

        this.imagePreview = null;

        this.removeImage = true;


        if (
            this.$refs.imageInput
        ) {
            this.$refs.imageInput.value = '';
        }
    }
}" class="
        grid
        gap-8
        xl:grid-cols-[minmax(0,1fr)_320px]
    ">

    {{-- ========================================================= --}}
    {{-- FORMULARIO PRINCIPAL --}}
    {{-- ========================================================= --}}

    <div class="space-y-8">

        {{-- ===================================================== --}}
        {{-- REPRESENTACIÓN VISUAL --}}
        {{-- ===================================================== --}}

        <section>

            <div>

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.15em]
                        text-indigo-600
                    ">
                    Representación visual
                </p>


                <h3
                    class="
                        mt-2
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    Imagen del tipo
                </h3>


                <p
                    class="
                        mt-2
                        max-w-2xl
                        text-sm
                        leading-6
                        text-slate-500
                    ">
                    Utiliza una imagen que permita reconocer
                    rápidamente esta categoría. Si no seleccionas
                    ninguna, OmniMerge utilizará el icono y el color.
                </p>

            </div>


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
                <div class="
        mt-5
        max-w-2xl
    "
                    @omni-image-selected="
        imagePreview =
            $event.detail.url;

        removeImage =
            false;
    "
                    @omni-image-cleared="
        imagePreview =
            null;

        removeImage =
            true;
    "
                    @omni-image-restored="
        imagePreview =
            $event.detail.url;

        removeImage =
            false;
    ">

                    <x-omni-image-upload name="image" label="Imagen del Tipo de Entidad" :current-url="$editing ? $entityType->image_url : null"
                        :max-mb="4" :remove-name="$editing ? 'remove_image' : null" />

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- IDENTIFICACIÓN --}}
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
                    tracking-[0.15em]
                    text-indigo-600
                ">
                Identificación
            </p>


            <div
                class="
                    mt-5
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
                            font-semibold
                            text-slate-700
                        ">
                        Nombre *
                    </label>


                    <input id="name" name="name" type="text" x-model="name"
                        value="{{ old('name', $entityType->name ?? '') }}" required placeholder="Ejemplo: Personaje"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:text-slate-900
                            focus:ring-indigo-500
                        ">


                    @error('name')
                        <p
                            class="
                                mt-2
                                text-sm
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
                            font-semibold
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
                            {{ $editing ? $entityType->code : $previewCode }}
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
                            Automático
                        </span>

                    </div>


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        ">
                        Identificador permanente.
                        No puede modificarse manualmente.
                    </p>

                </div>


                {{-- NÚMERO CREACIÓN --}}
                <div class="
                        lg:col-span-2
                    ">

                    <div
                        class="
                            rounded-xl
                            border
                            border-indigo-100
                            bg-indigo-50
                            px-4
                            py-3
                        ">

                        <p
                            class="
                                text-xs
                                font-semibold
                                text-indigo-800
                            ">
                            Número histórico de creación:

                            <strong>
                                #{{ $editing ? $entityType->sequence_number : $nextSequence }}
                            </strong>

                            · Este número nunca cambiará,
                            aunque posteriormente reorganices
                            visualmente los tipos.
                        </p>

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

            <label for="description"
                class="
                    mb-2
                    block
                    text-sm
                    font-semibold
                    text-slate-700
                ">
                Descripción
            </label>


            <textarea id="description" name="description" rows="6"
                placeholder="Explica qué clase de entidades pertenecerán a este tipo."
                class="
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-white
                    text-slate-900
                    placeholder:text-slate-400
                    focus:border-indigo-500
                    focus:text-slate-900
                    focus:ring-indigo-500
                ">{{ old('description', $entityType->description ?? '') }}</textarea>


            @error('description')
                <p
                    class="
                        mt-2
                        text-sm
                        text-red-600
                    ">
                    {{ $message }}
                </p>
            @enderror

        </section>


        {{-- ===================================================== --}}
        {{-- APARIENCIA --}}
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
                    tracking-[0.15em]
                    text-indigo-600
                ">
                Apariencia alternativa
            </p>


            <div
                class="
                    mt-5
                    grid
                    gap-6
                    lg:grid-cols-2
                ">

                {{-- ICONO --}}
                <div>

                    <label for="icon"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-slate-700
                        ">
                        Icono o símbolo
                    </label>


                    <input id="icon" name="icon" type="text" x-model="icon"
                        value="{{ old('icon', $entityType->icon ?? '') }}" placeholder="Ejemplo: 👤, 🐉, 🌍"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                            focus:border-indigo-500
                            focus:text-slate-900
                            focus:ring-indigo-500
                        ">


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        ">
                        Se utilizará cuando el tipo
                        no tenga una imagen.
                    </p>

                </div>


                {{-- COLOR --}}
                <div>

                    <label for="color"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-slate-700
                        ">
                        Color representativo
                    </label>


                    <div
                        class="
                            flex
                            gap-3
                        ">

                        <input id="color" name="color" type="color" x-model="color"
                            value="{{ old('color', $entityType->color ?? '#6366F1') }}"
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


                {{-- ESTADO --}}
                <div class="
                        lg:col-span-2
                    ">

                    <label for="status"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-slate-700
                        ">
                        Estado *
                    </label>


                    <select id="status" name="status" required
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            focus:border-indigo-500
                            focus:ring-indigo-500
                        ">

                        @foreach ([
        'ACTIVE' => 'Activo',

        'INACTIVE' => 'Inactivo',

        'ARCHIVED' => 'Archivado',
    ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $entityType->status ?? 'ACTIVE') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach

                    </select>


                    <div
                        class="
                            mt-3
                            grid
                            gap-2
                            text-xs
                            text-slate-500
                            sm:grid-cols-3
                        ">

                        <p>
                            <strong class="text-emerald-600">
                                Activo:
                            </strong>
                            disponible al crear entidades.
                        </p>

                        <p>
                            <strong class="text-amber-600">
                                Inactivo:
                            </strong>
                            temporalmente no seleccionable.
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

            <a href="{{ route('entity-types.index') }}"
                class="
                    rounded-xl
                    border
                    border-slate-300
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-slate-700
                    transition
                    hover:bg-slate-50
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
                    transition
                    hover:bg-indigo-700
                ">
                {{ $editing ? 'Guardar cambios' : 'Crear tipo' }}
            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- VISTA PREVIA --}}
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
                    tracking-[0.15em]
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
                    shadow-sm
                ">

                {{-- IMAGEN --}}
                <div class="
                        h-44
                        bg-slate-100
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
                                    || '◇'
                                "></span>
                        </div>

                    </template>

                </div>


                {{-- INFO --}}
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
                        {{ $editing ? $entityType->code : $previewCode }}
                    </p>


                    <h4 class="
                            mt-2
                            text-lg
                            font-black
                            text-slate-900
                        "
                        x-text="
                            name
                            || 'Nuevo tipo'
                        ">
                    </h4>


                    <p
                        class="
                            mt-2
                            text-xs
                            leading-5
                            text-slate-500
                        ">
                        Esta es una aproximación
                        de cómo se verá el tipo
                        dentro de OmniMerge.
                    </p>

                </div>

            </div>

        </div>


        <div
            class="
                mt-4
                rounded-2xl
                border
                border-indigo-100
                bg-indigo-50
                p-5
            ">

            <p
                class="
                    text-sm
                    font-black
                    text-indigo-900
                ">
                💡 ¿Qué es un tipo?
            </p>


            <p
                class="
                    mt-2
                    text-xs
                    leading-6
                    text-indigo-700
                ">
                Es una categoría reutilizable que permite
                organizar entidades similares. Por ejemplo,
                Personaje, País, Criatura, Objeto o Planeta.
            </p>

        </div>

    </aside>

</div>

@php

    $editing = isset($attribute) && $attribute->exists;

    $typeLocked = $typeLocked ?? false;

    $currentDataType = old('data_type', $attribute->data_type ?? 'OPTION');

    $currentMultiple = (bool) old('allows_multiple', $attribute->allows_multiple ?? true);

    $currentName = old('name', $attribute->name ?? '');

    $currentIcon = old('icon', $attribute->icon ?? '◆');

    $currentColor = old('color', $attribute->color ?? '#6366F1');
@endphp


<div x-data="{

    name: @js($currentName),

    dataType: @js($currentDataType),

    allowsMultiple: @js($currentMultiple),

    icon: @js($currentIcon),

    color: @js($currentColor),

    imagePreview: @js($editing ? $attribute->image_url : null),

    removeImage: false,

    advanced: false,


    selectType(type) {

        this.dataType =
            type;


        /*
         * Catálogo:
         * múltiple por defecto.
         */

        if (
            type === 'OPTION'
        ) {

            this.allowsMultiple =
                true;

        } else {

            this.allowsMultiple =
                false;
        }
    },


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
    },


    typeLabel() {

        const labels = {

            OPTION: 'Catálogo',

            BOOLEAN: 'Sí / No',

            TEXT: 'Texto corto',

            LONG_TEXT: 'Texto largo',

            INTEGER: 'Número entero',

            DECIMAL: 'Número decimal',

            DATE: 'Fecha',

            COLOR: 'Color',
        };


        return labels[
            this.dataType
        ] || this.dataType;
    },


    typeIcon() {

        const icons = {

            OPTION: '◆',

            BOOLEAN: '✓',

            TEXT: 'T',

            LONG_TEXT: '¶',

            INTEGER: '#',

            DECIMAL: '#',

            DATE: '◫',

            COLOR: '◉',
        };


        return icons[
            this.dataType
        ] || '◆';
    },


    presentationLabel() {

        if (
            this.dataType ===
            'OPTION'
        ) {

            return this.allowsMultiple

                ?
                'Selección múltiple'

                :
                'Selección única';
        }


        const labels = {

            BOOLEAN: 'Sí / No',

            TEXT: 'Caja de texto',

            LONG_TEXT: 'Área de texto',

            INTEGER: 'Campo numérico',

            DECIMAL: 'Campo numérico',

            DATE: 'Selector de fecha',

            COLOR: 'Selector de color',
        };


        return labels[
            this.dataType
        ] || 'Automático';
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


            <p
                class="
                    mt-2
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Define cómo se llamará esta característica
                dentro de OmniMerge.
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
                        required placeholder="Ejemplo: Anime"
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
                            {{ $editing ? $attribute->code : $previewCode }}
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
                        No puede modificarse.
                    </p>

                </div>


                {{-- SLUG --}}
                <div class="
                        lg:col-span-2
                    ">

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
                                {{ $attribute->slug }}
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
                        Se genera automáticamente.
                        Si ya existe uno igual, OmniMerge añadirá
                        un número para mantenerlo único.
                    </p>

                </div>

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
                    text-indigo-600
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

            <div class="
        mt-6
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

                <x-omni-image-upload name="image" label="Imagen del Atributo" :current-url="$editing ? $attribute->image_url : null" :max-mb="4"
                    :remove-name="$editing ? 'remove_image' : null"
                    help="JPG, PNG o WEBP. Esta imagen se utilizará en tarjetas, filtros y selectores." />

            </div>

            {{-- ICONO + COLOR --}}
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


                    <input name="icon" type="text" x-model="icon" value="{{ $currentIcon }}" placeholder="◆"
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
                            text-slate-500
                        ">
                        Se utilizará cuando no exista imagen.
                    </p>

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


                    <div
                        class="
                            flex
                            gap-3
                        ">

                        <input name="color" type="color" x-model="color" value="{{ $currentColor }}"
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
        {{-- TIPO --}}
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
                3 · Tipo
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                ¿Qué tipo de información representa?
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Catálogo es el tipo principal de OmniMerge:
                permite crear elementos reutilizables como
                Naruto, Fuego, Perú o Rareza legendaria.
            </p>


            @if ($typeLocked)
                <div
                    class="
                        mt-5
                        rounded-2xl
                        border
                        border-amber-200
                        bg-amber-50
                        p-4
                    ">

                    <p
                        class="
                            text-sm
                            font-black
                            text-amber-800
                        ">
                        🔒 Tipo protegido
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            leading-5
                            text-amber-700
                        ">
                        Este atributo ya contiene elementos
                        de catálogo o está siendo utilizado
                        por entidades. Su tipo de dato ya
                        no puede cambiarse.
                    </p>

                </div>
            @endif


            {{-- TIPOS PRINCIPALES --}}
            <div
                class="
                    mt-6
                    grid
                    gap-4
                    md:grid-cols-2
                ">

                {{-- CATÁLOGO --}}
                <label
                    :class="dataType === 'OPTION'
                    
                        ?
                        'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                    
                        :
                        'border-slate-200 bg-white hover:border-indigo-300'"
                    class="
                        relative
                        cursor-pointer
                        rounded-2xl
                        border
                        p-5
                        transition
                    ">

                    <input type="radio" name="data_type" value="OPTION" x-model="dataType"
                        @change="
                            allowsMultiple = true
                        "
                        class="sr-only" @disabled($typeLocked)>


                    <div
                        class="
                            flex
                            items-start
                            gap-4
                        ">

                        <div
                            class="
                                flex
                                h-12
                                w-12
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-indigo-100
                                text-xl
                                text-indigo-700
                            ">
                            ◆
                        </div>


                        <div>

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-2
                                ">

                                <p
                                    class="
                                        font-black
                                        text-slate-900
                                    ">
                                    Catálogo
                                </p>


                                <span
                                    class="
                                        rounded-full
                                        bg-indigo-100
                                        px-2
                                        py-1
                                        text-[9px]
                                        font-black
                                        uppercase
                                        tracking-wider
                                        text-indigo-700
                                    ">
                                    Recomendado
                                </span>

                            </div>


                            <p
                                class="
                                    mt-2
                                    text-sm
                                    leading-6
                                    text-slate-500
                                ">
                                Valores definidos previamente.
                                Ideal para Anime, Elemento,
                                País, Clase, Rareza, etc.
                            </p>

                        </div>

                    </div>

                </label>


                {{-- BOOLEAN --}}
                <label
                    :class="dataType === 'BOOLEAN'
                    
                        ?
                        'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-100'
                    
                        :
                        'border-slate-200 bg-white hover:border-emerald-300'"
                    class="
                        cursor-pointer
                        rounded-2xl
                        border
                        p-5
                        transition
                    ">

                    <input type="radio" name="data_type" value="BOOLEAN" x-model="dataType"
                        @change="
                            allowsMultiple = false
                        "
                        class="sr-only" @disabled($typeLocked)>


                    <div
                        class="
                            flex
                            items-start
                            gap-4
                        ">

                        <div
                            class="
                                flex
                                h-12
                                w-12
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-emerald-100
                                text-xl
                                font-black
                                text-emerald-700
                            ">
                            ✓
                        </div>


                        <div>

                            <p
                                class="
                                    font-black
                                    text-slate-900
                                ">
                                Sí / No
                            </p>


                            <p
                                class="
                                    mt-2
                                    text-sm
                                    leading-6
                                    text-slate-500
                                ">
                                Para características binarias.
                                Ejemplo: Puede volar,
                                es legendario, está activo.
                            </p>

                        </div>

                    </div>

                </label>

            </div>


            {{-- OTROS --}}
            <div class="mt-5">

                <p
                    class="
                        mb-3
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Otros tipos
                </p>


                <div
                    class="
                        grid
                        gap-3
                        sm:grid-cols-2
                        lg:grid-cols-3
                    ">

                    @foreach ([
        'TEXT' => ['T', 'Texto corto'],

        'LONG_TEXT' => ['¶', 'Texto largo'],

        'INTEGER' => ['#', 'Número entero'],

        'DECIMAL' => ['#.', 'Número decimal'],

        'DATE' => ['◫', 'Fecha'],

        'COLOR' => ['◉', 'Color'],
    ] as $value => [$symbol, $label])
                        <label
                            :class="dataType === '{{ $value }}'
                            
                                ?
                                'border-indigo-400 bg-indigo-50 text-indigo-800'
                            
                                :
                                'border-slate-200 bg-white text-slate-600 hover:border-indigo-200'"
                            class="
                                cursor-pointer
                                rounded-xl
                                border
                                p-4
                                transition
                            ">

                            <input type="radio" name="data_type" value="{{ $value }}" x-model="dataType"
                                @change="
                                    allowsMultiple = false
                                "
                                class="sr-only" @disabled($typeLocked)>


                            <div
                                class="
                                    flex
                                    items-center
                                    gap-3
                                ">

                                <span
                                    class="
                                        text-lg
                                        font-black
                                    ">
                                    {{ $symbol }}
                                </span>


                                <span
                                    class="
                                        text-sm
                                        font-bold
                                    ">
                                    {{ $label }}
                                </span>

                            </div>

                        </label>
                    @endforeach

                </div>

            </div>


            @error('data_type')
                <p
                    class="
                        mt-3
                        text-sm
                        font-semibold
                        text-red-600
                    ">
                    {{ $message }}
                </p>
            @enderror


            {{-- PRESENTACIÓN AUTOMÁTICA --}}
            <div
                class="
                    mt-5
                    rounded-2xl
                    border
                    border-slate-200
                    bg-slate-50
                    p-4
                ">

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Presentación automática
                </p>


                <p class="
                        mt-2
                        text-sm
                        font-black
                        text-slate-800
                    "
                    x-text="
                        presentationLabel()
                    "></p>


                <p
                    class="
                        mt-1
                        text-xs
                        leading-5
                        text-slate-500
                    ">
                    Ya no necesitas elegir manualmente cómo
                    se mostrará. OmniMerge lo determina según
                    el tipo y su comportamiento.
                </p>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- COMPORTAMIENTO --}}
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
                4 · Comportamiento
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Cómo se utilizará
            </h3>


            <input type="hidden" name="allows_multiple" value="0">


            {{-- MÚLTIPLE --}}
            <label x-show="
                    dataType === 'OPTION'
                "
                class="
                    mt-6
                    flex
                    cursor-pointer
                    items-start
                    gap-4
                    rounded-2xl
                    border
                    border-violet-200
                    bg-violet-50
                    p-5
                ">

                <input type="checkbox" name="allows_multiple" value="1" x-model="allowsMultiple"
                    class="
                        mt-1
                        rounded
                        border-violet-300
                        text-violet-600
                        focus:ring-violet-500
                    ">


                <div>

                    <p
                        class="
                            font-black
                            text-violet-900
                        ">
                        ◆ Permitir múltiples valores
                    </p>


                    <p
                        class="
                            mt-1
                            text-sm
                            leading-6
                            text-violet-700
                        ">
                        Una entidad podrá seleccionar varios
                        elementos del Catálogo.
                    </p>


                    <p
                        class="
                            mt-2
                            text-xs
                            font-semibold
                            text-violet-600
                        ">
                        Ejemplo: Elemento →
                        Fuego + Viento + Rayo
                    </p>

                </div>

            </label>


            {{-- OBLIGATORIO --}}
            <div class="mt-4">

                <input type="hidden" name="is_required" value="0">


                <label
                    class="
                        flex
                        cursor-pointer
                        items-start
                        gap-3
                        rounded-2xl
                        border
                        border-slate-200
                        p-4
                    ">

                    <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $attribute->is_required ?? false))
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
                                font-bold
                                text-slate-800
                            ">
                            Obligatorio
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-500
                            ">
                            Las entidades que utilicen este atributo
                            deberán proporcionar un valor.
                        </p>

                    </div>

                </label>

            </div>


            {{-- AYUDA --}}
            <div
                class="
                    mt-5
                    grid
                    gap-5
                    lg:grid-cols-2
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
                        Texto de ayuda
                    </label>


                    <textarea name="help_text" rows="3" placeholder="Explica al usuario qué debe seleccionar..."
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                        ">{{ old('help_text', $attribute->help_text ?? '') }}</textarea>

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
                        Placeholder
                    </label>


                    <input name="placeholder" value="{{ old('placeholder', $attribute->placeholder ?? '') }}"
                        placeholder="Ejemplo: Selecciona uno o varios..."
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                        ">

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


            <textarea name="description" rows="5"
                placeholder="Describe qué representa este atributo y cuándo debe utilizarse."
                class="
                    w-full
                    rounded-xl
                    border-slate-300
                    bg-white
                    text-slate-900
                    placeholder:text-slate-400
                ">{{ old('description', $attribute->description ?? '') }}</textarea>

        </section>


        {{-- ===================================================== --}}
        {{-- GRUPOS --}}
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
                Grupos de atributos
            </h3>


            @if ($groups->isNotEmpty())

                <div
                    class="
                        mt-5
                        grid
                        gap-3
                        sm:grid-cols-2
                    ">

                    @foreach ($groups as $group)
                        <label
                            class="
                                flex
                                cursor-pointer
                                items-center
                                gap-3
                                rounded-xl
                                border
                                border-slate-200
                                p-4
                                transition
                                hover:border-indigo-300
                            ">

                            <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                                @checked(in_array($group->id, old('group_ids', $editing ? $attribute->groups->pluck('id')->all() : [])))
                                class="
                                    rounded
                                    border-slate-300
                                    text-indigo-600
                                ">


                            <span>

                                <span
                                    class="
                                        block
                                        text-sm
                                        font-bold
                                        text-slate-800
                                    ">
                                    {{ $group->name }}
                                </span>


                                @if ($group->description)
                                    <span
                                        class="
                                            mt-1
                                            block
                                            line-clamp-1
                                            text-xs
                                            text-slate-500
                                        ">
                                        {{ $group->description }}
                                    </span>
                                @endif

                            </span>

                        </label>
                    @endforeach

                </div>
            @else
                <div
                    class="
                        mt-5
                        rounded-2xl
                        border
                        border-dashed
                        border-slate-300
                        bg-slate-50
                        p-5
                        text-sm
                        text-slate-500
                    ">
                    Todavía no tienes grupos de atributos.
                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- PUBLICACIÓN --}}
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
        'PUBLIC' => ['🌐', 'Público', 'Visible en Comunidad.'],

        'PRIVATE' => ['🔒', 'Privado', 'Solo tú puedes utilizarlo.'],

        'UNLISTED' => ['🔗', 'No listado', 'No aparece públicamente en búsquedas.'],
    ] as $value => [$symbol, $label, $description])
                    <label
                        class="
                            cursor-pointer
                            rounded-2xl
                            border
                            border-slate-200
                            p-4
                            transition
                            hover:border-indigo-300
                        ">

                        <div
                            class="
                                flex
                                items-start
                                gap-3
                            ">

                            <input type="radio" name="scope" value="{{ $value }}"
                                @checked(old('scope', $attribute->scope ?? 'PUBLIC') === $value)
                                class="
                                    mt-1
                                    border-slate-300
                                    text-indigo-600
                                ">


                            <div>

                                <p
                                    class="
                                        text-sm
                                        font-black
                                        text-slate-900
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

                            </div>

                        </div>

                    </label>
                @endforeach

            </div>


            {{-- CLONACIÓN --}}
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

                <input type="checkbox" name="allow_cloning" value="1" @checked(old('allow_cloning', $attribute->allow_cloning ?? true))
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
                            font-bold
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
                        Otros usuarios podrán crear una copia
                        independiente del atributo y de su Catálogo.
                    </p>

                </div>

            </label>

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
                        Filtros, comparación, límites,
                        unidades y estado.
                    </p>

                </div>


                <span class="
                        text-slate-400
                    "
                    x-text="
                        advanced
                            ? '−'
                            : '+'
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

                {{-- FLAGS --}}
                <div
                    class="
                        grid
                        gap-3
                        sm:grid-cols-2
                    ">

                    @foreach ([
        'is_filterable' => ['Filtrable', 'Puede utilizarse en filtros.', true],

        'is_comparable' => ['Comparable', 'Puede utilizarse para comparar entidades.', true],

        'is_searchable' => ['Buscable', 'Participa en búsquedas.', true],

        'is_visible' => ['Visible', 'Se muestra normalmente en la entidad.', true],

        'is_featured' => ['Destacado', 'Puede recibir mayor relevancia visual.', false],
    ] as $field => [$label, $description, $default])
                        <div>

                            <input type="hidden" name="{{ $field }}" value="0">


                            <label
                                class="
                                    flex
                                    cursor-pointer
                                    items-start
                                    gap-3
                                    rounded-xl
                                    border
                                    border-slate-200
                                    p-4
                                ">

                                <input type="checkbox" name="{{ $field }}" value="1"
                                    @checked(old($field, $editing ? $attribute->{$field} : $default))
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
                                            text-sm
                                            font-bold
                                            text-slate-800
                                        ">
                                        {{ $label }}
                                    </span>


                                    <span
                                        class="
                                            mt-1
                                            block
                                            text-xs
                                            text-slate-500
                                        ">
                                        {{ $description }}
                                    </span>

                                </span>

                            </label>

                        </div>
                    @endforeach

                </div>


                {{-- NÚMEROS --}}
                <div x-show="
                        dataType === 'INTEGER'
                        ||
                        dataType === 'DECIMAL'
                    "
                    class="
                        grid
                        gap-5
                        sm:grid-cols-3
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
                            Mínimo
                        </label>


                        <input type="number" step="any" name="min_numeric_value"
                            value="{{ old('min_numeric_value', $attribute->min_numeric_value ?? '') }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
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
                            Máximo
                        </label>


                        <input type="number" step="any" name="max_numeric_value"
                            value="{{ old('max_numeric_value', $attribute->max_numeric_value ?? '') }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
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
                            Unidad
                        </label>


                        <input name="unit" value="{{ old('unit', $attribute->unit ?? '') }}"
                            placeholder="kg, puntos..."
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
                                placeholder:text-slate-400
                            ">

                    </div>

                </div>


                {{-- TEXTO --}}
                <div x-show="
                        dataType === 'TEXT'
                        ||
                        dataType === 'LONG_TEXT'
                    "
                    class="
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
                            Longitud mínima
                        </label>


                        <input type="number" min="0" name="min_length"
                            value="{{ old('min_length', $attribute->min_length ?? '') }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
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
                            Longitud máxima
                        </label>


                        <input type="number" min="0" name="max_length"
                            value="{{ old('max_length', $attribute->max_length ?? '') }}"
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
                            ">

                    </div>

                </div>


                {{-- ESTADO --}}
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

                        @foreach ([
        'ACTIVE' => 'Activo',

        'INACTIVE' => 'Inactivo',

        'ARCHIVED' => 'Archivado',
    ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $attribute->status ?? 'ACTIVE') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach

                    </select>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- GUARDAR --}}
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

            <a href="{{ route('attributes.index') }}"
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
                {{ $editing ? 'Guardar cambios' : 'Crear atributo' }}
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
                    shadow-sm
                ">

                <div class="
                        h-40
                        bg-slate-100
                    ">

                    <template x-if="
                            imagePreview
                        ">

                        <img :src="imagePreview"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">

                    </template>


                    <template x-if="
                            ! imagePreview
                        ">

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
                                    || typeIcon()
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
                        {{ $editing ? $attribute->code : $previewCode }}
                    </p>


                    <h4 x-text="
                            name
                            || 'Nuevo atributo'
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
                            flex-wrap
                            gap-2
                        ">

                        <span x-text="
                                typeLabel()
                            "
                            class="
                                rounded-full
                                bg-indigo-50
                                px-2.5
                                py-1
                                text-[10px]
                                font-black
                                text-indigo-700
                            "></span>


                        <span
                            x-show="
                                dataType === 'OPTION'
                                &&
                                allowsMultiple
                            "
                            class="
                                rounded-full
                                bg-violet-50
                                px-2.5
                                py-1
                                text-[10px]
                                font-black
                                text-violet-700
                            ">
                            Múltiple
                        </span>


                        <span
                            class="
                                rounded-full
                                bg-emerald-50
                                px-2.5
                                py-1
                                text-[10px]
                                font-black
                                text-emerald-700
                            ">
                            Público por defecto
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
                ◆ Catálogo
            </p>


            <p
                class="
                    mt-2
                    text-xs
                    leading-6
                    text-indigo-700
                ">
                Es el tipo más importante para datos
                reutilizables. Una vez creado el atributo
                podrás construir su Catálogo con imágenes,
                jerarquías, colores y otros datos.
            </p>

        </div>

    </aside>

</div>

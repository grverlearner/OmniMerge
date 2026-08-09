@php

    $editing = isset($attributeGroup) && $attributeGroup->exists;

    /*
    |--------------------------------------------------------------------------
    | Selección inicial
    |--------------------------------------------------------------------------
    */

    $selectedAttributeIds = old(
        'attribute_ids',

        $editing ? $attributeGroup->attributes->pluck('id')->map(fn($id) => (string) $id)->all() : [],
    );

    $selectedAttributeIds = array_values(array_unique(array_map('strval', $selectedAttributeIds)));

    /*
    |--------------------------------------------------------------------------
    | Valores del Grupo
    |--------------------------------------------------------------------------
    */

    $currentName = old('name', $attributeGroup->name ?? '');

    $currentIcon = old('icon', $attributeGroup->icon ?? '▥');

    $currentColor = old('color', $attributeGroup->color ?? '#6366F1');

    $currentLayout = old('layout_type', $attributeGroup->layout_type ?? 'LIST');

    $currentCollapsible = (bool) old('collapsible', $attributeGroup->collapsible ?? true);

    $currentExpanded = (bool) old('default_expanded', $attributeGroup->default_expanded ?? true);
@endphp


<div x-data="{

    name: @js($currentName),

    icon: @js($currentIcon),

    color: @js($currentColor),

    layoutType: @js($currentLayout),

    collapsible: @js($currentCollapsible),

    expanded: @js($currentExpanded),

    selectedAttributes: @js($selectedAttributeIds),

    attributeSearch: '',

    dataType: 'ALL',


    isSelected(id) {

        return this
            .selectedAttributes
            .includes(
                String(id)
            );
    },


    addAttribute(id) {

        id =
            String(id);


        if (
            !this
            .selectedAttributes
            .includes(id)
        ) {

            this
                .selectedAttributes
                .push(id);
        }
    },


    removeAttribute(id) {

        id =
            String(id);


        this.selectedAttributes =
            this
            .selectedAttributes
            .filter(
                value =>
                value !== id
            );
    },


    moveUp(id) {

        id =
            String(id);


        const index =
            this
            .selectedAttributes
            .indexOf(id);


        if (index <= 0) {
            return;
        }


        const previous =
            this.selectedAttributes[
                index - 1
            ];


        this.selectedAttributes[
            index - 1
        ] = id;


        this.selectedAttributes[
            index
        ] = previous;
    },


    moveDown(id) {

        id =
            String(id);


        const index =
            this
            .selectedAttributes
            .indexOf(id);


        if (
            index < 0 ||
            index >=
            this.selectedAttributes.length - 1
        ) {
            return;
        }


        const next =
            this.selectedAttributes[
                index + 1
            ];


        this.selectedAttributes[
            index + 1
        ] = id;


        this.selectedAttributes[
            index
        ] = next;
    },


    matchesAttribute(
        name,
        code,
        type
    ) {

        const search =
            this
            .attributeSearch
            .toLowerCase();


        const text =
            `${name} ${code}`
            .toLowerCase();


        const matchesSearch = !search ||
            text.includes(
                search
            );


        const matchesType =
            this.dataType === 'ALL' ||
            this.dataType === type;


        return matchesSearch &&
            matchesType;
    }
}" class="
        grid
        gap-8
        xl:grid-cols-[minmax(0,1fr)_330px]
    ">

    {{-- ========================================================= --}}
    {{-- FORMULARIO --}}
    {{-- ========================================================= --}}

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
                Información del grupo
            </h3>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Un grupo organiza Atributos dentro de
                las fichas de Entidades. No duplica ni
                modifica los Atributos originales.
            </p>


            <div
                class="
                    mt-6
                    grid
                    gap-5
                    lg:grid-cols-2
                ">

                {{-- NAME --}}
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


                    <input type="text" name="name" x-model="name" value="{{ $currentName }}" required
                        placeholder="Ejemplo: Combate"
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
                        <p class="mt-2 text-sm font-bold text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

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
                                tracking-wider
                                text-slate-700
                            ">
                            {{ $editing ? $attributeGroup->code : $previewCode }}
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
                            Permanente
                        </span>

                    </div>


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        ">
                        Se genera automáticamente y no cambia
                        al renombrar el grupo.
                    </p>

                </div>


                {{-- DESCRIPTION --}}
                <div class="lg:col-span-2">

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


                    <textarea name="description" rows="4"
                        placeholder="Ejemplo: Características relacionadas con las capacidades de combate..."
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                            placeholder:text-slate-400
                        ">{{ old('description', $attributeGroup->description ?? '') }}</textarea>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 2. APARIENCIA --}}
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
                2 · Apariencia
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Identidad visual
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Los Grupos no necesitan una imagen propia.
                Su portada visual se construye utilizando
                las imágenes de los Atributos contenidos.
            </p>


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
                        Icono
                    </label>


                    <input type="text" name="icon" x-model="icon" value="{{ $currentIcon }}" placeholder="⚔"
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
        {{-- 3. ATRIBUTOS INCLUIDOS --}}
        {{-- ===================================================== --}}

        <section class="
                border-t
                border-slate-200
                pt-8
            ">

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
                            text-indigo-600
                        ">
                        3 · Contenido
                    </p>


                    <h3
                        class="
                            mt-2
                            text-xl
                            font-black
                            text-slate-900
                        ">
                        Atributos del grupo
                    </h3>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-slate-500
                        ">
                        Selecciona y ordena los atributos.
                    </p>

                </div>


                <span
                    class="
                        rounded-full
                        bg-indigo-50
                        px-3
                        py-1.5
                        text-xs
                        font-black
                        text-indigo-700
                    ">
                    <span
                        x-text="
                            selectedAttributes.length
                        "></span>

                    seleccionados
                </span>

            </div>


            {{-- INPUTS IDS --}}
            <template
                x-for="
                    attributeId
                    in selectedAttributes
                "
                :key="`selected-group-attribute-${attributeId}`">

                <input type="hidden" name="attribute_ids[]" :value="attributeId">

            </template>


            {{-- ================================================ --}}
            {{-- SELECCIONADOS --}}
            {{-- ================================================ --}}

            <div class="mt-5 space-y-3">

                @foreach ($attributes as $attribute)
                    @php

                        $pivot = $editing
                            ? $attributeGroup->attributes->firstWhere('id', $attribute->id)?->pivot
                            : null;

                        $customLabel = old("attribute_settings.{$attribute->id}.custom_label", $pivot?->custom_label);

                        $isFeatured = (bool) old(
                            "attribute_settings.{$attribute->id}.is_featured",
                            $pivot?->is_featured ?? false,
                        );

                        $typeLabel = match ($attribute->data_type) {
                            'OPTION' => 'Catálogo',
                            'BOOLEAN' => 'Sí / No',
                            'TEXT' => 'Texto',
                            'LONG_TEXT' => 'Texto largo',
                            'INTEGER' => 'Entero',
                            'DECIMAL' => 'Decimal',
                            'DATE' => 'Fecha',
                            'COLOR' => 'Color',

                            default => $attribute->data_type,
                        };
                    @endphp


                    <article x-cloak
                        x-show="
                            isSelected(
                                '{{ $attribute->id }}'
                            )
                        "
                        class="
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            p-4
                        ">

                        <div
                            class="
                                flex
                                flex-col
                                gap-4
                                sm:flex-row
                                sm:items-center
                            ">

                            {{-- IMAGE --}}
                            <div
                                class="
                                    h-16
                                    w-16
                                    shrink-0
                                    overflow-hidden
                                    rounded-xl
                                    bg-slate-100
                                ">

                                @if ($attribute->image_url)
                                    <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
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
                                                {{ $attribute->color ?? '#6366F1' }}20;

                                            color:
                                                {{ $attribute->color ?? '#6366F1' }};
                                        ">
                                        {{ $attribute->icon ?: '☷' }}
                                    </div>
                                @endif

                            </div>


                            {{-- INFO --}}
                            <div
                                class="
                                    min-w-0
                                    flex-1
                                ">

                                <p
                                    class="
                                        font-black
                                        text-slate-900
                                    ">
                                    {{ $attribute->name }}
                                </p>


                                <div
                                    class="
                                        mt-1
                                        flex
                                        flex-wrap
                                        gap-2
                                    ">

                                    <span
                                        class="
                                            font-mono
                                            text-[9px]
                                            font-bold
                                            text-slate-400
                                        ">
                                        {{ $attribute->code }}
                                    </span>


                                    <span
                                        class="
                                            rounded-full
                                            bg-slate-100
                                            px-2
                                            py-0.5
                                            text-[9px]
                                            font-bold
                                            text-slate-500
                                        ">
                                        {{ $typeLabel }}
                                    </span>


                                    @if ($attribute->data_type === 'OPTION')
                                        <span
                                            class="
                                                rounded-full
                                                bg-violet-50
                                                px-2
                                                py-0.5
                                                text-[9px]
                                                font-bold
                                                text-violet-600
                                            ">
                                            {{ $attribute->options_count }}
                                            elementos
                                        </span>
                                    @endif

                                </div>

                            </div>


                            {{-- ORDER --}}
                            <div
                                class="
                                    flex
                                    items-center
                                    gap-1
                                ">

                                <button type="button"
                                    @click="
                                        moveUp(
                                            '{{ $attribute->id }}'
                                        )
                                    "
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        items-center
                                        justify-center
                                        rounded-lg
                                        border
                                        border-slate-200
                                        bg-white
                                        text-slate-500
                                        hover:bg-slate-50
                                    "
                                    title="Mover arriba">
                                    ↑
                                </button>


                                <button type="button"
                                    @click="
                                        moveDown(
                                            '{{ $attribute->id }}'
                                        )
                                    "
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        items-center
                                        justify-center
                                        rounded-lg
                                        border
                                        border-slate-200
                                        bg-white
                                        text-slate-500
                                        hover:bg-slate-50
                                    "
                                    title="Mover abajo">
                                    ↓
                                </button>


                                <button type="button"
                                    @click="
                                        removeAttribute(
                                            '{{ $attribute->id }}'
                                        )
                                    "
                                    class="
                                        flex
                                        h-9
                                        items-center
                                        justify-center
                                        rounded-lg
                                        border
                                        border-red-100
                                        bg-red-50
                                        px-3
                                        text-xs
                                        font-bold
                                        text-red-600
                                    ">
                                    Quitar
                                </button>

                            </div>

                        </div>


                        {{-- CONFIG --}}
                        <div
                            class="
                                mt-4
                                grid
                                gap-4
                                border-t
                                border-slate-100
                                pt-4
                                md:grid-cols-[minmax(0,1fr)_180px]
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
                                    Etiqueta dentro del grupo
                                </label>


                                <input type="text" name="attribute_settings[{{ $attribute->id }}][custom_label]"
                                    value="{{ $customLabel }}" placeholder="{{ $attribute->name }}"
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        bg-white
                                        text-sm
                                        text-slate-900
                                        placeholder:text-slate-400
                                    ">


                                <p
                                    class="
                                        mt-1
                                        text-[10px]
                                        text-slate-400
                                    ">
                                    Opcional. No cambia el nombre global del atributo.
                                </p>

                            </div>


                            <div>

                                <p
                                    class="
                                        mb-2
                                        text-xs
                                        font-bold
                                        text-slate-600
                                    ">
                                    Destacado
                                </p>


                                <input type="hidden" name="attribute_settings[{{ $attribute->id }}][is_featured]"
                                    value="0">


                                <label
                                    class="
                                        flex
                                        cursor-pointer
                                        items-center
                                        gap-3
                                        rounded-xl
                                        border
                                        border-slate-200
                                        p-3
                                    ">

                                    <input type="checkbox"
                                        name="attribute_settings[{{ $attribute->id }}][is_featured]" value="1"
                                        @checked($isFeatured)
                                        class="
                                            rounded
                                            border-slate-300
                                            text-indigo-600
                                        ">


                                    <span
                                        class="
                                            text-xs
                                            font-bold
                                            text-slate-700
                                        ">
                                        ★ Prioritario
                                    </span>

                                </label>

                            </div>


                            <input type="hidden" name="attribute_settings[{{ $attribute->id }}][sort_order]"
                                :value="Math.max(
                                    10,
                                    (
                                        selectedAttributes.indexOf(
                                            '{{ $attribute->id }}'
                                        ) +
                                        1
                                    ) *
                                    10
                                )">

                        </div>

                    </article>
                @endforeach

            </div>


            {{-- ================================================ --}}
            {{-- EXPLORADOR --}}
            {{-- ================================================ --}}

            <div
                class="
                    mt-6
                    rounded-2xl
                    border
                    border-dashed
                    border-slate-300
                    bg-slate-50
                    p-5
                ">

                <div
                    class="
                        flex
                        flex-col
                        justify-between
                        gap-4
                        lg:flex-row
                        lg:items-center
                    ">

                    <div>

                        <h4
                            class="
                                font-black
                                text-slate-900
                            ">
                            + Añadir atributo
                        </h4>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-500
                            ">
                            Busca en tu Biblioteca.
                        </p>

                    </div>


                    <div
                        class="
                            grid
                            gap-2
                            sm:grid-cols-2
                            lg:w-[560px]
                        ">

                        <input type="text"
                            x-model="
                                attributeSearch
                            "
                            placeholder="Buscar atributo..."
                            class="
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-sm
                                text-slate-900
                                placeholder:text-slate-400
                            ">


                        <select x-model="
                                dataType
                            "
                            class="
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-sm
                                text-slate-900
                            ">

                            <option value="ALL">
                                Todos los tipos
                            </option>

                            <option value="OPTION">
                                Catálogo
                            </option>

                            <option value="BOOLEAN">
                                Sí / No
                            </option>

                            <option value="TEXT">
                                Texto
                            </option>

                            <option value="LONG_TEXT">
                                Texto largo
                            </option>

                            <option value="INTEGER">
                                Entero
                            </option>

                            <option value="DECIMAL">
                                Decimal
                            </option>

                            <option value="DATE">
                                Fecha
                            </option>

                            <option value="COLOR">
                                Color
                            </option>

                        </select>

                    </div>

                </div>


                <div
                    class="
                        mt-5
                        grid
                        max-h-[500px]
                        gap-3
                        overflow-y-auto
                        pr-1
                        sm:grid-cols-2
                        lg:grid-cols-3
                    ">

                    @forelse ($attributes as $attribute)
                        @php

                            $typeLabel = match ($attribute->data_type) {
                                'OPTION' => 'Catálogo',
                                'BOOLEAN' => 'Sí / No',
                                'TEXT' => 'Texto',
                                'LONG_TEXT' => 'Texto largo',
                                'INTEGER' => 'Entero',
                                'DECIMAL' => 'Decimal',
                                'DATE' => 'Fecha',
                                'COLOR' => 'Color',

                                default => $attribute->data_type,
                            };

                        @endphp


                        <button type="button"
                            x-show="
                                ! isSelected(
                                    '{{ $attribute->id }}'
                                )
                                &&
                                matchesAttribute(
                                    @js($attribute->name),
                                    @js($attribute->code),
                                    @js($attribute->data_type)
                                )
                            "
                            @click="
                                addAttribute(
                                    '{{ $attribute->id }}'
                                )
                            "
                            class="
                                group
                                flex
                                items-center
                                gap-3
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-3
                                text-left
                                transition
                                hover:border-indigo-300
                                hover:bg-indigo-50
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

                                @if ($attribute->image_url)
                                    <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
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
                                            text-lg
                                            font-black
                                        "
                                        style="
                                            background-color:
                                                {{ $attribute->color ?? '#6366F1' }}20;

                                            color:
                                                {{ $attribute->color ?? '#6366F1' }};
                                        ">
                                        {{ $attribute->icon ?: '☷' }}
                                    </div>
                                @endif

                            </div>


                            <div class="min-w-0 flex-1">

                                <p
                                    class="
                                        truncate
                                        text-sm
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $attribute->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        font-mono
                                        text-[9px]
                                        text-slate-400
                                    ">
                                    {{ $attribute->code }}
                                </p>


                                <div
                                    class="
                                        mt-1
                                        flex
                                        flex-wrap
                                        gap-1
                                    ">

                                    <span
                                        class="
                                            text-[9px]
                                            font-bold
                                            text-slate-500
                                        ">
                                        {{ $typeLabel }}
                                    </span>


                                    @if ($attribute->data_type === 'OPTION')
                                        <span
                                            class="
                                                text-[9px]
                                                text-violet-500
                                            ">
                                            · {{ $attribute->options_count }} elementos
                                        </span>
                                    @endif

                                </div>

                            </div>


                            <span
                                class="
                                    text-xl
                                    font-black
                                    text-indigo-500
                                ">
                                +
                            </span>

                        </button>

                    @empty

                        <div
                            class="
                                sm:col-span-2
                                lg:col-span-3
                                rounded-xl
                                border
                                border-amber-200
                                bg-amber-50
                                p-5
                                text-sm
                                text-amber-700
                            ">
                            Primero crea algún atributo.
                        </div>
                    @endforelse

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 4. PRESENTACIÓN --}}
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
                4 · Presentación
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Presentación predeterminada
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Define cómo debería presentarse este grupo
                dentro de las fichas de Entidades.
            </p>


            <div
                class="
                    mt-5
                    grid
                    gap-3
                    sm:grid-cols-2
                    xl:grid-cols-5
                ">

                @foreach ([
        'LIST' => ['☰', 'Lista', 'Una característica debajo de otra.'],

        'GRID' => ['▦', 'Cuadrícula', 'Información distribuida en columnas.'],

        'CARDS' => ['▣', 'Tarjetas', 'Cada atributo tiene su tarjeta.'],

        'TABLE' => ['≡', 'Tabla', 'Nombre y valor en filas.'],

        'COMPACT' => ['•••', 'Compacto', 'Máxima densidad de información.'],
    ] as $value => [$symbol, $labelText, $description])
                    <label
                        class="
                            cursor-pointer
                            rounded-2xl
                            border-2
                            border-slate-200
                            bg-white
                            p-4
                            transition
                            has-[:checked]:border-indigo-500
                            has-[:checked]:bg-indigo-50
                            has-[:checked]:ring-2
                            has-[:checked]:ring-indigo-100
                        ">

                        <input type="radio" name="layout_type" value="{{ $value }}"
                            x-model="
                                layoutType
                            "
                            @checked($currentLayout === $value) class="sr-only">


                        <p class="text-2xl">
                            {{ $symbol }}
                        </p>


                        <p
                            class="
                                mt-3
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            {{ $labelText }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-[10px]
                                leading-4
                                text-slate-500
                            ">
                            {{ $description }}
                        </p>

                    </label>
                @endforeach

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 5. COMPORTAMIENTO --}}
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
                5 · Comportamiento
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Expansión del grupo
            </h3>


            <input type="hidden" name="collapsible" value="0">


            <label
                class="
                    mt-5
                    flex
                    cursor-pointer
                    items-start
                    gap-3
                    rounded-2xl
                    border
                    border-slate-200
                    p-4
                ">

                <input type="checkbox" name="collapsible" value="1"
                    x-model="
                        collapsible
                    " @checked($currentCollapsible)
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
                        Puede contraerse
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            leading-5
                            text-slate-500
                        ">
                        Permite ocultar temporalmente
                        el contenido del grupo.
                    </p>

                </div>

            </label>


            <div x-show="
                    collapsible
                " x-transition class="mt-3">

                <input type="hidden" name="default_expanded" value="0">


                <label
                    class="
                        flex
                        cursor-pointer
                        items-start
                        gap-3
                        rounded-2xl
                        border
                        border-indigo-100
                        bg-indigo-50
                        p-4
                    ">

                    <input type="checkbox" name="default_expanded" value="1"
                        x-model="
                            expanded
                        "
                        @checked($currentExpanded)
                        class="
                            mt-1
                            rounded
                            border-indigo-300
                            text-indigo-600
                        ">


                    <div>

                        <p
                            class="
                                text-sm
                                font-black
                                text-indigo-900
                            ">
                            Abierto inicialmente
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                leading-5
                                text-indigo-600
                            ">
                            Al abrir una ficha, el contenido
                            se mostrará desplegado.
                        </p>

                    </div>

                </label>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 6. ESTADO --}}
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

                <option value="ACTIVE" @selected(old('status', $attributeGroup->status ?? 'ACTIVE') === 'ACTIVE')>
                    Activo
                </option>


                <option value="INACTIVE" @selected(old('status', $attributeGroup->status ?? 'ACTIVE') === 'INACTIVE')>
                    Inactivo
                </option>


                <option value="ARCHIVED" @selected(old('status', $attributeGroup->status ?? 'ACTIVE') === 'ARCHIVED')>
                    Archivado
                </option>

            </select>

        </section>


        {{-- ===================================================== --}}
        {{-- BUTTONS --}}
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

            <a href="{{ $editing ? route('attribute-groups.show', $attributeGroup) : route('attribute-groups.index') }}"
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
                    hover:bg-indigo-700
                ">
                {{ $editing ? 'Guardar cambios' : 'Crear grupo' }}
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

                {{-- MOSAICO --}}
                <div
                    class="
                        grid
                        h-44
                        grid-cols-2
                        gap-1.5
                        bg-slate-100
                        p-2
                    ">

                    @foreach ($attributes->take(12) as $attribute)
                        <div x-show="
                                isSelected(
                                    '{{ $attribute->id }}'
                                )
                                &&
                                selectedAttributes.indexOf(
                                    '{{ $attribute->id }}'
                                ) < 4
                            "
                            class="
                                overflow-hidden
                                rounded-lg
                                bg-white
                            ">

                            @if ($attribute->image_url)
                                <img src="{{ $attribute->image_url }}"
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
                                        color:
                                            {{ $attribute->color ?? '#6366F1' }};
                                    ">
                                    {{ $attribute->icon ?: '☷' }}
                                </div>
                            @endif

                        </div>
                    @endforeach


                    <div x-show="
                            selectedAttributes.length === 0
                        "
                        class="
                            col-span-2
                            flex
                            items-center
                            justify-center
                            text-5xl
                            font-black
                        "
                        :style="`
                                                    color:
                                                        ${color};
                                                `">
                        <span
                            x-text="
                                icon
                                || '▥'
                            "></span>
                    </div>

                </div>


                {{-- HEADER --}}
                <div class="p-5">

                    <p
                        class="
                            font-mono
                            text-[10px]
                            font-black
                            text-slate-400
                        ">
                        {{ $editing ? $attributeGroup->code : $previewCode }}
                    </p>


                    <div
                        class="
                            mt-2
                            flex
                            items-center
                            gap-2
                        ">

                        <span
                            x-text="
                                icon
                                || '▥'
                            "
                            class="text-xl"
                            :style="`
                                                            color:
                                                                ${color};
                                                        `"></span>


                        <h4 x-text="
                                name
                                || 'Nuevo grupo'
                            "
                            class="
                                text-xl
                                font-black
                                text-slate-900
                            ">
                        </h4>

                    </div>


                    <div
                        class="
                            mt-4
                            flex
                            flex-wrap
                            gap-2
                        ">

                        <span
                            class="
                                rounded-full
                                bg-indigo-50
                                px-2.5
                                py-1
                                text-[9px]
                                font-black
                                text-indigo-700
                            ">
                            <span
                                x-text="
                                    selectedAttributes.length
                                "></span>

                            atributos
                        </span>


                        <span
                            class="
                                rounded-full
                                bg-slate-100
                                px-2.5
                                py-1
                                text-[9px]
                                font-black
                                text-slate-600
                            "
                            x-text="
                                layoutType
                            "></span>

                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- PREVIEW EN ENTIDAD --}}
        {{-- ===================================================== --}}

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
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-indigo-400
                ">
                Ejemplo en una Entidad
            </p>


            <div
                class="
                    mt-4
                    rounded-xl
                    bg-white
                    p-4
                ">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                    ">

                    <div
                        class="
                            flex
                            items-center
                            gap-2
                        ">

                        <span
                            x-text="
                                icon
                                || '▥'
                            "
                            :style="`
                                                            color:
                                                                ${color};
                                                        `"></span>


                        <span
                            x-text="
                                name
                                || 'Grupo'
                            "
                            class="
                                text-xs
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-700
                            "></span>

                    </div>


                    <span x-show="
                            collapsible
                        "
                        class="
                            text-xs
                            text-slate-400
                        "
                        x-text="
                            expanded
                                ? '▲'
                                : '▼'
                        "></span>

                </div>


                <div x-show="
                        ! collapsible
                        ||
                        expanded
                    "
                    class="
                        mt-4
                        space-y-2
                    ">

                    <div
                        class="
                            flex
                            justify-between
                            rounded-lg
                            bg-slate-50
                            px-3
                            py-2
                        ">
                        <span class="text-xs text-slate-500">
                            Atributo
                        </span>

                        <span class="text-xs font-bold text-slate-700">
                            Valor
                        </span>
                    </div>


                    <div
                        class="
                            flex
                            justify-between
                            rounded-lg
                            bg-slate-50
                            px-3
                            py-2
                        ">
                        <span class="text-xs text-slate-500">
                            Atributo
                        </span>

                        <span class="text-xs font-bold text-slate-700">
                            Valor
                        </span>
                    </div>

                </div>

            </div>

        </div>

    </aside>

</div>

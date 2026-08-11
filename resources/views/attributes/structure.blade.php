<x-app-layout>

    <x-slot name="header">
        Estructura de Atributos
    </x-slot>


    @include('attributes.partials.section-navigation')


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <div x-data="attributeStructureBuilder({
        attributes: @js($attributePayload)
    })">

        @if (session('success'))
            <div
                class="
                    mb-5
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
                    mb-5
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    p-4
                ">

                @foreach ($errors->all() as $error)
                    <p
                        class="
                            text-xs
                            font-bold
                            text-red-600
                        ">
                        {{ $error }}
                    </p>
                @endforeach

            </div>

        @endif


        {{-- HERO --}}
        <section
            class="
                overflow-hidden
                rounded-3xl
                bg-gradient-to-br
                from-slate-950
                via-indigo-950
                to-violet-950
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
                            text-indigo-300
                        ">
                        Motor contextual
                    </p>


                    <h1
                        class="
                            mt-2
                            text-3xl
                            font-black
                            sm:text-4xl
                        ">
                        Estructura de Atributos
                    </h1>


                    <p
                        class="
                            mt-3
                            max-w-3xl
                            text-sm
                            leading-6
                            text-white/60
                        ">
                        Define cuándo un Atributo existe,
                        cuándo es obligatorio y qué elementos
                        de un Catálogo están disponibles según
                        otros valores.
                    </p>

                </div>


                <a href="{{ route('attributes.index') }}"
                    class="
                        rounded-xl
                        bg-white/10
                        px-4
                        py-2.5
                        text-center
                        text-xs
                        font-black
                        text-white
                    ">
                    ← Atributos
                </a>

            </div>


            <div
                class="
                    mt-7
                    grid
                    grid-cols-2
                    gap-2
                    md:grid-cols-4
                ">

                @foreach ([['Atributos', $stats['attributes']], ['Dependencias', $stats['relationships']], ['Reglas', $stats['rules']], ['Mapeos', $stats['option_relationships']]] as [$label, $value])
                    <div
                        class="
                            rounded-2xl
                            bg-white/10
                            p-4
                        ">

                        <p
                            class="
                                text-[8px]
                                font-black
                                uppercase
                                text-white/40
                            ">
                            {{ $label }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-2xl
                                font-black
                            ">
                            {{ $value }}
                        </p>

                    </div>
                @endforeach

            </div>

        </section>


        {{-- TABS --}}
        <nav
            class="
                mt-5
                overflow-x-auto
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-2
            ">

            <div
                class="
                    inline-flex
                    min-w-max
                    gap-1
                ">

                <button type="button" @click="
                        tab = 'STRUCTURE'
                    "
                    class="
                        rounded-xl
                        px-4
                        py-2.5
                        text-xs
                        font-black
                    "
                    :class="tab === 'STRUCTURE'
                        ?
                        'bg-indigo-600 text-white' :
                        'text-slate-500 hover:bg-slate-50'">
                    ⌘ Estructura
                </button>


                <button type="button" @click="
                        tab = 'RULES'
                    "
                    class="
                        rounded-xl
                        px-4
                        py-2.5
                        text-xs
                        font-black
                    "
                    :class="tab === 'RULES'
                        ?
                        'bg-violet-600 text-white' :
                        'text-slate-500 hover:bg-slate-50'">
                    ⚡ Reglas
                </button>


                <button type="button" @click="
                        tab = 'CATALOGS'
                    "
                    class="
                        rounded-xl
                        px-4
                        py-2.5
                        text-xs
                        font-black
                    "
                    :class="tab === 'CATALOGS'
                        ?
                        'bg-fuchsia-600 text-white' :
                        'text-slate-500 hover:bg-slate-50'">
                    ◆ Dependencias de Catálogos
                </button>

            </div>

        </nav>


        {{-- ===================================================== --}}
        {{-- ESTRUCTURA --}}
        {{-- ===================================================== --}}

        <section x-show="
                tab === 'STRUCTURE'
            "
            class="
                mt-5
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
                        text-indigo-500
                    ">
                    Jerarquía calculada
                </p>


                <h2
                    class="
                        mt-1
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Relaciones estructurales
                </h2>


                <p
                    class="
                        mt-2
                        max-w-3xl
                        text-sm
                        leading-6
                        text-slate-500
                    ">
                    El nivel se calcula automáticamente.
                    Ya no necesitas decidir manualmente
                    qué Atributo es nivel 0, 1 o 2.
                </p>

            </div>


            @if ($relationships->isEmpty())

                <div
                    class="
                        mt-6
                        rounded-2xl
                        border
                        border-dashed
                        border-indigo-200
                        bg-indigo-50/30
                        p-10
                        text-center
                    ">

                    <p
                        class="
                            text-sm
                            font-black
                            text-slate-600
                        ">
                        Todavía no existen dependencias.
                    </p>


                    <button type="button" @click="
                            tab = 'RULES'
                        "
                        class="
                            mt-3
                            text-xs
                            font-black
                            text-indigo-600
                            underline
                        ">
                        Crear la primera regla
                    </button>

                </div>
            @else
                <div class="
                        mt-6
                        space-y-3
                    ">

                    @foreach ($relationships as $relationship)
                        <article
                            class="
                                grid
                                gap-3
                                rounded-2xl
                                border
                                border-slate-200
                                bg-slate-50
                                p-4
                                md:grid-cols-[1fr_auto_1fr]
                                md:items-center
                            ">

                            <div
                                class="
                                    rounded-xl
                                    bg-white
                                    p-3
                                ">

                                <p
                                    class="
                                        text-[8px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    Superior · nivel
                                    {{ $relationship->sourceAttribute?->hierarchy_level }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $relationship->sourceAttribute?->name }}
                                </p>

                            </div>


                            <div
                                class="
                                    text-center
                                    text-xl
                                    font-black
                                    text-indigo-400
                                ">
                                →
                            </div>


                            <div
                                class="
                                    rounded-xl
                                    bg-indigo-50
                                    p-3
                                ">

                                <p
                                    class="
                                        text-[8px]
                                        font-black
                                        uppercase
                                        text-indigo-400
                                    ">
                                    Dependiente · nivel
                                    {{ $relationship->targetAttribute?->hierarchy_level }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        font-black
                                        text-indigo-800
                                    ">
                                    {{ $relationship->targetAttribute?->name }}
                                </p>

                            </div>

                        </article>
                    @endforeach

                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- REGLAS --}}
        {{-- ===================================================== --}}

        <section x-show="
                tab === 'RULES'
            " x-cloak
            class="
                mt-5
                grid
                gap-5
                xl:grid-cols-[430px_minmax(0,1fr)]
            ">

            {{-- BUILDER --}}
            <form method="POST" action="{{ route('attributes.structure.rules.store') }}"
                class="
                    rounded-3xl
                    border
                    border-violet-200
                    bg-white
                    p-5
                    shadow-sm
                ">

                @csrf


                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-500
                    ">
                    Nueva regla
                </p>


                <h2
                    class="
                        mt-1
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Crear comportamiento contextual
                </h2>


                <div class="mt-5">

                    <label
                        class="
                            text-xs
                            font-black
                            text-slate-700
                        ">
                        Atributo afectado
                    </label>


                    <select name="target_attribute_id" required
                        class="
                            mt-2
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


                <div
                    class="
                        mt-4
                        grid
                        grid-cols-2
                        gap-3
                    ">

                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Acción
                        </label>


                        <select name="action"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                                text-xs
                            ">

                            <option value="SHOW">
                                Mostrar
                            </option>

                            <option value="REQUIRE">
                                Mostrar + requerir
                            </option>

                            <option value="HIDE">
                                Ocultar
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Condiciones
                        </label>


                        <select name="match_mode"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                                text-xs
                            ">

                            <option value="ALL">
                                Todas — AND
                            </option>

                            <option value="ANY">
                                Cualquiera — OR
                            </option>

                        </select>

                    </div>

                </div>


                <div class="mt-4">

                    <label
                        class="
                            text-xs
                            font-black
                            text-slate-700
                        ">
                        Nombre opcional
                    </label>


                    <input type="text" name="name" placeholder="Ej. Atributos exclusivos de Naruto"
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-sm
                        ">

                </div>


                <div
                    class="
                        mt-5
                        border-t
                        border-slate-100
                        pt-5
                    ">

                    <div
                        class="
                            flex
                            items-center
                            justify-between
                            gap-3
                        ">

                        <p
                            class="
                                text-xs
                                font-black
                                text-slate-700
                            ">
                            Condiciones
                        </p>


                        <button type="button"
                            @click="
                                addCondition()
                            "
                            class="
                                rounded-lg
                                bg-violet-50
                                px-3
                                py-2
                                text-[9px]
                                font-black
                                text-violet-700
                            ">
                            + Condición
                        </button>

                    </div>


                    <div
                        class="
                            mt-3
                            space-y-3
                        ">

                        <template
                            x-for="
                                (condition, index)
                                in conditions
                            "
                            :key="condition.key">

                            <article
                                class="
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-slate-50
                                    p-3
                                ">

                                <select :name="`conditions[${index}][source_attribute_id]`"
                                    x-model="
                                        condition.source_attribute_id
                                    "
                                    required
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-xs
                                    ">

                                    <option value="">
                                        Cuando el Atributo...
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


                                <div
                                    class="
                                        mt-2
                                        grid
                                        gap-2
                                        sm:grid-cols-2
                                    ">

                                    <select :name="`conditions[${index}][operator]`"
                                        x-model="
                                            condition.operator
                                        "
                                        class="
                                            w-full
                                            rounded-xl
                                            border-slate-300
                                            text-xs
                                        ">

                                        <option value="EQUALS">
                                            es
                                        </option>

                                        <option value="NOT_EQUALS">
                                            no es
                                        </option>

                                        <option value="EXISTS">
                                            tiene valor
                                        </option>

                                        <option value="NOT_EXISTS">
                                            no tiene valor
                                        </option>

                                    </select>


                                    <select
                                        x-show="
                                            needsOption(
                                                condition
                                            )
                                        "
                                        :name="`conditions[${index}][source_option_id]`"
                                        x-model="
                                            condition.source_option_id
                                        "
                                        class="
                                            w-full
                                            rounded-xl
                                            border-slate-300
                                            text-xs
                                        ">

                                        <option value="">
                                            Elemento...
                                        </option>


                                        <template
                                            x-for="
                                                option
                                                in optionsFor(
                                                    condition.source_attribute_id
                                                )
                                            "
                                            :key="option.id">

                                            <option :value="option.id"
                                                x-text="
                                                    option.name
                                                ">
                                            </option>

                                        </template>

                                    </select>

                                </div>


                                <button
                                    x-show="
                                        conditions.length > 1
                                    "
                                    type="button"
                                    @click="
                                        removeCondition(
                                            index
                                        )
                                    "
                                    class="
                                        mt-2
                                        text-[9px]
                                        font-black
                                        text-red-500
                                    ">
                                    × Quitar condición
                                </button>

                            </article>

                        </template>

                    </div>

                </div>


                <button
                    class="
                        mt-5
                        w-full
                        rounded-xl
                        bg-violet-600
                        px-4
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear regla
                </button>

            </form>


            {{-- LISTADO --}}
            <div
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                ">

                <h2
                    class="
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Reglas existentes
                </h2>


                <div class="
                        mt-5
                        space-y-4
                    ">

                    @forelse ($rules as $rule)

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
                                    items-start
                                    justify-between
                                    gap-4
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
                                                px-2.5
                                                py-1
                                                text-[8px]
                                                font-black

                                                {{ $rule->action === 'SHOW'
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : ($rule->action === 'REQUIRE'
                                                        ? 'bg-amber-50 text-amber-700'
                                                        : 'bg-red-50 text-red-600') }}
                                            ">
                                            {{ $rule->action_label }}
                                        </span>


                                        <span
                                            class="
                                                rounded-full
                                                bg-slate-100
                                                px-2.5
                                                py-1
                                                text-[8px]
                                                font-black
                                                text-slate-500
                                            ">
                                            {{ $rule->match_mode }}
                                        </span>

                                    </div>


                                    <p
                                        class="
                                            mt-3
                                            text-sm
                                            font-black
                                            text-slate-800
                                        ">
                                        {{ $rule->targetAttribute?->name }}
                                    </p>


                                    @if ($rule->name)
                                        <p
                                            class="
                                                mt-1
                                                text-[9px]
                                                text-slate-400
                                            ">
                                            {{ $rule->name }}
                                        </p>
                                    @endif

                                </div>


                                <form method="POST"
                                    action="{{ route('attributes.structure.rules.destroy', $rule) }}"
                                    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                    data-confirm-title="Eliminar regla contextual"
                                    data-confirm-message="
        La dependencia dejará
        de aplicarse a las Entidades.
    "
                                    data-confirm-subject="{{ $rule->name ?: $rule->targetAttribute?->name ?? 'Regla contextual' }}"
                                    data-confirm-detail="
        Los Atributos y sus valores
        no serán eliminados.
    "
                                    data-confirm-action="Eliminar regla">

                                    @csrf
                                    @method('DELETE')


                                    <button
                                        class="
                                            rounded-lg
                                            bg-red-50
                                            px-3
                                            py-2
                                            text-[9px]
                                            font-black
                                            text-red-600
                                        ">
                                        Eliminar
                                    </button>

                                </form>

                            </div>


                            <div
                                class="
                                    mt-4
                                    space-y-2
                                ">

                                @foreach ($rule->conditions as $condition)
                                    <div
                                        class="
                                            flex
                                            flex-wrap
                                            items-center
                                            gap-2
                                            rounded-xl
                                            bg-slate-50
                                            px-3
                                            py-2
                                            text-[10px]
                                        ">

                                        <strong>
                                            {{ $condition->sourceAttribute?->name }}
                                        </strong>


                                        <span class="text-slate-400">
                                            {{ $condition->operator_label }}
                                        </span>


                                        @if ($condition->sourceOption)
                                            <strong class="text-violet-600">
                                                {{ $condition->sourceOption->name }}
                                            </strong>
                                        @endif

                                    </div>
                                @endforeach

                            </div>

                        </article>

                    @empty

                        <div
                            class="
                                rounded-2xl
                                border
                                border-dashed
                                border-slate-300
                                p-10
                                text-center
                                text-sm
                                text-slate-400
                            ">
                            Todavía no existen reglas.
                        </div>

                    @endforelse

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- CATÁLOGOS --}}
        {{-- ===================================================== --}}

        <section x-show="
                tab === 'CATALOGS'
            " x-cloak
            class="
                mt-5
                grid
                gap-5
                xl:grid-cols-[430px_minmax(0,1fr)]
            ">

            <form method="POST" action="{{ route('attributes.structure.options.store') }}"
                class="
                    rounded-3xl
                    border
                    border-fuchsia-200
                    bg-white
                    p-5
                    shadow-sm
                ">

                @csrf


                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        text-fuchsia-500
                    ">
                    Dependencia de Catálogos
                </p>


                <h2
                    class="
                        mt-1
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Filtrar elementos
                </h2>


                <p
                    class="
                        mt-2
                        text-xs
                        leading-5
                        text-slate-500
                    ">
                    Ejemplo: cuando País = Perú,
                    Región puede mostrar solamente Tacna,
                    Lima, Arequipa, etc.
                </p>


                <div class="mt-5">

                    <label
                        class="
                            text-xs
                            font-black
                            text-slate-700
                        ">
                        Catálogo fuente
                    </label>


                    <select x-model="
                            mappingSourceAttribute
                        "
                        class="
                            mt-2
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
                                attribute
                                in catalogAttributes()
                            "
                            :key="attribute.id">

                            <option :value="attribute.id"
                                x-text="
                                    attribute.name
                                ">
                            </option>

                        </template>

                    </select>


                    <select name="source_option_id" required
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-xs
                        ">

                        <option value="">
                            Elemento fuente...
                        </option>


                        <template
                            x-for="
                                option
                                in optionsFor(
                                    mappingSourceAttribute
                                )
                            "
                            :key="option.id">

                            <option :value="option.id"
                                x-text="
                                    option.name
                                ">
                            </option>

                        </template>

                    </select>

                </div>


                <div
                    class="
                        my-4
                        text-center
                        text-2xl
                        font-black
                        text-fuchsia-300
                    ">
                    ↓
                </div>


                <div>

                    <label
                        class="
                            text-xs
                            font-black
                            text-slate-700
                        ">
                        Catálogo dependiente
                    </label>


                    <select x-model="
                            mappingTargetAttribute
                        "
                        class="
                            mt-2
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
                                attribute
                                in catalogAttributes()
                            "
                            :key="attribute.id">

                            <option :value="attribute.id"
                                x-text="
                                    attribute.name
                                ">
                            </option>

                        </template>

                    </select>


                    <select name="target_option_id" required
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-xs
                        ">

                        <option value="">
                            Elemento dependiente...
                        </option>


                        <template
                            x-for="
                                option
                                in optionsFor(
                                    mappingTargetAttribute
                                )
                            "
                            :key="option.id">

                            <option :value="option.id"
                                x-text="
                                    option.name
                                ">
                            </option>

                        </template>

                    </select>

                </div>


                <select name="relationship_type"
                    class="
                        mt-4
                        w-full
                        rounded-xl
                        border-slate-300
                        text-xs
                    ">

                    <option value="ALLOWS">
                        Permitir este elemento
                    </option>

                    <option value="BLOCKS">
                        Bloquear este elemento
                    </option>

                </select>


                <button
                    class="
                        mt-4
                        w-full
                        rounded-xl
                        bg-fuchsia-600
                        px-4
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Guardar dependencia
                </button>

            </form>


            <div
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <h2
                    class="
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Relaciones de elementos
                </h2>


                <div class="
                        mt-5
                        space-y-3
                    ">

                    @forelse ($optionRelationships
                        as $relationship)
                        <article
                            class="
                                flex
                                flex-col
                                gap-3
                                rounded-2xl
                                border
                                border-slate-200
                                bg-slate-50
                                p-4
                                md:flex-row
                                md:items-center
                            ">

                            <div class="flex-1">

                                <p
                                    class="
                                        text-[8px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    {{ $relationship->sourceOption?->attribute?->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $relationship->sourceOption?->name }}
                                </p>

                            </div>


                            <span
                                class="
                                    rounded-full
                                    px-3
                                    py-1
                                    text-[8px]
                                    font-black

                                    {{ $relationship->relationship_type === 'ALLOWS'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-red-100 text-red-600' }}
                                ">
                                {{ $relationship->relationship_type === 'ALLOWS' ? 'PERMITE' : 'BLOQUEA' }}
                            </span>


                            <div class="flex-1">

                                <p
                                    class="
                                        text-[8px]
                                        font-black
                                        uppercase
                                        text-slate-400
                                    ">
                                    {{ $relationship->targetOption?->attribute?->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-sm
                                        font-black
                                        text-fuchsia-700
                                    ">
                                    {{ $relationship->targetOption?->name }}
                                </p>

                            </div>


                            <form method="POST"
                                action="{{ route('attributes.structure.options.destroy', $relationship) }}"
                                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                data-confirm-title="Eliminar dependencia de Catálogo"
                                data-confirm-message="
        Esta relación entre elementos
        dejará de aplicarse.
    "
                                data-confirm-subject="{{ ($relationship->sourceOption?->name ?? 'Origen') . ' → ' . ($relationship->targetOption?->name ?? 'Destino') }}"
                                data-confirm-detail="
        Los elementos de Catálogo
        no serán eliminados.
    "
                                data-confirm-action="Eliminar dependencia">

                                @csrf
                                @method('DELETE')


                                <button
                                    class="
                                        rounded-lg
                                        bg-red-50
                                        px-3
                                        py-2
                                        text-[9px]
                                        font-black
                                        text-red-600
                                    ">
                                    ×
                                </button>

                            </form>

                        </article>

                    @empty

                        <div
                            class="
                                rounded-2xl
                                border
                                border-dashed
                                border-slate-300
                                p-10
                                text-center
                                text-sm
                                text-slate-400
                            ">
                            Sin relaciones entre Catálogos.
                        </div>
                    @endforelse

                </div>

            </div>

        </section>

    </div>


    <script>
        function attributeStructureBuilder(
            config
        ) {

            return {

                tab: 'STRUCTURE',

                attributes: config.attributes ?? [],

                conditions: [{
                    key: `${Date.now()}-1`,

                    source_attribute_id: '',

                    operator: 'EQUALS',

                    source_option_id: '',
                }],

                mappingSourceAttribute: '',

                mappingTargetAttribute: '',


                addCondition() {

                    this.conditions.push({
                        key: `${Date.now()}-${Math.random()}`,

                        source_attribute_id: '',

                        operator: 'EQUALS',

                        source_option_id: '',
                    });
                },


                removeCondition(
                    index
                ) {

                    this.conditions.splice(
                        index,
                        1
                    );
                },


                needsOption(
                    condition
                ) {

                    return [
                        'EQUALS',
                        'NOT_EQUALS',
                    ].includes(
                        condition.operator
                    );
                },


                optionsFor(
                    attributeId
                ) {

                    return this.attributes.find(
                            item =>
                            String(item.id) ===
                            String(attributeId)
                        )
                        ?.options ?? [];
                },


                catalogAttributes() {

                    return this.attributes.filter(
                        item =>
                        item.data_type ===
                        'OPTION'
                    );
                },
            };
        }
    </script>

</x-app-layout>

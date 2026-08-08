<x-app-layout>

    <x-slot name="header">
        Catálogos
    </x-slot>


    <div x-data="{
    
        childrenView: localStorage.getItem(
                'omnimerge.catalogChildren.view'
            ) ||
            'grid',
    
    
        setChildrenView(value) {
    
            this.childrenView =
                value;
    
            localStorage.setItem(
                'omnimerge.catalogChildren.view',
                value
            );
        }
    }">

        {{-- ===================================================== --}}
        {{-- BREADCRUMB --}}
        {{-- ===================================================== --}}

        <nav
            class="
                mb-5
                flex
                flex-wrap
                items-center
                gap-2
                text-xs
                font-bold
                text-slate-400
            ">

            <a href="{{ route('attribute-options.index') }}" class="hover:text-violet-600">
                Catálogos
            </a>


            <span>›</span>


            <a href="{{ route('attributes.show', $attributeOption->attribute) }}"
                class="hover:text-violet-600">
                {{ $attributeOption->attribute->name }}
            </a>


            @foreach ($ancestors as $ancestor)
                <span>›</span>


                <a href="{{ route('attribute-options.show', $ancestor) }}"
                    class="hover:text-violet-600">
                    {{ $ancestor->name }}
                </a>
            @endforeach


            <span>›</span>


            <span class="text-slate-700">
                {{ $attributeOption->name }}
            </span>

        </nav>


        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}

        <article
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
                    grid
                    lg:grid-cols-[380px_minmax(0,1fr)]
                ">

                {{-- IMAGEN --}}
                <div
                    class="
                        min-h-[320px]
                        bg-slate-100
                    ">

                    @if ($attributeOption->image_url)
                        <img src="{{ $attributeOption->image_url }}" alt="{{ $attributeOption->name }}"
                            class="
                                h-full
                                min-h-[320px]
                                w-full
                                object-cover
                            ">
                    @else
                        <div class="
                                flex
                                h-full
                                min-h-[320px]
                                items-center
                                justify-center
                                text-8xl
                                font-black
                            "
                            style="
                                background-color:
                                    {{ $attributeOption->color ?? '#6366F1' }}20;

                                color:
                                    {{ $attributeOption->color ?? '#6366F1' }};
                            ">
                            {{ $attributeOption->icon ?: '◆' }}
                        </div>
                    @endif

                </div>


                {{-- INFORMACIÓN --}}
                <div
                    class="
                        flex
                        flex-col
                        justify-between
                        p-6
                        sm:p-8
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
                                    bg-violet-50
                                    px-3
                                    py-1
                                    font-mono
                                    text-[10px]
                                    font-black
                                    text-violet-700
                                ">
                                {{ $attributeOption->code }}
                            </span>


                            <x-status-badge :status="$attributeOption->status" />


                            <span
                                class="
                                    rounded-full
                                    bg-slate-100
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    text-slate-600
                                ">
                                {{ $attributeOption->hierarchy_label }}
                            </span>

                        </div>


                        <h1
                            class="
                                mt-5
                                text-3xl
                                font-black
                                tracking-tight
                                text-slate-900
                                sm:text-4xl
                            ">
                            {{ $attributeOption->name }}
                        </h1>


                        <p
                            class="
                                mt-5
                                max-w-3xl
                                whitespace-pre-line
                                leading-7
                                text-slate-600
                            ">
                            {{ $attributeOption->description ?: 'Este elemento todavía no tiene una descripción.' }}
                        </p>

                    </div>


                    <div
                        class="
                            mt-8
                            flex
                            flex-wrap
                            gap-3
                        ">

                        <a href="{{ route('attribute-options.edit', $attributeOption) }}"
                            class="
                                rounded-xl
                                bg-violet-600
                                px-5
                                py-3
                                text-sm
                                font-black
                                text-white
                                hover:bg-violet-700
                            ">
                            Editar elemento
                        </a>


                        @if ($attributeOption->values_count === 0 && $attributeOption->children_count === 0)
                            <form method="POST"
                                action="{{ route('attribute-options.destroy', $attributeOption) }}"
                                onsubmit="
                                    return confirm(
                                        '¿Eliminar definitivamente este elemento?'
                                    )
                                ">

                                @csrf
                                @method('DELETE')


                                <button type="submit"
                                    class="
                                        rounded-xl
                                        border
                                        border-red-200
                                        px-5
                                        py-3
                                        text-sm
                                        font-bold
                                        text-red-600
                                        hover:bg-red-50
                                    ">
                                    Eliminar
                                </button>

                            </form>
                        @else
                            <span title="Este elemento tiene uso o subelementos. Archívalo en lugar de eliminarlo."
                                class="
                                    cursor-not-allowed
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-slate-50
                                    px-5
                                    py-3
                                    text-sm
                                    font-bold
                                    text-slate-300
                                ">
                                Eliminar
                            </span>
                        @endif

                    </div>

                </div>

            </div>

        </article>


        {{-- ===================================================== --}}
        {{-- STATS --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-6
                grid
                gap-4
                sm:grid-cols-2
                xl:grid-cols-4
            ">

            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Utilizado en entidades
                </p>


                <p
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeOption->values_count }}
                </p>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Subelementos
                </p>


                <p
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeOption->children_count }}
                </p>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Nivel jerárquico
                </p>


                <p
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeOption->hierarchy_depth }}
                </p>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Creado
                </p>


                <p
                    class="
                        mt-2
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeOption->created_at->format('d/m/Y') }}
                </p>

            </article>

        </section>


        {{-- ===================================================== --}}
        {{-- CATÁLOGO PROPIETARIO --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-8
                rounded-3xl
                border
                border-violet-100
                bg-violet-50
                p-6
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-violet-500
                ">
                Pertenece al Catálogo
            </p>


            <div
                class="
                    mt-4
                    flex
                    flex-col
                    gap-5
                    sm:flex-row
                    sm:items-center
                ">

                <div
                    class="
                        h-20
                        w-20
                        shrink-0
                        overflow-hidden
                        rounded-2xl
                        bg-white
                    ">

                    @if ($attributeOption->attribute->image_url)
                        <img src="{{ $attributeOption->attribute->image_url }}"
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
                                text-3xl
                                font-black
                            ">
                            {{ $attributeOption->attribute->icon ?: '◆' }}
                        </div>
                    @endif

                </div>


                <div class="flex-1">

                    <p
                        class="
                            font-mono
                            text-[10px]
                            font-black
                            text-violet-400
                        ">
                        {{ $attributeOption->attribute->code }}
                    </p>


                    <h2
                        class="
                            mt-1
                            text-2xl
                            font-black
                            text-violet-950
                        ">
                        {{ $attributeOption->attribute->name }}
                    </h2>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-violet-700
                        ">
                        {{ $attributeOption->attribute->options_count }}
                        elementos

                        ·

                        {{ $attributeOption->attribute->selection_mode_label }}
                    </p>

                </div>


                <a href="{{ route('attributes.show', $attributeOption->attribute) }}"
                    class="
                        rounded-xl
                        bg-white
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-violet-700
                    ">
                    Abrir atributo →
                </a>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- APARIENCIA / VALOR --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-8
                grid
                gap-4
                md:grid-cols-3
            ">

            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Icono
                </p>


                <p class="
                        mt-4
                        text-4xl
                    ">
                    {{ $attributeOption->icon ?: '◆' }}
                </p>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Color
                </p>


                <div
                    class="
                        mt-4
                        flex
                        items-center
                        gap-3
                    ">

                    <span
                        class="
                            h-11
                            w-11
                            rounded-xl
                            border
                            border-slate-200
                        "
                        style="
                            background-color:
                                {{ $attributeOption->color ?? '#6366F1' }};
                        "></span>


                    <span
                        class="
                            font-mono
                            text-sm
                            font-bold
                            text-slate-700
                        ">
                        {{ $attributeOption->color ?? '#6366F1' }}
                    </span>

                </div>

            </article>


            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Valor de referencia
                </p>


                @if ($attributeOption->numeric_value !== null)
                    <p
                        class="
                            mt-4
                            text-3xl
                            font-black
                            text-slate-900
                        ">
                        {{ $attributeOption->numeric_value }}
                    </p>


                    <p
                        class="
                            mt-2
                            text-xs
                            leading-5
                            text-slate-500
                        ">
                        Valor numérico disponible para
                        comparaciones y reglas.
                    </p>
                @else
                    <p
                        class="
                            mt-4
                            text-sm
                            font-bold
                            text-slate-400
                        ">
                        No definido
                    </p>
                @endif

            </article>

        </section>


        {{-- ===================================================== --}}
        {{-- JERARQUÍA --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-10
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
                sm:p-8
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-violet-600
                ">
                Jerarquía del Catálogo
            </p>


            <h2
                class="
                    mt-2
                    text-2xl
                    font-black
                    text-slate-900
                ">
                Posición de {{ $attributeOption->name }}
            </h2>


            @if ($attributeOption->parent)

                <div
                    class="
                        mt-6
                        rounded-2xl
                        bg-slate-50
                        p-5
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            text-slate-400
                        ">
                        Ruta jerárquica
                    </p>


                    <div
                        class="
                            mt-4
                            flex
                            flex-wrap
                            items-center
                            gap-2
                        ">

                        @foreach ($ancestors as $ancestor)
                            <a href="{{ route('attribute-options.show', $ancestor) }}"
                                class="
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-white
                                    px-3
                                    py-2
                                    text-xs
                                    font-bold
                                    text-slate-700
                                    hover:border-violet-300
                                    hover:text-violet-700
                                ">
                                {{ $ancestor->name }}
                            </a>


                            <span class="text-slate-300">
                                →
                            </span>
                        @endforeach


                        <span
                            class="
                                rounded-xl
                                bg-violet-600
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-white
                            ">
                            {{ $attributeOption->name }}
                        </span>

                    </div>

                </div>
            @else
                <div
                    class="
                        mt-6
                        rounded-2xl
                        border
                        border-blue-100
                        bg-blue-50
                        p-5
                    ">

                    <p
                        class="
                            font-black
                            text-blue-900
                        ">
                        Nivel principal
                    </p>


                    <p
                        class="
                            mt-2
                            text-sm
                            leading-6
                            text-blue-700
                        ">
                        Este elemento no depende de ningún
                        elemento superior.
                    </p>

                </div>

            @endif


            {{-- HIJOS --}}
            <div
                class="
                    mt-8
                    border-t
                    border-slate-100
                    pt-7
                ">

                <div
                    class="
                        flex
                        flex-col
                        justify-between
                        gap-4
                        sm:flex-row
                        sm:items-center
                    ">

                    <div>

                        <h3
                            class="
                                text-lg
                                font-black
                                text-slate-900
                            ">
                            Subelementos
                        </h3>


                        <p
                            class="
                                mt-1
                                text-sm
                                text-slate-500
                            ">
                            Elementos que dependen directamente
                            de {{ $attributeOption->name }}.
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
                                setChildrenView(
                                    'grid'
                                )
                            "
                            :class="childrenView === 'grid'
                            
                                ?
                                'bg-violet-600 text-white'
                            
                                :
                                'bg-slate-100 text-slate-500'"
                            class="
                                rounded-lg
                                px-3
                                py-2
                                text-xs
                                font-bold
                            ">
                            ▦ Cuadrícula
                        </button>


                        <button type="button"
                            @click="
                                setChildrenView(
                                    'list'
                                )
                            "
                            :class="childrenView === 'list'
                            
                                ?
                                'bg-violet-600 text-white'
                            
                                :
                                'bg-slate-100 text-slate-500'"
                            class="
                                rounded-lg
                                px-3
                                py-2
                                text-xs
                                font-bold
                            ">
                            ☰ Lista
                        </button>


                        <a href="{{ route('attribute-options.create', [
                            'attribute' => $attributeOption->attribute_id,
                        
                            'parent' => $attributeOption->id,
                        ]) }}"
                            class="
                                rounded-lg
                                bg-slate-900
                                px-3
                                py-2
                                text-xs
                                font-black
                                text-white
                            ">
                            + Nuevo subelemento
                        </a>

                    </div>

                </div>


                @if ($attributeOption->children->isNotEmpty())

                    {{-- GRID --}}
                    <div x-show="
                            childrenView === 'grid'
                        "
                        class="
                            mt-5
                            grid
                            gap-4
                            sm:grid-cols-2
                            lg:grid-cols-3
                        ">

                        @foreach ($attributeOption->children as $child)
                            <a href="{{ route('attribute-options.show', $child) }}"
                                class="
                                    group
                                    overflow-hidden
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-white
                                    transition
                                    hover:border-violet-200
                                    hover:shadow-lg
                                ">

                                <div
                                    class="
                                        aspect-[16/8]
                                        bg-slate-100
                                    ">

                                    @if ($child->image_url)
                                        <img src="{{ $child->image_url }}"
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
                                                text-4xl
                                            "
                                            style="
                                                background-color:
                                                    {{ $child->color ?? '#6366F1' }}20;
                                            ">
                                            {{ $child->icon ?: '◆' }}
                                        </div>
                                    @endif

                                </div>


                                <div class="p-4">

                                    <p
                                        class="
                                            font-mono
                                            text-[9px]
                                            font-black
                                            text-slate-400
                                        ">
                                        {{ $child->code }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            font-black
                                            text-slate-900
                                            group-hover:text-violet-700
                                        ">
                                        {{ $child->name }}
                                    </p>


                                    <div
                                        class="
                                            mt-3
                                            flex
                                            gap-4
                                            text-xs
                                            text-slate-400
                                        ">

                                        <span>
                                            {{ $child->values_count }}
                                            usos
                                        </span>

                                        <span>
                                            {{ $child->children_count }}
                                            hijos
                                        </span>

                                    </div>

                                </div>

                            </a>
                        @endforeach

                    </div>


                    {{-- LIST --}}
                    <div x-cloak x-show="
                            childrenView === 'list'
                        "
                        class="
                            mt-5
                            space-y-3
                        ">

                        @foreach ($attributeOption->children as $child)
                            <a href="{{ route('attribute-options.show', $child) }}"
                                class="
                                    flex
                                    items-center
                                    gap-4
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    p-4
                                    hover:border-violet-200
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

                                    @if ($child->image_url)
                                        <img src="{{ $child->image_url }}"
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
                                            ">
                                            {{ $child->icon ?: '◆' }}
                                        </div>
                                    @endif

                                </div>


                                <div class="flex-1">

                                    <p
                                        class="
                                            font-black
                                            text-slate-900
                                        ">
                                        {{ $child->name }}
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            font-mono
                                            text-[10px]
                                            text-slate-400
                                        ">
                                        {{ $child->code }}
                                    </p>

                                </div>


                                <span
                                    class="
                                        text-xs
                                        font-bold
                                        text-slate-400
                                    ">
                                    {{ $child->values_count }}
                                    usos
                                </span>


                                <span
                                    class="
                                        text-xs
                                        font-black
                                        text-violet-600
                                    ">
                                    Abrir →
                                </span>

                            </a>
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
                            py-10
                            text-center
                        ">

                        <p
                            class="
                                text-sm
                                font-bold
                                text-slate-500
                            ">
                            Este elemento no tiene subelementos.
                        </p>

                    </div>

                @endif

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- FUTURO --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-8
                rounded-3xl
                border
                border-dashed
                border-violet-200
                bg-violet-50/50
                p-6
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-500
                ">
                Preparado para OmniMerge
            </p>


            <h3
                class="
                    mt-2
                    text-lg
                    font-black
                    text-violet-950
                ">
                Referencias futuras
            </h3>


            <p
                class="
                    mt-2
                    max-w-3xl
                    text-sm
                    leading-6
                    text-violet-700
                ">
                Este elemento ya posee una identidad estable
                que posteriormente podrá ser referenciada desde
                Universos, Torneos, filtros y reglas sin
                duplicar su información.
            </p>

        </section>

    </div>

</x-app-layout>

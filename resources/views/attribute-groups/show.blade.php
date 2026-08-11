<x-app-layout>

    <x-slot name="header">
        Grupos de atributos
    </x-slot>


    @include('attributes.partials.section-navigation')


    <div x-data="{
    
        view: localStorage.getItem(
                'omnimerge.attributeGroup.showView'
            ) ||
            'grid',
    
    
        setView(value) {
    
            this.view =
                value;
    
            localStorage.setItem(
                'omnimerge.attributeGroup.showView',
                value
            );
        }
    }">

        <div class="mb-5">

            <a href="{{ route('attribute-groups.index') }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Grupos
            </a>

        </div>


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

            <div class="
                    relative
                    overflow-hidden
                    p-7
                    sm:p-9
                "
                style="
                    background:
                        linear-gradient(
                            135deg,
                            {{ $attributeGroup->color ?? '#6366F1' }}18,
                            #ffffff 65%
                        );
                ">

                <div class="
                        absolute
                        -right-16
                        -top-16
                        h-56
                        w-56
                        rounded-full
                        opacity-20
                    "
                    style="
                        background-color:
                            {{ $attributeGroup->color ?? '#6366F1' }};
                    ">
                </div>


                <div
                    class="
                        relative
                        flex
                        flex-col
                        gap-6
                        lg:flex-row
                        lg:items-start
                        lg:justify-between
                    ">

                    <div class="max-w-3xl">

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
                                    bg-white
                                    px-3
                                    py-1
                                    font-mono
                                    text-[10px]
                                    font-black
                                    text-indigo-700
                                    shadow-sm
                                ">
                                {{ $attributeGroup->code }}
                            </span>


                            <x-status-badge :status="$attributeGroup->status" />

                        </div>


                        <div
                            class="
                                mt-6
                                flex
                                items-center
                                gap-4
                            ">

                            <div class="
                                    flex
                                    h-20
                                    w-20
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-2xl
                                    bg-white
                                    text-4xl
                                    font-black
                                    shadow-sm
                                "
                                style="
                                    color:
                                        {{ $attributeGroup->color ?? '#6366F1' }};
                                ">
                                {{ $attributeGroup->icon ?: '▥' }}
                            </div>


                            <div>

                                <h1
                                    class="
                                        text-3xl
                                        font-black
                                        tracking-tight
                                        text-slate-900
                                        sm:text-4xl
                                    ">
                                    {{ $attributeGroup->name }}
                                </h1>


                                <p
                                    class="
                                        mt-2
                                        text-sm
                                        font-bold
                                        text-slate-500
                                    ">
                                    Grupo organizativo de atributos
                                </p>

                            </div>

                        </div>


                        <p
                            class="
                                mt-6
                                max-w-2xl
                                whitespace-pre-line
                                leading-7
                                text-slate-600
                            ">
                            {{ $attributeGroup->description ?: 'Este grupo todavía no tiene una descripción.' }}
                        </p>

                    </div>


                    <div
                        class="
                            relative
                            flex
                            flex-wrap
                            gap-3
                        ">

                        <a href="{{ route('attribute-groups.edit', $attributeGroup) }}"
                            class="
                                rounded-xl
                                bg-indigo-600
                                px-5
                                py-3
                                text-sm
                                font-black
                                text-white
                                shadow-lg
                                shadow-indigo-600/20
                            ">
                            Editar grupo
                        </a>


                        <form method="POST"
                            action="{{ route('attribute-groups.destroy', $attributeGroup) }}"
                            data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                            data-confirm-title="Eliminar Grupo"
                            data-confirm-message="
        Este Grupo será eliminado
        de tu organización de Atributos.
    "
                            data-confirm-subject="{{ $attributeGroup->name }}"
                            data-confirm-detail="
        Los Atributos contenidos en el Grupo
        NO serán eliminados.
    "
                            data-confirm-action="Eliminar Grupo">

                            @csrf
                            @method('DELETE')


                            <button type="submit"
                                class="
                                    rounded-xl
                                    border
                                    border-red-200
                                    bg-white
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

                    </div>

                </div>

            </div>

        </article>


        {{-- ===================================================== --}}
        {{-- ESTADÍSTICAS --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-6
                grid
                grid-cols-2
                gap-3
                md:grid-cols-4
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
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Atributos
                </p>


                <p
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeGroup->attributes->count() }}
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
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Destacados
                </p>


                <p
                    class="
                        mt-2
                        text-3xl
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeGroup->attributes->filter(fn($attribute) => (bool) $attribute->pivot->is_featured)->count() }}
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
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Presentación
                </p>


                <p
                    class="
                        mt-2
                        text-lg
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeGroup->layout_label }}
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
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Comportamiento
                </p>


                <p
                    class="
                        mt-2
                        text-sm
                        font-black
                        text-slate-900
                    ">
                    {{ $attributeGroup->collapsible
                        ? ($attributeGroup->default_expanded
                            ? 'Contraíble · Abierto'
                            : 'Contraíble · Cerrado')
                        : 'Siempre visible' }}
                </p>

            </article>

        </section>


        {{-- ===================================================== --}}
        {{-- PREVIEW DE PRESENTACIÓN --}}
        {{-- ===================================================== --}}

        <section
            class="
                mt-8
                rounded-3xl
                border
                border-indigo-100
                bg-indigo-50
                p-6
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-500
                ">
                Vista en una Entidad
            </p>


            <div
                class="
                    mt-4
                    overflow-hidden
                    rounded-2xl
                    border
                    border-indigo-100
                    bg-white
                ">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                        border-b
                        border-slate-100
                        px-5
                        py-4
                    ">

                    <div
                        class="
                            flex
                            items-center
                            gap-3
                        ">

                        <span class="
                                text-xl
                            "
                            style="
                                color:
                                    {{ $attributeGroup->color ?? '#6366F1' }};
                            ">
                            {{ $attributeGroup->icon ?: '▥' }}
                        </span>


                        <span
                            class="
                                text-sm
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-800
                            ">
                            {{ $attributeGroup->name }}
                        </span>

                    </div>


                    @if ($attributeGroup->collapsible)
                        <span class="text-slate-400">
                            {{ $attributeGroup->default_expanded ? '▲' : '▼' }}
                        </span>
                    @endif

                </div>


                @if (!$attributeGroup->collapsible || $attributeGroup->default_expanded)

                    <div class="p-5">

                        @if ($attributeGroup->layout_type === 'GRID')

                            <div
                                class="
                                    grid
                                    gap-3
                                    sm:grid-cols-2
                                ">

                                @foreach ($attributeGroup->attributes->take(4) as $attribute)
                                    <div
                                        class="
                                            rounded-xl
                                            bg-slate-50
                                            p-4
                                        ">
                                        <p
                                            class="
                                                text-xs
                                                font-black
                                                text-slate-700
                                            ">
                                            {{ $attribute->pivot->custom_label ?: $attribute->name }}
                                        </p>

                                        <p
                                            class="
                                                mt-2
                                                text-sm
                                                text-slate-400
                                            ">
                                            Valor de ejemplo
                                        </p>
                                    </div>
                                @endforeach

                            </div>
                        @elseif ($attributeGroup->layout_type === 'CARDS')
                            <div
                                class="
                                    grid
                                    gap-3
                                    sm:grid-cols-2
                                ">

                                @foreach ($attributeGroup->attributes->take(4) as $attribute)
                                    <div
                                        class="
                                            rounded-2xl
                                            border
                                            border-slate-200
                                            p-4
                                        ">

                                        <p
                                            class="
                                                font-black
                                                text-slate-800
                                            ">
                                            {{ $attribute->pivot->custom_label ?: $attribute->name }}
                                        </p>

                                        <p
                                            class="
                                                mt-3
                                                text-sm
                                                text-slate-400
                                            ">
                                            Valor
                                        </p>

                                    </div>
                                @endforeach

                            </div>
                        @elseif ($attributeGroup->layout_type === 'TABLE')
                            <div
                                class="
                                    overflow-hidden
                                    rounded-xl
                                    border
                                    border-slate-200
                                ">

                                @foreach ($attributeGroup->attributes->take(5) as $attribute)
                                    <div
                                        class="
                                            grid
                                            grid-cols-2
                                            border-b
                                            border-slate-100
                                            last:border-b-0
                                        ">

                                        <div
                                            class="
                                                bg-slate-50
                                                px-4
                                                py-3
                                                text-xs
                                                font-bold
                                                text-slate-600
                                            ">
                                            {{ $attribute->pivot->custom_label ?: $attribute->name }}
                                        </div>


                                        <div
                                            class="
                                                px-4
                                                py-3
                                                text-xs
                                                text-slate-400
                                            ">
                                            Valor
                                        </div>

                                    </div>
                                @endforeach

                            </div>
                        @elseif ($attributeGroup->layout_type === 'COMPACT')
                            <div
                                class="
                                    flex
                                    flex-wrap
                                    gap-2
                                ">

                                @foreach ($attributeGroup->attributes->take(6) as $attribute)
                                    <span
                                        class="
                                            rounded-full
                                            bg-slate-100
                                            px-3
                                            py-2
                                            text-xs
                                            font-bold
                                            text-slate-600
                                        ">
                                        {{ $attribute->pivot->custom_label ?: $attribute->name }}

                                        · Valor
                                    </span>
                                @endforeach

                            </div>
                        @else
                            <div class="space-y-2">

                                @foreach ($attributeGroup->attributes->take(5) as $attribute)
                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            rounded-xl
                                            bg-slate-50
                                            px-4
                                            py-3
                                        ">

                                        <span
                                            class="
                                                text-xs
                                                font-bold
                                                text-slate-600
                                            ">
                                            {{ $attribute->pivot->custom_label ?: $attribute->name }}
                                        </span>


                                        <span
                                            class="
                                                text-xs
                                                text-slate-400
                                            ">
                                            Valor
                                        </span>

                                    </div>
                                @endforeach

                            </div>

                        @endif

                    </div>

                @endif

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ATRIBUTOS --}}
        {{-- ===================================================== --}}

        <section class="mt-10">

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
                        Contenido
                    </p>


                    <h2
                        class="
                            mt-2
                            text-3xl
                            font-black
                            text-slate-900
                        ">
                        Atributos del grupo
                    </h2>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-slate-500
                        ">
                        Los atributos siguen siendo recursos
                        independientes de la Biblioteca.
                    </p>

                </div>


                <div class="flex gap-2">

                    <button type="button"
                        @click="
                            setView(
                                'grid'
                            )
                        "
                        :class="view === 'grid'
                        
                            ?
                            'bg-indigo-600 text-white'
                        
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
                            setView(
                                'list'
                            )
                        "
                        :class="view === 'list'
                        
                            ?
                            'bg-indigo-600 text-white'
                        
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

                </div>

            </div>


            @if ($attributeGroup->attributes->isEmpty())

                <div
                    class="
                        mt-5
                        rounded-3xl
                        border
                        border-dashed
                        border-slate-300
                        bg-white
                        p-12
                        text-center
                    ">

                    <p
                        class="
                            font-black
                            text-slate-700
                        ">
                        Este grupo está vacío
                    </p>


                    <a href="{{ route('attribute-groups.edit', $attributeGroup) }}"
                        class="
                            mt-4
                            inline-flex
                            rounded-xl
                            bg-indigo-600
                            px-4
                            py-2.5
                            text-sm
                            font-black
                            text-white
                        ">
                        Añadir atributos
                    </a>

                </div>
            @else
                {{-- GRID --}}
                <div x-show="
                        view === 'grid'
                    "
                    class="
                        mt-5
                        grid
                        gap-4
                        sm:grid-cols-2
                        lg:grid-cols-3
                        xl:grid-cols-4
                    ">

                    @foreach ($attributeGroup->attributes as $attribute)
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


                        <a href="{{ route('attributes.show', $attribute) }}"
                            class="
                                group
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-sm
                                transition
                                hover:-translate-y-0.5
                                hover:border-indigo-200
                                hover:shadow-lg
                            ">

                            <div
                                class="
                                    relative
                                    aspect-[16/9]
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
                                            text-4xl
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


                                @if ($attribute->pivot->is_featured)
                                    <span
                                        class="
                                            absolute
                                            left-3
                                            top-3
                                            rounded-full
                                            bg-amber-400
                                            px-2.5
                                            py-1
                                            text-[9px]
                                            font-black
                                            text-amber-950
                                            shadow-sm
                                        ">
                                        ★ Destacado
                                    </span>
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
                                    {{ $attribute->code }}
                                </p>


                                <h3
                                    class="
                                        mt-1
                                        font-black
                                        text-slate-900
                                        group-hover:text-indigo-700
                                    ">
                                    {{ $attribute->pivot->custom_label ?: $attribute->name }}
                                </h3>


                                @if ($attribute->pivot->custom_label)
                                    <p
                                        class="
                                            mt-1
                                            text-[10px]
                                            text-slate-400
                                        ">
                                        Atributo:
                                        {{ $attribute->name }}
                                    </p>
                                @endif


                                <div
                                    class="
                                        mt-3
                                        flex
                                        flex-wrap
                                        gap-2
                                    ">

                                    <span
                                        class="
                                            rounded-full
                                            bg-slate-100
                                            px-2.5
                                            py-1
                                            text-[9px]
                                            font-bold
                                            text-slate-600
                                        ">
                                        {{ $typeLabel }}
                                    </span>


                                    @if ($attribute->data_type === 'OPTION')
                                        <span
                                            class="
                                                rounded-full
                                                bg-violet-50
                                                px-2.5
                                                py-1
                                                text-[9px]
                                                font-bold
                                                text-violet-600
                                            ">
                                            {{ $attribute->options_count }}
                                            elementos
                                        </span>
                                    @endif

                                </div>


                                <p
                                    class="
                                        mt-4
                                        line-clamp-2
                                        text-xs
                                        leading-5
                                        text-slate-500
                                    ">
                                    {{ $attribute->description ?: 'Sin descripción.' }}
                                </p>

                            </div>

                        </a>
                    @endforeach

                </div>


                {{-- LIST --}}
                <div x-cloak x-show="
                        view === 'list'
                    "
                    class="
                        mt-5
                        space-y-3
                    ">

                    @foreach ($attributeGroup->attributes as $attribute)
                        <a href="{{ route('attributes.show', $attribute) }}"
                            class="
                                flex
                                flex-col
                                gap-4
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                p-4
                                hover:border-indigo-200
                                sm:flex-row
                                sm:items-center
                            ">

                            <div
                                class="
                                    h-16
                                    w-full
                                    shrink-0
                                    overflow-hidden
                                    rounded-xl
                                    bg-slate-100
                                    sm:w-16
                                ">

                                @if ($attribute->image_url)
                                    <img src="{{ $attribute->image_url }}"
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
                                            text-xl
                                            font-black
                                        ">
                                        {{ $attribute->icon ?: '☷' }}
                                    </div>
                                @endif

                            </div>


                            <div
                                class="
                                    min-w-0
                                    flex-1
                                ">

                                <div
                                    class="
                                        flex
                                        flex-wrap
                                        gap-2
                                    ">

                                    <p
                                        class="
                                            font-black
                                            text-slate-900
                                        ">
                                        {{ $attribute->pivot->custom_label ?: $attribute->name }}
                                    </p>


                                    @if ($attribute->pivot->is_featured)
                                        <span
                                            class="
                                                text-xs
                                                text-amber-500
                                            ">
                                            ★
                                        </span>
                                    @endif

                                </div>


                                <p
                                    class="
                                        mt-1
                                        font-mono
                                        text-[9px]
                                        text-slate-400
                                    ">
                                    {{ $attribute->code }}
                                </p>

                            </div>


                            <span
                                class="
                                    text-xs
                                    font-bold
                                    text-slate-500
                                ">
                                {{ $attribute->data_type }}
                            </span>


                            <span
                                class="
                                    text-xs
                                    font-black
                                    text-indigo-600
                                ">
                                Abrir →
                            </span>

                        </a>
                    @endforeach

                </div>

            @endif

        </section>

    </div>

</x-app-layout>

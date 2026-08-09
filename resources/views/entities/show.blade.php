<x-app-layout>

    <x-slot name="header">
        Entidades
    </x-slot>


    @include('entities.partials.section-navigation')


    <div x-data="{
    
        characteristicView: localStorage.getItem(
                'omnimerge.entity.characteristicView'
            ) ||
            'cards',
    
    
        setCharacteristicView(value) {
    
            this.characteristicView =
                value;
    
            localStorage.setItem(
                'omnimerge.entity.characteristicView',
                value
            );
        }
    }">

        <div class="mb-5">

            <a href="{{ route('entities.index') }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Entidades
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

            <div
                class="
                    grid
                    lg:grid-cols-[420px_minmax(0,1fr)]
                ">

                <div
                    class="
                        min-h-[380px]
                        bg-slate-100
                    ">

                    @if ($entity->image_url)
                        <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
                            class="
                                h-full
                                min-h-[380px]
                                w-full
                                object-cover
                            ">
                    @else
                        <div
                            class="
                                flex
                                h-full
                                min-h-[380px]
                                items-center
                                justify-center
                                bg-gradient-to-br
                                from-indigo-100
                                via-violet-100
                                to-fuchsia-100
                                text-8xl
                                font-black
                                text-indigo-300
                            ">
                            {{ $entity->entityType?->icon ?: '✦' }}
                        </div>
                    @endif

                </div>


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
                                gap-2
                            ">

                            <span
                                class="
                                    rounded-full
                                    bg-indigo-50
                                    px-3
                                    py-1
                                    font-mono
                                    text-[10px]
                                    font-black
                                    text-indigo-700
                                ">
                                {{ $entity->code }}
                            </span>


                            <span
                                class="
                                    rounded-full
                                    bg-violet-50
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    text-violet-700
                                ">
                                {{ $entity->entityType?->name ?? 'Sin tipo' }}
                            </span>


                            <span
                                class="
                                    rounded-full
                                    bg-cyan-50
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    text-cyan-700
                                ">
                                {{ $entity->visibility_label }}
                            </span>


                            <x-status-badge :status="$entity->status" />

                        </div>


                        <h1
                            class="
                                mt-5
                                text-4xl
                                font-black
                                tracking-tight
                                text-slate-900
                            ">
                            {{ $entity->name }}
                        </h1>


                        <p
                            class="
                                mt-2
                                font-mono
                                text-xs
                                text-slate-400
                            ">
                            {{ $entity->slug }}
                        </p>


                        <p
                            class="
                                mt-6
                                max-w-3xl
                                whitespace-pre-line
                                leading-7
                                text-slate-600
                            ">
                            {{ $entity->description ?: 'Esta entidad todavía no tiene una descripción.' }}
                        </p>

                    </div>


                    <div
                        class="
                            mt-8
                            flex
                            flex-wrap
                            gap-3
                        ">

                        <a href="{{ route('entities.edit', $entity) }}"
                            class="
                                rounded-xl
                                bg-indigo-600
                                px-5
                                py-3
                                text-sm
                                font-black
                                text-white
                            ">
                            Editar entidad
                        </a>


                        <a href="{{ route('entities.attributes.edit', $entity) }}"
                            class="
                                rounded-xl
                                border
                                border-indigo-200
                                bg-indigo-50
                                px-5
                                py-3
                                text-sm
                                font-black
                                text-indigo-700
                            ">
                            Características
                        </a>


                        <form method="POST"
                            action="{{ route('entities.destroy', $entity) }}"
                            onsubmit="
                                return confirm(
                                    '¿Eliminar esta entidad?'
                                )
                            ">

                            @csrf
                            @method('DELETE')


                            <button
                                class="
                                    rounded-xl
                                    border
                                    border-red-200
                                    px-5
                                    py-3
                                    text-sm
                                    font-bold
                                    text-red-600
                                ">
                                Eliminar
                            </button>

                        </form>

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
                grid-cols-2
                gap-3
                lg:grid-cols-6
            ">

            @foreach ([['Características', $entity->entity_attributes_count], ['Catálogos', $catalogValuesCount], ['Colecciones', $entity->collections_count], ['Vistas', $entity->views_count], ['Clonaciones', $entity->clones_count], ['Creada', $entity->created_at->format('d/m/Y')]] as [$label, $value])
                <article
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        p-4
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-400
                        ">
                        {{ $label }}
                    </p>


                    <p
                        class="
                            mt-2
                            text-lg
                            font-black
                            text-slate-900
                        ">
                        {{ $value }}
                    </p>

                </article>
            @endforeach

        </section>


        {{-- ===================================================== --}}
        {{-- TIPO --}}
        {{-- ===================================================== --}}

        @if ($entity->entityType)

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
                        tracking-wider
                        text-indigo-500
                    ">
                    Tipo de entidad
                </p>


                <div
                    class="
                        mt-4
                        flex
                        items-center
                        gap-4
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

                        @if ($entity->entityType->image_url)
                            <img src="{{ $entity->entityType->image_url }}"
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
                                ">
                                {{ $entity->entityType->icon ?: '◇' }}
                            </div>
                        @endif

                    </div>


                    <div>

                        <p
                            class="
                                font-mono
                                text-[10px]
                                font-black
                                text-indigo-400
                            ">
                            {{ $entity->entityType->code }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-xl
                                font-black
                                text-indigo-950
                            ">
                            {{ $entity->entityType->name }}
                        </p>

                    </div>


                    <a href="{{ route('entity-types.show', $entity->entityType) }}"
                        class="
                            ml-auto
                            rounded-xl
                            bg-white
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-indigo-700
                        ">
                        Abrir tipo →
                    </a>

                </div>

            </section>

        @endif


        {{-- ===================================================== --}}
        {{-- COLECCIONES --}}
        {{-- ===================================================== --}}

        <section class="mt-10">

            <div
                class="
                    flex
                    items-end
                    justify-between
                ">

                <div>

                    <p
                        class="
                            text-xs
                            font-black
                            uppercase
                            tracking-wider
                            text-indigo-600
                        ">
                        Organización
                    </p>


                    <h2
                        class="
                            mt-2
                            text-2xl
                            font-black
                            text-slate-900
                        ">
                        Colecciones
                    </h2>

                </div>

            </div>


            @if ($entity->collections->isNotEmpty())

                <div
                    class="
                        mt-5
                        grid
                        gap-4
                        sm:grid-cols-2
                        lg:grid-cols-3
                    ">

                    @foreach ($entity->collections as $collection)
                        <a href="{{ route('collections.show', $collection) }}"
                            class="
                                group
                                flex
                                items-center
                                gap-4
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                p-4
                                hover:border-indigo-300
                            ">

                            <div
                                class="
                                    h-16
                                    w-16
                                    shrink-0
                                    overflow-hidden
                                    rounded-xl
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
                                    <div
                                        class="
                                            flex
                                            h-full
                                            items-center
                                            justify-center
                                            text-2xl
                                        ">
                                        {{ $collection->icon ?: '▤' }}
                                    </div>
                                @endif

                            </div>


                            <div class="min-w-0">

                                <p
                                    class="
                                        truncate
                                        font-black
                                        text-slate-900
                                        group-hover:text-indigo-700
                                    ">
                                    {{ $collection->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        font-mono
                                        text-[10px]
                                        text-slate-400
                                    ">
                                    {{ $collection->code }}
                                </p>

                            </div>

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
                        bg-white
                        p-8
                        text-center
                        text-sm
                        text-slate-500
                    ">
                    Esta entidad todavía no pertenece a ninguna colección.
                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- CARACTERÍSTICAS --}}
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
                            tracking-wider
                            text-violet-600
                        ">
                        Perfil dinámico
                    </p>


                    <h2
                        class="
                            mt-2
                            text-3xl
                            font-black
                            text-slate-900
                        ">
                        Características
                    </h2>

                </div>


                <div
                    class="
                        flex
                        flex-wrap
                        gap-2
                    ">

                    @foreach ([
        'cards' => '▦ Tarjetas',
        'list' => '☰ Lista',
        'groups' => '▥ Grupos',
    ] as $value => $label)
                        <button type="button"
                            @click="
                                setCharacteristicView(
                                    '{{ $value }}'
                                )
                            "
                            :class="characteristicView === '{{ $value }}'
                            
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
                            {{ $label }}
                        </button>
                    @endforeach

                </div>

            </div>


            @if ($entity->entityAttributes->isEmpty())

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
                        Sin características configuradas
                    </p>


                    <a href="{{ route('entities.attributes.edit', $entity) }}"
                        class="
                            mt-4
                            inline-flex
                            rounded-xl
                            bg-violet-600
                            px-4
                            py-2.5
                            text-sm
                            font-black
                            text-white
                        ">
                        Añadir características
                    </a>

                </div>
            @else
                {{-- CARDS --}}
                <div x-show="
                        characteristicView === 'cards'
                    "
                    class="
                        mt-5
                        grid
                        gap-4
                        md:grid-cols-2
                        xl:grid-cols-3
                    ">

                    @foreach ($entity->entityAttributes as $assignment)
                        @php
                            $attribute = $assignment->attribute;
                        @endphp


                        <article
                            class="
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                p-5
                            ">

                            <div
                                class="
                                    flex
                                    items-center
                                    gap-3
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
                                                font-black
                                            ">
                                            {{ $attribute->icon ?: $attribute->data_type_icon }}
                                        </div>
                                    @endif

                                </div>


                                <div>

                                    <p
                                        class="
                                            font-black
                                            text-slate-900
                                        ">
                                        {{ $assignment->custom_label ?: $attribute->name }}
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

                                </div>

                            </div>


                            <div
                                class="
                                    mt-4
                                    flex
                                    flex-wrap
                                    gap-2
                                ">

                                @forelse ($assignment->values
                                    as $value)
                                    @if ($value->option)
                                        <a href="{{ route('attribute-options.show', $value->option) }}"
                                            class="
                                                flex
                                                items-center
                                                gap-2
                                                rounded-xl
                                                border
                                                border-violet-100
                                                bg-violet-50
                                                p-2
                                            ">

                                            <div
                                                class="
                                                    h-9
                                                    w-9
                                                    overflow-hidden
                                                    rounded-lg
                                                    bg-white
                                                ">

                                                @if ($value->option->image_url)
                                                    <img src="{{ $value->option->image_url }}"
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
                                                        {{ $value->option->icon ?: '◆' }}
                                                    </div>
                                                @endif

                                            </div>


                                            <span
                                                class="
                                                    text-xs
                                                    font-black
                                                    text-violet-700
                                                ">
                                                {{ $value->option->name }}
                                            </span>

                                        </a>
                                    @else
                                        <span
                                            class="
                                                rounded-xl
                                                bg-slate-100
                                                px-3
                                                py-2
                                                text-sm
                                                font-black
                                                text-slate-700
                                            ">
                                            {{ $value->displayValue() }}

                                            @if ($attribute->unit)
                                                {{ $attribute->unit }}
                                            @endif
                                        </span>
                                    @endif

                                @empty

                                    <span
                                        class="
                                            text-sm
                                            font-bold
                                            text-slate-400
                                        ">
                                        Sin definir
                                    </span>
                                @endforelse

                            </div>

                        </article>
                    @endforeach

                </div>


                {{-- LIST --}}
                <div x-cloak x-show="
                        characteristicView === 'list'
                    "
                    class="
                        mt-5
                        overflow-hidden
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                    ">

                    @foreach ($entity->entityAttributes as $assignment)
                        <div
                            class="
                                grid
                                gap-3
                                border-b
                                border-slate-100
                                px-5
                                py-4
                                last:border-b-0
                                md:grid-cols-[220px_minmax(0,1fr)]
                            ">

                            <div>

                                <p
                                    class="
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $assignment->custom_label ?: $assignment->attribute->name }}
                                </p>

                            </div>


                            <div
                                class="
                                    flex
                                    flex-wrap
                                    gap-2
                                ">

                                @forelse ($assignment->values
                                    as $value)
                                    <span
                                        class="
                                            rounded-lg
                                            bg-slate-100
                                            px-3
                                            py-1.5
                                            text-sm
                                            font-bold
                                            text-slate-700
                                        ">
                                        {{ $value->displayValue() }}
                                    </span>

                                @empty

                                    <span class="text-sm text-slate-400">
                                        Sin definir
                                    </span>
                                @endforelse

                            </div>

                        </div>
                    @endforeach

                </div>


                {{-- GROUPS --}}
                <div x-cloak x-show="
                        characteristicView === 'groups'
                    "
                    class="
                        mt-5
                        space-y-6
                    ">

                    @foreach ($characteristicGroups as $groupName => $items)
                        <section
                            class="
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                p-5
                            ">

                            <h3
                                class="
                                    text-sm
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                {{ $groupName }}
                            </h3>


                            <div
                                class="
                                    mt-4
                                    space-y-3
                                ">

                                @foreach ($items as $item)
                                    @php
                                        $assignment = $item['assignment'];
                                    @endphp


                                    <div
                                        class="
                                            flex
                                            flex-col
                                            justify-between
                                            gap-2
                                            rounded-xl
                                            bg-slate-50
                                            p-4
                                            sm:flex-row
                                            sm:items-center
                                        ">

                                        <p
                                            class="
                                                font-bold
                                                text-slate-800
                                            ">
                                            {{ $assignment->attribute->name }}
                                        </p>


                                        <div
                                            class="
                                                flex
                                                flex-wrap
                                                gap-2
                                            ">

                                            @forelse ($assignment->values
                                                as $value)
                                                <span
                                                    class="
                                                        rounded-lg
                                                        bg-white
                                                        px-3
                                                        py-1.5
                                                        text-xs
                                                        font-black
                                                        text-slate-700
                                                    ">
                                                    {{ $value->displayValue() }}
                                                </span>

                                            @empty

                                                <span
                                                    class="
                                                        text-xs
                                                        text-slate-400
                                                    ">
                                                    Sin definir
                                                </span>
                                            @endforelse

                                        </div>

                                    </div>
                                @endforeach

                            </div>

                        </section>
                    @endforeach

                </div>

            @endif

        </section>

    </div>

</x-app-layout>

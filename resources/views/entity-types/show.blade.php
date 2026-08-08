<x-app-layout>

    <x-slot name="header">
        Entidades
    </x-slot>


    @include('entities.partials.section-navigation')


    {{-- ========================================================= --}}
    {{-- VOLVER --}}
    {{-- ========================================================= --}}

    <div class="
            mb-5
        ">

        <a href="{{ route('entity-types.index') }}"
            class="
                text-sm
                font-bold
                text-slate-400
                transition
                hover:text-indigo-600
            ">
            ← Tipos de entidad
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- CABECERA PRINCIPAL --}}
    {{-- ========================================================= --}}

    <section
        class="
            overflow-hidden
            rounded-3xl
            border
            border-slate-200
            bg-white
            shadow-sm
        ">

        <div class="
                grid
                lg:grid-cols-[340px_minmax(0,1fr)]
            ">

            {{-- ================================================= --}}
            {{-- REPRESENTACIÓN --}}
            {{-- ================================================= --}}

            <div class="
                    min-h-[280px]
                    bg-slate-100
                ">

                @if ($entityType->image_url)
                    <img src="{{ $entityType->image_url }}" alt="{{ $entityType->name }}"
                        class="
                            h-full
                            min-h-[280px]
                            w-full
                            object-cover
                        ">
                @else
                    <div class="
                            flex
                            h-full
                            min-h-[280px]
                            items-center
                            justify-center
                            text-7xl
                            font-black
                        "
                        style="
                            background-color:
                                {{ $entityType->color ?? '#6366F1' }}20;

                            color:
                                {{ $entityType->color ?? '#6366F1' }};
                        ">
                        {{ $entityType->icon ?: '◇' }}
                    </div>
                @endif

            </div>


            {{-- ================================================= --}}
            {{-- INFORMACIÓN --}}
            {{-- ================================================= --}}

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
                            gap-3
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
                                uppercase
                                tracking-wider
                                text-indigo-600
                            ">
                            {{ $entityType->code }}
                        </span>


                        <x-status-badge :status="$entityType->status" />

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
                        {{ $entityType->name }}
                    </h1>


                    <p
                        class="
                            mt-5
                            max-w-3xl
                            leading-7
                            text-slate-600
                        ">
                        {{ $entityType->description ?: 'Este tipo todavía no tiene una descripción.' }}
                    </p>

                </div>


                <div
                    class="
                        mt-8
                        flex
                        flex-wrap
                        gap-3
                    ">

                    <a href="{{ route('entities.create', [
                        'type' => $entityType->id,
                    ]) }}"
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
                            transition
                            hover:bg-indigo-700
                        ">
                        + Crear entidad de este tipo
                    </a>


                    <a href="{{ route('entity-types.edit', $entityType) }}"
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
                        Editar tipo
                    </a>


                    @if ($entityType->entities_count === 0)
                        <form method="POST"
                            action="{{ route('entity-types.destroy', $entityType) }}"
                            onsubmit="
                                return confirm(
                                    '¿Eliminar definitivamente este tipo de entidad?'
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
                        <span title="No puede eliminarse mientras tenga entidades asociadas."
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

    </section>


    {{-- ========================================================= --}}
    {{-- INFORMACIÓN DEL TIPO --}}
    {{-- ========================================================= --}}

    <section
        class="
            mt-6
            grid
            gap-4
            sm:grid-cols-2
            xl:grid-cols-4
        ">

        {{-- ENTIDADES --}}
        <article
            class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Entidades
            </p>


            <p
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                {{ $entityType->entities_count }}
            </p>

        </article>


        {{-- NÚMERO CREACIÓN --}}
        <article
            class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                N.º de creación
            </p>


            <p
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                #{{ $entityType->sequence_number }}
            </p>

        </article>


        {{-- CÓDIGO --}}
        <article
            class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Código
            </p>


            <p
                class="
                    mt-3
                    font-mono
                    text-lg
                    font-black
                    text-indigo-700
                ">
                {{ $entityType->code }}
            </p>

        </article>


        {{-- FECHA --}}
        <article
            class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Creado
            </p>


            <p
                class="
                    mt-3
                    text-lg
                    font-black
                    text-slate-900
                ">
                {{ $entityType->created_at->format('d/m/Y') }}
            </p>

        </article>

    </section>


    {{-- ========================================================= --}}
    {{-- ENTIDADES ASOCIADAS --}}
    {{-- ========================================================= --}}

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
                        tracking-[0.15em]
                        text-indigo-600
                    ">
                    Contenido relacionado
                </p>


                <h2
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Entidades de este tipo
                </h2>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Las últimas entidades creadas
                    utilizando {{ $entityType->name }}.
                </p>

            </div>


            <div
                class="
                    flex
                    flex-wrap
                    gap-3
                ">

                @if ($entityType->entities_count > 12)
                    <a href="{{ route('entities.index', [
                        'type' => $entityType->id,
                    ]) }}"
                        class="
                            rounded-xl
                            border
                            border-slate-300
                            bg-white
                            px-4
                            py-2.5
                            text-sm
                            font-bold
                            text-slate-700
                            hover:bg-slate-50
                        ">
                        Ver todas
                    </a>
                @endif


                <a href="{{ route('entities.create', [
                    'type' => $entityType->id,
                ]) }}"
                    class="
                        rounded-xl
                        bg-indigo-600
                        px-4
                        py-2.5
                        text-sm
                        font-bold
                        text-white
                        hover:bg-indigo-700
                    ">
                    + Nueva entidad
                </a>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- TARJETAS --}}
        {{-- ===================================================== --}}

        <div
            class="
                mt-6
                grid
                gap-4
                sm:grid-cols-2
                lg:grid-cols-3
                xl:grid-cols-4
            ">

            @forelse ($entities
                as $entity)
                <a href="{{ route('entities.show', $entity) }}"
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

                    {{-- IMAGEN --}}
                    <div
                        class="
                            relative
                            aspect-[16/10]
                            bg-gradient-to-br
                            from-indigo-50
                            to-violet-100
                        ">

                        @if ($entity->image_url)
                            <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                    transition
                                    duration-300
                                    group-hover:scale-[1.02]
                                ">
                        @else
                            <div
                                class="
                                    flex
                                    h-full
                                    items-center
                                    justify-center
                                    text-4xl
                                    font-black
                                    text-indigo-300
                                ">
                                {{ strtoupper(substr($entity->name, 0, 1)) }}
                            </div>
                        @endif


                        <div
                            class="
                                absolute
                                left-3
                                top-3
                            ">
                            <x-status-badge :status="$entity->status" />
                        </div>

                    </div>


                    {{-- INFO --}}
                    <div class="p-4">

                        <h3
                            class="
                                truncate
                                font-black
                                text-slate-900
                                group-hover:text-indigo-700
                            ">
                            {{ $entity->name }}
                        </h3>


                        @if ($entity->code)
                            <p
                                class="
                                    mt-1
                                    truncate
                                    font-mono
                                    text-[10px]
                                    font-bold
                                    uppercase
                                    tracking-wider
                                    text-slate-400
                                ">
                                {{ $entity->code }}
                            </p>
                        @endif


                        <p
                            class="
                                mt-2
                                line-clamp-2
                                text-xs
                                leading-5
                                text-slate-500
                            ">
                            {{ $entity->description ?: 'Sin descripción.' }}
                        </p>

                    </div>

                </a>

            @empty

                <div
                    class="
                        sm:col-span-2
                        lg:col-span-3
                        xl:col-span-4
                        rounded-3xl
                        border
                        border-dashed
                        border-slate-300
                        bg-white
                        py-14
                        text-center
                    ">

                    <div class="
                            text-4xl
                        ">
                        ◇
                    </div>


                    <p
                        class="
                            mt-4
                            font-black
                            text-slate-700
                        ">
                        Todavía no hay entidades
                    </p>


                    <p
                        class="
                            mt-2
                            text-sm
                            text-slate-500
                        ">
                        Crea la primera entidad
                        perteneciente a este tipo.
                    </p>


                    <a href="{{ route('entities.create', [
                        'type' => $entityType->id,
                    ]) }}"
                        class="
                            mt-5
                            inline-flex
                            rounded-xl
                            bg-indigo-600
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-white
                        ">
                        + Crear entidad
                    </a>

                </div>
            @endforelse

        </div>

    </section>

</x-app-layout>

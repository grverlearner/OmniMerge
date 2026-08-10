<x-app-layout>

    <x-slot name="header">
        Versiones
    </x-slot>


    @include('entities.partials.section-navigation')


    {{-- ===================================================== --}}
    {{-- MENSAJE --}}
    {{-- ===================================================== --}}

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
                grid
                lg:grid-cols-[380px_minmax(0,1fr)]
            ">

            {{-- IMAGEN --}}
            <div
                class="
                    relative
                    min-h-[380px]
                    overflow-hidden
                    bg-gradient-to-br
                    from-violet-100
                    via-indigo-50
                    to-slate-100
                ">

                @if ($version->image_url)
                    <img src="{{ $version->image_url }}" alt="{{ $version->name }}"
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
                            min-h-[380px]
                            h-full
                            w-full
                            flex-col
                            items-center
                            justify-center
                            gap-3
                            text-violet-300
                        ">

                        <span class="
                                text-7xl
                            ">
                            ◈
                        </span>


                        <span
                            class="
                                text-xs
                                font-black
                                uppercase
                                tracking-wider
                            ">
                            Sin imagen disponible
                        </span>

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

                    {{-- BADGES --}}
                    <div
                        class="
                            flex
                            flex-wrap
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
                            {{ $version->code }}
                        </span>


                        <span
                            class="
                                rounded-full
                                bg-indigo-50
                                px-3
                                py-1
                                text-[10px]
                                font-black
                                text-indigo-700
                            ">
                            {{ $version->kind_label }}
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
                            {{ $version->scope_label }}
                        </span>


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
                            {{ $version->activation_label }}
                        </span>


                        <span
                            class="
                                rounded-full
                                px-3
                                py-1
                                text-[10px]
                                font-black

                                {{ $version->status === 'ACTIVE'
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : ($version->status === 'ARCHIVED'
                                        ? 'bg-amber-50 text-amber-700'
                                        : 'bg-slate-100 text-slate-500') }}
                            ">
                            {{ $version->status_label }}
                        </span>

                    </div>


                    {{-- NOMBRE --}}
                    <h1
                        class="
                            mt-5
                            text-4xl
                            font-black
                            tracking-tight
                            text-slate-900
                        ">
                        {{ $version->name }}
                    </h1>


                    {{-- PADRE --}}
                    @if ($version->parent)
                        <p
                            class="
                                mt-3
                                text-xs
                                font-bold
                                text-violet-500
                            ">
                            ↳ Derivada de

                            <a href="{{ route('versions.show', $version->parent) }}"
                                class="
                                    underline
                                    decoration-violet-300
                                    underline-offset-2
                                ">
                                {{ $version->parent->name }}
                            </a>
                        </p>
                    @endif


                    {{-- DESCRIPCIÓN --}}
                    <p
                        class="
                            mt-6
                            max-w-3xl
                            whitespace-pre-line
                            leading-7
                            text-slate-600
                        ">
                        {{ $version->description ?: 'Esta Versión todavía no tiene una descripción.' }}
                    </p>

                </div>


                {{-- ACCIONES --}}
                <div
                    class="
                        mt-8
                        flex
                        flex-wrap
                        gap-3
                    ">

                    <a href="{{ route('versions.edit', $version) }}"
                        class="
                            rounded-xl
                            bg-violet-600
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-white
                            shadow-lg
                            shadow-violet-600/20
                            transition
                            hover:bg-violet-700
                        ">
                        ✎ Editar Versión
                    </a>


                    <a href="{{ route('versions.entities.bulk.create', $version) }}"
                        class="
                            rounded-xl
                            border
                            border-violet-200
                            bg-violet-50
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-violet-700
                            transition
                            hover:bg-violet-100
                        ">
                        + Asociar Entidades
                    </a>


                    <a href="{{ route('versions.index') }}"
                        class="
                            rounded-xl
                            bg-slate-100
                            px-5
                            py-3
                            text-sm
                            font-bold
                            text-slate-600
                            transition
                            hover:bg-slate-200
                        ">
                        ← Todas las Versiones
                    </a>

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
            md:grid-cols-3
            lg:grid-cols-5
        ">

        @foreach ([
        [
            'label' => 'Entidades',
            'value' => $version->entity_versions_count,
            'icon' => '✦',
        ],

        [
            'label' => 'Subversiones',
            'value' => $version->children_count,
            'icon' => '⌘',
        ],

        [
            'label' => 'Relaciones',
            'value' => $version->catalog_links_count,
            'icon' => '◆',
        ],

        [
            'label' => 'Prioridad',
            'value' => $version->priority,
            'icon' => '↑',
        ],

        [
            'label' => 'Orden',
            'value' => $version->sort_order,
            'icon' => '≡',
        ],
    ] as $stat)
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                    shadow-sm
                ">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                        gap-3
                    ">

                    <div>

                        <p
                            class="
                                text-[9px]
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-400
                            ">
                            {{ $stat['label'] }}
                        </p>


                        <p
                            class="
                                mt-2
                                text-2xl
                                font-black
                                text-slate-900
                            ">
                            {{ $stat['value'] }}
                        </p>

                    </div>


                    <div
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-xl
                            bg-violet-50
                            font-black
                            text-violet-600
                        ">
                        {{ $stat['icon'] }}
                    </div>

                </div>

            </article>
        @endforeach

    </section>


    {{-- ===================================================== --}}
    {{-- RELACIONES CON CATÁLOGOS --}}
    {{-- ===================================================== --}}

    <section
        class="
            mt-8
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
                    text-violet-500
                ">
                Contexto automático
            </p>


            <h2
                class="
                    mt-1
                    text-xl
                    font-black
                    text-slate-900
                ">
                Relación con Catálogos
            </h2>


            <p
                class="
                    mt-2
                    max-w-3xl
                    text-sm
                    leading-6
                    text-slate-500
                ">
                Estas relaciones permiten que OmniMerge pueda
                reconocer en qué contexto corresponde utilizar
                esta Versión.
            </p>

        </div>


        @if ($version->catalogLinks->isEmpty())

            <div
                class="
                    mt-5
                    rounded-2xl
                    border
                    border-dashed
                    border-slate-200
                    bg-slate-50
                    p-6
                    text-center
                ">

                <p
                    class="
                        text-sm
                        font-bold
                        text-slate-500
                    ">
                    Esta Versión no tiene relaciones de Catálogo.
                </p>


                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-400
                    ">
                    Puede utilizarse de forma completamente manual.
                </p>

            </div>
        @else
            <div
                class="
                    mt-5
                    grid
                    gap-3
                    md:grid-cols-2
                    xl:grid-cols-3
                ">

                @foreach ($version->catalogLinks as $link)
                    <article
                        class="
                            rounded-2xl
                            border
                            border-violet-100
                            bg-violet-50/40
                            p-4
                        ">

                        <div
                            class="
                                flex
                                items-start
                                justify-between
                                gap-3
                            ">

                            <div>

                                <p
                                    class="
                                        text-[9px]
                                        font-black
                                        uppercase
                                        tracking-wider
                                        text-violet-400
                                    ">
                                    {{ $link->relation_label }}
                                </p>


                                <p
                                    class="
                                        mt-2
                                        text-xs
                                        font-black
                                        text-slate-600
                                    ">
                                    {{ $link->attribute?->name ?? 'Atributo' }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-lg
                                        font-black
                                        text-violet-700
                                    ">
                                    {{ $link->option?->name ?? 'Elemento eliminado' }}
                                </p>

                            </div>


                            @if ($link->option && $link->option->image_url)
                                <img src="{{ $link->option->image_url }}" alt="{{ $link->option->name }}"
                                    class="
                                        h-14
                                        w-14
                                        shrink-0
                                        rounded-xl
                                        object-cover
                                    ">
                            @endif

                        </div>


                        <div
                            class="
                                mt-4
                                flex
                                flex-wrap
                                gap-1.5
                            ">

                            <span
                                class="
                                    rounded-full
                                    bg-white
                                    px-2
                                    py-1
                                    text-[8px]
                                    font-black
                                    text-slate-500
                                ">
                                Grupo {{ $link->condition_group }}
                            </span>


                            <span
                                class="
                                    rounded-full
                                    bg-white
                                    px-2
                                    py-1
                                    text-[8px]
                                    font-black
                                    text-slate-500
                                ">
                                {{ $link->logical_operator }}
                            </span>


                            @if ($link->is_required)
                                <span
                                    class="
                                        rounded-full
                                        bg-amber-100
                                        px-2
                                        py-1
                                        text-[8px]
                                        font-black
                                        text-amber-700
                                    ">
                                    Requerido
                                </span>
                            @endif

                        </div>

                    </article>
                @endforeach

            </div>

        @endif

    </section>


    {{-- ===================================================== --}}
    {{-- COBERTURA --}}
    {{-- ===================================================== --}}

    @if ($eligibleEntities->isNotEmpty())

        @php

            $eligibleCount = $eligibleEntities->count();

            $assignedEligible = $eligibleEntities->whereIn('id', $version->entityVersions->pluck('entity_id'))->count();

            $coverage = $eligibleCount > 0 ? round(($assignedEligible / $eligibleCount) * 100) : 0;
        @endphp


        <section
            class="
                mt-8
                rounded-3xl
                border
                border-cyan-200
                bg-cyan-50/40
                p-6
            ">

            <div
                class="
                    flex
                    flex-col
                    gap-5
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                ">

                <div>

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-cyan-500
                        ">
                        Cobertura
                    </p>


                    <h2
                        class="
                            mt-1
                            text-2xl
                            font-black
                            text-slate-900
                        ">
                        {{ $assignedEligible }}
                        /
                        {{ $eligibleCount }}
                        Entidades
                    </h2>


                    <p
                        class="
                            mt-2
                            max-w-2xl
                            text-xs
                            leading-5
                            text-slate-500
                        ">
                        Entidades detectadas a partir de los elementos
                        de Catálogo que activan esta Versión.
                    </p>

                </div>


                <div
                    class="
                        text-5xl
                        font-black
                        text-cyan-600
                    ">
                    {{ $coverage }}%
                </div>

            </div>


            <div
                class="
                    mt-5
                    h-3
                    overflow-hidden
                    rounded-full
                    bg-cyan-100
                ">

                <div class="
                        h-full
                        rounded-full
                        bg-cyan-500
                        transition-all
                    "
                    style="
                        width: {{ $coverage }}%;
                    "></div>

            </div>


            @if ($missingEntities->isNotEmpty())
                <div
                    class="
                        mt-5
                        rounded-2xl
                        border
                        border-amber-200
                        bg-amber-50
                        p-4
                    ">

                    <div
                        class="
                            flex
                            flex-col
                            gap-3
                            sm:flex-row
                            sm:items-center
                            sm:justify-between
                        ">

                        <div>

                            <p
                                class="
                                    text-sm
                                    font-black
                                    text-amber-800
                                ">
                                ⚠ {{ $missingEntities->count() }}
                                Entidades candidatas sin esta Versión
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[10px]
                                    text-amber-600
                                ">
                                Puedes crearlas mediante el editor masivo.
                            </p>

                        </div>


                        <a href="{{ route('versions.entities.bulk.create', $version) }}"
                            class="
                                rounded-xl
                                bg-amber-600
                                px-4
                                py-2.5
                                text-center
                                text-xs
                                font-black
                                text-white
                            ">
                            Crear faltantes
                        </a>

                    </div>

                </div>
            @endif

        </section>

    @endif


    {{-- ===================================================== --}}
    {{-- SUBVERSIONES --}}
    {{-- ===================================================== --}}

    @if ($version->children->isNotEmpty())

        <section class="mt-10">

            <div>

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-fuchsia-500
                    ">
                    Jerarquía
                </p>


                <h2
                    class="
                        mt-1
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Subversiones
                </h2>

            </div>


            <div
                class="
                    mt-5
                    grid
                    gap-4
                    sm:grid-cols-2
                    lg:grid-cols-3
                    xl:grid-cols-4
                ">

                @foreach ($version->children as $child)
                    <a href="{{ route('versions.show', $child) }}"
                        class="
                            group
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            shadow-sm
                            transition
                            hover:-translate-y-1
                            hover:border-fuchsia-300
                            hover:shadow-lg
                        ">

                        <div
                            class="
                                aspect-[4/3]
                                overflow-hidden
                                bg-slate-100
                            ">

                            @if ($child->image_url)
                                <img src="{{ $child->image_url }}" alt="{{ $child->name }}"
                                    class="
                                        h-full
                                        w-full
                                        object-cover
                                        transition
                                        duration-300
                                        group-hover:scale-105
                                    ">
                            @endif

                        </div>


                        <div class="p-4">

                            <p
                                class="
                                    text-[8px]
                                    font-black
                                    uppercase
                                    text-fuchsia-500
                                ">
                                {{ $child->kind_label }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    truncate
                                    text-sm
                                    font-black
                                    text-slate-800
                                ">
                                {{ $child->name }}
                            </p>

                        </div>

                    </a>
                @endforeach

            </div>

        </section>

    @endif


    {{-- ===================================================== --}}
    {{-- ENTIDADES ASOCIADAS --}}
    {{-- ===================================================== --}}

    <section class="mt-10">

        <div
            class="
                flex
                flex-col
                gap-4
                sm:flex-row
                sm:items-end
                sm:justify-between
            ">

            <div>

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-500
                    ">
                    Instancias
                </p>


                <h2
                    class="
                        mt-1
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Entidades en {{ $version->name }}
                </h2>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-500
                    ">
                    Cada Entidad posee su propio nombre,
                    imagen y características específicas
                    dentro de esta Versión.
                </p>

            </div>


            <a href="{{ route('versions.entities.bulk.create', $version) }}"
                class="
                    rounded-xl
                    bg-violet-600
                    px-4
                    py-2.5
                    text-center
                    text-xs
                    font-black
                    text-white
                ">
                + Asociar Entidades
            </a>

        </div>


        @if ($version->entityVersions->isEmpty())

            <div
                class="
                    mt-5
                    rounded-3xl
                    border
                    border-dashed
                    border-slate-300
                    bg-white
                    py-16
                    text-center
                ">

                <div
                    class="
                        text-5xl
                        text-violet-200
                    ">
                    ◈
                </div>


                <p
                    class="
                        mt-4
                        font-black
                        text-slate-600
                    ">
                    Ninguna Entidad está asociada todavía.
                </p>


                <a href="{{ route('versions.entities.bulk.create', $version) }}"
                    class="
                        mt-4
                        inline-flex
                        rounded-xl
                        bg-violet-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Asociar primeras Entidades
                </a>

            </div>
        @else
            <div
                class="
                    mt-5
                    grid
                    gap-4
                    grid-cols-2
                    md:grid-cols-3
                    lg:grid-cols-4
                    xl:grid-cols-5
                ">

                @foreach ($version->entityVersions as $entityVersion)
                    <a href="{{ route('entity-versions.show', [$entityVersion->entity, $entityVersion]) }}"
                        class="
                            group
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            shadow-sm
                            transition
                            hover:-translate-y-1
                            hover:border-violet-300
                            hover:shadow-lg
                        ">

                        <div
                            class="
                                relative
                                aspect-square
                                overflow-hidden
                                bg-slate-100
                            ">

                            @if ($entityVersion->image_url)
                                <img src="{{ $entityVersion->image_url }}" alt="{{ $entityVersion->name }}"
                                    class="
                                        h-full
                                        w-full
                                        object-cover
                                        transition
                                        duration-300
                                        group-hover:scale-105
                                    ">
                            @else
                                <div
                                    class="
                                        flex
                                        h-full
                                        items-center
                                        justify-center
                                        text-4xl
                                        text-violet-200
                                    ">
                                    ✦
                                </div>
                            @endif


                            @if ($entityVersion->is_default)
                                <span
                                    class="
                                        absolute
                                        right-2
                                        top-2
                                        rounded-full
                                        bg-amber-400
                                        px-2
                                        py-1
                                        text-[7px]
                                        font-black
                                        text-amber-950
                                    ">
                                    ★
                                </span>
                            @endif

                        </div>


                        <div class="p-3">

                            <p
                                class="
                                    truncate
                                    text-xs
                                    font-black
                                    text-slate-800
                                ">
                                {{ $entityVersion->name }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    truncate
                                    text-[8px]
                                    text-slate-400
                                ">
                                {{ $entityVersion->entity?->name }}
                            </p>

                        </div>

                    </a>
                @endforeach

            </div>

        @endif

    </section>


    {{-- ===================================================== --}}
    {{-- ZONA PELIGRO --}}
    {{-- ===================================================== --}}

    <section
        class="
            mt-10
            rounded-3xl
            border
            border-red-200
            bg-red-50/40
            p-5
        ">

        <div
            class="
                flex
                flex-col
                gap-4
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">

            <div>

                <p
                    class="
                        text-xs
                        font-black
                        text-red-700
                    ">
                    Eliminar Versión
                </p>


                <p
                    class="
                        mt-1
                        text-[10px]
                        text-red-500
                    ">
                    Se utiliza eliminación lógica.
                    Los archivos no se destruyen inmediatamente.
                </p>

            </div>


            <form method="POST"
                action="{{ route('versions.destroy', $version) }}"
                onsubmit="
                    return confirm(
                        '¿Seguro que deseas eliminar esta Versión?'
                    );
                ">

                @csrf
                @method('DELETE')


                <button type="submit"
                    class="
                        rounded-xl
                        bg-red-600
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-white
                    ">
                    Eliminar
                </button>

            </form>

        </div>

    </section>

</x-app-layout>

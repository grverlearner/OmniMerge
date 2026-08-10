<x-app-layout>

    <x-slot name="header">
        {{ $entityVersion->name }}
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <div x-data="{
        tab: 'summary'
    }">

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


        {{-- JUST CREATED --}}
        @if (session('entity_version_just_created'))

            <section
                class="
                    mb-5
                    rounded-3xl
                    border
                    border-violet-200
                    bg-gradient-to-r
                    from-violet-50
                    to-indigo-50
                    p-5
                ">

                <p
                    class="
                        text-xs
                        font-black
                        text-violet-800
                    ">
                    ✓ La Versión está lista
                </p>


                <p
                    class="
                        mt-1
                        text-[10px]
                        text-violet-600
                    ">
                    Puedes continuar completándola
                    sin volver a navegar por el módulo.
                </p>


                <div
                    class="
                        mt-4
                        flex
                        flex-wrap
                        gap-2
                    ">

                    <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
                        class="
                            rounded-xl
                            bg-violet-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Completar características
                    </a>


                    <a href="{{ route('entity-versions.create', [
                        $entity,
                        'definition_mode' => 'NEW_EXCLUSIVE',
                    
                        'parent_entity_version_id' => $entityVersion->id,
                    
                        'new_version_parent_id' => $entityVersion->version_id,
                    ]) }}"
                        class="
                            rounded-xl
                            border
                            border-violet-200
                            bg-white
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-violet-700
                        ">
                        + Crear subversión
                    </a>


                    @if (session('definition_just_created'))
                        <a href="{{ route('versions.entities.bulk.create', $entityVersion->version) }}"
                            class="
                                rounded-xl
                                border
                                border-indigo-200
                                bg-white
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-indigo-700
                            ">
                            Aplicar a otras Entidades
                        </a>
                    @endif

                </div>

            </section>

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

            <div
                class="
                    grid
                    lg:grid-cols-[420px_minmax(0,1fr)]
                ">

                <div
                    class="
                        relative
                        min-h-[420px]
                        overflow-hidden
                        bg-slate-100
                    ">

                    <img src="{{ $entityVersion->image_url }}" alt="{{ $entityVersion->name }}"
                        class="
                            h-full
                            min-h-[420px]
                            w-full
                            object-cover
                        ">


                    @if ($entityVersion->is_default)
                        <span
                            class="
                                absolute
                                left-4
                                top-4
                                rounded-full
                                bg-amber-400
                                px-3
                                py-1.5
                                text-[9px]
                                font-black
                                text-amber-950
                                shadow
                            ">
                            ★ PREDETERMINADA
                        </span>
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
                                    bg-violet-50
                                    px-3
                                    py-1
                                    font-mono
                                    text-[9px]
                                    font-black
                                    text-violet-700
                                ">
                                {{ $entityVersion->code }}
                            </span>


                            <span
                                class="
                                    rounded-full
                                    bg-indigo-50
                                    px-3
                                    py-1
                                    text-[9px]
                                    font-black
                                    text-indigo-700
                                ">
                                {{ $entityVersion->version->kind_label }}
                            </span>


                            <span
                                class="
                                    rounded-full
                                    bg-slate-100
                                    px-3
                                    py-1
                                    text-[9px]
                                    font-black
                                    text-slate-600
                                ">
                                {{ $entityVersion->status_label }}
                                @if ($entityVersion->baseSetting)
                                    <span
                                        class="
                                            rounded-full
                                            bg-violet-600
                                            px-3
                                            py-1
                                            text-[9px]
                                            font-black
                                            text-white
                                            shadow
                                        ">
                                        ★ BASE ACTIVA
                                    </span>
                                @endif


                                @if ($entityVersion->is_default)
                                    <span
                                        class="
                                            rounded-full
                                            bg-amber-100
                                            px-3
                                            py-1
                                            text-[9px]
                                            font-black
                                            text-amber-700
                                        ">
                                        ⚡ DEFAULT RESOLVER
                                    </span>
                                @endif
                            </span>

                        </div>


                        <p
                            class="
                                mt-5
                                text-xs
                                font-black
                                uppercase
                                tracking-wider
                                text-violet-500
                            ">
                            {{ $entity->name }}
                            →
                            {{ $entityVersion->version->name }}
                        </p>


                        <h1
                            class="
                                mt-2
                                text-4xl
                                font-black
                                tracking-tight
                                text-slate-900
                            ">
                            {{ $entityVersion->name }}
                        </h1>


                        <p
                            class="
                                mt-5
                                whitespace-pre-line
                                leading-7
                                text-slate-600
                            ">
                            {{ $entityVersion->description ?: 'Esta Versión todavía no tiene una descripción específica.' }}
                        </p>

                    </div>


                    <div
                        class="
                            mt-8
                            flex
                            flex-wrap
                            gap-2
                        ">

                        <a href="{{ route('entity-versions.edit', [$entity, $entityVersion]) }}"
                            class="
                                rounded-xl
                                bg-violet-600
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                            ">
                            ✎ Editar
                        </a>
                        @if ($entityVersion->baseSetting)
                            <form method="POST" action="{{ route('entities.base-version.destroy', $entity) }}"
                                onsubmit="
                                    return confirm(
                                        'Esta Version es la Base activa. ¿Volver a la Base original?'
                                    );
                                ">

                                @csrf
                                @method('DELETE')


                                <button type="submit"
                                    class="
                                        rounded-xl
                                        border
                                        border-violet-200
                                        bg-violet-50
                                        px-4
                                        py-2.5
                                        text-xs
                                        font-black
                                        text-violet-700
                                    ">
                                    ✓ Es Base · Restaurar original
                                </button>

                            </form>
                        @else
                            <form method="POST" action="{{ route('entities.base-version.update', $entity) }}"
                                onsubmit="
                                    return confirm(
                                        '¿Convertir esta Version en la Base activa de la Entidad?'
                                    );
                                ">

                                @csrf
                                @method('PUT')


                                <input type="hidden" name="entity_version_id" value="{{ $entityVersion->id }}">


                                <button type="submit"
                                    class="
                                        rounded-xl
                                        bg-fuchsia-600
                                        px-4
                                        py-2.5
                                        text-xs
                                        font-black
                                        text-white
                                        shadow-lg
                                        shadow-fuchsia-600/20
                                    ">
                                    ★ Hacer Base activa
                                </button>

                            </form>
                        @endif

                        <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
                            class="
                                rounded-xl
                                bg-indigo-50
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-indigo-700
                            ">
                            Características
                        </a>


                        <a href="{{ route('entity-versions.compare', $entity) }}"
                            class="
                                rounded-xl
                                bg-cyan-50
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-cyan-700
                            ">
                            ⇄ Comparar
                        </a>


                        <a href="{{ route('entity-versions.create', [
                            $entity,
                            'definition_mode' => 'NEW_EXCLUSIVE',
                        
                            'parent_entity_version_id' => $entityVersion->id,
                        
                            'new_version_parent_id' => $entityVersion->version_id,
                        ]) }}"
                            class="
                                rounded-xl
                                bg-fuchsia-50
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-fuchsia-700
                            ">
                            + Subversión
                        </a>

                        <form method="POST" action="{{ route('entities.presentation.update', $entity) }}">

                            @csrf
                            @method('PUT')


                            <input type="hidden" name="mode" value="VERSION_PRIMARY">


                            <input type="hidden" name="entity_version_id" value="{{ $entityVersion->id }}">


                            <input type="hidden" name="use_version_name" value="1">


                            <input type="hidden" name="use_version_description" value="1">


                            <button
                                class="
                                    rounded-xl
                                    bg-fuchsia-600
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-white
                                ">
                                ◎ Mostrar públicamente
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </article>


        {{-- ===================================================== --}}
        {{-- TABS --}}
        {{-- ===================================================== --}}

        <nav
            class="
                mt-6
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

                @foreach ([['summary', 'Resumen'], ['attributes', 'Características'], ['media', 'Multimedia'], ['hierarchy', 'Jerarquía']] as [$value, $label])
                    <button type="button"
                        @click="
                            tab = '{{ $value }}'
                        "
                        class="
                            rounded-xl
                            px-4
                            py-2.5
                            text-xs
                            font-black
                        "
                        :class="tab === '{{ $value }}'
                            ?
                            'bg-slate-900 text-white' :
                            'text-slate-500 hover:bg-slate-50'">
                        {{ $label }}
                    </button>
                @endforeach

            </div>

        </nav>


        {{-- ===================================================== --}}
        {{-- SUMMARY --}}
        {{-- ===================================================== --}}

        <section x-show="
                tab === 'summary'
            " class="mt-6">

            <div
                class="
                    grid
                    gap-3
                    md:grid-cols-2
                    xl:grid-cols-4
                ">

                @foreach ([
        [
            'label' => 'Definición',
            'value' => $entityVersion->version->name,
        ],

        [
            'label' => 'Padre concreto',
            'value' => $entityVersion->parent?->name ?? 'Ninguno',
        ],

        [
            'label' => 'Herencia base',
            'value' => $entityVersion->inherit_base_attributes ? 'Sí' : 'No',
        ],

        [
            'label' => 'Sobrescrituras',
            'value' => $entityVersion->versionAttributes->count(),
        ],
    ] as $stat)
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
                            {{ $stat['label'] }}
                        </p>


                        <p
                            class="
                                mt-2
                                font-black
                                text-slate-800
                            ">
                            {{ $stat['value'] }}
                        </p>

                    </article>
                @endforeach

            </div>


            <div
                class="
                    mt-5
                    grid
                    gap-4
                    lg:grid-cols-2
                ">

                <article
                    class="
                        rounded-3xl
                        border
                        border-violet-100
                        bg-violet-50/40
                        p-5
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            text-violet-500
                        ">
                        Entidad base
                    </p>


                    <div
                        class="
                            mt-4
                            flex
                            items-center
                            gap-3
                        ">

                        @if ($entity->image_url)
                            <img src="{{ $entity->image_url }}"
                                class="
                                    h-16
                                    w-16
                                    rounded-xl
                                    object-cover
                                ">
                        @endif


                        <div>

                            <p
                                class="
                                    font-black
                                    text-slate-800
                                ">
                                {{ $entity->name }}
                            </p>


                            <a href="{{ route('entities.show', $entity) }}"
                                class="
                                    mt-1
                                    inline-block
                                    text-xs
                                    font-black
                                    text-violet-600
                                ">
                                Abrir Entidad →
                            </a>

                        </div>

                    </div>

                </article>


                <article
                    class="
                        rounded-3xl
                        border
                        border-indigo-100
                        bg-indigo-50/40
                        p-5
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            text-indigo-500
                        ">
                        Definición general
                    </p>


                    <div
                        class="
                            mt-4
                            flex
                            items-center
                            gap-3
                        ">

                        @if ($entityVersion->version->image_url)
                            <img src="{{ $entityVersion->version->image_url }}"
                                class="
                                    h-16
                                    w-16
                                    rounded-xl
                                    object-cover
                                ">
                        @endif


                        <div>

                            <p
                                class="
                                    font-black
                                    text-slate-800
                                ">
                                {{ $entityVersion->version->name }}
                            </p>


                            <a href="{{ route('versions.show', $entityVersion->version) }}"
                                class="
                                    mt-1
                                    inline-block
                                    text-xs
                                    font-black
                                    text-indigo-600
                                ">
                                Abrir definición →
                            </a>

                        </div>

                    </div>

                </article>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- ATTRIBUTES --}}
        {{-- ===================================================== --}}

        <section x-show="
                tab === 'attributes'
            " x-cloak class="mt-6">

            <div
                class="
                    flex
                    items-end
                    justify-between
                    gap-4
                ">

                <div>

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            text-indigo-500
                        ">
                        Resultado final
                    </p>


                    <h2
                        class="
                            mt-1
                            text-2xl
                            font-black
                            text-slate-900
                        ">
                        Características efectivas
                    </h2>

                </div>


                <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
                    class="
                        rounded-xl
                        bg-indigo-600
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-white
                    ">
                    Editar características
                </a>

            </div>


            @if ($effectiveAttributes->isEmpty())

                <div
                    class="
                        mt-5
                        rounded-2xl
                        bg-slate-50
                        p-8
                        text-center
                        text-sm
                        text-slate-400
                    ">
                    No hay características efectivas.
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

                    @foreach ($effectiveAttributes as $item)
                        <article
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
                                    items-start
                                    justify-between
                                    gap-3
                                ">

                                <div>

                                    <p
                                        class="
                                            text-xs
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $item['custom_label'] ?: $item['attribute']->name }}
                                    </p>


                                    <p
                                        class="
                                            mt-2
                                            text-lg
                                            font-black
                                            text-slate-900
                                        ">
                                        {{ $item['display'] ?: '—' }}
                                    </p>

                                </div>


                                <span
                                    class="
                                        rounded-full
                                        px-2
                                        py-1
                                        text-[7px]
                                        font-black

                                        {{ $item['source'] === 'BASE' ? 'bg-slate-100 text-slate-500' : 'bg-violet-50 text-violet-600' }}
                                    ">
                                    {{ $item['source'] === 'BASE' ? 'HEREDADO' : 'SOBRESCRITO' }}
                                </span>

                            </div>


                            <p
                                class="
                                    mt-3
                                    text-[8px]
                                    text-slate-400
                                ">
                                Fuente:
                                {{ $item['source_name'] }}
                            </p>

                        </article>
                    @endforeach

                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- MEDIA --}}
        {{-- ===================================================== --}}

        <div x-show="
                tab === 'media'
            " x-cloak class="mt-6">

            @include('entity-versions.partials.media-manager')

        </div>


        {{-- ===================================================== --}}
        {{-- HIERARCHY --}}
        {{-- ===================================================== --}}

        <section x-show="
                tab === 'hierarchy'
            " x-cloak
            class="
                mt-6
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
            ">

            <h2
                class="
                    text-2xl
                    font-black
                    text-slate-900
                ">
                Jerarquía
            </h2>


            <div
                class="
                    mt-5
                    grid
                    gap-4
                    lg:grid-cols-2
                ">

                <article
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        p-4
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            text-slate-400
                        ">
                        Padre
                    </p>


                    @if ($entityVersion->parent)
                        <a href="{{ route('entity-versions.show', [$entity, $entityVersion->parent]) }}"
                            class="
                                mt-3
                                flex
                                items-center
                                gap-3
                            ">

                            <img src="{{ $entityVersion->parent->image_url }}"
                                class="
                                    h-14
                                    w-14
                                    rounded-xl
                                    object-cover
                                ">


                            <span
                                class="
                                    font-black
                                    text-slate-800
                                ">
                                {{ $entityVersion->parent->name }}
                            </span>

                        </a>
                    @else
                        <p
                            class="
                                mt-3
                                text-sm
                                text-slate-400
                            ">
                            Es una versión raíz.
                        </p>
                    @endif

                </article>


                <article
                    class="
                        rounded-2xl
                        border
                        border-slate-200
                        p-4
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            text-slate-400
                        ">
                        Subversiones
                    </p>


                    @if ($entityVersion->children->isEmpty())

                        <p
                            class="
                                mt-3
                                text-sm
                                text-slate-400
                            ">
                            No tiene subversiones.
                        </p>
                    @else
                        <div
                            class="
                                mt-3
                                space-y-2
                            ">

                            @foreach ($entityVersion->children as $child)
                                <a href="{{ route('entity-versions.show', [$entity, $child]) }}"
                                    class="
                                        flex
                                        items-center
                                        gap-3
                                        rounded-xl
                                        bg-slate-50
                                        p-3
                                    ">

                                    <img src="{{ $child->image_url }}"
                                        class="
                                            h-10
                                            w-10
                                            rounded-lg
                                            object-cover
                                        ">


                                    <span
                                        class="
                                            text-xs
                                            font-black
                                            text-slate-700
                                        ">
                                        {{ $child->name }}
                                    </span>

                                </a>
                            @endforeach

                        </div>

                    @endif

                </article>

            </div>


            <a href="{{ route('entity-versions.create', [
                $entity,
                'definition_mode' => 'NEW_EXCLUSIVE',
            
                'parent_entity_version_id' => $entityVersion->id,
            
                'new_version_parent_id' => $entityVersion->version_id,
            ]) }}"
                class="
                    mt-5
                    inline-flex
                    rounded-xl
                    bg-violet-600
                    px-4
                    py-2.5
                    text-xs
                    font-black
                    text-white
                ">
                + Crear subversión
            </a>

        </section>

    </div>

</x-app-layout>

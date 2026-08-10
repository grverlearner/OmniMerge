<x-app-layout>

    <x-slot name="header">
        Versiones de {{ $entity->name }}
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @php
        $activeBaseEntityVersion = $entity->baseVersionSetting?->entityVersion;
    @endphp


    <div x-data="{
        view: localStorage.getItem(
                'omnimerge.entityVersions.view'
            ) ||
            'timeline',
    
        setView(value) {
    
            this.view =
                value;
    
            localStorage.setItem(
                'omnimerge.entityVersions.view',
                value
            );
        }
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


        {{-- HERO --}}
        <section
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
                    lg:grid-cols-[220px_minmax(0,1fr)]
                ">

                <div class="
                        min-h-52
                        bg-slate-100
                    ">

                    @if ($entity->image_url)
                        <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
                            class="
                                h-full
                                min-h-52
                                w-full
                                object-cover
                            ">
                    @endif

                </div>


                <div
                    class="
                        bg-gradient-to-br
                        from-slate-950
                        via-indigo-950
                        to-violet-950
                        p-6
                        text-white
                        sm:p-8
                    ">

                    <p
                        class="
                            font-mono
                            text-[9px]
                            font-black
                            text-indigo-300
                        ">
                        {{ $entity->code }}
                    </p>


                    <h1
                        class="
                            mt-2
                            text-3xl
                            font-black
                            tracking-tight
                        ">
                        {{ $entity->name }}
                        @if ($activeBaseEntityVersion)
                            <div
                                class="
                                    mt-3
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-2
                                ">

                                <span
                                    class="
                                        rounded-full
                                        bg-violet-400/20
                                        px-3
                                        py-1.5
                                        text-[8px]
                                        font-black
                                        text-violet-200
                                        ring-1
                                        ring-violet-300/30
                                    ">
                                    ★ BASE ACTIVA
                                </span>


                                <span
                                    class="
                                        text-xs
                                        font-black
                                        text-violet-200
                                    ">
                                    {{ $activeBaseEntityVersion->name }}
                                </span>

                            </div>
                        @else
                            <div class="mt-3">

                                <span
                                    class="
                                        rounded-full
                                        bg-white/10
                                        px-3
                                        py-1.5
                                        text-[8px]
                                        font-black
                                        text-white/60
                                    ">
                                    BASE ORIGINAL
                                </span>

                            </div>
                        @endif

                    </h1>


                    <p
                        class="
                            mt-3
                            text-sm
                            text-white/60
                        ">
                        {{ $entity->entityVersions->count() }}
                        versiones registradas.
                    </p>


                    <div
                        class="
                            mt-6
                            flex
                            flex-wrap
                            gap-2
                        ">

                        <a href="{{ route('entity-versions.create', $entity) }}"
                            class="
                                rounded-xl
                                bg-white
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-violet-800
                            ">
                            + Nueva Versión
                        </a>


                        @if ($entity->entityVersions->count() >= 1)
                            <a href="{{ route('entity-versions.compare', $entity) }}"
                                class="
                                    rounded-xl
                                    bg-white/10
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-white
                                ">
                                ⇄ Comparar
                            </a>
                        @endif


                        <a href="{{ route('entities.show', $entity) }}"
                            class="
                                rounded-xl
                                bg-white/10
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-white
                            ">
                            Ver Entidad
                        </a>

                    </div>

                </div>

            </div>

        </section>

        @include('entities.partials.base-version-manager')
        {{-- VIEW SWITCH --}}
        <div class="
                mt-5
                flex
                gap-2
            ">

            <button type="button" @click="
                    setView('timeline')
                "
                class="
                    rounded-xl
                    px-4
                    py-2.5
                    text-xs
                    font-black
                "
                :class="view === 'timeline'
                    ?
                    'bg-violet-600 text-white' :
                    'border border-slate-200 bg-white text-slate-500'">
                ⌘ Línea evolutiva
            </button>


            <button type="button" @click="
                    setView('grid')
                "
                class="
                    rounded-xl
                    px-4
                    py-2.5
                    text-xs
                    font-black
                "
                :class="view === 'grid'
                    ?
                    'bg-violet-600 text-white' :
                    'border border-slate-200 bg-white text-slate-500'">
                ▦ Galería
            </button>

        </div>


        @if ($entity->entityVersions->isEmpty())

            <section
                class="
                    mt-6
                    rounded-3xl
                    border
                    border-dashed
                    border-violet-200
                    bg-white
                    py-20
                    text-center
                ">

                <div
                    class="
                        text-6xl
                        text-violet-200
                    ">
                    ◈
                </div>


                <h2
                    class="
                        mt-4
                        text-xl
                        font-black
                        text-slate-700
                    ">
                    Todavía no hay Versiones
                </h2>


                <p
                    class="
                        mt-2
                        text-sm
                        text-slate-400
                    ">
                    Puedes crear una definición nueva
                    directamente desde esta Entidad.
                </p>


                <a href="{{ route('entity-versions.create', $entity) }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-violet-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear primera Versión
                </a>

            </section>
        @else
            {{-- TIMELINE --}}
            <section x-show="
                    view === 'timeline'
                "
                class="
                    mt-6
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                    sm:p-7
                ">

                <div
                    class="
                        flex
                        items-center
                        gap-4
                        rounded-2xl
                        bg-slate-950
                        p-4
                        text-white
                    ">

                    <div
                        class="
                            h-14
                            w-14
                            shrink-0
                            overflow-hidden
                            rounded-xl
                            bg-slate-800
                        ">

                        @if ($entity->image_url)
                            <img src="{{ $entity->image_url }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">
                        @endif

                    </div>


                    <div>

                        <p
                            class="
                                text-[9px]
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-400
                            ">
                            Base original
                        </p>


                        <p
                            class="
                                mt-1
                                font-black
                            ">
                            {{ $entity->name }}
                        </p>

                    </div>

                </div>


                <div
                    class="
                        ml-7
                        mt-2
                        border-l-2
                        border-violet-100
                        pl-6
                    ">

                    @foreach ($entity->entityVersions->whereNull('parent_entity_version_id') as $node)
                        @include('entity-versions.partials.tree-node', [
                            'node' => $node,
                        
                            'allVersions' => $entity->entityVersions,
                        
                            'entity' => $entity,
                        
                            'depth' => 0,
                        ])
                    @endforeach

                </div>

            </section>


            {{-- GRID --}}
            <section x-show="
                    view === 'grid'
                " x-cloak
                class="
                    mt-6
                    grid
                    gap-4
                    sm:grid-cols-2
                    lg:grid-cols-3
                    xl:grid-cols-4
                ">

                @foreach ($entity->entityVersions as $item)
                    <a href="{{ route('entity-versions.show', [$entity, $item]) }}"
                        class="
                            group
                            overflow-hidden
                            rounded-3xl
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

                            <img src="{{ $item->image_url }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                    transition
                                    duration-300
                                    group-hover:scale-105
                                ">


                            <div
                                class="
                                    absolute
                                    right-3
                                    top-3
                                    flex
                                    flex-col
                                    items-end
                                    gap-1
                                ">

                                @if ($item->baseSetting)
                                    <span
                                        class="
                                            rounded-full
                                            bg-violet-600
                                            px-2.5
                                            py-1
                                            text-[7px]
                                            font-black
                                            text-white
                                            shadow
                                        ">
                                        ★ BASE
                                    </span>
                                @endif


                                @if ($item->is_default)
                                    <span
                                        class="
                                            rounded-full
                                            bg-amber-400
                                            px-2.5
                                            py-1
                                            text-[7px]
                                            font-black
                                            text-amber-950
                                            shadow
                                        ">
                                        ⚡ RESOLVER
                                    </span>
                                @endif

                            </div>

                        </div>


                        <div class="p-4">

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-black
                                    text-slate-800
                                ">
                                {{ $item->name }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[9px]
                                    font-bold
                                    text-violet-500
                                ">
                                {{ $item->version->name }}
                                ·
                                {{ $item->version->kind_label }}
                            </p>


                            <div
                                class="
                                    mt-3
                                    flex
                                    gap-2
                                    text-[8px]
                                    text-slate-400
                                ">

                                <span>
                                    {{ $item->version_attributes_count }}
                                    cambios
                                </span>

                                <span>
                                    {{ $item->images_count }}
                                    multimedia
                                </span>

                            </div>

                        </div>

                    </a>
                @endforeach

            </section>

        @endif

    </div>

</x-app-layout>

<x-app-layout>

    <x-slot name="header">
        Versiones de {{ $entity->name }}
    </x-slot>


    @include('entities.partials.section-navigation')


    @if (session('success'))
        <div
            class="
                mb-5
                rounded-2xl
                bg-emerald-50
                p-4
                text-sm
                font-bold
                text-emerald-700
            ">
            ✓ {{ session('success') }}
        </div>
    @endif


    {{-- ENTIDAD --}}
    <section
        class="
            flex
            flex-col
            gap-5
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
            sm:flex-row
            sm:items-center
        ">

        <div
            class="
                h-24
                w-24
                shrink-0
                overflow-hidden
                rounded-2xl
                bg-slate-100
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


        <div class="min-w-0 flex-1">

            <p
                class="
                    font-mono
                    text-[9px]
                    font-black
                    text-indigo-400
                ">
                {{ $entity->code }}
            </p>

            <h1
                class="
                    mt-1
                    text-2xl
                    font-black
                    text-slate-900
                ">
                {{ $entity->name }}
            </h1>

            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                {{ $entity->entityVersions->count() }}
                Versiones
            </p>

        </div>


        <div
            class="
                flex
                flex-col
                gap-2
                sm:flex-row
            ">

            <a href="{{ route('entities.show', $entity) }}"
                class="
                    rounded-xl
                    bg-slate-100
                    px-4
                    py-2.5
                    text-center
                    text-xs
                    font-black
                    text-slate-600
                ">
                Ver Entidad
            </a>

            <a href="{{ route('entity-versions.create', $entity) }}"
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
                + Nueva Version
            </a>

        </div>

    </section>


    @if ($entity->entityVersions->isEmpty())

        <div
            class="
                mt-6
                rounded-3xl
                border
                border-dashed
                border-slate-300
                bg-white
                py-20
                text-center
            ">
            <div class="text-6xl">
                ◈
            </div>

            <p
                class="
                    mt-4
                    font-black
                    text-slate-700
                ">
                {{ $entity->name }}
                todavía no tiene Versiones.
            </p>
        </div>
    @else
        <div
            class="
                mt-6
                grid
                gap-4
                sm:grid-cols-2
                lg:grid-cols-3
                xl:grid-cols-4
            ">

            @foreach ($entity->entityVersions as $entityVersion)
                <a href="{{ route('entity-versions.show', [$entity, $entityVersion]) }}"
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
                    ">

                    <div
                        class="
                            relative
                            aspect-[4/3]
                            overflow-hidden
                            bg-slate-100
                        ">

                        <img src="{{ $entityVersion->image_url }}"
                            class="
                                h-full
                                w-full
                                object-cover
                                transition
                                duration-300
                                group-hover:scale-105
                            ">


                        @if ($entityVersion->is_default)
                            <span
                                class="
                                    absolute
                                    right-3
                                    top-3
                                    rounded-full
                                    bg-amber-400
                                    px-2.5
                                    py-1
                                    text-[8px]
                                    font-black
                                    text-amber-950
                                ">
                                ★ PREDETERMINADA
                            </span>
                        @endif

                    </div>


                    <div class="p-4">

                        <p
                            class="
                                truncate
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            {{ $entityVersion->name }}
                        </p>

                        <p
                            class="
                                mt-1
                                truncate
                                text-[9px]
                                text-violet-500
                            ">
                            {{ $entityVersion->version->name }}
                            ·
                            {{ $entityVersion->version->kind_label }}
                        </p>

                        <p
                            class="
                                mt-2
                                font-mono
                                text-[8px]
                                text-slate-400
                            ">
                            {{ $entityVersion->code }}
                        </p>

                    </div>

                </a>
            @endforeach

        </div>

    @endif

</x-app-layout>

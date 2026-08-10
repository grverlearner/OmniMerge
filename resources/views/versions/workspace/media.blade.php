<x-app-layout>

    <x-slot name="header">
        Multimedia de Versiones
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    <section
        class="
            rounded-3xl
            bg-gradient-to-br
            from-fuchsia-950
            via-violet-950
            to-slate-950
            p-6
            text-white
            sm:p-8
        ">

        <p
            class="
                text-[10px]
                font-black
                uppercase
                tracking-widest
                text-fuchsia-300
            ">
            Biblioteca visual
        </p>


        <h1 class="
                mt-2
                text-3xl
                font-black
            ">
            Multimedia
        </h1>


        <p
            class="
                mt-3
                max-w-3xl
                text-sm
                text-white/60
            ">
            Explora portadas y galerías
            de todas tus Versiones de Entidades.
        </p>

    </section>


    <form method="GET"
        class="
            mt-5
            flex
            gap-2
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-4
        ">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar Entidad o Versión..."
            class="
                min-w-0
                flex-1
                rounded-xl
                border-slate-300
            ">


        <button
            class="
                rounded-xl
                bg-slate-900
                px-5
                text-xs
                font-black
                text-white
            ">
            Buscar
        </button>

    </form>


    <div class="
            mt-6
            space-y-6
        ">

        @foreach ($entityVersions as $item)
            <section
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                ">

                <div
                    class="
                        flex
                        flex-wrap
                        items-center
                        justify-between
                        gap-3
                    ">

                    <div>

                        <a href="{{ route('entity-versions.show', [$item->entity, $item]) }}"
                            class="
                                text-lg
                                font-black
                                text-slate-900
                                hover:text-fuchsia-600
                            ">
                            {{ $item->name }}
                        </a>


                        <p
                            class="
                                mt-1
                                text-[9px]
                                text-slate-400
                            ">
                            {{ $item->entity->name }}
                            ·
                            {{ $item->version->name }}
                            ·
                            {{ $item->images_count }}
                            imágenes adicionales
                        </p>

                    </div>

                </div>


                <div
                    class="
                        mt-4
                        flex
                        gap-3
                        overflow-x-auto
                        pb-2
                    ">

                    <div
                        class="
                            relative
                            h-40
                            w-40
                            shrink-0
                            overflow-hidden
                            rounded-2xl
                        ">

                        <img src="{{ $item->image_url }}"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">


                        <span
                            class="
                                absolute
                                left-2
                                top-2
                                rounded-full
                                bg-amber-400
                                px-2
                                py-1
                                text-[7px]
                                font-black
                                text-amber-950
                            ">
                            ★ PORTADA
                        </span>

                    </div>


                    @foreach ($item->images as $image)
                        <div
                            class="
                                relative
                                h-40
                                w-40
                                shrink-0
                                overflow-hidden
                                rounded-2xl
                            ">

                            <img src="{{ $image->image_url }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">


                            <span
                                class="
                                    absolute
                                    bottom-2
                                    left-2
                                    rounded-full
                                    bg-slate-950/70
                                    px-2
                                    py-1
                                    text-[7px]
                                    font-black
                                    text-white
                                ">
                                {{ $image->media_type_label }}
                            </span>

                        </div>
                    @endforeach

                </div>

            </section>
        @endforeach

    </div>


    <div class="mt-6">
        {{ $entityVersions->links() }}
    </div>

</x-app-layout>

<x-app-layout>

    <x-slot name="header">
        {{ $entityVersion->name }}
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


    {{-- HERO --}}
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
                lg:grid-cols-[420px_minmax(0,1fr)]
            ">

            <img src="{{ $entityVersion->image_url }}"
                class="
                    h-full
                    min-h-[420px]
                    w-full
                    object-cover
                ">


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
                                text-[10px]
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
                                text-[10px]
                                font-black
                                text-indigo-700
                            ">
                            {{ $entityVersion->version->kind_label }}
                        </span>

                        @if ($entityVersion->is_default)
                            <span
                                class="
                                    rounded-full
                                    bg-amber-100
                                    px-3
                                    py-1
                                    text-[10px]
                                    font-black
                                    text-amber-700
                                ">
                                ★ Predeterminada
                            </span>
                        @endif

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
                        ·
                        {{ $entityVersion->version->name }}
                    </p>


                    <h1
                        class="
                            mt-2
                            text-4xl
                            font-black
                            text-slate-900
                        ">
                        {{ $entityVersion->name }}
                    </h1>


                    <p
                        class="
                            mt-6
                            whitespace-pre-line
                            leading-7
                            text-slate-600
                        ">
                        {{ $entityVersion->description ?: 'Sin descripción específica.' }}
                    </p>

                </div>


                <div
                    class="
                        mt-8
                        flex
                        flex-wrap
                        gap-3
                    ">

                    <a href="{{ route('entity-versions.edit', [$entity, $entityVersion]) }}"
                        class="
                            rounded-xl
                            bg-violet-600
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-white
                        ">
                        Editar
                    </a>


                    <a href="{{ route('entity-versions.attributes.edit', [$entity, $entityVersion]) }}"
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
                        ">
                        Características
                    </a>


                    <a href="{{ route('entities.show', $entity) }}"
                        class="
                            rounded-xl
                            bg-slate-100
                            px-5
                            py-3
                            text-sm
                            font-bold
                            text-slate-600
                        ">
                        Entidad base
                    </a>

                </div>

            </div>

        </div>

    </article>


    {{-- HERENCIA --}}
    <section class="
            mt-6
            grid
            gap-3
            md:grid-cols-3
        ">

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
                    text-slate-400
                ">
                Herencia base
            </p>

            <p
                class="
                    mt-2
                    font-black
                    text-slate-800
                ">
                {{ $entityVersion->inherit_base_attributes ? 'Sí' : 'No' }}
            </p>
        </article>


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
                    text-slate-400
                ">
                Versión padre
            </p>

            <p
                class="
                    mt-2
                    font-black
                    text-slate-800
                ">
                {{ $entityVersion->parent?->name ?? 'Ninguna' }}
            </p>
        </article>


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
                    text-slate-400
                ">
                Sobrescrituras
            </p>

            <p
                class="
                    mt-2
                    font-black
                    text-slate-800
                ">
                {{ $entityVersion->versionAttributes->count() }}
            </p>
        </article>

    </section>


    {{-- EFECTIVOS --}}
    <section class="mt-10">

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

            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Resultado de combinar Entidad base,
                Versiones padre y sobrescrituras.
            </p>
        </div>


        @if ($effectiveAttributes->isEmpty())

            <p
                class="
                    mt-5
                    rounded-2xl
                    bg-slate-50
                    p-5
                    text-sm
                    text-slate-400
                ">
                Esta Versión no tiene características efectivas.
            </p>
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
                                    text-[8px]
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


    {{-- GALERÍA --}}
    <section
        class="
            mt-10
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-6
        ">

        <div
            class="
                flex
                flex-col
                gap-4
                lg:flex-row
                lg:items-end
                lg:justify-between
            ">

            <div>
                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        text-fuchsia-500
                    ">
                    Multimedia
                </p>

                <h2
                    class="
                        mt-1
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Galería
                </h2>
            </div>


            <form method="POST" enctype="multipart/form-data"
                action="{{ route('entity-versions.images.store', [$entity, $entityVersion]) }}"
                class="
                    flex
                    flex-col
                    gap-2
                    sm:flex-row
                ">
                @csrf

                <input type="file" name="gallery_images[]" multiple accept=".jpg,.jpeg,.png,.webp" required
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        p-2
                        text-xs
                    ">

                <button
                    class="
                        rounded-xl
                        bg-fuchsia-600
                        px-4
                        py-2
                        text-xs
                        font-black
                        text-white
                    ">
                    Añadir
                </button>
            </form>

        </div>


        @if ($entityVersion->images->isNotEmpty())

            <div
                class="
                    mt-5
                    grid
                    grid-cols-2
                    gap-3
                    md:grid-cols-3
                    lg:grid-cols-4
                ">

                @foreach ($entityVersion->images as $image)
                    <article
                        class="
                            group
                            relative
                            overflow-hidden
                            rounded-2xl
                        ">

                        <img src="{{ $image->image_url }}"
                            class="
                                aspect-square
                                h-full
                                w-full
                                object-cover
                            ">


                        <form method="POST"
                            action="{{ route('entity-versions.images.destroy', [$entity, $entityVersion, $image]) }}"
                            class="
                                absolute
                                right-2
                                top-2
                            ">
                            @csrf
                            @method('DELETE')

                            <button
                                class="
                                    flex
                                    h-8
                                    w-8
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-red-600/90
                                    font-black
                                    text-white
                                    opacity-0
                                    transition
                                    group-hover:opacity-100
                                ">
                                ×
                            </button>
                        </form>

                    </article>
                @endforeach

            </div>
        @else
            <p
                class="
                    mt-5
                    rounded-xl
                    bg-slate-50
                    p-5
                    text-sm
                    text-slate-400
                ">
                No hay imágenes adicionales.
            </p>

        @endif

    </section>

</x-app-layout>

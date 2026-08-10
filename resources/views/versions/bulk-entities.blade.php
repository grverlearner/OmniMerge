<x-app-layout>

    <x-slot name="header">
        Asociar Entidades
    </x-slot>


    @include('entities.partials.section-navigation')


    <section
        class="
            overflow-hidden
            rounded-3xl
            border
            border-slate-200
            bg-white
        ">

        <div class="
                grid
                md:grid-cols-[220px_minmax(0,1fr)]
            ">

            <img src="{{ $version->image_url }}"
                class="
                    h-full
                    min-h-56
                    w-full
                    object-cover
                ">


            <div
                class="
                    bg-gradient-to-br
                    from-violet-950
                    to-indigo-950
                    p-6
                    text-white
                ">
                <p
                    class="
                        font-mono
                        text-[9px]
                        font-black
                        text-violet-300
                    ">
                    {{ $version->code }}
                </p>

                <h1
                    class="
                        mt-2
                        text-3xl
                        font-black
                    ">
                    {{ $version->name }}
                </h1>

                <p
                    class="
                        mt-3
                        text-sm
                        text-white/65
                    ">
                    Selecciona Entidades y proporciona
                    la imagen correspondiente a cada una.
                </p>

                @if ($version->isExclusive())
                    <p
                        class="
                            mt-4
                            rounded-xl
                            bg-amber-400/15
                            p-3
                            text-xs
                            font-black
                            text-amber-200
                        ">
                        ⚠ Esta Versión es EXCLUSIVA.
                        Solo podrás seleccionar una Entidad.
                    </p>
                @endif
            </div>

        </div>

    </section>


    {{-- FILTER --}}
    <form method="GET"
        class="
            mt-5
            grid
            gap-3
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-4
            sm:grid-cols-[minmax(0,1fr)_220px_auto]
        ">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar Entidad..."
            class="
                min-w-0
                w-full
                rounded-xl
                border-slate-300
            ">


        <select name="type"
            class="
                w-full
                rounded-xl
                border-slate-300
            ">
            <option value="">
                Todos los tipos
            </option>

            @foreach ($entityTypes as $entityType)
                <option value="{{ $entityType->id }}" @selected($typeId === $entityType->id)>
                    {{ $entityType->name }}
                </option>
            @endforeach
        </select>


        <button
            class="
                rounded-xl
                bg-slate-900
                px-5
                py-2.5
                text-sm
                font-black
                text-white
            ">
            Filtrar
        </button>

    </form>


    @if ($errors->any())
        <div
            class="
                mt-5
                rounded-2xl
                bg-red-50
                p-5
                text-sm
                text-red-700
            ">
            @foreach ($errors->all() as $error)
                <p>
                    {{ $error }}
                </p>
            @endforeach
        </div>
    @endif


    <form method="POST" enctype="multipart/form-data"
        action="{{ route('versions.entities.bulk.store', $version) }}"
        class="mt-6" x-data="{
            selected: []
        }">

        @csrf


        {{-- BULK IMAGES --}}
        <section
            class="
                rounded-2xl
                border
                border-fuchsia-200
                bg-fuchsia-50
                p-5
            ">
            <p
                class="
                    text-xs
                    font-black
                    text-fuchsia-800
                ">
                Imágenes en masa
            </p>

            <p
                class="
                    mt-1
                    text-[10px]
                    text-fuchsia-600
                ">
                Si el archivo se llama
                <strong>Naruto Uzumaki.jpg</strong>,
                se intentará relacionar con
                <strong>Naruto Uzumaki</strong>.
            </p>


            <input type="file" name="bulk_images[]" multiple accept=".jpg,.jpeg,.png,.webp"
                class="
                    mt-4
                    block
                    w-full
                    text-xs
                ">
        </section>


        <div
            class="
                mt-5
                overflow-x-auto
                rounded-3xl
                border
                border-slate-200
                bg-white
            ">

            <table class="
                    min-w-[900px]
                    w-full
                ">

                <thead class="bg-slate-50">
                    <tr>

                        <th class="w-12 px-3 py-3">
                            ✓
                        </th>

                        <th
                            class="
                                px-3
                                py-3
                                text-left
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Entidad
                        </th>

                        <th
                            class="
                                px-3
                                py-3
                                text-left
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Nombre de la Version
                        </th>

                        <th
                            class="
                                px-3
                                py-3
                                text-left
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Imagen individual
                        </th>

                    </tr>
                </thead>


                <tbody
                    class="
                        divide-y
                        divide-slate-100
                    ">

                    @foreach ($entities as $entity)
                        <tr>
                            <td
                                class="
                                    px-3
                                    py-3
                                    text-center
                                ">
                                <input
                                    type="{{ $version->isExclusive() ? 'radio' : 'checkbox' }}"
                                    name="entity_ids[]" value="{{ $entity->id }}"
                                    class="
                                        border-slate-300
                                        text-violet-600
                                    ">
                            </td>


                            <td class="px-3 py-3">

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
                                                font-black
                                                text-slate-800
                                            ">
                                            {{ $entity->name }}
                                        </p>

                                        <p
                                            class="
                                                mt-1
                                                font-mono
                                                text-[8px]
                                                text-slate-400
                                            ">
                                            {{ $entity->code }}
                                        </p>
                                    </div>

                                </div>

                            </td>


                            <td class="px-3 py-3">
                                <input type="text" name="names[{{ $entity->id }}]"
                                    placeholder="{{ $entity->name }} — {{ $version->name }}"
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-300
                                        text-xs
                                    ">
                            </td>


                            <td class="px-3 py-3">
                                <input type="file" name="images[{{ $entity->id }}]" accept=".jpg,.jpeg,.png,.webp"
                                    class="
                                        w-full
                                        text-xs
                                    ">
                            </td>

                        </tr>
                    @endforeach

                </tbody>
            </table>

        </div>


        <div
            class="
                sticky
                bottom-4
                mt-5
                flex
                justify-end
                rounded-2xl
                border
                border-slate-200
                bg-white/95
                p-4
                shadow-2xl
                backdrop-blur
            ">
            <button
                class="
                    rounded-xl
                    bg-violet-600
                    px-6
                    py-3
                    text-sm
                    font-black
                    text-white
                ">
                Crear Versiones seleccionadas
            </button>
        </div>

    </form>

</x-app-layout>

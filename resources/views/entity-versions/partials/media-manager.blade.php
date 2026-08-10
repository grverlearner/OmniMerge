@php

    $mediaPayload = $entityVersion->images
        ->map(
            fn($image) => [
                'id' => (string) $image->id,

                'image_url' => $image->image_url,

                'caption' => $image->caption ?? '',

                'alt_text' => $image->alt_text ?? '',

                'media_type' => $image->media_type,

                'type_label' => $image->media_type_label,

                'update_url' => route('entity-versions.images.update', [$entity, $entityVersion, $image]),

                'primary_url' => route('entity-versions.images.primary', [$entity, $entityVersion, $image]),

                'delete_url' => route('entity-versions.images.destroy', [$entity, $entityVersion, $image]),
            ],
        )
        ->values()
        ->all();
@endphp


<section x-data="mediaManager(
    @js($mediaPayload)
)"
    class="
        rounded-3xl
        border
        border-slate-200
        bg-white
        p-5
        shadow-sm
        sm:p-6
    ">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


    <div
        class="
            flex
            flex-col
            gap-5
            lg:flex-row
            lg:items-start
            lg:justify-between
        ">

        <div>

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
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
                Galería de la Versión
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-xs
                    leading-5
                    text-slate-500
                ">
                Guarda retratos, cuerpo completo,
                escenas de combate, referencias,
                apariencias y artes alternativos.
                Cualquier imagen puede convertirse
                en la portada principal.
            </p>

        </div>


        <form method="POST" enctype="multipart/form-data"
            action="{{ route('entity-versions.images.store', [$entity, $entityVersion]) }}"
            class="
                w-full
                rounded-2xl
                border
                border-fuchsia-100
                bg-fuchsia-50
                p-4
                lg:max-w-md
            ">

            @csrf


            <p
                class="
                    text-xs
                    font-black
                    text-fuchsia-800
                ">
                + Añadir imágenes
            </p>


            <select name="media_type"
                class="
                    mt-3
                    w-full
                    rounded-xl
                    border-fuchsia-200
                    text-xs
                ">

                <option value="PORTRAIT">
                    Retrato
                </option>

                <option value="FULL_BODY">
                    Cuerpo completo
                </option>

                <option value="COMBAT">
                    Combate
                </option>

                <option value="OUTFIT">
                    Apariencia
                </option>

                <option value="REFERENCE">
                    Referencia
                </option>

                <option value="ALTERNATIVE" selected>
                    Alternativa
                </option>

                <option value="OTHER">
                    Otra
                </option>

            </select>


            <div class="mt-3">

                <x-omni-multi-image-upload name="gallery_images[]" label="Seleccionar Multimedia" :max-mb="2"
                    :max-files="20" />

            </div>


            <button
                class="
                    mt-3
                    w-full
                    rounded-xl
                    bg-fuchsia-600
                    px-4
                    py-2.5
                    text-xs
                    font-black
                    text-white
                ">
                Subir Multimedia
            </button>

        </form>

    </div>


    {{-- PORTADA --}}
    <div
        class="
            mt-6
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
                gap-4
                sm:flex-row
                sm:items-center
            ">

            <img src="{{ $entityVersion->image_url }}"
                class="
                    h-24
                    w-24
                    shrink-0
                    rounded-2xl
                    object-cover
                ">


            <div>

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-amber-600
                    ">
                    ★ Imagen principal
                </p>


                <p
                    class="
                        mt-1
                        text-sm
                        font-black
                        text-slate-800
                    ">
                    Portada de {{ $entityVersion->name }}
                </p>


                <p
                    class="
                        mt-1
                        text-[10px]
                        text-slate-500
                    ">
                    Esta imagen será la utilizada
                    en tarjetas, Torneos y vistas compactas.
                </p>

            </div>

        </div>

    </div>


    {{-- GALERÍA --}}
    <template x-if="
            items.length > 0
        ">

        <div class="mt-6">

            <p
                class="
                    mb-3
                    text-[9px]
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Arrastra las tarjetas para cambiar el orden
            </p>


            <div
                class="
                    grid
                    gap-4
                    sm:grid-cols-2
                    xl:grid-cols-3
                ">

                <template
                    x-for="
                        (item, index)
                        in items
                    "
                    :key="item.id">

                    <article draggable="true"
                        @dragstart="
                            dragIndex = index
                        "
                        @dragover.prevent
                        @drop.prevent="
                            move(
                                dragIndex,
                                index
                            )
                        "
                        class="
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                        ">

                        <button type="button"
                            @click="
                                lightbox =
                                    item.image_url
                            "
                            class="
                                group
                                relative
                                block
                                aspect-[4/3]
                                w-full
                                overflow-hidden
                                bg-slate-100
                            ">

                            <img :src="item.image_url"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                    transition
                                    duration-300
                                    group-hover:scale-105
                                ">


                            <span
                                class="
                                    absolute
                                    left-3
                                    top-3
                                    rounded-full
                                    bg-slate-950/75
                                    px-2.5
                                    py-1
                                    text-[8px]
                                    font-black
                                    text-white
                                    backdrop-blur
                                "
                                x-text="
                                    item.type_label
                                "></span>


                            <span
                                class="
                                    absolute
                                    bottom-3
                                    right-3
                                    rounded-lg
                                    bg-white/90
                                    px-2
                                    py-1
                                    text-[8px]
                                    font-black
                                    text-slate-700
                                ">
                                ⛶ Ver
                            </span>

                        </button>


                        <div class="p-4">

                            {{-- EDIT --}}
                            <form method="POST" :action="item.update_url">

                                @csrf
                                @method('PATCH')


                                <select name="media_type"
                                    x-model="
                                        item.media_type
                                    "
                                    class="
                                        w-full
                                        rounded-xl
                                        border-slate-200
                                        text-xs
                                    ">

                                    <option value="PORTRAIT">
                                        Retrato
                                    </option>

                                    <option value="FULL_BODY">
                                        Cuerpo completo
                                    </option>

                                    <option value="COMBAT">
                                        Combate
                                    </option>

                                    <option value="OUTFIT">
                                        Apariencia
                                    </option>

                                    <option value="REFERENCE">
                                        Referencia
                                    </option>

                                    <option value="ALTERNATIVE">
                                        Alternativa
                                    </option>

                                    <option value="OTHER">
                                        Otra
                                    </option>

                                </select>


                                <input type="text" name="caption"
                                    x-model="
                                        item.caption
                                    "
                                    placeholder="Título de la imagen..."
                                    class="
                                        mt-2
                                        w-full
                                        rounded-xl
                                        border-slate-200
                                        text-xs
                                    ">


                                <input type="text" name="alt_text"
                                    x-model="
                                        item.alt_text
                                    "
                                    placeholder="Descripción accesible..."
                                    class="
                                        mt-2
                                        w-full
                                        rounded-xl
                                        border-slate-200
                                        text-xs
                                    ">


                                <button
                                    class="
                                        mt-2
                                        w-full
                                        rounded-xl
                                        bg-slate-100
                                        py-2
                                        text-[9px]
                                        font-black
                                        text-slate-600
                                    ">
                                    Guardar información
                                </button>

                            </form>


                            <div
                                class="
                                    mt-3
                                    grid
                                    grid-cols-2
                                    gap-2
                                ">

                                {{-- PRIMARY --}}
                                <form method="POST" :action="item.primary_url">

                                    @csrf


                                    <button
                                        class="
                                            w-full
                                            rounded-xl
                                            bg-amber-50
                                            px-3
                                            py-2
                                            text-[9px]
                                            font-black
                                            text-amber-700
                                        ">
                                        ★ Usar como portada
                                    </button>

                                </form>


                                {{-- DELETE --}}
                                <form method="POST" :action="item.delete_url"
                                    onsubmit="
                                        return confirm(
                                            '¿Eliminar esta imagen?'
                                        );
                                    ">

                                    @csrf
                                    @method('DELETE')


                                    <button
                                        class="
                                            w-full
                                            rounded-xl
                                            bg-red-50
                                            px-3
                                            py-2
                                            text-[9px]
                                            font-black
                                            text-red-600
                                        ">
                                        Eliminar
                                    </button>

                                </form>

                                <form method="POST"
                                    action="{{ route('entities.presentation.update', $entity) }}">

                                    @csrf
                                    @method('PUT')


                                    <input type="hidden" name="mode" value="VERSION_MEDIA">


                                    <input type="hidden" name="entity_version_id" value="{{ $entityVersion->id }}">


                                    <input type="hidden" name="entity_version_image_id" :value="item.id">


                                    <input type="hidden" name="use_version_name" value="1">


                                    <input type="hidden" name="use_version_description" value="1">


                                    <button
                                        class="
                                            mt-2
                                            w-full
                                            rounded-xl
                                            bg-fuchsia-50
                                            px-3
                                            py-2
                                            text-[9px]
                                            font-black
                                            text-fuchsia-700
                                        ">
                                        ◎ Usar públicamente
                                    </button>

                                </form>

                            </div>

                        </div>

                    </article>

                </template>

            </div>


            {{-- REORDER --}}
            <form method="POST" action="{{ route('entity-versions.images.reorder', [$entity, $entityVersion]) }}"
                class="
                    mt-4
                    flex
                    justify-end
                ">

                @csrf
                @method('PATCH')


                <template x-for="
                        item in items
                    " :key="`order-${item.id}`">

                    <input type="hidden" name="ordered_ids[]" :value="item.id">

                </template>


                <button
                    class="
                        rounded-xl
                        bg-slate-900
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-white
                    ">
                    Guardar orden
                </button>

            </form>

        </div>

    </template>


    <div x-show="
            items.length === 0
        "
        class="
            mt-6
            rounded-2xl
            border
            border-dashed
            border-fuchsia-200
            bg-fuchsia-50/30
            p-10
            text-center
        ">

        <div class="
                text-5xl
                text-fuchsia-200
            ">
            ▣
        </div>


        <p
            class="
                mt-3
                text-sm
                font-black
                text-slate-600
            ">
            Todavía no hay multimedia adicional
        </p>


        <p class="
                mt-1
                text-xs
                text-slate-400
            ">
            La portada principal seguirá funcionando normalmente.
        </p>

    </div>


    {{-- LIGHTBOX --}}
    <div x-show="lightbox" x-cloak @keydown.escape.window="
            lightbox = null
        "
        @click.self="
            lightbox = null
        "
        class="
            fixed
            inset-0
            z-[100]
            flex
            items-center
            justify-center
            bg-slate-950/90
            p-5
            backdrop-blur-sm
        ">

        <button type="button" @click="
                lightbox = null
            "
            class="
                absolute
                right-5
                top-5
                flex
                h-11
                w-11
                items-center
                justify-center
                rounded-full
                bg-white/10
                text-xl
                font-black
                text-white
            ">
            ×
        </button>


        <img :src="lightbox"
            class="
                max-h-[90vh]
                max-w-[95vw]
                rounded-2xl
                object-contain
                shadow-2xl
            ">

    </div>


    <script>
        function mediaManager(
            initialItems
        ) {

            return {

                items: initialItems ?? [],

                dragIndex: null,

                lightbox: null,


                move(
                    from,
                    to
                ) {

                    if (
                        from === null ||
                        from === to
                    ) {
                        return;
                    }


                    const item =
                        this.items.splice(
                            from,
                            1
                        )[0];


                    this.items.splice(
                        to,
                        0,
                        item
                    );


                    this.dragIndex =
                        null;
                },
            };
        }
    </script>

</section>

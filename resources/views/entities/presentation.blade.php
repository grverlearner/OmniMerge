<x-app-layout>

    <x-slot name="header">
        Presentación pública
    </x-slot>


    @include('entities.partials.section-navigation')


    @php

        $presentation = $entity->presentation;

        $initialMode = old('mode', $presentation?->mode ?? 'BASE');

        $initialVersion = old('entity_version_id', $presentation?->entity_version_id ?? '');

        $initialMedia = old('entity_version_image_id', $presentation?->entity_version_image_id ?? '');

        $versionPayload = $entity->entityVersions
            ->map(
                fn($item) => [
                    'id' => (string) $item->id,

                    'name' => $item->name,

                    'description' => $item->description ?? '',

                    'definition_name' => $item->version?->name ?? 'Versión',

                    'image_url' => $item->image_url,

                    'is_default' => (bool) $item->is_default,

                    'images' => $item->images
                        ->map(
                            fn($image) => [
                                'id' => (string) $image->id,

                                'url' => $image->image_url,

                                'caption' => $image->caption ?: $image->media_type_label,

                                'type' => $image->media_type_label,
                            ],
                        )
                        ->values()
                        ->all(),
                ],
            )
            ->values()
            ->all();
    @endphp


    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


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


    @if ($errors->any())

        <div
            class="
                mb-5
                rounded-2xl
                border
                border-red-200
                bg-red-50
                p-4
            ">

            @foreach ($errors->all() as $error)
                <p
                    class="
                        text-xs
                        font-bold
                        text-red-600
                    ">
                    {{ $error }}
                </p>
            @endforeach

        </div>

    @endif


    <form method="POST"
        action="{{ route('entities.presentation.update', $entity) }}"
        x-data="entityPresentationBuilder({
            versions: @js($versionPayload),
        
            entityName: @js($entity->name),
        
            entityDescription: @js($entity->description ?? ''),
        
            entityImage: @js($entity->image_url),
        
            initialMode: @js($initialMode),
        
            initialVersion: @js((string) $initialVersion),
        
            initialMedia: @js((string) $initialMedia),
        })">

        @csrf
        @method('PUT')


        <input type="hidden" name="mode" :value="mode">


        <input type="hidden" name="entity_version_id" :value="selectedVersionId">


        <input type="hidden" name="entity_version_image_id" :value="selectedMediaId">


        {{-- HERO --}}
        <section
            class="
                overflow-hidden
                rounded-3xl
                bg-gradient-to-br
                from-slate-950
                via-indigo-950
                to-violet-950
                p-6
                text-white
                shadow-xl
                sm:p-8
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-widest
                    text-violet-300
                ">
                Presentación
            </p>


            <h1
                class="
                    mt-2
                    text-3xl
                    font-black
                    sm:text-4xl
                ">
                ¿Cómo debe mostrarse
                {{ $entity->name }}?
            </h1>


            <p
                class="
                    mt-3
                    max-w-3xl
                    text-sm
                    leading-6
                    text-white/60
                ">
                Esta selección afecta la presentación pública.
                No modifica la Versión predeterminada utilizada
                por el Resolver ni por futuros Torneos.
            </p>

        </section>


        <div
            class="
                mt-6
                grid
                gap-6
                xl:grid-cols-[minmax(0,1fr)_380px]
            ">

            {{-- SELECCIÓN --}}
            <div class="space-y-6">

                <section
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                        shadow-sm
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-indigo-500
                        ">
                        1 · Fuente
                    </p>


                    <h2
                        class="
                            mt-1
                            text-xl
                            font-black
                            text-slate-900
                        ">
                        Elige qué Version mostrar
                    </h2>


                    <div
                        class="
                            mt-5
                            grid
                            gap-4
                            sm:grid-cols-2
                            lg:grid-cols-3
                        ">

                        {{-- BASE --}}
                        <button type="button"
                            @click="
                                selectBase()
                            "
                            class="
                                overflow-hidden
                                rounded-2xl
                                border-2
                                bg-white
                                text-left
                                transition
                            "
                            :class="mode === 'BASE'
                                ?
                                'border-indigo-500 ring-4 ring-indigo-100' :
                                'border-slate-200 hover:border-indigo-200'">

                            <div
                                class="
                                    aspect-square
                                    overflow-hidden
                                    bg-slate-100
                                ">

                                @if ($entity->image_url)
                                    <img src="{{ $entity->image_url }}"
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
                                            text-6xl
                                            text-indigo-200
                                        ">
                                        ✦
                                    </div>
                                @endif

                            </div>


                            <div class="p-4">

                                <p
                                    class="
                                        text-[8px]
                                        font-black
                                        uppercase
                                        text-indigo-500
                                    ">
                                    Entidad base
                                </p>


                                <p
                                    class="
                                        mt-1
                                        truncate
                                        text-sm
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $entity->name }}
                                </p>

                            </div>

                        </button>


                        {{-- VERSIONES --}}
                        <template
                            x-for="
                                item
                                in versions
                            "
                            :key="item.id">

                            <button type="button"
                                @click="
                                    selectVersion(
                                        item
                                    )
                                "
                                class="
                                    overflow-hidden
                                    rounded-2xl
                                    border-2
                                    bg-white
                                    text-left
                                    transition
                                "
                                :class="mode !== 'BASE'
                                    &&
                                    String(
                                        selectedVersionId
                                    ) ===
                                    String(
                                        item.id
                                    ) ?
                                    'border-violet-500 ring-4 ring-violet-100' :
                                    'border-slate-200 hover:border-violet-200'">

                                <div
                                    class="
                                        relative
                                        aspect-square
                                        overflow-hidden
                                        bg-slate-100
                                    ">

                                    <img x-show="
                                            item.image_url
                                        "
                                        :src="item.image_url"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        ">


                                    <span
                                        x-show="
                                            item.is_default
                                        "
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
                                        ★ RESOLVER
                                    </span>

                                </div>


                                <div class="p-4">

                                    <p class="
                                            truncate
                                            text-[8px]
                                            font-black
                                            uppercase
                                            text-violet-500
                                        "
                                        x-text="
                                            item.definition_name
                                        ">
                                    </p>


                                    <p class="
                                            mt-1
                                            truncate
                                            text-sm
                                            font-black
                                            text-slate-800
                                        "
                                        x-text="
                                            item.name
                                        ">
                                    </p>

                                </div>

                            </button>

                        </template>

                    </div>

                </section>


                {{-- IMAGEN --}}
                <section x-show="
                        mode !== 'BASE'
                    " x-cloak
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                        shadow-sm
                    ">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-fuchsia-500
                        ">
                        2 · Imagen
                    </p>


                    <h2
                        class="
                            mt-1
                            text-xl
                            font-black
                            text-slate-900
                        ">
                        ¿Qué imagen pública utilizar?
                    </h2>


                    <div
                        class="
                            mt-5
                            grid
                            grid-cols-2
                            gap-3
                            md:grid-cols-3
                            lg:grid-cols-4
                        ">

                        <button type="button"
                            @click="
                                usePrimaryImage()
                            "
                            class="
                                overflow-hidden
                                rounded-2xl
                                border-2
                                bg-white
                                text-left
                            "
                            :class="mode === 'VERSION_PRIMARY'
                                ?
                                'border-amber-400 ring-4 ring-amber-100' :
                                'border-slate-200'">

                            <div
                                class="
                                    aspect-square
                                    overflow-hidden
                                    bg-slate-100
                                ">

                                <img x-show="
                                        selectedVersion()
                                            ?.image_url
                                    "
                                    :src="selectedVersion()
                                        ?.image_url"
                                    class="
                                        h-full
                                        w-full
                                        object-cover
                                    ">

                            </div>


                            <p
                                class="
                                    p-3
                                    text-[9px]
                                    font-black
                                    text-amber-700
                                ">
                                ★ Portada de Version
                            </p>

                        </button>


                        <template
                            x-for="
                                image
                                in selectedVersion()
                                    ?.images
                                || []
                            "
                            :key="image.id">

                            <button type="button"
                                @click="
                                    useMediaImage(
                                        image
                                    )
                                "
                                class="
                                    overflow-hidden
                                    rounded-2xl
                                    border-2
                                    bg-white
                                    text-left
                                "
                                :class="mode === 'VERSION_MEDIA'
                                    &&
                                    String(
                                        selectedMediaId
                                    ) ===
                                    String(
                                        image.id
                                    ) ?
                                    'border-fuchsia-500 ring-4 ring-fuchsia-100' :
                                    'border-slate-200'">

                                <div
                                    class="
                                        aspect-square
                                        overflow-hidden
                                    ">

                                    <img :src="image.url"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        ">

                                </div>


                                <div class="p-3">

                                    <p class="
                                            truncate
                                            text-[9px]
                                            font-black
                                            text-slate-700
                                        "
                                        x-text="
                                            image.caption
                                        ">
                                    </p>


                                    <p class="
                                            mt-1
                                            text-[7px]
                                            text-slate-400
                                        "
                                        x-text="
                                            image.type
                                        ">
                                    </p>

                                </div>

                            </button>

                        </template>

                    </div>

                </section>


                {{-- TEXTO --}}
                <section x-show="
                        mode !== 'BASE'
                    " x-cloak
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                        shadow-sm
                    ">

                    <h2
                        class="
                            text-xl
                            font-black
                            text-slate-900
                        ">
                        Nombre y descripción
                    </h2>


                    <div
                        class="
                            mt-5
                            grid
                            gap-3
                            md:grid-cols-2
                        ">

                        <label
                            class="
                                flex
                                cursor-pointer
                                gap-3
                                rounded-2xl
                                border
                                border-violet-100
                                bg-violet-50
                                p-4
                            ">

                            <input type="hidden" name="use_version_name" value="0">

                            <input type="checkbox" name="use_version_name" value="1"
                                x-model="
                                    useVersionName
                                "
                                class="
                                    mt-1
                                    rounded
                                    border-violet-300
                                    text-violet-600
                                ">


                            <div>

                                <p
                                    class="
                                        text-xs
                                        font-black
                                        text-violet-800
                                    ">
                                    Usar nombre de la Version
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-[9px]
                                        text-violet-600
                                    ">
                                    Ej. Naruto Uzumaki — Shippuden
                                </p>

                            </div>

                        </label>


                        <label
                            class="
                                flex
                                cursor-pointer
                                gap-3
                                rounded-2xl
                                border
                                border-indigo-100
                                bg-indigo-50
                                p-4
                            ">

                            <input type="hidden" name="use_version_description" value="0">

                            <input type="checkbox" name="use_version_description" value="1"
                                x-model="
                                    useVersionDescription
                                "
                                class="
                                    mt-1
                                    rounded
                                    border-indigo-300
                                    text-indigo-600
                                ">


                            <div>

                                <p
                                    class="
                                        text-xs
                                        font-black
                                        text-indigo-800
                                    ">
                                    Usar descripción de la Version
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-[9px]
                                        text-indigo-600
                                    ">
                                    La descripción base se mantiene almacenada.
                                </p>

                            </div>

                        </label>

                    </div>

                </section>

            </div>


            {{-- PREVIEW --}}
            <aside>

                <div
                    class="
                        sticky
                        top-6
                        overflow-hidden
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        shadow-xl
                    ">

                    <p
                        class="
                            border-b
                            border-slate-100
                            px-5
                            py-4
                            text-[9px]
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-400
                        ">
                        Vista previa pública
                    </p>


                    <div
                        class="
                            aspect-[4/3]
                            overflow-hidden
                            bg-gradient-to-br
                            from-indigo-100
                            to-violet-100
                        ">

                        <img x-show="
                                previewImage()
                            "
                            :src="previewImage()"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">

                    </div>


                    <div class="p-5">

                        <p class="
                                text-[9px]
                                font-black
                                uppercase
                                text-violet-500
                            "
                            x-text="
                                modeLabel()
                            "></p>


                        <h3 class="
                                mt-2
                                text-2xl
                                font-black
                                text-slate-900
                            "
                            x-text="
                                previewName()
                            "></h3>


                        <p class="
                                mt-3
                                line-clamp-4
                                text-sm
                                leading-6
                                text-slate-500
                            "
                            x-text="
                                previewDescription()
                                || 'Sin descripción.'
                            ">
                        </p>

                    </div>


                    <div
                        class="
                            border-t
                            border-slate-100
                            p-4
                        ">

                        <button type="submit"
                            class="
                                w-full
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
                            Guardar presentación
                        </button>


                        <a href="{{ route('entities.show', $entity) }}"
                            class="
                                mt-2
                                block
                                rounded-xl
                                bg-slate-100
                                px-5
                                py-3
                                text-center
                                text-xs
                                font-bold
                                text-slate-600
                            ">
                            Cancelar
                        </a>

                    </div>

                </div>

            </aside>

        </div>

    </form>


    <script>
        function entityPresentationBuilder(
            config
        ) {

            return {

                versions: config.versions ??
                    [],

                entityName: config.entityName,

                entityDescription: config.entityDescription,

                entityImage: config.entityImage,

                mode: config.initialMode ||
                    'BASE',

                selectedVersionId: String(
                    config.initialVersion ||
                    ''
                ),

                selectedMediaId: String(
                    config.initialMedia ||
                    ''
                ),

                useVersionName: @js(old('use_version_name', $presentation?->use_version_name ?? true)),

                useVersionDescription: @js(old('use_version_description', $presentation?->use_version_description ?? true)),


                selectedVersion() {

                    return this.versions.find(
                            item =>
                            String(
                                item.id
                            ) ===
                            String(
                                this.selectedVersionId
                            )
                        ) ||
                        null;
                },


                selectedMedia() {

                    return this
                        .selectedVersion()
                        ?.images
                        ?.find(
                            item =>
                            String(
                                item.id
                            ) ===
                            String(
                                this.selectedMediaId
                            )
                        ) ||
                        null;
                },


                selectBase() {

                    this.mode =
                        'BASE';

                    this.selectedVersionId =
                        '';

                    this.selectedMediaId =
                        '';
                },


                selectVersion(
                    item
                ) {

                    this.selectedVersionId =
                        String(
                            item.id
                        );

                    this.selectedMediaId =
                        '';

                    this.mode =
                        'VERSION_PRIMARY';
                },


                usePrimaryImage() {

                    this.mode =
                        'VERSION_PRIMARY';

                    this.selectedMediaId =
                        '';
                },


                useMediaImage(
                    image
                ) {

                    this.mode =
                        'VERSION_MEDIA';

                    this.selectedMediaId =
                        String(
                            image.id
                        );
                },


                previewImage() {

                    if (
                        this.mode ===
                        'BASE'
                    ) {

                        return this.entityImage;
                    }


                    if (
                        this.mode ===
                        'VERSION_MEDIA'
                    ) {

                        return this
                            .selectedMedia()
                            ?.url ||
                            this
                            .selectedVersion()
                            ?.image_url ||
                            this.entityImage;
                    }


                    return this
                        .selectedVersion()
                        ?.image_url ||
                        this.entityImage;
                },


                previewName() {

                    if (
                        this.mode ===
                        'BASE' ||
                        !this.useVersionName
                    ) {

                        return this.entityName;
                    }


                    return this
                        .selectedVersion()
                        ?.name ||
                        this.entityName;
                },


                previewDescription() {

                    if (
                        this.mode ===
                        'BASE' ||
                        !this.useVersionDescription
                    ) {

                        return this.entityDescription;
                    }


                    return this
                        .selectedVersion()
                        ?.description ||
                        this.entityDescription;
                },


                modeLabel() {

                    if (
                        this.mode ===
                        'BASE'
                    ) {

                        return 'Entidad base';
                    }


                    if (
                        this.mode ===
                        'VERSION_MEDIA'
                    ) {

                        return 'Version · Multimedia';
                    }


                    return 'Version · Portada';
                },
            };
        }
    </script>

</x-app-layout>

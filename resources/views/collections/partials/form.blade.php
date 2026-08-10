@php

    $editing = isset($collection) && $collection->exists;

    $selectedEntities = old(
        'entity_ids',
        $editing ? $collection->entities->pluck('id')->map(fn($id) => (string) $id)->all() : [],
    );

    $selectedEntities = array_map('strval', $selectedEntities);

@endphp


<div x-data="{

    name: @js(old('name', $collection->name ?? '')),

    entitySearch: '',

    entityType: 'ALL',

    imagePreview: @js($editing ? $collection->image_url : null),

    removeImage: false,


    slugify(value) {

        return value
            .toString()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            )
            .toLowerCase()
            .trim()
            .replace(
                /[^a-z0-9]+/g,
                '-'
            )
            .replace(
                /^-+|-+$/g,
                ''
            );
    },


    previewImage(event) {

        const file =
            event.target.files[0];


        if (!file) {
            return;
        }


        const reader =
            new FileReader();


        reader.onload =
            event =>
            this.imagePreview =
            event.target.result;


        reader.readAsDataURL(
            file
        );
    }
}">

    <div class="
            grid
            gap-8
            xl:grid-cols-[minmax(0,1fr)_320px]
        ">

        <div class="space-y-8">

            {{-- IDENTITY --}}
            <section>

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-indigo-600
                    ">
                    Identidad
                </p>


                <div
                    class="
                        mt-5
                        grid
                        gap-5
                        lg:grid-cols-2
                    ">

                    <div>

                        <label
                            class="
                                mb-2
                                block
                                text-sm
                                font-bold
                                text-slate-700
                            ">
                            Nombre *
                        </label>


                        <input name="name" x-model="name" value="{{ old('name', $collection->name ?? '') }}" required
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                text-slate-900
                            ">

                    </div>


                    <div>

                        <label
                            class="
                                mb-2
                                block
                                text-sm
                                font-bold
                                text-slate-700
                            ">
                            Código
                        </label>


                        <div
                            class="
                                rounded-xl
                                bg-slate-100
                                px-4
                                py-3
                                font-mono
                                text-sm
                                font-black
                                text-slate-700
                            ">
                            {{ $editing ? $collection->code : $previewCode }}
                        </div>

                    </div>


                    <div class="lg:col-span-2">

                        <label
                            class="
                                mb-2
                                block
                                text-sm
                                font-bold
                                text-slate-700
                            ">
                            URL
                        </label>


                        <div
                            class="
                                rounded-xl
                                bg-slate-50
                                px-4
                                py-3
                                font-mono
                                text-sm
                                text-slate-600
                            ">

                            @if ($editing)
                                {{ $collection->slug }}
                            @else
                                <span
                                    x-text="
                                        slugify(name)
                                        || 'automatico'
                                    "></span>
                            @endif

                        </div>

                    </div>

                </div>

            </section>


            {{-- DESCRIPTION --}}
            <section>

                <label class="mb-2 block text-sm font-bold">
                    Descripción
                </label>


                <textarea name="description" rows="4"
                    class="
                        w-full
                        rounded-xl
                        border-slate-300
                        text-slate-900
                    ">{{ old('description', $collection->description ?? '') }}</textarea>

            </section>

            {{-- IMAGE --}}
            <section
                @omni-image-selected="
        imagePreview =
            $event.detail.url;

        removeImage =
            false;
    "
                @omni-image-cleared="
        imagePreview =
            null;

        removeImage =
            true;
    "
                @omni-image-restored="
        imagePreview =
            $event.detail.url;

        removeImage =
            false;
    ">

                <label
                    class="
            mb-2
            block
            text-sm
            font-bold
            text-slate-700
        ">
                    Portada
                </label>


                <x-omni-image-upload name="image" label="Portada de la Colección" :current-url="$editing ? $collection->image_url : null" :max-mb="4"
                    :remove-name="$editing ? 'remove_image' : null" />

            </section>


            <section
                class="
                    grid
                    gap-4
                    sm:grid-cols-2
                ">

                <div>

                    <label class="mb-2 block text-sm font-bold">
                        Icono
                    </label>

                    <input name="icon" value="{{ old('icon', $collection->icon ?? '▤') }}"
                        class="
                            w-full
                            rounded-xl
                            border-slate-300
                            text-slate-900
                        ">

                </div>


                <div>

                    <label class="mb-2 block text-sm font-bold">
                        Color
                    </label>

                    <input type="color" name="color" value="{{ old('color', $collection->color ?? '#6366F1') }}"
                        class="
                            h-11
                            w-full
                            rounded-xl
                            border
                            border-slate-300
                        ">

                </div>

            </section>


            {{-- ENTITIES --}}
            <section
                class="
                    border-t
                    border-slate-200
                    pt-7
                ">

                <h3
                    class="
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Entidades incluidas
                </h3>


                <div
                    class="
                        mt-4
                        grid
                        gap-3
                        sm:grid-cols-2
                    ">

                    <input x-model="entitySearch" placeholder="Buscar entidad..."
                        class="
                            rounded-xl
                            border-slate-300
                            text-slate-900
                        ">


                    <select x-model="entityType"
                        class="
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                        ">

                        <option value="ALL">
                            Todos los tipos
                        </option>


                        @foreach ($entityTypes as $type)
                            <option value="{{ $type->id }}">
                                {{ $type->name }}
                            </option>
                        @endforeach

                    </select>

                </div>


                <div
                    class="
                        mt-4
                        grid
                        max-h-[600px]
                        gap-3
                        overflow-y-auto
                        pr-1
                        grid-cols-2
                        sm:grid-cols-3
                        lg:grid-cols-4
                    ">

                    @foreach ($entities as $entity)
                        <label
                            x-show="
                                (! entitySearch
                                    ||
                                    @js(mb_strtolower($entity->name . ' ' . $entity->code)).includes(
                                        entitySearch.toLowerCase()
                                    ))
&&
                                (
                                    entityType === 'ALL'
                                    ||
                                    entityType ===
                                        '{{ $entity->entity_type_id }}'
                                )
                            "
                            class="
                                relative
                                cursor-pointer
                                overflow-hidden
                                rounded-xl
                                border-2
                                border-slate-200
                                bg-white
                                has-[:checked]:border-indigo-500
                                has-[:checked]:bg-indigo-50
                            ">

                            <input type="checkbox" name="entity_ids[]" value="{{ $entity->id }}"
                                @checked(in_array((string) $entity->id, $selectedEntities, true))
                                class="
                                    absolute
                                    right-2
                                    top-2
                                    z-10
                                    rounded
                                    text-indigo-600
                                ">


                            <div class="aspect-square bg-slate-100">

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
                                            text-3xl
                                            text-indigo-300
                                        ">
                                        ✦
                                    </div>
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
                                    {{ $entity->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        truncate
                                        text-[9px]
                                        text-slate-400
                                    ">
                                    {{ $entity->entityType?->name ?? 'Sin tipo' }}
                                </p>

                            </div>

                        </label>
                    @endforeach

                </div>

            </section>


            {{-- VISIBILITY --}}
            <section
                class="
                    border-t
                    border-slate-200
                    pt-7
                ">

                <div
                    class="
                        grid
                        gap-4
                        sm:grid-cols-2
                    ">

                    <select name="visibility"
                        class="
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                        ">
                        <option value="PUBLIC" @selected(old('visibility', $collection->visibility ?? 'PUBLIC') === 'PUBLIC')>
                            🌐 Público
                        </option>

                        <option value="PRIVATE" @selected(old('visibility', $collection->visibility ?? 'PUBLIC') === 'PRIVATE')>
                            🔒 Privado
                        </option>

                        <option value="UNLISTED" @selected(old('visibility', $collection->visibility ?? 'PUBLIC') === 'UNLISTED')>
                            🔗 No listado
                        </option>
                    </select>


                    <select name="status"
                        class="
                            rounded-xl
                            border-slate-300
                            bg-white
                            text-slate-900
                        ">
                        <option value="ACTIVE">
                            Activo
                        </option>
                        <option value="INACTIVE">
                            Inactivo
                        </option>
                        <option value="ARCHIVED">
                            Archivado
                        </option>
                    </select>

                </div>


                <input type="hidden" name="allow_cloning" value="0">


                <label
                    class="
                        mt-4
                        flex
                        gap-3
                        rounded-xl
                        border
                        border-slate-200
                        p-4
                    ">

                    <input type="checkbox" name="allow_cloning" value="1" @checked(old('allow_cloning', $collection->allow_cloning ?? true))
                        class="
                            mt-1
                            rounded
                            text-indigo-600
                        ">

                    <span>
                        <strong class="text-sm">
                            Permitir clonación
                        </strong>

                        <span
                            class="
                                mt-1
                                block
                                text-xs
                                text-slate-500
                            ">
                            Otros usuarios podrán crear
                            una copia si la colección es pública.
                        </span>
                    </span>

                </label>

            </section>


            <div
                class="
                    flex
                    justify-end
                    gap-3
                    border-t
                    border-slate-200
                    pt-6
                ">

                <a href="{{ route('collections.index') }}"
                    class="
                        rounded-xl
                        border
                        border-slate-300
                        px-5
                        py-3
                        text-sm
                        font-bold
                    ">
                    Cancelar
                </a>

                <button
                    class="
                        rounded-xl
                        bg-indigo-600
                        px-6
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    {{ $editing ? 'Guardar colección' : 'Crear colección' }}
                </button>

            </div>

        </div>


        {{-- PREVIEW --}}
        <aside class="
                xl:sticky
                xl:top-24
                xl:self-start
            ">

            <div
                class="
                    overflow-hidden
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                ">

                <div
                    class="
                        aspect-[16/10]
                        bg-slate-100
                    ">

                    <template x-if="imagePreview">

                        <img :src="imagePreview"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">

                    </template>

                    <template x-if="! imagePreview">

                        <div
                            class="
                                flex
                                h-full
                                items-center
                                justify-center
                                text-6xl
                                text-indigo-300
                            ">
                            ▤
                        </div>

                    </template>

                </div>


                <div class="p-5">

                    <p
                        class="
                            font-mono
                            text-[10px]
                            text-slate-400
                        ">
                        {{ $editing ? $collection->code : $previewCode }}
                    </p>


                    <p x-text="
                            name
                            || 'Nueva colección'
                        "
                        class="
                            mt-2
                            text-xl
                            font-black
                            text-slate-900
                        ">
                    </p>

                </div>

            </div>

        </aside>

    </div>

</div>

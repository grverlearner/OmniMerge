@php

    $editing = isset($universe);

    $currentImage = $editing ? $universe->image_url : null;

@endphp


<div x-data="{

    preview: @js($currentImage),

    removeImage: false,

    loadImage(event) {

        const file =
            event.target.files[0];

        if (!file) {
            return;
        }

        this.preview =
            URL.createObjectURL(
                file
            );

        this.removeImage =
            false;
    },

    clearImage() {

        this.preview =
            null;

        this.removeImage =
            true;
    }
}" class="
        space-y-6
    ">

    <input type="hidden" name="remove_image"
        :value="removeImage
            ?
            1 :
            0">


    {{-- IDENTIDAD --}}

    <section
        class="
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-6
        ">

        <div class="
                border-b
                border-slate-100
                pb-5
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-600
                ">
                01 · Identidad
            </p>


            <h3
                class="
                    mt-2
                    text-xl
                    font-black
                    text-slate-900
                ">
                Información del Universo
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                El Universo agrupa tus torneos bajo un mismo
                nombre. Las reglas específicas viven en cada
                plantilla de torneo.
            </p>

        </div>


        <div
            class="
                mt-6
                grid
                gap-6
                lg:grid-cols-[220px_1fr]
            ">

            {{-- IMAGEN --}}

            <div>

                <p
                    class="
                        mb-2
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-500
                    ">
                    Portada
                </p>


                <div
                    class="
                        aspect-square
                        overflow-hidden
                        rounded-2xl
                        border
                        border-dashed
                        border-slate-300
                        bg-slate-50
                    ">

                    <template x-if="
                            preview
                        ">

                        <img :src="preview" alt="Preview"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">

                    </template>


                    <template x-if="
                            ! preview
                        ">

                        <div
                            class="
                                flex
                                h-full
                                flex-col
                                items-center
                                justify-center
                                gap-2
                                text-center
                            ">

                            <span
                                class="
                                    text-4xl
                                ">
                                🌌
                            </span>


                            <span
                                class="
                                    text-xs
                                    font-bold
                                    text-slate-400
                                ">
                                Sin portada
                            </span>

                        </div>

                    </template>

                </div>


                <label
                    class="
                        mt-3
                        block
                        cursor-pointer
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        px-4
                        py-2.5
                        text-center
                        text-xs
                        font-black
                        text-slate-700
                        transition
                        hover:bg-slate-50
                    ">

                    Seleccionar imagen

                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                        @change="
                            loadImage(
                                $event
                            )
                        "
                        class="
                            hidden
                        ">

                </label>


                <button type="button" @click="
                        clearImage()
                    "
                    x-show="
                        preview
                    "
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        bg-red-50
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-red-600
                    ">
                    Quitar imagen
                </button>


                <x-input-error :messages="$errors->get('image')" class="mt-2" />

            </div>


            {{-- DATOS --}}

            <div class="
                    space-y-5
                ">

                <div>

                    <label
                        class="
                            text-xs
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-500
                        ">
                        Nombre *
                    </label>


                    <input type="text" name="name"
                        value="{{ old('name', $editing ? $universe->name : '') }}"
                        placeholder="Ej. Universo Shonen"
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-slate-900
                            focus:border-violet-400
                            focus:ring-violet-400
                        ">


                    <x-input-error :messages="$errors->get('name')" class="mt-2" />

                </div>


                <div>

                    <label
                        class="
                            text-xs
                            font-black
                            uppercase
                            tracking-wider
                            text-slate-500
                        ">
                        Descripción
                    </label>


                    <textarea name="description" rows="7" placeholder="Explica qué reúne este Universo y qué tipo de torneos vivirán en él..."
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-slate-900
                            focus:border-violet-400
                            focus:ring-violet-400
                        ">{{ old('description', $editing ? $universe->description : '') }}</textarea>


                    <x-input-error :messages="$errors->get('description')" class="mt-2" />

                </div>


                <div
                    class="
                        rounded-2xl
                        border
                        border-violet-200
                        bg-violet-50
                        p-4
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            tracking-wider
                            text-violet-700
                        ">
                        Código interno
                    </p>


                    <p
                        class="
                            mt-1
                            font-mono
                            text-sm
                            font-black
                            text-violet-950
                        ">
                        {{ $editing ? $universe->code : $previewCode }}
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-violet-800/70
                        ">
                        OmniMerge genera este código automáticamente.
                    </p>

                </div>

            </div>

        </div>

    </section>


    {{-- ESTADO --}}

    <section
        class="
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-6
        ">

        <p
            class="
                text-[10px]
                font-black
                uppercase
                tracking-wider
                text-violet-600
            ">
            02 · Organización
        </p>


        <h3
            class="
                mt-2
                text-xl
                font-black
                text-slate-900
            ">
            Estado del Universo
        </h3>


        <div
            class="
                mt-5
                max-w-sm
            ">

            <label
                class="
                    text-xs
                    font-black
                    uppercase
                    text-slate-500
                ">
                Estado
            </label>


            <select name="status"
                class="
                    mt-2
                    w-full
                    rounded-xl
                    border-slate-300
                    focus:border-violet-400
                    focus:ring-violet-400
                ">

                @foreach ([
        'DRAFT' => 'Borrador',
        'ACTIVE' => 'Activo',
        'ARCHIVED' => 'Archivado',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $editing ? $universe->status : 'DRAFT') === $value)>
                        {{ $label }}
                    </option>
                @endforeach

            </select>


            <x-input-error :messages="$errors->get('status')" class="mt-2" />

        </div>

    </section>


    {{-- ACTIONS --}}

    <div
        class="
            flex
            flex-col-reverse
            gap-3
            sm:flex-row
            sm:justify-end
        ">

        <a href="{{ $editing ? route('universes.show', $universe) : route('universes.index') }}"
            class="
                rounded-xl
                border
                border-slate-200
                bg-white
                px-5
                py-3
                text-center
                text-sm
                font-black
                text-slate-600
            ">
            Cancelar
        </a>


        <button type="submit"
            class="
                rounded-xl
                bg-violet-500
                px-6
                py-3
                text-sm
                font-black
                text-white
                shadow-lg
                shadow-violet-500/20
                transition
                hover:bg-violet-600
            ">
            {{ $editing ? 'Guardar cambios' : 'Crear Universo' }}
        </button>

    </div>

</div>

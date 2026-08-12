@php

    $editing = isset($tournamentTemplate);

    $currentImage = $editing ? $tournamentTemplate->image_url : null;

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
                    text-amber-600
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
                Información de la plantilla
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Define el concepto general. Las fases y reglas
                específicas se configurarán después.
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
                                🏆
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
                        value="{{ old('name', $editing ? $tournamentTemplate->name : '') }}"
                        placeholder="Ej. Copa Eliminación Clásica"
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-slate-900
                            focus:border-amber-400
                            focus:ring-amber-400
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


                    <textarea name="description" rows="7" placeholder="Explica la finalidad y estructura general de esta plantilla..."
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border-slate-300
                            text-slate-900
                            focus:border-amber-400
                            focus:ring-amber-400
                        ">{{ old('description', $editing ? $tournamentTemplate->description : '') }}</textarea>


                    <x-input-error :messages="$errors->get('description')" class="mt-2" />

                </div>


                <div
                    class="
                        rounded-2xl
                        border
                        border-amber-200
                        bg-amber-50
                        p-4
                    ">

                    <p
                        class="
                            text-[9px]
                            font-black
                            uppercase
                            tracking-wider
                            text-amber-700
                        ">
                        Código interno
                    </p>


                    <p
                        class="
                            mt-1
                            font-mono
                            text-sm
                            font-black
                            text-amber-950
                        ">
                        {{ $editing ? $tournamentTemplate->code : $previewCode }}
                    </p>


                    <p
                        class="
                            mt-1
                            text-xs
                            text-amber-800/70
                        ">
                        OmniMerge genera este código automáticamente.
                    </p>

                </div>

            </div>

        </div>

    </section>


    {{-- PARTICIPANTES --}}

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
                text-amber-600
            ">
            02 · Participantes
        </p>


        <h3
            class="
                mt-2
                text-xl
                font-black
                text-slate-900
            ">
            Capacidad general
        </h3>


        <p class="
                mt-2
                text-sm
                text-slate-500
            ">
            Define el rango aceptado por la plantilla.
            El Universo podrá aplicar restricciones adicionales posteriormente.
        </p>


        <div
            class="
                mt-6
                grid
                gap-5
                md:grid-cols-2
            ">

            <div>

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Mínimo *
                </label>


                <input type="number" name="min_participants" min="2" max="512"
                    value="{{ old('min_participants', $editing ? $tournamentTemplate->min_participants : 2) }}"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">


                <x-input-error :messages="$errors->get('min_participants')" class="mt-2" />

            </div>


            <div>

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Máximo
                </label>


                <input type="number" name="max_participants" min="2" max="512"
                    value="{{ old('max_participants', $editing ? $tournamentTemplate->max_participants : '') }}"
                    placeholder="Sin límite definido"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">


                <x-input-error :messages="$errors->get('max_participants')" class="mt-2" />

            </div>

        </div>


        <label
            class="
                mt-5
                flex
                cursor-pointer
                items-start
                gap-3
                rounded-2xl
                border
                border-slate-200
                p-4
            ">

            <input type="checkbox" name="allow_byes" value="1" @checked(old('allow_byes', $editing ? $tournamentTemplate->allow_byes : false))
                class="
                    mt-0.5
                    rounded
                    border-slate-300
                    text-amber-500
                    focus:ring-amber-500
                ">


            <span>

                <span
                    class="
                        block
                        text-sm
                        font-black
                        text-slate-800
                    ">
                    Permitir BYE
                </span>


                <span
                    class="
                        mt-1
                        block
                        text-xs
                        leading-5
                        text-slate-500
                    ">
                    Una fase futura podrá permitir que determinados
                    participantes avancen automáticamente cuando la
                    llave no esté completa.
                </span>

            </span>

        </label>

    </section>


    {{-- PUBLICACIÓN --}}

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
                text-amber-600
            ">
            03 · Organización
        </p>


        <div
            class="
                mt-5
                grid
                gap-5
                md:grid-cols-2
            ">

            <div>

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
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">

                    @foreach ([
        'DRAFT' => 'Borrador',
        'ACTIVE' => 'Activa',
        'ARCHIVED' => 'Archivada',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $editing ? $tournamentTemplate->status : 'DRAFT') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <label
                    class="
                        text-xs
                        font-black
                        uppercase
                        text-slate-500
                    ">
                    Visibilidad
                </label>


                <select name="visibility"
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        focus:border-amber-400
                        focus:ring-amber-400
                    ">

                    @foreach ([
        'PRIVATE' => 'Privada',
        'PUBLIC' => 'Pública',
        'UNLISTED' => 'No listada',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('visibility', $editing ? $tournamentTemplate->visibility : 'PRIVATE') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

            </div>

        </div>


        <label
            class="
                mt-5
                flex
                cursor-pointer
                items-start
                gap-3
                rounded-2xl
                border
                border-violet-200
                bg-violet-50/50
                p-4
            ">

            <input type="checkbox" name="allow_cloning" value="1" @checked(old('allow_cloning', $editing ? $tournamentTemplate->allow_cloning : true))
                class="
                    mt-0.5
                    rounded
                    border-violet-300
                    text-violet-600
                    focus:ring-violet-500
                ">


            <span>

                <span
                    class="
                        block
                        text-sm
                        font-black
                        text-violet-900
                    ">
                    Permitir clonación cuando sea pública
                </span>


                <span
                    class="
                        mt-1
                        block
                        text-xs
                        leading-5
                        text-violet-700
                    ">
                    Dejamos preparado este comportamiento para
                    la futura integración de Plantillas con Comunidad.
                </span>

            </span>

        </label>

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

        <a href="{{ $editing ? route('tournaments.templates.show', $tournamentTemplate) : route('tournaments.templates.index') }}"
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
                bg-amber-500
                px-6
                py-3
                text-sm
                font-black
                text-white
                shadow-lg
                shadow-amber-500/20
                transition
                hover:bg-amber-600
            ">
            {{ $editing ? 'Guardar cambios' : 'Crear plantilla' }}
        </button>

    </div>

</div>

@props([
    'name' => 'image',
    'label' => 'Imagen',
    'currentUrl' => null,
    'maxMb' => 4,
    'removeName' => null,
    'help' => null,
])

@php

    $inputId = 'omni-image-' . \Illuminate\Support\Str::uuid();

@endphp


<div x-data="{
    preview: @js($currentUrl),

    originalPreview: @js($currentUrl),

    fileName: '',

    fileSize: '',

    error: '',

    dragging: false,

    removed: false,

    objectUrl: null,

    maxMb: @js((int) $maxMb),

    choose() {
        this.$refs.file.click();
    },

    handleInput(event) {

        const file =
            event.target.files?.[0];

        if (!file) {
            return;
        }

        this.acceptFile(
            file
        );
    },

    handleDrop(event) {

        this.dragging =
            false;

        const file =
            event.dataTransfer
            ?.files?.[0];

        if (!file) {
            return;
        }

        const transfer =
            new DataTransfer();

        transfer.items.add(
            file
        );

        this.$refs.file.files =
            transfer.files;

        this.acceptFile(
            file
        );
    },

    acceptFile(file) {

        this.error =
            '';

        const allowed = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (
            !allowed.includes(
                file.type
            )
        ) {

            this.error =
                'Selecciona una imagen JPG, PNG o WEBP.';

            this.$refs.file.value =
                '';

            return;
        }

        const maximum =
            this.maxMb *
            1024 *
            1024;

        if (
            file.size >
            maximum
        ) {

            this.error =
                `La imagen no puede superar ${this.maxMb} MB.`;

            this.$refs.file.value =
                '';

            return;
        }

        if (
            this.objectUrl
        ) {

            URL.revokeObjectURL(
                this.objectUrl
            );
        }

        this.objectUrl =
            URL.createObjectURL(
                file
            );

        this.preview =
            this.objectUrl;

        this.fileName =
            file.name;

        this.fileSize =
            this.formatSize(
                file.size
            );

        this.removed =
            false;

        this.$dispatch(
            'omni-image-selected', {
                url: this.preview,

                name: this.fileName,

                size: this.fileSize,
            }
        );
    },

    clear() {

        this.$refs.file.value =
            '';

        if (
            this.objectUrl
        ) {

            URL.revokeObjectURL(
                this.objectUrl
            );

            this.objectUrl =
                null;
        }

        this.preview =
            null;

        this.fileName =
            '';

        this.fileSize =
            '';

        this.error =
            '';

        this.removed = !!this.originalPreview;

        this.$dispatch(
            'omni-image-cleared'
        );
    },

    restore() {

        this.$refs.file.value =
            '';

        if (
            this.objectUrl
        ) {

            URL.revokeObjectURL(
                this.objectUrl
            );

            this.objectUrl =
                null;
        }

        this.preview =
            this.originalPreview;

        this.fileName =
            '';

        this.fileSize =
            '';

        this.removed =
            false;

        this.$dispatch(
            'omni-image-restored', {
                url: this.originalPreview
            }
        );
    },

    formatSize(bytes) {

        if (
            bytes <
            1024 * 1024
        ) {

            return `${Math.round(bytes / 1024)} KB`;
        }

        return `${(
                bytes
                /
                1024
                /
                1024
            ).toFixed(2)} MB`;
    }
}" class="w-full">

    @if ($removeName)
        <input type="hidden" name="{{ $removeName }}"
            :value="removed
                ?
                1 :
                0">
    @endif


    <input x-ref="file" id="{{ $inputId }}" type="file" name="{{ $name }}"
        accept="image/jpeg,image/png,image/webp" class="sr-only"
        @change="
            handleInput(
                $event
            )
        ">


    <div @dragenter.prevent="
            dragging = true
        "
        @dragover.prevent="
            dragging = true
        "
        @dragleave.prevent="
            dragging = false
        "
        @drop.prevent="
            handleDrop(
                $event
            )
        "
        class="
            overflow-hidden
            rounded-2xl
            border-2
            border-dashed
            bg-white
            transition
        "
        :class="dragging
            ?
            'border-violet-500 bg-violet-50 ring-4 ring-violet-100' :
            (
                preview ?
                'border-slate-200' :
                'border-slate-300 hover:border-violet-300'
            )">

        {{-- PREVIEW --}}
        <div x-show="preview" class="
                relative
                overflow-hidden
            ">

            <div
                class="
                    relative
                    aspect-[16/9]
                    overflow-hidden
                    bg-slate-100
                ">

                <img :src="preview" alt=""
                    class="
                        h-full
                        w-full
                        object-cover
                    ">


                <div
                    class="
                        absolute
                        inset-x-0
                        bottom-0
                        bg-gradient-to-t
                        from-slate-950/80
                        to-transparent
                        p-4
                        pt-12
                    ">

                    <p class="
                            truncate
                            text-xs
                            font-black
                            text-white
                        "
                        x-text="
                            fileName
                            || 'Imagen actual'
                        ">
                    </p>


                    <p x-show="fileSize"
                        class="
                            mt-1
                            text-[9px]
                            font-bold
                            text-white/60
                        "
                        x-text="
                            fileSize
                        "></p>

                </div>

            </div>


            <div
                class="
                    flex
                    flex-wrap
                    gap-2
                    border-t
                    border-slate-100
                    p-3
                ">

                <button type="button" @click="
                        choose()
                    "
                    class="
                        rounded-xl
                        bg-violet-50
                        px-3
                        py-2
                        text-[10px]
                        font-black
                        text-violet-700
                        transition
                        hover:bg-violet-100
                    ">
                    ↻ Cambiar
                </button>


                <button type="button" @click="
                        clear()
                    "
                    class="
                        rounded-xl
                        bg-red-50
                        px-3
                        py-2
                        text-[10px]
                        font-black
                        text-red-600
                        transition
                        hover:bg-red-100
                    ">
                    × Quitar
                </button>


                <button
                    x-show="
                        removed
                        &&
                        originalPreview
                    "
                    type="button" @click="
                        restore()
                    "
                    class="
                        rounded-xl
                        bg-slate-100
                        px-3
                        py-2
                        text-[10px]
                        font-black
                        text-slate-600
                    ">
                    Restaurar
                </button>

            </div>

        </div>


        {{-- VACÍO --}}
        <button x-show="
                ! preview
            " type="button"
            @click="
                choose()
            "
            class="
                flex
                min-h-52
                w-full
                flex-col
                items-center
                justify-center
                p-6
                text-center
            ">

            <div
                class="
                    flex
                    h-16
                    w-16
                    items-center
                    justify-center
                    rounded-2xl
                    bg-gradient-to-br
                    from-violet-100
                    to-indigo-100
                    text-3xl
                    text-violet-500
                ">
                ↑
            </div>


            <p
                class="
                    mt-4
                    text-sm
                    font-black
                    text-slate-700
                ">
                {{ $label }}
            </p>


            <p
                class="
                    mt-2
                    text-xs
                    text-slate-400
                ">
                Arrastra una imagen aquí
                o haz clic para elegirla.
            </p>


            <span
                class="
                    mt-4
                    rounded-xl
                    bg-violet-600
                    px-4
                    py-2.5
                    text-xs
                    font-black
                    text-white
                    shadow-lg
                    shadow-violet-600/20
                ">
                Elegir imagen
            </span>


            <p
                class="
                    mt-3
                    text-[9px]
                    font-bold
                    uppercase
                    tracking-wider
                    text-slate-300
                ">
                JPG · PNG · WEBP · máx.
                {{ $maxMb }} MB
            </p>

        </button>

    </div>


    @if ($help)
        <p
            class="
                mt-2
                text-[10px]
                leading-5
                text-slate-400
            ">
            {{ $help }}
        </p>
    @endif


    <p x-show="error" x-cloak
        class="
            mt-2
            rounded-xl
            bg-red-50
            px-3
            py-2
            text-xs
            font-bold
            text-red-600
        "
        x-text="
            error
        "></p>


    @error($name)
        <p
            class="
                mt-2
                text-xs
                font-bold
                text-red-600
            ">
            {{ $message }}
        </p>
    @enderror

</div>

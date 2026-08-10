@props([
    'name' => 'gallery_images[]',
    'label' => 'Añadir imágenes',
    'maxMb' => 2,
    'maxFiles' => 20,
])


<div x-data="{
    files: [],

    error: '',

    dragging: false,

    counter: 0,

    maxMb: @js((int) $maxMb),

    maxFiles: @js((int) $maxFiles),

    choose() {
        this.$refs.input.click();
    },

    handleInput(event) {

        this.addFiles(
            Array.from(
                event.target.files ||
                []
            )
        );
    },

    handleDrop(event) {

        this.dragging =
            false;

        this.addFiles(
            Array.from(
                event.dataTransfer
                ?.files ||
                []
            )
        );
    },

    addFiles(incoming) {

        this.error =
            '';

        for (
            const file of incoming
        ) {

            if (
                this.files.length >=
                this.maxFiles
            ) {

                this.error =
                    `Puedes subir un máximo de ${this.maxFiles} imágenes.`;

                break;
            }

            if (
                ![
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ].includes(
                    file.type
                )
            ) {

                this.error =
                    `${file.name}: formato no permitido.`;

                continue;
            }

            if (
                file.size >
                this.maxMb *
                1024 *
                1024
            ) {

                this.error =
                    `${file.name}: supera ${this.maxMb} MB.`;

                continue;
            }

            this.counter++;

            this.files.push({
                id: `${Date.now()}-${this.counter}`,

                file: file,

                url: URL.createObjectURL(
                    file
                ),

                name: file.name,

                size: this.formatSize(
                    file.size
                ),
            });
        }

        this.syncInput();
    },

    removeFile(index) {

        const item =
            this.files[
                index
            ];

        if (
            item?.url
        ) {

            URL.revokeObjectURL(
                item.url
            );
        }

        this.files.splice(
            index,
            1
        );

        this.syncInput();
    },

    clearAll() {

        for (
            const item of this.files
        ) {

            URL.revokeObjectURL(
                item.url
            );
        }

        this.files = [];

        this.syncInput();
    },

    syncInput() {

        const transfer =
            new DataTransfer();

        for (
            const item of this.files
        ) {

            transfer.items.add(
                item.file
            );
        }

        this.$refs.input.files =
            transfer.files;
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
}">

    <input x-ref="input" type="file" name="{{ $name }}" multiple accept="image/jpeg,image/png,image/webp"
        class="sr-only" @change="
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
            rounded-2xl
            border-2
            border-dashed
            p-4
            transition
        "
        :class="dragging
            ?
            'border-fuchsia-500 bg-fuchsia-100' :
            'border-fuchsia-200 bg-white'">

        <button type="button" @click="
                choose()
            "
            class="
                flex
                w-full
                flex-col
                items-center
                justify-center
                rounded-xl
                py-5
                text-center
                transition
                hover:bg-fuchsia-50
            ">

            <span
                class="
                    flex
                    h-12
                    w-12
                    items-center
                    justify-center
                    rounded-xl
                    bg-fuchsia-100
                    text-xl
                    text-fuchsia-600
                ">
                ＋
            </span>


            <strong
                class="
                    mt-3
                    text-xs
                    text-slate-700
                ">
                {{ $label }}
            </strong>


            <span
                class="
                    mt-1
                    text-[9px]
                    text-slate-400
                ">
                Arrastra archivos o haz clic
                · hasta {{ $maxFiles }}
                · {{ $maxMb }} MB c/u
            </span>

        </button>


        <div x-show="
                files.length > 0
            " x-cloak class="
                mt-4
            ">

            <div
                class="
                    mb-3
                    flex
                    items-center
                    justify-between
                    gap-3
                ">

                <p
                    class="
                        text-[10px]
                        font-black
                        text-fuchsia-700
                    ">
                    <span x-text="
                            files.length
                        "></span>
                    imágenes seleccionadas
                </p>


                <button type="button" @click="
                        clearAll()
                    "
                    class="
                        text-[9px]
                        font-black
                        text-red-500
                    ">
                    Quitar todas
                </button>

            </div>


            <div
                class="
                    grid
                    grid-cols-2
                    gap-2
                    sm:grid-cols-3
                ">

                <template
                    x-for="
                        (item, index)
                        in files
                    "
                    :key="item.id">

                    <article
                        class="
                            relative
                            overflow-hidden
                            rounded-xl
                            border
                            border-slate-200
                            bg-slate-50
                        ">

                        <div
                            class="
                                aspect-square
                                overflow-hidden
                            ">

                            <img :src="item.url"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">

                        </div>


                        <button type="button"
                            @click="
                                removeFile(
                                    index
                                )
                            "
                            class="
                                absolute
                                right-2
                                top-2
                                flex
                                h-7
                                w-7
                                items-center
                                justify-center
                                rounded-full
                                bg-slate-950/80
                                text-xs
                                font-black
                                text-white
                                backdrop-blur
                            ">
                            ×
                        </button>


                        <div class="p-2">

                            <p class="
                                    truncate
                                    text-[9px]
                                    font-black
                                    text-slate-600
                                "
                                x-text="
                                    item.name
                                ">
                            </p>


                            <p class="
                                    mt-0.5
                                    text-[8px]
                                    text-slate-400
                                "
                                x-text="
                                    item.size
                                ">
                            </p>

                        </div>

                    </article>

                </template>

            </div>

        </div>

    </div>


    <p x-show="error" x-cloak
        class="
            mt-2
            rounded-xl
            bg-red-50
            p-2
            text-[10px]
            font-bold
            text-red-600
        "
        x-text="
            error
        "></p>

</div>

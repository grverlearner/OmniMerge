<style>
    [x-cloak] {
        display: none !important;
    }
</style>


<div x-data="omniConfirmModal" x-show="open" x-cloak data-omni-confirm-modal
    @omni-confirm:open.window="
        openFromEvent(
            $event
        )
    "
    @keydown.escape.window="
        close()
    "
    class="
        fixed
        inset-0
        z-[9999]
        overflow-y-auto
    "
    role="dialog"
    aria-modal="true"
    aria-labelledby="omni-confirm-title"
    aria-describedby="omni-confirm-message"
    :aria-busy="submitting ? 'true' : 'false'">

    {{-- ===================================================== --}}
    {{-- FONDO --}}
    {{-- ===================================================== --}}

    <div x-show="open" x-transition.opacity
        class="
            fixed
            inset-0
            bg-slate-950/75
            backdrop-blur-sm
        "
        @click="
            close()
        "></div>


    {{-- ===================================================== --}}
    {{-- CENTRADO --}}
    {{-- ===================================================== --}}

    <div
        class="
            relative
            flex
            min-h-full
            items-center
            justify-center
            p-4
            sm:p-6
        ">

        <section x-show="open"
            x-transition:enter="
                transition
                ease-out
                duration-200
            "
            x-transition:enter-start="
                opacity-0
                scale-95
                translate-y-4
            "
            x-transition:enter-end="
                opacity-100
                scale-100
                translate-y-0
            "
            x-transition:leave="
                transition
                ease-in
                duration-150
            "
            x-transition:leave-start="
                opacity-100
                scale-100
            "
            x-transition:leave-end="
                opacity-0
                scale-95
            " @click.stop
            class="
                relative
                w-full
                max-w-lg
                overflow-hidden
                rounded-3xl
                border
                border-white/10
                bg-white
                shadow-2xl
                shadow-slate-950/40
            ">

            {{-- ================================================= --}}
            {{-- CABECERA --}}
            {{-- ================================================= --}}

            <div
                class="
                    relative
                    overflow-hidden
                    bg-gradient-to-br
                    from-slate-950
                    via-indigo-950
                    to-violet-950
                    px-6
                    pb-7
                    pt-6
                    text-white
                ">

                {{-- Decoración --}}
                <div
                    class="
                        pointer-events-none
                        absolute
                        -right-16
                        -top-16
                        h-48
                        w-48
                        rounded-full
                        bg-violet-500/20
                        blur-3xl
                    ">
                </div>


                <div
                    class="
                        pointer-events-none
                        absolute
                        -bottom-20
                        -left-10
                        h-40
                        w-40
                        rounded-full
                        bg-indigo-400/15
                        blur-3xl
                    ">
                </div>


                <div
                    class="
                        relative
                        flex
                        items-start
                        justify-between
                        gap-5
                    ">

                    <div
                        class="
                            flex
                            min-w-0
                            items-start
                            gap-4
                        ">

                        {{-- ICONO --}}
                        <div class="
                                flex
                                h-14
                                w-14
                                shrink-0
                                items-center
                                justify-center
                                rounded-2xl
                                text-2xl
                                font-black
                                shadow-lg
                                ring-1
                                ring-white/10
                            "
                            :class="{
                                'bg-red-500/20 text-red-200': variant === 'danger',
                            
                                'bg-amber-400/20 text-amber-200': variant === 'warning',
                            
                                'bg-indigo-400/20 text-indigo-200': variant === 'primary',
                            
                                'bg-violet-400/20 text-violet-200': variant === 'violet',
                            
                                'bg-emerald-400/20 text-emerald-200': variant === 'success',
                            }">
                            <span
                                x-text="
                                    icon
                                "></span>
                        </div>


                        <div class="min-w-0">

                            <p
                                class="
                                    text-[9px]
                                    font-black
                                    uppercase
                                    tracking-[0.2em]
                                    text-white/40
                                ">
                                Confirmación OmniMerge
                            </p>


                            <h2 id="omni-confirm-title" class="
                                    mt-1
                                    text-xl
                                    font-black
                                    leading-tight
                                    text-white
                                "
                                x-text="
                                    title
                                ">
                            </h2>


                            <p id="omni-confirm-message" class="
                                    mt-2
                                    text-xs
                                    leading-5
                                    text-white/55
                                "
                                x-text="
                                    message
                                ">
                            </p>

                        </div>

                    </div>


                    {{-- CERRAR --}}
                    <button type="button" @click="
                            close()
                        "
                        :disabled="submitting"
                        aria-label="Cerrar confirmación"
                        class="
                            flex
                            h-9
                            w-9
                            shrink-0
                            items-center
                            justify-center
                            rounded-full
                            bg-white/10
                            text-lg
                            font-black
                            text-white/70
                            transition
                            hover:bg-white/20
                            hover:text-white
                            disabled:cursor-not-allowed
                            disabled:opacity-40
                        ">
                        ×
                    </button>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- CUERPO --}}
            {{-- ================================================= --}}

            <div class="p-6">

                {{-- Recurso afectado --}}
                <div x-show="
                        subject
                        ||
                        image
                    "
                    x-cloak
                    class="
                        flex
                        items-center
                        gap-4
                        rounded-2xl
                        border
                        border-slate-200
                        bg-slate-50
                        p-3
                    ">

                    <div x-show="
                            image
                        " x-cloak
                        class="
                            h-16
                            w-16
                            shrink-0
                            overflow-hidden
                            rounded-xl
                            bg-slate-200
                        ">

                        {{--
                            x-if y no solo x-show.

                            x-show oculta el <img>, pero el <img> sigue en el
                            DOM con src="" —porque `image` es null mientras no
                            hay nada que confirmar—, y un src vacio hace que
                            el navegador vuelva a pedir LA PAGINA ENTERA. Una
                            peticion duplicada en cada pantalla que monte
                            este modal, que son casi todas.
                        --}}
                        <template x-if="image">
                            <img :src="image" alt=""
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">
                        </template>

                    </div>


                    <div
                        class="
                            min-w-0
                            flex-1
                        ">

                        <p
                            class="
                                text-[8px]
                                font-black
                                uppercase
                                tracking-widest
                                text-slate-400
                            ">
                            Recurso afectado
                        </p>


                        <p class="
                                mt-1
                                truncate
                                text-sm
                                font-black
                                text-slate-800
                            "
                            x-text="
                                subject
                            "></p>


                        <span
                            class="
                                mt-2
                                inline-flex
                                rounded-full
                                px-2.5
                                py-1
                                text-[8px]
                                font-black
                            "
                            :class="{
                                'bg-red-100 text-red-700': variant === 'danger',
                            
                                'bg-amber-100 text-amber-700': variant === 'warning',
                            
                                'bg-indigo-100 text-indigo-700': variant === 'primary',
                            
                                'bg-violet-100 text-violet-700': variant === 'violet',
                            
                                'bg-emerald-100 text-emerald-700': variant === 'success',
                            }"
                            x-text="
                                variantLabel()
                            "></span>

                    </div>

                </div>


                {{-- Explicación secundaria --}}
                <div x-show="
                        detail
                    " x-cloak
                    class="
                        mt-4
                        rounded-2xl
                        border
                        border-slate-200
                        bg-white
                        px-4
                        py-3
                    ">

                    <div
                        class="
                            flex
                            items-start
                            gap-3
                        ">

                        <span
                            class="
                                mt-0.5
                                flex
                                h-6
                                w-6
                                shrink-0
                                items-center
                                justify-center
                                rounded-full
                                bg-slate-100
                                text-[10px]
                                font-black
                                text-slate-500
                            ">
                            i
                        </span>


                        <p class="
                                text-xs
                                leading-5
                                text-slate-500
                            "
                            x-text="
                                detail
                            "></p>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- BOTONES --}}
                {{-- ================================================= --}}

                <div
                    class="
                        mt-6
                        grid
                        gap-3
                        sm:grid-cols-2
                    ">

                    <button type="button" @click="
                            close()
                        "
                        :disabled="submitting"
                        class="
                            order-2
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-slate-600
                            transition
                            hover:bg-slate-50
                            disabled:cursor-not-allowed
                            disabled:opacity-50
                            sm:order-1
                        "
                        x-text="
                            cancelLabel
                        "></button>


                    <button x-ref="confirmAction" type="button"
                        @click="
                            approveAndSubmit()
                        "
                        :disabled="submitting"
                        class="
                            order-1
                            flex
                            items-center
                            justify-center
                            gap-2
                            rounded-xl
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-white
                            shadow-lg
                            transition
                            disabled:cursor-wait
                            disabled:opacity-70
                            sm:order-2
                        "
                        :class="{
                            'bg-red-600 shadow-red-600/20 hover:bg-red-700': variant === 'danger',
                        
                            'bg-amber-500 shadow-amber-500/20 hover:bg-amber-600': variant === 'warning',
                        
                            'bg-indigo-600 shadow-indigo-600/20 hover:bg-indigo-700': variant === 'primary',
                        
                            'bg-violet-600 shadow-violet-600/20 hover:bg-violet-700': variant === 'violet',
                        
                            'bg-emerald-600 shadow-emerald-600/20 hover:bg-emerald-700': variant === 'success',
                        }">

                        <span x-show="
                                submitting
                            "
                            class="
                                h-4
                                w-4
                                animate-spin
                                rounded-full
                                border-2
                                border-white/30
                                border-t-white
                            "></span>


                        <span
                            x-text="
                                submitting
                                    ? 'Procesando...'
                                    : actionLabel
                            "></span>

                    </button>

                </div>

            </div>

        </section>

    </div>

</div>

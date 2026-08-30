<style>
    [x-cloak] {
        display: none !important;
    }
</style>

{{--
    El modal de confirmación.

    Es el mismo componente de siempre —mismo estado, mismos métodos, mismo
    contrato de `data-confirm-*`—; lo que cambia es la piel: pasa de la
    tarjeta blanca con cabecera degradada a la estética oscura del resto de
    la aplicación, que es donde vive: bordes finos, fondo casi negro, y el
    color reservado para lo único que importa —qué clase de acción vas a
    confirmar—.

    Contrato (no tocar sin mirar app.js):

        title  message  detail  subject  image  icon
        actionLabel  cancelLabel
        variant   danger | warning | primary | violet | success
        variantLabel()
        open  submitting  close()  approveAndSubmit()
        $refs.confirmAction

    El color de cada variante se escribe LITERAL en cada sitio, nunca
    compuesto: Tailwind lee el archivo y una clase armada con 'bg-' . $x no
    existiría en el CSS.
--}}

<div x-data="omniConfirmModal" x-show="open" x-cloak data-omni-confirm-modal
    @omni-confirm:open.window="
        openFromEvent(
            $event
        )
    "
    @keydown.escape.window="
        close()
    "
    class="fixed inset-0 z-[9999] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="omni-confirm-title"
    aria-describedby="omni-confirm-message"
    :aria-busy="submitting ? 'true' : 'false'">

    {{-- ===================================================== --}}
    {{-- FONDO --}}
    {{-- ===================================================== --}}

    <div x-show="open" x-transition.opacity
        class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm"
        @click="close()"></div>


    {{-- ===================================================== --}}
    {{-- CENTRADO --}}
    {{-- ===================================================== --}}

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">

        <section x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/60">

            {{-- La franja de color: lo único que grita, y solo un poco --}}

            <div class="h-1 w-full"
                :class="{
                    'bg-rose-500': variant === 'danger',
                    'bg-amber-500': variant === 'warning',
                    'bg-indigo-500': variant === 'primary',
                    'bg-violet-500': variant === 'violet',
                    'bg-emerald-500': variant === 'success',
                }"></div>


            {{-- ================================================= --}}
            {{-- CABECERA --}}
            {{-- ================================================= --}}

            <div class="flex items-start gap-4 border-b border-slate-800 px-5 py-4">

                {{-- El icono, teñido por la variante --}}

                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border text-lg font-black"
                    :class="{
                        'border-rose-500/40 bg-rose-500/10 text-rose-300': variant === 'danger',
                        'border-amber-500/40 bg-amber-500/10 text-amber-300': variant === 'warning',
                        'border-indigo-500/40 bg-indigo-500/10 text-indigo-300': variant === 'primary',
                        'border-violet-500/40 bg-violet-500/10 text-violet-300': variant === 'violet',
                        'border-emerald-500/40 bg-emerald-500/10 text-emerald-300': variant === 'success',
                    }"
                    x-text="icon"></span>

                <div class="min-w-0 flex-1 pt-0.5">

                    <p class="text-[9px] font-black uppercase tracking-[0.22em]"
                        :class="{
                            'text-rose-400': variant === 'danger',
                            'text-amber-400': variant === 'warning',
                            'text-indigo-400': variant === 'primary',
                            'text-violet-400': variant === 'violet',
                            'text-emerald-400': variant === 'success',
                        }"
                        x-text="variantLabel()"></p>

                    <h2 id="omni-confirm-title"
                        class="mt-1 text-lg font-black leading-tight text-white"
                        x-text="title"></h2>

                </div>

                <button type="button" @click="close()" :disabled="submitting"
                    aria-label="Cerrar"
                    class="-mr-1 -mt-1 shrink-0 rounded-lg px-2 py-1 text-lg leading-none text-slate-600 transition hover:text-slate-200 disabled:opacity-40">
                    ×
                </button>

            </div>


            {{-- ================================================= --}}
            {{-- CUERPO --}}
            {{-- ================================================= --}}

            <div class="px-5 py-4">

                <p id="omni-confirm-message"
                    class="text-sm leading-relaxed text-slate-300"
                    x-text="message"></p>


                {{-- ============ SOBRE QUÉ ============ --}}

                {{--
                    La ficha de lo que se va a tocar. Es la mitad del valor
                    del modal: confirmar «eliminar» sin ver QUÉ se elimina no
                    es confirmar nada.
                --}}

                <div x-show="subject || image"
                    class="mt-4 flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/70 p-3">

                    <div x-show="image"
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-900">

                        {{--
                            x-if y no solo x-show.

                            x-show oculta el <img>, pero el <img> sigue en el
                            DOM: con src="" el navegador vuelve a pedir la
                            página entera. Ver el arreglo de esta misma
                            pantalla.
                        --}}
                        <template x-if="image">
                            <img :src="image" alt="" class="h-full w-full object-cover">
                        </template>

                    </div>

                    <div class="min-w-0 flex-1">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Sobre
                        </p>

                        <p class="mt-0.5 truncate text-sm font-black text-white" x-text="subject"></p>

                    </div>

                </div>


                {{-- ============ LA LETRA PEQUEÑA ============ --}}

                <div x-show="detail"
                    class="mt-3 rounded-xl border-l-2 bg-slate-950/70 py-2.5 pl-3 pr-3"
                    :class="{
                        'border-rose-500/60': variant === 'danger',
                        'border-amber-500/60': variant === 'warning',
                        'border-indigo-500/60': variant === 'primary',
                        'border-violet-500/60': variant === 'violet',
                        'border-emerald-500/60': variant === 'success',
                    }">

                    <p class="text-[11px] leading-relaxed text-slate-400" x-text="detail"></p>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- BOTONES --}}
            {{-- ================================================= --}}

            <div class="flex flex-col-reverse gap-2 border-t border-slate-800 bg-slate-950/40 px-5 py-4 sm:flex-row sm:justify-end">

                <button type="button" @click="close()" :disabled="submitting"
                    class="rounded-xl border border-slate-700 px-5 py-2.5 text-xs font-black text-slate-300 transition hover:border-slate-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    x-text="cancelLabel"></button>

                <button x-ref="confirmAction" type="button"
                    @click="approveAndSubmit()"
                    :disabled="submitting"
                    class="flex items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-xs font-black shadow-lg transition disabled:cursor-wait disabled:opacity-70"
                    :class="{
                        'bg-rose-500 text-white shadow-rose-950/40 hover:bg-rose-400': variant === 'danger',
                        'bg-amber-500 text-slate-950 shadow-amber-950/40 hover:bg-amber-400': variant === 'warning',
                        'bg-indigo-500 text-white shadow-indigo-950/40 hover:bg-indigo-400': variant === 'primary',
                        'bg-violet-500 text-white shadow-violet-950/40 hover:bg-violet-400': variant === 'violet',
                        'bg-emerald-500 text-slate-950 shadow-emerald-950/40 hover:bg-emerald-400': variant === 'success',
                    }">

                    <span x-show="submitting"
                        class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current/30 border-t-current"></span>

                    <span x-text="submitting ? 'Procesando…' : actionLabel"></span>

                </button>

            </div>

        </section>

    </div>

</div>

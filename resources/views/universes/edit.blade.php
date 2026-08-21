<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Configuración
    </x-slot>


    <div class="
            mx-auto
            max-w-5xl
        ">

        <div class="
                mb-7
            ">

            <a href="{{ route('universes.show', $universe) }}"
                class="
                    text-xs
                    font-black
                    text-slate-400
                    hover:text-violet-600
                ">
                ← Volver al Universo
            </a>


            <p
                class="
                    mt-5
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-600
                ">
                {{ $universe->code }}
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Editar {{ $universe->name }}
            </h2>

        </div>


        <form method="POST"
            action="{{ route('universes.update', $universe) }}"
            enctype="multipart/form-data">

            @csrf

            @method('PUT')


            @include('universes.partials.universe-form')

        </form>


        {{-- ARCHIVAR --}}

        @can('update', $universe)
            @if ($universe->status !== 'ARCHIVED')
                <section
                    class="
                        mt-6
                        flex
                        flex-col
                        justify-between
                        gap-4
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                        sm:flex-row
                        sm:items-center
                    ">

                    <div>
                        <p
                            class="
                                text-sm
                                font-black
                                text-slate-800
                            ">
                            Archivar Universo
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-500
                            ">
                            Deja de aparecer entre los Universos activos, pero
                            conserva todo su contenido.
                        </p>
                    </div>


                    <form method="POST" action="{{ route('universes.archive', $universe) }}">

                        @csrf

                        @method('PATCH')


                        <button type="submit"
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-slate-500
                            ">
                            Archivar
                        </button>

                    </form>

                </section>
            @endif
        @endcan


        {{-- DANGER ZONE --}}

        @can('delete', $universe)
            <section x-data="{
                deleting: false
            }"
                class="
                    mt-6
                    rounded-3xl
                    border
                    border-red-200
                    bg-red-50
                    p-6
                ">

                <div
                    class="
                        flex
                        flex-col
                        justify-between
                        gap-4
                        sm:flex-row
                        sm:items-center
                    ">

                    <div>

                        <p
                            class="
                                text-sm
                                font-black
                                text-red-800
                            ">
                            Eliminar Universo
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-red-600
                            ">
                            Se aplicará Soft Delete. Sus competidores y temporadas
                            dejarán de estar accesibles, pero las entidades de tu
                            Biblioteca y las plantillas de torneo permanecen intactas.
                        </p>

                    </div>


                    <button type="button" @click="
                            deleting = true
                        "
                        class="
                            shrink-0
                            rounded-xl
                            bg-red-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Eliminar
                    </button>

                </div>


                <div x-show="
                        deleting
                    " x-transition
                    class="
                        mt-5
                        rounded-2xl
                        border
                        border-red-200
                        bg-white
                        p-5
                    "
                    style="
                        display: none;
                    ">

                    <p class="
                            font-black
                            text-slate-900
                        ">
                        ¿Eliminar “{{ $universe->name }}”?
                    </p>


                    <div class="
                            mt-4
                            flex
                            gap-3
                        ">

                        <button type="button" @click="
                                deleting = false
                            "
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                px-4
                                py-2.5
                                text-xs
                                font-black
                                text-slate-600
                            ">
                            Cancelar
                        </button>


                        <form method="POST" action="{{ route('universes.destroy', $universe) }}">

                            @csrf

                            @method('DELETE')


                            <button type="submit"
                                class="
                                    rounded-xl
                                    bg-red-600
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-white
                                ">
                                Sí, eliminar
                            </button>

                        </form>

                    </div>

                </div>

            </section>
        @endcan

    </div>

</x-universe-layout>

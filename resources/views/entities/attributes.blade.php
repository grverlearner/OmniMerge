<x-app-layout>

    <x-slot name="header">
        Características
    </x-slot>


    @include('entities.partials.section-navigation')


    <div
        class="
            mb-6
            flex
            flex-col
            justify-between
            gap-4
            sm:flex-row
            sm:items-start
        ">

        <div>

            <a href="{{ route('entities.show', $entity) }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← {{ $entity->name }}
            </a>


            <p
                class="
                    mt-4
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-indigo-600
                ">
                Editor avanzado
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Características de {{ $entity->name }}
            </h2>


            <p
                class="
                    mt-2
                    max-w-3xl
                    text-slate-500
                ">
                Añade únicamente las características
                que correspondan a esta entidad y configura
                sus valores.
            </p>

        </div>


        <a href="{{ route('entities.edit', $entity) }}"
            class="
                rounded-xl
                border
                border-slate-300
                bg-white
                px-4
                py-2.5
                text-sm
                font-bold
                text-slate-700
            ">
            Editar entidad completa
        </a>

    </div>


    <form method="POST"
        action="{{ route('entities.attributes.update', $entity) }}">

        @csrf
        @method('PUT')


        <div
            class="
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
                sm:p-8
            ">

            @include('entities.partials.characteristics-builder')


            <div
                class="
                    mt-8
                    flex
                    justify-end
                    gap-3
                    border-t
                    border-slate-200
                    pt-6
                ">

                <a href="{{ route('entities.show', $entity) }}"
                    class="
                        rounded-xl
                        border
                        border-slate-300
                        px-5
                        py-3
                        text-sm
                        font-bold
                        text-slate-700
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
                    Guardar características
                </button>

            </div>

        </div>

    </form>

</x-app-layout>

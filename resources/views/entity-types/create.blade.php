<x-app-layout>

    <x-slot name="header">
        Entidades
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

            <a href="{{ route('entity-types.index') }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Tipos de entidad
            </a>


            <h2
                class="
                    mt-3
                    text-2xl
                    font-black
                    text-slate-900
                ">
                Nuevo tipo de entidad
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Define una categoría reutilizable como
                Personaje, País, Objeto, Criatura o Concepto.
                OmniMerge generará automáticamente su código.
            </p>

        </div>


        <div
            class="
                rounded-2xl
                border
                border-indigo-100
                bg-indigo-50
                px-5
                py-3
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-indigo-400
                ">
                Próximo código
            </p>

            <p
                class="
                    mt-1
                    font-mono
                    text-lg
                    font-black
                    text-indigo-700
                ">
                {{ $previewCode }}
            </p>

        </div>

    </div>


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

        <form method="POST" action="{{ route('entity-types.store') }}" enctype="multipart/form-data">

            @csrf


            @include('entity-types.partials.form')

        </form>

    </div>

</x-app-layout>

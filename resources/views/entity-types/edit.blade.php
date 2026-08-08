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

            <a href="{{ route('entity-types.show', $entityType) }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Volver al tipo
            </a>


            <h2
                class="
                    mt-3
                    text-2xl
                    font-black
                    text-slate-900
                ">
                Editar {{ $entityType->name }}
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Modifica su representación y configuración.
                El código y el número de creación permanecen
                bloqueados para mantener su identidad histórica.
            </p>

        </div>


        <div
            class="
                rounded-2xl
                border
                border-slate-200
                bg-slate-50
                px-5
                py-3
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-slate-400
                ">
                Identificador
            </p>

            <p
                class="
                    mt-1
                    font-mono
                    text-lg
                    font-black
                    text-slate-700
                ">
                {{ $entityType->code }}
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

        <form method="POST"
            action="{{ route('entity-types.update', $entityType) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')


            @include('entity-types.partials.form')

        </form>

    </div>

</x-app-layout>

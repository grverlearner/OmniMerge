<x-app-layout>

    <x-slot name="header">
        Entidades
    </x-slot>


    @include('entities.partials.section-navigation')


    <div class="mb-7">

        <a href="{{ route('entities.show', $entity) }}"
            class="
                text-sm
                font-bold
                text-slate-400
                hover:text-indigo-600
            ">
            ← Volver a la entidad
        </a>


        <p
            class="
                mt-4
                font-mono
                text-xs
                font-black
                text-indigo-600
            ">
            {{ $entity->code }}
            ·
            {{ $entity->slug }}
        </p>


        <h2
            class="
                mt-2
                text-3xl
                font-black
                tracking-tight
                text-slate-900
            ">
            Editar {{ $entity->name }}
        </h2>


        <p class="
                mt-2
                max-w-3xl
                text-slate-500
            ">
            Modifica información, tipo, características,
            valores, Colecciones y publicación desde
            una sola pantalla.
        </p>

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
            action="{{ route('entities.update', $entity) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')


            @include('entities.partials.form')

        </form>

    </div>

</x-app-layout>

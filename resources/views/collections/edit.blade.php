<x-app-layout>

    <x-slot name="header">
        Colecciones
    </x-slot>


    @include('entities.partials.section-navigation')


    <div class="mb-7">

        <a href="{{ route('collections.show', $collection) }}"
            class="
                text-sm
                font-bold
                text-slate-400
            ">
            ← Volver
        </a>


        <p
            class="
                mt-4
                font-mono
                text-xs
                font-black
                text-indigo-600
            ">
            {{ $collection->code }}
        </p>


        <h2
            class="
                mt-2
                text-3xl
                font-black
                text-slate-900
            ">
            Editar {{ $collection->name }}
        </h2>

    </div>


    <div
        class="
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-6
            sm:p-8
        ">

        <form method="POST"
            action="{{ route('collections.update', $collection) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')


            @include('collections.partials.form')

        </form>

    </div>

</x-app-layout>

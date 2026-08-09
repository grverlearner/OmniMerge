<x-app-layout>

    <x-slot name="header">
        Colecciones
    </x-slot>


    @include('entities.partials.section-navigation')


    <div class="mb-7">

        <p
            class="
                text-xs
                font-black
                uppercase
                text-indigo-600
            ">
            Entidades · Colecciones
        </p>


        <h2
            class="
                mt-2
                text-3xl
                font-black
                text-slate-900
            ">
            Nueva colección
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

        <form method="POST" action="{{ route('collections.store') }}" enctype="multipart/form-data">

            @csrf


            @include('collections.partials.form')

        </form>

    </div>

</x-app-layout>

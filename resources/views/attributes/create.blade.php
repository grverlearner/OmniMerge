<x-app-layout>

    <x-slot name="header">
        Atributos
    </x-slot>

    @include('attributes.partials.section-navigation')


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

            <a href="{{ route('attributes.index') }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Atributos
            </a>


            <p
                class="
                    mt-4
                    text-xs
                    font-black
                    uppercase
                    tracking-[0.16em]
                    text-indigo-600
                ">
                Biblioteca · Características
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-900
                ">
                Nuevo atributo
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Crea una característica reutilizable para
                tus entidades. Catálogo y selección múltiple
                están preparados como configuración principal.
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

        <form method="POST" action="{{ route('attributes.store') }}" enctype="multipart/form-data">

            @csrf


            @include('attributes.partials.form')

        </form>

    </div>

</x-app-layout>

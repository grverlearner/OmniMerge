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

            <a href="{{ route('attributes.show', $attribute) }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Volver al atributo
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
                {{ $attribute->code }}
                ·
                {{ $attribute->slug }}
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Editar {{ $attribute->name }}
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Modifica su configuración, imagen,
                comportamiento y publicación.
                Su código e identificador URL permanecen estables.
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

        <form method="POST" action="{{ route('attributes.update', $attribute) }}" enctype="multipart/form-data">

            @csrf
            @method('PUT')


            @include('attributes.partials.form')

        </form>

    </div>

</x-app-layout>

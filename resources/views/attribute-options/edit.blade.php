<x-app-layout>

    <x-slot name="header">
        Catálogos
    </x-slot>


    <div
        class="
            mb-7
            flex
            flex-col
            justify-between
            gap-4
            sm:flex-row
            sm:items-start
        ">

        <div>

            <a href="{{ route('attribute-options.show', $attributeOption) }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-violet-600
                ">
                ← Volver al elemento
            </a>


            <p
                class="
                    mt-4
                    font-mono
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-600
                ">
                {{ $attributeOption->code }}
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-900
                ">
                Editar {{ $attributeOption->name }}
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Modifica su representación, descripción,
                jerarquía y configuración. Su código y
                Catálogo propietario permanecen bloqueados.
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
            action="{{ route('attributes.options.update', [$attributeOption->attribute, $attributeOption]) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')


            @include('attribute-options.partials.form')

        </form>

    </div>

</x-app-layout>

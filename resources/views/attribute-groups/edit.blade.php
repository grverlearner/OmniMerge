<x-app-layout>

    <x-slot name="header">
        Grupos de atributos
    </x-slot>


    @include('attributes.partials.section-navigation')


    <div class="mb-7">

        <a href="{{ route('attribute-groups.show', $attributeGroup) }}"
            class="
                text-sm
                font-bold
                text-slate-400
                hover:text-indigo-600
            ">
            ← Volver al grupo
        </a>


        <p
            class="
                mt-4
                font-mono
                text-xs
                font-black
                uppercase
                text-indigo-600
            ">
            {{ $attributeGroup->code }}
        </p>


        <h2
            class="
                mt-2
                text-3xl
                font-black
                tracking-tight
                text-slate-900
            ">
            Editar {{ $attributeGroup->name }}
        </h2>


        <p class="
                mt-2
                max-w-3xl
                text-slate-500
            ">
            Modifica el contenido, orden y presentación
            del grupo. Su código OmniMerge permanece
            inmutable.
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
            action="{{ route('attribute-groups.update', $attributeGroup) }}">

            @csrf
            @method('PUT')


            @include('attribute-groups.partials.form')

        </form>

    </div>

</x-app-layout>

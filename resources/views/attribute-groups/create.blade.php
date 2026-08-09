<x-app-layout>

    <x-slot name="header">
        Grupos de atributos
    </x-slot>


    @include('attributes.partials.section-navigation')


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

            <a href="{{ route('attribute-groups.index') }}"
                class="
                    text-sm
                    font-bold
                    text-slate-400
                    hover:text-indigo-600
                ">
                ← Grupos
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
                Atributos · Organización
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-900
                ">
                Nuevo grupo
            </h2>


            <p
                class="
                    mt-2
                    max-w-3xl
                    text-slate-500
                ">
                Agrupa características relacionadas y define
                cómo deberían presentarse dentro de las
                fichas de Entidades.
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

        <form method="POST" action="{{ route('attribute-groups.store') }}">

            @csrf


            @include('attribute-groups.partials.form')

        </form>

    </div>

</x-app-layout>

<x-universe-layout>

    <x-slot name="header">
        Nuevo Universo
    </x-slot>


    <div class="
            mx-auto
            max-w-5xl
        ">

        <div class="
                mb-7
            ">

            <a href="{{ route('universes.index') }}"
                class="
                    text-xs
                    font-black
                    text-slate-400
                    hover:text-violet-600
                ">
                ← Mis Universos
            </a>


            <p
                class="
                    mt-5
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-600
                ">
                Universos
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Nuevo Universo
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Un Universo agrupa varios torneos bajo un mismo
                nombre. Después podrás crear plantillas de torneo
                dentro de él.
            </p>

        </div>


        <form method="POST" action="{{ route('universes.store') }}"
            enctype="multipart/form-data">

            @csrf


            @include('universes.partials.universe-form')

        </form>

    </div>

</x-universe-layout>

<x-tournament-layout>

    <x-slot name="header">
        Nueva plantilla
    </x-slot>


    <div class="
            mx-auto
            max-w-5xl
        ">

        <div class="
                mb-7
            ">

            <a href="{{ route('tournaments.templates.index') }}"
                class="
                    text-xs
                    font-black
                    text-slate-400
                    hover:text-amber-600
                ">
                ← Mis plantillas
            </a>


            <p
                class="
                    mt-5
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-amber-600
                ">
                Tournament Designer
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Nueva plantilla
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Primero define su identidad y límites generales.
                Después construiremos las fases que forman el sistema.
            </p>

        </div>


        <form method="POST" action="{{ route('tournaments.templates.store') }}"
            enctype="multipart/form-data">

            @csrf


            @include('tournaments.partials.template-form')

        </form>

    </div>

</x-tournament-layout>

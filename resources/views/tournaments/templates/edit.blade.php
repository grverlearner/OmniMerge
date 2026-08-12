<x-tournament-layout>

    <x-slot name="header">
        Editar plantilla
    </x-slot>


    <div class="
            mx-auto
            max-w-5xl
        ">

        <div class="
                mb-7
            ">

            <a href="{{ route('tournaments.templates.show', $tournamentTemplate) }}"
                class="
                    text-xs
                    font-black
                    text-slate-400
                    hover:text-amber-600
                ">
                ← Volver a la plantilla
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
                {{ $tournamentTemplate->code }}
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Editar {{ $tournamentTemplate->name }}
            </h2>

        </div>


        <form method="POST"
            action="{{ route('tournaments.templates.update', $tournamentTemplate) }}"
            enctype="multipart/form-data">

            @csrf

            @method('PUT')


            @include('tournaments.partials.template-form')

        </form>

    </div>

</x-tournament-layout>

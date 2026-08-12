<x-tournament-layout>

    <x-slot name="header">
        Nueva fase
    </x-slot>


    <div class="
            mx-auto
            max-w-4xl
        ">

        <a href="{{ route('tournaments.phases.index', $tournamentTemplate) }}"
            class="
                text-xs
                font-black
                text-slate-400
                hover:text-amber-600
            ">
            ← Volver a fases
        </a>


        <p
            class="
                mt-6
                text-xs
                font-black
                uppercase
                tracking-wider
                text-amber-600
            ">
            {{ $tournamentTemplate->name }}
        </p>


        <h2
            class="
                mt-2
                text-3xl
                font-black
                text-slate-900
            ">
            Agregar fase
        </h2>


        <p class="
                mt-2
                text-slate-500
            ">
            Empezaremos construyendo las bases de Eliminación directa.
        </p>


        <form method="POST"
            action="{{ route('tournaments.phases.store', $tournamentTemplate) }}"
            class="
                mt-7
            ">

            @csrf


            @include('tournaments.partials.phase-form')

        </form>

    </div>

</x-tournament-layout>

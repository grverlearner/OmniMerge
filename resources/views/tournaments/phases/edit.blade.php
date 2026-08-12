<x-tournament-layout>

    <x-slot name="header">
        Editar fase
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
                font-mono
                text-xs
                font-black
                text-amber-600
            ">
            {{ $phase->code }}
        </p>


        <h2
            class="
                mt-2
                text-3xl
                font-black
                text-slate-900
            ">
            Editar {{ $phase->name }}
        </h2>


        <form method="POST"
            action="{{ route('tournaments.phases.update', [$tournamentTemplate, $phase]) }}"
            class="
                mt-7
            ">

            @csrf

            @method('PUT')


            @include('tournaments.partials.phase-form')

        </form>

    </div>

</x-tournament-layout>

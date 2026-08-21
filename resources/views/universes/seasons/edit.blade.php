<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Editar temporada
    </x-slot>


    <div class="mx-auto max-w-4xl">

        <div class="mb-7">

            <a href="{{ route('universes.seasons.index', $universe) }}"
                class="
                    text-xs
                    font-black
                    text-slate-400
                    hover:text-violet-600
                ">
                ← Temporadas
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
                {{ $universe->name }} · Temporada {{ $season->number }}
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Editar {{ $season->name }}
            </h2>

        </div>


        <form method="POST" action="{{ route('universes.seasons.update', [$universe, $season]) }}">

            @csrf

            @method('PUT')


            @include('universes.seasons.partials.season-form', [
                'activeSeason' => $universe->activeSeason(),
            ])

        </form>

    </div>

</x-universe-layout>

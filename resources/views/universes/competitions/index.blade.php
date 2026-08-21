<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Competiciones
    </x-slot>


    <div
        class="
            flex
            flex-col
            justify-between
            gap-5
            sm:flex-row
            sm:items-start
        ">

        <div>

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-600
                ">
                {{ $universe->name }} · Competiciones
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Competiciones
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Torneos que se están jugando de verdad. Cada competición
                guarda su estado en la base de datos: puedes cerrar el
                navegador y continuar otro día donde lo dejaste.
            </p>

        </div>


        @can('update', $universe)
            <a href="{{ route('universes.tournaments.index', $universe) }}"
                class="
                    shrink-0
                    rounded-xl
                    bg-violet-600
                    px-5
                    py-3
                    text-sm
                    font-black
                    text-white
                    shadow-lg
                    shadow-violet-600/20
                    transition
                    hover:bg-violet-700
                ">
                + Nueva competición
            </a>
        @endcan

    </div>


    {{-- STATS --}}

    <div
        class="
            mt-7
            grid
            grid-cols-2
            gap-3
            lg:grid-cols-4
        ">

        @foreach ([['Total', $statistics['total']], ['En curso', $statistics['running']], ['Finalizadas', $statistics['completed']], ['Preparadas', $statistics['draft']]] as [$label, $value])
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                ">

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    {{ $label }}
                </p>


                <p
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    {{ $value }}
                </p>

            </article>
        @endforeach

    </div>


    {{-- FILTRO --}}

    <form method="GET"
        class="
            mt-6
            grid
            gap-3
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-4
            md:grid-cols-2
        ">

        <select name="status"
            class="
                rounded-xl
                border-slate-300
                bg-white
                text-sm
                text-slate-900
                focus:border-violet-400
                focus:ring-violet-400
            ">

            <option value="">
                Todo estado
            </option>

            @foreach (\App\Models\TournamentInstance::statuses() as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>
                    {{ $label }}
                </option>
            @endforeach

        </select>


        <button
            class="
                rounded-xl
                bg-slate-950
                px-4
                py-3
                text-sm
                font-black
                text-white
            ">
            Aplicar
        </button>

    </form>


    @if ($competitions->isEmpty())

        <div
            class="
                mt-8
                rounded-3xl
                border
                border-dashed
                border-slate-300
                bg-white
                p-12
                text-center
            ">

            <div class="text-5xl">
                ⚔
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                Todavía no se ha jugado nada aquí
            </h3>


            <p
                class="
                    mx-auto
                    mt-2
                    max-w-lg
                    text-sm
                    text-slate-500
                ">
                Una competición nace de un torneo configurado del Universo.
                Abre uno y lánzalo con tus competidores.
            </p>


            @can('update', $universe)
                <a href="{{ route('universes.tournaments.index', $universe) }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-violet-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Ver torneos del Universo
                </a>
            @endcan

        </div>
    @else

        <div
            class="
                mt-6
                grid
                gap-4
                lg:grid-cols-2
            ">

            @foreach ($competitions as $competition)
                @include('universes.competitions.partials.competition-card', [
                    'competition' => $competition,
                ])
            @endforeach

        </div>


        <div class="mt-8">
            {{ $competitions->links() }}
        </div>
    @endif

</x-universe-layout>

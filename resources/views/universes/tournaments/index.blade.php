<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Torneos
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
                {{ $universe->name }} · Torneos
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Torneos del Universo
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Plantillas de tu Biblioteca de Torneos adoptadas por este
                Universo. La plantilla no se copia: sigue siendo reutilizable
                y puede usarse también en otros Universos.
            </p>

        </div>


        @can('update', $universe)
            <a href="{{ route('universes.tournaments.create', $universe) }}"
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
                + Añadir torneo
            </a>
        @endcan

    </div>


    {{-- STATS --}}

    <div
        class="
            mt-7
            grid
            grid-cols-3
            gap-3
        ">

        @foreach ([['Total', $statistics['total']], ['Activos', $statistics['active']], ['Borradores', $statistics['draft']]] as [$label, $value])
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


    @if ($universeTournaments->isEmpty())

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
                🏆
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                Este Universo todavía no tiene torneos
            </h3>


            <p
                class="
                    mt-2
                    mx-auto
                    max-w-lg
                    text-sm
                    text-slate-500
                ">
                Adopta una de tus plantillas de torneo para que forme parte
                de este Universo.
            </p>


            @can('update', $universe)
                <a href="{{ route('universes.tournaments.create', $universe) }}"
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
                    + Añadir torneo
                </a>
            @endcan

        </div>
    @else

        <div class="mt-6 space-y-3">

            @foreach ($universeTournaments as $universeTournament)
                <article
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-5
                    ">

                    <div
                        class="
                            flex
                            flex-col
                            gap-4
                            lg:flex-row
                            lg:items-center
                            lg:justify-between
                        ">

                        <div class="flex items-center gap-4">

                            <div
                                class="
                                    flex
                                    h-14
                                    w-14
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-2xl
                                    bg-violet-100
                                    text-2xl
                                ">
                                🏆
                            </div>


                            <div class="min-w-0">

                                <div
                                    class="
                                        flex
                                        flex-wrap
                                        items-center
                                        gap-2
                                    ">

                                    <p
                                        class="
                                            text-lg
                                            font-black
                                            text-slate-900
                                        ">
                                        {{ $universeTournament->name }}
                                    </p>


                                    <span
                                        class="
                                            rounded-full
                                            px-2.5
                                            py-1
                                            text-[9px]
                                            font-black
                                            uppercase

                                            {{ match ($universeTournament->status) {
                                                'ACTIVE' => 'bg-emerald-100 text-emerald-700',
                                                'DRAFT' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-slate-200 text-slate-600',
                                            } }}
                                        ">
                                        {{ $universeTournament->status_label }}
                                    </span>

                                </div>


                                @if ($universeTournament->tournamentTemplate)
                                    <p
                                        class="
                                            mt-1
                                            text-xs
                                            text-slate-500
                                        ">
                                        Usa la plantilla
                                        <span class="font-bold text-slate-700">
                                            {{ $universeTournament->tournamentTemplate->name }}
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-400">
                                            {{ $universeTournament->tournamentTemplate->code }}
                                        </span>
                                    </p>
                                @else
                                    <p
                                        class="
                                            mt-1
                                            text-xs
                                            text-red-500
                                        ">
                                        La plantilla original ya no está disponible.
                                    </p>
                                @endif


                                @if ($universeTournament->description)
                                    <p
                                        class="
                                            mt-2
                                            line-clamp-2
                                            max-w-2xl
                                            text-sm
                                            text-slate-500
                                        ">
                                        {{ $universeTournament->description }}
                                    </p>
                                @endif

                            </div>

                        </div>


                        <div
                            class="
                                flex
                                flex-wrap
                                items-center
                                gap-2
                            ">

                            <a href="{{ route('universes.tournaments.show', [$universe, $universeTournament]) }}"
                                class="
                                    rounded-xl
                                    bg-violet-600
                                    px-3
                                    py-2
                                    text-[11px]
                                    font-black
                                    text-white
                                ">
                                ⚔ Competiciones
                            </a>


                            @if ($universeTournament->tournamentTemplate)
                                <a href="{{ route('tournaments.templates.show', $universeTournament->tournamentTemplate) }}"
                                    class="
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        px-3
                                        py-2
                                        text-[11px]
                                        font-black
                                        text-slate-600
                                    ">
                                    Ver plantilla →
                                </a>
                            @endif


                            @can('update', $universe)
                                <a href="{{ route('universes.tournaments.edit', [$universe, $universeTournament]) }}"
                                    class="
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        px-3
                                        py-2
                                        text-[11px]
                                        font-black
                                        text-slate-600
                                    ">
                                    Editar
                                </a>


                                <form method="POST"
                                    action="{{ route('universes.tournaments.destroy', [$universe, $universeTournament]) }}">

                                    @csrf

                                    @method('DELETE')


                                    <button type="submit"
                                        class="
                                            rounded-xl
                                            px-3
                                            py-2
                                            text-[11px]
                                            font-black
                                            text-red-500
                                        ">
                                        Quitar
                                    </button>

                                </form>
                            @endcan

                        </div>

                    </div>

                </article>
            @endforeach

        </div>


        <div class="mt-8">
            {{ $universeTournaments->links() }}
        </div>
    @endif


    {{-- NOTA DE ALCANCE --}}

    <section
        class="
            mt-8
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-6
        ">

        <p
            class="
                text-xs
                font-black
                uppercase
                tracking-[0.18em]
                text-violet-600
            ">
            Qué viene después
        </p>


        <p
            class="
                mt-3
                max-w-3xl
                text-sm
                leading-6
                text-slate-500
            ">
            Ahora mismo esto define <strong>qué torneos existen</strong> en el
            Universo. Todavía no se pueden jugar de verdad con estos
            competidores: eso llegará con el runtime persistente, que
            registrará cada competición dentro de la temporada en la que
            ocurrió. Mientras tanto puedes probar cualquier plantilla en el
            Competition Lab.
        </p>


        <a href="{{ route('tournaments.lab.index') }}"
            class="
                mt-4
                inline-flex
                rounded-xl
                border
                border-slate-200
                bg-white
                px-4
                py-2.5
                text-xs
                font-black
                text-slate-700
            ">
            ⚗ Abrir Competition Lab
        </a>

    </section>

</x-universe-layout>

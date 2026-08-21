<x-universe-layout>

    <x-slot name="header">
        Mis Universos
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
                Torneos · Universos
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Mis Universos
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                Agrupa tus torneos bajo un mismo contenedor.
                Cada Universo es el punto de partida antes de
                crear una plantilla de torneo.
            </p>

        </div>


        <a href="{{ route('universes.create') }}"
            class="
                rounded-xl
                bg-violet-500
                px-5
                py-3
                text-sm
                font-black
                text-white
                shadow-lg
                shadow-violet-500/20
                transition
                hover:bg-violet-600
            ">
            + Nuevo Universo
        </a>

    </div>


    {{-- STATS --}}

    <div
        class="
            mt-7
            grid
            grid-cols-3
            gap-3
        ">

        @foreach ([['Total', $stats['total']], ['Activos', $stats['active']], ['Borradores', $stats['draft']]] as [$label, $value])
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


    {{-- FILTERS --}}

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
            xl:grid-cols-4
        ">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar Universo..."
            class="
                rounded-xl
                border-slate-300
                text-sm
                text-slate-900
                placeholder:text-slate-400
                focus:border-violet-400
                focus:ring-violet-400
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

            <option value="DRAFT" @selected($status === 'DRAFT')>
                Borrador
            </option>

            <option value="ACTIVE" @selected($status === 'ACTIVE')>
                Activo
            </option>

            <option value="ARCHIVED" @selected($status === 'ARCHIVED')>
                Archivado
            </option>

        </select>


        <select name="sort"
            class="
                rounded-xl
                border-slate-300
                bg-white
                text-sm
                text-slate-900
                focus:border-violet-400
                focus:ring-violet-400
            ">

            @foreach ([
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'name_asc' => 'Nombre A → Z',
        'name_desc' => 'Nombre Z → A',
        'tournaments_desc' => 'Más torneos',
    ] as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>
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


    @if ($universes->isEmpty())

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

            <div class="
                    text-5xl
                ">
                🌌
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                No encontramos Universos
            </h3>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Crea un Universo nuevo o cambia los filtros.
            </p>

        </div>
    @else
        <div
            class="
                mt-6
                grid
                gap-5
                sm:grid-cols-2
                lg:grid-cols-3
            ">

            @foreach ($universes as $universe)
                @include('universes.partials.universe-card', [
                    'universe' => $universe,
                ])
            @endforeach

        </div>


        <div class="
                mt-8
            ">
            {{ $universes->links() }}
        </div>

    @endif

</x-universe-layout>

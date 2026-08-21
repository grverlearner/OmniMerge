<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Temporadas
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
                {{ $universe->name }} · Temporadas
            </p>


            <h2
                class="
                    mt-2
                    text-3xl
                    font-black
                    text-slate-900
                ">
                Temporadas
            </h2>


            <p
                class="
                    mt-2
                    max-w-2xl
                    text-slate-500
                ">
                El tiempo propio del Universo. Solo una temporada puede
                estar en curso a la vez.
            </p>

        </div>


        @can('update', $universe)
            <a href="{{ route('universes.seasons.create', $universe) }}"
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
                + Nueva temporada
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

        @foreach ([['Total', $statistics['total']], ['Planificadas', $statistics['planned']], ['Finalizadas', $statistics['completed']]] as [$label, $value])
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


    @if ($seasons->isEmpty())

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
                ◷
            </div>


            <h3
                class="
                    mt-4
                    text-xl
                    font-black
                    text-slate-900
                ">
                Este Universo todavía no tiene temporadas
            </h3>


            <p
                class="
                    mt-2
                    mx-auto
                    max-w-lg
                    text-sm
                    text-slate-500
                ">
                Las temporadas organizan el tiempo del Universo: a qué periodo
                pertenece cada torneo y sus resultados.
            </p>


            @can('update', $universe)
                <a href="{{ route('universes.seasons.create', $universe) }}"
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
                    + Crear la primera temporada
                </a>
            @endcan

        </div>
    @else

        <div class="mt-6 space-y-3">

            @foreach ($seasons as $season)
                @php
                    $isActive = $season->status === 'ACTIVE';
                @endphp

                <article
                    class="
                        {{ $isActive ? 'border-violet-300 bg-gradient-to-br from-white to-violet-50/60' : 'border-slate-200 bg-white' }}

                        rounded-3xl
                        border
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
                                    {{ $isActive ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-500' }}

                                    flex
                                    h-14
                                    w-14
                                    shrink-0
                                    flex-col
                                    items-center
                                    justify-center
                                    rounded-2xl
                                ">

                                <span class="text-[8px] font-black uppercase">
                                    Temp.
                                </span>

                                <span class="text-lg font-black leading-none">
                                    {{ $season->number }}
                                </span>

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
                                        {{ $season->name }}
                                    </p>


                                    <span
                                        class="
                                            rounded-full
                                            px-2.5
                                            py-1
                                            text-[9px]
                                            font-black
                                            uppercase

                                            {{ match ($season->status) {
                                                'ACTIVE' => 'bg-violet-600 text-white',
                                                'PLANNED' => 'bg-amber-100 text-amber-700',
                                                'COMPLETED' => 'bg-emerald-100 text-emerald-700',
                                                default => 'bg-slate-200 text-slate-600',
                                            } }}
                                        ">
                                        {{ $season->status_label }}
                                    </span>

                                </div>


                                <p
                                    class="
                                        mt-1
                                        text-xs
                                        text-slate-500
                                    ">
                                    {{ $season->period_label }}
                                </p>


                                @if ($season->description)
                                    <p
                                        class="
                                            mt-2
                                            line-clamp-2
                                            max-w-2xl
                                            text-sm
                                            text-slate-500
                                        ">
                                        {{ $season->description }}
                                    </p>
                                @endif

                            </div>

                        </div>


                        @can('update', $universe)
                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-2
                                ">

                                @if (! $isActive && $season->status !== 'ARCHIVED')
                                    <form method="POST"
                                        action="{{ route('universes.seasons.activate', [$universe, $season]) }}">

                                        @csrf

                                        @method('PATCH')


                                        <button type="submit"
                                            class="
                                                rounded-xl
                                                bg-violet-600
                                                px-3
                                                py-2
                                                text-[11px]
                                                font-black
                                                text-white
                                            ">
                                            Poner en curso
                                        </button>

                                    </form>
                                @endif


                                @if ($isActive)
                                    <form method="POST"
                                        action="{{ route('universes.seasons.complete', [$universe, $season]) }}">

                                        @csrf

                                        @method('PATCH')


                                        <button type="submit"
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
                                            Finalizar
                                        </button>

                                    </form>
                                @endif


                                <a href="{{ route('universes.seasons.edit', [$universe, $season]) }}"
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
                                    action="{{ route('universes.seasons.destroy', [$universe, $season]) }}">

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
                                        Eliminar
                                    </button>

                                </form>

                            </div>
                        @endcan

                    </div>

                </article>
            @endforeach

        </div>


        <div class="mt-8">
            {{ $seasons->links() }}
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
            Hoy una temporada organiza el tiempo del Universo. Cuando exista
            el runtime persistente de torneos, cada competición jugada quedará
            registrada dentro de la temporada en la que ocurrió, junto a sus
            resultados, recompensas y ranking.
        </p>

    </section>

</x-universe-layout>

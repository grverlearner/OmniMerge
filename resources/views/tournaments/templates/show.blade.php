<x-tournament-layout>

    <x-slot name="header">
        {{ $tournamentTemplate->name }}
    </x-slot>


    @include('tournaments.partials.template-navigation')


    {{-- HERO --}}

    <section
        class="
            overflow-hidden
            rounded-3xl
            border
            border-slate-200
            bg-white
        ">

        <div class="
                grid
                lg:grid-cols-[340px_1fr]
            ">

            <div
                class="
                    min-h-[280px]
                    bg-gradient-to-br
                    from-slate-950
                    via-slate-900
                    to-amber-950
                ">

                @if ($tournamentTemplate->image_url)
                    <img src="{{ $tournamentTemplate->image_url }}" alt="{{ $tournamentTemplate->name }}"
                        class="
                            h-full
                            min-h-[280px]
                            w-full
                            object-cover
                        ">
                @else
                    <div
                        class="
                            flex
                            h-full
                            min-h-[280px]
                            items-center
                            justify-center
                            text-7xl
                        ">
                        🏆
                    </div>
                @endif

            </div>


            <div class="
                    p-7
                    sm:p-8
                ">

                <div
                    class="
                        flex
                        flex-wrap
                        items-center
                        gap-2
                    ">

                    <span
                        class="
                            rounded-full
                            bg-slate-100
                            px-3
                            py-1
                            font-mono
                            text-[9px]
                            font-black
                            text-slate-500
                        ">
                        {{ $tournamentTemplate->code }}
                    </span>


                    <span
                        class="
                            rounded-full
                            px-3
                            py-1
                            text-[9px]
                            font-black
                            uppercase

                            {{ $tournamentTemplate->status === 'ACTIVE'
                                ? 'bg-emerald-100 text-emerald-700'
                                : ($tournamentTemplate->status === 'DRAFT'
                                    ? 'bg-amber-100 text-amber-700'
                                    : 'bg-slate-200 text-slate-600') }}
                        ">
                        {{ $tournamentTemplate->status_label }}
                    </span>


                    <span
                        class="
                            rounded-full
                            bg-violet-100
                            px-3
                            py-1
                            text-[9px]
                            font-black
                            uppercase
                            text-violet-700
                        ">
                        {{ $tournamentTemplate->visibility_label }}
                    </span>

                </div>


                <h2
                    class="
                        mt-5
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    {{ $tournamentTemplate->name }}
                </h2>


                <p
                    class="
                        mt-4
                        max-w-3xl
                        whitespace-pre-line
                        text-sm
                        leading-7
                        text-slate-500
                    ">
                    {{ $tournamentTemplate->description ?: 'Esta plantilla todavía no tiene descripción.' }}
                </p>


                <div
                    class="
                        mt-7
                        flex
                        flex-wrap
                        gap-3
                    ">

                    <a href="{{ route('tournaments.templates.edit', $tournamentTemplate) }}"
                        class="
                            rounded-xl
                            bg-amber-500
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Editar
                    </a>


                    <form method="POST" action="{{ route('tournaments.templates.duplicate', $tournamentTemplate) }}">

                        @csrf


                        <button type="submit"
                            class="
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
                            ⧉ Duplicar
                        </button>

                    </form>


                    @if ($tournamentTemplate->status !== 'ARCHIVED')
                        <form method="POST"
                            action="{{ route('tournaments.templates.archive', $tournamentTemplate) }}">

                            @csrf

                            @method('PATCH')


                            <button type="submit"
                                class="
                                    rounded-xl
                                    border
                                    border-slate-200
                                    bg-white
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-slate-500
                                ">
                                Archivar
                            </button>

                        </form>
                    @endif

                </div>

            </div>

        </div>

    </section>


    {{-- INFO --}}

    <section
        class="
            mt-6
            grid
            gap-3
            sm:grid-cols-2
            lg:grid-cols-4
        ">

        @foreach ([
        [
            'label' => 'Participantes',
            'value' => $tournamentTemplate->participant_range_label,
        ],
        [
            'label' => 'Tournament Graph',
            'value' => $tournamentTemplate->graph_nodes_count . ' ' . ($tournamentTemplate->graph_nodes_count === 1 ? 'Node' : 'Nodes') . ' · ' . $tournamentTemplate->graph_connections_count . ' conexiones',
        ],
        [
            'label' => 'BYE',
            'value' => $tournamentTemplate->allow_byes ? 'Permitido' : 'No permitido',
        ],
        [
            'label' => 'Clonación',
            'value' => $tournamentTemplate->allow_cloning ? 'Permitida' : 'Desactivada',
        ],
    ] as $item)
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    {{ $item['label'] }}
                </p>


                <p
                    class="
                        mt-2
                        text-sm
                        font-black
                        text-slate-800
                    ">
                    {{ $item['value'] }}
                </p>

            </article>
        @endforeach

    </section>

    {{-- ========================================================= --}}
    {{-- TOURNAMENT GRAPH --}}
    {{-- ========================================================= --}}

    <section
        class="mt-8 overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-br from-white to-amber-50/60">

        <div class="grid lg:grid-cols-[1fr_360px]">

            <div class="p-7">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
                    Tournament Graph Foundation
                </p>

                <h3 class="mt-2 text-2xl font-black text-slate-900">
                    Construye el camino competitivo
                </h3>

                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">
                    Coloca tus PhaseTemplates como Nodes, conecta puertas de salida
                    con entradas de otras Fases, crea bifurcaciones, convergencias,
                    repechajes y múltiples destinos finales.
                </p>


                <div class="mt-6 flex flex-wrap items-center gap-2 text-xs font-black">

                    <span class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700">
                        Start
                    </span>

                    <span class="text-amber-500">
                        →
                    </span>

                    <span class="rounded-xl bg-white px-3 py-2 text-slate-600 shadow-sm">
                        EntryPort
                    </span>

                    <span class="text-amber-500">
                        →
                    </span>

                    <span class="rounded-xl bg-white px-3 py-2 text-slate-600 shadow-sm">
                        PhaseNode
                    </span>

                    <span class="text-amber-500">
                        →
                    </span>

                    <span class="rounded-xl bg-white px-3 py-2 text-slate-600 shadow-sm">
                        PhaseExit
                    </span>

                    <span class="text-amber-500">
                        →
                    </span>

                    <span class="rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-violet-700">
                        Connection
                    </span>

                    <span class="text-amber-500">
                        →
                    </span>

                    <span class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-700">
                        Terminal
                    </span>

                </div>

            </div>


            <div
                class="flex flex-col justify-center border-t border-amber-100 bg-white/70 p-6 lg:border-l lg:border-t-0">

                <div class="grid grid-cols-2 gap-3">

                    <div class="rounded-2xl bg-slate-950 p-4 text-white">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            Nodes
                        </p>

                        <p class="mt-2 text-2xl font-black">
                            {{ $tournamentTemplate->graph_nodes_count }}
                        </p>

                    </div>


                    <div class="rounded-2xl bg-violet-600 p-4 text-white">

                        <p class="text-[9px] font-black uppercase tracking-wider text-violet-200">
                            Connections
                        </p>

                        <p class="mt-2 text-2xl font-black">
                            {{ $tournamentTemplate->graph_connections_count }}
                        </p>

                    </div>

                </div>


                <a href="{{ route('tournaments.graph.show', $tournamentTemplate) }}"
                    class="mt-4 rounded-xl bg-amber-500 px-5 py-3.5 text-center text-sm font-black text-white shadow-lg shadow-amber-500/20">

                    ◇ Abrir Tournament Graph

                </a>

            </div>

        </div>

    </section>

    {{-- DANGER ZONE --}}

    <section x-data="{
        deleting: false
    }"
        class="
            mt-10
            rounded-3xl
            border
            border-red-200
            bg-red-50
            p-6
        ">

        <div
            class="
                flex
                flex-col
                justify-between
                gap-4
                sm:flex-row
                sm:items-center
            ">

            <div>

                <p
                    class="
                        text-sm
                        font-black
                        text-red-800
                    ">
                    Eliminar plantilla
                </p>


                <p
                    class="
                        mt-1
                        text-xs
                        text-red-600
                    ">
                    Se aplicará Soft Delete y dejará de aparecer
                    en el módulo.
                </p>

            </div>


            <button type="button" @click="
                    deleting = true
                "
                class="
                    rounded-xl
                    bg-red-600
                    px-4
                    py-2.5
                    text-xs
                    font-black
                    text-white
                ">
                Eliminar
            </button>

        </div>


        <div x-show="
                deleting
            " x-transition
            class="
                mt-5
                rounded-2xl
                border
                border-red-200
                bg-white
                p-5
            "
            style="
                display: none;
            ">

            <p class="
                    font-black
                    text-slate-900
                ">
                ¿Eliminar “{{ $tournamentTemplate->name }}”?
            </p>


            <p
                class="
                    mt-2
                    text-sm
                    text-slate-500
                ">
                Esta acción eliminará lógicamente la plantilla.
            </p>


            <div class="
                    mt-4
                    flex
                    gap-3
                ">

                <button type="button" @click="
                        deleting = false
                    "
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-slate-600
                    ">
                    Cancelar
                </button>


                <form method="POST" action="{{ route('tournaments.templates.destroy', $tournamentTemplate) }}">

                    @csrf

                    @method('DELETE')


                    <button type="submit"
                        class="
                            rounded-xl
                            bg-red-600
                            px-4
                            py-2.5
                            text-xs
                            font-black
                            text-white
                        ">
                        Sí, eliminar
                    </button>

                </form>

            </div>

        </div>

    </section>

</x-tournament-layout>

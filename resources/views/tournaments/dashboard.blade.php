<x-tournament-layout>

    <x-slot name="header">
        Dashboard de Torneos
    </x-slot>


    {{-- ========================================================= --}}
    {{-- HERO --}}
    {{-- ========================================================= --}}

    <section
        class="
            relative
            overflow-hidden
            rounded-[32px]
            bg-gradient-to-br
            from-slate-950
            via-amber-950
            to-orange-950
            p-7
            text-white
            shadow-2xl
            shadow-amber-950/20
            sm:p-9
        ">

        <div
            class="
                pointer-events-none
                absolute
                -right-24
                -top-24
                h-80
                w-80
                rounded-full
                bg-amber-400/15
                blur-3xl
            ">
        </div>


        <div
            class="
                pointer-events-none
                absolute
                -bottom-24
                left-1/3
                h-64
                w-64
                rounded-full
                bg-orange-500/10
                blur-3xl
            ">
        </div>


        <div
            class="
                relative
                flex
                flex-col
                justify-between
                gap-8
                lg:flex-row
                lg:items-end
            ">

            <div class="
                    max-w-3xl
                ">

                <div
                    class="
                        inline-flex
                        items-center
                        gap-2
                        rounded-full
                        border
                        border-amber-300/20
                        bg-amber-400/10
                        px-4
                        py-2
                        text-[10px]
                        font-black
                        uppercase
                        tracking-[0.18em]
                        text-amber-300
                    ">
                    🏆 Competition Designer
                </div>


                <h2
                    class="
                        mt-5
                        text-3xl
                        font-black
                        tracking-tight
                        sm:text-4xl
                    ">
                    Diseña cómo funciona
                    una competición.
                </h2>


                <p
                    class="
                        mt-4
                        max-w-2xl
                        text-sm
                        leading-7
                        text-slate-300
                    ">
                    Crea estructuras reutilizables con fases,
                    participantes, formatos y reglas. Más adelante
                    estas plantillas podrán utilizarse dentro de
                    Universos y compartirse mediante Comunidad.
                </p>

            </div>


            <div
                class="
                    flex
                    flex-wrap
                    gap-3
                ">

                <a href="{{ route('tournaments.templates.index') }}"
                    class="
                        rounded-xl
                        border
                        border-white/15
                        bg-white/10
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                        backdrop-blur
                        transition
                        hover:bg-white/15
                    ">
                    Mis plantillas
                </a>


                <a href="{{ route('tournaments.templates.create') }}"
                    class="
                        rounded-xl
                        bg-amber-400
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-slate-950
                        shadow-lg
                        shadow-amber-500/20
                        transition
                        hover:bg-amber-300
                    ">
                    + Nueva plantilla
                </a>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- STATS --}}
    {{-- ========================================================= --}}

    <section
        class="
            mt-6
            grid
            grid-cols-2
            gap-3
            lg:grid-cols-5
        ">

        @foreach ([
        [
            'label' => 'Plantillas',
            'value' => $statistics['total'],
            'icon' => '🏆',
        ],
        [
            'label' => 'Activas',
            'value' => $statistics['active'],
            'icon' => '●',
        ],
        [
            'label' => 'Borradores',
            'value' => $statistics['draft'],
            'icon' => '✎',
        ],
        [
            'label' => 'Públicas',
            'value' => $statistics['public'],
            'icon' => '◎',
        ],
        [
            'label' => 'Fases',
            'value' => $statistics['phases'],
            'icon' => '⌘',
        ],
    ] as $stat)
            <article
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                ">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                    ">

                    <div>

                        <p
                            class="
                                text-[9px]
                                font-black
                                uppercase
                                tracking-wider
                                text-slate-400
                            ">
                            {{ $stat['label'] }}
                        </p>


                        <p
                            class="
                                mt-2
                                text-3xl
                                font-black
                                text-slate-900
                            ">
                            {{ number_format($stat['value']) }}
                        </p>

                    </div>


                    <div
                        class="
                            flex
                            h-11
                            w-11
                            items-center
                            justify-center
                            rounded-xl
                            bg-amber-50
                            text-lg
                            text-amber-700
                        ">
                        {{ $stat['icon'] }}
                    </div>

                </div>

            </article>
        @endforeach

    </section>


    {{-- ========================================================= --}}
    {{-- PLANTILLAS RECIENTES --}}
    {{-- ========================================================= --}}

    <section class="
            mt-8
        ">

        <div
            class="
                flex
                items-end
                justify-between
                gap-4
            ">

            <div>

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-amber-600
                    ">
                    Tu trabajo
                </p>


                <h3
                    class="
                        mt-2
                        text-2xl
                        font-black
                        text-slate-900
                    ">
                    Plantillas recientes
                </h3>

            </div>


            <a href="{{ route('tournaments.templates.index') }}"
                class="
                    text-sm
                    font-black
                    text-amber-600
                ">
                Ver todas →
            </a>

        </div>


        @if ($recentTemplates->isEmpty())

            <div
                class="
                    mt-5
                    rounded-3xl
                    border
                    border-dashed
                    border-amber-300
                    bg-amber-50/50
                    p-10
                    text-center
                ">

                <div class="
                        text-5xl
                    ">
                    🏆
                </div>


                <h4
                    class="
                        mt-4
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    Todavía no tienes plantillas
                </h4>


                <p
                    class="
                        mx-auto
                        mt-2
                        max-w-lg
                        text-sm
                        leading-6
                        text-slate-500
                    ">
                    Crea la primera estructura competitiva
                    de OmniMerge. Empezaremos con eliminación
                    directa y progresivamente añadiremos más formatos.
                </p>


                <a href="{{ route('tournaments.templates.create') }}"
                    class="
                        mt-6
                        inline-flex
                        rounded-xl
                        bg-amber-500
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear primera plantilla
                </a>

            </div>
        @else
            <div
                class="
                    mt-5
                    grid
                    gap-5
                    sm:grid-cols-2
                    lg:grid-cols-3
                ">

                @foreach ($recentTemplates as $template)
                    @include('tournaments.partials.template-card', [
                        'template' => $template,
                    ])
                @endforeach

            </div>

        @endif

    </section>


    {{-- ========================================================= --}}
    {{-- SIGUIENTES CAPACIDADES --}}
    {{-- ========================================================= --}}

    <section class="
            mt-10
            grid
            gap-4
            lg:grid-cols-3
        ">

        @foreach ([
        [
            'icon' => '⌘',
            'title' => 'Constructor de fases',
            'text' => 'La base ya permitirá definir las etapas que componen una plantilla.',
            'state' => 'Disponible',
        ],
        [
            'icon' => '⚗',
            'title' => 'Competition Lab',
            'text' => 'Espacio temporal para probar plantillas sin generar historial oficial.',
            'state' => 'Preparado',
        ],
        [
            'icon' => '🌌',
            'title' => 'Integración con Universos',
            'text' => 'Después del motor competitivo, los Universos consumirán estas plantillas.',
            'state' => 'Futuro',
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

                <div
                    class="
                        flex
                        h-11
                        w-11
                        items-center
                        justify-center
                        rounded-xl
                        bg-slate-950
                        text-lg
                        text-amber-300
                    ">
                    {{ $item['icon'] }}
                </div>


                <h4
                    class="
                        mt-4
                        font-black
                        text-slate-900
                    ">
                    {{ $item['title'] }}
                </h4>


                <p
                    class="
                        mt-2
                        text-sm
                        leading-6
                        text-slate-500
                    ">
                    {{ $item['text'] }}
                </p>


                <p
                    class="
                        mt-4
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-amber-600
                    ">
                    {{ $item['state'] }}
                </p>

            </article>
        @endforeach

    </section>

</x-tournament-layout>

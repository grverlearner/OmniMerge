@extends('layouts.hub')

@section('title', 'Centro OmniMerge')


@section('content')

    {{-- ========================================================= --}}
    {{-- HERO --}}
    {{-- ========================================================= --}}

    <section
        class="
            mx-auto
            max-w-7xl
            px-5
            pb-10
            pt-12
            sm:px-6
            lg:px-8
            lg:pt-16
        ">

        <div
            class="
                relative
                overflow-hidden
                rounded-[32px]
                border
                border-white/10
                bg-gradient-to-br
                from-indigo-600
                via-violet-600
                to-fuchsia-600
                p-7
                shadow-2xl
                shadow-indigo-950/40
                sm:p-10
            ">

            <div
                class="
                    absolute
                    -right-20
                    -top-24
                    h-72
                    w-72
                    rounded-full
                    bg-white/10
                    blur-3xl
                ">
            </div>


            <div
                class="
                    absolute
                    -bottom-24
                    left-1/3
                    h-64
                    w-64
                    rounded-full
                    bg-fuchsia-300/10
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

                <div class="max-w-3xl">

                    <div
                        class="
                            inline-flex
                            items-center
                            gap-2
                            rounded-full
                            border
                            border-white/20
                            bg-white/10
                            px-4
                            py-2
                            text-xs
                            font-black
                            uppercase
                            tracking-[0.16em]
                            text-indigo-100
                            backdrop-blur
                        ">
                        <span
                            class="
                                h-2
                                w-2
                                rounded-full
                                bg-emerald-300
                            "></span>

                        Centro OmniMerge
                    </div>


                    <h1
                        class="
                            mt-6
                            text-3xl
                            font-black
                            tracking-tight
                            text-white
                            sm:text-4xl
                            lg:text-5xl
                        ">
                        Hola,
                        {{ auth()->user()->name }}.
                    </h1>


                    <p
                        class="
                            mt-4
                            max-w-2xl
                            text-sm
                            leading-7
                            text-indigo-100
                            sm:text-base
                        ">
                        Este es el punto central de tu cuenta.
                        Desde aquí puedes entrar a tu biblioteca,
                        explorar la comunidad y, más adelante,
                        administrar universos, torneos y mucho más.
                    </p>

                </div>


                <div
                    class="
                        flex
                        flex-wrap
                        gap-3
                    ">

                    <a href="{{ route('home') }}"
                        class="
                            rounded-xl
                            border
                            border-white/20
                            bg-white/10
                            px-5
                            py-3
                            text-sm
                            font-bold
                            text-white
                            backdrop-blur
                            transition
                            hover:bg-white/20
                        ">
                        Ver página pública
                    </a>


                    <a href="{{ route('profile.edit') }}"
                        class="
                            rounded-xl
                            bg-white
                            px-5
                            py-3
                            text-sm
                            font-black
                            text-indigo-700
                            shadow-lg
                        ">
                        Mi cuenta
                    </a>

                </div>
            </div>
        </div>
    </section>


    {{-- ========================================================= --}}
    {{-- ESTADÍSTICAS --}}
    {{-- ========================================================= --}}

    <section
        class="
            mx-auto
            max-w-7xl
            px-5
            py-6
            sm:px-6
            lg:px-8
        ">

        <div
            class="
                grid
                gap-4
                sm:grid-cols-2
                xl:grid-cols-4
            ">

            @foreach ([
            [
                'label' => 'Entidades',
                'value' => $statistics['entities'],
                'icon' => '✦',
                'description' => 'Elementos creados',
            ],
            [
                'label' => 'Atributos',
                'value' => $statistics['attributes'],
                'icon' => '☷',
                'description' => 'Características definidas',
            ],
            [
                'label' => 'Colecciones',
                'value' => $statistics['collections'],
                'icon' => '▤',
                'description' => 'Grupos de entidades',
            ],
            [
                'label' => 'Contenido público',
                'value' => $statistics['public_content'],
                'icon' => '◎',
                'description' => 'Visible en comunidad',
            ],
        ] as $stat)
                <article
                    class="
                        rounded-2xl
                        border
                        border-white/10
                        bg-white/[0.03]
                        p-5
                        transition
                        hover:border-indigo-400/20
                        hover:bg-white/[0.05]
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
                                    text-xs
                                    font-bold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                {{ $stat['label'] }}
                            </p>

                            <p
                                class="
                                    mt-2
                                    text-3xl
                                    font-black
                                    text-white
                                ">
                                {{ number_format($stat['value']) }}
                            </p>

                        </div>


                        <div
                            class="
                                flex
                                h-12
                                w-12
                                items-center
                                justify-center
                                rounded-2xl
                                bg-indigo-500/10
                                text-xl
                                text-indigo-300
                            ">
                            {{ $stat['icon'] }}
                        </div>

                    </div>


                    <p
                        class="
                            mt-3
                            text-xs
                            text-slate-500
                        ">
                        {{ $stat['description'] }}
                    </p>

                </article>
            @endforeach

        </div>
    </section>


    {{-- ========================================================= --}}
    {{-- MÓDULOS --}}
    {{-- ========================================================= --}}

    <section
        class="
            mx-auto
            max-w-7xl
            px-5
            py-12
            sm:px-6
            lg:px-8
        ">

        <div
            class="
                flex
                flex-col
                justify-between
                gap-4
                sm:flex-row
                sm:items-end
            ">

            <div>

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.18em]
                        text-indigo-400
                    ">
                    Áreas de trabajo
                </p>

                <h2
                    class="
                        mt-3
                        text-2xl
                        font-black
                        text-white
                        sm:text-3xl
                    ">
                    ¿A dónde quieres ir?
                </h2>

                <p
                    class="
                        mt-3
                        max-w-2xl
                        text-sm
                        leading-6
                        text-slate-500
                    ">
                    Cada área de OmniMerge posee una función distinta.
                    Tu Biblioteca administra las piezas que después podrán
                    utilizarse en otros módulos.
                </p>

            </div>

        </div>


        <div
            class="
                mt-8
                grid
                gap-5
                md:grid-cols-2
                xl:grid-cols-3
            ">

            {{-- ================================================= --}}
            {{-- BIBLIOTECA --}}
            {{-- ================================================= --}}

            <a href="{{ route('dashboard') }}"
                class="
                    group
                    relative
                    overflow-hidden
                    rounded-3xl
                    border
                    border-indigo-400/30
                    bg-gradient-to-br
                    from-indigo-500/15
                    to-violet-500/5
                    p-7
                    transition
                    hover:-translate-y-1
                    hover:border-indigo-400/60
                    hover:shadow-2xl
                    hover:shadow-indigo-950/30
                ">

                <div
                    class="
                        absolute
                        -right-12
                        -top-12
                        h-32
                        w-32
                        rounded-full
                        bg-indigo-500/20
                        blur-3xl
                    ">
                </div>


                <div class="relative">

                    <div
                        class="
                            flex
                            items-start
                            justify-between
                        ">

                        <div
                            class="
                                flex
                                h-14
                                w-14
                                items-center
                                justify-center
                                rounded-2xl
                                bg-indigo-500
                                text-2xl
                                shadow-lg
                                shadow-indigo-500/20
                            ">
                            📚
                        </div>


                        <span
                            class="
                                rounded-full
                                bg-emerald-500/10
                                px-3
                                py-1
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                text-emerald-400
                            ">
                            Disponible
                        </span>

                    </div>


                    <h3
                        class="
                            mt-6
                            text-xl
                            font-black
                            text-white
                        ">
                        Biblioteca
                    </h3>


                    <p
                        class="
                            mt-3
                            min-h-[72px]
                            text-sm
                            leading-6
                            text-slate-400
                        ">
                        Crea y administra tipos de entidad,
                        entidades, atributos, opciones,
                        grupos y colecciones.
                    </p>


                    <div
                        class="
                            mt-6
                            flex
                            items-center
                            justify-between
                            border-t
                            border-white/10
                            pt-5
                        ">

                        <span
                            class="
                                text-xs
                                font-semibold
                                text-slate-500
                            ">
                            {{ $statistics['entities'] }}
                            entidades
                        </span>

                        <span
                            class="
                                text-sm
                                font-black
                                text-indigo-300
                                transition
                                group-hover:translate-x-1
                            ">
                            Entrar →
                        </span>

                    </div>
                </div>
            </a>


            {{-- ================================================= --}}
            {{-- COMUNIDAD --}}
            {{-- ================================================= --}}

            <a href="{{ route('community.index') }}"
                class="
                    group
                    rounded-3xl
                    border
                    border-white/10
                    bg-white/[0.03]
                    p-7
                    transition
                    hover:-translate-y-1
                    hover:border-fuchsia-400/30
                    hover:bg-fuchsia-500/[0.04]
                ">

                <div
                    class="
                        flex
                        items-start
                        justify-between
                    ">

                    <div
                        class="
                            flex
                            h-14
                            w-14
                            items-center
                            justify-center
                            rounded-2xl
                            bg-fuchsia-500/10
                            text-2xl
                        ">
                        🌐
                    </div>

                    <span
                        class="
                            rounded-full
                            bg-emerald-500/10
                            px-3
                            py-1
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-emerald-400
                        ">
                        Disponible
                    </span>

                </div>


                <h3
                    class="
                        mt-6
                        text-xl
                        font-black
                        text-white
                    ">
                    Comunidad
                </h3>


                <p
                    class="
                        mt-3
                        min-h-[72px]
                        text-sm
                        leading-6
                        text-slate-400
                    ">
                    Explora entidades, atributos y colecciones
                    públicas creadas por otros usuarios.
                </p>


                <div
                    class="
                        mt-6
                        flex
                        items-center
                        justify-between
                        border-t
                        border-white/10
                        pt-5
                    ">

                    <span
                        class="
                            text-xs
                            font-semibold
                            text-slate-500
                        ">
                        Explorar contenido
                    </span>

                    <span
                        class="
                            text-sm
                            font-black
                            text-fuchsia-300
                            transition
                            group-hover:translate-x-1
                        ">
                        Explorar →
                    </span>

                </div>

            </a>


            {{-- ================================================= --}}
            {{-- PERFIL --}}
            {{-- ================================================= --}}

            <a href="{{ route('profile.edit') }}"
                class="
                    group
                    rounded-3xl
                    border
                    border-white/10
                    bg-white/[0.03]
                    p-7
                    transition
                    hover:-translate-y-1
                    hover:border-cyan-400/30
                    hover:bg-cyan-500/[0.04]
                ">

                <div
                    class="
                        flex
                        items-start
                        justify-between
                    ">

                    <x-user-avatar :user="auth()->user()" size="lg" square />

                    <span
                        class="
                            rounded-full
                            bg-emerald-500/10
                            px-3
                            py-1
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-emerald-400
                        ">
                        Disponible
                    </span>

                </div>


                <h3
                    class="
                        mt-6
                        text-xl
                        font-black
                        text-white
                    ">
                    Perfil y cuenta
                </h3>


                <p
                    class="
                        mt-3
                        min-h-[72px]
                        text-sm
                        leading-6
                        text-slate-400
                    ">
                    Administra tu información personal,
                    contraseña y configuración básica de cuenta.
                </p>


                <div
                    class="
                        mt-6
                        flex
                        items-center
                        justify-between
                        border-t
                        border-white/10
                        pt-5
                    ">

                    <span
                        class="
                            truncate
                            text-xs
                            font-semibold
                            text-slate-500
                        ">
                        {{ '@' . auth()->user()->username }}
                    </span>

                    <span
                        class="
                            text-sm
                            font-black
                            text-cyan-300
                            transition
                            group-hover:translate-x-1
                        ">
                        Abrir →
                    </span>

                </div>

            </a>


            {{-- ================================================= --}}
            {{-- UNIVERSOS --}}
            {{-- ================================================= --}}

            <a href="{{ route('universes.dashboard') }}"
                class="
                    group
                    rounded-3xl
                    border
                    border-white/10
                    bg-white/[0.03]
                    p-7
                    transition
                    hover:-translate-y-1
                    hover:border-violet-400/30
                    hover:bg-violet-500/[0.04]
                ">

                <div
                    class="
                        flex
                        items-start
                        justify-between
                    ">

                    <div
                        class="
                            flex
                            h-14
                            w-14
                            items-center
                            justify-center
                            rounded-2xl
                            bg-violet-500/10
                            text-2xl
                        ">
                        🌌
                    </div>


                    <span
                        class="
                            rounded-full
                            bg-emerald-500/10
                            px-3
                            py-1
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-emerald-400
                        ">
                        Disponible
                    </span>

                </div>


                <h3
                    class="
                        mt-6
                        text-xl
                        font-black
                        text-white
                    ">
                    Universos
                </h3>


                <p
                    class="
                        mt-3
                        min-h-[72px]
                        text-sm
                        leading-6
                        text-slate-400
                    ">
                    Agrupa tus plantillas de torneo bajo un mismo
                    contenedor organizativo.
                </p>


                <div
                    class="
                        mt-6
                        flex
                        items-center
                        justify-between
                        border-t
                        border-white/10
                        pt-5
                    ">

                    <span
                        class="
                            text-xs
                            font-semibold
                            text-slate-500
                        ">
                        {{ $statistics['universes'] }}
                        universos
                    </span>


                    <span
                        class="
                            text-sm
                            font-black
                            text-violet-300
                            transition
                            group-hover:translate-x-1
                        ">
                        Entrar →
                    </span>

                </div>

            </a>

            {{-- ================================================= --}}
            {{-- TORNEOS --}}
            {{-- ================================================= --}}

            <a href="{{ route('tournaments.dashboard') }}"
                class="
                    group
                    rounded-3xl
                    border
                    border-white/10
                    bg-white/[0.03]
                    p-7
                    transition
                    hover:-translate-y-1
                    hover:border-amber-400/30
                    hover:bg-amber-500/[0.04]
                ">

                <div
                    class="
                        flex
                        items-start
                        justify-between
                    ">

                    <div
                        class="
                            flex
                            h-14
                            w-14
                            items-center
                            justify-center
                            rounded-2xl
                            bg-amber-500/10
                            text-2xl
                        ">
                        🏆
                    </div>


                    <span
                        class="
                            rounded-full
                            bg-emerald-500/10
                            px-3
                            py-1
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-emerald-400
                        ">
                        Disponible
                    </span>

                </div>


                <h3
                    class="
                        mt-6
                        text-xl
                        font-black
                        text-white
                    ">
                    Torneos
                </h3>


                <p
                    class="
                        mt-3
                        min-h-[72px]
                        text-sm
                        leading-6
                        text-slate-400
                    ">
                    Diseña sistemas de competición reutilizables
                    mediante plantillas, fases, formatos y reglas.
                </p>


                <div
                    class="
                        mt-6
                        flex
                        items-center
                        justify-between
                        border-t
                        border-white/10
                        pt-5
                    ">

                    <span
                        class="
                            text-xs
                            font-semibold
                            text-slate-500
                        ">
                        {{ $statistics['tournaments'] }}
                        plantillas
                    </span>


                    <span
                        class="
                            text-sm
                            font-black
                            text-amber-300
                            transition
                            group-hover:translate-x-1
                        ">
                        Entrar →
                    </span>

                </div>

            </a>

            {{-- ================================================= --}}
            {{-- RANKINGS --}}
            {{-- ================================================= --}}

            <article
                class="
                    rounded-3xl
                    border
                    border-dashed
                    border-white/10
                    bg-white/[0.02]
                    p-7
                ">

                <div
                    class="
                        flex
                        items-start
                        justify-between
                    ">

                    <div
                        class="
                            flex
                            h-14
                            w-14
                            items-center
                            justify-center
                            rounded-2xl
                            bg-emerald-500/10
                            text-2xl
                        ">
                        📊
                    </div>


                    <span
                        class="
                            rounded-full
                            bg-amber-500/10
                            px-3
                            py-1
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-amber-400
                        ">
                        Próximamente
                    </span>

                </div>


                <h3
                    class="
                        mt-6
                        text-xl
                        font-black
                        text-white
                    ">
                    Rankings y analítica
                </h3>


                <p
                    class="
                        mt-3
                        min-h-[72px]
                        text-sm
                        leading-6
                        text-slate-500
                    ">
                    Analiza rendimiento, resultados,
                    tendencias y evolución de tus
                    futuros universos y torneos.
                </p>


                <div
                    class="
                        mt-6
                        border-t
                        border-white/10
                        pt-5
                    ">
                    <span
                        class="
                            text-xs
                            font-semibold
                            text-slate-600
                        ">
                        Módulo en desarrollo
                    </span>
                </div>

            </article>

        </div>
    </section>


    {{-- ========================================================= --}}
    {{-- PARTE INFERIOR --}}
    {{-- ========================================================= --}}

    <section
        class="
            mx-auto
            grid
            max-w-7xl
            gap-6
            px-5
            pb-20
            pt-4
            sm:px-6
            lg:grid-cols-[1fr_360px]
            lg:px-8
        ">

        {{-- ACTIVIDAD --}}
        <div
            class="
                rounded-3xl
                border
                border-white/10
                bg-white/[0.03]
                p-6
                sm:p-7
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
                            text-xs
                            font-black
                            uppercase
                            tracking-wider
                            text-indigo-400
                        ">
                        Tu actividad
                    </p>

                    <h2
                        class="
                            mt-2
                            text-xl
                            font-black
                            text-white
                        ">
                        Creaciones recientes
                    </h2>

                </div>


                <a href="{{ route('dashboard') }}"
                    class="
                        text-xs
                        font-bold
                        text-indigo-400
                        hover:text-indigo-300
                    ">
                    Ir a Biblioteca →
                </a>

            </div>


            <div class="mt-6 space-y-3">

                @forelse ($recentActivity as $activity)
                    <a href="{{ $activity['url'] }}"
                        class="
                            flex
                            items-center
                            gap-4
                            rounded-2xl
                            border
                            border-white/5
                            bg-slate-950/40
                            p-4
                            transition
                            hover:border-indigo-400/20
                            hover:bg-white/[0.04]
                        ">

                        <div
                            class="
                                flex
                                h-11
                                w-11
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-indigo-500/10
                                text-lg
                                text-indigo-300
                            ">
                            {{ $activity['icon'] }}
                        </div>


                        <div
                            class="
                                min-w-0
                                flex-1
                            ">

                            <div
                                class="
                                    flex
                                    items-center
                                    gap-2
                                ">
                                <p
                                    class="
                                        truncate
                                        text-sm
                                        font-black
                                        text-white
                                    ">
                                    {{ $activity['name'] }}
                                </p>


                                <span
                                    class="
                                        rounded-full
                                        bg-white/5
                                        px-2
                                        py-0.5
                                        text-[9px]
                                        font-bold
                                        uppercase
                                        tracking-wider
                                        text-slate-500
                                    ">
                                    {{ $activity['type'] }}
                                </span>
                            </div>


                            <p
                                class="
                                    mt-1
                                    truncate
                                    text-xs
                                    text-slate-500
                                ">
                                {{ $activity['description'] }}
                            </p>

                        </div>


                        <div
                            class="
                                shrink-0
                                text-right
                            ">

                            <p
                                class="
                                    text-xs
                                    text-slate-600
                                ">
                                {{ $activity['created_at']?->diffForHumans() }}
                            </p>

                        </div>

                    </a>

                @empty

                    <div
                        class="
                            rounded-2xl
                            border
                            border-dashed
                            border-white/10
                            py-12
                            text-center
                        ">

                        <div class="text-3xl">
                            ✦
                        </div>

                        <p
                            class="
                                mt-4
                                font-bold
                                text-slate-300
                            ">
                            Todavía no hay actividad
                        </p>

                        <p
                            class="
                                mt-2
                                text-sm
                                text-slate-600
                            ">
                            Crea tu primera entidad para comenzar.
                        </p>


                        <a href="{{ route('entities.create') }}"
                            class="
                                mt-5
                                inline-flex
                                rounded-xl
                                bg-indigo-600
                                px-5
                                py-3
                                text-sm
                                font-black
                                text-white
                            ">
                            Crear entidad
                        </a>

                    </div>
                @endforelse

            </div>

        </div>


        {{-- ACCESOS RÁPIDOS --}}
        <aside
            class="
                rounded-3xl
                border
                border-white/10
                bg-white/[0.03]
                p-6
                sm:p-7
            ">

            <p
                class="
                    text-xs
                    font-black
                    uppercase
                    tracking-wider
                    text-indigo-400
                ">
                Acceso rápido
            </p>

            <h2
                class="
                    mt-2
                    text-xl
                    font-black
                    text-white
                ">
                Crear y explorar
            </h2>


            <div class="mt-6 space-y-3">

                <a href="{{ route('entities.create') }}"
                    class="
                        flex
                        items-center
                        gap-4
                        rounded-2xl
                        border
                        border-white/10
                        bg-white/[0.02]
                        p-4
                        transition
                        hover:border-indigo-400/30
                        hover:bg-indigo-500/5
                    ">
                    <div
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-xl
                            bg-indigo-500/10
                        ">
                        ✦
                    </div>

                    <div>
                        <p
                            class="
                                text-sm
                                font-black
                                text-white
                            ">
                            Nueva entidad
                        </p>

                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-600
                            ">
                            Crear una nueva pieza
                        </p>
                    </div>
                </a>


                <a href="{{ route('attributes.create') }}"
                    class="
                        flex
                        items-center
                        gap-4
                        rounded-2xl
                        border
                        border-white/10
                        bg-white/[0.02]
                        p-4
                        transition
                        hover:border-violet-400/30
                        hover:bg-violet-500/5
                    ">
                    <div
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-xl
                            bg-violet-500/10
                        ">
                        ☷
                    </div>

                    <div>
                        <p
                            class="
                                text-sm
                                font-black
                                text-white
                            ">
                            Nuevo atributo
                        </p>

                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-600
                            ">
                            Crear una característica
                        </p>
                    </div>
                </a>


                <a href="{{ route('collections.create') }}"
                    class="
                        flex
                        items-center
                        gap-4
                        rounded-2xl
                        border
                        border-white/10
                        bg-white/[0.02]
                        p-4
                        transition
                        hover:border-cyan-400/30
                        hover:bg-cyan-500/5
                    ">
                    <div
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-xl
                            bg-cyan-500/10
                        ">
                        ▤
                    </div>

                    <div>
                        <p
                            class="
                                text-sm
                                font-black
                                text-white
                            ">
                            Nueva colección
                        </p>

                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-600
                            ">
                            Agrupar entidades
                        </p>
                    </div>
                </a>


                <a href="{{ route('community.index') }}"
                    class="
                        flex
                        items-center
                        gap-4
                        rounded-2xl
                        border
                        border-white/10
                        bg-white/[0.02]
                        p-4
                        transition
                        hover:border-fuchsia-400/30
                        hover:bg-fuchsia-500/5
                    ">
                    <div
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-xl
                            bg-fuchsia-500/10
                        ">
                        ◎
                    </div>

                    <div>
                        <p
                            class="
                                text-sm
                                font-black
                                text-white
                            ">
                            Explorar comunidad
                        </p>

                        <p
                            class="
                                mt-1
                                text-xs
                                text-slate-600
                            ">
                            Descubrir otras creaciones
                        </p>
                    </div>
                </a>

            </div>

        </aside>

    </section>

@endsection

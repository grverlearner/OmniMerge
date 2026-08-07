<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description"
        content="OmniMerge es una plataforma para crear, organizar, compartir y reutilizar entidades, atributos y colecciones.">

    <meta name="theme-color" content="#020617">

    <title>
        OmniMerge — Crea, organiza y conecta tus ideas
    </title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body
    class="
        min-h-screen
        bg-slate-950
        font-sans
        text-slate-100
        antialiased
        selection:bg-indigo-500
        selection:text-white
    ">

    <div x-data="{ mobileMenu: false }" class="min-h-screen overflow-hidden">

        {{-- ========================================================= --}}
        {{-- FONDO --}}
        {{-- ========================================================= --}}

        <div
            class="
            pointer-events-none
            fixed
            inset-0
            -z-10
            overflow-hidden
        ">
            <div
                class="
                absolute
                left-1/2
                top-[-300px]
                h-[650px]
                w-[650px]
                -translate-x-1/2
                rounded-full
                bg-indigo-600/20
                blur-[150px]
            ">
            </div>

            <div
                class="
                absolute
                right-[-200px]
                top-[300px]
                h-[500px]
                w-[500px]
                rounded-full
                bg-violet-600/10
                blur-[150px]
            ">
            </div>

            <div
                class="
                absolute
                bottom-[-250px]
                left-[-150px]
                h-[550px]
                w-[550px]
                rounded-full
                bg-fuchsia-600/10
                blur-[150px]
            ">
            </div>
        </div>


        {{-- ========================================================= --}}
        {{-- NAVBAR --}}
        {{-- ========================================================= --}}

        <header
            class="
            fixed
            inset-x-0
            top-0
            z-50
            border-b
            border-white/10
            bg-slate-950/80
            backdrop-blur-xl
        ">
            <div
                class="
                mx-auto
                flex
                h-20
                max-w-7xl
                items-center
                justify-between
                px-5
                sm:px-6
                lg:px-8
            ">

                {{-- LOGO --}}
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10" />

                    <div>
                        <span
                            class="
                            block
                            text-lg
                            font-black
                            tracking-tight
                            text-white
                        ">
                            OmniMerge
                        </span>

                        <span
                            class="
                            hidden
                            text-[10px]
                            font-semibold
                            uppercase
                            tracking-[0.22em]
                            text-slate-500
                            sm:block
                        ">
                            Create · Connect · Evolve
                        </span>
                    </div>
                </a>


                {{-- NAVEGACIÓN ESCRITORIO --}}
                <nav
                    class="
                    hidden
                    items-center
                    gap-7
                    lg:flex
                ">
                    <a href="#features"
                        class="
                        text-sm
                        font-semibold
                        text-slate-400
                        transition
                        hover:text-white
                    ">
                        Características
                    </a>

                    <a href="#how-it-works"
                        class="
                        text-sm
                        font-semibold
                        text-slate-400
                        transition
                        hover:text-white
                    ">
                        Cómo funciona
                    </a>

                    <a href="#community"
                        class="
                        text-sm
                        font-semibold
                        text-slate-400
                        transition
                        hover:text-white
                    ">
                        Comunidad
                    </a>

                    <a href="#future"
                        class="
                        text-sm
                        font-semibold
                        text-slate-400
                        transition
                        hover:text-white
                    ">
                        Futuro
                    </a>
                </nav>


                {{-- AUTENTICACIÓN --}}
                <div
                    class="
                    hidden
                    items-center
                    gap-3
                    lg:flex
                ">
                    @auth

                        <a href="{{ route('community.index') }}"
                            class="
                            rounded-xl
                            px-4
                            py-2.5
                            text-sm
                            font-bold
                            text-slate-300
                            transition
                            hover:bg-white/5
                            hover:text-white
                        ">
                            Explorar
                        </a>

                        <a href="{{ route('hub') }}"
                            class="
                            rounded-xl
                            bg-white
                            px-5
                            py-2.5
                            text-sm
                            font-black
                            text-slate-950
                            transition
                            hover:bg-indigo-50
                        ">
                            Ir a OmniMerge
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="
                            rounded-xl
                            px-4
                            py-2.5
                            text-sm
                            font-bold
                            text-slate-300
                            transition
                            hover:bg-white/5
                            hover:text-white
                        ">
                            Iniciar sesión
                        </a>

                        <a href="{{ route('register') }}"
                            class="
                            rounded-xl
                            bg-white
                            px-5
                            py-2.5
                            text-sm
                            font-black
                            text-slate-950
                            shadow-xl
                            shadow-indigo-950/20
                            transition
                            hover:bg-indigo-50
                        ">
                            Crear cuenta
                        </a>

                    @endauth
                </div>


                {{-- BOTÓN MÓVIL --}}
                <button type="button" @click="mobileMenu = !mobileMenu"
                    class="
                    flex
                    h-11
                    w-11
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-white/10
                    text-slate-300
                    lg:hidden
                ">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>


            {{-- MENÚ MÓVIL --}}
            <div x-cloak x-show="mobileMenu" x-transition
                class="
                border-t
                border-white/10
                bg-slate-950
                px-5
                py-5
                lg:hidden
            ">
                <div class="space-y-2">

                    <a href="#features" @click="mobileMenu = false"
                        class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        font-bold
                        text-slate-300
                        hover:bg-white/5
                    ">
                        Características
                    </a>

                    <a href="#how-it-works" @click="mobileMenu = false"
                        class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        font-bold
                        text-slate-300
                        hover:bg-white/5
                    ">
                        Cómo funciona
                    </a>

                    <a href="#community" @click="mobileMenu = false"
                        class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        font-bold
                        text-slate-300
                        hover:bg-white/5
                    ">
                        Comunidad
                    </a>

                    @auth

                        <a href="{{ route('hub') }}"
                            class="
                            mt-3
                            block
                            rounded-xl
                            bg-indigo-600
                            px-4
                            py-3
                            text-center
                            text-sm
                            font-black
                            text-white
                        ">
                            Ir a OmniMerge
                        </a>
                    @else
                        <div
                            class="
                            grid
                            grid-cols-2
                            gap-3
                            pt-3
                        ">
                            <a href="{{ route('login') }}"
                                class="
                                rounded-xl
                                border
                                border-white/10
                                px-4
                                py-3
                                text-center
                                text-sm
                                font-bold
                                text-white
                            ">
                                Entrar
                            </a>

                            <a href="{{ route('register') }}"
                                class="
                                rounded-xl
                                bg-indigo-600
                                px-4
                                py-3
                                text-center
                                text-sm
                                font-bold
                                text-white
                            ">
                                Registrarme
                            </a>
                        </div>

                    @endauth
                </div>
            </div>
        </header>


        {{-- ========================================================= --}}
        {{-- HERO --}}
        {{-- ========================================================= --}}

        <main>

            <section
                class="
                relative
                mx-auto
                max-w-7xl
                px-5
                pb-24
                pt-40
                sm:px-6
                lg:px-8
                lg:pb-32
                lg:pt-48
            ">

                <div
                    class="
                    mx-auto
                    max-w-4xl
                    text-center
                ">

                    <div
                        class="
                        inline-flex
                        items-center
                        gap-2
                        rounded-full
                        border
                        border-indigo-400/20
                        bg-indigo-500/10
                        px-4
                        py-2
                        text-xs
                        font-bold
                        uppercase
                        tracking-[0.18em]
                        text-indigo-300
                    ">
                        <span
                            class="
                            h-2
                            w-2
                            rounded-full
                            bg-indigo-400
                            shadow-[0_0_12px_rgba(129,140,248,0.8)]
                        "></span>

                        Tu imaginación, estructurada
                    </div>


                    <h1
                        class="
                        mt-8
                        text-5xl
                        font-black
                        leading-[1.05]
                        tracking-[-0.04em]
                        text-white
                        sm:text-6xl
                        lg:text-7xl
                    ">
                        Crea cualquier cosa.

                        <span
                            class="
                            bg-gradient-to-r
                            from-indigo-400
                            via-violet-400
                            to-fuchsia-400
                            bg-clip-text
                            text-transparent
                        ">
                            Conecta todo.
                        </span>
                    </h1>


                    <p
                        class="
                        mx-auto
                        mt-7
                        max-w-2xl
                        text-base
                        leading-8
                        text-slate-400
                        sm:text-lg
                    ">
                        OmniMerge es un espacio donde puedes crear personajes,
                        países, criaturas, objetos, conceptos y cualquier otra
                        entidad; definir sus propias características, organizarlas
                        y reutilizarlas en nuevos contextos.
                    </p>


                    <div
                        class="
                        mt-10
                        flex
                        flex-col
                        justify-center
                        gap-3
                        sm:flex-row
                    ">
                        @auth

                            <a href="{{ route('hub') }}"
                                class="
                                inline-flex
                                items-center
                                justify-center
                                gap-2
                                rounded-2xl
                                bg-indigo-600
                                px-7
                                py-4
                                text-sm
                                font-black
                                text-white
                                shadow-2xl
                                shadow-indigo-600/30
                                transition
                                hover:-translate-y-0.5
                                hover:bg-indigo-500
                            ">
                                Abrir mi biblioteca

                                <span>→</span>
                            </a>

                            <a href="{{ route('community.index') }}"
                                class="
                                rounded-2xl
                                border
                                border-white/10
                                bg-white/5
                                px-7
                                py-4
                                text-sm
                                font-black
                                text-white
                                backdrop-blur
                                transition
                                hover:bg-white/10
                            ">
                                Explorar comunidad
                            </a>
                        @else
                            <a href="{{ route('register') }}"
                                class="
                                inline-flex
                                items-center
                                justify-center
                                gap-2
                                rounded-2xl
                                bg-indigo-600
                                px-7
                                py-4
                                text-sm
                                font-black
                                text-white
                                shadow-2xl
                                shadow-indigo-600/30
                                transition
                                hover:-translate-y-0.5
                                hover:bg-indigo-500
                            ">
                                Comenzar gratis

                                <span>→</span>
                            </a>

                            <a href="#features"
                                class="
                                rounded-2xl
                                border
                                border-white/10
                                bg-white/5
                                px-7
                                py-4
                                text-sm
                                font-black
                                text-white
                                backdrop-blur
                                transition
                                hover:bg-white/10
                            ">
                                Descubrir OmniMerge
                            </a>

                        @endauth
                    </div>


                    <div
                        class="
                        mt-8
                        flex
                        flex-wrap
                        justify-center
                        gap-x-6
                        gap-y-2
                        text-xs
                        font-semibold
                        text-slate-500
                    ">
                        <span>✓ Entidades personalizadas</span>
                        <span>✓ Atributos dinámicos</span>
                        <span>✓ Colecciones</span>
                        <span>✓ Comunidad</span>
                    </div>
                </div>


                {{-- ================================================= --}}
                {{-- PREVIEW DE LA APP --}}
                {{-- ================================================= --}}

                <div
                    class="
                    relative
                    mx-auto
                    mt-20
                    max-w-6xl
                ">

                    <div
                        class="
                        absolute
                        inset-x-20
                        bottom-0
                        h-48
                        bg-indigo-500/20
                        blur-[100px]
                    ">
                    </div>


                    <div
                        class="
                        relative
                        overflow-hidden
                        rounded-[28px]
                        border
                        border-white/10
                        bg-slate-900
                        shadow-2xl
                        shadow-black/60
                    ">

                        {{-- TOP BAR --}}
                        <div
                            class="
                            flex
                            h-14
                            items-center
                            justify-between
                            border-b
                            border-white/10
                            bg-slate-950/70
                            px-5
                        ">
                            <div class="flex gap-2">
                                <span class="h-3 w-3 rounded-full bg-red-400/70"></span>
                                <span class="h-3 w-3 rounded-full bg-amber-400/70"></span>
                                <span class="h-3 w-3 rounded-full bg-emerald-400/70"></span>
                            </div>

                            <span
                                class="
                                text-xs
                                font-semibold
                                text-slate-500
                            ">
                                OmniMerge · Mi biblioteca
                            </span>

                            <div class="w-14"></div>
                        </div>


                        <div
                            class="
                            grid
                            min-h-[520px]
                            lg:grid-cols-[230px_1fr]
                        ">

                            {{-- SIDEBAR PREVIEW --}}
                            <aside
                                class="
                                hidden
                                border-r
                                border-white/10
                                bg-slate-950/60
                                p-5
                                lg:block
                            ">
                                <div
                                    class="
                                    flex
                                    items-center
                                    gap-3
                                    px-2
                                ">
                                    <x-application-logo class="h-9 w-9" />

                                    <span
                                        class="
                                        font-black
                                        text-white
                                    ">
                                        OmniMerge
                                    </span>
                                </div>


                                <div class="mt-8 space-y-2">

                                    <div
                                        class="
                                        rounded-xl
                                        bg-indigo-500/15
                                        px-4
                                        py-3
                                        text-sm
                                        font-bold
                                        text-indigo-300
                                    ">
                                        ◈ Dashboard
                                    </div>

                                    <div
                                        class="
                                        px-4
                                        py-3
                                        text-sm
                                        text-slate-500
                                    ">
                                        ✦ Entidades
                                    </div>

                                    <div
                                        class="
                                        px-4
                                        py-3
                                        text-sm
                                        text-slate-500
                                    ">
                                        ☷ Atributos
                                    </div>

                                    <div
                                        class="
                                        px-4
                                        py-3
                                        text-sm
                                        text-slate-500
                                    ">
                                        ◆ Opciones
                                    </div>

                                    <div
                                        class="
                                        px-4
                                        py-3
                                        text-sm
                                        text-slate-500
                                    ">
                                        ▤ Colecciones
                                    </div>

                                    <div
                                        class="
                                        px-4
                                        py-3
                                        text-sm
                                        text-slate-500
                                    ">
                                        ◎ Comunidad
                                    </div>
                                </div>
                            </aside>


                            {{-- CONTENIDO PREVIEW --}}
                            <div class="p-5 sm:p-8">

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
                                            text-xs
                                            font-bold
                                            uppercase
                                            tracking-[0.18em]
                                            text-indigo-400
                                        ">
                                            Biblioteca
                                        </p>

                                        <h3
                                            class="
                                            mt-2
                                            text-2xl
                                            font-black
                                            text-white
                                        ">
                                            Mis entidades
                                        </h3>
                                    </div>

                                    <div
                                        class="
                                        rounded-xl
                                        bg-indigo-600
                                        px-5
                                        py-3
                                        text-center
                                        text-xs
                                        font-black
                                        text-white
                                    ">
                                        + Nueva entidad
                                    </div>
                                </div>


                                <div
                                    class="
                                    mt-8
                                    grid
                                    gap-5
                                    md:grid-cols-3
                                ">

                                    {{-- CARD 1 --}}
                                    <div
                                        class="
                                        overflow-hidden
                                        rounded-2xl
                                        border
                                        border-white/10
                                        bg-white/5
                                    ">
                                        <div
                                            class="
                                            flex
                                            aspect-[4/3]
                                            items-center
                                            justify-center
                                            bg-gradient-to-br
                                            from-orange-500/20
                                            to-red-500/10
                                        ">
                                            <span class="text-6xl">
                                                🍥
                                            </span>
                                        </div>

                                        <div class="p-4">
                                            <span
                                                class="
                                                text-[10px]
                                                font-black
                                                uppercase
                                                tracking-wider
                                                text-orange-400
                                            ">
                                                Personaje
                                            </span>

                                            <h4
                                                class="
                                                mt-1
                                                font-black
                                                text-white
                                            ">
                                                Naruto Uzumaki
                                            </h4>

                                            <p
                                                class="
                                                mt-2
                                                text-xs
                                                text-slate-500
                                            ">
                                                Anime · Viento · Ninja
                                            </p>
                                        </div>
                                    </div>


                                    {{-- CARD 2 --}}
                                    <div
                                        class="
                                        overflow-hidden
                                        rounded-2xl
                                        border
                                        border-white/10
                                        bg-white/5
                                    ">
                                        <div
                                            class="
                                            flex
                                            aspect-[4/3]
                                            items-center
                                            justify-center
                                            bg-gradient-to-br
                                            from-cyan-500/20
                                            to-blue-500/10
                                        ">
                                            <span class="text-6xl">
                                                🌎
                                            </span>
                                        </div>

                                        <div class="p-4">
                                            <span
                                                class="
                                                text-[10px]
                                                font-black
                                                uppercase
                                                tracking-wider
                                                text-cyan-400
                                            ">
                                                País
                                            </span>

                                            <h4
                                                class="
                                                mt-1
                                                font-black
                                                text-white
                                            ">
                                                Perú
                                            </h4>

                                            <p
                                                class="
                                                mt-2
                                                text-xs
                                                text-slate-500
                                            ">
                                                América · Español
                                            </p>
                                        </div>
                                    </div>


                                    {{-- CARD 3 --}}
                                    <div
                                        class="
                                        overflow-hidden
                                        rounded-2xl
                                        border
                                        border-white/10
                                        bg-white/5
                                    ">
                                        <div
                                            class="
                                            flex
                                            aspect-[4/3]
                                            items-center
                                            justify-center
                                            bg-gradient-to-br
                                            from-violet-500/20
                                            to-fuchsia-500/10
                                        ">
                                            <span class="text-6xl">
                                                🐉
                                            </span>
                                        </div>

                                        <div class="p-4">
                                            <span
                                                class="
                                                text-[10px]
                                                font-black
                                                uppercase
                                                tracking-wider
                                                text-violet-400
                                            ">
                                                Criatura
                                            </span>

                                            <h4
                                                class="
                                                mt-1
                                                font-black
                                                text-white
                                            ">
                                                Dragón Arcano
                                            </h4>

                                            <p
                                                class="
                                                mt-2
                                                text-xs
                                                text-slate-500
                                            ">
                                                Fuego · Volador
                                            </p>
                                        </div>
                                    </div>

                                </div>


                                {{-- ATRIBUTOS PREVIEW --}}
                                <div
                                    class="
                                    mt-6
                                    rounded-2xl
                                    border
                                    border-white/10
                                    bg-white/[0.03]
                                    p-5
                                ">
                                    <div
                                        class="
                                        flex
                                        items-center
                                        justify-between
                                    ">
                                        <span
                                            class="
                                            text-sm
                                            font-black
                                            text-white
                                        ">
                                            Atributos dinámicos
                                        </span>

                                        <span
                                            class="
                                            rounded-full
                                            bg-emerald-500/10
                                            px-3
                                            py-1
                                            text-[10px]
                                            font-bold
                                            text-emerald-400
                                        ">
                                            Flexible
                                        </span>
                                    </div>

                                    <div
                                        class="
                                        mt-4
                                        flex
                                        flex-wrap
                                        gap-2
                                    ">
                                        @foreach (['Anime', 'Elemento', 'Poder', 'Origen', 'Color', 'Habilidades'] as $previewAttribute)
                                            <span
                                                class="
                                                rounded-lg
                                                border
                                                border-white/10
                                                bg-slate-950
                                                px-3
                                                py-2
                                                text-xs
                                                font-semibold
                                                text-slate-400
                                            ">
                                                {{ $previewAttribute }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- QUÉ PUEDES CREAR --}}
            {{-- ========================================================= --}}

            <section
                class="
                border-y
                border-white/10
                bg-white/[0.02]
                py-20
            ">
                <div
                    class="
                    mx-auto
                    max-w-7xl
                    px-5
                    sm:px-6
                    lg:px-8
                ">
                    <div
                        class="
                        mx-auto
                        max-w-3xl
                        text-center
                    ">
                        <p
                            class="
                            text-xs
                            font-black
                            uppercase
                            tracking-[0.2em]
                            text-indigo-400
                        ">
                            Sin categorías rígidas
                        </p>

                        <h2
                            class="
                            mt-4
                            text-3xl
                            font-black
                            tracking-tight
                            text-white
                            sm:text-4xl
                        ">
                            ¿Qué puedes crear?
                        </h2>

                        <p
                            class="
                            mt-5
                            text-base
                            leading-7
                            text-slate-400
                        ">
                            OmniMerge no decide qué es una entidad.
                            Tú decides qué quieres representar.
                        </p>
                    </div>


                    <div
                        class="
                        mt-12
                        grid
                        grid-cols-2
                        gap-3
                        sm:grid-cols-3
                        lg:grid-cols-6
                    ">
                        @foreach ([['👤', 'Personajes'], ['🐉', 'Criaturas'], ['🌎', 'Países'], ['⚔️', 'Objetos'], ['🪐', 'Planetas'], ['💡', 'Conceptos'], ['🐺', 'Animales'], ['🚗', 'Vehículos'], ['🏰', 'Lugares'], ['🔥', 'Elementos'], ['🛡️', 'Organizaciones'], ['∞', 'Lo que imagines']] as [$icon, $label])
                            <div
                                class="
                                rounded-2xl
                                border
                                border-white/10
                                bg-white/[0.03]
                                p-5
                                text-center
                                transition
                                hover:-translate-y-1
                                hover:border-indigo-400/30
                                hover:bg-indigo-500/5
                            ">
                                <span
                                    class="
                                    text-3xl
                                ">
                                    {{ $icon }}
                                </span>

                                <p
                                    class="
                                    mt-3
                                    text-xs
                                    font-bold
                                    text-slate-300
                                ">
                                    {{ $label }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- FEATURES --}}
            {{-- ========================================================= --}}

            <section id="features"
                class="
                mx-auto
                max-w-7xl
                scroll-mt-24
                px-5
                py-28
                sm:px-6
                lg:px-8
            ">
                <div class="max-w-3xl">

                    <p
                        class="
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.2em]
                        text-indigo-400
                    ">
                        Herramientas
                    </p>

                    <h2
                        class="
                        mt-4
                        text-3xl
                        font-black
                        tracking-tight
                        text-white
                        sm:text-4xl
                    ">
                        Una biblioteca que se adapta a tus ideas
                    </h2>

                    <p
                        class="
                        mt-5
                        text-base
                        leading-8
                        text-slate-400
                    ">
                        Define primero tu estructura y después construye sobre ella.
                        No necesitas adaptar tus ideas a un formulario fijo.
                    </p>
                </div>


                <div
                    class="
                    mt-14
                    grid
                    gap-5
                    md:grid-cols-2
                    lg:grid-cols-3
                ">

                    @php
                        $features = [
                            [
                                'icon' => '✦',
                                'title' => 'Entidades libres',
                                'description' =>
                                    'Crea personajes, lugares, objetos, animales, conceptos o cualquier elemento que necesites.',
                            ],
                            [
                                'icon' => '☷',
                                'title' => 'Atributos dinámicos',
                                'description' =>
                                    'Define texto, números, fechas, colores, booleanos, catálogos y otros tipos de características.',
                            ],
                            [
                                'icon' => '◆',
                                'title' => 'Catálogos visuales',
                                'description' =>
                                    'Crea opciones reutilizables con nombre, descripción, imagen, icono, color y jerarquía.',
                            ],
                            [
                                'icon' => '☑',
                                'title' => 'Multiselección',
                                'description' =>
                                    'Una entidad puede poseer varios elementos, habilidades, géneros, categorías o características.',
                            ],
                            [
                                'icon' => '▤',
                                'title' => 'Colecciones',
                                'description' =>
                                    'Agrupa entidades en conjuntos temáticos y reutilízalas según el contexto que necesites.',
                            ],
                            [
                                'icon' => '◎',
                                'title' => 'Comunidad',
                                'description' =>
                                    'Publica contenido, explora creaciones de otros usuarios y copia recursos a tu propia biblioteca.',
                            ],
                        ];
                    @endphp


                    @foreach ($features as $feature)
                        <article
                            class="
                            group
                            rounded-3xl
                            border
                            border-white/10
                            bg-white/[0.025]
                            p-7
                            transition
                            hover:-translate-y-1
                            hover:border-indigo-400/30
                            hover:bg-indigo-500/[0.04]
                        ">
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
                                transition
                                group-hover:bg-indigo-500/20
                            ">
                                {{ $feature['icon'] }}
                            </div>

                            <h3
                                class="
                                mt-6
                                text-lg
                                font-black
                                text-white
                            ">
                                {{ $feature['title'] }}
                            </h3>

                            <p
                                class="
                                mt-3
                                text-sm
                                leading-7
                                text-slate-400
                            ">
                                {{ $feature['description'] }}
                            </p>
                        </article>
                    @endforeach

                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- CÓMO FUNCIONA --}}
            {{-- ========================================================= --}}

            <section id="how-it-works"
                class="
                scroll-mt-24
                border-y
                border-white/10
                bg-white/[0.02]
                py-28
            ">
                <div
                    class="
                    mx-auto
                    max-w-7xl
                    px-5
                    sm:px-6
                    lg:px-8
                ">

                    <div
                        class="
                        mx-auto
                        max-w-3xl
                        text-center
                    ">
                        <p
                            class="
                            text-xs
                            font-black
                            uppercase
                            tracking-[0.2em]
                            text-violet-400
                        ">
                            Flujo de trabajo
                        </p>

                        <h2
                            class="
                            mt-4
                            text-3xl
                            font-black
                            text-white
                            sm:text-4xl
                        ">
                            De una idea a una biblioteca organizada
                        </h2>
                    </div>


                    <div
                        class="
                        mt-16
                        grid
                        gap-6
                        lg:grid-cols-4
                    ">

                        @foreach ([['01', 'Define el tipo', 'Crea categorías como Personaje, País, Objeto o Criatura.'], ['02', 'Diseña atributos', 'Decide qué características podrán describir tus entidades.'], ['03', 'Crea entidades', 'Añade información, imágenes y valores personalizados.'], ['04', 'Organiza y comparte', 'Agrupa mediante colecciones o publica contenido para la comunidad.']] as [$number, $title, $description])
                            <article
                                class="
                                relative
                                rounded-3xl
                                border
                                border-white/10
                                bg-slate-950
                                p-7
                            ">
                                <span
                                    class="
                                    text-4xl
                                    font-black
                                    text-white/5
                                ">
                                    {{ $number }}
                                </span>

                                <h3
                                    class="
                                    mt-5
                                    font-black
                                    text-white
                                ">
                                    {{ $title }}
                                </h3>

                                <p
                                    class="
                                    mt-3
                                    text-sm
                                    leading-7
                                    text-slate-500
                                ">
                                    {{ $description }}
                                </p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- ATRIBUTOS --}}
            {{-- ========================================================= --}}

            <section
                class="
                mx-auto
                grid
                max-w-7xl
                gap-14
                px-5
                py-28
                sm:px-6
                lg:grid-cols-2
                lg:items-center
                lg:px-8
            ">

                <div>

                    <p
                        class="
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.2em]
                        text-indigo-400
                    ">
                        Personalización profunda
                    </p>

                    <h2
                        class="
                        mt-4
                        text-3xl
                        font-black
                        tracking-tight
                        text-white
                        sm:text-4xl
                    ">
                        Tú decides qué información existe
                    </h2>

                    <p
                        class="
                        mt-6
                        text-base
                        leading-8
                        text-slate-400
                    ">
                        Los atributos de OmniMerge no son columnas rígidas.
                        Cada usuario puede diseñar las características que
                        necesita para sus propias entidades.
                    </p>


                    <div class="mt-8 space-y-4">

                        @foreach (['Texto y texto largo', 'Números enteros y decimales', 'Sí / No', 'Fechas', 'Colores', 'Catálogos seleccionables', 'Selección múltiple'] as $type)
                            <div
                                class="
                                flex
                                items-center
                                gap-3
                                text-sm
                                font-semibold
                                text-slate-300
                            ">
                                <span
                                    class="
                                    flex
                                    h-6
                                    w-6
                                    items-center
                                    justify-center
                                    rounded-full
                                    bg-emerald-500/10
                                    text-xs
                                    text-emerald-400
                                ">
                                    ✓
                                </span>

                                {{ $type }}
                            </div>
                        @endforeach
                    </div>
                </div>


                {{-- EJEMPLO DE FORMULARIO --}}
                <div
                    class="
                    rounded-[30px]
                    border
                    border-white/10
                    bg-white/[0.03]
                    p-6
                    shadow-2xl
                    shadow-black/20
                    sm:p-8
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
                                text-indigo-400
                            ">
                                Ejemplo
                            </p>

                            <h3
                                class="
                                mt-1
                                text-xl
                                font-black
                                text-white
                            ">
                                Naruto Uzumaki
                            </h3>
                        </div>

                        <span class="text-4xl">
                            🍥
                        </span>
                    </div>


                    <div class="mt-7 space-y-5">

                        @foreach ([['Anime', 'Naruto', 'bg-orange-500/10 text-orange-300'], ['Elemento', 'Viento · Fuego', 'bg-red-500/10 text-red-300'], ['Nivel de poder', '92 / 100', 'bg-violet-500/10 text-violet-300'], ['Puede volar', 'No', 'bg-slate-700 text-slate-300']] as [$label, $value, $classes])
                            <div>
                                <p
                                    class="
                                    mb-2
                                    text-xs
                                    font-bold
                                    uppercase
                                    tracking-wider
                                    text-slate-500
                                ">
                                    {{ $label }}
                                </p>

                                <div
                                    class="
                                    rounded-xl
                                    border
                                    border-white/10
                                    bg-slate-950
                                    p-3
                                ">
                                    <span
                                        class="
                                        inline-flex
                                        rounded-lg
                                        px-3
                                        py-1.5
                                        text-xs
                                        font-bold
                                        {{ $classes }}
                                    ">
                                        {{ $value }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- COMUNIDAD --}}
            {{-- ========================================================= --}}

            <section id="community"
                class="
                scroll-mt-24
                border-y
                border-white/10
                bg-gradient-to-b
                from-indigo-500/[0.04]
                to-transparent
                py-28
            ">
                <div
                    class="
                    mx-auto
                    max-w-7xl
                    px-5
                    sm:px-6
                    lg:px-8
                ">

                    <div
                        class="
                        grid
                        gap-12
                        lg:grid-cols-[0.9fr_1.1fr]
                        lg:items-center
                    ">

                        <div>

                            <p
                                class="
                                text-xs
                                font-black
                                uppercase
                                tracking-[0.2em]
                                text-fuchsia-400
                            ">
                                Comunidad OmniMerge
                            </p>

                            <h2
                                class="
                                mt-4
                                text-3xl
                                font-black
                                text-white
                                sm:text-4xl
                            ">
                                Descubre ideas y conviértelas en tuyas
                            </h2>

                            <p
                                class="
                                mt-6
                                text-base
                                leading-8
                                text-slate-400
                            ">
                                El contenido público puede ser descubierto por
                                otros usuarios. Si el creador permite clonarlo,
                                puedes generar una copia independiente en tu
                                propia biblioteca.
                            </p>


                            <div class="mt-8 space-y-4">

                                @foreach (['Explora entidades públicas', 'Descubre colecciones', 'Busca atributos y catálogos', 'Filtra y ordena resultados', 'Copia contenido reutilizable', 'Modifica tu copia sin afectar el original'] as $communityFeature)
                                    <div
                                        class="
                                        flex
                                        items-center
                                        gap-3
                                        text-sm
                                        font-semibold
                                        text-slate-300
                                    ">
                                        <span
                                            class="
                                            flex
                                            h-7
                                            w-7
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-fuchsia-500/10
                                            text-fuchsia-400
                                        ">
                                            ✓
                                        </span>

                                        {{ $communityFeature }}
                                    </div>
                                @endforeach
                            </div>


                            @auth
                                <a href="{{ route('community.index') }}"
                                    class="
                                    mt-9
                                    inline-flex
                                    rounded-2xl
                                    bg-white
                                    px-6
                                    py-3.5
                                    text-sm
                                    font-black
                                    text-slate-950
                                ">
                                    Explorar comunidad →
                                </a>
                            @else
                                <a href="{{ route('register') }}"
                                    class="
                                    mt-9
                                    inline-flex
                                    rounded-2xl
                                    bg-white
                                    px-6
                                    py-3.5
                                    text-sm
                                    font-black
                                    text-slate-950
                                ">
                                    Crear cuenta →
                                </a>
                            @endauth
                        </div>


                        {{-- MOCKUP COMUNIDAD --}}
                        <div
                            class="
                            grid
                            gap-4
                            sm:grid-cols-2
                        ">
                            @foreach ([['🍥', 'Naruto Uzumaki', 'Personaje', 'Anime · Ninja', 'from-orange-500/20 to-red-500/10'], ['🐉', 'Dragón Arcano', 'Criatura', 'Fuego · Volador', 'from-violet-500/20 to-purple-500/10'], ['🌍', 'Países de América', 'Colección', '12 entidades', 'from-cyan-500/20 to-blue-500/10'], ['🔥', 'Elemento', 'Atributo', '8 opciones', 'from-red-500/20 to-orange-500/10']] as [$icon, $name, $type, $meta, $gradient])
                                <article
                                    class="
                                    overflow-hidden
                                    rounded-3xl
                                    border
                                    border-white/10
                                    bg-white/[0.03]
                                ">
                                    <div
                                        class="
                                        flex
                                        aspect-[16/9]
                                        items-center
                                        justify-center
                                        bg-gradient-to-br
                                        {{ $gradient }}
                                    ">
                                        <span class="text-5xl">
                                            {{ $icon }}
                                        </span>
                                    </div>

                                    <div class="p-5">
                                        <p
                                            class="
                                            text-[10px]
                                            font-black
                                            uppercase
                                            tracking-wider
                                            text-indigo-400
                                        ">
                                            {{ $type }}
                                        </p>

                                        <h3
                                            class="
                                            mt-1
                                            font-black
                                            text-white
                                        ">
                                            {{ $name }}
                                        </h3>

                                        <p
                                            class="
                                            mt-2
                                            text-xs
                                            text-slate-500
                                        ">
                                            {{ $meta }}
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- FUTURO --}}
            {{-- ========================================================= --}}

            <section id="future"
                class="
                mx-auto
                max-w-7xl
                scroll-mt-24
                px-5
                py-28
                sm:px-6
                lg:px-8
            ">
                <div
                    class="
                    mx-auto
                    max-w-3xl
                    text-center
                ">
                    <div
                        class="
                        inline-flex
                        rounded-full
                        bg-amber-500/10
                        px-4
                        py-2
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-amber-400
                    ">
                        En desarrollo
                    </div>

                    <h2
                        class="
                        mt-5
                        text-3xl
                        font-black
                        text-white
                        sm:text-4xl
                    ">
                        La biblioteca es solo el comienzo
                    </h2>

                    <p
                        class="
                        mt-5
                        leading-8
                        text-slate-400
                    ">
                        OmniMerge está preparado para evolucionar hacia un
                        sistema donde tus entidades puedan participar en
                        universos, temporadas y simulaciones.
                    </p>
                </div>


                <div
                    class="
                    mt-14
                    grid
                    gap-5
                    md:grid-cols-2
                    lg:grid-cols-4
                ">
                    @foreach ([['🌌', 'Universos', 'Agrupa entidades dentro de mundos independientes.'], ['🏆', 'Torneos', 'Organiza competencias utilizando tus entidades.'], ['⚡', 'Simulaciones', 'Genera resultados a partir de reglas y atributos.'], ['📊', 'Rankings', 'Analiza resultados, estadísticas e historial.']] as [$icon, $title, $description])
                        <article
                            class="
                            rounded-3xl
                            border
                            border-dashed
                            border-white/10
                            bg-white/[0.02]
                            p-7
                        ">
                            <span class="text-3xl">
                                {{ $icon }}
                            </span>

                            <div
                                class="
                                mt-5
                                flex
                                items-center
                                gap-2
                            ">
                                <h3
                                    class="
                                    font-black
                                    text-white
                                ">
                                    {{ $title }}
                                </h3>

                                <span
                                    class="
                                    rounded-full
                                    bg-amber-500/10
                                    px-2
                                    py-0.5
                                    text-[9px]
                                    font-black
                                    uppercase
                                    tracking-wider
                                    text-amber-400
                                ">
                                    Próximamente
                                </span>
                            </div>

                            <p
                                class="
                                mt-3
                                text-sm
                                leading-7
                                text-slate-500
                            ">
                                {{ $description }}
                            </p>
                        </article>
                    @endforeach
                </div>
            </section>


            {{-- ========================================================= --}}
            {{-- CTA FINAL --}}
            {{-- ========================================================= --}}

            <section
                class="
                mx-auto
                max-w-7xl
                px-5
                pb-28
                sm:px-6
                lg:px-8
            ">
                <div
                    class="
                    relative
                    overflow-hidden
                    rounded-[36px]
                    border
                    border-indigo-400/20
                    bg-gradient-to-br
                    from-indigo-600
                    via-violet-600
                    to-fuchsia-600
                    px-6
                    py-16
                    text-center
                    shadow-2xl
                    shadow-indigo-950/40
                    sm:px-12
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

                    <div class="relative">

                        <h2
                            class="
                            text-3xl
                            font-black
                            tracking-tight
                            text-white
                            sm:text-5xl
                        ">
                            Construye tu propia biblioteca
                        </h2>

                        <p
                            class="
                            mx-auto
                            mt-5
                            max-w-xl
                            text-sm
                            leading-7
                            text-indigo-100
                            sm:text-base
                        ">
                            Crea entidades, diseña sus características,
                            organízalas y descubre lo que otros usuarios
                            están construyendo.
                        </p>


                        @auth

                            <a href="{{ route('hub') }}"
                                class="
                                mt-8
                                inline-flex
                                rounded-2xl
                                bg-white
                                px-7
                                py-4
                                text-sm
                                font-black
                                text-indigo-700
                                shadow-xl
                                transition
                                hover:-translate-y-0.5
                            ">
                                Abrir OmniMerge →
                            </a>
                        @else
                            <div
                                class="
                                mt-8
                                flex
                                flex-col
                                justify-center
                                gap-3
                                sm:flex-row
                            ">
                                <a href="{{ route('register') }}"
                                    class="
                                    rounded-2xl
                                    bg-white
                                    px-7
                                    py-4
                                    text-sm
                                    font-black
                                    text-indigo-700
                                    shadow-xl
                                ">
                                    Crear mi cuenta
                                </a>

                                <a href="{{ route('login') }}"
                                    class="
                                    rounded-2xl
                                    border
                                    border-white/20
                                    bg-white/10
                                    px-7
                                    py-4
                                    text-sm
                                    font-black
                                    text-white
                                    backdrop-blur
                                ">
                                    Ya tengo una cuenta
                                </a>
                            </div>

                        @endauth
                    </div>
                </div>
            </section>

        </main>


        {{-- ========================================================= --}}
        {{-- FOOTER --}}
        {{-- ========================================================= --}}

        <footer class="
            border-t
            border-white/10
            bg-slate-950
        ">
            <div
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
                    gap-8
                    md:flex-row
                ">

                    <div class="max-w-sm">
                        <div
                            class="
                            flex
                            items-center
                            gap-3
                        ">
                            <x-application-logo class="h-9 w-9" />

                            <span
                                class="
                                text-lg
                                font-black
                                text-white
                            ">
                                OmniMerge
                            </span>
                        </div>

                        <p
                            class="
                            mt-4
                            text-sm
                            leading-7
                            text-slate-500
                        ">
                            Una plataforma flexible para crear,
                            organizar, conectar y reutilizar ideas.
                        </p>
                    </div>


                    <div
                        class="
                        grid
                        grid-cols-2
                        gap-12
                        text-sm
                    ">
                        <div>
                            <p
                                class="
                                font-black
                                text-white
                            ">
                                Plataforma
                            </p>

                            <div
                                class="
                                mt-4
                                space-y-3
                                text-slate-500
                            ">
                                <a href="#features" class="block hover:text-white">
                                    Características
                                </a>

                                <a href="#community" class="block hover:text-white">
                                    Comunidad
                                </a>

                                <a href="#future" class="block hover:text-white">
                                    Roadmap
                                </a>
                            </div>
                        </div>


                        <div>
                            <p
                                class="
                                font-black
                                text-white
                            ">
                                Cuenta
                            </p>

                            <div
                                class="
                                mt-4
                                space-y-3
                                text-slate-500
                            ">
                                @guest
                                    <a href="{{ route('login') }}" class="block hover:text-white">
                                        Iniciar sesión
                                    </a>

                                    <a href="{{ route('register') }}" class="block hover:text-white">
                                        Registrarse
                                    </a>
                                @else
                                    <a href="{{ route('dashboard') }}" class="block hover:text-white">
                                        Dashboard
                                    </a>

                                    <a href="{{ route('profile.edit') }}" class="block hover:text-white">
                                        Mi perfil
                                    </a>
                                @endguest
                            </div>
                        </div>
                    </div>
                </div>


                <div
                    class="
                    mt-10
                    flex
                    flex-col
                    justify-between
                    gap-3
                    border-t
                    border-white/10
                    pt-6
                    text-xs
                    text-slate-600
                    sm:flex-row
                ">
                    <p>
                        © {{ date('Y') }} OmniMerge.
                    </p>

                    <p>
                        Create · Connect · Evolve
                    </p>
                </div>
            </div>
        </footer>

    </div>

</body>

</html>

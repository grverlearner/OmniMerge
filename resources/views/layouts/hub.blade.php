<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="theme-color" content="#020617">
    <link rel="icon" type="image/png" href="{{ asset('images/joganboruto.jpg') }}">
    <title>
        @yield('title', 'Centro OmniMerge') | OmniMerge
    </title>


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

    <div x-data="{
        mobileMenu: false,
        userMenu: false
    }" class="min-h-screen">

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
                top-[-350px]
                h-[700px]
                w-[700px]
                -translate-x-1/2
                rounded-full
                bg-indigo-600/15
                blur-[150px]
            ">
            </div>

            <div
                class="
                absolute
                right-[-250px]
                top-[350px]
                h-[550px]
                w-[550px]
                rounded-full
                bg-violet-600/10
                blur-[150px]
            ">
            </div>

            <div
                class="
                absolute
                bottom-[-250px]
                left-[-200px]
                h-[500px]
                w-[500px]
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
            sticky
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
                <a href="{{ route('hub') }}"
                    class="
                    flex
                    items-center
                    gap-3
                ">
                    <x-application-logo class="h-10 w-10" />

                    <div>
                        <p
                            class="
                            text-lg
                            font-black
                            tracking-tight
                            text-white
                        ">
                            OmniMerge
                        </p>

                        <p
                            class="
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-[0.2em]
                            text-slate-500
                        ">
                            Centro
                        </p>
                    </div>
                </a>


                {{-- NAVEGACIÓN ESCRITORIO --}}
                <nav
                    class="
                    hidden
                    items-center
                    gap-2
                    lg:flex
                ">
                    <a href="{{ route('hub') }}"
                        class="
                        rounded-xl
                        bg-white/10
                        px-4
                        py-2.5
                        text-sm
                        font-bold
                        text-white
                    ">
                        Centro
                    </a>

                    <a href="{{ route('dashboard') }}"
                        class="
                        rounded-xl
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-slate-400
                        transition
                        hover:bg-white/5
                        hover:text-white
                    ">
                        Biblioteca
                    </a>

                    <a href="{{ route('community.index') }}"
                        class="
                        rounded-xl
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-slate-400
                        transition
                        hover:bg-white/5
                        hover:text-white
                    ">
                        Comunidad
                    </a>

                    <a href="{{ route('home') }}"
                        class="
                        rounded-xl
                        px-4
                        py-2.5
                        text-sm
                        font-semibold
                        text-slate-400
                        transition
                        hover:bg-white/5
                        hover:text-white
                    ">
                        Inicio público
                    </a>
                </nav>


                {{-- USUARIO --}}
                <div class="relative hidden lg:block">

                    <button type="button" @click="userMenu = !userMenu"
                        class="
                        flex
                        items-center
                        gap-3
                        rounded-2xl
                        border
                        border-white/10
                        bg-white/5
                        px-3
                        py-2
                        transition
                        hover:bg-white/10
                    ">

                        <x-user-avatar :user="auth()->user()" size="sm" square />

                        <div class="text-left">

                            <p
                                class="
                                max-w-[140px]
                                truncate
                                text-sm
                                font-bold
                                text-white
                            ">
                                {{ auth()->user()->name }}
                            </p>

                            <p
                                class="
                                max-w-[140px]
                                truncate
                                text-xs
                                text-slate-500
                            ">
                                {{ '@' . auth()->user()->username }}
                            </p>

                        </div>


                        <svg class="
                            h-4
                            w-4
                            text-slate-500
                        "
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>

                    </button>


                    <div x-cloak x-show="userMenu" x-transition @click.outside="userMenu = false"
                        class="
                        absolute
                        right-0
                        mt-3
                        w-64
                        overflow-hidden
                        rounded-2xl
                        border
                        border-white/10
                        bg-slate-900
                        p-2
                        shadow-2xl
                        shadow-black/40
                    ">

                        <div
                            class="
                            border-b
                            border-white/10
                            px-4
                            py-3
                        ">
                            <p
                                class="
                                text-sm
                                font-bold
                                text-white
                            ">
                                {{ auth()->user()->name }}
                            </p>

                            <p
                                class="
                                mt-1
                                truncate
                                text-xs
                                text-slate-500
                            ">
                                {{ auth()->user()->email }}
                            </p>
                        </div>


                        <a href="{{ route('profile.edit') }}"
                            class="
                            mt-2
                            flex
                            items-center
                            gap-3
                            rounded-xl
                            px-4
                            py-3
                            text-sm
                            font-semibold
                            text-slate-300
                            transition
                            hover:bg-white/5
                            hover:text-white
                        ">
                            <span>👤</span>

                            Perfil y cuenta
                        </a>


                        <a href="{{ route('home') }}"
                            class="
                            flex
                            items-center
                            gap-3
                            rounded-xl
                            px-4
                            py-3
                            text-sm
                            font-semibold
                            text-slate-300
                            transition
                            hover:bg-white/5
                            hover:text-white
                        ">
                            <span>⌂</span>

                            Página pública
                        </a>


                        <form method="POST" action="{{ route('logout') }}"
                            class="
                            mt-2
                            border-t
                            border-white/10
                            pt-2
                        ">
                            @csrf

                            <button type="submit"
                                class="
                                flex
                                w-full
                                items-center
                                gap-3
                                rounded-xl
                                px-4
                                py-3
                                text-left
                                text-sm
                                font-semibold
                                text-red-400
                                transition
                                hover:bg-red-500/10
                            ">
                                <span>↪</span>

                                Cerrar sesión
                            </button>
                        </form>

                    </div>
                </div>


                {{-- MENÚ MÓVIL --}}
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


            {{-- MENÚ MÓVIL ABIERTO --}}
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

                    <a href="{{ route('hub') }}"
                        class="
                        block
                        rounded-xl
                        bg-indigo-500/10
                        px-4
                        py-3
                        text-sm
                        font-bold
                        text-indigo-300
                    ">
                        🏠 Centro OmniMerge
                    </a>

                    <a href="{{ route('dashboard') }}"
                        class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        font-semibold
                        text-slate-300
                        hover:bg-white/5
                    ">
                        📚 Biblioteca
                    </a>

                    <a href="{{ route('community.index') }}"
                        class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        font-semibold
                        text-slate-300
                        hover:bg-white/5
                    ">
                        🌐 Comunidad
                    </a>

                    <a href="{{ route('profile.edit') }}"
                        class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        font-semibold
                        text-slate-300
                        hover:bg-white/5
                    ">
                        👤 Perfil y cuenta
                    </a>

                    <a href="{{ route('home') }}"
                        class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        font-semibold
                        text-slate-300
                        hover:bg-white/5
                    ">
                        ⌂ Inicio público
                    </a>


                    <form method="POST" action="{{ route('logout') }}"
                        class="
                        mt-4
                        border-t
                        border-white/10
                        pt-4
                    ">
                        @csrf

                        <button type="submit"
                            class="
                            w-full
                            rounded-xl
                            bg-red-500/10
                            px-4
                            py-3
                            text-left
                            text-sm
                            font-bold
                            text-red-400
                        ">
                            Cerrar sesión
                        </button>
                    </form>

                </div>
            </div>
        </header>


        {{-- ========================================================= --}}
        {{-- CONTENIDO --}}
        {{-- ========================================================= --}}

        <main>
            @yield('content')
        </main>


        {{-- ========================================================= --}}
        {{-- FOOTER --}}
        {{-- ========================================================= --}}

        <footer class="
            border-t
            border-white/10
            py-8
        ">
            <div
                class="
                mx-auto
                flex
                max-w-7xl
                flex-col
                justify-between
                gap-3
                px-5
                text-xs
                text-slate-600
                sm:flex-row
                sm:px-6
                lg:px-8
            ">
                <p>
                    © {{ date('Y') }} OmniMerge
                </p>

                <p>
                    Create · Connect · Evolve
                </p>
            </div>
        </footer>

    </div>

    <x-omni-confirm-modal />

</body>

</html>
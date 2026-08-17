<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="theme-color" content="#020617">

    <title>
        {{ config('app.name', 'OmniMerge') }}
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
        text-slate-900
        antialiased
    ">

    <div class="
        relative
        min-h-screen
        overflow-hidden
    ">

        {{-- LUCES DE FONDO --}}
        <div
            class="
            pointer-events-none
            absolute
            inset-0
            overflow-hidden
        ">
            <div
                class="
                absolute
                -left-32
                top-0
                h-[500px]
                w-[500px]
                rounded-full
                bg-indigo-600/20
                blur-[140px]
            ">
            </div>

            <div
                class="
                absolute
                bottom-0
                right-0
                h-[500px]
                w-[500px]
                rounded-full
                bg-violet-600/15
                blur-[140px]
            ">
            </div>
        </div>


        <div
            class="
            relative
            grid
            min-h-screen
            lg:grid-cols-[1fr_1fr]
        ">

            {{-- ====================================================== --}}
            {{-- PANEL IZQUIERDO --}}
            {{-- ====================================================== --}}

            <section
                class="
                hidden
                min-h-screen
                flex-col
                justify-between
                border-r
                border-white/10
                p-10
                lg:flex
                xl:p-14
            ">

                <a href="{{ route('home') }}"
                    class="
                    inline-flex
                    items-center
                    gap-3
                    self-start
                ">
                    <x-application-logo class="h-11 w-11" />

                    <div>
                        <p
                            class="
                            text-lg
                            font-black
                            text-white
                        ">
                            OmniMerge
                        </p>

                        <p
                            class="
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-[0.22em]
                            text-slate-500
                        ">
                            Create · Connect · Evolve
                        </p>
                    </div>
                </a>


                <div class="
                    max-w-xl
                    py-10
                ">
                    <div
                        class="
                        inline-flex
                        rounded-full
                        border
                        border-indigo-400/20
                        bg-indigo-500/10
                        px-4
                        py-2
                        text-xs
                        font-bold
                        uppercase
                        tracking-wider
                        text-indigo-300
                    ">
                        Tu universo comienza aquí
                    </div>

                    <h1
                        class="
                        mt-7
                        text-4xl
                        font-black
                        leading-tight
                        tracking-tight
                        text-white
                        xl:text-5xl
                    ">
                        Tus ideas no deberían estar limitadas por un formulario.
                    </h1>

                    <p
                        class="
                        mt-6
                        max-w-lg
                        text-base
                        leading-8
                        text-slate-400
                    ">
                        Crea tus propios tipos, atributos, catálogos,
                        entidades y colecciones. Tú defines la estructura.
                    </p>


                    <div
                        class="
                        mt-10
                        grid
                        gap-4
                        sm:grid-cols-2
                    ">
                        @foreach ([['✦', 'Entidades libres', 'Personajes, lugares, objetos y más.'], ['☷', 'Atributos dinámicos', 'Define tus propias características.'], ['▤', 'Colecciones', 'Organiza y reutiliza contenido.'], ['◎', 'Comunidad', 'Descubre y comparte creaciones.']] as [$icon, $title, $description])
                            <div
                                class="
                                rounded-2xl
                                border
                                border-white/10
                                bg-white/[0.03]
                                p-5
                            ">
                                <span
                                    class="
                                    text-xl
                                    text-indigo-300
                                ">
                                    {{ $icon }}
                                </span>

                                <p
                                    class="
                                    mt-3
                                    text-sm
                                    font-black
                                    text-white
                                ">
                                    {{ $title }}
                                </p>

                                <p
                                    class="
                                    mt-1
                                    text-xs
                                    leading-5
                                    text-slate-500
                                ">
                                    {{ $description }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>


                <div
                    class="
                    flex
                    items-center
                    justify-between
                    text-xs
                    text-slate-600
                ">
                    <span>
                        © {{ date('Y') }} OmniMerge
                    </span>

                    <a href="{{ route('home') }}"
                        class="
                        font-semibold
                        text-slate-500
                        hover:text-white
                    ">
                        ← Volver al inicio
                    </a>
                </div>

            </section>


            {{-- ====================================================== --}}
            {{-- FORMULARIO --}}
            {{-- ====================================================== --}}

            <section
                class="
                flex
                min-h-screen
                items-center
                justify-center
                px-5
                py-10
                sm:px-8
                lg:bg-white
                lg:px-12
            ">

                <div
                    class="
                    w-full
                    max-w-md
                    rounded-[28px]
                    border
                    border-white/10
                    bg-white
                    p-6
                    shadow-2xl
                    shadow-black/20
                    sm:p-8
                    lg:border-0
                    lg:bg-transparent
                    lg:p-0
                    lg:shadow-none
                ">

                    {{-- LOGO MÓVIL --}}
                    <div
                        class="
                        mb-9
                        flex
                        items-center
                        justify-between
                        lg:hidden
                    ">
                        <a href="{{ route('home') }}"
                            class="
                            flex
                            items-center
                            gap-3
                        ">
                            <x-application-logo class="h-10 w-10" />

                            <span
                                class="
                                font-black
                                text-slate-950
                            ">
                                OmniMerge
                            </span>
                        </a>

                        <a href="{{ route('home') }}"
                            class="
                            text-sm
                            font-bold
                            text-slate-500
                        ">
                            Inicio
                        </a>
                    </div>


                    {{ $slot }}

                </div>
            </section>
        </div>
    </div>

    <x-omni-confirm-modal />

</body>

</html>
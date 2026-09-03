<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/joganboruto.jpg') }}">

    <title>
        {{ isset($header) ? $header . ' | ' : '' }}OmniMerge
    </title>


    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>


@php
    /* La ficha de una fase se dibuja en oscuro; el resto del modulo, en claro */
    $dark = ($surface ?? 'light') === 'dark';

    /* Ver el comentario de layouts/app.blade.php */
    $sidebarCompacto = request()->cookie('omni_sidebar') === 'compact';
@endphp

<body class="min-h-screen antialiased {{ $dark ? 'bg-slate-950 text-slate-100' : 'bg-slate-100 text-slate-900' }}">

    <div x-data="omniSidebar({{ $sidebarCompacto ? 'true' : 'false' }})" class="min-h-screen">

        @include('partials.tournaments.sidebar')


        <div :class="{ 'lg:pl-[4.5rem]': compact, 'lg:pl-72': ! compact }"
            class="transition-all duration-300 {{ $sidebarCompacto ? 'lg:pl-[4.5rem]' : 'lg:pl-72' }}">

            @include('partials.tournaments.header', ['dark' => $dark])


            {{--
                En oscuro la pagina respira menos y ocupa mas: lo que se
                ensena ahi son cuadros y tablas que quieren ancho, no una
                columna de lectura centrada.
            --}}
            <main class="{{ $dark ? 'px-3 py-4 sm:px-4 lg:px-6' : 'px-4 py-6 sm:px-6 lg:px-8' }}">

                <div class="mx-auto {{ $dark ? 'max-w-[1600px]' : 'max-w-7xl' }}">

                    <x-alert />


                    {{ $slot }}

                </div>

            </main>

        </div>

    </div>
    <x-omni-confirm-modal />

</body>

</html>

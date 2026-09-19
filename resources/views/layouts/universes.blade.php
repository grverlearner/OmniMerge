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
    /* Ver App\View\Components\UniverseLayout */
    $dark = ($surface ?? 'light') === 'dark';

    /* Ver el comentario de layouts/app.blade.php */
    $sidebarCompacto = request()->cookie('omni_sidebar') === 'compact';
@endphp

<body class="min-h-screen antialiased {{ $dark ? 'bg-slate-950 text-slate-100' : 'bg-slate-100 text-slate-900' }}">

    <div x-data="omniSidebar({{ $sidebarCompacto ? 'true' : 'false' }})" class="min-h-screen">

        {{-- El color del universo, en una línea arriba de todo --}}
        @if ($universe ?? null)
            <div class="pointer-events-none fixed inset-x-0 top-0 z-50 h-0.5"
                style="background: linear-gradient(90deg, transparent, {{ $universe->accent }} 30%, {{ $universe->accent }} 70%, transparent)"></div>
        @endif

        @include('partials.universes.sidebar')


        <div :class="{ 'lg:pl-[4.5rem]': compact, 'lg:pl-72': ! compact }"
            class="transition-all duration-300 {{ $sidebarCompacto ? 'lg:pl-[4.5rem]' : 'lg:pl-72' }}">

            @include('partials.universes.header', ['dark' => $dark])


            {{--
                En oscuro la pagina ocupa mas y respira menos: lo que se
                ensena ahi quiere ancho, no una columna de lectura.
            --}}
            <main class="{{ ($bleed ?? false) ? 'px-2 py-2 sm:px-3' : ($dark ? 'px-3 py-4 sm:px-4 lg:px-6' : 'px-4 py-6 sm:px-6 lg:px-8') }}">

                <div class="mx-auto {{ ($bleed ?? false) ? 'max-w-none' : ($dark ? 'max-w-[1500px]' : 'max-w-7xl') }}">

                    <x-alert :dark="$dark" :contenido="$slot" />


                    {{ $slot }}

                </div>

            </main>

        </div>

    </div>
    <x-omni-confirm-modal />

</body>

</html>

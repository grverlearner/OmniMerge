<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/joganboruto.jpg') }}">

    <title>
        {{ isset($title) ? $title . ' | ' : '' }}OmniMerge
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    /*
     * El margen del contenido tiene que coincidir con el ancho del sidebar,
     * y el sidebar se pliega. Blade lee la misma cookie que Alpine para
     * pintar ya el margen correcto: si no, la pagina nacería ancha y daría
     * un salto en cuanto arrancase Alpine.
     */
    $sidebarCompacto = request()->cookie('omni_sidebar') === 'compact';
@endphp

<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div x-data="omniSidebar({{ $sidebarCompacto ? 'true' : 'false' }})" class="min-h-screen">
        @include('partials.sidebar')

        <div :class="{ 'lg:pl-[4.5rem]': compact, 'lg:pl-72': ! compact }"
            class="transition-all duration-300 {{ $sidebarCompacto ? 'lg:pl-[4.5rem]' : 'lg:pl-72' }}">
            @include('partials.header')

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <x-alert />

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    {{-- ========================================================= --}}
    {{-- OMNIMERGE GLOBAL CONFIRMATION --}}
    {{-- ========================================================= --}}

    <x-omni-confirm-modal />
</body>

</html>

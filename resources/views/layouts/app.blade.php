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

<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        @include('partials.sidebar')

        <div class="lg:pl-72">
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

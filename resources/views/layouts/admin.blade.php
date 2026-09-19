<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-site-head />

    <title>
        {{ $title ? $title . ' | ' : '' }}Administración · {{ $sitio->name() }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>


@php
    /* Ver App\View\Components\AdminLayout */
    $sidebarCompacto = request()->cookie('omni_sidebar') === 'compact';
@endphp

<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">

    <div x-data="omniSidebar({{ $sidebarCompacto ? 'true' : 'false' }})" class="min-h-screen">

        @include('partials.admin.sidebar')

        <div :class="{ 'lg:pl-[4.5rem]': compact, 'lg:pl-72': ! compact }"
            class="transition-all duration-300 {{ $sidebarCompacto ? 'lg:pl-[4.5rem]' : 'lg:pl-72' }}">

            @include('partials.admin.header')

            <main class="px-3 py-4 sm:px-4 lg:px-6">
                <div class="mx-auto max-w-[1600px]">

                    <x-site-announcement class="mb-4 overflow-hidden rounded-xl border" />


                    <x-alert :dark="true" :contenido="$slot" />

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <x-omni-confirm-modal />

</body>

</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/joganboruto.jpg') }}">

    <title>{{ isset($title) ? $title . ' | ' : '' }}OmniMerge</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
         * La arena ocupa la ventana entera: aquí no hay sidebar ni
         * cabecera de módulo. Se juega, no se administra.
         */
        html, body { height: 100%; }

        [x-cloak] { display: none !important; }

    </style>
</head>

<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">

    {{ $slot }}

</body>

</html>

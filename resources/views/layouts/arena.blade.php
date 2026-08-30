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


    {{-- ========================================================= --}}
    {{-- CONFIRMACIÓN GLOBAL --}}
    {{-- ========================================================= --}}

    {{--
        Faltaba aquí, y solo aquí.

        La arena y la Super Edición viven en este layout, y sin el modal en
        la página el interceptor detiene el envío del formulario y lanza un
        evento que no escucha nadie: pulsar la × de una salida no hacía
        absolutamente nada. Con confirm() del navegador no se notaba —el
        cuadro del sistema no necesita estar en el DOM— y por eso el hueco
        pasó desapercibido hasta que se sustituyó.
    --}}

    <x-omni-confirm-modal />

</body>

</html>

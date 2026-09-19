@extends('layouts.hub')

@section('title', 'Centro')

@section('content')

    @php
        /*
         * El Centro de OmniMerge: la puerta de entrada a todo.
         *
         * Se lee de arriba abajo como una jornada: quién eres y qué puedes
         * crear ahora mismo; qué te espera; los cuatro módulos como portales
         * con sus imágenes; lo que se está jugando y quién ganó lo último;
         * tus mundos; lo último que tocaste; y lo que se mira en la comunidad.
         *
         * Ver docs/md/74-Centro-OmniMerge.md
         */

        $usuario = auth()->user();
        $cuentaVacia = $statistics['total'] === 0;
    @endphp

    <div class="mx-auto max-w-[1500px] space-y-8 px-4 py-6 sm:px-6">

        @include('hub.partials.portada')

        @include('hub.partials.buscador')

        @if ($cuentaVacia)

            @include('hub.partials.primeros-pasos')

            @include('hub.partials.modulos')

        @else

            @include('hub.partials.atencion')

            @include('hub.partials.modulos')

            @include('hub.partials.en-juego')

            @include('hub.partials.mundos')

            @include('hub.partials.reciente')

        @endif

        @include('hub.partials.comunidad')
    </div>

@endsection

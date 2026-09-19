@php
    /*
     * El inicio público: lo que ve alguien que todavía no tiene cuenta.
     *
     * Lo que había contaba un producto más pequeño del que existe. Toda la
     * página hablaba de la biblioteca, y al final una sección llamada «Futuro»
     * anunciaba cuatro cosas «próximamente»: Universos, Torneos, Simulaciones y
     * Rankings. Tres de las cuatro llevan tiempo hechas y en uso —mundos con su
     * calendario, torneos con sus fases y recorridos, clasificación con su
     * sistema de puntos configurable—. La página se estaba vendiendo por debajo
     * de lo que es.
     *
     * Y llevaba cuarenta y seis emojis y glifos sueltos (🍥, 🐉, ✦, ☷, ◈…) que
     * cambian de forma según el sistema operativo y no heredan el color del
     * texto. Todos pasan al juego de iconos de la aplicación.
     *
     * Una decisión deliberada: aquí NO se enseña contenido real de usuarios.
     * La comunidad vive detrás del login, así que marcar algo como «público»
     * hoy significa «visible para quien tenga cuenta». Sacarlo a una página
     * anónima ampliaría esa exposición sin haberlo preguntado.
     *
     * Ver docs/md/75-Inicio-Publico.md
     */

    /* Los cuatro modulos que existen de verdad */
    $modulos = [
        [
            'Biblioteca',
            'libro',
            '#818cf8',
            'Lo que existe',
            'Creas entidades de cualquier cosa —un personaje, un país, una criatura— y las describes con los atributos que tú definas. Nada viene con categorías puestas.',
            ['Entidades de cualquier dominio', 'Atributos y catálogos propios', 'Versiones de una misma entidad', 'Colecciones para agrupar'],
        ],
        [
            'Universos',
            'globo',
            '#a78bfa',
            'Dónde viven',
            'Un mundo con su propia gente, su propio calendario y su propia clasificación. La misma entidad puede ser la número uno en uno y la última en otro: los mundos no se mezclan.',
            ['Temporadas como reloj del mundo', 'Copias independientes de tu biblioteca', 'Trofeos y palmarés', 'Mapa visual de quién es quién'],
        ],
        [
            'Torneos',
            'trofeo',
            '#fbbf24',
            'Cómo compiten',
            'Diseñas la forma de la competición: fases, cómo se cruzan, por dónde se sale y a dónde va cada salida. Después un universo la adopta y la juega cuantas veces quiera.',
            ['Eliminación directa, liga, grupos y suizo', 'Recorridos con bifurcaciones', 'Premios por puesto', 'Repetible temporada tras temporada'],
        ],
        [
            'Comunidad',
            'orbita',
            '#34d399',
            'Con quién',
            'Lo que otros comparten se puede copiar a lo tuyo. La copia es independiente: evoluciona por su cuenta y el original no la toca, pero se recuerda de dónde vino.',
            ['Explorar entidades, atributos y catálogos', 'Copiar con un clic', 'Atribución al creador original', 'Perfil público si quieres'],
        ],
    ];

    /* Los pasos del recorrido, para el diagrama */
    $pasos = [
        ['Entidades', 'lo que existe', '#818cf8'],
        ['Atributos', 'cómo se describen', '#22d3ee'],
        ['Universos', 'dónde viven', '#a78bfa'],
        ['Torneos', 'cómo compiten', '#fbbf24'],
        ['Clasificación', 'quién manda', '#34d399'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $sitio->name() }} — {{ $sitio->get('tagline') ?: 'Crea entidades, dales un mundo y hazlas competir' }}</title>

    <meta name="description"
        content="Crea entidades de cualquier cosa, descríbelas con tus propios atributos, organízalas en mundos con su calendario y hazlas competir en torneos que tú diseñas.">

    <x-site-head />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500 selection:text-white">

    <x-site-announcement />


    {{-- El fondo: tres luces quietas, nada que distraiga --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-indigo-600/15 blur-[130px]"></div>
        <div class="absolute right-0 top-1/3 h-[460px] w-[460px] rounded-full bg-violet-600/10 blur-[130px]"></div>
        <div class="absolute bottom-0 left-1/3 h-[420px] w-[420px] rounded-full bg-fuchsia-600/[0.07] blur-[130px]"></div>
    </div>


    <div class="relative" x-data="{ menuMovil: false }">

        @include('welcome.partials.navbar')

        @include('welcome.partials.hero')

        @include('welcome.partials.modulos')

        @include('welcome.partials.como-funciona')

        @include('welcome.partials.enfrentamiento')

        @include('welcome.partials.que-crear')

        @include('welcome.partials.lo-que-viene')

        @include('welcome.partials.cierre')

        @include('welcome.partials.pie')
    </div>

</body>

</html>

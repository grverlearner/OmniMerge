@php
    /*
     * El layout de quien todavía no ha entrado: entrada, registro y el resto
     * del flujo de contraseña.
     *
     * Lo que había no casaba ni consigo mismo: el panel izquierdo era oscuro y
     * el formulario, blanco. Contaba además el discurso antiguo —«crea tus
     * tipos, atributos y colecciones»— con glifos sueltos (✦ ☷ ▤ ◎), cuando el
     * inicio público ya cuenta el producto entero.
     *
     * Ahora es la continuación directa de ese inicio: mismo fondo, misma marca,
     * mismo arco —crea, dale un mundo, hazlo competir— y el formulario en una
     * tarjeta oscura.
     *
     * Ver docs/md/77-Entrada-Registro.md
     */

    $pasos = [
        ['Entidades', 'lo que existe', 'libro', '#818cf8'],
        ['Atributos', 'cómo se describen', 'controles', '#22d3ee'],
        ['Universos', 'dónde viven', 'globo', '#a78bfa'],
        ['Torneos', 'cómo compiten', 'trofeo', '#fbbf24'],
        ['Clasificación', 'quién manda', 'barras', '#34d399'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">

    <title>{{ isset($titulo) ? $titulo . ' · ' : '' }}OmniMerge</title>

    <link rel="icon" type="image/png" href="{{ asset('images/joganboruto.jpg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500 selection:text-white">

    {{-- Las mismas luces quietas que el inicio público --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-40 -top-40 h-[520px] w-[520px] rounded-full bg-indigo-600/15 blur-[130px]"></div>
        <div class="absolute bottom-0 right-0 h-[480px] w-[480px] rounded-full bg-violet-600/12 blur-[130px]"></div>
    </div>


    <div class="relative grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">

        {{-- ===================================================== --}}
        {{-- LO QUE HAY AL OTRO LADO --}}
        {{-- ===================================================== --}}

        <section class="hidden min-h-screen flex-col justify-between border-r border-white/10 p-10 lg:flex xl:p-14">

            <a href="{{ route('home') }}" class="flex items-center gap-2.5 self-start">
                <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600">
                    <img src="{{ asset('images/joganboruto.jpg') }}" alt="" class="h-full w-full object-cover">
                </span>

                <span class="leading-none">
                    <span class="block text-[16px] font-black tracking-tight text-white">OmniMerge</span>
                    <span class="block text-[8px] font-black uppercase tracking-[0.2em] text-indigo-400/70">
                        Create · Connect · Evolve
                    </span>
                </span>
            </a>


            <div class="max-w-xl py-10">

                <span class="inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.18em] text-indigo-300">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-indigo-400"></span>
                    Crea · organiza · compite
                </span>

                <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-tight text-white xl:text-5xl">
                    Crea lo que quieras.
                    <span class="block bg-gradient-to-r from-indigo-400 via-violet-400 to-fuchsia-400 bg-clip-text text-transparent">
                        Dale un mundo. Hazlo competir.
                    </span>
                </h1>

                <p class="mt-5 max-w-lg text-[15px] leading-relaxed text-slate-400">
                    Entidades de cualquier cosa, descritas con los atributos que tú definas,
                    viviendo en mundos con su propio calendario y enfrentándose en torneos que
                    tú diseñas.
                </p>


                {{-- El recorrido, en vertical --}}
                <ol class="mt-8 space-y-2">
                    @foreach ($pasos as $indice => [$titulo, $ayuda, $icono, $tono])
                        <li class="flex items-center gap-3 rounded-2xl border bg-slate-900/40 px-3 py-2.5"
                            style="border-color: {{ $tono }}2e">

                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                style="background-color: {{ $tono }}1f; color: {{ $tono }}">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-[14px] font-black leading-tight" style="color: {{ $tono }}">
                                    {{ $titulo }}
                                </span>
                                <span class="block text-[11px] text-slate-500">{{ $ayuda }}</span>
                            </span>

                            <span class="shrink-0 font-mono text-[11px] font-black text-slate-700">
                                {{ $indice + 1 }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>


            <div class="flex items-center justify-between text-[12px] text-slate-600">
                <span class="font-mono">© {{ date('Y') }} OmniMerge</span>

                <a href="{{ route('home') }}"
                    class="flex items-center gap-1.5 font-semibold text-slate-500 transition hover:text-white">
                    <x-omni-icon name="flecha-izquierda" size="h-3.5 w-3.5" />
                    Volver al inicio
                </a>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- EL FORMULARIO --}}
        {{-- ===================================================== --}}

        <section class="flex min-h-screen flex-col items-center justify-center px-5 py-10 sm:px-8 lg:px-12">

            {{-- Marca en móvil, donde no hay panel izquierdo --}}
            <div class="mb-8 flex w-full max-w-md items-center justify-between lg:hidden">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600">
                        <img src="{{ asset('images/joganboruto.jpg') }}" alt="" class="h-full w-full object-cover">
                    </span>
                    <span class="text-[15px] font-black tracking-tight text-white">OmniMerge</span>
                </a>

                <a href="{{ route('home') }}"
                    class="flex items-center gap-1.5 text-[12px] font-semibold text-slate-500 transition hover:text-white">
                    <x-omni-icon name="flecha-izquierda" size="h-3.5 w-3.5" />
                    Inicio
                </a>
            </div>

            <div class="w-full max-w-md rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-2xl shadow-indigo-950/40 backdrop-blur sm:p-8">
                {{ $slot }}
            </div>
        </section>
    </div>

    <x-omni-confirm-modal />

</body>

</html>

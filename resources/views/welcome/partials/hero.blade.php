@php
    /*
     * La entrada.
     *
     * El titular de antes -«Crea cualquier cosa. Conecta todo.»- describia solo
     * la biblioteca, que es la mitad del producto. El de ahora cuenta el arco
     * entero en una frase, porque es lo que distingue a esto de un gestor de
     * fichas: las fichas compiten.
     *
     * Debajo, una representacion de la aplicacion. No es una captura y no
     * finge serlo: es un dibujo con la navegacion real -incluidas Universos y
     * Torneos, que en el mockup anterior ni aparecian- y tres tarjetas de
     * ejemplo sin un solo emoji.
     */
@endphp

<section class="relative px-5 pb-10 pt-14 lg:px-8 lg:pt-20">

    <div class="mx-auto max-w-7xl">

        <div class="mx-auto max-w-3xl text-center">

            <span class="inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.18em] text-indigo-300">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-indigo-400"></span>
                Crea · organiza · compite
            </span>

            <h1 class="mt-5 text-4xl font-black leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl">
                Crea lo que quieras.
                <span class="block bg-gradient-to-r from-indigo-400 via-violet-400 to-fuchsia-400 bg-clip-text text-transparent">
                    Dale un mundo. Hazlo competir.
                </span>
            </h1>

            <p class="mx-auto mt-5 max-w-2xl text-[15px] leading-relaxed text-slate-400">
                Personajes, países, criaturas, objetos, conceptos: creas las entidades que
                quieras y las describes con los atributos que tú definas. Después las metes en
                un universo con su propio calendario y las haces competir en torneos que tú
                diseñas, hasta ver <strong class="text-slate-200">quién manda en ese
                mundo</strong>.
            </p>

            <div class="mt-7 flex flex-wrap items-center justify-center gap-2.5">
                @auth
                    <a href="{{ route('hub') }}"
                        class="flex items-center gap-2 rounded-xl bg-indigo-500 px-6 py-3.5 text-[14px] font-black text-white transition hover:bg-indigo-400">
                        <x-omni-icon name="casa" size="h-4 w-4" />
                        Abrir OmniMerge
                    </a>

                    <a href="{{ route('universes.dashboard') }}"
                        class="flex items-center gap-2 rounded-xl border border-white/15 px-6 py-3.5 text-[14px] font-black text-slate-200 transition hover:bg-white/5">
                        <x-omni-icon name="globo" size="h-4 w-4" />
                        Mis universos
                    </a>
                @else
                    <a href="{{ route('register') }}"
                        class="flex items-center gap-2 rounded-xl bg-white px-6 py-3.5 text-[14px] font-black text-slate-950 transition hover:bg-slate-200">
                        Empezar gratis
                        <x-omni-icon name="flecha-derecha" size="h-4 w-4" />
                    </a>

                    <a href="#como-funciona"
                        class="rounded-xl border border-white/15 px-6 py-3.5 text-[14px] font-black text-slate-200 transition hover:bg-white/5">
                        Ver cómo funciona
                    </a>
                @endauth
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-x-5 gap-y-1.5">
                @foreach (['Entidades de cualquier dominio', 'Atributos que defines tú', 'Mundos con su calendario', 'Torneos que diseñas'] as $promesa)
                    <span class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500">
                        <span class="h-1 w-1 rounded-full bg-indigo-400"></span>
                        {{ $promesa }}
                    </span>
                @endforeach
            </div>
        </div>


        {{-- ---------- LA APLICACIÓN, DIBUJADA ---------- --}}

        <div class="mx-auto mt-12 max-w-5xl">

            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-indigo-950/40 backdrop-blur">

                {{-- Barra de ventana --}}
                <div class="flex items-center gap-2 border-b border-white/10 bg-slate-950/60 px-3 py-2">
                    <span class="flex gap-1.5">
                        @foreach (['#fb7185', '#fbbf24', '#34d399'] as $punto)
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $punto }}66"></span>
                        @endforeach
                    </span>

                    <span class="mx-auto rounded-md bg-slate-900 px-3 py-0.5 font-mono text-[10px] text-slate-500">
                        omnimerge · universo anime · clasificación
                    </span>
                </div>

                <div class="grid sm:grid-cols-[180px_minmax(0,1fr)]">

                    {{-- La navegación de verdad --}}
                    <div class="hidden border-r border-white/10 bg-slate-950/40 p-2.5 sm:block">

                        <p class="px-2 pb-1.5 text-[8px] font-black uppercase tracking-[0.18em] text-slate-600">
                            Universo
                        </p>

                        @foreach ([['Resumen', 'cuadricula', false], ['Explorar', 'globo', false], ['Entidades', 'libro', false], ['Temporadas', 'calendario', false], ['Torneos', 'trofeo', false], ['Competiciones', 'espadas', false], ['Clasificación', 'barras', true]] as [$texto, $icono, $activo])
                            <span class="mb-0.5 flex items-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-bold {{ $activo ? 'bg-violet-500/20 text-violet-200' : 'text-slate-500' }}">
                                <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                                {{ $texto }}
                            </span>
                        @endforeach
                    </div>


                    {{-- Un podio, que es lo que esto produce --}}
                    <div class="p-4">

                        <p class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-600">
                            Universo Anime · Clasificación
                        </p>

                        <h3 class="mt-0.5 text-[17px] font-black text-white">Quién manda en este mundo</h3>

                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            @foreach ([['#fbbf24', 1, 126, 3, 'pt-0'], ['#cbd5e1', 2, 102, 2, 'pt-4'], ['#f59e0b', 3, 81, 0, 'pt-7']] as [$tono, $puesto, $puntos, $titulos, $alto])
                                <div class="{{ $alto }}">
                                    <div class="overflow-hidden rounded-xl border bg-slate-900/60"
                                        style="border-color: {{ $tono }}55">

                                        <div class="relative h-16"
                                            style="background: radial-gradient(120% 110% at 50% 0%, {{ $tono }}33, #020617 72%)">

                                            <span class="absolute left-2 top-2 flex h-6 w-6 items-center justify-center rounded-lg font-mono text-[12px] font-black"
                                                style="background-color: {{ $tono }}; color: #020617">{{ $puesto }}</span>

                                            @if ($titulos > 0)
                                                <span class="absolute right-2 top-2 flex items-center gap-1 rounded bg-slate-950/70 px-1.5 py-0.5 font-mono text-[10px] font-black"
                                                    style="color: {{ $tono }}">
                                                    <x-omni-icon name="trofeo" size="h-3 w-3" />
                                                    {{ $titulos }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="p-2">
                                            <span class="block h-2 w-3/4 rounded bg-slate-700/70"></span>

                                            <span class="mt-1.5 block font-mono text-[15px] font-black"
                                                style="color: {{ $tono }}">{{ $puntos }}
                                                <span class="text-[8px] uppercase tracking-wider text-slate-600">pts</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 space-y-1">
                            @foreach ([[4, 81, 62], [5, 80, 61], [6, 78, 59]] as [$puesto, $puntos, $ancho])
                                <div class="flex items-center gap-2 rounded-lg border border-white/5 px-2 py-1.5">
                                    <span class="w-4 text-center font-mono text-[10px] font-black text-slate-600">{{ $puesto }}</span>
                                    <span class="h-6 w-6 shrink-0 rounded-md bg-slate-800"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block h-1.5 w-1/3 rounded bg-slate-700/70"></span>
                                        <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-slate-950">
                                            <span class="block h-full rounded-full bg-violet-500" style="width: {{ $ancho }}%"></span>
                                        </span>
                                    </span>
                                    <span class="font-mono text-[11px] font-black text-violet-300">{{ $puntos }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-2 text-center text-[10px] text-slate-600">
                Una representación de la pantalla de clasificación. Los nombres y las caras se
                han dejado en blanco a propósito: son de quien los crea.
            </p>
        </div>
    </div>
</section>

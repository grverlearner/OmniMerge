@extends('layouts.hub')

@section('title', 'Centro OmniMerge')

@section('content')

    @php
        /*
         * El Centro de OmniMerge: la puerta de entrada.
         *
         * Lo que habia: seis tarjetas de modulo con un emoji, un texto generico y
         * dos promesas caducadas -el saludo prometia universos y torneos «mas
         * adelante», y una tarjeta anunciaba «Rankings y analitica, proximamente»-.
         * Las tres cosas llevan tiempo hechas y en uso.
         *
         * Lo que hay ahora: que contiene cada modulo de verdad, con sus caras y sus
         * numeros, y donde hace falta que entres. Un modulo se reconoce por lo que
         * hay dentro, no por su descripcion.
         *
         * Ver docs/md/74-Centro-OmniMerge.md
         */
    
        $usuario = auth()->user();
    
        $cuentaVacia = $statistics['total'] === 0;
    
        $urgentes = $atencion->where('urgente', true)->count();
    @endphp

    <div class="mx-auto max-w-[1500px] space-y-3 px-3 py-4 sm:px-4 lg:px-6">

        {{-- ===================================================== --}}
        {{-- LA PORTADA --}}
        {{-- ===================================================== --}}

        <section class="relative overflow-hidden rounded-2xl border border-indigo-500/25 bg-slate-900/50">

            @if ($mosaico->isNotEmpty())
                <div class="pointer-events-none absolute inset-0 grid grid-cols-8 opacity-[0.13] sm:grid-cols-12 lg:grid-cols-[repeat(24,minmax(0,1fr))]">
                    @foreach ($mosaico as $cara)
                        <span class="block aspect-square overflow-hidden">
                            <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </span>
                    @endforeach
                </div>

                <div class="pointer-events-none absolute inset-0"
                    style="background: linear-gradient(105deg, #020617 28%, #020617dd 60%, #020617aa 100%)"></div>
            @endif

            <div class="relative flex flex-wrap items-center gap-4 p-4">

                {{-- Quién eres --}}
                <a href="{{ route('profile.edit') }}"
                    class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border-2 border-indigo-500/50 bg-slate-950"
                    title="Tu cuenta">
                    @if ($usuario->avatar_url)
                        <img src="{{ $usuario->avatar_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-[22px] font-black text-indigo-300">
                            {{ $usuario->initials }}
                        </span>
                    @endif
                </a>

                <div class="min-w-0 flex-1">

                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-indigo-400/70">
                        Centro OmniMerge
                    </p>

                    <h1 class="mt-0.5 text-2xl font-black leading-tight tracking-tight text-white">
                        @if ($cuentaVacia)
                            Hola, {{ $usuario->name }}
                        @elseif ($statistics['live'] > 0)
                            Hay {{ $statistics['live'] }}
                            {{ $statistics['live'] === 1 ? 'competición jugándose' : 'competiciones jugándose' }}
                        @elseif ($urgentes > 0)
                            Algo te está esperando
                        @else
                            Todo está al día, {{ $usuario->name }}
                        @endif
                    </h1>

                    <p class="mt-0.5 max-w-2xl text-[11px] leading-relaxed text-slate-400">
                        @if ($cuentaVacia)
                            Aquí se crean entidades, se describen con atributos, se organizan en
                            mundos y se les hace competir. Empieza por lo que quieras.
                        @else
                            {{ $statistics['entities'] }} entidades,
                            {{ $statistics['universes'] }}
                            {{ $statistics['universes'] === 1 ? 'mundo' : 'mundos' }} y
                            {{ $statistics['tournaments'] }}
                            {{ $statistics['tournaments'] === 1 ? 'torneo diseñado' : 'torneos diseñados' }}.
                            @if ($statistics['competitions'] > 0)
                                Se han jugado {{ $statistics['competitions'] }} competiciones.
                            @endif
                        @endif
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-1.5">
                    {{--
                        Lleva al perfil ENTERO, no al de biblioteca: «tu página
                        pública» debe ser la de la persona. Y se enseña aunque
                        el perfil este cerrado, porque el dueño siempre puede
                        mirar la suya -y conviene que vea que pinta tiene-.
                    --}}
                    <a href="{{ route('profiles.show', $usuario->username) }}"
                        class="flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                        <x-omni-icon name="usuario" size="h-3.5 w-3.5" />
                        Tu perfil
                        @unless ($usuario->isPublicProfile())
                            <span class="rounded bg-amber-500/20 px-1 text-[8px] font-black uppercase tracking-wider text-amber-300">
                                privado
                            </span>
                        @endunless
                    </a>

                    <a href="{{ route('profile.edit') }}"
                        class="rounded-xl border border-slate-700 bg-slate-950/70 p-2 text-slate-400 transition hover:border-indigo-500 hover:text-indigo-300"
                        title="Ajustes de tu cuenta">
                        <x-omni-icon name="engranaje" size="h-4 w-4" />
                    </a>
                </div>
            </div>
        </section>


        @if ($cuentaVacia)

            @include('hub.partials.primeros-pasos')

        @else

            {{-- ===================================================== --}}
            {{-- LO QUE ESPERA POR TI --}}
            {{-- ===================================================== --}}

            @include('hub.partials.atencion')


            {{-- ===================================================== --}}
            {{-- LOS CUATRO MÓDULOS --}}
            {{-- ===================================================== --}}

            @include('hub.partials.modulos')


            {{-- ===================================================== --}}
            {{-- LO ÚLTIMO QUE HAS TOCADO --}}
            {{-- ===================================================== --}}

            @include('hub.partials.reciente')
        @endif
    </div>

@endsection

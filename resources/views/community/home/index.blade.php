@php
    /*
     * La portada de la Comunidad.
     *
     * Las dos mitades de lo que se comparte —la Biblioteca y los Torneos— en
     * una sola vista: por dónde entrar, qué acaba de salir, qué se copia,
     * quién lo hace y qué parte de todo eso es tuya.
     *
     * Ver docs/md/78-Comunidad.md
     */

    $totalPublicado = array_sum($cifras);
    $ladoBiblioteca = $cifras['entidad'] + $cifras['coleccion'] + $cifras['atributo'];
    $ladoTorneos = $cifras['torneo'] + $cifras['fase'];
@endphp

<x-community-layout title="Comunidad" surface="dark">

    <x-slot name="header">Inicio</x-slot>

    <div class="space-y-3">

        {{-- ===================================================== --}}
        {{-- PORTADA: LAS CARAS DE LA COMUNIDAD Y EL BUSCADOR --}}
        {{-- ===================================================== --}}

        <section class="relative rounded-3xl border border-emerald-500/25 bg-slate-900">

            <div class="absolute inset-0 overflow-hidden rounded-3xl">
                @if ($mosaico->isNotEmpty())
                    <div class="grid h-full grid-cols-8 opacity-40 sm:grid-cols-16" style="grid-auto-rows: 1fr">
                        @foreach ($mosaico->concat($mosaico)->concat($mosaico)->concat($mosaico)->take(32) as $cara)
                            <span class="block overflow-hidden">
                                <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            </span>
                        @endforeach
                    </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-950/85 to-emerald-950/60"></div>
            </div>

            <div class="relative px-4 py-6 sm:px-8 sm:py-8">
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-emerald-400">OmniMerge · Comunidad</p>

                <h1 class="mt-2 max-w-3xl text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl">
                    Lo que otros han creado, listo para mirarlo o llevártelo
                </h1>

                <p class="mt-2 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                    Entidades, colecciones y atributos de la Biblioteca; plantillas de torneo y de fase de Torneos.
                    Todo lo que alguien decidió publicar, y la gente que lo hizo.
                </p>

                <div class="mt-5 max-w-3xl">
                    @include('community.home.partials.buscador')
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ([[$ladoBiblioteca, 'en la biblioteca', '#818cf8', route('community.index')], [$ladoTorneos, 'plantillas de torneo', '#fbbf24', route('tournaments.community.index')], [$creadores->count(), $creadores->count() === 1 ? 'creador' : 'creadores', '#34d399', route('community.creators.index')], [$copiasTotales, 'copias hechas', '#f472b6', null]] as [$valor, $texto, $tono, $destino])
                        @php $cifra = '<span class="font-mono text-xl font-black" style="color: ' . ($valor > 0 ? $tono : '#475569') . '">' . $valor . '</span><span class="text-[11px] font-bold text-slate-400">' . e($texto) . '</span>'; @endphp

                        @if ($destino)
                            <a href="{{ $destino }}" class="flex items-baseline gap-2 rounded-xl border bg-slate-950/70 px-3 py-2 backdrop-blur transition hover:-translate-y-0.5"
                                style="border-color: {{ $tono }}40">{!! $cifra !!}</a>
                        @else
                            <span class="flex items-baseline gap-2 rounded-xl border bg-slate-950/70 px-3 py-2 backdrop-blur"
                                style="border-color: {{ $tono }}40">{!! $cifra !!}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- POR DÓNDE ENTRAR --}}
        {{-- ===================================================== --}}

        @include('community.home.partials.puertas')


        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_380px]">

            @include('community.home.partials.recientes')

            <div class="space-y-3">
                @include('community.home.partials.huella')
                @include('community.home.partials.copiados')
            </div>
        </div>


        @include('community.home.partials.creadores')
    </div>

</x-community-layout>

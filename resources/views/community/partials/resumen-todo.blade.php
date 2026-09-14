@php
    /*
     * La pestaña «Todo».
     *
     * No es una lista más: es un escaparate. De cada clase se enseña un puñado y
     * un enlace a su pestaña, porque mezclar entidades, colecciones, atributos y
     * valores en una sola rejilla no deja comparar nada.
     */

    $bloques = [
        [
            'clave' => 'entities',
            'titulo' => 'Entidades',
            'pie' => 'Personajes, países, lo que sea. Se copian con sus atributos rellenados.',
            'icono' => 'chispa',
            'tono' => '#a78bfa',
            'items' => $allResults['entities'] ?? collect(),
        ],
        [
            'clave' => 'collections',
            'titulo' => 'Colecciones',
            'pie' => 'Montones de entidades ya agrupadas. Copiarlas se lleva lo que tienen dentro.',
            'icono' => 'capas',
            'tono' => '#22d3ee',
            'items' => $allResults['collections'] ?? collect(),
        ],
        [
            'clave' => 'attributes',
            'titulo' => 'Atributos',
            'pie' => 'Los datos con los que se describen las entidades, con sus catálogos.',
            'icono' => 'controles',
            'tono' => '#34d399',
            'items' => $allResults['attributes'] ?? collect(),
        ],
        [
            'clave' => 'catalogs',
            'titulo' => 'Catálogos',
            'pie' => 'Valores sueltos que se pueden llevar uno a uno.',
            'icono' => 'cuadricula',
            'tono' => '#fbbf24',
            'items' => $allResults['catalogs'] ?? collect(),
        ],
    ];

    $creadoresDestacados = $allResults['creators'] ?? collect();
@endphp

<div class="space-y-4">

    @foreach ($bloques as $bloque)
        @continue($bloque['items']->isEmpty())

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    style="background-color: {{ $bloque['tono'] }}22; color: {{ $bloque['tono'] }}">
                    <x-omni-icon :name="$bloque['icono']" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">{{ $bloque['titulo'] }}</h2>
                    <p class="text-[10px] leading-relaxed text-slate-500">{{ $bloque['pie'] }}</p>
                </div>

                <a href="{{ route('community.index', array_merge($comunes, ['tab' => $bloque['clave']])) }}"
                    class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:text-white"
                    style="border-color: {{ $bloque['tono'] }}40">
                    Ver {{ $statistics[$bloque['clave']] }} →
                </a>
            </div>

            <div class="grid gap-2.5 p-4 {{ in_array($bloque['clave'], ['collections', 'attributes'], true) ? 'sm:grid-cols-2 lg:grid-cols-3' : 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-6' }}">
                @foreach ($bloque['items'] as $item)
                    @if ($bloque['clave'] === 'entities')
                        @include('community.partials.tarjeta-entidad', ['entidad' => $item])
                    @elseif ($bloque['clave'] === 'collections')
                        @include('community.partials.tarjeta-coleccion', ['coleccion' => $item])
                    @elseif ($bloque['clave'] === 'attributes')
                        @include('community.partials.tarjeta-atributo', ['atributo' => $item])
                    @else
                        @include('community.partials.tarjeta-catalogo', ['valor' => $item])
                    @endif
                @endforeach
            </div>
        </section>
    @endforeach


    @if ($creadoresDestacados->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                    <x-omni-icon name="usuario" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Creadores</h2>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Quién está haciendo cosas. Entrar en su perfil es más rápido que buscar sus piezas
                        una a una.
                    </p>
                </div>

                <a href="{{ route('community.index', array_merge($comunes, ['tab' => 'creators'])) }}"
                    class="shrink-0 rounded-xl border border-rose-500/30 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:text-white">
                    Ver {{ $statistics['creators'] }} →
                </a>
            </div>

            <div class="grid gap-2.5 p-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($creadoresDestacados as $unCreador)
                    @include('community.partials.tarjeta-creador', ['creador' => $unCreador])
                @endforeach
            </div>
        </section>
    @endif


    @if (collect($bloques)->every(fn($b) => $b['items']->isEmpty()) && $creadoresDestacados->isEmpty())
        @include('community.partials.vacio', ['que' => 'nada'])
    @endif

</div>

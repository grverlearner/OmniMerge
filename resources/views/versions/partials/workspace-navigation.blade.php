@php
    /*
     * La navegación del taller de versiones.
     *
     * Cuatro pantallas, cuatro palabras. La explicación de cada una vivía
     * aquí debajo del nombre y hacía la barra el doble de alta en todas las
     * pantallas: una barra de navegación se lee cien veces y solo hace falta
     * entenderla una. Lo que cada pestaña significa se explica dentro de su
     * propia pantalla, no en el camino hacia ella.
     *
     * El concepto que sostiene el taller: una DEFINICIÓN («Shippuden») no es
     * la versión de nadie; es el molde que muchas entidades aplican, y cada
     * aplicación es una VERSIÓN DE ENTIDAD.
     */

    $actual = request()->route()?->getName();

    $enDefiniciones = in_array(
        $actual,
        ['versions.index', 'versions.create', 'versions.store', 'versions.show', 'versions.edit', 'versions.update'],
        true,
    );

    $enEntidades =
        $actual === 'versions.entities.index' ||
        str_starts_with((string) $actual, 'entity-versions.') ||
        str_starts_with((string) $actual, 'versions.entities.');

    $pestanas = [
        ['definiciones', $enDefiniciones, route('versions.index'), 'capas', 'Definiciones'],
        ['entidades', $enEntidades, route('versions.entities.index'), 'chispa', 'Aplicadas'],
        ['cobertura', $actual === 'versions.coverage', route('versions.coverage'), 'barras', 'Cobertura'],
        ['multimedia', $actual === 'versions.media', route('versions.media'), 'galeria', 'Imágenes'],
    ];
@endphp

<nav aria-label="Taller de versiones"
    class="flex items-center gap-1 overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/50 p-1.5">

    @foreach ($pestanas as [$clave, $activa, $url, $icono, $titulo])
        <a href="{{ $url }}" @if ($activa) aria-current="page" @endif
            class="group flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-[12px] font-black transition
                {{ $activa
                    ? 'bg-violet-500/15 text-violet-200 ring-1 ring-inset ring-violet-500/40'
                    : 'text-slate-500 hover:bg-slate-950 hover:text-slate-200' }}">

            <x-omni-icon :name="$icono" size="h-4 w-4" />
            {{ $titulo }}
        </a>
    @endforeach

</nav>

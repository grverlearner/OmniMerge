@php
    /*
     * La cara de un atributo o de un valor de catálogo.
     *
     * Los dos tienen imagen, símbolo y color, así que la misma pieza sirve para
     * los dos y la pantalla de estructura no repite este marcado doce veces.
     *
     *   $cosa     el atributo o el valor
     *   $tamano   clases de tamaño, por ejemplo «h-9 w-9»
     *   $respaldo el glifo que se pinta cuando no hay ni imagen ni símbolo
     *   $tono     color de acento; si no se pasa, el suyo propio
     */

    $tamano = $tamano ?? 'h-9 w-9';

    $respaldo = $respaldo ?? '◇';

    $tono = $tono ?? ($cosa?->color ?: '#6366f1');

    $simbolo = $cosa?->icon ?: $respaldo;
@endphp

{{--
    `inline-flex` y no un `span` a secas: un elemento en línea ignora el alto y
    el ancho, así que la cara solo se dimensionaba cuando su padre resultaba ser
    un contenedor flex —y se desbordaba en cuanto alguien la envolvía en un
    enlace—. Así mide lo que se le pide esté donde esté.
--}}

{{--
    Y `align-middle` porque una caja en línea se apoya en la línea base del
    texto: sin esto, la fila que la lleva dentro de un enlace queda unos píxeles
    más alta que las demás y la lista se ve descuadrada.
--}}

<span class="{{ $tamano }} inline-flex shrink-0 overflow-hidden rounded-lg border bg-slate-950 align-middle"
    style="border-color: {{ $tono }}55"
    @if ($cosa) title="{{ $cosa->name }}" @endif>

    @if ($cosa?->image_url)
        <img src="{{ $cosa->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
    @else
        <span class="flex h-full w-full items-center justify-center text-[11px]"
            style="color: {{ $tono }}">{{ $simbolo }}</span>
    @endif
</span>

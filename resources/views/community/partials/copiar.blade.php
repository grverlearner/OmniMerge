@php
    /*
     * El botón de copiar algo de la comunidad a tu biblioteca.
     *
     * Tiene cuatro estados y los cuatro importan, porque un botón que se puede
     * pulsar y luego falla es peor que uno que no está:
     *
     *   · es tuyo            → no hay nada que copiar
     *   · ya lo copiaste     → se dice, y se enlaza a tu copia
     *   · su autor no deja   → se dice por qué, en vez de fallar al pulsar
     *   · se puede           → el botón
     *
     * El tercero se apoya en `clones`, que la consulta ya trae filtrado al
     * usuario que mira: si viene algo, es que ya tienes una copia.
     *
     *   $ruta      a dónde se envía
     *   $esMio     si el recurso es del que mira
     *   $miCopia   la copia que ya tenga, o null
     *   $seDeja    si su autor permite copiarlo
     *   $rutaMia   a dónde lleva mi copia, si la hay
     *   $compacto  botón pequeño para las rejillas
     */

    $compacto = $compacto ?? false;

    $clases = $compacto
        ? 'px-2 py-1 text-[10px]'
        : 'px-3 py-2 text-[11px]';
@endphp

@if ($esMio)

    <span class="rounded-lg border border-slate-800 {{ $clases }} font-black text-slate-600"
        title="Es tuyo: ya está en tu biblioteca">
        Tuyo
    </span>

@elseif ($miCopia)

    <a href="{{ $rutaMia }}"
        class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 {{ $clases }} font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-white"
        title="Ya lo copiaste. Esto lleva a tu copia.">
        ✓ Ya lo tienes
    </a>

@elseif (! $seDeja)

    <span class="rounded-lg border border-slate-800 {{ $clases }} font-black text-slate-600"
        title="Su autor no permite copiarlo">
        No se copia
    </span>

@else

    <form method="POST" action="{{ $ruta }}" class="contents">
        @csrf

        <button type="submit"
            class="rounded-lg border border-violet-500/50 bg-violet-500/10 {{ $clases }} font-black text-violet-200 transition hover:bg-violet-500 hover:text-white"
            title="Se copia a tu biblioteca, anotando de quién viene">
            Copiar
        </button>
    </form>

@endif

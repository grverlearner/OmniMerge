@props(['title' => null])

@php
    /*
     * Un grupo de enlaces con su rótulo.
     *
     * Plegado, el rótulo no cabe —y sobra: los iconos de un grupo ya se leen
     * juntos—. En su lugar queda una línea fina que sigue separando los
     * grupos, para que la columna de iconos no se convierta en una lista
     * uniforme donde no se distingue dónde acaba una cosa y empieza otra.
     */
@endphp

<div>
    @if ($title)
        <p x-show="!compact" class="mb-1.5 px-3 text-[10px] font-black uppercase tracking-[0.16em] text-slate-600">
            {{ $title }}
        </p>

        <div x-show="compact" x-cloak class="mx-auto mb-2 h-px w-6 bg-slate-800"></div>
    @endif

    <div class="space-y-1">
        {{ $slot }}
    </div>
</div>

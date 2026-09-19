@props(['type', 'id', 'flags' => null, 'all' => false, 'size' => 'sm', 'wrap' => null])

{{--
    Las marcas de un admin sobre un contenido, como insignias.

    Fuera del espacio de administración solo se enseñan las públicas
    (verificado, confiable, destacado); «oculto» solo lo ve el admin, con
    `all`. Si la lista ya trae sus marcas leídas en bloque, llegan por
    `flags` y no se consulta nada más.
--}}

@php
    $claves = $flags ?? app(\App\Services\Admin\ContentFlags::class)->of($type, (int) $id);

    if (! $all) {
        $claves = array_values(array_intersect($claves, \App\Models\ContentFlag::PUBLIC_FLAGS));
    }
@endphp

@if ($wrap && $claves)
    <span class="{{ $wrap }}">
@endif

@foreach ($claves as $clave)
    @php $meta = \App\Models\ContentFlag::FLAGS[$clave] ?? null; @endphp

    @if ($meta)
        <span title="{{ $meta['label'] }} por el equipo de {{ $sitio->name() }}"
            {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded-full border font-black ' . ($size === 'xs' ? 'px-1.5 py-0.5 text-[9px]' : 'px-2 py-0.5 text-[10px]')]) }}
            style="color: {{ $meta['tone'] }}; border-color: {{ $meta['tone'] }}55; background-color: {{ $meta['tone'] }}14;">
            <x-omni-icon :name="$meta['icon']" size="{{ $size === 'xs' ? 'h-2.5 w-2.5' : 'h-3 w-3' }}" />
            {{ $meta['label'] }}
        </span>
    @endif
@endforeach

@if ($wrap && $claves)
    </span>
@endif

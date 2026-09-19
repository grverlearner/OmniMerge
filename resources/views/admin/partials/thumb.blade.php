{{--
    La cara de un contenido en las listas del admin: su imagen si la tiene,
    y si no, el icono de su tipo sobre su color. Nunca un hueco.
--}}

@php
    $imagen = $modelo->image_url ?? null;
    $clase = $clase ?? 'h-12 w-12 rounded-xl';
@endphp

<span class="relative flex shrink-0 items-center justify-center overflow-hidden border {{ $clase }}"
    style="border-color: {{ $meta['tone'] }}55; background-color: {{ $meta['tone'] }}14; color: {{ $meta['tone'] }};">
    @if ($imagen)
        <img src="{{ $imagen }}" alt="" loading="lazy" class="h-full w-full object-cover">
    @else
        <x-omni-icon :name="$meta['icon']" size="{{ $icono ?? 'h-5 w-5' }}" />
    @endif
</span>

@props(['user' => null, 'size' => 'sm'])

{{--
    La insignia que un admin da a un creador: «verificado» o «confiable».
    Es solo una etiqueta pública, no cambia lo que la cuenta puede hacer.
    Si el creador no tiene ninguna, no se pinta nada.
--}}

@php
    $meta = $user?->creator_badge_meta;
@endphp

@if ($meta)
    <span title="{{ $meta['label'] }}{{ $user->creator_badge_note ? ' · ' . $user->creator_badge_note : '' }}"
        {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded-full border font-black ' . ($size === 'xs' ? 'px-1.5 py-0.5 text-[9px]' : 'px-2 py-0.5 text-[10px]')]) }}
        style="color: {{ $meta['tone'] }}; border-color: {{ $meta['tone'] }}55; background-color: {{ $meta['tone'] }}14;">
        <x-omni-icon :name="$meta['icon']" size="{{ $size === 'xs' ? 'h-2.5 w-2.5' : 'h-3 w-3' }}" />
        {{ $meta['short'] }}
    </span>
@endif

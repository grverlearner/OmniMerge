@props(['user', 'size' => 'md', 'ring' => false, 'square' => false])


@php

    $sizes = [
        'xs' => 'h-7 w-7 text-[10px]',
        'sm' => 'h-9 w-9 text-xs',
        'md' => 'h-11 w-11 text-sm',
        'lg' => 'h-14 w-14 text-base',
        'xl' => 'h-20 w-20 text-xl',
        '2xl' => 'h-28 w-28 text-3xl',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];

    $shapeClass = $square ? 'rounded-2xl' : 'rounded-full';

    $ringClass = $ring ? 'ring-4 ring-white/20' : '';

@endphp


<div
    {{ $attributes->class([
        '
                relative
                shrink-0
                overflow-hidden
                bg-gradient-to-br
                from-indigo-500
                via-violet-500
                to-fuchsia-500
                font-black
                text-white
                shadow-sm
            ',
        $sizeClass,
        $shapeClass,
        $ringClass,
    ]) }}>

    @if ($user->avatar_url)
        <img src="{{ $user->avatar_url }}" alt="Foto de perfil de {{ $user->name }}"
            class="
                h-full
                w-full
                object-cover
            ">
    @else
        <div
            class="
                flex
                h-full
                w-full
                items-center
                justify-center
            ">
            {{ $user->initials }}
        </div>
    @endif

</div>

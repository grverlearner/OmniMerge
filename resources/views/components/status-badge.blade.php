@props(['status'])

@php
    $classes = match ($status) {
        'ACTIVE' =>
            'bg-emerald-100 text-emerald-700',

        'INACTIVE' =>
            'bg-amber-100 text-amber-700',

        'ARCHIVED' =>
            'bg-slate-200 text-slate-600',

        'PUBLIC' =>
            'bg-sky-100 text-sky-700',

        'PRIVATE' =>
            'bg-violet-100 text-violet-700',

        'UNLISTED' =>
            'bg-gray-100 text-gray-700',

        default =>
            'bg-slate-100 text-slate-700',
    };

    $label = match ($status) {
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
        'ARCHIVED' => 'Archivado',
        'PUBLIC' => 'Público',
        'PRIVATE' => 'Privado',
        'UNLISTED' => 'No listado',
        default => $status,
    };
@endphp

<span
    {{ $attributes->merge([
        'class' =>
            "inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {$classes}"
    ]) }}
>
    {{ $label }}
</span>
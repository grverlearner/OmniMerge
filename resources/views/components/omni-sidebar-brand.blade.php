@props([
    'href' => '#',
    'title' => 'OmniMerge',
    'subtitle' => null,
    'meta' => null,
    'back' => null,
    'backLabel' => null,
    'icon' => 'orbita',
    'image' => null,
    'accent' => 'indigo',
    'logo' => false,
])

@php
    /*
     * La identidad del módulo, arriba del sidebar.
     *
     * Plegado queda solo la marca cuadrada —que por eso lleva su propio
     * color y no es un icono suelto: es lo que dice en qué módulo estás
     * cuando no hay texto—. El enlace de vuelta se convierte en una flecha.
     */

    $marcas = [
        'indigo' => 'from-indigo-500 to-violet-600 shadow-indigo-950/40',
        'amber' => 'from-amber-400 to-orange-500 shadow-amber-950/40',
        'violet' => 'from-violet-500 to-indigo-600 shadow-violet-950/40',
    ];

    $vueltas = [
        'indigo' => 'hover:border-indigo-500/40 hover:bg-indigo-500/10 hover:text-indigo-300',
        'amber' => 'hover:border-amber-500/40 hover:bg-amber-500/10 hover:text-amber-300',
        'violet' => 'hover:border-violet-500/40 hover:bg-violet-500/10 hover:text-violet-300',
    ];

    $rotulos = [
        'indigo' => 'text-indigo-400',
        'amber' => 'text-amber-400',
        'violet' => 'text-violet-400',
    ];

    $marca = $marcas[$accent] ?? $marcas['indigo'];

    /* El estado inicial, para que el primer pintado ya sea el correcto */
    $compactoAqui = request()->cookie('omni_sidebar') === 'compact';
@endphp

{{-- VOLVER --}}

@if ($back)
    <a href="{{ $back }}" title="{{ $backLabel }}"
        :class="{ 'lg:justify-center': compact, 'lg:px-0': compact }"
        class="mb-3 flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/70 px-3 py-2 {{ $compactoAqui ? 'lg:justify-center lg:px-0' : '' }} text-[11px] font-bold text-slate-400 transition {{ $vueltas[$accent] ?? $vueltas['indigo'] }}">

        <x-omni-icon name="flecha-izquierda" size="h-4 w-4" />

        <span x-show="!compact" class="truncate">{{ $backLabel }}</span>
    </a>
@endif


{{-- QUIÉN ES --}}

<a href="{{ $href }}" :class="{ 'lg:justify-center': compact }"
    class="flex items-center gap-3 rounded-xl transition hover:opacity-90 {{ $compactoAqui ? 'lg:justify-center' : '' }}">

    <span
        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br text-white shadow-lg {{ $marca }}">

        @if ($image)
            <img src="{{ $image }}" alt="" class="h-full w-full object-cover">
        @elseif ($logo)
            <x-application-logo class="h-6 w-6" />
        @else
            <x-omni-icon :name="$icon" size="h-5 w-5" />
        @endif

    </span>

    <span x-show="!compact" class="min-w-0 flex-1">
        <span class="block truncate text-base font-black tracking-tight text-white">
            {{ $title }}
        </span>

        @if ($subtitle || $meta)
            <span class="mt-0.5 flex items-center gap-2">
                @if ($subtitle)
                    <span
                        class="truncate text-[10px] font-black uppercase tracking-wider {{ $rotulos[$accent] ?? $rotulos['indigo'] }}">
                        {{ $subtitle }}
                    </span>
                @endif

                @if ($subtitle && $meta)
                    <span class="h-1 w-1 shrink-0 rounded-full bg-slate-700"></span>
                @endif

                @if ($meta)
                    <span class="truncate text-[10px] text-slate-500">{{ $meta }}</span>
                @endif
            </span>
        @endif
    </span>

</a>

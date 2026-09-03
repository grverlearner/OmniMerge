@php
    /*
     * El pie del sidebar: quién ha entrado.
     *
     * Plegado queda solo el avatar, centrado, porque una cara ya identifica
     * sin necesidad de leer nada. Los enlaces a los otros módulos se
     * convierten en sus iconos y conservan el globo con su nombre.
     */
    $usuario = auth()->user();

    $compactoAqui = request()->cookie('omni_sidebar') === 'compact';
@endphp

<a href="{{ route('profile.edit') }}" :class="{ 'lg:justify-center': compact, 'lg:px-0': compact }"
    class="group/nav relative flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-900 {{ $compactoAqui ? 'lg:justify-center lg:px-0' : '' }}">

    <x-user-avatar :user="$usuario" size="md" />

    <span x-show="!compact" class="min-w-0 flex-1">
        <span class="block truncate text-[13px] font-bold text-white">
            {{ $usuario->name }}
        </span>

        <span class="block truncate text-[11px] text-slate-500">
            {{ '@' . $usuario->username }}
        </span>
    </span>

    <span x-show="!compact" class="shrink-0 text-slate-600">
        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
    </span>

    <span x-show="compact" x-cloak
        class="pointer-events-none absolute left-full top-1/2 z-50 ml-3 hidden -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-xl transition-opacity duration-150 group-hover/nav:opacity-100 lg:block">
        {{ $usuario->name }}
    </span>
</a>

@if (isset($slot) && trim($slot) !== '')
    <div class="mt-1 space-y-0.5">
        {{ $slot }}
    </div>
@endif

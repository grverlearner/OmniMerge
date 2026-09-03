@props([
    'href' => null,
    'icon' => 'punto',
    'label' => '',
    'badge' => null,
    'active' => false,
    'accent' => 'indigo',
    'muted' => false,
    'note' => null,
])

@php
    /*
     * Un enlace del sidebar.
     *
     * Cuando el sidebar está plegado desaparece el texto y queda el icono
     * solo, así que el nombre tiene que seguir estando a mano: aparece en un
     * globo al pasar por encima. Sin eso, plegar el sidebar convertiría la
     * navegación en una adivinanza.
     *
     * El texto se OCULTA (`x-show`), no se desmonta: plegar y desplegar
     * veinte veces no debería reconstruir el menú veinte veces.
     */

    $activos = [
        'indigo' => 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40',
        'amber' => 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-950/40',
        'violet' => 'bg-violet-500 text-white shadow-lg shadow-violet-950/40',
    ];

    $insignias = [
        'indigo' => 'bg-indigo-500/15 text-indigo-300',
        'amber' => 'bg-amber-500/15 text-amber-300',
        'violet' => 'bg-violet-500/15 text-violet-300',
    ];

    $clase = $active
        ? $activos[$accent] ?? $activos['indigo']
        : ($muted
            ? 'text-slate-600'
            : 'text-slate-300 hover:bg-slate-900 hover:text-white');

    $etiqueta = trim($label) !== '' ? $label : $slot;

    $elemento = $href && !$muted ? 'a' : 'div';

    /* El estado inicial, para que el primer pintado ya sea el correcto */
    $compactoAqui = request()->cookie('omni_sidebar') === 'compact';
@endphp

<{{ $elemento }} @if ($href && !$muted) href="{{ $href }}" @endif
    @if ($active) aria-current="page" @endif
    :class="{ 'lg:justify-center': compact, 'lg:px-0': compact }"
    class="group/nav relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $clase }} {{ $compactoAqui ? 'lg:justify-center lg:px-0' : '' }}">

    <x-omni-icon :name="$icon" size="h-[18px] w-[18px]" />

    <span x-show="!compact" class="min-w-0 flex-1 truncate">
        {{ $etiqueta }}
    </span>

    @if ($badge !== null)
        <span x-show="!compact"
            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-black {{ $active ? 'bg-white/20 text-white' : ($insignias[$accent] ?? $insignias['indigo']) }}">
            {{ $badge }}
        </span>
    @endif

    @if ($note)
        <span x-show="!compact"
            class="shrink-0 rounded-full bg-slate-800 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400">
            {{ $note }}
        </span>
    @endif

    {{--
        El nombre, cuando el sidebar está plegado y el icono no basta.
        Solo en escritorio: en móvil el sidebar nunca está plegado.
    --}}
    <span x-show="compact" x-cloak
        class="pointer-events-none absolute left-full top-1/2 z-50 ml-3 hidden -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-xl transition-opacity duration-150 group-hover/nav:opacity-100 lg:block">
        {{ $etiqueta }}
        @if ($badge !== null)
            <span class="ml-1 text-slate-400">{{ $badge }}</span>
        @endif
    </span>

</{{ $elemento }}>

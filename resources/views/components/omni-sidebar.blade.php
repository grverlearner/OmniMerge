@props([
    'accent' => 'indigo',
    'brand' => null,
    'footer' => null,
])

@php
    /*
     * El armazón del sidebar, uno para los tres módulos.
     *
     * Tiene tres estados y no dos:
     *
     *   desplegado   icono y texto, 18rem
     *   plegado      solo iconos, 4.5rem, con el nombre en un globo al pasar
     *   móvil        fuera de la pantalla; entra deslizándose entero
     *
     * El estado plegado se recuerda en una cookie y no en el navegador a
     * secas porque el ancho del sidebar decide el margen del contenido: si
     * el servidor no lo supiera, la página se pintaría ancha y daría un
     * salto al arrancar Alpine.
     *
     * Los tres módulos comparten este archivo. Lo único que cambia entre
     * ellos es el color, que llega por `accent`, y lo que va dentro.
     */

    $compacto = request()->cookie('omni_sidebar') === 'compact';

    $acentos = [
        'indigo' => ['texto' => 'text-indigo-300', 'borde' => 'hover:border-indigo-500/40', 'fondo' => 'hover:bg-indigo-500/10'],
        'amber' => ['texto' => 'text-amber-300', 'borde' => 'hover:border-amber-500/40', 'fondo' => 'hover:bg-amber-500/10'],
        'violet' => ['texto' => 'text-violet-300', 'borde' => 'hover:border-violet-500/40', 'fondo' => 'hover:bg-violet-500/10'],
    ];

    $tono = $acentos[$accent] ?? $acentos['indigo'];
@endphp

{{-- ============================================================= --}}
{{-- FONDO, SOLO EN MÓVIL --}}
{{-- ============================================================= --}}

<div x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden"></div>


{{-- ============================================================= --}}
{{-- EL SIDEBAR --}}
{{-- ============================================================= --}}

<aside :class="{
    'translate-x-0': sidebarOpen,
    '-translate-x-full': ! sidebarOpen,
    'lg:w-[4.5rem]': compact,
    'lg:w-72': ! compact,
}"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-800/80 bg-slate-950 text-slate-100 transition-all duration-300 lg:translate-x-0 {{ $compacto ? 'lg:w-[4.5rem]' : 'lg:w-72' }}">

    {{-- ========================================================= --}}
    {{-- IDENTIDAD DEL MÓDULO --}}
    {{-- ========================================================= --}}

    <div class="relative border-b border-slate-800/80">

        <div :class="{ 'lg:px-2': compact, 'lg:py-3': compact }"
            class="px-4 py-4 {{ $compacto ? 'lg:px-2 lg:py-3' : '' }}">
            {{ $brand }}
        </div>

        {{--
            El control de plegado.

            Vive en el borde del sidebar, montado sobre la línea que lo separa
            del contenido, porque es lo que hace: mover esa línea. En móvil no
            existe -allí el sidebar entra entero o no entra- y en su lugar
            aparece el aspa para cerrarlo.
        --}}

        <button type="button" @click="toggleCompact()"
            :aria-label="compact ? 'Desplegar el menú' : 'Plegar el menú'"
            :title="compact ? 'Desplegar el menú' : 'Plegar el menú'"
            class="absolute -right-3 top-1/2 hidden h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full border border-slate-700 bg-slate-900 text-slate-400 transition hover:border-slate-500 hover:text-white lg:flex">

            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" x-show="!compact" />
            <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" x-show="compact" x-cloak />
        </button>

        {{-- Y en móvil, cerrar --}}
        <button type="button" @click="sidebarOpen = false" aria-label="Cerrar el menú"
            class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-900 hover:text-white lg:hidden">
            <x-omni-icon name="cerrar" size="h-4 w-4" />
        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- NAVEGACIÓN --}}
    {{-- ========================================================= --}}

    <nav aria-label="Navegación principal" :class="{ 'lg:px-2': compact }"
        class="flex-1 space-y-5 overflow-y-auto overflow-x-hidden px-3 py-5 {{ $compacto ? 'lg:px-2' : '' }}">
        {{ $slot }}
    </nav>


    {{-- ========================================================= --}}
    {{-- PIE --}}
    {{-- ========================================================= --}}

    @if ($footer)
        <div :class="{ 'lg:px-2': compact }"
            class="border-t border-slate-800/80 px-3 py-3 {{ $compacto ? 'lg:px-2' : '' }}">
            {{ $footer }}
        </div>
    @endif

</aside>

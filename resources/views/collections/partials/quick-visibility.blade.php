@php
    /*
     * Cambiar la visibilidad sin salir del índice.
     *
     * Es lo que más se toca de una colección y lo que más caro salía: abrir el
     * formulario de edición entero —con su imagen, su color y su lista de
     * entidades— para mover un desplegable.
     *
     * Cada opción es su propio formulario porque no hay JavaScript de por
     * medio: se pulsa y se envía. La actual sale marcada y deshabilitada.
     */

    $visibilidades = [
        'PUBLIC' => ['Público', 'Cualquiera puede encontrarla en Comunidad.', 'text-emerald-300'],
        'UNLISTED' => ['No listado', 'Solo quien tenga el enlace.', 'text-amber-300'],
        'PRIVATE' => ['Privado', 'Solo tú.', 'text-slate-400'],
    ];
@endphp

<div x-data="{ abierto: false }" class="relative ml-auto">

    <button type="button" @click="abierto = !abierto" @click.outside="abierto = false"
        title="Cambiar quién puede verla"
        class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
        <x-omni-icon name="globo" size="h-3 w-3" />
        {{ $coleccion->visibility_label }}
    </button>

    <div x-show="abierto" x-cloak x-transition
        class="absolute bottom-full right-0 z-30 mb-1 w-56 overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl">

        <p class="border-b border-slate-800 px-3 py-2 text-[9px] font-black uppercase tracking-wider text-slate-600">
            ¿Quién puede verla?
        </p>

        @foreach ($visibilidades as $valor => [$etiqueta, $ayuda, $tono])
            @php $actual = $coleccion->visibility === $valor; @endphp

            <form method="POST" action="{{ route('collections.quick-update', $coleccion) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="field" value="visibility">
                <input type="hidden" name="value" value="{{ $valor }}">

                <button type="submit" @disabled($actual)
                    class="flex w-full items-start gap-2 px-3 py-2 text-left transition {{ $actual ? 'bg-slate-800/60' : 'hover:bg-slate-800' }}">

                    <span class="mt-0.5 shrink-0 text-[10px] {{ $actual ? $tono : 'text-slate-700' }}">
                        {{ $actual ? '●' : '○' }}
                    </span>

                    <span class="min-w-0">
                        <span class="block text-[11px] font-black {{ $actual ? 'text-white' : 'text-slate-300' }}">
                            {{ $etiqueta }}
                        </span>
                        <span class="block text-[9px] leading-3 text-slate-500">{{ $ayuda }}</span>
                    </span>
                </button>
            </form>
        @endforeach

        <div class="border-t border-slate-800">
            <form method="POST" action="{{ route('collections.quick-update', $coleccion) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="field" value="allow_cloning">
                <input type="hidden" name="value" value="{{ $coleccion->allow_cloning ? '0' : '1' }}">

                <button type="submit"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left transition hover:bg-slate-800">
                    <span class="shrink-0 text-[10px] {{ $coleccion->allow_cloning ? 'text-emerald-300' : 'text-slate-700' }}">
                        {{ $coleccion->allow_cloning ? '●' : '○' }}
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[11px] font-black text-slate-300">
                            {{ $coleccion->allow_cloning ? 'Dejar de permitir copias' : 'Permitir que la copien' }}
                        </span>
                        <span class="block text-[9px] leading-3 text-slate-500">
                            Que otros se la lleven a su biblioteca.
                        </span>
                    </span>
                </button>
            </form>
        </div>

    </div>

</div>

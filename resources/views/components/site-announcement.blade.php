{{--
    El anuncio que un admin publica para todos, en la franja de arriba.

    Se puede cerrar, y el cierre se recuerda por el texto: si el admin lo
    cambia, vuelve a aparecer para todos, porque es otro anuncio.
--}}

@php
    $anuncio = $sitio->announcement();
@endphp

@if ($anuncio)
    <div x-data="{
            clave: 'omni.anuncio.' + @js(md5($anuncio['text'] . $anuncio['link'])),
            visible: true,
            init() { try { this.visible = localStorage.getItem(this.clave) !== 'cerrado' } catch (e) {} },
            cerrar() { this.visible = false; try { localStorage.setItem(this.clave, 'cerrado') } catch (e) {} },
        }"
        x-show="visible" role="status"
        {{ $attributes->merge(['class' => 'relative z-40 border-b']) }}
        style="color: {{ $anuncio['color'] }}; border-color: {{ $anuncio['color'] }}55; background-color: color-mix(in srgb, {{ $anuncio['color'] }} 12%, #020617);">
        <div class="mx-auto flex max-w-[1600px] items-center gap-2.5 px-4 py-2 text-xs font-bold sm:px-6">
            <x-omni-icon name="megafono" size="h-4 w-4" />

            <p class="min-w-0 flex-1">
                {{ $anuncio['text'] }}
                @if ($anuncio['link'])
                    <a href="{{ $anuncio['link'] }}" class="ml-1 underline underline-offset-2 hover:opacity-80" target="_blank" rel="noopener">Más información</a>
                @endif
            </p>

            <button type="button" @click="cerrar()" aria-label="Cerrar el anuncio" class="rounded-md p-1 transition hover:bg-white/10">
                <x-omni-icon name="cerrar" size="h-3.5 w-3.5" />
            </button>
        </div>
    </div>
@endif

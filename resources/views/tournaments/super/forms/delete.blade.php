{{--
    Borrar una pieza del grafo.

    Avisa de cuántas rutas se lleva por delante, porque borrar una fase con
    cinco rutas conectadas no es lo mismo que borrar una suelta y desde el
    panel no se ve.
--}}

<form method="POST" :action="{{ $piece }}.delete_url"
    @submit="confirm(
        linksTouching({{ $piece }}.key).length
            ? '{{ $aviso }} Se eliminarán también ' + linksTouching({{ $piece }}.key).length + ' rutas.'
            : '{{ $aviso }}'
    ) || $event.preventDefault()">

    @csrf
    @method('DELETE')

    <button class="w-full rounded-md border border-slate-800 px-2 py-1 text-[9px] font-black text-slate-500 transition hover:border-rose-500 hover:text-rose-400">
        Eliminar
        <template x-if="linksTouching({{ $piece }}.key).length">
            <span>· y <span x-text="linksTouching({{ $piece }}.key).length"></span>
                <span x-text="linksTouching({{ $piece }}.key).length === 1 ? 'ruta' : 'rutas'"></span></span>
        </template>
    </button>

</form>

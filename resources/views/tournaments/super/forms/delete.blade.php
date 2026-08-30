{{--
    Borrar una pieza del grafo.

    Avisa de cuántas rutas se lleva por delante, porque borrar una fase con
    cinco rutas conectadas no es lo mismo que borrar una suelta y desde el
    panel no se ve.

    El aviso lo da el modal de OmniMerge, no el confirm() del navegador: el
    detalle va enlazado porque el número de rutas sale del estado de Alpine
    y solo se sabe en el momento de pulsar.
--}}

<form method="POST" :action="{{ $piece }}.delete_url"
    data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
    data-confirm-title="Eliminar del recorrido"
    data-confirm-message="{{ $aviso }}"
    :data-confirm-subject="{{ $piece }}.name ?? {{ $piece }}.label ?? ''"
    :data-confirm-detail="linksTouching({{ $piece }}.key).length
        ? 'Se eliminarán también ' + linksTouching({{ $piece }}.key).length
            + (linksTouching({{ $piece }}.key).length === 1 ? ' ruta conectada.' : ' rutas conectadas.')
        : 'No hay ninguna ruta conectada a esta pieza.'"
    data-confirm-action="Sí, eliminar">

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

@php
    /*
     * Lo que se puede hacer con una temporada sin abrirla.
     *
     * Activar, darla por terminada o archivarla son un clic y no un formulario,
     * así que estaban escondidos dentro de su ficha sin motivo. Solo se ofrece
     * lo que tiene sentido en su estado: activar una ya terminada no significa
     * nada, y ofrecerlo es prometer algo que no va a pasar.
     */
@endphp

<div class="flex shrink-0 items-center gap-0.5">

    <a href="{{ route('universes.seasons.show', [$universe, $temporada]) }}"
        class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
        Ver
    </a>

    @if ($puedeEditar)

        <a href="{{ route('universes.seasons.edit', [$universe, $temporada]) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
            ✎
        </a>

        @if ($temporada->status === 'PLANNED')
            <form method="POST" action="{{ route('universes.seasons.activate', [$universe, $temporada]) }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="rounded-lg px-2 py-1 text-[10px] font-black text-emerald-400 transition hover:text-emerald-300"
                    title="Ponerla en curso. La que estuviera activa deja de estarlo.">
                    Activar
                </button>
            </form>
        @endif

        @if ($temporada->status === 'ACTIVE')
            <form method="POST" action="{{ route('universes.seasons.complete', [$universe, $temporada]) }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="rounded-lg px-2 py-1 text-[10px] font-black text-cyan-400 transition hover:text-cyan-300"
                    title="Darla por terminada">
                    Terminar
                </button>
            </form>
        @endif

        @if ($temporada->status !== 'ARCHIVED')
            <form method="POST" action="{{ route('universes.seasons.archive', [$universe, $temporada]) }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                    class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-600 transition hover:text-slate-300"
                    title="Sacarla de en medio sin borrar nada">
                    Archivar
                </button>
            </form>
        @endif

    @endif
</div>

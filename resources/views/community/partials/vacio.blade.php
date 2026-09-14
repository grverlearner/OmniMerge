@php
    /*
     * Cuando no hay nada que enseñar.
     *
     * Distingue los dos motivos, porque no son el mismo problema: si hay filtros
     * puestos, lo que falla es la búsqueda; si no los hay, es que la comunidad
     * todavía no tiene nada de eso, y entonces lo útil es invitar a publicar.
     */
@endphp

<section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

    <span class="inline-flex text-slate-700"><x-omni-icon name="globo" size="h-9 w-9" /></span>

    <p class="mt-2 text-[13px] font-black text-white">
        {{ $hayFiltros ? 'Nada encaja con lo que has filtrado' : 'Todavía no hay ' . mb_strtolower($que) . ' en la comunidad' }}
    </p>

    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
        @if ($hayFiltros)
            Prueba a quitar algún filtro o a buscar otra cosa.
        @else
            Nada se comparte solo: algo aparece aquí cuando su autor lo marca como público. Puedes ser el
            primero.
        @endif
    </p>

    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
        @if ($hayFiltros)
            <a href="{{ route('community.index', ['tab' => $tab]) }}"
                class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                Quitar filtros
            </a>
        @endif

        <a href="{{ route('entities.index') }}"
            class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
            Ir a mi biblioteca
        </a>
    </div>
</section>

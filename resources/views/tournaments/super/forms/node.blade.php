@php
    /*
     * Alta o edición de una fase dentro del torneo.
     *
     * Al crear se elige QUÉ fase se mete; al editar ya no, porque cambiar la
     * plantilla de un nodo que ya tiene rutas conectadas dejaría esas rutas
     * apuntando a salidas de otra fase. Para eso está borrar y volver a
     * añadir, que es explícito.
     */
    $editando = ($node ?? null) === 'alpine';
@endphp

<form method="POST" class="space-y-1.5 rounded-lg border border-sky-500/30 bg-slate-950/60 p-2"
    @if ($editando)
        :action="node.update_url"
    @else
        action="{{ route('tournaments.graph.nodes.store', $tournamentTemplate) }}"
    @endif>

    @csrf
    @if ($editando) @method('PUT') @endif

    @unless ($editando)
        <label class="block">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Qué fase</span>

            <select name="phase_template_id" required
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                <option value="">— elige una fase —</option>
                @foreach ($availablePhases as $fase)
                    <option value="{{ $fase['id'] }}">
                        {{ $fase['name'] }} · {{ $fase['type_label'] }} · {{ $fase['contract'] }}
                    </option>
                @endforeach
            </select>

            @if ($availablePhases === [])
                <span class="mt-1 block text-[9px] leading-relaxed text-amber-300">
                    No tienes fases activas. Crea una primero.
                </span>
            @endif
        </label>
    @endunless

    <input type="text" name="name" required maxlength="120"
        @if ($editando) :value="node.name" @else value="" @endif
        placeholder="Cómo se llama aquí"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-sky-500 focus:ring-sky-500">

    <p class="text-[9px] leading-relaxed text-slate-600">
        El nombre es de este torneo: la misma fase puede llamarse
        «Cuartos» aquí y «Repesca» en otro sitio.
    </p>

    <input type="text" name="description" maxlength="255"
        @if ($editando) :value="node.description" @endif
        placeholder="Descripción (opcional)"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-300 focus:border-sky-500 focus:ring-sky-500">

    <button class="w-full rounded-md bg-sky-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-sky-500">
        {{ $editando ? 'Guardar fase' : 'Añadir al torneo' }}
    </button>

</form>

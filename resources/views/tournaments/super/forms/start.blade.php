@php
    /* Alta o edición de una entrada al torneo. */
    $editando = ($start ?? null) === 'alpine';
@endphp

<form method="POST" class="space-y-1.5 rounded-lg border border-emerald-500/30 bg-slate-950/60 p-2"
    @if ($editando)
        :action="start.update_url"
    @else
        action="{{ route('tournaments.graph.starts.store', $tournamentTemplate) }}"
    @endif>

    @csrf
    @if ($editando) @method('PUT') @endif

    <input type="text" name="name" required maxlength="120"
        @if ($editando) :value="start.name" @else value="" @endif
        placeholder="Nombre de la entrada"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">

    <div class="grid grid-cols-2 gap-1.5">

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">De dónde salen</span>
            <select name="source_type"
                @if ($editando) x-model="start.source_type" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                @foreach ([
                    'MAIN_POOL' => 'Grupo principal',
                    'SEEDED_POOL' => 'Cabezas de serie',
                    'QUALIFIER_POOL' => 'Clasificados previos',
                    'INVITED_POOL' => 'Invitados',
                    'CUSTOM' => 'Otro',
                ] as $valor => $texto)
                    <option value="{{ $valor }}">{{ $texto }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuántos</span>
            <input type="number" name="expected_participants" min="1" max="512"
                @if ($editando) :value="start.expected_participants" @endif
                placeholder="—"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-center text-[10px] font-bold text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
        </label>

    </div>

    <input type="text" name="description" maxlength="255"
        @if ($editando) :value="start.description" @endif
        placeholder="Descripción (opcional)"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-300 focus:border-emerald-500 focus:ring-emerald-500">

    <button class="w-full rounded-md bg-emerald-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-emerald-500">
        {{ $editando ? 'Guardar entrada' : 'Crear entrada' }}
    </button>

</form>

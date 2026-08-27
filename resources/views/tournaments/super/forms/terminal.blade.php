@php
    /* Alta o edición de un final del recorrido. */
    $editando = ($terminal ?? null) === 'alpine';
@endphp

<form method="POST" class="space-y-1.5 rounded-lg border border-rose-500/30 bg-slate-950/60 p-2"
    @if ($editando)
        :action="terminal.update_url"
    @else
        action="{{ route('tournaments.graph.terminals.store', $tournamentTemplate) }}"
    @endif>

    @csrf
    @if ($editando) @method('PUT') @endif

    <input type="text" name="name" required maxlength="120"
        @if ($editando) :value="terminal.name" @else value="" @endif
        placeholder="Nombre del final"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-rose-500 focus:ring-rose-500">

    <div class="grid grid-cols-2 gap-1.5">

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Qué es</span>
            <select name="terminal_type"
                @if ($editando) x-model="terminal.terminal_type" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-[10px] text-slate-200 focus:border-rose-500 focus:ring-rose-500">
                @foreach ([
                    'CHAMPION' => 'Campeón',
                    'QUALIFIED' => 'Clasificado',
                    'PLACEMENT' => 'Puesto final',
                    'SECONDARY' => 'Cuadro secundario',
                    'ELIMINATED' => 'Eliminado',
                    'CUSTOM' => 'Otro',
                ] as $valor => $texto)
                    <option value="{{ $valor }}">{{ $texto }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuántos caben</span>
            <input type="number" name="expected_participants" min="1" max="512"
                @if ($editando) :value="terminal.expected_participants" @endif
                placeholder="—"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-1 text-center text-[10px] font-bold text-slate-100 focus:border-rose-500 focus:ring-rose-500">
        </label>

    </div>

    <input type="text" name="description" maxlength="255"
        @if ($editando) :value="terminal.description" @endif
        placeholder="Descripción (opcional)"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-300 focus:border-rose-500 focus:ring-rose-500">

    <button class="w-full rounded-md bg-rose-600 px-2 py-1 text-[10px] font-black text-white transition hover:bg-rose-500">
        {{ $editando ? 'Guardar final' : 'Crear final' }}
    </button>

</form>

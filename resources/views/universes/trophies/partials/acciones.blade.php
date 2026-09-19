@php
    /*
     * Lo que se puede hacer con un trofeo.
     *
     * Borrar solo se ofrece cuando nadie lo ha ganado: retirar una copa que ya
     * está en la vitrina de alguien reescribe su historia, y el controlador lo
     * impide. Ofrecer un botón que va a fallar es peor que no ofrecerlo.
     */

    $conquistado = $trofeo->awards_count > 0;
@endphp

<div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

    <button type="button" @click="editando = editando === {{ $trofeo->id }} ? null : {{ $trofeo->id }}"
        class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
        ✎ Editar
    </button>

    @if ($conquistado)
        <span class="ml-auto rounded-lg px-2 py-1 text-[10px] font-black text-slate-700"
            title="No se puede retirar: ya está en la vitrina de alguien">
            Conquistado
        </span>
    @else
        <form method="POST" action="{{ route('universes.trophies.destroy', [$universe, $trofeo]) }}"
            class="ml-auto"
            data-omni-confirm data-confirm-variant="danger" data-confirm-title="Eliminar el trofeo" data-confirm-message="Se elimina de este universo." data-confirm-subject="{{ $trofeo->name }}" data-confirm-detail="No se puede deshacer." data-confirm-action="Sí, eliminarlo">
            @csrf
            @method('DELETE')

            <button type="submit"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-600 transition hover:text-rose-300">
                Eliminar
            </button>
        </form>
    @endif
</div>


{{-- El editor, dentro de la misma tarjeta: no saca de la vitrina --}}
<div x-show="editando === {{ $trofeo->id }}" x-cloak x-collapse
    class="border-t border-slate-800 bg-slate-950/60">

    <form method="POST" action="{{ route('universes.trophies.update', [$universe, $trofeo]) }}"
        enctype="multipart/form-data" class="space-y-2 p-3">
        @csrf
        @method('PUT')

        <label class="block">
            <span class="mb-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">Nombre</span>
            <input type="text" name="name" value="{{ $trofeo->name }}" required maxlength="150"
                class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-[11px] font-bold text-white focus:border-amber-500 focus:ring-amber-500">
        </label>

        <div class="grid grid-cols-2 gap-2">
            <label class="block">
                <span class="mb-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">Nivel</span>
                <select name="tier"
                    class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                    @foreach (App\Models\UniverseTrophy::TIERS as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($trofeo->tier === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">Símbolo</span>
                <input type="text" name="icon" value="{{ $trofeo->icon }}" maxlength="16" placeholder="🏆"
                    class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-center text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
            </label>
        </div>

        <label class="block">
            <span class="mb-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">Qué premia</span>
            <textarea name="description" rows="2" maxlength="500"
                class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">{{ $trofeo->description }}</textarea>
        </label>

        <label class="block cursor-pointer rounded-lg border border-dashed border-slate-700 px-2 py-1.5 text-center transition hover:border-amber-500">
            <input type="file" name="image" accept="image/*" class="sr-only">
            <span class="text-[10px] font-black text-slate-400">
                {{ $trofeo->image_url ? 'Cambiar la imagen' : 'Ponerle imagen' }}
            </span>
        </label>

        <div class="flex items-center gap-1.5">
            <button type="submit"
                class="flex-1 rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                Guardar
            </button>

            <button type="button" @click="editando = null"
                class="rounded-lg border border-slate-800 px-3 py-1.5 text-[11px] font-black text-slate-400 transition hover:text-white">
                Cancelar
            </button>
        </div>
    </form>
</div>

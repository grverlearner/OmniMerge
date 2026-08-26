@php
    /*
     * Alta o edición de un grupo.
     *
     * $group === null       alta
     * $group === 'alpine'   edición, con los datos del `group` del bucle
     *
     * Las vueltas propias son opcionales: en blanco, el grupo usa las de la
     * fase. Guardar aquí un número igual al de la fase solo crearía una
     * copia que habría que mantener sincronizada a mano.
     */

    $editing = ($group ?? null) === 'alpine';
@endphp

<form method="POST"
    @if ($editing)
        :action="@js(route('tournaments.phase-templates.super.groups.update', [$phaseTemplate, '__ID__'])).replace('__ID__', group.definition_id)"
    @else
        action="{{ route('tournaments.phase-templates.super.groups.store', $phaseTemplate) }}"
    @endif
    class="space-y-2">

    @csrf
    @if ($editing) @method('PUT') @endif

    @include('tournaments.phase-templates.super.partials.preview-state')

    <input type="text" name="name" required maxlength="120"
        @if ($editing) :value="group.name" @else value="" @endif
        placeholder="Nombre del grupo"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-amber-500 focus:ring-amber-500">

    <div class="grid grid-cols-2 gap-1.5">

        <label class="block">
            <span class="text-[9px] font-black text-slate-500">Cupo</span>
            <input type="number" name="capacity" min="2" max="64"
                @if ($editing) :value="group.size" @else value="4" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

        <label class="block">
            <span class="text-[9px] font-black text-slate-500">Vueltas</span>
            <input type="number" name="cycles" min="1" max="10" placeholder="—"
                @if ($editing) :value="group.has_custom_cycles ? group.cycles : ''" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

    </div>

    <p class="text-[9px] leading-tight text-slate-600">
        Vueltas en blanco = las de la fase.
    </p>

    <button class="w-full rounded-md bg-amber-500 px-3 py-1 text-[10px] font-black text-slate-950 transition hover:bg-amber-400">
        {{ $editing ? 'Guardar grupo' : 'Crear grupo' }}
    </button>

</form>

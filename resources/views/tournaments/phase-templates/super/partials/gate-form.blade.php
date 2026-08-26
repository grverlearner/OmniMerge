@php
    /*
     * Alta o edición de una puerta de entrada.
     *
     * $gate === null       formulario de alta
     * $gate === 'alpine'   edición, con los datos del `gate` del bucle
     *
     * El truco del segundo caso: el listado se pinta con x-for, así que la
     * puerta concreta solo existe en el ámbito de Alpine. El formulario lee
     * de ahí con x-model, y la acción se compone reemplazando el id.
     *
     * No se pregunta cuánta gente admite: se deduce de los puestos que
     * reclama. Una puerta que se lleva del 1 al 4 admite exactamente 4, y
     * dejar escribir otra cantidad solo permitiría guardar una
     * contradicción.
     */

    $editing = ($gate ?? null) === 'alpine';
@endphp

<form method="POST"
    @if ($editing)
        :action="@js(route('tournaments.phase-templates.super.gates.update', [$phaseTemplate, '__ID__'])).replace('__ID__', gate.id)"
    @else
        action="{{ route('tournaments.phase-templates.super.gates.store', $phaseTemplate) }}"
    @endif
    class="space-y-2"
    x-data="{
        type: @if ($editing) gate.seed_type @else 'FIRST_N' @endif,
        needs(field) {
            return (payload.catalog.seed_rule_types[this.type]?.needs ?? []).includes(field);
        },
    }">

    @csrf
    @if ($editing) @method('PUT') @endif

    <input type="text" name="name" required maxlength="120"
        @if ($editing) :value="gate.name" @else value="" @endif
        placeholder="Nombre de la puerta"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">


    <select name="seed_type" x-model="type"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
        @foreach ($payload['catalog']['seed_rule_types'] as $key => $definition)
            <option value="{{ $key }}">{{ $definition['label'] }}</option>
        @endforeach
    </select>

    <p class="text-[9px] leading-tight text-slate-500"
        x-text="payload.catalog.seed_rule_types[type]?.hint"></p>


    <div class="grid grid-cols-2 gap-1.5">

        <label x-show="needs('count')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">Cuántos</span>
            <input type="number" name="seed_count" min="1" max="512" :disabled="!needs('count')"
                @if ($editing) :value="gate.seed_count ?? 2" @else value="2" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[11px] text-slate-100">
        </label>

        <label x-show="needs('from')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">Desde el puesto</span>
            <input type="number" name="seed_from" min="1" max="512" :disabled="!needs('from')"
                @if ($editing) :value="gate.seed_from ?? 1" @else value="1" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[11px] text-slate-100">
        </label>

        <label x-show="needs('to')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">Hasta el puesto</span>
            <input type="number" name="seed_to" min="1" max="512" :disabled="!needs('to')"
                @if ($editing) :value="gate.seed_to ?? 4" @else value="4" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[11px] text-slate-100">
        </label>

    </div>


    <div class="flex items-center justify-between gap-2">

        <label class="flex cursor-pointer items-center gap-1.5">
            <input type="checkbox" name="is_required" value="1"
                @if ($editing) :checked="gate.is_required" @endif
                class="h-3 w-3 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500">
            <span class="text-[9px] text-slate-500">Obligatoria</span>
        </label>

        <button class="rounded-md bg-emerald-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-emerald-500">
            {{ $editing ? 'Guardar' : 'Crear' }}
        </button>

    </div>

</form>

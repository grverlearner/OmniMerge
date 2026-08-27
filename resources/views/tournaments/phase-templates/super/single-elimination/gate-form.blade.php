@php
    /*
     * Alta o edición de una puerta de entrada.
     *
     * En un cuadro una puerta reclama PUESTOS DEL ÁRBOL. No dice cuánta
     * gente entra —eso lo fija el tamaño del cuadro—: dice por dónde entra,
     * y en una eliminación directa el puesto es todo, porque decide contra
     * quién se abre y con quién no te puedes cruzar hasta el final.
     *
     * La capacidad se deduce de la regla: una puerta que reclama del 1 al 4
     * admite exactamente 4.
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

    @include('tournaments.phase-templates.super.partials.preview-state')

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
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

        <label x-show="needs('from')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">Desde el puesto</span>
            <input type="number" name="seed_from" min="1" max="512" :disabled="!needs('from')"
                @if ($editing) :value="gate.seed_from ?? 1" @else value="1" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

        <label x-show="needs('to')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">Hasta el puesto</span>
            <input type="number" name="seed_to" min="1" max="512" :disabled="!needs('to')"
                @if ($editing) :value="gate.seed_to ?? 4" @else value="4" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

    </div>

    <button class="w-full rounded-md bg-emerald-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-emerald-500">
        {{ $editing ? 'Guardar puerta' : 'Crear puerta' }}
    </button>

</form>

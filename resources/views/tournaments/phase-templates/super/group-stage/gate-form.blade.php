@php
    /*
     * Alta o edición de una puerta de entrada.
     *
     * En una fase de grupos una puerta no dice cuánta gente entra: dice
     * QUÉ TRAMO de los que llegan va a QUÉ GRUPO. Por eso los campos son un
     * rango de entrantes y un grupo destino, y no una capacidad.
     */

    $editing = ($gate ?? null) === 'alpine';
@endphp

<form method="POST"
    @if ($editing)
        :action="@js(route('tournaments.phase-templates.super.gates.update', [$phaseTemplate, '__ID__'])).replace('__ID__', gate.id)"
    @else
        action="{{ route('tournaments.phase-templates.super.gates.store', $phaseTemplate) }}"
    @endif
    class="space-y-2">

    @csrf
    @if ($editing) @method('PUT') @endif

    @include('tournaments.phase-templates.super.partials.preview-state')

    <input type="text" name="name" required maxlength="120"
        @if ($editing) :value="gate.name" @else value="" @endif
        placeholder="Nombre de la puerta"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">

    <div class="grid grid-cols-2 gap-1.5">

        <label class="block">
            <span class="text-[9px] font-black text-slate-500">Del entrante</span>
            <input type="number" name="entry_from" min="1" max="512"
                @if ($editing) :value="gate.entry_from ?? 1" @else value="1" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

        <label class="block">
            <span class="text-[9px] font-black text-slate-500">Al entrante</span>
            <input type="number" name="entry_to" min="1" max="512"
                @if ($editing) :value="gate.entry_to ?? 4" @else value="4" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

    </div>

    <label class="block">
        <span class="text-[9px] font-black text-slate-500">Van al grupo</span>

        <select name="target_group_code"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">— sin destino fijo —</option>

            <template x-for="g in groups" :key="'opt' + g.index">
                <option :value="g.code"
                    @if ($editing) :selected="gate.target_group_code === g.code" @endif
                    x-text="g.name"></option>
            </template>
        </select>
    </label>

    <button class="w-full rounded-md bg-emerald-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-emerald-500">
        {{ $editing ? 'Guardar puerta' : 'Crear puerta' }}
    </button>

</form>

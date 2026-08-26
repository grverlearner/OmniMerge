@php
    /*
     * Alta o edición de una puerta de salida.
     *
     * $exit === null       formulario de alta
     * $exit === 'alpine'   edición, con los datos del `exit` del bucle
     *
     * No se pregunta cuándo se cruza. En una liga no hay eliminación a
     * mitad de fase —todos juegan sus jornadas y la tabla no es firme hasta
     * el final—, así que la única respuesta posible es "al terminar" y el
     * motor rechazaría cualquier otra.
     */

    $editing = ($exit ?? null) === 'alpine';
@endphp

<form method="POST"
    @if ($editing)
        :action="@js(route('tournaments.phase-templates.super.exits.update', [$phaseTemplate, '__ID__'])).replace('__ID__', exit.id)"
    @else
        action="{{ route('tournaments.phase-templates.super.exits.store', $phaseTemplate) }}"
    @endif
    class="space-y-2"
    x-data="{
        type: @if ($editing) exit.selector_type @else 'TOP_N' @endif,
        needs(field) {
            return (payload.catalog.exit_selectors[this.type]?.needs ?? []).includes(field);
        },
    }">

    @csrf
    @if ($editing) @method('PUT') @endif

    <input type="text" name="name" required maxlength="120"
        @if ($editing) :value="exit.name" @else value="" @endif
        placeholder="Nombre de la salida"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-violet-500 focus:ring-violet-500">


    <select name="selector_type" x-model="type"
        class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
        @foreach ($payload['catalog']['exit_selectors'] as $key => $definition)
            <option value="{{ $key }}">{{ $definition['label'] }}</option>
        @endforeach
    </select>

    <p class="text-[9px] leading-tight text-slate-500"
        x-text="payload.catalog.exit_selectors[type]?.hint"></p>


    <div class="grid grid-cols-2 gap-1.5">

        <label x-show="needs('from')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">
                <span x-show="needs('to')">Desde el puesto</span>
                <span x-show="!needs('to')">Cuántos / puesto</span>
            </span>
            <input type="number" name="selector_from" min="1" max="512" :disabled="!needs('from')"
                @if ($editing) :value="exit.selector_from ?? 1" @else value="2" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[11px] text-slate-100">
        </label>

        <label x-show="needs('to')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">Hasta el puesto</span>
            <input type="number" name="selector_to" min="1" max="512" :disabled="!needs('to')"
                @if ($editing) :value="exit.selector_to ?? 4" @else value="4" @endif
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[11px] text-slate-100">
        </label>

    </div>


    <div class="flex items-center justify-between gap-2">

        <label class="flex items-center gap-1.5">
            <span class="text-[9px] font-black text-slate-500">Prioridad</span>
            <input type="number" name="priority" min="1" max="999"
                @if ($editing) :value="exit.priority ?? 10" @else value="10" @endif
                class="w-12 rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-[11px] text-slate-100">
        </label>

        <button class="rounded-md bg-violet-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
            {{ $editing ? 'Guardar' : 'Crear' }}
        </button>

    </div>

</form>

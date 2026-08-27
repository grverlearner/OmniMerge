@php
    /*
     * Alta o edición de una puerta de salida.
     *
     * Los selectores se ofrecen en el idioma de la fase —el campeón, el
     * finalista, el tercero— en vez del idioma del modelo, que habla de
     * puestos abstractos. Debajo se dice a qué puesto corresponde cada uno.
     *
     * La lista NO es siempre la misma: la manda el servidor según cómo
     * termine la fase. Parándose antes de la final no hay campeón ni
     * finalista, y ofrecerlos sería dar a elegir dos puertas que nunca se
     * van a llenar.
     *
     * No se pregunta cuándo se cruza: un cuadro no reparte plazas hasta que
     * termina, porque la clasificación no es firme antes.
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
        /*
         * El primer selector de la lista, no uno fijo: cuál es el primero
         * depende del modo, y fijar 'WINNER' abría el formulario en una
         * opción que en modo supervivientes ni siquiera existe.
         */
        type: @if ($editing) exit.selector_type @else Object.keys(payload.catalog.exit_selectors)[0] @endif,

        from: @if ($editing) (exit.selector_from ?? 1) @else 1 @endif,
        to: @if ($editing) (exit.selector_to ?? 4) @else 4 @endif,
        branch: @if ($editing) (exit.branch ?? 1) @else 1 @endif,

        needs(field) {
            return (payload.catalog.exit_selectors[this.type]?.needs ?? []).includes(field);
        },
    }">

    @csrf
    @if ($editing) @method('PUT') @endif

    @include('tournaments.phase-templates.super.partials.preview-state')

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
                <span x-show="!needs('to')">Puesto / cuántos</span>
            </span>
            <input type="number" name="selector_from" min="1" max="512" x-model.number="from"
                :disabled="!needs('from')"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

        <label x-show="needs('to')" x-cloak class="block">
            <span class="text-[9px] font-black text-slate-500">Hasta el puesto</span>
            <input type="number" name="selector_to" min="1" max="512" x-model.number="to"
                :disabled="!needs('to')"
                class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
        </label>

    </div>

    {{--
        La rama del cuadro.

        Es lo que hace falta cuando la fase termina con varios en pie: cada
        superviviente sale de un trozo distinto del árbol, y poder mandar el
        de arriba a un sitio y el de abajo a otro es justo lo que un cuadro
        recortado tiene que saber hacer.

        Viaja en `selector_from` porque es un número y ese campo ya existe:
        darle columna propia habría significado una migración para guardar
        un entero donde ya cabía.
    --}}

    <label x-show="needs('branch')" x-cloak class="block">
        <span class="text-[9px] font-black text-slate-500">¿De qué rama?</span>

        <select name="selector_from" x-model.number="branch" :disabled="!needs('branch')"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            <template x-for="option in payload.catalog.branch_options" :key="'bo' + option.value">
                <option :value="option.value" x-text="option.label + ' · ' + option.hint"></option>
            </template>
        </select>

        <p class="mt-0.5 text-[9px] leading-tight text-slate-600"
            x-show="payload.catalog.branch_options.length === 0">
            Este cuadro no tiene ramas: solo queda uno al final.
        </p>
    </label>

    {{-- Aviso cuando la salida pide un puesto que el cuadro no decide --}}

    <template x-if="blockingGroup(type, from, to)">
        <div class="rounded-md bg-amber-500/10 px-2 py-1">
            <p class="text-[9px] font-bold leading-relaxed text-amber-300">
                Ese puesto lo comparten
                <span x-text="blockingGroup(type, from, to).entrants"></span>:
                nadie decide cuál de ellos es.
            </p>

            <button type="button" @click="togglePlacement(blockingGroup(type, from, to).key)"
                class="mt-1 rounded border border-amber-500/50 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-300 transition hover:bg-amber-500/20">
                Ordenar «<span x-text="blockingGroup(type, from, to).label"></span>»
                · +<span x-text="blockingGroup(type, from, to).cost"></span> duelos
            </button>
        </div>
    </template>

    <label class="flex items-center gap-1.5">
        <span class="text-[9px] font-black text-slate-500">Prioridad</span>
        <input type="number" name="priority" min="1" max="999"
            @if ($editing) :value="exit.priority ?? 10" @else value="10" @endif
            class="w-12 rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-[11px] text-slate-100">
        <span class="text-[9px] leading-tight text-slate-600">
            Si dos salidas se disputan al mismo, gana la del número más bajo.
        </span>
    </label>

    <button class="w-full rounded-md bg-violet-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
        {{ $editing ? 'Guardar salida' : 'Crear salida' }}
    </button>

</form>

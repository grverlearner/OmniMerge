@php
    /*
     * Los campos que describen QUIÉN sale por una puerta.
     *
     * Viven aparte porque hacen falta en tres sitios: al crear la salida, al
     * añadirle otro criterio y al editar uno existente. Antes eran tres
     * copias que se iban separando entre sí.
     *
     * No trae <form>: lo pone quien lo incluye, porque cada sitio manda a
     * una ruta distinta. El estado de Alpine tampoco: lo pone el <form> con
     * exitCriterionFields(), para que el criterio de partida sea el que
     * toque en cada caso.
     *
     * $families   criterios agrupados por familia, con las claves intactas
     * $rule       'alpine' cuando se edita uno del bucle, o null
     */

    $editing = ($rule ?? null) === 'alpine';
@endphp

<select name="rule_type" x-model="type"
    class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
    @foreach ($families as $family => $types)
        <optgroup label="{{ $family }}">
            @foreach ($types as $key => $definition)
                <option value="{{ $key }}">{{ $definition['label'] }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>

@foreach ($payload['catalog']['rule_types'] as $key => $definition)
    <p x-show="type === @js($key)" x-cloak class="text-[9px] leading-tight text-slate-500">
        {{ $definition['description'] }}
    </p>
@endforeach


<div class="grid grid-cols-2 gap-1.5">

    <label x-show="usesTake" x-cloak class="block">
        <span class="text-[9px] font-black text-slate-500">
            <span x-show="perGroup">De cada grupo</span>
            <span x-show="!perGroup">En total</span>
        </span>
        <input type="number" name="take" min="1" max="512" x-model.number="take"
            :disabled="!usesTake"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
    </label>

    <label x-show="usesFrom" x-cloak class="block">
        <span class="text-[9px] font-black text-slate-500">
            <span x-show="usesTo">Desde</span>
            <span x-show="!usesTo">Puesto</span>
        </span>
        <input type="number" name="position_from" min="1" max="512" x-model.number="from"
            :disabled="!usesFrom"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
    </label>

    <label x-show="usesTo" x-cloak class="block">
        <span class="text-[9px] font-black text-slate-500">Hasta</span>
        <input type="number" name="position_to" min="1" max="512" x-model.number="to"
            :disabled="!usesTo"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
    </label>

    <label x-show="usesGroup" x-cloak class="block">
        <span class="text-[9px] font-black text-slate-500">Del grupo</span>
        <select name="phase_group_stage_group_id" :disabled="!usesGroup"
            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-[11px] text-slate-100">
            <template x-for="g in groups.filter(g => g.definition_id)" :key="'xg' + g.index">
                <option :value="g.definition_id" :selected="g.definition_id === groupId" x-text="g.name"></option>
            </template>
        </select>
    </label>

</div>


{{-- Cuánta gente saca de verdad --}}

<template x-if="reach">
    <p class="rounded-md px-2 py-1 text-[9px] font-bold leading-relaxed"
        :class="reach.everyone ? 'bg-rose-500/10 text-rose-300' : 'bg-violet-500/10 text-violet-300'">
        <template x-if="reach.perGroup">
            <span>
                <span x-text="take"></span> de cada uno de los
                <span x-text="groups.length"></span> grupos =
                <strong x-text="reach.total"></strong> de <span x-text="castSize"></span>.
            </span>
        </template>

        <template x-if="!reach.perGroup">
            <span>
                Aquí la cantidad es el total:
                <strong x-text="reach.total"></strong> de <span x-text="castSize"></span>.
            </span>
        </template>

        <span x-show="reach.everyone" x-cloak class="block font-black">
            Pasan todos: no eliminaría a nadie.
        </span>
    </p>
</template>

@php
    /*
     * El editor del valor de una característica, según su tipo.
     *
     * Existe una sola vez porque en esta pantalla hace falta en TRES sitios
     * —el valor común del lote, el valor que se aplica en masa a las filas
     * seleccionadas, y la celda de cada fila— y los tres tienen que ofrecer
     * exactamente lo mismo. Tenerlo escrito tres veces garantizaba que un día
     * uno de ellos se quedara sin soportar un tipo.
     *
     * Recibe dos expresiones de JavaScript, no valores:
     *
     *   $model   dónde vive el valor          p. ej. commonValues[attribute.id]
     *   $name    cómo se llama al enviarse    p. ej. `common_attributes[${attribute.id}]`
     *            (null cuando no debe enviarse, como en la acción masiva)
     *
     * `attribute` tiene que existir en el ámbito de Alpine donde se incluya.
     */

    $name = $name ?? null;

    $sinValor = $sinValor ?? 'Sin valor';

    $clase =
        'w-full rounded-lg border-slate-800 bg-slate-950 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500';
@endphp

{{-- Catálogo, un solo valor --}}
<template x-if="attribute.data_type === 'OPTION' && ! attribute.allows_multiple">
    <select x-model="{{ $model }}" @if ($name) :name="{{ $name }}" @endif class="{{ $clase }}">
        <option value="">{{ $sinValor }}</option>

        <template x-for="option in attribute.options" :key="option.id">
            <option :value="option.id" x-text="option.name"></option>
        </template>
    </select>
</template>

{{-- Catálogo, varios valores --}}
<template x-if="attribute.data_type === 'OPTION' && attribute.allows_multiple">
    <select multiple x-model="{{ $model }}" @if ($name) :name="{{ $name }}" @endif
        class="{{ $clase }} min-h-20">
        <template x-for="option in attribute.options" :key="option.id">
            <option :value="option.id" x-text="option.name"></option>
        </template>
    </select>
</template>

{{-- Sí o no --}}
<template x-if="attribute.data_type === 'BOOLEAN'">
    <select x-model="{{ $model }}" @if ($name) :name="{{ $name }}" @endif class="{{ $clase }}">
        <option value="">{{ $sinValor }}</option>
        <option value="1">Sí</option>
        <option value="0">No</option>
    </select>
</template>

{{-- Número --}}
<template x-if="attribute.data_type === 'INTEGER' || attribute.data_type === 'DECIMAL'">
    <input type="number" :step="attribute.data_type === 'DECIMAL' ? '0.01' : '1'" x-model="{{ $model }}"
        @if ($name) :name="{{ $name }}" @endif placeholder="—" class="{{ $clase }}">
</template>

{{-- Fecha --}}
<template x-if="attribute.data_type === 'DATE'">
    <input type="date" x-model="{{ $model }}" @if ($name) :name="{{ $name }}" @endif class="{{ $clase }}">
</template>

{{-- Color: el cuadrado y el código, porque escribir un hex a ciegas no es elegir un color --}}
<template x-if="attribute.data_type === 'COLOR'">
    <span class="flex items-center gap-1.5">
        <input type="color" x-model="{{ $model }}"
            class="h-8 w-9 shrink-0 cursor-pointer rounded-lg border border-slate-800 bg-slate-950 p-0.5">

        <input type="text" x-model="{{ $model }}" @if ($name) :name="{{ $name }}" @endif placeholder="#000000"
            class="{{ $clase }} font-mono">
    </span>
</template>

{{-- Texto largo --}}
<template x-if="attribute.data_type === 'LONG_TEXT'">
    <textarea rows="2" x-model="{{ $model }}" @if ($name) :name="{{ $name }}" @endif placeholder="—"
        class="{{ $clase }}"></textarea>
</template>

{{-- Texto --}}
<template x-if="attribute.data_type === 'TEXT'">
    <input type="text" x-model="{{ $model }}" @if ($name) :name="{{ $name }}" @endif
        :placeholder="attribute.placeholder || '—'" class="{{ $clase }}">
</template>

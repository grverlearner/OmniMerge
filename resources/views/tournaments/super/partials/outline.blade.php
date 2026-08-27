@php
    /*
     * La forma de una fase, en pequeño.
     *
     * No es la fase: es su silueta. Lo justo para reconocer de un vistazo si
     * ese nodo del torneo es un cuadro que va estrechándose, una tabla donde
     * juegan todos contra todos, o un puñado de grupos en paralelo.
     *
     * Se dibuja con los números que da `outline()` —cuatro enteros— y no
     * montando la fase entera: enseñar la forma de cinco fases en un mapa no
     * puede costar cinco estructuras completas.
     *
     * Los motores que todavía no saben dibujarse (Swiss, League) no traen
     * esquema, y entonces no se dibuja nada. Una caja vacía dice la verdad;
     * una forma prestada, no.
     *
     * Espera en el ámbito: $piece (la variable Alpine con la pieza).
     */
@endphp

<template x-if="outlineOf({{ $piece }}.key)">
    <div class="flex items-end gap-0.5" :title="outlineOf({{ $piece }}.key).label">

        {{-- Un cuadro: cada ronda tiene la mitad de duelos que la anterior --}}

        <template x-if="outlineOf({{ $piece }}.key).kind === 'BRACKET'">
            <div class="flex items-end gap-[2px]">
                <template x-for="(matches, i) in outlineOf({{ $piece }}.key).columns" :key="'ob' + i">
                    <span class="w-[3px] rounded-sm"
                        :class="colorOf({{ $piece }}.key).solid"
                        :style="'height: ' + Math.max(4, Math.min(18, matches * 2 + 4)) + 'px; opacity: ' + (0.4 + i * 0.15)"></span>
                </template>
            </div>
        </template>

        {{-- Grupos: una caja por grupo, con sus huecos dentro --}}

        <template x-if="outlineOf({{ $piece }}.key).kind === 'GROUPS'">
            <div class="flex flex-wrap items-end gap-[2px]" style="max-width: 62px">
                <template x-for="(size, i) in outlineOf({{ $piece }}.key).columns" :key="'og' + i">
                    <span class="h-[7px] w-[7px] rounded-[2px] border"
                        :class="colorOf({{ $piece }}.key).border + ' ' + colorOf({{ $piece }}.key).soft"></span>
                </template>
            </div>
        </template>

        {{-- Una tabla: filas apiladas --}}

        <template x-if="outlineOf({{ $piece }}.key).kind === 'TABLE'">
            <div class="flex flex-col gap-[2px]">
                <template x-for="i in Math.min(5, outlineOf({{ $piece }}.key).columns[0] ?? 0)" :key="'ot' + i">
                    <span class="h-[2px] w-[16px] rounded-sm"
                        :class="colorOf({{ $piece }}.key).solid"
                        :style="'opacity: ' + (1 - i * 0.13)"></span>
                </template>
            </div>
        </template>

        <span class="ml-1 font-mono text-[8px] text-slate-500"
            x-text="outlineOf({{ $piece }}.key).label"></span>

    </div>
</template>

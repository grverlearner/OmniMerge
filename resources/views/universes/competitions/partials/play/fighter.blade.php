{{--
    Un contendiente en la pantalla de batalla.

    Se usa dentro de un x-for cuya variable se llama `side`, así que todo
    aquí es Alpine. $align solo decide de qué lado se alinea el contenido.
--}}

@php
    $items = $align === 'right' ? 'lg:items-end' : 'lg:items-start';
    $justify = $align === 'right' ? 'lg:justify-end' : 'lg:justify-start';
    $portrait = $align === 'right' ? 'lg:ml-auto' : 'lg:mr-auto';
@endphp

<template x-if="side">
    <div class="flex flex-col items-center {{ $items }}">

        {{-- RETRATO --}}

        <div class="mx-auto h-44 w-44 overflow-hidden rounded-3xl border-4 bg-slate-800 shadow-2xl transition xl:h-52 xl:w-52 {{ $portrait }}"
            :class="side.is_winner
                ? 'border-emerald-400 shadow-emerald-900/50'
                : 'border-slate-800'">

            <template x-if="side.image">
                <img :src="side.image" :alt="side.name" class="h-full w-full object-cover">
            </template>

            <template x-if="!side.image">
                <div class="flex h-full w-full items-center justify-center text-5xl opacity-25">✦</div>
            </template>

        </div>


        {{-- NOMBRE --}}

        <h3 class="mt-4 text-2xl font-black leading-tight text-white xl:text-3xl" x-text="side.name"></h3>


        {{-- CAPACIDADES DEL JUEGO --}}

        <div class="mt-3 flex flex-wrap justify-center gap-2 {{ $justify }}">
            <template x-for="stat in side.stats" :key="stat.label">
                <span class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1">
                    <span class="text-[9px] font-bold uppercase tracking-wider text-emerald-500/80"
                        x-text="stat.label"></span>
                    <span class="ml-1.5 font-mono text-xs font-black text-emerald-300" x-text="stat.value"></span>
                </span>
            </template>
        </div>


        {{-- CATÁLOGO Y ATRIBUTOS, EN MINIATURA --}}

        <template x-if="side.attributes && side.attributes.length">
            <div class="mt-3 flex flex-wrap justify-center gap-1.5 {{ $justify }}">

                <template x-for="attribute in side.attributes" :key="attribute.name + attribute.display">
                    <span class="group/attr relative flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-900/70 py-1 pl-1 pr-2">

                        {{-- Miniatura del catálogo, si la opción tiene --}}
                        <template x-if="attribute.image">
                            <img :src="attribute.image" :alt="attribute.display"
                                class="h-6 w-6 shrink-0 rounded object-cover">
                        </template>

                        <template x-if="!attribute.image && attribute.icon">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-800 text-xs"
                                x-text="attribute.icon"></span>
                        </template>

                        <template x-if="!attribute.image && !attribute.icon">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-800 text-[9px] font-black text-slate-500"
                                x-text="attribute.display.substring(0, 2).toUpperCase()"></span>
                        </template>

                        <span class="text-[10px] font-bold capitalize text-slate-300" x-text="attribute.display"></span>

                        {{-- De qué atributo es --}}
                        <span class="pointer-events-none absolute -top-6 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-slate-950 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-slate-400 shadow-lg group-hover/attr:block"
                            x-text="attribute.name"></span>

                    </span>
                </template>

            </div>
        </template>


        {{-- PALMARÉS --}}

        <template x-if="side.trophies > 0">
            <div class="mt-3 flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1">
                <span class="text-sm">🏆</span>
                <span class="text-[10px] font-black text-amber-300">
                    <span x-text="side.trophies"></span> en su vitrina
                </span>
            </div>
        </template>

    </div>
</template>

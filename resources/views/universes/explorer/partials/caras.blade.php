@php
    /*
     * Una cara del mapa.
     *
     * Es la unica pieza que se dibuja en los cuatro modos, asi que vive aqui
     * una sola vez y los modos la incluyen con la lista que toque.
     *
     * Lo importante es el borde: cuando una entidad esta en mas de un cuadro
     * a la vez -dos aldeas, dos animes- el borde deja de ser un color y pasa
     * a ser todos sus colores repartidos en circulo. Es la forma de ver «este
     * pertenece a dos sitios» sin escribirlo.
     *
     *   $lista     expresion JS con la coleccion a recorrer
     *   $etiqueta  si se escribe el nombre debajo (por defecto, segun tamaño)
     */
    $etiqueta = $etiqueta ?? 'auto';
@endphp

<template x-for="e in {{ $lista }}" :key="e.id">

    <button type="button" @click="elegida = e"
        class="group relative block text-left transition"
        :class="apagada(e) ? 'opacity-20 grayscale' : 'hover:z-10'"
        :title="e.nombre">

        {{-- El aro de colores: uno solo, o todos los suyos repartidos --}}
        <span class="block rounded-xl p-[2px] transition"
            :style="`background: ${aro(e)}`">

            <span class="block overflow-hidden rounded-[10px] bg-slate-950"
                :style="`width: ${lado}px; height: ${lado}px`">

                <template x-if="e.img">
                    <img :src="e.img" alt="" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-110">
                </template>

                <template x-if="! e.img">
                    <span class="flex h-full w-full items-center justify-center text-slate-700"
                        :style="`font-size: ${Math.round(lado / 2.4)}px`">◍</span>
                </template>
            </span>
        </span>

        {{-- Lo que ha hecho, solo si lo ha hecho --}}
        <template x-if="e.titulos > 0">
            <span class="absolute -right-1 -top-1 flex items-center justify-center rounded-full bg-amber-400 px-1 font-mono text-[9px] font-black text-slate-950"
                :title="`${e.titulos} título${e.titulos === 1 ? '' : 's'}`"
                x-text="e.titulos"></span>
        </template>

        {{-- En cuantos cuadros esta, cuando esta en mas de uno --}}
        <template x-if="cuantosCuadros(e) > 1">
            <span class="absolute -left-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full border border-slate-950 bg-slate-800 font-mono text-[8px] font-black text-slate-200"
                :title="`Está en ${cuantosCuadros(e)} cuadros a la vez`"
                x-text="cuantosCuadros(e)"></span>
        </template>

        @if ($etiqueta !== 'nunca')
            <template x-if="lado >= 56">
                <span class="mt-1 block max-w-full truncate text-center text-[9px] font-bold text-slate-500 group-hover:text-slate-200"
                    :style="`width: ${lado}px`"
                    x-text="e.nombre"></span>
            </template>
        @endif
    </button>
</template>

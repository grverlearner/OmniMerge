@php
    /*
     * SUPER EDICIÓN — armazón
     *
     * La pantalla completa donde se edita una fase. El armazón no sabe de
     * Round Robin: reparte la ventana en zonas y encaja en dos de ellas las
     * vistas que le da el editor del motor. Cuando exista el de Eliminación
     * Directa, este archivo no se toca.
     *
     * Reparto de la ventana:
     *
     *   cabecera   identidad de la fase y estado — se edita aquí
     *   izquierda  configuración        (hueco del motor)
     *   centro     estructura           (hueco del motor)
     *   derecha    puertas de entrada y salida
     *   abajo      jornadas y enfrentamientos
     *
     * Cada zona hace su propio scroll: la pantalla nunca crece, y por eso
     * la cabecera con el botón de guardar siempre está a la vista.
     *
     * En pantallas estrechas las tres columnas se apilan. Se hace cambiando
     * la DIRECCIÓN del contenedor y no repitiendo los paneles: incluirlos
     * dos veces metía en el DOM dos formularios de alta de puerta, dos
     * estados de Alpine y dos copias de cada control, con el bonito efecto
     * de que editar en uno no se veía en el otro.
     */
@endphp

<x-arena-layout>

    <x-slot name="title">Super Edición · {{ $phaseTemplate->name }}</x-slot>

    <div class="flex h-screen flex-col overflow-hidden"
        x-data="phaseSuperEditor({
            payload: @js($payload),
            previewUrl: @js(route('tournaments.phase-templates.super.preview', $phaseTemplate)),
        })">

        @include('tournaments.phase-templates.super.partials.header')


        {{-- TRES ZONAS: en columna si no caben, en fila si caben --}}

        <div class="arena-scroll flex min-h-0 flex-1 flex-col overflow-y-auto lg:flex-row lg:overflow-hidden">

            <aside class="arena-scroll shrink-0 border-b border-slate-800 bg-slate-900/40 lg:w-[248px] lg:overflow-y-auto lg:border-b-0 lg:border-r">
                @include($configView)
            </aside>

            <main class="arena-scroll min-w-0 flex-1 lg:overflow-y-auto">
                @include($stageView)
            </main>

            <aside class="arena-scroll shrink-0 border-t border-slate-800 bg-slate-900/40 lg:w-[300px] lg:overflow-y-auto lg:border-l lg:border-t-0">
                @include('tournaments.phase-templates.super.partials.gates')
            </aside>

        </div>


        {{-- JORNADAS --}}

        @include('tournaments.phase-templates.super.partials.schedule')

    </div>

</x-arena-layout>

@php
    /*
     * SUPER EDICIÓN DE TORNEO — armazón
     *
     * Pantalla completa, con el mismo reparto que la Super Edición de una
     * fase, porque es la misma clase de trabajo: configurar a la izquierda,
     * ver el resultado en el centro, y que el centro reaccione.
     *
     *   cabecera   identidad del torneo — se edita aquí
     *   izquierda  los bloques: inicios, fases, terminales y conexiones
     *   centro     tres vistas — el mapa, el recorrido y el taller
     *   abajo      diagnóstico del grafo
     *
     * El panel de la izquierda se pliega, y se pliega solo al entrar al
     * taller: ahí lo que se edita está en el centro y el panel le quitaba
     * un tercio de ancho justo cuando más falta hace.
     *
     * Cada zona hace su propio scroll: la pantalla nunca crece y el botón de
     * guardar siempre está a la vista.
     *
     * ---------------------------------------------------------------
     *
     * El CRUD del grafo NO vive aquí. Crear una fase, un inicio, un terminal
     * o una conexión son rutas que ya existían y que responden con `back()`,
     * así que sus formularios funcionan desde esta pantalla sin que se haya
     * tocado ni un controlador del grafo.
     */
@endphp

<x-arena-layout>

    <x-slot name="title">Super Edición · {{ $tournamentTemplate->name }}</x-slot>

    <div class="flex h-screen flex-col overflow-hidden"
        x-data="tournamentSuperEditor({
            payload: @js($payload),
            previewUrl: @js(route('tournaments.super.preview', $tournamentTemplate)),
        })">

        @include('tournaments.super.partials.header')


        <div class="arena-scroll flex min-h-0 flex-1 flex-col overflow-y-auto lg:flex-row lg:overflow-hidden">

            {{--
                El panel se pliega. En el taller estorba: lo que se edita
                está en el centro y el panel le quita un tercio de ancho
                justo cuando más falta hace.
            --}}
            <aside x-show="panelOpen" x-cloak
                class="arena-scroll shrink-0 border-b border-slate-800 bg-slate-900/40 lg:w-[300px] lg:overflow-y-auto lg:border-b-0 lg:border-r">
                @include('tournaments.super.partials.blocks')
            </aside>

            <main class="arena-scroll min-w-0 flex-1 lg:overflow-y-auto">

                {{-- El selector de vista --}}

                <div class="sticky top-0 z-10 flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/95 px-3 py-2 backdrop-blur">

                    {{-- Plegar el panel de la izquierda --}}

                    <button type="button" @click="togglePanel()"
                        class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100"
                        :title="panelOpen ? 'Ocultar el panel' : 'Mostrar el panel'"
                        x-text="panelOpen ? '⇤' : '⇥'"></button>

                    <div class="flex rounded-lg border border-slate-800 bg-slate-900 p-0.5">
                        <button type="button" @click="setView('MAP')"
                            class="rounded-md px-3 py-1 text-[10px] font-black uppercase tracking-wider transition"
                            :class="view === 'MAP'
                                ? 'bg-amber-500 text-slate-950'
                                : 'text-slate-400 hover:text-slate-100'">
                            ⛯ El mapa
                        </button>

                        <button type="button" @click="setView('PATH')"
                            class="rounded-md px-3 py-1 text-[10px] font-black uppercase tracking-wider transition"
                            :class="view === 'PATH'
                                ? 'bg-amber-500 text-slate-950'
                                : 'text-slate-400 hover:text-slate-100'">
                            ⇄ El recorrido
                        </button>

                        <button type="button" @click="setView('EDIT')"
                            class="rounded-md px-3 py-1 text-[10px] font-black uppercase tracking-wider transition"
                            :class="view === 'EDIT'
                                ? 'bg-amber-500 text-slate-950'
                                : 'text-slate-400 hover:text-slate-100'">
                            ✎ El taller
                        </button>
                    </div>

                    <p class="text-[9px] leading-tight text-slate-600"
                        x-text="{
                            MAP: 'El torneo entero, de principio a fin.',
                            PATH: 'Una fase, con lo que tiene antes y después.',
                            EDIT: 'Lo mismo, pero editable puerta por puerta.',
                        }[view]"></p>

                    {{--
                        El selector de fase vivía aquí, en una esquina.
                        Ahora está dentro de la estructura, donde se usa:
                        ver partials/phase-nav.
                    --}}

                </div>

                <div x-show="view === 'MAP'">
                    @include('tournaments.super.partials.map')
                </div>

                <div x-show="view === 'PATH'" x-cloak>
                    @include('tournaments.super.partials.path')
                </div>

                <div x-show="view === 'EDIT'" x-cloak>
                    @include('tournaments.super.partials.workshop')
                </div>

            </main>

        </div>


        @include('tournaments.super.partials.diagnostics')

    </div>

</x-arena-layout>

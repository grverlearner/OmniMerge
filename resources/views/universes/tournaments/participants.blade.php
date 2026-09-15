@php
    /*
     * LA SALA DE PARTICIPANTES
     *
     * Quién entra en este torneo, por qué puerta de su recorrido y con qué
     * cara. A pantalla completa porque es un sitio para mirar: el catálogo
     * a un lado, las caras al otro, y cada decisión mueve las caras en el
     * mismo clic.
     *
     *   panel izquierdo   quién entra · puertas · caras
     *   escenario         dentro y fuera · por puerta · por valor · lista
     *   ficha             un competidor: por qué está, su puerta, su cara
     *
     * Todo se calcula en el navegador con las mismas reglas que el servidor
     * (resources/js/universes/participant-room.js) y se guarda en el torneo:
     * lo heredan sus ediciones nuevas. Cada edición tiene la suya dentro de
     * su diseñador, con las mismas piezas.
     *
     * Ver docs/md/79-Sala-De-Participantes.md
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark" :bleed="true">

    <x-slot name="header">Participantes · {{ $torneo->name }}</x-slot>

    <div x-data="participantRoom(@js($sala))" class="space-y-2">

        @include('universes.tournaments.partials.sala.cabecera')

        @if ($errors->has('design'))
            <p class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-[11px] font-bold text-rose-200">
                {{ $errors->first('design') }}
            </p>
        @endif

        <div class="grid items-start gap-2 xl:grid-cols-[400px_minmax(0,1fr)]">

            <aside class="space-y-2 xl:sticky xl:top-24 xl:max-h-[calc(100vh-6.5rem)] xl:overflow-y-auto xl:pr-1">
                @include('universes.tournaments.partials.sala.pestanas')
                @include('universes.tournaments.partials.sala.panel-quien')
                @include('universes.tournaments.partials.sala.panel-puertas')
                @include('universes.tournaments.partials.sala.panel-caras')
            </aside>

            @include('universes.tournaments.partials.sala.escenario')
        </div>

        @include('universes.tournaments.partials.sala.ficha')
    </div>

</x-universe-layout>

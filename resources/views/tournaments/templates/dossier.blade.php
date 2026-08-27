@php
    /*
     * LA FICHA DE UN TORNEO
     *
     * Lo que ves al abrir una plantilla de torneo. Es el torneo PRESENTADO,
     * no editado: aquí no hay ni un botón de guardar.
     *
     * Se lee de arriba abajo como una respuesta:
     *
     *   qué es          portada, cifras, estado del recorrido
     *   qué camino tiene la estructura, con sus colores y etiquetas
     *   qué pasaría     el simulador
     *
     * ---------------------------------------------------------------
     *
     * Comparte el payload con la Super Edición —el mismo grafo, los mismos
     * niveles, las mismas siluetas de fase— porque es la misma información
     * leída para otra cosa. Lo único que sobra son los botones, y esos no
     * están en el payload sino en la vista.
     *
     * El simulador tampoco tiene motor propio: lo ejecuta en el servidor
     * TournamentFlowPreviewService, que ya recorría el grafo repartiendo
     * participantes hasta que llegan a un terminal o se pierden.
     */
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">{{ $tournamentTemplate->name }}</x-slot>

    <div x-data="tournamentDossier({
            payload: @js($payload),
            simulateUrl: @js(route('tournaments.templates.simulate', $tournamentTemplate)),
            csrf: @js(csrf_token()),
        })">

        @include('tournaments.templates.dossier.cover')

        @include('tournaments.templates.dossier.structure')

        @include('tournaments.templates.dossier.simulator')

    </div>

</x-tournament-layout>

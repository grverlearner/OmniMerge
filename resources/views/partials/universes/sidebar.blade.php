@php
    /*
     * El sidebar de Universos es contextual: dentro de un Universo concreto
     * enseña su navegación interna; fuera, la del módulo. El armazón es el
     * mismo de los otros dos (ver partials/sidebar.blade.php).
     */

    $universoActual = $universe ?? null;

    if ($universoActual) {
        $universoActual->loadCount(['entities', 'seasons', 'universeTournaments', 'tournamentInstances']);

        $temporadaActual = $universoActual->activeSeason();
    }
@endphp

<x-omni-sidebar accent="violet">

    <x-slot:brand>
        @if ($universoActual)
            <x-omni-sidebar-brand accent="violet" :href="route('universes.show', $universoActual)" :back="route('universes.index')"
                back-label="Todos los Universos" :title="$universoActual->name" :subtitle="$universoActual->code" :meta="$temporadaActual ? 'Temporada ' . $temporadaActual->number : null" :image="$universoActual->image_url"
                icon="orbita" />
        @else
            <x-omni-sidebar-brand accent="violet" :href="route('universes.dashboard')" :back="route('hub')" back-label="Centro OmniMerge"
                title="Universos" subtitle="Mundos" icon="orbita" />
        @endif
    </x-slot:brand>


    @if ($universoActual)

        <x-omni-nav-section title="Universo">
            <x-omni-nav-item accent="violet" :href="route('universes.show', $universoActual)" icon="cuadricula" label="Resumen"
                :active="request()->routeIs('universes.show')" />
        </x-omni-nav-section>


        <x-omni-nav-section title="Contenido">
            <x-omni-nav-item accent="violet" :href="route('universes.explorer', $universoActual)" icon="brujula" label="Explorar"
                :active="request()->routeIs('universes.explorer')" />

            <x-omni-nav-item accent="violet" :href="route('universes.games.index', $universoActual)" icon="dado" label="Juegos"
                :active="request()->routeIs('universes.games.*')" />

            <x-omni-nav-item accent="violet" :href="route('universes.entities.index', $universoActual)" icon="chispa"
                label="Entidades" :badge="$universoActual->entities_count" :active="request()->routeIs('universes.entities.*')" />

            <x-omni-nav-item accent="violet" :href="route('universes.seasons.index', $universoActual)" icon="calendario"
                label="Temporadas" :badge="$universoActual->seasons_count" :active="request()->routeIs('universes.seasons.*')" />

            <x-omni-nav-item accent="violet" :href="route('universes.tournaments.index', $universoActual)" icon="trofeo"
                label="Torneos" :badge="$universoActual->universe_tournaments_count" :active="request()->routeIs('universes.tournaments.*')" />

            {{-- Competiciones reales, no plantillas --}}
            <x-omni-nav-item accent="violet" :href="route('universes.competitions.index', $universoActual)" icon="espadas"
                label="Competiciones" :badge="$universoActual->tournament_instances_count" :active="request()->routeIs('universes.competitions.*')" />
        </x-omni-nav-section>


        <x-omni-nav-section title="Historia">
            <x-omni-nav-item accent="violet" :href="route('universes.history', $universoActual)" icon="historial" label="Historial"
                :active="request()->routeIs('universes.history')" />

            <x-omni-nav-item accent="violet" :href="route('universes.trophies.index', $universoActual)" icon="medalla"
                label="Trofeos" :active="request()->routeIs('universes.trophies.*')" />

            <x-omni-nav-item accent="violet" :href="route('universes.ranking', $universoActual)" icon="barras"
                label="Clasificación" :active="request()->routeIs('universes.ranking*')" />
        </x-omni-nav-section>


        <x-omni-nav-section title="Ajustes">
            <x-omni-nav-item accent="violet" :href="route('universes.edit', $universoActual)" icon="engranaje"
                label="Configuración" :active="request()->routeIs('universes.edit')" />
        </x-omni-nav-section>

    @else

        <x-omni-nav-section title="Principal">
            <x-omni-nav-item accent="violet" :href="route('universes.dashboard')" icon="cuadricula" label="Dashboard"
                :active="request()->routeIs('universes.dashboard')" />

            <x-omni-nav-item accent="violet" :href="route('universes.index')" icon="orbita" label="Mis Universos"
                :active="request()->routeIs('universes.index')" />

            <x-omni-nav-item accent="violet" :href="route('universes.create')" icon="mas" label="Nuevo Universo"
                :active="request()->routeIs('universes.create')" />
        </x-omni-nav-section>

    @endif


    <x-slot:footer>
        <x-omni-sidebar-user>
            <x-omni-nav-item accent="amber" :href="route('tournaments.dashboard')" icon="trofeo" label="Ir a Torneos" />

            <x-omni-nav-item accent="indigo" :href="route('dashboard')" icon="libro" label="Ir a Biblioteca" />
        </x-omni-sidebar-user>
    </x-slot:footer>

</x-omni-sidebar>

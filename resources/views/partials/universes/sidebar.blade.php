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

        /* Su configuración: color, icono, vocabulario y qué secciones se ven */
        $ajustesMundo = $universoActual->ajustes();
    }
@endphp

<x-omni-sidebar accent="violet">

    <x-slot:brand>
        @if ($universoActual)
            <x-omni-sidebar-brand accent="violet" :href="route('universes.show', $universoActual)" :back="route('universes.index')"
                back-label="Todos los Universos" :title="$universoActual->name" :subtitle="$universoActual->code" :meta="$temporadaActual ? $ajustesMundo->label('label_season') . ' ' . $temporadaActual->number : null" :image="$universoActual->image_url"
                :icon="$ajustesMundo->icon()" :color="$ajustesMundo->accent()" />
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


        @php
            /*
             * Las secciones, con los nombres de este universo y sin las que su
             * configuración esconde. Ver App\Support\Universes\UniverseSettings.
             */
            $gruposMenu = [
                'Contenido' => ['explorer', 'games', 'entities', 'seasons', 'tournaments', 'competitions'],
                'Historia' => ['history', 'trophies', 'ranking'],
            ];

            $insigniasMenu = [
                'entities' => $universoActual->entities_count,
                'seasons' => $universoActual->seasons_count,
                'tournaments' => $universoActual->universe_tournaments_count,
                'competitions' => $universoActual->tournament_instances_count,
            ];

            $etiquetasMenu = [
                'entities' => $ajustesMundo->label('label_entities'),
                'seasons' => $ajustesMundo->label('label_seasons'),
                'tournaments' => $ajustesMundo->label('label_tournaments'),
                'competitions' => $ajustesMundo->label('label_competitions'),
            ];

            $activosMenu = [
                'explorer' => 'universes.explorer',
                'games' => 'universes.games.*',
                'entities' => 'universes.entities.*',
                'seasons' => 'universes.seasons.*',
                'tournaments' => 'universes.tournaments.*',
                'competitions' => 'universes.competitions.*',
                'history' => 'universes.history',
                'trophies' => 'universes.trophies.*',
                'ranking' => 'universes.ranking*',
            ];
        @endphp

        @foreach ($gruposMenu as $tituloMenu => $clavesMenu)
            @php $visiblesMenu = array_filter($clavesMenu, fn ($k) => $ajustesMundo->navVisible($k)); @endphp

            @if ($visiblesMenu)
                <x-omni-nav-section :title="$tituloMenu">
                    @foreach ($visiblesMenu as $claveMenu)
                        @php [$rutaMenu, $textoMenu, $iconoMenu] = \App\Support\Universes\UniverseSettings::NAV[$claveMenu]; @endphp

                        <x-omni-nav-item accent="violet" :href="route($rutaMenu, $universoActual)" :icon="$iconoMenu"
                            :label="$etiquetasMenu[$claveMenu] ?? $textoMenu" :badge="$insigniasMenu[$claveMenu] ?? null"
                            :active="request()->routeIs($activosMenu[$claveMenu])" />
                    @endforeach
                </x-omni-nav-section>
            @endif
        @endforeach


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

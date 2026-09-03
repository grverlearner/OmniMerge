{{--
    El sidebar de Torneos. Ver el comentario de partials/sidebar.blade.php:
    el armazón es el mismo, aquí solo se dice qué hay.

    El Laboratorio y las Recompensas ya no están: el laboratorio se abre desde
    el taller, junto a la plantilla que se va a probar —que es cuando se
    necesita—, y las recompensas no existen todavía como para ocupar sitio.
--}}

<x-omni-sidebar accent="amber">

    <x-slot:brand>
        <x-omni-sidebar-brand accent="amber" :href="route('tournaments.dashboard')" :back="route('hub')" back-label="Centro OmniMerge"
            title="Torneos" subtitle="Designer" icon="trofeo" />
    </x-slot:brand>


    <x-omni-nav-section title="Principal">
        <x-omni-nav-item accent="amber" :href="route('tournaments.dashboard')" icon="cuadricula" label="Taller"
            :active="request()->routeIs('tournaments.dashboard')" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Diseño">
        <x-omni-nav-item accent="amber" :href="route('tournaments.templates.index')" icon="trofeo" label="Torneos" :active="request()->routeIs('tournaments.templates.*') ||
            request()->routeIs('tournaments.super.*') ||
            request()->routeIs('tournaments.graph.*')" />

        <x-omni-nav-item accent="amber" :href="route('tournaments.phase-templates.index')" icon="grafo" label="Fases" :active="request()->routeIs('tournaments.phase-templates.*') ||
            request()->routeIs('tournaments.phase-exits.*') ||
            request()->routeIs('tournaments.single-elimination.*') ||
            request()->routeIs('tournaments.round-robin.*') ||
            request()->routeIs('tournaments.group-stage.*') ||
            request()->routeIs('tournaments.swiss.*')" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Comunidad">
        <x-omni-nav-item accent="violet" :href="route('tournaments.community.index')" icon="globo" label="Explorar"
            :active="request()->routeIs('tournaments.community.*')" />

        <x-omni-nav-item accent="violet" :href="route('tournaments.creator.show')" icon="usuario" label="Creador"
            :active="request()->routeIs('tournaments.creator.*')" />
    </x-omni-nav-section>


    <x-slot:footer>
        <x-omni-sidebar-user>
            <x-omni-nav-item accent="violet" :href="route('universes.dashboard')" icon="orbita" label="Ir a Universos" />

            <x-omni-nav-item accent="indigo" :href="route('dashboard')" icon="libro" label="Ir a Biblioteca" />
        </x-omni-sidebar-user>
    </x-slot:footer>

</x-omni-sidebar>

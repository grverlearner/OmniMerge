{{--
    El sidebar de la Biblioteca.

    Todo lo que dibuja está en <x-omni-sidebar> y sus piezas; aquí solo se
    dice QUÉ hay y dónde está uno. Los tres módulos comparten armazón para
    que plegarse, recordarse y comportarse en móvil sea una sola cosa y no
    tres que un día divergen.
--}}

<x-omni-sidebar accent="indigo">

    <x-slot:brand>
        <x-omni-sidebar-brand accent="indigo" :href="route('dashboard')" :back="route('hub')" back-label="Centro OmniMerge"
            title="Biblioteca" subtitle="Creaciones" logo />
    </x-slot:brand>


    <x-omni-nav-section title="Principal">
        <x-omni-nav-item accent="indigo" :href="route('dashboard')" icon="cuadricula" label="Dashboard"
            :active="request()->routeIs('dashboard')" />
    </x-omni-nav-section>


    {{--
        Los tipos no tienen entrada propia: se llega a ellos desde el índice de
        Entidades, que es donde se usan. El sidebar enseña los sitios donde uno
        trabaja, no todas las tablas que existen.
    --}}
    <x-omni-nav-section title="Creaciones">
        <x-omni-nav-item accent="indigo" :href="route('entities.index')" icon="chispa" label="Entidades" :active="request()->routeIs('entities.*') || request()->routeIs('entity-types.*')" />

        <x-omni-nav-item accent="indigo" :href="route('collections.index')" icon="capas" label="Colecciones"
            :active="request()->routeIs('collections.*')" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Características">
        <x-omni-nav-item accent="indigo" :href="route('attributes.index')" icon="controles" label="Atributos" :active="request()->routeIs('attributes.*') || request()->routeIs('attribute-groups.*')" />

        <x-omni-nav-item accent="indigo" :href="route('attribute-options.index')" icon="capas" label="Catálogos"
            :active="request()->routeIs('attribute-options.*')" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Descubrir">
        <x-omni-nav-item accent="violet" :href="route('community.index')" icon="globo" label="Comunidad"
            :active="request()->routeIs('community.*')" />
    </x-omni-nav-section>


    <x-slot:footer>
        <x-omni-sidebar-user>
            <x-omni-nav-item accent="amber" :href="route('tournaments.dashboard')" icon="trofeo" label="Ir a Torneos" />

            <x-omni-nav-item accent="violet" :href="route('universes.dashboard')" icon="orbita" label="Ir a Universos" />
        </x-omni-sidebar-user>
    </x-slot:footer>

</x-omni-sidebar>

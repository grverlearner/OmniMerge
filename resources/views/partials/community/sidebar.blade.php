{{--
    El sidebar de la Comunidad.

    El armazón es el mismo de los otros tres módulos —ver partials/sidebar—;
    aquí solo se dice qué hay: la puerta de entrada, los dos exploradores que
    antes vivían cada uno en su módulo, las personas y lo tuyo.
--}}

@php
    $yo = auth()->user();

    /* El perfil completo es «Mi perfil» si es el tuyo y «Creadores» si es de otro */
    $perfilPropio = request()->routeIs('profiles.show')
        && request()->route('user')?->is($yo);
@endphp

<x-omni-sidebar accent="emerald">

    <x-slot:brand>
        <x-omni-sidebar-brand accent="emerald" :href="route('community.home')" :back="route('hub')" back-label="Centro OmniMerge"
            title="Comunidad" subtitle="Lo que hacen otros" icon="orbita" />
    </x-slot:brand>


    <x-omni-nav-section title="Principal">
        <x-omni-nav-item accent="emerald" :href="route('community.home')" icon="cuadricula" label="Inicio"
            :active="request()->routeIs('community.home')" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Explorar">
        <x-omni-nav-item accent="emerald" :href="route('community.index')" icon="libro" label="Biblioteca" :active="request()->routeIs('community.index') ||
            request()->routeIs('community.search') ||
            request()->routeIs('community.entities.show') ||
            request()->routeIs('community.collections.show') ||
            request()->routeIs('community.attributes.show') ||
            request()->routeIs('community.catalogs.show')" />

        <x-omni-nav-item accent="emerald" :href="route('tournaments.community.index')" icon="trofeo" label="Torneos y fases" :active="request()->routeIs('tournaments.community.index') ||
            request()->routeIs('tournaments.community.tournament') ||
            request()->routeIs('tournaments.community.phase')" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Personas">
        <x-omni-nav-item accent="emerald" :href="route('community.creators.index')" icon="usuario" label="Creadores" :active="request()->routeIs('community.creators.*') ||
            request()->routeIs('tournaments.community.creator') ||
            (request()->routeIs('profiles.show') && ! $perfilPropio)" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Tú">
        <x-omni-nav-item accent="emerald" :href="route('profiles.show', $yo->username)" icon="chispa" label="Mi perfil público"
            :active="$perfilPropio" />

        <x-omni-nav-item accent="emerald" :href="route('profile.edit')" icon="panel" label="Qué se ve de lo mío" />

        <x-omni-nav-item accent="emerald" :href="route('tournaments.creator.show')" icon="controles" label="Panel de creador" />
    </x-omni-nav-section>


    <x-slot:footer>
        <x-omni-sidebar-user>
            <x-omni-nav-item accent="indigo" :href="route('dashboard')" icon="libro" label="Ir a Biblioteca" />

            <x-omni-nav-item accent="amber" :href="route('tournaments.dashboard')" icon="trofeo" label="Ir a Torneos" />

            <x-omni-nav-item accent="violet" :href="route('universes.dashboard')" icon="orbita" label="Ir a Universos" />
        </x-omni-sidebar-user>
    </x-slot:footer>

</x-omni-sidebar>

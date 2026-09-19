{{--
    El sidebar del administrador. Mismo armazón que los otros módulos
    —ver components/omni-sidebar—, con el acento rosa: aquí se toca lo de
    todos, y tiene que notarse.
--}}

@php
    $pendientes = [
        'bloqueadas' => \App\Models\User::query()->whereNotNull('banned_at')->count(),
    ];
@endphp

<x-omni-sidebar accent="rose">

    <x-slot:brand>
        <x-omni-sidebar-brand accent="rose" :href="route('admin.dashboard')" :back="route('hub')" back-label="Centro OmniMerge"
            title="Administración" subtitle="Todo el sitio" icon="escudo" />
    </x-slot:brand>


    <x-omni-nav-section title="Principal">
        <x-omni-nav-item accent="rose" :href="route('admin.dashboard')" icon="cuadricula" label="Resumen"
            :active="request()->routeIs('admin.dashboard')" />

        <x-omni-nav-item accent="rose" :href="route('admin.popular')" icon="barras" label="Lo más visto"
            :active="request()->routeIs('admin.popular')" />
    </x-omni-nav-section>


    <x-omni-nav-section title="Personas">
        <x-omni-nav-item accent="rose" :href="route('admin.users.index')" icon="usuario" label="Usuarios"
            :active="request()->routeIs('admin.users.*') && request('estado') !== 'bloqueadas'" />

        <x-omni-nav-item accent="rose" :href="route('admin.users.index', ['estado' => 'bloqueadas'])" icon="prohibido"
            :label="'Cuentas bloqueadas' . ($pendientes['bloqueadas'] ? ' · ' . $pendientes['bloqueadas'] : '')"
            :active="request()->routeIs('admin.users.index') && request('estado') === 'bloqueadas'" />
    </x-omni-nav-section>


    @foreach (collect(\App\Services\Admin\ContentRegistry::TYPES)->groupBy('module', true) as $modulo => $tipos)
        <x-omni-nav-section :title="$modulo">
            @foreach ($tipos as $clave => $tipo)
                <x-omni-nav-item accent="rose" :href="route('admin.content.index', $clave)" :icon="$tipo['icon']" :label="$tipo['label']"
                    :active="request()->routeIs('admin.content.*') && request()->route('type') === $clave" />
            @endforeach
        </x-omni-nav-section>
    @endforeach


    <x-omni-nav-section title="Sitio">
        <x-omni-nav-item accent="rose" :href="route('admin.settings.edit')" icon="engranaje" label="Configuración"
            :active="request()->routeIs('admin.settings.*')" />

        <x-omni-nav-item accent="rose" :href="route('admin.audit')" icon="historial" label="Registro de acciones"
            :active="request()->routeIs('admin.audit')" />
    </x-omni-nav-section>


    <x-slot:footer>
        <x-omni-sidebar-user>
            <x-omni-nav-item accent="indigo" :href="route('dashboard')" icon="libro" label="Ir a Biblioteca" />

            <x-omni-nav-item accent="amber" :href="route('tournaments.dashboard')" icon="trofeo" label="Ir a Torneos" />

            <x-omni-nav-item accent="violet" :href="route('universes.dashboard')" icon="orbita" label="Ir a Universos" />

            <x-omni-nav-item accent="emerald" :href="route('community.home')" icon="globo" label="Ir a Comunidad" />
        </x-omni-sidebar-user>
    </x-slot:footer>

</x-omni-sidebar>

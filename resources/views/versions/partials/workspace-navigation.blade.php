@php

    $currentRoute = request()->route()?->getName();

    $definitionsActive = in_array(
        $currentRoute,
        ['versions.index', 'versions.create', 'versions.store', 'versions.show', 'versions.edit', 'versions.update'],
        true,
    );

    $entitiesActive =
        $currentRoute === 'versions.entities.index' || str_starts_with((string) $currentRoute, 'entity-versions.');
@endphp


<nav
    class="
        mb-6
        overflow-x-auto
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-2
        shadow-sm
    ">

    <div class="
            inline-flex
            min-w-max
            gap-1
        ">

        <a href="{{ route('versions.index') }}"
            class="
                rounded-xl
                px-4
                py-2.5
                text-xs
                font-black
                transition

                {{ $definitionsActive
                    ? 'bg-violet-600 text-white shadow-sm'
                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}
            ">
            ◈ Definiciones
        </a>


        <a href="{{ route('versions.entities.index') }}"
            class="
                rounded-xl
                px-4
                py-2.5
                text-xs
                font-black
                transition

                {{ $entitiesActive
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}
            ">
            ✦ Versiones de Entidades
        </a>


        <a href="{{ route('versions.coverage') }}"
            class="
                rounded-xl
                px-4
                py-2.5
                text-xs
                font-black
                transition

                {{ $currentRoute === 'versions.coverage'
                    ? 'bg-cyan-600 text-white shadow-sm'
                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}
            ">
            ◉ Cobertura
        </a>


        <a href="{{ route('versions.media') }}"
            class="
                rounded-xl
                px-4
                py-2.5
                text-xs
                font-black
                transition

                {{ $currentRoute === 'versions.media'
                    ? 'bg-fuchsia-600 text-white shadow-sm'
                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}
            ">
            ▣ Multimedia
        </a>


        <a href="{{ route('versions.resolver') }}"
            class="
                rounded-xl
                px-4
                py-2.5
                text-xs
                font-black
                transition

                {{ $currentRoute === 'versions.resolver'
                    ? 'bg-amber-500 text-white shadow-sm'
                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}
            ">
            ⚡ Probar Resolver
        </a>

    </div>

</nav>

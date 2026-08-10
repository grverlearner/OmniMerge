<div
    class="
        mb-7
        flex
        flex-col
        gap-4
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-3
        shadow-sm
        xl:flex-row
        xl:items-center
        xl:justify-between
    ">

    <div class="
            overflow-x-auto
        ">

        <div
            class="
                inline-flex
                min-w-max
                rounded-xl
                bg-slate-100
                p-1
            ">

            {{-- ENTIDADES --}}
            <a href="{{ route('entities.index') }}"
                class="
                    {{ request()->routeIs('entities.*')
                        ? 'bg-white text-indigo-700 shadow-sm'
                        : 'text-slate-500 hover:text-slate-900' }}

                    rounded-lg
                    px-4
                    py-2.5
                    text-center
                    text-sm
                    font-bold
                    transition
                ">
                ✦ Entidades
            </a>


            {{-- VERSIONES --}}
            <a href="{{ route('versions.index') }}"
                class="
                    {{ request()->routeIs('versions.*') || request()->routeIs('entity-versions.*')
                        ? 'bg-white text-violet-700 shadow-sm'
                        : 'text-slate-500 hover:text-slate-900' }}

                    rounded-lg
                    px-4
                    py-2.5
                    text-center
                    text-sm
                    font-bold
                    transition
                ">
                ◈ Versiones
            </a>


            {{-- TIPOS --}}
            <a href="{{ route('entity-types.index') }}"
                class="
                    {{ request()->routeIs('entity-types.*')
                        ? 'bg-white text-indigo-700 shadow-sm'
                        : 'text-slate-500 hover:text-slate-900' }}

                    rounded-lg
                    px-4
                    py-2.5
                    text-center
                    text-sm
                    font-bold
                    transition
                ">
                ◇ Tipos
            </a>


            {{-- COLECCIONES --}}
            <a href="{{ route('collections.index') }}"
                class="
                    {{ request()->routeIs('collections.*')
                        ? 'bg-white text-cyan-700 shadow-sm'
                        : 'text-slate-500 hover:text-slate-900' }}

                    rounded-lg
                    px-4
                    py-2.5
                    text-center
                    text-sm
                    font-bold
                    transition
                ">
                ▤ Colecciones
            </a>

        </div>

    </div>


    <p
        class="
            hidden
            max-w-lg
            text-right
            text-xs
            leading-5
            text-slate-400
            xl:block
        ">
        Crea identidades base y representa
        sus eras, formas, transformaciones
        y otras Versiones.
    </p>

</div>

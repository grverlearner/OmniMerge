{{-- ============================================================= --}}
{{-- FONDO MÓVIL --}}
{{-- ============================================================= --}}

<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
    class="
        fixed
        inset-0
        z-40
        bg-slate-950/60
        lg:hidden
    "></div>


{{-- ============================================================= --}}
{{-- SIDEBAR DE BIBLIOTECA --}}
{{-- ============================================================= --}}

<aside :class="sidebarOpen
    ?
    'translate-x-0' :
    '-translate-x-full'"
    class="
        fixed
        inset-y-0
        left-0
        z-50
        flex
        w-72
        flex-col
        bg-slate-950
        text-slate-100
        transition-transform
        duration-300
        lg:translate-x-0
    ">

    {{-- ========================================================= --}}
    {{-- CABECERA --}}
    {{-- ========================================================= --}}

    <div
        class="
            border-b
            border-slate-800
            px-5
            pb-5
            pt-5
        ">

        {{-- VOLVER AL HUB --}}
        <a href="{{ route('hub') }}"
            class="
                mb-4
                flex
                items-center
                gap-2
                rounded-xl
                border
                border-slate-800
                bg-slate-900/70
                px-3
                py-2.5
                text-xs
                font-bold
                text-slate-400
                transition
                hover:border-indigo-500/30
                hover:bg-indigo-500/10
                hover:text-indigo-300
            ">
            <span>←</span>

            Centro OmniMerge
        </a>


        {{-- IDENTIDAD DEL MÓDULO --}}
        <a href="{{ route('dashboard') }}"
            class="
                flex
                items-center
                gap-3
                rounded-xl
                px-1
                py-1
            ">

            <x-application-logo
                class="
                    h-11
                    w-11
                    shrink-0
                " />

            <div class="min-w-0">

                <p
                    class="
                        truncate
                        text-lg
                        font-black
                        tracking-tight
                        text-white
                    ">
                    OmniMerge
                </p>

                <div
                    class="
                        mt-0.5
                        flex
                        items-center
                        gap-2
                    ">

                    <span
                        class="
                            text-xs
                            font-bold
                            uppercase
                            tracking-wider
                            text-indigo-400
                        ">
                        Biblioteca
                    </span>

                    <span
                        class="
                            h-1
                            w-1
                            rounded-full
                            bg-slate-600
                        "></span>

                    <span
                        class="
                            text-xs
                            text-slate-500
                        ">
                        Creaciones
                    </span>

                </div>
            </div>
        </a>
    </div>


    {{-- ========================================================= --}}
    {{-- NAVEGACIÓN --}}
    {{-- ========================================================= --}}

    <nav
        class="
            flex-1
            space-y-6
            overflow-y-auto
            px-4
            py-6
        ">

        {{-- PRINCIPAL --}}
        <div>

            <p
                class="
                    mb-2
                    px-3
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wider
                    text-slate-500
                ">
                Biblioteca
            </p>


            <div class="space-y-1">

                <a href="{{ route('dashboard') }}"
                    class="
                        {{ request()->routeIs('dashboard')
                            ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}
                        flex
                        items-center
                        gap-3
                        rounded-xl
                        px-3
                        py-3
                        text-sm
                        font-medium
                        transition
                    ">
                    <span class="text-lg">
                        ▦
                    </span>

                    Dashboard
                </a>

            </div>
        </div>


        {{-- ENTIDADES --}}
        <div>

            <p
                class="
                    mb-2
                    px-3
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wider
                    text-slate-500
                ">
                Entidades
            </p>


            <div class="space-y-1">
                <a href="{{ route('entities.index') }}"
                    class="{{ request()->routeIs('entities.*') ||
                    request()->routeIs('entity-types.*') ||
                    request()->routeIs('collections.*')
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40'
                        : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}
                        flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition">

                    <span class="text-lg">
                        ✦
                    </span>

                    <span>
                        Entidades
                    </span>

                </a>

            </div>
        </div>


        {{-- ATRIBUTOS --}}
        <div>

            <p
                class="
                    mb-2
                    px-3
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wider
                    text-slate-500
                ">
                Características
            </p>


            <div class="space-y-1">

                <a href="{{ route('attributes.index') }}"
                    class="
                    {{ request()->routeIs('attributes.*') || request()->routeIs('attribute-groups.*')
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40'
                        : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}

                            flex
                            items-center
                            gap-3
                            rounded-xl
                            px-3
                            py-3
                            text-sm
                            font-medium
                            transition
                        ">
                    <span class="text-lg">
                        ☷
                    </span>

                    Atributos
                </a>


                <a href="{{ route('attribute-options.index') }}"
                    class="
                        {{ request()->routeIs('attribute-options.*')
                            ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-950/40'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}
                        flex
                        items-center
                        gap-3
                        rounded-xl
                        px-3
                        py-3
                        text-sm
                        font-medium
                        transition
                    ">
                    <span class="text-lg">
                        ◆
                    </span>

                    Catálogos
                </a>

            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- DESCUBRIR --}}
        {{-- ========================================================= --}}

        <div>

            <p
                class="
            mb-2
            px-3
            text-xs
            font-semibold
            uppercase
            tracking-wider
            text-slate-500
        ">
                Descubrir
            </p>


            <div class="space-y-1">

                <a href="{{ route('community.index') }}"
                    class="
                {{ request()->routeIs('community.*')
                    ? 'bg-violet-500 text-white shadow-lg shadow-violet-950/40'
                    : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}

                flex
                items-center
                gap-3
                rounded-xl
                px-3
                py-3
                text-sm
                font-medium
                transition
            ">
                    <span class="text-lg">
                        🌐
                    </span>

                    <span>
                        Comunidad
                    </span>
                </a>

            </div>

        </div>

    </nav>


    {{-- ========================================================= --}}
    {{-- USUARIO --}}
    {{-- ========================================================= --}}

    <div class="
            border-t
            border-slate-800
            p-4
        ">

        <a href="{{ route('profile.edit') }}"
            class="
                flex
                items-center
                gap-3
                rounded-xl
                p-3
                transition
                hover:bg-slate-900
            ">

            <x-user-avatar :user="auth()->user()" size="md" />


            <div class="
                    min-w-0
                    flex-1
                ">

                <p
                    class="
                        truncate
                        text-sm
                        font-semibold
                        text-white
                    ">
                    {{ auth()->user()->name }}
                </p>

                <p
                    class="
                        truncate
                        text-xs
                        text-slate-400
                    ">
                    {{ '@' . auth()->user()->username }}
                </p>

            </div>


            <span class="
                    text-xs
                    text-slate-600
                ">
                →
            </span>

        </a>


        <a href="{{ route('home') }}"
            class="
                mt-2
                flex
                items-center
                gap-3
                rounded-xl
                px-3
                py-2.5
                text-xs
                font-semibold
                text-slate-500
                transition
                hover:bg-slate-900
                hover:text-slate-300
            ">
            <span>
                ⌂
            </span>

            Página pública
        </a>

    </div>

</aside>

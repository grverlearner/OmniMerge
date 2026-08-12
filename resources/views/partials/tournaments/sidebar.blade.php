{{-- ============================================================= --}}
{{-- OVERLAY MÓVIL --}}
{{-- ============================================================= --}}

<div x-show="
        sidebarOpen
    " x-transition.opacity @click="
        sidebarOpen = false
    "
    class="
        fixed
        inset-0
        z-40
        bg-slate-950/60
        backdrop-blur-sm
        lg:hidden
    ">
</div>


{{-- ============================================================= --}}
{{-- SIDEBAR --}}
{{-- ============================================================= --}}

<aside
    :class="sidebarOpen
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
                hover:border-amber-500/30
                hover:bg-amber-500/10
                hover:text-amber-300
            ">
            <span>
                ←
            </span>

            Centro OmniMerge
        </a>


        <a href="{{ route('tournaments.dashboard') }}"
            class="
                flex
                items-center
                gap-3
                rounded-xl
                px-1
                py-1
            ">

            <div
                class="
                    flex
                    h-11
                    w-11
                    shrink-0
                    items-center
                    justify-center
                    rounded-xl
                    bg-gradient-to-br
                    from-amber-400
                    to-orange-500
                    text-xl
                    shadow-lg
                    shadow-amber-950/30
                ">
                🏆
            </div>


            <div class="
                    min-w-0
                ">

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
                            text-amber-400
                        ">
                        Torneos
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
                        Designer
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
                Principal
            </p>


            <a href="{{ route('tournaments.dashboard') }}"
                class="
                    {{ request()->routeIs('tournaments.dashboard')
                        ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-950/30'
                        : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}

                    flex
                    items-center
                    gap-3
                    rounded-xl
                    px-3
                    py-3
                    text-sm
                    font-semibold
                    transition
                ">

                <span class="
                        text-lg
                    ">
                    ▦
                </span>

                Dashboard

            </a>

        </div>


        {{-- PLANTILLAS --}}

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
                Plantillas
            </p>


            <div class="
                    space-y-1
                ">

                <a href="{{ route('tournaments.templates.index') }}"
                    class="
                        {{ request()->routeIs('tournaments.templates.*') || request()->routeIs('tournaments.phases.*')
                            ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-950/30'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}

                        flex
                        items-center
                        gap-3
                        rounded-xl
                        px-3
                        py-3
                        text-sm
                        font-semibold
                        transition
                    ">

                    <span class="
                            text-lg
                        ">
                        🏆
                    </span>

                    Mis plantillas

                </a>


                <a href="{{ route('tournaments.templates.create') }}"
                    class="
                        flex
                        items-center
                        gap-3
                        rounded-xl
                        px-3
                        py-3
                        text-sm
                        font-semibold
                        text-slate-300
                        transition
                        hover:bg-slate-900
                        hover:text-white
                    ">

                    <span class="
                            text-lg
                        ">
                        ＋
                    </span>

                    Crear plantilla

                </a>

            </div>

        </div>


        {{-- PRUEBAS --}}

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
                Pruebas
            </p>


            <a href="{{ route('tournaments.lab.index') }}"
                class="
                    {{ request()->routeIs('tournaments.lab.*')
                        ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-950/30'
                        : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}

                    flex
                    items-center
                    gap-3
                    rounded-xl
                    px-3
                    py-3
                    text-sm
                    font-semibold
                    transition
                ">

                <span class="
                        text-lg
                    ">
                    ⚗
                </span>

                Laboratorio

            </a>

        </div>


        {{-- RECURSOS --}}

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
                Recursos
            </p>


            <div
                class="
                    flex
                    items-center
                    gap-3
                    rounded-xl
                    px-3
                    py-3
                    text-sm
                    font-semibold
                    text-slate-600
                ">

                <span class="
                        text-lg
                    ">
                    ◇
                </span>


                <span class="
                        flex-1
                    ">
                    Recompensas
                </span>


                <span
                    class="
                        rounded-full
                        bg-amber-500/10
                        px-2
                        py-1
                        text-[8px]
                        font-black
                        uppercase
                        text-amber-500
                    ">
                    Próximo
                </span>

            </div>

        </div>


        {{-- DESCUBRIR --}}

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


            <a href="{{ route('community.index') }}"
                class="
                    flex
                    items-center
                    gap-3
                    rounded-xl
                    px-3
                    py-3
                    text-sm
                    font-semibold
                    text-slate-300
                    transition
                    hover:bg-slate-900
                    hover:text-white
                ">

                <span class="
                        text-lg
                    ">
                    🌐
                </span>


                <span class="
                        flex-1
                    ">
                    Comunidad
                </span>


                <span
                    class="
                        rounded-full
                        bg-violet-500/10
                        px-2
                        py-1
                        text-[8px]
                        font-black
                        uppercase
                        text-violet-400
                    ">
                    Templates pronto
                </span>

            </a>

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


        <a href="{{ route('dashboard') }}"
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
                hover:text-indigo-300
            ">

            <span>
                📚
            </span>

            Ir a Biblioteca

        </a>

    </div>

</aside>

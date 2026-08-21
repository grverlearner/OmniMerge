<header
    class="
        sticky
        top-0
        z-30
        border-b
        border-slate-200
        bg-white/90
        backdrop-blur
    ">

    <div
        class="
            flex
            h-20
            items-center
            justify-between
            px-4
            sm:px-6
            lg:px-8
        ">

        <div class="
                flex
                items-center
                gap-4
            ">

            <button type="button" @click="
                    sidebarOpen = true
                "
                class="
                    rounded-xl
                    border
                    border-slate-200
                    p-2
                    text-slate-600
                    transition
                    hover:bg-slate-100
                    lg:hidden
                ">
                ☰
            </button>


            <div>

                <p
                    class="
                        text-xs
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-600
                    ">
                    OmniMerge · Universos
                </p>


                <h1
                    class="
                        text-lg
                        font-bold
                        text-slate-900
                    ">
                    {{ $header ?? 'Universos' }}
                </h1>

            </div>

        </div>


        <div x-data="{
            open: false
        }" class="
                relative
            ">

            <button type="button" @click="
                    open = !open
                "
                class="
                    flex
                    items-center
                    gap-3
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    px-3
                    py-2
                    transition
                    hover:border-violet-300
                    hover:bg-violet-50
                ">

                <x-user-avatar :user="auth()->user()" size="sm" />


                <div
                    class="
                        hidden
                        text-left
                        sm:block
                    ">

                    <p
                        class="
                            text-sm
                            font-semibold
                            text-slate-800
                        ">
                        {{ auth()->user()->name }}
                    </p>


                    <p
                        class="
                            text-xs
                            text-slate-500
                        ">
                        {{ auth()->user()->role }}
                    </p>

                </div>


                <span
                    class="
                        text-xs
                        text-slate-400
                    ">
                    ▼
                </span>

            </button>


            <div x-show="open" x-transition @click.outside="
                    open = false
                "
                style="
                    display: none;
                "
                class="
                    absolute
                    right-0
                    mt-2
                    w-56
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-2
                    shadow-xl
                ">

                <a href="{{ route('hub') }}"
                    class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        text-slate-700
                        hover:bg-slate-100
                    ">
                    Centro OmniMerge
                </a>


                <a href="{{ route('dashboard') }}"
                    class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        text-slate-700
                        hover:bg-slate-100
                    ">
                    Biblioteca
                </a>


                <a href="{{ route('tournaments.dashboard') }}"
                    class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        text-slate-700
                        hover:bg-slate-100
                    ">
                    Torneos
                </a>


                <a href="{{ route('profile.edit') }}"
                    class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        text-sm
                        text-slate-700
                        hover:bg-slate-100
                    ">
                    Mi perfil
                </a>


                <form method="POST" action="{{ route('logout') }}">

                    @csrf


                    <button type="submit"
                        class="
                            block
                            w-full
                            rounded-xl
                            px-4
                            py-3
                            text-left
                            text-sm
                            text-red-600
                            hover:bg-red-50
                        ">
                        Cerrar sesión
                    </button>

                </form>

            </div>

        </div>

    </div>

</header>

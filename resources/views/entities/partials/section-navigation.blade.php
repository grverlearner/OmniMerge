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
        lg:flex-row
        lg:items-center
        lg:justify-between
    ">

    <div
        class="
            inline-flex
            w-full
            rounded-xl
            bg-slate-100
            p-1
            sm:w-auto
        ">

        {{-- ENTIDADES --}}
        <a href="{{ route('entities.index') }}"
            class="
                {{ request()->routeIs('entities.*')
                    ? 'bg-white text-indigo-700 shadow-sm'
                    : 'text-slate-500 hover:text-slate-900' }}

                flex-1
                rounded-lg
                px-4
                py-2.5
                text-center
                text-sm
                font-bold
                transition
                sm:flex-none
            ">
            ✦ Entidades
        </a>


        {{-- TIPOS --}}
        <a href="{{ route('entity-types.index') }}"
            class="
                {{ request()->routeIs('entity-types.*')
                    ? 'bg-white text-indigo-700 shadow-sm'
                    : 'text-slate-500 hover:text-slate-900' }}

                flex-1
                rounded-lg
                px-4
                py-2.5
                text-center
                text-sm
                font-bold
                transition
                sm:flex-none
            ">
            ◇ Tipos
        </a>


        {{-- COLECCIONES --}}
        <a href="{{ route('collections.index') }}"
            class="
                {{ request()->routeIs('collections.*')
                    ? 'bg-white text-indigo-700 shadow-sm'
                    : 'text-slate-500 hover:text-slate-900' }}

                flex-1
                rounded-lg
                px-4
                py-2.5
                text-center
                text-sm
                font-bold
                transition
                sm:flex-none
            ">
            ▤ Colecciones
        </a>

    </div>


    <p class="
            hidden
            text-xs
            text-slate-400
            xl:block
        ">
        Entidades crean · Tipos clasifican · Colecciones organizan
    </p>

</div>

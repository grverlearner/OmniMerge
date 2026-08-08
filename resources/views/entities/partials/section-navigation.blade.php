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
        sm:flex-row
        sm:items-center
        sm:justify-between
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

        {{-- MIS ENTIDADES --}}
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
            ✦ Mis entidades
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
            ◇ Tipos de entidad
        </a>

    </div>


    <p class="
            hidden
            text-xs
            text-slate-400
            lg:block
        ">
        Las entidades son tus creaciones.
        Los tipos sirven para organizarlas.
    </p>

</div>

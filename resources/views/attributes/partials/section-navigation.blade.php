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

        {{-- ===================================================== --}}
        {{-- ATRIBUTOS --}}
        {{-- ===================================================== --}}

        <a href="{{ route('attributes.index') }}"
            class="
                {{ request()->routeIs('attributes.*')
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
            ☷ Atributos
        </a>


        {{-- ===================================================== --}}
        {{-- CATÁLOGOS --}}
        {{-- ===================================================== --}}

        <a href="{{ route('attribute-options.index') }}"
            class="
                {{ request()->routeIs('attribute-options.*')
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
            ◆ Catálogos
        </a>


        {{-- ===================================================== --}}
        {{-- GRUPOS --}}
        {{-- ===================================================== --}}

        <a href="{{ route('attribute-groups.index') }}"
            class="
                {{ request()->routeIs('attribute-groups.*')
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
            ▥ Grupos
        </a>

    </div>


    <p class="
            hidden
            text-xs
            text-slate-400
            xl:block
        ">
        Atributos definen · Catálogos alimentan · Grupos organizan
    </p>

</div>

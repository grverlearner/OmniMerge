<article
    class="
        flex
        flex-col
        gap-4
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-4
        shadow-sm
        transition
        hover:border-violet-200
        hover:shadow-md
        lg:flex-row
        lg:items-center
    ">

    {{-- IMAGEN --}}
    <div
        class="
            h-20
            w-full
            shrink-0
            overflow-hidden
            rounded-xl
            bg-slate-100
            lg:w-20
        ">

        @if ($option->image_url)
            <img src="{{ $option->image_url }}" alt="{{ $option->name }}"
                class="
                    h-full
                    w-full
                    object-cover
                ">
        @else
            <div class="
                    flex
                    h-full
                    items-center
                    justify-center
                    text-2xl
                    font-black
                "
                style="
                    background-color:
                        {{ $option->color ?? '#6366F1' }}20;

                    color:
                        {{ $option->color ?? '#6366F1' }};
                ">
                {{ $option->icon ?: '◆' }}
            </div>
        @endif

    </div>


    {{-- INFORMACIÓN --}}
    <div class="
            min-w-0
            flex-1
        ">

        <div
            class="
                flex
                flex-wrap
                items-center
                gap-2
            ">

            <a href="{{ route('attribute-options.show', $option) }}"
                class="
                    font-black
                    text-slate-900
                    hover:text-violet-700
                ">
                {{ $option->name }}
            </a>


            <x-status-badge :status="$option->status" />

        </div>


        <p
            class="
                mt-1
                font-mono
                text-[10px]
                font-black
                text-slate-400
            ">
            {{ $option->code }}
        </p>


        <p
            class="
                mt-2
                line-clamp-1
                text-sm
                text-slate-500
            ">
            {{ $option->description ?: 'Sin descripción.' }}
        </p>

    </div>


    {{-- CATÁLOGO --}}
    <div class="
            min-w-[170px]
            lg:shrink-0
        ">

        <p
            class="
                text-[9px]
                font-black
                uppercase
                text-slate-400
            ">
            Catálogo
        </p>


        <div
            class="
                mt-2
                flex
                items-center
                gap-2
            ">

            <div
                class="
                    h-8
                    w-8
                    overflow-hidden
                    rounded-lg
                    bg-slate-100
                ">

                @if ($option->attribute->image_url)
                    <img src="{{ $option->attribute->image_url }}"
                        class="
                            h-full
                            w-full
                            object-cover
                        ">
                @else
                    <div
                        class="
                            flex
                            h-full
                            items-center
                            justify-center
                            text-xs
                        ">
                        {{ $option->attribute->icon ?: '◆' }}
                    </div>
                @endif

            </div>


            <span
                class="
                    text-sm
                    font-bold
                    text-slate-700
                ">
                {{ $option->attribute->name }}
            </span>

        </div>

    </div>


    {{-- JERARQUÍA --}}
    <div class="
            min-w-[120px]
            lg:shrink-0
        ">

        <p
            class="
                text-[9px]
                font-black
                uppercase
                text-slate-400
            ">
            Jerarquía
        </p>


        <p
            class="
                mt-2
                text-xs
                font-bold
                text-slate-600
            ">
            {{ $option->parent ? '↳ ' . $option->parent->name : 'Nivel principal' }}
        </p>

    </div>


    {{-- STATS --}}
    <div class="
            flex
            gap-5
            lg:shrink-0
        ">

        <div>

            <p
                class="
                    text-[9px]
                    font-black
                    uppercase
                    text-slate-400
                ">
                Usos
            </p>

            <p class="mt-1 font-black">
                {{ $option->values_count }}
            </p>

        </div>


        <div>

            <p
                class="
                    text-[9px]
                    font-black
                    uppercase
                    text-slate-400
                ">
                Hijos
            </p>

            <p class="mt-1 font-black">
                {{ $option->children_count }}
            </p>

        </div>

    </div>


    <a href="{{ route('attribute-options.show', $option) }}"
        class="
            rounded-xl
            bg-violet-50
            px-4
            py-2.5
            text-center
            text-xs
            font-black
            text-violet-700
            lg:shrink-0
        ">
        Abrir →
    </a>

</article>

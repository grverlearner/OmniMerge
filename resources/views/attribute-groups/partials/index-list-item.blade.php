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
        hover:border-indigo-200
        hover:shadow-md
        lg:flex-row
        lg:items-center
    ">

    {{-- ICONO --}}
    <div class="
            flex
            h-16
            w-full
            shrink-0
            items-center
            justify-center
            rounded-xl
            text-2xl
            font-black
            lg:w-16
        "
        style="
            background-color:
                {{ $group->color ?? '#6366F1' }}20;

            color:
                {{ $group->color ?? '#6366F1' }};
        ">
        {{ $group->icon ?: '▥' }}
    </div>


    {{-- INFO --}}
    <div class="min-w-0 flex-1">

        <div
            class="
                flex
                flex-wrap
                items-center
                gap-2
            ">

            <a href="{{ route('attribute-groups.show', $group) }}"
                class="
                    font-black
                    text-slate-900
                    hover:text-indigo-700
                ">
                {{ $group->name }}
            </a>


            <x-status-badge :status="$group->status" />

        </div>


        <p
            class="
                mt-1
                font-mono
                text-[10px]
                text-slate-400
            ">
            {{ $group->code }}
        </p>


        <p
            class="
                mt-2
                line-clamp-1
                text-sm
                text-slate-500
            ">
            {{ $group->description ?: 'Sin descripción.' }}
        </p>

    </div>


    {{-- PREVIEW --}}
    <div
        class="
            flex
            min-w-[190px]
            items-center
            gap-1.5
            lg:shrink-0
        ">

        @foreach ($group->attributes->take(4) as $attribute)
            <div title="{{ $attribute->name }}"
                class="
                    h-10
                    w-10
                    overflow-hidden
                    rounded-lg
                    bg-slate-100
                ">

                @if ($attribute->image_url)
                    <img src="{{ $attribute->image_url }}"
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
                            text-xs
                            font-black
                        "
                        style="
                            color:
                                {{ $attribute->color ?? '#6366F1' }};
                        ">
                        {{ $attribute->icon ?: '☷' }}
                    </div>
                @endif

            </div>
        @endforeach


        @if ($group->attributes_count > 4)
            <div
                class="
                    flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-lg
                    bg-indigo-50
                    text-[10px]
                    font-black
                    text-indigo-600
                ">
                +{{ $group->attributes_count - 4 }}
            </div>
        @endif

    </div>


    {{-- DATOS --}}
    <div class="
            flex
            gap-6
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
                Atributos
            </p>


            <p
                class="
                    mt-1
                    font-black
                    text-slate-700
                ">
                {{ $group->attributes_count }}
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
                Diseño
            </p>


            <p
                class="
                    mt-1
                    text-xs
                    font-bold
                    text-slate-700
                ">
                {{ $group->layout_label }}
            </p>

        </div>

    </div>


    <a href="{{ route('attribute-groups.show', $group) }}"
        class="
            rounded-xl
            bg-indigo-50
            px-4
            py-2.5
            text-center
            text-xs
            font-black
            text-indigo-700
            lg:shrink-0
        ">
        Abrir →
    </a>

</article>

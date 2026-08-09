@php

    $galleryAttributes = $group->attributes->take(4);

@endphp


<a href="{{ route('attribute-groups.show', $group) }}"
    class="
        group
        min-w-0
        overflow-hidden
        rounded-xl
        border
        border-slate-200
        bg-white
        shadow-sm
        transition
        hover:-translate-y-0.5
        hover:border-indigo-300
        hover:shadow-md
    ">

    {{-- ========================================================= --}}
    {{-- PORTADA DINÁMICA --}}
    {{-- ========================================================= --}}

    <div class="
            aspect-square
            overflow-hidden
            bg-slate-100
        ">

        @if ($galleryAttributes->isNotEmpty())

            <div
                class="
                    grid
                    h-full
                    w-full
                    grid-cols-2
                    gap-0.5
                    bg-slate-200
                ">

                @foreach ($galleryAttributes as $attribute)
                    <div
                        class="
                            min-h-0
                            min-w-0
                            overflow-hidden
                            bg-white
                        ">

                        @if ($attribute->image_url)
                            <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                ">
                        @else
                            <div class="
                                    flex
                                    h-full
                                    min-h-[50px]
                                    w-full
                                    items-center
                                    justify-center
                                    text-xl
                                    font-black
                                "
                                style="
                                    background-color:
                                        {{ $attribute->color ?? '#6366F1' }}18;

                                    color:
                                        {{ $attribute->color ?? '#6366F1' }};
                                ">
                                {{ $attribute->icon ?: $attribute->data_type_icon }}
                            </div>
                        @endif

                    </div>
                @endforeach

            </div>
        @else
            <div class="
                    flex
                    h-full
                    w-full
                    items-center
                    justify-center
                    text-4xl
                    font-black
                "
                style="
                    background-color:
                        {{ $group->color ?? '#6366F1' }}18;

                    color:
                        {{ $group->color ?? '#6366F1' }};
                ">
                {{ $group->icon ?: '▥' }}
            </div>

        @endif

    </div>


    {{-- SOLO NOMBRE --}}

    <div class="px-2 py-2.5">

        <p class="
                truncate
                text-center
                text-xs
                font-black
                text-slate-800
                group-hover:text-indigo-700
            "
            title="{{ $group->name }}">
            {{ $group->name }}
        </p>

    </div>

</a>

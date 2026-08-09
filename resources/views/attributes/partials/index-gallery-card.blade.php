<a href="{{ route('attributes.show', $attribute) }}"
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

    <div class="
            aspect-square
            overflow-hidden
            bg-slate-100
        ">

        @if ($attribute->image_url)
            <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                class="
                    h-full
                    w-full
                    object-cover
                    transition
                    duration-300
                    group-hover:scale-105
                ">
        @else
            <div class="
                    flex
                    h-full
                    w-full
                    items-center
                    justify-center
                    text-3xl
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


    <div class="px-2 py-2.5">

        <p class="
                truncate
                text-center
                text-xs
                font-black
                text-slate-800
                group-hover:text-indigo-700
            "
            title="{{ $attribute->name }}">
            {{ $attribute->name }}
        </p>

    </div>

</a>

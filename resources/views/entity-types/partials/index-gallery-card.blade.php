<a href="{{ route('entity-types.show', $type) }}"
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

        @if ($type->image_url)
            <img src="{{ $type->image_url }}" alt="{{ $type->name }}"
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
                        {{ $type->color ?? '#6366F1' }}18;

                    color:
                        {{ $type->color ?? '#6366F1' }};
                ">
                {{ $type->icon ?: mb_strtoupper(mb_substr($type->name, 0, 1)) }}
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
                transition
                group-hover:text-indigo-700
            "
            title="{{ $type->name }}">
            {{ $type->name }}
        </p>

    </div>

</a>

<a href="{{ route('attribute-options.show', $option) }}"
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
        hover:border-violet-300
        hover:shadow-md
    ">

    <div class="
            aspect-square
            overflow-hidden
            bg-slate-100
        ">

        @if ($option->image_url)
            <img src="{{ $option->image_url }}" alt="{{ $option->name }}"
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
                        {{ $option->color ?? '#7C3AED' }}18;

                    color:
                        {{ $option->color ?? '#7C3AED' }};
                ">
                {{ $option->icon ?: '◆' }}
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
                group-hover:text-violet-700
            "
            title="{{ $option->name }}">
            {{ $option->name }}
        </p>

    </div>

</a>

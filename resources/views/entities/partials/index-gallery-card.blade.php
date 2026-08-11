<a href="{{ route('entities.show', $entity) }}"
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
    {{-- IMAGEN --}}
    {{-- ========================================================= --}}

    <div class="
            aspect-square
            overflow-hidden
            bg-slate-100
        ">

        @if ($entity->base_display_image_url)
            <img src="{{ $entity->base_display_image_url }}" alt="{{ $entity->name }}"
                class="
                    h-full
                    w-full
                    object-cover
                    transition
                    duration-300
                    group-hover:scale-105
                ">
        @else
            <div
                class="
                    flex
                    h-full
                    w-full
                    items-center
                    justify-center
                    bg-gradient-to-br
                    from-indigo-50
                    to-violet-100
                    text-3xl
                    font-black
                    text-indigo-300
                ">
                {{ $entity->entityType?->icon ?: mb_strtoupper(mb_substr($entity->name, 0, 1)) }}
            </div>
        @endif

    </div>


    {{-- ========================================================= --}}
    {{-- SOLO NOMBRE --}}
    {{-- ========================================================= --}}

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
            title="{{ $entity->name }}">
            {{ $entity->name }}
        </p>

    </div>

</a>

<a href="{{ route('entities.show', $entity) }}"
    class="
        group
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
            relative
            aspect-square
            bg-slate-100
        ">

        @if ($entity->image_url)
            <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
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


        <span
            class="
                absolute
                right-2
                top-2
                h-2.5
                w-2.5
                rounded-full
                ring-2
                ring-white

                {{ $entity->status === 'ACTIVE'
                    ? 'bg-emerald-500'
                    : ($entity->status === 'ARCHIVED'
                        ? 'bg-slate-400'
                        : 'bg-amber-500') }}
            "></span>

    </div>


    <div class="px-3 py-2.5">

        <p
            class="
                truncate
                text-center
                text-xs
                font-black
                text-slate-800
                group-hover:text-indigo-700
            ">
            {{ $entity->name }}
        </p>

    </div>

</a>

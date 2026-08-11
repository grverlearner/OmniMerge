<article
    class="
        group
        overflow-hidden
        rounded-2xl
        border
        border-slate-200
        bg-white
        shadow-sm
        transition
        hover:-translate-y-0.5
        hover:border-indigo-200
        hover:shadow-lg
    ">

    <a href="{{ route('entities.show', $entity) }}"
        class="
            block
            overflow-hidden
            bg-slate-100
        "
        :class="{
            'h-32': density === 'compact',
        
            'h-44': density === 'medium',
        
            'h-60': density === 'large'
        }">

        @if ($entity->base_display_image_url)
            <img src="{{ $entity->base_display_image_url }}" alt="{{ $entity->name }}"
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
                    bg-gradient-to-br
                    from-indigo-50
                    to-violet-100
                    text-5xl
                    font-black
                    text-indigo-300
                ">
                {{ $entity->entityType?->icon ?: mb_strtoupper(mb_substr($entity->name, 0, 1)) }}
            </div>
        @endif

    </a>


    <div
        :class="{
            'p-4': density === 'compact',
        
            'p-5': density === 'medium',
        
            'p-6': density === 'large'
        }">

        <div
            class="
                flex
                items-start
                justify-between
                gap-3
            ">

            <div class="min-w-0">

                <p
                    class="
                        font-mono
                        text-[9px]
                        font-black
                        text-slate-400
                    ">
                    {{ $entity->code }}
                </p>


                <a href="{{ route('entities.show', $entity) }}"
                    class="
                        mt-1
                        block
                        truncate
                        font-black
                        text-slate-900
                        hover:text-indigo-700
                    ">
                    {{ $entity->name }}
                </a>

            </div>


            <x-status-badge :status="$entity->status" />

        </div>


        <div
            class="
                mt-3
                flex
                flex-wrap
                gap-2
            ">

            <span
                class="
                    rounded-full
                    bg-indigo-50
                    px-2
                    py-1
                    text-[9px]
                    font-black
                    text-indigo-700
                ">
                {{ $entity->entityType?->name ?? 'Sin tipo' }}
            </span>


            <span
                class="
                    rounded-full
                    bg-slate-100
                    px-2
                    py-1
                    text-[9px]
                    font-bold
                    text-slate-600
                ">
                {{ $entity->visibility_label }}
            </span>

        </div>


        <p x-show="
                density !== 'compact'
            "
            class="
                mt-3
                line-clamp-2
                text-sm
                leading-6
                text-slate-500
            ">
            {{ $entity->description ?: 'Sin descripción.' }}
        </p>


        <div
            class="
                mt-4
                grid
                grid-cols-2
                gap-2
                border-t
                border-slate-100
                pt-3
            ">

            <div>

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        text-slate-400
                    ">
                    Características
                </p>

                <p
                    class="
                        mt-1
                        font-black
                        text-slate-700
                    ">
                    {{ $entity->entity_attributes_count }}
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
                    Colecciones
                </p>

                <p
                    class="
                        mt-1
                        font-black
                        text-slate-700
                    ">
                    {{ $entity->collections_count }}
                </p>

            </div>

        </div>

    </div>

</article>

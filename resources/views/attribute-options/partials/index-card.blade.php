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
        hover:border-violet-200
        hover:shadow-lg
    ">

    {{-- IMAGEN --}}
    <a href="{{ route('attribute-options.show', $option) }}"
        class="
            block
            overflow-hidden
            bg-slate-100
        "
        :class="{
            'h-28': density === 'compact',
        
            'h-36': density === 'medium',
        
            'h-48': density === 'large'
        }">

        @if ($option->image_url)
            <img src="{{ $option->image_url }}" alt="{{ $option->name }}"
                class="
                    h-full
                    w-full
                    object-cover
                    transition
                    duration-300
                    group-hover:scale-[1.02]
                ">
        @else
            <div class="
                    flex
                    h-full
                    items-center
                    justify-center
                    text-4xl
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
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    {{ $option->code }}
                </p>


                <a href="{{ route('attribute-options.show', $option) }}"
                    class="
                        mt-1
                        block
                        truncate
                        font-black
                        text-slate-900
                        hover:text-violet-700
                    ">
                    {{ $option->name }}
                </a>

            </div>


            <x-status-badge :status="$option->status" />

        </div>


        {{-- CATÁLOGO --}}
        <a href="{{ route('attributes.show', $option->attribute) }}"
            class="
                mt-3
                flex
                items-center
                gap-2
                rounded-xl
                bg-violet-50
                px-3
                py-2
                transition
                hover:bg-violet-100
            ">

            <div
                class="
                    h-7
                    w-7
                    shrink-0
                    overflow-hidden
                    rounded-lg
                    bg-white
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


            <div class="min-w-0">

                <p
                    class="
                        truncate
                        text-[10px]
                        font-black
                        text-violet-700
                    ">
                    {{ $option->attribute->name }}
                </p>


                <p
                    class="
                        font-mono
                        text-[9px]
                        text-violet-400
                    ">
                    {{ $option->attribute->code }}
                </p>

            </div>

        </a>


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
            {{ $option->description ?: 'Sin descripción.' }}
        </p>


        @if ($option->parent)
            <div
                class="
                    mt-3
                    rounded-lg
                    bg-slate-50
                    px-3
                    py-2
                ">

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-wider
                        text-slate-400
                    ">
                    Elemento superior
                </p>


                <p
                    class="
                        mt-1
                        truncate
                        text-xs
                        font-bold
                        text-slate-600
                    ">
                    ↳ {{ $option->parent->name }}
                </p>

            </div>
        @endif


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
                    Usos
                </p>


                <p
                    class="
                        mt-1
                        text-sm
                        font-black
                        text-slate-700
                    ">
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
                    Subelementos
                </p>


                <p
                    class="
                        mt-1
                        text-sm
                        font-black
                        text-slate-700
                    ">
                    {{ $option->children_count }}
                </p>

            </div>

        </div>

    </div>

</article>

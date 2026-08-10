@php

    $children = $allVersions->where('parent_entity_version_id', $node->id);

@endphp


<div class="
        relative
        py-3
    ">

    <div
        class="
            absolute
            -left-[25px]
            top-9
            h-px
            w-6
            bg-violet-100
        ">
    </div>


    <div
        class="
            flex
            flex-col
            gap-3
            rounded-2xl
            border
            border-slate-200
            bg-slate-50
            p-3
            sm:flex-row
            sm:items-center
        ">

        <img src="{{ $node->image_url }}"
            class="
                h-14
                w-14
                shrink-0
                rounded-xl
                object-cover
            ">


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

                <a href="{{ route('entity-versions.show', [$entity, $node]) }}"
                    class="
                        truncate
                        text-sm
                        font-black
                        text-slate-800
                        hover:text-violet-600
                    ">
                    {{ $node->name }}
                </a>


                @if ($node->is_default)
                    <span
                        class="
                            rounded-full
                            bg-amber-100
                            px-2
                            py-1
                            text-[7px]
                            font-black
                            text-amber-700
                        ">
                        ★
                    </span>
                @endif

            </div>


            <p
                class="
                    mt-1
                    text-[9px]
                    text-violet-500
                ">
                {{ $node->version->name }}
                ·
                {{ $node->version->kind_label }}
            </p>

        </div>


        <div class="
                flex
                gap-2
            ">

            <a href="{{ route('entity-versions.create', [
                $entity,
                'definition_mode' => 'NEW_EXCLUSIVE',
            
                'parent_entity_version_id' => $node->id,
            
                'new_version_parent_id' => $node->version_id,
            ]) }}"
                class="
                    rounded-lg
                    bg-violet-50
                    px-3
                    py-2
                    text-[9px]
                    font-black
                    text-violet-700
                ">
                + Subversión
            </a>

        </div>

    </div>


    @if ($children->isNotEmpty())

        <div
            class="
                ml-7
                border-l-2
                border-violet-100
                pl-6
            ">

            @foreach ($children as $child)
                @include('entity-versions.partials.tree-node', [
                    'node' => $child,
                
                    'allVersions' => $allVersions,
                
                    'entity' => $entity,
                
                    'depth' => $depth + 1,
                ])
            @endforeach

        </div>

    @endif

</div>

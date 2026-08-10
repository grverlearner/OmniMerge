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
            p-3
            sm:flex-row
            sm:items-center

            {{ $node->baseSetting
                ? 'border-violet-300 bg-violet-50 ring-2 ring-violet-100'
                : 'border-slate-200 bg-slate-50' }}
        ">

        <div
            class="
                relative
                h-14
                w-14
                shrink-0
            ">

            <img src="{{ $node->image_url }}" alt="{{ $node->name }}"
                class="
                    h-14
                    w-14
                    rounded-xl
                    object-cover
                ">


            @if ($node->baseSetting)
                <span
                    class="
                        absolute
                        -bottom-1
                        -right-1
                        flex
                        h-5
                        w-5
                        items-center
                        justify-center
                        rounded-full
                        bg-violet-600
                        text-[8px]
                        font-black
                        text-white
                        ring-2
                        ring-white
                    ">
                    ★
                </span>
            @endif

        </div>


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


                @if ($node->baseSetting)
                    <span
                        class="
                            rounded-full
                            bg-violet-600
                            px-2
                            py-1
                            text-[7px]
                            font-black
                            text-white
                        ">
                        ★ BASE
                    </span>
                @endif


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
                        ⚡ RESOLVER
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


            @if ($node->baseSetting)
                <p
                    class="
                        mt-1
                        text-[8px]
                        font-bold
                        text-violet-600
                    ">
                    Representación principal actual de
                    {{ $entity->name }}
                </p>
            @endif

        </div>


        <div class="
                flex
                flex-wrap
                gap-2
            ">

            @if (!$node->baseSetting)
                <form method="POST"
                    action="{{ route('entities.base-version.update', $entity) }}">

                    @csrf
                    @method('PUT')


                    <input type="hidden" name="entity_version_id" value="{{ $node->id }}">


                    <button type="submit"
                        class="
                            rounded-lg
                            bg-indigo-50
                            px-3
                            py-2
                            text-[9px]
                            font-black
                            text-indigo-700
                        ">
                        ★ Hacer Base
                    </button>

                </form>
            @endif


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

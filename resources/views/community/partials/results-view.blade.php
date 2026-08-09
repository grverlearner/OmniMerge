{{-- ========================================================= --}}
{{-- GALERÍA --}}
{{-- ========================================================= --}}

<div x-cloak x-show="
        view === 'gallery'
    "
    class="
        grid
        grid-cols-3
        gap-3
        sm:grid-cols-4
        md:grid-cols-5
        lg:grid-cols-7
        xl:grid-cols-8
        2xl:grid-cols-10
    ">

    @foreach ($items as $item)
        @include('community.partials.gallery-item', [
            'item' => $item,
            'itemType' => $itemType,
        ])
    @endforeach

</div>


{{-- ========================================================= --}}
{{-- CUADRÍCULA --}}
{{-- ========================================================= --}}

<div x-show="
        view === 'grid'
    " class="grid gap-5"
    :class="{
    
        'sm:grid-cols-2 xl:grid-cols-4': density === 'compact',
    
        'sm:grid-cols-2 xl:grid-cols-3': density === 'medium',
    
        'sm:grid-cols-2': density === 'large'
    }">

    @foreach ($items as $item)
        @include('community.partials.item-card', [
            'item' => $item,
            'itemType' => $itemType,
        ])
    @endforeach

</div>


{{-- ========================================================= --}}
{{-- MOSAICO --}}
{{-- ========================================================= --}}

<div x-cloak x-show="
        view === 'masonry'
    "
    class="
        columns-2
        gap-4
        sm:columns-3
        lg:columns-4
        xl:columns-5
    ">

    @foreach ($items as $item)
        <div class="mb-4 break-inside-avoid">

            @include('community.partials.item-card', [
                'item' => $item,
                'itemType' => $itemType,
            ])

        </div>
    @endforeach

</div>


{{-- ========================================================= --}}
{{-- LISTA --}}
{{-- ========================================================= --}}

<div x-cloak x-show="
        view === 'list'
    " class="space-y-3">

    @foreach ($items as $item)
        @include('community.partials.list-item', [
            'item' => $item,
            'itemType' => $itemType,
        ])
    @endforeach

</div>


{{-- ========================================================= --}}
{{-- TABLA --}}
{{-- ========================================================= --}}

<div x-cloak x-show="
        view === 'table'
    "
    class="
        overflow-hidden
        rounded-2xl
        border
        border-slate-200
        bg-white
    ">

    <div class="overflow-x-auto">

        <table class="min-w-full">

            <thead class="bg-slate-50">

                <tr>

                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase text-slate-400">
                        Recurso
                    </th>

                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase text-slate-400">
                        Tipo / contexto
                    </th>

                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase text-slate-400">
                        Creador
                    </th>

                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase text-slate-400">
                        Abrir
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-slate-100">

                @foreach ($items as $item)
                    @php

                        if ($itemType === 'entity') {
                            $name = $item->name;
                            $context = $item->entityType?->name ?? 'Sin tipo';
                            $creatorName = '@' . $item->creator->username;
                            $url = route('community.entities.show', $item);
                        } elseif ($itemType === 'collection') {
                            $name = $item->name;
                            $context = $item->entities_count . ' entidades';
                            $creatorName = '@' . $item->creator->username;
                            $url = route('community.collections.show', $item);
                        } elseif ($itemType === 'attribute') {
                            $name = $item->name;
                            $context = $item->data_type_label;
                            $creatorName = '@' . $item->creator->username;
                            $url = route('community.attributes.show', $item);
                        } elseif ($itemType === 'catalog') {
                            $name = $item->name;
                            $context = $item->attribute?->name ?? 'Catálogo';
                            $creatorName = '@' . $item->user->username;
                            $url = route('community.catalogs.show', $item);
                        } else {
                            $name = $item->name;
                            $context = '@' . $item->username;
                            $creatorName = 'Creador';
                            $url = route('community.creators.show', $item->username);
                        }

                    @endphp


                    <tr class="hover:bg-slate-50">

                        <td class="px-4 py-3">

                            <p class="font-bold text-slate-800">
                                {{ $name }}
                            </p>

                        </td>


                        <td class="px-4 py-3 text-sm text-slate-500">
                            {{ $context }}
                        </td>


                        <td class="px-4 py-3 text-sm text-slate-500">
                            {{ $creatorName }}
                        </td>


                        <td class="px-4 py-3">

                            <a href="{{ $url }}" class="text-xs font-black text-indigo-600">
                                Abrir →
                            </a>

                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>

    </div>

</div>
